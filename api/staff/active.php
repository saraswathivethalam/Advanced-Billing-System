<?php
require_once "../../config/database.php";
header("Content-Type: application/json");

$sql = "SELECT id, name, role, status FROM staff WHERE status='Active' LIMIT 10";
$result = $conn->query($sql);

$data = [];
while($row = $result->fetch_assoc()){
    $data[] = $row;
}
echo json_encode($data);
