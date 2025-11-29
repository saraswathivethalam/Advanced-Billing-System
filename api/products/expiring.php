<?php
require_once "../../config/database.php";
header("Content-Type: application/json");

$sql = "SELECT id, name, category, expiry_date, DATEDIFF(expiry_date, CURDATE()) AS days_remaining
        FROM products WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY expiry_date ASC LIMIT 10";
$result = $conn->query($sql);

$data = [];
while($row = $result->fetch_assoc()){
    $data[] = $row;
}
echo json_encode($data);
