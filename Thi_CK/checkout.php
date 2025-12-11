<?php 
include 'header.php'; 
require_once 'vnpay_config.php'; // Config VNPAY

// 1. Kiểm tra đăng nhập & Giỏ hàng
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Bạn cần đăng nhập để thanh toán!'); window.location.href='login.php';</script>"; exit();
}
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: index.php"); exit();
}

$user = $_SESSION['user'];
$cart_ids = array_keys($_SESSION['cart']);
$ids_string = implode(',', $cart_ids);

// 2. Lấy thông tin sản phẩm trong giỏ (Kèm ảnh Thumbnail)
$stmt = $conn->query("SELECT v.variant_id, v.size, p.product_name, p.thumbnail, p.price, p.sale_price 
                      FROM tbl_product_variants v 
                      JOIN tbl_inventory p ON v.product_id = p.product_id 
                      WHERE v.variant_id IN ($ids_string)");

$total_bill = 0;
$order_items = []; 

// Lấy dữ liệu
while ($row = $stmt->fetch()) {
    $qty = $_SESSION['cart'][$row['variant_id']];
    $price = $row['sale_price'] > 0 ? $row['sale_price'] : $row['price'];
    $total_bill += $price * $qty;
    
    // Lưu vào mảng để dùng khi hiển thị và lưu DB
    $row['qty_buy'] = $qty;
    $row['price_buy'] = $price;
    $order_items[] = $row;
}

// --- 3. XỬ LÝ ĐẶT HÀNG (GIỮ NGUYÊN LOGIC CŨ) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $address = $_POST['address'];
    $payment_method = $_POST['payment_method'];
    $customer_id = $user['customer_id'];
    $note = isset($_POST['note']) ? $_POST['note'] : '';

    try {
        $conn->beginTransaction();

        // A. Tạo đơn hàng
        $sql_order = "INSERT INTO tbl_orders (customer_id, total_amount, payment_method, status, shipping_address) 
        VALUES (:customer_id, :total, :method, 'pending', :address)";

        $stmt_order = $conn->prepare($sql_order);
        $stmt_order->execute([
        ':customer_id' => $customer_id,
        ':total'       => $total_bill,
        ':method'      => $payment_method, 
        ':address'     => $address
        ]);
        $order_id = $conn->lastInsertId(); 

        // B. Lưu chi tiết & Trừ kho
        $sql_item = "INSERT INTO tbl_order_items (order_id, variant_id, quantity, price_at_purchase) VALUES (:order_id, :vid, :qty, :price)";
        $stmt_item = $conn->prepare($sql_item);
        
        $sql_deduct = "UPDATE tbl_product_variants SET stock_quantity = stock_quantity - :qty WHERE variant_id = :vid AND stock_quantity >= :qty";
        $stmt_deduct = $conn->prepare($sql_deduct);

        foreach ($order_items as $item) {
            $stmt_item->execute([':order_id' => $order_id, ':vid' => $item['variant_id'], ':qty' => $item['qty_buy'], ':price' => $item['price_buy']]);
            $stmt_deduct->execute([':qty' => $item['qty_buy'], ':vid' => $item['variant_id']]);
            
            if ($stmt_deduct->rowCount() == 0) {
                $conn->rollBack();
                echo "<script>alert('Sản phẩm ".$item['product_name']." (Size: ".$item['size'].") không đủ hàng!'); window.location.href='cart.php';</script>"; exit();
            }
        }
        $conn->commit();
        
        // C. Xử lý thanh toán VNPAY
        if ($payment_method == 'vnpay') {
            $vnp_TxnRef = $order_id; 
            $vnp_OrderInfo = 'Thanh toan don hang #' . $order_id;
            $vnp_Amount = $total_bill * 100;
            $vnp_Locale = 'vn';
            $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

            $inputData = array(
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => $vnp_TmnCode,
                "vnp_Amount" => $vnp_Amount,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $vnp_IpAddr,
                "vnp_Locale" => $vnp_Locale,
                "vnp_OrderInfo" => $vnp_OrderInfo,
                "vnp_OrderType" => "other",
                "vnp_ReturnUrl" => $vnp_Returnurl,
                "vnp_TxnRef" => $vnp_TxnRef
            );

            ksort($inputData);
            $query = "";
            $i = 0;
            $hashdata = "";
            foreach ($inputData as $key => $value) {
                if ($i == 1) {
                    $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
                } else {
                    $hashdata .= urlencode($key) . "=" . urlencode($value);
                    $i = 1;
                }
                $query .= urlencode($key) . "=" . urlencode($value) . '&';
            }

            $vnp_Url = $vnp_Url . "?" . $query;
            if (isset($vnp_HashSecret)) {
                $vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret);
                $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
            }
            header('Location: ' . $vnp_Url);
            die();

        } else {
            unset($_SESSION['cart']);
            echo "<script>alert('Đặt hàng thành công (COD)! Mã đơn: #$order_id'); window.location.href='index.php';</script>";
        }

    } catch (PDOException $e) {
        $conn->rollBack();
        echo "Lỗi: " . $e->getMessage();
    }
}
?>

