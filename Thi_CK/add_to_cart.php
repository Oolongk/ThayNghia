<?php
require_once 'config.php'; // Đã kết nối PDO

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['variant_id'])) {
    $variant_id = intval($_POST['variant_id']);
    $qty = 1; // Mặc định thêm 1 (Sau này có thể lấy từ $_POST['quantity'] nếu muốn)

    // --- BƯỚC KIỂM TRA (Dùng PDO) ---
    // Kiểm tra xem ID này có thật trong Database không để tránh lỗi
    $stmt = $conn->prepare("SELECT variant_id FROM tbl_product_variants WHERE variant_id = :id");
    $stmt->execute([':id' => $variant_id]);

    // Nếu sản phẩm có tồn tại (rowCount > 0) thì mới thêm vào giỏ
    if ($stmt->rowCount() > 0) {
        
        // Giỏ hàng lưu dưới dạng: [variant_id => quantity]
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (isset($_SESSION['cart'][$variant_id])) {
            $_SESSION['cart'][$variant_id] += $qty;
        } else {
            $_SESSION['cart'][$variant_id] = $qty;
        }
    }

    // Chuyển hướng
    header("Location: cart.php");
    exit();

} else {
    // Nếu truy cập trực tiếp hoặc không chọn size
    header("Location: index.php");
    exit();
}
?>