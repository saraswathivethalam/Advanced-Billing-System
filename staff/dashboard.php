<?php
    session_start();
require("../config/database.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: login.php');
    exit;
}

date_default_timezone_set('Asia/Kolkata');
$current_date = date("l, F j, Y");
$current_time = date("h:i A");
$staff_id = $_SESSION['staff_id'] ?? 0;

// Optional: simple safety check
if ($staff_id == 0) {
    die("❌ Staff not logged in");
}


$sql = "SELECT id, name, username, email, phone, join_date FROM staff WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();


// Fetch real data from database
$today_bills = 0;
$today_revenue = 0;
$customers_served = 0;
$active_offers = 2;

if ($staff_id) {
    // Get staff name
    $stmt = $conn->prepare("SELECT name FROM staff WHERE id=?");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $staff = $result->fetch_assoc();
        $staff_name = $staff['name'];
        $_SESSION['username'] = $staff_name;
    }
    $stmt->close();

    // Get today's bills count and revenue
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(*) as bill_count, COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE staff_id = ? AND DATE(order_date) = ?");
    $stmt->bind_param("is", $staff_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $today_bills = $data['bill_count'];
        $today_revenue = $data['revenue'];
    }
    $stmt->close();

    // Get customers served today
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT customer_name) as customers FROM orders WHERE staff_id = ? AND DATE(order_date) = ?");
    $stmt->bind_param("is", $staff_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $customers_served = $data['customers'];
    }
    $stmt->close();

    // Example: Pending orders count
