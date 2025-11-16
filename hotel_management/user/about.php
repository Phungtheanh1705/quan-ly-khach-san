    <?php
    // user/about.php (Trang Giới thiệu)
    include "../config/db.php"; 

    // --- Logic PHP ---
    session_start();
    $is_logged_in = isset($_SESSION['user_id']);
    $username = $is_logged_in ? $_SESSION['user_username'] : '';

    // =========================================================================
    // PHẦN THAY THẾ: CÁC ĐƯỜNG DẪN ẢNH 
    // =========================================================================
    $image_links = [
        // 1. ẢNH BANNER TĨNH MỚI (THAY THẾ SLIDER)
        'single_banner_about' => 'https://cdn.xanhsm.com/2024/11/70b17f2e-khach-san-3-sao-sai-gon-1.jpg', 

        // 2. Ảnh Giới thiệu
        'about_story' => 'https://dyf.vn/wp-content/uploads/2021/01/thiet-ke-phong-ngu-khach-san-mini-2-giuong-don.jpg', 
        'about_mission' => 'https://sametel.com.vn/uploads/danhmuc/tam-nhin-1636861903-s2iqi.jpg', 
        
        // 3. Ảnh Đội ngũ (Team)
        'team_ceo' => '../assets/images/the_anh.jpg', 
        'team_manager' => '../assets/images/duc.jpg', 
        'team_chef' => '../assets/images/minh_quan.jpg', 
        'team_receptionist' => '../assets/images/huy.jpg', 
    ];
    // =========================================================================

    // Dữ liệu giả lập cho phần Đội ngũ (4 thành viên)
    $team_members = [
        ['name' => 'Phùng Thế Anh', 'title' => 'CEO & Founder', 'img' => $image_links['team_ceo']],
        ['name' => 'Bùi Anh Đức', 'title' => 'Tổng Quản lý', 'img' => $image_links['team_manager']],
        ['name' => 'Trịnh Minh Quân', 'title' => 'Quản lý thực phẩm', 'img' => $image_links['team_chef']],
        ['name' => 'Trần Văn Huy', 'title' => 'Trưởng Bộ phận Lễ tân', 'img' => $image_links['team_receptionist']],
    ];

    // Dữ liệu cho các mục nổi bật (4 mục)
    $highlights = [
        ['icon' => 'fas fa-shield-alt', 'title' => 'An toàn & Bảo mật', 'desc' => 'Chúng tôi đặt sự an toàn và riêng tư của khách hàng lên hàng đầu.'],
        ['icon' => 'fas fa-map-marker-alt', 'title' => 'Vị trí đắc địa', 'desc' => 'Tọa lạc tại trung tâm, thuận tiện cho việc di chuyển và khám phá thành phố.'],
        ['icon' => 'fas fa-trophy', 'title' => 'Giải thưởng danh giá', 'desc' => 'Được công nhận là Khách sạn Sang trọng hàng đầu Châu Á.'],
        ['icon' => 'fas fa-heart', 'title' => 'Dịch vụ tận tâm', 'desc' => 'Đội ngũ chuyên nghiệp luôn sẵn sàng phục vụ 24/7.'],
    ];
    ?>

    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>GIỚI THIỆU - THE CAPPA LUXURY HOTEL</title>
        <link href="https://fonts.googleapis.com/css2?family=Lora:wght@600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        
        <style>
            /* --- CSS TỪ FILE INDEX.PHP (Đảm bảo đồng bộ giao diện) --- */
            :root {
                --color-primary: #524741;      
                --color-secondary: #a38c71;    
                --color-background: #f7f3ed;  
                --color-white: #ffffff;
                --color-shadow: rgba(0, 0, 0, 0.15);
            }

            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            body {
                font-family: 'Roboto', sans-serif;
                color: var(--color-primary);
                line-height: 1.6;
                background-color: var(--color-background);
                padding-top: 60px; 
            }

            /* --- 1. Thanh Điều Hướng (Navbar) - FIX MÀU SẮC KHI CUỘN SANG TRẮNG --- */
            .navbar {
                padding: 15px 50px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                /* Ban đầu trong suốt */
                background-color: rgba(0, 0, 0, 0.2); 
                box-shadow: none;
                position: fixed; 
                width: 100%;
                top: 0; 
                z-index: 1001;
                transition: background-color 0.4s ease, box-shadow 0.4s ease; 
                height: 60px; 
            }

            .navbar.scrolled {
                background-color: var(--color-white); /* Chuyển sang NỀN TRẮNG khi cuộn */
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }

            /* Logo và Links (Ban đầu chữ trắng) */
            .logo a, .nav-links a, .user-icon {
                color: var(--color-white); 
                transition: color 0.4s ease;
            }

            /* Logo và Links (Khi cuộn, chuyển sang CHỮ ĐEN) */
            .navbar.scrolled .logo a, 
            .navbar.scrolled .nav-links a,
            .navbar.scrolled .user-icon {
                color: var(--color-primary); /* Chuyển sang màu đậm (gần như đen) */
            }
            
            .nav-links a:hover, .nav-links a.active, 
            .navbar.scrolled .nav-links a:hover, 
            .navbar.scrolled .nav-links a.active {
                color: var(--color-secondary); /* Màu nhấn vẫn là màu phụ */
                transform: translateY(-2px);
            }
            .user-icon:hover {
                color: var(--color-secondary);
            }

            /* Dropdown User Icon (ĐÃ SỬA KÍCH THƯỚC) */
        .user-icon {
            font-size: 1.8em; 
            cursor: pointer;
            transition: color 0.3s;
        }

            .logo a {
                font-family: 'Lora', serif;
                font-size: 1.5em;
                font-weight: 700;
                text-decoration: none;
            }

            .nav-links {
                display: flex;
                align-items: center;
                flex-wrap: nowrap; 
                margin-left: auto; 
            }

            .nav-links a {
                text-decoration: none;
                margin-left: 25px;
                font-weight: 500;
                text-transform: uppercase;
                font-size: 0.9em;
                display: inline-block; 
            }
            
            .user-menu {
                position: relative;
                margin-left: 25px;
                flex-shrink: 0; 
            }
            
            /* Dropdown content CSS giữ nguyên... */
            .dropdown-content {
                display: none;
                position: absolute;
                right: 0;
                background-color: var(--color-white);
                min-width: 180px;
                box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
                z-index: 10;
                border-radius: 5px;
                overflow: hidden;
                opacity: 0;
                transform: translateY(10px);
                transition: opacity 0.3s ease, transform 0.3s ease;
            }
            .dropdown-content.show {
                display: block;
                opacity: 1;
                transform: translateY(0);
            }
            .dropdown-content a {
                color: var(--color-primary); 
                padding: 12px 16px;
                text-decoration: none;
                display: block;
                text-align: left;
                margin: 0; 
                text-transform: none;
                font-size: 1em;
                font-weight: 400;
            }
            .dropdown-content a:hover {
                background-color: #f1f1f1;
                color: var(--color-secondary);
                transform: none;
            }


            /* --- 2. Hero Section (Banner Tĩnh) --- */
            .hero-section-about {
                height: 50vh; 
                position: relative;
                background-image: url('<?php echo $image_links['single_banner_about']; ?>');
                background-size: cover;
                background-position: center;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                color: var(--color-white);
                margin-top: -60px;
            }
            .hero-section-about .hero-overlay { 
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5); 
                z-index: 5;
            }
            .hero-section-about .hero-content {
                z-index: 10;
                padding: 20px;
            }
            .hero-section-about h1 {
                font-family: 'Lora', serif;
                font-size: 3.5em;
                text-transform: uppercase;
                margin-bottom: 10px;
                /* Hiệu ứng chữ Banner */
                opacity: 0;
                transform: translateY(20px);
                animation: fadeInTop 1s forwards 0.5s;
            }
            .hero-section-about p {
                font-size: 1.2em;
                max-width: 700px;
                margin: 0 auto;
                /* Hiệu ứng chữ Banner */
                opacity: 0;
                transform: translateY(20px);
                animation: fadeInTop 1s forwards 0.8s;
            }

            @keyframes fadeInTop {
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* --- 3. Nội dung Chính Giới Thiệu & Hiệu ứng cuộn (Fade In) --- */
            .main-content {
                max-width: 1200px;
                margin: 80px auto 50px; 
                padding: 0 50px;
            }
            
            /* CSS cho hiệu ứng chữ xuất hiện khi cuộn */
            /* Giữ nguyên hiệu ứng Fade-in khi cuộn */
            .about-section h2, .team-section h2, .about-section span, 
            .about-content p, .about-image img, .team-section .subtitle, 
            .team-card, .highlight-card {
                opacity: 0;
                transform: translateY(20px);
                transition: opacity 0.8s ease, transform 0.8s ease;
            }
            
            /* Thêm thuộc tính data-animate cho các phần tử */
            .highlight-card {
                transform: scale(0.9);
            }
            .team-card {
                transform: scale(0.9);
            }
            /* Dùng data-animate để kiểm soát animation */
            [data-animate].visible {
                opacity: 1;
                transform: translateY(0);
            }
            .team-card.visible, .highlight-card.visible {
                opacity: 1;
                transform: scale(1);
            }

            .highlights-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr); 
                gap: 30px;
                margin-bottom: 50px;
                padding-bottom: 50px;
                border-bottom: 1px solid #ddd;
            }
            .highlight-card {
                text-align: center;
                padding: 20px;
                background-color: var(--color-white);
                border-radius: 8px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            }
            .highlight-card i {
                font-size: 2.5em;
                color: var(--color-secondary);
                margin-bottom: 15px;
            }
            .highlight-card h4 {
                font-family: 'Lora', serif;
                font-size: 1.3em;
                margin-bottom: 10px;
            }
            .highlight-card p {
                color: #777;
                font-size: 0.95em;
            }

            .about-section {
                padding: 50px 0;
                display: flex;
                align-items: center;
                gap: 50px;
                margin-bottom: 30px;
            }
            .about-section:nth-child(even) { 
                flex-direction: row-reverse;
            }
            .about-content { flex: 1; }
            .about-image { flex: 1; max-width: 50%; }
            .about-image img {
                width: 100%;
                height: auto;
                border-radius: 8px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                object-fit: cover;
            }
            .about-content h2 { font-family: 'Lora', serif; font-size: 2.5em; color: var(--color-primary); margin-bottom: 15px; }
            .about-content h2 span { color: var(--color-secondary); }
            .about-content p { color: #777; margin-bottom: 20px; }
            
            .team-section {
                text-align: center;
                padding: 50px 0;
            }
            .team-section .subtitle {
                font-size: 1.2em;
                color: var(--color-secondary);
                font-weight: 500;
                text-transform: uppercase;
                margin-bottom: 5px;
            }
            .team-section h2 {
                font-family: 'Lora', serif;
                font-size: 2.5em;
                margin-bottom: 40px;
            }
            .team-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr); 
                gap: 30px;
            }
            .team-card {
                background-color: var(--color-white);
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                transition: transform 0.3s;
            }
            .team-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            }
            .team-card img {
                width: 100%;
                height: 300px;
                object-fit: cover;
            }
            .team-info {
                padding: 20px 15px;
            }
            .team-info h4 {
                font-family: 'Lora', serif;
                font-size: 1.3em;
                margin-bottom: 5px;
            }
            .team-info p {
                color: var(--color-secondary);
                font-weight: 500;
                margin: 0;
            }

            /* --- 4. Footer (Giữ nguyên CSS nền đậm) --- */
            .main-footer {
                background-color: #1a1a1a; 
                color: #fff;
                padding: 60px 50px 20px;
                font-size: 0.9em;
                margin-top: 50px; 
            }
            /* Các CSS Footer khác giữ nguyên... */
            .footer-container {
                max-width: 1200px;
                margin: 0 auto;
                display: flex;
                justify-content: space-between;
                gap: 40px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                padding-bottom: 30px;
                margin-bottom: 20px;
            }
            .footer-column {
                flex: 1;
                min-width: 200px;
            }
            .footer-column h3 {
                font-family: 'Lora', serif;
                font-size: 1.5em;
                font-weight: 700;
                color: var(--color-secondary); 
                margin-bottom: 20px;
                text-transform: uppercase;
            }
            .footer-column.about h3 {
                color: #fff; 
                font-size: 1.8em;
            }
            .footer-column p {
                color: #b7b7b7;
                line-height: 1.6;
            }
            .footer-column ul {
                list-style: none;
                padding: 0;
            }
            .footer-column ul li a {
                color: #b7b7b7;
                text-decoration: none;
                line-height: 2.2;
                display: block;
                transition: color 0.3s;
            }
            .footer-column ul li a:hover {
                color: var(--color-secondary);
            }
            .contact-details p,
            .contact-details a {
                color: #b7b7b7;
                text-decoration: none;
                line-height: 1.8;
            }
            .contact-details strong {
                color: #fff;
                font-size: 1.5em;
                display: block;
                margin: 5px 0 10px;
                font-weight: 700;
            }
            .contact-details .email-link {
                border-bottom: 1px solid var(--color-secondary);
                color: var(--color-secondary);
            }
            .social-icons a {
                color: #b7b7b7;
                font-size: 1.4em;
                margin-right: 15px;
                transition: color 0.3s;
            }
            .social-icons a:hover {
                color: var(--color-secondary);
            }
            .copyright {
                text-align: center;
                color: #777;
                font-size: 0.85em;
                padding-top: 20px;
            }
        </style>
    </head>
    <body>

        <nav class="navbar" id="mainNav">
            <div class="logo">
                <a href="index.php">THE CAPPA LUXURY HOTEL</a>
            </div>
            <div class="nav-links">
                <a href="index.php">TRANG CHỦ</a>
                <a href="about.php" class="active">GIỚI THIỆU</a>
                <a href="rooms.php">PHÒNG & GIÁ</a>
                <a href="index.php#services">DỊCH VỤ</a>
                <a href="#">LIÊN HỆ</a>
                
                <div class="user-menu">
                    <i class="fas fa-user-circle user-icon" id="userIcon"></i>
                    
                    <?php if ($is_logged_in): ?>
                    <div class="dropdown-content">
                        <a href="profile.php">Thông tin cá nhân</a>
                        <a href="dashboard.php">Đơn đặt phòng</a>
                        <a href="logout.php" style="color:#dc3545;">Đăng xuất</a>
                    </div>
                    <?php else: ?>
                    <div class="dropdown-content">
                        <a href="login.php">Đăng nhập</a>
                        <a href="register.php">Đăng ký</a>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </nav>

        <header class="hero-section-about">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h1>GIỚI THIỆU KHÁCH SẠN</h1>
                <p>Khám phá câu chuyện đằng sau sự sang trọng và dịch vụ đẳng cấp của chúng tôi.</p>
            </div>
        </header>

        <main class="main-content">
            
            <div class="highlights-grid">
                <?php foreach ($highlights as $item): ?>
                <div class="highlight-card" data-animate>
                    <i class="<?php echo $item['icon']; ?>"></i>
                    <h4><?php echo $item['title']; ?></h4>
                    <p><?php echo $item['desc']; ?></p>
                </div>
                <?php endforeach; ?>
            </div>

            <section class="about-section" data-animate>
                <div class="about-content">
                    <span style="color: var(--color-secondary); font-size: 1.1em; font-weight: 500;" data-animate>TẦM NHÌN</span>
                    <h2 data-animate>Sứ mệnh của <span>THE CAPPA</span></h2>
                    <p data-animate>The Cappa Luxury Hotel được xây dựng với tầm nhìn trở thành khách sạn 5 sao hàng đầu tại Hà Nội, mang đến trải nghiệm lưu trú không chỉ là nghỉ ngơi mà còn là một hành trình khám phá văn hóa và sự thư giãn tuyệt đối.</p>
                    <p data-animate>Sứ mệnh của chúng tôi là tạo ra những kỷ niệm đáng nhớ cho mỗi du khách. Chúng tôi cam kết cung cấp dịch vụ xuất sắc, ẩm thực tinh tế và không gian nghỉ dưỡng sang trọng, kết hợp hài hòa giữa nét truyền thống Việt Nam và sự hiện đại quốc tế.</p>
                </div>
                <div class="about-image">
                    <img src="<?php echo $image_links['about_mission']; ?>" alt="Hình ảnh thể hiện tầm nhìn và sứ mệnh" data-animate>
                </div>
            </section>

            <section class="about-section" data-animate>
                <div class="about-content">
                    <span style="color: var(--color-secondary); font-size: 1.1em; font-weight: 500;" data-animate>KHỞI NGUỒN</span>
                    <h2 data-animate>Câu chuyện về <span>Sự Sang Trọng</span></h2>
                    <p data-animate>Khách sạn được thành lập vào năm 2018 với niềm đam mê kiến tạo nên một không gian nghỉ dưỡng đẳng cấp, nơi mỗi vị khách đều cảm thấy được trân trọng và chăm sóc như những vị thượng khách. Tên gọi "Cappa" lấy cảm hứng từ sự giao thoa văn hóa Á-Âu, phản ánh kiến trúc độc đáo và dịch vụ đa dạng của khách sạn.</p>
                    <p data-animate>Từ những ngày đầu, The Cappa đã không ngừng nâng cao chất lượng, đầu tư vào tiện nghi hiện đại và đào tạo đội ngũ nhân viên chuyên nghiệp, tận tâm. Chúng tôi tự hào là lựa chọn hàng đầu cho các doanh nhân và du khách quốc tế tìm kiếm sự tinh tế và riêng tư.</p>
                    </div>
                <div class="about-image">
                    <img src="<?php echo $image_links['about_story']; ?>" alt="Hình ảnh câu chuyện về khách sạn" data-animate>
                </div>
            </section>

            <section class="team-section">
                <p class="subtitle" data-animate>NHỮNG CON NGƯỜI TẬN TÂM</p>
                <h2 data-animate>Gặp gỡ đội ngũ của chúng tôi</h2>
                
                <div class="team-grid">
                    <?php foreach ($team_members as $member): ?>
                    <div class="team-card" data-animate>
                        <img src="<?php echo $member['img']; ?>" alt="Ảnh <?php echo $member['name']; ?>">
                        <div class="team-info">
                            <h4><?php echo $member['name']; ?></h4>
                            <p><?php echo $member['title']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            
        </main>
        
        <footer class="main-footer">
            <div class="footer-container">
                <div class="footer-column about">
                    <h3>THE CAPPA LUXURY HOTEL</h3>
                    <p>Sở hữu những đường cong duyên dáng được thiết kế hài hòa với cảnh quan đường phố xung quanh, The Cappa Luxury Hotel chắc chắn sẽ là điểm dừng chân lý tưởng để thư giãn và khơi dậy các giác quan của bạn.</p>
                </div>
                <div class="footer-column">
                    <h3>ĐẶT PHÒNG</h3>
                    <ul>
                        <li><a href="#">Điều kiện & điều khoản</a></li>
                        <li><a href="#">Privacy policy</a></li>
                    </ul>
                </div>
                <div class="footer-column contact-details">
                    <h3>Liên hệ</h3>
                    <p>Số 147 Mai Dịch, Cầu Giấy, Hà Nội</p>
                    <strong>0242 242 0777</strong>
                    <a href="mailto:Info@webhotel.vn" class="email-link">Info@webhotel.vn</a>
                    <div class="social-icons" style="margin-top: 20px;">
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Youtube"><i class="fab fa-youtube"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <p>Copyright © THE CAPPA LUXURY HOTEL. <?php echo date("Y"); ?> All Rights Reserved</p>
            </div>
        </footer>


        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // --- Logic cho Sticky Navbar (ĐÃ FIX SANG NỀN TRẮNG) ---
                const mainNav = document.getElementById('mainNav');
                const scrollThreshold = 100; 
                
                // --- Logic cho hiệu ứng cuộn (Fade In) ---
                function checkVisibility() {
                    // Thu thập TẤT CẢ các phần tử cần hiệu ứng
                    const elementsToAnimate = document.querySelectorAll('[data-animate]');
                    
                    elementsToAnimate.forEach(el => {
                        // Lấy vị trí của phần tử so với viewport
                        const rect = el.getBoundingClientRect();
                        // Nếu phần tử nằm trong khoảng 80% từ đỉnh viewport trở lên
                        if (rect.top < window.innerHeight * 0.8 && rect.bottom > 0) {
                            el.classList.add('visible');
                        }
                    });
                }

                function handleScroll() {
                    // 1. Navbar Logic
                    if (window.scrollY > scrollThreshold) {
                        mainNav.classList.add('scrolled');
                    } else {
                        mainNav.classList.remove('scrolled');
                    }
                    
                    // 2. Animation Logic
                    checkVisibility();
                }

                window.addEventListener('scroll', handleScroll);
                handleScroll(); // Chạy lần đầu để kiểm tra vị trí ban đầu

                // --- Logic cho Dropdown Menu Người dùng ---
                const userIcon = document.getElementById('userIcon');
                const userMenu = userIcon.closest('.user-menu'); 
                const dropdown = userMenu.querySelector('.dropdown-content');
                
                if (userIcon && dropdown) {
                    userIcon.addEventListener('click', function(e) {
                        dropdown.classList.toggle('show');
                        e.stopPropagation(); 
                    });
                    
                    document.addEventListener('click', function(e) {
                        if (dropdown.classList.contains('show') && !userMenu.contains(e.target)) {
                            dropdown.classList.remove('show');
                        }
                    });
                }
            });
        </script>

    </body>
    </html>