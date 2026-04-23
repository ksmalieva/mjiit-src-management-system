<?php
// admin/settings.php - System Settings
require_once '../config.php';
require_admin();

$success = '';
$error = '';

// Handle General Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_general'])) {
    $platform_name = sanitize($_POST['platform_name']);
    $support_email = filter_var($_POST['support_email'], FILTER_VALIDATE_EMAIL);
    $timezone = sanitize($_POST['timezone']);
    $language = sanitize($_POST['language']);
    
    if ($platform_name && $support_email) {
        // Update settings in database or config file
        // For now, we'll store in a settings table or session
        $_SESSION['platform_name'] = $platform_name;
        $_SESSION['support_email'] = $support_email;
        
        // You can also update a settings table if created
        log_activity($_SESSION['user_id'], 'settings_updated', "Updated general settings");
        $success = "General settings updated successfully!";
    } else {
        $error = "Please fill all required fields";
    }
}

// Handle Password Policy Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_security'])) {
    $require_special = isset($_POST['require_special']) ? 1 : 0;
    $force_reset = isset($_POST['force_reset']) ? 1 : 0;
    $min_length = (int)$_POST['min_length'];
    
    // Store in session or database
    $_SESSION['password_policy'] = [
        'require_special' => $require_special,
        'force_reset' => $force_reset,
        'min_length' => $min_length
    ];
    
    log_activity($_SESSION['user_id'], 'security_settings_updated', "Updated security settings");
    $success = "Security settings updated successfully!";
}

