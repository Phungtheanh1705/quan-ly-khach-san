<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Đường dẫn config (admin/index.php đi ra 1 cấp để gặp config)
include "../config/db.php";

/* --- LẤY TỔNG SỐ LIỆU --- */
$total_rooms = $conn->query("SELECT COUNT(*) AS total FROM rooms")->fetch_assoc()['total'] ?? 0;
$total_bookings = $conn->query("SELECT COUNT(*) AS total FROM bookings")->fetch_assoc()['total'] ?? 0;
$total_users = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'] ?? 0;
$total_revenue = $conn->query("SELECT SUM(amount) AS total FROM payments WHERE status = 'completed'")->fetch_assoc()['total'] ?? 0; // Chỉ tính tiền đã hoàn tất
if (!$total_revenue) $total_revenue = 0;

/* --- XỬ LÝ LỌC --- */
$where = [];
$valid_status = ["pending","confirmed","checked_in","checked_out","cancelled"];

if (!empty($_GET['username'])) {
    $username = $conn->real_escape_string($_GET['username']);
    $where[] = "u.username LIKE '%$username%'";
}

if (!empty($_GET['room_number'])) {
    $room = $conn->real_escape_string($_GET['room_number']);
    $where[] = "r.room_number LIKE '%$room%'";
}

if (!empty($_GET['status']) && in_array($_GET['status'], $valid_status)) {
    $status = $conn->real_escape_string($_GET['status']);
    $where[] = "b.status = '$status'";
}

if (!empty($_GET['payment_method'])) {
    $payment = $conn->real_escape_string($_GET['payment_method']);
    $where[] = "b.payment_method = '$payment'";
}

if (!empty($_GET['check_in'])) {
    $ci = $conn->real_escape_string($_GET['check_in']);
    $where[] = "b.check_in >= '$ci'";
}

if (!empty($_GET['check_out'])) {
    $co = $conn->real_escape_string($_GET['check_out']);
    $where[] = "b.check_out <= '$co'";
}

$filter_sql = count($where) ? "WHERE ".implode(" AND ", $where) : "";

