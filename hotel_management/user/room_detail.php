<?php
// Kết nối CSDL (Giả định file db.php tồn tại và đã định nghĩa biến $conn)
include "../config/db.php"; 

// --- Logic PHP ---
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['user_username'] : '';

// Lấy ID phòng từ URL
$room_type_id = $_GET['room_id'] ?? 0;
$room_type_id = (int)$room_type_id;

$room_data = null;
$amenities = [];
$related_rooms = [];

// =========================================================================
// HÀM FORMAT TIỀN TỆ
// =========================================================================
function format_currency($amount) {
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}

// =========================================================================
// 1. LẤY THÔNG TIN CHI TIẾT LOẠI PHÒNG VÀ GIÁ (ĐÃ SỬA LỖI GROUP BY)
// =========================================================================
if ($room_type_id > 0 && isset($conn) && $conn) {
    // Truy vấn chính: Lấy thông tin loại phòng và giá thấp nhất (MIN)
    $sql_room = "SELECT 
                    T.id, 
                    T.type_name AS `name`,
                    MIN(R.price) AS price,  /* <--- ĐÃ SỬA LỖI ONLY_FULL_GROUP_BY */
                    T.short_description,
                    T.full_description AS `desc`, 
                    T.area_sqm, 
                    T.max_guests AS `guests`, 
                    T.image_path AS `img` 
                 FROM room_types T
                 JOIN rooms R ON T.id = R.type_id
                 WHERE T.id = ?
                 GROUP BY T.id, T.type_name, T.short_description, T.full_description, T.area_sqm, T.max_guests, T.image_path /* <--- Thêm tất cả cột không aggregate vào GROUP BY */
                 LIMIT 1";
    
    $stmt_room = $conn->prepare($sql_room);
    if ($stmt_room) {
        $stmt_room->bind_param("i", $room_type_id);
        $stmt_room->execute();
        $result_room = $stmt_room->get_result();
        
        if ($result_room->num_rows > 0) {
            $room_data = $result_room->fetch_assoc();
            $room_data['area'] = $room_data['area_sqm'] . 'm²';
        }
        $stmt_room->close();
    } else {
        error_log("SQL Error on room_detail.php (Room Info): " . $conn->error);
    }
    
    // =========================================================================
    // 2. LẤY DANH SÁCH TIỆN NGHI (AMENITIES)
    // =========================================================================
    // Note: room_type_amenities table doesn't exist, so amenities are disabled
    // $amenities array remains empty by default
    // If you want to add amenities, create the room_type_amenities table first

    // =========================================================================
    // 3. LẤY CÁC PHÒNG LIÊN QUAN (CÁC LOẠI PHÒNG KHÁC) (ĐÃ SỬA LỖI GROUP BY)
    // =========================================================================
    $sql_related = "SELECT 
                        T.id, 
                        T.type_name AS `name`,
                        MIN(R.price) AS price, /* <--- ĐÃ SỬA LỖI ONLY_FULL_GROUP_BY */
                        T.image_path AS `img` 
                    FROM room_types T
                    JOIN rooms R ON T.id = R.type_id
                    WHERE T.id != ?
                    GROUP BY T.id, T.type_name, T.image_path /* <--- Thêm các cột không aggregate vào GROUP BY */
                    LIMIT 3"; 

    $stmt_related = $conn->prepare($sql_related);
    if ($stmt_related) {
        $stmt_related->bind_param("i", $room_type_id);
        $stmt_related->execute();
        $result_related = $stmt_related->get_result();
        
        while($row = $result_related->fetch_assoc()) {
            $related_rooms[] = $row;
        }
        $stmt_related->close();
    } else {
        error_log("SQL Error on room_detail.php (Related Rooms): " . $conn->error);
    }


} else if ($room_type_id == 0) {
    // Nếu không có ID, chuyển hướng về trang danh sách phòng
    header("Location: rooms.php");
    exit();
}

