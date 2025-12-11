<?php
require_once '../config.php'; // Đã kết nối PDO

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = intval($_GET['id']);

// XỬ LÝ CẬP NHẬT TRẠNG THÁI
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    
    // --- CHUYỂN ĐỔI PDO ---
    try {
        $sql_update = "UPDATE tbl_orders SET status = :status WHERE order_id = :id";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->execute([
            ':status' => $new_status,
            ':id'     => $order_id
        ]);
        
        echo "<script>alert('Cập nhật trạng thái thành công!'); window.location.href='index.php';</script>"; // Chuyển về Dashboard theo ý bạn trước đó
    } catch (PDOException $e) {
        echo "<script>alert('Lỗi cập nhật: " . $e->getMessage() . "');</script>";
    }
}

// Lấy thông tin đơn hàng + Khách hàng (PDO)
$sql_order = "SELECT o.*, c.full_name, c.email, c.phone 
              FROM tbl_orders o 
              JOIN tbl_customers c ON o.customer_id = c.customer_id 
              WHERE o.order_id = :id";
$stmt_order = $conn->prepare($sql_order);
$stmt_order->execute([':id' => $order_id]);
$order = $stmt_order->fetch();

if (!$order) {
    echo "Đơn hàng không tồn tại.";
    exit();
}

// Lấy chi tiết sản phẩm trong đơn (PDO)
$sql_items = "SELECT oi.*, p.product_name, p.thumbnail, v.size 
              FROM tbl_order_items oi
              JOIN tbl_product_variants v ON oi.variant_id = v.variant_id
              JOIN tbl_inventory p ON v.product_id = p.product_id
              WHERE oi.order_id = :id";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->execute([':id' => $order_id]);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn hàng #<?php echo $order_id; ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .info-box { background: #fff; padding: 20px; border-radius: 5px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .btn-save { background: #28a745; color: #fff; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <div class="sidebar">
            <div class="sidebar-header"><h3>Admin Panel</h3></div>
            <ul class="sidebar-menu">
                <li><a href="orders.php" class="active"><i class="fas fa-arrow-left"></i> Quay lại DS</a></li>
            </ul>
        </div>

        <div class="main-content">
            <h2 class="page-title">Chi tiết đơn hàng #<?php echo $order_id; ?></h2>

            <div style="display: flex; gap: 20px;">
                <div style="width: 40%;">
                    <div class="info-box">
                        <h3>Thông tin khách hàng</h3>
                        <br>
                        <p><b>Họ tên:</b> <?php echo $order['full_name']; ?></p>
                        <p><b>Email:</b> <?php echo $order['email']; ?></p>
                        <p><b>SĐT:</b> <?php echo $order['phone']; ?></p>
                        <p><b>Ngày đặt:</b> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                        <br>
                        <h3>Địa chỉ giao hàng</h3>
                        <p style="background: #f9f9f9; padding: 10px; border-radius: 4px; margin-top: 5px;">
                            <?php echo $order['shipping_address']; ?>
                        </p>
                    </div>

                    <div class="info-box">
                        <h3>Trạng thái đơn hàng</h3>
                        <form action="" method="POST" style="margin-top: 15px;">
                            <select name="status" style="width: 100%; padding: 8px; margin-bottom: 10px; border-radius: 4px; border: 1px solid #ddd;">
                                <option value="pending" <?php if($order['status']=='pending') echo 'selected'; ?>>Chờ xử lý</option>
                                <option value="processing" <?php if($order['status']=='processing') echo 'selected'; ?>>Đang giao hàng</option>
                                <option value="completed" <?php if($order['status']=='completed') echo 'selected'; ?>>Hoàn thành</option>
                                <option value="cancelled" <?php if($order['status']=='cancelled') echo 'selected'; ?>>Đã hủy</option>
                            </select>
                            <button type="submit" name="update_status" class="btn-save">Cập nhật trạng thái</button>
                        </form>
                    </div>
                </div>

                <div style="width: 60%;">
                    <div class="info-box">
                        <h3>Sản phẩm đã mua</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Giá mua</th>
                                    <th>SL</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_check = 0;
                                // --- Thay mysqli_fetch_assoc bằng fetch() ---
                                while($item = $stmt_items->fetch()): 
                                    $subtotal = $item['price_at_purchase'] * $item['quantity'];
                                    $total_check += $subtotal;
                                ?>
                                <tr>
                                    <td style="display: flex; gap: 10px; align-items: center; border-bottom: none;">
                                        <img src="../uploads/<?php echo $item['thumbnail']; ?>" width="50" style="border-radius: 4px;">
                                        <div>
                                            <div style="font-weight: bold; font-size: 14px;"><?php echo $item['product_name']; ?></div>
                                            <div style="font-size: 12px; color: #666;">Size: <?php echo $item['size']; ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo number_format($item['price_at_purchase']); ?>đ</td>
                                    <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                                    <td style="color: var(--primary-color); font-weight: bold;"><?php echo number_format($subtotal); ?>đ</td>
                                </tr>
                                <?php endwhile; ?>
                                <tr style="background: #f1f1f1;">
                                    <td colspan="3" style="text-align: right; font-weight: bold;">TỔNG TIỀN:</td>
                                    <td style="color: red; font-weight: bold; font-size: 18px;"><?php echo number_format($total_check); ?>đ</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>