<?php
include "../config/db.php";
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['user_username'] : '';
// Thêm dữ liệu dịch vụ với ảnh (sử dụng link mẫu giống index)
$services = [
    [
        'title' => 'Đưa đón sân bay',
        'price' => '50 / daily',
        'details' => ['Đưa đón tận nơi', 'Giá cả hợp lý', 'Lái xe nhiệt tình cẩn thận'],
        'img_url' => 'https://www.vietnambooking.com/wp-content/uploads/2018/11/co-nen-su-dung-dich-vu-dua-don-san-bay-tai-khach-san-2.jpg'
    ],
    [
        'title' => 'Ăn sáng tại phòng',
        'price' => '30 / daily',
        'details' => ['Thực đơn phong phú', 'Thực đơn đảm bảo cho sức khỏe', 'Có thực đơn cho trẻ em'],
        'img_url' => 'https://www.huongnghiepaau.com/wp-content/uploads/2020/08/muc-luong-room-service.jpg'
    ],
    [
        'title' => 'Spa & Wellness',
        'price' => 'Tùy chọn',
        'details' => ['Massage', 'Xông hơi', 'Chăm sóc da'],
        'img_url' => 'https://images.pexels.com/photos/3820032/pexels-photo-3820032.jpeg'
    ],
    [
        'title' => 'Hồ bơi & Fitness',
        'price' => 'Miễn phí cho khách',
        'details' => ['Hồ bơi 4 mùa', 'Phòng gym'],
        'img_url' => 'https://images.pexels.com/photos/261102/pexels-photo-261102.jpeg'
    ],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DỊCH VỤ - THE CAPPA LUXURY HOTEL</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root{--color-primary:#524741;--color-secondary:#a38c71;--color-background:#f7f3ed;--color-white:#fff}
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Roboto',sans-serif;color:var(--color-primary);background:var(--color-background);padding-top:60px}
        .navbar{padding:15px 30px;display:flex;justify-content:space-between;align-items:center;position:fixed;width:100%;top:0;background:rgba(0,0,0,0.15);height:60px;z-index:2000;transition:background-color .3s,box-shadow .3s}
        .navbar.scrolled{background:var(--color-white);box-shadow:0 2px 10px rgba(0,0,0,0.08)}
        .logo a{font-family:'Lora',serif;font-size:1.2rem;color:var(--color-white);text-decoration:none}
        .nav-links{display:flex;align-items:center;margin-left:auto}
        .nav-links a{color:var(--color-white);margin-left:20px;text-decoration:none;font-weight:500;transition:color .18s ease,transform .18s ease;display:inline-block}
        .nav-links a:hover{color:var(--color-secondary);transform:translateY(-3px)}
        .navbar.scrolled .nav-links a{color:var(--color-primary)}
        .user-menu{position:relative;margin-left:25px;z-index:2100}
        .user-icon{font-size:1.8em;color:var(--color-white);margin-left:20px;cursor:pointer;transition:color .3s ease}
        .navbar.scrolled .user-icon{color:var(--color-primary)}
        .dropdown-content{display:none;position:absolute;right:0;background:var(--color-white);min-width:180px;box-shadow:0 8px 20px rgba(0,0,0,0.12);border-radius:8px;opacity:0;transform:translateY(8px);transition:opacity .2s,transform .2s;z-index:2200}
        .dropdown-content.show{display:block;opacity:1;transform:translateY(0)}
        .dropdown-content a{display:block;padding:12px 14px;color:var(--color-primary);text-decoration:none}

        .hero{height:260px;background:linear-gradient(135deg,rgba(82,71,65,0.8),rgba(163,140,113,0.6)),linear-gradient(rgba(0,0,0,0.25),rgba(0,0,0,0.25));background-size:cover;background-position:center;display:flex;align-items:center;justify-content:center;color:var(--color-white)}
        .hero h1{font-family:'Lora',serif;font-size:2.4rem}
        .container{max-width:1100px;margin:40px auto;padding:0 20px}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px}
        .card{background:var(--color-white);padding:22px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.06)}
        .card img{width:100%;height:150px;object-fit:cover;border-radius:8px;margin-bottom:12px}
        .card h3{font-family:'Lora',serif;margin-bottom:10px;color:var(--color-primary)}
        .card p{color:#666;line-height:1.5}
        .contact-cta{margin-top:18px}
        .btn{display:inline-block;padding:10px 16px;background:linear-gradient(135deg,var(--color-secondary),var(--color-primary));color:#fff;border-radius:8px;text-decoration:none}

        /* Footer giống index */
        .main-footer{background-color:#1a1a1a;color:#fff;padding:60px 50px 20px;font-size:0.9em}
        .footer-container{max-width:1200px;margin:0 auto;display:flex;justify-content:space-between;gap:40px;border-bottom:1px solid rgba(255,255,255,0.1);padding-bottom:30px;margin-bottom:20px}
        .footer-column{flex:1;min-width:200px}
        .footer-column h3{font-family:'Lora',serif;font-size:1.5em;font-weight:700;color:var(--color-secondary);margin-bottom:20px;text-transform:uppercase}
        .footer-column p{color:#b7b7b7;line-height:1.6}
        @media(max-width:600px){.hero h1{font-size:1.6rem}}
    </style>
</head>
<body>
    <nav class="navbar" id="mainNav">
        <div class="logo"><a href="index.php">THE CAPPA LUXURY HOTEL</a></div>
        <div class="nav-links">
            <a href="index.php">TRANG CHỦ</a>
            <a href="about.php">GIỚI THIỆU</a>
            <a href="rooms.php">PHÒNG & GIÁ</a>
            <a href="services.php" class="active">DỊCH VỤ</a>
            <a href="contact.php">LIÊN HỆ</a>
            <div class="user-menu">
                <i class="fas fa-user-circle user-icon" id="userIcon"></i>
                <div class="dropdown-content">
                    <?php if($is_logged_in): ?>
                        <a href="profile.php">Thông tin cá nhân</a>
                        <a href="dashboard.php">Đơn đặt phòng</a>
                        <a href="logout.php" style="color:#dc3545">Đăng xuất</a>
                    <?php else: ?>
                        <a href="login.php">Đăng nhập</a>
                        <a href="register.php">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <header class="hero">
        <h1>DỊCH VỤ & TIỆN ÍCH</h1>
    </header>

    <main class="container">
        <p style="color:#666;margin-bottom:18px">Chúng tôi cung cấp những dịch vụ chất lượng cao để làm cho kỳ nghỉ của bạn trở nên hoàn hảo.</p>

        <div class="grid">
            <?php foreach($services as $svc): ?>
                <div class="card">
                    <img src="<?php echo htmlspecialchars($svc['img_url']); ?>" alt="<?php echo htmlspecialchars($svc['title']); ?>">
                    <h3><?php echo htmlspecialchars($svc['title']); ?> <small style="font-size:0.85rem;color:#888;display:block;margin-top:6px"><?php echo htmlspecialchars($svc['price']); ?></small></h3>
                    <p><?php echo htmlspecialchars(implode(' · ', $svc['details'])); ?></p>
                    <div class="contact-cta"><a class="btn" href="contact.php">Yêu cầu dịch vụ</a></div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-column">
                <h3>THE CAPPA</h3>
                <p>Khách sạn sang trọng tại trung tâm Hà Nội, mang đến trải nghiệm nghỉ dưỡng và dịch vụ chuyên nghiệp.</p>
            </div>
            <div class="footer-column">
                <h3>LIÊN HỆ</h3>
                <p>Địa chỉ: Số 147 Mai Dịch, Cầu Giấy, Hà Nội</p>
                <p>Điện thoại: 0242 242 0777</p>
                <p>Email: Info@webhotel.vn</p>
            </div>
            <div class="footer-column">
                <h3>MỞ CỬA</h3>
                <p>Nhận phòng: 14:00 | Trả phòng: 12:00</p>
                <p>Hỗ trợ 24/7 cho khách hàng.</p>
            </div>
        </div>
    </footer>

    <script>
        // Navbar scroll behavior & user dropdown (same behavior as index)
        (function(){
            var nav = document.getElementById('mainNav');
            function onScroll(){
                if(window.scrollY > 30) nav.classList.add('scrolled'); else nav.classList.remove('scrolled');
            }
            onScroll();
            window.addEventListener('scroll', onScroll);

            var userIcon = document.getElementById('userIcon');
            var dropdown = document.querySelector('.dropdown-content');
            if(userIcon && dropdown){
                userIcon.addEventListener('click', function(e){
                    dropdown.classList.toggle('show');
                    e.stopPropagation();
                });
                document.addEventListener('click', function(e){
                    if(!e.target.closest || !e.target.closest('.user-menu')){
                        dropdown.classList.remove('show');
                    }
                });
            }
        })();
    </script>
</body>
</html>
