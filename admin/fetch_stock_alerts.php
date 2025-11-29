<?php
include 'db.php';

$sql = "SELECT product_id, name, stock 
        FROM products 
        WHERE stock <= 5"; // low stock threshold

$result = $mysqli->query($sql);

$alerts = [];
while($row = $result->fetch_assoc()){
    $row['status'] = $row['stock'] == 0 ? 'out' : 'low';
    $alerts[] = $row;
}

echo json_encode($alerts);
?>
