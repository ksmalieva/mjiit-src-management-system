<?php
// staff/researchers.php - Researchers directory
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
$search = $_GET['search'] ?? '';

// Build query for researchers (users with role researcher or staff)
$where_conditions = ["role IN ('researcher', 'staff')"];
$params = [];

if ($filter == 'internal') {
    $where_conditions[] = "role = 'staff'";
} elseif ($filter == 'external') {
    $where_conditions[] = "role = 'researcher'";
} elseif ($filter == 'ai_ml') {
    // This would require a specialization field - for now, show all
    $where_conditions[] = "role IN ('researcher', 'staff')";
}

if (!empty($search)) {
    $where_conditions[] = "(full_name LIKE ? OR email LIKE ? OR username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = implode(' AND ', $where_conditions);

// Pagination
$page = (int)($_GET['page'] ?? 1);
$per_page = 6;
$offset = ($page - 1) * $per_page;

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM users WHERE $where_clause";
$total_result = db_fetch_one($count_sql, $params);
$total_researchers = $total_result['total'] ?? 0;
$total_pages = ceil($total_researchers / $per_page);

// Get researchers
$params[] = $per_page;
$params[] = $offset;
$researchers = db_fetch_all(
    "SELECT * FROM users WHERE $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?",
    $params
);

// Get statistics
$total_internal = db_count('users', "role = 'staff' AND status = 'active'");
$total_external = db_count('users', "role = 'researcher' AND status = 'active'");
$total_active_projects = db_count('collaborations', "status = 'active'");

// Get current user info
$current_user = get_user($_SESSION['user_id']);

// Helper function to get random avatar color
function get_avatar_color($name) {
    $colors = ['#00436f', '#006a6a', '#005b94', '#0d9488', '#2563eb', '#7c3aed', '#db2777'];
    $index = abs(crc32($name)) % count($colors);
    return $colors[$index];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Researchers - The Academic Nexus</title>

    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="styles/researchers.css" />
    
    <style>
        .logout-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
        }
        
        .filter-chip {
            cursor: pointer;
        }
        
        .filter-chip.active {
            background-color: var(--primary);
            color: white;
        }
        
        .card-link {
            cursor: pointer;
        }
        
        .avatar-initials {
            width: 64px;
            height: 64px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24px;
        }
        
        .search-form {
            display: flex;
            align-items: center;
        }
        
        .search-form input {
            background-color: var(--surface-container-low);
            border-radius: 8px;
            padding: 8px 16px 8px 40px;
            font-size: 14px;
            width: 320px;
            transition: box-shadow 0.2s;
            border: none;
        }
        
        .search-form input:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 67, 111, 0.2);
        }
    </style>
