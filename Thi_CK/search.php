<?php include 'header.php'; // Đã bao gồm config.php (PDO) ?>

<div class="container main-content" style="display: block;">
    <h2 style="margin-bottom: 20px;">
        Kết quả tìm kiếm cho: "<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>"
    </h2>

    <div class="products-grid">
        <?php
        if (isset($_GET['keyword']) && trim($_GET['keyword']) != '') {
            $key = $_GET['keyword'];
            
            // --- CHUYỂN ĐỔI PDO: TÌM KIẾM AN TOÀN ---
            
            // 1. Chuẩn bị câu lệnh với placeholder :keyword
            $sql = "SELECT * FROM tbl_inventory WHERE product_name LIKE :keyword";
            $stmt = $conn->prepare($sql);
            
            // 2. Tạo chuỗi tìm kiếm có dấu % (ví dụ: %nike%)
            $search_term = "%" . $key . "%";
            
            // 3. Thực thi (PDO tự động xử lý các ký tự đặc biệt)
            $stmt->execute([':keyword' => $search_term]);

            // 4. Kiểm tra số dòng và hiển thị
            if ($stmt->rowCount() > 0) {
                while ($row = $stmt->fetch()) {
                    // Logic tính toán giảm giá (Giữ nguyên)
                    $percent = 0;
                    if($row['sale_price'] > 0 && $row['price'] > 0) {
                        $percent = round((($row['price'] - $row['sale_price']) / $row['price']) * 100);
                    }
                    $display_price = $row['sale_price'] > 0 ? $row['sale_price'] : $row['price'];
                    ?>
                    
                    <div class="product-card">
                        <div class="product-img">
                            <?php if($percent > 0): ?><span class="discount-badge">-<?php echo $percent; ?>%</span><?php endif; ?>
                            <a href="product_detail.php?id=<?php echo $row['product_id']; ?>">
                                <img src="uploads/<?php echo $row['thumbnail']; ?>" alt="<?php echo $row['product_name']; ?>">
                            </a>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name">
                                <a href="product_detail.php?id=<?php echo $row['product_id']; ?>"><?php echo $row['product_name']; ?></a>
                            </h3>
                            <div class="price-box">
                                <span class="product-price"><?php echo number_format($display_price, 0, ',', '.'); ?>đ</span>
                                <?php if($percent > 0): ?>
                                    <span class="old-price"><?php echo number_format($row['price'], 0, ',', '.'); ?>đ</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<p>Không tìm thấy sản phẩm nào phù hợp với từ khóa.</p>";
            }
        } else {
            echo "<p>Vui lòng nhập từ khóa để tìm kiếm.</p>";
        }
        ?>
    </div>
</div>

<?php include 'footer.php'; ?>