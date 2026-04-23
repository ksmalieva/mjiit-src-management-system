<?php
// staff/bookings.php - Booking Space Management
require_once dirname(__DIR__) . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'auth/login.php');
    exit();
}

$user_role = $_SESSION['user_role'];
if ($user_role != 'staff' && $user_role != 'admin') {
    die('Access denied. Staff or Admin privileges required.');
}

// Handle booking actions
$success = '';
$error = '';

// Approve booking (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_booking']) && $user_role == 'admin') {
    $booking_id = (int)$_POST['booking_id'];
    if (approve_booking($booking_id, $_SESSION['user_id'])) {
        $success = "Booking approved successfully!";
    } else {
        $error = "Failed to approve booking";
    }
}

// Decline booking (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decline_booking']) && $user_role == 'admin') {
    $booking_id = (int)$_POST['booking_id'];
    if (reject_booking($booking_id, $_SESSION['user_id'], $_POST['reason'] ?? 'Declined by admin')) {
        $success = "Booking declined.";
    } else {
        $error = "Failed to decline booking";
    }
}

// Cancel booking (staff/admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    if (cancel_booking($booking_id, $_SESSION['user_id'], $_POST['reason'] ?? 'User cancelled')) {
        $success = "Booking cancelled.";
    } else {
        $error = "Failed to cancel booking";
    }
}

// Get all rooms
$rooms = get_all_rooms();

// Get today's date for display
$today = date('Y-m-d');
$current_date = date('l, M d');

// Get bookings for timeline display (today's bookings)
$today_bookings = db_fetch_all(
    "SELECT b.*, r.room_name, r.room_code, u.full_name as booked_by 
     FROM bookings b
     JOIN rooms r ON b.room_id = r.room_id
     JOIN users u ON b.user_id = u.user_id
     WHERE b.booking_date = ? AND b.status IN ('approved', 'pending')
     ORDER BY b.start_time ASC",
    [$today]
);

// Get pending approvals (admin only)
$pending_approvals = [];
if ($user_role == 'admin') {
    $pending_approvals = get_pending_bookings();
}

// Get booking statistics
$total_rooms = count($rooms);
$active_now = db_count('bookings', "booking_date = CURDATE() AND start_time <= CURTIME() AND end_time >= CURTIME() AND status = 'approved'");
$total_bookings = db_count('bookings');
$avg_occupancy = $total_rooms > 0 ? round(($active_now / $total_rooms) * 100) : 0;

