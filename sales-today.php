<?php
session_start();
require '../includes/config.php';
$staff_id = $_SESSION['staff_id'];

$stmt = $pdo->prepare("SELECT order_id, customer_name, amount, TIME(order_date) as time FROM orders WHERE DATE(order_date)=CURDATE() AND staff_id=? ORDER BY order_date DESC");
$stmt->execute([$staff_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
