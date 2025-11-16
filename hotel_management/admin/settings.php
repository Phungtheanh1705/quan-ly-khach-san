<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include "../config/db.php";

$message = '';
$error = '';

/**
 * Hàm lưu hoặc cập nhật cài đặt trong DB.
 * Sử dụng INSERT ... ON DUPLICATE KEY UPDATE để xử lý 
 * cả INSERT (nếu chưa có) và UPDATE (nếu đã có key).
 */
function save_setting($conn, $key, $value) {
    // Chỉ lưu khi key và value hợp lệ
    if (empty($key)) return false; 
    
    // Sử dụng prepare statement để ngăn SQL Injection
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("sss", $key, $value, $value);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Hàm tải tất cả cài đặt hiện tại từ DB.
 */
function load_settings($conn) {
    $result = $conn->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        $result->free();
    }
    return $settings;
}

/**
 * Hàm lấy giá trị cài đặt hoặc giá trị mặc định nếu chưa có trong DB.
 */
function get_setting($key, $settings, $default) {
    // Trả về giá trị từ DB nếu tồn tại, ngược lại trả về giá trị mặc định
    return htmlspecialchars($settings[$key] ?? $default);
}


// Xử lý lưu các cài đặt khi form được gửi đi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    
    // Bắt đầu giao dịch (Transaction) để đảm bảo tất cả đều thành công hoặc tất cả đều thất bại
    $conn->begin_transaction();
    $all_success = true;

    try {
        // 1. THÔNG TIN CHUNG
        $all_success &= save_setting($conn, 'hotel_name', $_POST['hotel_name']);
        $all_success &= save_setting($conn, 'currency', $_POST['currency']);
        $all_success &= save_setting($conn, 'checkin_time', $_POST['checkin_time']);
        $all_success &= save_setting($conn, 'checkout_time', $_POST['checkout_time']);
        $all_success &= save_setting($conn, 'tax_rate', (float)$_POST['tax_rate']); 
        $all_success &= save_setting($conn, 'hotel_address', $_POST['hotel_address']);

        // 2. THANH TOÁN
        $all_success &= save_setting($conn, 'vnpay_status', $_POST['vnpay_status']);
        $all_success &= save_setting($conn, 'vnpay_tmncode', $_POST['vnpay_tmncode']);
        $all_success &= save_setting($conn, 'vnpay_hashsecret', $_POST['vnpay_hashsecret']);
        $all_success &= save_setting($conn, 'bank_name', $_POST['bank_name']);
        $all_success &= save_setting($conn, 'account_number', $_POST['account_number']);

        // 3. EMAIL & THÔNG BÁO
        $all_success &= save_setting($conn, 'smtp_host', $_POST['smtp_host']);
        $all_success &= save_setting($conn, 'smtp_port', $_POST['smtp_port']);
        $all_success &= save_setting($conn, 'smtp_username', $_POST['smtp_username']);
        $all_success &= save_setting($conn, 'smtp_password', $_POST['smtp_password']); 
        $all_success &= save_setting($conn, 'from_name', $_POST['from_name']);
        
        if ($all_success) {
             $conn->commit();
             $message = "Cài đặt hệ thống đã được cập nhật thành công!";
        } else {
             $conn->rollback();
             $error = "Đã xảy ra lỗi khi lưu một số cài đặt. Dữ liệu chưa được cập nhật.";
        }

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Lỗi nghiêm trọng khi lưu cài đặt: " . $e->getMessage();
    }
}

