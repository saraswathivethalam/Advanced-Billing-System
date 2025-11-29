<?php
session_start();
include __DIR__ . '/config/database.php';

$error = "";
$enteredUsername = "";
$role = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredUsername = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if ($enteredUsername === '' || $password === '' || $role === '') {
        $error = "All fields are required!";
    } else {
        // Admin check (hardcoded or DB if you want)
        if ($role === 'admin' && $enteredUsername === 'admin' && $password === 'admin123') {
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'admin';
            $_SESSION['role'] = 'admin';
            header("Location: admin/admin_dashboard.php");
            exit;
        }

        // Staff check from DB
        if ($role === 'staff') {
            $stmt = $conn->prepare("SELECT id, username, password FROM staff WHERE username = ? LIMIT 1");
            $stmt->bind_param("s", $enteredUsername);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = 'staff';
                    header("Location: staff/dashboard.php");
                    exit;
                }
            }
            $error = "Invalid staff credentials!";
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IAB STORE | Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #1a237e;
    --primary-light: #303f9f;
    --accent: #00b0ff;
    --accent-dark: #0091ea;
    --light: #f5f5f5;
    --dark: #212121;
    --success: #00c853;
    --danger: #ff1744;
    --warning: #ff9100;
    --gray: #757575;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: url('https://media.istockphoto.com/id/917884972/photo/businessperson-checking-invoice-on-computer.jpg?s=612x612&w=0&k=20&c=poJnPJ0nYZUDRM2-ccfhwFhWaZhfTIt6ISY-W-QgQJM=') no-repeat center center fixed;
    background-size: cover;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding: 20px;
    position: relative;
}

body::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(26, 35, 126, 0.7);
    z-index: 1;
}

.login-container {
    width: 100%;
    max-width: 450px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    position: relative;
    z-index: 2;
    backdrop-filter: blur(5px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.login-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    padding: 30px 20px;
    text-align: center;
    color: white;
    position: relative;
    overflow: hidden;
}

.login-header::before {
    content: '';
    position: absolute;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
    top: -50%;
    left: -50%;
    transform: rotate(30deg);
}

.logo {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
    position: relative;
    z-index: 1;
}

.logo-icon {
    font-size: 2.2rem;
    margin-right: 10px;
    color: var(--accent);
}

.logo-text {
    font-size: 1.8rem;
    font-weight: 700;
}

.login-title {
    font-size: 1.3rem;
    font-weight: 500;
    opacity: 0.9;
    position: relative;
    z-index: 1;
}

.login-form {
    padding: 30px;
}

.form-group {
    margin-bottom: 20px;
    position: relative;
}

.form-control {
    padding: 14px 15px 14px 50px;
    border-radius: 10px;
    border: 1px solid #e0e0e0;
    transition: all 0.3s;
    font-size: 1rem;
    background-color: #fafafa;
}

.form-control:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(0, 176, 255, 0.15);
    background-color: white;
}

.input-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray);
    font-size: 1.1rem;
    transition: color 0.3s;
}

.form-control:focus + .input-icon {
    color: var(--accent);
}

.form-select {
    padding: 14px 15px 14px 50px;
    border-radius: 10px;
    border: 1px solid #e0e0e0;
    transition: all 0.3s;
    font-size: 1rem;
    background-color: #fafafa;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23757575' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 15px center;
    background-size: 16px;
    background-repeat: no-repeat;
}

.form-select:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(0, 176, 255, 0.15);
    background-color: white;
}

.btn-login {
    background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
    border: none;
    color: white;
    padding: 14px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s;
    margin-top: 10px;
    width: 100%;
    position: relative;
    overflow: hidden;
}

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 176, 255, 0.4);
}

.btn-login:active {
    transform: translateY(0);
}

.error {
    background: #ffebee;
    color: var(--danger);
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid var(--danger);
    display: flex;
    align-items: center;
    font-size: 0.9rem;
}

.error-icon {
    margin-right: 10px;
    font-size: 1.1rem;
}

.remember-forgot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
    font-size: 0.9rem;
}

