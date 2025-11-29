<?php
session_start();
require("../config/database.php");

// 🔒 Staff login check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit;
}

$staff_id = $_SESSION['staff_id'] ?? 0;
if ($staff_id == 0) {
    die("❌ Staff not logged in. Please login first.");
}

// --- Daily Sales ---
$today = date('Y-m-d');
$sql_daily = "SELECT SUM(total_amount) as daily_total, COUNT(*) as daily_orders 
              FROM orders WHERE staff_id=? AND DATE(order_date)=?";
$stmt_daily = $conn->prepare($sql_daily);
$stmt_daily->bind_param("is", $staff_id, $today);
$stmt_daily->execute();
$daily_res = $stmt_daily->get_result()->fetch_assoc();
$daily_total = $daily_res['daily_total'] ?? 0;
$daily_orders = $daily_res['daily_orders'] ?? 0;

// --- Monthly Sales ---
$month = date('Y-m');
$sql_monthly = "SELECT SUM(total_amount) as monthly_total, COUNT(*) as monthly_orders 
                FROM orders WHERE staff_id=? AND DATE_FORMAT(order_date,'%Y-%m')=?";
$stmt_monthly = $conn->prepare($sql_monthly);
$stmt_monthly->bind_param("is", $staff_id, $month);
$stmt_monthly->execute();
$monthly_res = $stmt_monthly->get_result()->fetch_assoc();
$monthly_total = $monthly_res['monthly_total'] ?? 0;
$monthly_orders = $monthly_res['monthly_orders'] ?? 0;

// --- Payment Breakdown ---
$sql_payment_breakdown = "SELECT payment_method, COUNT(*) as count, SUM(total_amount) as amount 
                         FROM orders WHERE staff_id=? GROUP BY payment_method";
$stmt_breakdown = $conn->prepare($sql_payment_breakdown);
$stmt_breakdown->bind_param("i", $staff_id);
$stmt_breakdown->execute();
$payment_breakdown = $stmt_breakdown->get_result();

// --- Fetch complete transaction history with corrected query ---
$sql = "SELECT 
            o.order_id, 
            o.customer_name, 
            o.customer_email, 
            o.order_date, 
            o.delivery_date, 
            o.payment_method,
            o.total_amount,
            i.product_name, 
            i.quantity, 
            i.price, 
            i.gst_percentage as gst_percent,
            i.gst_amount,
            IFNULL(i.discount, 0) AS discount
        FROM orders o 
        LEFT JOIN order_items i ON o.order_id = i.order_id
        WHERE o.staff_id = ?
        ORDER BY o.order_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Payment History | Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* --- Basic Styles --- */
