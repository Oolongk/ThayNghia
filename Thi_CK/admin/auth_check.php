<?php
// File này dùng để chặn người lạ vào trang Admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kiểm tra đã đăng nhập chưa?
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Vui lòng đăng nhập để truy cập Admin!'); window.location.href='../login.php';</script>";
    exit();
}

// 2. Kiểm tra có phải Admin (role = 1) không?
if ($_SESSION['user']['role'] != 1) {
    echo "<script>alert('Bạn không có quyền truy cập trang này!'); window.location.href='../index.php';</script>";
    exit();
}
?>