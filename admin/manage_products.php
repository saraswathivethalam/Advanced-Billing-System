<?php
include_once __DIR__ . '/../config/database.php';

// Get selected category safely
$selected_category = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// Fetch all categories for filter
$categories_result = $conn->query("SELECT * FROM categories");

// Fetch products with category name and GST
$sql = "
SELECT p.*, c.category_name, c.gst_percentage
FROM products p
LEFT JOIN categories c ON p.category_id = c.category_id
";

if($selected_category > 0){
    $sql .= " WHERE p.category_id = $selected_category";
}

$sql .= " ORDER BY p.name ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Products | Inventory System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #6C63FF;
    --primary-light: #8A85FF;
    --primary-dark: #554FD8;
    --secondary: #FF6584;
    --success: #36D1DC;
    --info: #4CC9F0;
    --warning: #FF9A3D;
    --danger: #FF3860;
    --light: #F8F9FC;
    --dark: #2D3748;
    --gray: #A0AEC0;
    --light-gray: #EDF2F7;
    --border-radius: 12px;
    --border-radius-sm: 8px;
    --box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --box-shadow-light: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --gradient-primary: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    --gradient-secondary: linear-gradient(135deg, var(--secondary) 0%, #FF8EA5 100%);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background: linear-gradient(135deg, #f5f7ff 0%, #f0f4ff 100%);
    color: var(--dark);
    line-height: 1.6;
    min-height: 100vh;
    padding: 20px;
}

.container {
    max-width: 1400px;
    margin: 0 auto;
}

/* Header Styles */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 20px 0;
    position: relative;
}

.header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: linear-gradient(to right, transparent, var(--primary-light), transparent);
}

.header h1 {
    color: var(--dark);
    font-size: 32px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 12px;
}

.header h1 i {
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Alert Styles */
.alert {
    padding: 16px 20px;
    border-radius: var(--border-radius-sm);
    margin-bottom: 25px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: var(--box-shadow-light);
    border-left: 4px solid;
    animation: slideIn 0.5s ease-out;
}

.alert-success {
    background-color: rgba(54, 209, 220, 0.1);
    color: #0E7C86;
    border-left-color: var(--success);
}

/* Filter Section */
.filter-section {
    background: white;
    padding: 25px;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow-light);
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    transition: var(--transition);
}

.filter-section:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 15px;
}

.filter-label {
    font-weight: 600;
    color: var(--dark);
    font-size: 16px;
}

.filter-select {
    padding: 12px 20px;
    border: 1px solid var(--light-gray);
    border-radius: var(--border-radius-sm);
    background-color: white;
    font-size: 15px;
    transition: var(--transition);
    min-width: 220px;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236C63FF' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 15px center;
    background-size: 16px;
}

.filter-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.2);
}

.product-count {
    background: var(--gradient-primary);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 14px;
    box-shadow: var(--box-shadow-light);
}

/* Button Styles */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 24px;
    border-radius: var(--border-radius-sm);
    text-decoration: none;
    font-weight: 600;
    transition: var(--transition);
    border: none;
    cursor: pointer;
    font-size: 15px;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s;
}

.btn:hover::before {
    left: 100%;
}

.btn-primary {
    background: var(--gradient-primary);
    color: white;
    box-shadow: 0 4px 6px rgba(108, 99, 255, 0.25);
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 15px rgba(108, 99, 255, 0.3);
}

.btn-edit {
    background: var(--warning);
    color: white;
    padding: 8px 16px;
    font-size: 14px;
}

.btn-edit:hover {
    background: #FF8A1D;
    transform: translateY(-2px);
}

.btn-danger {
    background: var(--danger);
    color: white;
    padding: 8px 16px;
    font-size: 14px;
}

.btn-danger:hover {
    background: #FF1E4A;
    transform: translateY(-2px);
}

/* Table Styles */
.products-card {
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--box-shadow);
    margin-bottom: 30px;
    animation: fadeIn 0.8s ease-out;
}

.table-header {
    background: var(--gradient-primary);
    padding: 20px 25px;
    color: white;
}

.table-header h3 {
    font-size: 20px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.table-container {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background-color: #F8FAFC;
    color: var(--dark);
    padding: 18px 15px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--light-gray);
}

td {
    padding: 18px 15px;
    border-bottom: 1px solid var(--light-gray);
    color: var(--dark);
    transition: var(--transition);
}

tr:last-child td {
    border-bottom: none;
}

tr:hover td {
    background-color: rgba(108, 99, 255, 0.03);
}

/* Stock and Status Styles */
.low-stock {
    background-color: rgba(255, 56, 96, 0.05) !important;
}

.low-stock:hover td {
    background-color: rgba(255, 56, 96, 0.08) !important;
}

.stock-indicator {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 0 2px rgba(255,255,255,0.8);
}

.indicator.low {
    background-color: var(--danger);
    animation: pulse 2s infinite;
}

.indicator.medium {
    background-color: var(--warning);
}

.indicator.high {
    background-color: #10B981;
}

.badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-fresh {
    background-color: rgba(54, 209, 220, 0.15);
    color: #0E7C86;
}

.badge-expiry {
    background-color: rgba(255, 56, 96, 0.15);
    color: var(--danger);
}

/* Actions */
.actions {
    display: flex;
    gap: 8px;
}

/* No Products */
.no-products {
    text-align: center;
    padding: 60px 20px;
    color: var(--gray);
}

.no-products i {
    font-size: 48px;
    margin-bottom: 15px;
    color: var(--light-gray);
}

.no-products h3 {
    font-size: 20px;
    margin-bottom: 10px;
    color: var(--dark);
}

