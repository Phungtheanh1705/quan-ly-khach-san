<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); 
    exit();
}

include "../../config/db.php"; 

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$booking = null;
$rooms_list = []; 

if ($booking_id > 0) {
    // 1. Lấy thông tin Booking hiện tại
    $query = "
        SELECT 
            b.*, 
            u.username, u.email, 
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
    
    if (!$booking) {
        $_SESSION['message'] = "Không tìm thấy đơn đặt phòng này.";
        $_SESSION['msg_type'] = "danger";
        header("Location: index.php");
        exit();
    }
    
    // 2. Lấy danh sách phòng khả dụng (available) + phòng hiện tại của booking
    $current_room_id = $booking['room_id'];
    $rooms_query = "
        SELECT r.id, r.room_number, rt.type_name, r.price
        FROM rooms r
        JOIN room_types rt ON r.type_id = rt.id
        WHERE r.status = 'available' OR r.id = ?
        ORDER BY r.room_number
    ";
    
    if ($rooms_stmt = $conn->prepare($rooms_query)) {
        $rooms_stmt->bind_param("i", $current_room_id);
        $rooms_stmt->execute();
        $rooms_result = $rooms_stmt->get_result();
        while ($row = $rooms_result->fetch_assoc()) {
            $rooms_list[] = $row;
        }
        $rooms_stmt->close();
    }
} else {
    header("Location: index.php");
    exit();
}


// --- XỬ LÝ FORM CẬP NHẬT ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Lấy dữ liệu từ form
    $room_id = (int) $_POST['room_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $guest_count = (int) $_POST['guest_count'];
    $status = $_POST['status'];
    $payment_method = $_POST['payment_method'];
    $transaction_id = $_POST['transaction_id'] ?? null;
    $notes = $_POST['notes'] ?? null;
    $total_amount = (float) $_POST['total_amount'];
    
    // Lấy room_id cũ để cập nhật status
    $old_room_id = $booking['room_id'];

    // 1. Cập nhật bảng bookings
    $booking_update_query = "
        UPDATE bookings SET 
            room_id = ?, 
            check_in = ?, 
            check_out = ?, 
            guest_count = ?, 
            status = ?, 
            payment_method = ?, 
            notes = ?
        WHERE id = ?
    ";
    
    if ($stmt = $conn->prepare($booking_update_query)) {
        $stmt->bind_param(
            "ississis", 
            $room_id, 
            $check_in, 
            $check_out, 
            $guest_count, 
            $status, 
            $payment_method, 
            $notes, 
            $booking_id
        );
        $booking_updated = $stmt->execute();
        $stmt->close();
    } else {
        $booking_updated = false;
    }
    
    // 2. Cập nhật bảng payments (Chỉ cập nhật nếu có)
    $payment_updated = true;
    if (isset($booking['total_amount'])) { 
        $payment_update_query = "
            UPDATE payments SET 
                amount = ?, 
                payment_method = ?, 
                transaction_id = ?
            WHERE booking_id = ?
        ";
        
        if ($stmt = $conn->prepare($payment_update_query)) {
            $stmt->bind_param(
                "dssi", 
                $total_amount, 
                $payment_method, 
                $transaction_id, 
                $booking_id
            );
            $payment_updated = $stmt->execute();
            $stmt->close();
        } else {
             $payment_updated = false;
        }
    }

    // 3. Cập nhật status phòng (Logic đơn giản)
    $rooms_status_updated = true;
    // Nếu phòng thay đổi, đặt phòng cũ thành 'available'
    if ($old_room_id != $room_id) {
        $conn->query("UPDATE rooms SET status = 'available' WHERE id = {$old_room_id}");
    }
    
    // Đặt phòng mới (hoặc phòng hiện tại) thành 'booked' nếu status booking là confirmed/pending
    if ($status == 'confirmed' || $status == 'pending' || $status == 'checked_in') {
        $conn->query("UPDATE rooms SET status = 'booked' WHERE id = {$room_id}");
    } else if ($status == 'checked_out' || $status == 'cancelled') {
        $conn->query("UPDATE rooms SET status = 'available' WHERE id = {$room_id}");
    }
    

    // --- Thông báo kết quả ---
    if ($booking_updated && $payment_updated && $rooms_status_updated) {
        $_SESSION['message'] = "Cập nhật Booking #{$booking_id} thành công!";
        $_SESSION['msg_type'] = "success";
        header("Location: detail.php?id={$booking_id}");
    } else {
        $_SESSION['message'] = "Lỗi: Không thể cập nhật Booking. Vui lòng kiểm tra kết nối DB.";
        $_SESSION['msg_type'] = "danger";
    }
    $conn->close();
    exit();
}

