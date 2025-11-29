<?php
session_start();
require("../config/database.php");

// Check if staff is logged in
if(!isset($_SESSION['staff_id'])){
    header("Location: login.php");
    exit;
}

$staff_id = $_SESSION['staff_id'];

// Fetch staff name for greeting
$stmt = $conn->prepare("SELECT name FROM staff WHERE id=?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();
$stmt->close();

// Get current date, day, and time
date_default_timezone_set('Asia/Kolkata');
$current_date = date("l, F j, Y");
$current_time = date("h:i A");
$current_day = date("l");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Dashboard</title>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #0c0c0c 0%, #2d1b69 100%);
        min-height: 100vh;
        color: white;
        display: flex;
    }
    
    /* Sidebar Styles */
    .sidebar {
        width: 250px;
        background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
        padding: 30px 20px;
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        border-right: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 5px 0 25px rgba(0, 0, 0, 0.3);
    }
    
    .logo {
        text-align: center;
        margin-bottom: 40px;
        padding-bottom: 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .logo h2 {
        background: linear-gradient(45deg, #ff6b35, #667eea);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-size: 24px;
    }
    
    .nav-links {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .nav-link {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px 20px;
        text-decoration: none;
        color: rgba(255, 255, 255, 0.8);
        border-radius: 12px;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .nav-link:hover, .nav-link.active {
        background: linear-gradient(135deg, #ff6b35, #f7931e);
        color: white;
        transform: translateX(5px);
    }
    
    .nav-link i {
        font-size: 18px;
        width: 20px;
    }
    
    /* Main Content */
    .main-content {
        flex: 1;
        margin-left: 250px;
        padding: 30px;
    }
    
    /* Top Bar */
    .top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
        backdrop-filter: blur(15px);
        padding: 20px 30px;
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .user-avatar {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #ff6b35, #f7931e);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        font-weight: bold;
    }
    
    .user-details h3 {
        font-size: 18px;
        margin-bottom: 5px;
    }
    
    .user-details p {
        font-size: 14px;
        opacity: 0.7;
    }
    
    .datetime-display {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 5px;
    }
    
    .current-date {
        font-size: 16px;
        font-weight: 500;
        opacity: 0.9;
    }
    
    .current-time {
        font-size: 24px;
        font-weight: 700;
        background: linear-gradient(45deg, #ff6b35, #667eea);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    /* Header */
    .header {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
        backdrop-filter: blur(15px);
        border-radius: 20px;
        padding: 40px;
        margin-bottom: 40px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: rotate 10s linear infinite;
    }
    
    @keyframes rotate {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .welcome-section {
        position: relative;
        z-index: 2;
    }
    
    .welcome-section h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 15px;
        background: linear-gradient(45deg, #ff6b35, #f7931e, #667eea, #764ba2);
        background-size: 300% 300%;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: gradientShift 3s ease infinite;
    }
    
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    .welcome-section p {
        font-size: 1.2rem;
        opacity: 0.8;
        font-weight: 300;
    }
    
    /* Cards Grid */
    .cards-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }
    
    .card {
        background: linear-gradient(135deg, rgba(40, 40, 40, 0.9) 0%, rgba(30, 30, 30, 0.9) 100%);
        border-radius: 20px;
        padding: 30px;
        text-decoration: none;
        color: white;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: 1px solid rgba(255, 255, 255, 0.1);
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
    
    .card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        transition: 0.5s;
    }
    
    .card:hover::before {
        left: 100%;
    }
    
    .card:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        border-color: rgba(255, 255, 255, 0.2);
    }
    
    .card-content {
        display: flex;
        align-items: center;
        gap: 20px;
        position: relative;
        z-index: 2;
    }
    
    .card-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    }
    
    .card-text h3 {
        font-size: 1.3rem;
        font-weight: 600;
        margin-bottom: 8px;
    }
    
    .card-text p {
        font-size: 0.9rem;
        opacity: 0.7;
        line-height: 1.4;
    }
    
    /* Individual card colors */
    .card:nth-child(1) .card-icon {
        background: linear-gradient(135deg, #ff6b35, #f7931e);
    }
    
    .card:nth-child(2) .card-icon {
        background: linear-gradient(135deg, #667eea, #764ba2);
    }
    
    .card:nth-child(3) .card-icon {
        background: linear-gradient(135deg, #f093fb, #f5576c);
    }
    
    .card:nth-child(4) .card-icon {
        background: linear-gradient(135deg, #4facfe, #00f2fe);
    }
    
    .card:nth-child(5) .card-icon {
        background: linear-gradient(135deg, #43e97b, #38f9d7);
    }
    
    /* Stats Bar */
    .stats-bar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .stat-card {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
        padding: 25px;
        border-radius: 15px;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 5px;
        background: linear-gradient(45deg, #ff6b35, #667eea);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .stat-label {
        font-size: 0.9rem;
        opacity: 0.7;
    }
    
    /* Quick Actions */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .action-btn {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
        padding: 20px;
        border-radius: 15px;
        text-align: center;
        text-decoration: none;
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
    }
    
    .action-btn:hover {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%);
        transform: translateY(-3px);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        body {
            flex-direction: column;
        }
        
        .sidebar {
            width: 100%;
            height: auto;
            position: relative;
            padding: 20px;
        }
        
        .main-content {
            margin-left: 0;
            padding: 20px;
        }
        
        .top-bar {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
        
        .datetime-display {
            align-items: center;
        }
        
        .welcome-section h1 {
            font-size: 2rem;
        }
        
        .cards-container {
            grid-template-columns: 1fr;
        }
    }
</style>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <h2>🍽️ RestaurantPro</h2>
        </div>
        
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="generate_bill.php" class="nav-link">
                <i class="fas fa-receipt"></i>
                <span>Generate Bill</span>
            </a>
            <a href="order_details.php" class="nav-link">
                <i class="fas fa-clipboard-list"></i>
                <span>Orders</span>
            </a>
            <a href="offers.php" class="nav-link">
                <i class="fas fa-tags"></i>
                <span>Offers</span>
            </a>
            <a href="payment_history.php" class="nav-link">
                <i class="fas fa-history"></i>
                <span>Payments</span>
            </a>
            <a href="staff_profile.php" class="nav-link">
                <i class="fas fa-user-cog"></i>
                <span>Profile</span>
            </a>
            <a href="logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($staff['name'], 0, 1)) ?>
                </div>
                <div class="user-details">
                    <h3><?= htmlspecialchars($staff['name']) ?></h3>
                    <p>Staff Member</p>
                </div>
            </div>
            
            <div class="datetime-display">
                <div class="current-date" id="live-date"><?= $current_date ?></div>
                <div class="current-time" id="live-time"><?= $current_time ?></div>
            </div>
        </div>

        <!-- Header -->
        <div class="header">
            <div class="welcome-section">
                <h1>Welcome back, <?= htmlspecialchars($staff['name']) ?>! 👋</h1>
                <p>Staff Management Dashboard</p>
            </div>
        </div>

        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-number">128</div>
                <div class="stat-label">Today's Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">₹24,560</div>
                <div class="stat-label">Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">42</div>
                <div class="stat-label">Pending Bills</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">95%</div>
                <div class="stat-label">Satisfaction</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="generate_bill.php" class="action-btn">
                <i class="fas fa-plus-circle" style="font-size: 24px; margin-bottom: 10px;"></i>
                <div>New Bill</div>
            </a>
            <a href="order_details.php" class="action-btn">
                <i class="fas fa-list" style="font-size: 24px; margin-bottom: 10px;"></i>
                <div>View Orders</div>
            </a>
            <a href="offers.php" class="action-btn">
                <i class="fas fa-tag" style="font-size: 24px; margin-bottom: 10px;"></i>
                <div>Manage Offers</div>
            </a>
            <a href="staff_profile.php" class="action-btn">
                <i class="fas fa-user" style="font-size: 24px; margin-bottom: 10px;"></i>
                <div>My Profile</div>
            </a>
        </div>

        <!-- Cards Grid -->
        <div class="cards-container">
            <a href="generate_bill.php" class="card">
                <div class="card-content">
                    <div class="card-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="card-text">
                        <h3>Generate Bill</h3>
                        <p>Create new bills for customers</p>
                    </div>
                </div>
            </a>
            
            <a href="order_details.php" class="card">
                <div class="card-content">
                    <div class="card-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="card-text">
                        <h3>Orders</h3>
                        <p>View all customer orders</p>
                    </div>
                </div>
            </a>
            
            <a href="offers.php" class="card">
                <div class="card-content">
                    <div class="card-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="card-text">
                        <h3>Offers</h3>
                        <p>Manage special offers</p>
                    </div>
                </div>
            </a>
            
            <a href="payment_history.php" class="card">
                <div class="card-content">
                    <div class="card-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="card-text">
                        <h3>Payment History</h3>
                        <p>View all payments made</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

<script>
// Live time and date update
function updateDateTime() {
    const now = new Date();
    
    // Update time
    const timeElem = document.getElementById('live-time');
    timeElem.textContent = now.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit', 
        hour12: true 
    });
    
    // Update date
    const dateElem = document.getElementById('live-date');
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    dateElem.textContent = now.toLocaleDateString('en-IN', options);
}

// Update immediately and then every second
updateDateTime();
setInterval(updateDateTime, 1000);

// Card animations
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 150);
    });
});

// Active nav link highlighting
const currentPage = window.location.pathname.split('/').pop();
const navLinks = document.querySelectorAll('.nav-link');
navLinks.forEach(link => {
    if (link.getAttribute('href') === currentPage) {
        link.classList.add('active');
    }
});

// Add subtle animation to datetime display
setInterval(() => {
    const timeElem = document.getElementById('live-time');
    timeElem.style.transform = 'scale(1.05)';
    setTimeout(() => {
        timeElem.style.transform = 'scale(1)';
    }, 200);
}, 5000);
</script>

</body>
</html>