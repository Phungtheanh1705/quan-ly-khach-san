<?php
// ... (PHẦN KẾT NỐI DB VÀ TRUY VẤN GIỮ NGUYÊN NHƯ PHIÊN BẢN CUỐI CÙNG TÔI GỬI) ...
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); 
    exit();
}

include "../../config/db.php"; 

$booking = null;
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($booking_id > 0) {
    // TRUY VẤN ĐÃ SỬA: Lấy giá từ r.price và các cột cần thiết
    $query = "
        SELECT 
            b.*, 
            u.username, u.email, u.phone, u.address,  
            r.room_number, r.price AS base_room_price,  
            rt.type_name,
            p.amount AS total_amount, p.transaction_id, p.payment_date
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN rooms r ON b.room_id = r.id
        LEFT JOIN room_types rt ON r.type_id = rt.id
        LEFT JOIN payments p ON b.id = p.booking_id
        WHERE b.id = ?
    ";
    
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $booking = $result->fetch_assoc();
        $stmt->close();
    }
}

if (!$booking) {
    $_SESSION['message'] = "Không tìm thấy đơn đặt phòng này.";
    $_SESSION['msg_type'] = "danger";
    header("Location: index.php");
    exit();
}

$conn->close();

// --- HÀM TIỆN ÍCH CHO TRẠNG THÁI ---
function get_status_badge($status) {
    switch ($status) {
        case 'confirmed':
            return '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Đã xác nhận</span>';
        case 'pending':
            return '<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Đang chờ</span>';
        case 'checked_in':
            return '<span class="badge bg-primary"><i class="fas fa-door-open"></i> Đã nhận phòng</span>';
        case 'checked_out':
            return '<span class="badge bg-secondary"><i class="fas fa-sign-out-alt"></i> Đã trả phòng</span>';
        case 'cancelled':
            return '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Đã hủy</span>';
        default:
            return '<span class="badge bg-info">' . htmlspecialchars(ucfirst($status)) . '</span>';
    }
}

// Tính số đêm
$check_in = new DateTime($booking['check_in']);
$check_out = new DateTime($booking['check_out']);
$interval = $check_in->diff($check_out);
$num_nights = $interval->days;

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Booking #<?= $booking['id'] ?> - Admin Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <link rel="stylesheet" href="../../assets/css/admin.css"> 
</head>
<body>

<div class="main-wrapper d-flex">

<nav id="sidebar" class="bg-dark text-white p-3 shadow-lg collapse">
    <div class="sidebar-header text-center mb-4">
        <h3 class="text-white"><i class="fas fa-hotel me-2"></i> QLKS Admin</h3>
        <hr class="text-secondary">
    </div>

    <ul class="list-unstyled components">
        <li><a href="../index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
        <li class="active"><a href="index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded bg-primary"><i class="fas fa-book me-2"></i> Quản lý Booking</a></li>
        <li><a href="../rooms/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded"><i class="fas fa-bed me-2"></i> Quản lý Phòng</a></li>
        <li><a href="../users/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded"><i class="fas fa-users me-2"></i> Quản lý Users</a></li>
        <li><a href="../payments/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded"><i class="fas fa-credit-card me-2"></i> Thanh toán</a></li>
        <li>
            <a href="#reportSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle d-block py-2 px-3 text-white text-decoration-none rounded">
                <i class="fas fa-chart-line me-2"></i> Báo cáo & Thống kê
            </a>
            <ul class="collapse list-unstyled" id="reportSubmenu">
                <li><a href="../reports/monthly.php" class="d-block py-2 ps-5 text-white-50 text-decoration-none">Doanh thu tháng</a></li>
                <li><a href="../reports/yearly.php" class="d-block py-2 ps-5 text-white-50 text-decoration-none">Tổng quan năm</a></li>
            </ul>
        </li>
    </ul>

    <div class="bottom-logout p-3">
        <hr class="text-secondary">
        <a href="../logout.php" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i> Đăng xuất</a>
    </div>
</nav>

