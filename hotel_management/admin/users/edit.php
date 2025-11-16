<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); 
    exit();
}
include "../../config/db.php"; 

$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?? 0;
$user_data = null;
$errors = [];
$message = $_SESSION['message'] ?? null;
$msg_type = $_SESSION['msg_type'] ?? 'danger';
unset($_SESSION['message']);
unset($_SESSION['msg_type']);

// 1. XỬ LÝ CẬP NHẬT FORM (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $user_id = (int)$_POST['id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $role = $_POST['role'];
    $new_password = $_POST['new_password'];

    // Validation cơ bản
    if (empty($username) || empty($role)) {
        $errors[] = "Tên đăng nhập và vai trò không được để trống.";
    }
    if ($new_password && strlen($new_password) < 6) {
        $errors[] = "Mật khẩu mới phải có ít nhất 6 ký tự.";
    }

    // Kiểm tra username trùng lặp (loại trừ chính user này)
    if (empty($errors)) {
        $check_sql = "SELECT id FROM users WHERE username = ? AND id != ?";
        if ($stmt = $conn->prepare($check_sql)) {
            $stmt->bind_param("si", $username, $user_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "Tên đăng nhập đã tồn tại cho người dùng khác.";
            }
            $stmt->close();
        }
    }
    
    if (empty($errors)) {
        // Cập nhật mật khẩu nếu có nhập mới
        $password_update_sql = "";
        $password_hash = null;
        if ($new_password) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $password_update_sql = ", password_hash = ?";
        }

        // Tạo câu lệnh SQL chính
        $sql = "UPDATE users SET username = ?, email = ?, full_name = ?, phone = ?, address = ?, role = ? {$password_update_sql} WHERE id = ?";
        
        $bind_types = "ssssss"; // Cho username, email, full_name, phone, address, role
        $bind_values = [&$username, &$email, &$full_name, &$phone, &$address, &$role];
        
        if ($new_password) {
            $bind_types .= "s";
            $bind_values[] = &$password_hash;
        }
        $bind_types .= "i";
        $bind_values[] = &$user_id;

        if ($stmt = $conn->prepare($sql)) {
            // Chuẩn bị mảng cho bind_param
            $bind_params = array_merge([$bind_types], $bind_values);
            call_user_func_array([$stmt, 'bind_param'], $bind_params);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Cập nhật người dùng **{$username}** thành công!";
                $_SESSION['msg_type'] = "success";
                header("Location: index.php");
                exit();
            } else {
                $errors[] = "Lỗi SQL khi cập nhật: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Lỗi chuẩn bị truy vấn.";
        }
    }
    // Nếu có lỗi, reload trang với thông báo
    $user_data = $_POST; // Dùng dữ liệu POST để điền lại form
} 

// 2. TẢI DỮ LIỆU BAN ĐẦU (GET) HOẶC SAU KHI XỬ LÝ LỖI
if (empty($user_data)) {
    $sql = "SELECT id, username, email, full_name, phone, address, role FROM users WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}
$conn->close();

if (!$user_data) {
    $_SESSION['message'] = "Không tìm thấy người dùng để chỉnh sửa.";
    $_SESSION['msg_type'] = "danger";
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa User #<?= $user_data['id'] ?> - QLKS Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    
    <style>
        .initial-hidden { opacity: 0; }
        .fade-in-element { opacity: 0; transition: opacity 0.5s ease-in-out; }
        .fade-in-element.is-visible { opacity: 1; }
        .main-container { width: 100%; max-width: 700px; padding: 20px; margin: 0 auto; }
        .card { border-radius: 1rem; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); }
        .card-header { background-color: #ffc107; color: #333; font-weight: bold; }
        .form-label.required::after { content: " *"; color: #dc3545; font-weight: normal; }
        .btn-update { background-color: #007bff; border-color: #007bff; transition: all 0.3s; }
        .btn-update:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3); }
    </style>
</head>
<body class="initial-hidden">

<div class="main-container fade-in-element">
    <div class="card">
        <div class="card-header">
            <span><i class="fas fa-edit me-2"></i> Chỉnh sửa User #<?= $user_data['id'] ?></span>
            <a href="index.php" class="btn btn-light btn-sm rounded-pill px-3 nav-link-fade">
                <i class="fas fa-arrow-left me-1"></i> Quay lại
            </a>
        </div>
        <div class="card-body">
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <strong>Lỗi:</strong>
                    <ul>
                        <?php foreach ($errors as $e): ?>
                            <li><?= $e ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="edit.php?id=<?= $user_id ?>" method="POST"> 
                <input type="hidden" name="id" value="<?= $user_data['id'] ?>">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label required">Tên đăng nhập</label>
                        <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($user_data['username']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="role" class="form-label required">Vai trò</label>
                        <select name="role" id="role" class="form-select" required>
                            <option value="user" <?= ($user_data['role'] == 'user') ? 'selected' : '' ?>>User</option>
                            <option value="admin" <?= ($user_data['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="full_name" class="form-label">Họ và Tên</label>
                        <input type="text" name="full_name" id="full_name" class="form-control" value="<?= htmlspecialchars($user_data['full_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user_data['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Điện thoại</label>
                        <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="address" class="form-label">Địa chỉ</label>
                        <input type="text" name="address" id="address" class="form-control" value="<?= htmlspecialchars($user_data['address'] ?? '') ?>">
                    </div>
                    
                    <div class="col-12 mt-4 border-top pt-3">
                        <label for="new_password" class="form-label">Mật khẩu mới (Để trống nếu không đổi)</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Tối thiểu 6 ký tự">
                    </div>
                    
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-update"><i class="fas fa-save me-2"></i> Cập nhật</button>
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
            bodyElement.classList.remove('initial-hidden');
        }, 50); 
    }
    
    // 2. XỬ LÝ FADE-OUT KHI NHẤN VÀO LINK
    document.querySelectorAll('a.nav-link-fade').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
                e.preventDefault(); 
                
                if (fadeElement) {
                    fadeElement.classList.remove('is-visible');
                }
                
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