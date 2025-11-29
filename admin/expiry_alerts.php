<?php
require("../config/database.php");
date_default_timezone_set('Asia/Kolkata');
$current_date = date('Y-m-d');

// ✅ Query valid expiry dates
$query = "
    SELECT product_id, name, expiry_date,
    CASE 
        WHEN expiry_date <= CURDATE() THEN 'Expired'
        WHEN expiry_date > CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Near Expiry'
        ELSE 'OK'
    END AS status
    FROM products
    WHERE expiry_date IS NOT NULL
      AND expiry_date <> ''
      AND expiry_date <> '0000-00-00'
    ORDER BY expiry_date ASC
";
$result = $conn->query($query);

// ✅ Stats
$total_alerts = 0;
$expired = [];
$near = [];
$ok = [];

while ($row = $result->fetch_assoc()) {
    $total_alerts++;
    if ($row['status'] === 'Expired') $expired[] = $row;
    elseif ($row['status'] === 'Near Expiry') $near[] = $row;
    else $ok[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Expiry Alerts Dashboard</title>
<style>
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    padding: 0;
    background: #f5f8ff;
    color: #222;
}

/* HEADER */
header {
    background: linear-gradient(135deg, #0062ff, #00c6ff);
    color: white;
    padding: 30px 20px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.15);
}
header h2 {
    margin: 0;
    font-size: 30px;
    letter-spacing: 1px;
}
header p {
    margin-top: 5px;
    font-size: 15px;
    opacity: 0.9;
}

/* MAIN CONTAINER */
.container {
    max-width: 1200px;
    margin: 40px auto;
    background: #fff;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* STATS */
.stats {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
}
.stat-card {
    flex: 1;
    min-width: 250px;
    background: linear-gradient(135deg, #ffffff, #f7f9ff);
    border-radius: 12px;
    text-align: center;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}
.stat-card:hover {
    transform: scale(1.04);
}
.stat-card h3 {
    margin: 10px 0;
    font-size: 24px;
}
.stat-card span {
    font-size: 16px;
    color: #666;
}
.stat-card.total { border-top: 5px solid #007bff; }
.stat-card.expired { border-top: 5px solid #ff4d4d; }
.stat-card.near { border-top: 5px solid #ffc107; }

/* TABLE */
.section {
    margin-top: 40px;
}
.section h3 {
    color: #007bff;
    text-align: left;
    margin-bottom: 10px;
    border-left: 6px solid #007bff;
    padding-left: 10px;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    border-radius: 10px;
    overflow: hidden;
}
th, td {
    padding: 12px;
    text-align: center;
}
th {
    background: #007bff;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
tr:nth-child(even) { background: #f7f9ff; }
tr:hover { background: #e8f1ff; transition: 0.2s; }

.status {
    display: inline-block;
    font-weight: bold;
    border-radius: 30px;
    padding: 6px 14px;
    font-size: 13px;
}
.status-expired {
    background: linear-gradient(135deg, #ff6b6b, #ff4757);
    color: white;
    box-shadow: 0 0 8px rgba(255, 71, 87, 0.4);
}
.status-near {
    background: linear-gradient(135deg, #ffe259, #ffa751);
    color: #333;
    box-shadow: 0 0 8px rgba(255, 193, 7, 0.3);
}
.status-ok {
    background: linear-gradient(135deg, #38ef7d, #11998e);
    color: white;
    box-shadow: 0 0 8px rgba(56, 239, 125, 0.3);
}

/* FOOTER */
footer {
    text-align: center;
    margin: 30px 0 15px;
    color: #777;
    font-size: 14px;
}
</style>
</head>
<body>

<header>
    <h2>📅 Expiry Alerts Dashboard</h2>
    <p>Track expired and near-expiry products at a glance</p>
</header>

<div class="container">
    <div class="stats">
        <div class="stat-card total">
            📊<h3><?php echo $total_alerts; ?></h3><span>Total Alerts</span>
        </div>
        <div class="stat-card expired">
            ⏰<h3><?php echo count($expired); ?></h3><span>Expired Products</span>
        </div>
        <div class="stat-card near">
            ⚠️<h3><?php echo count($near); ?></h3><span>Near Expiry (Next 7 Days)</span>
        </div>
    </div>

    <!-- Expired Products -->
    <div class="section">
        <h3>🔴 Expired Products</h3>
        <table>
            <tr><th>Product</th><th>Expiry Date</th><th>Status</th></tr>
            <?php if (count($expired) > 0): ?>
                <?php foreach ($expired as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['name']); ?></td>
                        <td><?php echo date("M j, Y", strtotime($p['expiry_date'])); ?></td>
                        <td><span class="status status-expired">Expired</span></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3">✅ No expired items!</td></tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Near Expiry Products -->
    <div class="section">
        <h3>🟡 Near Expiry (within 7 days)</h3>
        <table>
            <tr><th>Product</th><th>Expiry Date</th><th>Status</th></tr>
            <?php if (count($near) > 0): ?>
                <?php foreach ($near as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['name']); ?></td>
                        <td><?php echo date("M j, Y", strtotime($p['expiry_date'])); ?></td>
                        <td><span class="status status-near">Near Expiry</span></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3">✅ No near-expiry items!</td></tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- OK Products -->
    <div class="section">
        <h3>🟢 Safe / OK Products</h3>
        <table>
            <tr><th>Product</th><th>Expiry Date</th><th>Status</th></tr>
            <?php if (count($ok) > 0): ?>
                <?php foreach ($ok as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['name']); ?></td>
                        <td><?php echo date("M j, Y", strtotime($p['expiry_date'])); ?></td>
                        <td><span class="status status-ok">OK</span></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3">✅ All products are within safe dates!</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<footer>
    Expiry Alerts System • Last updated: <?php echo date("M j, Y g:i A"); ?>
</footer>

</body>
</html>
