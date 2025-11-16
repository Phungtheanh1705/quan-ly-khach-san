<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); 
    exit();
}
include "../../config/db.php"; 

$errors = [];
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Lấy dữ liệu và làm sạch
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $is_active = 1; // Mặc định là active

    // Validation
    if (empty($username) || empty($password) || empty($role)) {
        $errors[] = "Vui lòng điền đầy đủ các trường bắt buộc (*).";
    }
    if (strlen($password) < 6) {
        $errors[] = "Mật khẩu phải có ít nhất 6 ký tự.";
    }

    // Kiểm tra username đã tồn tại chưa
    if (empty($errors)) {
        $check_sql = "SELECT id FROM users WHERE username = ?";
        if ($stmt = $conn->prepare($check_sql)) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.";
            }
            $stmt->close();
        }
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, password_hash, email, full_name, phone, address, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssssssi", $username, $hashed_password, $email, $full_name, $phone, $address, $role, $is_active);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Thêm người dùng **{$username}** thành công!";
                $_SESSION['msg_type'] = "success";
                $stmt->close();
                header("Location: index.php");
                exit();
            } else {
                $errors[] = "Lỗi SQL khi thêm người dùng: " . $stmt->error;
                $stmt->close();
            }
        } else {
            $errors[] = "Lỗi chuẩn bị truy vấn.";
        }
    }
    
    // Lưu lại dữ liệu form nếu có lỗi
    $_SESSION['input_data'] = $_POST;
    $message = "Có lỗi xảy ra khi tạo người dùng.";
    $msg_type = "danger";
}

$conn->close();

// Load dữ liệu đã nhập nếu có lỗi
$input_data = $_SESSION['input_data'] ?? [];
unset($_SESSION['input_data']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Người dùng Mới - QLKS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .initial-hidden { opacity: 0; }
        .fade-in-element { opacity: 0; transition: opacity 0.5s ease-in-out; }
        .fade-in-element.is-visible { opacity: 1; }
        .form-label.required::after { content: " *"; color: #dc3545; font-weight: normal; }
    </style>
</head>
<body class="initial-hidden">

<div class="main-wrapper d-flex fade-in-element">

<div class="page-content-wrapper flex-grow-1">
    
    <nav class="navbar navbar-light bg-white shadow-sm sticky-top p-3">
        <div class="container-fluid">
            <a class="navbar-brand text-primary fw-bold nav-link-fade" href="index.php">
                <i class="fas fa-arrow-left me-1"></i> Quay lại QL Người dùng
            </a>
        </div>
    </nav>

    <div class="content container-fluid mt-4">
        <h1 class="mb-4"><i class="fas fa-user-plus me-2 text-primary"></i> Thêm Người dùng Mới</h1>
        
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

        <div class="card shadow-lg">
            <div class="card-body p-4">
                <form action="add.php" method="POST"> 
                    <div class="row g-3">
                        
                        <div class="col-md-6">
                            <label for="username" class="form-label required">Tên đăng nhập</label>
                            <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($input_data['username'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label required">Mật khẩu</label>
                            <input type="password" name="password" id="password" class="form-control" required minlength="6">
                            <small class="form-text text-muted">Tối thiểu 6 ký tự.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Họ và Tên</label>
                            <input type="text" name="full_name" id="full_name" class="form-control" value="<?= htmlspecialchars($input_data['full_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($input_data['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Điện thoại</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($input_data['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="address" class="form-label">Địa chỉ</label>
                            <input type="text" name="address" id="address" class="form-control" value="<?= htmlspecialchars($input_data['address'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="role" class="form-label required">Vai trò</label>
                            <select name="role" id="role" class="form-select" required>
                                <option value="user" <?= ($input_data['role'] ?? 'user') == 'user' ? 'selected' : '' ?>>User (Khách hàng)</option>
                                <option value="admin" <?= ($input_data['role'] ?? '') == 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        
                    </div>

                    <div class="text-end pt-4 border-top mt-4">
                        <a href="index.php" class="btn btn-outline-secondary me-3 nav-link-fade">
                            <i class="fas fa-times me-2"></i> Hủy
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Lưu Người dùng
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div> 

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. KÍCH HOẠT HIỆU ỨNG FADE-IN KHI TẢI TRANG
    const fadeElement = document.querySelector('.fade-in-element');
    if (fadeElement) {
        setTimeout(() => {
            fadeElement.classList.add('is-visible');
            document.body.classList.remove('initial-hidden');
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