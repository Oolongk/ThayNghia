<?php 
include 'header.php'; // Đã bao gồm config.php (PDO)

// Kiểm tra nếu đã đăng nhập thì đá về trang chủ
if (isset($_SESSION['user'])) {
    header("Location: index.php"); 
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $pass = $_POST['password'];

    // --- CHUYỂN ĐỔI PDO ---
    
    // 1. Chuẩn bị câu lệnh (Dùng :email làm placeholder)
    $sql = "SELECT * FROM tbl_customers WHERE email = :email";
    $stmt = $conn->prepare($sql);
    
    // 2. Thực thi và truyền giá trị (PDO tự động xử lý ký tự đặc biệt)
    $stmt->execute([':email' => $email]);
    
    // 3. Kiểm tra số dòng trả về
    // ... Đoạn trên giữ nguyên ...

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch();
    
    if (password_verify($pass, $row['password'])) {
        // Lưu session
        $_SESSION['user'] = $row; 

        // --- SỬA ĐOẠN NÀY: KIỂM TRA QUYỀN ĐỂ CHUYỂN HƯỚNG ---
        if ($row['role'] == 1) {
            // Nếu là Admin -> Vào trang quản trị
            header("Location: admin/index.php");
        } else {
            // Nếu là Khách -> Về trang chủ
            header("Location: index.php");
        }
        exit();
        // ----------------------------------------------------

    } else {
        $error = "Sai mật khẩu!";
    }
}
}
?>

<div class="container" style="max-width: 400px; margin-top: 50px; margin-bottom: 80px;">
    <h2 style="text-align: center; margin-bottom: 20px;">Đăng nhập</h2>
    <?php if($error) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>
    
    <form action="" method="POST" style="border: 1px solid #ddd; padding: 20px; border-radius: 5px;">
        <div style="margin-bottom: 15px;">
            <label>Email</label>
            <input type="email" name="email" required style="width: 100%; padding: 8px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label>Mật khẩu</label>
            <input type="password" name="password" required style="width: 100%; padding: 8px;">
        </div>
        <button type="submit" class="btn-primary" style="width: 100%; padding: 10px;">ĐĂNG NHẬP</button>
        <p style="margin-top: 15px; text-align: center;">Chưa có tài khoản? <a href="register.php" style="color: blue;">Đăng ký</a></p>
    </form>
</div>
<?php include 'footer.php'; ?>