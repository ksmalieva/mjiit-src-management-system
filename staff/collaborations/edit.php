<?php
// staff/collaborations/edit.php - Edit collaboration with delete option
require_once dirname(__DIR__, 2) . '/config.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'auth/login.php');
    exit();
}

$user_role = $_SESSION['user_role'];
if ($user_role != 'staff' && $user_role != 'admin') {
    die('Access denied. Staff or Admin privileges required.');
}

$collab_id = (int)($_GET['id'] ?? 0);
$collaboration = get_collaboration($collab_id);

if (!$collaboration) {
    header('Location: ../collaborations.php?error=notfound');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Update
    if (isset($_POST['update'])) {
        $partner_name = sanitize($_POST['partner_name']);
        $partner_type = $_POST['partner_type'];
        $agreement_type = sanitize($_POST['agreement_type']);
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $status = $_POST['status'];
        $description = sanitize($_POST['description']);
        $contact_person = sanitize($_POST['contact_person']);
        $contact_email = filter_var($_POST['contact_email'], FILTER_VALIDATE_EMAIL);
        $contact_phone = sanitize($_POST['contact_phone']);
        
        if (!$partner_name || !$partner_type) {
            $error = 'Partner name and type are required';
        } elseif ($contact_email && !$contact_email) {
            $error = 'Invalid email format';
        } else {
            $result = db_update('collaborations', [
                'partner_name' => $partner_name,
                'partner_type' => $partner_type,
                'agreement_type' => $agreement_type,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'status' => $status,
                'description' => $description,
                'contact_person' => $contact_person,
                'contact_email' => $contact_email,
                'contact_phone' => $contact_phone
            ], 'collab_id = ?', [$collab_id]);
            
            if ($result !== false) {
                log_activity($_SESSION['user_id'], 'collaboration_updated', "Updated collaboration ID $collab_id");
                $success = "Collaboration record updated successfully!";
                
                // Refresh data
                $collaboration = get_collaboration($collab_id);
            } else {
                $error = "Failed to update collaboration record";
            }
        }
    }
    
    // Handle Delete
    if (isset($_POST['delete'])) {
        $result = db_delete('collaborations', 'collab_id = ?', [$collab_id]);
        
        if ($result) {
            log_activity($_SESSION['user_id'], 'collaboration_deleted', "Deleted collaboration: {$collaboration['partner_name']}");
            header('Location: ../collaborations.php?deleted=1');
            exit();
        } else {
            $error = "Failed to delete collaboration record";
        }
    }
}

