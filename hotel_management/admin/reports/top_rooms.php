<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
include "../../config/db.php";

$rooms_labels = [];
$rooms_data = [];

// --- CẬP NHẬT SQL CHUẨN ---
// 1. JOIN 'bookings' với 'rooms' qua 'room_id'
// 2. JOIN 'rooms' với 'room_types' qua 'type_id' (Đây là cột bạn vừa gửi)
// 3. Lấy 'type_name' từ bảng 'room_types'
$sql = "
    SELECT 
        r.room_number, 
        rt.type_name, 
        COUNT(b.id) as total_bookings
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN room_types rt ON r.type_id = rt.id 
    WHERE b.status != 'cancelled'
    GROUP BY r.id
    ORDER BY total_bookings DESC
    LIMIT 5
";

try {
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Hiển thị: Phòng 101 - Deluxe Double
            $rooms_labels[] = "Phòng " . $row['room_number'] . " - " . $row['type_name'];
            $rooms_data[] = $row['total_bookings'];
        }
    }
} catch (mysqli_sql_exception $e) {
    die("Lỗi SQL: " . $e->getMessage());
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Top Phòng - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        #sidebar { min-height: 100vh; width: 250px; background: #212529; flex-shrink: 0; }
        .content-area { flex-grow: 1; padding: 20px; }
    </style>
</head>
<body>
<div class="d-flex">
    <nav id="sidebar" class="d-flex flex-column p-3 text-white">
        <h3 class="text-center mb-4">QLKS Admin</h3>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item mb-2"><a href="../index.php" class="nav-link text-white"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
            <li class="nav-item mb-2"><a href="../bookings/index.php" class="nav-link text-white"><i class="fas fa-book me-2"></i> Quản lý Booking</a></li>
            <li class="nav-item mb-2"><a href="../rooms/index.php" class="nav-link text-white"><i class="fas fa-bed me-2"></i> Quản lý Phòng</a></li>
            <li class="nav-item mb-2"><a href="../users/index.php" class="nav-link text-white"><i class="fas fa-users me-2"></i> Quản lý Users</a></li>
            <li class="nav-item mb-2"><a href="../payments/index.php" class="nav-link text-white"><i class="fas fa-credit-card me-2"></i> Thanh toán</a></li>
            
            <li class="nav-item mb-2">
                <a href="#" class="nav-link active bg-primary text-white"><i class="fas fa-chart-line me-2"></i> Báo cáo</a>
                <div class="ms-4 mt-1">
                    <a href="revenue.php" class="d-block text-white-50 text-decoration-none mb-1">• Doanh thu</a>
                    <a href="top_rooms.php" class="d-block text-white text-decoration-none fw-bold">• Top Phòng</a>
                </div>
            </li>
        </ul>
        <hr>
        <a href="../logout.php" class="btn btn-outline-danger w-100">Đăng xuất</a>
    </nav>

    <div class="content-area bg-light">
        <h2 class="text-primary fw-bold mb-4"><i class="fas fa-trophy me-2"></i> Top Phòng Được Yêu Thích</h2>
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <canvas id="topRoomsChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">Danh sách chi tiết</div>
                    <ul class="list-group list-group-flush">
                        <?php for($i=0; $i < count($rooms_labels); $i++): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= $rooms_labels[$i] ?>
                            <span class="badge bg-primary rounded-pill"><?= $rooms_data[$i] ?> lượt</span>
                        </li>
                        <?php endfor; ?>
                         <?php if(empty($rooms_labels)): ?>
                            <li class="list-group-item text-center text-muted p-3">Chưa có dữ liệu đặt phòng nào.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('topRoomsChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut', // Biểu đồ hình tròn khuyết
    data: {
        labels: <?= json_encode($rooms_labels) ?>,
        datasets: [{
            label: 'Số lượt đặt',
            data: <?= json_encode($rooms_data) ?>,
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
            ],
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
            }
        }
    }
});
</script>
</body>
</html>