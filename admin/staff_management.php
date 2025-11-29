<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "online_billing_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Handle actions
$action = $_GET['action'] ?? '';
$id = intval($_GET['id'] ?? 0);

// Add staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_staff') {
    $staff_name = $_POST['staff_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO staff (name, username, phone, role, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $staff_name, $email, $phone, $role, $password);
    if ($stmt->execute()) {
        header("Location: staff_management.php?message=added");
        exit;
    } else {
        die("Add staff failed: " . $stmt->error);
    }
}

// Delete staff
if ($action === 'delete' && $id > 0) {
    $conn->query("DELETE FROM orders WHERE staff_id = $id");
    $conn->query("DELETE FROM staff WHERE id = $id");
    header("Location: staff_management.php?message=deleted");
    exit;
}

// Fetch staff data with sales statistics
$sql = "SELECT s.id, s.name, s.username, s.phone, s.role, 
               COUNT(o.order_id) as total_orders,
               COALESCE(SUM(o.total_amount), 0) as total_sales,
               MAX(o.order_date) as last_sale_date
        FROM staff s 
        LEFT JOIN orders o ON s.id = o.staff_id 
        GROUP BY s.id, s.name, s.username, s.phone, s.role 
        ORDER BY s.id ASC";
$result = $conn->query($sql);
if(!$result) { die("Database query failed: " . $conn->error); }

// Fetch recent sales for modal
$recent_sales = [];
if ($id > 0) {
    $sales_sql = "SELECT o.order_id, o.customer_name, o.total_amount, o.order_date, 
                         COUNT(oi.item_id) as items_count
                  FROM orders o 
                  LEFT JOIN order_items oi ON o.order_id = oi.order_id 
                  WHERE o.staff_id = $id 
                  GROUP BY o.order_id 
                  ORDER BY o.order_date DESC 
                  LIMIT 10";
    $sales_result = $conn->query($sales_sql);
    if ($sales_result) {
        while ($row = $sales_result->fetch_assoc()) {
            $recent_sales[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Management - IBA STORE</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #3b82f6;
    --primary-light: #60a5fa;
    --primary-dark: #2563eb;
    --secondary: #f59e0b;
    --danger: #ef4444;
    --success: #10b981;
    --info: #06b6d4;
    --warning: #f59e0b;
    --dark: #1f2937;
    --light: #f9fafb;
    --gray: #6b7280;
    --gray-light: #e5e7eb;
    --card-bg: #ffffff;
    --sidebar-bg: #1e293b;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #f0f4ff 0%, #f8fafc 100%);
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
    padding-bottom: 20px;
    border-bottom: 2px solid var(--gray-light);
}

.header h1 {
    font-size: 2.2rem;
    font-weight: 700;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 12px;
}

.header h1 i {
    color: var(--primary);
    font-size: 2rem;
}

.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: var(--primary);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
}

.back-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(59, 130, 246, 0.4);
}

/* Message Styles */
.message {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 25px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideIn 0.5s ease-out;
}

.success {
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
}

@keyframes slideIn {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* Button Styles */
.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    text-decoration: none;
    color: white;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
}

.btn-danger {
    background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
}

.btn-secondary {
    background: linear-gradient(135deg, var(--gray) 0%, #475569 100%);
}

.btn-info {
    background: linear-gradient(135deg, var(--info) 0%, #0891b2 100%);
}

/* Staff Grid */
.staff-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
    margin-top: 30px;
}

.staff-card {
    background: var(--card-bg);
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
    border-top: 4px solid var(--primary);
}

.staff-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.05), transparent);
    transition: left 0.6s ease;
}

.staff-card:hover::before {
    left: 100%;
}

.staff-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
}

.staff-info {
    margin-bottom: 20px;
}

