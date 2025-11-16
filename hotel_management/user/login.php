<?php
// user/login.php
session_start();
// Đảm bảo đường dẫn include/require_once đúng nếu cần thiết, giả định đúng là "../config/db.php"
include "../config/db.php";

// ---------------------------------------------------------------------
// SỬA CHỖ 1: CHUYỂN HƯỚNG KHI ĐÃ CÓ SESSION (NGƯỜI DÙNG ĐÃ ĐĂNG NHẬP)
// Nếu đã đăng nhập, chuyển hướng đến trang chủ khách hàng (index.php)
if(isset($_SESSION['user_id'])){
    header("Location: index.php"); 
    exit();
}
// ---------------------------------------------------------------------

$error = '';

if(isset($_POST['login'])){
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Chuẩn bị truy vấn để lấy thông tin người dùng và role
    $stmt = $conn->prepare("SELECT id, username, password_hash, role FROM users WHERE username=? AND role='user'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 1){
        $user = $result->fetch_assoc();
        
        // Xác minh mật khẩu
        if(password_verify($password, $user['password_hash'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_username'] = $user['username'];
            
            // ---------------------------------------------------------------------
            // SỬA CHỖ 2: CHUYỂN HƯỚNG SAU KHI ĐĂNG NHẬP THÀNH CÔNG
            // Chuyển hướng đến trang chủ khách hàng (index.php)
            header("Location: index.php");
            exit();
            // ---------------------------------------------------------------------
        }
        $error = "Sai mật khẩu!";
    } else {
        $error = "Tài khoản không tồn tại!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Khách hàng</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS LUXURY BEIGE/TAUPE (Giữ nguyên) */
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
        button[name="login"]::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient( 90deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.3) 50%, rgba(255, 255, 255, 0.1) 100% ); transform: skewX(-20deg); transition: transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1); }
        button[name="login"]:hover::before { transform: skewX(-20deg) translateX(200%); }
        .error-message { color: #dc3545; margin-top: 10px; font-size: 0.9em; }
        .links-group { margin-top: 30px; display: flex; justify-content: space-between; font-size: 0.9em; }
        .links-group a { color: #c1a88e; text-decoration: none; transition: color 0.3s; }
        .links-group a:hover { color: #4b3d36; text-decoration: underline; }
        .home-link { text-align: center; margin-top: 20px; font-size: 0.85em; }
        .home-link a { color: #a0a0a0; }
        .home-link a:hover { color: #c1a88e; }
    </style>
</head>
<body>

    <div class="login-container">
        <h2>ĐĂNG NHẬP KHÁCH HÀNG</h2>
        
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
                <i class="fas fa-concierge-bell"></i> ĐĂNG NHẬP
            </button>
        </form>
        
        <?php if($error): ?>
            <p class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </p>
        <?php endif; ?>

        <div class="links-group">
            <a href="register.php">
                <i class="fas fa-user-plus"></i> Đăng ký tài khoản
            </a>
            
            <a href="#">
                <i class="fas fa-key"></i> Quên mật khẩu?
            </a>
        </div>
        
        <div class="home-link">
            <a href="../index.php">
                <i class="fas fa-home"></i> Quay lại Trang chủ
            </a>
        </div>
        
    </div>

</body>
</html>