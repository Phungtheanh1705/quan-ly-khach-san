<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); 
    exit();
}
include "../../config/db.php"; 

// ====================================================================
// LOGIC XỬ LÝ DỮ LIỆU THANH TOÁN
// ====================================================================
$limit = 10; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search_term = $_GET['search'] ?? '';
$method_filter = $_GET['method'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where_clauses = [];
$params = [];
$types = '';

// 1. Tìm kiếm
if ($search_term) {
    $search_like = '%' . $search_term . '%';
    $where_clauses[] = "(u.username LIKE ? OR p.transaction_id LIKE ?)";
    $params = array_merge($params, [$search_like, $search_like]);
    $types .= 'ss';
}

// 2. Lọc Phương thức
if ($method_filter && in_array($method_filter, ['cash', 'banking', 'creditcard'])) { 
    $where_clauses[] = "p.payment_method = ?";
    $params[] = $method_filter;
    $types .= 's';
}

// 3. Lọc Trạng thái (Đã thêm 'refunded')
if ($status_filter && in_array($status_filter, ['pending', 'completed', 'failed', 'refunded'])) { 
    $where_clauses[] = "p.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// 4. Lọc Ngày từ
if ($date_from) {
    $where_clauses[] = "p.payment_date >= ?";
    $params[] = $date_from;
    $types .= 's';
}

// 5. Lọc Ngày đến
if ($date_to) {
    $date_to_inclusive = date('Y-m-d', strtotime($date_to . ' +1 day'));
    $where_clauses[] = "p.payment_date < ?";
    $params[] = $date_to_inclusive;
    $types .= 's';
}

$filter_sql = count($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";

function bind_params_reference($stmt, $param_types, $params) {
    $bind_names = [$param_types];
    for ($i = 0; $i < count($params); $i++) {
        $bind_names[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}

// Lấy tổng số lượng
$sql_count = "
    SELECT COUNT(p.id) AS total FROM payments p 
    LEFT JOIN bookings b ON p.booking_id = b.id 
    LEFT JOIN users u ON b.user_id = u.id
    $filter_sql
";
$total_records = 0;
if ($stmt_count = $conn->prepare($sql_count)) {
    if (count($params) > 0) {
        $count_types = substr($types, 0, strlen($types)); 
        bind_params_reference($stmt_count, $count_types, $params);
    }
    $stmt_count->execute();
    $total_records = $stmt_count->get_result()->fetch_assoc()['total'];
    $stmt_count->close();
}
$total_pages = ceil($total_records / $limit);

// Lấy dữ liệu
$sql_data = "
    SELECT 
        p.id AS payment_id, p.booking_id, p.amount, p.payment_method, p.payment_date, p.status, p.transaction_id,
        u.username
    FROM payments p
    LEFT JOIN bookings b ON p.booking_id = b.id
    LEFT JOIN users u ON b.user_id = u.id
    $filter_sql
    ORDER BY p.payment_date DESC LIMIT ? OFFSET ?
";
$payments = [];
if ($stmt_data = $conn->prepare($sql_data)) {
    $final_params = $params;
    $final_params[] = $limit; 
    $final_params[] = $offset; 
    $final_param_types = $types . "ii"; 

    bind_params_reference($stmt_data, $final_param_types, $final_params);
    $stmt_data->execute();
    $result_data = $stmt_data->get_result();
    while ($row = $result_data->fetch_assoc()) {
        $payments[] = $row;
    }
    $stmt_data->close();
}
$conn->close();

$message = $_SESSION['message'] ?? null;
$msg_type = $_SESSION['msg_type'] ?? 'success';
unset($_SESSION['message']);
unset($_SESSION['msg_type']);

// Hàm hiển thị trạng thái thanh toán (Đã thêm case 'refunded')
function get_payment_status_badge($status) {
    switch ($status) {
        case 'completed': return '<span class="badge bg-success"><i class="fas fa-check"></i> Hoàn tất</span>';
        case 'pending': return '<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Đang chờ</span>';
        case 'failed': return '<span class="badge bg-danger"><i class="fas fa-times"></i> Thất bại</span>';
        case 'refunded': return '<span class="badge bg-info text-dark"><i class="fas fa-undo"></i> Đã hoàn tiền</span>';
        default: return '<span class="badge bg-secondary">N/A</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Thanh toán - QLKS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css"> 
    <style>
        .initial-hidden { opacity: 0; transition: opacity 0.5s ease; }
        .fade-in { opacity: 1; }
        .content-area-main { transition: opacity 0.5s ease-in-out; }
        .main-wrapper { display: flex; min-height: 100vh; }
        #sidebar { width: 250px; background-color: #212529; position: sticky; top: 0; height: 100vh; flex-shrink: 0; display: flex; flex-direction: column; }
        #sidebar .list-unstyled.components { flex-grow: 1; overflow-y: auto; padding-bottom: 1rem; }
        .bottom-logout { margin-top: auto; width: 100%; padding: 1rem; }
        
        /* Nút hành động nhỏ gọn */
        .table-responsive td .btn-sm { padding: 0.25rem 0.5rem !important; font-size: 0.8rem !important; }
        
        .nav-link-fade { transition: all 0.3s ease; }
        .table thead th { background-color: #e9ecef; color: #495057; font-weight: 600; vertical-align: middle; font-size: 0.9rem; }
        .filter-form { border-radius: 12px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); padding: 20px; margin-bottom: 20px; background-color: #f8f9fa; }
    </style>
</head>
<body>

<div class="main-wrapper">
    <nav id="sidebar" class="bg-dark text-white p-3 shadow-lg collapse d-lg-block">
        <div class="sidebar-header text-center mb-4">
            <h3 class="text-white"><i class="fas fa-hotel me-2"></i> QLKS Admin</h3>
            <hr class="text-secondary">
        </div>
        <ul class="list-unstyled components">
            <li><a href="../index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded nav-link-fade"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
            <li><a href="../bookings/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded nav-link-fade"><i class="fas fa-book me-2"></i> Quản lý Booking</a></li>
            <li><a href="../rooms/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded nav-link-fade"><i class="fas fa-bed me-2"></i> Quản lý Phòng</a></li>
            <li><a href="../users/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded nav-link-fade"><i class="fas fa-users me-2"></i> Quản lý Users</a></li>
            <li class="active"><a href="index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded bg-primary"><i class="fas fa-credit-card me-2"></i> Thanh toán</a></li>
            <li>
                <a href="#reportSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle d-block py-2 px-3 text-white text-decoration-none rounded"><i class="fas fa-chart-line me-2"></i> Báo cáo</a>
                <ul class="collapse list-unstyled" id="reportSubmenu">
                    <li><a href="../reports/monthly.php" class="d-block py-2 ps-5 text-white-50 text-decoration-none nav-link-fade">Doanh thu tháng</a></li>
                    <li><a href="../reports/yearly.php" class="d-block py-2 ps-5 text-white-50 text-decoration-none nav-link-fade">Tổng quan năm</a></li>
                </ul>
            </li>
        </ul>
        <div class="bottom-logout p-3">
            <hr class="text-secondary">
            <a href="../logout.php" class="btn btn-outline-danger w-100 nav-link-fade"><i class="fas fa-sign-out-alt me-2"></i> Đăng xuất</a>
        </div>
    </nav>

    <div class="page-content-wrapper flex-grow-1 content-area-main initial-hidden">
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top p-3">
            <div class="container-fluid">
                <button class="btn btn-outline-secondary d-inline-block d-lg-none me-2" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar"><i class="fas fa-bars"></i></button>
                <a class="navbar-brand text-primary fw-bold nav-link-fade" href="../index.php"><i class="fas fa-credit-card me-1"></i> Quản lý Thanh toán</a>
                <div class="collapse navbar-collapse justify-content-end">
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <span class="me-2 d-none d-sm-inline">Xin chào, Admin! (<?php echo $_SESSION['admin_id'] ?? 'Guest'; ?>)</span>
                                <i class="fas fa-user-circle fa-lg"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item nav-link-fade" href="../profile.php"><i class="fas fa-user-cog me-2"></i> Hồ sơ</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger nav-link-fade" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Đăng xuất</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="content container-fluid p-4">
            <h2 class="mb-4 text-uppercase fw-bold"><i class="fas fa-list me-2 text-primary"></i> Danh sách Thanh toán</h2>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $msg_type; ?> alert-dismissible fade show mt-3" role="alert">
                    <?= $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-bold text-secondary">Tổng số giao dịch: <span class="text-dark"><?= $total_records ?></span></h5>
                </div>
                <div class="card-body">
                    
                    <div class="filter-form mb-4">
                        <form method="GET" action="index.php" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label fw-bold small">Tìm kiếm</label>
                                <input type="text" name="search" class="form-control form-control-sm" placeholder="Mã GD, Username..." value="<?= htmlspecialchars($search_term) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold small">Phương thức</label>
                                <select name="method" class="form-select form-select-sm">
                                    <option value="">-- Tất cả --</option>
                                    <option value="cash" <?= ($method_filter == 'cash') ? 'selected' : '' ?>>Tiền mặt</option>
                                    <option value="banking" <?= ($method_filter == 'banking') ? 'selected' : '' ?>>Chuyển khoản</option>
                                    <option value="creditcard" <?= ($method_filter == 'creditcard') ? 'selected' : '' ?>>Thẻ</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold small">Trạng thái</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">-- Tất cả --</option>
                                    <option value="completed" <?= ($status_filter == 'completed') ? 'selected' : '' ?>>Hoàn tất</option>
                                    <option value="pending" <?= ($status_filter == 'pending') ? 'selected' : '' ?>>Đang chờ</option>
                                    <option value="failed" <?= ($status_filter == 'failed') ? 'selected' : '' ?>>Thất bại</option>
                                    <option value="refunded" <?= ($status_filter == 'refunded') ? 'selected' : '' ?>>Đã hoàn tiền</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold small">Từ ngày</label>
                                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($date_from) ?>">
                            </div>
                             <div class="col-md-2">
                                <label class="form-label fw-bold small">Đến ngày</label>
                                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($date_to) ?>">
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter"></i> Lọc</button>
                            </div>
                        </form>
                        <?php if (!empty($search_term) || !empty($method_filter) || !empty($status_filter) || !empty($date_from) || !empty($date_to) || ($page > 1)): ?>
                            <div class="mt-2 text-end"><a href="index.php" class="btn btn-outline-secondary btn-sm py-0" style="font-size: 0.7rem;"><i class="fas fa-sync-alt me-1"></i> Reset</a></div>
                        <?php endif; ?>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;">ID</th>
                                    <th style="width: 15%;">Khách hàng</th>
                                    <th style="width: 15%;">Mã GD</th>
                                    <th style="width: 15%;" class="text-end">Số tiền</th>
                                    <th style="width: 10%;">PTTT</th>
                                    <th style="width: 10%;">Trạng thái</th>
                                    <th style="width: 15%;">Thời gian</th>
                                    <th class="text-center" style="width: 15%;">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($payments) > 0): ?>
                                    <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><span class="text-muted">#<?= htmlspecialchars($payment['payment_id']) ?></span></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($payment['username'] ?: 'Khách vãng lai') ?></div>
                                            <small class="text-muted">Booking: #<?= htmlspecialchars($payment['booking_id']) ?></small>
                                        </td>
                                        <td><code><?= htmlspecialchars($payment['transaction_id'] ?: '-') ?></code></td>
                                        <td class="text-end"><span class="fw-bold text-success"><?= number_format($payment['amount'] ?: 0) ?> đ</span></td>
                                        <td><span class="badge border text-dark bg-light"><?= ucfirst(htmlspecialchars($payment['payment_method'])) ?></span></td>
                                        
                                        <td><?= get_payment_status_badge($payment['status']) ?></td>
                                        
                                        <td><small><?= date('d/m/Y H:i', strtotime($payment['payment_date'])) ?></small></td>
                                        
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="detail.php?id=<?= $payment['payment_id'] ?>" class="btn btn-sm btn-primary nav-link-fade" title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if($payment['status'] !== 'refunded'): ?>
                                                    <a href="refund.php?id=<?= $payment['payment_id'] ?>" 
                                                       class="btn btn-sm btn-warning text-dark nav-link-fade" 
                                                       title="Hoàn tiền"
                                                       onclick="return confirm('Bạn có chắc muốn hoàn tiền giao dịch này? Trạng thái sẽ chuyển sang Đã hoàn tiền.');">
                                                        <i class="fas fa-undo"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary" disabled title="Đã hoàn tiền"><i class="fas fa-undo"></i></button>
                                                <?php endif; ?>

                                                <a href="delete.php?id=<?= $payment['payment_id'] ?>" 
                                                   class="btn btn-sm btn-danger nav-link-fade" 
                                                   title="Xóa giao dịch"
                                                   onclick="return confirm('Cảnh báo: Hành động này không thể khôi phục! Bạn có chắc chắn muốn xóa?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="text-center p-5 text-muted">Không tìm thấy giao dịch nào.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-center mt-4">
                            <nav>
                                <ul class="pagination mb-0">
                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link nav-link-fade" href="?page=<?= $page - 1 ?>&search=<?= htmlspecialchars($search_term) ?>&method=<?= htmlspecialchars($method_filter) ?>&status=<?= htmlspecialchars($status_filter) ?>"><i class="fas fa-chevron-left"></i></a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                            <a class="page-link nav-link-fade" href="?page=<?= $i ?>&search=<?= htmlspecialchars($search_term) ?>&method=<?= htmlspecialchars($method_filter) ?>&status=<?= htmlspecialchars($status_filter) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                        <a class="page-link nav-link-fade" href="?page=<?= $page + 1 ?>&search=<?= htmlspecialchars($search_term) ?>&method=<?= htmlspecialchars($method_filter) ?>&status=<?= htmlspecialchars($status_filter) ?>"><i class="fas fa-chevron-right"></i></a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
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
    const contentArea = document.querySelector('.content-area-main');

    if (contentArea) {
        setTimeout(() => {
            contentArea.classList.remove('initial-hidden');
            contentArea.classList.add('fade-in');
        }, 50); 
    }
    
    document.querySelectorAll('a.nav-link-fade, .pagination .page-link-fade').forEach(link => {
        if (!link.closest('.page-item.disabled')) { 
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href && !href.startsWith('#') && !href.startsWith('javascript:') && !link.onclick) {
                    e.preventDefault(); 
                    if (contentArea) {
                        contentArea.classList.remove('fade-in');
                        contentArea.classList.add('initial-hidden');
                    }
                    setTimeout(() => { window.location.href = href; }, 500); 
                }
            });
        }
    });

    if (sidebar) {
        document.addEventListener('click', function(event) {
            const isSidebarOpen = sidebar.classList.contains('show');
            const isMobileScreen = window.innerWidth < 992;
            const toggleButton = document.querySelector('[data-bs-target="#sidebar"]');
            if (isSidebarOpen && isMobileScreen && !sidebar.contains(event.target) && (!toggleButton || !toggleButton.contains(event.target))) {
                const sidebarCollapse = bootstrap.Collapse.getInstance(sidebar) || new bootstrap.Collapse(sidebar, { toggle: false });
                sidebarCollapse.hide();
            }
        });
    }
});
</script>
</body>
</html>