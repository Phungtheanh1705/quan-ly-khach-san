<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Management Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Keyframes: Hiệu ứng Tải trang - Phóng to nhẹ nhàng */
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.98); }
            to { opacity: 1; transform: scale(1); }
        }
        
        /* Keyframes: Hiệu ứng Phát sáng Viền nhẹ */
        @keyframes subtleGlow {
            0% { box-shadow: 0 0 0 0 rgba(193, 168, 142, 0.4); }
            100% { box-shadow: 0 0 0 8px rgba(193, 168, 142, 0); }
        }

        /* Thiết lập chung */
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f7f3ed; /* Nền màu Beige (Vàng nhạt) ấm áp */
            color: #4b3d36; /* Màu chữ Nâu Đất đậm */
            overflow: hidden;
        }

        /* Container Chính */
        .main-container {
            background-color: #ffffff;
            padding: 60px 80px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.07); /* Bóng đổ rất nhẹ */
            text-align: center;
            max-width: 480px;
            width: 100%;
            /* Hiệu ứng khi tải trang */
            opacity: 0;
            animation: scaleIn 0.9s ease-out forwards;
            position: relative;
        }

        /* Tiêu đề */
        h1 {
            font-family: 'Lora', serif; /* Font chữ sang trọng cho tiêu đề */
            color: #2c2725;
            margin-bottom: 5px;
            font-size: 2.8em;
            font-weight: 700;
        }

        h2 {
            color: #c1a88e; /* Màu Vàng Đồng / Taupe (Màu nhấn) */
            margin-bottom: 45px;
            font-size: 1.1em;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* Container Nút */
        .button-group {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        /* Phong cách Nút */
        .btn {
            display: block;
            padding: 18px 30px;
            font-size: 1.1em;
            font-weight: 700;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid transparent;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        /* Nút Admin - Màu sắc uy quyền (Tông tối) */
        .btn-admin {
            background-color: #4b3d36; /* Nâu Đất đậm */
            color: #fff;
        }
        /* Hiệu ứng Hover - Viền ngoài phát sáng nhẹ */
        .btn-admin:hover {
            background-color: #382d27;
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        /* Nút User - Màu nhấn tinh tế */
        .btn-user {
            background-color: #c1a88e; /* Màu Vàng Đồng / Taupe */
            color: #fff;
            animation: subtleGlow 2.5s infinite alternate; /* Hiệu ứng Viền Nhấp nháy nhẹ */
        }
        /* Hiệu ứng Hover - Đẩy lên, Đổi màu */
        .btn-user:hover {
            background-color: #b39b83;
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(193, 168, 142, 0.4);
            animation: none; /* Tắt hiệu ứng nhấp nháy khi hover */
        }
        
        /* Hiệu ứng Lớp màu lướt qua (Glare/Shine effect) */
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0.1) 0%,
                rgba(255, 255, 255, 0.3) 50%,
                rgba(255, 255, 255, 0.1) 100%
            );
            transform: skewX(-20deg);
            transition: transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        .btn:hover::before {
            transform: skewX(-20deg) translateX(200%);
        }

    </style>
</head>
<body>

    <div class="main-container">
        <h1>RESORT & HOTEL</h1>
        <h2>Hệ thống Truy cập Quản lý</h2>
        
        <div class="button-group">
            
            <a href="admin/login.php" class="btn btn-admin">
                Truy cập Quản trị viên
            </a>
            
            <a href="user/login.php" class="btn btn-user">
                Đăng nhập Khách hàng
            </a>
        </div>
        
    </div>

</body>
</html>