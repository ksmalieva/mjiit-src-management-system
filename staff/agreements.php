<?php
// staff/agreements.php - Agreements/Collaborations management page
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
$tab = $_GET['tab'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query based on filter
$where_conditions = [];
$params = [];

if ($tab == 'recent') {
    $where_conditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($tab == 'expiring') {
    $where_conditions[] = "end_date IS NOT NULL AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
} elseif ($tab == 'active') {
    $where_conditions[] = "status = 'active'";
} elseif ($tab == 'pending') {
    $where_conditions[] = "status = 'pending'";
}

if (!empty($search)) {
    $where_conditions[] = "(partner_name LIKE ? OR agreement_type LIKE ? OR contact_person LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get all collaborations (agreements)
$agreements = db_fetch_all(
    "SELECT c.*, u.full_name as created_by_name 
     FROM collaborations c
     LEFT JOIN users u ON c.created_by = u.user_id
     $where_clause
     ORDER BY c.created_at DESC"
);

// Get statistics
$total_active = db_count('collaborations', "status = 'active'");
$total_pending = db_count('collaborations', "status = 'pending'");
$total_expiring = db_count('collaborations', "end_date IS NOT NULL AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
$total_value = 2400000; // Mock data - would need a grant_value field

// Generate agreement ID based on collaboration ID
function generate_agreement_id($collab_id, $year) {
    return 'SRC-' . $year . '-' . str_pad($collab_id, 4, '0', STR_PAD_LEFT);
}

// Get status class
function get_status_class($status) {
    switch($status) {
        case 'active': return 'text-secondary';
        case 'pending': return 'text-warning';
        case 'expired': return 'text-muted line-through';
        default: return 'text-muted';
    }
}

function get_status_dot($status) {
    switch($status) {
        case 'active': return 'bg-secondary';
        case 'pending': return 'bg-warning';
        case 'expired': return 'bg-muted';
        default: return 'bg-muted';
    }
}

function get_badge_type($agreement_type) {
    $type = strtoupper($agreement_type);
    if (strpos($type, 'MOU') !== false) return 'MOU';
    if (strpos($type, 'MOA') !== false) return 'MOA';
    return 'MOU';
}

// Get current user info
$current_user = get_user($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Agreements | The Academic Nexus</title>

    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="styles/agreements.css" />
    
    <style>
        .logout-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
        }
        
        .status-warning {
            color: #f59e0b;
        }
        
        .bg-warning {
            background-color: #f59e0b;
        }
        
        .text-warning {
            color: #f59e0b;
        }
        
        .search-form {
            position: relative;
        }
        
        .search-form input {
            padding: 0.375rem 1rem 0.375rem 2.5rem;
            background-color: var(--surface-container-low);
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            width: 256px;
            outline: none;
            transition: box-shadow 0.2s;
        }
        
        .search-form input:focus {
            box-shadow: 0 0 0 2px rgba(0, 91, 148, 0.2);
        }
        
        .search-form .material-symbols-outlined {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--slate-400);
            font-size: 1.125rem;
        }
        
        .tab-link {
            text-decoration: none;
        }
        
        .avatar-initials {
            height: 2rem;
            width: 2rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
        }
        
        .filter-active {
            color: var(--primary);
            font-weight: 700;
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <aside class="sidebar">
      <div class="sidebar-header">
        <img id="logo" src="../logo/SRC_logo.png" alt="Logo" />
      </div>

      <nav class="nav-links">
        <a href="dashboard.php" class="nav-item">
          <span class="material-symbols-outlined">dashboard</span> Dashboard
        </a>
        <a href="collaborations.php" class="nav-item">
          <span class="material-symbols-outlined">work</span> Collaborations
        </a>
        <a href="researchers.php" class="nav-item">
          <span class="material-symbols-outlined">school</span> Researchers
        </a>
        <a href="#" class="nav-item active">
          <span class="material-symbols-outlined">description</span> Agreements
        </a>
        <a href="bookings.php" class="nav-item">
          <span class="material-symbols-outlined">event_seat</span> Booking Space
        </a>
      </nav>

      <div class="sidebar-footer">
        <a href="settings.php" class="nav-item">
          <span class="material-symbols-outlined">settings</span> Settings
        </a>
        <a href="../auth/logout.php" class="nav-item logout-link">
          <span class="material-symbols-outlined">logout</span> Logout
        </a>
      </div>
    </aside>

    <header class="header">
      <div class="header-left">
        <div class="breadcrumb">
          Staff <span class="divider">/</span>
          <span class="active">Agreements</span>
        </div>
      </div>

      <div class="header-right">
        <div class="search-bar">
          <form method="GET" action="" class="search-form">
            <span class="material-symbols-outlined">search</span>
            <input type="text" name="search" placeholder="Search agreements..." value="<?php echo htmlspecialchars($search); ?>" />
            <?php if (!empty($search)): ?>
            <input type="hidden" name="tab" value="<?php echo $tab; ?>">
            <?php endif; ?>
          </form>
        </div>
        <div class="header-icons">
          <button>
            <span class="material-symbols-outlined">notifications</span>
          </button>
          <button>
            <span class="material-symbols-outlined">help_outline</span>
          </button>
        </div>
        <div class="user-profile">
          <div style="width: 2rem; height: 2rem; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; color: white;">
            <?php echo substr($_SESSION['user_name'], 0, 2); ?>
          </div>
        </div>
      </div>
    </header>

    <main class="main-content">
      <div class="page-container">
        <div class="page-header">
          <div>
            <h2 class="page-title">Agreements</h2>
            <p class="page-subtitle">
              Curate and monitor formal institutional partnerships. Manage the
              lifecycle of Memorandums of Understanding and Agreement.
            </p>
          </div>
          <button class="btn-primary" id="upload-btn" onclick="location.href='collaborations/add.php'">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1">cloud_upload</span>
            Upload New Agreement
          </button>
        </div>

        <div class="kpi-grid">
          <div class="kpi-card border-primary">
            <span class="kpi-label">Active MOUs</span>
            <div class="kpi-data">
              <span class="kpi-value value-primary"><?php echo $total_active; ?></span>
              <span class="kpi-subtext sub-secondary">Active agreements</span>
            </div>
          </div>

          <div class="kpi-card border-tertiary">
            <span class="kpi-label">Pending MOAs</span>
            <div class="kpi-data">
              <span class="kpi-value value-tertiary"><?php echo $total_pending; ?></span>
              <span class="kpi-subtext">Awaiting signatures</span>
            </div>
          </div>

          <div class="kpi-card border-error">
            <span class="kpi-label">Expiring Soon</span>
            <div class="kpi-data">
              <span class="kpi-value value-error"><?php echo $total_expiring; ?></span>
              <span class="kpi-subtext sub-error">Within 30 days</span>
            </div>
          </div>

          <div class="kpi-card border-secondary">
            <span class="kpi-label">Total Value</span>
            <div class="kpi-data">
              <span class="kpi-value value-secondary">RM <?php echo number_format($total_value / 1000000, 1); ?>M</span>
              <span class="kpi-subtext">Grant ecosystem</span>
            </div>
          </div>
        </div>

        <div class="table-container">
          <div class="table-header">
            <div class="table-tabs">
              <a href="?tab=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="tab-link">
                <button class="tab <?php echo $tab == 'all' ? 'active' : ''; ?>">All Agreements</button>
              </a>
              <a href="?tab=recent<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="tab-link">
                <button class="tab <?php echo $tab == 'recent' ? 'active' : ''; ?>">Recently Added</button>
              </a>
              <a href="?tab=expiring<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="tab-link">
                <button class="tab <?php echo $tab == 'expiring' ? 'active' : ''; ?>">Expiring</button>
              </a>
            </div>
            <div class="table-actions">
              <button class="icon-btn" onclick="toggleFilter()">
                <span class="material-symbols-outlined">filter_list</span>
              </button>
              <button class="icon-btn" onclick="exportAgreements()">
                <span class="material-symbols-outlined">download</span>
              </button>
            </div>
          </div>

          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Agreement ID</th>
                  <th>Partner Name</th>
                  <th class="text-center">Type</th>
                  <th>Status</th>
                  <th>Expiry Date</th>
                  <th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($agreements)): ?>
                <tr>
                  <td colspan="6" style="text-align: center; padding: 3rem; color: var(--slate-400);">
                    <span class="material-symbols-outlined" style="font-size: 48px;">description</span>
                    <p style="margin-top: 1rem;">No agreements found.</p>
                    <button class="btn-primary" style="margin-top: 1rem;" onclick="location.href='collaborations/add.php'">Create New Agreement</button>
                  </td>
                </tr>
                <?php else: ?>
                  <?php foreach ($agreements as $agreement): ?>
                  <?php 
                  $agreement_id = generate_agreement_id($agreement['collab_id'], date('Y', strtotime($agreement['created_at'])));
                  $status_class = get_status_class($agreement['status']);
                  $dot_class = get_status_dot($agreement['status']);
                  $badge_type = get_badge_type($agreement['agreement_type']);
                  $expiry_date = $agreement['end_date'] ? date('d M Y', strtotime($agreement['end_date'])) : 'N/A';
                  $partner_initials = strtoupper(substr($agreement['partner_name'], 0, 2));
                  ?>
                  <tr>
                    <td class="font-mono text-primary font-semibold">
                      <?php echo $agreement_id; ?>
                    </td>
                    <td>
                      <div class="partner-cell">
                        <div class="avatar-initials avatar-primary"><?php echo $partner_initials; ?></div>
                        <div>
                          <p class="partner-name"><?php echo htmlspecialchars($agreement['partner_name']); ?></p>
                          <p class="partner-location"><?php echo ucfirst(str_replace('_', ' ', $agreement['partner_type'])); ?></p>
                        </div>
                      </div>
                    </td>
                    <td class="text-center"><span class="badge"><?php echo $badge_type; ?></span></td>
                    <td>
                      <div class="status <?php echo $status_class; ?>">
                        <span class="dot <?php echo $dot_class; ?>"></span> 
                        <?php echo ucfirst($agreement['status']); ?>
                      </div>
                    </td>
                    <td class="text-muted"><?php echo $expiry_date; ?></td>
                    <td class="text-right">
                      <div class="row-actions">
                        <button class="icon-btn" onclick="location.href='collaborations/edit.php?id=<?php echo $agreement['collab_id']; ?>'">
                          <span class="material-symbols-outlined">visibility</span>
                        </button>
                        <button class="icon-btn" onclick="location.href='collaborations/edit.php?id=<?php echo $agreement['collab_id']; ?>'">
                          <span class="material-symbols-outlined">edit</span>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <div class="table-footer">
            <p>Showing <?php echo count($agreements); ?> of <?php echo db_count('collaborations'); ?> agreements</p>
            <div class="pagination">
              <button class="icon-btn" onclick="previousPage()">
                <span class="material-symbols-outlined text-sm">chevron_left</span>
              </button>
              <button class="page-btn active">1</button>
              <button class="page-btn">2</button>
              <button class="page-btn">3</button>
              <button class="icon-btn" onclick="nextPage()">
                <span class="material-symbols-outlined text-sm">chevron_right</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>

    <script>
      function toggleFilter() {
        alert('Filter options would appear here');
      }
      
      function exportAgreements() {
        alert('Export agreements feature coming soon');
      }
      
      function previousPage() {
        alert('Previous page');
      }
      
      function nextPage() {
        alert('Next page');
      }
      
      // Auto-submit search on input
      document.querySelector('.search-form input')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          this.closest('form').submit();
        }
      });
    </script>
</body>
</html>