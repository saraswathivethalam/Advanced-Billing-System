<?php
session_start();
require('../config/database.php');

if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: admin_login.php");
    exit;
}

$adminName = $_SESSION['name'] ?? $_SESSION['username'];

// Fetch stats
$totalProducts = $conn->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc()['total'] ?? 0;
$lowStock = $conn->query("SELECT COUNT(*) AS total FROM products WHERE stock < 10")->fetch_assoc()['total'] ?? 0;
$expiringProducts = $conn->query("SELECT COUNT(*) AS total FROM products WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetch_assoc()['total'] ?? 0;
$activeStaff = $conn->query("SELECT COUNT(*) AS total FROM staff")->fetch_assoc()['total'] ?? 0;

// Fetch today's orders - FIXED QUERY
$sql = "
SELECT 
    o.order_id,
    COALESCE(s.name,'-') AS staff_name,
    o.customer_name,
    c.name AS category_name,
    i.product_name,
    i.quantity,
    i.unit,
    IFNULL(i.price,0) AS price,
    IFNULL(c.gst_percentage,0) AS gst_percentage,
    (IFNULL(i.price,0)*i.quantity*c.gst_percentage/100) AS gst_amount,
    (IFNULL(i.price,0)*i.quantity)+(IFNULL(i.price,0)*i.quantity*c.gst_percentage/100) AS total_amount,
    o.order_date,
    o.delivery_date
FROM orders o
LEFT JOIN staff s ON o.staff_id = s.id
LEFT JOIN order_items i ON o.order_id = i.order_id
LEFT JOIN categories c ON i.category_id = c.category_id
WHERE DATE(o.order_date) = CURDATE()
ORDER BY o.order_date DESC, o.order_id DESC
";

$todayOrders = $conn->query($sql);

if(!$todayOrders){
    die("Query failed: " . $conn->error);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IBA STORE - Admin Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary: #3b82f6;
    --primary-light: #60a5fa;
    --primary-dark: #2563eb;
    --secondary: #f59e0b;
    --danger: #ef4444;
    --success: #10b981;
    --info: #06b6d4;
    --warning: #f59e0b;
    --dark: #1f2937;
    --light: #f9fafb;
    --gray: #6b7280;
    --gray-light: #e5e7eb;
    --sidebar-bg: #1e293b;
    --sidebar-text: #f1f5f9;
    --card-bg: #ffffff;
}

body {
    font-family: 'Inter', sans-serif;
    background: #f8fafc;
    color: var(--dark);
    line-height: 1.6;
    display: flex;
    min-height: 100vh;
}

/* Sidebar Styles */
.sidebar {
    width: 260px;
    background: var(--sidebar-bg);
    color: var(--sidebar-text);
    position: fixed;
    height: 100vh;
    overflow-y: auto;
    transition: all 0.3s ease;
    z-index: 1000;
}

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid #334155;
    display: flex;
    align-items: center;
    gap: 12px;
}

.sidebar-header h2 {
    font-size: 1.3rem;
    font-weight: 600;
}

.sidebar-header i {
    font-size: 1.5rem;
    color: var(--primary-light);
}

.sidebar-menu {
    padding: 1rem 0;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0.9rem 1.5rem;
    color: var(--sidebar-text);
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.menu-item:hover, .menu-item.active {
    background: #334155;
    border-left-color: var(--primary);
    color: white;
}

.menu-item i {
    width: 20px;
    text-align: center;
}

/* Main Content Area */
.main-content {
    flex: 1;
    margin-left: 260px;
    transition: all 0.3s ease;
}

/* Topbar Styles */
.topbar {
    background: var(--card-bg);
    padding: 1.2rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 0;
    z-index: 100;
}

.welcome-text h1 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--dark);
}

.welcome-text p {
    color: var(--gray);
    font-size: 0.9rem;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.time-display {
    text-align: right;
}

.current-time {
    font-size: 1.1rem;
    font-weight: 600;
    font-family: monospace;
    color: var(--dark);
}

.current-date {
    font-size: 0.8rem;
    color: var(--gray);
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
}

/* Dashboard Container */
.dashboard-container {
    padding: 2rem;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.stat-card {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 1.8rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    border-left: 4px solid;
}

.stat-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), transparent);
    transform: translateX(-100%);
    transition: transform 0.5s ease;
}

