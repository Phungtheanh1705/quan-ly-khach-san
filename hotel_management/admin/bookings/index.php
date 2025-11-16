<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); 
    exit();
}

include "../../config/db.php"; 

/* --- LẤY DỮ LIỆU BOOKING VÀ XỬ LÝ LỌC & PHÂN TRANG BẰNG PREPARED STATEMENTS --- */

$where_clauses = [];
$params = [];
$param_types = "";
$valid_status = ["pending", "confirmed", "checked_in", "checked_out", "cancelled"];

// 1. Tên người đặt (username)
if (!empty($_GET['username'])) {
    $where_clauses[] = "u.username LIKE ?";
    $params[] = "%" . $_GET['username'] . "%";
    $param_types .= "s";
}
// 2. Số phòng (room_number)
if (!empty($_GET['room_number'])) {
    $where_clauses[] = "r.room_number LIKE ?";
    $params[] = "%" . $_GET['room_number'] . "%";
    $param_types .= "s";
}
// 3. Trạng thái (status)
if (!empty($_GET['status']) && in_array($_GET['status'], $valid_status)) {
    $where_clauses[] = "b.status = ?";
    $params[] = $_GET['status'];
    $param_types .= "s";
}
// 4. Phương thức thanh toán (payment_method)
if (!empty($_GET['payment_method'])) {
    $where_clauses[] = "b.payment_method = ?";
    $params[] = $_GET['payment_method'];
    $param_types .= "s";
}
// 5. Check-in từ (check_in >=)
if (!empty($_GET['check_in'])) {
    $where_clauses[] = "b.check_in >= ?";
    $params[] = $_GET['check_in'];
    $param_types .= "s";
}
// 6. Check-out đến (check_out <=)
if (!empty($_GET['check_out'])) {
    $check_out_date = date('Y-m-d', strtotime($_GET['check_out'] . ' +1 day'));
    $where_clauses[] = "b.check_out < ?";
    $params[] = $check_out_date;
    $param_types .= "s";
}

