<?php
// admin/event.php - Events & News Management
require_once '../config.php';
require_admin();

// Handle news submission
$news_success = '';
$news_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_news'])) {
    $title = sanitize($_POST['title']);
    $category = sanitize($_POST['category']);
    $content = sanitize($_POST['content']);
    $publish_date = $_POST['publish_date'];
    $status = $_POST['status'] ?? 'published';
    
    if (!$title || !$content) {
        $news_error = "Title and content are required";
    } else {
        $news_id = db_insert('news', [
            'title' => $title,
            'category' => strtolower($category),
            'content' => $content,
            'status' => $status,
            'published_at' => $publish_date ?: date('Y-m-d H:i:s'),
            'created_by' => $_SESSION['user_id']
        ]);
        
        if ($news_id) {
            log_activity($_SESSION['user_id'], 'news_created', "Created news: $title");
            $news_success = "News published successfully!";
        } else {
            $news_error = "Failed to publish news";
        }
    }
}

// Handle meeting submission
$meeting_success = '';
$meeting_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_meeting'])) {
    $title = sanitize($_POST['meeting_title']);
    $date = $_POST['meeting_date'];
    $time = $_POST['meeting_time'];
    $room = sanitize($_POST['meeting_room']);
    $description = sanitize($_POST['meeting_description']);
    
    if (!$title || !$date || !$time) {
        $meeting_error = "Meeting title, date and time are required";
    } else {
        // Insert into meetings table
        $meeting_id = db_insert('meetings', [
            'title' => $title,
            'description' => $description,
            'meeting_date' => $date,
            'start_time' => $time,
            'end_time' => date('H:i:s', strtotime($time) + 3600),
            'location' => $room,
            'status' => 'scheduled',
            'created_by' => $_SESSION['user_id']
        ]);
        
        if ($meeting_id) {
            log_activity($_SESSION['user_id'], 'meeting_scheduled', "Scheduled meeting: $title");
            $meeting_success = "Meeting scheduled successfully!";
        } else {
            $meeting_error = "Failed to schedule meeting";
        }
    }
}

// Get recent news
$recent_news = db_fetch_all(
    "SELECT * FROM news ORDER BY created_at DESC LIMIT 10"
);

