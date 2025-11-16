<?php
// user/index.php (Trang chủ của khu vực khách hàng)
include "../config/db.php"; 

// --- Logic PHP ---
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['user_username'] : '';

// =========================================================================
// PHẦN THAY THẾ: CÁC ĐƯỜNG DẪN ẢNH (HÃY THAY THẾ TẤT CẢ CÁC URL NÀY)
// =========================================================================
$image_links = [
    // 1. ẢNH SLIDE MỚI - BẠN CẦN THAY THẾ 3 LINK NÀY
    'slide_1' => 'https://thecapphotel.webhotel.vn/files/images/slide/bn2.jpg', // THAY LINK NÀY!
    'slide_2' => 'https://thecapphotel.webhotel.vn/files/images/Room/7.jpg', // THAY LINK NÀY!
    'slide_3' => 'https://thecapphotel.webhotel.vn/files/images/gallery/10.jpg', // THAY LINK NÀY!

    // 2. Ảnh Giới thiệu (Intro Section) - Dùng trong HTML
    'intro_lobby' => 'https://thecapphotel.webhotel.vn/files/images/intro/Home2.jpg',       // THAY LINK NÀY!
    'intro_room' => 'https://thecapphotel.webhotel.vn/files/images/intro/Home1.jpg',    // THAY LINK NÀY!

    // 3. Ảnh Đại diện Phòng (Room Cards) - Sẽ được dùng theo thứ tự
    'room_junior_suite' => 'https://thecapphotel.webhotel.vn/files/images/slide/bn2.jpg', // THAY LINK NÀY!
    'room_family' => 'https://thecapphotel.webhotel.vn/files/images/slide/bn3.jpg',         // THAY LINK NÀY!
    'room_double' => 'https://thecapphotel.webhotel.vn/files/images/slide/bn1.jpg',         // THAY LINK NÀY!
    'room_deluxe' => 'https://thecapphotel.webhotel.vn/files/images/Room/5.jpg',         // THAY LINK NÀY!
    'room_superior' => 'https://thecapphotel.webhotel.vn/files/images/Room/7.jpg',      // THAY LINK NÀY!

    // 4. Ảnh Dịch vụ Bổ sung (Service Cards)
    'service_transfer' => 'https://www.vietnambooking.com/wp-content/uploads/2018/11/co-nen-su-dung-dich-vu-dua-don-san-bay-tai-khach-san-2.jpg', // THAY LINK NÀY!
    'service_breakfast' => 'https://www.huongnghiepaau.com/wp-content/uploads/2020/08/muc-luong-room-service.jpg',// THAY LINK NÀY!
];
// =========================================================================
// KẾT THÚC PHẦN THAY THẾ LINK ẢNH
// =========================================================================

