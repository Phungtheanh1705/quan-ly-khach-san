<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); 
    exit();
}
include "../../config/db.php"; 

$payment_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?? 0;
$payment_data = null;

if ($payment_id > 0) {
    // Truy vấn chi tiết Thanh toán, Booking, và User
    $sql = "
        SELECT 
            p.id AS payment_id, p.amount, p.payment_method, p.payment_date, p.status AS payment_status, p.transaction_id,
            b.id AS booking_id, b.check_in, b.check_out, b.status AS booking_status,
            u.username, u.email, u.phone
        FROM payments p
        LEFT JOIN bookings b ON p.booking_id = b.id
        LEFT JOIN users u ON b.user_id = u.id
        WHERE p.id = ?
    ";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $payment_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$payment_data) {
    $_SESSION['message'] = "Không tìm thấy giao dịch thanh toán này.";
    $_SESSION['msg_type'] = "danger";
    header("Location: index.php");
    exit();
}

$conn->close();

// Hàm hiển thị trạng thái thanh toán (Đã cập nhật thêm case 'refunded')
function get_payment_status_badge($status) {
    switch ($status) {
        case 'completed':
            return '<span class="badge bg-success py-2 px-3"><i class="fas fa-check"></i> Hoàn tất</span>';
        case 'pending':
            return '<span class="badge bg-warning text-dark py-2 px-3"><i class="fas fa-clock"></i> Đang chờ</span>';
        case 'failed':
            return '<span class="badge bg-danger py-2 px-3"><i class="fas fa-times"></i> Thất bại</span>';
        case 'refunded':
            return '<span class="badge bg-info text-dark py-2 px-3"><i class="fas fa-undo"></i> Đã hoàn tiền</span>';
        default:
            return '<span class="badge bg-secondary py-2 px-3">N/A</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Thanh toán #<?= $payment_data['payment_id'] ?> - Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <link rel="stylesheet" href="../../assets/css/admin.css"> 
    
    <style>
        .initial-hidden { opacity: 0; }
        .fade-in-element { opacity: 0; transition: opacity 0.5s ease-in-out; }
        .fade-in-element.is-visible { opacity: 1; }
        
        .main-container { max-width: 800px; margin: 30px auto; }
        .detail-card { border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); margin-bottom: 20px; }
        .amount-display { font-size: 2rem; font-weight: bold; color: #dc3545; }
    </style>
</head>
<body class="initial-hidden">

<div class="main-container fade-in-element">
    
    <nav class="navbar navbar-light bg-white shadow-sm p-3 mb-4 rounded-pill">
        <div class="container-fluid">
            <a class="navbar-brand text-primary fw-bold nav-link-fade" href="index.php">
                <i class="fas fa-arrow-left me-1"></i> Quay lại DS Thanh toán
            </a>
            <span class="text-muted">Chi tiết Giao dịch #<?= $payment_data['payment_id'] ?></span>
        </div>
    </nav>

    <h1 class="mb-4 text-center"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i> Chi tiết Giao dịch</h1>

    <div class="card detail-card">
        <div class="card-header text-white" style="background-color: #007bff;">
            Thông tin Thanh toán
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 border-end">
                    <div class="mb-4">
                        <p class="mb-1 text-muted small">Mã giao dịch (Transaction ID):</p>
                        <p class="fw-bold fs-5 text-primary"><?= htmlspecialchars($payment_data['transaction_id'] ?: 'N/A') ?></p>
                    </div>
                    <div class="mb-4">
                        <p class="mb-1 text-muted small">Thời gian giao dịch:</p>
                        <p class="fw-bold"><?= date('d/m/Y H:i:s', strtotime($payment_data['payment_date'])) ?></p>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-4">
                        <p class="mb-1 text-muted small">Trạng thái Thanh toán:</p>
                        <?= get_payment_status_badge($payment_data['payment_status']) ?>
                    </div>
                    <div class="mb-4">
                        <p class="mb-1 text-muted small">Phương thức Thanh toán:</p>
                        <span class="badge bg-dark fs-6"><?= strtoupper(htmlspecialchars($payment_data['payment_method'])) ?></span>
                    </div>
                    <div class="mb-4">
                        <p class="mb-1 text-muted small">Booking ID liên quan:</p>
                        <a href="../bookings/detail.php?id=<?= $payment_data['booking_id'] ?>" class="fw-bold text-primary nav-link-fade">#<?= $payment_data['booking_id'] ?></a>
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded">
                <h4 class="mb-0 text-uppercase text-dark">Tổng số tiền:</h4>
                <div class="amount-display"><?= number_format($payment_data['amount'], 0, ',', '.') ?> VNĐ</div>
            </div>
            
            <?php if($payment_data['payment_status'] !== 'refunded'): ?>
            <div class="mt-3 text-end">
                 <a href="refund.php?id=<?= $payment_data['payment_id'] ?>" class="btn btn-outline-warning" onclick="return confirm('Bạn có chắc muốn hoàn tiền?');"><i class="fas fa-undo me-1"></i> Hoàn tiền giao dịch này</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card detail-card">
        <div class="card-header text-white" style="background-color: #28a745;">
            Thông tin Booking & Khách hàng
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 border-end">
                    <p class="mb-1 text-muted small">Tên Khách hàng:</p>
                    <p class="fw-bold fs-5 text-info"><?= htmlspecialchars($payment_data['username'] ?: 'N/A') ?></p>
                    <hr>
                    <p class="mb-1 text-muted small">Email liên hệ:</p>
                    <p class="fw-bold"><?= htmlspecialchars($payment_data['email'] ?: 'N/A') ?></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1 text-muted small">Ngày Check-in:</p>
                    <p class="fw-bold"><?= date('d/m/Y', strtotime($payment_data['check_in'])) ?></p>
                    <hr>
                    <p class="mb-1 text-muted small">Ngày Check-out:</p>
                    <p class="fw-bold"><?= date('d/m/Y', strtotime($payment_data['check_out'])) ?></p>
                </div>
            </div>
        </div>
    </div>
    
</div> 

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fadeElement = document.querySelector('.fade-in-element');
    if (fadeElement) {
        setTimeout(() => {
            fadeElement.classList.add('is-visible');
            document.body.classList.remove('initial-hidden');
        }, 50); 
    }
    document.querySelectorAll('a.nav-link-fade').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
                e.preventDefault(); 
                if (fadeElement) fadeElement.classList.remove('is-visible');
                setTimeout(() => { window.location.href = href; }, 500); 
            }
        });
    });
});
</script>
</body>
</html>