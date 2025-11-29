<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    $_SESSION['staff_id'] = 999;
    echo "✅ Session created.<br>";
} else {
    echo "💾 Session exists: " . $_SESSION['staff_id'] . "<br>";
}
?>
<a href="test_session.php">Refresh</a>
