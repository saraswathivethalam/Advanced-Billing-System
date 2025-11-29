<?php
require("../config/database.php");
$today = date('Y-m-d');

$res = mysqli_query($conn,"SELECT o.*, s.name AS staff_name FROM orders o 
                           JOIN staff s ON o.staff_id = s.id
                           WHERE DATE(o.created_at)='$today' ORDER BY o.id DESC");

echo "<h2>Today's Orders</h2>";
echo "<table border=1><tr><th>Order ID</th><th>Customer</th><th>Staff</th><th>Total</th><th>Payment</th><th>Time</th></tr>";
while($row=mysqli_fetch_assoc($res)){
    echo "<tr>
    <td>{$row['id']}</td>
    <td>{$row['customer_name']}</td>
    <td>{$row['staff_name']}</td>
    <td>{$row['grand_total']}</td>
    <td>{$row['payment_method']}</td>
    <td>{$row['created_at']}</td>
    </tr>";
}
echo "</table>";
?>
