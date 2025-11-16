<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); 
    exit();
}

include "../../config/db.php"; 

$users = [];
$rooms = [];

// Lấy danh sách Users để chọn Khách hàng
$user_query = "SELECT id, username FROM users ORDER BY username ASC";
$user_result = $conn->query($user_query);
while ($row = $user_result->fetch_assoc()) {
    $users[] = $row;
}

// Lấy danh sách Rooms (phòng) và Loại phòng (Đã sửa lỗi giá 0 VNĐ bằng cách chạy lại SQL)
$room_query = "
    SELECT r.id, r.room_number, rt.type_name, rt.price_per_night 
    FROM rooms r
    JOIN room_types rt ON r.type_id = rt.id
    ORDER BY r.room_number ASC
";
$room_result = $conn->query($room_query);
while ($row = $room_result->fetch_assoc()) {
    $rooms[] = $row;
}

$conn->close();

// Kiểm tra và hiển thị thông báo lỗi/thành công từ Session
$message = $_SESSION['message'] ?? null;
$msg_type = $_SESSION['msg_type'] ?? 'danger';
unset($_SESSION['message']);
unset($_SESSION['msg_type']);

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo Booking mới - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/booking_create.css">
    
</head>
<body>

<div class="main-wrapper d-flex">

<nav id="sidebar" class="bg-dark text-white p-3 shadow-lg">
    <div class="sidebar-header text-center mb-4"><h3 class="text-white">QLKS Admin</h3><hr class="text-secondary"></div>
    <ul class="list-unstyled components">
        <li><a href="../index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
        <li class="active"><a href="index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded bg-primary"><i class="fas fa-book me-2"></i> QL Booking</a></li>
        <li><a href="../rooms/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded"><i class="fas fa-bed me-2"></i> QL Phòng</a></li>
        <li><a href="../users/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded"><i class="fas fa-users me-2"></i> QL Users</a></li>
        <li><a href="../payments/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded"><i class="fas fa-credit-card me-2"></i> Thanh toán</a></li>
    </ul>
    <div class="bottom-logout p-3"><hr class="text-secondary"><a href="../logout.php" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i> Đăng xuất</a></div>
</nav>

