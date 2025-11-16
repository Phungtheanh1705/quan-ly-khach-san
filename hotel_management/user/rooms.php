<?php
// Kết nối CSDL (Giả định file db.php tồn tại và đã định nghĩa biến $conn)
include "../config/db.php"; 

// --- Logic PHP ---
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['user_username'] : '';

// =========================================================================
// CÁC ĐƯỜNG DẪN ẢNH KHÁC
// =========================================================================
$image_links = [
    // 1. ẢNH BANNER TĨNH TRANG PHÒNG
    'single_banner_rooms' => 'https://thecapphotel.webhotel.vn/files/images/Room/7.jpg', 
    // Các ảnh phòng cụ thể sẽ được lấy từ cột image_path trong bảng room_types
];


// =========================================================================
// LẤY DỮ LIỆU THỰC TỪ DATABASE (SỬ DỤNG JOIN)
// =========================================================================
$rooms = [];
$guest_filter_options = []; // Dùng để lọc các giá trị 'guests' duy nhất từ DB

// Kiểm tra xem biến $conn (kết nối DB) có tồn tại và thành công không
if (isset($conn) && $conn) {
    // Truy vấn: Lấy thông tin chi tiết về LOẠI PHÒNG và giá cơ bản từ bảng ROOMS.
    // Tính phòng còn trống: phòng có status='available' và KHÔNG có booking nào overlap (chưa hủy)
    $sql = "SELECT 
                T.id, 
                T.type_name AS `name`,
                MIN(R.price) AS price, 
                T.full_description AS `desc`, 
                T.area_sqm, 
                T.max_guests AS `guests`, 
                T.image_path AS `img`,
                COUNT(DISTINCT CASE 
                    WHEN R.status = 'available' 
                    AND R.id NOT IN (
                        SELECT DISTINCT B.room_id FROM bookings B
                        WHERE B.status != 'cancelled'
                    ) 
                    THEN R.id 
                END) AS available_rooms,
                COUNT(DISTINCT R.id) AS total_rooms
            FROM room_types T
            LEFT JOIN rooms R ON T.id = R.type_id
            GROUP BY T.id, T.type_name, T.full_description, T.area_sqm, T.max_guests, T.image_path
            ORDER BY T.id"; 
    
    $result = $conn->query($sql);

    if ($result) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Thêm trường area (string) cho hiển thị
                $row['area'] = $row['area_sqm'] . 'm²';
                
                $rooms[] = $row;
                
                // Thu thập các giá trị 'guests' (số khách) duy nhất cho bộ lọc
                $guest_filter_options[] = $row['guests'];
            }
        }
        $result->free(); 
    } else {
        error_log("SQL Error on rooms.php: " . $conn->error);
    }
} else {
    error_log("Database connection failed in rooms.php");
}

// Hàm format tiền tệ (Ví dụ: 2500000 -> 2.500.000 VNĐ)
function format_currency($amount) {
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}

// --- Logic Sắp xếp & Lọc ---

$filtered_rooms = $rooms; // Khởi tạo với tất cả phòng đã lấy từ DB

// Lấy tham số sắp xếp
$sort_by = $_GET['sort'] ?? 'price_asc';
$guest_filter = $_GET['guests'] ?? '';

// 1. Lọc theo Số khách
if (!empty($guest_filter)) {
    $guest_filter = (int)$guest_filter;
    $filtered_rooms = array_filter($filtered_rooms, function($room) use ($guest_filter) {
        return $room['guests'] == $guest_filter;
    });
}

// 2. Sắp xếp
usort($filtered_rooms, function($a, $b) use ($sort_by) {
    switch ($sort_by) {
        case 'price_desc':
            return $b['price'] <=> $a['price'];
        case 'area_desc':
            return $b['area_sqm'] <=> $a['area_sqm'];
        case 'price_asc':
        default:
            return $a['price'] <=> $b['price'];
    }
});

// Chuyển mảng đã sắp xếp và lọc vào biến rooms để hiển thị
$rooms = $filtered_rooms; 

// Tạo danh sách số khách duy nhất cho dropdown filter
$unique_guests = array_unique($guest_filter_options);
sort($unique_guests);

// --- PHÂN TRANG ---
$items_per_page = 6; // Số phòng hiển thị mỗi trang
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$total_items = count($rooms);
$total_pages = max(1, ceil($total_items / $items_per_page));

// Đảm bảo current_page không vượt quá tổng số trang
if ($current_page > $total_pages) {
    $current_page = $total_pages;
}

