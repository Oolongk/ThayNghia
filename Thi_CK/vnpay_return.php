<?php
require_once 'config.php'; // Kết nối DB (PDO)
require_once 'vnpay_config.php'; // Cấu hình VNPAY

$vnp_SecureHash = $_GET['vnp_SecureHash'];
$inputData = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}
unset($inputData['vnp_SecureHash']);
ksort($inputData);
$i = 0;
$hashdata = "";
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashdata .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}

$secureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kết quả thanh toán VNPAY</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .res-container { max-width: 600px; margin: 50px auto; text-align: center; padding: 40px; border: 1px solid #ddd; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .icon-success { color: #28a745; font-size: 60px; margin-bottom: 20px; }
        .icon-fail { color: #dc3545; font-size: 60px; margin-bottom: 20px; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="res-container">
        <?php
        // KIỂM TRA CHỮ KÝ HỢP LỆ
        if ($secureHash == $vnp_SecureHash) {
            $order_id = $_GET['vnp_TxnRef'];
            $amount = $_GET['vnp_Amount'] / 100;

            if ($_GET['vnp_ResponseCode'] == '00') {
                // --- THANH TOÁN THÀNH CÔNG ---
                
                // 1. Cập nhật trạng thái đơn hàng thành 'processing' (Đã thanh toán/Đang xử lý)
                $stmt = $conn->prepare("UPDATE tbl_orders SET status = 'processing' WHERE order_id = :id");
                $stmt->execute([':id' => $order_id]);

                // 2. Xóa giỏ hàng (Vì lúc checkout VNPAY mình chưa xóa)
                
                unset($_SESSION['cart']);

                echo "<i class='fas fa-check-circle icon-success'></i>";
                echo "<h2>Thanh toán thành công!</h2>";
                echo "<p>Mã đơn hàng: <b>#$order_id</b></p>";
                echo "<p>Số tiền: <b>".number_format($amount)."đ</b></p>";
                echo "<a href='index.php' class='btn-primary' style='display:inline-block; margin-top:20px; padding:10px 20px;'>Tiếp tục mua sắm</a>";

            } else {
                // --- THANH TOÁN THẤT BẠI / HỦY BỎ ---
                echo "<i class='fas fa-times-circle icon-fail'></i>";
                echo "<h2>Thanh toán thất bại</h2>";
                echo "<p>Giao dịch bị hủy hoặc có lỗi xảy ra.</p>";
                echo "<a href='checkout.php' class='btn-primary' style='background:#333; display:inline-block; margin-top:20px; padding:10px 20px;'>Thử lại</a>";
            }
        } else {
            echo "<h2 style='color:red'>Chữ ký không hợp lệ! (Checksum failed)</h2>";
        }
        ?>
    </div>
</body>
</html>