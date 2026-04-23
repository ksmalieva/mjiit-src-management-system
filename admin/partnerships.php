<?php
// admin/partnerships.php - Partnership Directory
require_once '../config.php';
require_admin();

// Get all active collaborations (partnerships)
$partnerships = db_fetch_all(
    "SELECT c.*, u.full_name as created_by_name 
     FROM collaborations c
     LEFT JOIN users u ON c.created_by = u.user_id
     ORDER BY c.status DESC, c.created_at DESC"
);

// Get statistics
$total_active = db_count('collaborations', "status = 'active'");
$total_partners = db_count('collaborations');
$total_industries = db_count('collaborations', "partner_type = 'industry' AND status = 'active'");
$total_universities = db_count('collaborations', "partner_type = 'university' AND status = 'active'");
$total_research = db_count('collaborations', "partner_type = 'research_institute' AND status = 'active'");
$total_government = db_count('collaborations', "partner_type = 'government' AND status = 'active'");

// Get unique partner types for filter
$partner_types = db_fetch_all("SELECT DISTINCT partner_type, COUNT(*) as count FROM collaborations GROUP BY partner_type");

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $partner = db_fetch_one("SELECT partner_name FROM collaborations WHERE collab_id = ?", [$delete_id]);
    if ($partner) {
        db_delete('collaborations', 'collab_id = ?', [$delete_id]);
        log_activity($_SESSION['user_id'], 'partner_deleted', "Deleted partner: " . $partner['partner_name']);
        header('Location: partnerships.php?deleted=1');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Partnership Directory | The Academic Nexus</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="styles/partnership.css" />
    
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
        
        .alert-close {
            margin-left: auto;
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: inherit;
        }
        
        .delete-btn {
            color: #dc2626;
        }
        
        .delete-btn:hover {
            color: #b91c1c;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }
        
        .badge-research-institute {
            background-color: #8b5cf6;
            color: white;
        }
        
        .badge-government {
            background-color: #10b981;
            color: white;
        }
        
        .badge-industry {
            background-color: var(--secondary-container);
            color: var(--on-secondary-container);
        }
        
        .badge-university {
            background-color: var(--tertiary-fixed);
            color: var(--on-tertiary-fixed-variant);
        }
    </style>
</head>
<body>
    <aside class="sidebar">
      <div class="sidebar-header">
        <img
          src="../logo/SRC_logo.png"
          alt="MJIIT Renkei Logo"
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
        <a href="#" class="nav-item active">
          <span class="material-symbols-outlined filled">corporate_fare</span>
          <span>Partners</span>
        </a>
        <a href="settings.php" class="nav-item">
          <span class="material-symbols-outlined">settings</span>
          <span>Settings</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <a href="../staff/collaborations/add.php" class="nav-item">
          <span class="material-symbols-outlined">add_business</span>
          <span>Add Partner</span>
        </a>
        <a href="../auth/logout.php" class="nav-item logout-link">
          <span class="material-symbols-outlined">logout</span>
          <span>Logout</span>
        </a>
      </div>
    </aside>

    <main class="main-content">
      <header class="top-header">
        <div class="header-left">
          <h2>Partnership Directory</h2>
          <div class="search-container">
            <span class="material-symbols-outlined search-icon">search</span>
            <input
              type="text"
              class="search-input"
              id="searchPartners"
              onkeyup="filterPartners()"
              placeholder="Search industrial partners, sectors, or contact persons..."
            />
          </div>
        </div>

        <div class="header-right">
          <button class="icon-btn">
            <span class="material-symbols-outlined">notifications</span>
          </button>
          <button class="icon-btn">
            <span class="material-symbols-outlined">help_outline</span>
          </button>
          <div class="profile-avatar">
            <div class="user-avatar" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: var(--primary); color: white;">
              <?php echo substr($_SESSION['user_name'], 0, 2); ?>
            </div>
          </div>
        </div>
      </header>

      <div class="content-container">
        <?php if (isset($_GET['deleted'])): ?>
        <div class="alert-success">
          <span class="material-symbols-outlined">check_circle</span>
          <span>Partner deleted successfully!</span>
          <button class="alert-close" onclick="this.parentElement.style.display='none'">×</button>
        </div>
        <?php endif; ?>

        <section class="filter-section">
          <div class="filter-header">
            <div>
              <span class="subtitle">Intellectual Capital</span>
              <h3>Industrial Ecosystem</h3>
            </div>
            <div class="filter-actions">
              <button class="btn-filter" onclick="filterByType('all')">
                <span class="material-symbols-outlined">filter_list</span>
                All
              </button>
              <button class="btn-filter" onclick="filterByType('industry')">
                Industry
              </button>
              <button class="btn-filter" onclick="filterByType('university')">
                University
              </button>
              <button class="btn-filter" onclick="filterByType('research_institute')">
                Research
              </button>
            </div>
          </div>

          <div class="tag-group" id="tagGroup">
            <span class="tag tag-active" data-type="all" onclick="filterByType('all')">All Partners</span>
            <span class="tag" data-type="industry" onclick="filterByType('industry')">Industry</span>
            <span class="tag" data-type="university" onclick="filterByType('university')">University</span>
            <span class="tag" data-type="research_institute" onclick="filterByType('research_institute')">Research Institute</span>
            <span class="tag" data-type="government" onclick="filterByType('government')">Government</span>
          </div>
        </section>

        <div class="partners-grid" id="partnersGrid">
          <?php foreach ($partnerships as $partner): ?>
          <div class="partner-card group" data-type="<?php echo $partner['partner_type']; ?>" data-name="<?php echo strtolower($partner['partner_name']); ?>">
            <div class="card-top">
              <div class="logo-box">
                <?php 
                $icon = 'business';
                if ($partner['partner_type'] == 'industry') $icon = 'precision_manufacturing';
                elseif ($partner['partner_type'] == 'university') $icon = 'school';
                elseif ($partner['partner_type'] == 'research_institute') $icon = 'science';
                elseif ($partner['partner_type'] == 'government') $icon = 'account_balance';
                ?>
                <span class="material-symbols-outlined" style="font-size: 2rem; color: var(--primary);"><?php echo $icon; ?></span>
              </div>
              <?php 
              $badge_class = 'badge-strategic';
              $badge_text = 'Active Strategic';
              if ($partner['status'] == 'pending') {
                  $badge_class = 'badge-pending';
                  $badge_text = 'Pending';
              } elseif ($partner['status'] == 'expired') {
                  $badge_class = 'badge-inactive';
                  $badge_text = 'Inactive';
              } elseif ($partner['status'] == 'completed') {
                  $badge_class = 'badge-inactive';
                  $badge_text = 'Completed';
              }
              ?>
              <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
            </div>
            <h4><?php echo htmlspecialchars($partner['partner_name']); ?></h4>
            <p class="industry-text">
              <?php 
              $type_display = ucfirst(str_replace('_', ' ', $partner['partner_type']));
              echo $type_display;
              ?>
            </p>

            <div class="card-details">
              <div class="detail-row">
                <div class="icon-box">
                  <span class="material-symbols-outlined">handshake</span>
                </div>
                <div class="detail-text">
                  <span class="detail-label">Agreement</span>
                  <span class="detail-value"><?php echo htmlspecialchars($partner['agreement_type'] ?: 'MoU/MoA'); ?></span>
                </div>
              </div>
              <?php if ($partner['contact_person']): ?>
              <div class="detail-row">
                <div class="icon-box">
                  <span class="material-symbols-outlined">person</span>
                </div>
                <div class="detail-text">
                  <span class="detail-label">Key Contact</span>
                  <span class="detail-value"><?php echo htmlspecialchars($partner['contact_person']); ?></span>
                </div>
              </div>
              <?php endif; ?>
              <?php if ($partner['start_date']): ?>
              <div class="detail-row">
                <div class="icon-box">
                  <span class="material-symbols-outlined">calendar_today</span>
                </div>
                <div class="detail-text">
                  <span class="detail-label">Since</span>
                  <span class="detail-value"><?php echo date('Y', strtotime($partner['start_date'])); ?></span>
                </div>
              </div>
              <?php endif; ?>
            </div>
            <div class="action-buttons" style="margin-top: auto;">
              <a href="../staff/collaborations/edit.php?id=<?php echo $partner['collab_id']; ?>" class="btn-block" style="text-align: center; text-decoration: none;">View Profile</a>
            </div>
          </div>
          <?php endforeach; ?>

          <!-- Add New Partner Card -->
          <div class="partner-card add-new-card" onclick="location.href='../staff/collaborations/add.php'">
            <div class="add-icon-wrapper">
              <span class="material-symbols-outlined">add_business</span>
            </div>
            <h4>Onboard New Partner</h4>
            <p>
              Initiate administrative vetting for a new industrial collaborator.
            </p>
          </div>
        </div>

        <section class="summary-section">
          <div class="summary-grid">
            <div class="summary-item">
              <p class="summary-label">Total Active Partners</p>
              <p class="summary-value"><?php echo $total_active; ?></p>
              <p class="summary-trend">↑ Active collaborations</p>
            </div>
            <div class="summary-item">
              <p class="summary-label">Industry Partners</p>
              <p class="summary-value"><?php echo $total_industries; ?></p>
              <p class="summary-trend">Corporate collaborators</p>
            </div>
            <div class="summary-item">
              <p class="summary-label">Academic Partners</p>
              <p class="summary-value"><?php echo $total_universities; ?></p>
              <p class="summary-trend">Universities & Institutes</p>
            </div>
            <div class="summary-item no-border">
              <p class="summary-label">Total Partnerships</p>
              <p class="summary-value"><?php echo $total_partners; ?></p>
              <div class="avatar-group">
                <div class="avatar bg-primary">AI</div>
                <div class="avatar bg-secondary">EV</div>
                <div class="avatar bg-tertiary">QC</div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </main>

    <button class="fab" onclick="location.href='../staff/collaborations/add.php'">
      <span class="material-symbols-outlined">add</span>
      <span class="fab-tooltip">Register New Partner</span>
    </button>

    <script>
      function filterPartners() {
        const input = document.getElementById('searchPartners');
        const filter = input.value.toLowerCase();
        const cards = document.querySelectorAll('.partner-card');
        const activeType = document.querySelector('.tag-active')?.getAttribute('data-type') || 'all';
        
        cards.forEach(card => {
          const name = card.getAttribute('data-name') || '';
          const type = card.getAttribute('data-type') || '';
          
          const matchesSearch = name.indexOf(filter) > -1;
          const matchesType = activeType === 'all' || type === activeType;
          
          if (matchesSearch && matchesType && !card.classList.contains('add-new-card')) {
            card.style.display = '';
          } else if (card.classList.contains('add-new-card')) {
            card.style.display = matchesSearch && matchesType ? '' : 'none';
          } else {
            card.style.display = 'none';
          }
        });
      }
      
      function filterByType(type) {
        // Update active tag
        const tags = document.querySelectorAll('.tag');
        tags.forEach(tag => {
          if (tag.getAttribute('data-type') === type) {
            tag.classList.add('tag-active');
          } else {
            tag.classList.remove('tag-active');
          }
        });
        
        // Filter cards
        const cards = document.querySelectorAll('.partner-card');
        const searchInput = document.getElementById('searchPartners');
        const filter = searchInput.value.toLowerCase();
        
        cards.forEach(card => {
          const cardType = card.getAttribute('data-type');
          const name = card.getAttribute('data-name') || '';
          const matchesSearch = name.indexOf(filter) > -1;
          const matchesType = type === 'all' || cardType === type;
          
          if (matchesSearch && matchesType && !card.classList.contains('add-new-card')) {
            card.style.display = '';
          } else if (card.classList.contains('add-new-card')) {
            card.style.display = matchesSearch && matchesType ? '' : 'none';
          } else {
            card.style.display = 'none';
          }
        });
      }
      
      // Initialize
      document.addEventListener('DOMContentLoaded', function() {
        // Set data-name attributes for filtering
        document.querySelectorAll('.partner-card').forEach(card => {
          const nameElement = card.querySelector('h4');
          if (nameElement) {
            card.setAttribute('data-name', nameElement.innerText.toLowerCase());
          }
          const typeElement = card.querySelector('.industry-text');
          if (typeElement) {
            let type = '';
            const text = typeElement.innerText.toLowerCase();
            if (text.includes('industry')) type = 'industry';
            else if (text.includes('university')) type = 'university';
            else if (text.includes('research')) type = 'research_institute';
            else if (text.includes('government')) type = 'government';
            card.setAttribute('data-type', type);
          }
        });
      });
    </script>
</body>
</html>