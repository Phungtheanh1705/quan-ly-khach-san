<?php
// user/profile.php - Thông tin cá nhân người dùng
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
$user_data = [];

// Lấy thông tin người dùng
$sql_user = "SELECT id, username, created_at FROM users WHERE id = ? LIMIT 1";
$stmt_user = $conn->prepare($sql_user);
if ($stmt_user) {
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $res_user = $stmt_user->get_result();
    if ($res_user->num_rows > 0) {
        $user_data = $res_user->fetch_assoc();
    }
    $stmt_user->close();
}

// Xử lý cập nhật password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "Vui lòng điền đầy đủ thông tin.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Mật khẩu mới không khớp.";
    } elseif (strlen($new_password) < 6) {
        $error_message = "Mật khẩu mới phải ít nhất 6 ký tự.";
    } else {
        // Kiểm tra mật khẩu cũ
        $sql_check = "SELECT password_hash FROM users WHERE id = ? LIMIT 1";
        $stmt_check = $conn->prepare($sql_check);
        if ($stmt_check) {
            $stmt_check->bind_param("i", $user_id);
            $stmt_check->execute();
            $res_check = $stmt_check->get_result();
            if ($res_check->num_rows > 0) {
                $user_record = $res_check->fetch_assoc();
                if (password_verify($old_password, $user_record['password_hash'])) {
                    // Cập nhật mật khẩu mới
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql_update = "UPDATE users SET password_hash = ? WHERE id = ? LIMIT 1";
                    $stmt_update = $conn->prepare($sql_update);
                    if ($stmt_update) {
                        $stmt_update->bind_param("si", $new_hash, $user_id);
                        if ($stmt_update->execute()) {
                            $success_message = "✓ Cập nhật mật khẩu thành công!";
                        } else {
                            $error_message = "Lỗi cập nhật: " . $stmt_update->error;
                        }
                        $stmt_update->close();
                    }
                } else {
                    $error_message = "Mật khẩu cũ không chính xác.";
                }
            }
            $stmt_check->close();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>THÔNG TIN CÁ NHÂN - THE CAPPA LUXURY HOTEL</title>
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
            z-index: 2000;
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
            transition: color .18s ease, transform .18s ease;
            display: inline-block;
        }

        .nav-links a:hover {
            color: var(--color-secondary);
            transform: translateY(-3px);
        }

        .navbar.scrolled .nav-links a {
            color: var(--color-primary);
        }

        .user-menu {
            position: relative;
            margin-left: 25px;
            z-index: 2100;
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

        .user-icon:hover {
            transform: scale(1.1);
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: var(--color-white);
            min-width: 180px;
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 2200;
            border-radius: 8px;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s;
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
            font-weight: 500;
            transition: all 0.2s;
        }

        .dropdown-content a:hover {
            background: #f1f1f1;
            color: var(--color-secondary);
            padding-left: 20px;
        }

        .hero{height:160px;background:linear-gradient(135deg,rgba(82,71,65,0.8),rgba(163,140,113,0.6)),linear-gradient(rgba(0,0,0,0.22),rgba(0,0,0,0.22));background-size:cover;background-position:center;display:flex;align-items:center;justify-content:center;color:var(--color-white)}

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 60px;
        }

        .header h1 {
            font-family: 'Lora', serif;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1em;
            color: #888;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-error {
            background: #ffe0e0;
            border-left: 5px solid #dc3545;
            color: #dc3545;
        }

        .alert-success {
            background: #e0ffe0;
            border-left: 5px solid #28a745;
            color: #28a745;
        }

        .profile-section {
            background: linear-gradient(135deg, #fafafa, var(--color-white));
            padding: 40px;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .section-title {
            font-family: 'Lora', serif;
            font-size: 1.6rem;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--color-secondary);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: var(--color-secondary);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item {
            background: var(--color-white);
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .info-label {
            font-size: 0.9em;
            color: #888;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1.1em;
            color: var(--color-primary);
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--color-primary);
        }

        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Roboto', sans-serif;
            font-size: 1em;
            transition: all 0.3s;
        }

        input[type="password"]:focus,
        input[type="text"]:focus {
            outline: none;
            border-color: var(--color-secondary);
            box-shadow: 0 0 0 3px rgba(163, 140, 113, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn {
            display: inline-block;
            padding: 14px 30px;
            background: linear-gradient(135deg, var(--color-secondary), var(--color-primary));
            color: var(--color-white);
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            text-transform: uppercase;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            letter-spacing: 0.5px;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(163, 140, 113, 0.4);
        }

        .btn-secondary {
            background: #f5f5f5;
            color: var(--color-primary);
            margin-left: 12px;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .back-link {
            display: inline-block;
            margin-bottom: 30px;
            color: var(--color-secondary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-link:hover {
            transform: translateX(-5px);
        }

        .header-with-back {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 50px;
        }

        .header-content h1 {
            margin: 0;
        }

        .header-content p {
            margin: 8px 0 0 0;
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

        @media (max-width: 768px) {
            .container {
                margin: 80px auto;
                padding: 15px;
            }

            .profile-section {
                padding: 25px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .header h1 {
                font-size: 1.8rem;
            }

            .btn-secondary {
                margin-left: 0;
                margin-top: 10px;
                width: 100%;
            }

            .header-with-back {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
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
            <i class="fas fa-user-circle user-icon" id="userIcon"></i>
            <div class="dropdown-content">
                <a href="profile.php">Thông tin cá nhân</a>
                <a href="dashboard.php">Đơn đặt phòng</a>
                <a href="logout.php" style="color:#dc3545;">Đăng xuất</a>
            </div>
        </div>
    </div>
</nav>

<header class="hero" aria-hidden="true"></header>

<div class="container">
    <div class="header-with-back">
        <div class="header">
            <h1><i class="fas fa-user-circle"></i> THÔNG TIN CÁ NHÂN</h1>
            <p>Quản lý thông tin tài khoản của bạn</p>
        </div>
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
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

    <!-- Phần thông tin tài khoản -->
    <div class="profile-section">
        <div class="section-title">
            <i class="fas fa-id-card"></i> Thông tin tài khoản
        </div>

        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Tên đăng nhập</div>
                <div class="info-value"><?php echo htmlspecialchars($user_data['username'] ?? ''); ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Mã người dùng</div>
                <div class="info-value">#<?php echo htmlspecialchars($user_data['id'] ?? ''); ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Ngày đăng ký</div>
                <div class="info-value">
                    <?php 
                    if (!empty($user_data['created_at'])) {
                        echo date('d/m/Y H:i', strtotime($user_data['created_at']));
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Trạng thái</div>
                <div class="info-value" style="color: #28a745;">
                    <i class="fas fa-check-circle"></i> Hoạt động
                </div>
            </div>
        </div>
    </div>

    <!-- Phần đổi mật khẩu -->
    <div class="profile-section">
        <div class="section-title">
            <i class="fas fa-lock"></i> Đổi mật khẩu
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="old_password">Mật khẩu hiện tại</label>
                <input type="password" id="old_password" name="old_password" required placeholder="Nhập mật khẩu hiện tại">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="new_password">Mật khẩu mới</label>
                    <input type="password" id="new_password" name="new_password" required placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu mới</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Xác nhận mật khẩu mới">
                </div>
            </div>

            <div>
                <button type="submit" name="update_password" class="btn">
                    <i class="fas fa-save"></i> Cập nhật mật khẩu
                </button>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Hủy
                </a>
            </div>
        </form>
    </div>

    <!-- Phần thêm thông tin khác -->
    <div class="profile-section">
        <div class="section-title">
            <i class="fas fa-info-circle"></i> Thông tin bổ sung
        </div>

        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Tổng số đặt phòng</div>
                <div class="info-value">
                    <?php 
                    $sql_count = "SELECT COUNT(*) as cnt FROM bookings WHERE user_id = ? AND status != 'cancelled'";
                    $stmt_count = $conn->prepare($sql_count);
                    if ($stmt_count) {
                        $stmt_count->bind_param("i", $user_id);
                        $stmt_count->execute();
                        $res_count = $stmt_count->get_result();
                        $count_data = $res_count->fetch_assoc();
                        echo $count_data['cnt'] ?? 0;
                        $stmt_count->close();
                    }
                    ?>
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Đơn chưa xác nhận</div>
                <div class="info-value">
                    <?php 
                    $sql_pending = "SELECT COUNT(*) as cnt FROM bookings WHERE user_id = ? AND status = 'pending'";
                    $stmt_pending = $conn->prepare($sql_pending);
                    if ($stmt_pending) {
                        $stmt_pending->bind_param("i", $user_id);
                        $stmt_pending->execute();
                        $res_pending = $stmt_pending->get_result();
                        $pending_data = $res_pending->fetch_assoc();
                        echo $pending_data['cnt'] ?? 0;
                        $stmt_pending->close();
                    }
                    ?>
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Đơn đã xác nhận</div>
                <div class="info-value">
                    <?php 
                    $sql_confirmed = "SELECT COUNT(*) as cnt FROM bookings WHERE user_id = ? AND status = 'confirmed'";
                    $stmt_confirmed = $conn->prepare($sql_confirmed);
                    if ($stmt_confirmed) {
                        $stmt_confirmed->bind_param("i", $user_id);
                        $stmt_confirmed->execute();
                        $res_confirmed = $stmt_confirmed->get_result();
                        $confirmed_data = $res_confirmed->fetch_assoc();
                        echo $confirmed_data['cnt'] ?? 0;
                        $stmt_confirmed->close();
                    }
                    ?>
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Đơn đã hủy</div>
                <div class="info-value">
                    <?php 
                    $sql_cancelled = "SELECT COUNT(*) as cnt FROM bookings WHERE user_id = ? AND status = 'cancelled'";
                    $stmt_cancelled = $conn->prepare($sql_cancelled);
                    if ($stmt_cancelled) {
                        $stmt_cancelled->bind_param("i", $user_id);
                        $stmt_cancelled->execute();
                        $res_cancelled = $stmt_cancelled->get_result();
                        $cancelled_data = $res_cancelled->fetch_assoc();
                        echo $cancelled_data['cnt'] ?? 0;
                        $stmt_cancelled->close();
                    }
                    ?>
                </div>
            </div>
        </div>
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
        if (window.scrollY > 30) {
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
            // Close dropdown if click happens outside the .user-menu
            if (!e.target.closest || !e.target.closest('.user-menu')) {
                dropdown.classList.remove('show');
            }
        });
    }
});

</script>

</body>
</html>
