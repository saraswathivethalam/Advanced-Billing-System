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
<title>SmartBilling Pro | Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f8fafc;display:flex;align-items:center;justify-content:center;height:100vh;}
.login-box{background:white;padding:40px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.1);width:100%;max-width:400px;text-align:center;}
h2{color:#1e1b4b;margin-bottom:20px;}
.error{background:#fee2e2;color:#b91c1c;padding:10px;border-radius:8px;margin-bottom:12px;}
</style>
</head>
<body>
<div class="login-box">
    <h2><i class="fas fa-cash-register"></i> SmartBilling Pro</h2>
    <?php if($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
        <input type="text" name="username" class="form-control mb-3" placeholder="Username" value="<?= htmlspecialchars($enteredUsername) ?>" required>
        <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
        <select name="role" class="form-select mb-3" required>
            <option value="">Select Role</option>
            <option value="admin" <?= $role==='admin'?'selected':''; ?>>Administrator</option>
            <option value="staff" <?= $role==='staff'?'selected':''; ?>>Staff</option>
        </select>
        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
</div>
</body>
</html>
