<div class="position-sticky pt-3">
    <div class="text-center mb-4">
        <h3 class="fw-bold">IBA STORE</h3>
        <small>Inventory System</small>
    </div>
    
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" href="products.php">
                <i class="fas fa-box me-2"></i> Manage Products
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'stock_alerts.php' ? 'active' : ''; ?>" href="stock_alerts.php">
                <i class="fas fa-exclamation-triangle me-2"></i> Stock Alerts
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'expiry_alerts.php' ? 'active' : ''; ?>" href="expiry_alerts.php">
                <i class="fas fa-calendar-times me-2"></i> Expiry Alerts
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'staff.php' ? 'active' : ''; ?>" href="staff.php">
                <i class="fas fa-users me-2"></i> Staff Management
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                <i class="fas fa-chart-bar me-2"></i> Daily Report
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </li>
    </ul>
</div>