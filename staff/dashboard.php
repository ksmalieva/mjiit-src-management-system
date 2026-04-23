<?php
// staff/dashboard.php - Staff dashboard with your design
require_once dirname(__DIR__) . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'auth/login.php');
    exit();
}

$user_role = $_SESSION['user_role'];
if ($user_role != 'staff' && $user_role != 'admin') {
    die('Access denied. Staff or Admin privileges required.');
}

// Get statistics
$total_collaborations = db_count('collaborations');
$active_collaborations = db_count('collaborations', "status = 'active'");
$pending_collaborations = db_count('collaborations', "status = 'pending'");
$total_bookings = db_count('bookings', "user_id = " . $_SESSION['user_id']);
$draft_news = db_count('news', "status = 'draft' AND created_by = " . $_SESSION['user_id']);

// Get recent activities
$recent_activities = db_fetch_all(
    "SELECT * FROM system_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
    [$_SESSION['user_id']]
);

// Get upcoming meetings
$upcoming_meetings = db_fetch_all(
    "SELECT * FROM meetings WHERE meeting_date >= CURDATE() ORDER BY meeting_date ASC LIMIT 5"
);

// Get current user info
$current_user = get_user($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Staff Dashboard | The Academic Nexus</title>

    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap"
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
        
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
        }
        
        .progress-fill {
            transition: width 0.5s ease;
        }
    </style>