// Get current user for profile display
$current_user = get_user($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>System Settings | The Academic Nexus</title>

    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;700;800&family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="styles/settings.css" />
    
    <style>
        .logout-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
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
        
        .settings-panel {
            display: block;
        }
        
        .settings-panel.hidden {
            display: none;
        }
        
        .current-value {
            font-size: 0.75rem;
            color: var(--slate-400);
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <aside class="sidebar">
      <div class="brand-header">
        <img
          src="../logo/SRC_logo.png"
          alt="Academic Nexus Logo"
          class="brand-logo"
        />
      </div>

      <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item">
          <span class="material-symbols-outlined">dashboard</span>
          <span>Dashboard</span>
        </a>

        <a href="../admin/role-management.php" class="nav-item">
          <span class="material-symbols-outlined">manage_accounts</span>
          <span>Role Management</span>
        </a>

        <a href="../staff/collaborations.php" class="nav-item">
          <span class="material-symbols-outlined">handshake</span>
          <span>Collaborations</span>
        </a>

        <a href="event.php" class="nav-item">
          <span class="material-symbols-outlined">event</span>
          <span>Events</span>
        </a>
        <a href="partnerships.php" class="nav-item">
          <span class="material-symbols-outlined filled">corporate_fare</span>
          <span>Partners</span>
        </a>
        <a href="settings.php" class="nav-item active">
          <span class="material-symbols-outlined">settings</span>
          <span>Settings</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <a href="#" class="nav-item">
          <span class="material-symbols-outlined">contact_support</span>
          Help Center
        </a>
        <a href="../auth/logout.php" class="nav-item danger logout-link">
          <span class="material-symbols-outlined">logout</span>
          Logout
        </a>
      </div>
    </aside>

    <main class="main-content">
      <header class="topbar">
        <div class="topbar-left">
          <h1>The Academic Nexus</h1>
          <div class="search-wrapper">
            <span class="material-symbols-outlined search-icon">search</span>
            <input
              type="text"
              class="search-input"
              id="globalSearch"
              placeholder="Global search..."
              onkeyup="searchSettings()"
            />
          </div>
        </div>

        <div class="topbar-right">
          <button class="icon-btn">
            <span class="material-symbols-outlined">notifications</span>
          </button>
          <button class="icon-btn">
            <span class="material-symbols-outlined">help_outline</span>
          </button>
          <div class="user-avatar"><?php echo substr($_SESSION['user_name'], 0, 2); ?></div>
        </div>
      </header>

      <div class="settings-container">
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

        <div class="settings-header">
          <h2>System Settings</h2>
          <p>
            Configure global parameters and security protocols for the SRC
            platform.
          </p>
        </div>

        <div class="settings-layout">
          <nav class="settings-menu">
            <button class="menu-btn active" onclick="showPanel('general')">
              <span class="material-symbols-outlined">settings_suggest</span>
              General Settings
            </button>
            <button class="menu-btn" onclick="showPanel('security')">
              <span class="material-symbols-outlined">admin_panel_settings</span>
              Security & Privacy
            </button>
            <button class="menu-btn" onclick="showPanel('permissions')">
              <span class="material-symbols-outlined">group_work</span>
              User Permissions
            </button>
            <button class="menu-btn" onclick="showPanel('integrations')">
              <span class="material-symbols-outlined">hub</span>
              Integrations
            </button>
            <button class="menu-btn" onclick="showPanel('logs')">
              <span class="material-symbols-outlined">history_edu</span>
              System Logs
            </button>
          </nav>

          <div class="settings-panels">
            <!-- General Settings Panel -->
            <section class="panel-card settings-panel" id="panel-general">
              <div class="panel-title">
                <div class="icon-wrapper bg-primary-light text-primary">
                  <span class="material-symbols-outlined">tune</span>
                </div>
                <h3>General Configuration</h3>
              </div>

              <form method="POST" action="">
                <div class="form-grid">
                  <div class="form-group">
                    <label>Platform Name</label>
                    <input type="text" class="form-control" name="platform_name" 
                           value="<?php echo $_SESSION['platform_name'] ?? 'The Academic Nexus - MJIIT'; ?>" />
                  </div>
                  <div class="form-group">
                    <label>Support Email</label>
                    <input type="email" class="form-control" name="support_email" 
                           value="<?php echo $_SESSION['support_email'] ?? 'src.support@mjiit.edu.my'; ?>" />
                  </div>
                  <div class="form-group">
                    <label>System Timezone</label>
                    <select class="form-control" name="timezone">
                      <option value="Asia/Kuala_Lumpur" selected>(GMT+08:00) Kuala Lumpur, Singapore</option>
                      <option value="UTC">(GMT+00:00) UTC</option>
                      <option value="Asia/Tokyo">(GMT+09:00) Tokyo, Seoul</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Primary Language</label>
                    <select class="form-control" name="language">
                      <option>English (UK)</option>
                      <option>Bahasa Melayu</option>
                      <option>Japanese</option>
                    </select>
                  </div>
                </div>
                <div class="form-actions" style="margin-top: 2rem;">
                  <button type="submit" name="update_general" class="btn-primary">Save Configuration</button>
                </div>
              </form>
            </section>

            <!-- Security & Privacy Panel -->
            <section class="panel-card settings-panel hidden" id="panel-security">
              <div class="panel-title">
                <div class="icon-wrapper bg-primary-light text-primary">
                  <span class="material-symbols-outlined">shield_lock</span>
                </div>
                <h3>Security & Privacy</h3>
              </div>

              <form method="POST" action="">
                <div class="split-panel">
                  <div class="panel-card" style="padding: 0; box-shadow: none;">
                    <div class="panel-title sm">
                      <span class="material-symbols-outlined text-secondary">shield_lock</span>
                      <h3>Password Policy</h3>
                    </div>
                    <div class="policy-list">
                      <label class="policy-item">
                        <span>Require Special Characters</span>
                        <input type="checkbox" name="require_special" checked class="form-checkbox" />
                      </label>
                      <label class="policy-item">
                        <span>Force Password Reset (90 days)</span>
                        <input type="checkbox" name="force_reset" class="form-checkbox" />
                      </label>
                      <div class="range-group">
                        <label>Min. Length</label>
                        <input type="range" name="min_length" min="8" max="24" value="12" class="form-range" id="minLengthRange" oninput="updateRangeValue(this.value)" />
                        <div class="range-labels">
                          <span>8 CHARS</span>
                          <span id="rangeValue">12</span>
                          <span>24 CHARS</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="mfa-card">
                    <div class="mfa-content">
                      <div class="panel-title sm mfa-title">
                        <span class="material-symbols-outlined">verified_user</span>
                        <h3 style="color: white;">MFA Enforcement</h3>
                      </div>
                      <p>
                        Multi-Factor Authentication adds an extra layer of
                        protection. High-privilege roles are required to use
                        authenticator apps.
                      </p>
                    </div>
                    <button type="button" class="btn-solid-white" onclick="alert('MFA setup would be configured here')">Update Security Tiers</button>
                  </div>
                </div>
                <div class="form-actions" style="margin-top: 2rem;">
                  <button type="submit" name="update_security" class="btn-primary">Save Security Settings</button>
                </div>
              </form>
            </section>

            <!-- User Permissions Panel -->
            <section class="panel-card settings-panel hidden" id="panel-permissions">
              <div class="panel-title">
                <div class="icon-wrapper bg-primary-light text-primary">
                  <span class="material-symbols-outlined">group_work</span>
                </div>
                <h3>User Permissions</h3>
              </div>
              
              <p style="margin-bottom: 1.5rem; color: var(--on-surface-variant);">
                Manage role-based access control and user privileges.
              </p>
              
              <div class="form-grid">
                <div class="form-group">
                  <label>Default User Role</label>
                  <select class="form-control">
                    <option>Public</option>
                    <option selected>Researcher</option>
                    <option>Staff</option>
                    <option>Admin</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>New User Approval</label>
                  <select class="form-control">
                    <option selected>Admin Approval Required</option>
                    <option>Auto-approve</option>
                  </select>
                </div>
              </div>
              
              <div class="form-actions" style="margin-top: 2rem;">
                <a href="role-management.php"><button type="button" class="btn-primary">Manage User Roles</button></a>
              </div>
            </section>

            <!-- Integrations Panel -->
            <section class="panel-card settings-panel hidden" id="panel-integrations">
              <div class="panel-title">
                <div class="icon-wrapper bg-primary-light text-primary">
                  <span class="material-symbols-outlined">hub</span>
                </div>
                <h3>Integrations</h3>
              </div>
              
              <p style="margin-bottom: 1.5rem; color: var(--on-surface-variant);">
                Connect external services and APIs to extend platform functionality.
              </p>
              
              <div class="form-grid">
                <div class="form-group">
                  <label>Google Calendar API</label>
                  <select class="form-control">
                    <option>Disabled</option>
                    <option selected>Enabled</option>
                  </select>
                  <div class="current-value">Connect to sync meeting schedules</div>
                </div>
                <div class="form-group">
                  <label>Email Notification Service</label>
                  <select class="form-control">
                    <option selected>SMTP (Default)</option>
                    <option>SendGrid</option>
                    <option>Mailgun</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>API Access</label>
                  <select class="form-control">
                    <option selected>Restricted</option>
                    <option>Full Access</option>
                  </select>
                </div>
              </div>
              
              <div class="form-actions" style="margin-top: 2rem;">
                <button class="btn-primary" onclick="alert('Integration settings would be saved here')">Save Integrations</button>
              </div>
            </section>

            <!-- System Logs Panel -->
            <section class="panel-card settings-panel hidden" id="panel-logs">
              <div class="panel-title">
                <div class="icon-wrapper bg-primary-light text-primary">
                  <span class="material-symbols-outlined">history_edu</span>
                </div>
                <h3>System Logs</h3>
              </div>
              
              <p style="margin-bottom: 1.5rem; color: var(--on-surface-variant);">
                View and export system activity logs.
              </p>
              
              <div class="table-responsive" style="overflow-x: auto;">
                <table class="data-table" style="width: 100%; border-collapse: collapse;">
                  <thead>
                    <tr style="background: var(--surface-container-low);">
                      <th style="padding: 0.75rem; text-align: left;">Timestamp</th>
                      <th style="padding: 0.75rem; text-align: left;">User</th>
                      <th style="padding: 0.75rem; text-align: left;">Action</th>
                      <th style="padding: 0.75rem; text-align: left;">Details</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $recent_logs = db_fetch_all(
                        "SELECT l.*, u.full_name as user_name 
                         FROM system_logs l
                         LEFT JOIN users u ON l.user_id = u.user_id
                         ORDER BY l.created_at DESC LIMIT 10"
                    );
                    foreach ($recent_logs as $log):
                    ?>
                    <tr style="border-bottom: 1px solid var(--surface-container-high);">
                      <td style="padding: 0.75rem; font-size: 0.75rem;"><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                      <td style="padding: 0.75rem;"><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></td>
                      <td style="padding: 0.75rem;"><?php echo htmlspecialchars($log['action']); ?></td>
                      <td style="padding: 0.75rem; font-size: 0.75rem; color: var(--outline);"><?php echo htmlspecialchars(substr($log['details'] ?? '', 0, 50)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              
              <div class="form-actions" style="margin-top: 2rem;">
                <button class="btn-ghost" onclick="alert('Export logs feature')">Export Logs</button>
                <button class="btn-primary" onclick="alert('View full logs')">View Full Logs</button>
              </div>
            </section>

            <div class="form-actions" style="justify-content: flex-end; border-top: 1px solid var(--slate-200); padding-top: 1.5rem;">
              <button class="btn-ghost" onclick="resetSettings()">Reset to Default</button>
            </div>
          </div>
        </div>
      </div>

      <footer class="main-footer">
        <p>
          © 2024 MJIIT Sangaku Renkei Center. Professional Intellectual Capital
          Management.
        </p>
        <div class="footer-links">
          <a href="#">Privacy Policy</a>
          <a href="#">Compliance Standards</a>
        </div>
      </footer>
    </main>

    <script>
      function showPanel(panelName) {
        // Hide all panels
        document.querySelectorAll('.settings-panel').forEach(panel => {
          panel.classList.add('hidden');
        });
        
        // Show selected panel
        const activePanel = document.getElementById('panel-' + panelName);
        if (activePanel) {
          activePanel.classList.remove('hidden');
        }
        
        // Update active menu button
        document.querySelectorAll('.menu-btn').forEach(btn => {
          btn.classList.remove('active');
        });
        event.target.classList.add('active');
      }
      
      function updateRangeValue(value) {
        document.getElementById('rangeValue').textContent = value;
      }
      
      function resetSettings() {
        if (confirm('Reset all settings to default values?')) {
          alert('Settings would be reset to defaults');
        }
      }
      
      function searchSettings() {
        const input = document.getElementById('globalSearch');
        const filter = input.value.toLowerCase();
        
        // Simple search - could be expanded
        console.log('Searching for: ' + filter);
      }
      
      // Initialize range display
      document.addEventListener('DOMContentLoaded', function() {
        const rangeInput = document.getElementById('minLengthRange');
        if (rangeInput) {
          document.getElementById('rangeValue').textContent = rangeInput.value;
        }
      });
    </script>
</body>
</html>