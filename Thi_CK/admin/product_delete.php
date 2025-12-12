<?php
require_once '../config.php'; // Kết nối dạng PDO
require_once 'auth_check.php';

// Kiểm tra xem có ID được gửi lên không
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Chuyển về số nguyên cho an toàn

    try {
        // BƯỚC 1: Lấy tên file ảnh trước khi xóa (Để dọn dẹp thư mục uploads)
        // Chuẩn bị câu lệnh SELECT
        $stmt_img = $conn->prepare("SELECT thumbnail FROM tbl_inventory WHERE product_id = :id");
        $stmt_img->execute([':id' => $id]);
        
        // Kiểm tra xem sản phẩm có tồn tại không
        if ($stmt_img->rowCount() > 0) {
            $row = $stmt_img->fetch(); // Lấy dữ liệu
            $image_name = $row['thumbnail'];
            $file_path = "../uploads/" . $image_name;

            // Kiểm tra nếu file có tồn tại trong thư mục thì xóa nó đi
            if (!empty($image_name) && file_exists($file_path)) {
                unlink($file_path); // Hàm unlink dùng để xóa file vật lý
            }

            // BƯỚC 2: Xóa dữ liệu trong Database
            // Chuẩn bị câu lệnh DELETE
            $stmt_del = $conn->prepare("DELETE FROM tbl_inventory WHERE product_id = :id");
            
            // Thực thi lệnh xóa
            if ($stmt_del->execute([':id' => $id])) {
                // Xóa thành công thì quay về trang danh sách
                header("Location: products.php?msg=deleted");
                exit();
            } else {
                echo "Lỗi: Không thể xóa sản phẩm.";
            }
        } else {
            // Nếu không tìm thấy ID sản phẩm trong DB
            header("Location: products.php?err=notfound");
            exit();
        }

    } catch (PDOException $e) {
        // Bắt lỗi nếu có vấn đề về SQL
        echo "Lỗi Database: " . $e->getMessage();
    }

} else {
    // Không có ID thì quay về trang danh sách
    header("Location: products.php");
    exit();
}
?>