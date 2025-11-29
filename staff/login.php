<?php
session_start();
require("../config/database.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Input validation
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        $sql = "SELECT * FROM staff WHERE username=?";
                $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $staff = $result->fetch_assoc();

        if ($staff && password_verify($password, $staff['password'])) {
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['staff_id'] = $staff['id'];
            $_SESSION['staff_name'] = $staff['name'];
            $_SESSION['staff_username'] = $staff['username'];
            $_SESSION['role'] = 'staff';
            $_SESSION['login_time'] = time();
            
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Staff Login</title>
    <style>
        body { font-family: Arial; background: #f8f9fa; }
        .container { width: 350px; margin: 100px auto; padding: 25px; background: #fff; border-radius: 10px; box-shadow: 0 0 10px #ccc; }
        h2 { text-align: center; margin-bottom: 20px; }
        input { width: 100%; padding: 8px; margin: 8px 0; border-radius: 5px; border: 1px solid #ccc; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .error { color: red; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Staff Login</h2>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