</head>
<body>
    <aside class="sidebar">
      <div class="sidebar-header">
        <img id="logo" class="sidebar-logo" src="../logo/SRC_logo.png" alt="SRC Logo" />
      </div>

      <nav class="nav-menu">
        <a class="nav-link" href="dashboard.php">
          <span class="material-symbols-outlined">dashboard</span>
          <span>Dashboard</span>
        </a>
        <a class="nav-link" href="collaborations.php">
          <span class="material-symbols-outlined">work</span>
          <span>Collaborations</span>
        </a>
        <a class="nav-link active" href="#">
          <span class="material-symbols-outlined active-pill">school</span>
          <span>Researchers</span>
        </a>
        <a class="nav-link" href="agreements.php">
          <span class="material-symbols-outlined">description</span>
          <span>Agreements</span>
        </a>
        <a class="nav-link" href="bookings.php">
          <span class="material-symbols-outlined">event_seat</span>
          <span>Booking Space</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <a class="nav-link" href="settings.php">
          <span class="material-symbols-outlined">settings</span>
          <span>Settings</span>
        </a>
        <a class="nav-link text-error logout-link" href="../auth/logout.php">
          <span class="material-symbols-outlined">logout</span>
          <span>Logout</span>
        </a>
      </div>
    </aside>

    <header class="topbar">
      <div style="display: flex; align-items: center">
        <div class="search-container">
          <form method="GET" action="" class="search-form">
            <span class="material-symbols-outlined search-icon">search</span>
            <input class="search-input" name="search" placeholder="Search directory..." type="text" value="<?php echo htmlspecialchars($search); ?>" />
          </form>
        </div>
        <nav class="top-nav-links">
          <a href="#">Reports</a>
        </nav>
      </div>
      <div class="top-actions">
        <button class="icon-btn">
          <span class="material-symbols-outlined">notifications</span>
        </button>
        <button class="icon-btn">
          <span class="material-symbols-outlined">help_outline</span>
        </button>
        <div class="divider-v"></div>
        <div class="profile-pic" style="background: var(--primary); display: flex; align-items: center; justify-content: center; color: white;">
          <?php echo substr($_SESSION['user_name'], 0, 2); ?>
        </div>
      </div>
    </header>

    <main class="main-content">
      <div class="page-header">
        <div>
          <h2 class="page-title font-headline">Researchers</h2>
          <p class="page-desc">
            Directory of Academic Talent and Industry Partners
          </p>
        </div>
        <div class="btn-group">
          <button class="btn btn-secondary" onclick="toggleFilters()">
            <span class="material-symbols-outlined">filter_list</span>
            Filters
          </button>
          <button class="btn btn-primary" onclick="location.href='../auth/register.php'">
            <span class="material-symbols-outlined">person_add</span>
            Add Researcher
          </button>
        </div>
      </div>

      <div class="filters no-scrollbar" id="filtersContainer">
        <a href="?filter=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
          <button class="chip <?php echo $filter == 'all' ? 'active' : ''; ?>">All Researchers</button>
        </a>
        <a href="?filter=internal<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
          <button class="chip <?php echo $filter == 'internal' ? 'active' : ''; ?>">Internal Faculty</button>
        </a>
        <a href="?filter=external<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
          <button class="chip <?php echo $filter == 'external' ? 'active' : ''; ?>">External Partners</button>
        </a>
        <button class="chip" onclick="filterBySpecialization('ai_ml')">AI & ML</button>
        <button class="chip" onclick="filterBySpecialization('robotics')">Robotics</button>
        <button class="chip" onclick="filterBySpecialization('energy')">Renewable Energy</button>
        <button class="chip" onclick="filterBySpecialization('biotech')">Biotechnology</button>
      </div>

      <div class="grid" id="researchersGrid">
        <?php if (empty($researchers)): ?>
        <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 60px;">
          <span class="material-symbols-outlined" style="font-size: 48px; color: var(--slate-400);">person_off</span>
          <p style="margin-top: 16px; color: var(--slate-500);">No researchers found.</p>
          <button class="btn btn-primary" style="margin-top: 16px;" onclick="location.href='../auth/register.php'">Add New Researcher</button>
        </div>
        <?php else: ?>
          <?php foreach ($researchers as $researcher): ?>
          <?php 
          $is_internal = $researcher['role'] == 'staff';
          $badge_class = $is_internal ? 'badge-internal' : 'badge-external';
          $badge_text = $is_internal ? 'Internal' : 'External';
          $avatar_color = get_avatar_color($researcher['full_name']);
          $initials = strtoupper(substr($researcher['full_name'], 0, 2));
          ?>
          <div class="card card-link" onclick="location.href='researcher-detail.php?id=<?php echo $researcher['user_id']; ?>'">
            <div class="card-top">
              <div class="card-profile">
                <div class="avatar-initials" style="background: <?php echo $avatar_color; ?>">
                  <?php echo $initials; ?>
                </div>
                <div>
                  <h3 class="card-name font-headline"><?php echo htmlspecialchars($researcher['full_name']); ?></h3>
                  <p class="card-role"><?php echo ucfirst($researcher['role']); ?></p>
                </div>
              </div>
              <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
            </div>
            <div class="card-details">
              <div class="detail-item">
                <span class="material-symbols-outlined">corporate_fare</span>
                <span><?php echo $researcher['email']; ?></span>
              </div>
              <div class="detail-item">
                <span class="material-symbols-outlined">rocket_launch</span>
                <span><?php echo rand(2, 15); ?> Active Projects</span>
              </div>
            </div>
            <div class="card-footer">
              <div class="avatar-group">
                <div class="mini-avatar bg-primary-fixed"><?php echo substr($researcher['full_name'], 0, 2); ?></div>
                <div class="mini-avatar bg-tertiary-fixed">AI</div>
              </div>
              <button class="link-btn" onclick="event.stopPropagation(); location.href='researcher-detail.php?id=<?php echo $researcher['user_id']; ?>'">
                View Profile
                <span class="material-symbols-outlined">arrow_forward</span>
              </button>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <!-- Add New Researcher Card -->
        <div class="card-add" onclick="location.href='../auth/register.php'">
          <div class="add-icon-wrapper">
            <span class="material-symbols-outlined" style="font-size: 30px">add</span>
          </div>
          <h3 class="font-headline">Register New Researcher</h3>
          <p>Add internal faculty or industry collaborators to the Nexus.</p>
        </div>
      </div>

      <?php if ($total_pages > 1): ?>
      <div class="pagination-container">
        <span class="pagination-text">
          Showing <span><?php echo $offset + 1; ?>-<?php echo min($offset + $per_page, $total_researchers); ?></span> 
          of <span><?php echo $total_researchers; ?></span> researchers
        </span>
        <div class="pagination-controls">
          <?php if ($page > 1): ?>
          <a href="?page=<?php echo $page - 1; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>">
            <button class="page-btn">
              <span class="material-symbols-outlined">chevron_left</span>
            </button>
          </a>
          <?php endif; ?>
          
          <?php for ($i = 1; $i <= min($total_pages, 5); $i++): ?>
          <a href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>">
            <button class="page-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></button>
          </a>
          <?php endfor; ?>
          
          <?php if ($page < $total_pages): ?>
          <a href="?page=<?php echo $page + 1; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>">
            <button class="page-btn">
              <span class="material-symbols-outlined">chevron_right</span>
            </button>
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </main>

    <div class="fab" onclick="location.href='../auth/register.php'">
      <span class="material-symbols-outlined active-pill" style="font-size: 24px">add</span>
    </div>

    <script>
      function toggleFilters() {
        const filters = document.getElementById('filtersContainer');
        if (filters.style.display === 'none') {
          filters.style.display = 'flex';
        } else {
          filters.style.display = 'none';
        }
      }
      
      function filterBySpecialization(specialization) {
        // This would filter by specialization if we had that field
        alert('Filter by ' + specialization.toUpperCase() + ' - Feature coming soon with specialization field.');
      }
      
      // Auto-submit search on input
      document.querySelector('.search-input')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          this.closest('form').submit();
        }
      });
    </script>
</body>
</html>