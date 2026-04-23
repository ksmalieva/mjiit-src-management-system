<?php
// admin/role-management.php - Role management page
require_once '../config.php';
require_admin();

$message = '';
$error = '';

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = (int)$_POST['user_id'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    
    $result = db_update('users', 
        ['role' => $role, 'status' => $status],
        'user_id = ?',
        [$user_id]
    );
    
    if ($result !== false) {
        log_activity($_SESSION['user_id'], 'role_updated', "Updated user $user_id role to $role, status to $status");
        $message = "User role and status updated successfully!";
    } else {
        $error = "Failed to update user";
    }
}

// Get all users
$users = get_all_users();

// Get statistics
$total_users = count($users);
$admin_count = db_count('users', "role = 'admin'");
$staff_count = db_count('users', "role = 'staff'");
$researcher_count = db_count('users', "role = 'researcher'");
$pending_count = db_count('users', "status = 'pending'");
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Role Management | SRC Admin Dashboard</title>

    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="styles/role-management.css" />
    
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
        <a href="dashboard.php" class="nav-item">
          <span class="material-symbols-outlined">dashboard</span>
          <span>Dashboard</span>
        </a>

        <div class="nav-item active">
          <span class="material-symbols-outlined">manage_accounts</span>
          <span>Role Management</span>
        </div>

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
        <h2 class="page-title">Role Management</h2>
        <div class="topbar-actions">
          <div class="search-wrapper">
            <input
              type="text"
              class="search-input"
              placeholder="Search users..."
              id="searchUsers"
              onkeyup="filterUsers()"
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
        <!-- Stats Grid -->
        <section class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">
              <span class="material-symbols-outlined">group</span>
            </div>
            <div>
              <p class="stat-label">Total Users</p>
              <h3 class="stat-value"><?php echo $total_users; ?></h3>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <span class="material-symbols-outlined">admin_panel_settings</span>
            </div>
            <div>
              <p class="stat-label">Administrators</p>
              <h3 class="stat-value"><?php echo $admin_count; ?></h3>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <span class="material-symbols-outlined">badge</span>
            </div>
            <div>
              <p class="stat-label">Staff Members</p>
              <h3 class="stat-value"><?php echo $staff_count; ?></h3>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <span class="material-symbols-outlined">school</span>
            </div>
            <div>
              <p class="stat-label">Researchers</p>
              <h3 class="stat-value"><?php echo $researcher_count; ?></h3>
            </div>
          </div>
        </section>

        <!-- Alert Messages -->
        <?php if ($message): ?>
        <div class="alert success">
          <span class="material-symbols-outlined">check_circle</span>
          <span><?php echo htmlspecialchars($message); ?></span>
          <button class="alert-close" onclick="this.parentElement.style.display='none'">×</button>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert error">
          <span class="material-symbols-outlined">error</span>
          <span><?php echo htmlspecialchars($error); ?></span>
          <button class="alert-close" onclick="this.parentElement.style.display='none'">×</button>
        </div>
        <?php endif; ?>

        <!-- Users Table -->
        <section class="users-section">
          <div class="section-header">
            <h3 class="section-title">System Users</h3>
            <div class="filter-badge">
              <span class="badge"><?php echo $pending_count; ?> Pending Approval</span>
            </div>
          </div>

          <div class="table-container">
            <table class="data-table" id="usersTable">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Registered</th>
                  <th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                  <td>
                    <div class="user-info">
                      <div class="user-avatar-small" style="background: <?php 
                        $colors = ['#00436f', '#006a6a', '#005b94', '#0d9488', '#2563eb'];
                        echo $colors[$user['user_id'] % count($colors)];
                      ?>;">
                        <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                      </div>
                      <div>
                        <p class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></p>
                        <p class="user-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                      </div>
                    </div>
                  </td>
                  <td class="user-email"><?php echo htmlspecialchars($user['email']); ?></td>
                  <td>
                    <form method="POST" action="" class="inline-form" id="form-<?php echo $user['user_id']; ?>">
                      <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                      <select name="role" class="role-select" data-user-id="<?php echo $user['user_id']; ?>">
                        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="staff" <?php echo $user['role'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                        <option value="researcher" <?php echo $user['role'] == 'researcher' ? 'selected' : ''; ?>>Researcher</option>
                        <option value="public" <?php echo $user['role'] == 'public' ? 'selected' : ''; ?>>Public</option>
                      </select>
                  </td>
                  <td>
                      <select name="status" class="status-select" data-user-id="<?php echo $user['user_id']; ?>">
                        <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="pending" <?php echo $user['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="inactive" <?php echo $user['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                      </select>
                  </td>
                  <td class="register-date"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                  <td class="text-right">
                      <button type="submit" name="update_role" class="btn-update" data-user-id="<?php echo $user['user_id']; ?>">
                        <span class="material-symbols-outlined">save</span>
                        <span class="btn-text">Save</span>
                      </button>
                    </form>
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
      function filterUsers() {
        const input = document.getElementById('searchUsers');
        const filter = input.value.toLowerCase();
        const table = document.getElementById('usersTable');
        const rows = table.getElementsByTagName('tr');
        
        for (let i = 1; i < rows.length; i++) {
          const cells = rows[i].getElementsByTagName('td');
          if (cells.length > 0) {
            const name = cells[0].innerText.toLowerCase();
            const email = cells[1].innerText.toLowerCase();
            if (name.indexOf(filter) > -1 || email.indexOf(filter) > -1) {
              rows[i].style.display = '';
            } else {
              rows[i].style.display = 'none';
            }
          }
        }
      }
      
    </script>
</body>
</html>