<?php
session_start();
include '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../admin_login.php");
    exit();
}

// Fetch all categories
$result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Manage Categories</h2>
    <a href="add_category.php" class="btn btn-success mb-3">Add New Category</a>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>GST %</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['category_id'] ?></td>
                <td><?= $row['name'] ?></td>
                <td><?= $row['description'] ?></td>
                <td><?= $row['gst_percentage'] ?></td>
                <td>
                    <a href="edit_category.php?id=<?= $row['category_id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                    <a href="delete_category.php?id=<?= $row['category_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
