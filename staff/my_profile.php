<?php
session_start();
require("../config/database.php");

// 🔒 Staff login check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit;
}

// Get staff ID from session safely
$staff_id = $_SESSION['staff_id'] ?? 0;
if ($staff_id == 0) {
    die("❌ Staff not logged in. Please login first.");
}

// Fetch staff details
$sql = "SELECT id, name, username, email, phone, join_date FROM staff WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - <?= isset($staff['name']) ? htmlspecialchars($staff['name']) : 'Staff' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2e7d32;
            --primary-dark: #1b5e20;
            --secondary: #ff9800;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #4caf50;
            --card-shadow: 0 10px 30px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8f5e8 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            width: 100%;
            max-width: 900px;
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin: 20px auto;
        }
        
        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            text-align: center;
            padding: 30px 20px;
            position: relative;
            transition: var(--transition);
        }
        
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .profile-header {
            margin-bottom: 25px;
        }
        
        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.3);
        }
        
        .profile-name {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .profile-role {
            color: var(--primary);
            font-weight: 500;
            font-size: 16px;
            padding: 5px 15px;
            background: rgba(46, 125, 50, 0.1);
            border-radius: 20px;
            display: inline-block;
        }
        
        .profile-stats {
            display: flex;
            justify-content: space-around;
            margin: 25px 0;
            padding: 15px 0;
            border-top: 1px solid rgba(0,0,0,0.05);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .profile-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 20px;
        }
        
        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 15px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.3);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        
        .btn-outline:hover {
            background: rgba(46, 125, 50, 0.1);
            transform: translateY(-2px);
        }
        
        .details-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            padding: 30px;
            transition: var(--transition);
        }
        
        .details-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .card-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--dark);
            margin-left: 10px;
        }
        
        .card-icon {
            width: 40px;
            height: 40px;
            background: rgba(46, 125, 50, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 18px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .info-item {
            margin-bottom: 20px;
        }
        
        .info-label {
            font-size: 13px;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            padding: 12px 15px;
            background: rgba(0,0,0,0.02);
            border-radius: 10px;
            border-left: 4px solid var(--primary);
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }
        
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                max-width: 500px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Profile Card -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h2 class="profile-name">
                    <?= isset($staff['name']) ? htmlspecialchars($staff['name']) : 'Staff Member' ?>
                </h2>
                <div class="profile-role">Staff Member</div>
    </div>
            
            <div class="profile-actions">
                <a href="dashboard.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="edit_profile.php" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
            </div>
        </div>
        
        <!-- Details Card -->
        <div class="details-card">
            <div class="card-header">
                <div class="card-icon">
                    <i class="fas fa-id-card"></i>
                </div>
                <h2 class="card-title">Profile Information</h2>
            </div>
            
            <?php if (!$staff): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Unable to load profile information. Please try again later.</p>
                </div>
            <?php else: ?>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?= htmlspecialchars($staff['name']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Username</div>
                        <div class="info-value"><?= htmlspecialchars($staff['username']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?= htmlspecialchars($staff['email']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Phone</div>
                        <div class="info-value"><?= htmlspecialchars($staff['phone'] ?? 'Not set') ?></div>
                    </div>
                    
                    <div class="info-item full-width">
                        <div class="info-label">Joined On</div>
                        <div class="info-value"><?= htmlspecialchars($staff['join_date']) ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
// Close connection only if it exists
if (isset($conn)) {
    $conn->close();
}
?>