body { font-family: 'Inter', sans-serif; background:#f8fafc; margin:0; padding:20px; color:#2c3e50; }
.container { max-width:1400px; margin:0 auto; }
header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; padding:25px 30px; background:#fff; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.08); border-left:4px solid #3498db; }
.logo { display:flex; align-items:center; gap:15px; }
.logo i { font-size:2.2rem; color:#3498db; }
.logo h1 { font-size:1.6rem; font-weight:600; color:#2c3e50; }
.user-info { display:flex; align-items:center; gap:10px; background:#ecf0f1; padding:10px 20px; border-radius:30px; font-weight:500; }
.user-info i { color:#3498db; }

/* --- Stats Grid --- */
.stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:20px; margin-bottom:30px; }
.stat-box { background:#fff; border-radius:8px; padding:25px; box-shadow:0 2px 10px rgba(0,0,0,0.08); position:relative; overflow:hidden; transition:all 0.3s ease; }
.stat-box::before { content:''; position:absolute; top:0; left:0; width:4px; height:100%; background:#3498db; }
.stat-box.today::before { background:#27ae60; }
.stat-box.monthly::before { background:#3498db; }
.stat-box.total::before { background:#e74c3c; }
.stat-box:hover { transform:translateY(-3px); box-shadow:0 5px 15px rgba(0,0,0,0.1); }
.stat-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px; }
.stat-title { font-size:0.95rem; color:#95a5a6; font-weight:500; text-transform:uppercase; letter-spacing:0.5px; }
.stat-icon { width:40px; height:40px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; background:rgba(52,152,219,0.1); color:#3498db; }
.today .stat-icon { background:rgba(39,174,96,0.1); color:#27ae60; }
.monthly .stat-icon { background:rgba(52,152,219,0.1); color:#3498db; }
.total .stat-icon { background:rgba(231,76,60,0.1); color:#e74c3c; }
.stat-value { font-size:2.2rem; font-weight:700; margin-bottom:5px; color:#2c3e50; }
.stat-footer { font-size:0.9rem; color:#95a5a6; display:flex; align-items:center; gap:5px; }

/* --- Payment Methods --- */
.payment-summary { background:#fff; border-radius:8px; padding:25px; box-shadow:0 2px 10px rgba(0,0,0,0.08); margin-bottom:30px; }
.methods-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:20px; }
.method-item { display:flex; align-items:center; gap:15px; padding:15px; background:#ecf0f1; border-radius:8px; transition:all 0.3s ease; }
.method-item:hover { background:#e3f2fd; }
.method-icon { width:50px; height:50px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; background:#fff; box-shadow:0 2px 5px rgba(0,0,0,0.05); }
.cash .method-icon { color:#27ae60; }
.upi .method-icon { color:#3498db; }
.card .method-icon { color:#e74c3c; }
.netbanking .method-icon { color:#9b59b6; }
.method-details { flex:1; }
.method-name { font-weight:600; margin-bottom:5px; color:#2c3e50; }
.method-stats { display:flex; justify-content:space-between; font-size:0.9rem; }
.method-amount { font-weight:700; color:#2c3e50; }
.method-count { color:#95a5a6; }

/* --- Table --- */
.table-container { background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,0.08); margin-bottom:30px; }
.table-header { padding:20px 25px; background:#2c3e50; color:#fff; display:flex; justify-content:space-between; align-items:center; }
.table-header h3 { font-size:1.3rem; font-weight:600; display:flex; align-items:center; gap:10px; }
.btn { padding:8px 16px; border:none; border-radius:6px; cursor:pointer; display:flex; align-items:center; gap:6px; font-weight:500; transition:all 0.3s ease; font-size:0.9rem; }
.btn-outline { background:transparent; border:1px solid rgba(255,255,255,0.3); color:#fff; }
.btn-outline:hover { background:rgba(255,255,255,0.1); }
table { width:100%; border-collapse:collapse; }
thead { background:#f8fafc; }
th { padding:16px 12px; text-align:left; font-weight:600; color:#2c3e50; border-bottom:1px solid #bdc3c7; font-size:0.9rem; text-transform:uppercase; letter-spacing:0.5px; }
td { padding:14px 12px; border-bottom:1px solid #bdc3c7; font-size:0.9rem; }
tbody tr:hover { background:#f8fafc; }
.payment-badge { display:inline-block; padding:5px 12px; border-radius:20px; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; }
.badge-cash { background:rgba(39,174,96,0.1); color:#27ae60; border:1px solid rgba(39,174,96,0.2); }
.badge-upi { background:rgba(52,152,219,0.1); color:#3498db; border:1px solid rgba(52,152,219,0.2); }
.badge-card { background:rgba(231,76,60,0.1); color:#e74c3c; border:1px solid rgba(231,76,60,0.2); }
.badge-netbanking { background:rgba(155,89,182,0.1); color:#9b59b6; border:1px solid rgba(155,89,182,0.2); }
.customer-info { display:flex; flex-direction:column; }
.customer-name { font-weight:600; color:#2c3e50; }
.customer-email { font-size:0.8rem; color:#95a5a6; }
.amount-cell { font-weight:600; color:#2c3e50; }
.no-data { text-align:center; padding:50px; color:#95a5a6; }
.no-data i { font-size:3rem; margin-bottom:15px; opacity:0.5; }
.no-data h3 { margin-bottom:10px; font-weight:600; }
footer { text-align:center; padding:20px; color:#95a5a6; font-size:0.9rem; border-top:1px solid #bdc3c7; margin-top:30px; }
@media(max-width:768px){ .stats-grid,.methods-grid{grid-template-columns:1fr;} header{flex-direction:column;gap:15px;text-align:center;} .table-header{flex-direction:column;gap:15px;align-items:flex-start;} table{font-size:0.85rem;} th,td{padding:10px 8px;} .table-actions{width:100%;justify-content:flex-end;} }
</style>
</head>
<body>
<div class="container">
<header>
    <div class="logo">
        <i class="fas fa-receipt"></i>
        <h1>Payment History</h1>
    </div>
    <div class="user-info">
        <i class="fas fa-user-tie"></i>
        <span>Staff ID: <?= $staff_id ?></span>
    </div>
</header>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-box today">
        <div class="stat-header">
            <div>
                <div class="stat-title">Today's Sales</div>
                <div class="stat-value">₹<?= number_format($daily_total,2) ?></div>
            </div>
            <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
        </div>
        <div class="stat-footer"><i class="fas fa-shopping-cart"></i> <?= $daily_orders ?> orders today</div>
    </div>
    <div class="stat-box monthly">
        <div class="stat-header">
            <div>
                <div class="stat-title">Monthly Sales</div>
                <div class="stat-value">₹<?= number_format($monthly_total,2) ?></div>
            </div>
            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
        </div>
        <div class="stat-footer"><i class="fas fa-chart-line"></i> <?= $monthly_orders ?> orders this month</div>
    </div>
    <div class="stat-box total">
        <div class="stat-header">
            <div>
                <div class="stat-title">Total Orders</div>
                <div class="stat-value"><?= $result->num_rows ?></div>
            </div>
            <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
        </div>
        <div class="stat-footer"><i class="fas fa-history"></i> All-time transactions</div>
    </div>
</div>

<!-- Payment Methods -->
<div class="payment-summary">
    <div class="methods-grid">
        <?php 
        $payment_data = [];
        while($row_break = $payment_breakdown->fetch_assoc()){
            $payment_data[$row_break['payment_method']] = $row_break;
        }
        $methods = [
            'cash'=>['Cash','cash','fa-money-bill-wave'],
            'upi'=>['UPI','upi','fa-mobile-alt'],
            'card'=>['Card','card','fa-credit-card'],
            'netbanking'=>['Net Banking','netbanking','fa-university']
        ];
        foreach($methods as $key=>$method):
            $data = $payment_data[$key] ?? ['count'=>0,'amount'=>0];
        ?>
        <div class="method-item <?= $method[1] ?>">
            <div class="method-icon"><i class="fas <?= $method[2] ?>"></i></div>
            <div class="method-details">
                <div class="method-name"><?= $method[0] ?></div>
                <div class="method-stats">
                    <span class="method-amount">₹<?= number_format($data['amount'],2) ?></span>
                    <span class="method-count"><?= $data['count'] ?> transactions</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Transaction Table -->
<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-table"></i> Transaction History</h3>
        <div class="table-actions">
            <button class="btn btn-outline"><i class="fas fa-file-export"></i> Export</button>
            <button class="btn btn-outline"><i class="fas fa-filter"></i> Filter</button>
        </div>
    </div>
    <div style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Product</th>
                <th>Qty</th>
                <th>Price</th>
                <th>GST %</th>
                <th>GST Amount</th>
                <th>Discount</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Order Date</th>
            </tr>
        </thead>
        <tbody>
        <?php if($result->num_rows>0): 
            while($row=$result->fetch_assoc()): 
                // Determine badge class based on payment method
                $badge_class = 'badge-' . $row['payment_method'];
                if (!in_array($row['payment_method'], ['cash', 'upi', 'card', 'netbanking'])) {
                    $badge_class = 'badge-cash'; // default fallback
                }
        ?>
            <tr>
                <td><strong>#<?= $row['order_id'] ?></strong></td>
                <td>
                    <div class="customer-info">
                        <span class="customer-name"><?= htmlspecialchars($row['customer_name']) ?></span>
                        <span class="customer-email"><?= htmlspecialchars($row['customer_email']) ?></span>
                    </div>
                </td>
                <td><?= htmlspecialchars($row['product_name']) ?></td>
                <td><?= $row['quantity'] ?></td>
                <td class="amount-cell">₹<?= number_format($row['price'],2) ?></td>
                <td><?= number_format($row['gst_percent'],2) ?>%</td>
                <td class="amount-cell">₹<?= number_format($row['gst_amount'],2) ?></td>
                <td class="amount-cell">₹<?= number_format($row['discount'],2) ?></td>
                <td class="amount-cell">₹<?= number_format($row['total_amount'],2) ?></td>
                <td>
                    <span class="payment-badge <?= $badge_class ?>">
                        <?= strtoupper($row['payment_method']) ?>
                    </span>
                </td>
                <td><?= date("M d, Y", strtotime($row['order_date'])) ?></td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td colspan="11">
                <div class="no-data">
                    <i class="fas fa-search"></i>
                    <h3>No transactions found</h3>
                    <p>Your sales history will appear here</p>
                </div>
            </td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<footer>
    <p>&copy; <?= date('Y') ?> Staff Dashboard • Staff ID: <?= $staff_id ?></p>
</footer>
</div>

</body>
</html>

<?php
// Close connections
$stmt->close();
$stmt_daily->close();
$stmt_monthly->close();
$stmt_breakdown->close();
$conn->close();
?>