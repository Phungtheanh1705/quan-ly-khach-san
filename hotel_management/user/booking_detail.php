<?php
// user/booking_detail.php - Chi tiết booking và hóa đơn
include "../config/db.php";
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['user_username'] ?? '';
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$booking_data = [];
$error_message = '';

// Lấy thông tin booking
if ($booking_id > 0) {
    $sql = "SELECT 
                b.id,
                b.user_id,
                b.room_id,
                b.check_in,
                b.check_out,
                b.status,
                b.payment_method,
                b.notes,
                b.created_at,
                rt.type_name AS room_name,
                rt.max_guests,
                rt.area_sqm,
                r.room_number,
                r.price,
                u.username
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN room_types rt ON r.type_id = rt.id
            JOIN users u ON b.user_id = u.id
            WHERE b.id = ? AND b.user_id = ?
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $booking_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $booking_data = $result->fetch_assoc();
        } else {
            $error_message = "Không tìm thấy booking này hoặc không phải của bạn.";
        }
        $stmt->close();
    }
} else {
    $error_message = "ID booking không hợp lệ.";
}

function format_currency($amount) {
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}

function format_date($date) {
    return date('d/m/Y', strtotime($date));
}

function format_datetime($date) {
    return date('d/m/Y H:i', strtotime($date));
}

function get_status_badge($status) {
    $badges = [
        'pending' => ['icon' => 'fas fa-clock', 'color' => '#ff9800', 'text' => 'Chờ xác nhận'],
        'confirmed' => ['icon' => 'fas fa-check-circle', 'color' => '#4CAF50', 'text' => 'Đã xác nhận'],
        'checked_in' => ['icon' => 'fas fa-sign-in-alt', 'color' => '#2196F3', 'text' => 'Đã nhận phòng'],
        'checked_out' => ['icon' => 'fas fa-sign-out-alt', 'color' => '#9C27B0', 'text' => 'Đã trả phòng'],
        'cancelled' => ['icon' => 'fas fa-times-circle', 'color' => '#f44336', 'text' => 'Đã hủy'],
    ];
    
    return $badges[$status] ?? ['icon' => 'fas fa-question-circle', 'color' => '#999', 'text' => $status];
}

