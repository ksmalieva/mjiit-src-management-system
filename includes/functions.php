<?php
// includes/functions.php - Helper functions for SPRINT 1

// Log user activity
function log_activity($user_id, $action, $details = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    return db_insert('system_logs', [
        'user_id' => $user_id,
        'action' => $action,
        'details' => $details,
        'ip_address' => $ip,
        'user_agent' => $user_agent
    ]);
}

// Sanitize input
function sanitize($input) {
    if (is_null($input)) return '';
    return htmlspecialchars(strip_tags(trim($input)));
}

// Generate unique token
function generate_token() {
    return bin2hex(random_bytes(32));
}

// Get user by ID
function get_user($user_id) {
    return db_fetch_one("SELECT * FROM users WHERE user_id = ?", [$user_id]);
}

// Get all users (for role management)
function get_all_users() {
    return db_fetch_all("SELECT user_id, username, email, full_name, role, status, created_at FROM users ORDER BY created_at DESC");
}

// Get all collaborations with filters
function get_collaborations($filters = []) {
    $sql = "SELECT c.*, u.full_name as created_by_name 
            FROM collaborations c
            LEFT JOIN users u ON c.created_by = u.user_id
            WHERE 1=1";
    $params = [];
    
    if (!empty($filters['search'])) {
        $sql .= " AND (c.partner_name LIKE ? OR c.description LIKE ? OR c.contact_person LIKE ?)";
        $search = "%{$filters['search']}%";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }
    
    if (!empty($filters['type'])) {
        $sql .= " AND c.partner_type = ?";
        $params[] = $filters['type'];
    }
    
    if (!empty($filters['status'])) {
        $sql .= " AND c.status = ?";
        $params[] = $filters['status'];
    }
    
    $sql .= " ORDER BY c.created_at DESC";
    
    return db_fetch_all($sql, $params);
}

// Get single collaboration
function get_collaboration($collab_id) {
    $result = db_fetch_one(
        "SELECT c.*, u.full_name as created_by_name 
         FROM collaborations c
         LEFT JOIN users u ON c.created_by = u.user_id
         WHERE c.collab_id = ?",
        [$collab_id]
    );
    return $result;
}

// Check if email exists
function email_exists($email) {
    $result = db_fetch_one("SELECT user_id FROM users WHERE email = ?", [$email]);
    return $result !== null;
}

// Check if username exists
function username_exists($username) {
    $result = db_fetch_one("SELECT user_id FROM users WHERE username = ?", [$username]);
    return $result !== null;
}

// Update user role
function update_user_role($user_id, $role) {
    return db_update('users', ['role' => $role], 'user_id = ?', [$user_id]);
}

// Update user status
function update_user_status($user_id, $status) {
    return db_update('users', ['status' => $status], 'user_id = ?', [$user_id]);
}

// Get user by reset token
function get_user_by_reset_token($token) {
    return db_fetch_one(
        "SELECT user_id FROM users WHERE reset_token = ? AND reset_expires > NOW()",
        [$token]
    );
}

// Update user password
function update_user_password($user_id, $password_hash) {
    return db_update('users', 
        ['password_hash' => $password_hash, 'reset_token' => null, 'reset_expires' => null],
        'user_id = ?',
        [$user_id]
    );
}

// Get statistics for dashboard
function get_stats() {
    return [
        'total_users' => db_count('users'),
        'pending_users' => db_count('users', "status = 'pending'"),
        'total_collaborations' => db_count('collaborations'),
        'active_collaborations' => db_count('collaborations', "status = 'active'")
    ];
}

// Get recent collaborations
function get_recent_collaborations($limit = 5) {
    return db_fetch_all(
        "SELECT * FROM collaborations ORDER BY created_at DESC LIMIT ?",
        [$limit]
    );
}

// Helper function for time ago
function time_ago($timestamp) {
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return $diff . ' sec ago';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' min ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return date('M d', $timestamp);
    };
}

function get_all_rooms() {
    return db_fetch_all("SELECT * FROM rooms WHERE is_available = TRUE ORDER BY room_name");
}

// Get room by ID
function get_room($room_id) {
    return db_fetch_one("SELECT * FROM rooms WHERE room_id = ?", [$room_id]);
}

// Check if room is available for booking
function is_room_available($room_id, $booking_date, $start_time, $end_time, $exclude_booking_id = null) {
    $sql = "SELECT COUNT(*) as count FROM bookings 
            WHERE room_id = ? 
            AND booking_date = ? 
            AND status IN ('pending', 'approved')
            AND (
                (start_time <= ? AND end_time > ?) OR
                (start_time < ? AND end_time >= ?) OR
                (start_time >= ? AND end_time <= ?)
            )";
    
    $params = [$room_id, $booking_date, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time];
    
    if ($exclude_booking_id) {
        $sql .= " AND booking_id != ?";
        $params[] = $exclude_booking_id;
    }
    
    $result = db_fetch_one($sql, $params);
    return $result['count'] == 0;
}

