<?php
require_once '../config.php'; // Đảm bảo config.php đã dùng PDO

// Xử lý khi bấm nút Lưu
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $sale_price = $_POST['sale_price'];
    $cat_id = $_POST['cat_id'];
    $desc = $_POST['description'];

    // --- 1. XỬ LÝ UPLOAD ẢNH (Giữ nguyên logic của bạn) ---
    $thumbnail = "";
    
    if (isset($_FILES['image']) && $_FILES['image']['name'] != "") {
        $target_dir = "../uploads/";

        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $new_name = time() . "_" . rand(1000, 9999) . "." . $imageFileType;
        $target_file = $target_dir . $new_name;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $thumbnail = $new_name; 
        } else {
            $error = "Lỗi: Không thể lưu file vào thư mục uploads.";
        }
    }

    // --- 2. THAY ĐỔI: INSERT DỮ LIỆU BẰNG PDO ---
    if (!isset($error)) {
        try {
            // A. Chuẩn bị câu lệnh SQL với các tham số :name, :price... (Placeholder)
            $sql = "INSERT INTO tbl_inventory (product_name, cat_id, price, sale_price, thumbnail, description) 
                    VALUES (:name, :cat_id, :price, :sale_price, :thumbnail, :desc)";
            
            // B. Chuẩn bị statement
            $stmt = $conn->prepare($sql);

            // C. Gán giá trị và thực thi (An toàn tuyệt đối)
            $stmt->execute([
                ':name'       => $name,
                ':cat_id'     => $cat_id,
                ':price'      => $price,
                ':sale_price' => $sale_price,
                ':thumbnail'  => $thumbnail,
                ':desc'       => $desc
            ]);

            // D. Lấy ID vừa tạo (Thay vì mysqli_insert_id)
            $new_id = $conn->lastInsertId();
            
            // E. Thêm Size mặc định (Dùng query thường cho nhanh vì dữ liệu cứng)
            $conn->query("INSERT INTO tbl_product_variants (product_id, size, stock_quantity) VALUES ($new_id, '39', 10)");
            $conn->query("INSERT INTO tbl_product_variants (product_id, size, stock_quantity) VALUES ($new_id, '40', 10)");
            
            // Chuyển hướng
            header("Location: products.php"); 
            exit();

        } catch (PDOException $e) {
            // Bắt lỗi Database
            $error = "Lỗi Database: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm sản phẩm mới</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .form-container { background: #fff; padding: 30px; border-radius: 5px; max-width: 800px; margin: 0 auto; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        textarea.form-control { height: 100px; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <div class="sidebar">
            <div class="sidebar-header"><h3>Admin Panel</h3></div>
            <ul class="sidebar-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="products.php" class="active"><i class="fas fa-box"></i> Sản phẩm</a></li>
                <li><a href="../index.php"><i class="fas fa-sign-out-alt"></i> Xem Website</a></li>
            </ul>
        </div>

        <div class="main-content">
            <h2 class="page-title">Thêm sản phẩm mới</h2>
            
            <div class="form-container">
                <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
                
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Tên sản phẩm</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="width: 50%;">
                            <label>Giá gốc (VNĐ)</label>
                            <input type="number" name="price" class="form-control" required>
                        </div>
                        <div class="form-group" style="width: 50%;">
                            <label>Giá khuyến mãi (VNĐ)</label>
                            <input type="number" name="sale_price" class="form-control" value="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Danh mục</label>
                        <select name="cat_id" class="form-control">
                            <?php
                            // --- 3. THAY ĐỔI: SELECT DANH MỤC BẰNG PDO ---
                            // Thay mysqli_query bằng $conn->query
                            $stmt_cats = $conn->query("SELECT * FROM tbl_categories");
                            
                            // Thay mysqli_fetch_assoc bằng fetch()
                            while($c = $stmt_cats->fetch()){
                                echo "<option value='".$c['cat_id']."'>".$c['cat_name']."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Ảnh đại diện</label>
                        <input type="file" name="image" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Mô tả chi tiết</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>

                    <button type="submit" class="btn-add">Lưu sản phẩm</button>
                    <a href="products.php" class="btn-delete" style="padding: 11px 15px; display:inline-block;">Hủy</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>