/* --- PHÂN TRANG --- */
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$total_booking_count = $conn->query("
    SELECT COUNT(*) AS total
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN rooms r ON b.room_id = r.id
    $filter_sql
")->fetch_assoc()['total'] ?? 0;

$total_pages = ceil($total_booking_count / $limit);

/* --- LẤY DỮ LIỆU BOOKING --- */
$recent = $conn->query("
    SELECT b.id, u.username, r.room_number, b.check_in, b.check_out, b.status,
           b.payment_method, p.amount AS total_amount
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN rooms r ON b.room_id = r.id
    LEFT JOIN payments p ON b.id = p.booking_id
    $filter_sql
    ORDER BY b.id DESC 
    LIMIT $limit OFFSET $offset
");

// Hàm tiện ích cho trạng thái
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
            return '<span class="badge bg-info">' . ucfirst($status) . '</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - QLKS Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
        .main-wrapper { display: flex; min-height: 100vh; }
        .content-area-main { opacity: 0; transition: opacity 0.5s ease-in-out; }
        .content-area-main.fade-in { opacity: 1; }
        .initial-hidden { opacity: 0; }
        .card-body h5 { font-size: 1rem; font-weight: 500; }
        .card-body h3 { font-size: 2rem; font-weight: 700; }
        .card { border-radius: 10px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); transition: transform 0.3s; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); }
        .stat-icon { font-size: 2.5rem; opacity: 0.8; }
        .booking-header { border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        
        /* FIX LỖI SIDEBAR */
        #sidebar {
            display: flex;
            flex-direction: column; 
            height: 100vh; 
            position: sticky;
            top: 0;
            width: 250px;
        }
        #sidebar .list-unstyled {
            flex-grow: 1;
            overflow-y: auto;
            padding: 15px 0;
        }
        .bottom-logout {
            margin-top: auto; 
            width: 100%;
            padding: 1rem;
        }
        
        .filter-form-wrapper {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            padding: 15px;
        }
        .filter-form-wrapper label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #495057;
        }
        .filter-form-wrapper .form-control, .filter-form-wrapper .form-select {
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
            height: 38px;
        }
        .table thead th, .table tbody td {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="main-wrapper d-flex">

<nav id="sidebar" class="bg-dark text-white shadow-lg collapse d-lg-block">
    <div class="sidebar-header text-center mb-4 p-3">
        <h3 class="text-white"><i class="fas fa-hotel me-2"></i> QLKS Admin</h3>
        <hr class="text-secondary">
    </div>

    <ul class="list-unstyled components">
        <li class="active">
            <a href="index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded bg-primary nav-link-fade">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="bookings/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded nav-link-fade">
                <i class="fas fa-book me-2"></i> Quản lý Booking
            </a>
        </li>
        <li>
            <a href="rooms/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded nav-link-fade">
                <i class="fas fa-bed me-2"></i> Quản lý Phòng
            </a>
        </li>
        <li>
            <a href="users/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded nav-link-fade">
                <i class="fas fa-users me-2"></i> Quản lý Users
            </a>
        </li>
        <li>
            <a href="payments/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded nav-link-fade">
                <i class="fas fa-credit-card me-2"></i> Thanh toán
            </a>
        </li>
        <li>
             <a href="#reportSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle d-block py-2 px-3 text-white text-decoration-none rounded">
                 <i class="fas fa-chart-line me-2"></i> Báo cáo & Thống kê
             </a>
             <ul class="collapse list-unstyled" id="reportSubmenu">
                 <li><a href="reports/revenue.php" class="d-block py-2 ps-5 text-white-50 text-decoration-none nav-link-fade">Doanh thu</a></li>
                 <li><a href="reports/top_rooms.php" class="d-block py-2 ps-5 text-white-50 text-decoration-none nav-link-fade">Top Phòng</a></li>
             </ul>
        </li>
    </ul>

    <div class="bottom-logout">
        <hr class="text-secondary">
        <a href="logout.php" class="btn btn-outline-danger w-100 nav-link-fade">
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
            
            <a class="navbar-brand text-primary fw-bold nav-link-fade" href="index.php">
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
                            <li><a class="dropdown-item nav-link-fade" href="profile.php"><i class="fas fa-user-cog me-2"></i> Hồ sơ</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger nav-link-fade" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Đăng xuất
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content container-fluid mt-4">
        <h1 class="mb-4"><i class="fas fa-tachometer-alt me-2 text-primary"></i> Dashboard Admin</h1>

        <div class="row g-4 mb-5">
            <div class="col-sm-6 col-md-3">
                <div class="card text-center text-white bg-primary">
                    <div class="card-body">
                        <i class="fas fa-bed stat-icon"></i>
                        <h5>Tổng phòng</h5>
                        <h3><?php echo $total_rooms; ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="card text-center text-white bg-success">
                    <div class="card-body">
                        <i class="fas fa-book stat-icon"></i>
                        <h5>Tổng booking</h5>
                        <h3><?php echo $total_bookings; ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="card text-center text-white bg-warning">
                    <div class="card-body">
                        <i class="fas fa-users stat-icon"></i>
                        <h5>Tổng user</h5>
                        <h3><?php echo $total_users; ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="card text-center text-white bg-danger">
                    <div class="card-body">
                        <i class="fas fa-credit-card stat-icon"></i>
                        <h5>Tổng doanh thu</h5>
                        <h3><?php echo number_format($total_revenue); ?> VNĐ</h3>
                    </div>
                </div>
            </div>
        </div>


        <h3 class="booking-header"><i class="fas fa-history me-2"></i> Booking gần đây</h3>
        
        <div class="filter-form-wrapper mb-4 p-3 border rounded">
            <form class="row g-3 align-items-end" method="get">
                
                <div class="col-md-3">
                    <label>Tên người đặt</label>
                    <input type="text" name="username" class="form-control form-control-sm" value="<?= $_GET['username'] ?? '' ?>" placeholder="Tên khách...">
                </div>

                <div class="col-md-3">
                    <label>Số phòng</label>
                    <input type="text" name="room_number" class="form-control form-control-sm" value="<?= $_GET['room_number'] ?? '' ?>" placeholder="Phòng số...">
                </div>

                <div class="col-md-3">
                    <label>Trạng thái</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Tất cả</option>
                        <?php
                        foreach ($valid_status as $s) {
                            $sel = ($s == ($_GET['status'] ?? "")) ? "selected" : "";
                            echo "<option value='$s' $sel>".ucfirst(str_replace("_"," ",$s))."</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Thanh toán</label>
                    <select name="payment_method" class="form-select form-select-sm">
                        <option value="">Tất cả</option>
                        <option value="cod" <?= (($_GET['payment_method'] ?? '') == 'cod') ? "selected" : "" ?>>COD</option>
                        <option value="bank" <?= (($_GET['payment_method'] ?? '') == 'bank') ? "selected" : "" ?>>BANK</option>
                        <option value="vnpay" <?= (($_GET['payment_method'] ?? '') == 'vnpay') ? "selected" : "" ?>>VNPAY</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Check-in</label>
                    <input type="date" name="check_in" class="form-control form-control-sm" value="<?= $_GET['check_in'] ?? '' ?>">
                </div>

                <div class="col-md-3">
                    <label>Check-out</label>
                    <input type="date" name="check_out" class="form-control form-control-sm" value="<?= $_GET['check_out'] ?? '' ?>">
                </div>

                <div class="col-auto">
                    <button class="btn btn-primary btn-sm">Lọc</button>
                </div>
                
                <?php 
                $has_filters = false;
                foreach($_GET as $key => $value) {
                    if ($key !== 'page' && !empty($value)) {
                        $has_filters = true;
                        break;
                    }
                }
                if ($has_filters): ?>
                <div class="col-auto">
                    <a href="index.php" class="btn btn-secondary btn-sm">Xóa Lọc</a>
                </div>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive mt-3">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Khách</th>
                        <th>Phòng</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Thanh toán</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent->num_rows > 0): ?>
                        <?php while ($row = $recent->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td class="text-start"><?= $row['username'] ?></td>
                                <td><strong><?= $row['room_number'] ?></strong></td>
                                <td><?= date('d/m/Y', strtotime($row['check_in'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($row['check_out'])) ?></td>
                                <td>
                                    <strong><?= $row['total_amount'] ? number_format($row['total_amount']) . " VNĐ" : "—" ?></strong>
                                </td>
                                <td>
                                    <?= get_status_badge($row['status']) ?>
                                </td>
                                <td><?= strtoupper($row['payment_method']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-danger">Không có kết quả</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>


        <?php if ($total_pages > 1): ?>
        <nav aria-label="Phân trang booking">
            <ul class="pagination justify-content-center mt-3">
                <?php
                $params = $_GET;

                if ($page > 1) {
                    $params['page'] = $page - 1;
                    echo '<li class="page-item"><a class="page-link nav-link-fade" href="?'.http_build_query($params).'">Trước</a></li>';
                }

                for ($i=1; $i <= $total_pages; $i++) {
                    $active = ($i == $page) ? "active" : "";
                    $params['page'] = $i;
                    echo "<li class='page-item $active'><a class='page-link nav-link-fade' href='?".http_build_query($params)."'>$i</a></li>";
                }

                if ($page < $total_pages) {
                    $params['page'] = $page + 1;
                    echo '<li class="page-item"><a class="page-link nav-link-fade" href="?'.http_build_query($params).'">Tiếp</a></li>';
                }
                ?>
            </ul>
        </nav>
        <?php endif; ?>

    </div>

</div> 
</div> 

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const contentArea = document.querySelector('.content-area-main');

    // 1. KÍCH HOẠT HIỆU ỨNG FADE-IN KHI TẢI TRANG
    if (contentArea) {
        setTimeout(() => {
            contentArea.classList.remove('initial-hidden');
            contentArea.classList.add('fade-in');
        }, 50); 
    }
    
    // 2. XỬ LÝ FADE-OUT KHI NHẤN VÀO LINK (HIỆU ỨNG CHUYỂN TRANG)
    document.querySelectorAll('a.nav-link-fade, .pagination .page-link-fade').forEach(link => {
        if (!link.closest('.page-item.disabled')) { 
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                
                if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
                    e.preventDefault(); 
                    
                    // Kích hoạt hiệu ứng fade out
                    if (contentArea) {
                        contentArea.classList.remove('fade-in');
                        contentArea.classList.add('initial-hidden');
                    }
                    
                    // Chờ hiệu ứng fade out hoàn tất (0.5s) rồi mới chuyển hướng
                    setTimeout(() => {
                        window.location.href = href;
                    }, 500); 
                }
            });
        }
    });

    // 3. LOGIC ĐÓNG SIDEBAR KHI NHẤN RA NGOÀI (Cho màn hình mobile)
    if (sidebar) {
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
        
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
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