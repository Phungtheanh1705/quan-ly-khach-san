<?php
include "../config/db.php";
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['user_username'] : '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LIÊN HỆ - THE CAPPA LUXURY HOTEL</title>
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

        .hero{height:280px;background-size:cover;background-position:center;display:flex;align-items:center;justify-content:center;color:var(--color-white);position:relative}
        .hero::before{content:'';position:absolute;top:0;left:0;right:0;bottom:0;background:linear-gradient(135deg,rgba(82,71,65,0.6),rgba(0,0,0,0.5));z-index:1}
        .hero h1{font-family:'Lora',serif;font-size:2.2rem;font-weight:700;position:relative;z-index:2;text-align:center}

        .container{max-width:900px;margin:60px auto;padding:0 20px}

        .contact-header{text-align:center;margin-bottom:50px}
        .contact-header h2{font-family:'Lora',serif;font-size:2rem;margin-bottom:12px;color:var(--color-primary)}
        .contact-header p{font-size:1.05rem;color:#888;line-height:1.6}

        .contact-form{background:var(--color-white);padding:40px;border-radius:14px;box-shadow:0 15px 40px rgba(0,0,0,0.08);border:1px solid #f0f0f0;display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:start}

        .contact-image{border-radius:12px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.1);height:100%;min-height:400px}
        .contact-image img{width:100%;height:100%;object-fit:cover}

        .contact-form-content{display:flex;flex-direction:column}

        form{display:contents}
        .form-group{margin-bottom:24px}
        .form-group-full{grid-column:1/-1}

        label{display:block;margin-bottom:10px;font-weight:600;color:var(--color-primary);font-size:0.95rem;text-transform:uppercase;letter-spacing:0.5px}
        label i{margin-right:8px;color:var(--color-secondary)}

        input[type="text"],input[type="email"],textarea{width:100%;padding:14px 16px;border:2px solid #e8e8e8;border-radius:8px;font-family:'Roboto',sans-serif;font-size:1rem;color:var(--color-primary);transition:all .3s ease;background:#fafafa}

        input[type="text"]:focus,input[type="email"]:focus,textarea:focus{outline:none;border-color:var(--color-secondary);background:var(--color-white);box-shadow:0 0 0 4px rgba(163,140,113,0.08)}

        textarea{resize:vertical;min-height:140px;line-height:1.6}

        .form-hint{font-size:0.85rem;color:#999;margin-top:6px}

        .btn{display:inline-block;padding:14px 36px;background:linear-gradient(135deg,var(--color-secondary),var(--color-primary));color:var(--color-white);border:none;border-radius:8px;font-weight:700;cursor:pointer;text-transform:uppercase;letter-spacing:1px;font-size:0.95rem;transition:all .3s ease;text-decoration:none}
        .btn:hover{transform:translateY(-2px);box-shadow:0 10px 25px rgba(163,140,113,0.4)}
        .btn:active{transform:translateY(0)}

        .alert{padding:18px 20px;border-radius:10px;margin-top:24px;display:flex;align-items:center;gap:12px;font-weight:500}
        .alert-success{background:#e8f5e9;border-left:5px solid #28a745;color:#28a745}
        .alert-success i{font-size:1.2em}

        @media(max-width:768px){
            .contact-form{grid-template-columns:1fr;gap:24px;padding:25px}
            .contact-image{min-height:250px}
            .form-row{grid-template-columns:1fr;gap:16px}
            .contact-header h2{font-size:1.5rem}
        }

        /* Footer giống index */
        .main-footer{background-color:#1a1a1a;color:#fff;padding:60px 50px 20px;font-size:0.9em}
        .footer-container{max-width:1200px;margin:0 auto;display:flex;justify-content:space-between;gap:40px;border-bottom:1px solid rgba(255,255,255,0.1);padding-bottom:30px;margin-bottom:20px}
        .footer-column{flex:1;min-width:200px}
        .footer-column h3{font-family:'Lora',serif;font-size:1.5em;font-weight:700;color:var(--color-secondary);margin-bottom:20px;text-transform:uppercase}
        .footer-column p{color:#b7b7b7;line-height:1.6}
        .footer-contact-img{margin-top:12px;max-width:220px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.12)}
    </style>
</head>
<body>
    <nav class="navbar" id="mainNav">
        <div class="logo"><a href="index.php">THE CAPPA LUXURY HOTEL</a></div>
        <div class="nav-links">
            <a href="index.php">TRANG CHỦ</a>
            <a href="about.php">GIỚI THIỆU</a>
            <a href="rooms.php">PHÒNG & GIÁ</a>
            <a href="index.php#services">DỊCH VỤ</a>
            <a href="contact.php" class="active">LIÊN HỆ</a>
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

    <header class="hero" style="background-image:url('https://photo.znews.vn/w660/Uploaded/spivpdiv/2019_11_18/49151029_2178125862448410_8488626153257435136_o.jpg')">
        <h1><i class="fas fa-envelope"></i> LIÊN HỆ VỚI CHÚNG TÔI</h1>
    </header>

    <div class="container">
        <div class="contact-header">
            <h2>Gửi tin nhắn cho chúng tôi</h2>
            <p>Chúng tôi luôn sẵn sàng lắng nghe những gợi ý, câu hỏi hoặc yêu cầu của bạn.<br>Hãy liên hệ với chúng tôi và chúng tôi sẽ phản hồi trong vòng 24 giờ.</p>
        </div>

        <div class="contact-form">
            <div class="contact-image">
                <img src="https://photo.znews.vn/w660/Uploaded/spivpdiv/2019_11_18/49151029_2178125862448410_8488626153257435136_o.jpg" alt="Liên hệ với THE CAPPA LUXURY HOTEL">
            </div>

            <div class="contact-form-content">
                <form method="post" action="contact.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name"><i class="fas fa-user"></i> Họ & Tên</label>
                            <input id="name" name="name" type="text" placeholder="Nhập họ và tên của bạn" required>
                            <div class="form-hint">Họ và tên đầy đủ</div>
                        </div>

                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email</label>
                            <input id="email" name="email" type="email" placeholder="example@email.com" required>
                            <div class="form-hint">Địa chỉ email hợp lệ</div>
                        </div>
                    </div>

                    <div class="form-group form-group-full">
                        <label for="subject"><i class="fas fa-tag"></i> Tiêu đề</label>
                        <input id="subject" name="subject" type="text" placeholder="Chủ đề của tin nhắn" required>
                    </div>

                    <div class="form-group form-group-full">
                        <label for="message"><i class="fas fa-pencil-alt"></i> Nội dung</label>
                        <textarea id="message" name="message" placeholder="Viết tin nhắn của bạn ở đây..." required></textarea>
                        <div class="form-hint">Mô tả chi tiết vấn đề hoặc yêu cầu của bạn</div>
                    </div>

                    <button class="btn" type="submit" name="send"><i class="fas fa-paper-plane"></i> Gửi tin nhắn</button>
                </form>

            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
                echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i><span>Cảm ơn! Tin nhắn của bạn đã được gửi thành công. Chúng tôi sẽ liên hệ lại sớm nhất.</span></div>';
            }
            ?>
        </div>
    </div>

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
                <h3>GIỜ MỞ CỬA</h3>
                <p>Nhận phòng: 14:00 | Trả phòng: 12:00</p>
                <p>Hỗ trợ: 24/7</p>
            </div>
        </div>
    </footer>

    <script>
        (function(){
            var nav = document.getElementById('mainNav');
            function onScroll(){
                if(window.scrollY > 30) nav.classList.add('scrolled'); else nav.classList.remove('scrolled');
            }
            onScroll();
            window.addEventListener('scroll', onScroll);

            var userIcon = document.getElementById('userIcon');
            var dropdown = document.querySelector('.dropdown-content');
            if(userIcon){
                userIcon.addEventListener('click', function(e){
                    e.stopPropagation();
                    dropdown.classList.toggle('show');
                });
                document.addEventListener('click', function(){ dropdown.classList.remove('show'); });
            }
        })();
    </script>
</body>
</html>
