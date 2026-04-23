<?php
// auth/login.php - Fixed redirect paths
require_once '../config.php';

if (isset($_SESSION['user_id'])) {
    // FIXED: Correct paths from auth folder
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    } elseif ($_SESSION['user_role'] === 'staff') {
        header('Location: ../staff/dashboard.php');
    } else {
        header('Location: ../public/index.php');
    }
    exit();
}

$error = '';
$success = isset($_GET['registered']) ? 'Registration successful! Please wait for admin approval.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        $user = db_fetch_one("SELECT * FROM users WHERE email = ?", [$email]);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] !== 'active') {
                $error = 'Your account is pending approval. Please contact administrator.';
            } else {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['full_name'];
                
                // FIXED: Correct redirect paths (no extra ../ and no SITE_URL)
                if ($user['role'] === 'admin') {
                    header('Location: ../admin/dashboard.php');
                } elseif ($user['role'] === 'staff') {
                    header('Location: ../staff/dashboard.php');
                } else {
                    header('Location: ../public/index.php');
                }
                exit();
            }
        } else {
            $error = 'Invalid email or password';
        }
    } else {
        $error = 'Please enter email and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SRC Public - Authentication</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />

    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="styles/login.css" />
    
    <style>
        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-left: 3px solid #dc2626;
        }
        
        .success-message {
            background: #d1fae5;
            color: #059669;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-left: 3px solid #059669;
        }
        
        .error-message .material-symbols-outlined,
        .success-message .material-symbols-outlined {
            font-size: 1.25rem;
        }
    </style>
</head>
<body>
    <header class="header">
      <div class="header-nav">
        <a href="../public/index.php">About</a>
        <a href="../public/news.php">News</a>
      </div>
    </header>

    <main class="main-content">
      <div class="auth-wrapper">
        <div class="toast-container">
          <div class="toast">
            <span class="material-symbols-outlined icon-success"
              >check_circle</span
            >
            <p class="toast-text">
              Systems are online. Please authenticate to continue.
            </p>
          </div>
        </div>

        <div class="auth-card">
          <div class="auth-card-header">
            <img alt="SRC Logo" class="auth-logo" src="../logo/SRC_logo.png" />
            <h1 class="auth-title">The Academic Nexus</h1>
            <p class="auth-subtitle">
              MJIIT Sangaku Renkei Center Management System
            </p>
          </div>

          <nav class="auth-tabs">
            <button class="auth-tab active">Login</button>
            <button class="auth-tab" onclick="goToRegistr()">Register</button>
            <button class="auth-tab" onclick="goToRecovery()">Recovery</button>
          </nav>

          <div class="auth-body">
            <?php if ($error): ?>
            <div class="error-message">
              <span class="material-symbols-outlined">error</span>
              <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="success-message">
              <span class="material-symbols-outlined">check_circle</span>
              <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <?php endif; ?>
            
            <form class="auth-form" method="POST" action="">
              <div class="form-group">
                <label class="form-label">Email Address</label>
                <div class="input-wrapper">
                  <span class="material-symbols-outlined input-icon-left"
                    >alternate_email</span
                  >
                  <input
                    class="form-input"
                    placeholder="name@university.edu"
                    type="email"
                    name="email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                  />
                </div>
              </div>

              <div class="form-group">
                <div class="form-label-row">
                  <label class="form-label">Password</label>
                  <a class="forgot-link" href="forgot-password.php">Forgot?</a>
                </div>
                <div class="input-wrapper">
                  <span class="material-symbols-outlined input-icon-left"
                    >lock</span
                  >
                  <input
                    class="form-input form-input-password"
                    placeholder="••••••••"
                    type="password"
                    name="password"
                    id="password"
                    required
                  />
                  <button class="input-icon-right" type="button" onclick="togglePassword()">
                    <span class="material-symbols-outlined" id="visibilityIcon">visibility</span>
                  </button>
                </div>
              </div>

              <div class="checkbox-group">
                <input class="checkbox" id="remember" type="checkbox" name="remember" />
                <label class="checkbox-label" for="remember"
                  >Remember this device for 30 days</label
                >
              </div>

              <button class="btn-submit" type="submit">
                <span>Sign In to Nexus</span>
                <span class="material-symbols-outlined icon-small"
                  >arrow_forward</span
                >
              </button>
            </form>
          </div>
        </div>

        <div class="info-grid">
          <div class="info-card">
            <span class="material-symbols-outlined info-icon">hub</span>
            <span class="info-title">Collaborations</span>
            <span class="info-desc">500+ Active</span>
          </div>
          <div class="info-card">
            <span class="material-symbols-outlined info-icon">science</span>
            <span class="info-title">Researchers</span>
            <span class="info-desc">Top Tier</span>
          </div>
          <div class="info-card">
            <span class="material-symbols-outlined info-icon">security</span>
            <span class="info-title">Security</span>
            <span class="info-desc">ISO Certified</span>
          </div>
        </div>
      </div>
    </main>

    <footer class="footer">
      <div class="footer-left">
        <span class="footer-brand">SRC Public</span>
        <span class="footer-copy"
          >© 2024 MJIIT UTM Sangaku Renkei Center. All rights reserved.</span
        >
      </div>
      <div class="footer-links">
        <a href="#">Privacy Policy</a>
        <a href="#">Contact Support</a>
        <a href="#">MJIIT Official</a>
      </div>
    </footer>
    
    <script>
      function goToRegistr() {
        window.location.href = "register.php";
      }

      function goToRecovery() {
        window.location.href = "forgot-password.php";
      }
      
      function togglePassword() {
        const passwordInput = document.getElementById('password');
        const visibilityIcon = document.getElementById('visibilityIcon');
        
        if (passwordInput.type === 'password') {
          passwordInput.type = 'text';
          visibilityIcon.textContent = 'visibility_off';
        } else {
          passwordInput.type = 'password';
          visibilityIcon.textContent = 'visibility';
        }
      }
      
      setTimeout(function() {
        const toast = document.querySelector('.toast');
        if (toast) {
          toast.style.opacity = '0';
          setTimeout(function() {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
          }, 300);
        }
      }, 5000);
    </script>
  </body>
</html>