<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
include "../../config/db.php";

// Lấy năm từ URL (mặc định năm nay)
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Lấy dữ liệu doanh thu theo tháng (Chỉ tính đơn đã hoàn tất - status 'completed')
$revenue_data = array_fill(1, 12, 0);
$sql = "SELECT MONTH(payment_date) as month, SUM(amount) as total 
        FROM payments 
        WHERE YEAR(payment_date) = ? AND status = 'completed' 
        GROUP BY MONTH(payment_date)";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $revenue_data[$row['month']] = $row['total'];
    }
    $stmt->close();
}

$total_year = array_sum($revenue_data);
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo Doanh thu - Admin</title>
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
                    <a href="revenue.php" class="d-block text-white text-decoration-none fw-bold mb-1">• Doanh thu</a>
                    <a href="top_rooms.php" class="d-block text-white-50 text-decoration-none">• Top Phòng</a>
                </div>
            </li>
        </ul>
        <hr>
        <a href="../logout.php" class="btn btn-outline-danger w-100">Đăng xuất</a>
    </nav>

    <div class="content-area bg-light">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-primary fw-bold"><i class="fas fa-chart-column me-2"></i> Báo cáo Doanh thu</h2>
            <div class="d-flex gap-2">
                <form method="GET" class="d-flex">
                    <select name="year" class="form-select me-2" onchange="this.form.submit()">
                        <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>>Năm <?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
                <a href="export_excel.php?year=<?= $year ?>" class="btn btn-success"><i class="fas fa-file-excel me-2"></i> Xuất Excel</a>
            </div>
        </div>

        <div class="card bg-primary text-white shadow-sm mb-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="opacity-75">Tổng doanh thu năm <?= $year ?></h5>
                    <h2 class="fw-bold mb-0"><?= number_format($total_year) ?> VNĐ</h2>
                </div>
                <i class="fas fa-wallet fa-3x opacity-50"></i>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">Biểu đồ biến động doanh thu</div>
            <div class="card-body">
                <canvas id="revenueChart" style="height: 350px;"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'line', 
    data: {
        labels: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
        datasets: [{
            label: 'Doanh thu (VNĐ)',
            data: <?= json_encode(array_values($revenue_data)) ?>,
            backgroundColor: 'rgba(13, 110, 253, 0.2)',
            borderColor: '#0d6efd',
            borderWidth: 2,
            fill: true,
            tension: 0.3
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
});
</script>
</body>
</html>