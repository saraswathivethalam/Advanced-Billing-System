<?php
include_once __DIR__ . '/../config/database.php';

// Low stock limit
$low_stock_limit = 10;

// Initialize result variable
$result = null;
$alert_message = '';

try {
    // Fetch products with low stock and category name
    $sql = "
        SELECT p.product_id, p.name AS product_name, p.stock, c.category_name
        FROM products p
        JOIN categories c ON p.category_id = c.category_id
        WHERE p.stock <= ?
        ORDER BY p.stock ASC, c.category_name ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $low_stock_limit);
    $stmt->execute();
    $result = $stmt->get_result();

} catch (Exception $e) {
    $alert_message = "Error retrieving stock data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Low Stock Alerts</title>
    <style>
        :root {
            --primary-color: #4361ee;
            --warning-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4cc9f0;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .header h1 {
            color: var(--dark-color);
            margin-bottom: 10px;
            font-size: 2.5rem;
        }
        
        .header p {
            color: #6c757d;
            font-size: 1.1rem;
        }
        
        .alert-badge {
            display: inline-block;
            background-color: var(--warning-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-left: 10px;
            vertical-align: middle;
        }
        
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .card-header {
            background: var(--primary-color);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-body {
            padding: 0;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            background-color: #f1f3f9;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--dark-color);
            border-bottom: 2px solid #e0e0e0;
        }
        
        .table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .low-stock {
            transition: all 0.3s ease;
        }
        
        .low-stock:hover {
            background-color: #fff5f5;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .stock-indicator {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .stock-critical {
            background-color: #ffe6e6;
            color: #d00000;
        }
        
        .stock-low {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .no-alerts {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .no-alerts i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--success-color);
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
            text-align: center;
        }
        
        .summary-card h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .summary-card p {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .summary-card .number {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
        }
        
        .critical {
            color: var(--warning-color);
        }
        
        .low {
            color: #ff9e00;
        }
        
        .error-alert {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        @media (max-width: 768px) {
            .table {
                display: block;
                overflow-x: auto;
            }
            
            .summary-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>IBA STORE<span class="alert-badge">Low Stock Alerts</span></h1>
            <p>Stock Alerts</p>
        </div>
        
        <?php if(!empty($alert_message)): ?>
            <div class="error-alert">
                <strong>Database Error:</strong> <?= $alert_message ?>
            </div>
        <?php endif; ?>
        
        <?php if($result && $result->num_rows > 0): ?>
            <div class="summary-cards">
                <div class="summary-card">
                    <h3>Total Low Stock Items</h3>
                    <div class="number"><?= $result->num_rows ?></div>
                    <p>Products below threshold</p>
                </div>
                
                <?php
                // Calculate critical items (stock <= 3)
                $critical_count = 0;
                $result->data_seek(0); // Reset pointer to beginning
                while($row = $result->fetch_assoc()) {
                    if($row['stock'] <= 3) {
                        $critical_count++;
                    }
                }
                $result->data_seek(0); // Reset pointer again for main display
                ?>
                
                <div class="summary-card">
                    <h3>Critical Items</h3>
                    <div class="number critical"><?= $critical_count ?></div>
                    <p>Stock level 3 or less</p>
                </div>
                
                <div class="summary-card">
                    <h3>Low Stock Threshold</h3>
                    <div class="number low"><?= $low_stock_limit ?></div>
                    <p>Alert trigger level</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Low Stock Products</h2>
                    <span>Sorted by stock level</span>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Stock Level</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): 
                                $stock_class = $row['stock'] <= 3 ? 'stock-critical' : 'stock-low';
                                $status_text = $row['stock'] <= 3 ? 'Critical' : 'Low';
                            ?>
                            <tr class="low-stock">
                                <td><?= $row['product_id'] ?></td>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><?= htmlspecialchars($row['category_name']) ?></td>
                                <td><?= $row['stock'] ?></td>
                                <td><span class="stock-indicator <?= $stock_class ?>"><?= $status_text ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif($result && $result->num_rows == 0): ?>
            <div class="card">
                <div class="no-alerts">
                    <i>✓</i>
                    <h2>All products have sufficient stock</h2>
                    <p>No low stock alerts at this time</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="no-alerts">
                    <i>⚠</i>
                    <h2>Unable to load stock data</h2>
                    <p>Please check your database connection</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>