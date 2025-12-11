<?php 
include 'header.php'; // Đã bao gồm config.php (PDO)

// 1. Lấy ID từ URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// --- PDO: LẤY THÔNG TIN SẢN PHẨM ---
$stmt = $conn->prepare("SELECT * FROM tbl_inventory WHERE product_id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

// Kiểm tra tồn tại
if (!$product) {
    echo "<div class='container' style='margin-top: 60px; text-align: center; padding: 50px; background: #f9f9f9; border-radius: 8px;'>
            <i class='fas fa-box-open' style='font-size: 50px; color: #ccc; margin-bottom: 20px;'></i>
            <h2>Sản phẩm không tồn tại hoặc đã bị xóa!</h2>
            <a href='index.php' class='btn-primary' style='margin-top: 20px; display:inline-block; text-decoration: none;'>Quay về trang chủ</a>
          </div>";
    include 'footer.php';
    exit();
}

// --- PDO: LẤY DANH SÁCH SIZE ---
$stmt_variants = $conn->prepare("SELECT * FROM tbl_product_variants WHERE product_id = :id AND stock_quantity > 0 ORDER BY size ASC");
$stmt_variants->execute([':id' => $id]);
$has_stock = $stmt_variants->rowCount() > 0;
?>

<style>
    /* Bố cục Grid 2 cột */
    .pd-container {
        display: grid;
        grid-template-columns: 1fr 1fr; /* Chia đôi màn hình */
        gap: 40px;
        margin-top: 40px;
        margin-bottom: 60px;
        background: #fff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }

    /* Cột ảnh */
    .pd-image-box {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #f0f0f0;
    }
    .pd-image-box img {
        width: 100%;
        height: auto;
        display: block;
        transition: transform 0.3s;
    }
    .pd-image-box:hover img {
        transform: scale(1.05); /* Zoom nhẹ khi di chuột */
    }

    /* Cột thông tin */
    .pd-title { font-size: 28px; font-weight: 700; margin-bottom: 15px; color: #333; }
    
    /* Hộp giá */
    .pd-price-box {
        background: #fafafa;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .pd-price-current { font-size: 32px; color: var(--primary-color); font-weight: 700; }
    .pd-price-old { font-size: 18px; color: #999; text-decoration: line-through; }
    .pd-discount-tag { background: var(--primary-color); color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }

    /* Form chọn hàng */
    .pd-form-group { margin-bottom: 25px; }
    .pd-label { font-weight: 600; display: block; margin-bottom: 10px; color: #555; }

    /* Nút chọn Size đẹp */
    .size-selector { display: flex; gap: 10px; flex-wrap: wrap; }
    .size-selector input { display: none; }
    .size-option {
        border: 2px solid #e0e0e0;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        color: #555;
        min-width: 60px;
        text-align: center;
    }
    .size-selector input:checked + .size-option {
        border-color: var(--primary-color);
        color: var(--primary-color);
        background: rgba(241, 90, 34, 0.05); /* Màu cam nhạt */
    }
    .size-selector .size-option:hover { border-color: var(--primary-color); }

    /* Ô chọn số lượng */
    .qty-input {
        width: 80px; padding: 10px; border: 2px solid #e0e0e0; 
        border-radius: 6px; font-weight: 600; text-align: center; font-size: 16px;
    }

    /* Các nút hành động (CTA) */
    .pd-actions { display: flex; gap: 15px; margin-top: 30px; }
    .btn-cta {
        flex: 1; /* Hai nút bằng nhau */
        padding: 15px; font-size: 16px; font-weight: 700;
        border-radius: 6px; cursor: pointer; transition: 0.3s;
        display: flex; justify-content: center; align-items: center; gap: 10px;
        text-transform: uppercase;
        border: 2px solid transparent;
    }
    /* Nút Thêm vào giỏ (Viền cam) */
    .btn-add-cart {
        background: #fff; color: var(--primary-color); border-color: var(--primary-color);
    }
    .btn-add-cart:hover { background: #fff5f0; }
    
    /* Nút Mua ngay (Nền cam) */
    .btn-buy-now {
        background: var(--primary-color); color: #fff; border-color: var(--primary-color);
    }
    .btn-buy-now:hover { background: #d1400b; border-color: #d1400b; }

    /* Nút Hết hàng */
    .btn-disabled {
        background: #eee; color: #999; border-color: #eee; cursor: not-allowed;
    }

    /* Phần mô tả bên dưới */
    .pd-description {
        margin-top: 50px;
        padding-top: 30px;
        border-top: 1px solid #eee;
    }
    .pd-desc-title { font-size: 20px; font-weight: 700; margin-bottom: 20px; position: relative; display: inline-block; }
    .pd-desc-title::after { content: ''; position: absolute; bottom: -5px; left: 0; width: 50px; height: 3px; background: var(--primary-color); }
    .pd-desc-content { line-height: 1.8; color: #555; font-size: 15px; }
    
    /* Responsive Mobile */
    @media (max-width: 768px) {
        .pd-container { grid-template-columns: 1fr; gap: 20px; padding: 20px; }
        .pd-title { font-size: 24px; }
        .pd-actions { flex-direction: column; }
    }
</style>

<div class="container">
    <div class="pd-container">
        <div class="pd-image-box">
            <img src="uploads/<?php echo $product['thumbnail']; ?>" alt="<?php echo $product['product_name']; ?>">
        </div>

        <div class="pd-info-box">
            <h1 class="pd-title"><?php echo $product['product_name']; ?></h1>
            
            <?php 
                $price = $product['price'];
                $sale_price = $product['sale_price'];
                $current_price = ($sale_price > 0) ? $sale_price : $price;
                $percent = 0;
                if($sale_price > 0 && $price > 0) {
                    $percent = round((($price - $sale_price) / $price) * 100);
                }
            ?>

            <div class="pd-price-box">
                <span class="pd-price-current"><?php echo number_format($current_price, 0, ',', '.'); ?>đ</span>
                <?php if($sale_price > 0): ?>
                    <span class="pd-price-old"><?php echo number_format($price, 0, ',', '.'); ?>đ</span>
                    <span class="pd-discount-tag">-<?php echo $percent; ?>%</span>
                <?php endif; ?>
            </div>

            <form action="add_to_cart.php" method="POST">
                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">

                <div class="pd-form-group">
                    <span class="pd-label">Chọn kích thước (Size): <?php if(!$has_stock) echo "<span style='color:red'>(Hết hàng)</span>"; ?></span>
                    <div class="size-selector">
                        <?php 
                        if ($has_stock):
                            while($v = $stmt_variants->fetch()): 
                        ?>
                            <label>
                                <input type="radio" name="variant_id" value="<?php echo $v['variant_id']; ?>" required>
                                <span class="size-option"><?php echo $v['size']; ?></span>
                            </label>
                        <?php 
                            endwhile; 
                        endif;
                        ?>
                    </div>
                </div>

                <?php if ($has_stock): ?>
                <div class="pd-form-group">
                    <span class="pd-label">Số lượng:</span>
                    <input type="number" name="quantity" class="qty-input" value="1" min="1" max="10">
                </div>
                <?php endif; ?>

                <div class="pd-actions">
                    <?php if ($has_stock): ?>
                        <button type="submit" class="btn-cta btn-add-cart">
                            <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                        </button>
                        
                        <button type="submit" class="btn-cta btn-buy-now">
                            Mua ngay
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn-cta btn-disabled" disabled>
                            TẠM HẾT HÀNG
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="pd-description">
        <h3 class="pd-desc-title">Mô tả sản phẩm</h3>
        <div class="pd-desc-content">
            <?php echo nl2br($product['description']); ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>