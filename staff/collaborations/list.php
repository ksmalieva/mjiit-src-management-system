<?php
// staff/collaborations/list.php - Role-aware version
require_once dirname(__DIR__, 2) . '/config.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'auth/login.php');
    exit();
}

// Get user role
$user_role = $_SESSION['user_role'];

// Allow both staff and admin
if ($user_role != 'staff' && $user_role != 'admin') {
    die('Access denied. Staff or Admin privileges required.');
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';

$filters = [
    'search' => $search,
    'type' => $type,
    'status' => $status
];

$collaborations = get_collaborations($filters);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Collaborations - SRC System</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="dashboard-container">
        <!-- ROLE-AWARE SIDEBAR: Show different menu based on user role -->
        <div class="sidebar">
            <?php if ($user_role == 'admin'): ?>
                <!-- ADMIN MENU -->
                <h3>Admin Menu</h3>
                <a href="<?php echo SITE_URL; ?>admin/dashboard.php">Dashboard</a>
                <a href="<?php echo SITE_URL; ?>admin/role-management.php">Role Management</a>
                <a href="<?php echo SITE_URL; ?>staff/collaborations/list.php" class="active">Collaborations</a>
                <a href="<?php echo SITE_URL; ?>staff/collaborations/add.php">Add Collaboration</a>
            <?php else: ?>
                <!-- STAFF MENU -->
                <h3>Staff Menu</h3>
                <a href="<?php echo SITE_URL; ?>staff/dashboard.php">Dashboard</a>
                <a href="<?php echo SITE_URL; ?>staff/collaborations/list.php" class="active">Collaborations</a>
                <a href="<?php echo SITE_URL; ?>staff/collaborations/add.php">Add Collaboration</a>
            <?php endif; ?>
        </div>
        
        <div class="main-content">
            <h1>Collaboration Management 
                <?php if ($user_role == 'admin'): ?>
                    <span style="font-size: 14px; color: #e74c3c;">(Admin View)</span>
                <?php endif; ?>
            </h1>
            
            <!-- Search and Filter Section -->
            <div class="filter-section">
                <form method="GET" action="" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <input type="text" name="search" placeholder="Search by partner, contact, or description..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <select name="type">
                                <option value="">All Types</option>
                                <option value="industry" <?php echo $type == 'industry' ? 'selected' : ''; ?>>Industry</option>
                                <option value="university" <?php echo $type == 'university' ? 'selected' : ''; ?>>University</option>
                                <option value="research_institute" <?php echo $type == 'research_institute' ? 'selected' : ''; ?>>Research Institute</option>
                                <option value="government" <?php echo $type == 'government' ? 'selected' : ''; ?>>Government</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="expired" <?php echo $status == 'expired' ? 'selected' : ''; ?>>Expired</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <a href="list.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="table-container">
                <?php if (empty($collaborations)): ?>
                    <div class="alert alert-info">No collaborations found.</div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Partner Name</th>
                                <th>Type</th>
                                <th>Agreement</th>
                                <th>Period</th>
                                <th>Status</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($collaborations as $collab): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($collab['partner_name']); ?></strong></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $collab['partner_type'])); ?></td>
                                <td><?php echo htmlspecialchars($collab['agreement_type'] ?? '-'); ?></td>
                                <td>
                                    <?php 
                                    if ($collab['start_date']) {
                                        echo date('M Y', strtotime($collab['start_date']));
                                        if ($collab['end_date']) {
                                            echo ' - ' . date('M Y', strtotime($collab['end_date']));
                                        }
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $collab['status']; ?>">
                                        <?php echo ucfirst($collab['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($collab['contact_person']): ?>
                                        <?php echo htmlspecialchars($collab['contact_person']); ?><br>
                                        <small><?php echo htmlspecialchars($collab['contact_email'] ?? ''); ?></small>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit.php?id=<?php echo $collab['collab_id']; ?>" class="btn btn-sm">Edit</a>
                                    <a href="delete.php?id=<?php echo $collab['collab_id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Admin extra options (only visible to admin) -->
            <?php if ($user_role == 'admin'): ?>
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <strong>Admin Options:</strong>
                <a href="<?php echo SITE_URL; ?>admin/role-management.php" class="btn btn-sm">Manage Users</a>
                <a href="<?php echo SITE_URL; ?>admin/dashboard.php" class="btn btn-sm">Admin Dashboard</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>