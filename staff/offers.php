<?php
session_start();
require("../config/database.php");

// Only staff can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit;
}

// Fetch all active offers from the database
$sql = "SELECT id, title, discount, start_date, end_date 
        FROM offers 
        WHERE status='active'
        ORDER BY start_date DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Offers | Staff Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6C63FF;
            --primary-light: #8A84FF;
            --secondary: #FF6584;
            --accent: #36D1DC;
            --success: #4CD964;
            --warning: #FF9500;
            --light: #F8F9FF;
            --dark: #2D2B55;
            --gray: #A0A0C0;
            --card-shadow: 0 15px 35px rgba(108, 99, 255, 0.1);
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #F8F9FF 0%, #E6E9FF 100%);
            min-height: 100vh;
            padding: 0;
            color: var(--dark);
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header Styles */
        .main-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            padding: 80px 0 60px;
            text-align: center;
            position: relative;
            overflow: hidden;
            margin-bottom: 50px;
            box-shadow: 0 10px 30px rgba(108, 99, 255, 0.3);
        }
        
        .main-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,70 Q50,100 100,70 L100,0 L0,0 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
        }
        
        .main-header::after {
            content: "";
            position: absolute;
            top: -100px;
            right: -100px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
        }
        
        .header-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
        }
        
        h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 15px;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            letter-spacing: -1px;
        }
        
        .subtitle {
            font-size: 1.4rem;
            opacity: 0.9;
            font-weight: 300;
            margin-bottom: 30px;
        }
        
        .header-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        
        .header-stat {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .header-stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            display: block;
        }
        
        .header-stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        /* Stats Bar */
        .stats-bar {
            display: flex;
            justify-content: space-around;
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            margin-bottom: 40px;
            flex-wrap: wrap;
            border: 1px solid rgba(108, 99, 255, 0.1);
        }
        
        .stat-item {
            text-align: center;
            padding: 10px 20px;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            display: block;
        }
        
        .stat-label {
            font-size: 1rem;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }
        
        /* Offers Grid */
        .offers-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }
        
        .offer-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            position: relative;
            border: 1px solid rgba(108, 99, 255, 0.1);
        }
        
        .offer-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 25px 50px rgba(108, 99, 255, 0.15);
        }
        
        .offer-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--secondary);
            color: white;
            padding: 8px 15px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            z-index: 2;
            box-shadow: 0 5px 15px rgba(255, 101, 132, 0.4);
        }
        
        .offer-image {
            height: 200px;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .offer-image::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Q50,80 0,100 Z" fill="rgba(255,255,255,0.2)"/></svg>');
            background-size: cover;
        }
        
        .discount-circle {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid white;
            backdrop-filter: blur(5px);
            z-index: 1;
            position: relative;
            overflow: hidden;
        }
        
        .discount-circle::after {
            content: "";
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            z-index: -1;
        }
        
        .discount-value {
            font-size: 2.8rem;
            font-weight: 800;
            color: white;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .offer-content {
            padding: 25px;
        }
        
        .offer-title {
            font-size: 1.6rem;
            margin-bottom: 15px;
            color: var(--dark);
            font-weight: 600;
            line-height: 1.4;
        }
        
        .offer-dates {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed #e9ecef;
        }
        
        .date-item {
            text-align: center;
            flex: 1;
        }
        
        .date-label {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }
        
        .date-value {
            font-weight: 600;
            color: var(--primary);
            font-size: 1.1rem;
        }
        
        .progress-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            margin-top: 15px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent), var(--primary));
            border-radius: 4px;
            width: 70%; /* This would be dynamic based on date */
            position: relative;
            overflow: hidden;
        }
        
        .progress-fill::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 20px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent);
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(400%); }
        }
        
        /* No Offers State */
        .no-offers {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 30px;
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(108, 99, 255, 0.1);
        }
        
        .no-offers i {
            font-size: 5rem;
            color: var(--gray);
            margin-bottom: 25px;
            opacity: 0.5;
        }
        
        .no-offers h2 {
            color: var(--dark);
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .no-offers p {
            color: var(--gray);
            max-width: 500px;
            margin: 0 auto;
            line-height: 1.6;
            font-size: 1.1rem;
        }
        
        /* Action Buttons */
        .actions {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 16px 35px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            margin: 0 15px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            box-shadow: 0 10px 20px rgba(108, 99, 255, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 15px 30px rgba(108, 99, 255, 0.4);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        
        .btn-outline:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-5px);
        }
        
        .btn i {
            margin-right: 10px;
        }
        
        /* Footer */
        footer {
            text-align: center;
            margin-top: 50px;
            color: var(--gray);
            font-size: 0.9rem;
            padding: 30px 20px;
            border-top: 1px solid rgba(108, 99, 255, 0.1);
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .offers-container {
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            }
            
            h1 {
                font-size: 3.2rem;
            }
        }
        
        @media (max-width: 768px) {
            .offers-container {
                grid-template-columns: 1fr;
            }
            
            h1 {
                font-size: 2.5rem;
            }
            
            .subtitle {
                font-size: 1.2rem;
            }
            
            .header-stats {
                gap: 20px;
            }
            
            .header-stat {
                padding: 15px 20px;
            }
            
            .header-stat-value {
                font-size: 2rem;
            }
            
            .stats-bar {
                flex-direction: column;
                gap: 20px;
            }
            
            .btn {
                width: 100%;
                margin: 10px 0;
            }
            
            .actions {
                display: flex;
                flex-direction: column;
                gap: 15px;
            }
        }
        
        /* Animation for cards */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .offer-card {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }
        
        .offer-card:nth-child(1) { animation-delay: 0.1s; }
        .offer-card:nth-child(2) { animation-delay: 0.2s; }
        .offer-card:nth-child(3) { animation-delay: 0.3s; }
        .offer-card:nth-child(4) { animation-delay: 0.4s; }
        .offer-card:nth-child(5) { animation-delay: 0.5s; }
        .offer-card:nth-child(6) { animation-delay: 0.6s; }
        
        /* Floating elements */
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body>
    <!-- Main Header -->
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <h1 class="floating">Active Offers</h1>
                <p class="subtitle">Manage and view all current promotional offers</p>
                
                <div class="header-stats">
                    <div class="header-stat">
                        <span class="header-stat-value">
                            <?php 
                                $count = $result ? $result->num_rows : 0;
                                echo $count;
                            ?>
                        </span>
                        <span class="header-stat-label">Active Offers</span>
                    </div>
                    <div class="header-stat">
                        <span class="header-stat-value">
                            <?php
                                $maxDiscount = 0;
                                if ($result && $result->num_rows > 0) {
                                    $result->data_seek(0);
                                    while($row = $result->fetch_assoc()) {
                                        if ($row['discount'] > $maxDiscount) {
                                            $maxDiscount = $row['discount'];
                                        }
                                    }
                                    $result->data_seek(0);
                                }
                                echo $maxDiscount . '%';
                            ?>
                        </span>
                        <span class="header-stat-label">Highest Discount</span>
                    </div>
                    <div class="header-stat">
                        <span class="header-stat-value">
                            <?php
                                $soonExpiring = 0;
                                $today = date('Y-m-d');
                                $nextWeek = date('Y-m-d', strtotime('+7 days'));
                                
                                if ($result && $result->num_rows > 0) {
                                    $result->data_seek(0);
                                    while($row = $result->fetch_assoc()) {
                                        if ($row['end_date'] <= $nextWeek && $row['end_date'] >= $today) {
                                            $soonExpiring++;
                                        }
                                    }
                                    $result->data_seek(0);
                                }
                                echo $soonExpiring;
                            ?>
                        </span>
                        <span class="header-stat-label">Expiring Soon</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Action Buttons -->
        <div class="actions">
            <a href="add_offer.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Offer
            </a>
            <a href="dashboard.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <!-- Offers Grid -->
        <div class="offers-container">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): 
                    // Calculate progress (how much of the offer period has passed)
                    $start = strtotime($row['start_date']);
                    $end = strtotime($row['end_date']);
                    $today = time();
                    $totalDuration = $end - $start;
                    $elapsed = $today - $start;
                    $progress = ($totalDuration > 0) ? min(100, max(0, ($elapsed / $totalDuration) * 100)) : 0;
                ?>
                <div class="offer-card">
                    <div class="offer-badge">ID: <?= $row['id'] ?></div>
                    <div class="offer-image">
                        <div class="discount-circle">
                            <span class="discount-value"><?= $row['discount'] ?>%</span>
                        </div>
                    </div>
                    <div class="offer-content">
                        <h3 class="offer-title"><?= htmlspecialchars($row['title']) ?></h3>
                        
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $progress ?>%"></div>
                        </div>
                        
                        <div class="offer-dates">
                            <div class="date-item">
                                <div class="date-label">Starts</div>
                                <div class="date-value"><?= date('M j, Y', strtotime($row['start_date'])) ?></div>
                            </div>
                            <div class="date-item">
                                <div class="date-label">Ends</div>
                                <div class="date-value"><?= date('M j, Y', strtotime($row['end_date'])) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-offers">
                    <i class="far fa-frown-open"></i>
                    <h2>No Active Offers</h2>
                    <p>There are no active promotional offers at the moment. Add a new offer to attract more customers.</p>
                    <div style="margin-top: 30px;">
                        <a href="add_offer.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Your First Offer
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <footer>
            <p>Staff Dashboard &copy; <?= date('Y') ?> | Active Offers Management System</p>
        </footer>
    </div>

    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effect to cards
            const cards = document.querySelectorAll('.offer-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
            
            // Animate stats counter
            const statValues = document.querySelectorAll('.header-stat-value');
            statValues.forEach(stat => {
                const text = stat.textContent;
                if (text.includes('%')) {
                    const target = parseInt(text);
                    let current = 0;
                    const increment = target / 30;
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            current = target;
                            clearInterval(timer);
                        }
                        stat.textContent = Math.round(current) + '%';
                    }, 50);
                } else {
                    const target = parseInt(text);
                    let current = 0;
                    const increment = target / 30;
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            current = target;
                            clearInterval(timer);
                        }
                        stat.textContent = Math.round(current);
                    }, 50);
                }
            });
        });
    </script>
</body>
</html>