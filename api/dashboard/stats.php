<?php
require_once "../../config/database.php";
header("Content-Type: application/json");

$today = date("Y-m-d");

// Total products
$total_products = $conn->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc()['c'];

// Today’s orders
$today_orders = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE DATE(order_date)='$today'")->fetch_assoc()['c'];

// Today’s revenue
$today_revenue = $conn->query("SELECT IFNULL(SUM(total_amount),0) AS t FROM orders WHERE DATE(order_date)='$today'")->fetch_assoc()['t'];

// Low stock
$low_stock = $conn->query("SELECT COUNT(*) AS c FROM products WHERE stock <= stock_alert")->fetch_assoc()['c'];

// Expiring products
$expiring = $conn->query("SELECT COUNT(*) AS c FROM products WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetch_assoc()['c'];

// Active staff
$active_staff = $conn->query("SELECT COUNT(*) AS c FROM staff WHERE status='Active'")->fetch_assoc()['c'];

echo json_encode([
    "total_products" => $total_products,
    "today_orders" => $today_orders,
    "today_revenue" => $today_revenue,
    "low_stock" => $low_stock,
    "expiring_products" => $expiring,
    "active_staff" => $active_staff,
]);
