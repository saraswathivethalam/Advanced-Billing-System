<?php
session_start();
include_once __DIR__ . '/../config/database.php';

// 🔹 Staff ID from session
$staff_id = $_SESSION['staff_id'] ?? null;

// 🔹 Cart and total from POST
$total_amount = $_POST['total_amount'] ?? 0;
$cart = $_POST['cart'] ?? []; // Each item: ['product_id', 'quantity', 'price', 'gst_percentage', 'name', 'category']

if (!$staff_id || empty($cart)) {
    die("Invalid data. Please try again.");
}

// Start transaction
$conn->begin_transaction();

try {
    // 1️⃣ Insert into bills table
    $stmt = $conn->prepare("INSERT INTO bills (staff_id, total_amount) VALUES (?, ?)");
    $stmt->bind_param("id", $staff_id, $total_amount);
    $stmt->execute();
    $bill_id = $conn->insert_id;

    // 2️⃣ Loop through cart: Insert bill_items & update stock
    foreach ($cart as $item) {
        $product_id    = $item['product_id'];
        $quantity_raw  = $item['quantity'];       // e.g., "2 pcs"
        $quantity      = (int)filter_var($quantity_raw, FILTER_SANITIZE_NUMBER_INT);
        $price         = $item['price'];
        $gst           = $item['gst_percentage'] ?? 0;
        $final_price   = ($price + ($price * $gst / 100)) * $quantity;
        $product_name  = $item['name'] ?? '';
        $category      = $item['category'] ?? '';

        // 🔹 Insert into bill_items
        $stmt_item = $conn->prepare("
            INSERT INTO bill_items 
            (bill_id, product_id, product_name, category, quantity, price, gst, final_price) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_item->bind_param("iissiddd", $bill_id, $product_id, $product_name, $category, $quantity, $price, $gst, $final_price);
        $stmt_item->execute();

      // Reduce stock for each product
$stmt_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ? AND stock >= ?");
$stmt_stock->bind_param("iii", $quantity, $product_id, $quantity);
$stmt_stock->execute();

// Check if stock update worked
if ($stmt_stock->affected_rows == 0) {
    throw new Exception("Not enough stock for product: $product_name");
}

    }

    // Commit transaction
    $conn->commit();
    echo "✅ Bill processed successfully. Bill ID: $bill_id";

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo "❌ Error processing bill: " . $e->getMessage();
}
?>
