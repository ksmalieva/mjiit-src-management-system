<?php
// auth/register.php - Integrated registration page with your design
require_once '../config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $role = $_POST['role'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (!$full_name || !$email || !$password) {
        $error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif (email_exists($email)) {
        $error = "Email already registered";
    } else {
        $username = strtolower(explode('@', $email)[0]);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $user_id = db_insert('users', [
            'username' => $username,
            'email' => $email,
            'password_hash' => $password_hash,
            'full_name' => $full_name,
            'role' => strtolower($role),
            'status' => 'pending'
        ]);
        
        if ($user_id) {
            header("Location: login.php?registered=1");
            exit();
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Registration | The Academic Nexus</title>

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

    <link rel="stylesheet" href="styles/register.css" />
    
    <style>
        /* Additional styles for error/success messages */
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
        
        .error-message .material-symbols-outlined {
            font-size: 1.25rem;
        }
        
        /* Ensure radio buttons work properly */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }
        
        .role-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .role-option input[type="radio"]:checked + .role-box {
            background-color: var(--secondary-container);
            color: var(--on-secondary-container);
            border: 2px solid var(--secondary);
        }
        
        .role-box {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .role-option:hover .role-box {
            background-color: var(--surface-container-high);
        }
    </style>
</head>
<body>
    <nav class="navbar">
      <div class="nav-brand">
        <img src="../logo/SRC_logo.png" alt="MJIIT Logo" class="nav-logo" />
      </div>
      <div class="nav-links">
        <a href="../public/index.php">MJIIT Official</a>
        <a href="#">Help Center</a>
      </div>
    </nav>

    <main class="main-content">
      <div class="form-card">
        <div class="card-body">
          <div class="card-header">
            <h1>Join the Network</h1>
            <p>
              Register to access the Sangaku Renkei Center Management System and
              collaborate with global academic leaders.
            </p>
          </div>

          <?php if ($error): ?>
            <div class="error-message">
              <span class="material-symbols-outlined">error</span>
              <span><?php echo htmlspecialchars($error); ?></span>
            </div>
          <?php endif; ?>

          <form
            id="registrationForm"
            class="registration-form"
            method="POST"
            action=""
          >
            <div class="input-group">
              <label for="full_name">Full Name</label>
              <input 
                type="text" 
                id="full_name" 
                name="full_name" 
                value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                required 
              />
            </div>

            <div class="input-group">
              <label for="email">University Email</label>
              <input
                type="email"
                id="email"
                name="email"
                placeholder="example@utm.my"
                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                required
              />
            </div>

            <div class="input-group">
              <label>Role Selection</label>
              <div class="role-grid">
                <label class="role-option">
                  <input
                    type="radio"
                    name="role"
                    value="Admin"
                    class="sr-only"
                    <?php echo (($_POST['role'] ?? '') == 'Admin') ? 'checked' : ''; ?>
                  />
                  <div class="role-box">Admin</div>
                </label>
                <label class="role-option">
                  <input
                    type="radio"
                    name="role"
                    value="Staff"
                    class="sr-only"
                    <?php echo (!isset($_POST['role']) || ($_POST['role'] ?? '') == 'Staff') ? 'checked' : ''; ?>
                  />
                  <div class="role-box">Staff</div>
                </label>
                <label class="role-option">
                  <input
                    type="radio"
                    name="role"
                    value="Researcher"
                    class="sr-only"
                    <?php echo (($_POST['role'] ?? '') == 'Researcher') ? 'checked' : ''; ?>
                  />
                  <div class="role-box">Researcher</div>
                </label>
              </div>
            </div>

            <div class="password-grid">
              <div class="input-group">
                <label for="password">Password</label>
                <input
                  type="password"
                  id="password"
                  name="password"
                  placeholder="At least 8 characters"
                  required
                  minlength="6"
                />
              </div>
              <div class="input-group">
                <label for="confirm_password">Confirm Password</label>
                <input
                  type="password"
                  id="confirm_password"
                  name="confirm_password"
                  required
                />
              </div>
            </div>

            <div class="submit-section">
              <button type="submit" class="btn-primary">
                Create Account
                <span class="material-symbols-outlined">arrow_forward</span>
              </button>
            </div>

            <div class="divider">
              <span>OR</span>
            </div>

            <div class="login-prompt">
              <p>Already have an account? <a href="login.php">Sign In</a></p>
            </div>
          </form>
        </div>

        <div class="card-footer">
          <div class="security-badge">
            <span class="material-symbols-outlined filled-icon"
              >verified_user</span
            >
            <span>SSO Encryption Active</span>
          </div>
          <div class="legal-links">
            <a href="#">Privacy</a>
            <a href="#">Terms</a>
            <a href="#">Support</a>
          </div>
        </div>
      </div>
    </main>

    <footer class="global-footer">
      <div class="footer-brand">Sangaku Renkei Center</div>
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
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
        
        // Role selection styling enhancement
        document.querySelectorAll('.role-option').forEach(option => {
            const radio = option.querySelector('input[type="radio"]');
            const box = option.querySelector('.role-box');
            
            if (radio.checked) {
                box.style.backgroundColor = 'var(--secondary-container)';
                box.style.color = 'var(--on-secondary-container)';
                box.style.border = '2px solid var(--secondary)';
            }
            
            option.addEventListener('click', function() {
                document.querySelectorAll('.role-option .role-box').forEach(b => {
                    b.style.backgroundColor = '';
                    b.style.color = '';
                    b.style.border = '';
                });
                box.style.backgroundColor = 'var(--secondary-container)';
                box.style.color = 'var(--on-secondary-container)';
                box.style.border = '2px solid var(--secondary)';
            });
        });
    </script>
</body>
</html>