<?php
include_once __DIR__ . '/../config/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_name = trim($_POST['staff_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    // Simple validation
    if (empty($staff_name) || empty($email) || empty($password) || empty($role)) {
        $message = "All fields are required.";
    } else {
        // Hash password
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);

        // Insert into database
       $stmt = $conn->prepare("INSERT INTO staff (staff_name, email, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $staff_name, $email, $password_hashed, $role);


        if ($stmt->execute()) {
            header("Location: staff_management.php");
            exit();
        } else {
            $message = "Error: " . $stmt->error;
        }
    }
}
?>

<h2 style="text-align:center;">Add New Staff</h2>

<?php if ($message): ?>
    <p style="color:red; text-align:center;"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="post" style="max-width:400px; margin:0 auto;">
    <label>Staff Name:</label><br>
    <input type="text" name="staff_name" required style="width:100%; padding:8px; margin:5px 0;"><br>

    <label>Email:</label><br>
    <input type="email" name="email" required style="width:100%; padding:8px; margin:5px 0;"><br>

    <label>Password:</label><br>
    <input type="password" name="password" required style="width:100%; padding:8px; margin:5px 0;"><br>

    <label>Role:</label><br>
    <select name="role" required style="width:100%; padding:8px; margin:5px 0;">
        <option value="staff">Staff</option>
        <option value="admin">Admin</option>
    </select><br><br>

    <button type="submit" style="padding:10px 15px; background:#2c7be5; color:#fff; border:none; border-radius:5px;">Add Staff</button>
</form>