<style>
    body { background-color: #f5f5fa; } /* Nền xám nhẹ cho toàn trang */
    
    .checkout-wrapper {
        display: flex;
        gap: 30px;
        margin-top: 40px;
        margin-bottom: 60px;
    }

    /* --- CỘT TRÁI --- */
    .checkout-left { flex: 2; }
    .checkout-section {
        background: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        margin-bottom: 20px;
    }
    .section-title {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 20px;
        display: flex; align-items: center; gap: 10px;
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
    }
    .section-title i { color: var(--primary-color); }

    .form-group { margin-bottom: 15px; }
    .form-label { font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block; color: #555; }
    .form-control {
        width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px;
        font-family: inherit; font-size: 14px; transition: 0.3s;
        background: #f9f9f9;
    }
    .form-control:focus { border-color: var(--primary-color); background: #fff; outline: none; }
    .form-control:disabled { background: #eee; color: #777; cursor: not-allowed; }

    /* Phương thức thanh toán đẹp */
    .payment-options { display: flex; flex-direction: column; gap: 15px; }
    .payment-card {
        display: flex; align-items: center; gap: 15px;
        padding: 15px; border: 1px solid #ddd; border-radius: 8px;
        cursor: pointer; transition: 0.3s;
        background: #fff;
    }
    .payment-card:hover { border-color: var(--primary-color); background: #fffcf8; }
    
    /* Ẩn radio mặc định */
    .payment-card input[type="radio"] { accent-color: var(--primary-color); transform: scale(1.2); }
    
    /* Highlight khi được chọn */
    .payment-card.selected { border: 2px solid var(--primary-color); background: #fff5f0; }

    /* --- CỘT PHẢI (SUMMARY) --- */
    .checkout-right { 
        flex: 1; 
        position: sticky; top: 100px; /* Trượt theo khi cuộn */
        height: fit-content;
    }
    .summary-card {
        background: #fff; padding: 25px; border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    
    /* Danh sách sản phẩm nhỏ */
    .item-row { display: flex; gap: 15px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dashed #eee; }
    .item-thumb { width: 60px; height: 60px; border-radius: 6px; object-fit: cover; border: 1px solid #f0f0f0; }
    .item-info { flex: 1; }
    .item-name { font-size: 14px; font-weight: 600; margin-bottom: 4px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .item-meta { font-size: 12px; color: #888; }
    .item-price { font-weight: bold; font-size: 14px; }

    /* Tổng tiền */
    .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; color: #555; }
    .summary-total { 
        display: flex; justify-content: space-between; margin-top: 20px; padding-top: 20px; 
        border-top: 2px solid #eee; font-size: 18px; font-weight: 800; color: #333; 
    }
    .total-price { color: var(--primary-color); font-size: 22px; }

    /* Nút Đặt hàng */
    .btn-checkout {
        width: 100%; padding: 15px; margin-top: 20px;
        background: var(--primary-color); color: #fff; border: none; border-radius: 6px;
        font-size: 16px; font-weight: 700; text-transform: uppercase; cursor: pointer;
        transition: 0.3s;
    }
    .btn-checkout:hover { background: #d1400b; box-shadow: 0 5px 15px rgba(241, 90, 34, 0.3); }

    /* Responsive */
    @media (max-width: 768px) {
        .checkout-wrapper { flex-direction: column; }
        .checkout-right { position: static; }
    }
</style>

<div class="container checkout-wrapper">
    <div class="checkout-left">
        <form method="POST" id="checkoutForm">
            <div class="checkout-section">
                <div class="section-title"><i class="fas fa-map-marker-alt"></i> Thông tin giao hàng</div>
                
                <div class="form-group">
                    <label class="form-label">Người nhận</label>
                    <input type="text" value="<?php echo $user['full_name']; ?> - <?php echo $user['phone']; ?>" class="form-control" disabled>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Địa chỉ nhận hàng (Bắt buộc)</label>
                    <textarea name="address" class="form-control" rows="3" required placeholder="Nhập số nhà, tên đường, phường/xã..."><?php echo $user['address']; ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Ghi chú đơn hàng (Tùy chọn)</label>
                    <textarea name="note" class="form-control" rows="2" placeholder="Ví dụ: Giao hàng giờ hành chính..."></textarea>
                </div>
            </div>

            <div class="checkout-section">
                <div class="section-title"><i class="fas fa-wallet"></i> Phương thức thanh toán</div>
                
                <div class="payment-options">
                    <label class="payment-card selected" onclick="selectPayment(this)">
                        <input type="radio" name="payment_method" value="cod" checked>
                        <i class="fas fa-money-bill-wave" style="font-size: 24px; color: #28a745;"></i>
                        <div>
                            <div style="font-weight: bold;">Thanh toán khi nhận hàng (COD)</div>
                            <div style="font-size: 12px; color: #666;">Bạn chỉ phải thanh toán khi nhận được hàng.</div>
                        </div>
                    </label>

                    <label class="payment-card" onclick="selectPayment(this)">
                        <input type="radio" name="payment_method" value="vnpay">
                        <img src="https://vnpay.vn/assets/images/logo-icon/logo-primary.svg" height="24">
                        <div>
                            <div style="font-weight: bold;">Thanh toán qua VNPAY</div>
                            <div style="font-size: 12px; color: #666;">Quét mã QR hoặc thẻ ATM/Visa nội địa.</div>
                        </div>
                    </label>
                </div>
            </div>
        </form>
    </div>

    <div class="checkout-right">
        <div class="summary-card">
            <div class="section-title">Đơn hàng của bạn (<?php echo count($order_items); ?> món)</div>
            
            <div class="cart-items-list" style="max-height: 300px; overflow-y: auto; margin-bottom: 20px; padding-right: 5px;">
                <?php foreach($order_items as $item): ?>
                <div class="item-row">
                    <img src="uploads/<?php echo $item['thumbnail']; ?>" class="item-thumb">
                    <div class="item-info">
                        <div class="item-name"><?php echo $item['product_name']; ?></div>
                        <div class="item-meta">Size: <?php echo $item['size']; ?> | x<?php echo $item['qty_buy']; ?></div>
                    </div>
                    <div class="item-price"><?php echo number_format($item['price_buy'] * $item['qty_buy']); ?>đ</div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-row">
                <span>Tạm tính:</span>
                <span><?php echo number_format($total_bill); ?>đ</span>
            </div>
            <div class="summary-row">
                <span>Phí vận chuyển:</span>
                <span style="color: #28a745;">Miễn phí</span>
            </div>
            
            <div class="summary-total">
                <span>Tổng cộng:</span>
                <span class="total-price"><?php echo number_format($total_bill); ?>đ</span>
            </div>

            <button type="submit" form="checkoutForm" class="btn-checkout">
                ĐẶT HÀNG NGAY
            </button>
            
            <div style="text-align: center; margin-top: 15px; font-size: 13px; color: #888;">
                <i class="fas fa-shield-alt"></i> Thông tin được bảo mật tuyệt đối
            </div>
        </div>
    </div>
</div>

<script>
    function selectPayment(element) {
        // Xóa class selected ở tất cả card
        document.querySelectorAll('.payment-card').forEach(card => card.classList.remove('selected'));
        // Thêm class selected vào card được chọn
        element.classList.add('selected');
    }
</script>

<?php include 'footer.php'; ?>