<?php
include_once __DIR__ . '/../config/database.php';

$sql = "SELECT ph.payment_id, c.name AS customer_name, c.email, ph.bill_amount, ph.payment_status, ph.created_at
        FROM payment_history ph
        JOIN customers c ON ph.customer_id = c.customer_id
        ORDER BY ph.created_at DESC";
$res = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment History</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding:20px; }
        table { width:100%; border-collapse: collapse; background:#fff; }
        th, td { padding:10px; border:1px solid #ccc; text-align:center; }
        th { background:#ddd; }
    </style>
</head>
<body>
    <h2>Payment History</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Email</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
        <?php while($row = $res->fetch_assoc()): ?>
        <tr>
            <td><?= $row['payment_id'] ?></td>
            <td><?= $row['customer_name'] ?></td>
            <td><?= $row['email'] ?></td>
            <td><?= $row['bill_amount'] ?></td>
            <td><?= $row['payment_status'] ?></td>
            <td><?= $row['created_at'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