.no-products p {
    margin-bottom: 20px;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideIn {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

/* Responsive Design */
@media (max-width: 1024px) {
    .filter-section {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .filter-group {
        width: 100%;
        justify-content: space-between;
    }
}

@media (max-width: 768px) {
    .header {
        flex-direction: column;
        gap: 20px;
        align-items: flex-start;
    }
    
    .header h1 {
        font-size: 28px;
    }
    
    .filter-select {
        min-width: 180px;
    }
    
    th, td {
        padding: 12px 8px;
        font-size: 14px;
    }
    
    .actions {
        flex-direction: column;
        gap: 5px;
    }
    
    .btn {
        padding: 10px 16px;
        font-size: 14px;
    }
    
    .table-header {
        padding: 15px 20px;
    }
}

/* Loading Animation */
.loading {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255,255,255,.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-boxes"></i> Product Management</h1>
        <a href="add_product.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Product
        </a>
    </div>
    
    <?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($_GET['msg']) ?>
    </div>
    <?php endif; ?>

    <form method="get" action="">
        <div class="filter-section">
            <div class="filter-group">
                <span class="filter-label"><i class="fas fa-filter"></i> Filter by Category:</span>
                <select class="filter-select" name="category_id" onchange="this.form.submit()">
                    <option value="0">All Categories</option>
                    <?php 
                    $categories_result->data_seek(0);
                    while($c = $categories_result->fetch_assoc()){
                        $cat_id = (int)$c['category_id'];
                        $selected = ($selected_category === $cat_id) ? 'selected' : '';
                        echo "<option value='$cat_id' $selected>".htmlspecialchars($c['category_name'])."</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="product-count">
                <i class="fas fa-cube"></i> <?= $result->num_rows ?> Product(s) Found
            </div>
        </div>
    </form>

    <div class="products-card">
        <div class="table-header">
            <h3><i class="fas fa-list"></i> Product List</h3>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price (₹)</th>
                        <th>GST (%)</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <?php if($selected_category != 9): /* Stationery category ID 9 */ ?>
                        <th>Status</th>
                        <?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): 
                        // Determine unit for stock
                        $kg_items = ['tomato','potato','onion','carrot','capsicum','spinach','beetroot'];
                        $pcs_items = ['cabbage','cucumber'];
                        $name_lower = strtolower($row['name']);
                        $unit = in_array($name_lower, $pcs_items) ? 'pcs' : (in_array($name_lower, $kg_items) ? 'kg' : '');

                        // Expiry / Fresh display
                        if ($row['category_id'] == 9) { // Stationery
                            $expiry_display = null;
                        } else {
                            $expiry_display = ($row['expiry_date'] && $row['expiry_date'] != '0000-00-00') ? 
                                            date('M j, Y', strtotime($row['expiry_date'])) : 'Fresh';
                        }
                        
                        // Stock indicator
                        $stock_indicator = '';
                        if ($row['stock'] <= 10) {
                            $stock_indicator = 'low';
                        } elseif ($row['stock'] <= 30) {
                            $stock_indicator = 'medium';
                        } else {
                            $stock_indicator = 'high';
                        }
                    ?>
                    <tr class="<?= ($row['stock']<=10)?'low-stock':'' ?>">
                        <td><strong>#<?= $row['product_id'] ?></strong></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><strong>₹<?= number_format($row['price'],2) ?></strong></td>
                        <td><?= number_format($row['gst_percentage'],2) ?>%</td>
                        <td>
                            <span style="background: #F0F4FF; color: var(--primary); padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                <?= htmlspecialchars($row['category_name']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="stock-indicator">
                                <span class="indicator <?= $stock_indicator ?>"></span>
                                <?= $row['stock'] . ($unit ? " $unit" : '') ?>
                            </div>
                        </td>
                        <?php if($expiry_display !== null): ?>
                        <td>
                            <span class="badge <?= $expiry_display == 'Fresh' ? 'badge-fresh' : 'badge-expiry' ?>">
                                <i class="fas <?= $expiry_display == 'Fresh' ? 'fa-leaf' : 'fa-calendar-day' ?>"></i>
                                <?= $expiry_display ?>
                            </span>
                        </td>
                        <?php endif; ?>
                        <td>
                            <div class="actions">
                                <a href="edit_product.php?id=<?= $row['product_id'] ?>" class="btn btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                             <a href="delete_products.php?id=<?= $row['product_id'] ?>" 
   class="btn btn-danger" 
   onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars($row['name']) ?>?')">
   <i class="fas fa-trash"></i> Delete
</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="<?= ($selected_category == 9) ? 7 : 8 ?>">
                            <div class="no-products">
                                <i class="fas fa-box-open"></i>
                                <h3>No Products Found</h3>
                                <p>There are no products in this category.</p>
                                <a href="add_product.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add Your First Product
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Enhanced interactivity
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        if (!row.querySelector('.no-products')) {
            row.addEventListener('click', function(e) {
                // Don't trigger if clicking on action buttons
                if (!e.target.closest('.actions')) {
                    this.style.transform = 'scale(1.01)';
                    this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
                    setTimeout(() => {
                        this.style.transform = '';
                        this.style.boxShadow = '';
                    }, 300);
                }
            });
        }
    });
    
    // Add loading state to buttons on click
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (this.getAttribute('href') && this.getAttribute('href').includes('delete_productsss')) {
                // For delete buttons, we'll keep the default confirmation
                return;
            }
            
            if (!this.querySelector('.loading')) {
                const originalHTML = this.innerHTML;
                this.innerHTML = '<span class="loading"></span> Loading...';
                this.style.pointerEvents = 'none';
                
                // Reset after 2 seconds (in case of error)
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                    this.style.pointerEvents = 'auto';
                }, 2000);
            }
        });
    });
});
</script>
</body>
</html>