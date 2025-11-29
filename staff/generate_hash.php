<?php
$password_plain = "12345"; // your staff password
$hashed_password = password_hash($password_plain, PASSWORD_DEFAULT);
echo $hashed_password;
?>
