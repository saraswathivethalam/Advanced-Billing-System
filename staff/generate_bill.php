<?php
session_start();
require("../config/database.php");
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;

// Initialize bill session
if (!isset($_SESSION['bill_items'])) $_SESSION['bill_items'] = [];

// DB Connection check
if ($conn->connect_errno) die("DB Connection failed: " . $conn->connect_error);

// Get products for the product box
$products = [];
$product_query = "SELECT p.*, c.category_name FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  ORDER BY p.name ASC";
$result = $conn->query($product_query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

$staff_id = $_SESSION['staff_id'];
$customer_name = $_SESSION['customer']['name'] ?? '';
$customer_email = $_SESSION['customer']['email'] ?? '';
$payment_method = $_SESSION['payment_method'] ?? 'cash';
$grand_total = array_sum(array_column($_SESSION['bill_items'],'total'));
$gst_total = array_sum(array_map(function($item){
    return $item['price'] * (int)filter_var($item['quantity'], FILTER_SANITIZE_NUMBER_INT) * $item['gst_percentage']/100;
}, $_SESSION['bill_items']));

// 1️⃣ Save bill
$stmt = $conn->prepare("INSERT INTO bill (staff_id, customer_name, customer_email, total_amount, gst_amount, payment_method) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issdds", $staff_id, $customer_name, $customer_email, $grand_total, $gst_total, $payment_method);
$stmt->execute();
$bill_id = $stmt->insert_id;

// 2️⃣ Save bill items
foreach($_SESSION['bill_items'] as $item){
    // Get numeric quantity if stored as "2 pcs"
    $qty_number = (int)filter_var($item['quantity'], FILTER_SANITIZE_NUMBER_INT);

    $stmt_item = $conn->prepare("
        INSERT INTO bill_items 
        (bill_id, product_id, product_name, category, quantity, price, gst, final_price) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt_item->bind_param(
        "iissiddd", 
        $bill_id,               // int
        $item['product_id'],    // int
        $item['name'],          // string → product_name
        $item['category'],      // string → category
        $qty_number,            // int → quantity
        $item['price'],         // double → price
        $item['gst_percentage'],// double → gst
        $item['total']          // double → final_price
    );

    $stmt_item->execute();
}



// ------------------ Add Product from Box ------------------
if (isset($_POST['add_from_box'])) {
    $product_id = $_POST['product_id'] ?? '';
    $qty = (int)($_POST['quantity'] ?? 1);
    $unit = $_POST['unit'] ?? 'pcs';
    $customer_name = $_POST['customer_name'] ?? '';
    $customer_email = $_POST['customer_email'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'cash';

    $_SESSION['customer'] = ['name'=>$customer_name,'email'=>$customer_email];
    $_SESSION['payment_method'] = $payment_method;

    // Save customer if not exists
    if(!empty($customer_email)){
        $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE customer_email=?");
        $stmt->bind_param("s",$customer_email);
        $stmt->execute();
        $stmt->store_result();
        if($stmt->num_rows==0){
            $stmt->close();
            $insert_stmt = $conn->prepare("INSERT INTO customers (customer_name,customer_email) VALUES (?,?)");
            $insert_stmt->bind_param("ss",$customer_name,$customer_email);
            $insert_stmt->execute();
            $insert_stmt->close();
        } else $stmt->close();
    }

    // Add product to bill (with quantity merge)
    if(!empty($product_id)){
        $sql = "SELECT p.*, c.category_name AS category
                FROM products p
                LEFT JOIN categories c ON p.category_id=c.category_id
                WHERE p.product_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $product_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if($res && $res->num_rows>0){
            $row = $res->fetch_assoc();
            $gst = ($row['price'] * $row['gst_percentage']/100);
            $total = ($row['price'] + $gst) * $qty;

            $exists = false;
            foreach($_SESSION['bill_items'] as &$item){
                if($item['product_id'] == $row['product_id']){
                    // Merge quantity
                    $prev_qty = (int)filter_var($item['quantity'], FILTER_SANITIZE_NUMBER_INT);
                    $item['quantity'] = ($prev_qty + $qty).' '.$unit;
                    $item['total'] = ($row['price'] + $gst) * ($prev_qty + $qty);
                    $exists = true;
                    break;
                }
            }
            if(!$exists){
                $_SESSION['bill_items'][] = [
                    'product_id'=>$row['product_id'],
                    'name'=>$row['name'],
                    'category'=>$row['category'] ?? '-',
                    'price'=>$row['price'],
                    'gst_percentage'=>$row['gst_percentage'],
                    'quantity'=>$qty.' '.$unit,
                    'total'=>$total
                ];
            }
        }
        $stmt->close();
    }
}

// ------------------ Add Product from Search ------------------
if (isset($_POST['add_product'])) {
    $product_code   = $_POST['product_code'] ?? '';
    $qty            = (int)($_POST['quantity'] ?? 1);
    $unit           = $_POST['unit'] ?? 'pcs';
    $customer_name  = $_POST['customer_name'] ?? '';
    $customer_email = $_POST['customer_email'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'cash';

    $_SESSION['customer'] = ['name'=>$customer_name,'email'=>$customer_email];
    $_SESSION['payment_method'] = $payment_method;

    // Save customer if not exists
    if(!empty($customer_email)){
        $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE customer_email=?");
        $stmt->bind_param("s",$customer_email);
        $stmt->execute();
        $stmt->store_result();
        if($stmt->num_rows==0){
            $stmt->close();
            $insert_stmt = $conn->prepare("INSERT INTO customers (customer_name,customer_email) VALUES (?,?)");
            $insert_stmt->bind_param("ss",$customer_name,$customer_email);
            $insert_stmt->execute();
            $insert_stmt->close();
        } else $stmt->close();
    }

    // Add product to bill (with quantity merge)
    if(!empty($product_code)){
        $sql = "SELECT p.*, c.category_name AS category
                FROM products p
                LEFT JOIN categories c ON p.category_id=c.category_id
                WHERE p.product_id=? OR p.barcode=? OR p.name LIKE ?";
        $stmt = $conn->prepare($sql);
        $like = "%$product_code%";
        $stmt->bind_param("sss",$product_code,$product_code,$like);
        $stmt->execute();
        $res = $stmt->get_result();
        if($res && $res->num_rows>0){
            $row = $res->fetch_assoc();
            $gst = ($row['price'] * $row['gst_percentage']/100);
            $total = ($row['price'] + $gst) * $qty;

            $exists = false;
            foreach($_SESSION['bill_items'] as &$item){
                if($item['product_id'] == $row['product_id']){
                    // Merge quantity
                    $prev_qty = (int)filter_var($item['quantity'], FILTER_SANITIZE_NUMBER_INT);
                    $item['quantity'] = ($prev_qty + $qty).' '.$unit;
                    $item['total'] = ($row['price'] + $gst) * ($prev_qty + $qty);
                    $exists = true;
                    break;
                }
            }
            if(!$exists){
                $_SESSION['bill_items'][] = [
                    'product_id'=>$row['product_id'],
                    'name'=>$row['name'],
                    'category'=>$row['category'] ?? '-',
                    'price'=>$row['price'],
                    'gst_percentage'=>$row['gst_percentage'],
                    'quantity'=>$qty.' '.$unit,
                    'total'=>$total
                ];
            }
        }
        $stmt->close();
    }
}

// ------------------ Clear Bill ------------------
if(isset($_POST['clear_bill'])){
    $_SESSION['bill_items'] = [];
    unset($_SESSION['customer']);
    unset($_SESSION['payment_method']);
    unset($_SESSION['qr_file']);
    unset($_SESSION['card_payment']);
}

// ------------------ Remove Single Item ------------------
if(isset($_POST['remove_item'])){
    $product_id = $_POST['product_id'] ?? '';
    if(!empty($product_id)){
        $_SESSION['bill_items'] = array_filter($_SESSION['bill_items'], function($item) use ($product_id) {
            return $item['product_id'] != $product_id;
        });
    }
}

// ------------------ Generate UPI QR for staff view only ------------------
$grand_total = array_sum(array_column($_SESSION['bill_items'],'total'));

// FIXED: Generate QR code whenever UPI is selected and there are items
if(!empty($_SESSION['bill_items']) && ($_SESSION['payment_method'] ?? '') == 'UPI'){
    // Always regenerate QR code when UPI is selected to ensure it matches current total
    if(!is_dir('qr_codes')) mkdir('qr_codes',0777,true);
    
    // Clean up old QR files
    $files = glob('qr_codes/qr_*.png');
    foreach($files as $file){
        if(is_file($file) && time() - filemtime($file) >= 3600) { // Delete files older than 1 hour
            unlink($file);
        }
    }
    
    $upi_text = "upi://pay?pa=merchant@upi&pn=IBA%20Store&tn=Bill%20Payment&am=$grand_total&cu=INR";

    try {
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($upi_text)
            ->encoding(new Encoding('UTF-8'))
            ->size(300)
            ->margin(10)
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->build();

        $qr_file = "qr_codes/qr_" . time() . ".png";
        $result->saveToFile($qr_file);
        $_SESSION['qr_file'] = $qr_file;
    } catch (Exception $e) {
        error_log("QR Code Generation Error: " . $e->getMessage());
    }
}

// ------------------ Card Payment ------------------
if(isset($_POST['process_card']) && !empty($_SESSION['bill_items'])){
    $card_number = $_POST['card_number'] ?? '';
    $card_holder = $_POST['card_holder'] ?? '';
    $expiry_date = $_POST['expiry_date'] ?? '';
    $cvv = $_POST['cvv'] ?? '';

    if(empty($card_number) || empty($card_holder) || empty($expiry_date) || empty($cvv)){
        echo "<script>alert('Please fill all card details');</script>";
    } else {
        $_SESSION['card_payment'] = [
            'status'=>'success',
            'transaction_id'=>'TXN'.time(),
            'amount'=>$grand_total,
            'timestamp'=>date('Y-m-d H:i:s')
        ];
        echo "<script>alert('Card payment of ₹$grand_total processed successfully! Transaction ID: ".$_SESSION['card_payment']['transaction_id']."');</script>";
    }
}

// ------------------ Send Bill Email ------------------
if(isset($_POST['send_email']) && isset($_SESSION['bill_items'])){
    $customer_email = $_SESSION['customer']['email'] ?? '';
    $customer_name = $_SESSION['customer']['name'] ?? '';
    $payment_method = $_SESSION['payment_method'] ?? 'cash';

    $bill_html = "<h2>Bill for $customer_name</h2><table border='1' cellpadding='5' cellspacing='0'>
        <tr><th>Product ID</th><th>Name</th><th>Category</th><th>Price</th><th>GST %</th><th>Qty</th><th>Total</th></tr>";

    foreach($_SESSION['bill_items'] as $item){
        $bill_html .= "<tr>
            <td>{$item['product_id']}</td>
            <td>{$item['name']}</td>
            <td>{$item['category']}</td>
            <td>₹".number_format($item['price'],2)."</td>
            <td>{$item['gst_percentage']}%</td>
            <td>{$item['quantity']}</td>
            <td>₹".number_format($item['total'],2)."</td>
        </tr>";
    }

    $bill_html .= "<tr><td colspan='6'><strong>Grand Total</strong></td><td>₹".number_format($grand_total,2)."</td></tr>";
    $bill_html .= "<tr><td colspan='6'><strong>Payment Method</strong></td><td>$payment_method</td></tr>";

    if($payment_method=='card' && isset($_SESSION['card_payment'])){
        $txn = $_SESSION['card_payment'];
        $bill_html .= "<tr><td colspan='7' style='text-align:center;background:#d4edda;'>
            <h3>✅ Card Payment Successful</h3>
            <p>Transaction ID: {$txn['transaction_id']}</p>
            <p>Amount Paid: ₹{$txn['amount']}</p>
            <p>Date: {$txn['timestamp']}</p>
        </td></tr>";
    }

    $bill_html .= "</table>";

    $mail = new PHPMailer(true);
    try{
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ibastore810@gmail.com';
        $mail->Password = 'chgj zjts djei zrqk';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('ibastore810@gmail.com','IBA Store');
        $mail->addAddress($customer_email,$customer_name);
        $mail->isHTML(true);
        $mail->Subject = "Your Bill - ₹$grand_total";
        $mail->Body = $bill_html;
        $mail->send();

        echo "<script>alert('Bill sent successfully to $customer_email');</script>";

        $_SESSION['bill_items'] = [];
        unset($_SESSION['customer']);
        unset($_SESSION['payment_method']);
        unset($_SESSION['qr_file']);
        unset($_SESSION['card_payment']);
    } catch(Exception $e){
        echo "<script>alert('Mailer Error: {$mail->ErrorInfo}');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Staff - Generate Bill</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        h2 {
            color: #2c3e50;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #3498db;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        h2:before {
            content: "🛒";
            font-size: 1.5em;
        }
        
        .form-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        button {
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        button[name="add_product"] {
            background: linear-gradient(to right, #3498db, #2980b9);
            color: white;
        }
        
        button[name="add_product"]:hover {
            background: linear-gradient(to right, #2980b9, #2573a7);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        th {
            background: linear-gradient(to right, #2c3e50, #34495e);
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: 600;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        tr:hover {
            background-color: #f1f8ff;
        }
        
        .actions {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .actions button {
            flex: 1;
            min-width: 150px;
        }
        
        button[name="clear_bill"] {
            background: linear-gradient(to right, #e74c3c, #c0392b);
            color: white;
        }
        
        button[name="clear_bill"]:hover {
            background: linear-gradient(to right, #c0392b, #a93226);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }
        
        button[onclick="printBill()"] {
            background: linear-gradient(to right, #f39c12, #e67e22);
            color: white;
        }
        
        button[onclick="printBill()"]:hover {
            background: linear-gradient(to right, #e67e22, #d35400);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3);
        }
        
        button[name="send_email"] {
            background: linear-gradient(to right, #27ae60, #219653);
            color: white;
        }
        
        button[name="send_email"]:hover {
            background: linear-gradient(to right, #219653, #1e8449);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        .payment-section {
            margin: 25px 0;
            padding: 25px;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-radius: 10px;
            border: 2px dashed #3498db;
            text-align: center;
        }
        
        .payment-section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .payment-section img {
            max-width: 300px;
            border: 2px solid #3498db;
            border-radius: 10px;
            margin: 15px 0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .payment-section p {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .product-box {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .product-box h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
        }
        
        .product-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-color: #3498db;
        }
        
        .product-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .product-details {
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .product-price {
            font-weight: 700;
            color: #27ae60;
            margin-top: 5px;
        }
        
        .card-payment-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .card-payment-form h3 {
            grid-column: 1 / -1;
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .quantity-controls input {
            width: 70px;
            text-align: center;
        }
        
        .quantity-controls select {
            width: 80px;
        }
        
        .remove-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .remove-btn:hover {
            background: #c0392b;
        }
        
        .add-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            width: 100%;
            margin-top: 10px;
        }
        
        .add-btn:hover {
            background: #219653;
        }
        
        @media print {
            body * {
                visibility: hidden;
            }
            
            table, table * {
                visibility: visible;
            }
            
            table {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none;
            }
            
            .product-box, .form-container, .actions, .payment-section {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .form-container {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .actions button {
                width: 100%;
            }
            
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .quantity-controls {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
    <script>
    function printBill(){
        var printContents = document.querySelector('table').outerHTML;
        var originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    }
    
    function toggleCardForm() {
        const paymentMethod = document.querySelector('select[name="payment_method"]').value;
        const cardForm = document.getElementById('card-payment-form');
        
        if (paymentMethod === 'Card') {
            cardForm.style.display = 'grid';
        } else {
            cardForm.style.display = 'none';
        }
    }
    
    // Initialize on page load
    window.onload = function() {
        toggleCardForm();
    };
    </script>
</head>
<body>
<div class="container">
<h2>Generate Bill</h2>

<form method="post" class="form-container">
    <div>
        <label>Customer Name:</label>
        <input type="text" name="customer_name" required value="<?= $_SESSION['customer']['name'] ?? '' ?>">
    </div>
    <div>
        <label>Customer Email:</label>
        <input type="email" name="customer_email" required value="<?= $_SESSION['customer']['email'] ?? '' ?>">
    </div>
    <div>
        <label>Product ID / Name / Barcode:</label>
        <input type="text" name="product_code" required id="product-code-input">
    </div>
    <div>
        <label>Quantity:</label>
        <div style="display: flex; gap: 10px;">
            <input type="number" name="quantity" value="1" min="1" style="flex: 1;">
            <select name="unit" style="width: 100px;">
                <option value="pcs">Pcs</option>
                <option value="kg">Kg</option>
                <option value="box">Box</option>
            </select>
        </div>
    </div>
    <div>
        <label>Payment Method:</label>
        <select name="payment_method" required onchange="toggleCardForm()">
            <option value="Cash" <?= (($_SESSION['payment_method'] ?? '')=='Cash')?'selected':'' ?>>Cash</option>
            <option value="Card" <?= (($_SESSION['payment_method'] ?? '')=='Card')?'selected':'' ?>>Card</option>
            <option value="UPI" <?= (($_SESSION['payment_method'] ?? '')=='UPI')?'selected':'' ?>>UPI</option>
        </select>
    </div>
    <div>
        <button type="submit" name="add_product">➕ Add to Bill</button>
    </div>
</form>

<?php if(!empty($_SESSION['bill_items']) && ($_SESSION['payment_method'] ?? '')=='Card'): ?>
<form method="post" class="card-payment-form" id="card-payment-form">
    <h3>💳 Card Payment Details</h3>
    <div>
        <label>Card Number:</label>
        <input type="text" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
    </div>
    <div>
        <label>Card Holder Name:</label>
        <input type="text" name="card_holder" placeholder="John Doe">
    </div>
    <div>
        <label>Expiry Date:</label>
        <input type="text" name="expiry_date" placeholder="MM/YY" maxlength="5">
    </div>
    <div>
        <label>CVV:</label>
        <input type="text" name="cvv" placeholder="123" maxlength="3">
    </div>
    <div>
        <button type="submit" name="process_card">💳 Process Card Payment</button>
    </div>
</form>
<?php else: ?>
<div class="card-payment-form" id="card-payment-form" style="display: none;">
    <h3>💳 Card Payment Details</h3>
    <div>
        <label>Card Number:</label>
        <input type="text" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
    </div>
    <div>
        <label>Card Holder Name:</label>
        <input type="text" name="card_holder" placeholder="John Doe">
    </div>
    <div>
        <label>Expiry Date:</label>
        <input type="text" name="expiry_date" placeholder="MM/YY" maxlength="5">
    </div>
    <div>
        <label>CVV:</label>
        <input type="text" name="cvv" placeholder="123" maxlength="3">
    </div>
    <div>
        <button type="submit" name="process_card">💳 Process Card Payment</button>
    </div>
</div>
<?php endif; ?>

<!-- FIXED: UPI QR Code Display - Now shows whenever UPI is selected and items exist -->
<?php if(!empty($_SESSION['bill_items']) && ($_SESSION['payment_method'] ?? '') == 'UPI' && isset($_SESSION['qr_file']) && file_exists($_SESSION['qr_file'])): ?>
<div class="payment-section">
    <h3>📱 UPI Payment (Staff View)</h3>
    <img src="<?= $_SESSION['qr_file'] ?>" alt="UPI QR Code for ₹<?= number_format($grand_total,2) ?>">
    <p>Amount: ₹<?= number_format($grand_total,2) ?></p>
    <p>Scan this QR code to pay via UPI</p>
</div>
<?php elseif(!empty($_SESSION['bill_items']) && ($_SESSION['payment_method'] ?? '') == 'UPI'): ?>
<div class="payment-section">
    <h3>📱 UPI Payment (Staff View)</h3>
    <p style="color: #e74c3c; padding: 20px;">QR Code generation failed. Please try adding a product again.</p>
</div>
<?php endif; ?>

<table>
<tr>
    <th>Product ID</th><th>Name</th><th>Category</th><th>Price</th>
    <th>GST %</th><th>Qty</th><th>Total</th><th>Action</th>
</tr>
<?php if(!empty($_SESSION['bill_items'])): ?>
    <?php foreach($_SESSION['bill_items'] as $item): ?>
    <tr>
        <td><?= $item['product_id'] ?></td>
        <td><?= $item['name'] ?></td>
        <td><?= $item['category'] ?></td>
        <td>₹<?= number_format($item['price'],2) ?></td>
        <td><?= $item['gst_percentage'] ?>%</td>
        <td><?= $item['quantity'] ?></td>
        <td>₹<?= number_format($item['total'],2) ?></td>
        <td>
            <form method="post" style="display: inline;">
                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                <button type="submit" name="remove_item" class="remove-btn">Remove</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    <tr style="background: #e8f4f8;">
        <td colspan="6"><strong>Grand Total</strong></td>
        <td><strong>₹<?= number_format($grand_total,2) ?></strong></td>
        <td></td>
    </tr>
    <tr style="background: #e8f4f8;">
        <td colspan="6"><strong>Payment Method</strong></td>
        <td><strong><?= $_SESSION['payment_method'] ?? '-' ?></strong></td>
        <td></td>
    </tr>
    <?php if(isset($_SESSION['card_payment'])): ?>
    <tr style="background: #d4edda;">
        <td colspan="8" style="text-align: center;">
            <strong>✅ Card Payment Successful</strong><br>
            Transaction ID: <?= $_SESSION['card_payment']['transaction_id'] ?><br>
            Amount Paid: ₹<?= number_format($_SESSION['card_payment']['amount'],2) ?><br>
            Date: <?= $_SESSION['card_payment']['timestamp'] ?>
        </td>
    </tr>
    <?php endif; ?>
<?php else: ?>
    <tr><td colspan="8" style="text-align: center; padding: 30px; color: #7f8c8d;">No items added yet.</td></tr>
<?php endif; ?>
</table>

<div class="actions">
    <form method="post" style="display:inline; flex: 1;"><button type="submit" name="clear_bill">🗑️ Clear Bill</button></form>
    <button onclick="printBill()" style="flex: 1;">🖨️ Print Bill</button>
    <form method="post" style="display:inline; flex: 1;"><button type="submit" name="send_email">📧 Send Bill via Email</button></form>
</div>

<div class="product-box">
    <h3>📦 Available Products - Click "Add to Bill" on any product</h3>
    <div class="product-grid">
        <?php if(!empty($products)): ?>
            <?php foreach($products as $product): ?>
                <div class="product-card">
                    <div class="product-name"><?= $product['name'] ?></div>
                    <div class="product-details">
                        ID: <?= $product['product_id'] ?><br>
                        Category: <?= $product['category_name'] ?? 'N/A' ?><br>
                        GST: <?= $product['gst_percentage'] ?>%
                    </div>
                    <div class="product-price">₹<?= number_format($product['price'], 2) ?></div>
                    
                    <form method="post" style="margin-top: 10px;">
                        <input type="hidden" name="customer_name" value="<?= $_SESSION['customer']['name'] ?? '' ?>">
                        <input type="hidden" name="customer_email" value="<?= $_SESSION['customer']['email'] ?? '' ?>">
                        <input type="hidden" name="payment_method" value="<?= $_SESSION['payment_method'] ?? 'Cash' ?>">
                        <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                        
                        <div class="quantity-controls">
                            <input type="number" name="quantity" value="1" min="1" placeholder="Qty">
                            <select name="unit">
                                <option value="pcs">Pcs</option>
                                <option value="kg">Kg</option>
                                <option value="box">Box</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="add_from_box" class="add-btn">➕ Add to Bill</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #7f8c8d;">No products available.</p>
        <?php endif; ?>
    </div>
</div>
</div>
</body>
</html>