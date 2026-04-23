<?php
// config.php - Simplified working version

// Session configuration
ini_set('session.gc_maxlifetime', 1800);
session_start();

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'malika05');
define('DB_NAME', 'src_system');

// Site configuration
define('SITE_NAME', 'SRC Management System');
define('SITE_URL', 'http://localhost:8080/mjiit_src/src-system/public/');

// PHP settings
date_default_timezone_set('Asia/Kuala_Lumpur');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include dependencies
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// =============================================
// AUTHENTICATION HELPERS
// =============================================

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . SITE_URL . 'auth/login.php');
        exit();
    }
}

function require_role($role) {
    require_login();
    $user_role = $_SESSION['user_role'] ?? '';
    if ($user_role !== $role && $user_role !== 'admin') {
        die('Access denied. ' . ucfirst($role) . ' privileges required.');
    }
}

function require_admin() {
    require_login();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        die('Access denied. Admin privileges required.');
    }
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function current_user_role() {
    return $_SESSION['user_role'] ?? null;
}

function current_user_name() {
    return $_SESSION['user_name'] ?? '';
}
?>