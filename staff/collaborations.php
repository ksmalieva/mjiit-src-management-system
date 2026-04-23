<?php
// staff/projects.php - Projects/Collaborations page
require_once dirname(__DIR__) . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'auth/login.php');
    exit();
}

$user_role = $_SESSION['user_role'];
if ($user_role != 'staff' && $user_role != 'admin') {
    die('Access denied. Staff or Admin privileges required.');
}

// Get filter parameter
$filter = $_GET['filter'] ?? 'all';

// Build query based on filter
$where_conditions = [];
$params = [];

if ($filter == 'active') {
    $where_conditions[] = "status = 'active'";
} elseif ($filter == 'pending') {
    $where_conditions[] = "status = 'pending'";
} elseif ($filter == 'high_priority') {
    $where_conditions[] = "status IN ('active', 'pending')";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get active projects (collaborations)
$active_projects = db_fetch_all(
    "SELECT c.*, u.full_name as created_by_name 
     FROM collaborations c
     LEFT JOIN users u ON c.created_by = u.user_id
     $where_clause
     ORDER BY c.created_at DESC"
);

// Get statistics
$total_active = db_count('collaborations', "status = 'active'");
$total_pending = db_count('collaborations', "status = 'pending'");
$total_budget = 4200000; // Mock data - you can add budget field to collaborations table
$avg_progress = 68; // Mock data - you can add progress field

// Get archived projects (completed or expired)
$archived_projects = db_fetch_all(
    "SELECT * FROM collaborations WHERE status IN ('completed', 'expired') ORDER BY updated_at DESC LIMIT 10"
);

// Get current user info
$current_user = get_user($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Projects - The Academic Nexus</title>

    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap"
      rel="stylesheet"
    />

    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="styles/collaborations.css" />
    
    <style>
        .logout-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
        }
        
        .filter-btn.active {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: var(--shadow-sm);
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-active {
            background-color: rgba(0, 106, 106, 0.1);
            color: var(--secondary);
        }
        
        .status-pending {
            background-color: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        
        .status-completed {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .status-expired {
            background-color: rgba(186, 26, 26, 0.1);
            color: var(--error);
        }
        
        .card-link {
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .card-link:hover .card-title {
            color: var(--secondary);
        }
    </style>
</head>
<body>
     <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../../logo/SRC_logo.png" alt="SRC Logo" class="brand-logo" />
        </div>

        <nav class="sidebar-nav">
            <?php if ($user_role == 'admin'): ?>
                <a href="<?php echo SITE_URL; ?>../admin/dashboard.php" class="nav-item">
                    <span class="material-symbols-outlined">dashboard</span>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo SITE_URL; ?>../admin/role-management.php" class="nav-item">
                    <span class="material-symbols-outlined">manage_accounts</span>
                    <span>Role Management</span>
                </a>
                <a href="collaborations.php" class="nav-item">
                    <span class="material-symbols-outlined">handshake</span>
                    <span>Collaborations</span>
                </a>
                <a href="add.php" class="nav-item active">
                    <span class="material-symbols-outlined">add</span>
                    <span>Add Collaboration</span>
                </a>
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>../staff/dashboard.php" class="nav-item">
                    <span class="material-symbols-outlined">dashboard</span>
                    <span>Dashboard</span>
                <a href="<?php echo SITE_URL; ?>../staff/collaborations.php" class="nav-item">
                    <span class="material-symbols-outlined">handshake</span>
                    <span>Collaborations</span> 
                </a>
                <a href="<?php echo SITE_URL; ?>../staff/collaborations/add.php" class="nav-item active">
                    <span class="material-symbols-outlined">add</span>
                    <span>Add Collaboration</span>
                </a>
                <a href="<?php echo SITE_URL; ?>../staff/bookings.php" class="nav-item">
                    <span class="material-symbols-outlined">event_seat</span>
                    <span>Booking Space</span>
                </a>
                <a href="<?php echo SITE_URL; ?>../staff/news.php" class="nav-item">
                    <span class="material-symbols-outlined">newspaper</span>
                    <span>News</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <a href="<?php echo SITE_URL; ?>../staff/settings.php" class="nav-item">
                <span class="material-symbols-outlined">settings</span>
                <span>Settings</span>
            </a>
            <a href="<?php echo SITE_URL; ?>auth/logout.php" class="nav-item">
                <span class="material-symbols-outlined">logout</span>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <header class="top-header">
      <div class="header-left">
        <nav class="breadcrumb">
          <span class="crumb-light">Staff-Dashboard</span>
          <span class="material-symbols-outlined icon-small">chevron_right</span>
          <span class="crumb-dark">Projects</span>
        </nav>
        <div class="search-bar">
          <span class="material-symbols-outlined search-icon">search</span>
          <input class="search-input" id="searchProjects" placeholder="Search projects..." type="text" onkeyup="filterProjects()" />
        </div>
      </div>

      <div class="header-right">
        <button class="icon-btn notification-btn">
          <span class="material-symbols-outlined">notifications</span>
          <span class="notification-badge"></span>
        </button>
        <button class="icon-btn">
          <span class="material-symbols-outlined">help_outline</span>
        </button>
        <div class="divider"></div>
        <div class="user-profile">
          <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
          <div class="avatar" style="background: var(--primary); display: flex; align-items: center; justify-content: center; color: white;">
            <?php echo substr($_SESSION['user_name'], 0, 2); ?>
          </div>
        </div>
      </div>
    </header>

    <main class="main-content">
      <div class="container">
        <div class="page-header">
          <div>
            <h2 class="page-title">Project Portfolio</h2>
            <p class="page-subtitle">
              Manage industrial collaborations and research progress.
            </p>
          </div>
          <button class="btn-primary" onclick="location.href='collaborations/add.php'">
            <span class="material-symbols-outlined icon-medium">add</span>
            New Project
          </button>
        </div>

        <div class="stats-grid">
          <div class="stat-card">
            <p class="stat-label">Active</p>
            <div class="stat-content">
              <span class="stat-value"><?php echo $total_active; ?></span>
              <span class="stat-trend trend-up">
                <span class="material-symbols-outlined icon-small">trending_up</span>
                +<?php echo $total_active > 0 ? round(($total_active / ($total_active + $total_pending)) * 100) : 0; ?>%
              </span>
            </div>
          </div>
          <div class="stat-card">
            <p class="stat-label">Total Budget</p>
            <div class="stat-content">
              <span class="stat-value">RM <?php echo number_format($total_budget / 1000000, 1); ?>M</span>
              <span class="stat-note">FY 2024/25</span>
            </div>
          </div>
          <div class="stat-card">
            <p class="stat-label">Avg. Progress</p>
            <div class="stat-content">
              <span class="stat-value"><?php echo $avg_progress; ?>%</span>
              <div class="progress-bar-container w-16">
                <div class="progress-bar bg-secondary" style="width: <?php echo $avg_progress; ?>%"></div>
              </div>
            </div>
          </div>
          <div class="stat-card border-accent">
            <p class="stat-label">Success Rate</p>
            <div class="stat-content">
              <span class="stat-value text-secondary"><?php echo $total_active + $total_pending > 0 ? round(($total_active / ($total_active + $total_pending)) * 100) : 0; ?>%</span>
              <span class="material-symbols-outlined icon-large opacity-20 text-secondary">verified</span>
            </div>
          </div>
        </div>

        <section class="section-container">
          <div class="section-header">
            <h3 class="section-title">Active Collaborations</h3>
            <div class="filter-group">
              <a href="?filter=all"><button class="filter-btn <?php echo $filter == 'all' ? 'active' : ''; ?>">All</button></a>
              <a href="?filter=active"><button class="filter-btn <?php echo $filter == 'active' ? 'active' : ''; ?>">Active</button></a>
              <a href="?filter=high_priority"><button class="filter-btn <?php echo $filter == 'high_priority' ? 'active' : ''; ?>">High Priority</button></a>
            </div>
          </div>

          <div class="projects-grid" id="projectsGrid">
            <?php if (empty($active_projects)): ?>
            <div class="project-card" style="grid-column: 1 / -1; text-align: center; padding: 60px;">
              <span class="material-symbols-outlined" style="font-size: 48px; color: var(--slate-400);">work_off</span>
              <p style="margin-top: 16px; color: var(--slate-500);">No active projects found.</p>
              <button class="btn-primary" style="margin-top: 16px;" onclick="location.href='collaborations/add.php'">Create New Project</button>
            </div>
            <?php else: ?>
              <?php foreach ($active_projects as $project): ?>
              <?php 
              $status_class = '';
              $status_text = '';
              if ($project['status'] == 'active') {
                  $status_class = 'badge-progress';
                  $status_text = 'In Progress';
              } elseif ($project['status'] == 'pending') {
                  $status_class = 'badge-review';
                  $status_text = 'Review Needed';
              } else {
                  $status_class = 'badge-progress';
                  $status_text = ucfirst($project['status']);
              }
              ?>
              <div class="project-card card-link" onclick="location.href='collaborations/edit.php?id=<?php echo $project['collab_id']; ?>'">
                <div class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></div>
                <div class="card-body">
                  <div class="card-info">
                    <h4 class="card-title"><?php echo htmlspecialchars($project['partner_name']); ?></h4>
                    <div class="card-partner">
                      <span class="material-symbols-outlined icon-small">corporate_fare</span>
                      <span><?php echo htmlspecialchars($project['partner_name']); ?></span>
                    </div>
                  </div>
                  <div class="card-metrics">
                    <div class="metric-box">
                      <p class="metric-label">Agreement Type</p>
                      <p class="metric-value"><?php echo htmlspecialchars($project['agreement_type'] ?: 'MoU/MoA'); ?></p>
                    </div>
                    <div class="metric-box">
                      <p class="metric-label">Since</p>
                      <p class="metric-value text-dark"><?php echo $project['start_date'] ? date('M Y', strtotime($project['start_date'])) : 'N/A'; ?></p>
                    </div>
                  </div>
                  <div class="card-footer">
                    <div class="progress-header">
                      <span>Project Completion</span>
                      <span class="progress-percent"><?php echo $project['status'] == 'active' ? '65%' : '32%'; ?></span>
                    </div>
                    <div class="progress-bar-container full-width">
                      <div class="progress-bar gradient-primary-secondary" style="width: <?php echo $project['status'] == 'active' ? '65' : '32'; ?>%"></div>
                    </div>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </section>

        <?php if (!empty($archived_projects)): ?>
        <section class="section-container">
          <div class="section-header-simple">
            <span class="material-symbols-outlined text-slate-400">archive</span>
            <h3 class="section-title">Archived Records</h3>
          </div>

          <div class="table-container">
            <table class="data-table" id="archivedTable">
              <thead>
                <tr>
                  <th>Project Details</th>
                  <th>Partner</th>
                  <th>Budget Utilization</th>
                  <th>Status</th>
                  <th class="text-right">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($archived_projects as $project): ?>
                <tr>
                  <td>
                    <p class="table-primary-text"><?php echo htmlspecialchars($project['partner_name']); ?></p>
                    <p class="table-secondary-text">Completed: <?php echo $project['end_date'] ? date('M Y', strtotime($project['end_date'])) : 'N/A'; ?></p>
                  </div>
                  </td>
                  <td><span class="table-partner"><?php echo htmlspecialchars($project['partner_name']); ?></span></td>
                  <td>
                    <div class="table-progress">
                      <div class="progress-bar-container w-24">
                        <div class="progress-bar bg-secondary" style="width: <?php echo $project['status'] == 'completed' ? '100' : '92'; ?>%"></div>
                      </div>
                      <span class="progress-text"><?php echo $project['status'] == 'completed' ? '100%' : '92%'; ?></span>
                    </div>
                  </div>
                  <td><span class="badge-simple"><?php echo strtoupper($project['status']); ?></span></td>
                  <td class="text-right">
                    <button class="btn-icon-table" onclick="location.href='collaborations/edit.php?id=<?php echo $project['collab_id']; ?>'">
                      <span class="material-symbols-outlined icon-medium">visibility</span>
                    </button>
                  </div>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
        <?php endif; ?>
      </div>
    </main>

    <div class="fab-container">
      <button class="fab-btn" onclick="location.href='collaborations/add.php'">
        <span class="material-symbols-outlined icon-large">add</span>
      </button>
    </div>

    <script>
      function filterProjects() {
        const input = document.getElementById('searchProjects');
        const filter = input.value.toLowerCase();
        const projects = document.querySelectorAll('.project-card');
        
        projects.forEach(project => {
          if (project.classList.contains('card-link')) {
            const title = project.querySelector('.card-title')?.innerText.toLowerCase() || '';
            const partner = project.querySelector('.card-partner span')?.innerText.toLowerCase() || '';
            
            if (title.indexOf(filter) > -1 || partner.indexOf(filter) > -1) {
              project.style.display = '';
            } else {
              project.style.display = 'none';
            }
          }
        });
      }
      
      function filterArchived() {
        const input = document.getElementById('searchArchived');
        if (!input) return;
        const filter = input.value.toLowerCase();
        const rows = document.querySelectorAll('#archivedTable tbody tr');
        
        rows.forEach(row => {
          const text = row.innerText.toLowerCase();
          if (text.indexOf(filter) > -1) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      }
    </script>
</body>
</html>