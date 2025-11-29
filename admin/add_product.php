<?php
include_once __DIR__ . '/../config/database.php';

if(isset($_POST['submit'])){
    $name  = $_POST['name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category_id = $_POST['category_id'];
    $expiry = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : NULL;

    $cat = $conn->query("SELECT gst_percentage FROM categories WHERE category_id=$category_id");
    $gst = $cat->fetch_assoc()['gst_percentage'];

    $sql = "INSERT INTO products (name, price, stock, category_id, gst, expiry_date) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdiids", $name, $price, $stock, $category_id, $gst, $expiry);

    if($stmt->execute()){
        header("Location: manage_products.php?msg=Product+added+successfully");
        exit();
    } else {
        $error = "Error: " . $stmt->error;
    }
}

$categories = $conn->query("SELECT * FROM categories");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Product | Inventory System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #2563eb;
    --primary-light: #3b82f6;
    --primary-dark: #1d4ed8;
    --secondary: #64748b;
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --light: #f8fafc;
    --dark: #1e293b;
    --border: #e2e8f0;
    --radius: 8px;
    --shadow: 0 1px 3px rgba(0,0,0,0.1);
    --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
}

body {
    background-color: #f1f5f9;
    color: var(--dark);
    line-height: 1.6;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.container {
    width: 100%;
    max-width: 520px;
}

.card {
    background: white;
    border-radius: 12px;
    box-shadow: var(--shadow-lg);
    overflow: hidden;
}

.card-header {
    background: white;
    padding: 24px 32px;
    border-bottom: 1px solid var(--border);
    text-align: center;
}

.card-header h1 {
    color: var(--dark);
    font-size: 24px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.card-header h1 i {
    color: var(--primary);
    background: #eff6ff;
    padding: 10px;
    border-radius: 10px;
}

.card-body {
    padding: 32px;
}

.form-group {
    margin-bottom: 24px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--dark);
    font-size: 14px;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-size: 15px;
    transition: all 0.2s;
    background: white;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-control[readonly] {
    background-color: #f8fafc;
    color: #64748b;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.badge {
    background: #94a3b8;
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
    margin-left: 6px;
}

.btn {
    width: 100%;
    padding: 14px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: var(--radius);
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
}

.btn:active {
    transform: translateY(0);
}

.back-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 20px;
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    transition: color 0.2s;
}

.back-link:hover {
    color: var(--primary-dark);
}

.alert {
    padding: 12px 16px;
    border-radius: var(--radius);
    margin-bottom: 20px;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.alert-error {
    background: #fef2f2;
    color: #dc2626;
    border-left: 4px solid var(--danger);
}

.alert-success {
    background: #f0fdf4;
    color: #166534;
    border-left: 4px solid var(--success);
}

.optional-note {
    font-size: 12px;
    color: #64748b;
    margin-top: 4px;
    font-style: italic;
}

@media (max-width: 640px) {
    .container {
        padding: 0;
    }
    
    .card {
        border-radius: 0;
        box-shadow: none;
    }
    
    .card-body {
        padding: 24px 20px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
}

/* Loading animation */
.btn-loading {
    position: relative;
    color: transparent;
}

.btn-loading::after {
    content: "";
    position: absolute;
    width: 20px;
    height: 20px;
    border: 2px solid transparent;
    border-top: 2px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1><i class="fas fa-cube"></i> Add New Product</h1>
        </div>
        
        <div class="card-body">
            <?php if(isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <form method="post" id="productForm">
                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter product name" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price (₹)</label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label for="stock">Stock Quantity</label>
                        <input type="number" class="form-control" id="stock" name="stock" min="0" placeholder="0" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select class="form-control" id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php while($c = $categories->fetch_assoc()): ?>
                                <option value="<?= $c['category_id'] ?>" data-gst="<?= $c['gst_percentage'] ?>">
                                    <?= htmlspecialchars($c['category_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="gst">GST Percentage</label>
                        <input type="text" class="form-control" id="gst" readonly placeholder="Select category first">
                    </div>
                </div>

                <div class="form-group">
                    <label for="expiry_date">
                        Expiry Date 
                        <span class="badge">OPTIONAL</span>
                    </label>
                    <input type="date" class="form-control" id="expiry_date" name="expiry_date" min="<?= date('Y-m-d') ?>">
                    <div class="optional-note">Leave empty for non-perishable items</div>
                </div>

                <button type="submit" name="submit" class="btn" id="submitBtn">
                    <i class="fas fa-plus"></i> Add Product
                </button>
            </form>

            <a href="manage_products.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Product List
            </a>
        </div>
    </div>
</div>

<script>
// GST auto-update
document.getElementById('category_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const gstValue = selectedOption?.dataset.gst || '';
    document.getElementById('gst').value = gstValue ? `${gstValue}%` : '';
});

// Set minimum date to today
document.getElementById('expiry_date').min = new Date().toISOString().split('T')[0];

// Input validation
document.getElementById('price').addEventListener('input', function() {
    if (this.value < 0) this.value = 0;
});

document.getElementById('stock').addEventListener('input', function() {
    if (this.value < 0) this.value = 0;
    this.value = Math.floor(this.value);
});

// Form submission loading state
document.getElementById('productForm').addEventListener('submit', function() {
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.classList.add('btn-loading');
    submitBtn.disabled = true;
});
</script>

</body>
</html>