// Lấy dữ liệu cho trang hiện tại
$start_index = ($current_page - 1) * $items_per_page;
$paginated_rooms = array_slice($rooms, $start_index, $items_per_page);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHÒNG & GIÁ - THE CAPPA LUXURY HOTEL</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        /* CSS CHUNG (Giữ nguyên) */
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

        /* 1. Navbar CSS */
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
        
        /* 2. Footer CSS */
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

        /* --- CSS ĐẶC THÙ CHO TRANG PHÒNG --- */
        .hero-section-rooms {
            height: 50vh; 
            position: relative;
            background-image: url('<?php echo $image_links['single_banner_rooms']; ?>');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--color-white);
            margin-top: -60px;
        }
        .hero-section-rooms .hero-overlay { 
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); 
            z-index: 5;
        }
        .hero-section-rooms .hero-content {
            z-index: 10;
            padding: 20px;
        }
        .hero-section-rooms h1 {
            font-family: 'Lora', serif;
            font-size: 3.5em;
            text-transform: uppercase;
            margin-bottom: 10px;
            animation: fadeInTop 1s forwards 0.5s;
        }
        @keyframes fadeInTop {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .main-content {
            max-width: 1200px;
            margin: 80px auto 50px; 
            padding: 0 50px;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }
        .section-header .subtitle {
            font-size: 1.2em;
            color: var(--color-secondary);
            font-weight: 500;
            text-transform: uppercase;
            margin-bottom: 5px;
            opacity: 0; transform: translateY(20px); transition: opacity 0.8s ease, transform 0.8s ease 0.1s;
        }
        .section-header h2 {
            font-family: 'Lora', serif;
            font-size: 2.5em;
            margin-bottom: 10px;
            opacity: 0; transform: translateY(20px); transition: opacity 0.8s ease, transform 0.8s ease 0.2s;
        }
        .section-header.visible .subtitle, .section-header.visible h2 {
            opacity: 1;
            transform: translateY(0);
        }

        /* Form Lọc/Sắp xếp */
        .filter-bar {
            background-color: var(--color-white);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 40px;
            display: flex;
            justify-content: space-around;
            align-items: center;
        }
        .filter-bar form {
            display: flex;
            justify-content: space-around;
            width: 100%;
            gap: 20px;
        }
        .filter-bar select, .filter-bar button {
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 1em;
            font-family: 'Roboto', sans-serif;
        }
        .filter-bar button {
            background-color: var(--color-primary);
            color: var(--color-white);
            cursor: pointer;
            transition: background-color 0.3s;
            flex-shrink: 0;
        }
        .filter-bar button:hover {
            background-color: var(--color-secondary);
        }

        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }

        .room-card {
            background-color: var(--color-white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            opacity: 0;
            transform: translateY(20px);
            /* animation-delay sẽ được set bằng JS */
        }
        .room-card.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .room-card:hover {
            transform: translateY(-5px);
        }

        .room-card-image {
            height: 250px;
            overflow: hidden;
        }
        .room-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        .room-card:hover .room-card-image img {
            transform: scale(1.05);
        }

        .room-card-content {
            padding: 25px;
        }
        .room-card-content h3 {
            font-family: 'Lora', serif;
            font-size: 1.8em;
            margin-bottom: 10px;
            color: var(--color-primary);
        }
        .room-card-content .price {
            font-size: 1.5em;
            font-weight: 700;
            color: var(--color-secondary);
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .room-card-content .details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            color: #777;
            font-size: 0.95em;
        }
        .room-card-content .details span {
            display: flex;
            align-items: center;
        }
        .room-card-content .details i {
            color: var(--color-secondary);
            margin-right: 8px; /* Tăng khoảng cách */
        }
        .room-card-content .desc {
            margin-bottom: 20px;
            color: #555;
            font-size: 0.9em;
            min-height: 50px; /* Giúp các thẻ có chiều cao tương đối bằng nhau */
        }

        /* --- CSS CHO CÁC NÚT HÀNH ĐỘNG --- */
        .room-actions {
            display: flex; /* Hiển thị các nút trên cùng một hàng */
            gap: 15px; /* Khoảng cách giữa các nút */
            margin-top: 20px; /* Khoảng cách với mô tả phòng */
        }
        .btn-action {
            display: block;
            width: 50%; /* Chia đều 50% cho mỗi nút */
            padding: 12px;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            text-transform: uppercase;
            transition: background-color 0.3s, border-color 0.3s, color 0.3s;
        }
        /* Style cho nút Đặt Phòng Ngay (Primary) */
        .btn-book {
            background-color: var(--color-primary);
            color: var(--color-white);
        }
        .btn-book:hover {
            background-color: var(--color-secondary);
        }
        /* Style cho nút Xem Chi Tiết (Secondary/Outline) */
        .btn-detail {
            background-color: var(--color-white);
            color: var(--color-primary);
            border: 1px solid var(--color-primary);
        }
        .btn-detail:hover {
            background-color: var(--color-primary);
            color: var(--color-white);
            border-color: var(--color-primary);
        }

        /* CSS CHO PHÂN TRANG */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 60px;
            padding: 30px 20px;
            flex-wrap: wrap;
        }

        .pagination-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            padding: 0 12px;
            background-color: var(--color-white);
            color: var(--color-primary);
            border: 2px solid #ddd;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .pagination-link:hover {
            background-color: var(--color-secondary);
            color: var(--color-white);
            border-color: var(--color-secondary);
            transform: translateY(-2px);
        }

        .pagination-link.active {
            background: linear-gradient(135deg, var(--color-secondary), var(--color-primary));
            color: var(--color-white);
            border-color: transparent;
            box-shadow: 0 8px 20px rgba(163, 140, 113, 0.3);
        }

        .pagination-prev,
        .pagination-next {
            width: auto;
            padding: 0 16px;
            min-width: 120px;
        }

        .pagination-dots {
            color: #999;
            font-weight: 700;
            padding: 0 6px;
        }

        @media (max-width: 768px) {
            .pagination-link {
                width: 38px;
                height: 38px;
                font-size: 0.9em;
            }

            .pagination-prev,
            .pagination-next {
                font-size: 0.85em;
                padding: 0 12px;
                min-width: auto;
            }

            .pagination {
                gap: 4px;
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
            <a href="rooms.php" class="active">PHÒNG & GIÁ</a>
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

    <header class="hero-section-rooms">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>CÁC LOẠI PHÒNG & GIÁ</h1>
            <p>Khám phá không gian nghỉ dưỡng lý tưởng với mức giá tốt nhất tại The Cappa.</p>
        </div>
    </header>

    <main class="main-content">
        
        <div class="section-header" data-animate-parent>
            <p class="subtitle" data-animate>SỰ LỰA CHỌN HOÀN HẢO</p>
            <h2 data-animate>Danh sách các phòng tại khách sạn</h2>
        </div>

        <div class="filter-bar">
            <form method="GET" action="rooms.php">
                <select name="sort" id="sort" onchange="this.form.submit()">
                    <option value="price_asc" <?php echo ($sort_by == 'price_asc') ? 'selected' : ''; ?>>Sắp xếp theo Giá (Thấp nhất)</option>
                    <option value="price_desc" <?php echo ($sort_by == 'price_desc') ? 'selected' : ''; ?>>Sắp xếp theo Giá (Cao nhất)</option>
                    <option value="area_desc" <?php echo ($sort_by == 'area_desc') ? 'selected' : ''; ?>>Sắp xếp theo Diện tích (Lớn nhất)</option>
                </select>
                <select name="guests" id="guests" onchange="this.form.submit()">
                    <option value="">Số khách (Tất cả)</option>
                    <?php 
                        foreach ($unique_guests as $guest_count) {
                            $selected = ($guest_filter == $guest_count) ? 'selected' : '';
                            echo "<option value='{$guest_count}' {$selected}>{$guest_count} Khách</option>";
                        }
                    ?>
                </select>
                <button type="submit"><i class="fas fa-search"></i> Tìm kiếm</button>
            </form>
        </div>

        <div class="room-grid">
            <?php if (empty($paginated_rooms)): ?>
                <p style="grid-column: 1 / -1; text-align: center; font-size: 1.2em; color: #777;">Không tìm thấy phòng phù hợp với tiêu chí tìm kiếm hoặc không kết nối được với dữ liệu.</p>
            <?php else: ?>
                <?php $delay = 0; foreach ($paginated_rooms as $room): $delay += 0.1; ?>
                <div class="room-card" data-animate style="transition-delay: <?php echo $delay; ?>s;">
                    <div class="room-card-image">
                        <img src="<?php echo $room['img']; ?>" alt="Ảnh phòng <?php echo $room['name']; ?>" loading="lazy">
                    </div>
                    <div class="room-card-content">
                        <h3><?php echo $room['name']; ?></h3>
                        
                        <div class="details">
                            <span><i class="fas fa-ruler-combined"></i> Diện tích: <?php echo $room['area']; ?></span>
                            <span><i class="fas fa-users"></i> Tối đa: <?php echo $room['guests']; ?> khách</span>
                            <span><i class="fas fa-bed"></i> Còn trống: <?php echo $room['available_rooms']; ?> phòng</span>
                        </div>

                        <p class="desc"><?php echo $room['desc']; ?></p>

                        <p class="price"><?php echo format_currency($room['price']); ?> / Đêm</p>
                        
                        <div class="room-actions">
                            <a href="room_detail.php?room_id=<?php echo $room['id']; ?>" class="btn-action btn-detail">
                                XEM CHI TIẾT
                            </a>
                            <a href="booking.php?room_id=<?php echo $room['id']; ?>" class="btn-action btn-book">
                                ĐẶT PHÒNG NGAY
                            </a>
                        </div>

                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- PHÂN TRANG -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php 
            // Nút "Trang trước"
            if ($current_page > 1): 
                $prev_url = "rooms.php?page=" . ($current_page - 1);
                if (!empty($sort_by) && $sort_by != 'price_asc') $prev_url .= "&sort=" . urlencode($sort_by);
                if (!empty($guest_filter)) $prev_url .= "&guests=" . urlencode($guest_filter);
            ?>
                <a href="<?php echo $prev_url; ?>" class="pagination-link pagination-prev">
                    <i class="fas fa-chevron-left"></i> Trang trước
                </a>
            <?php endif; ?>

            <!-- Số trang -->
            <?php 
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            
            if ($start_page > 1): ?>
                <a href="rooms.php?page=1<?php if (!empty($sort_by) && $sort_by != 'price_asc') echo "&sort=" . urlencode($sort_by); if (!empty($guest_filter)) echo "&guests=" . urlencode($guest_filter); ?>" class="pagination-link">1</a>
                <?php if ($start_page > 2): ?>
                    <span class="pagination-dots">...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start_page; $i <= $end_page; $i++): 
                $page_url = "rooms.php?page=" . $i;
                if (!empty($sort_by) && $sort_by != 'price_asc') $page_url .= "&sort=" . urlencode($sort_by);
                if (!empty($guest_filter)) $page_url .= "&guests=" . urlencode($guest_filter);
                $is_current = ($i == $current_page);
            ?>
                <a href="<?php echo $page_url; ?>" class="pagination-link <?php echo $is_current ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($end_page < $total_pages): 
                if ($end_page < $total_pages - 1): ?>
                    <span class="pagination-dots">...</span>
                <?php endif; ?>
                <a href="rooms.php?page=<?php echo $total_pages; ?><?php if (!empty($sort_by) && $sort_by != 'price_asc') echo "&sort=" . urlencode($sort_by); if (!empty($guest_filter)) echo "&guests=" . urlencode($guest_filter); ?>" class="pagination-link"><?php echo $total_pages; ?></a>
            <?php endif; ?>

            <!-- Nút "Trang tiếp" -->
            <?php if ($current_page < $total_pages): 
                $next_url = "rooms.php?page=" . ($current_page + 1);
                if (!empty($sort_by) && $sort_by != 'price_asc') $next_url .= "&sort=" . urlencode($sort_by);
                if (!empty($guest_filter)) $next_url .= "&guests=" . urlencode($guest_filter);
            ?>
                <a href="<?php echo $next_url; ?>" class="pagination-link pagination-next">
                    Trang tiếp <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
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
        document.addEventListener('DOMContentLoaded', function() {
            const mainNav = document.getElementById('mainNav');
            const scrollThreshold = 100; 
            
            function checkVisibility() {
                // Kiểm tra và hiển thị tiêu đề (section-header)
                const headerToAnimate = document.querySelector('.section-header[data-animate-parent]');
                if (headerToAnimate) {
                    const rect = headerToAnimate.getBoundingClientRect();
                    if (rect.top < window.innerHeight * 0.8 && rect.bottom > 0) {
                        headerToAnimate.classList.add('visible');
                    }
                }

                // Kiểm tra và hiển thị các thẻ phòng (room-card)
                const elementsToAnimate = document.querySelectorAll('.room-card[data-animate]');
                elementsToAnimate.forEach(el => {
                    const rect = el.getBoundingClientRect();
                    if (rect.top < window.innerHeight * 0.9 && rect.bottom > 0) {
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
                
                // 2. Animation Logic (Fade-in khi cuộn)
                checkVisibility();
            }

            window.addEventListener('scroll', handleScroll);
            handleScroll(); // Chạy lần đầu để hiển thị các phần tử nằm trong viewport khi tải trang

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