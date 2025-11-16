<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); 
    exit();
}
include "../../config/db.php"; 

$room_types = [];

// Lấy danh sách Loại phòng để chọn
$type_query = "SELECT id, type_name FROM room_types ORDER BY type_name ASC";
$type_result = $conn->query($type_query);
while ($row = $type_result->fetch_assoc()) {
    $room_types[] = $row;
}
$conn->close();

// Lấy thông báo và dữ liệu đã nhập nếu có
$message = $_SESSION['message'] ?? null;
$msg_type = $_SESSION['msg_type'] ?? 'danger';
unset($_SESSION['message']);
unset($_SESSION['msg_type']);

// Dữ liệu đã nhập (nếu có lỗi, để người dùng không phải nhập lại)
$room_number = $_SESSION['input_data']['room_number'] ?? '';
$type_id = $_SESSION['input_data']['type_id'] ?? '';
$status = $_SESSION['input_data']['status'] ?? 'available';
unset($_SESSION['input_data']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Phòng Mới - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa; /* Nền nhẹ nhàng */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* Đảm bảo chiều cao tối thiểu cho hiệu ứng căn giữa */
        }
        .main-container {
            width: 100%;
            max-width: 600px; /* Chiều rộng tối đa cho form */
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 15px; /* Bo tròn mạnh hơn */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); /* Đổ bóng mềm mại hơn */
            overflow: hidden; /* Đảm bảo bo tròn không bị cắt */
        }
        .card-header {
            background-color: #007bff; /* Màu chủ đạo */
            color: white;
            padding: 20px 30px;
            border-bottom: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .card-body {
            padding: 30px;
        }
        .form-label.required::after {
            content: " *";
            color: #dc3545;
            font-weight: normal; /* Không in đậm dấu sao */
        }
        .form-control, .form-select {
            border-radius: 8px; /* Bo tròn input */
            padding: 10px 15px;
            border: 1px solid #dee2e6;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }
        .btn-primary-custom {
            background: linear-gradient(45deg, #007bff, #0056b3); /* Gradient */
            border: none;
            border-radius: 8px;
            padding: 12px 25px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
        }
        .btn-primary-custom:hover {
            transform: translateY(-3px); /* Nhấc lên khi hover */
            box-shadow: 0 6px 15px rgba(0, 123, 255, 0.4);
            filter: brightness(1.1); /* Sáng hơn một chút */
        }
        .btn-outline-secondary-custom {
            border: 1px solid #6c757d;
            color: #6c757d;
            border-radius: 8px;
            padding: 12px 25px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        .btn-outline-secondary-custom:hover {
            background-color: #6c757d;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(108, 117, 125, 0.2);
        }

        /* Hiệu ứng Fade In cho toàn bộ nội dung */
        .initial-hidden { opacity: 0; }
        .fade-in-element { 
            opacity: 0; 
            transition: opacity 0.5s ease-in-out; 
        }
        .fade-in-element.is-visible { 
            opacity: 1; 
        }

        /* Input with icon placeholder */
        .form-control.with-icon {
            padding-left: 40px; /* Khoảng trống cho icon */
            background-repeat: no-repeat;
            background-position: 12px center;
            background-size: 18px 18px;
        }
        /* Các icon cụ thể cho input */
        #room_number.with-icon { background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="%236c757d"><path d="M64 112c-8.8 0-16 7.2-16 16v22.1L220.5 291.4c3.4 2.5 7.6 3.9 12.1 3.9c4.5 0 8.7-1.4 12.1-3.9L464 150.1V128c0-8.8-7.2-16-16-16H64zm400 64.9V384c0 8.8-7.2 16-16 16H64c-8.8 0-16-7.2-16-16V176.9L249.1 333.3c-2.4 1.8-5.2 2.7-8.1 2.7s-5.7-.9-8.1-2.7L48 176.9V384c0 8.8 7.2 16 16 16H448c8.8 0 16-7.2 16-16V176.9zM0 128C0 92.7 28.7 64 64 64H448c35.3 0 64 28.7 64 64V384c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V128z"/></svg>'); }
        #type_id.with-icon { background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" fill="%236c757d"><path d="M575.8 255.5c0 18-15 32.1-32 32.1h-32l.7 160.2c0 2.7-.2 5.4-.5 8.1V472c0 22.1-17.9 40-40 40H456c-1.1 0-2.2 0-3.3-.1c-1.4 .1-2.8 .1-4.2 .1H416 392c-22.1 0-40-17.9-40-40V448 384c0-17.7-14.3-32-32-32H256c-17.7 0-32 14.3-32 32v64 24c0 22.1-17.9 40-40 40H160 128.1c-1.5 0-3-.1-4.5-.2c-1.2 .1-2.4 .2-3.6 .2H80c-22.1 0-40-17.9-40-40V360c0-.9 0-1.9 .1-2.8V287.6H32c-18 0-32-14-32-32.1c0-9 3-17.6 8.3-24.6L214.6 1.4c6-3.9 13.5-3.9 19.5 0l210.7 141.2c6.4 4.2 9.9 11.2 9.9 18.9v44.1l-61.3 41.1c-18.4 12.3-30.2 33.5-30.2 57.2v80.6z"/></svg>'); }
        #status.with-icon { background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="%236c757d"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM216 336V288H144c-13.3 0-24-10.7-24-24s10.7-24 24-24h72V192c0-13.3 10.7-24 24-24s24 10.7 24 24v48h72c13.3 0 24 10.7 24 24s-10.7 24-24 24H264v48c0 13.3-10.7 24-24 24s-24-10.7-24-24z"/></svg>'); }
    </style>
</head>
<body class="initial-hidden"> <div class="main-container fade-in-element">
    <div class="card">
        <div class="card-header">
            <span><i class="fas fa-plus-circle me-2"></i> Thêm Phòng Mới</span>
            <a href="index.php" class="btn btn-light btn-sm rounded-pill px-3 nav-link-fade">
                <i class="fas fa-arrow-left me-1"></i> Quay lại
            </a>
        </div>
        <div class="card-body">
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $msg_type; ?> alert-dismissible fade show mb-4" role="alert">
                    <?= $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="process_create.php" method="POST"> 
                
                <div class="mb-3">
                    <label for="room_number" class="form-label required">Số Phòng</label>
                    <input type="text" name="room_number" id="room_number" class="form-control with-icon" value="<?= htmlspecialchars($room_number) ?>" required placeholder="Ví dụ: 101, 205A">
                </div>

                <div class="mb-3">
                    <label for="type_id" class="form-label required">Loại Phòng</label>
                    <select name="type_id" id="type_id" class="form-select with-icon" required>
                        <option value="">Chọn loại phòng</option>
                        <?php foreach ($room_types as $type): ?>
                            <option value="<?= $type['id'] ?>" <?= ($type['id'] == $type_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['type_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="status" class="form-label required">Trạng thái ban đầu</label>
                    <select name="status" id="status" class="form-select with-icon" required>
                        <option value="available" <?= ($status == 'available') ? 'selected' : '' ?>>Sẵn sàng (Available)</option>
                        <option value="occupied" <?= ($status == 'occupied') ? 'selected' : '' ?>>Đang sử dụng (Occupied)</option>
                        <option value="cleaning" <?= ($status == 'cleaning') ? 'selected' : '' ?>>Đang dọn dẹp (Cleaning)</option>
                        <option value="maintenance" <?= ($status == 'maintenance') ? 'selected' : '' ?>>Bảo trì (Maintenance)</option>
                    </select>
                    <small class="form-text text-muted mt-2 d-block">Trạng thái này sẽ được cập nhật khi có booking hoặc thao tác thủ công.</small>
                </div>

                <div class="d-grid gap-2 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="fas fa-save me-2"></i> Lưu Phòng
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary-custom nav-link-fade">
                        <i class="fas fa-times me-2"></i> Hủy và Quay lại
                    </a>
                </div>
            </form>
        </div>
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
            bodyElement.classList.remove('initial-hidden'); // Xóa class ẩn khỏi body
        }, 50); 
    }
    
    // 2. XỬ LÝ FADE-OUT KHI NHẤN VÀO LINK (CHO CÁC LINK QUAY LẠI/HỦY)
    document.querySelectorAll('a.nav-link-fade').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
                e.preventDefault(); 
                
                // Kích hoạt hiệu ứng fade out
                if (fadeElement) {
                    fadeElement.classList.remove('is-visible');
                    // Thêm lại class ẩn vào body để chuẩn bị cho trang tiếp theo (nếu cũng có hiệu ứng)
                    bodyElement.classList.add('initial-hidden'); 
                }
                
                // Chờ hiệu ứng fade out hoàn tất (0.5s) rồi mới chuyển hướng
                setTimeout(() => {
                    window.location.href = href;
                }, 500); // Phải khớp với CSS transition duration (0.5s)
            }
        });
    });
});
</script>

</body>
</html>