// Get user's own bookings
$my_bookings = get_user_bookings($_SESSION['user_id'], 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Booking Space - SRC Portal</title>

    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="styles/booking.css" />
    
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
        
        .booking-form-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .booking-form-content {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--on-surface-variant);
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--surface-container-high);
            border-radius: 0.5rem;
            font-family: inherit;
        }
        
        .form-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-container));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
        }
        
        .btn-secondary {
            background: var(--surface-container-high);
            color: var(--on-surface-variant);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
        }
        
        .btn-danger {
            background: #dc2626;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            cursor: pointer;
        }
        
        .booking-status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.625rem;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #d97706;
        }
        
        .status-approved {
            background-color: #d1fae5;
            color: #059669;
        }
        
        .status-cancelled {
            background-color: #fee2e2;
            color: #dc2626;
        }
        
        .my-bookings-section {
            margin-top: 2rem;
        }
        
        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--surface-container-lowest);
            border-radius: 0.75rem;
            overflow: hidden;
        }
        
        .bookings-table th, .bookings-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--surface-container-high);
        }
        
        .bookings-table th {
            background: var(--surface-container-low);
            font-size: 0.75rem;
            font-weight: 600;
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
        <a href="projects.php" class="nav-item">
          <span class="material-symbols-outlined">work</span> Projects
        </a>
        <a href="researchers.php" class="nav-item">
          <span class="material-symbols-outlined">school</span> Researchers
        </a>
        <a href="agreements.php" class="nav-item">
          <span class="material-symbols-outlined">description</span> Agreements
        </a>
        <a href="#" class="nav-item active">
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
          SRC Staff Dashboard
          <span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle">chevron_right</span>
          <span class="active">Booking Space</span>
        </div>
        <div class="search-bar">
          <span class="material-symbols-outlined">search</span>
          <input type="text" id="searchBookings" placeholder="Search facilities..." onkeyup="filterRooms()" />
        </div>
      </div>

      <div class="header-right">
        <div class="header-icons">
          <button>
            <span class="material-symbols-outlined">notifications</span>
          </button>
          <button>
            <span class="material-symbols-outlined">help_outline</span>
          </button>
        </div>
        <div class="divider"></div>
        <div class="user-profile">
          <div class="user-info">
            <div class="role"><?php echo ucfirst($user_role); ?></div>
            <div class="level"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
          </div>
          <div style="width: 2.25rem; height: 2.25rem; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; color: white;">
            <?php echo substr($_SESSION['user_name'], 0, 2); ?>
          </div>
        </div>
      </div>
    </header>

    <main class="main-content">
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

      <div class="dashboard-grid">
        <section class="schedule-column">
          <div class="section-header">
            <div>
              <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--primary);">
                Facility Schedule
              </h2>
              <p style="font-size: 0.875rem; color: #64748b">
                Managing real-time capacity for SRC research zones.
              </p>
            </div>
            <button class="btn-primary" onclick="openBookingModal()">
              <span class="material-symbols-outlined">add</span> New Booking
            </button>
          </div>

          <div class="timeline-container">
            <div class="timeline-controls">
              <div class="date-nav">
                <button onclick="changeDate(-1)">
                  <span class="material-symbols-outlined">arrow_back_ios</span>
                </button>
                <span class="current-date" id="currentDate"><?php echo $current_date; ?></span>
                <button onclick="changeDate(1)">
                  <span class="material-symbols-outlined">arrow_forward_ios</span>
                </button>
              </div>
              <button class="jump-today" onclick="jumpToToday()">
                <span class="material-symbols-outlined">today</span> Jump to Today
              </button>
            </div>

            <div class="timeline-grid" id="timelineGrid">
              <div class="timeline-wrapper">
                <div class="time-markers">
                  <div class="marker">08:00</div>
                  <div class="marker">10:00</div>
                  <div class="marker">12:00</div>
                  <div class="marker">14:00</div>
                  <div class="marker">16:00</div>
                  <div class="marker">18:00</div>
                </div>

                <?php foreach ($rooms as $room): ?>
                <?php
                // Get bookings for this room today
                $room_bookings = array_filter($today_bookings, function($b) use ($room) {
                    return $b['room_id'] == $room['room_id'];
                });
                ?>
                <div class="room-row" data-room-name="<?php echo strtolower($room['room_name']); ?>">
                  <div class="room-label">
                    <span class="name"><?php echo htmlspecialchars($room['room_name']); ?></span>
                    <span class="cap">Cap: <?php echo $room['capacity']; ?> pax</span>
                  </div>
                  <div class="timeline-track">
                    <?php if (empty($room_bookings)): ?>
                      <div class="empty-track">No bookings for this period</div>
                    <?php else: ?>
                      <?php foreach ($room_bookings as $booking): ?>
                      <?php
                      $start_hour = (int)substr($booking['start_time'], 0, 2);
                      $start_minute = (int)substr($booking['start_time'], 3, 2);
                      $end_hour = (int)substr($booking['end_time'], 0, 2);
                      $end_minute = (int)substr($booking['end_time'], 3, 2);
                      
                      $start_percent = (($start_hour - 8) * 60 + $start_minute) / (10 * 60) * 100;
                      $duration_minutes = ($end_hour - $start_hour) * 60 + ($end_minute - $start_minute);
                      $width_percent = ($duration_minutes / (10 * 60)) * 100;
                      
                      $block_class = $booking['status'] == 'approved' ? 'block-primary' : 'block-tertiary';
                      ?>
                      <div class="booking-block <?php echo $block_class; ?>" style="left: <?php echo max(0, $start_percent); ?>%; width: <?php echo min(100 - $start_percent, $width_percent); ?>%">
                        <span class="b-title"><?php echo htmlspecialchars($booking['purpose']); ?></span>
                        <span class="b-time"><?php echo date('h:i A', strtotime($booking['start_time'])); ?> - <?php echo date('h:i A', strtotime($booking['end_time'])); ?></span>
                      </div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="kpi-grid">
            <div class="kpi-card">
              <div class="kpi-label">Total Rooms</div>
              <div class="kpi-value"><?php echo $total_rooms; ?></div>
            </div>
            <div class="kpi-card">
              <div class="kpi-label">Active Now</div>
              <div class="kpi-value secondary"><?php echo $active_now; ?></div>
            </div>
            <div class="kpi-card">
              <div class="kpi-label">Avg Occupancy</div>
              <div class="kpi-value"><?php echo $avg_occupancy; ?>%</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-label">Total Bookings</div>
              <div class="kpi-value secondary"><?php echo $total_bookings; ?></div>
            </div>
          </div>
        </section>

        <section class="approvals-column">
          <div class="section-header">
            <h2 class="section-title">
              <span class="material-symbols-outlined" style="color: var(--secondary)">pending_actions</span>
              Pending Approvals
            </h2>
            <span class="badge"><?php echo count($pending_approvals); ?> NEW</span>
          </div>

          <?php if (empty($pending_approvals)): ?>
          <div class="request-card">
            <div style="text-align: center; padding: 2rem; color: #94a3b8;">
              <span class="material-symbols-outlined" style="font-size: 48px;">check_circle</span>
              <p style="margin-top: 0.5rem;">No pending approvals</p>
            </div>
          </div>
          <?php else: ?>
            <?php foreach ($pending_approvals as $booking): ?>
            <div class="request-card" id="req-<?php echo $booking['booking_id']; ?>">
              <div class="card-header">
                <div class="card-title-group">
                  <div class="icon-box">
                    <span class="material-symbols-outlined">event_seat</span>
                  </div>
                  <div>
                    <div class="room-name"><?php echo htmlspecialchars($booking['room_name']); ?></div>
                    <div class="requester">Requested by <?php echo htmlspecialchars($booking['requester_name']); ?></div>
                  </div>
                </div>
                <div class="time-ago"><?php echo time_ago(strtotime($booking['created_at'])); ?></div>
              </div>

              <div class="card-details">
                <div class="detail-row">
                  <span class="detail-label">Date</span>
                  <span class="detail-value"><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Duration</span>
                  <span class="detail-value"><?php echo date('h:i A', strtotime($booking['start_time'])); ?> - <?php echo date('h:i A', strtotime($booking['end_time'])); ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Purpose</span>
                  <span class="detail-value" style="font-style: italic"><?php echo htmlspecialchars($booking['purpose']); ?></span>
                </div>
              </div>

              <div class="card-actions">
                <form method="POST" style="flex: 1;">
                  <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                  <button type="submit" name="decline_booking" class="btn btn-decline action-btn" onclick="return confirm('Decline this booking?')">Decline</button>
                </form>
                <form method="POST" style="flex: 1;">
                  <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                  <button type="submit" name="approve_booking" class="btn btn-approve action-btn">Approve</button>
                </form>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <button class="view-all-btn" onclick="location.href='bookings-list.php'">
            View All Pending Requests
          </button>
        </section>
      </div>

      <!-- My Bookings Section -->
      <div class="my-bookings-section">
        <div class="section-header">
          <h3 class="section-title">My Bookings</h3>
        </div>
        <div class="table-responsive">
          <table class="bookings-table">
            <thead>
              <tr>
                <th>Reference</th>
                <th>Room</th>
                <th>Date</th>
                <th>Time</th>
                <th>Purpose</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($my_bookings)): ?>
              <tr>
                <td colspan="7" style="text-align: center; padding: 2rem;">No bookings found</td>
              </tr>
              <?php else: ?>
                <?php foreach ($my_bookings as $booking): ?>
                <tr>
                  <td class="font-mono"><?php echo $booking['booking_reference']; ?></td>
                  <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                  <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                  <td><?php echo date('h:i A', strtotime($booking['start_time'])); ?> - <?php echo date('h:i A', strtotime($booking['end_time'])); ?></td>
                  <td><?php echo htmlspecialchars(substr($booking['purpose'], 0, 30)); ?>...</td>
                  <td>
                    <span class="booking-status status-<?php echo $booking['status']; ?>">
                      <?php echo ucfirst($booking['status']); ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($booking['status'] == 'pending'): ?>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                      <button type="submit" name="cancel_booking" class="btn-danger" onclick="return confirm('Cancel this booking?')">Cancel</button>
                    </form>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- Booking Modal -->
    <div id="bookingModal" class="booking-form-modal">
      <div class="booking-form-content">
        <h3 style="margin-bottom: 1rem;">Book a Room</h3>
        <form method="POST" action="bookings.php?action=create">
          <div class="form-group">
            <label>Select Room *</label>
            <select name="room_id" required>
              <option value="">Choose a room...</option>
              <?php foreach ($rooms as $room): ?>
                <option value="<?php echo $room['room_id']; ?>"><?php echo htmlspecialchars($room['room_name']); ?> (Capacity: <?php echo $room['capacity']; ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Date *</label>
            <input type="date" name="booking_date" min="<?php echo date('Y-m-d'); ?>" required>
          </div>
          <div class="form-group">
            <label>Start Time *</label>
            <input type="time" name="start_time" required>
          </div>
          <div class="form-group">
            <label>End Time *</label>
            <input type="time" name="end_time" required>
          </div>
          <div class="form-group">
            <label>Purpose *</label>
            <textarea name="purpose" rows="2" placeholder="Describe the purpose..." required></textarea>
          </div>
          <div class="form-group">
            <label>Expected Attendees</label>
            <input type="number" name="attendees_count" value="5">
          </div>
          <div class="form-actions">
            <button type="submit" name="create_booking" class="btn-primary">Submit Request</button>
            <button type="button" class="btn-secondary" onclick="closeBookingModal()">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <script>
      function openBookingModal() {
        document.getElementById('bookingModal').style.display = 'flex';
      }
      
      function closeBookingModal() {
        document.getElementById('bookingModal').style.display = 'none';
      }
      
      function filterRooms() {
        const input = document.getElementById('searchBookings');
        const filter = input.value.toLowerCase();
        const rows = document.querySelectorAll('.room-row');
        
        rows.forEach(row => {
          const roomName = row.getAttribute('data-room-name') || '';
          if (roomName.indexOf(filter) > -1) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      }
      
      function changeDate(days) {
        // Simple date navigation - would need AJAX for full functionality
        alert('Date navigation - would load bookings for selected date');
      }
      
      function jumpToToday() {
        alert('Jump to today - would reload current date bookings');
      }
      
      // Close modal when clicking outside
      document.getElementById('bookingModal').addEventListener('click', function(e) {
        if (e.target === this) {
          closeBookingModal();
        }
      });
    </script>
</body>
</html>