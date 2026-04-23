<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/style.css">
    <?php if (basename($_SERVER['PHP_SELF']) !== 'index.php'): ?>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/dashboard.css">
    <?php endif; ?>
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <a href="<?php echo SITE_URL; ?>" class="nav-brand">SRC Management</a>
        <div class="nav-menu">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="<?php echo SITE_URL; ?>auth/logout.php" class="nav-link">Logout</a>
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>auth/login.php" class="nav-link">Login</a>
                <a href="<?php echo SITE_URL; ?>auth/register.php" class="nav-link">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main>