// Get current user info
$current_user = get_user($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Collaboration | MJIIT Sangaku Renkei</title>

    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
      rel="stylesheet"
    />

    <style>
        /* --- Variables & Theme --- */
        :root {
            --primary: #00436f;
            --primary-container: #005b94;
            --secondary: #006a6a;
            --secondary-container: #90efef;
            --on-secondary-container: #006e6e;
            --surface: #f7f9fb;
            --surface-container-lowest: #ffffff;
            --surface-container-low: #f2f4f6;
            --surface-container-high: #e6e8ea;
            --on-surface: #191c1e;
            --on-surface-variant: #414750;
            --outline: #717881;
            --outline-variant: #c1c7d1;
            --error: #ba1a1a;
            --error-container: #ffdad6;
            --success: #10b981;
            --success-container: #d1fae5;
            --warning: #f59e0b;
            
            --sidebar-width: 280px;
            --transition: all 0.2s ease-in-out;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--surface);
            color: var(--on-surface);
            min-height: 100vh;
        }

        h1, h2, h3, h4 {
            font-family: 'Manrope', sans-serif;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }

        /* --- Sidebar --- */
        .sidebar {
            width: var(--sidebar-width);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            background-color: #f8fafc;
            display: flex;
            flex-direction: column;
            padding: 1.5rem 0;
            border-right: 1px solid var(--surface-container-high);
            z-index: 50;
        }

        .sidebar-header {
            padding: 0 1.5rem;
            margin-bottom: 2rem;
        }

        .brand-logo {
            height: 3rem;
            width: auto;
            object-fit: contain;
        }

        .sidebar-nav {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: #64748b;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .nav-item:hover {
            color: var(--primary);
            background-color: #f1f5f9;
        }

        .nav-item.active {
            color: var(--primary);
            font-weight: 600;
            background-color: #f1f5f9;
            border-right: 4px solid #00c2cb;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 1rem 1rem 0;
            border-top: 1px solid var(--surface-container-high);
        }

        /* --- Main Content --- */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        /* --- Top Bar --- */
        .top-bar {
            position: sticky;
            top: 0;
            right: 0;
            left: var(--sidebar-width);
            height: 64px;
            background-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--surface-container-high);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
            z-index: 40;
        }

        .breadcrumb {
            font-family: 'Manrope', sans-serif;
            font-size: 0.875rem;
            color: #94a3b8;
        }

        .breadcrumb .active {
            color: var(--primary);
            font-weight: 700;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 50%;
            background-color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        /* --- Page Container --- */
        .page-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 1.875rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.025em;
        }

        .page-subtitle {
            color: var(--on-surface-variant);
            margin-top: 0.25rem;
        }

        /* --- Form Card --- */
        .form-card {
            background-color: var(--surface-container-lowest);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--on-surface-variant);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        input, select, textarea {
            padding: 0.75rem 1rem;
            background-color: var(--surface-container-low);
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-family: inherit;
            transition: var(--transition);
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 67, 111, 0.2);
        }

        textarea {
            resize: vertical;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--surface-container-high);
            justify-content: space-between;
        }

        .form-actions-left {
            display: flex;
            gap: 1rem;
        }

        .form-actions-right {
            display: flex;
            gap: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-container));
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: var(--surface-container-high);
            color: var(--on-surface-variant);
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }

        .btn-secondary:hover {
            background-color: var(--surface-container-highest);
        }

        .btn-danger {
            background-color: var(--error);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }

        .btn-danger:hover {
            background-color: #991b1b;
            transform: translateY(-1px);
        }

        /* Alerts */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background-color: var(--success-container);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background-color: var(--error-container);
            color: var(--error);
            border-left: 4px solid var(--error);
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary);
            margin: 1.5rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--surface-container-high);
        }

        .section-title:first-of-type {
            margin-top: 0;
        }

        .edit-badge {
            display: inline-block;
            background-color: var(--primary);
            color: white;
            font-size: 0.625rem;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            margin-left: 0.75rem;
            vertical-align: middle;
        }

        /* Delete Confirmation Modal */
        .delete-modal {
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

        .delete-modal-content {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            width: 90%;
            max-width: 450px;
            text-align: center;
        }

        .delete-modal-content .warning-icon {
            font-size: 3rem;
            color: var(--error);
            margin-bottom: 1rem;
        }

        .delete-modal-content h3 {
            margin-bottom: 0.5rem;
            color: var(--error);
        }

        .delete-modal-content p {
            color: var(--on-surface-variant);
            margin-bottom: 1.5rem;
        }

        .delete-modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-group.full-width {
                grid-column: span 1;
            }
            .page-container {
                padding: 1rem;
            }
            .form-actions {
                flex-direction: column;
            }
            .form-actions-left, .form-actions-right {
                justify-content: center;
            }
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
            <a href="<?php echo SITE_URL; ?>staff/settings.php" class="nav-item">
                <span class="material-symbols-outlined">settings</span>
                <span>Settings</span>
            </a>
            <a href="<?php echo SITE_URL; ?>auth/logout.php" class="nav-item">
                <span class="material-symbols-outlined">logout</span>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <div class="breadcrumb">
                <span>Collaborations</span>
                <span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle;">chevron_right</span>
                <span class="active">Edit Collaboration</span>
            </div>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <div class="user-avatar"><?php echo substr($_SESSION['user_name'], 0, 2); ?></div>
            </div>
        </header>

        <div class="page-container">
            <div class="page-header">
                <h1 class="page-title">
                    Edit Collaboration
                    <span class="edit-badge">ID: <?php echo str_pad($collab_id, 4, '0', STR_PAD_LEFT); ?></span>
                </h1>
                <p class="page-subtitle">Update collaboration details and information.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="material-symbols-outlined">error</span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span class="material-symbols-outlined">check_circle</span>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <form method="POST" action="" id="editForm">
                    <h3 class="section-title">Project Identity</h3>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label>Partner Name *</label>
                            <input type="text" name="partner_name" value="<?php echo htmlspecialchars($collaboration['partner_name']); ?>" placeholder="Enter partner organization name" required />
                        </div>
                        
                        <div class="form-group">
                            <label>Partner Type *</label>
                            <select name="partner_type" required>
                                <option value="industry" <?php echo $collaboration['partner_type'] == 'industry' ? 'selected' : ''; ?>>Industry</option>
                                <option value="university" <?php echo $collaboration['partner_type'] == 'university' ? 'selected' : ''; ?>>University</option>
                                <option value="research_institute" <?php echo $collaboration['partner_type'] == 'research_institute' ? 'selected' : ''; ?>>Research Institute</option>
                                <option value="government" <?php echo $collaboration['partner_type'] == 'government' ? 'selected' : ''; ?>>Government</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Agreement Type</label>
                            <input type="text" name="agreement_type" value="<?php echo htmlspecialchars($collaboration['agreement_type'] ?? ''); ?>" placeholder="MoU, MoA, Partnership, etc." />
                        </div>
                        
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="pending" <?php echo $collaboration['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="active" <?php echo $collaboration['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="completed" <?php echo $collaboration['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="expired" <?php echo $collaboration['status'] == 'expired' ? 'selected' : ''; ?>>Expired</option>
                            </select>
                        </div>
                    </div>

                    <h3 class="section-title">Timeline</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" value="<?php echo $collaboration['start_date']; ?>" />
                        </div>
                        
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" value="<?php echo $collaboration['end_date']; ?>" />
                        </div>
                    </div>

                    <h3 class="section-title">Description</h3>
                    <div class="form-group full-width">
                        <textarea name="description" rows="4" placeholder="Describe the collaboration scope, objectives, and expected outcomes..."><?php echo htmlspecialchars($collaboration['description'] ?? ''); ?></textarea>
                    </div>

                    <h3 class="section-title">Contact Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Contact Person</label>
                            <input type="text" name="contact_person" value="<?php echo htmlspecialchars($collaboration['contact_person'] ?? ''); ?>" placeholder="Full name" />
                        </div>
                        
                        <div class="form-group">
                            <label>Contact Email</label>
                            <input type="email" name="contact_email" value="<?php echo htmlspecialchars($collaboration['contact_email'] ?? ''); ?>" placeholder="email@example.com" />
                        </div>
                        
                        <div class="form-group">
                            <label>Contact Phone</label>
                            <input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($collaboration['contact_phone'] ?? ''); ?>" placeholder="+60 XX XXX XXXX" />
                        </div>
                    </div>

                    <div class="form-actions">
                        <div class="form-actions-left">
                            <button type="submit" name="update" class="btn-primary">
                                <span class="material-symbols-outlined">save</span>
                                Update Collaboration
                            </button>
                            <a href="../collaborations.php" class="btn-secondary">
                                <span class="material-symbols-outlined">cancel</span>
                                Cancel
                            </a>
                        </div>
                        <div class="form-actions-right">
                            <button type="button" class="btn-danger" onclick="openDeleteModal()">
                                <span class="material-symbols-outlined">delete</span>
                                Delete
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="delete-modal">
        <div class="delete-modal-content">
            <div class="warning-icon">
                <span class="material-symbols-outlined">warning</span>
            </div>
            <h3>Delete Collaboration</h3>
            <p>Are you sure you want to delete "<strong><?php echo htmlspecialchars($collaboration['partner_name']); ?></strong>"?</p>
            <p style="font-size: 0.875rem; color: var(--error);">This action cannot be undone!</p>
            <div class="delete-modal-actions">
                <form method="POST" action="">
                    <button type="submit" name="delete" class="btn-danger">
                        <span class="material-symbols-outlined">delete_forever</span>
                        Yes, Delete
                    </button>
                </form>
                <button type="button" class="btn-secondary" onclick="closeDeleteModal()">
                    <span class="material-symbols-outlined">cancel</span>
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
        function openDeleteModal() {
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>