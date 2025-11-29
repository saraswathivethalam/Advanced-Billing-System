<?php
// includes/auth.php
session_start();

if(!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])){
    // Staff not logged in, redirect to login
    header("Location: ../staff_login.php");
    exit();
}

// Optional: store username for later use
$staff_username = $_SESSION['username'];
?>
