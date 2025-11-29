<?php
session_start();
require 'config.php'; // DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM staff WHERE email=?");
    $stmt->execute([$email]);
    $staff = $stmt->fetch();

    if ($staff && $staff['password'] === $password) { // Later: use password_verify
        $_SESSION['staff_id'] = $staff['staff_id'];
        $_SESSION['staff_name'] = $staff['name'];
        $_SESSION['staff_email'] = $staff['email'];
        header("Location: staff_portal.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<form method="post">
    <input type="email" name="email" placeholder="Email" required/>
    <input type="password" name="password" placeholder="Password" required/>
    <button type="submit">Login</button>
</form>
<?php if(isset($error)) echo "<p>$error</p>"; ?>
