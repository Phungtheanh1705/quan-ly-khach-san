<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); 
    exit();
}
include "../../config/db.php"; 

$rooms = [];
$room_types = [];
$where = [];

// Cấu hình phân trang
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// --- XỬ LÝ LỌC TÌM KIẾM ---
if (!empty($_GET['room_number'])) {
    $room_number = $conn->real_escape_string($_GET['room_number']);
    $where[] = "r.room_number LIKE '%$room_number%'";
}
if (!empty($_GET['type_id'])) {
    $type_id = (int)$_GET['type_id'];
    $where[] = "r.type_id = $type_id";
}
$filter_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// --- Lấy danh sách Loại phòng cho bộ lọc ---
$type_query = "SELECT id, type_name FROM room_types ORDER BY type_name ASC";
$type_result = $conn->query($type_query);
while ($row = $type_result->fetch_assoc()) {
    $room_types[] = $row;
}

// 1. Đếm tổng số bản ghi (cho phân trang)
$count_sql = "SELECT COUNT(r.id) AS total FROM rooms r JOIN room_types rt ON r.type_id = rt.id $filter_sql";
$count_result = $conn->query($count_sql);
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// 2. Truy vấn lấy danh sách phòng có giới hạn (cho phân trang)
$sql = "
    SELECT 
        r.id, 
        r.room_number, 
        r.status, 
        r.type_id, 
        rt.type_name,
        rt.price_per_night,
        rt.image_path  
    FROM rooms r
    JOIN room_types rt ON r.type_id = rt.id
    $filter_sql
    ORDER BY r.room_number ASC
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
}

