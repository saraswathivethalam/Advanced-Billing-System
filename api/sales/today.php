<?php
require_once "../../config/database.php";
header("Content-Type: application/json");

$today = date("Y-m-d");
$sql = "SELECT order_id, customer_name, total_amount 
        FROM orders WHERE DATE(order_date)='$today' ORDER BY order_date DESC LIMIT 10";
$result = $conn->query($sql);

$data = [];
while($row = $result->fetch_assoc()){
    $data[] = $row;
}
echo json_encode($data);
