<?php
require_once '../config.php';
require_once 'auth_check.php';

// Kiểm tra ID
if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = intval($_GET['id']);

// --- 1. LẤY THÔNG TIN SẢN PHẨM (Kèm ảnh Thumbnail) ---
$stmt_p = $conn->prepare("SELECT product_name, thumbnail, price FROM tbl_inventory WHERE product_id = :id");
$stmt_p->execute([':id' => $product_id]);
$product = $stmt_p->fetch();

if(!$product){
    header("Location: products.php");
    exit();
}

// --- 2. XỬ LÝ LOGIC (Giữ nguyên logic cũ) ---

// Thêm mới
if (isset($_POST['add_variant'])) {
    $size = $_POST['size'];
    $stock = intval($_POST['stock']);
    
    $check = $conn->prepare("SELECT * FROM tbl_product_variants WHERE product_id = :pid AND size = :size");
    $check->execute([':pid' => $product_id, ':size' => $size]);
    
    if($check->rowCount() == 0) {
        $add = $conn->prepare("INSERT INTO tbl_product_variants (product_id, size, stock_quantity) VALUES (:pid, :size, :stock)");
        $add->execute([':pid' => $product_id, ':size' => $size, ':stock' => $stock]);
        $msg = "Đã thêm Size " . htmlspecialchars($size) . " thành công!";
    } else {
        $error = "Size này đã tồn tại!";
    }
}

// Cập nhật số lượng hàng loạt
if (isset($_POST['update_stock'])) {
    foreach ($_POST['stock_qty'] as $vid => $qty) {
        $upd = $conn->prepare("UPDATE tbl_product_variants SET stock_quantity = :qty WHERE variant_id = :vid");
        $upd->execute([':qty' => intval($qty), ':vid' => $vid]);
    }
    $msg = "Đã cập nhật kho hàng thành công!";
}

// Xóa biến thể
if (isset($_GET['del_variant'])) {
    $vid = intval($_GET['del_variant']);
    $del = $conn->prepare("DELETE FROM tbl_product_variants WHERE variant_id = :vid");
    $del->execute([':vid' => $vid]);
    header("Location: product_variants.php?id=$product_id&msg=deleted");
    exit();
}

