<?php
include '../../config/database.php';
$result = $conn->query("SELECT id, customer_name, date_time, amount, payment_method, status FROM transactions ORDER BY date_time DESC LIMIT 10");
$data=[];
while($row=$result->fetch_assoc()) $data[]=$row;
echo json_encode($data);
?>