$conn->close();

// Hàm tiện ích: tính toán tổng tiền tạm thời cho hiển thị
function calculate_temp_total($check_in, $check_out, $base_price) {
    if (!$check_in || !$check_out || !$base_price) return 0;
    try {
        $in = new DateTime($check_in);
        $out = new DateTime($check_out);
        $interval = $in->diff($out);
        $num_nights = $interval->days;
        // Đảm bảo số đêm >= 1 nếu check-out > check-in
        if ($num_nights == 0 && $in < $out) $num_nights = 1; 
        
        return $base_price * $num_nights;
    } catch (Exception $e) {
        return 0;
    }
}
$temp_total = calculate_temp_total($booking['check_in'], $booking['check_out'], $booking['base_room_price'] ?? 0);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa Booking #<?= $booking_id ?> - Admin</title>
    
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
            <h1 class="mb-0"><i class="fas fa-edit me-2 text-warning"></i> Sửa Booking #<?= $booking_id ?></h1>
            <a href="detail.php?id=<?= $booking_id ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Quay lại
            </a>
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

        <form method="POST" action="edit.php?id=<?= $booking_id ?>">
            <div class="row">
                
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-lg border-start border-5 border-warning h-100">
                        <div class="card-header bg-light text-dark fw-bold border-bottom">
                            <i class="fas fa-calendar-check me-2 text-warning"></i> Chi Tiết Đơn Đặt
                        </div>
                        <div class="card-body">
                            
                            <div class="mb-3 p-2 bg-light rounded">
                                <label class="form-label text-muted small">Khách hàng</label>
                                <p class="fw-bold mb-0"><?= htmlspecialchars($booking['username'] ?? 'Khách lẻ') ?> (ID: <?= $booking['user_id'] ?>)</p>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label fw-bold">Trạng thái Booking <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    <?php 
                                    $statuses = ['pending' => 'Đang chờ', 'confirmed' => 'Đã xác nhận', 'checked_in' => 'Đã nhận phòng', 'checked_out' => 'Đã trả phòng', 'cancelled' => 'Đã hủy'];
                                    foreach ($statuses as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= $booking['status'] == $val ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="room_id" class="form-label fw-bold">Phòng <span class="text-danger">*</span></label>
                                <select class="form-select" id="room_id" name="room_id" required>
                                    <option value="" disabled>-- Chọn phòng --</option>
                                    <?php foreach ($rooms_list as $room): 
                                        $label = "{$room['room_number']} ({$room['type_name']} - " . number_format($room['price']) . " VNĐ)";
                                        $selected = $booking['room_id'] == $room['id'] ? 'selected' : '';
                                        // Thêm class để dễ nhận biết phòng không khả dụng nhưng đang được book
                                        $is_current = $booking['room_id'] == $room['id'] ? ' (Phòng hiện tại)' : '';
                                    ?>
                                        <option value="<?= $room['id'] ?>" data-price="<?= $room['price'] ?>" <?= $selected ?>><?= $label . $is_current ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text text-danger fst-italic" id="room-warning" style="display:none;"><i class="fas fa-exclamation-triangle me-1"></i> Lưu ý: Thay đổi phòng sẽ giải phóng phòng cũ.</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="check_in" class="form-label fw-bold">Check-in <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="check_in" name="check_in" value="<?= date('Y-m-d', strtotime($booking['check_in'])) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="check_out" class="form-label fw-bold">Check-out <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="check_out" name="check_out" value="<?= date('Y-m-d', strtotime($booking['check_out'])) ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="guest_count" class="form-label fw-bold">Số lượng khách</label>
                                <input type="number" class="form-control" id="guest_count" name="guest_count" value="<?= htmlspecialchars($booking['guest_count'] ?? 1) ?>" min="1" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label fw-bold">Ghi chú</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($booking['notes'] ?? '') ?></textarea>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card shadow-lg border-start border-5 border-primary h-100">
                        <div class="card-header bg-light text-dark fw-bold border-bottom">
                            <i class="fas fa-calculator me-2 text-primary"></i> Chi Tiết Thanh Toán
                        </div>
                        <div class="card-body">
                            
                            <div class="alert alert-primary p-3 mb-4 rounded-3 text-center">
                                <h6 class="mb-1 text-uppercase">Tổng tiền ước tính (Chưa bao gồm dịch vụ)</h6>
                                <p id="temp_total_display" class="fw-bold fs-3 mb-0 text-success"><?= number_format($temp_total) ?> VNĐ</p>
                                <input type="hidden" id="calculated_total_price" value="<?= $temp_total ?>">
                                <div class="form-text mt-1 text-primary">Thay đổi Phòng/Ngày ở sẽ cập nhật giá này.</div>
                            </div>

                            <div class="mb-3">
                                <label for="total_amount" class="form-label fw-bold">Tổng số tiền ĐÃ thanh toán (VNĐ) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-lg" id="total_amount" name="total_amount" value="<?= htmlspecialchars($booking['total_amount'] ?? $booking['total_price'] ?? 0) ?>" step="1000" min="0" required>
                                <div class="form-text">Số tiền thực tế khách đã trả.</div>
                            </div>

                            <div class="mb-3">
                                <label for="payment_method" class="form-label fw-bold">Phương thức thanh toán <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <?php 
                                    $methods = ['cash' => 'Tiền mặt', 'banking' => 'Chuyển khoản', 'creditcard' => 'Thẻ tín dụng/ghi nợ', 'cod' => 'Thanh toán tại chỗ'];
                                    foreach ($methods as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= $booking['payment_method'] == $val ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="transaction_id" class="form-label fw-bold">Mã giao dịch (Nếu có)</label>
                                <input type="text" class="form-control" id="transaction_id" name="transaction_id" value="<?= htmlspecialchars($booking['transaction_id'] ?? '') ?>">
                            </div>
                            
                        </div>
                        
                        <div class="card-footer bg-light text-end">
                            <button type="submit" class="btn btn-warning btn-lg shadow-sm"><i class="fas fa-save me-2"></i> Cập Nhật Booking</button>
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </div>

</div> 
</div> 

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // --- Javascript để tính toán lại Tổng tiền ước tính ---

    const roomSelect = document.getElementById('room_id');
    const checkInInput = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');
    const tempTotalDisplay = document.getElementById('temp_total_display');
    const roomWarning = document.getElementById('room-warning');
    const totalAmountInput = document.getElementById('total_amount');
    const initialRoomId = <?= $booking['room_id'] ?>;

    function formatDate(dateString) {
        if (!dateString) return null;
        // Chuyển đổi định dạng yyyy-mm-dd
        return dateString.split('T')[0];
    }
    
    function calculateTotal() {
        const selectedOption = roomSelect.options[roomSelect.selectedIndex];
        const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
        const checkIn = new Date(formatDate(checkInInput.value));
        const checkOut = new Date(formatDate(checkOutInput.value));
        
        let total = 0;
        let numNights = 0;

        if (checkIn && checkOut && checkOut > checkIn) {
            const timeDiff = checkOut.getTime() - checkIn.getTime();
            numNights = Math.ceil(timeDiff / (1000 * 3600 * 24));
            if (numNights === 0) numNights = 1; // Đảm bảo tính ít nhất 1 đêm nếu ngày check-out > check-in
            total = price * numNights;
        }

        // Hiển thị giá trị đã format
        tempTotalDisplay.textContent = new Intl.NumberFormat('vi-VN', { 
            style: 'currency', 
            currency: 'VND', 
            minimumFractionDigits: 0 
        }).format(total);
        
        // Cập nhật cảnh báo
        const currentRoomId = parseInt(roomSelect.value);
        if (currentRoomId !== initialRoomId) {
            roomWarning.style.display = 'block';
        } else {
            roomWarning.style.display = 'none';
        }
    }

    // Gán sự kiện và chạy lần đầu
    roomSelect.addEventListener('change', calculateTotal);
    checkInInput.addEventListener('change', calculateTotal);
    checkOutInput.addEventListener('change', calculateTotal);

    // Kích hoạt tính toán khi tải trang
    calculateTotal();

    // Fade-in effect
    document.addEventListener('DOMContentLoaded', function() {
        const content = document.querySelector('.content-area-main');
        if (content) {
            setTimeout(() => {
                content.classList.remove('initial-hidden');
                content.classList.add('fade-in');
            }, 50); 
        }
    });
</script>

</body>
</html>