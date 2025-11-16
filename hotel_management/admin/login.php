<?php
// admin/login.php
session_start();
include "../config/db.php";

// Nếu đã đăng nhập admin (kiểm tra username) → chuyển về dashboard
if(isset($_SESSION['admin_id'])){
    header("Location: index.php");
    exit();
}

$error = '';

if(isset($_POST['login'])){
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Lấy tài khoản admin TỪ BẢNG USERS
    $stmt = $conn->prepare("SELECT id, username, password_hash, role 
                            FROM users 
                            WHERE username=? AND role='admin'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 1){
        $admin = $result->fetch_assoc();

        if(password_verify($password, $admin['password_hash'])){
            
            // LƯU Ý: Đặt $_SESSION['admin_id'] là username để khớp với các file khác (index/profile)
            $_SESSION['admin_id'] = $admin['username']; 
            // Lưu thêm ID chính (PK) để dùng cho các truy vấn cần ID
            $_SESSION['user_id_pk'] = $admin['id']; 

            header("Location: index.php");
            exit();
        }
        $error = "Sai mật khẩu!";
    } else {
        $error = "Tài khoản admin không tồn tại hoặc không có quyền truy cập!";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin</title>

    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        /* GIỮ NGUYÊN TOÀN BỘ STYLE BẠN ĐÃ CUNG CẤP */
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.98); } to { opacity: 1; transform: scale(1); } }
        @keyframes subtleGlow { 0% { box-shadow: 0 0 0 0 rgba(193, 168, 142, 0.4); } 100% { box-shadow: 0 0 0 8px rgba(193, 168, 142, 0); } }
        body { font-family: 'Roboto', sans-serif; margin: 0; padding: 0; min-height: 100vh; display: flex; justify-content: center; align-items: center; background-color: #f7f3ed; color: #4b3d36; overflow: hidden; }
        .login-container { background-color: #ffffff; padding: 50px 40px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); text-align: center; max-width: 400px; width: 90%; opacity: 0; animation: scaleIn 0.9s ease-out forwards; position: relative; }
        h2 { font-family: 'Lora', serif; color: #2c2725; margin-bottom: 30px; font-size: 2em; font-weight: 700; letter-spacing: 1px; position: relative; }
        h2::after { content: ''; display: block; width: 50px; height: 2px; background-color: #c1a88e; margin: 10px auto 0; }
        form { display: flex; flex-direction: column; gap: 20px; }
        .input-group { position: relative; text-align: left; width: 100%; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #c1a88e; font-size: 1.1em; transition: color 0.3s; pointer-events: none; }
        input[type="text"], input[type="password"] { width: 100%; box-sizing: border-box; padding: 15px 15px 15px 45px; background-color: #fcfcfc; border: 1px solid #e0e0e0; border-radius: 8px; color: #4b3d36; font-size: 1em; transition: border-color 0.3s, box-shadow 0.3s; }
        input:focus { outline: none; border-color: #c1a88e; box-shadow: 0 0 10px rgba(193, 168, 142, 0.4); }
        input:focus ~ i { color: #4b3d36; }
        button[name="login"] { padding: 15px; background-color: #c1a88e; color: #fff; border: none; border-radius: 8px; font-size: 1.1em; font-weight: 700; text-transform: uppercase; cursor: pointer; transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); box-shadow: 0 5px 15px rgba(193, 168, 142, 0.3); animation: subtleGlow 2.5s infinite alternate; position: relative; overflow: hidden; }
        button[name="login"]:hover { background-color: #b39b83; transform: translateY(-4px); box-shadow: 0 12px 25px rgba(193, 168, 142, 0.5); animation: none; }
        button[name="login"]::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.3) 50%, rgba(255, 255, 255, 0.1) 100% ); transform: skewX(-20deg); transition: transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1); }
        button[name="login"]:hover::before { transform: skewX(-20deg) translateX(200%); }
        .error-message { color: #dc3545; margin-top: 10px; font-size: 0.9em; }
        .home-link { text-align: center; margin-top: 20px; font-size: 0.85em; }
    </style>
</head>
<body>

<div class="login-container">
    <h2>ĐĂNG NHẬP ADMIN</h2>

    <form method="post">
        <div class="input-group">
            <input type="text" name="username" placeholder="Tài khoản" required
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            <i class="fas fa-user"></i>
        </div>

        <div class="input-group">
            <input type="password" name="password" placeholder="Mật khẩu" required>
            <i class="fas fa-lock"></i>
        </div>

        <button type="submit" name="login">
            <i class="fas fa-sign-in-alt"></i> ĐĂNG NHẬP
        </button>
    </form>

    <?php if($error): ?>
        <p class="error-message">
            <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
        </p>
    <?php endif; ?>

    <div class="home-link">
        <a href="../index.php"><i class="fas fa-home"></i> Quay lại Trang chủ</a>
    </div>
</div>

</body>
</html>