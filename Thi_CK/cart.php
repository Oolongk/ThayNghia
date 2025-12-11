<?php
include 'header.php'; // Đã bao gồm config.php (PDO) và session_start()
?>

<div class="container" style="margin-top: 40px; margin-bottom: 60px;">
    <h2 style="text-transform: uppercase; margin-bottom: 30px; border-bottom: 2px solid var(--primary-color); display: inline-block;">
        Giỏ hàng của bạn
    </h2>

    <?php
    // Kiểm tra giỏ hàng có trống không
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        echo '<div style="text-align:center; padding: 50px; background: #f9f9f9;">
                <p>Giỏ hàng đang trống!</p>
                <a href="index.php" class="btn-primary" style="margin-top:15px; display:inline-block;">Tiếp tục mua sắm</a>
              </div>';
    } else {
        // Xử lý cập nhật số lượng hoặc xóa (Phần này xử lý Session nên giữ nguyên)
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['update_qty'])) {
                foreach ($_POST['qty'] as $vid => $q) {
                    if ($q <= 0) {
                        unset($_SESSION['cart'][$vid]);
                    } else {
                        $_SESSION['cart'][$vid] = intval($q);
                    }
                }
            }
            if (isset($_POST['remove_id'])) {
                unset($_SESSION['cart'][$_POST['remove_id']]);
            }
            // Refresh lại trang để cập nhật số liệu
            echo "<script>window.location.href='cart.php';</script>";
        }

        // Lấy danh sách ID các biến thể (Variant IDs) từ session
        $cart_ids = array_keys($_SESSION['cart']);
        
        // Chuyển mảng ID thành chuỗi (ví dụ: 1,5,8) để dùng trong câu lệnh SQL IN (...)
        // Lưu ý: array_keys lấy từ session do server tạo ra nên tương đối an toàn, 
        // nhưng cẩn thận hơn có thể dùng intval để ép kiểu từng ID.
        $ids_string = implode(',', $cart_ids);

        // --- THAY ĐỔI: QUERY BẰNG PDO ---
        $sql = "SELECT v.variant_id, v.size, p.product_name, p.price, p.sale_price, p.thumbnail 
                FROM tbl_product_variants v 
                JOIN tbl_inventory p ON v.product_id = p.product_id 
                WHERE v.variant_id IN ($ids_string)";
        
        // Thay mysqli_query bằng $conn->query()
        $stmt = $conn->query($sql);
        
        $total_bill = 0;
    ?>

    <form action="" method="POST">
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr style="background: var(--secondary-color); color: #fff; text-align: left;">
                    <th style="padding: 15px;">Sản phẩm</th>
                    <th style="padding: 15px;">Đơn giá</th>
                    <th style="padding: 15px;">Số lượng</th>
                    <th style="padding: 15px;">Thành tiền</th>
                    <th style="padding: 15px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // --- THAY ĐỔI: FETCH BẰNG PDO ---
                // Thay mysqli_fetch_assoc bằng $stmt->fetch()
                while ($row = $stmt->fetch()): 
                    $qty = $_SESSION['cart'][$row['variant_id']];
                    $price = $row['sale_price'] > 0 ? $row['sale_price'] : $row['price'];
                    $subtotal = $price * $qty;
                    $total_bill += $subtotal;
                ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 15px; display: flex; align-items: center; gap: 15px;">
                        <img src="uploads/<?php echo $row['thumbnail']; ?>" style="width: 60px; height: 60px; object-fit: cover;">
                        <div>
                            <div style="font-weight: bold;"><?php echo $row['product_name']; ?></div>
                            <div style="font-size: 13px; color: #666;">Size: <?php echo $row['size']; ?></div>
                        </div>
                    </td>
                    <td style="padding: 15px; font-weight: 500;">
                        <?php echo number_format($price, 0, ',', '.'); ?>đ
                    </td>
                    <td style="padding: 15px;">
                        <input type="number" name="qty[<?php echo $row['variant_id']; ?>]" value="<?php echo $qty; ?>" min="1" style="width: 50px; padding: 5px; text-align: center;">
                    </td>
                    <td style="padding: 15px; color: var(--primary-color); font-weight: bold;">
                        <?php echo number_format($subtotal, 0, ',', '.'); ?>đ
                    </td>
                    <td style="padding: 15px;">
                        <button type="submit" name="remove_id" value="<?php echo $row['variant_id']; ?>" style="background: none; border: none; color: red; cursor: pointer;">
                            <i class="fas fa-trash"></i> Xóa
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 30px;">
            <button type="submit" name="update_qty" class="btn-primary" style="background: #666;">Cập nhật giỏ hàng</button>
            
            <div style="width: 300px; text-align: right;">
                <div style="font-size: 18px; font-weight: bold; margin-bottom: 20px;">
                    Tổng cộng: <span style="color: var(--primary-color); font-size: 24px;"><?php echo number_format($total_bill, 0, ',', '.'); ?>đ</span>
                </div>
                <a href="checkout.php" class="btn-primary" style="display: block; text-align: center; padding: 15px;">TIẾN HÀNH THANH TOÁN</a>
                <p style="margin-top: 10px; font-size: 12px; color: #666;">Phí vận chuyển sẽ được tính ở trang thanh toán.</p>
            </div>
        </div>
    </form>
    
    <?php } ?>
</div>

<?php include 'footer.php'; ?>