</head>
<body>
    <aside class="sidebar">
      <div class="brand">
        <img alt="MJIIT Logo" src="../logo/SRC_logo.png" />
        <div class="brand-text">
          <h1>The Academic Nexus</h1>
          <p>SRC Staff Portal</p>
        </div>
      </div>
      <nav class="nav-menu">
        <a href="dashboard.php" class="nav-item active">
          <span class="material-symbols-outlined">dashboard</span>
          <span>Dashboard</span>
        </a>

        <a href="collaborations.php" class="nav-item">
          <span class="material-symbols-outlined">work</span>
          <span>Collaborations</span>
        </a>
        <a href="researchers.php" class="nav-item">
          <span class="material-symbols-outlined">school</span>
          <span>Researchers</span>
        </a>
        <a href="agreements.php" class="nav-item">
          <span class="material-symbols-outlined">description</span>
          <span>Agreements</span>
        </a>
        <a href="bookings.php" class="nav-item">
          <span class="material-symbols-outlined">event_seat</span>
          <span>Booking Space</span>
        </a>
      </nav>
      <div class="nav-footer">
        <a href="settings.php" class="nav-item">
          <span class="material-symbols-outlined">settings</span>
          <span>Settings</span>
        </a>
        <a href="../auth/logout.php" class="nav-item logout-link">
          <span class="material-symbols-outlined">logout</span>
          <span>Logout</span>
        </a>
      </div>
    </aside>

    <main class="main-content">
      <header class="top-bar">
        <div class="breadcrumb">
          <span>SRC</span>
          <span class="divider">/</span>
          <span class="current">Staff Dashboard</span>
        </div>
        <div class="top-actions">
          <div class="search-box">
            <span class="material-symbols-outlined search-icon">search</span>
            <input type="text" id="searchInput" placeholder="Search activities, docs..." onkeyup="filterActivities()" />
          </div>
          <div class="action-icons">
            <button class="icon-btn relative">
              <span class="material-symbols-outlined">notifications</span>
              <span class="notification-dot"></span>
            </button>
            <button class="icon-btn">
              <span class="material-symbols-outlined">help_outline</span>
            </button>
          </div>
          <div class="user-profile">
            <div class="user-info">
              <p class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
              <p class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></p>
            </div>
            <div class="user-avatar">
              <div style="width: 100%; height: 100%; background: var(--primary); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                <?php echo substr($_SESSION['user_name'], 0, 2); ?>
              </div>
            </div>
          </div>
        </div>
      </header>

      <div class="page-canvas">
        <div class="page-header">
          <div class="header-titles">
            <h2>Overview</h2>
            <p>
              Welcome back. You have
              <span class="highlight"><?php echo $pending_collaborations; ?> pending tasks</span> that require
              attention today.
            </p>
          </div>
          <div class="header-buttons">
            <button class="btn btn-outline" onclick="location.href='news.php'">
              <span class="material-symbols-outlined">post_add</span> Post News
            </button>
            <button class="btn btn-gradient" onclick="location.href='collaborations/add.php'">
              <span class="material-symbols-outlined">add</span> New Collaboration
            </button>
          </div>
        </div>

        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Active Collabs</span>
              <span class="stat-badge badge-secondary">
                <span class="material-symbols-outlined">trending_up</span>
                <?php echo $total_collaborations > 0 ? round(($active_collaborations / $total_collaborations) * 100) : 0; ?>%
              </span>
            </div>
            <div class="stat-body">
              <span class="stat-value text-primary"><?php echo $active_collaborations; ?></span>
              <div class="progress-bar">
                <div class="progress-fill bg-primary" style="width: <?php echo $total_collaborations > 0 ? ($active_collaborations / $total_collaborations) * 100 : 0; ?>%"></div>
              </div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Pending</span>
              <span class="stat-badge badge-tertiary">Urgent</span>
            </div>
            <div class="stat-body">
              <span class="stat-value text-tertiary"><?php echo $pending_collaborations; ?></span>
              <div class="progress-bar">
                <div class="progress-fill bg-tertiary" style="width: <?php echo $total_collaborations > 0 ? ($pending_collaborations / $total_collaborations) * 100 : 0; ?>%"></div>
              </div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Bookings</span>
              <span class="stat-badge badge-secondary">Today</span>
            </div>
            <div class="stat-body">
              <span class="stat-value text-secondary"><?php echo $total_bookings; ?></span>
              <div class="progress-bar">
                <div class="progress-fill bg-secondary" style="width: 85%"></div>
              </div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Drafts</span>
              <span class="stat-badge badge-neutral">News</span>
            </div>
            <div class="stat-body">
              <span class="stat-value text-neutral"><?php echo $draft_news; ?></span>
              <div class="progress-bar">
                <div class="progress-fill bg-neutral" style="width: 15%"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="content-grid">
          <div class="main-column">
            <section class="card">
              <div class="card-header">
                <div>
                  <h3>Recent Activity</h3>
                  <p class="subtitle">Real-time updates from your network</p>
                </div>
                <button class="text-btn" onclick="location.href='activity.php'">View Timeline</button>
              </div>
              <div class="timeline">
                <div class="timeline-line"></div>
                <?php foreach ($recent_activities as $activity): ?>
                <div class="timeline-item">
                  <div class="timeline-icon icon-primary">
                    <?php 
                    $icon = 'info';
                    if (strpos($activity['action'], 'login') !== false) $icon = 'login';
                    elseif (strpos($activity['action'], 'collaboration') !== false) $icon = 'handshake';
                    elseif (strpos($activity['action'], 'booking') !== false) $icon = 'event_seat';
                    else $icon = 'verified';
                    ?>
                    <span class="material-symbols-outlined"><?php echo $icon; ?></span>
                  </div>
                  <div class="timeline-content">
                    <div class="timeline-meta">
                      <h4><?php echo ucfirst(str_replace('_', ' ', $activity['action'])); ?></h4>
                      <span class="time"><?php echo date('h:i A', strtotime($activity['created_at'])); ?></span>
                    </div>
                    <p><?php echo htmlspecialchars($activity['details'] ?? 'Activity recorded'); ?></p>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </section>

            <section class="card mt-10">
              <div class="card-header">
                <div>
                  <h3>Engagement Trends</h3>
                  <p class="subtitle">
                    Weekly comparison of partner vs research activity
                  </p>
                </div>
                <div class="chart-legend">
                  <span class="legend-item"><span class="dot dot-primary"></span> Corporate</span>
                  <span class="legend-item"><span class="dot dot-secondary"></span> Research</span>
                </div>
              </div>
              <div class="chart-container">
                <div class="chart-grid-lines">
                  <div class="line"></div>
                  <div class="line"></div>
                  <div class="line"></div>
                  <div class="line last"></div>
                </div>
                <div class="chart-columns">
                  <div class="chart-col">
                    <div class="bars">
                      <div class="bar bar-primary faded" style="height: 40%"></div>
                      <div class="bar bar-secondary faded" style="height: 60%"></div>
                    </div>
                    <span class="day">Mon</span>
                  </div>
                  <div class="chart-col">
                    <div class="bars">
                      <div class="bar bar-primary active" style="height: 70%"></div>
                      <div class="bar bar-secondary" style="height: 40%"></div>
                    </div>
                    <span class="day">Tue</span>
                  </div>
                  <div class="chart-col">
                    <div class="bars">
                      <div class="bar bar-primary faded" style="height: 50%"></div>
                      <div class="bar bar-secondary faded" style="height: 30%"></div>
                    </div>
                    <span class="day">Wed</span>
                  </div>
                  <div class="chart-col">
                    <div class="bars">
                      <div class="bar bar-primary" style="height: 90%"></div>
                      <div class="bar bar-secondary active" style="height: 80%"></div>
                    </div>
                    <span class="day active-day">Thu</span>
                  </div>
                  <div class="chart-col">
                    <div class="bars">
                      <div class="bar bar-primary faded" style="height: 60%"></div>
                      <div class="bar bar-secondary faded" style="height: 50%"></div>
                    </div>
                    <span class="day">Fri</span>
                  </div>
                </div>
              </div>
            </section>
          </div>

          <div class="side-column">
            <section class="card h-full flex-col">
              <div class="card-header">
                <div>
                  <h3>Schedule</h3>
                  <p class="subtitle">Calendar & milestones</p>
                </div>
                <button class="icon-btn-bordered" onclick="location.href='calendar.php'">
                  <span class="material-symbols-outlined">calendar_month</span>
                </button>
              </div>

              <div class="schedule-list">
                <?php foreach ($upcoming_meetings as $meeting): ?>
                <div class="schedule-item" onclick="location.href='meeting-detail.php?id=<?php echo $meeting['meeting_id']; ?>'">
                  <div class="date-box">
                    <span class="month"><?php echo date('M', strtotime($meeting['meeting_date'])); ?></span>
                    <span class="day"><?php echo date('d', strtotime($meeting['meeting_date'])); ?></span>
                  </div>
                  <div class="event-info">
                    <h4><?php echo htmlspecialchars($meeting['title']); ?></h4>
                    <p class="event-meta">
                      <span class="material-symbols-outlined">schedule</span>
                      <?php echo date('h:i A', strtotime($meeting['start_time'])); ?>
                    </p>
                    <div class="attendees">
                      <div class="avatar-group">
                        <div class="avatar-placeholder"></div>
                        <div class="avatar-more">+4</div>
                      </div>
                      <span class="attendee-text">Attendees</span>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>

              <div class="action-space">
                <div class="task-date-wrapper" style="position: relative; width: 100%; display: block">
                  <button class="btn-dashed" id="addTaskDisplayBtn" style="width: 100%" onclick="document.getElementById('taskDateInput').showPicker()">
                    <span class="material-symbols-outlined">add_circle</span>
                    Add New Task
                  </button>
                  <input type="date" id="taskDateInput" aria-label="Schedule a new task" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; box-sizing: border-box;" onchange="addTask(this.value)" />
                </div>
                <div class="system-health" style="margin-top: 1.5rem">
                  <div class="health-header">
                    <span class="health-label">Portal Status</span>
                    <div class="status-indicator">
                      <span class="pulse-dot"></span>
                      <span class="status-text">Online</span>
                    </div>
                  </div>
                  <div class="health-body">
                    <div class="health-meta">
                      <span>Sync Status</span>
                      <strong>100% Secure</strong>
                    </div>
                    <div class="progress-bar">
                      <div class="progress-fill bg-success" style="width: 100%"></div>
                    </div>
                  </div>
                </div>
              </div>
            </section>
          </div>
        </div>

        <footer class="main-footer">
          <div class="footer-brand">
            <img alt="MJIIT Logo" src="../../Logo/SRC_logo.png" />
            <p>
              © 2024 Academic Nexus - MJIIT Sangaku Renkei Center. All rights reserved.
            </p>
          </div>
          <div class="footer-links">
            <a href="#">Privacy Policy</a>
            <a href="#">Terms</a>
            <a href="#">Support</a>
          </div>
        </footer>
      </div>
    </main>

    <script>
      function filterActivities() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toLowerCase();
        const timelineItems = document.querySelectorAll('.timeline-item');
        
        timelineItems.forEach(item => {
          const text = item.innerText.toLowerCase();
          if (text.indexOf(filter) > -1) {
            item.style.display = '';
          } else {
            item.style.display = 'none';
          }
        });
      }
      
      function addTask(date) {
        if (date) {
          alert('Task scheduled for ' + date + '. Full task management coming soon.');
        }
        document.getElementById('taskDateInput').value = '';
      }
      
      // Animate progress bars on load
      document.addEventListener('DOMContentLoaded', function() {
        const progressBars = document.querySelectorAll('.progress-fill');
        progressBars.forEach(bar => {
          const width = bar.style.width;
          bar.style.width = '0%';
          setTimeout(() => {
            bar.style.width = width;
          }, 100);
        });
      });
    </script>
</body>
</html>