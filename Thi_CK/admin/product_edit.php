<?php
require_once '../config.php'; // Kết nối PDO

// 1. Lấy ID sản phẩm cần sửa
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // --- CHUYỂN ĐỔI PDO: LẤY SẢN PHẨM ---
    $stmt = $conn->prepare("SELECT * FROM tbl_inventory WHERE product_id = :id");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch();
    
    // Nếu không tìm thấy sản phẩm thì quay về
    if (!$product) {
        header("Location: products.php");
        exit();
    }
} else {
    header("Location: products.php");
    exit();
}

// 2. Xử lý khi bấm nút Cập nhật
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $sale_price = $_POST['sale_price'];
    $cat_id = $_POST['cat_id'];
    $desc = $_POST['description'];
    
    // Mặc định giữ nguyên tên ảnh cũ
    $thumbnail = $product['thumbnail'];

    // Nếu người dùng CÓ chọn ảnh mới (Logic giữ nguyên)
    if (isset($_FILES['image']) && $_FILES['image']['name'] != "") {
        $target_dir = "../uploads/";
        
        // Tạo folder nếu chưa có
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

        $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $new_name = time() . "_" . rand(1000, 9999) . "." . $imageFileType;
        $target_file = $target_dir . $new_name;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Xóa ảnh cũ đi cho đỡ rác
            if (file_exists("../uploads/" . $product['thumbnail']) && $product['thumbnail'] != "") {
                unlink("../uploads/" . $product['thumbnail']);
            }
            $thumbnail = $new_name; // Cập nhật tên ảnh mới
        }
    }

    // --- CHUYỂN ĐỔI PDO: UPDATE DỮ LIỆU ---
    try {
        $sql = "UPDATE tbl_inventory SET 
                product_name = :name, 
                cat_id = :cat_id, 
                price = :price, 
                sale_price = :sale_price, 
                thumbnail = :thumbnail, 
                description = :desc 
                WHERE product_id = :id";
        
        $stmt_update = $conn->prepare($sql);
        
        $stmt_update->execute([
            ':name'       => $name,
            ':cat_id'     => $cat_id,
            ':price'      => $price,
            ':sale_price' => $sale_price,
            ':thumbnail'  => $thumbnail,
            ':desc'       => $desc,
            ':id'         => $id
        ]);

        header("Location: products.php?msg=updated");
        exit();

    } catch (PDOException $e) {
        $error = "Lỗi Database: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa sản phẩm</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .form-container { background: #fff; padding: 30px; border-radius: 5px; max-width: 800px; margin: 0 auto; }
        .form-control { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; }
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
            <h2 class="page-title">Sửa sản phẩm: #<?php echo $id; ?></h2>
            
            <div class="form-container">
                <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
                
                <form action="" method="POST" enctype="multipart/form-data">
                    <label>Tên sản phẩm</label>
                    <input type="text" name="name" class="form-control" value="<?php echo $product['product_name']; ?>" required>

                    <div style="display: flex; gap: 20px;">
                        <div style="width: 50%;">
                            <label>Giá gốc</label>
                            <input type="number" name="price" class="form-control" value="<?php echo $product['price']; ?>" required>
                        </div>
                        <div style="width: 50%;">
                            <label>Giá Sale</label>
                            <input type="number" name="sale_price" class="form-control" value="<?php echo $product['sale_price']; ?>">
                        </div>
                    </div>

                    <label>Danh mục</label>
                    <select name="cat_id" class="form-control">
                        <?php
                        // --- CHUYỂN ĐỔI PDO: SELECT DANH MỤC ---
                        $stmt_cats = $conn->query("SELECT * FROM tbl_categories");
                        while($c = $stmt_cats->fetch()){
                            $selected = ($c['cat_id'] == $product['cat_id']) ? "selected" : "";
                            echo "<option value='".$c['cat_id']."' $selected>".$c['cat_name']."</option>";
                        }
                        ?>
                    </select>

                    <label>Ảnh đại diện</label>
                    <div style="margin-bottom: 10px;">
                        <img src="../uploads/<?php echo $product['thumbnail']; ?>" height="100" style="border: 1px solid #ddd; padding: 2px;">
                    </div>
                    <input type="file" name="image" class="form-control">
                    <small>Để trống nếu không muốn thay đổi ảnh.</small>

                    <label style="margin-top: 15px;">Mô tả</label>
                    <textarea name="description" class="form-control" style="height: 100px;"><?php echo $product['description']; ?></textarea>

                    <button type="submit" class="btn-add">Cập nhật</button>
                    <a href="products.php" class="btn-delete" style="padding: 11px 15px; display:inline-block;">Hủy</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>