<?php
session_start();
require("../config/database.php");

// Staff-only access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit;
}

// Staff info
$staff_id   = (int)($_SESSION['staff_id'] ?? 0);
$staff_name = trim($_SESSION['staff_name'] ?? '');

// Fetch categories with GST percentages
$categories = [];
$catQuery = $conn->query("SELECT category_id, name, gst_percentage FROM categories");
while($cat = $catQuery->fetch_assoc()) {
    $categories[] = $cat;
}

// Fetch products for autocomplete
$products = [];
$prodQuery = $conn->query("SELECT product_id, name, price, category_id FROM products");
while($prod = $prodQuery->fetch_assoc()) {
    $products[] = $prod;
}

// Handle new order submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
    $customer_name  = trim($_POST['customer_name']);
    $customer_email = trim($_POST['customer_email']);
    $delivery_date  = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null;
    $order_date     = date("Y-m-d H:i:s");
    $payment_method = $_POST['payment_method'] ?? 'cash';

    // Get product data from form
    $category_ids    = $_POST['category_id'] ?? [];
    $product_names   = $_POST['product_name'] ?? [];
    $units           = $_POST['unit'] ?? [];
    $quantities      = $_POST['quantity'] ?? [];
    $prices          = $_POST['price'] ?? [];
    $gst_percentages = $_POST['gst_percentage'] ?? [];

    if ($staff_id && $customer_name && $customer_email && !empty($product_names)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // 1️⃣ Calculate total amount and insert order
            $total_amount = 0;
            foreach($quantities as $i => $qty){
                $price = floatval($prices[$i] ?? 0);
                $gst   = floatval($gst_percentages[$i] ?? 0);
                $gst_amount = ($price * $gst / 100) * $qty;
                $total_amount += ($price * $qty) + $gst_amount;
            }

            $stmt = $conn->prepare("
                INSERT INTO orders 
                (staff_id, staff_name, customer_name, customer_email, order_date, delivery_date, payment_method, total_amount, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->bind_param("issssssd", $staff_id, $staff_name, $customer_name, $customer_email, $order_date, $delivery_date, $payment_method, $total_amount);
            $stmt->execute();
            $order_id = $conn->insert_id;

            // 2️⃣ Insert order items with all details
            $itemStmt = $conn->prepare("
                INSERT INTO order_items
                (order_id, product_id, product_name, category_id, unit, quantity, price, gst_percentage)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            foreach($product_names as $i => $pname){
                $category_id = (int)($category_ids[$i] ?? 0);
                $pname      = trim($product_names[$i] ?? '');
                $unit       = trim($units[$i] ?? '');
                $qty        = floatval($quantities[$i] ?? 0);
                $price      = floatval($prices[$i] ?? 0);
                $gst        = floatval($gst_percentages[$i] ?? 0);

                // Use 0 for product_id since we're not linking to products table directly
                $product_id = 0;

                $itemStmt->bind_param(
                    "iisisidd",
                    $order_id,
                    $product_id,
                    $pname,
                    $category_id,
                    $unit,
                    $qty,
                    $price,
                    $gst
                );
                $itemStmt->execute();
            }

            // Commit transaction
            $conn->commit();
            $message = "✅ Order placed successfully! Order ID: $order_id";

        } catch(Exception $e) {
            $conn->rollback();
            $message = "❌ Failed to place order: " . $e->getMessage();
        }

    } else {
        $message = "❌ Please fill all required fields and at least one product.";
    }
}

// Fetch staff's orders with items for dashboard
$sql = "
    SELECT 
        o.order_id,
        o.customer_name,
        o.customer_email,
        o.staff_name,
        o.order_date,
        o.delivery_date,
        o.total_amount,
        o.payment_method,
        o.status,
        i.product_name,
        i.unit,
        i.quantity,
        i.price,
        i.gst_percentage,
        c.name AS category_name
    FROM orders o
    LEFT JOIN order_items i ON o.order_id = i.order_id
    LEFT JOIN categories c ON i.category_id = c.category_id
    WHERE o.staff_id = ?
    ORDER BY o.order_date DESC, o.order_id DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$ordersResult = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management | Staff Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Your existing CSS styles remain the same */
        :root {
            --primary: #4CAF50;
            --secondary: #FF9800;
            --accent: #2196F3;
            --light: #FFFFFF;
            --lighter: #F8F9FA;
            --lightest: #F5F7FF;
            --text: #424242;
            --text-light: #757575;
            --border: #E0E0E0;
            --success: #4CAF50;
            --warning: #FF9800;
            --error: #F44336;
            --shadow: 0 8px 30px rgba(0,0,0,0.08);
            --gradient: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--text);
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            background: var(--light);
            border-radius: 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 50px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.1)"><circle cx="200" cy="50" r="30"/><circle cx="600" cy="30" r="20"/><circle cx="800" cy="70" r="25"/></svg>');
            background-size: cover;
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        .header h1 {
            font-size: 3rem;
            font-weight: 300;
            margin-bottom: 15px;
            letter-spacing: -0.5px;
        }

        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
            font-weight: 300;
        }

        .content {
            padding: 40px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .card {
            background: var(--light);
            border-radius: 20px;
            padding: 35px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }

        .card h2 {
            color: var(--text);
            margin-bottom: 25px;
            font-size: 1.6rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card h2 i {
            color: var(--primary);
            font-size: 1.4em;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--text);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        label i {
            color: var(--accent);
            width: 16px;
        }

        input, select {
            padding: 14px 16px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--lighter);
            font-family: inherit;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--light);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }

        .products-section {
            background: var(--lightest);
            border-radius: 16px;
            padding: 25px;
            margin: 25px 0;
            border: 2px dashed var(--border);
        }

        .product-card {
            background: var(--light);
            padding: 25px;
            border-radius: 14px;
            margin-bottom: 20px;
            border-left: 4px solid var(--secondary);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .product-card:hover {
            border-left-color: var(--primary);
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            font-family: inherit;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.3);
        }

        .btn-success {
            background: var(--accent);
            color: white;
        }

        .btn-success:hover {
            background: #1976D2;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(33, 150, 243, 0.3);
        }

        .btn-danger {
            background: var(--error);
            color: white;
            padding: 10px 16px;
            font-size: 0.9rem;
        }

        .btn-danger:hover {
            background: #d32f2f;
            transform: translateY(-1px);
        }

        .submit-section {
            text-align: center;
            margin-top: 30px;
        }

        .btn-submit {
            background: var(--gradient);
            color: white;
            padding: 16px 50px;
            font-size: 1.1rem;
            border-radius: 50px;
            min-width: 200px;
            font-weight: 500;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(76, 175, 80, 0.4);
        }

        .table-container {
            background: var(--light);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-top: 20px;
            border: 1px solid var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(135deg, var(--primary), #45a049);
            color: white;
            padding: 18px 16px;
            text-align: left;
            font-weight: 500;
            font-size: 0.9rem;
            position: relative;
        }

        th:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 20%;
            height: 60%;
            width: 1px;
            background: rgba(255,255,255,0.3);
        }

        td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            transition: background 0.2s ease;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:nth-child(even) {
            background: var(--lightest);
        }

        tr:hover {
            background: rgba(76, 175, 80, 0.03);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending {
            background: rgba(255, 152, 0, 0.1);
            color: var(--warning);
        }

        .status-completed {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success);
        }

        .status-delivered {
            background: rgba(33, 150, 243, 0.1);
            color: var(--accent);
        }

        .back-nav {
            text-align: center;
            margin-top: 40px;
        }

        .btn-back {
            background: var(--text);
            color: white;
            padding: 14px 35px;
            border-radius: 50px;
            font-size: 1rem;
        }

        .btn-back:hover {
            background: #616161;
            transform: translateX(-5px);
        }

        .message {
            padding: 20px;
            border-radius: 14px;
            margin-bottom: 30px;
            font-weight: 500;
            text-align: center;
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-size: 1.1rem;
        }

        .message.success {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success);
            border-color: var(--success);
        }

        .message.error {
            background: rgba(244, 67, 54, 0.1);
            color: var(--error);
            border-color: var(--error);
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .product-card {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .container {
                margin: 20px;
                border-radius: 20px;
            }
            
            .content {
                padding: 30px;
            }
            
            .header h1 {
                font-size: 2.2rem;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }

        .order-count {
            background: var(--accent);
            color: white;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-left: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: var(--border);
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.4rem;
            margin-bottom: 10px;
            color: var(--text);
            font-weight: 400;
        }

        .amount-display {
            font-weight: 600;
            color: var(--primary);
        }

        .calculation-display {
            background: var(--lightest);
            padding: 15px;
            border-radius: 10px;
            margin-top: 10px;
            border-left: 4px solid var(--accent);
            grid-column: 1 / -1;
        }

        .calculation-display div {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .calculation-display .total {
            font-weight: 600;
            color: var(--primary);
            border-top: 1px solid var(--border);
            padding-top: 5px;
            margin-top: 5px;
        }

        .order-total-display {
            background: var(--primary);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .payment-badge {
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 500;
            background: rgba(33, 150, 243, 0.1);
            color: var(--accent);
        }
    </style>
    <script>
    // Product data from PHP
    const products = <?php echo json_encode($products); ?>;
    const categories = <?php echo json_encode($categories); ?>;

    function addProductRow() {
        const container = document.getElementById('products-container');
        const template = document.querySelector('.product-card');
        const newRow = template.cloneNode(true);
        
        // Reset inputs
        newRow.querySelectorAll('input').forEach(input => {
            if (input.name !== 'gst_percentage[]') {
                input.value = '';
            }
        });
        newRow.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
        
        // Clear calculation display
        const calcDisplay = newRow.querySelector('.calculation-display');
        if (calcDisplay) calcDisplay.innerHTML = '';
        
        // Add remove button functionality
        const removeBtn = newRow.querySelector('.btn-danger');
        removeBtn.onclick = function() {
            if (document.querySelectorAll('.product-card').length > 1) {
                newRow.style.transform = 'translateX(-100%)';
                newRow.style.opacity = '0';
                setTimeout(() => newRow.remove(), 300);
                updateOrderTotal();
            } else {
                alert('At least one product is required.');
            }
        };
        
        // Add event listeners for auto-fill
        const categorySelect = newRow.querySelector('select[name="category_id[]"]');
        const productInput = newRow.querySelector('input[name="product_name[]"]');
        const priceInput = newRow.querySelector('input[name="price[]"]');
        const gstInput = newRow.querySelector('input[name="gst_percentage[]"]');
        const quantityInput = newRow.querySelector('input[name="quantity[]"]');
        
        categorySelect.addEventListener('change', function() {
            updateGSTFromCategory(this);
        });
        
        productInput.addEventListener('input', function() {
            autoFillProductDetails(this);
        });
        
        quantityInput.addEventListener('input', function() {
            calculateProductTotal(this);
            updateOrderTotal();
        });
        
        priceInput.addEventListener('input', function() {
            calculateProductTotal(this);
            updateOrderTotal();
        });
        
        gstInput.addEventListener('input', function() {
            calculateProductTotal(this);
            updateOrderTotal();
        });
        
        container.appendChild(newRow);
        
        // Add entrance animation
        newRow.style.transform = 'translateY(20px)';
        newRow.style.opacity = '0';
        setTimeout(() => {
            newRow.style.transition = 'all 0.3s ease';
            newRow.style.transform = 'translateY(0)';
            newRow.style.opacity = '1';
        }, 10);
    }

    function updateGSTFromCategory(selectElement) {
        const categoryId = selectElement.value;
        const productCard = selectElement.closest('.product-card');
        const gstInput = productCard.querySelector('input[name="gst_percentage[]"]');
        
        const category = categories.find(cat => cat.category_id == categoryId);
        if (category && category.gst_percentage) {
            gstInput.value = category.gst_percentage;
            calculateProductTotal(selectElement);
            updateOrderTotal();
        }
    }

    function autoFillProductDetails(inputElement) {
        const productName = inputElement.value.toLowerCase();
        const productCard = inputElement.closest('.product-card');
        const priceInput = productCard.querySelector('input[name="price[]"]');
        const categorySelect = productCard.querySelector('select[name="category_id[]"]');
        const unitInput = productCard.querySelector('input[name="unit[]"]');
        
        // Find matching product
        const product = products.find(prod => 
            prod.name.toLowerCase().includes(productName) || 
            productName.includes(prod.name.toLowerCase())
        );
        
        if (product) {
            priceInput.value = product.price;
            categorySelect.value = product.category_id;
            unitInput.value = 'pcs'; // Default unit, you can modify this
            
            // Update GST based on category
            updateGSTFromCategory(categorySelect);
            
            calculateProductTotal(inputElement);
            updateOrderTotal();
        }
    }

    function calculateProductTotal(inputElement) {
        const productCard = inputElement.closest('.product-card');
        const price = parseFloat(productCard.querySelector('input[name="price[]"]').value) || 0;
        const quantity = parseFloat(productCard.querySelector('input[name="quantity[]"]').value) || 0;
        const gstPercentage = parseFloat(productCard.querySelector('input[name="gst_percentage[]"]').value) || 0;
        
        const baseAmount = price * quantity;
        const gstAmount = (baseAmount * gstPercentage) / 100;
        const total = baseAmount + gstAmount;
        
        // Create or update calculation display
        let calcDisplay = productCard.querySelector('.calculation-display');
        if (!calcDisplay) {
            calcDisplay = document.createElement('div');
            calcDisplay.className = 'calculation-display';
            productCard.appendChild(calcDisplay);
        }
        
        calcDisplay.innerHTML = `
            <div>
                <span>Base Amount:</span>
                <span>₹${baseAmount.toFixed(2)}</span>
            </div>
            <div>
                <span>GST (${gstPercentage}%):</span>
                <span>₹${gstAmount.toFixed(2)}</span>
            </div>
            <div class="total">
                <span>Product Total:</span>
                <span>₹${total.toFixed(2)}</span>
            </div>
        `;
        
        return total;
    }

    function updateOrderTotal() {
        let orderTotal = 0;
        const productCards = document.querySelectorAll('.product-card');
        
        productCards.forEach(card => {
            const price = parseFloat(card.querySelector('input[name="price[]"]').value) || 0;
            const quantity = parseFloat(card.querySelector('input[name="quantity[]"]').value) || 0;
            const gstPercentage = parseFloat(card.querySelector('input[name="gst_percentage[]"]').value) || 0;
            
            const baseAmount = price * quantity;
            const gstAmount = (baseAmount * gstPercentage) / 100;
            const total = baseAmount + gstAmount;
            
            orderTotal += total;
        });
        
        // Update order total display
        let orderTotalDisplay = document.querySelector('.order-total-display');
        if (!orderTotalDisplay) {
            orderTotalDisplay = document.createElement('div');
            orderTotalDisplay.className = 'order-total-display';
            document.querySelector('.products-section').appendChild(orderTotalDisplay);
        }
        
        orderTotalDisplay.innerHTML = `
            <i class="fas fa-receipt"></i>
            Order Total: ₹${orderTotal.toFixed(2)}
        `;
    }

    // Initialize event listeners for first product row
    document.addEventListener('DOMContentLoaded', function() {
        const firstCategorySelect = document.querySelector('select[name="category_id[]"]');
        const firstProductInput = document.querySelector('input[name="product_name[]"]');
        const firstQuantityInput = document.querySelector('input[name="quantity[]"]');
        const firstPriceInput = document.querySelector('input[name="price[]"]');
        const firstGstInput = document.querySelector('input[name="gst_percentage[]"]');
        
        if (firstCategorySelect) {
            firstCategorySelect.addEventListener('change', function() {
                updateGSTFromCategory(this);
            });
        }
        
        if (firstProductInput) {
            firstProductInput.addEventListener('input', function() {
                autoFillProductDetails(this);
            });
        }
        
        if (firstQuantityInput) {
            firstQuantityInput.addEventListener('input', function() {
                calculateProductTotal(this);
                updateOrderTotal();
            });
        }
        
        if (firstPriceInput) {
            firstPriceInput.addEventListener('input', function() {
                calculateProductTotal(this);
                updateOrderTotal();
            });
        }
        
        if (firstGstInput) {
            firstGstInput.addEventListener('input', function() {
                calculateProductTotal(this);
                updateOrderTotal();
            });
        }
        
        // Initialize order total
        updateOrderTotal();
    });
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <h1 class="floating"><i class="fas fa-shopping-bag"></i> Order Management</h1>
                <p>Streamline your order processing workflow</p>
            </div>
        </div>

        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
                    <i class="fas <?php echo strpos($message, '✅') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <div class="card">
                    <h2><i class="fas fa-plus"></i> Create New Order</h2>
                    <form method="post">
                        <div class="form-grid">
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Customer Name</label>
                                <input type="text" name="customer_name" required placeholder="Enter customer name">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> Customer Email</label>
                                <input type="email" name="customer_email" required placeholder="Enter email address">
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label><i class="fas fa-calendar"></i> Delivery Date (Optional)</label>
                                <input type="date" name="delivery_date">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-credit-card"></i> Payment Method</label>
                                <select name="payment_method" required>
                                    <option value="cash">Cash</option>
                                    <option value="card">Credit Card</option>
                                    <option value="upi">UPI</option>
                                    <option value="netbanking">Net Banking</option>
                                </select>
                            </div>
                        </div>

                        <div class="products-section">
                            <h3 style="color: var(--text); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-boxes"></i> Order Items
                            </h3>
                            
                            <div id="products-container">
                                <div class="product-card">
                                    <div class="form-group">
                                        <label><i class="fas fa-tag"></i> Category</label>
                                        <select name="category_id[]" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['category_id'] ?>" data-gst="<?= $cat['gst_percentage'] ?>">
                                                    <?= htmlspecialchars($cat['name']) ?> (GST: <?= $cat['gst_percentage'] ?>%)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fas fa-cube"></i> Product Name</label>
                                        <input type="text" name="product_name[]" placeholder="Start typing product name..." required>
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fas fa-balance-scale"></i> Quantity</label>
                                        <input type="number" name="quantity[]" step="0.01" min="0.01" placeholder="0.00" required>
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fas fa-ruler"></i> Unit</label>
                                        <input type="text" name="unit[]" placeholder="kg, pcs, box..." required>
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fas fa-dollar-sign"></i> Price</label>
                                        <input type="number" name="price[]" step="0.01" min="0" placeholder="0.00" required>
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fas fa-percentage"></i> GST %</label>
                                        <input type="number" name="gst_percentage[]" step="0.01" min="0" placeholder="0.00" required readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="button" class="btn btn-danger" onclick="if(document.querySelectorAll('.product-card').length > 1) this.closest('.product-card').remove(); else alert('At least one product is required.');">
                                            <i class="fas fa-times"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-success" onclick="addProductRow()">
                                <i class="fas fa-plus"></i> Add Product
                            </button>
                        </div>

                        <div class="submit-section">
                            <button type="submit" class="btn btn-submit" name="submit_order">
                                <i class="fas fa-paper-plane"></i> Submit Order
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h2>
                        <i class="fas fa-history"></i> Order History
                        <span class="order-count">
                            <?php 
                                $orderCount = $ordersResult->num_rows;
                                echo $orderCount . ' orders';
                            ?>
                        </span>
                    </h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Product</th>
                                    <th>Details</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $count = 0;
                                while($row = $ordersResult->fetch_assoc()): 
                                    $count++;
                                ?>
                                <tr>
                                    <td><strong>#<?= $row['order_id'] ?></strong></td>
                                    <td>
                                        <div style="font-weight: 500;"><?= htmlspecialchars($row['customer_name']) ?></div>
                                        <div style="font-size: 0.85rem; color: var(--text-light);"><?= htmlspecialchars($row['customer_email']) ?></div>
                                        <div style="font-size: 0.8rem; color: var(--accent); margin-top: 4px;">
                                            <i class="fas fa-user-tie"></i> <?= htmlspecialchars($row['staff_name']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500;"><?= htmlspecialchars($row['product_name']) ?></div>
                                        <div style="font-size: 0.85rem; color: var(--accent);"><?= htmlspecialchars($row['category_name']) ?></div>
                                    </td>
                                    <td>
                                        <span style="font-weight: 500; color: var(--secondary);"><?= htmlspecialchars($row['quantity']) ?> <?= htmlspecialchars($row['unit']) ?></span>
                                        <div style="font-size: 0.85rem;">₹<?= number_format($row['price'], 2) ?></div>
                                        <div style="font-size: 0.8rem; color: var(--warning);">GST: <?= $row['gst_percentage'] ?>%</div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--primary);">₹<?= number_format($row['total_amount'], 2) ?></div>
                                        <div class="payment-badge"><?= ucfirst($row['payment_method']) ?></div>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_class = 'status-pending';
                                        $status_text = 'Pending';
                                        if ($row['status'] == 'completed') {
                                            $status_class = 'status-completed';
                                            $status_text = 'Completed';
                                        } elseif ($row['status'] == 'delivered') {
                                            $status_class = 'status-delivered';
                                            $status_text = 'Delivered';
                                        }
                                        ?>
                                        <div class="status-badge <?= $status_class ?>"><?= $status_text ?></div>
                                        <?php if($row['delivery_date']): ?>
                                        <div style="font-size: 0.75rem; margin-top: 4px; color: var(--text-light);">
                                            Delivery: <?= date('M j, Y', strtotime($row['delivery_date'])) ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.9rem;"><?= date('M j, Y', strtotime($row['order_date'])) ?></div>
                                        <div style="font-size: 0.8rem; color: var(--text-light);"><?= date('h:i A', strtotime($row['order_date'])) ?></div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                
                                <?php if($count === 0): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <h3>No Orders Yet</h3>
                                            <p>Create your first order to get started</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="back-nav">
                <a href="staff_dashboard.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>