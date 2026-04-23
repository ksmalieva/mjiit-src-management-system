<?php
// staff/settings.php - System Settings page
require_once dirname(__DIR__) . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'auth/login.php');
    exit();
}

$user_role = $_SESSION['user_role'];
if ($user_role != 'staff' && $user_role != 'admin') {
    die('Access denied. Staff or Admin privileges required.');
}

$success = '';
$error = '';

// Get current user info
$current_user = get_user($_SESSION['user_id']);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($_POST['full_name']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $bio = sanitize($_POST['bio']);
    $academic_title = sanitize($_POST['academic_title']);
    $department = sanitize($_POST['department']);
    
    if ($full_name && $email) {
        $result = db_update('users', [
            'full_name' => $full_name,
            'email' => $email
        ], 'user_id = ?', [$_SESSION['user_id']]);
        
        if ($result !== false) {
            $_SESSION['user_name'] = $full_name;
            log_activity($_SESSION['user_id'], 'profile_updated', "Updated profile information");
            $success = "Profile updated successfully!";
        } else {
            $error = "Failed to update profile";
        }
    } else {
        $error = "Please fill all required fields";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (password_verify($current_password, $current_user['password_hash'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $result = db_update('users', [
                    'password_hash' => $new_hash
                ], 'user_id = ?', [$_SESSION['user_id']]);
                
                if ($result !== false) {
                    log_activity($_SESSION['user_id'], 'password_changed', "Changed password");
                    $success = "Password changed successfully!";
                } else {
                    $error = "Failed to change password";
                }
            } else {
                $error = "New password must be at least 6 characters";
            }
        } else {
            $error = "New passwords do not match";
        }
    } else {
        $error = "Current password is incorrect";
    }
}

// Handle notification preferences
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notifications'])) {
    $project_milestones = isset($_POST['project_milestones']) ? 1 : 0;
    $booking_approvals = isset($_POST['booking_approvals']) ? 1 : 0;
    $weekly_summary = isset($_POST['weekly_summary']) ? 1 : 0;
    
    // Store preferences in session or database
    $_SESSION['notifications'] = [
        'project_milestones' => $project_milestones,
        'booking_approvals' => $booking_approvals,
        'weekly_summary' => $weekly_summary
    ];
    
    log_activity($_SESSION['user_id'], 'notification_preferences_updated', "Updated notification settings");
    $success = "Notification preferences updated!";
}

// Handle two-factor auth toggle
$two_factor_enabled = isset($_SESSION['two_factor']) ? $_SESSION['two_factor'] : false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_2fa'])) {
    $_SESSION['two_factor'] = !$two_factor_enabled;
    $two_factor_enabled = $_SESSION['two_factor'];
    $success = $two_factor_enabled ? "Two-factor authentication enabled!" : "Two-factor authentication disabled!";
    log_activity($_SESSION['user_id'], '2fa_toggled', "2FA set to: " . ($two_factor_enabled ? 'enabled' : 'disabled'));
}

// Get notification preferences from session
$notif_project = $_SESSION['notifications']['project_milestones'] ?? 1;
$notif_booking = $_SESSION['notifications']['booking_approvals'] ?? 1;
$notif_weekly = $_SESSION['notifications']['weekly_summary'] ?? 0;
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>System Settings | The Academic Nexus</title>

    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="styles/settings.css" />

    <style>
      html {
        scroll-behavior: smooth;
        scroll-padding-top: 6rem;
      }
      
      .alert-success {
        background-color: #d1fae5;
        color: #059669;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-left: 4px solid #059669;
      }
      
      .alert-error {
        background-color: #fee2e2;
        color: #dc2626;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-left: 4px solid #dc2626;
      }
      
      .alert-close {
        margin-left: auto;
        background: none;
        border: none;
        font-size: 1.25rem;
        cursor: pointer;
        color: inherit;
      }
      
      .password-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
      }
      
      .password-modal-content {
        background: white;
        border-radius: 1rem;
        padding: 2rem;
        width: 90%;
        max-width: 450px;
      }
      
      .password-modal-content .form-group {
        margin-bottom: 1rem;
      }
      
      .password-modal-content input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--surface-container-high);
        border-radius: 0.5rem;
      }
    </style>
