<?php
// auth/logout.php - Simple logout
session_start();
session_destroy();
header("Location: login.php");
exit();
?>