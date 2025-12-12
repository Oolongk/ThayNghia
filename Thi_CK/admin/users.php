<?php
// 1. Kết nối Database (PDO)
require_once '../config.php'; 
require_once 'auth_check.php';

// 2. Xử lý Xóa User
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
      //Kiểm tra không cho xóa chính mình hoặc admin (nếu có logic role)
      //Ở đây mình xóa khách hàng dựa trên ID
    if ($delete_id > 0) { 
        try {
            $sql = "DELETE FROM tbl_customers WHERE customer_id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id' => $delete_id]);
            
            $success_message = "Đã xóa khách hàng có ID #$delete_id.";
        } catch (PDOException $e) {
            $error_message = "Lỗi: Không thể xóa (Có thể khách hàng này đang có đơn hàng).";
        }
    }
}

// 3. Lấy danh sách User (Có thể thêm phân trang nếu muốn, ở đây mình hiện hết)
// Sắp xếp người mới đăng ký lên đầu
$sql = "SELECT * FROM tbl_customers ORDER BY customer_id DESC";
$stmt = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý người dùng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        /* CSS riêng cho bảng user nếu cần */
        .msg-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .msg-error { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Admin Panel</h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-box"></i> Sản phẩm</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
                <li><a href="users.php" class="active"><i class="fas fa-users"></i> Người dùng</a></li>
                <li><a href="../index.php" target="_blank"><i class="fas fa-sign-out-alt"></i> Xem Website</a></li>
            </ul>
        </div>

        <div class="main-content">
            <h2 class="page-title">Quản lý Khách hàng</h2>

            <?php if (isset($success_message)): ?>
                <div class="msg-success"><?= $success_message ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="msg-error"><?= $error_message ?></div>
            <?php endif; ?>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Họ và tên</th>
                            <th>Email</th>
                            <th>Số điện thoại</th>
                            <th>Địa chỉ</th>
                            <th>Ngày đăng ký</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($stmt->rowCount() > 0):
                            while ($row = $stmt->fetch()): 
                        ?>
                        <tr>
                            <td>#<?= $row['customer_id'] ?></td>
                            <td>
                                <b><?= htmlspecialchars($row['full_name']) ?></b>
                            </td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['phone']) ?></td>
                            <td>
                                <span style="font-size: 13px; color: #555;">
                                    <?= htmlspecialchars(substr($row['address'], 0, 30)) ?>...
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <a href='?delete=<?= $row['customer_id'] ?>' 
                                   onclick="return confirm('CẢNH BÁO: Xóa khách hàng này có thể ảnh hưởng đến lịch sử đơn hàng.\nBạn có chắc chắn muốn xóa?')" 
                                   class='btn-action btn-delete'>
                                   <i class="fas fa-trash"></i> Xóa
                                </a>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                            echo "<tr><td colspan='7' style='text-align:center'>Chưa có người dùng nào.</td></tr>";
                        endif; 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>