</head>
<body>
    <header class="app-header">
      <div class="header-left">
        <a class="back-btn" href="dashboard.php">
          <span class="material-symbols-outlined icon-back">arrow_back</span>
          <span class="back-text">Back to Dashboard</span>
        </a>
      </div>
      <div class="header-right">
        <button class="icon-btn">
          <span class="material-symbols-outlined">notifications</span>
        </button>
        <button class="icon-btn">
          <span class="material-symbols-outlined">help_outline</span>
        </button>
        <div class="avatar-container">
          <div style="width: 100%; height: 100%; background: var(--primary); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
            <?php echo substr($_SESSION['user_name'], 0, 2); ?>
          </div>
        </div>
      </div>
    </header>

    <main class="main-content">
      <div class="content-wrapper">
        <div class="page-header">
          <h2 class="page-title">System Settings</h2>
          <p class="page-subtitle">
            Manage your professional profile, security protocols, and platform
            preferences.
          </p>
        </div>

        <?php if ($success): ?>
        <div class="alert-success">
          <span class="material-symbols-outlined">check_circle</span>
          <span><?php echo $success; ?></span>
          <button class="alert-close" onclick="this.parentElement.style.display='none'">×</button>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert-error">
          <span class="material-symbols-outlined">error</span>
          <span><?php echo $error; ?></span>
          <button class="alert-close" onclick="this.parentElement.style.display='none'">×</button>
        </div>
        <?php endif; ?>

        <div class="nav-tabs">
          <a href="#profile" class="tab-btn active">Profile</a>
          <a href="#security" class="tab-btn">Security</a>
          <a href="#notifications" class="tab-btn">Notifications</a>
          <a href="#systemconfig" class="tab-btn">System Configuration</a>
        </div>

        <div class="bento-grid">
          <!-- Profile Section -->
          <section class="card col-lg-8">
            <form method="POST" action="">
              <div class="card-header">
                <div>
                  <h3 id="profile" class="card-title">User Information</h3>
                  <p class="card-subtitle">
                    Update your public profile and contact details.
                  </p>
                </div>
                <button type="submit" name="update_profile" class="btn-primary">Save Changes</button>
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">Full Name</label>
                  <input class="form-input border-highlight" type="text" name="full_name" value="<?php echo htmlspecialchars($current_user['full_name']); ?>" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Email Address</label>
                  <input class="form-input" type="email" name="email" value="<?php echo htmlspecialchars($current_user['email']); ?>" required />
                </div>
                <div class="form-group col-span-2">
                  <label class="form-label">Professional Bio</label>
                  <textarea class="form-input" name="bio" rows="4" placeholder="Write your message here..."><?php echo htmlspecialchars($current_user['bio'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                  <label class="form-label">Role</label>
                  <select class="form-input" name="academic_title" disabled>
                    <option><?php echo ucfirst($current_user['role']); ?></option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Department</label>
                  <input class="form-input" type="text" name="department" value="<?php echo htmlspecialchars($current_user['department'] ?? 'Innovation & Technology Transfer'); ?>" />
                </div>
              </div>
            </form>
          </section>

          <!-- Profile Photo Section -->
          <section class="card col-lg-4 align-center text-center">
            <div class="profile-photo-wrapper">
              <div class="profile-photo-frame">
                <div style="width: 100%; height: 100%; background: var(--primary); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: bold;">
                  <?php echo substr($current_user['full_name'], 0, 2); ?>
                </div>
              </div>
              <button class="btn-edit-photo" onclick="alert('Photo upload feature coming soon')">
                <span class="material-symbols-outlined">edit</span>
              </button>
            </div>
            <h4 class="photo-title">Profile Photo</h4>
            <p class="photo-subtitle">JPG, GIF or PNG. Max size of 2MB.</p>
            <button class="btn-text-primary mt-6" onclick="alert('Remove photo feature coming soon')">Remove Photo</button>
          </section>

          <!-- Security Section -->
          <section class="card col-lg-6">
            <div class="card-header">
              <h3 id="security" class="card-title">Security</h3>
              <span class="badge-secure">High Protection</span>
            </div>

            <div class="list-group">
              <div class="list-item">
                <div class="item-left">
                  <div class="item-icon-box">
                    <span class="material-symbols-outlined">lock_reset</span>
                  </div>
                  <div>
                    <p class="item-title">Update Password</p>
                    <p class="item-desc">Last changed <?php echo date('M Y', strtotime($current_user['updated_at'])); ?></p>
                  </div>
                </div>
                <button class="btn-text-primary px-4 py-2 hover-bg" onclick="openPasswordModal()">Change</button>
              </div>

              <div class="list-item">
                <div class="item-left">
                  <div class="item-icon-box">
                    <span class="material-symbols-outlined">verified_user</span>
                  </div>
                  <div>
                    <p class="item-title">Two-Factor Auth</p>
                    <p class="item-desc">Recommended for staff accounts</p>
                  </div>
                </div>
                <form method="POST" style="display: inline;">
                  <label class="toggle-switch">
                    <input type="checkbox" name="toggle_2fa" onchange="this.form.submit()" <?php echo $two_factor_enabled ? 'checked' : ''; ?> />
                    <span class="toggle-slider"></span>
                  </label>
                </form>
              </div>
            </div>
          </section>

          <!-- Notification Preferences Section -->
          <section class="card col-lg-6">
            <h3 id="notifications" class="card-title mb-6">Notification Preferences</h3>
            <form method="POST" action="">
              <div class="list-group list-group-simple">
                <div class="list-item-simple">
                  <div>
                    <p class="item-title-sm">Project Milestones</p>
                    <p class="item-desc">Alerts when key research stages are reached</p>
                  </div>
                  <label class="toggle-switch">
                    <input type="checkbox" name="project_milestones" <?php echo $notif_project ? 'checked' : ''; ?> />
                    <span class="toggle-slider"></span>
                  </label>
                </div>

                <div class="list-item-simple border-top">
                  <div>
                    <p class="item-title-sm">Booking Approvals</p>
                    <p class="item-desc">Real-time alerts for space reservation requests</p>
                  </div>
                  <label class="toggle-switch">
                    <input type="checkbox" name="booking_approvals" <?php echo $notif_booking ? 'checked' : ''; ?> />
                    <span class="toggle-slider"></span>
                  </label>
                </div>

                <div class="list-item-simple border-top">
                  <div>
                    <p class="item-title-sm">Weekly SRC Summary</p>
                    <p class="item-desc">Condensed report of weekly activity</p>
                  </div>
                  <label class="toggle-switch">
                    <input type="checkbox" name="weekly_summary" <?php echo $notif_weekly ? 'checked' : ''; ?> />
                    <span class="toggle-slider"></span>
                  </label>
                </div>
              </div>
              <div style="margin-top: 1.5rem;">
                <button type="submit" name="update_notifications" class="btn-primary">Save Preferences</button>
              </div>
            </form>
          </section>

          <!-- System Configuration Section -->
          <section class="card col-lg-12">
            <div class="config-section">
              <div>
                <h3 id="systemconfig" class="card-title">System Configuration</h3>
                <p class="item-desc mt-1">
                  Configure advanced platform behaviors and external API integrations.
                </p>
              </div>
              <div class="action-buttons">
                <button class="btn-secondary" onclick="exportData()">Export Account Data</button>
                <button class="btn-danger" onclick="deactivateAccount()">
                  <span class="material-symbols-outlined icon-sm">delete</span>
                  Deactivate Account
                </button>
              </div>
            </div>
          </section>
        </div>
      </div>
    </main>

    <!-- Password Change Modal -->
    <div id="passwordModal" class="password-modal">
      <div class="password-modal-content">
        <h3 style="margin-bottom: 1rem;">Change Password</h3>
        <form method="POST" action="">
          <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" required />
          </div>
          <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" minlength="6" required />
          </div>
          <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required />
          </div>
          <div style="display: flex; gap: 1rem; margin-top: 1rem;">
            <button type="submit" name="change_password" class="btn-primary">Update Password</button>
            <button type="button" class="btn-secondary" onclick="closePasswordModal()">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <script>
      function openPasswordModal() {
        document.getElementById('passwordModal').style.display = 'flex';
      }
      
      function closePasswordModal() {
        document.getElementById('passwordModal').style.display = 'none';
      }
      
      function exportData() {
        alert('Export account data feature coming soon.');
      }
      
      function deactivateAccount() {
        if (confirm('Are you sure you want to deactivate your account? This action can be reversed by an administrator.')) {
          alert('Account deactivation request submitted. Please contact administrator.');
        }
      }
      
      // Close modal when clicking outside
      document.getElementById('passwordModal').addEventListener('click', function(e) {
        if (e.target === this) {
          closePasswordModal();
        }
      });
      
      // Smooth scroll for anchor links
      document.querySelectorAll('.tab-btn').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
          e.preventDefault();
          const targetId = this.getAttribute('href');
          const targetElement = document.querySelector(targetId);
          if (targetElement) {
            targetElement.scrollIntoView({ behavior: 'smooth' });
          }
        });
      });
    </script>
</body>
</html>