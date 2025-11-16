<?php
// user/dashboard.php - Xem danh sách đơn đặt phòng
include "../config/db.php";
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['user_username'] ?? '';
$success_message = '';
$error_message = '';

// Xử lý hủy booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    
    if ($booking_id > 0) {
        // Kiểm tra booking có thuộc về user không
        $sql_check = "SELECT id FROM bookings WHERE id = ? AND user_id = ? LIMIT 1";
        $stmt_check = $conn->prepare($sql_check);
        if ($stmt_check) {
            $stmt_check->bind_param("ii", $booking_id, $user_id);
            $stmt_check->execute();
            $res_check = $stmt_check->get_result();
            
            if ($res_check->num_rows > 0) {
                // Cập nhật status thành cancelled
                $sql_update = "UPDATE bookings SET status = 'cancelled' WHERE id = ? LIMIT 1";
                $stmt_update = $conn->prepare($sql_update);
                if ($stmt_update) {
                    $stmt_update->bind_param("i", $booking_id);
                    if ($stmt_update->execute()) {
                        $success_message = "✓ Đã hủy booking thành công!";
                    } else {
                        $error_message = "Lỗi cập nhật: " . $stmt_update->error;
                    }
                    $stmt_update->close();
                }
            } else {
                $error_message = "Booking không tồn tại hoặc không phải của bạn.";
            }
            $stmt_check->close();
        }
    }
}

// Lấy danh sách booking của user
$bookings = [];
$sql = "SELECT 
            b.id,
            b.room_id,
            b.check_in,
            b.check_out,
            b.status,
            b.created_at,
            rt.type_name AS room_name,
            r.room_number,
            r.price
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN room_types rt ON r.type_id = rt.id
        WHERE b.user_id = ?
        ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
    }
    $stmt->close();
}

// --- PHÂN TRANG ---
$items_per_page = 6; // Số booking hiển thị mỗi trang
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$total_items = count($bookings);
$total_pages = max(1, ceil($total_items / $items_per_page));

// Đảm bảo current_page không vượt quá tổng số trang
if ($current_page > $total_pages) {
    $current_page = $total_pages;
}

// Lấy dữ liệu cho trang hiện tại
$start_index = ($current_page - 1) * $items_per_page;
$paginated_bookings = array_slice($bookings, $start_index, $items_per_page);

function format_currency($amount) {
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}

function format_date($date) {
    return date('d/m/Y', strtotime($date));
}