// Create a new booking
function create_booking($room_id, $user_id, $booking_date, $start_time, $end_time, $purpose, $attendees_count = 0, $special_requirements = null) {
    // Generate unique booking reference
    $reference = 'BK-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    return db_insert('bookings', [
        'booking_reference' => $reference,
        'room_id' => $room_id,
        'user_id' => $user_id,
        'booking_date' => $booking_date,
        'start_time' => $start_time,
        'end_time' => $end_time,
        'purpose' => $purpose,
        'attendees_count' => $attendees_count,
        'special_requirements' => $special_requirements,
        'status' => 'pending'
    ]);
}

// Get user's bookings
function get_user_bookings($user_id, $limit = null) {
    $sql = "SELECT b.*, r.room_name, r.room_code, r.capacity 
            FROM bookings b
            JOIN rooms r ON b.room_id = r.room_id
            WHERE b.user_id = ?
            ORDER BY b.booking_date DESC, b.start_time DESC";
    
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    
    return db_fetch_all($sql, [$user_id]);
}

// Get pending bookings (for approval)
function get_pending_bookings() {
    return db_fetch_all(
        "SELECT b.*, r.room_name, r.room_code, u.full_name as requester_name, u.email as requester_email
         FROM bookings b
         JOIN rooms r ON b.room_id = r.room_id
         JOIN users u ON b.user_id = u.user_id
         WHERE b.status = 'pending'
         ORDER BY b.booking_date ASC, b.start_time ASC"
    );
}

// Approve booking
function approve_booking($booking_id, $approved_by) {
    $result = db_update('bookings', [
        'status' => 'approved',
        'approved_by' => $approved_by,
        'approved_at' => date('Y-m-d H:i:s')
    ], 'booking_id = ?', [$booking_id]);
    
    if ($result) {
        // Get booking details for notification
        $booking = db_fetch_one("SELECT user_id FROM bookings WHERE booking_id = ?", [$booking_id]);
        if ($booking) {
            send_notification($booking['user_id'], 'Booking Approved', 'Your room booking has been approved.', 'booking', $booking_id);
        }
    }
    
    return $result;
}

// Reject booking
function reject_booking($booking_id, $approved_by, $reason = null) {
    return db_update('bookings', [
        'status' => 'rejected',
        'approved_by' => $approved_by,
        'approved_at' => date('Y-m-d H:i:s'),
        'cancellation_reason' => $reason
    ], 'booking_id = ?', [$booking_id]);
}

// Cancel booking
function cancel_booking($booking_id, $cancelled_by, $reason = null) {
    return db_update('bookings', [
        'status' => 'cancelled',
        'cancelled_by' => $cancelled_by,
        'cancelled_at' => date('Y-m-d H:i:s'),
        'cancellation_reason' => $reason
    ], 'booking_id = ?', [$booking_id]);
}

// Get booking statistics
function get_booking_stats() {
    $total = db_count('bookings');
    $pending = db_count('bookings', "status = 'pending'");
    $approved = db_count('bookings', "status = 'approved'");
    $completed = db_count('bookings', "status = 'completed'");
    $cancelled = db_count('bookings', "status = 'cancelled'");
    
    return [
        'total' => $total,
        'pending' => $pending,
        'approved' => $approved,
        'completed' => $completed,
        'cancelled' => $cancelled
    ];
}

// Get today's bookings
function get_today_bookings() {
    $today = date('Y-m-d');
    return db_fetch_all(
        "SELECT b.*, r.room_name, u.full_name as booked_by
         FROM bookings b
         JOIN rooms r ON b.room_id = r.room_id
         JOIN users u ON b.user_id = u.user_id
         WHERE b.booking_date = ? AND b.status IN ('approved', 'pending')
         ORDER BY b.start_time ASC",
        [$today]
    );
}

// Get bookings by date
function get_bookings_by_date($date) {
    return db_fetch_all(
        "SELECT b.*, r.room_name, u.full_name as booked_by
         FROM bookings b
         JOIN rooms r ON b.room_id = r.room_id
         JOIN users u ON b.user_id = u.user_id
         WHERE b.booking_date = ? AND b.status IN ('approved', 'pending')
         ORDER BY b.start_time ASC",
        [$date]
    );
}

// Send notification function (if not exists)
function send_notification($user_id, $title, $message, $type = 'system', $related_id = null) {
    return db_insert('notifications', [
        'user_id' => $user_id,
        'title' => $title,
        'message' => $message,
        'type' => $type,
        'related_id' => $related_id,
        'is_read' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ]);
}

?>