.remember-me {
    display: flex;
    align-items: center;
}

.remember-me input {
    margin-right: 5px;
    accent-color: var(--accent);
}

.forgot-password {
    color: var(--accent);
    text-decoration: none;
    transition: color 0.3s;
}

.forgot-password:hover {
    color: var(--accent-dark);
    text-decoration: underline;
}

.login-footer {
    text-align: center;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
    font-size: 0.85rem;
    color: var(--gray);
}

/* Animation for form elements */
.form-group {
    animation: fadeInUp 0.5s ease-out;
    animation-fill-mode: both;
}

.form-group:nth-child(1) { animation-delay: 0.1s; }
.form-group:nth-child(2) { animation-delay: 0.2s; }
.form-group:nth-child(3) { animation-delay: 0.3s; }
.remember-forgot { animation-delay: 0.4s; }
.btn-login { animation-delay: 0.5s; }

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive adjustments */
@media (max-width: 480px) {
    .login-container {
        max-width: 100%;
    }
    
    .login-form {
        padding: 25px 20px;
    }
    
    .login-header {
        padding: 25px 20px;
    }
}

/* Decorative elements */
.decoration {
    position: absolute;
    border-radius: 50%;
    opacity: 0.1;
}

.decoration-1 {
    width: 100px;
    height: 100px;
    background: var(--accent);
    top: 10%;
    right: 10%;
}

.decoration-2 {
    width: 150px;
    height: 150px;
    background: var(--primary);
    bottom: 5%;
    left: 5%;
}

.decoration-3 {
    width: 70px;
    height: 70px;
    background: var(--success);
    top: 60%;
    right: 20%;
}
</style>
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <div class="decoration decoration-1"></div>
        <div class="decoration decoration-2"></div>
        <div class="decoration decoration-3"></div>
        
        <div class="logo">
            <i class="fas fa-cash-register logo-icon"></i>
            <span class="logo-text">SmartBilling Pro</span>
        </div>
        <h2 class="login-title">Sign In to Your Account</h2>
    </div>
    
    <div class="login-form">
        <?php if($error): ?>
        <div class="error">
            <i class="fas fa-exclamation-circle error-icon"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <input type="text" name="username" class="form-control" placeholder="Username" value="<?= htmlspecialchars($enteredUsername) ?>" required>
                <i class="fas fa-user input-icon"></i>
            </div>
            
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
                <i class="fas fa-lock input-icon"></i>
            </div>
            
            <div class="form-group">
                <select name="role" class="form-select" required>
                    <option value="">Select Role</option>
                    <option value="admin" <?= $role==='admin'?'selected':''; ?>>Administrator</option>
                    <option value="staff" <?= $role==='staff'?'selected':''; ?>>Staff</option>
                </select>
                <i class="fas fa-user-tag input-icon"></i>
            </div>
            
            <div class="remember-forgot">
                <div class="remember-me">
                    <input type="checkbox" id="remember">
                    <label for="remember">Remember me</label>
                </div>
                <a href="#" class="forgot-password">Forgot password?</a>
            </div>
            
            <button type="submit" class="btn btn-login">Login</button>
        </form>
        
        
    </div>
</div>

<script>
// Add interactive effects
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.form-control, .form-select');
    
    inputs.forEach(input => {
        // Add focus effect
        input.addEventListener('focus', function() {
            this.style.backgroundColor = 'white';
        });
        
        // Remove focus effect
        input.addEventListener('blur', function() {
            if (this.value === '') {
                this.style.backgroundColor = '#fafafa';
            }
        });
        
        // Check if input has value on page load
        if (input.value !== '') {
            input.style.backgroundColor = 'white';
        }
    });
    
    // Add ripple effect to login button
    const loginBtn = document.querySelector('.btn-login');
    loginBtn.addEventListener('click', function(e) {
        // Create ripple element
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size/2;
        const y = e.clientY - rect.top - size/2;
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple-effect');
        
        this.appendChild(ripple);
        
        // Remove ripple after animation
        setTimeout(() => {
            ripple.remove();
        }, 600);
    });
});
</script>
</body>
</html>