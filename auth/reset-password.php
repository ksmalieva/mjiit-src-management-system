<?php
// auth/reset-password.php - Password reset form with token
require_once '../config.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (!$token) {
    header('Location: forgot-password.php');
    exit();
}

// Verify token
$user = db_fetch_one(
    "SELECT user_id FROM users WHERE reset_token = ? AND reset_expires > NOW()",
    [$token]
);

if (!$user) {
    $error = "Invalid or expired reset link. Please request a new one.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        db_update('users',
            [
                'password_hash' => $password_hash,
                'reset_token' => null,
                'reset_expires' => null
            ],
            'user_id = ?',
            [$user['user_id']]
        );
        
        log_activity($user['user_id'], 'password_reset', "Password reset successfully");
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Password | The Academic Nexus</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="styles/forgotpassword.css" />
    
    <style>
        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-left: 3px solid #dc2626;
        }
        
        .success-message {
            background: #d1fae5;
            color: #059669;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
            border-left: 3px solid #059669;
        }
        
        .success-message .material-symbols-outlined {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
        
        .success-message a {
            display: inline-block;
            margin-top: 1rem;
            color: #059669;
            text-decoration: underline;
        }
        
        .error-message .material-symbols-outlined {
            font-size: 1.25rem;
        }
        
        .password-requirements {
            font-size: 0.75rem;
            color: var(--outline);
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <header class="navbar minimal-nav">
      <div class="nav-brand">
        <img
          src="../logo/SRC_logo.png"
          alt="The Academic Nexus Logo"
          class="nav-logo"
        />
      </div>
    </header>

    <main class="main-content relative-bg">
      <div class="bg-blur blur-top-right"></div>
      <div class="bg-blur blur-bottom-left"></div>

      <div class="form-card recovery-card">
        <div class="card-body">
          <div class="recovery-header">
            <div class="icon-box">
              <span class="material-symbols-outlined">lock_reset</span>
            </div>
            <h1>Create New Password</h1>
            <p>
              Please enter your new password below.
            </p>
          </div>

          <?php if ($error): ?>
            <div class="error-message">
              <span class="material-symbols-outlined">error</span>
              <span><?php echo htmlspecialchars($error); ?></span>
            </div>
          <?php endif; ?>

          <?php if ($success === true): ?>
            <div class="success-message">
              <span class="material-symbols-outlined">check_circle</span>
              <h3 style="margin-bottom: 0.5rem;">Password Reset Successful!</h3>
              <p>Your password has been successfully updated.</p>
              <a href="login.php">Click here to login</a>
            </div>
          <?php else: ?>
            <form class="registration-form" method="POST" action="">
              <div class="input-group">
                <label for="password">New Password</label>
                <div class="input-with-icon">
                  <span class="material-symbols-outlined left-icon">lock</span>
                  <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    required
                    minlength="6
                  />
                  <div class="focus-line"></div>
                </div>
                <div class="password-requirements">
                  Minimum 6 characters
                </div>
              </div>

              <div class="input-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-with-icon">
                  <span class="material-symbols-outlined left-icon">lock</span>
                  <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="••••••••"
                    required
                  />
                  <div class="focus-line"></div>
                </div>
              </div>

              <div class="submit-section">
                <button type="submit" class="btn-primary">
                  <span>Reset Password</span>
                  <span class="material-symbols-outlined">arrow_forward</span>
                </button>
              </div>
            </form>
          <?php endif; ?>

          <div class="back-link-container">
            <a href="login.php" class="back-link">
              <span class="material-symbols-outlined">arrow_back</span>
              <span>Back to login</span>
            </a>
          </div>
        </div>
      </div>
    </main>

    <footer class="global-footer no-border">
      <div class="footer-links">
        <a href="#">Privacy Policy</a>
        <a href="#">Terms of Service</a>
        <a href="#">Accessibility</a>
        <a href="#">MJIIT Official</a>
      </div>
      <p class="copyright">
        © 2024 Sangaku Renkei Center. All rights reserved.
      </p>
    </footer>

    <script>
        // Password match validation
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const confirm = document.getElementById('confirm_password').value;
                
                if (password !== confirm) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                }
            });
        }
    </script>
</body>
</html>