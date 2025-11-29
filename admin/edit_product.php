<?php
include_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? null;
$product = $conn->query("SELECT * FROM products WHERE product_id=$id")->fetch_assoc();

if(isset($_POST['update'])){
    $name = $_POST['name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category_id = $_POST['category_id'];
    $expiry = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : NULL;

    $sql = "UPDATE products SET name=?, price=?, stock=?, category_id=?, expiry_date=? WHERE product_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdiisi", $name, $price, $stock, $category_id, $expiry, $id);
    
    if($stmt->execute()){
        header("Location: manage_products.php?msg=Product+updated");
        exit();
    }
}

$categories = $conn->query("SELECT * FROM categories");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        .btn { background: #6C63FF; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #5752d4; }
        .header { text-align: center; margin-bottom: 30px; color: #333; }
        .back-link { display: inline-block; margin-top: 15px; color: #6C63FF; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>✏️ Edit Product</h2>
        </div>
        
        <form method="post">
            <div class="form-group">
                <label>Product Name</label>
                <input type="text" name="name" value="<?= $product['name'] ?>" required>
            </div>
            
            <div class="form-group">
                <label>Price (₹)</label>
                <input type="number" name="price" step="0.01" value="<?= $product['price'] ?>" required>
            </div>
            
            <div class="form-group">
                <label>Stock Quantity</label>
                <input type="number" name="stock" value="<?= $product['stock'] ?>" required>
            </div>
            
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" required>
                    <?php while($c = $categories->fetch_assoc()): ?>
                        <option value="<?= $c['category_id'] ?>" <?= $c['category_id'] == $product['category_id'] ? 'selected' : '' ?>>
                            <?= $c['category_name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Expiry Date</label>
                <input type="date" name="expiry_date" value="<?= $product['expiry_date'] ?>">
            </div>
            
            <button type="submit" name="update" class="btn">Update Product</button>
            <a href="manage_products.php" class="back-link">← Back to Products</a>
        </form>
    </div>
</body>
</html>