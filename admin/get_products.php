<?php
// get_products.php
header('Content-Type: application/json');

// Database connection
$servername = '127.0.0.1';
$username = 'root';
$password = "your_password";
$dbname = "online_billing_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to get products with expiry dates
$sql = "SELECT name, expiry_date FROM products WHERE expiry_date IS NOT NULL";
$result = $conn->query($sql);

$products = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

$conn->close();

echo json_encode($products);
?>