.staff-field {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.staff-field:last-child {
    border-bottom: none;
}

.field-label {
    font-weight: 600;
    color: var(--gray);
    font-size: 0.9rem;
}

.field-value {
    font-weight: 500;
    color: var(--dark);
    text-align: right;
}

.role-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.role-staff { background: #dbeafe; color: #1e40af; }
.role-manager { background: #fef3c7; color: #92400e; }
.role-sales { background: #dcfce7; color: #166534; }
.role-support { background: #f3e8ff; color: #7e22ce; }
.role-admin { background: #fecaca; color: #991b1b; }

.sales-stats {
    background: #f8fafc;
    padding: 15px;
    border-radius: 10px;
    margin: 15px 0;
    border-left: 4px solid var(--success);
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.stat-item:last-child {
    margin-bottom: 0;
}

.stat-label {
    font-size: 0.85rem;
    color: var(--gray);
}

.stat-value {
    font-weight: 600;
    color: var(--dark);
}

.staff-actions {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-top: 15px;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    justify-content: center;
    align-items: center;
    z-index: 1000;
    animation: fadeIn 0.3s ease;
    padding: 20px;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background: white;
    padding: 35px;
    border-radius: 16px;
    max-width: 800px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    animation: slideUp 0.4s ease;
}

@keyframes slideUp {
    from { transform: translateY(30px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f1f5f9;
}

.modal-header h2 {
    font-size: 1.6rem;
    font-weight: 600;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 10px;
}

.close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: var(--gray);
    cursor: pointer;
    transition: color 0.3s ease;
}

.close-btn:hover {
    color: var(--danger);
}

/* Sales Table */
.sales-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.sales-table th {
    background: var(--primary);
    color: white;
    padding: 15px;
    text-align: left;
    font-weight: 600;
}

.sales-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #f1f5f9;
}

.sales-table tr:hover {
    background: #f8fafc;
}

.sales-table tr:last-child td {
    border-bottom: none;
}

/* Form Styles */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--dark);
}

.form-control {
    width: 100%;
    padding: 14px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: #f8fafc;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    background: white;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 25px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--gray);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 20px;
    color: #cbd5e1;
}

.empty-state h3 {
    font-size: 1.5rem;
    margin-bottom: 10px;
    color: var(--dark);
}

/* Responsive Design */
@media (max-width: 1024px) {
    .staff-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }
}

@media (max-width: 768px) {
    body {
        padding: 15px;
    }
    
    .header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .header h1 {
        font-size: 1.8rem;
    }
    
    .staff-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        padding: 25px;
        max-width: 95%;
    }
    
    .staff-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .staff-field {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .field-value {
        text-align: left;
    }
    
    .sales-table {
        font-size: 0.8rem;
    }
    
    .sales-table th,
    .sales-table td {
        padding: 8px 10px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        justify-content: center;
    }
}
</style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <h1><i class="fas fa-users-cog"></i> Staff Management</h1>
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Success Message -->
    <?php if(isset($_GET['message'])): ?>
    <div class="message success">
        <i class="fas fa-check-circle"></i>
        <?php
        switch($_GET['message']){
            case 'added': echo "Staff member added successfully!"; break;
            case 'deleted': echo "Staff member deleted successfully!"; break;
        }
        ?>
    </div>
    <?php endif; ?>

    <!-- Add Staff Button -->
    <button class="btn btn-primary" onclick="openAddModal()">
        <i class="fas fa-user-plus"></i> Add New Staff
    </button>

    <!-- Staff Grid -->
    <div class="staff-grid">
        <?php if($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="staff-card">
                    <div class="staff-info">
                        <div class="staff-field">
                            <span class="field-label">Name</span>
                            <span class="field-value"><?= htmlspecialchars($row['name']) ?></span>
                        </div>
                        <div class="staff-field">
                            <span class="field-label">Username</span>
                            <span class="field-value"><?= htmlspecialchars($row['username']) ?></span>
                        </div>
                        <div class="staff-field">
                            <span class="field-label">Phone</span>
                            <span class="field-value">
                                <i class="fas fa-phone"></i> 
                                <?= htmlspecialchars($row['phone'] ?: 'Not provided') ?>
                            </span>
                        </div>
                        <div class="staff-field">
                            <span class="field-label">Role</span>
                            <span class="field-value">
                                <span class="role-badge role-<?= strtolower(str_replace(' ', '-', $row['role'])) ?>">
                                    <?= htmlspecialchars($row['role']) ?>
                                </span>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Sales Statistics -->
                    <div class="sales-stats">
                        <div class="stat-item">
                            <span class="stat-label">Total Orders</span>
                            <span class="stat-value"><?= $row['total_orders'] ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Total Sales</span>
                            <span class="stat-value">₹<?= number_format($row['total_sales'], 2) ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Last Sale</span>
                            <span class="stat-value">
                                <?= $row['last_sale_date'] ? date('M d, Y', strtotime($row['last_sale_date'])) : 'No sales' ?>
                            </span>
                        </div>
                    </div>

                    <div class="staff-actions">
                        <button class="btn btn-info" onclick="viewSales(<?= $row['id'] ?>)">
                            <i class="fas fa-chart-line"></i> View Sales
                        </button>
                        <a href="staff_management.php?action=delete&id=<?= $row['id'] ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this staff member?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <h3>No Staff Members Found</h3>
                <p>Get started by adding your first staff member.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Staff Modal -->
<div class="modal" id="addModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-user-plus"></i> Add New Staff</h2>
            <button class="close-btn" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST" action="staff_management.php">
            <input type="hidden" name="action" value="add_staff">

            <div class="form-group">
                <label for="staff_name">Full Name</label>
                <input type="text" id="staff_name" name="staff_name" class="form-control" placeholder="Enter full name" required>
            </div>

            <div class="form-group">
                <label for="email">Username / Email</label>
                <input type="text" id="email" name="email" class="form-control" placeholder="Enter username or email" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control" placeholder="Enter phone number">
            </div>

            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="Staff" selected>Staff</option>
                    <option value="Manager">Manager</option>
                    <option value="Sales Associate">Sales Associate</option>
                    <option value="Support Staff">Support Staff</option>
                    <option value="Administrator">Administrator</option>
                </select>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Add Staff
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Sales Details Modal -->
<div class="modal" id="salesModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-chart-line"></i> Sales Details</h2>
            <button class="close-btn" onclick="closeModal('salesModal')">&times;</button>
        </div>
        <div id="salesContent">
            <!-- Sales content will be loaded here via AJAX -->
        </div>
    </div>
</div>

<script>
function openAddModal() { 
    document.getElementById('addModal').style.display = 'flex'; 
}

function closeModal(modalId) { 
    document.getElementById(modalId).style.display = 'none'; 
}

function viewSales(staffId) {
    // Show loading
    document.getElementById('salesContent').innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #3b82f6;"></i>
            <p>Loading sales data...</p>
        </div>
    `;
    
    document.getElementById('salesModal').style.display = 'flex';
    
    // Load sales data via AJAX
    fetch(`get_staff_sales.php?staff_id=${staffId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('salesContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('salesContent').innerHTML = `
                <div style="text-align: center; padding: 40px; color: #ef4444;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem;"></i>
                    <p>Error loading sales data</p>
                </div>
            `;
        });
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// Add animation to staff cards when they come into view
document.addEventListener('DOMContentLoaded', function() {
    const staffCards = document.querySelectorAll('.staff-card');
    staffCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
});
</script>

</body>
</html>

<?php 
// Close connection
$conn->close(); 
?>