// Tải cài đặt hiện tại để điền vào form (luôn chạy)
$current_settings = load_settings($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt Hệ thống - Admin Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
        .settings-card {
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        .nav-link {
            transition: all 0.3s;
        }
        .nav-link.active {
            font-weight: bold;
            color: #007bff !important;
            border-bottom: 3px solid #007bff;
            background-color: #f8f9fa;
        }
        .tab-pane label {
            font-weight: 500;
            color: #495057;
        }
        .tab-pane .input-group-text {
            color: #007bff;
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
        <h1 class="mb-4"><i class="fas fa-cogs me-2 text-secondary"></i> Cài đặt Hệ thống</h1>
        
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

        <div class="card settings-card">
            <div class="card-body p-4">
                
                <form method="post" action="settings.php">
                    <ul class="nav nav-tabs mb-4" id="settingsTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                                <i class="fas fa-info-circle me-1"></i> Thông tin chung
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button" role="tab" aria-controls="payment" aria-selected="false">
                                <i class="fas fa-credit-card me-1"></i> Thanh toán
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab" aria-controls="email" aria-selected="false">
                                <i class="fas fa-envelope me-1"></i> Email & Thông báo
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="settingsTabContent">
                        
                        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="hotel_name" class="form-label">Tên Khách sạn</label>
                                    <input type="text" class="form-control" id="hotel_name" name="hotel_name" value="<?= get_setting('hotel_name', $current_settings, 'QLKS Modern Admin') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="currency" class="form-label">Đơn vị tiền tệ mặc định</label>
                                    <select class="form-select" id="currency" name="currency">
                                        <option value="VND" <?= get_setting('currency', $current_settings, 'VND') == 'VND' ? 'selected' : '' ?>>VND - Đồng Việt Nam</option>
                                        <option value="USD" <?= get_setting('currency', $current_settings, 'VND') == 'USD' ? 'selected' : '' ?>>USD - Đô la Mỹ</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="checkin_time" class="form-label">Giờ Check-in Mặc định</label>
                                    <input type="time" class="form-control" id="checkin_time" name="checkin_time" value="<?= get_setting('checkin_time', $current_settings, '14:00') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="checkout_time" class="form-label">Giờ Check-out Mặc định</label>
                                    <input type="time" class="form-control" id="checkout_time" name="checkout_time" value="<?= get_setting('checkout_time', $current_settings, '12:00') ?>" required>
                                </div>
                                <div class="col-12">
                                    <label for="tax_rate" class="form-label">Thuế suất VAT (%)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" id="tax_rate" name="tax_rate" value="<?= get_setting('tax_rate', $current_settings, '10.00') ?>" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="hotel_address" class="form-label">Địa chỉ Khách sạn</label>
                                    <textarea class="form-control" id="hotel_address" name="hotel_address" rows="2"><?= get_setting('hotel_address', $current_settings, '123 Đường Công Nghệ, Quận 1, TP.HCM') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="payment" role="tabpanel" aria-labelledby="payment-tab">
                            <div class="row g-3">
                                <div class="col-12">
                                    <h5 class="text-primary"><i class="fas fa-money-check-alt me-2"></i> Cổng Thanh toán (VNPay)</h5>
                                </div>
                                <div class="col-md-6">
                                    <label for="vnpay_status" class="form-label">Trạng thái VNPay</label>
                                    <select class="form-select" id="vnpay_status" name="vnpay_status">
                                        <option value="enabled" <?= get_setting('vnpay_status', $current_settings, 'enabled') == 'enabled' ? 'selected' : '' ?>>Kích hoạt</option>
                                        <option value="disabled" <?= get_setting('vnpay_status', $current_settings, 'enabled') == 'disabled' ? 'selected' : '' ?>>Vô hiệu hóa</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="vnpay_tmncode" class="form-label">TMN Code</label>
                                    <input type="text" class="form-control" id="vnpay_tmncode" name="vnpay_tmncode" value="<?= get_setting('vnpay_tmncode', $current_settings, 'ABCDEFGHIJ') ?>">
                                </div>
                                <div class="col-12">
                                    <label for="vnpay_hashsecret" class="form-label">Hash Secret Key</label>
                                    <input type="text" class="form-control" id="vnpay_hashsecret" name="vnpay_hashsecret" value="<?= get_setting('vnpay_hashsecret', $current_settings, 'secret_1234567890') ?>">
                                </div>
                                <div class="col-12">
                                    <h5 class="text-primary mt-3"><i class="fas fa-university me-2"></i> Thanh toán Chuyển khoản</h5>
                                </div>
                                <div class="col-md-6">
                                    <label for="bank_name" class="form-label">Tên Ngân hàng</label>
                                    <input type="text" class="form-control" id="bank_name" name="bank_name" value="<?= get_setting('bank_name', $current_settings, 'Vietcombank') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="account_number" class="form-label">Số Tài khoản</label>
                                    <input type="text" class="form-control" id="account_number" name="account_number" value="<?= get_setting('account_number', $current_settings, '001100220033') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="email" role="tabpanel" aria-labelledby="email-tab">
                            <div class="row g-3">
                                <div class="col-12">
                                    <h5 class="text-success"><i class="fas fa-server me-2"></i> Cấu hình SMTP (Email Server)</h5>
                                </div>
                                <div class="col-md-6">
                                    <label for="smtp_host" class="form-label">SMTP Host</label>
                                    <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?= get_setting('smtp_host', $current_settings, 'smtp.yourservice.com') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="smtp_port" class="form-label">SMTP Port</label>
                                    <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?= get_setting('smtp_port', $current_settings, '587') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="smtp_username" class="form-label">Tên đăng nhập Email (Username)</label>
                                    <input type="email" class="form-control" id="smtp_username" name="smtp_username" value="<?= get_setting('smtp_username', $current_settings, 'no-reply@yourhotel.com') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="smtp_password" class="form-label">Mật khẩu Email (Password)</label>
                                    <input type="password" class="form-control" id="smtp_password" name="smtp_password" value="<?= get_setting('smtp_password', $current_settings, '') ?>">
                                    <small class="form-text text-muted">Để trống nếu không muốn thay đổi</small>
                                </div>
                                <div class="col-12">
                                    <label for="from_name" class="form-label">Tên hiển thị khi gửi</label>
                                    <input type="text" class="form-control" id="from_name" name="from_name" value="<?= get_setting('from_name', $current_settings, 'Phòng Booking Khách sạn') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top text-end">
                        <button type="submit" name="save_settings" class="btn btn-success btn-lg">
                            <i class="fas fa-save me-2"></i> Lưu Cài đặt
                        </button>
                    </div>
                </form>

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
        
        // KÍCH HOẠT HIỆU ỨNG FADE-IN (CHUYỂN TRANG MƯỢT)
        const content = document.querySelector('.content-area-main');
        if (content) {
            setTimeout(() => {
                content.classList.remove('initial-hidden');
                content.classList.add('fade-in');
            }, 50);
        }

        // LOGIC ĐÓNG SIDEBAR KHI NHẤN RA NGOÀI (MOBILE ONLY)
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
        
        // Xử lý lớp phủ nền tối (Backdrop) khi Sidebar mở/đóng
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

        // Xử lý khi resize sang Desktop
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