// Lấy danh sách size hiện có
$stmt_v = $conn->prepare("SELECT * FROM tbl_product_variants WHERE product_id = :pid ORDER BY size ASC");
$stmt_v->execute([':pid' => $product_id]);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý kho hàng - <?php echo $product['product_name']; ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* --- CSS NÂNG CẤP GIAO DIỆN --- */
        body { background-color: #f4f6f9; }
        
        .product-header {
            display: flex; align-items: center; gap: 20px;
            background: #fff; padding: 20px; border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .product-thumb { width: 60px; height: 60px; border-radius: 5px; object-fit: cover; border: 1px solid #ddd; }
        .product-meta h2 { margin: 0; font-size: 20px; color: #333; }
        .product-meta p { margin: 5px 0 0; color: #666; font-size: 14px; }

        .variant-wrapper { display: flex; gap: 30px; align-items: flex-start; }
        
        /* Card Style */
        .v-card {
            background: #fff; border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .v-card-header {
            padding: 15px 20px; background: #f8f9fa; border-bottom: 1px solid #eee;
            font-weight: bold; text-transform: uppercase; font-size: 14px; color: #555;
            display: flex; justify-content: space-between; align-items: center;
        }

        /* Cột trái: Danh sách */
        .list-section { flex: 2; }
        
        /* Cột phải: Form thêm */
        .add-section { flex: 1; position: sticky; top: 20px; }

        /* Table Style */
        .v-table { width: 100%; border-collapse: collapse; }
        .v-table th { background: #f1f1f1; padding: 12px; text-align: center; font-size: 13px; color: #555; font-weight: 600; }
        .v-table td { padding: 12px; text-align: center; border-bottom: 1px solid #eee; vertical-align: middle; }
        .v-table tr:hover { background-color: #f9f9f9; }

        /* Input Stock */
        .stock-input {
            width: 80px; padding: 8px; text-align: center;
            border: 1px solid #ddd; border-radius: 4px;
            font-weight: bold; color: #333; transition: 0.3s;
        }
        .stock-input:focus { border-color: #007bff; outline: none; background: #eef7ff; }
        
        /* Trạng thái */
        .badge-stock { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .in-stock { background: #e6fffa; color: #00796b; }
        .out-stock { background: #fff5f5; color: #c53030; }

        /* Form Add */
        .add-form { padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 14px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        
        .btn-update-all {
            width: 100%; padding: 12px; margin-top: 10px;
            background: #007bff; color: white; border: none; 
            font-weight: bold; cursor: pointer; transition: 0.3s;
        }
        .btn-update-all:hover { background: #0056b3; }

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
                <li><a href="../index.php" target="_blank"><i class="fas fa-sign-out-alt"></i> Xem Website</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 class="page-title" style="margin: 0;">Quản lý kho hàng</h2>
                <a href="products.php" class="btn-delete" style="padding: 10px 15px; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>

            <?php if(isset($msg)): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                    <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
                </div>
            <?php endif; ?>
            <?php if(isset($error)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="product-header">
                <img src="../uploads/<?php echo $product['thumbnail']; ?>" class="product-thumb">
                <div class="product-meta">
                    <h2><?php echo $product['product_name']; ?></h2>
                    <p>Giá niêm yết: <b><?php echo number_format($product['price']); ?>đ</b></p>
                </div>
            </div>

            <div class="variant-wrapper">
                <div class="v-card list-section">
                    <div class="v-card-header">
                        <span><i class="fas fa-list"></i> Danh sách Size & Số lượng</span>
                    </div>
                    
                    <form action="" method="POST">
                        <table class="v-table">
                            <thead>
                                <tr>
                                    <th width="20%">Size</th>
                                    <th width="30%">Tồn kho hiện tại</th>
                                    <th width="30%">Trạng thái</th>
                                    <th width="20%">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($stmt_v->rowCount() > 0): 
                                    while($v = $stmt_v->fetch()): 
                                        $is_out = ($v['stock_quantity'] == 0);
                                ?>
                                    <tr>
                                        <td style="font-size: 16px; font-weight: bold;">
                                            <span style="background: #eee; padding: 5px 10px; border-radius: 4px;"><?php echo $v['size']; ?></span>
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   name="stock_qty[<?php echo $v['variant_id']; ?>]" 
                                                   value="<?php echo $v['stock_quantity']; ?>" 
                                                   min="0" 
                                                   class="stock-input"
                                                   style="<?php echo $is_out ? 'color:red; border-color:red;' : ''; ?>">
                                        </td>
                                        <td>
                                            <?php if($is_out): ?>
                                                <span class="badge-stock out-stock">HẾT HÀNG</span>
                                            <?php elseif($v['stock_quantity'] < 5): ?>
                                                <span class="badge-stock" style="background:#fffaf0; color:#dd6b20;">SẮP HẾT</span>
                                            <?php else: ?>
                                                <span class="badge-stock in-stock">CÒN HÀNG</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?id=<?php echo $product_id; ?>&del_variant=<?php echo $v['variant_id']; ?>" 
                                               class="btn-action btn-delete" 
                                               style="padding: 5px 10px; font-size: 12px;"
                                               onclick="return confirm('Bạn có chắc muốn xóa Size <?php echo $v['size']; ?> không?')">
                                               <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="4" style="padding: 30px; color: #999;">Chưa có kích thước nào. Hãy thêm mới bên phải.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        
                        <?php if($stmt_v->rowCount() > 0): ?>
                        <div style="padding: 15px;">
                            <button type="submit" name="update_stock" class="btn-update-all">
                                <i class="fas fa-save"></i> CẬP NHẬT KHO HÀNG
                            </button>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="v-card add-section">
                    <div class="v-card-header" style="background: #e3f2fd; color: #0d47a1;">
                        <span><i class="fas fa-plus-circle"></i> Thêm Size Mới</span>
                    </div>
                    <div class="add-form">
                        <form action="" method="POST">
                            <div class="form-group">
                                <label>Kích thước (Size)</label>
                                <input type="number" name="size" class="form-control" required placeholder="VD: 39, 40...">
                            </div>
                            <div class="form-group">
                                <label>Số lượng nhập kho</label>
                                <input type="number" name="stock" class="form-control" required value="10" min="1">
                            </div>
                            <button type="submit" name="add_variant" class="btn-add" style="width:100%; margin-top: 10px;">
                                <i class="fas fa-plus"></i> Thêm ngay
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>