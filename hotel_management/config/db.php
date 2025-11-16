<?php
$host = "localhost";
$dbname = "hotel_db";
$user = "root";
$pass = "123456789";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Bổ sung để đảm bảo tiếng Việt hiển thị chính xác
$conn->set_charset("utf8mb4"); 
?>