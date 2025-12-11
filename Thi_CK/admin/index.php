<?php
// Lưu ý: Đường dẫn ../config.php để lùi ra ngoài folder admin
require_once '../config.php'; 

// --- 1. CHUYỂN ĐỔI: LẤY THỐNG KÊ BẰNG PDO ---

// Đếm tổng sản phẩm
// fetchColumn() lấy ngay giá trị của cột đầu tiên, rất tiện cho lệnh COUNT hoặc SUM
$count_pro = $conn->query("SELECT COUNT(*) FROM tbl_inventory")->fetchColumn();

// Đếm tổng đơn hàng
$count_order = $conn->query("SELECT COUNT(*) FROM tbl_orders")->fetchColumn();

// Tính tổng doanh thu
$revenue = $conn->query("SELECT SUM(total_amount) FROM tbl_orders")->fetchColumn();
// Nếu chưa có doanh thu (null) thì gán bằng 0
$revenue = $revenue ? $revenue : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Admin Panel</h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-box"></i> Sản phẩm</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
                <li><a href="users.php"><i class="fas fa-shopping-cart"></i> Khách hàng</a></li>
                <li><a href="../index.php" target="_blank"><i class="fas fa-sign-out-alt"></i> Xem Website</a></li>
            </ul>
        </div>

        <div class="main-content">
            <h2 class="page-title">Tổng quan</h2>
            
            <div class="dashboard-cards">
                <div class="card bg-blue">
                    <div class="card-info">
                        <h3><?php echo $count_pro; ?></h3>
                        <span>Tổng sản phẩm</span>
                    </div>
                    <div class="card-icon"><i class="fas fa-box"></i></div>
                </div>
                <div class="card bg-green">
                    <div class="card-info">
                        <h3><?php echo number_format($revenue, 0, ',', '.'); ?>đ</h3>
                        <span>Doanh thu</span>
                    </div>
                    <div class="card-icon"><i class="fas fa-money-bill-wave"></i></div>
                </div>
                <div class="card bg-orange">
                    <div class="card-info">
                        <h3><?php echo $count_order; ?></h3>
                        <span>Đơn hàng</span>
                    </div>
                    <div class="card-icon"><i class="fas fa-shopping-bag"></i></div>
                </div>
                 <div class="card bg-red">
                    <div class="card-info">
                        <?php 
                        // Ví dụ đếm khách hàng bằng PDO luôn cho đủ bộ
                        $count_cust = $conn->query("SELECT COUNT(*) FROM tbl_customers")->fetchColumn();
                        ?>
                        <h3><?php echo $count_cust; ?></h3>
                        <span>Khách hàng</span>
                    </div>
                    <div class="card-icon"><i class="fas fa-users"></i></div>
                </div>
            </div>

            <div class="table-container">
                <h3>Đơn hàng mới nhất</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Mã ĐH</th>
                            <th>Khách hàng</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày đặt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql_new_orders = "SELECT * FROM tbl_orders ORDER BY order_id DESC LIMIT 5";
                        $stmt_orders = $conn->query($sql_new_orders);

                        if($stmt_orders->rowCount() > 0){
                            while($row = $stmt_orders->fetch()){
                                // --- THÊM ĐOẠN XỬ LÝ MÀU SẮC & DỊCH TIẾNG VIỆT ---
                                $status_text = '';
                                $status_color = '#eee'; // Màu nền mặc định
                                $text_color = '#333';   // Màu chữ mặc định

                                switch($row['status']){
                                    case 'pending': 
                                        $status_text = 'Chờ xử lý'; 
                                        $status_color = '#ffc107'; // Vàng
                                        break;
                                    case 'processing': 
                                        $status_text = 'Đang giao'; 
                                        $status_color = '#17a2b8'; // Xanh dương
                                        $text_color = '#fff';
                                        break;
                                    case 'completed': 
                                        $status_text = 'Hoàn thành'; 
                                        $status_color = '#28a745'; // Xanh lá
                                        $text_color = '#fff';
                                        break;
                                    case 'cancelled': 
                                        $status_text = 'Đã hủy'; 
                                        $status_color = '#dc3545'; // Đỏ
                                        $text_color = '#fff';
                                        break;
                                    default: 
                                        $status_text = $row['status'];
                                }
                                // --------------------------------------------------

                                echo "<tr>
                                    <td>#".$row['order_id']."</td>
                                    <td>Khách ID: ".$row['customer_id']."</td> 
                                    <td>".number_format($row['total_amount'])."đ</td>
                                    <td>
                                        <span style='padding:5px 10px; background: $status_color; color: $text_color; border-radius:15px; font-size:12px; font-weight:bold;'>
                                            $status_text
                                        </span>
                                    </td>
                                    <td>".date('d/m/Y', strtotime($row['created_at']))."</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center'>Chưa có đơn hàng nào</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>