// Nếu không tìm thấy dữ liệu phòng sau khi truy vấn
if (!$room_data) {
    $error_message = "Không tìm thấy thông tin loại phòng này.";
}

// =========================================================================
// CÁC ĐƯỜNG DẪN ẢNH KHÁC
// =========================================================================
$image_links = [
    // 1. ẢNH BANNER TĨNH TRANG CHI TIẾT PHÒNG
    'single_banner_detail' => 'https://thecapphotel.webhotel.vn/files/images/Room/5.jpg', 
];

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHI TIẾT PHÒNG - <?php echo $room_data['name'] ?? 'THE CAPPA HOTEL'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        /* CSS CHUNG (Copy từ rooms.php) */
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
        
        /* 1. Navbar CSS (Copy từ rooms.php) */
        .navbar {
            padding: 15px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            background-color: var(--color-white); 
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .logo a, .nav-links a, .user-icon {
            color: var(--color-white); 
            transition: color 0.4s ease;
        }
        .navbar.scrolled .logo a, 
        .navbar.scrolled .nav-links a,
        .navbar.scrolled .user-icon {
            color: var(--color-primary); 
        }
        .nav-links a:hover, .nav-links a.active, 
        .navbar.scrolled .nav-links a:hover, 
        .navbar.scrolled .nav-links a.active {
            color: var(--color-secondary); 
            transform: translateY(-2px);
        }
        .user-icon { font-size: 1.8em; cursor: pointer; transition: color 0.3s; }
        .logo a { font-family: 'Lora', serif; font-size: 1.5em; font-weight: 700; text-decoration: none; }
        .nav-links { display: flex; align-items: center; flex-wrap: nowrap; margin-left: auto; }
        .nav-links a { text-decoration: none; margin-left: 25px; font-weight: 500; text-transform: uppercase; font-size: 0.9em; display: inline-block; }
        .user-menu { position: relative; margin-left: 25px; flex-shrink: 0; }
        .dropdown-content { display: none; position: absolute; right: 0; background-color: var(--color-white); min-width: 180px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 10; border-radius: 5px; overflow: hidden; opacity: 0; transform: translateY(10px); transition: opacity 0.3s ease, transform 0.3s ease; }
        .dropdown-content.show { display: block; opacity: 1; transform: translateY(0); }
        .dropdown-content a { color: var(--color-primary); padding: 12px 16px; text-decoration: none; display: block; text-align: left; margin: 0; text-transform: none; font-size: 1em; font-weight: 400; }
        .dropdown-content a:hover { background-color: #f1f1f1; color: var(--color-secondary); transform: none; }
        
        /* 2. Footer CSS (Copy từ rooms.php) */
        .main-footer {
            background-color: #1a1a1a; 
            color: #fff;
            padding: 60px 50px 20px;
            font-size: 0.9em;
            margin-top: 50px; 
        }
        .footer-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; gap: 40px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 30px; margin-bottom: 20px; }
        .footer-column { flex: 1; min-width: 200px; }
        .footer-column h3 { font-family: 'Lora', serif; font-size: 1.5em; font-weight: 700; color: var(--color-secondary); margin-bottom: 20px; text-transform: uppercase; }
        .footer-column.about h3 { color: #fff; font-size: 1.8em; }
        .footer-column p { color: #b7b7b7; line-height: 1.6; }
        .footer-column ul { list-style: none; padding: 0; }
        .footer-column ul li a { color: #b7b7b7; text-decoration: none; line-height: 2.2; display: block; transition: color 0.3s; }
        .footer-column ul li a:hover { color: var(--color-secondary); }
        .contact-details p, .contact-details a { color: #b7b7b7; text-decoration: none; line-height: 1.8; }
        .contact-details strong { color: #fff; font-size: 1.5em; display: block; margin: 5px 0 10px; font-weight: 700; }
        .contact-details .email-link { border-bottom: 1px solid var(--color-secondary); color: var(--color-secondary); }
        .social-icons a { color: #b7b7b7; font-size: 1.4em; margin-right: 15px; transition: color 0.3s; }
        .social-icons a:hover { color: var(--color-secondary); }
        .copyright { text-align: center; color: #777; font-size: 0.85em; padding-top: 20px; }

        /* --- Hero Banner Section --- */
        .room-hero-banner {
            width: 100%;
            height: 350px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--color-white);
            position: relative;
            margin-top: 60px;
            overflow: hidden;
        }

        .room-hero-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, 
                rgba(0, 0, 0, 0.2) 0%, 
                rgba(0, 0, 0, 0.4) 50%, 
                rgba(0, 0, 0, 0.2) 100%);
            pointer-events: none;
        }

        .hero-banner-content {
            position: relative;
            z-index: 1;
            animation: slideInDown 0.8s ease-out;
        }

        .hero-banner-content h2 {
            font-family: 'Lora', serif;
            font-size: 3.5em;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            letter-spacing: 2px;
        }

        .hero-banner-content p {
            font-size: 1.4em;
            font-weight: 300;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            letter-spacing: 0.5px;
            line-height: 1.6;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* --- CSS ĐẶC THÙ CHO TRANG CHI TIẾT PHÒNG --- */
        .room-detail-container {
            max-width: 1400px;
            margin: 80px auto;
            padding: 0 20px;
        }

        .room-main-info {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 60px;
            align-items: start;
            margin-bottom: 100px;
        }

        .room-header-img {
            width: 100%;
            height: 600px;
            overflow: hidden;
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 100px;
            background: linear-gradient(135deg, #f0f0f0, #fff);
        }
        
        .room-header-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .room-header-img:hover img {
            transform: scale(1.08);
        }

        .info-content {
            padding: 40px;
            background: linear-gradient(135deg, #fafafa, var(--color-white));
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .info-content h1 {
            font-family: 'Lora', serif;
            font-size: 3.2em;
            margin-bottom: 12px;
            color: var(--color-primary);
            line-height: 1.1;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .short-desc {
            font-size: 1.15em;
            color: #888;
            margin-bottom: 28px;
            font-style: italic;
            font-weight: 300;
            line-height: 1.6;
        }

        .info-content .price-display {
            font-size: 2.8em;
            font-weight: 700;
            background: linear-gradient(135deg, var(--color-secondary), var(--color-primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 30px;
            padding-bottom: 25px;
            border-bottom: 3px solid var(--color-secondary);
            display: inline-block;
            letter-spacing: -1px;
        }
        
        .room-specifications {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 35px;
            padding: 30px;
            background: linear-gradient(135deg, var(--color-background), rgba(255, 255, 255, 0.5));
            border-radius: 15px;
            border-left: 6px solid var(--color-secondary);
            border-top: 1px solid rgba(163, 140, 113, 0.2);
        }

        .room-specifications div {
            display: flex;
            align-items: center;
            font-weight: 600;
            color: var(--color-primary);
            font-size: 1.12em;
            gap: 12px;
            padding: 8px 0;
        }

        .room-specifications i {
            color: var(--color-secondary);
            font-size: 1.8em;
            width: 35px;
            text-align: center;
        }

        .section-title {
            font-family: 'Lora', serif;
            font-size: 2.2em;
            color: var(--color-primary);
            margin-bottom: 30px;
            margin-top: 45px;
            padding-bottom: 18px;
            border-bottom: 4px solid var(--color-secondary);
            display: inline-block;
            letter-spacing: -0.5px;
            font-weight: 700;
        }

        .full-description {
            background: linear-gradient(135deg, rgba(247, 243, 237, 0.5), rgba(255, 255, 255, 0.8));
            padding: 35px;
            border-radius: 15px;
            border-left: 6px solid var(--color-secondary);
            border-top: 1px solid rgba(163, 140, 113, 0.15);
        }

        .full-description p {
            margin-bottom: 18px;
            color: #555;
            font-size: 1.08em;
            line-height: 1.9;
            text-align: justify;
            font-weight: 400;
        }

        /* Tiện nghi */
        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 18px;
            list-style: none;
            padding: 0;
            margin-top: 30px;
        }

        .amenities-grid li {
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, var(--color-background), rgba(255, 255, 255, 0.8));
            padding: 18px;
            border-radius: 12px;
            font-size: 1.05em;
            font-weight: 500;
            color: var(--color-primary);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.06);
            border-left: 5px solid var(--color-secondary);
            border-top: 1px solid rgba(163, 140, 113, 0.15);
            transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
            gap: 12px;
        }

        .amenities-grid li:hover {
            transform: translateX(8px) translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
            background: linear-gradient(135deg, rgba(163, 140, 113, 0.05), var(--color-white));
        }

        .amenities-grid li i {
            color: var(--color-secondary);
            font-size: 1.6em;
            width: 35px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .amenities-grid li:hover i {
            transform: scale(1.2) rotate(5deg);
        }

        /* Nút Đặt phòng */
        .booking-fixed-bar {
            background: linear-gradient(135deg, var(--color-white), rgba(247, 243, 237, 0.5));
            padding: 35px;
            text-align: center;
            border-top: 2px solid rgba(163, 140, 113, 0.2);
            border-radius: 18px;
            margin-top: 40px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            display: flex;
            gap: 18px;
            justify-content: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .btn-book-detail {
            display: inline-block;
            padding: 20px 50px;
            background: linear-gradient(135deg, var(--color-secondary), var(--color-primary));
            color: var(--color-white);
            text-decoration: none;
            font-size: 1.15em;
            font-weight: 700;
            border-radius: 60px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 12px 30px rgba(82, 71, 65, 0.25);
            border: 2px solid transparent;
            gap: 10px;
            display: inline-flex;
            align-items: center;
        }

        .btn-book-detail:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 20px 50px rgba(163, 140, 113, 0.4);
            letter-spacing: 2px;
        }

        .btn-book-detail:active {
            transform: translateY(-2px) scale(0.98);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 20px 50px;
            background-color: #f5f5f5;
            color: var(--color-primary);
            text-decoration: none;
            font-size: 1.15em;
            font-weight: 700;
            border-radius: 60px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 2px solid transparent;
        }

        .btn-back:hover {
            background: linear-gradient(135deg, var(--color-secondary), var(--color-primary));
            color: var(--color-white);
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            letter-spacing: 2px;
        }

        .btn-back:active {
            transform: translateY(-2px) scale(0.98);
        }

        /* Phòng liên quan */
        .related-rooms-section {
            margin-top: 120px;
            margin-bottom: 100px;
            padding: 80px 40px;
            background: linear-gradient(135deg, var(--color-background), rgba(163, 140, 113, 0.08));
            border-radius: 25px;
            border-top: 2px solid rgba(163, 140, 113, 0.2);
        }

        .related-rooms-section .section-title {
            display: block;
            margin-bottom: 50px;
            margin-top: 0;
        }

        .related-rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 45px;
        }
        
        .related-room-card {
            background: linear-gradient(135deg, var(--color-white), rgba(255, 255, 255, 0.95));
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .related-room-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .related-room-card-image {
            height: 280px;
            overflow: hidden;
            position: relative;
            background: linear-gradient(135deg, #f0f0f0, #e8e8e8);
        }

        .related-room-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .related-room-card:hover .related-room-card-image img {
            transform: scale(1.12) rotate(2deg);
        }

        .related-room-content {
            padding: 30px;
        }

        .related-room-content h4 {
            font-family: 'Lora', serif;
            font-size: 1.75em;
            margin-bottom: 12px;
            color: var(--color-primary);
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .related-room-content .price {
            font-size: 1.55em;
            font-weight: 700;
            background: linear-gradient(135deg, var(--color-secondary), var(--color-primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 22px;
            display: block;
            letter-spacing: -0.5px;
        }

        .related-room-content .btn-detail {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px 28px;
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            color: var(--color-white);
            text-align: center;
            text-decoration: none;
            border-radius: 60px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 1em;
            letter-spacing: 0.8px;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 8px 20px rgba(82, 71, 65, 0.2);
            border: 2px solid transparent;
        }

        .related-room-content .btn-detail:hover {
            transform: scale(1.08) translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            letter-spacing: 1.2px;
        }

        .related-room-content .btn-detail:active {
            transform: scale(0.96) translateY(-1px);
        }

        .no-amenities {
            padding: 25px;
            background: linear-gradient(135deg, #f9f9f9, #f0f0f0);
            border-left: 5px solid #ddd;
            border-radius: 12px;
            color: #999;
            font-style: italic;
            font-size: 1.05em;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 40px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .no-amenities i {
            font-size: 1.4em;
        }

        @media (max-width: 768px) {
            .room-main-info {
                grid-template-columns: 1fr;
                gap: 35px;
            }

            .room-header-img {
                position: relative;
                top: auto;
                height: 400px;
            }

            .room-specifications {
                grid-template-columns: 1fr;
            }

            .info-content h1 {
                font-size: 2.2em;
            }

            .info-content {
                padding: 25px;
            }

            .booking-fixed-bar {
                flex-direction: column;
                gap: 12px;
                padding: 25px;
            }

            .btn-book-detail, .btn-back {
                width: 100%;
                padding: 16px 30px;
            }

            .related-rooms-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .related-rooms-section {
                padding: 50px 25px;
            }

            .amenities-grid {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 1.8em;
            }

            /* Mobile Hero Banner */
            .room-hero-banner {
                height: 250px;
                margin-top: 60px;
            }

            .hero-banner-content h2 {
                font-size: 2.2em;
                letter-spacing: 1px;
            }

            .hero-banner-content p {
                font-size: 1.1em;
            }
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
            <a href="index.php#services">DỊCH VỤ</a>
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

    <!-- Hero Banner Section -->
    <section class="room-hero-banner" style="background: linear-gradient(135deg, rgba(82, 71, 65, 0.7) 0%, rgba(163, 140, 113, 0.5) 50%, rgba(82, 71, 65, 0.7) 100%), url('<?php echo $image_links['single_banner_detail']; ?>') center/cover; background-attachment: fixed;">
        <div class="hero-banner-content">
            <h2>CHI TIẾT PHÒNG</h2>
            <p>Khám phá không gian nghỉ dưỡng sang trọng tại The Cappa Luxury Hotel</p>
        </div>
    </section>

    <main>
        <?php if ($room_data): ?>
        <div class="room-detail-container">
            <div class="room-main-info">
                <div class="room-header-img">
                    <img src="<?php echo $room_data['img']; ?>" alt="Ảnh phòng <?php echo $room_data['name']; ?>">
                </div>
                
                <div>
                    <div class="info-content">
                        <h1><?php echo $room_data['name']; ?></h1>
                        
                        <?php if ($room_data['short_description']): ?>
                            <p class="short-desc"><?php echo $room_data['short_description']; ?></p>
                        <?php endif; ?>
                        
                        <span class="price-display"><?php echo format_currency($room_data['price']); ?>/Đêm</span>

                        <div class="room-specifications">
                            <div><i class="fas fa-ruler-combined"></i> <strong>Diện tích:</strong> &nbsp;<?php echo $room_data['area']; ?></div>
                            <div><i class="fas fa-users"></i> <strong>Tối đa:</strong> &nbsp;<?php echo $room_data['guests']; ?> Khách</div>
                        </div>

                        <h2 class="section-title">Chi tiết Phòng</h2>
                        <div class="full-description">
                            <p><?php echo nl2br($room_data['desc']); ?></p>
                        </div>

                        <?php if (!empty($amenities)): ?>
                        <h2 class="section-title" style="margin-top: 40px;">Tiện nghi trong phòng</h2>
                        <ul class="amenities-grid">
                            <?php foreach ($amenities as $amenity): ?>
                            <li><i class="<?php echo $amenity['icon_class']; ?>"></i> <?php echo $amenity['amenity_name']; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                            <div class="no-amenities">
                                <i class="fas fa-lightbulb"></i>
                                <span>Tiện nghi sẽ được cập nhật sớm</span>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                    
                    <div class="booking-fixed-bar">
                        <a href="booking.php?room_id=<?php echo $room_data['id']; ?>" class="btn-book-detail">
                            <i class="fas fa-calendar-check"></i> ĐẶT PHÒNG NGAY
                        </a>
                        <a href="rooms.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i> QUAY LẠI
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($related_rooms)): ?>
        <div class="related-rooms-section room-detail-container">
            <h2 class="section-title">Các Loại Phòng Khác</h2>
            <div class="related-rooms-grid">
                <?php foreach ($related_rooms as $related_room): ?>
                <div class="related-room-card">
                    <div class="related-room-card-image">
                        <img src="<?php echo $related_room['img']; ?>" alt="Ảnh phòng <?php echo $related_room['name']; ?>" loading="lazy">
                    </div>
                    <div class="related-room-content">
                        <h4><?php echo $related_room['name']; ?></h4>
                        <span class="price">Từ <?php echo format_currency($related_room['price']); ?></span>
                        <a href="room_detail.php?room_id=<?php echo $related_room['id']; ?>" class="btn-detail">
                            <i class="fas fa-arrow-right"></i> XEM CHI TIẾT
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div style="text-align: center; padding: 150px 30px; min-height: 70vh; display: flex; flex-direction: column; justify-content: center; align-items: center;">
            <div style="animation: float 3s ease-in-out infinite;">
                <i class="fas fa-exclamation-circle" style="font-size: 6.5em; background: linear-gradient(135deg, var(--color-secondary), var(--color-primary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 25px; display: block;"></i>
            </div>
            <h1 style="font-size: 3.2em; color: var(--color-primary); margin-bottom: 15px; letter-spacing: -1px; font-weight: 700;">Lỗi 404</h1>
            <p style="font-size: 1.3em; color: #888; margin: 20px 0 35px; max-width: 500px; line-height: 1.8;">
                <?php echo $error_message ?? "Rất tiếc, thông tin phòng bạn yêu cầu không tồn tại hoặc đã bị xóa."; ?>
            </p>
            <a href="rooms.php" class="btn-book-detail" style="background: linear-gradient(135deg, var(--color-secondary), var(--color-primary)); text-decoration: none; display: inline-flex; align-items: center; gap: 12px;">
                <i class="fas fa-arrow-left"></i> QUAY LẠI DANH SÁCH PHÒNG
            </a>
        </div>
        
        <style>
            @keyframes float {
                0%, 100% { transform: translateY(0px); }
                50% { transform: translateY(-20px); }
            }
        </style>
        <?php endif; ?>
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
                    <a href="#" aria-label="Instagram" target="_blank"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Youtube" target="_blank"><i class="fab fa-youtube"></i></a>
                    <a href="#" aria-label="Twitter" target="_blank"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="Facebook" target="_blank"><i class="fab fa-facebook-f"></i></a>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>Copyright © THE CAPPA LUXURY HOTEL. <?php echo date("Y"); ?> All Rights Reserved</p>
        </div>
    </footer>


    <script>
        // Script Dropdown Menu Người dùng
        document.addEventListener('DOMContentLoaded', function() {
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

            // Navbar Scroll Logic (từ rooms.php)
            const mainNav = document.getElementById('mainNav');
            const scrollThreshold = 100; 
            
            function handleScroll() {
                if (window.scrollY > scrollThreshold) {
                    mainNav.classList.add('scrolled');
                } else {
                    mainNav.classList.remove('scrolled');
                }
            }

            window.addEventListener('scroll', handleScroll);
            handleScroll(); 
        });
    </script>

</body>
</html>