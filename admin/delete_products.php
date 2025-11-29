<?php
session_start();
echo "Script started<br>";

// Include database
include 'config/database.php';
echo "Database included<br>";

// Get ID
$id = $_GET['id'] ?? 0;
echo "ID received: " . $id . "<br>";

if($id > 0){
    echo "ID is valid<br>";
    
    // Check connection
    if($conn){
        echo "Database connected<br>";
        
        // Try to delete
        if($conn->query("DELETE FROM products WHERE product_id=$id")){
            echo "Product deleted successfully<br>";
            $_SESSION['msg'] = "Product deleted";
        } else {
            echo "Delete failed: " . $conn->error . "<br>";
            $_SESSION['msg'] = "Error: " . $conn->error;
        }
    } else {
        echo "No database connection<br>";
    }
} else {
    echo "Invalid ID<br>";
}

echo "Redirecting...<br>";
header("Location: manage_products.php");
exit();
?>