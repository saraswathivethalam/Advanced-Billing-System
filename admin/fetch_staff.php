<?php
include 'db.php';

$sql = "SELECT s.staff_id, s.name, s.role, SUM(oi.quantity * oi.price) AS total_sales
        FROM staff s
        LEFT JOIN orders o ON s.staff_id = o.staff_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        GROUP BY s.staff_id, s.name, s.role";

$result = $mysqli->query($sql);

$staff = [];
while($row = $result->fetch_assoc()){
    $staff[] = $row;
}

echo json_encode($staff);
?>
