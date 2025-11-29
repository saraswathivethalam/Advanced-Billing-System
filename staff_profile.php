<?php
session_start();
require '../includes/config.php';
$staff_id = $_SESSION['staff_id'];
$stmt = $pdo->prepare("SELECT name, role, shift FROM staff WHERE id=?");
$stmt->execute([$staff_id]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
?>