.stat-card:hover::before {
    transform: translateX(0);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
}

.stat-card:nth-child(1) {
    border-left-color: var(--primary);
}

.stat-card:nth-child(2) {
    border-left-color: var(--warning);
}

.stat-card:nth-child(3) {
    border-left-color: var(--danger);
}

.stat-card:nth-child(4) {
    border-left-color: var(--success);
}

.stat-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.stat-card-title {
    font-weight: 500;
    font-size: 0.9rem;
    color: var(--gray);
}

.stat-icon {
    font-size: 1.8rem;
    opacity: 0.8;
}

.stat-card:nth-child(1) .stat-icon {
    color: var(--primary);
}

.stat-card:nth-child(2) .stat-icon {
    color: var(--warning);
}

.stat-card:nth-child(3) .stat-icon {
    color: var(--danger);
}

.stat-card:nth-child(4) .stat-icon {
    color: var(--success);
}

.stat-card-value {
    font-size: 2.2rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.stat-card-footer {
    font-size: 0.85rem;
    color: var(--gray);
}

/* Section Title */
.section-title {
    font-size: 1.4rem;
    font-weight: 600;
    margin: 2rem 0 1.5rem;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--gray-light);
}

.section-title i {
    font-size: 1.2rem;
    color: var(--primary);
}

/* Orders Table */
.orders-table {
    overflow-x: auto;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 3rem;
    background: var(--card-bg);
}

.orders-table table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1000px;
}

.orders-table th {
    background: var(--primary);
    color: white;
    padding: 1rem;
    text-align: left;
    font-weight: 500;
    white-space: nowrap;
}

.orders-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--gray-light);
    white-space: nowrap;
}

.orders-table tr:last-child td {
    border-bottom: none;
}

.orders-table tr:nth-child(even) {
    background: #fafafa;
}

.orders-table tr:hover {
    background: #f0f4f8;
}

.no-orders {
    text-align: center;
    padding: 2rem;
    color: var(--gray);
    font-style: italic;
}