// 1. Lấy dữ liệu 5 loại phòng nổi bật
$rooms_query = $conn->prepare("SELECT 
    rt.id,
    rt.type_name, 
    MIN(r.price) AS price_min
    FROM room_types rt
    JOIN rooms r ON rt.id = r.type_id
    GROUP BY rt.id, rt.type_name
    ORDER BY price_min DESC LIMIT 5");
$rooms_query->execute();
$rooms_result = $rooms_query->get_result();
$rooms = $rooms_result->fetch_all(MYSQLI_ASSOC);

// Mảng giả lập cho hình ảnh và giá (để đảm bảo hiển thị đủ 5 phòng như hình)
$sample_rooms = [
    ['id' => 1, 'type_name' => 'Junior Suite', 'price_min' => 1500000],
    ['id' => 2, 'type_name' => 'Family Room', 'price_min' => 2500000],
    ['id' => 3, 'type_name' => 'Double Room', 'price_min' => 2500000],
    ['id' => 4, 'type_name' => 'Deluxe Room', 'price_min' => 3500000],
    ['id' => 5, 'type_name' => 'Superior Room', 'price_min' => 1500000],
];

// Dùng dữ liệu giả lập nếu CSDL không đủ
if (count($rooms) < 5) {
    $rooms = $sample_rooms; 
}

// Mảng chứa key ảnh tương ứng với thứ tự phòng
$room_image_keys = ['room_junior_suite', 'room_family', 'room_double', 'room_deluxe', 'room_superior'];


// Dữ liệu giả lập cho phần Dịch vụ Bổ sung - CẬP NHẬT để sử dụng link ảnh từ mảng $image_links
$services = [
    [
        'title' => 'Đưa đón sân bay',
        'price' => '50 / daily',
        'details' => ['Đưa đón tận nơi', 'Giá cả hợp lý', 'Lái xe nhiệt tình cẩn thận'],
        'img_url' => $image_links['service_transfer'] // Dùng link ảnh
    ],
    [
        'title' => 'Ăn sáng tại phòng',
        'price' => '30 / daily',
        'details' => ['Thực đơn phong phú', 'Thực đơn đảm bảo cho sức khỏe', 'Có thực đơn cho trẻ em'],
        'img_url' => $image_links['service_breakfast'] // Dùng link ảnh
    ]
];

// Dữ liệu cho phần Tiện ích Khách sạn mới
$amenities = [
    ['icon' => 'fas fa-car', 'title' => 'Đưa đón sân bay', 'description' => 'Chúng tôi sẽ đón từ sân bay trong khi bạn thoải mái trên chuyến đi của mình, một người yêu thích sự nhẹ nhàng.'],
    ['icon' => 'fas fa-concierge-bell', 'title' => 'Dịch vụ phòng', 'description' => 'Chúng tôi sẽ giúp mang lại trải nghiệm lưu trú cùng nhau và hài lòng tại đây 1 cách tốt nhất của du khách.'],
    ['icon' => 'fas fa-water', 'title' => 'Hồ bơi', 'description' => 'Bể bơi 4 mùa được điều chỉnh nhiệt độ nước để du khách có thể thỏa sức bơi lội vào bất kỳ thời điểm nào trong năm.'],
    ['icon' => 'fas fa-wifi', 'title' => 'Wifi', 'description' => 'Khách sạn cung cấp WiFi miễn phí trong toàn bộ khuôn viên ngoài ra khách hàng có thể truy cập Internet ngay cả khi ở bãi đậu xe của khách sạn.'],
    ['icon' => 'fas fa-utensils', 'title' => 'Bữa ăn sáng', 'description' => 'Buffet sáng tại The Cappa Luxury Hotel với gần 40 món ăn sáng kiểu Á được thay đổi hàng ngày do đầu bếp chuyên nghiệp chế biến.'],
    ['icon' => 'fas fa-spa', 'title' => 'Spa', 'description' => 'Tại đây, bạn được thỏa mình vào những trải nghiệm spa độc đáo, được chắt lọc nghệ thuật tinh túy của phương Đông và phương Tây.'],
];

// Dữ liệu cho 3 slide mới
$slides = [
    [
        'img' => $image_links['slide_1'],
        'subtitle' => 'TRẢI NGHIỆM ĐẲNG CẤP',
        'title' => 'THE CAPPA LUXURY HOTEL',
        'desc' => 'Nơi nghỉ dưỡng hoàn hảo, kiến tạo những kỷ niệm khó quên.'
    ],
    [
        'img' => $image_links['slide_2'],
        'subtitle' => 'GIÁ TỐT NHẤT CHO',
        'title' => 'PHÒNG & RESORT',
        'desc' => 'Tận hưởng mọi tiện nghi 5 sao với chi phí hợp lý nhất.'
    ],
    [
        'img' => $image_links['slide_3'],
        'subtitle' => 'DỊCH VỤ CHU ĐÁO',
        'title' => 'BỮA SÁNG & SPA',
        'desc' => 'Thư giãn tuyệt đối với dịch vụ Spa cao cấp và ẩm thực phong phú.'
    ]
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>THE CAPPA LUXURY HOTEL - Trang chủ</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        /* --- CSS Cập nhật màu sắc, Hiệu ứng và Dropdown --- */
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

        /* --- 1. Thanh Điều Hướng (Navbar) --- */
        .navbar {
            padding: 15px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.2); 
            position: fixed; 
            width: 100%;
            top: 0; 
            z-index: 1001;
            transition: background-color 0.4s ease, box-shadow 0.4s ease; 
            height: 60px; 
        }

        /* Navbar khi cuộn */
        .navbar.scrolled {
            background-color: var(--color-white); 
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .logo a {
            font-family: 'Lora', serif;
            font-size: 1.5em;
            font-weight: 700;
            color: var(--color-white); 
            text-decoration: none;
            transition: color 0.4s ease;
        }
        .navbar.scrolled .logo a {
            color: var(--color-primary); 
        }

        .nav-links {
            display: flex;
            align-items: center;
            flex-wrap: nowrap; 
            margin-left: auto; 
        }

        .nav-links a {
            text-decoration: none;
            color: var(--color-white); 
            margin-left: 25px;
            font-weight: 500;
            transition: color 0.3s, transform 0.3s; 
            text-transform: uppercase;
            font-size: 0.9em;
            display: inline-block; 
        }
        .navbar.scrolled .nav-links a {
            color: var(--color-primary); 
        }

        /* Hiệu ứng nhảy chữ */
        .nav-links a:hover {
            color: var(--color-secondary);
            transform: translateY(-2px);
        }

        /* Dropdown User Icon */
        .user-menu {
            position: relative;
            margin-left: 25px;
            flex-shrink: 0; 
        }

        .user-icon {
            font-size: 1.8em;
            color: var(--color-white); 
            cursor: pointer;
            transition: color 0.3s;
        }
        .navbar.scrolled .user-icon {
            color: var(--color-primary); 
        }

        .user-icon:hover {
            color: var(--color-secondary);
        }
        
        /* Dropdown Content */
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

        /* --- 2. Hero Section (Banner/Slider) --- */
        .hero-section {
            margin-top: -60px; 
            height: 100vh; 
            position: relative;
            overflow: hidden; /* Quan trọng để slide hoạt động */
        }
        
        /* Slider Container */
        .slides-container {
            width: 100%;
            height: 100%;
            position: relative;
        }

        /* Individual Slide */
        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease; /* Tốc độ chuyển ảnh */
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .slide.active {
            opacity: 1;
        }

        /* Slide Content Overlay */
        .slide-content {
            z-index: 10;
            color: var(--color-white);
            text-align: center;
            padding: 20px;
            max-width: 800px;
        }
        
        .slide-content h5 {
            font-size: 1.2em;
            font-weight: 500;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 10px;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.8s ease 0.5s, transform 0.8s ease 0.5s; /* Hiệu ứng chữ xuất hiện */
        }
        .slide.active .slide-content h5 {
            opacity: 1;
            transform: translateY(0);
        }

        .slide-content h2 {
            font-family: 'Lora', serif;
            font-size: 4em;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.1;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.8s ease 0.8s, transform 0.8s ease 0.8s; /* Hiệu ứng chữ xuất hiện */
        }
        .slide.active .slide-content h2 {
            opacity: 1;
            transform: translateY(0);
        }
        
        .slide-content p {
            font-size: 1.1em;
            margin-top: 15px;
            color: rgba(255, 255, 255, 0.9);
            opacity: 0;
            transition: opacity 0.8s ease 1.1s; /* Hiệu ứng chữ xuất hiện */
        }
        .slide.active .slide-content p {
            opacity: 1;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4); /* Độ tối vừa phải cho chữ nổi bật */
            z-index: 5;
        }
        
        /* Nút Đặt phòng lớn ở giữa slide */
        .btn-slide-book {
            display: inline-block;
            margin-top: 30px;
            padding: 15px 40px;
            background-color: var(--color-secondary);
            color: var(--color-white);
            text-decoration: none;
            font-weight: 700;
            text-transform: uppercase;
            border: 2px solid var(--color-secondary);
            transition: background-color 0.3s, border-color 0.3s;
            opacity: 0;
            transform: scale(0.8);
            transition: opacity 0.8s ease 1.4s, transform 0.8s ease 1.4s;
            z-index: 10;
        }
        .slide.active .btn-slide-book {
            opacity: 1;
            transform: scale(1);
        }

        .btn-slide-book:hover {
            background-color: transparent;
            border-color: var(--color-white);
        }

        /* --- 3. Booking Bar (Phần tìm kiếm) --- */
        .booking-bar {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 25px 50px;
            background-color: rgba(255, 255, 255, 0.95); 
            display: flex;
            gap: 15px;
            align-items: center;
            z-index: 100;
            opacity: 0; 
            transform: translateY(100%); 
            animation: slideUp 1s forwards 0.5s;
        }
        @keyframes slideUp {
            to { opacity: 1; transform: translateY(0); }
        }

        .booking-bar .input-group { 
            flex-grow: 1; 
            position: relative; 
        }
        
        /* FIX LỖI: Cập nhật lại style cho input/select để chúng hiển thị chính xác */
        .booking-bar select, .booking-bar input {
            width: 100%;
            padding: 15px 15px;
            border: 1px solid #ddd;
            border-radius: 0;
            font-size: 0.95em;
            color: var(--color-primary); 
            background-color: var(--color-white);
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            cursor: pointer;
        }
        
        /* Tạo mũi tên giả cho Select */
        .booking-bar .input-group:nth-child(3)::after, 
        .booking-bar .input-group:nth-child(4)::after {
            content: "\f078"; /* Mã Unicode của FontAwesome Angle Down */
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none; 
            color: #777;
            font-size: 0.8em;
        }
        
        .booking-bar .btn-datphong {
            padding: 15px 30px;
            background-color: var(--color-secondary); 
            color: var(--color-white);
            border: none;
            border-radius: 0; 
            font-weight: 700;
            text-transform: uppercase;
            cursor: pointer;
            flex-shrink: 0;
            transition: background-color 0.3s, transform 0.3s;
        }
        .booking-bar .btn-datphong:hover {
            background-color: #8f795d;
            transform: scale(1.02);
        }

        /* --- 4. Nội dung Chính (Giới thiệu) --- */
        .content-section {
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 50px;
        }
        
        .intro-text h2 span { color: var(--color-secondary); }

        /* Bố cục Giới thiệu (2 cột) */
        .intro-section {
            display: grid;
            grid-template-columns: 1.5fr 1fr; 
            gap: 40px;
            align-items: flex-start; 
            margin-bottom: 100px;
            padding-top: 50px;
        }
        .intro-text h2 {
            font-family: 'Lora', serif;
            font-size: 2.5em;
            color: var(--color-primary);
            margin-top: 10px;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        .intro-text p {
            margin-bottom: 25px;
            color: #777;
            font-size: 1em;
            text-align: justify;
        }
        .contact-info p {
            font-size: 1.1em;
            color: var(--color-primary);
            font-weight: 500;
        }
        .contact-info i {
            color: var(--color-secondary);
            margin-right: 10px;
        }
        /* Style cho thẻ <img> */
        .intro-images img { 
            width: 100%;
            height: 300px; 
            object-fit: cover;
            border-radius: 5px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            display: block;
        }
        .intro-images img:last-of-type {
            margin-top: 15px;
        }

        /* --- 5. Phần Phòng & Giá --- */
        .room-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* Hàng 1 có 3 cột */
            gap: 30px;
            margin-top: 40px;
        }

        /* Thêm style cho hàng 2 (Deluxe và Superior) */
        .room-grid > div:nth-child(4),
        .room-grid > div:nth-child(5) {
            grid-column: span 1; /* Ban đầu là 1 cột */
        }

        /* Căn giữa hai card cuối và làm cho chúng lớn hơn */
        .room-grid-footer {
            grid-column: 1 / -1; /* Chiếm toàn bộ chiều rộng */
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-top: 30px;
            margin-left: auto;
            margin-right: auto;
            width: calc((100% - 30px) * 2 / 3 + 30px); 
            max-width: 800px;
        }
        .room-grid-footer .room-card:first-child { 
            grid-column: 1 / 2; /* Phòng Deluxe */
        }
        .room-grid-footer .room-card:last-child {
            grid-column: 2 / 3; /* Phòng Superior */
        }

        .room-card {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            cursor: pointer;
            transition: box-shadow 0.4s ease;
        }
        /* Hiệu ứng hover cho room-card */
        .room-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        /* Style cho thẻ <img> */
        .room-card img {
            width: 100%;
            height: 350px; 
            object-fit: cover;
            display: block;
            transition: transform 0.6s ease;
        }
        /* Hiệu ứng phóng to nhẹ hình ảnh khi hover */
        .room-card:hover img {
            transform: scale(1.05);
        }
        
        /* Thông tin giá và loại phòng */
        .room-info {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 20px;
            color: var(--color-white);
            background: linear-gradient(to top, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0));
            pointer-events: none; /* Quan trọng: Cho phép click xuyên qua */
        }
        
        .room-price-info {
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        /* Hiệu ứng thông tin trồi lên nhẹ khi hover */
        .room-card:hover .room-price-info {
            transform: translateY(-10px);
            opacity: 0.9;
        }
        
        .room-info .price {
            font-size: 1.1em;
            font-weight: 700;
            color: var(--color-secondary);
            margin-bottom: 5px;
        }
        .room-info h4 {
            font-family: 'Lora', serif;
            margin: 0;
            font-size: 1.8em;
            font-weight: 700;
            text-transform: uppercase;
        }

        /* Overlay khi hover - chứa 2 nút */
        .room-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(82, 71, 65, 0.85);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 15px;
            opacity: 0;
            transform: scale(0.95);
            transition: opacity 0.4s ease, transform 0.4s ease;
            pointer-events: none;
            z-index: 10;
        }
        
        .room-card:hover .room-overlay {
            opacity: 1;
            transform: scale(1);
            pointer-events: auto;
        }
        
        /* Nút hành động trong overlay */
        .room-action-btn {
            padding: 12px 30px;
            border: 2px solid var(--color-white);
            background: transparent;
            color: var(--color-white);
            text-decoration: none;
            text-transform: uppercase;
            font-weight: 700;
            font-size: 0.95em;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s ease;
            display: inline-block;
            min-width: 180px;
            text-align: center;
        }
        
        .room-action-btn:hover {
            background: var(--color-secondary);
            border-color: var(--color-secondary);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(163, 140, 113, 0.4);
        }
        
        /* Nút chính (Đặt phòng) */
        .room-action-btn.btn-book {
            background: linear-gradient(135deg, var(--color-secondary), var(--color-primary));
            border-color: transparent;
        }
        
        .room-action-btn.btn-book:hover {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            box-shadow: 0 10px 30px rgba(163, 140, 113, 0.5);
        }

        /* Nút ĐẶT PHÒNG/CHI TIẾT - cũ (optional, có thể ẩn) */
        .room-action-tag {
            position: absolute;
            top: 20px;
            right: 0;
            background-color: var(--color-primary);
            color: var(--color-white);
            padding: 8px 20px;
            font-size: 0.85em;
            font-weight: 700;
            text-transform: uppercase;
            text-decoration: none;
            clip-path: polygon(10% 0, 100% 0, 100% 100%, 0 100%);
            z-index: 5;
            transition: background-color 0.3s;
            opacity: 0;
            pointer-events: none;
        }

        /* --- 6. Dịch vụ Bổ sung (Service Section) --- */
        .services-section {
            background-color: var(--color-primary);
            color: var(--color-white);
            padding: 80px 0;
            margin-top: 80px;
        }
        .services-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 50px;
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr;
            gap: 40px;
            align-items: stretch;
        }
        .service-intro h3 {
            font-family: 'Lora', serif;
            font-size: 2.2em;
            margin-bottom: 10px;
            line-height: 1.2;
        }
        .service-intro p {
            color: #b7b7b7;
            margin-bottom: 25px;
        }
        .service-intro .contact-link {
            display: block;
            color: var(--color-secondary);
            font-weight: 500;
            text-decoration: none;
            font-size: 1.1em;
        }
        .service-intro .btn-view-more {
            display: inline-block;
            margin-top: 14px;
            padding: 10px 16px;
            background: linear-gradient(135deg, var(--color-secondary), var(--color-primary));
            color: var(--color-white);
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }
        .service-intro .btn-view-more:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 30px rgba(0,0,0,0.18);
        }
        .service-intro .contact-link i {
            margin-right: 5px;
        }
        .service-card {
            background-color: #433934; 
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            position: relative;
        }
        /* Style cho thẻ <img> */
        .service-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 15px;
            display: block;
        }
        .service-card h4 {
            font-family: 'Lora', serif;
            color: var(--color-secondary);
            font-size: 1.4em;
            margin-bottom: 5px;
        }
        .service-card .price-tag {
            font-size: 1em;
            font-weight: 700;
            color: var(--color-white);
            display: block;
            margin-bottom: 10px;
        }
        .service-card ul {
            list-style: none;
            padding-left: 0;
            margin-top: 10px;
        }
        .service-card ul li {
            color: #b7b7b7;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        .service-card ul li i {
            color: var(--color-secondary);
            margin-right: 8px;
        }

        /* --- 7. Tiện ích Khách sạn (Hotel Amenities - NEW) --- */
        .hotel-amenities-section {
            padding: 80px 50px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .amenity-title {
            text-align: center;
            font-size: 1.2em;
            color: var(--color-secondary);
            font-weight: 500;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .amenity-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 40px;
        }
        .amenity-card {
            background-color: var(--color-white);
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .amenity-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        .amenity-card i {
            font-size: 3em;
            color: var(--color-secondary);
            margin-bottom: 15px;
        }
        .amenity-card h4 {
            font-family: 'Lora', serif;
            font-size: 1.2em;
            color: var(--color-primary);
            margin-bottom: 10px;
        }
        .amenity-card p {
            font-size: 0.9em;
            color: #777;
            line-height: 1.5;
        }

        /* --- 8. Footer Mới (Theo yêu cầu) --- */
        .main-footer {
            background-color: #1a1a1a; /* Màu nền tối */
            color: #fff;
            padding: 60px 50px 20px;
            font-size: 0.9em;
        }

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
            color: var(--color-secondary); /* Màu vàng nâu */
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .footer-column.about h3 {
            color: #fff; /* Tiêu đề chính Hotel màu trắng */
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

        /* Thông tin liên hệ */
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

        /* Icon mạng xã hội */
        .social-icons a {
            color: #b7b7b7;
            font-size: 1.4em;
            margin-right: 15px;
            transition: color 0.3s;
        }

        .social-icons a:hover {
            color: var(--color-secondary);
        }

        /* Bản quyền */
        .copyright {
            text-align: center;
            color: #777;
            font-size: 0.85em;
            padding-top: 20px;
        }
        /* Loại bỏ footer cũ */
        .footer {
            display: none; 
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
            <a href="about.php">GIỚI THIỆU</a>
            <a href="rooms.php">PHÒNG & GIÁ</a>
            <a href="#services">DỊCH VỤ</a>
            <a href="contact.php">LIÊN HỆ</a>
            
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

    <header class="hero-section">
        
        <div class="slides-container" id="slidesContainer">
            <?php $slide_index = 0; ?>
            <?php foreach ($slides as $slide): ?>
            <div class="slide <?php echo $slide_index === 0 ? 'active' : ''; ?>" style="background-image: url('<?php echo $slide['img']; ?>');">
                <div class="hero-overlay"></div>
                <div class="slide-content">
                    <h5><?php echo $slide['subtitle']; ?></h5>
                    <h2><?php echo $slide['title']; ?></h2>
                    <p><?php echo $slide['desc']; ?></p>
                    <a href="book_room.php" class="btn-slide-book">ĐẶT PHÒNG NGAY</a>
                </div>
            </div>
            <?php $slide_index++; ?>
            <?php endforeach; ?>
        </div>

        <form class="booking-bar" action="book_room.php" method="GET">
            <div class="input-group">
                <input type="text" onfocus="(this.type='date')" onblur="if(!this.value) this.type='text'" placeholder="Ngày đến" name="checkin" required>
            </div>
            <div class="input-group">
                <input type="text" onfocus="(this.type='date')" onblur="if(!this.value) this.type='text'" placeholder="Ngày đi" name="checkout" required>
            </div>
            <div class="input-group">
                <select name="guests" required>
                    <option value="" disabled selected>Người lớn</option>
                    <option value="1">1 Người lớn</option>
                    <option value="2">2 Người lớn</option>
                    <option value="3">3 Người lớn</option>
                </select>
            </div>
            <div class="input-group">
                <select name="children">
                    <option value="0" selected>Trẻ em</option>
                    <option value="1">1 Trẻ em</option>
                    <option value="2">2 Trẻ em</option>
                </select>
            </div>
            <button type="submit" class="btn-datphong">ĐẶT PHÒNG</button>
        </form>
    </header>

    <main class="content-section">
        
        <div class="intro-section">
            <div class="intro-text">
                <span style="color: var(--color-secondary); font-size: 1.5em;">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                <h2>Tận hưởng trải nghiệm <span>sang trọng</span></h2>
                <p>The Cappa Luxury Hotel cung cấp đầy đủ những dịch vụ tiện ích, có chỗ lục kiệm xe nhanh chóng, Wi-Fi công cộng miễn phí cùng toàn bộ khách sạn... Điểm nổi bật của The Cappa Luxury Hotel là chất lượng dịch vụ được ưu tiên hàng đầu, đồng thời phòng ốc luôn được giữ gìn và phục vụ chu đáo cho khách hàng. Quý khách có thể vui lòng lựa chọn nơi trọ/tận hưởng ngày/lần/giờ trôi đầy sao xà và thành phố khi về đêm.</p>
                <div class="contact-info">
                    <p><i class="fas fa-phone"></i> Địa chỉ: 0242 242 0777</p>
                </div>
            </div>
            <div class="intro-images">
                <img src="<?php echo $image_links['intro_lobby']; ?>" alt="Nội thất sảnh khách sạn">
                <img src="<?php echo $image_links['intro_room']; ?>" alt="Phòng ngủ khách sạn sang trọng"> 
            </div>
        </div>

<hr>

        <section id="rooms">
            <h2 class="section-title" style="margin-bottom: 5px;">PHÒNG & GIÁ</h2>
            <h2 class="section-title" style="margin-top: -10px; font-weight: 500; font-size: 1.5em; color: #777;">THE CAPPA LUXURY HOTEL</h2>
            
            <div class="room-grid">
                <?php 
                if (count($rooms) > 0) {
                    $i = 0;
                    // Hàng 1: 3 phòng
                    foreach(array_slice($rooms, 0, 3) as $r) { 
                        $room_type = htmlspecialchars($r['type_name']);
                        $price_text = number_format($r['price_min'], 0, ',', '.') . ' / ĐÊM';
                        $image_key = $room_image_keys[$i] ?? ''; // Lấy key ảnh tương ứng
                ?>
                    <div class="room-card">
                        <img src="<?php echo $image_links[$image_key]; ?>" alt="<?php echo $room_type; ?>">
                        
                        <!-- Overlay với 2 nút -->
                        <div class="room-overlay">
                            <a href="room_detail.php?room_id=<?php echo $r['id']; ?>" class="room-action-btn">
                                <i class="fas fa-eye"></i> XEM CHI TIẾT
                            </a>
                            <a href="booking.php?room_id=<?php echo $r['id']; ?>" class="room-action-btn btn-book">
                                <i class="fas fa-door-open"></i> ĐẶT PHÒNG
                            </a>
                        </div>
                        
                        <div class="room-info">
                            <div class="room-price-info">
                                <div class="price"><?php echo $price_text; ?></div>
                                <h4><?php echo $room_type; ?></h4>
                            </div>
                        </div>
                    </div>
                <?php 
                    $i++;
                    }
                }
                ?>
            </div>

            <?php if (count($rooms) > 3): ?>
            <div class="room-grid-footer">
                <?php 
                // Phòng Deluxe (index 3)
                $r = $rooms[3];
                $room_type = htmlspecialchars($r['type_name']);
                $price_text = number_format($r['price_min'], 0, ',', '.') . ' / ĐÊM';
                ?>
                <div class="room-card">
                    <img src="<?php echo $image_links['room_deluxe']; ?>" alt="<?php echo $room_type; ?>">
                    
                    <!-- Overlay với 2 nút -->
                    <div class="room-overlay">
                        <a href="room_detail.php?room_id=<?php echo $r['id']; ?>" class="room-action-btn">
                            <i class="fas fa-eye"></i> XEM CHI TIẾT
                        </a>
                        <a href="booking.php?room_id=<?php echo $r['id']; ?>" class="room-action-btn btn-book">
                            <i class="fas fa-door-open"></i> ĐẶT PHÒNG
                        </a>
                    </div>
                    
                    <div class="room-info">
                        <div class="room-price-info">
                            <div class="price"><?php echo $price_text; ?></div>
                            <h4><?php echo $room_type; ?></h4>
                        </div>
                    </div>
                </div>

                <?php 
                // Phòng Superior (index 4)
                $r = $rooms[4];
                $room_type = htmlspecialchars($r['type_name']);
                $price_text = number_format($r['price_min'], 0, ',', '.') . ' / ĐÊM';
                ?>
                <div class="room-card">
                    <img src="<?php echo $image_links['room_superior']; ?>" alt="<?php echo $room_type; ?>">
                    
                    <!-- Overlay với 2 nút -->
                    <div class="room-overlay">
                        <a href="room_detail.php?room_id=<?php echo $r['id']; ?>" class="room-action-btn">
                            <i class="fas fa-eye"></i> XEM CHI TIẾT
                        </a>
                        <a href="booking.php?room_id=<?php echo $r['id']; ?>" class="room-action-btn btn-book">
                            <i class="fas fa-door-open"></i> ĐẶT PHÒNG
                        </a>
                    </div>
                    
                    <div class="room-info">
                        <div class="room-price-info">
                            <div class="price"><?php echo $price_text; ?></div>
                            <h4><?php echo $room_type; ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </section>

    </main>

<hr>
    
    <section id="services" class="services-section">
        <div class="services-container">
            <div class="service-intro">
                <h2 style="color: var(--color-secondary); font-size: 1.2em; font-weight: 500; text-transform: uppercase;">GIÁ TỐT NHẤT</h2>
                <h3>Dịch vụ bổ sung</h3>
                <p>Nơi tốt nhất cho kỳ nghỉ như gia đình của bạn. Chúng tôi cung cấp dịch vụ với chất lượng cao nhất cho khách hàng, mang đến cho bạn những dịch vụ tốt nhất cho khách hàng.</p>
                <a href="#" class="contact-link"><i class="fas fa-phone"></i> 0242 242 0777</a>
                <br>
                <a href="services.php" class="btn-view-more"><i class="fas fa-ellipsis-h"></i> Xem thêm</a>
            </div>
            
            <?php foreach ($services as $service): ?>
                <div class="service-card">
                    <img src="<?php echo $service['img_url']; ?>" alt="<?php echo $service['title']; ?>">
                    
                    <h4><?php echo $service['title']; ?></h4>
                    <span class="price-tag"><?php echo $service['price']; ?></span>
                    
                    <ul>
                        <?php foreach ($service['details'] as $detail): ?>
                            <li><i class="fas fa-check"></i> <?php echo $detail; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

<hr>

    <section class="hotel-amenities-section">
        <h2 class="amenity-title">DỊCH VỤ KHÁCH</h2>
        <h2 class="section-title" style="margin-top: 5px; font-family: 'Lora', serif; font-size: 2.5em; text-align: center; color: var(--color-primary);">Các tiện ích khách sạn</h2>
        
        <div class="amenity-grid">
            <?php foreach ($amenities as $amenity): ?>
                <div class="amenity-card">
                    <i class="<?php echo $amenity['icon']; ?>"></i>
                    <h4><?php echo $amenity['title']; ?></h4>
                    <p><?php echo $amenity['description']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    
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
                <p>Số 18 Phương Đình, Đan Phượng, Hà Nội</p>
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
    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> THE CAPPA LUXURY HOTEL. All rights reserved.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Logic cho Sticky Navbar ---
            const mainNav = document.getElementById('mainNav');
            const heroSection = document.querySelector('.hero-section');
            const scrollThreshold = heroSection ? heroSection.offsetHeight * 0.8 : 100; // Ngưỡng cuộn
            
            function handleScroll() {
                if (window.scrollY > scrollThreshold) {
                    mainNav.classList.add('scrolled');
                } else {
                    mainNav.classList.remove('scrolled');
                }
            }
            window.addEventListener('scroll', handleScroll);
            
            // --- Logic cho Booking Bar ---
            const dateInputs = document.querySelectorAll('.booking-bar input[type="text"]');
            dateInputs.forEach(input => {
                // Kiểm tra lại màu chữ khi blur
                input.addEventListener('focus', function() {
                    this.type = 'date';
                    this.style.color = 'var(--color-primary)';
                });
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.type = 'text';
                        this.style.color = 'var(--color-primary)'; // Giữ nguyên màu primary khi rỗng nếu muốn chữ placeholder rõ
                    }
                });
            });
            const selects = document.querySelectorAll('.booking-bar select');
            selects.forEach(select => {
                select.addEventListener('change', function() {
                    this.style.color = 'var(--color-primary)';
                });
            });
            
            // --- Logic cho Dropdown Menu Người dùng ---
            const userIcon = document.getElementById('userIcon');
            const userMenu = userIcon.closest('.user-menu'); 
            const dropdown = userMenu.querySelector('.dropdown-content');
            
            userIcon.addEventListener('click', function(e) {
                if (dropdown) {
                    dropdown.classList.toggle('show');
                    e.stopPropagation(); 
                }
            });
            
            document.addEventListener('click', function(e) {
                if (dropdown && dropdown.classList.contains('show') && !userMenu.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });

            // --- LOGIC SLIDER ---
            const slides = document.querySelectorAll('.slide');
            let currentSlide = 0;
            const slideInterval = 4000; // Tốc độ chuyển slide: 4 giây

            function showSlide(index) {
                slides.forEach((slide, i) => {
                    slide.classList.remove('active');
                    // Reset CSS Transitions để kích hoạt lại animation chữ khi chuyển slide
                    const content = slide.querySelector('.slide-content');
                    if (content) {
                        content.querySelectorAll('*').forEach(el => {
                            // Tạm thời loại bỏ class để reset transition
                            el.style.transition = 'none'; 
                            el.offsetHeight; // Kích hoạt repaint
                            el.style.transition = ''; // Khôi phục transition
                        });
                    }
                });

                if (slides[index]) {
                    slides[index].classList.add('active');
                }
            }

            function nextSlide() {
                currentSlide = (currentSlide + 1) % slides.length;
                showSlide(currentSlide);
            }

            // Khởi động slider
            if (slides.length > 0) {
                showSlide(currentSlide);
                setInterval(nextSlide, slideInterval);
            }
        });
    </script>

</body>
</html>