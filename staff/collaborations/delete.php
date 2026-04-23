<?php
// staff/collaborations/delete.php - Delete collaboration confirmation
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
    header('Location: list.php?error=notfound');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $result = db_delete('collaborations', 'collab_id = ?', [$collab_id]);
    
    if ($result) {
        log_activity($_SESSION['user_id'], 'collaboration_deleted', "Deleted collaboration: {$collaboration['partner_name']}");
        header('Location: list.php?deleted=1');
        exit();
    } else {
        $error = "Failed to delete collaboration record";
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
    <title>Delete Collaboration | MJIIT Sangaku Renkei</title>

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
            --warning: #f59e0b;
            --warning-container: #fef3c7;
            --success: #10b981;
            --success-container: #d1fae5;
            
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
            max-width: 800px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 1.875rem;
            font-weight: 800;
            color: var(--error);
            letter-spacing: -0.025em;
        }

        .page-subtitle {
            color: var(--on-surface-variant);
            margin-top: 0.25rem;
        }

        /* --- Warning Card --- */
        .warning-card {
            background-color: var(--surface-container-lowest);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            border-left: 4px solid var(--error);
        }

        .warning-icon {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .warning-icon span {
            font-size: 4rem;
            color: var(--error);
        }

        .warning-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--error);
            text-align: center;
            margin-bottom: 1rem;
        }

        .warning-message {
            text-align: center;
            color: var(--on-surface-variant);
            margin-bottom: 2rem;
        }

        /* --- Info Card --- */
        .info-card {
            background-color: var(--surface-container-low);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .info-row {
            display: flex;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--surface-container-high);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            width: 120px;
            font-weight: 600;
            color: var(--on-surface-variant);
        }

        .info-value {
            flex: 1;
            font-weight: 500;
            color: var(--on-surface);
        }

        /* --- Form Actions --- */
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: center;
        }

        .btn-danger {
            background-color: var(--error);
            color: white;
            padding: 0.75rem 2rem;
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

        .btn-secondary {
            background-color: var(--surface-container-high);
            color: var(--on-surface-variant);
            padding: 0.75rem 2rem;
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

        /* Alerts */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-error {
            background-color: var(--error-container);
            color: var(--error);
            border-left: 4px solid var(--error);
        }

        @media (max-width: 768px) {
            .page-container {
                padding: 1rem;
            }
            .info-row {
                flex-direction: column;
                gap: 0.25rem;
            }
            .info-label {
                width: 100%;
            }
            .form-actions {
                flex-direction: column;
            }
            .btn-danger, .btn-secondary {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../../../Logo/SRC_logo.png" alt="SRC Logo" class="brand-logo" />
        </div>

        <nav class="sidebar-nav">
            <?php if ($user_role == 'admin'): ?>
                <a href="<?php echo SITE_URL; ?>admin/dashboard.php" class="nav-item">
                    <span class="material-symbols-outlined">dashboard</span>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo SITE_URL; ?>admin/role-management.php" class="nav-item">
                    <span class="material-symbols-outlined">manage_accounts</span>
                    <span>Role Management</span>
                </a>
                <a href="list.php" class="nav-item">
                    <span class="material-symbols-outlined">handshake</span>
                    <span>Collaborations</span>
                </a>
                <a href="add.php" class="nav-item">
                    <span class="material-symbols-outlined">add</span>
                    <span>Add Collaboration</span>
                </a>
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>staff/dashboard.php" class="nav-item">
                    <span class="material-symbols-outlined">dashboard</span>
                    <span>Dashboard</span>
                </a>
                <a href="list.php" class="nav-item">
                    <span class="material-symbols-outlined">handshake</span>
                    <span>Collaborations</span>
                </a>
                <a href="add.php" class="nav-item">
                    <span class="material-symbols-outlined">add</span>
                    <span>Add Collaboration</span>
                </a>
                <a href="<?php echo SITE_URL; ?>staff/bookings.php" class="nav-item">
                    <span class="material-symbols-outlined">event_seat</span>
                    <span>Booking Space</span>
                </a>
                <a href="<?php echo SITE_URL; ?>staff/news.php" class="nav-item">
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
                <span class="active">Delete Collaboration</span>
            </div>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <div class="user-avatar"><?php echo substr($_SESSION['user_name'], 0, 2); ?></div>
            </div>
        </header>

        <div class="page-container">
            <div class="page-header">
                <h1 class="page-title">Delete Collaboration</h1>
                <p class="page-subtitle">This action cannot be undone. Please confirm.</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <span class="material-symbols-outlined">error</span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <div class="warning-card">
                <div class="warning-icon">
                    <span class="material-symbols-outlined">warning</span>
                </div>
                <h2 class="warning-title">Are you sure?</h2>
                <p class="warning-message">
                    You are about to permanently delete the following collaboration record. 
                    This action cannot be undone.
                </p>

                <div class="info-card">
                    <div class="info-row">
                        <div class="info-label">Partner Name</div>
                        <div class="info-value"><strong><?php echo htmlspecialchars($collaboration['partner_name']); ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Partner Type</div>
                        <div class="info-value"><?php echo ucfirst(str_replace('_', ' ', $collaboration['partner_type'])); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Agreement Type</div>
                        <div class="info-value"><?php echo htmlspecialchars($collaboration['agreement_type'] ?: 'Not specified'); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; background-color: <?php 
                                echo $collaboration['status'] == 'active' ? 'var(--success-container)' : ($collaboration['status'] == 'pending' ? 'var(--warning-container)' : 'var(--surface-container-high)');
                            ?>; color: <?php 
                                echo $collaboration['status'] == 'active' ? 'var(--success)' : ($collaboration['status'] == 'pending' ? 'var(--warning)' : 'var(--on-surface-variant)');
                            ?>;">
                                <?php echo ucfirst($collaboration['status']); ?>
                            </span>
                        </div>
                    </div>
                    <?php if ($collaboration['start_date']): ?>
                    <div class="info-row">
                        <div class="info-label">Start Date</div>
                        <div class="info-value"><?php echo date('d M Y', strtotime($collaboration['start_date'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($collaboration['end_date']): ?>
                    <div class="info-row">
                        <div class="info-label">End Date</div>
                        <div class="info-value"><?php echo date('d M Y', strtotime($collaboration['end_date'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($collaboration['contact_person']): ?>
                    <div class="info-row">
                        <div class="info-label">Contact Person</div>
                        <div class="info-value"><?php echo htmlspecialchars($collaboration['contact_person']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <form method="POST" action="" style="display: inline;">
                        <button type="submit" name="confirm" class="btn-danger">
                            <span class="material-symbols-outlined">delete_forever</span>
                            Yes, Delete Permanently
                        </button>
                    </form>
                    <a href="list.php" class="btn-secondary">
                        <span class="material-symbols-outlined">cancel</span>
                        No, Cancel
                    </a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>