<?php
$conn = new mysqli("localhost", "root", "", "webshop_php", 3307);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
?>