<?php

$host = "localhost";
$db_name = "db"; 
$username = "root";
$password = "";

try {
    
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    

} catch(PDOException $e) {
    echo "Kết nối thất bại: " . $e->getMessage();
    die();
}


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>