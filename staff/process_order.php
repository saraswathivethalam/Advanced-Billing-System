<?php
session_start();
require("../config/database.php");

// Redirect if staff not logged in
if(!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit;
}

if(isset($_POST['place_order'])) {

    $staff_id = $_SESSION['staff_id'];
    $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
    $payment_method = $_POST['payment_method'];
    $products = $_POST['product_id'];
    $quantities = $_POST['quantity'];

    if($customer_id <= 0){
        die("Please select a valid customer.");
    }

    $grand_total = 0;
    $order_ids = []; // store inserted order IDs

    foreach($products as $i => $pid) {
        $pid = (int)$pid;
        $qty = (int)$quantities[$i];

        // Fetch product price
        $res = mysqli_query($conn, "SELECT price FROM product WHERE product_id='$pid'");
        if(!$res || mysqli_num_rows($res) == 0){
            continue; // skip if product not found
        }
        $row = mysqli_fetch_assoc($res);
        $price = $row['price'];
        $total = $price * $qty;
        $grand_total += $total;

        // Insert each product as a separate row in orders
        $stmt = $conn->prepare("INSERT INTO orders (customer_id, product_id, quantity, staff_id, gst_percentage, gst_amount) VALUES (?,?,?,?,?,?)");
        $gst_percentage = 0.00; 
        $gst_amount = 0.00;
        $stmt->bind_param("iiiddd", $customer_id, $pid, $qty, $staff_id, $gst_percentage, $gst_amount);
        $stmt->execute();
        $order_ids[] = $stmt->insert_id;
        $stmt->close();
    }

    // Insert payment record (one payment for all products)
    if($grand_total > 0 && !empty($order_ids)){
        foreach($order_ids as $oid){
            $stmt = $conn->prepare("INSERT INTO payments (order_id, amount, payment_method) VALUES (?,?,?)");
            $stmt->bind_param("dds", $oid, $grand_total, $payment_method);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: dashboard.php?order_success=1");
    exit;
}
?>
