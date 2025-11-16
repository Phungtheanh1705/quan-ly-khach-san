<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include "../config/db.php";

$admin_id = $_SESSION['admin_id']; // Đây là username
$admin_info = [];
$message = '';
$error = '';

// Lấy thông tin Admin TỪ BẢNG users
$stmt = $conn->prepare("SELECT id, username, role, created_at FROM users WHERE username = ? AND role = 'admin'");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $admin_info = $result->fetch_assoc();
} else {
    $error = "Không tìm thấy thông tin quản trị viên hoặc tài khoản không có quyền Admin.";
}
$stmt->close();

// Xử lý cập nhật mật khẩu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "Mật khẩu mới và xác nhận mật khẩu không khớp.";
    } elseif (strlen($new_password) < 6) {
        $error = "Mật khẩu mới phải có ít nhất 6 ký tự.";
    } else {
        // Lấy mật khẩu cũ (hashed) từ DB TỪ BẢNG users
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE username = ?");
        $stmt->bind_param("s", $admin_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($old_password, $user['password_hash'])) {
            // Cập nhật mật khẩu mới (dùng password_hash)
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
            $stmt->bind_param("ss", $hashed_password, $admin_id);
            if ($stmt->execute()) {
                $message = "Mật khẩu đã được cập nhật thành công.";
            } else {
                $error = "Lỗi khi cập nhật mật khẩu: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = "Mật khẩu cũ không chính xác.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ Sơ Cá Nhân - Admin Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
        /* Profile Card Mới */
        .profile-card {
            max-width: 500px;
            margin: 0 auto; /* Bỏ margin top/bottom cố định để card dính vào cột */
            border: none; 
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease; /* Thêm transition cho card */
        }
        .profile-card:hover {
            transform: translateY(-5px); /* Hiệu ứng nhấc toàn bộ card nhẹ */
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }
        .profile-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: #fff;
            padding: 30px 20px;
            text-align: center;
            margin-bottom: 0;
        }
        .profile-header h4 {
            font-weight: 700;
            margin-top: 5px;
            font-size: 1.5rem;
        }
        .profile-header i {
            font-size: 2.5rem;
        }

        /* Định dạng nhóm thông tin */
        .info-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 25px;
            border-bottom: 1px solid #f0f0f0;
            /* THÊM TRANSITION CHO HIỆU ỨNG MƯỢT HƠN */
            transition: background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease; 
            cursor: pointer; /* Cho biết đây là vùng tương tác */
        }
        .info-group:last-child {
            border-bottom: none;
        }
        
        /* HIỆU ỨNG HOVER MỚI: RÕ RÀNG VÀ HIỆN ĐẠI HƠN */
        .info-group:hover {
            background-color: #e9ecef; /* Màu nền rõ ràng hơn (light gray) */
            transform: scale(1.01); /* Zoom nhẹ (thay vì nhấc) */
        }
        
        .info-group .label {
            font-weight: 500;
            color: #6c757d;
            display: flex;
            align-items: center;
        }
        .info-group .label i {
            margin-right: 10px;
            font-size: 1.1rem;
            color: #007bff;
        }
        .info-group .value {
            font-weight: 600;
            color: #343a40;
        }

        /* Style riêng cho đổi mật khẩu */
        .password-card .profile-header {
            background: linear-gradient(135deg, #dc3545, #b02a37);
        }
        .password-card .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
    </style>
</head>
<body>

<div class="main-wrapper d-flex">

<nav id="sidebar" class="bg-dark text-white p-3 shadow-lg collapse">
    <div class="sidebar-header text-center mb-4">
        <h3 class="text-white"><i class="fas fa-hotel me-2"></i> QLKS Admin</h3>
        <hr class="text-secondary">
    </div>

    <ul class="list-unstyled components">
        <li>
            <a href="index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="bookings/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded">
                <i class="fas fa-book me-2"></i> Quản lý Booking
            </a>
        </li>
        <li>
            <a href="rooms/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded">
                <i class="fas fa-bed me-2"></i> Quản lý Phòng
            </a>
        </li>
        <li>
            <a href="users/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded">
                <i class="fas fa-users me-2"></i> Quản lý Users
            </a>
        </li>
        <li>
            <a href="payments/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded">
                <i class="fas fa-credit-card me-2"></i> Thanh toán
            </a>
        </li>
        <li>
            <a href="#reportSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle d-block py-2 px-3 text-white text-decoration-none rounded">
                <i class="fas fa-chart-line me-2"></i> Báo cáo & Thống kê
            </a>
            <ul class="collapse list-unstyled" id="reportSubmenu">
                <li>
                    <a href="reports/monthly.php" class="d-block py-2 ps-5 text-white-50 text-decoration-none">Doanh thu tháng</a>
                </li>
                <li>
                    <a href="reports/yearly.php" class="d-block py-2 ps-5 text-white-50 text-decoration-none">Tổng quan năm</a>
                </li>
            </ul>
        </li>
    </ul>

    <div class="bottom-logout p-3">
        <hr class="text-secondary">
        <a href="logout.php" class="btn btn-outline-danger w-100">
            <i class="fas fa-sign-out-alt me-2"></i> Đăng xuất
        </a>
    </div>
</nav>
<div class="page-content-wrapper flex-grow-1 content-area-main initial-hidden">
    
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container-fluid">
            <button class="btn btn-outline-secondary d-inline-block d-lg-none me-2" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
            
            <a class="navbar-brand text-primary fw-bold" href="index.php">
                <i class="fas fa-home me-1"></i> Trang Chủ
            </a>

            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle fa-lg me-2 text-primary"></i> 
                            <span class="admin-info-text me-2 d-none d-sm-inline">
                                Xin chào, **Admin**! (<?php echo $_SESSION['admin_id'] ?? 'Guest'; ?>)
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li class="dropdown-header text-center border-bottom mb-2 pb-2">
                                <span class="admin-info-text text-primary"><?php echo $_SESSION['admin_id'] ?? 'Guest'; ?></span>
                                <br><small class="text-muted">Quản trị viên</small>
                            </li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog me-2 text-secondary"></i> Hồ sơ cá nhân</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cogs me-2 text-secondary"></i> Cài đặt hệ thống</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Đăng xuất</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="content container-fluid">
        <h1 class="mb-4"><i class="fas fa-user-circle me-2 text-primary"></i> Hồ Sơ Cá Nhân</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-6 mb-4 d-flex">
                <div class="card profile-card h-100">
                    <div class="profile-header">
                        <i class="fas fa-user-shield"></i>
                        <h4>Thông tin tài khoản Admin</h4>
                    </div>
                    <div class="card-body p-0">
                        <div class="info-group">
                            <div class="label"><i class="fas fa-user"></i> Tên đăng nhập:</div>
                            <div class="value text-primary"><?= htmlspecialchars($admin_info['username'] ?? 'N/A') ?></div>
                        </div>
                        <div class="info-group">
                            <div class="label"><i class="fas fa-key"></i> ID User (PK):</div>
                            <div class="value"><?= htmlspecialchars($admin_info['id'] ?? 'N/A') ?></div>
                        </div>
                        <div class="info-group">
                            <div class="label"><i class="fas fa-check-circle"></i> Quyền:</div>
                            <div class="value text-success"><?= ucfirst(htmlspecialchars($admin_info['role'] ?? 'N/A')) ?></div>
                        </div>
                        <div class="info-group">
                            <div class="label"><i class="fas fa-calendar-alt"></i> Ngày tham gia:</div>
                            <div class="value"><?= date('d/m/Y', strtotime($admin_info['created_at'] ?? 'now')) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4 d-flex">
                <div class="card profile-card password-card h-100">
                    <div class="profile-header">
                        <i class="fas fa-lock"></i>
                        <h4>Đổi mật khẩu bảo mật</h4>
                    </div>
                    <div class="card-body p-4">
                        <form method="post" action="profile.php">
                            <div class="mb-3">
                                <label for="old_password" class="form-label fw-bold"><i class="fas fa-unlock-alt me-2"></i> Mật khẩu cũ</label>
                                <input type="password" class="form-control" id="old_password" name="old_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label fw-bold"><i class="fas fa-shield-alt me-2"></i> Mật khẩu mới</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="form-text">Mật khẩu mới phải có ít nhất 6 ký tự.</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label fw-bold"><i class="fas fa-sync-alt me-2"></i> Xác nhận mật khẩu mới</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" name="update_password" class="btn btn-primary w-100 mt-2">
                                <i class="fas fa-save me-2"></i> Cập nhật mật khẩu
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

</div> 
</div> 

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');

    if (sidebar) {
        
        // 1. KÍCH HOẠT HIỆU ỨNG FADE-IN (CHUYỂN TRANG MƯỢT)
        const content = document.querySelector('.content-area-main');
        if (content) {
            setTimeout(() => {
                content.classList.remove('initial-hidden');
                content.classList.add('fade-in');
            }, 50);
        }

        // 2. LOGIC ĐÓNG SIDEBAR KHI NHẤN RA NGOÀI (MOBILE ONLY)
        document.addEventListener('click', function(event) {
            const isSidebarOpen = sidebar.classList.contains('show');
            const isMobileScreen = window.innerWidth < 992;
            const toggleButton = document.querySelector('[data-bs-target="#sidebar"]');
            
            if (isSidebarOpen && isMobileScreen) {
                if (!sidebar.contains(event.target) && (!toggleButton || !toggleButton.contains(event.target))) {
                    const sidebarCollapse = bootstrap.Collapse.getInstance(sidebar) || new bootstrap.Collapse(sidebar, { toggle: false });
                    sidebarCollapse.hide();
                }
            }
        });
        
        // 3. Xử lý lớp phủ nền tối (Backdrop) khi Sidebar mở/đóng
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    if (sidebar.classList.contains('show') && window.innerWidth < 992) {
                        document.body.classList.add('sidebar-open');
                    } else {
                        document.body.classList.remove('sidebar-open');
                    }
                }
            });
        });
        observer.observe(sidebar, { attributes: true });

        // 4. Xử lý khi resize sang Desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                document.body.classList.remove('sidebar-open');
                if (sidebar.classList.contains('show')) {
                     const sidebarCollapse = bootstrap.Collapse.getInstance(sidebar) || new bootstrap.Collapse(sidebar, { toggle: false });
                     sidebarCollapse.hide(); 
                }
            }
        });
    }
});
</script>

</body>
</html>