// Kiểm tra thông báo từ session
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
    <title>Quản lý Phòng - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        /* CSS cho hiệu ứng Fade */
        .initial-hidden { opacity: 0; }
        /* Tăng transition duration lên 0.5s để mượt hơn */
        .content-area-main { 
            opacity: 0; 
            transition: opacity 0.5s ease-in-out; 
        }
        .content-area-main.fade-in { 
            opacity: 1; 
        }
        /* Style cho backdrop khi sidebar mở trên mobile */
        body.sidebar-open::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040; 
        }
        .room-img { width: 50px; height: 35px; object-fit: cover; border-radius: 4px; }
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
            <a href="../index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded nav-link-fade">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="../bookings/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded nav-link-fade">
                <i class="fas fa-book me-2"></i> Quản lý Booking
            </a>
        </li>
        <li class="active"> 
            <a href="index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded bg-primary">
                <i class="fas fa-bed me-2"></i> Quản lý Phòng
            </a>
        </li>
        <li>
            <a href="../users/index.php" class="d-block py-2 px-3 text-white text-decoration-none rounded nav-link-fade">
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
                 <li><a href="../reports/monthly.php" class="d-block py-2 ps-5 text-white-50 text-decoration-none nav-link-fade">Doanh thu tháng</a></li>
                 <li><a href="../reports/yearly.php" class="d-block py-2 ps-5 text-white-50 text-decoration-none nav-link-fade">Tổng quan năm</a></li>
             </ul>
        </li>
    </ul>

    <div class="bottom-logout p-3">
        <hr class="text-secondary">
        <a href="../logout.php" class="btn btn-outline-danger w-100 nav-link-fade">
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
            
            <a class="navbar-brand text-primary fw-bold nav-link-fade" href="../index.php">
                <i class="fas fa-bed me-1"></i> Trang chủ

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
                            <li><a class="dropdown-item text-danger nav-link-fade" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Đăng xuất</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="content container-fluid mt-4">
        <h1 class="mb-4"><i class="fas fa-bed me-2 text-primary"></i> Danh sách Phòng</h1>

        <?php if ($message): ?>
            <div class="alert alert-<?= $msg_type; ?> alert-dismissible fade show" role="alert">
                <?= $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="filter-form-wrapper shadow-sm p-3 mb-4 bg-white rounded">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-8">
                    <form class="row g-2 align-items-end" method="get">
                        <div class="col-12 col-md-auto">
                            <label>Số phòng</label>
                            <input type="text" name="room_number" class="form-control form-control-sm" value="<?= $_GET['room_number'] ?? '' ?>" placeholder="Ví dụ: 101, 205...">
                        </div>
        
                        <div class="col-12 col-md-auto">
                            <label>Loại phòng</label>
                            <select name="type_id" class="form-select form-select-sm">
                                <option value="">Tất cả</option>
                                <?php foreach ($room_types as $type): ?>
                                    <?php $selected = (isset($_GET['type_id']) && (int)$_GET['type_id'] == $type['id']) ? 'selected' : ''; ?>
                                    <option value="<?= $type['id'] ?>" <?= $selected ?>><?= htmlspecialchars($type['type_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-12 col-md-auto">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i> Lọc</button>
                            <?php if (count($where) > 0 || $page > 1): ?>
                                <a href="index.php" class="btn btn-secondary btn-sm"><i class="fas fa-times me-1"></i> Xóa lọc</a>
                            <?php endif; ?>
                        </div>
                        <?php if (isset($_GET['room_number'])): ?>
                            <input type="hidden" name="room_number" value="<?= htmlspecialchars($_GET['room_number']) ?>">
                        <?php endif; ?>
                        <?php if (isset($_GET['type_id'])): ?>
                            <input type="hidden" name="type_id" value="<?= htmlspecialchars($_GET['type_id']) ?>">
                        <?php endif; ?>
                    </form>
                </div>

                <div class="col-12 col-lg-4 text-end d-flex justify-content-end gap-2">
                    <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#importCSVModal">
                        <i class="fas fa-file-csv me-1"></i> Thêm nhiều từ CSV
                    </button>
                    <a href="add.php" class="btn btn-success btn-sm nav-link-fade">
                        <i class="fas fa-plus me-1"></i> Thêm Phòng Mới
                    </a>
                </div>
            </div>
        </div>

        <div class="card shadow-lg mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Ảnh</th>
                                <th>ID</th>
                                <th>Số Phòng</th>
                                <th>Loại Phòng</th>
                                <th>ID Loại</th>
                                <th>Giá/Đêm</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rooms)): ?>
                                <tr><td colspan="8" class="text-center">Chưa có phòng nào được tạo hoặc không tìm thấy kết quả.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rooms as $room): ?>
                                    <tr>
                                        <td>
                                            <img src="<?= htmlspecialchars($room['image_path'] ?? '../../assets/images/placeholder.jpg') ?>" class="room-img" alt="Ảnh phòng">
                                        </td>
                                        <td><?= $room['id'] ?></td>
                                        <td>**<?= $room['room_number'] ?>**</td>
                                        <td><?= htmlspecialchars($room['type_name']) ?></td>
                                        <td><span class="badge bg-dark"><?= $room['type_id'] ?></span></td>
                                        <td><?= number_format($room['price_per_night']) ?> VNĐ</td>
                                        <td>
                                            <?php 
                                                $status_class = match ($room['status']) {
                                                    'available' => 'success',
                                                    'occupied' => 'danger',
                                                    'cleaning' => 'warning',
                                                    'maintenance' => 'secondary',
                                                    default => 'info',
                                                };
                                            ?>
                                            <span class="badge bg-<?= $status_class ?>">
                                                <?= ucfirst($room['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view.php?id=<?= $room['id'] ?>" class="btn btn-sm btn-info me-1 nav-link-fade" title="Xem chi tiết"><i class="fas fa-eye"></i></a>
                                            <a href="edit.php?id=<?= $room['id'] ?>" class="btn btn-sm btn-primary me-1 nav-link-fade" title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
                                            <a href="delete.php?id=<?= $room['id'] ?>" class="btn btn-sm btn-danger" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa phòng <?= $room['room_number'] ?>? Việc này không thể hoàn tác.')"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav aria-label="Phân trang danh sách phòng">
                <ul class="pagination justify-content-center">
                    <?php 
                        $query_params = $_GET;
                        $query_string = http_build_query($query_params);

                        // Hàm tạo link
                        function create_pagination_link($p, $current_query_params) {
                            $current_query_params['page'] = $p;
                            return '?' . http_build_query($current_query_params);
                        }
                    ?>

                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link nav-link-fade" href="<?= create_pagination_link($page - 1, $query_params) ?>" tabindex="-1">Trang trước</a>
                    </li>

                    <?php 
                    // Hiển thị tối đa 5 nút trang
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    if ($start_page > 1) {
                        echo '<li class="page-item"><a class="page-link nav-link-fade" href="' . create_pagination_link(1, $query_params) . '">1</a></li>';
                        if ($start_page > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }

                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link nav-link-fade" href="<?= create_pagination_link($i, $query_params) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; 

                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link nav-link-fade" href="' . create_pagination_link($total_pages, $query_params) . '">' . $total_pages . '</a></li>';
                    }
                    ?>
                    
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link nav-link-fade" href="<?= create_pagination_link($page + 1, $query_params) ?>">Trang kế</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
        </div>

</div> 
</div> 

<div class="modal fade" id="importCSVModal" tabindex="-1" aria-labelledby="importCSVModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title" id="importCSVModalLabel"><i class="fas fa-file-csv me-2"></i> Nhập Phòng từ CSV</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="process_csv_import.php" method="POST" enctype="multipart/form-data">
        <div class="modal-body">
            <div class="alert alert-info">
                Vui lòng đảm bảo file CSV có định dạng: **room_number,type_id,status**. Ví dụ: `101,1,available`.
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const contentArea = document.querySelector('.content-area-main');

    // 1. KÍCH HOẠT HIỆU ỨNG FADE-IN KHI TẢI TRANG
    if (contentArea) {
        // Tăng thời gian chờ lên 500ms (0.5s) để khớp với CSS transition
        setTimeout(() => {
            contentArea.classList.remove('initial-hidden');
            contentArea.classList.add('fade-in');
        }, 10);
    }
    
    // 2. XỬ LÝ FADE-OUT KHI NHẤN VÀO LINK (HIỆU ỨNG CHUYỂN TRANG)
    // Áp dụng cho class 'nav-link-fade' và các link phân trang 'page-link'
    document.querySelectorAll('a.nav-link-fade, .pagination .page-link').forEach(link => {
        // Loại trừ các link có class 'disabled' (link phân trang bị vô hiệu hóa)
        if (!link.closest('.page-item.disabled')) {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                
                // Chỉ áp dụng cho các link nội bộ và không phải là anchor link
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
                    }, 500); // 500ms = 0.5 giây
                }
            });
        }
    });

    // 3. LOGIC XỬ LÝ SIDEBAR (GIỮ NGUYÊN ĐỒNG BỘ)
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