function get_status_badge($status) {
    $badges = [
        'pending' => ['icon' => 'fas fa-clock', 'color' => '#ff9800', 'text' => 'Chờ xác nhận'],
        'confirmed' => ['icon' => 'fas fa-check-circle', 'color' => '#4CAF50', 'text' => 'Đã xác nhận'],
        'checked_in' => ['icon' => 'fas fa-sign-in-alt', 'color' => '#2196F3', 'text' => 'Đã nhận phòng'],
        'checked_out' => ['icon' => 'fas fa-sign-out-alt', 'color' => '#9C27B0', 'text' => 'Đã trả phòng'],
        'cancelled' => ['icon' => 'fas fa-times-circle', 'color' => '#f44336', 'text' => 'Đã hủy'],
    ];
    
    $badge = $badges[$status] ?? ['icon' => 'fas fa-question-circle', 'color' => '#999', 'text' => $status];
    return $badge;
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DANH SÁCH ĐẶT PHÒNG - THE CAPPA LUXURY HOTEL</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --color-primary: #524741;
            --color-secondary: #a38c71;
            --color-background: #f7f3ed;
            --color-white: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            color: var(--color-primary);
            background-color: var(--color-background);
            padding-top: 60px;
        }

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
            transition: all 0.4s ease;
            height: 60px;
        }

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
        }

        .navbar.scrolled .logo a {
            color: var(--color-primary);
        }

        .nav-links {
            display: flex;
            align-items: center;
            margin-left: auto;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--color-white);
            margin-left: 25px;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.9em;
        }

        .navbar.scrolled .nav-links a {
            color: var(--color-primary);
        }

        .nav-links a:hover {
            color: var(--color-secondary);
        }

        .user-menu {
            position: relative;
            margin-left: 25px;
        }

        .user-icon {
            font-size: 1.8em;
            color: var(--color-white);
            cursor: pointer;
        }

        .navbar.scrolled .user-icon {
            color: var(--color-primary);
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: var(--color-white);
            min-width: 180px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
            z-index: 10;
            border-radius: 5px;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
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
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
            color: var(--color-secondary);
        }

        .container {
            max-width: 1200px;
            margin: 80px auto;
            padding: 0 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 60px;
        }

        .header h1 {
            font-family: 'Lora', serif;
            font-size: 3em;
            margin-bottom: 15px;
            color: var(--color-primary);
        }

        .header p {
            font-size: 1.2em;
            color: #888;
        }

        .alert {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .alert-error {
            background-color: #ffe0e0;
            border-left: 5px solid #dc3545;
            color: #dc3545;
        }

        .alert-success {
            background-color: #e0ffe0;
            border-left: 5px solid #28a745;
            color: #28a745;
        }

        .booking-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 80px;
        }

        .booking-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(250, 250, 250, 0.95));
            border-radius: 18px;
            padding: 0;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            border: 1px solid rgba(163, 140, 113, 0.1);
            overflow: hidden;
            position: relative;
        }

        .booking-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--color-secondary), var(--color-primary));
        }

        .booking-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 25px 70px rgba(82, 71, 65, 0.15);
            border-color: rgba(163, 140, 113, 0.3);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0;
            padding: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background: linear-gradient(135deg, rgba(163, 140, 113, 0.02), rgba(82, 71, 65, 0.02));
        }

        .booking-id {
            font-size: 0.85em;
            color: #999;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .booking-room-name {
            font-family: 'Lora', serif;
            font-size: 1.4em;
            color: var(--color-primary);
            font-weight: 700;
            margin-top: 5px;
        }

        .booking-room-name small {
            font-family: 'Roboto', sans-serif;
            font-size: 0.5em;
            display: block;
            margin-top: 6px;
            color: #b0a99f;
            font-weight: 400;
        }

        .status-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 25px;
            background-color: #f5f5f5;
            font-weight: 600;
            font-size: 0.82em;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .booking-info {
            margin: 0;
            line-height: 1.6;
            padding: 20px 25px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.04);
            font-size: 0.95em;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #888;
            font-weight: 500;
            font-size: 0.9em;
        }

        .info-value {
            color: var(--color-primary);
            font-weight: 700;
            text-align: right;
        }

        .booking-actions {
            display: flex;
            gap: 10px;
            margin-top: 0;
            padding: 20px 25px;
            background: linear-gradient(135deg, rgba(163, 140, 113, 0.02), rgba(82, 71, 65, 0.01));
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .btn {
            flex: 1;
            padding: 12px 14px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-view {
            background: linear-gradient(135deg, var(--color-secondary), var(--color-primary));
            color: var(--color-white);
            box-shadow: 0 8px 25px rgba(163, 140, 113, 0.25);
        }

        .btn-view:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(163, 140, 113, 0.35);
        }

        .btn-cancel {
            background-color: #fff5f5;
            color: #d32f2f;
            border: 2px solid #ffcccc;
        }

        .btn-cancel:hover {
            background-color: #d32f2f;
            color: var(--color-white);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(211, 47, 47, 0.25);
        }

        .empty-state {
            text-align: center;
            padding: 100px 20px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.5), rgba(250, 250, 250, 0.5));
            border-radius: 18px;
            border: 2px dashed rgba(163, 140, 113, 0.2);
        }

        .empty-state-icon {
            font-size: 5em;
            background: linear-gradient(135deg, var(--color-secondary), var(--color-primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 25px;
            opacity: 0.6;
        }

        .empty-state-text {
            font-size: 1.4em;
            color: #999;
            margin-bottom: 35px;
            font-weight: 500;
        }

        .btn-book {
            display: inline-block;
            padding: 16px 50px;
            background: linear-gradient(135deg, var(--color-secondary), var(--color-primary));
            color: var(--color-white);
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            text-transform: uppercase;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            letter-spacing: 1px;
            box-shadow: 0 12px 35px rgba(163, 140, 113, 0.3);
        }

        .btn-book:hover {
            transform: translateY(-5px);
            box-shadow: 0 18px 50px rgba(163, 140, 113, 0.4);
        }

        .footer {
            background-color: #1a1a1a;
            color: #fff;
            padding: 60px 50px 20px;
            font-size: 0.9em;
            margin-top: 80px;
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

        .footer-col {
            flex: 1;
        }

        .footer-col h3 {
            font-family: 'Lora', serif;
            font-size: 1.5em;
            font-weight: 700;
            color: var(--color-secondary);
            margin-bottom: 20px;
        }

        .footer-col p {
            color: #b7b7b7;
            line-height: 1.6;
        }

        .copyright {
            text-align: center;
            color: #777;
            font-size: 0.85em;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: var(--color-white);
            margin: 100px auto;
            padding: 40px;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 80px rgba(0, 0, 0, 0.25);
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-title {
            font-family: 'Lora', serif;
            font-size: 1.6em;
            color: var(--color-primary);
            margin-bottom: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-title i {
            font-size: 1.3em;
        }

        .modal-text {
            color: #666;
            margin-bottom: 35px;
            line-height: 1.7;
            font-size: 1.05em;
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
        }

        .btn-modal {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-modal-confirm {
            background: linear-gradient(135deg, #d32f2f, #c62828);
            color: var(--color-white);
            box-shadow: 0 8px 25px rgba(211, 47, 47, 0.3);
        }

        .btn-modal-confirm:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(211, 47, 47, 0.4);
        }

        .btn-modal-cancel {
            background-color: #f5f5f5;
            color: var(--color-primary);
            border: 2px solid #e0e0e0;
        }

        .btn-modal-cancel:hover {
            background-color: #e0e0e0;
            transform: translateY(-3px);
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

        .pagination-info {
            text-align: center;
            color: #888;
            font-size: 0.95em;
            margin-bottom: 20px;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .booking-cards {
                grid-template-columns: 1fr;
            }

            .header h1 {
                font-size: 2.2em;
            }

            .nav-links a {
                margin-left: 15px;
                font-size: 0.8em;
            }

            .navbar {
                padding: 15px 20px;
            }

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
        <a href="rooms.php">PHÒNG & GIÁ</a>
        <a href="index.php#services">DỊCH VỤ</a>
        <a href="contact.php">LIÊN HỆ</a>
        <div class="user-menu">
        <div class="user-menu">
            <i class="fas fa-user-circle user-icon" id="userIcon"></i>
            <div class="dropdown-content">
                <a href="profile.php">Thông tin cá nhân</a>
                <a href="dashboard.php">Đơn đặt phòng</a>
                <a href="logout.php" style="color:#dc3545;">Đăng xuất</a>
            </div>
        </div>
    </div>
</nav>

<div class="container">
    <div class="header">
        <h1>📋 DANH SÁCH ĐẶT PHÒNG</h1>
        <p>Xem và quản lý các đơn đặt phòng của bạn tại The Cappa Luxury Hotel</p>
    </div>

    <?php if (!empty($error_message)): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <span><?php echo $error_message; ?></span>
    </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <span><?php echo $success_message; ?></span>
    </div>
    <?php endif; ?>

    <?php if (count($bookings) > 0): ?>

    <div class="booking-cards">
        <?php foreach ($paginated_bookings as $booking): 
            $status_info = get_status_badge($booking['status']);
            $check_in = strtotime($booking['check_in']);
            $check_out = strtotime($booking['check_out']);
            $nights = ceil(($check_out - $check_in) / 86400);
        ?>
        <div class="booking-card">
            <div class="booking-header">
                <div>
                    <div class="booking-id">#<?php echo $booking['id']; ?></div>
                    <div class="booking-room-name">
                        <?php echo htmlspecialchars($booking['room_name']); ?>
                        <small style="font-size: 0.6em; display: block; margin-top: 5px; color: #999;">Phòng <?php echo htmlspecialchars($booking['room_number']); ?></small>
                    </div>
                </div>
                <div class="status-badge" style="background-color: rgba(<?php 
                    $color = $status_info['color'];
                    // Convert hex to RGB
                    if (strpos($color, '#') === 0) {
                        $hex = str_replace('#', '', $color);
                        $r = hexdec(substr($hex, 0, 2));
                        $g = hexdec(substr($hex, 2, 2));
                        $b = hexdec(substr($hex, 4, 2));
                        echo "$r, $g, $b";
                    }
                ?>, 0.1); color: <?php echo $status_info['color']; ?>;">
                    <i class="<?php echo $status_info['icon']; ?>"></i>
                    <?php echo $status_info['text']; ?>
                </div>
            </div>

            <div class="booking-info">
                <div class="info-row">
                    <span class="info-label">Ngày nhận:</span>
                    <span class="info-value"><?php echo format_date($booking['check_in']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Ngày trả:</span>
                    <span class="info-value"><?php echo format_date($booking['check_out']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Số đêm:</span>
                    <span class="info-value"><?php echo $nights; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Giá phòng:</span>
                    <span class="info-value"><?php echo format_currency($booking['price']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Đặt ngày:</span>
                    <span class="info-value"><?php echo format_date($booking['created_at']); ?></span>
                </div>
            </div>

            <div class="booking-actions">
                <a href="booking_detail.php?id=<?php echo $booking['id']; ?>" class="btn btn-view">
                    <i class="fas fa-eye"></i> XEM CHI TIẾT
                </a>
                <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                <button type="button" class="btn btn-cancel" onclick="openCancelModal(<?php echo $booking['id']; ?>)">
                    <i class="fas fa-times"></i> HỦY PHÒNG
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- PHÂN TRANG -->
    <div class="pagination-info">
        Hiển thị trang <?php echo $current_page; ?> / <?php echo $total_pages; ?> (Tổng <?php echo $total_items; ?> đơn đặt phòng)
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php 
        // Nút "Trang trước"
        if ($current_page > 1): 
        ?>
            <a href="dashboard.php?page=<?php echo $current_page - 1; ?>" class="pagination-link pagination-prev">
                <i class="fas fa-chevron-left"></i> Trang trước
            </a>
        <?php endif; ?>

        <!-- Số trang -->
        <?php 
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        if ($start_page > 1): ?>
            <a href="dashboard.php?page=1" class="pagination-link">1</a>
            <?php if ($start_page > 2): ?>
                <span class="pagination-dots">...</span>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start_page; $i <= $end_page; $i++): 
            $is_current = ($i == $current_page);
        ?>
            <a href="dashboard.php?page=<?php echo $i; ?>" class="pagination-link <?php echo $is_current ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($end_page < $total_pages): 
            if ($end_page < $total_pages - 1): ?>
                <span class="pagination-dots">...</span>
            <?php endif; ?>
            <a href="dashboard.php?page=<?php echo $total_pages; ?>" class="pagination-link"><?php echo $total_pages; ?></a>
        <?php endif; ?>

        <!-- Nút "Trang tiếp" -->
        <?php if ($current_page < $total_pages): 
        ?>
            <a href="dashboard.php?page=<?php echo $current_page + 1; ?>" class="pagination-link pagination-next">
                Trang tiếp <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>

    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="fas fa-inbox"></i>
        </div>
        <div class="empty-state-text">
            Bạn chưa có đơn đặt phòng nào 📭
        </div>
        <p style="color: #999; margin-bottom: 30px;">Hãy khám phá những phòng tuyệt đẹp và đặt phòng ngay hôm nay!</p>
        <a href="rooms.php" class="btn-book">
            <i class="fas fa-door-open"></i> KHÁM PHÁ PHÒNG
        </a>
    </div>

    <?php endif; ?>
</div>

    <div id="cancelModal" class="modal">
    <div class="modal-content">
        <div class="modal-title">
            <i class="fas fa-exclamation-triangle" style="color: #d32f2f;"></i> Xác nhận hủy đơn
        </div>
        <div class="modal-text">
            Bạn chắc chắn muốn hủy đơn đặt phòng này? Hành động này không thể hoàn tác. Nếu đã thanh toán, vui lòng liên hệ khách sạn.
        </div>
        <form id="cancelForm" method="POST">
            <input type="hidden" name="booking_id" id="bookingIdInput" value="">
            <div class="modal-buttons">
                <button type="submit" name="cancel_booking" class="btn-modal btn-modal-confirm">
                    <i class="fas fa-check"></i> Xác nhận hủy
                </button>
                <button type="button" class="btn-modal btn-modal-cancel" onclick="closeCancelModal()">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </button>
            </div>
        </form>
    </div>
</div>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-col">
            <h3>THE CAPPA LUXURY HOTEL</h3>
            <p>Sở hữu những đường cong duyên dáng được thiết kế hài hòa với cảnh quan đường phố.</p>
        </div>
        <div class="footer-col">
            <h3>LIÊN HỆ</h3>
            <p>Số 147 Mai Dịch, Cầu Giấy, Hà Nội</p>
            <p>Điện thoại: 0242 242 0777</p>
            <p>Email: Info@webhotel.vn</p>
        </div>
    </div>
    <div class="copyright">
        <p>Copyright © THE CAPPA LUXURY HOTEL. <?php echo date("Y"); ?> All Rights Reserved</p>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mainNav = document.getElementById('mainNav');
    const userIcon = document.getElementById('userIcon');
    const dropdown = document.querySelector('.dropdown-content');

    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        if (window.scrollY > 100) {
            mainNav.classList.add('scrolled');
        } else {
            mainNav.classList.remove('scrolled');
        }
    });

    // Dropdown menu
    if (userIcon && dropdown) {
        userIcon.addEventListener('click', function(e) {
            dropdown.classList.toggle('show');
            e.stopPropagation();
        });

        document.addEventListener('click', function(e) {
            if (!userIcon.closest('.user-menu').contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    }
});

function openCancelModal(bookingId) {
    document.getElementById('bookingIdInput').value = bookingId;
    document.getElementById('cancelModal').classList.add('show');
}

function closeCancelModal() {
    document.getElementById('cancelModal').classList.remove('show');
}

// Đóng modal khi click ngoài
window.addEventListener('click', function(e) {
    const modal = document.getElementById('cancelModal');
    if (e.target === modal) {
        closeCancelModal();
    }
});
</script>

</body>
</html>