<div class="page-content-wrapper flex-grow-1 content-area-main initial-hidden">
    
    <nav class="navbar navbar-light bg-white shadow-sm sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand text-primary fw-bold" href="index.php">
                <i class="fas fa-arrow-left me-1"></i> Quay lại Danh sách Booking
            </a>
        </div>
    </nav>

    <div class="content container-fluid">
        <h1 class="mb-4"><i class="fas fa-plus-circle me-2 text-primary"></i> Tạo Booking mới</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?= $msg_type; ?> alert-dismissible fade show" role="alert">
                <?= $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-lg">
            <div class="card-body p-4">
                <form action="process_create.php" method="POST">
                    
                    <div class="row g-4">
                        
                        <div class="col-lg-6">
                            
                            <div class="form-section-header header-primary">
                                <i class="fas fa-user-check me-2"></i> Thông tin Khách hàng
                            </div>
                            
                            <div class="mb-4">
                                <label for="user_id" class="form-label">Khách hàng</label>
                                <select name="user_id" id="user_id" class="form-select">
                                    <option value="">Khách lẻ (Không chọn User)</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?> (ID: <?= $user['id'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            
                            <div class="form-section-header header-success">
                                <i class="fas fa-bed me-2"></i> Thông tin Phòng
                            </div>
                            
                            <div class="mb-3">
                                <label for="room_id" class="form-label required">Phòng đặt</label>
                                <select name="room_id" id="room_id" class="form-select" required>
                                    <option value="">Chọn phòng</option>
                                    <?php foreach ($rooms as $room): ?>
                                        <option 
                                            value="<?= $room['id'] ?>" 
                                            data-price="<?= $room['price_per_night'] ?>"
                                            data-room-number="<?= $room['room_number'] ?>"
                                        >
                                            Phòng <?= $room['room_number'] ?> - <?= htmlspecialchars($room['type_name']) ?> (<?= number_format($room['price_per_night']) ?> VNĐ/đêm)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="guest_count" class="form-label required">Số lượng khách</label>
                                <input type="number" name="guest_count" id="guest_count" class="form-control" value="1" min="1" required>
                            </div>
                            
                        </div>

                        <div class="col-lg-6">
                            
                            <div class="form-section-header header-warning">
                                <i class="fas fa-calendar-alt me-2"></i> Ngày & Trạng thái
                            </div>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="check_in" class="form-label required">Ngày Check-in</label>
                                    <input type="date" name="check_in" id="check_in" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="check_out" class="form-label required">Ngày Check-out</label>
                                    <input type="date" name="check_out" id="check_out" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="status" class="form-label required">Trạng thái Booking</label>
                                <select name="status" id="status" class="form-select" required>
                                    <option value="pending">Đang chờ xử lý (Pending)</option>
                                    <option value="confirmed">Đã xác nhận (Confirmed)</option>
                                    <option value="checked_in">Đã nhận phòng (Checked In)</option>
                                    <option value="checked_out">Đã trả phòng (Checked Out)</option>
                                    <option value="cancelled">Đã hủy (Cancelled)</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="payment_method" class="form-label required">Phương thức thanh toán</label>
                                <select name="payment_method" id="payment_method" class="form-select" required>
                                    <option value="cash">Thanh toán tại quầy (COD)</option>
                                    <option value="banking">Chuyển khoản Ngân hàng (BANK)</option>
                                    <option value="creditcard">Cổng thanh toán (VNPay/Momo...)</option>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="notes" class="form-label">Ghi chú</label>
                                <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                            </div>
                            
                        </div>
                        
                        <div class="col-12">
                            <hr class="mt-0">
                            <div class="total-price-box d-flex justify-content-between align-items-center">
                                <h4 class="mb-0">Tổng tiền dự kiến:</h4> 
                                <h4 class="mb-0" id="total_price_display">0 VNĐ</h4>
                            </div>
                            <input type="hidden" name="total_price" id="total_price_input" value="0"> 
                        </div>

                        <div class="col-12 text-end pt-3">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save me-2"></i> Lưu Booking
                            </button>
                        </div>
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
    const content = document.querySelector('.content-area-main');
    if (content) {
        setTimeout(() => {
            content.classList.remove('initial-hidden');
            content.classList.add('fade-in');
        }, 50); 
    }

    const roomSelect = document.getElementById('room_id');
    const checkInInput = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');
    const totalPriceDisplay = document.getElementById('total_price_display');
    const totalPriceInput = document.getElementById('total_price_input');

    // Hàm tính toán và cập nhật tổng tiền
    function calculateTotalPrice() {
        const selectedRoom = roomSelect.options[roomSelect.selectedIndex];
        if (!selectedRoom || !selectedRoom.value || !checkInInput.value || !checkOutInput.value) {
            totalPriceDisplay.textContent = "0 VNĐ";
            totalPriceInput.value = 0;
            return;
        }

        const pricePerNight = parseFloat(selectedRoom.getAttribute('data-price')) || 0;
        const checkInDate = new Date(checkInInput.value);
        const checkOutDate = new Date(checkOutInput.value);

        if (checkOutDate <= checkInDate) {
            totalPriceDisplay.textContent = "Ngày không hợp lệ";
            totalPriceInput.value = 0;
            return;
        }

        const diffTime = Math.abs(checkOutDate - checkInDate);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        const totalPrice = pricePerNight * diffDays;
        
        // Cập nhật hiển thị và giá trị ẩn
        totalPriceDisplay.textContent = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(totalPrice);
        totalPriceInput.value = totalPrice.toFixed(2); // Lưu giá trị thập phân chính xác
    }

    roomSelect.addEventListener('change', calculateTotalPrice);
    checkInInput.addEventListener('change', calculateTotalPrice);
    checkOutInput.addEventListener('change', calculateTotalPrice);

    checkInInput.addEventListener('change', function() {
        if (this.value) {
            const minCheckoutDate = new Date(this.value);
            minCheckoutDate.setDate(minCheckoutDate.getDate() + 1);
            checkOutInput.min = minCheckoutDate.toISOString().split('T')[0];
        }
    });

    calculateTotalPrice();
});
</script>

</body>
</html>