<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); 
    exit();
}
include "../../config/db.php"; 

// ====================================================================
// LOGIC XỬ LÝ DỮ LIỆU USERS
// ====================================================================
$search_term = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clauses = [];
$params = [];
$types = '';

// Thêm điều kiện tìm kiếm theo username/full_name/email
if ($search_term) {
    $search_like = '%' . $search_term . '%';
    $where_clauses[] = "(username LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $params = array_merge($params, [$search_like, $search_like, $search_like]);
    $types .= 'sss';
}

// Thêm điều kiện lọc theo role
if ($role_filter && in_array($role_filter, ['admin', 'user'])) { 
    $where_clauses[] = "role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

$filter_sql = count($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";

// --- HÀM HỖ TRỢ BIND THAM SỐ DÙNG THAM CHIẾU ---
function bind_params_reference($stmt, $param_types, $params) {
    $bind_names = [$param_types];
    for ($i = 0; $i < count($params); $i++) {
        $bind_names[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}
// -----------------------------------------------------------------


// 1. Lấy tổng số lượng kết quả sau khi lọc (Dòng 66)
$sql_count = "SELECT COUNT(id) AS total FROM users $filter_sql";
$total_records = 0;
if ($stmt_count = $conn->prepare($sql_count)) {
    if (count($params) > 0) {
        $count_types = substr($types, 0, strlen($types)); 
        bind_params_reference($stmt_count, $count_types, $params);
    }
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $total_records = $result_count->fetch_assoc()['total'];
    $stmt_count->close();
}
$total_pages = ceil($total_records / $limit);


// 2. Lấy dữ liệu Users
$sql_data = "SELECT id, username, email, full_name, phone, address, role, created_at, is_active FROM users $filter_sql ORDER BY created_at DESC LIMIT ? OFFSET ?";
$users = [];
if ($stmt_data = $conn->prepare($sql_data)) {
    
    $final_params = $params;
    $final_params[] = $limit; 
    $final_params[] = $offset; 
    $final_param_types = $types . "ii"; 

    bind_params_reference($stmt_data, $final_param_types, $final_params);

    $stmt_data->execute();
    $result_data = $stmt_data->get_result();
    while ($row = $result_data->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt_data->close();
}

$conn->close();

// Lấy thông báo flash
$message = $_SESSION['message'] ?? null;
$msg_type = $_SESSION['msg_type'] ?? 'success';
unset($_SESSION['message']);
unset($_SESSION['msg_type']);

// Hàm hiển thị role và trạng thái đẹp hơn
function get_role_badge($role) {
    if ($role == 'admin') {
        return '<span class="badge bg-primary rounded-pill"><i class="fas fa-crown me-1"></i> Admin</span>';
    }
    return '<span class="badge bg-info text-dark rounded-pill"><i class="fas fa-user me-1"></i> User</span>';
}

function get_status_badge($is_active) {
    if ($is_active == 1) {
        return '<span class="badge bg-success"><i class="fas fa-check"></i> Active</span>';
    }
    return '<span class="badge bg-secondary"><i class="fas fa-lock"></i> Inactive</span>';
}
// ====================================================================
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Người dùng - QLKS Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <link rel="stylesheet" href="../../assets/css/admin.css"> 
    
    <style>
        /* CSS CHẮC CHẮN GHI ĐÈ ĐỂ ĐỒNG BỘ HIỆU ỨNG VÀ KÍCH THƯỚC ICON */
        .initial-hidden { opacity: 0; transition: opacity 0.5s ease; }
        .fade-in { opacity: 1; }
        .content-area-main { transition: opacity 0.5s ease-in-out; }
        
        .main-wrapper { display: flex; min-height: 100vh; }
        
        /* Sidebar Styling: Quan trọng để FIX LỆCH ĐĂNG XUẤT */
        #sidebar {
            width: 250px;
            background-color: #212529; 
            position: sticky;
            top: 0;
            height: 100vh; 
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
        }
        #sidebar .list-unstyled.components {
            flex-grow: 1; 
            overflow-y: auto;
            padding-bottom: 1rem;
        }
        .bottom-logout {
            margin-top: auto; 
            width: 100%;
            padding: 1rem;
        }

        /* Tối ưu hóa nút thao tác (giống như Booking) */
        .table-responsive td .btn-sm {
            padding: 0.1rem 0.25rem !important; 
            line-height: 1; 
            font-size: 0.75rem !important; 
        }
        .table-responsive td:last-child {
            white-space: nowrap; 
        }
        
        .nav-link-fade { transition: all 0.3s ease; }

        /* Tùy chỉnh Table để dễ đọc hơn */
        .table thead th {
            background-color: #e9ecef;
            color: #495057;
            font-weight: 600;
            vertical-align: middle;
            font-size: 0.9rem; 
        }
        .table td {
             font-size: 0.9rem; 
        }
        
        /* Form Lọc */
        .filter-form {
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        .filter-form .form-control, .filter-form .form-select {
            font-size: 0.85rem;
        }
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
        <li>
            <a href="../index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded nav-link-fade">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="../bookings/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded nav-link-fade"> 
                <i class="fas fa-book me-2"></i> Quản lý Booking
            </a>
        </li>
        <li>
            <a href="../rooms/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded nav-link-fade">
                <i class="fas fa-bed me-2"></i> Quản lý Phòng
            </a>
        </li>
        <li class="active">
            <a href="index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded bg-primary">
                <i class="fas fa-users me-2"></i> Quản lý Users
            </a>
        </li>
        <li>
            <a href="../payments/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded nav-link-fade">
                <i class="fas fa-credit-card me-2"></i> Thanh toán
            </a>
        </li>
        <li>
            <a href="#reportSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle d-block py-2 px-3 text-white text-decoration-none rounded">
                <i class="fas fa-chart-line me-2"></i> Báo cáo & Thống kê
            </a>
            <ul class="collapse list-unstyled" id="reportSubmenu">
                <li>
                    <a href="../reports/monthly.php" class="d-block py-2 ps-5 text-white-50 text-decoration-none nav-link-fade">Doanh thu tháng</a>
                </li>
                <li>
                    <a href="../reports/yearly.php" class="d-block py-2 ps-5 text-white-50 text-decoration-none nav-link-fade">Tổng quan năm</a>
                </li>
            </ul>
        </li>
    </ul>

    <div class="bottom-logout p-3" style="width: 100%;">
        <hr class="text-secondary">
        <a href="../logout.php" class="btn btn-outline-danger w-100 nav-link-fade">
            <i class="fas fa-sign-out-alt me-2"></i> Đăng xuất
        </a>
    </div>
</nav>

<div class="page-content-wrapper flex-grow-1 content-area-main initial-hidden">
    
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top p-3">
        <div class="container-fluid">
            <button class="btn btn-outline-secondary d-inline-block d-lg-none me-2" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
            
            <a class="navbar-brand text-primary fw-bold nav-link-fade" href="../index.php">
                <i class="fas fa-users me-1"></i> Quản lý Users
            </a>

            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="me-2 d-none d-sm-inline">Xin chào, Admin! (<?php echo $_SESSION['admin_id'] ?? 'Guest'; ?>)</span>
                            <i class="fas fa-user-circle fa-lg"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item nav-link-fade" href="../profile.php"><i class="fas fa-user-cog me-2"></i> Hồ sơ</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger nav-link-fade" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Đăng xuất
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content container-fluid">
        <h1 class="mb-4"><i class="fas fa-users me-2 text-primary"></i> Quản lý Người dùng</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?= $msg_type; ?> alert-dismissible fade show mt-3" role="alert">
                <?= $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Danh sách Người dùng (<?= $total_records ?>)</h5>
                
                <div class="d-flex gap-2">
                    <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#importCSVModal">
                        <i class="fas fa-file-csv me-1"></i> Import CSV
                    </button>
                    <a href="add.php" class="btn btn-primary btn-sm nav-link-fade">
                        <i class="fas fa-user-plus me-1"></i> Thêm Mới
                    </a>
                </div>
            </div>
            <div class="card-body">
                
                <div class="filter-form mb-4">
                    <form method="GET" action="index.php" class="row g-3 align-items-end">
                        
                        <div class="col-md-5">
                            <label for="search" class="form-label">Tìm kiếm</label>
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Tên, Email, Username..." value="<?= htmlspecialchars($search_term) ?>">
                        </div>

                        <div class="col-md-3">
                            <label for="role" class="form-label">Vai trò</label>
                            <select name="role" class="form-select form-select-sm">
                                <option value="">Tất cả</option>
                                <option value="admin" <?= ($role_filter == 'admin') ? 'selected' : '' ?>>Admin</option>
                                <option value="user" <?= ($role_filter == 'user') ? 'selected' : '' ?>>User</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 d-flex">
                            <button type="submit" class="btn btn-info btn-sm me-2"><i class="fas fa-filter me-1"></i> Lọc</button>
                            <?php 
                            $is_filtered = !empty($search_term) || !empty($role_filter) || ($page > 1);
                            if ($is_filtered): ?>
                            <a href="index.php" class="btn btn-secondary btn-sm"><i class="fas fa-sync-alt me-1"></i> Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>


                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 5%;">ID</th>
                                <th style="width: 15%;">Username</th>
                                <th style="width: 15%;">Họ & Tên</th>
                                <th style="width: 25%;">Email / SĐT</th>
                                <th style="width: 10%;">Vai trò</th>
                                <th style="width: 10%;">Trạng thái</th>
                                <th style="width: 10%;">Ngày tạo</th>
                                <th class="text-center" style="width: 10%;">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($users) > 0): ?>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['id']) ?></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['full_name'] ?: 'N/A') ?></td>
                                    <td>
                                        <i class="fas fa-envelope text-primary me-1"></i> <?= htmlspecialchars($user['email'] ?: 'N/A') ?><br>
                                        <i class="fas fa-phone-alt text-success me-1"></i> <?= htmlspecialchars($user['phone'] ?: 'N/A') ?>
                                    </td>
                                    <td><?= get_role_badge($user['role']) ?></td>
                                    <td><?= get_status_badge($user['is_active']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                    
                                    <td class="action-btns">
                                        <div class="d-flex justify-content-center gap-1"> 
                                            <a href="view.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-info nav-link-fade" title="Xem chi tiết"><i class="fas fa-eye"></i></a>
                                            
                                            <a href="edit.php?id=<?= $user['id'] ?>" class="btn btn-primary btn-sm nav-link-fade" title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
                                            
                                            <form action="delete.php" method="POST" class="d-inline" onsubmit="return confirm('CẢNH BÁO: XÓA VĨNH VIỄN người dùng <?= $user['username'] ?>?');">
                                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" title="Xóa vĩnh viễn"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center p-4 text-muted">Không tìm thấy người dùng nào phù hợp với điều kiện tìm kiếm.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="card-footer bg-white">
                        <nav>
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link nav-link-fade" href="?page=<?= $page - 1 ?>&search=<?= htmlspecialchars($search_term) ?>&role=<?= htmlspecialchars($role_filter) ?>">Trước</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                        <a class="page-link nav-link-fade" href="?page=<?= $i ?>&search=<?= htmlspecialchars($search_term) ?>&role=<?= htmlspecialchars($role_filter) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                    <a class="page-link nav-link-fade" href="?page=<?= $page + 1 ?>&search=<?= htmlspecialchars($search_term) ?>&role=<?= htmlspecialchars($role_filter) ?>">Sau</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div> 

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<div class="modal fade" id="importCSVModal" tabindex="-1" aria-labelledby="importCSVModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title" id="importCSVModalLabel"><i class="fas fa-file-csv me-2"></i> Nhập Users từ CSV</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="process_csv_import.php" method="POST" enctype="multipart/form-data">
        <div class="modal-body">
            <div class="alert alert-info">
                Vui lòng đảm bảo file CSV có định dạng: **username,email,full_name,password,role**.
            </div>
            <div class="mb-3">
                <label for="csvFile" class="form-label">Chọn File CSV</label>
                <input class="form-control" type="file" id="csvFile" name="csv_file" required accept=".csv">
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-success"><i class="fas fa-upload me-1"></i> Tải lên và Thêm</button>
        </div>
      </form>
    </div>
  </div>
</div>
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