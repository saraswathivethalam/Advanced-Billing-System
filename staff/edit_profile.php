<?php
session_start();
require("../config/database.php");

// 🔒 Staff login check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit;
}

$staff_id = $_SESSION['staff_id'] ?? 0;
if ($staff_id == 0) {
    die("❌ Staff not logged in. Please login first.");
}

// Fetch staff details
$sql = "SELECT id, name, username, email, phone FROM staff WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    die("❌ Staff not found.");
}

$staff = $result->fetch_assoc();
$error = "";
$success = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if ($name === "" || $email === "" || $phone === "") {
        $error = "All fields are required.";
    } else {
        $update_sql = "UPDATE staff SET name = ?, email = ?, phone = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssi", $name, $email, $phone, $staff_id);
        if ($stmt->execute()) {
            $success = "✅ Profile updated successfully.";
            $_SESSION['staff_name'] = $name; // update session
            // refresh details
            $staff['name'] = $name;
            $staff['email'] = $email;
            $staff['phone'] = $phone;
        } else {
            $error = "❌ Error updating profile.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - <?php echo htmlspecialchars($staff['name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a6fd8;
            --secondary: #764ba2;
            --accent: #f093fb;
            --light: #f8faff;
            --dark: #2d3748;
            --gray: #718096;
            --light-gray: #e2e8f0;
            --success: #48bb78;
            --error: #f56565;
            --warning: #ed8936;
            --border-radius: 20px;
            --box-shadow: 0 20px 60px rgba(102, 126, 234, 0.15);
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="%23ffffff10" points="0,1000 1000,0 1000,1000"/></svg>');
            background-size: cover;
            z-index: 0;
        }

        .container {
            width: 100%;
            max-width: 550px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            padding: 50px 40px;
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.3);
            z-index: 1;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--accent));
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            color: white;
            font-size: 40px;
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
            position: relative;
            transition: var(--transition);
            border: 4px solid white;
        }

        .profile-avatar:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.5);
        }

        .profile-avatar::after {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            z-index: -1;
            opacity: 0.6;
            filter: blur(10px);
        }

        h2 {
            color: var(--dark);
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 8px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        .subtitle {
            color: var(--gray);
            font-size: 16px;
            font-weight: 500;
        }

        .message {
            padding: 18px 20px;
            border-radius: 16px;
            margin-bottom: 30px;
            font-weight: 600;
            text-align: center;
            animation: slideDown 0.5s ease;
            border-left: 5px solid;
            position: relative;
            overflow: hidden;
        }

        .message::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.1;
        }

        .message.success {
            background: rgba(72, 187, 120, 0.1);
            color: var(--success);
            border-left-color: var(--success);
        }

        .message.success::before {
            background: var(--success);
        }

        .message.error {
            background: rgba(245, 101, 101, 0.1);
            color: var(--error);
            border-left-color: var(--error);
        }

        .message.error::before {
            background: var(--error);
        }

        .form-group {
            margin-bottom: 30px;
            position: relative;
            animation: slideInUp 0.6s ease;
        }

        .form-group label {
            display: block;
            margin-bottom: 12px;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            transition: var(--transition);
        }

        .form-group label i {
            color: var(--primary);
            width: 20px;
            font-size: 16px;
            transition: var(--transition);
        }

        .input-container {
            position: relative;
            transition: var(--transition);
        }

        .form-control {
            width: 100%;
            padding: 18px 20px 18px 50px;
            border: 2px solid var(--light-gray);
            border-radius: 14px;
            font-size: 16px;
            transition: var(--transition);
            background: white;
            color: var(--dark);
            font-weight: 500;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
        }

        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 18px;
            transition: var(--transition);
            z-index: 2;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
            background: white;
            transform: translateY(-3px);
            padding-left: 55px;
        }

        .form-control:focus + .input-icon {
            color: var(--primary);
            transform: translateY(-50%) scale(1.2);
        }

        .btn-group {
            display: flex;
            gap: 20px;
            margin-top: 40px;
        }

        .btn {
            flex: 1;
            padding: 18px 25px;
            border-radius: 14px;
            border: none;
            cursor: pointer;
            font-weight: 700;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 16px;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 14px;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: var(--transition);
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.5);
        }

        .btn-secondary {
            background: white;
            color: var(--dark);
            border: 2px solid var(--light-gray);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .btn-secondary:hover {
            background: var(--light);
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            color: var(--primary);
        }

        .btn i {
            margin-right: 10px;
            font-size: 16px;
        }

        /* Animations */
        @keyframes slideDown {
            from { 
                opacity: 0; 
                transform: translateY(-20px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        @keyframes slideInUp {
            from { 
                opacity: 0; 
                transform: translateY(30px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .container {
            animation: fadeIn 0.8s ease;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }

        /* Floating elements */
        .floating-element {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
            z-index: 0;
        }

        .floating-element:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 5%;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            width: 120px;
            height: 120px;
            bottom: 15%;
            right: 8%;
            animation-delay: 2s;
        }

        .floating-element:nth-child(3) {
            width: 60px;
            height: 60px;
            top: 50%;
            left: 8%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 40px 30px;
                max-width: 450px;
            }
            
            .btn-group {
                flex-direction: column;
                gap: 15px;
            }
            
            h2 {
                font-size: 28px;
            }
            
            .profile-avatar {
                width: 90px;
                height: 90px;
                font-size: 36px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }
            
            h2 {
                font-size: 24px;
            }
            
            .form-control {
                padding: 16px 20px 16px 45px;
            }
        }

        /* Password toggle effect */
        .password-toggle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            font-size: 16px;
            transition: var(--transition);
        }

        .password-toggle:hover {
            color: var(--primary);
            transform: translateY(-50%) scale(1.1);
        }
    </style>
</head>
<body>
    <!-- Floating background elements -->
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>

    <div class="container">
        <div class="header">
            <div class="profile-avatar">
                <i class="fas fa-user-edit"></i>
            </div>
            <h2>Edit Profile</h2>
            <p class="subtitle">Update your personal information</p>
        </div>

        <?php if ($error): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="name">
                    <i class="fas fa-signature"></i> Full Name
                </label>
                <div class="input-container">
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?php echo htmlspecialchars($staff['name']); ?>" required>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> Email Address
                </label>
                <div class="input-container">
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($staff['email']); ?>" required>
                    <div class="input-icon">
                        <i class="fas fa-at"></i>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="phone">
                    <i class="fas fa-phone"></i> Phone Number
                </label>
                <div class="input-container">
                    <input type="text" id="phone" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($staff['phone']); ?>" required>
                    <div class="input-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                </div>
            </div>
            
            <div class="btn-group">
                <a href="my_profile.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Profile
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-control');
            const buttons = document.querySelectorAll('.btn');
            
            // Enhanced input interactions
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                    this.parentElement.parentElement.style.transform = 'translateY(-5px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                    this.parentElement.parentElement.style.transform = 'translateY(0)';
                });
                
                // Real-time validation feedback
                input.addEventListener('input', function() {
                    if (this.value.trim() === '') {
                        this.style.borderColor = 'var(--error)';
                    } else {
                        this.style.borderColor = 'var(--success)';
                    }
                });
            });
            
            // Enhanced button interactions
            buttons.forEach(button => {
                button.addEventListener('mousedown', function() {
                    this.style.transform = 'scale(0.95)';
                });
                
                button.addEventListener('mouseup', function() {
                    this.style.transform = '';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                });
            });
            
            // Add ripple effect to buttons
            function createRipple(event) {
                const button = event.currentTarget;
                const circle = document.createElement('span');
                const diameter = Math.max(button.clientWidth, button.clientHeight);
                const radius = diameter / 2;
                
                circle.style.width = circle.style.height = diameter + 'px';
                circle.style.left = (event.clientX - button.getBoundingClientRect().left - radius) + 'px';
                circle.style.top = (event.clientY - button.getBoundingClientRect().top - radius) + 'px';
                circle.classList.add('ripple');
                
                const ripple = button.getElementsByClassName('ripple')[0];
                if (ripple) {
                    ripple.remove();
                }
                
                button.appendChild(circle);
            }
            
            buttons.forEach(button => {
                button.addEventListener('click', createRipple);
            });
        });
    </script>
</body>
</html> 