<?php
// navbar.php — Top navigation bar
session_start();
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="dashboard.php"><?php echo SITE_NAME; ?></a>
    <div class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto">
            <?php if(isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
