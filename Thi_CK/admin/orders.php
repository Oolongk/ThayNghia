<?php
require_once '../config.php'; // Đã kết nối PDO
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý đơn hàng</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; color: #fff; }
        .status-pending { background: #ffc107; color: #333; } /* Chờ xử lý */
        .status-processing { background: #17a2b8; } /* Đang xử lý */
        .status-completed { background: #28a745; } /* Hoàn thành */
        .status-cancelled { background: #dc3545; } /* Đã hủy */
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <div class="sidebar">
            <div class="sidebar-header"><h3>Admin Panel</h3></div>
            <ul class="sidebar-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-box"></i> Sản phẩm</a></li>
                <li><a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Khách hàng</a></li>
                <li><a href="../index.php" target="_blank"><i class="fas fa-sign-out-alt"></i> Xem Website</a></li>
            </ul>
        </div>

        <div class="main-content">
            <h2 class="page-title">Danh sách đơn hàng</h2>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Mã ĐH</th>
                            <th>Khách hàng</th>
                            <th>Ngày đặt</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Join bảng Orders với Customers để lấy tên khách
                        $sql = "SELECT o.*, c.full_name, c.email 
                                FROM tbl_orders o 
                                JOIN tbl_customers c ON o.customer_id = c.customer_id 
                                ORDER BY o.created_at DESC";
                        
                        // --- CHUYỂN ĐỔI PDO ---
                        $stmt = $conn->query($sql);

                        // Kiểm tra số dòng
                        if($stmt->rowCount() > 0):
                            // Lặp dữ liệu
                            while($row = $stmt->fetch()):
                                // Xử lý màu sắc trạng thái
                                $status_class = 'status-' . $row['status'];
                                $status_text = '';
                                switch($row['status']){
                                    case 'pending': $status_text = 'Chờ xử lý'; break;
                                    case 'processing': $status_text = 'Đang giao'; break;
                                    case 'completed': $status_text = 'Hoàn thành'; break;
                                    case 'cancelled': $status_text = 'Đã hủy'; break;
                                    default: $status_text = $row['status']; break;
                                }
                        ?>
                        <tr>
                            <td>#<?php echo $row['order_id']; ?></td>
                            <td>
                                <b><?php echo $row['full_name']; ?></b><br>
                                <small><?php echo $row['email']; ?></small>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                            <td style="color: red; font-weight: bold;"><?php echo number_format($row['total_amount']); ?>đ</td>
                            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            <td>
                                <a href="order_details.php?id=<?php echo $row['order_id']; ?>" class="btn-action btn-edit" style="background: #333;">
                                    <i class="fas fa-eye"></i> Xem
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; 
                        else: echo "<tr><td colspan='6' style='text-align:center'>Chưa có đơn hàng nào!</td></tr>";
                        endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>