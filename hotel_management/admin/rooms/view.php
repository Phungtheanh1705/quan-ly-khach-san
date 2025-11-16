<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); 
    exit();
}
include "../../config/db.php"; 

$room_data = null;
$room_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?? 0;

if ($room_id <= 0) {
    $_SESSION['message'] = "ID phòng không hợp lệ.";
    $_SESSION['msg_type'] = "danger";
    header("Location: index.php");
    exit();
}

// Truy vấn lấy chi tiết phòng và thông tin loại phòng
$sql = "
    SELECT 
        r.id, 
        r.room_number, 
        r.status, 
        rt.type_name,
        rt.short_description,
        rt.full_description,
        rt.max_guests,
        rt.area_sqm,
        rt.price_per_night 
    FROM rooms r
    JOIN room_types rt ON r.type_id = rt.id
    WHERE r.id = ?
";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $room_data = $result->fetch_assoc();
    $stmt->close();
}

$conn->close();

if (!$room_data) {
    $_SESSION['message'] = "Không tìm thấy phòng có ID #{$room_id}.";
    $_SESSION['msg_type'] = "danger";
    header("Location: index.php");
    exit();
}

// Hàm chuyển đổi trạng thái tiếng Anh sang tiếng Việt để hiển thị đẹp hơn
function get_status_display($status) {
    switch ($status) {
        case 'available':
            return ['text' => 'Sẵn sàng', 'class' => 'success', 'icon' => 'fa-check-circle'];
        case 'occupied':
            return ['text' => 'Đang thuê', 'class' => 'danger', 'icon' => 'fa-door-closed'];
        case 'cleaning':
            return ['text' => 'Đang dọn dẹp', 'class' => 'warning', 'icon' => 'fa-broom'];
        case 'maintenance':
            return ['text' => 'Bảo trì', 'class' => 'secondary', 'icon' => 'fa-tools'];
        default:
            return ['text' => 'Không rõ', 'class' => 'info', 'icon' => 'fa-question-circle'];
    }
}
$status_info = get_status_display($room_data['status']);

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Phòng #<?= htmlspecialchars($room_data['room_number']) ?> - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    
    <style>
        /* BASE STYLES & LAYOUT */
        body {
            background-color: #f4f6f9; /* Nền sáng, chuyên nghiệp */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .main-container {
            width: 100%;
            max-width: 850px; 
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); 
            margin-bottom: 25px;
        }
        .card-header {
            background-color: #ffffff; 
            border-bottom: 1px solid #dee2e6; /* Đường kẻ nhẹ */
            color: #343a40; 
            padding: 15px 25px;
            font-size: 1.1rem;
            font-weight: 600;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
        .card-body {
            padding: 25px;
        }
        
        /* INFO ITEMS (Sử dụng cấu trúc tương tự Input Group để hiển thị chuyên nghiệp) */
        .info-display-group {
            display: flex;
            margin-bottom: 15px;
            align-items: center;
        }
        .info-prepend {
            padding: 10px 15px;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #495057;
            text-align: center;
            background-color: #e9ecef; /* Nền xám nhạt */
            border: 1px solid #ced4da;
            border-radius: 8px 0 0 8px;
            white-space: nowrap;
        }
        .info-field {
            padding: 10px 15px;
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.5;
            color: #212529;
            background-color: #ffffff;
            border: 1px solid #ced4da;
            border-left: none; /* Nối liền với info-prepend */
            border-radius: 0 8px 8px 0;
            flex-grow: 1;
            word-break: break-word;
        }
        .info-field.large {
            font-size: 1.25rem;
            font-weight: 700;
            color: #007bff;
        }
        
        /* BADGE & BUTTON STYLES */
        .status-badge {
            font-size: 0.95rem;
            padding: 0.5em 1em;
            border-radius: 6px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .btn-action-group {
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
            margin-top: 20px;
            text-align: center;
        }
        .btn-edit-custom {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
            font-weight: 600;
        }
        .btn-edit-custom:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 123, 255, 0.3);
        }

        /* FADE IN EFFECT */
        .initial-hidden { opacity: 0; }
        .fade-in-element { 
            opacity: 0; 
            transition: opacity 0.5s ease-in-out; 
        }
        .fade-in-element.is-visible { 
            opacity: 1; 
        }
    </style>
</head>
<body class="initial-hidden">

