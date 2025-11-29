<?php
include_once __DIR__ . '/../config/database.php';

$staff_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch staff info
$stmt = $conn->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$staff_result = $stmt->get_result();

if($staff_result->num_rows == 0){
    echo "<p>Staff not found.</p>";
    exit;
}

$staff = $staff_result->fetch_assoc();
?>

<h2>Staff Profile</h2>

<p><strong>Name:</strong> <?= htmlspecialchars($staff['staff_name']) ?></p>
<p><strong>Email:</strong> <?= htmlspecialchars($staff['email']) ?></p>
<p><strong>Role:</strong> <?= htmlspecialchars($staff['role']) ?></p>
<p><strong>Status:</strong> <?= htmlspecialchars($staff['status']) ?></p>

<a href="staff_management.php">← Back to Staff Management</a>
