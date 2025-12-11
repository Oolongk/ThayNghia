<?php 
include 'header.php'; // Đã bao gồm config.php (PDO)

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['fullname'];
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // --- CHUYỂN ĐỔI PDO ---

    // 1. Kiểm tra email đã tồn tại chưa (Dùng Prepare Statement)
    $stmt_check = $conn->prepare("SELECT customer_id FROM tbl_customers WHERE email = :email");
    $stmt_check->execute([':email' => $email]);

    if ($stmt_check->rowCount() > 0) {
        $error = "Email này đã được sử dụng!";
    } else {
        // Mã hóa mật khẩu
        $pass_hash = password_hash($pass, PASSWORD_DEFAULT);
        
        try {
            // 2. Thêm khách hàng mới (Dùng Prepare Statement)
            $sql = "INSERT INTO tbl_customers (full_name, email, password, phone, address) 
                    VALUES (:name, :email, :pass, :phone, :address)";
            
            $stmt = $conn->prepare($sql);
            
            // Thực thi và truyền dữ liệu an toàn
            $stmt->execute([
                ':name'    => $name,
                ':email'   => $email,
                ':pass'    => $pass_hash,
                ':phone'   => $phone,
                ':address' => $address
            ]);

            echo "<script>alert('Đăng ký thành công! Vui lòng đăng nhập.'); window.location.href='login.php';</script>";
            
        } catch (PDOException $e) {
            $error = "Lỗi Database: " . $e->getMessage();
        }
    }
}
?>

<div class="container" style="max-width: 500px; margin-top: 50px; margin-bottom: 50px;">
    <h2 style="text-align: center; margin-bottom: 20px;">Đăng ký tài khoản</h2>
    <?php if($error) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>
    
    <form action="" method="POST" style="border: 1px solid #ddd; padding: 20px; border-radius: 5px;">
        <div style="margin-bottom: 15px;">
            <label>Họ và tên</label>
            <input type="text" name="fullname" required style="width: 100%; padding: 8px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label>Email</label>
            <input type="email" name="email" required style="width: 100%; padding: 8px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label>Mật khẩu</label>
            <input type="password" name="password" required style="width: 100%; padding: 8px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label>Số điện thoại</label>
            <input type="text" name="phone" required style="width: 100%; padding: 8px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label>Địa chỉ nhận hàng</label>
            <textarea name="address" required style="width: 100%; padding: 8px;"></textarea>
        </div>
        <button type="submit" class="btn-primary" style="width: 100%; padding: 10px;">ĐĂNG KÝ</button>
        <p style="margin-top: 15px; text-align: center;">Đã có tài khoản? <a href="login.php" style="color: blue;">Đăng nhập</a></p>
    </form>
</div>
<?php include 'footer.php'; ?>