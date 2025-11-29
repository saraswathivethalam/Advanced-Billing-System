<?php
// Use your existing database configuration
require("../config/database.php");

$today = date('Y-m-d');

// Check if connection is established
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? "Connection not established"));
}

// First, let's check what columns exist in the products table
$check_columns_sql = "SHOW COLUMNS FROM products";
$columns_result = $conn->query($check_columns_sql);
$existing_columns = [];
while ($column = $columns_result->fetch_assoc()) {
    $existing_columns[] = $column['Field'];
}

// Determine the correct column names
$stock_column = 'stock';
$name_column = 'name';
$category_id_column = 'category_id';
$price_column = 'price';
$id_column = 'product_id';

// Check if categories table exists and get category names
$categories = [];
$category_table_exists = false;

try {
    $check_categories = $conn->query("SHOW TABLES LIKE 'categories'");
    if ($check_categories->num_rows > 0) {
        $category_table_exists = true;
        $categories_result = $conn->query("SELECT * FROM categories");
        while ($cat = $categories_result->fetch_assoc()) {
            $categories[$cat['category_id']] = $cat['category_name'] ?? $cat['name'] ?? 'Unknown';
        }
    }
} catch (Exception $e) {
    $category_table_exists = false;
}

// Get statistics
try {
    $stats_sql = "SELECT 
                COUNT(*) as total_products,
                SUM(stock) as total_stock,
                AVG(stock) as avg_stock,
                SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN stock <= 2 THEN 1 ELSE 0 END) as critical_stock,
                SUM(CASE WHEN stock <= 10 AND stock > 2 THEN 1 ELSE 0 END) as low_stock,
                SUM(CASE WHEN stock > 10 THEN 1 ELSE 0 END) as normal_stock
              FROM products";
    $stats_result = $conn->query($stats_sql);
    $stats = $stats_result->fetch_assoc();
} catch (Exception $e) {
    $stats = [];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Stock Report</title>
    <style>
        :root {
            --primary: #6C63FF;
            --secondary: #FF6584;
            --accent: #00D2A8;
            --success: #00D2A8;
            --warning: #FF9F43;
            --danger: #FF6B6B;
            --dark: #2d3436;
            --light: #f8f9fa;
            --gradient-primary: linear-gradient(135deg, #6C63FF 0%, #8A2BE2 100%);
            --gradient-secondary: linear-gradient(135deg, #FF6584 0%, #FF6B6B 100%);
            --gradient-accent: linear-gradient(135deg, #00D2A8 0%, #4ECDC4 100%);
            --gradient-warning: linear-gradient(135deg, #FF9F43 0%, #FFC107 100%);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            text-align: center;
            padding: 40px 30px;
            background: var(--gradient-primary);
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            animation: float 20s linear infinite;
        }
        
        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-20px, -20px) rotate(360deg); }
        }
        
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }
        
        .header h3 {
            margin: 0;
            font-weight: 400;
            opacity: 0.9;
            position: relative;
            z-index: 1;
            font-size: 1.2rem;
        }
        
        .controls {
            text-align: center;
            padding: 25px;
            background: white;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .print-btn {
            background: var(--gradient-primary);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            margin: 0 10px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(108, 99, 255, 0.3);
        }
        
        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 99, 255, 0.4);
        }
        
        .print-btn.refresh {
            background: var(--gradient-accent);
            box-shadow: 0 4px 15px rgba(0, 210, 168, 0.3);
        }
        
        .print-btn.refresh:hover {
            box-shadow: 0 6px 20px rgba(0, 210, 168, 0.4);
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
            background: white;
        }
        
        .stat-card {
            padding: 25px;
            background: white;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 5px solid var(--primary);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.critical {
            border-left-color: var(--danger);
            background: linear-gradient(135deg, #fff, #ffebee);
        }
        
        .stat-card.warning {
            border-left-color: var(--warning);
            background: linear-gradient(135deg, #fff, #fff3cd);
        }
        
        .stat-card.success {
            border-left-color: var(--success);
            background: linear-gradient(135deg, #fff, #d4edda);
        }
        
        .stat-card.primary {
            border-left-color: var(--primary);
            background: linear-gradient(135deg, #fff, #e3f2fd);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-card.critical .stat-number {
            background: linear-gradient(135deg, #FF6B6B, #FF8E53);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-card.warning .stat-number {
            background: linear-gradient(135deg, #FF9F43, #FFC107);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-card.success .stat-number {
            background: linear-gradient(135deg, #00D2A8, #4ECDC4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }
        
        /* Section Styles */
        .section {
            margin: 0;
            padding: 0;
            background: white;
        }
        
        .section-title {
            background: var(--gradient-primary);
            color: white;
            padding: 20px 30px;
            margin: 0;
            font-size: 1.4rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-content {
            padding: 30px;
        }
        
        /* Modern table style */
        .modern-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .modern-table th {
            background: var(--gradient-primary);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .modern-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s ease;
        }
        
        .modern-table tr:hover td {
            background: #f8f9fa;
        }
        
        /* Status colors */
        .critical-row {
            background: linear-gradient(90deg, #ffebee, #fff);
            border-left: 4px solid #FF6B6B;
        }
        
        .low-stock-row {
            background: linear-gradient(90deg, #fff3cd, #fff);
            border-left: 4px solid #FF9F43;
        }
        
        .very-low-row {
            background: linear-gradient(90deg, #f8d7da, #fff);
            border-left: 4px solid #dc3545;
        }
        
        .summary-box {
            background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
            padding: 20px;
            border-radius: 15px;
            margin: 25px 0;
            border-left: 5px solid #2196f3;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .summary-box h4 {
            margin: 0 0 15px 0;
            color: var(--primary);
            font-size: 1.2rem;
        }
        
        .success-message {
            background: linear-gradient(135deg, #d4edda, #c8e6c9);
            color: #155724;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin: 20px 0;
            border-left: 5px solid #28a745;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        @media print {
            .print-btn {
                display: none;
            }
            body {
                margin: 10px;
                font-size: 12px;
                background: white;
            }
            .container {
                box-shadow: none;
                border-radius: 0;
            }
            .modern-table {
                font-size: 11px;
                box-shadow: none;
            }
            .modern-table th,
            .modern-table td {
                padding: 8px;
            }
            .section-title {
                background: #6C63FF !important;
                -webkit-print-color-adjust: exact;
                color: white !important;
            }
            .stats-grid {
                padding: 15px;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .header h3 {
                font-size: 1rem;
            }
            
            .modern-table {
                font-size: 14px;
            }
            
            .controls {
                padding: 15px;
            }
            
            .print-btn {
                display: block;
                width: 200px;
                margin: 10px auto;
            }
            
            .header {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Daily Stock Analysis Report</h1>
            <h3>Date: <?php echo $today; ?></h3>
        </div>

        <div class="controls">
            <button class="print-btn" onclick="window.print()">🖨️ Print Report</button>
            <button class="print-btn refresh" onclick="location.reload()">🔄 Refresh Data</button>
        </div>

        <!-- Statistics Section -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div>Total Products</div>
                <div class="stat-number"><?php echo $stats['total_products'] ?? 0; ?></div>
                <div class="stat-label">Items in inventory</div>
            </div>
            
            <div class="stat-card success">
                <div>Normal Stock</div>
                <div class="stat-number"><?php echo $stats['normal_stock'] ?? 0; ?></div>
                <div class="stat-label">Stock > 10 units</div>
            </div>
            
            <div class="stat-card warning">
                <div>Low Stock</div>
                <div class="stat-number"><?php echo $stats['low_stock'] ?? 0; ?></div>
                <div class="stat-label">3-10 units remaining</div>
            </div>
            
            <div class="stat-card critical">
                <div>Critical Stock</div>
                <div class="stat-number"><?php echo $stats['critical_stock'] ?? 0; ?></div>
                <div class="stat-label">≤ 2 units remaining</div>
            </div>
            
            <div class="stat-card">
                <div>Out of Stock</div>
                <div class="stat-number"><?php echo $stats['out_of_stock'] ?? 0; ?></div>
                <div class="stat-label">0 units available</div>
            </div>
            
            <div class="stat-card">
                <div>Average Stock</div>
                <div class="stat-number"><?php echo round($stats['avg_stock'] ?? 0, 1); ?></div>
                <div class="stat-label">Units per product</div>
            </div>
        </div>

        <?php
        // Section 1: Low Stock Products (stock <= 10)
        try {
            if ($category_table_exists) {
                $low_stock_sql = "SELECT p.*, c.category_name 
                                FROM products p 
                                LEFT JOIN categories c ON p.category_id = c.category_id 
                                WHERE p.stock <= 10 AND p.stock > 0 
                                ORDER BY p.stock ASC, p.name ASC";
            } else {
                $low_stock_sql = "SELECT * FROM products 
                                WHERE stock <= 10 AND stock > 0 
                                ORDER BY stock ASC, name ASC";
            }
            $low_stock_result = $conn->query($low_stock_sql);
        } catch (Exception $e) {
            echo "<div style='color: red; padding: 10px; background: #ffebee; border-radius: 5px;'>Error fetching low stock products: " . $e->getMessage() . "</div>";
            $low_stock_result = false;
        }
        ?>

        <div class="section">
            <h2 class="section-title">⚠️ Low Stock Alert (Stock ≤ 10) - <?php echo $low_stock_result ? $low_stock_result->num_rows : 0; ?> Items</h2>
            
            <div class="section-content">
                <?php if ($low_stock_result && $low_stock_result->num_rows > 0) { ?>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Stock</th>
                                <th>Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $low_stock_result->fetch_assoc()) { 
                                $current_stock = $row['stock'] ?? 0;
                                
                                // Determine row class and status
                                if ($current_stock <= 2) {
                                    $row_class = 'critical-row';
                                    $status = 'CRITICAL ⚠️';
                                    $status_icon = '🔴';
                                } elseif ($current_stock <= 3) {
                                    $row_class = 'critical-row';
                                    $status = 'CRITICAL';
                                    $status_icon = '🔴';
                                } elseif ($current_stock <= 5) {
                                    $row_class = 'very-low-row';
                                    $status = 'Very Low';
                                    $status_icon = '🟡';
                                } else {
                                    $row_class = 'low-stock-row';
                                    $status = 'Low Stock';
                                    $status_icon = '🟠';
                                }
                                
                                // Get category name
                                $category_name = 'N/A';
                                if ($category_table_exists && isset($row['category_name'])) {
                                    $category_name = $row['category_name'];
                                } elseif (isset($row['category_id']) && isset($categories[$row['category_id']])) {
                                    $category_name = $categories[$row['category_id']];
                                }
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td><strong>#<?php echo $row['product_id'] ?? 'N/A'; ?></strong></td>
                                <td><?php echo htmlspecialchars($row['name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($category_name); ?></td>
                                <td><strong><?php echo $current_stock; ?></strong></td>
                                <td>₹<?php echo number_format($row['price'] ?? 0, 2); ?></td>
                                <td><strong><?php echo $status_icon . ' ' . $status; ?></strong></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    
                    <!-- Quick Summary -->
                    <div class="summary-box">
                        <h4>📈 Quick Summary:</h4>
                        <p><strong>Critical Items (≤3 stock):</strong> <?php echo $stats['critical_stock'] ?? 0; ?> products need <strong>immediate attention</strong></p>
                        <p><strong>Low Stock Items (4-10):</strong> <?php echo $stats['low_stock'] ?? 0; ?> products need restocking soon</p>
                        <p><strong>Priority:</strong> Focus on Critical items first, then Very Low stock items</p>
                    </div>
                    
                <?php } else { ?>
                    <div class="success-message">
                        ✅ No low stock products. All items are sufficiently stocked.
                    </div>
                <?php } ?>
            </div>
        </div>

        <?php
        // Section 2: Out of Stock Products (stock = 0)
        try {
            if ($category_table_exists) {
                $no_stock_sql = "SELECT p.*, c.category_name 
                               FROM products p 
                               LEFT JOIN categories c ON p.category_id = c.category_id 
                               WHERE p.stock = 0 
                               ORDER BY p.name";
            } else {
                $no_stock_sql = "SELECT * FROM products WHERE stock = 0 ORDER BY name";
            }
            $no_stock_result = $conn->query($no_stock_sql);
        } catch (Exception $e) {
            $no_stock_result = false;
        }
        ?>

        <div class="section">
            <h2 class="section-title">🚫 Out of Stock Products</h2>
            <div class="section-content">
                <?php if ($no_stock_result && $no_stock_result->num_rows > 0) { ?>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $no_stock_result->fetch_assoc()) { 
                                $category_name = 'N/A';
                                if ($category_table_exists && isset($row['category_name'])) {
                                    $category_name = $row['category_name'];
                                } elseif (isset($row['category_id']) && isset($categories[$row['category_id']])) {
                                    $category_name = $categories[$row['category_id']];
                                }
                            ?>
                            <tr class="critical-row">
                                <td><strong>#<?php echo $row['product_id'] ?? 'N/A'; ?></strong></td>
                                <td><?php echo htmlspecialchars($row['name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($category_name); ?></td>
                                <td>₹<?php echo number_format($row['price'] ?? 0, 2); ?></td>
                                <td><strong>🔴 OUT OF STOCK</strong></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <div class="success-message">
                        ✅ Great! No products are out of stock.
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

</body>
</html>
<?php 
// Close connection
$conn->close();
?>