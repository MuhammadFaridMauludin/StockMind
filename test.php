<?php
require_once "config/Database.php";

$db = new Database();
$conn = $db->getConnection();

$result = $conn->query("SELECT * FROM saham");

while ($row = $result->fetch_assoc()) {
    echo $row['kode'] . "<br>";
}