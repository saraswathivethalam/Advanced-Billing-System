<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "online_billing_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$staff_id = intval($_GET['staff_id'] ?? 0);

if ($staff_id > 0) {
    // Get staff info
    $staff_sql = "SELECT name, role FROM staff WHERE id = ?";
    $stmt = $conn->prepare($staff_sql);
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $staff_result = $stmt->get_result();
    $staff = $staff_result->fetch_assoc();
    
    // FIXED: Using the correct column name 'order_item_id'
    $sales_sql = "SELECT o.order_id, o.customer_name, o.total_amount, o.order_date, 
                         COUNT(oi.order_item_id) as items_count
                  FROM orders o 
                  LEFT JOIN order_items oi ON o.order_id = oi.order_id 
                  WHERE o.staff_id = ? 
                  GROUP BY o.order_id 
                  ORDER BY o.order_date DESC 
                  LIMIT 20";
    $stmt = $conn->prepare($sales_sql);
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $sales_result = $stmt->get_result();
    
    // Get sales statistics
    $stats_sql = "SELECT 
                    COUNT(*) as total_orders,
                    COALESCE(SUM(total_amount), 0) as total_sales,
                    AVG(total_amount) as avg_order_value,
                    MAX(order_date) as last_sale,
                    MIN(order_date) as first_sale
                  FROM orders 
                  WHERE staff_id = ?";
    $stmt = $conn->prepare($stats_sql);
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $stats_result = $stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    ?>
    
    <div class="staff-info">
        <h3><?= htmlspecialchars($staff['name']) ?> - <?= htmlspecialchars($staff['role']) ?></h3>
        
        <div class="sales-stats" style="margin: 20px 0;">
            <div class="stat-item">
                <span class="stat-label">Total Orders</span>
                <span class="stat-value"><?= $stats['total_orders'] ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total Sales Value</span>
                <span class="stat-value">₹<?= number_format($stats['total_sales'], 2) ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Average Order Value</span>
                <span class="stat-value">₹<?= number_format($stats['avg_order_value'] ?? 0, 2) ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">First Sale</span>
                <span class="stat-value"><?= $stats['first_sale'] ? date('M d, Y', strtotime($stats['first_sale'])) : 'N/A' ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Last Sale</span>
                <span class="stat-value"><?= $stats['last_sale'] ? date('M d, Y', strtotime($stats['last_sale'])) : 'N/A' ?></span>
            </div>
        </div>
    </div>

    <?php if ($sales_result->num_rows > 0): ?>
        <h4>Recent Sales (Last 20 Orders)</h4>
        <table class="sales-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($sale = $sales_result->fetch_assoc()): ?>
                <tr>
                    <td>#<?= $sale['order_id'] ?></td>
                    <td><?= htmlspecialchars($sale['customer_name']) ?></td>
                    <td><?= date('M d, Y', strtotime($sale['order_date'])) ?></td>
                    <td><?= $sale['items_count'] ?></td>
                    <td>₹<?= number_format($sale['total_amount'], 2) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="text-align: center; padding: 40px; color: #6b7280;">
            <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 15px;"></i>
            <h3>No Sales Recorded</h3>
            <p>This staff member hasn't made any sales yet.</p>
        </div>
    <?php endif;
} else {
    echo '<div style="text-align: center; padding: 40px; color: #ef4444;">Invalid staff ID</div>';
}

$conn->close();
?>