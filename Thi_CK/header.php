<?php require_once 'config.php'; // Đã kết nối PDO ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oolong Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="top-bar">FREESHIP CHO ĐƠN HÀNG TỪ 1 TRIỆU ĐỒNG</div>
    
    <header>
        <div class="container header-inner">
            <a href="index.php" class="logo">
                <img src="images/logo.png">
            </a>
            
            <nav>
                <ul class="main-menu">
                    <?php
                    $stmt_cat = $conn->query("SELECT * FROM tbl_categories WHERE parent_id = 0");
                    
                    // Thay mysqli_fetch_assoc bằng fetch()
                    while($row = $stmt_cat->fetch()){
                        echo "<li><a href='index.php?cat_id=".$row['cat_id']."'>".$row['cat_name']."</a></li>";
                    }
                    ?>
                </ul>
            </nav>

            <div class="header-icons">
                <form action="search.php" method="GET" style="display: inline-block; position: relative;">
                    <input type="text" name="keyword" placeholder="Tìm giày..." style="padding: 5px 10px; border: 1px solid #ccc; border-radius: 20px;">
                    <button type="submit" style="background: none; border: none; cursor: pointer; position: absolute; right: 5px; top: 5px;">
                        <i class="fas fa-search"></i>
                    </button>
                </form>

                <?php if(isset($_SESSION['user'])): ?>
                    <span style="font-size: 13px;">Xin chào, <b><?php echo $_SESSION['user']['full_name']; ?></b></span>
                    <a href="logout.php" style="font-size: 13px; color: red; margin-left: 10px;">(Đăng xuất)</a>
                <?php else: ?>
                    <a href="login.php"><i class="far fa-user"></i></a>
                <?php endif; ?>

                <a href="cart.php" class="cart-icon">
                    <i class="fas fa-shopping-bag"></i>
                    <?php 
                        $total_items = 0;
                        if(isset($_SESSION['cart'])) {
                            foreach($_SESSION['cart'] as $qty) $total_items += $qty;
                        }
                    ?>
                    <span class="cart-count"><?php echo $total_items; ?></span>
                </a>
            </div>
        </div>
    </header>