<?php
require_once '../config.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quản lý sản phẩm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .admin-pagination { display: flex; justify-content: center; margin-top: 20px; gap: 5px; padding-bottom: 20px; }
        .p-btn { display: inline-flex; justify-content: center; align-items: center; min-width: 35px; height: 35px; padding: 0 10px; border: 1px solid #ddd; background: #fff; color: #333; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px; transition: all 0.3s; }
        .p-btn:hover { background: #f1f1f1; border-color: #bbb; }
        .p-btn.active { background: #007bff; color: #fff; border-color: #007bff; }
        
        /* Badge tồn kho */
        .stock-tag { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
        .stock-ok { background: #e6fffa; color: #00796b; border: 1px solid #b2f5ea; } /* Còn hàng */
        .stock-low { background: #fffaf0; color: #dd6b20; border: 1px solid #fbd38d; } /* Sắp hết */
        .stock-out { background: #fff5f5; color: #c53030; border: 1px solid #feb2b2; } /* Hết hàng */
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <div class="sidebar">
            <div class="sidebar-header"><h3>Admin Panel</h3></div>
            <ul class="sidebar-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="products.php" class="active"><i class="fas fa-box"></i> Sản phẩm</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Khách hàng</a></li>
                <li><a href="../index.php" target="_blank"><i class="fas fa-sign-out-alt"></i> Xem Website</a></li>
            </ul>
        </div>

        <div class="main-content">
            <h2 class="page-title">Danh sách sản phẩm</h2>
            <a href="product_add.php" class="btn-add"><i class="fas fa-plus"></i> Thêm mới</a>
            
            <?php
            // 1. Phân trang
            $limit = 10;
            $page = isset($_GET['page']) && $_GET['page'] > 0 ? intval($_GET['page']) : 1;
            $offset = ($page - 1) * $limit;

            $sql_count = "SELECT COUNT(*) as total FROM tbl_inventory";
            $total_records = $conn->query($sql_count)->fetchColumn();
            $total_pages = ceil($total_records / $limit);

            // 2. Lấy dữ liệu KÈM TỔNG TỒN KHO (Subquery)
            // (SELECT SUM(stock_quantity) ...) dùng để cộng dồn số lượng từ bảng biến thể
            $sql = "SELECT p.*, 
                   (SELECT SUM(stock_quantity) FROM tbl_product_variants v WHERE v.product_id = p.product_id) as total_stock 
                    FROM tbl_inventory p 
                    ORDER BY p.product_id DESC 
                    LIMIT $offset, $limit";
            
            $stmt = $conn->query($sql);
            ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th>Giá bán</th>
                            <th>Tổng tồn kho</th> <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($stmt->rowCount() > 0):
                            while($row = $stmt->fetch()):
                                // Xử lý hiển thị tồn kho
                                $stock = intval($row['total_stock']); // Nếu null thì về 0
                                $stock_class = 'stock-ok';
                                if($stock == 0) $stock_class = 'stock-out';
                                elseif($stock < 10) $stock_class = 'stock-low';
                        ?>
                        <tr>
                            <td><?php echo $row['product_id']; ?></td>
                            <td>
                                <img src="../uploads/<?php echo $row['thumbnail']; ?>" width="50" style="border-radius:4px;">
                            </td>
                            <td>
                                <b><?php echo $row['product_name']; ?></b>
                            </td>
                            <td>
                                <?php if($row['sale_price'] > 0): ?>
                                    <span style="color:red; font-weight:bold;"><?php echo number_format($row['sale_price']); ?>đ</span>
                                    <br><del style="font-size:12px; color:#999;"><?php echo number_format($row['price']); ?>đ</del>
                                <?php else: ?>
                                    <?php echo number_format($row['price']); ?>đ
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="stock-tag <?php echo $stock_class; ?>">
                                    <?php echo $stock; ?> đôi
                                </span>
                            </td>
                            <td>
                                <a href="product_variants.php?id=<?php echo $row['product_id']; ?>" class="btn-action" style="background: #17a2b8; color:white;" title="Quản lý Kho">
                                    <i class="fas fa-boxes"></i>
                                </a>
                                <a href="product_edit.php?id=<?php echo $row['product_id']; ?>" class="btn-action btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="product_delete.php?id=<?php echo $row['product_id']; ?>" class="btn-action btn-delete" onclick="return confirm('Xóa nhé?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                            echo "<tr><td colspan='6' style='text-align:center'>Chưa có sản phẩm nào.</td></tr>";
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>

            <?php if($total_pages > 1): ?>
            <div class="admin-pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="p-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>