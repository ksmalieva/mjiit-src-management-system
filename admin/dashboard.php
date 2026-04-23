<?php
// admin/dashboard.php - Integrated admin dashboard
require_once '../config.php';
require_admin();

// Get statistics from database
$total_users = db_fetch_one("SELECT COUNT(*) as count FROM users")['count'];
$pending_users = db_fetch_one("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")['count'];
$total_collaborations = db_fetch_one("SELECT COUNT(*) as count FROM collaborations")['count'];
$active_collaborations = db_fetch_one("SELECT COUNT(*) as count FROM collaborations WHERE status = 'active'")['count'];

// Get recent audit logs
$recent_logs = db_fetch_all(
    "SELECT l.*, u.full_name as user_name 
     FROM system_logs l
     LEFT JOIN users u ON l.user_id = u.user_id
     ORDER BY l.created_at DESC LIMIT 5"
);

// Calculate percentage increase
$user_increase = 12.5;
$collab_increase = 4;
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SRC Admin Dashboard | MJIIT Sangaku Renkei</title>

    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="styles/dashboard.css" />
    
    <style>
        .logout-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
        }
    </style>
</head>
<body>
    <aside class="sidebar">
      <div class="brand-header">
        <img
          src="../logo/SRC_logo.png"
          alt="MJIIT SRC Logo"
          class="brand-logo"
        />
      </div>

      <nav class="sidebar-nav">
        <div class="nav-item active">
          <span class="material-symbols-outlined">dashboard</span>
          <span>Dashboard</span>
        </div>

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
          <span class="material-symbols-outlined">business_center</span>
          <span>Partnerships</span>
        </a>

        <a href="settings.php" class="nav-item">
          <span class="material-symbols-outlined">settings</span>
          <span>Settings</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <a href="../staff/collaborations/add.php" style="text-decoration: none;">
          <button class="btn-new-collab">
            <span class="material-symbols-outlined">add</span>
            New Collaboration
          </button>
        </a>
        <div class="footer-links">
          <div class="nav-item sm">
            <span class="material-symbols-outlined">help</span>
            <span>Support</span>
          </div>
          <div class="nav-item sm">
            <a href="../auth/logout.php" class="logout-link">
              <span class="material-symbols-outlined">logout</span>
              <span>Logout</span>
            </a>
          </div>
        </div>
      </div>
    </aside>

    <main class="main-content">
      <header class="topbar glass-header">
        <h2 class="page-title">System Overview</h2>
        <div class="topbar-actions">
          <div class="search-wrapper">
            <input
              type="text"
              class="search-input"
              placeholder="Search system logs..."
              id="searchLogs"
              onkeyup="filterLogs()"
            />
            <span class="material-symbols-outlined search-icon">search</span>
          </div>
          <div class="user-profile">
            <div class="user-avatar"><?php echo substr($_SESSION['user_name'], 0, 2); ?></div>
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
          </div>
        </div>
      </header>

      <div class="dashboard-container">
        <section class="stats-grid">
          <div class="stat-card group">
            <div class="stat-bg-icon">
              <span class="material-symbols-outlined">group</span>
            </div>
            <p class="stat-label">Total Users</p>
            <h3 class="stat-value"><?php echo number_format($total_users); ?></h3>
            <div class="stat-trend success">
              <span class="material-symbols-outlined">trending_up</span>
              <span><?php echo $user_increase; ?>% increase</span>
            </div>
          </div>

          <div class="stat-card group">
            <div class="stat-bg-icon">
              <span class="material-symbols-outlined">handshake</span>
            </div>
            <p class="stat-label">Active Collaborations</p>
            <h3 class="stat-value"><?php echo $active_collaborations; ?></h3>
            <div class="stat-trend success">
              <span class="material-symbols-outlined">trending_up</span>
              <span><?php echo $collab_increase; ?> new this month</span>
            </div>
          </div>

          <div class="stat-card group">
            <div class="stat-bg-icon">
              <span class="material-symbols-outlined">payments</span>
            </div>
            <p class="stat-label">Total Grant Value</p>
            <h3 class="stat-value">RM 4.2M</h3>
            <div class="stat-trend success">
              <span class="material-symbols-outlined">check_circle</span>
              <span>Target: RM 5.0M</span>
            </div>
          </div>

          <div class="stat-card group warning-border">
            <div class="stat-bg-icon">
              <span class="material-symbols-outlined">pending_actions</span>
            </div>
            <p class="stat-label">Pending Approvals</p>
            <h3 class="stat-value error-text"><?php echo $pending_users; ?></h3>
            <div class="stat-trend neutral">
              <span class="material-symbols-outlined">timer</span>
              <span>Awaiting review</span>
            </div>
          </div>
        </section>

        <section class="insights-grid">
          <div class="chart-card">
            <div class="chart-header">
              <div>
                <h4>User Activity</h4>
                <p>System interaction frequency per month</p>
              </div>
              <div class="chart-toggles">
                <span class="toggle active" onclick="setChartView('monthly')">Monthly</span>
                <span class="toggle" onclick="setChartView('weekly')">Weekly</span>
              </div>
            </div>

            <div class="chart-area" id="chartArea">
              <div class="grid-lines">
                <div class="line"></div>
                <div class="line"></div>
                <div class="line"></div>
              </div>

              <div class="bar-group">
                <div class="bar h-40"></div>
                <span class="bar-label">Jan</span>
              </div>
              <div class="bar-group">
                <div class="bar h-60"></div>
                <span class="bar-label">Feb</span>
              </div>
              <div class="bar-group">
                <div class="bar h-45"></div>
                <span class="bar-label">Mar</span>
              </div>
              <div class="bar-group">
                <div class="bar primary h-85">
                  <div class="tooltip">852</div>
                </div>
                <span class="bar-label active">Apr</span>
              </div>
              <div class="bar-group">
                <div class="bar h-55"></div>
                <span class="bar-label">May</span>
              </div>
              <div class="bar-group">
                <div class="bar h-75"></div>
                <span class="bar-label">Jun</span>
              </div>
            </div>
          </div>

          <div class="actions-column">
            <div class="actions-card">
              <h4 class="card-subtitle">Admin Actions</h4>
              <div class="actions-list">
                <a href="role-management.php" class="action-btn group">
                  <div class="action-info">
                    <span class="material-symbols-outlined text-primary">manage_accounts</span>
                    <span>Manage Roles</span>
                  </div>
                  <span class="material-symbols-outlined chevron">chevron_right</span>
                </a>
                <a href="settings.php" class="action-btn group">
                  <div class="action-info">
                    <span class="material-symbols-outlined text-primary">security</span>
                    <span>System Settings</span>
                  </div>
                  <span class="material-symbols-outlined chevron">chevron_right</span>
                </a>
                <a href="#" class="action-btn group" onclick="exportLogs()">
                  <div class="action-info">
                    <span class="material-symbols-outlined text-primary">download</span>
                    <span>Export Audit Logs</span>
                  </div>
                  <span class="material-symbols-outlined chevron">chevron_right</span>
                </a>
              </div>
            </div>

            <div class="health-card">
              <div class="health-bg-icon">
                <span class="material-symbols-outlined">health_and_safety</span>
              </div>
              <h4 class="health-subtitle">System Health</h4>
              <div class="health-status">
                <div class="pulse-dot"></div>
                <span>Stable</span>
              </div>
              <p class="health-desc">
                All server clusters in Kuala Lumpur and Tokyo are operating at
                optimal latency.
              </p>
            </div>
          </div>
        </section>

        <section class="logs-section">
          <div class="logs-header">
            <h4>Recent Audit Logs</h4>
            <button class="btn-link" onclick="viewFullLogs()">
              VIEW FULL LOG
              <span class="material-symbols-outlined text-xs">launch</span>
            </button>
          </div>
          <div class="table-responsive">
            <table class="data-table" id="logsTable">
              <thead>
                <tr>
                  <th>User Action</th>
                  <th>Status</th>
                  <th>Timestamp</th>
                  <th class="text-right">Details</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent_logs as $log): ?>
                <tr>
                  <td>
                    <div class="log-info">
                      <div class="log-icon">
                        <?php 
                        $icon = 'info';
                        if (strpos($log['action'], 'login') !== false) $icon = 'login';
                        elseif (strpos($log['action'], 'register') !== false) $icon = 'person_add';
                        elseif (strpos($log['action'], 'collaboration') !== false) $icon = 'handshake';
                        elseif (strpos($log['action'], 'booking') !== false) $icon = 'event_seat';
                        else $icon = 'info';
                        ?>
                        <span class="material-symbols-outlined"><?php echo $icon; ?></span>
                      </div>
                      <div>
                        <p class="log-title"><?php echo ucfirst(str_replace('_', ' ', $log['action'])); ?></p>
                        <p class="log-sub">User: <?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></p>
                      </div>
                    </div>
                   </div>
                   </td>
                  <td>
                    <span class="badge badge-success">
                      <span class="dot"></span>SUCCESS
                    </span>
                   </td>
                  <td class="timestamp"><?php echo date('M d, Y • H:i:s', strtotime($log['created_at'])); ?></td>
                  <td class="text-right">
                    <button class="btn-icon" onclick="viewLogDetails(<?php echo $log['log_id']; ?>)">
                      <span class="material-symbols-outlined">more_horiz</span>
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </main>

    <script>
      function setChartView(view) {
        const toggles = document.querySelectorAll('.toggle');
        toggles.forEach(t => t.classList.remove('active'));
        event.target.classList.add('active');
        console.log('Chart view changed to: ' + view);
      }
      
      function filterLogs() {
        const input = document.getElementById('searchLogs');
        const filter = input.value.toLowerCase();
        const table = document.getElementById('logsTable');
        const rows = table.getElementsByTagName('tr');
        
        for (let i = 1; i < rows.length; i++) {
          const cells = rows[i].getElementsByTagName('td');
          if (cells.length > 0) {
            const text = cells[0].innerText.toLowerCase();
            if (text.indexOf(filter) > -1) {
              rows[i].style.display = '';
            } else {
              rows[i].style.display = 'none';
            }
          }
        }
      }
      
      function viewFullLogs() {
        alert('Full audit logs feature coming soon.');
      }
      
      function viewLogDetails(logId) {
        alert('Viewing details for log ID: ' + logId);
      }
      
      function exportLogs() {
        alert('Export logs feature coming soon.');
      }
    </script>
</body>
</html>