/* Mobile Menu Toggle */
.menu-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 1.5rem;
    color: var(--dark);
    cursor: pointer;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .sidebar {
        width: 70px;
        overflow: visible;
    }
    
    .sidebar-header h2, .menu-item span {
        display: none;
    }
    
    .sidebar-header {
        justify-content: center;
        padding: 1rem;
    }
    
    .menu-item {
        justify-content: center;
        padding: 1rem;
    }
    
    .main-content {
        margin-left: 70px;
    }
    
    .sidebar:hover {
        width: 260px;
    }
    
    .sidebar:hover .sidebar-header h2,
    .sidebar:hover .menu-item span {
        display: block;
    }
    
    .sidebar:hover .menu-item {
        justify-content: flex-start;
        padding: 0.9rem 1.5rem;
    }
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
        width: 260px;
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0;
    }
    
    .menu-toggle {
        display: block;
    }
    
    .topbar {
        padding: 1rem;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .dashboard-container {
        padding: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .user-info {
        flex-direction: column;
        gap: 5px;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .orders-table table {
        min-width: 800px;
    }
    
    .section-title {
        font-size: 1.2rem;
    }
    
    .stat-card-value {
        font-size: 1.8rem;
    }
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-store"></i>
        <h2>IBA STORE</h2>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="menu-item active">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        <a href="manage_products.php" class="menu-item">
            <i class="fas fa-boxes"></i>
            <span>Manage Products</span>
        </a>
        <a href="stock_alerts.php" class="menu-item">
            <i class="fas fa-bell"></i>
            <span>Stock Alerts</span>
        </a>
        <a href="expiry_alerts.php" class="menu-item">
            <i class="fas fa-clock"></i>
            <span>Expiry Alerts</span>
        </a>
        <a href="staff_management.php" class="menu-item">
            <i class="fas fa-users"></i>
            <span>Staff Management</span>
        </a>
        <a href="admin_offers.php" class="menu-item">
            <i class="fas fa-tags"></i>
            <span>Offers</span>
        </a>
        <a href="daily_report.php" class="menu-item">
            <i class="fas fa-chart-bar"></i>
            <span>Daily Report</span>
        </a>
        <a href="logout.php" class="menu-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="welcome-text">
            <h1>Welcome back, <?= htmlspecialchars($adminName) ?>!</h1>
        </div>
        <div class="user-info">
            <div class="time-display">
                <div class="current-time" id="current-time">--:--:--</div>
                <div class="current-date" id="current-date">Loading...</div>
            </div>
            <div class="user-avatar">
                <?= strtoupper(substr($adminName, 0, 1)) ?>
            </div>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="dashboard-container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-title">TOTAL PRODUCTS</div>
                    <i class="fas fa-box stat-icon"></i>
                </div>
                <div class="stat-card-value"><?= $totalProducts ?></div>
                <div class="stat-card-footer">All products in inventory</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-title">LOW STOCK</div>
                    <i class="fas fa-exclamation-triangle stat-icon"></i>
                </div>
                <div class="stat-card-value"><?= $lowStock ?></div>
                <div class="stat-card-footer">Products with stock less than 10</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-title">EXPIRING PRODUCTS</div>
                    <i class="fas fa-calendar-times stat-icon"></i>
                </div>
                <div class="stat-card-value"><?= $expiringProducts ?></div>
                <div class="stat-card-footer">Expiring within 30 days</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-title">ACTIVE STAFF</div>
                    <i class="fas fa-user-check stat-icon"></i>
                </div>
                <div class="stat-card-value"><?= $activeStaff ?></div>
                <div class="stat-card-footer">Total staff members</div>
            </div>
        </div>

        <h2 class="section-title"><i class="fas fa-shopping-cart"></i> Today's Orders</h2>
        <div class="orders-table">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Staff</th>
                        <th>Customer</th>
                        <th>Category</th>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>GST %</th>
                        <th>GST Amount</th>
                        <th>Total Amount</th>
                        <th>Order Date</th>
                        <th>Delivery Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($todayOrders->num_rows > 0): ?>
                        <?php while($row = $todayOrders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $row['order_id'] ?></td>
                                <td><?= htmlspecialchars($row['staff_name']) ?></td>
                                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td><?= htmlspecialchars($row['category_name']) ?></td>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td>₹<?= number_format($row['price'], 2) ?></td>
                                <td><?= htmlspecialchars($row['quantity']) ?> <?= htmlspecialchars($row['unit']) ?></td>
                                <td><?= number_format($row['gst_percentage'], 2) ?>%</td>
                                <td>₹<?= number_format($row['gst_amount'], 2) ?></td>
                                <td>₹<?= number_format($row['total_amount'], 2) ?></td>
                                <td><?= date('M d, Y g:i A', strtotime($row['order_date'])) ?></td>
                                <td><?= !empty($row['delivery_date']) ? date('M d, Y', strtotime($row['delivery_date'])) : '-' ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" style="text-align:center; padding: 2rem;">No orders found today.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Time display
function updateTime() {
    const now = new Date();
    document.getElementById('current-time').textContent = now.toLocaleTimeString('en-IN', { hour12:false });
    document.getElementById('current-date').textContent = now.toLocaleDateString('en-IN', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
}
updateTime();
setInterval(updateTime, 1000);

// Mobile menu toggle
document.getElementById('menuToggle').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('active');
});

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menuToggle');
    
    if (window.innerWidth <= 768 && 
        !sidebar.contains(event.target) && 
        !menuToggle.contains(event.target) &&
        sidebar.classList.contains('active')) {
        sidebar.classList.remove('active');
    }
});

// Responsive sidebar behavior
window.addEventListener('resize', function() {
    const sidebar = document.getElementById('sidebar');
    if (window.innerWidth > 768) {
        sidebar.classList.remove('active');
    }
});
</script>

</body>
</html>