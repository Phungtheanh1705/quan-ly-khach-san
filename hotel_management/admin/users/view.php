<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); 
    exit();
}
include "../../config/db.php"; 

$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?? 0;
$user_data = null;

if ($user_id > 0) {
    $sql = "SELECT id, username, email, full_name, phone, address, role, created_at, is_active FROM users WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}
$conn->close();

if (!$user_data) {
    $_SESSION['message'] = "Không tìm thấy người dùng này.";
    $_SESSION['msg_type'] = "danger";
    header("Location: index.php");
    exit();
}

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
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết User #<?= $user_data['id'] ?> - QLKS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .initial-hidden { opacity: 0; }
        .fade-in-element { opacity: 0; transition: opacity 0.5s ease-in-out; }
        .fade-in-element.is-visible { opacity: 1; }
        .main-container { width: 100%; max-width: 800px; padding: 20px; margin: 0 auto; }
        .card { border-radius: 1rem; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); }
        .info-group { padding: 10px 0; border-bottom: 1px dashed #eee; }
        .info-group:last-child { border-bottom: none; }
        .info-group .label { font-weight: 500; color: #6c757d; }
    </style>
</head>
<body class="initial-hidden">

<div class="main-container fade-in-element">
    <div class="card">
        <div class="card-header bg-primary text-white p-3">
            <span><i class="fas fa-eye me-2"></i> Chi tiết Người dùng #<?= $user_data['id'] ?></span>
            <a href="index.php" class="btn btn-light btn-sm rounded-pill px-3 nav-link-fade">
                <i class="fas fa-arrow-left me-1"></i> Quay lại
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 border-end">
                    <h5 class="text-primary mb-3"><i class="fas fa-user-shield me-2"></i> Tài khoản</h5>
                    <div class="info-group d-flex justify-content-between">
                        <span class="label">Username:</span>
                        <span class="fw-bold"><?= htmlspecialchars($user_data['username']) ?></span>
                    </div>
                    <div class="info-group d-flex justify-content-between">
                        <span class="label">Họ và Tên:</span>
                        <span class="fw-bold"><?= htmlspecialchars($user_data['full_name'] ?: 'N/A') ?></span>
                    </div>
                    <div class="info-group d-flex justify-content-between">
                        <span class="label">Vai trò:</span>
                        <span><?= get_role_badge($user_data['role']) ?></span>
                    </div>
                    <div class="info-group d-flex justify-content-between">
                        <span class="label">Trạng thái:</span>
                        <span><?= get_status_badge($user_data['is_active']) ?></span>
                    </div>
                    <div class="info-group d-flex justify-content-between">
                        <span class="label">Ngày tạo:</span>
                        <span><?= date('d/m/Y H:i', strtotime($user_data['created_at'])) ?></span>
                    </div>
                </div>

                <div class="col-md-6">
                    <h5 class="text-primary mb-3"><i class="fas fa-address-book me-2"></i> Liên hệ & Địa chỉ</h5>
                    <div class="info-group d-flex justify-content-between">
                        <span class="label"><i class="fas fa-envelope"></i> Email:</span>
                        <span><?= htmlspecialchars($user_data['email'] ?: 'N/A') ?></span>
                    </div>
                    <div class="info-group d-flex justify-content-between">
                        <span class="label"><i class="fas fa-phone-alt"></i> Điện thoại:</span>
                        <span><?= htmlspecialchars($user_data['phone'] ?: 'N/A') ?></span>
                    </div>
                    <div class="info-group d-flex justify-content-between">
                        <span class="label"><i class="fas fa-map-marker-alt"></i> Địa chỉ:</span>
                        <span><?= htmlspecialchars($user_data['address'] ?: 'N/A') ?></span>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4 pt-3 border-top">
                <a href="edit.php?id=<?= $user_data['id'] ?>" class="btn btn-primary"><i class="fas fa-edit me-2"></i> Chỉnh sửa</a>
                <a href="index.php" class="btn btn-secondary nav-link-fade"><i class="fas fa-list me-2"></i> Quay lại</a>
            </div>
        </div>
    </div>
</div> 

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fadeElement = document.querySelector('.fade-in-element');

    if (fadeElement) {
        setTimeout(() => {
            fadeElement.classList.add('is-visible');
            document.body.classList.remove('initial-hidden');
        }, 50); 
    }
});
</script>

</body>
</html>