<div class="main-container fade-in-element">
    
    <nav class="navbar navbar-light bg-transparent mb-4">
        <div class="container-fluid p-0">
            <a class="navbar-brand text-muted nav-link-fade" href="index.php">
                <i class="fas fa-arrow-left me-1"></i> Quay lại QL Phòng
            </a>
            <a href="edit.php?id=<?= $room_data['id'] ?>" class="btn btn-edit-custom btn-sm nav-link-fade">
                <i class="fas fa-edit me-1"></i> Chỉnh sửa
            </a>
        </div>
    </nav>
    
    <h1 class="mb-5 text-center text-dark"><i class="fas fa-search me-2 text-primary"></i> Chi tiết Phòng #<span class="text-primary"><?= htmlspecialchars($room_data['room_number']) ?></span></h1>

    <div class="card">
        <div class="card-header bg-primary text-white" style="background-color: #007bff !important;">
            <i class="fas fa-bed me-2"></i> Thông tin cơ bản & Trạng thái
        </div>
        <div class="card-body">
            <div class="row">
                
                <div class="col-md-6 border-end">
                    
                    <label class="form-label text-muted fw-bold">Số Phòng & Loại Phòng</label>
                    <div class="info-display-group">
                        <span class="info-prepend"><i class="fas fa-hashtag"></i></span>
                        <span class="info-field large"><?= htmlspecialchars($room_data['room_number']) ?></span>
                    </div>

                    <div class="info-display-group">
                        <span class="info-prepend"><i class="fas fa-tag"></i> Loại phòng</span>
                        <span class="info-field"><?= htmlspecialchars($room_data['type_name']) ?></span>
                    </div>

                    <div class="info-display-group">
                        <span class="info-prepend"><i class="fas fa-id-badge"></i> ID Hệ thống</span>
                        <span class="info-field text-muted">#<?= htmlspecialchars($room_data['id']) ?></span>
                    </div>
                </div>

                <div class="col-md-6">

                    <label class="form-label text-muted fw-bold">Trạng thái hiện tại</label>
                    <div class="info-display-group">
                        <span class="info-prepend"><i class="fas <?= $status_info['icon'] ?>"></i></span>
                        <span class="info-field">
                            <span class="badge bg-<?= $status_info['class'] ?> status-badge">
                                <?= $status_info['text'] ?>
                            </span>
                        </span>
                    </div>

                    <label class="form-label text-muted fw-bold">Giá & Sức chứa</label>
                    <div class="info-display-group">
                        <span class="info-prepend"><i class="fas fa-money-bill-wave text-success"></i> Giá/Đêm</span>
                        <span class="info-field text-success fw-bold"><?= number_format($room_data['price_per_night'], 0, ',', '.') ?> VNĐ</span>
                    </div>
                    
                    <div class="info-display-group">
                        <span class="info-prepend"><i class="fas fa-users"></i> Khách tối đa</span>
                        <span class="info-field"><?= htmlspecialchars($room_data['max_guests']) ?> người</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <i class="fas fa-file-alt me-2"></i> Mô tả chi tiết Loại Phòng
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h5 class="text-secondary mb-3"><i class="fas fa-ruler-combined me-2"></i> Diện tích</h5>
                    <div class="alert alert-info bg-light-info border-info border-3 p-3 text-center">
                        <p class="mb-0 fs-3 fw-bold text-info"><?= htmlspecialchars($room_data['area_sqm']) ?> m²</p>
                    </div>
                </div>
                <div class="col-md-8">
                    <h5 class="text-secondary mb-3"><i class="fas fa-book-open me-2"></i> Mô tả ngắn</h5>
                    <div class="alert alert-light border p-3">
                        <p class="mb-0 text-dark"><?= htmlspecialchars($room_data['short_description']) ?></p>
                    </div>
                </div>
            </div>

            <h5 class="text-secondary mt-3 mb-3"><i class="fas fa-scroll me-2"></i> Mô tả chi tiết đầy đủ</h5>
            <div class="bg-light p-4 rounded border">
                <p class="text-dark mb-0"><?= nl2br(htmlspecialchars($room_data['full_description'])) ?></p>
            </div>
        </div>
    </div>
    
    <div class="btn-action-group">
        <a href="edit.php?id=<?= $room_data['id'] ?>" class="btn btn-edit-custom btn-lg me-3 nav-link-fade">
            <i class="fas fa-pencil-alt me-2"></i> Chỉnh sửa thông tin
        </a>
        <a href="index.php" class="btn btn-outline-secondary btn-lg nav-link-fade">
            <i class="fas fa-list me-2"></i> Quay lại Danh sách
        </a>
    </div>

</div> 

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fadeElement = document.querySelector('.fade-in-element');
    const bodyElement = document.body;

    // 1. KÍCH HOẠT HIỆU ỨNG FADE-IN KHI TẢI TRANG
    if (fadeElement) {
        setTimeout(() => {
            fadeElement.classList.add('is-visible');
            bodyElement.classList.remove('initial-hidden');
        }, 50); 
    }
    
    // 2. XỬ LÝ FADE-OUT KHI NHẤN VÀO LINK
    document.querySelectorAll('a.nav-link-fade').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
                e.preventDefault(); 
                
                // Kích hoạt hiệu ứng fade out
                if (fadeElement) {
                    fadeElement.classList.remove('is-visible');
                }
                
                // Chờ hiệu ứng fade out hoàn tất (0.5s) rồi mới chuyển hướng
                setTimeout(() => {
                    window.location.href = href;
                }, 500); 
            }
        });
    });
});
</script>

</body>
</html>