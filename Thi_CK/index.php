<?php include 'header.php'; // Đã bao gồm config.php (PDO) ?>


<style>

    .pagination-container {

        display: flex !important;

        justify-content: center !important;

        align-items: center !important;

        width: 100% !important;

        margin: 40px 0 60px 0 !important;

    }

    .pagination {

        display: flex !important;

        gap: 10px !important;

        background: #fff !important;

        padding: 10px 20px !important;

        border-radius: 50px !important;

        box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important;

    }

    .page-link {

        display: flex !important;

        justify-content: center !important;

        align-items: center !important;

        width: 45px !important;

        height: 45px !important;

        border-radius: 50% !important;

        text-decoration: none !important;

        color: #333 !important;

        background-color: #f8f8f8 !important;

        font-weight: bold !important;

        border: 1px solid #ddd !important;

        transition: all 0.3s ease !important;

    }

    .page-link:hover:not(.disabled):not(.dots) {

        background-color: #F15A22 !important;

        color: #fff !important;

        transform: translateY(-3px);

        box-shadow: 0 5px 10px rgba(241, 90, 34, 0.3);

        border-color: #F15A22 !important;

    }

    .page-link.active {

        background-color: #F15A22 !important;

        color: #fff;

        border-color: #F15A22 !important;

        pointer-events: none;

    }

    .page-link.disabled {

        background-color: #eee !important;

        color: #bbb !important;

        cursor: not-allowed !important;

    }

</style>


<div class="hero-banner">

    <img src="images/banner.png" alt="Banner">

</div>


<div class="container main-content">

    <aside class="sidebar">

        <div class="filter-widget">

            <h3>Danh mục</h3>

            <ul>

                <?php

                // --- 1. CHUYỂN ĐỔI PDO: LẤY DANH MỤC ---

                $stmt_cat = $conn->query("SELECT * FROM tbl_categories");

                while($c = $stmt_cat->fetch()){

                    echo "<li class='filter-item'><a href='?cat_id=".$c['cat_id']."'>".$c['cat_name']."</a></li>";

                }

                ?>

            </ul>

        </div>

        <div class="filter-widget">

            <h3>Khoảng giá</h3>

            <div class="filter-item"><a href="?price=1">Dưới 500k</a></div>

            <div class="filter-item"><a href="?price=2">500k - 1 triệu</a></div>

            <div class="filter-item"><a href="?price=3">Trên 1 triệu</a></div>

        </div>

    </aside>


    <section class="product-list">

        <div class="products-grid">

            <?php

            // --- 2. LOGIC LỌC VÀ PHÂN TRANG (PDO) ---

           

            $where = "1=1";

            $param_link = ""; // Biến này để giữ lại các tham số khi chuyển trang


            // Lọc theo Danh mục (Dùng intval để an toàn)

            if(isset($_GET['cat_id'])) {

                $cat_id = intval($_GET['cat_id']);

                $where .= " AND cat_id = $cat_id";

                $param_link .= "&cat_id=$cat_id";

            }

           

            // Lọc theo Giá

            if(isset($_GET['price'])) {

                $p = intval($_GET['price']);

                if($p == 1) $where .= " AND price < 500000";

                if($p == 2) $where .= " AND price BETWEEN 500000 AND 1000000";

                if($p == 3) $where .= " AND price > 1000000"; // Sửa lại logic > 1 triệu cho đúng

                $param_link .= "&price=$p";

            }

           

            // Lọc theo Từ khóa (Dùng quote để chống hack thay cho mysqli_real_escape_string)

            if(isset($_GET['keyword'])) {

                $key = $_GET['keyword'];

                // Hàm quote() sẽ tự động thêm dấu nháy đơn bao quanh chuỗi và xử lý ký tự đặc biệt

                $quoted_key = $conn->quote('%' . $key . '%');

                $where .= " AND product_name LIKE $quoted_key";

                $param_link .= "&keyword=".urlencode($key);

            }

           

            // Cấu hình phân trang

            $limit = 6;

            $page = isset($_GET['page']) && $_GET['page'] > 0 ? intval($_GET['page']) : 1;

            $offset = ($page - 1) * $limit;


            // --- 3. TÍNH TỔNG SỐ SẢN PHẨM (PDO) ---

            // fetchColumn() lấy ngay giá trị đếm được

            $sql_count = "SELECT COUNT(*) as total FROM tbl_inventory WHERE $where";

            $total_records = $conn->query($sql_count)->fetchColumn();

            $total_pages = ceil($total_records / $limit);

           

            // --- 4. LẤY DỮ LIỆU SẢN PHẨM (PDO) ---

            $sql = "SELECT * FROM tbl_inventory WHERE $where ORDER BY product_id DESC LIMIT $offset, $limit";

            $stmt = $conn->query($sql);

           

            // Kiểm tra có dữ liệu không

            if($stmt->rowCount() > 0):

                while($row = $stmt->fetch()):

                    // Tính phần trăm giảm giá

                    $percent = 0;

                    if($row['sale_price'] > 0 && $row['price'] > 0) {

                        $percent = round((($row['price'] - $row['sale_price']) / $row['price']) * 100);

                    }

                    $display_price = $row['sale_price'] > 0 ? $row['sale_price'] : $row['price'];

            ?>

            <div class="product-card">

                <div class="product-img">

                    <?php if($percent > 0): ?>

                        <span class="discount-badge">-<?php echo $percent; ?>%</span>

                    <?php endif; ?>

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

                endwhile;

            else:

                echo "<p style='width:100%; text-align:center; padding:20px;'>Không tìm thấy sản phẩm nào.</p>";

            endif;

            ?>

        </div>


        <?php if($total_pages > 1): ?>

            <div class="pagination-container">

                <div class="pagination">

                    <?php if ($page > 1): ?>

                        <a href="?page=<?php echo ($page - 1) . $param_link; ?>" class="page-link prev">

                            <i class="fas fa-chevron-left"></i>

                        </a>

                    <?php else: ?>

                        <span class="page-link disabled"><i class="fas fa-chevron-left"></i></span>

                    <?php endif; ?>


                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>

                        <?php

                        if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)):

                        ?>

                            <a href="?page=<?php echo $i . $param_link; ?>"

                            class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">

                            <?php echo $i; ?>

                            </a>

                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>

                            <span class="page-link dots">...</span>

                        <?php endif; ?>

                    <?php endfor; ?>


                    <?php if ($page < $total_pages): ?>

                        <a href="?page=<?php echo ($page + 1) . $param_link; ?>" class="page-link next">

                            <i class="fas fa-chevron-right"></i>

                        </a>

                    <?php else: ?>

                        <span class="page-link disabled"><i class="fas fa-chevron-right"></i></span>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>

    </section>

</div>


<?php include 'footer.php'; ?> 