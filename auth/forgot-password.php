<?php
// auth/forgot-password.php - Integrated password recovery page
require_once '../config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    
    if ($email) {
        $user = db_fetch_one("SELECT user_id, email FROM users WHERE email = ?", [$email]);
        
        if ($user) {
            // Generate reset token
            $token = generate_token();
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            db_update('users', 
                ['reset_token' => $token, 'reset_expires' => $expires],
                'user_id = ?',
                [$user['user_id']]
            );
            
            // Build reset link
            $reset_link = SITE_URL . "auth/reset-password.php?token=" . $token;
            
            // For development, show the reset link
            // In production, you would send an email
            $success = "Password reset link has been sent to your email.<br>
                       <small style='display: inline-block; margin-top: 0.5rem;'>Link: <a href='$reset_link' style='color: #006a6a;'>$reset_link</a></small>";
            
            log_activity($user['user_id'], 'password_reset_request', "Password reset requested");
        } else {
            // Don't reveal if email exists or not for security
            $success = "If an account exists with that email, you will receive a reset link.";
        }
    } else {
        $error = "Please enter a valid email address";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Account Recovery | The Academic Nexus</title>

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
        /* Additional styles for messages */
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
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            border-left: 3px solid #059669;
        }
        
        .success-message .material-symbols-outlined {
            font-size: 1.25rem;
        }
        
        .error-message .material-symbols-outlined {
            font-size: 1.25rem;
        }
        
        .success-message a {
            color: #059669;
            text-decoration: underline;
        }
        
        .success-message a:hover {
            color: #047857;
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
            <h1>Forgot Password?</h1>
            <p>
              Enter your email address to receive a password reset link. We will
              help you get back to managing your intellectual capital.
            </p>
          </div>

          <?php if ($error): ?>
            <div class="error-message">
              <span class="material-symbols-outlined">error</span>
              <span><?php echo htmlspecialchars($error); ?></span>
            </div>
          <?php endif; ?>

          <?php if ($success): ?>
            <div class="success-message">
              <span class="material-symbols-outlined">check_circle</span>
              <span><?php echo $success; ?></span>
            </div>
          <?php endif; ?>

          <?php if (!$success): ?>
          <form class="registration-form" method="POST" action="">
            <div class="input-group">
              <label for="email">Email Address</label>
              <div class="input-with-icon">
                <span class="material-symbols-outlined left-icon">mail</span>
                <input
                  type="email"
                  id="email"
                  name="email"
                  placeholder="name@university.edu"
                  value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                  required
                />
                <div class="focus-line"></div>
              </div>
            </div>

            <div class="submit-section">
              <button type="submit" class="btn-primary">
                <span>Send Reset Link</span>
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
</body>
</html>