<?php
// public/collaborations.php - Integrated collaborations page
require_once '../config.php';

// Get filter parameters
$type = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query based on filters
$where_conditions = ["status = 'active'"];
$params = [];

if ($type !== 'all') {
    $where_conditions[] = "partner_type = ?";
    $params[] = $type;
}

if (!empty($search)) {
    $where_conditions[] = "(partner_name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = implode(' AND ', $where_conditions);

// Get all active collaborations
$sql = "SELECT * FROM collaborations WHERE $where_clause ORDER BY created_at DESC";
$collaborations = db_fetch_all($sql, $params);

// Get statistics
$total_active = db_count('collaborations', "status = 'active'");
$total_partners = db_count('collaborations');
$total_industries = db_count('collaborations', "partner_type = 'industry' AND status = 'active'");
$total_universities = db_count('collaborations', "partner_type = 'university' AND status = 'active'");

// Get featured collaboration (oldest active or random)
$featured = db_fetch_one(
    "SELECT * FROM collaborations WHERE status = 'active' ORDER BY created_at ASC LIMIT 1"
);

// Icon mapping for partner types
$icon_map = [
    'industry' => 'precision_manufacturing',
    'university' => 'school',
    'research_institute' => 'biotech',
    'government' => 'account_balance'
];

$icon_display_map = [
    'industry' => 'precision_manufacturing',
    'university' => 'school',
    'research_institute' => 'science',
    'government' => 'account_balance'
];

// Color mapping for cards
$color_map = [
    'industry' => '#00436f',
    'university' => '#006a6a',
    'research_institute' => '#005b94',
    'government' => '#0d9488'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Strategic Collaborations | Sangaku Renkei Center</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="styles/collaboration.css" />
    
    <style>
        /* Additional styles for dynamic content */
        .filter-bar {
            max-width: 1280px;
            margin: 2rem auto 0;
            padding: 0 2rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        
        .filter-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            background: var(--surface-container-low);
            padding: 0.25rem;
            border-radius: 12px;
        }
        
        .filter-tab {
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            border: none;
            background: transparent;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            color: var(--text-variant);
            transition: var(--transition);
        }
        
        .filter-tab.active {
            background: white;
            color: var(--primary);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .filter-search {
            position: relative;
        }
        
        .filter-search input {
            background: white;
            border: 1px solid var(--border);
            padding: 0.5rem 1rem;
            border-radius: 12px;
            width: 250px;
            font-family: 'Inter', sans-serif;
        }
        
        .filter-search button {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-variant);
        }
        
        .no-results {
            text-align: center;
            padding: 4rem;
            background: white;
            border-radius: 1rem;
            grid-column: 1 / -1;
        }
        
        .no-results .material-symbols-outlined {
            font-size: 4rem;
            color: var(--text-variant);
            margin-bottom: 1rem;
        }
        
        .stats-row {
            max-width: 1280px;
            margin: 2rem auto 0;
            padding: 0 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
        }
        
        .stat-mini {
            background: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            color: var(--text-variant);
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        
        .stat-mini strong {
            color: var(--primary);
            font-weight: 800;
        }
        
        @media (max-width: 768px) {
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-search input {
                width: 100%;
            }
            .featured-banner {
                padding: 2rem;
            }
            .banner-stat {
                margin-top: 1rem;
            }
        }
        
        .card-link {
            cursor: pointer;
        }
        
        .partner-detail-link {
            text-decoration: none;
            color: inherit;
        }
    </style>
</head>
<body>
    <nav class="navbar">
      <div class="nav-container">
        <div class="logo">Sangaku Renkei Center</div>
        <div class="nav-links">
          <a href="index.php">About Us</a>
          <a href="news.php">News</a>
          <a href="#" class="active">Collaborations</a>
        </div>
        <div class="nav-actions">
          <button class="icon-btn" onclick="toggleSearch()">
            <span class="material-symbols-outlined">search</span>
          </button>
          <a href="../auth/login.php"><button class="btn-primary">Contact Us</button></a>
        </div>
      </div>
    </nav>

    <main>
      <header class="hero">
        <div class="hero-container">
          <div class="hero-text">
            <span class="eyebrow">OUR ECOSYSTEM</span>
            <h1>Strategic Collaborations</h1>
            <p>
              Bridging high-performance industrial expertise with rigorous
              academic research. We cultivate partnerships that transform
              intellectual capital into sustainable global impact.
            </p>
          </div>
          <div class="hero-stat">
            <span class="stat-number"><?php echo $total_active; ?></span> Active Research Partners
          </div>
        </div>
      </header>

      <!-- Statistics Row -->
      <div class="stats-row">
        <div class="stat-mini"><strong><?php echo $total_partners; ?></strong> Total Partners</div>
        <div class="stat-mini"><strong><?php echo $total_industries; ?></strong> Industry Partners</div>
        <div class="stat-mini"><strong><?php echo $total_universities; ?></strong> Academic Partners</div>
      </div>

      <!-- Filter Bar -->
      <div class="filter-bar">
        <div class="filter-tabs">
          <a href="?type=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
            <button class="filter-tab <?php echo $type == 'all' ? 'active' : ''; ?>">All</button>
          </a>
          <a href="?type=industry<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
            <button class="filter-tab <?php echo $type == 'industry' ? 'active' : ''; ?>">Industry</button>
          </a>
          <a href="?type=university<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
            <button class="filter-tab <?php echo $type == 'university' ? 'active' : ''; ?>">University</button>
          </a>
          <a href="?type=research_institute<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
            <button class="filter-tab <?php echo $type == 'research_institute' ? 'active' : ''; ?>">Research Institute</button>
          </a>
          <a href="?type=government<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
            <button class="filter-tab <?php echo $type == 'government' ? 'active' : ''; ?>">Government</button>
          </a>
        </div>
        <div class="filter-search" id="searchContainer" style="display: block;">
          <form method="GET" action="">
            <?php if ($type !== 'all'): ?>
              <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
            <?php endif; ?>
            <input type="text" name="search" placeholder="Search partners..." value="<?php echo htmlspecialchars($search); ?>" />
            <button type="submit">
              <span class="material-symbols-outlined">search</span>
            </button>
            <?php if (!empty($search) || $type !== 'all'): ?>
              <a href="?type=all" style="position: absolute; right: 2.5rem; top: 50%; transform: translateY(-50%); text-decoration: none; color: var(--text-variant);">✕</a>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <section class="grid-section">
        <div class="partners-grid">
          <?php if (!empty($collaborations)): ?>
            <?php foreach ($collaborations as $collab): ?>
              <?php 
              $partner_type = $collab['partner_type'];
              $icon = $icon_map[$partner_type] ?? 'handshake';
              $display_icon = $icon_display_map[$partner_type] ?? 'handshake';
              ?>
              <div class="card" onclick="location.href='collaboration-detail.php?id=<?php echo $collab['collab_id']; ?>'" style="cursor: pointer;">
                <div class="icon-box">
                  <span class="material-symbols-outlined filled"><?php echo $display_icon; ?></span>
                </div>
                <h3><?php echo htmlspecialchars($collab['partner_name']); ?></h3>
                <p><?php echo htmlspecialchars(substr($collab['description'] ?? 'Strategic partnership in progress.', 0, 120)); ?></p>
                <a href="collaboration-detail.php?id=<?php echo $collab['collab_id']; ?>" class="view-scope">
                  View Project Scope
                  <span class="material-symbols-outlined">arrow_forward</span>
                </a>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="no-results">
              <span class="material-symbols-outlined">handshake</span>
              <h3>No collaborations found</h3>
              <p>Try adjusting your search or filter criteria.</p>
              <a href="?type=all"><button class="btn-primary" style="margin-top: 1rem;">View all collaborations</button></a>
            </div>
          <?php endif; ?>

          <!-- Featured Banner (only show if we have enough content) -->
          <?php if ($featured && count($collaborations) >= 3): ?>
          <div class="featured-banner">
            <img
              src="https://lh3.googleusercontent.com/aida-public/AB6AXuDH-KBf85mEJ5OvC4E_ThM-UVWt6P37EmyPVQCBGRdmt57N18AEU7VAYYOvHL3e8YqFL3rXf3iLhRWxI_xoskjiqcXKE8Esp23keIbj0jFGOqsVob2qbyEmKMZRVwMhLu597mQd3lXqAWkoFq8Lbu9ABgyGt6OiTAcBTIzMRyzad9SkuWGdkruq7LTBoSxsq0gPBOs5_MIlehWGu7CqGIEwotD2_dWovAGR2CB5ohUsN3WOyOGMlN7aAbzbiZmjYsaDka8G4dYxnqk"
              alt="Lab Research"
              class="banner-bg"
            />
            <div class="banner-content">
              <span class="eyebrow">GLOBAL PARTNERSHIP SPOTLIGHT</span>
              <h3><?php echo htmlspecialchars($featured['partner_name']); ?></h3>
              <p><?php echo htmlspecialchars(substr($featured['description'] ?? 'A strategic partnership driving innovation and excellence.', 0, 150)); ?></p>
              <a href="collaboration-detail.php?id=<?php echo $featured['collab_id']; ?>">
                <button class="btn-white">Explore Partnership</button>
              </a>
            </div>
            <div class="banner-stat">
              <span class="stat-big"><?php 
                $years_active = $featured['start_date'] ? date('Y') - date('Y', strtotime($featured['start_date'])) + 1 : '5+';
                echo $years_active . '+';
              ?></span>
              <span class="stat-label">Years of Collaboration</span>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </section>

      <section class="cta-section">
        <div class="cta-container">
          <h2>Partner with the Future</h2>
          <p>
            Join an elite network of global organizations driving industrial
            innovation through scholarly excellence. Our doors are open to
            visionary partnerships.
          </p>
          <a href="../auth/register.php">
            <button class="btn-primary-large">Register as Partner</button>
          </a>
        </div>
      </section>
    </main>

    <footer class="footer">
      <div class="footer-container">
        <div class="footer-brand">
          <div class="footer-logo">Sangaku Renkei Center</div>
          <p>
            © 2024 Sangaku Renkei Center. All intellectual capital reserved.
          </p>
        </div>
        <div class="footer-nav">
          <a href="#">Privacy Policy</a>
          <a href="#">Terms of Engagement</a>
          <a href="#">Faculty Link</a>
          <a href="#">Global Partnerships</a>
        </div>
        <div class="footer-social">
          <a href="#" class="social-icon"
            ><span class="material-symbols-outlined">public</span></a
          >
          <a href="#" class="social-icon"
            ><span class="material-symbols-outlined">hub</span></a
          >
        </div>
      </div>
    </footer>
    
    <script>
      function toggleSearch() {
        const searchContainer = document.getElementById('searchContainer');
        if (searchContainer.style.display === 'none') {
          searchContainer.style.display = 'block';
        } else {
          searchContainer.style.display = 'none';
        }
      }
      
      // Update active tab based on URL parameter
      document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const type = urlParams.get('type');
        
        if (type && type !== 'all') {
          const tabs = document.querySelectorAll('.filter-tab');
          tabs.forEach(tab => {
            if (tab.textContent.toLowerCase() === type.replace('_', ' ')) {
              tab.classList.add('active');
            }
          });
        }
      });
    </script>
</body>
</html>