<?php
include 'db.php';

$sql = "SELECT p.product_id, p.name, p.stock, SUM(oi.quantity) AS total_sold
        FROM products p
        LEFT JOIN order_items oi ON p.product_id = oi.product_id
        GROUP BY p.product_id, p.name, p.stock";

$result = $mysqli->query($sql);

$products = [];
while($row = $result->fetch_assoc()){
    $products[] = $row;
}

echo json_encode($products);
?>
