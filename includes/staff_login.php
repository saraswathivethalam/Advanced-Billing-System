<?php
session_start();
include '../config.php'; // DB connection

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $mysqli->prepare("SELECT * FROM users WHERE username=? AND role='staff'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res->num_rows == 1){
        $user = $res->fetch_assoc();

        if(!empty($user['password']) && password_verify($password, $user['password'])){
            // Normal hashed password login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: staff/dashboard.php");
            exit();
        } elseif (empty($user['password']) && $password === $user['username'].'123') {
            // Default username+123 login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: staff/dashboard.php");
            exit();
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "Invalid username";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Login</title>
</head>
<body>
    <h2>Staff Login</h2>
    <?php if($error) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="post">
        <label>Username:</label><br>
        <input type="text" name="username" required><br><br>
        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>
        <button type="submit">Login</button>
    </form>
</body>
</html>