// Get upcoming meetings
$upcoming_meetings = db_fetch_all(
    "SELECT * FROM meetings WHERE meeting_date >= CURDATE() ORDER BY meeting_date ASC LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Events & News Management | MJIIT Sangaku Renkei</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="styles/event.css" />
    
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
    </style>
</head>
<body>
    <aside class="sidebar">
      <div class="sidebar-header">
        <img src="../logo/SRC_logo.png" alt="MJIIT Logo" class="logo-img" />
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

        <a href="event.php" class="nav-item active">
          <span class="material-symbols-outlined">event</span>
          <span>Events</span>
        </a>
        <a href="partnerships.php" class="nav-item">
          <span class="material-symbols-outlined filled">corporate_fare</span>
          <span>Partners</span>
        </a>
        <a href="settings.php" class="nav-item">
          <span class="material-symbols-outlined">settings</span>
          <span>Settings</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <a href="../staff/collaborations/add.php" style="text-decoration: none;">
          <button class="btn-primary w-full">
            <span class="material-symbols-outlined">add</span>
            New Collaboration
          </button>
        </a>
        <div class="footer-links">
          <a href="#" class="nav-item-small">
            <span class="material-symbols-outlined">help</span>
            Support
          </a>
          <a href="../auth/logout.php" class="nav-item-small logout-link">
            <span class="material-symbols-outlined">logout</span>
            Logout
          </a>
        </div>
      </div>
    </aside>

    <main class="main-content">
      <header class="page-header">
        <div class="header-titles">
          <h2>Events & News Management</h2>
          <p>Curating intellectual capital for the MJIIT community.</p>
        </div>
        <div class="header-actions">
          <div class="view-toggle">
            <button class="toggle-btn active" onclick="showManagementView()">Management View</button>
            <button class="toggle-btn" onclick="window.location.href='../public/news.php'">Public Preview</button>
          </div>
        </div>
      </header>

      <div class="dashboard-grid">
        <!-- News Form Section -->
        <section class="form-section">
          <div class="card p-lg">
            <div class="card-header-simple">
              <span class="material-symbols-outlined icon-secondary">edit_note</span>
              <h3>Post News</h3>
            </div>

            <?php if ($news_success): ?>
            <div class="alert-success">
              <span class="material-symbols-outlined">check_circle</span>
              <span><?php echo $news_success; ?></span>
              <button class="alert-close" onclick="this.parentElement.style.display='none'">×</button>
            </div>
            <?php endif; ?>
            
            <?php if ($news_error): ?>
            <div class="alert-error">
              <span class="material-symbols-outlined">error</span>
              <span><?php echo $news_error; ?></span>
              <button class="alert-close" onclick="this.parentElement.style.display='none'">×</button>
            </div>
            <?php endif; ?>

            <form class="news-form" method="POST" action="">
              <div class="form-group">
                <label>News Title</label>
                <input type="text" class="form-control" name="title" placeholder="Enter headline..." required />
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Category</label>
                  <select class="form-control" name="category">
                    <option>Announcement</option>
                    <option>Research</option>
                    <option>Achievement</option>
                    <option>Event</option>
                    <option>General</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Publish Date</label>
                  <input type="date" class="form-control" name="publish_date" value="<?php echo date('Y-m-d'); ?>" />
                </div>
              </div>

              <div class="form-group">
                <label>Status</label>
                <select class="form-control" name="status">
                  <option value="published">Published</option>
                  <option value="draft">Save as Draft</option>
                </select>
              </div>

              <div class="form-group">
                <label>Content</label>
                <textarea class="form-control" name="content" rows="5" placeholder="Draft your content here..." required></textarea>
              </div>

              <div class="form-group">
                <label>Cover Image URL (Optional)</label>
                <input type="text" class="form-control" name="featured_image" placeholder="https://example.com/image.jpg" />
              </div>

              <div class="form-actions">
                <button type="submit" name="publish_news" class="btn-primary flex-1">
                  Publish News
                </button>
                <button type="submit" name="publish_news" value="draft" class="btn-secondary">Save Draft</button>
              </div>
            </form>
          </div>
        </section>

        <section class="tables-section">
          <!-- Recent Posts Table -->
          <div class="card table-card">
            <div class="table-header">
              <h3>Recent Posts</h3>
              <div class="filter-wrapper">
                <span class="material-symbols-outlined filter-icon">filter_list</span>
                <select class="filter-select" id="categoryFilter" onchange="filterNews()">
                  <option>All Categories</option>
                  <option>Announcement</option>
                  <option>Research</option>
                  <option>Achievement</option>
                  <option>Event</option>
                </select>
              </div>
            </div>
            <div class="table-responsive">
              <table class="data-table" id="newsTable">
                <thead>
                  <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recent_news as $news): ?>
                  <tr data-category="<?php echo strtolower($news['category']); ?>">
                    <td>
                      <div class="font-bold text-primary">
                        <?php echo htmlspecialchars($news['title']); ?>
                      </div>
                    </td>
                    <td><span class="badge badge-<?php echo strtolower($news['category']) == 'research' ? 'research' : (strtolower($news['category']) == 'achievement' ? 'achievement' : 'announcement'); ?>">
                      <?php echo ucfirst($news['category']); ?>
                    </span></td>
                    <td>
                      <div class="status-indicator status-<?php echo $news['status'] == 'published' ? 'published' : 'draft'; ?>">
                        <span class="dot"></span> <?php echo ucfirst($news['status']); ?>
                      </div>
                    </td>
                    <td class="text-variant"><?php echo date('M d, Y', strtotime($news['created_at'])); ?></td>
                    <td class="text-right">
                      <button class="btn-icon" onclick="editNews(<?php echo $news['news_id']; ?>)">
                        <span class="material-symbols-outlined">more_vert</span>
                      </button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Meeting Schedule Management -->
          <div class="card table-card">
            <div class="table-header">
              <div class="header-title-group">
                <span class="material-symbols-outlined icon-secondary">calendar_month</span>
                <h3>Meeting Schedule Management</h3>
              </div>
              <button class="btn-primary-small" onclick="openMeetingModal()">
                <span class="material-symbols-outlined">add</span>
                Schedule New Meeting
              </button>
            </div>
            
            <?php if ($meeting_success): ?>
            <div class="alert-success" style="margin: 1rem;">
              <span class="material-symbols-outlined">check_circle</span>
              <span><?php echo $meeting_success; ?></span>
              <button class="alert-close" onclick="this.parentElement.style.display='none'">×</button>
            </div>
            <?php endif; ?>
            
            <?php if ($meeting_error): ?>
            <div class="alert-error" style="margin: 1rem;">
              <span class="material-symbols-outlined">error</span>
              <span><?php echo $meeting_error; ?></span>
              <button class="alert-close" onclick="this.parentElement.style.display='none'">×</button>
            </div>
            <?php endif; ?>

            <div class="table-responsive">
              <table class="data-table border-rows">
                <thead>
                  <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Room</th>
                    <th>Status</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($upcoming_meetings)): ?>
                  <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--outline);">
                      No upcoming meetings scheduled.
                    </td>
                  </tr>
                  <?php else: ?>
                    <?php foreach ($upcoming_meetings as $meeting): ?>
                    <tr>
                      <td>
                        <div class="font-bold text-primary">
                          <?php echo htmlspecialchars($meeting['title']); ?>
                        </div>
                      </td>
                      <td class="text-variant"><?php echo date('M d, Y', strtotime($meeting['meeting_date'])); ?></td>
                      <td class="text-variant"><?php echo date('h:i A', strtotime($meeting['start_time'])); ?></td>
                      <td>
                        <span class="badge badge-announcement"><?php echo htmlspecialchars($meeting['location'] ?: 'TBD'); ?></span>
                      </td>
                      <td>
                        <div class="status-indicator status-primary">
                          <span class="dot"></span> <?php echo ucfirst($meeting['status']); ?>
                        </div>
                      </td>
                      <td class="text-right">
                        <button class="btn-icon" onclick="editMeeting(<?php echo $meeting['meeting_id']; ?>)">
                          <span class="material-symbols-outlined">more_vert</span>
                        </button>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>
      </div>
    </main>

    <!-- Meeting Modal -->
    <div id="meetingModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
      <div style="background: white; border-radius: 1rem; padding: 2rem; width: 90%; max-width: 500px;">
        <h3 style="margin-bottom: 1rem;">Schedule New Meeting</h3>
        <form method="POST" action="">
          <div class="form-group">
            <label>Meeting Title</label>
            <input type="text" class="form-control" name="meeting_title" required />
          </div>
          <div class="form-group">
            <label>Date</label>
            <input type="date" class="form-control" name="meeting_date" required />
          </div>
          <div class="form-group">
            <label>Time</label>
            <input type="time" class="form-control" name="meeting_time" required />
          </div>
          <div class="form-group">
            <label>Room / Location</label>
            <input type="text" class="form-control" name="meeting_room" placeholder="e.g., Boardroom A" />
          </div>
          <div class="form-group">
            <label>Description (Optional)</label>
            <textarea class="form-control" name="meeting_description" rows="3"></textarea>
          </div>
          <div class="form-actions" style="margin-top: 1rem;">
            <button type="submit" name="schedule_meeting" class="btn-primary flex-1">Schedule Meeting</button>
            <button type="button" class="btn-secondary" onclick="closeMeetingModal()">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <script>
      function filterNews() {
        const filter = document.getElementById('categoryFilter').value.toLowerCase();
        const rows = document.querySelectorAll('#newsTable tbody tr');
        
        rows.forEach(row => {
          const category = row.getAttribute('data-category');
          if (filter === 'all categories' || category === filter) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      }
      
      function showManagementView() {
        // Already on management view
        console.log('Management view');
      }
      
      function openMeetingModal() {
        document.getElementById('meetingModal').style.display = 'flex';
      }
      
      function closeMeetingModal() {
        document.getElementById('meetingModal').style.display = 'none';
      }
      
      function editNews(newsId) {
        alert('Edit news feature coming soon. ID: ' + newsId);
      }
      
      function editMeeting(meetingId) {
        alert('Edit meeting feature coming soon. ID: ' + meetingId);
      }
      
      // Close modal when clicking outside
      document.getElementById('meetingModal').addEventListener('click', function(e) {
        if (e.target === this) {
          closeMeetingModal();
        }
      });
    </script>
</body>
</html>