<?php
header('Content-Type: application/json');
require("../config/database.php");

// Get product name safely
$product_name = trim($_POST['name'] ?? '');

if ($product_name) {
    $stmt = $conn->prepare("SELECT price, gst_percentage FROM products WHERE name = ? LIMIT 1");
    $stmt->bind_param("s", $product_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        echo json_encode([
            'price' => (float)$product['price'],
            'gst_percentage' => (float)$product['gst_percentage']
        ]);
    } else {
        echo json_encode(['price' => 0, 'gst_percentage' => 0]);
    }
}