$sql = "SELECT COUNT(*) as pending_orders FROM orders WHERE staff_id=? AND payment_status='pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$pending_orders = $result->fetch_assoc()['pending_orders'] ?? 0;
$stmt->close();

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Dashboard - IBA STORE</title>
<style>
    :root {
        --primary: #2563EB;
        --primary-light: #3B82F6;
        --secondary: #059669;
        --accent: #DC2626;
        --sidebar: #1E293B;
        --light: #F8FAFC;
        --dark: #1E293B;
        --gray: #64748B;
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    body {
        background: var(--light);
        color: var(--dark);
        display: flex;
        min-height: 100vh;
    }
    
    /* Sidebar */
    .sidebar {
        width: 260px;
        background: var(--sidebar);
        color: white;
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        padding: 20px 0;
    }
    
    .logo {
        text-align: center;
        padding: 20px;
        border-bottom: 1px solid #334155;
        margin-bottom: 20px;
    }
    
    .logo h2 {
        color: white;
        font-size: 1.5rem;
    }
    
    .menu {
        padding: 0 15px;
    }
    
    .menu-item {
        display: flex;
        align-items: center;
        padding: 15px 20px;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        margin-bottom: 8px;
        transition: all 0.3s;
    }
    
    .menu-item:hover, .menu-item.active {
        background: var(--primary);
    }
    
    .menu-item i {
        margin-right: 12px;
        font-size: 1.2rem;
        width: 20px;
        text-align: center;
    }
    
    /* Main Content */
    .main-content {
        flex: 1;
        margin-left: 260px;
        padding: 30px;
    }
    
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        padding: 20px 0;
        border-bottom: 2px solid #E2E8F0;
    }
    
    .welcome h1 {
        font-size: 2rem;
        color: var(--primary);
        margin-bottom: 5px;
    }
    
    .datetime {
        color: var(--gray);
        font-size: 1.1rem;
    }
    
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        border-left: 4px solid var(--primary);
    }
    
    .stat-card:nth-child(2) { border-left-color: var(--secondary); }
    .stat-card:nth-child(3) { border-left-color: #7C3AED; }
    .stat-card:nth-child(4) { border-left-color: var(--accent); }
    
    .stat-number {
        font-size: 2.2rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 8px;
    }
    
    .stat-label {
        color: var(--gray);
        font-size: 1rem;
        font-weight: 500;
    }
    
    /* Features Grid */
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }
    
    .feature-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        text-decoration: none;
        color: inherit;
        transition: transform 0.3s;
        border: 1px solid #E2E8F0;
    }
    
    .feature-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }
    
    .feature-header {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .feature-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        background: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.4rem;
        margin-right: 15px;
    }
    
    .feature-card:nth-child(2) .feature-icon { background: var(--secondary); }
    .feature-card:nth-child(3) .feature-icon { background: #7C3AED; }
    .feature-card:nth-child(4) .feature-icon { background: #DC2626; }
    .feature-card:nth-child(5) .feature-icon { background: #D97706; }
    
    .feature-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--dark);
    }
    
    .feature-desc {
        color: var(--gray);
        line-height: 1.5;
        margin-bottom: 15px;
    }
    
    .feature-badge {
        display: inline-block;
        background: var(--primary);
        color: white;
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    /* Mobile Menu */
    .menu-toggle {
        display: none;
        background: var(--primary);
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 1.2rem;
    }
    
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s;
        }
        
        .sidebar.active {
            transform: translateX(0);
        }
        
        .main-content {
            margin-left: 0;
            padding: 20px;
        }
        
        .menu-toggle {
            display: block;
            margin-bottom: 20px;
        }
        
        .header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        
        .stats-grid,
        .features-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">
        <h2>IBA STORE</h2>
        <p style="color: #94A3B8; font-size: 0.9rem;">Staff Portal</p>
    </div>
    
    <div class="menu">
        <a href="#" class="menu-item active">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        <a href="generate_bill.php" class="menu-item">
            <i class="fas fa-receipt"></i>
            <span>Generate Bill</span>
        </a>
        <a href="orders.php" class="menu-item">
            <i class="fas fa-shopping-cart"></i>
            <span>Orders</span>
        </a>
        <a href="offers.php" class="menu-item">
            <i class="fas fa-gift"></i>
            <span>Offers</span>
        </a>
        <a href="payment_history.php" class="menu-item">
            <i class="fas fa-history"></i>
            <span>Payment History</span>
        </a>
        <a href="my_profile.php" class="menu-item">
            <i class="fas fa-user-circle"></i>
            <span>My Profile</span>
        </a>
        <a href="logout.php" class="menu-item" style="margin-top: 30px; color: #F87171;">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <button class="menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="header">
        <div class="welcome">
           <h2>Welcome, <?= htmlspecialchars($staff['name']) ?>!</h2>
</table>


            <div class="datetime"><?= $current_date; ?> | <?= $current_time; ?></div>
        </div>
    </div>
    
    <!-- Real Stats from Database -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $today_bills; ?></div>
            <div class="stat-label">Today's Bills</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number">₹<?= number_format($today_revenue, 2); ?></div>
            <div class="stat-label">Today's Revenue</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number"><?= $customers_served; ?></div>
            <div class="stat-label">Customers Served</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number"><?= $active_offers; ?></div>
            <div class="stat-label">Active Offers</div>
        </div>
    </div>
    
    <!-- Features -->
    <div class="features-grid">
        <a href="generate_bill.php" class="feature-card">
            <div class="feature-header">
                <div class="feature-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <h3 class="feature-title">Generate Bill</h3>
            </div>
            <p class="feature-desc">Create invoices and bills for customers</p>
            <span class="feature-badge">Quick Access</span>
        </a>
        
        <a href="orders.php" class="feature-card">
            <div class="feature-header">
                <div class="feature-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3 class="feature-title">Order Management</h3>
            </div>
            <p class="feature-desc">Manage and track customer orders</p>
            <span class="feature-badge">View Orders</span>
        </a>
        
        <a href="offers.php" class="feature-card">
            <div class="feature-header">
                <div class="feature-icon">
                    <i class="fas fa-gift"></i>
                </div>
                <h3 class="feature-title">Promotions & Offers</h3>
            </div>
            <p class="feature-desc">Manage discounts and promotions</p>
            <span class="feature-badge"><?= $active_offers; ?> Active</span>
        </a>
        
        <a href="payment_history.php" class="feature-card">
            <div class="feature-header">
                <div class="feature-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h3 class="feature-title">Payment History</h3>
            </div>
            <p class="feature-desc">View transaction records</p>
            <span class="feature-badge">Secure</span>
        </a>
        
        <a href="my_profile.php" class="feature-card">
            <div class="feature-header">
                <div class="feature-icon">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h3 class="feature-title">My Profile</h3>
            </div>
            <p class="feature-desc">Update your information</p>
            <span class="feature-badge">Personal</span>
        </a>
    </div>
</div>

<script>
// Mobile menu toggle
document.querySelector('.menu-toggle').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.toggle('active');
});

// Update time every minute
setInterval(() => {
    const now = new Date();
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    };
    const dateStr = now.toLocaleDateString('en-IN', options);
    const timeStr = now.toLocaleTimeString('en-US', {
        hour: '2-digit', 
        minute: '2-digit', 
        hour12: true 
    });
    
    document.querySelector('.datetime').textContent = `${dateStr} | ${timeStr}`;
}, 60000);

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.sidebar');
    const toggle = document.querySelector('.menu-toggle');
    
    if (window.innerWidth <= 768 && 
        !sidebar.contains(event.target) && 
        !toggle.contains(event.target) && 
        sidebar.classList.contains('active')) {
        sidebar.classList.remove('active');
    }
});
</script>

</body>
</html>