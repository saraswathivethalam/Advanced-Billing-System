<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// Check session expiration (30 minutes)
$session_duration = 1800; // 30 minutes in seconds
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $session_duration) {
    session_unset();
    session_destroy();
    header("Location: login.php?error=Session expired");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>