<div class="page-content-wrapper flex-grow-1 content-area-main initial-hidden">
    
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container-fluid">
            <button class="btn btn-outline-secondary d-inline-block d-lg-none me-2" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
            
            <a class="navbar-brand text-primary fw-bold" href="../index.php">
                <i class="fas fa-home me-1"></i> Trang Chủ
            </a>

            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="me-2 d-none d-sm-inline">Xin chào, Admin! (<?php echo $_SESSION['admin_id'] ?? 'Guest'; ?>)</span>
                            <i class="fas fa-user-circle fa-lg"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="../profile.php"><i class="fas fa-user-cog me-2"></i> Hồ sơ</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Đăng xuất</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i> Chi tiết Booking #<?= $booking['id'] ?></h1>
            <div class="action-buttons">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Quay lại
                </a>
                <a href="edit.php?id=<?= $booking['id'] ?>" class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i> Sửa Booking
                </a>
                <?php if ($booking['status'] == 'pending'): ?>
                    <a href="approve.php?id=<?= $booking['id'] ?>" class="btn btn-success" onclick="return confirm('Bạn có chắc chắn muốn XÁC NHẬN đơn đặt phòng #<?= $booking['id'] ?> này không?');">
                        <i class="fas fa-check me-1"></i> Xác nhận
                    </a>
                <?php endif; ?>
                <?php if ($booking['status'] != 'cancelled'): ?>
                    <a href="cancel.php?id=<?= $booking['id'] ?>" class="btn btn-danger" onclick="return confirm('Bạn có chắc chắn muốn HỦY đơn đặt phòng #<?= $booking['id'] ?> này không?');">
                        <i class="fas fa-times me-1"></i> Hủy
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php 
        if (isset($_SESSION['message'])): 
        ?>
            <div class="alert alert-<?= $_SESSION['msg_type'] ?? 'success'; ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php 
            unset($_SESSION['message']);
            unset($_SESSION['msg_type']);
        endif; 
        ?>

        <div class="row">
            
            <div class="col-lg-6 mb-4">
                
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-calendar-alt me-2"></i> Chi Tiết Đơn Đặt Phòng
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Trạng thái: 
                                <span><?= get_status_badge($booking['status']) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Ngày đặt: 
                                <span class="fw-bold"><?= date('d/m/Y H:i', strtotime($booking['created_at'])) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Check-in: 
                                <span class="badge bg-success py-2 px-3"><?= date('d/m/Y', strtotime($booking['check_in'])) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Check-out: 
                                <span class="badge bg-danger py-2 px-3"><?= date('d/m/Y', strtotime($booking['check_out'])) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Số đêm: 
                                <span class="fw-bold"><?= $num_nights ?> đêm</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Số lượng khách: 
                                <span class="fw-bold"><?= $booking['guest_count'] ?? 'N/A' ?> người</span>
                            </li>
                            <li class="list-group-item">
                                <span class="d-block mb-1">Ghi chú:</span>
                                <p class="text-muted fst-italic mb-0">
                                    <?= !empty($booking['notes']) ? nl2br(htmlspecialchars($booking['notes'])) : 'Không có ghi chú.' ?>
                                </p>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-user me-2"></i> Thông Tin Khách Hàng
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                Tên đăng nhập: 
                                <span class="fw-bold"><?= htmlspecialchars($booking['username'] ?? 'Khách lẻ') ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                Email: 
                                <span class="fw-bold text-end"><?= htmlspecialchars($booking['email'] ?? 'N/A') ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                Điện thoại: 
                                <span class="fw-bold"><?= htmlspecialchars($booking['phone'] ?? 'N/A') ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                Địa chỉ: 
                                <span class="fw-bold text-end"><?= htmlspecialchars($booking['address'] ?? 'N/A') ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-bed me-2"></i> Thông Tin Phòng
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                Số phòng: 
                                <span class="fw-bold badge bg-dark fs-6"><?= htmlspecialchars($booking['room_number'] ?? 'N/A') ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                Loại phòng: 
                                <span class="fw-bold"><?= htmlspecialchars($booking['type_name'] ?? 'N/A') ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                Giá cơ bản/đêm: 
                                <span class="fw-bold text-primary"><?= number_format($booking['base_room_price'] ?? 0) ?> VNĐ</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                Tổng giá phòng (<?= $num_nights ?> đêm): 
                                <span class="fw-bold text-dark"><?= number_format(($booking['base_room_price'] ?? 0) * $num_nights) ?> VNĐ</span>
                            </li>
                        </ul>
                        <div class="card-footer bg-light mt-2 p-2">
                             <div class="d-flex justify-content-between">
                                <span class="text-muted small">Chi phí thêm / Giảm giá:</span>
                                <span class="text-muted small">0 VNĐ / 0 VNĐ</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <i class="fas fa-credit-card me-2"></i> Thông Tin Thanh Toán
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                Phương thức: 
                                <span class="fw-bold badge bg-dark"><?= strtoupper($booking['payment_method']) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                **TỔNG SỐ TIỀN THANH TOÁN:** <span class="fw-bold text-success fs-4"><?= number_format($booking['total_amount'] ?? $booking['total_price'] ?? 0) ?> VNĐ</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                Trạng thái TT: 
                                <span class="badge bg-warning text-dark">Đang chờ</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                Ngày TT: 
                                <span class="fw-bold"><?= $booking['payment_date'] ? date('d/m/Y H:i', strtotime($booking['payment_date'])) : 'N/A' ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                Mã giao dịch: 
                                <span class="fw-bold"><?= htmlspecialchars($booking['transaction_id'] ?? 'N/A') ?></span>
                            </li>
                        </ul>
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
        // KÍCH HOẠT HIỆU ỨNG FADE-IN
        const content = document.querySelector('.content-area-main');
        if (content) {
            setTimeout(() => {
                content.classList.remove('initial-hidden');
                content.classList.add('fade-in');
            }, 50); 
        }
    }
});
</script>

</body>
</html>