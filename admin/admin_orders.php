<?php
session_start();
require('../config/database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

$sql = "
SELECT 
    o.order_id,
    o.customer_name,
    o.customer_email,
    o.order_date,
    o.delivery_date,
    o.staff_id,
    o.staff_name,
    i.product_name,
    i.unit,
    i.quantity,
    i.price,
    i.gst_percentage,
    i.gst_amount,
    i.total_amount
FROM orders o
LEFT JOIN order_items i ON o.order_id = i.order_id
ORDER BY o.order_date DESC, o.order_id DESC
";

$result = $conn->query($sql);
?>
>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Staff Orders</title>
<style>
body{font-family:'Poppins',sans-serif;background:#f4f7fa;padding:20px;}
h1{text-align:center;color:#2575fc;}
table{width:100%;border-collapse:collapse;background:white;box-shadow:0 4px 12px rgba(0,0,0,0.08);margin-top:20px;}
th,td{border:1px solid #ddd;padding:10px;text-align:center;}
th{background:#2575fc;color:white;}
tr:nth-child(even){background:#f9f9f9;}
.back{display:block;text-align:center;background:#2575fc;color:white;padding:10px;border-radius:6px;text-decoration:none;max-width:200px;margin:20px auto;}
.back:hover{background:#0056b3;}
</style>
</head>
<body>

<h1>All Staff Orders</h1>

<table>
<tr>
    <th>Order ID</th>
    <th>Customer</th>
    <th>Product</th>
    <th>Quantity</th>
    <th>GST %</th>
    <th>GST Amount</th>
    <th>Staff Name</th>
    <th>Order Date</th>
</tr>

<?php if($result && $result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['order_id'] ?></td>
        <td><?= htmlspecialchars($row['customer_name']) ?></td>
        <td><?= htmlspecialchars($row['product_name']) ?></td>
        <td><?= $row['quantity'] ?></td>
        <td><?= $row['gst_percentage'] ?>%</td>
        <td>₹<?= number_format($row['gst_amount'],2) ?></td>
        <td><?= htmlspecialchars($row['staff_name']) ?></td>
        <td><?= $row['order_date'] ?></td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="8" style="text-align:center;">No orders placed yet.</td></tr>
<?php endif; ?>
</table>

<a href="admin_dashboard.php" class="back">⬅ Back to Dashboard</a>

</body>
</html>