// Tính số đêm và tổng tiền
$nights = 0;
$total_price = 0;
if (!empty($booking_data)) {
    $check_in = strtotime($booking_data['check_in']);
    $check_out = strtotime($booking_data['check_out']);
    $nights = ceil(($check_out - $check_in) / 86400);
    $total_price = $nights * $booking_data['price'];
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHI TIẾT BOOKING & HÓA ĐƠN - THE CAPPA LUXURY HOTEL</title>
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
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            background: rgba(0, 0, 0, 0.15);
            height: 60px;
            z-index: 1000;
            transition: all 0.3s;
        }

        .navbar.scrolled {
            background: var(--color-white);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .logo a {
            font-family: 'Lora', serif;
            font-size: 1.2rem;
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
            color: var(--color-white);
            margin-left: 20px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-links a:hover {
            color: var(--color-secondary);
        }

        .navbar.scrolled .nav-links a {
            color: var(--color-primary);
        }

        .user-menu {
            position: relative;
            margin-left: 25px;
        }

        .user-icon {
            font-size: 1.8em;
            color: var(--color-white);
            cursor: pointer;
            transition: all 0.3s;
        }

        .navbar.scrolled .user-icon {
            color: var(--color-primary);
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: var(--color-white);
            min-width: 180px;
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 10;
            border-radius: 8px;
        }

        .dropdown-content.show {
            display: block;
        }

        .dropdown-content a {
            color: var(--color-primary);
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-weight: 500;
        }

        .dropdown-content a:hover {
            background: #f1f1f1;
            color: var(--color-secondary);
        }

        .container {
            max-width: 1000px;
            margin: 80px auto;
            padding: 20px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 30px;
            color: var(--color-secondary);
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            transform: translateX(-5px);
        }

        .header {
            text-align: center;
            margin-bottom: 50px;
        }

        .header h1 {
            font-family: 'Lora', serif;
            font-size: 2.2rem;
            margin-bottom: 10px;
        }

        .alert-error {
            background: #ffe0e0;
            border-left: 5px solid #dc3545;
            color: #dc3545;
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .invoice-container {
            background: var(--color-white);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .invoice-header {
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 25px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: start;
        }

        .invoice-title {
            font-family: 'Lora', serif;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .invoice-id {
            color: #888;
            font-size: 0.95em;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: #f5f5f5;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9em;
        }

        .section {
            margin-bottom: 35px;
        }

        .section-title {
            font-family: 'Lora', serif;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--color-secondary);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-label {
            color: #888;
            font-weight: 500;
        }

        .info-value {
            font-weight: 700;
            color: var(--color-primary);
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-box {
            background: #fafafa;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .info-box-label {
            color: #888;
            font-size: 0.9em;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .info-box-value {
            font-size: 1.1em;
            font-weight: 700;
            color: var(--color-primary);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: var(--color-primary);
            color: var(--color-white);
            padding: 12px;
            text-align: left;
            font-weight: 700;
        }

        .table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table-total {
            background: #fafafa;
            font-weight: 700;
        }

        .price-highlight {
            font-size: 1.3em;
            color: var(--color-secondary);
        }

        .footer {
            margin-top: 40px;
            padding-top: 25px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            color: #888;
            font-size: 0.9em;
        }

        .print-button {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 30px;
            background: linear-gradient(135deg, var(--color-secondary), var(--color-primary));
            color: var(--color-white);
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }

        .print-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(163, 140, 113, 0.3);
        }

        @media print {
            body, .navbar, .back-link, .print-button {
                display: none;
            }
            .invoice-container {
                box-shadow: none;
                padding: 0;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .invoice-header {
                flex-direction: column;
                gap: 15px;
            }

            .invoice-container {
                padding: 25px;
            }
        }
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
    <a href="dashboard.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách
    </a>

    <div class="header">
        <h1><i class="fas fa-receipt"></i> HÓA ĐƠN BOOKING</h1>
    </div>

    <?php if (!empty($error_message)): ?>
    <div class="alert-error">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($booking_data)): ?>
    <div class="invoice-container">
        <!-- Invoice Header -->
        <div class="invoice-header">
            <div>
                <div class="invoice-title">THE CAPPA LUXURY HOTEL</div>
                <div class="invoice-id">Booking #<?php echo $booking_data['id']; ?></div>
            </div>
            <?php 
            $status_info = get_status_badge($booking_data['status']);
            ?>
            <div class="status-badge" style="background-color: rgba(<?php 
                $color = $status_info['color'];
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

        <!-- Customer Info -->
        <div class="section">
            <div class="section-title">Thông tin khách hàng</div>
            <div class="info-grid">
                <div class="info-box">
                    <div class="info-box-label">Họ và tên</div>
                    <div class="info-box-value"><?php echo htmlspecialchars($booking_data['username']); ?></div>
                </div>
                <div class="info-box">
                    <div class="info-box-label">Mã khách hàng</div>
                    <div class="info-box-value">#<?php echo $booking_data['user_id']; ?></div>
                </div>
                <div class="info-box">
                    <div class="info-box-label">Ngày đặt</div>
                    <div class="info-box-value"><?php echo format_datetime($booking_data['created_at']); ?></div>
                </div>
                <div class="info-box">
                    <div class="info-box-label">Phương thức thanh toán</div>
                    <div class="info-box-value">
                        <?php 
                        $methods = ['cod' => 'Thanh toán tại quán', 'vnpay' => 'VNPay', 'bank' => 'Chuyển khoản'];
                        echo $methods[$booking_data['payment_method']] ?? $booking_data['payment_method'];
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Room Info -->
        <div class="section">
            <div class="section-title">Thông tin phòng</div>
            <div class="info-grid">
                <div class="info-box">
                    <div class="info-box-label">Loại phòng</div>
                    <div class="info-box-value"><?php echo htmlspecialchars($booking_data['room_name']); ?></div>
                </div>
                <div class="info-box">
                    <div class="info-box-label">Số phòng</div>
                    <div class="info-box-value"><?php echo htmlspecialchars($booking_data['room_number']); ?></div>
                </div>
                <div class="info-box">
                    <div class="info-box-label">Diện tích</div>
                    <div class="info-box-value"><?php echo $booking_data['area_sqm']; ?> m²</div>
                </div>
                <div class="info-box">
                    <div class="info-box-label">Sức chứa</div>
                    <div class="info-box-value">Tối đa <?php echo $booking_data['max_guests']; ?> khách</div>
                </div>
            </div>
        </div>

        <!-- Booking Info -->
        <div class="section">
            <div class="section-title">Thông tin đặt phòng</div>
            <div class="info-row">
                <span class="info-label">Ngày nhận phòng:</span>
                <span class="info-value"><?php echo format_date($booking_data['check_in']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Ngày trả phòng:</span>
                <span class="info-value"><?php echo format_date($booking_data['check_out']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Số đêm:</span>
                <span class="info-value"><?php echo $nights; ?> đêm</span>
            </div>
        </div>

        <!-- Price Summary -->
        <div class="section">
            <div class="section-title">Chi tiết giá</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Mô tả</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($booking_data['room_name']); ?></td>
                        <td><?php echo $nights; ?> đêm</td>
                        <td><?php echo format_currency($booking_data['price']); ?></td>
                        <td><?php echo format_currency($total_price); ?></td>
                    </tr>
                    <tr class="table-total">
                        <td colspan="3" style="text-align: right;">Tổng cộng:</td>
                        <td><span class="price-highlight"><?php echo format_currency($total_price); ?></span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Notes -->
        <?php if (!empty($booking_data['notes'])): ?>
        <div class="section">
            <div class="section-title">Ghi chú</div>
            <div class="info-box">
                <p><?php echo htmlspecialchars($booking_data['notes']); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <p>Cảm ơn bạn đã chọn The Cappa Luxury Hotel!</p>
            <p style="margin-top: 10px; font-size: 0.85em;">Địa chỉ: Số 147 Mai Dịch, Cầu Giấy, Hà Nội | Điện thoại: 0242 242 0777</p>
        </div>

        <button class="print-button" onclick="window.print();">
            <i class="fas fa-print"></i> In hóa đơn
        </button>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mainNav = document.getElementById('mainNav');
    const userIcon = document.getElementById('userIcon');
    const dropdown = document.querySelector('.dropdown-content');

    window.addEventListener('scroll', function() {
        if (window.scrollY > 100) {
            mainNav.classList.add('scrolled');
        } else {
            mainNav.classList.remove('scrolled');
        }
    });

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
</script>

</body>
</html>
