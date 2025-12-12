<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang nộp bài - [Tên Của Bạn]</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #333;
        }
        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            width: 400px;
            max-width: 90%;
        }
        .info-box { margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        h1 { font-size: 22px; color: #4a4a4a; margin-bottom: 10px; text-transform: uppercase; }
        p { font-size: 16px; color: #666; margin: 5px 0; }
        
        .menu-btn {
            display: block;
            width: 100%;
            padding: 15px;
            margin-bottom: 15px;
            text-decoration: none;
            color: #fff;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        /* Nút Website - Màu Cam (Giống theme giày) */
        .btn-web {
            background: linear-gradient(to right, #F15A22, #ff7e5f);
            box-shadow: 0 4px 15px rgba(241, 90, 34, 0.3);
        }
        
        /* Nút Lab - Màu Xanh */
        .btn-lab {
            background: linear-gradient(to right, #00c6ff, #0072ff);
            box-shadow: 0 4px 15px rgba(0, 114, 255, 0.3);
        }

        .menu-btn:hover {
            transform: translateY(-3px);
            opacity: 0.9;
        }
        
        .footer { font-size: 12px; color: #999; margin-top: 20px; }
    </style>
</head>
<body>

    <div class="container">
        <div class="info-box">
            <h1>Báo cáo Cuối Kỳ</h1>
            <p><strong>Họ tên:</strong> Nguyễn Văn A</p> <p><strong>MSSV:</strong> 12345678</p>       <p><strong>Lớp:</strong> CNTT_K15</p>        </div>

        <a href="Thi_CK/index.php" class="menu-btn btn-web">
            🌐 WEBSITE BÁN GIÀY
        </a>

        <a href="labs.html" class="menu-btn btn-lab">
            📚 DANH SÁCH BÀI LAB
        </a>
        
        <div class="footer">Chúc thầy một ngày tốt lành!</div>
    </div>

</body>
</html>