$filter_sql = count($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";

// --- PHÂN TRANG ---
$limit = 10; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;


// --- HÀM HỖ TRỢ BIND THAM SỐ DÙNG THAM CHIẾU ---
function bind_params_reference($stmt, $param_types, $params) {
    $bind_names = [$param_types];
    for ($i = 0; $i < count($params); $i++) {
        $bind_names[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}
// -----------------------------------------------------------------


// 1. Lấy tổng số lượng kết quả sau khi lọc
$total_query = "
    SELECT COUNT(*) AS total
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN rooms r ON b.room_id = r.id
    $filter_sql
";
$total = 0;
if ($stmt_total = $conn->prepare($total_query)) {
    if (count($params) > 0) {
        bind_params_reference($stmt_total, $param_types, $params);
    }
    $stmt_total->execute();
    $total = $stmt_total->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_total->close();
}
$total_pages = ceil($total / $limit);


// 2. Lấy dữ liệu Booking
$booking_query = "
    SELECT b.id, u.username, r.room_number, rt.type_name, b.check_in, b.check_out, b.status,
           b.payment_method, p.amount AS total_amount, b.created_at
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN rooms r ON b.room_id = r.id
    LEFT JOIN room_types rt ON r.type_id = rt.id
    LEFT JOIN payments p ON b.id = p.booking_id
    $filter_sql
    ORDER BY b.created_at DESC
    LIMIT ? OFFSET ?
";
$bookings = [];
if ($stmt_booking = $conn->prepare($booking_query)) {
    
    $final_params = $params;
    $final_params[] = $limit; 
    $final_params[] = $offset; 
    $final_param_types = $param_types . "ii"; 

    bind_params_reference($stmt_booking, $final_param_types, $final_params);

    $stmt_booking->execute();
    $bookings_result = $stmt_booking->get_result();
    while ($row = $bookings_result->fetch_assoc()) {
        $bookings[] = $row;
    }
    $stmt_booking->close();
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
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Booking - Admin Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <link rel="stylesheet" href="../../assets/css/admin.css"> 
    
    <style>
        .table-responsive td .btn-sm {
            /* ÉP GIẢM padding ngang và dọc tối đa */
            padding: 0.1rem 0.25rem !important; 
            line-height: 1; 
            /* ÉP GIẢM kích thước font icon xuống 0.75rem */
            font-size: 0.75rem !important; 
        }
        /* Đảm bảo cột không bao giờ xuống dòng, giữ các nút xếp ngang */
        .table-responsive td:last-child {
            white-space: nowrap; 
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
            <a href="../index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
        </li>
        <li class="active">
            <a href="index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded bg-primary"> 
                <i class="fas fa-book me-2"></i> Quản lý Booking
            </a>
        </li>
        <li>
            <a href="../rooms/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded">
                <i class="fas fa-bed me-2"></i> Quản lý Phòng
            </a>
        </li>
        <li>
            <a href="../users/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded">
                <i class="fas fa-users me-2"></i> Quản lý Users
            </a>
        </li>
        <li>
            <a href="../payments/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded">
                <i class="fas fa-credit-card me-2"></i> Thanh toán
            </a>
        </li>
        <li>
            <a href="#reportSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle d-block py-2 px-3 text-white text-decoration-none rounded">
                <i class="fas fa-chart-line me-2"></i> Báo cáo & Thống kê
            </a>
            <ul class="collapse list-unstyled" id="reportSubmenu">
                <li>
                    <a href="../reports/monthly.php" class="d-block py-2 ps-5 text-white-50 text-decoration-none">Doanh thu tháng</a>
                </li>
                <li>
                    <a href="../reports/yearly.php" class="d-block py-2 ps-5 text-white-50 text-decoration-none">Tổng quan năm</a>
                </li>
            </ul>
        </li>
    </ul>

    <div class="bottom-logout p-3">
        <hr class="text-secondary">
        <a href="../logout.php" class="btn btn-outline-danger w-100">
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
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Đăng xuất
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content container-fluid">
        <h1 class="mb-4"><i class="fas fa-book me-2 text-primary"></i> Quản lý Booking</h1>
        
        <?php 
        // Hiển thị thông báo (Nếu có)
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

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Danh sách Đơn đặt phòng (<?= $total ?>)</h5>
                <a href="create.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> Tạo Booking mới
                </a>
            </div>
            <div class="card-body">
                
                <div class="filter-form-wrapper shadow-sm mb-4 p-3 border rounded">
                    <form class="row g-3 align-items-end" method="get" action="index.php">
                        
                        <div class="col-12 col-md-4 col-lg-2">
                            <label class="small">Tên người đặt</label>
                            <input type="text" name="username" class="form-control form-control-sm" value="<?= $_GET['username'] ?? '' ?>" placeholder="Tên khách...">
                        </div>

                        <div class="col-12 col-md-4 col-lg-2">
                            <label class="small">Số phòng</label>
                            <input type="text" name="room_number" class="form-control form-control-sm" value="<?= $_GET['room_number'] ?? '' ?>" placeholder="Phòng số...">
                        </div>
                        
                        <div class="col-12 col-md-4 col-lg-2">
                            <label class="small">Trạng thái</label>
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
                        
                        <div class="col-12 col-md-4 col-lg-2">
                            <label class="small">Check-in từ</label>
                            <input type="date" name="check_in" class="form-control form-control-sm" value="<?= $_GET['check_in'] ?? '' ?>">
                        </div>

                        <div class="col-12 col-md-4 col-lg-2">
                            <label class="small">Check-out đến</label>
                            <input type="date" name="check_out" class="form-control form-control-sm" value="<?= $_GET['check_out'] ?? '' ?>">
                        </div>
                        
                        <div class="col-12 col-md-4 col-lg-2">
                            <label class="small">Thanh toán</label>
                            <select name="payment_method" class="form-select form-select-sm">
                                <option value="">Tất cả</option>
                                <option value="cod" <?= (($_GET['payment_method'] ?? '') == 'cod') ? "selected" : "" ?>>COD</option>
                                <option value="bank" <?= (($_GET['payment_method'] ?? '') == 'bank') ? "selected" : "" ?>>BANK</option>
                                <option value="vnpay" <?= (($_GET['payment_method'] ?? '') == 'vnpay') ? "selected" : "" ?>>VNPAY</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-auto">
                            <button class="btn btn-info btn-sm mt-3">
                                <i class="fas fa-filter me-1"></i> Lọc
                            </button>
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
                        <div class="col-12 col-md-auto">
                            <a href="index.php" class="btn btn-secondary btn-sm mt-3">
                                <i class="fas fa-times me-1"></i> Xóa Lọc
                            </a>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>


                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Khách hàng</th>
                                <th>Phòng/Loại phòng</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th class="text-end">Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Thanh toán</th>
                                <th>Ngày đặt</th>
                                <th class="text-center" style="width: 150px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($bookings) > 0): ?>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td>#<?= $booking['id'] ?></td>
                                        <td><?= htmlspecialchars($booking['username'] ?? 'Khách lẻ') ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($booking['room_number'] ?? 'N/A') ?></strong> 
                                            <br><small class="text-muted"><?= htmlspecialchars($booking['type_name'] ?? 'N/A') ?></small>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($booking['check_in'])) ?></td>
                                        <td><?= date('d/m/Y', strtotime($booking['check_out'])) ?></td>
                                        <td class="text-end">
                                            <strong><?= $booking['total_amount'] ? number_format($booking['total_amount']) . " VNĐ" : "—" ?></strong>
                                        </td>
                                        <td><?= get_status_badge($booking['status']) ?></td>
                                        <td><span class="badge bg-secondary"><?= strtoupper($booking['payment_method']) ?></span></td>
                                        <td><?= date('d/m/Y', strtotime($booking['created_at'])) ?></td>
                                        
                                        <td class="text-center">
                                            <div class="d-flex flex-wrap justify-content-center gap-1"> 
                                                
                                                <a href="detail.php?id=<?= $booking['id'] ?>" class="btn btn-sm btn-info text-white" title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                <?php if ($booking['status'] == 'pending'): ?>
                                                    <a href="approve.php?id=<?= $booking['id'] ?>" 
                                                       class="btn btn-sm btn-success" 
                                                       title="Xác nhận Booking"
                                                       onclick="return confirm('Xác nhận Booking #<?= $booking['id'] ?>?');">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <?php if (!in_array($booking['status'], ['cancelled', 'checked_out'])): ?>
                                                    <a href="cancel.php?id=<?= $booking['id'] ?>" 
                                                       class="btn btn-sm btn-warning text-dark" 
                                                       title="Hủy Booking"
                                                       onclick="return confirm('Bạn có muốn HỦY Booking #<?= $booking['id'] ?> không?');">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="edit.php?id=<?= $booking['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Sửa thông tin">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <a href="delete.php?id=<?= $booking['id'] ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   title="Xóa vĩnh viễn Booking"
                                                   onclick="return confirm('CẢNH BÁO: XÓA VĨNH VIỄN Booking #<?= $booking['id'] ?>? Thao tác này KHÔNG thể hoàn tác!');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                        </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">Không tìm thấy đơn đặt phòng nào phù hợp với điều kiện lọc.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center mb-0">
                            <?php
                            $params = $_GET;

                            if ($page > 1) {
                                $params['page'] = $page - 1;
                                echo '<li class="page-item"><a class="page-link" href="?'.http_build_query($params).'" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>';
                            }

                            for ($i=1; $i <= $total_pages; $i++) {
                                $active = ($i == $page) ? "active" : "";
                                $params['page'] = $i;
                                echo "<li class='page-item $active'><a class='page-link' href='?".http_build_query($params)."'>$i</a></li>";
                            }

                            if ($page < $total_pages) {
                                $params['page'] = $page + 1;
                                echo '<li class="page-item"><a class="page-link" href="?'.http_build_query($params).'" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>';
                            }
                            ?>
                        </ul>
                    </nav>
                <?php endif; ?>

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
        
        // Xử lý lớp phủ nền tối (Backdrop)
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