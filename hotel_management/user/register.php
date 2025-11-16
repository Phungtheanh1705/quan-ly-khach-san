<?php
session_start();
include "../config/db.php";

$error = '';
$success = '';

if(isset($_POST['register'])){
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm = trim($_POST['confirm']);

    if(empty($username) || empty($password) || empty($confirm)){
        $error = "Vui lòng điền đầy đủ thông tin!";
    } elseif($password !== $confirm){
        $error = "Mật khẩu xác nhận không khớp!";
    } else {
        // 1. Kiểm tra tài khoản đã tồn tại
        $stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0){
            $error = "Tên tài khoản đã tồn tại!";
        } else {
            // SỬA ĐỔI QUAN TRỌNG: Băm mật khẩu và sử dụng cột 'password_hash'
            $hashed_password = password_hash($password, PASSWORD_DEFAULT); 
            $role = 'user';
            
            // Thay 'password' bằng 'password_hash'
            $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashed_password, $role);

            if ($stmt->execute()) { 
                $success = "Đăng ký thành công! <a href='login.php' class='link-success'>Đăng nhập ngay</a>";
            } else {
                 $error = "Lỗi khi đăng ký tài khoản: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký Khách hàng</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS LUXURY BEIGE/TAUPE (Giữ nguyên) */
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.98); } to { opacity: 1; transform: scale(1); } }
        body { font-family: 'Roboto', sans-serif; margin: 0; padding: 0; min-height: 100vh; display: flex; justify-content: center; align-items: center; background-color: #f7f3ed; color: #4b3d36; overflow: hidden; }
        .register-container { background-color: #ffffff; padding: 50px 40px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); text-align: center; max-width: 400px; width: 90%; opacity: 0; animation: scaleIn 0.9s ease-out forwards; position: relative; }
        h2 { font-family: 'Lora', serif; color: #2c2725; margin-bottom: 30px; font-size: 2em; font-weight: 700; letter-spacing: 1px; position: relative; }
        h2::after { content: ''; display: block; width: 60px; height: 2px; background-color: #c1a88e; margin: 10px auto 0; }
        form { display: flex; flex-direction: column; gap: 20px; }
        .input-group { position: relative; text-align: left; width: 100%; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #c1a88e; font-size: 1.1em; transition: color 0.3s; pointer-events: none; }
        input[type="text"], input[type="password"] { width: 100%; box-sizing: border-box; padding: 15px 15px 15px 45px; background-color: #fcfcfc; border: 1px solid #e0e0e0; border-radius: 8px; color: #4b3d36; font-size: 1em; transition: border-color 0.3s, box-shadow 0.3s; }
        input:focus { outline: none; border-color: #c1a88e; box-shadow: 0 0 10px rgba(193, 168, 142, 0.4); }
        input:focus ~ i { color: #4b3d36; }
        button[name="register"] { padding: 15px; background-color: #4b3d36; color: #fff; border: none; border-radius: 8px; font-size: 1.1em; font-weight: 700; text-transform: uppercase; cursor: pointer; transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); position: relative; overflow: hidden; }
        button[name="register"]:hover { background-color: #2c221e; transform: translateY(-4px); box-shadow: 0 12px 25px rgba(0, 0, 0, 0.25); }
        button[name="register"]::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient( 90deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.3) 50%, rgba(255, 255, 255, 0.1) 100% ); transform: skewX(-20deg); transition: transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1); }
        button[name="register"]:hover::before { transform: skewX(-20deg) translateX(200%); }
        .message-box { margin-top: 15px; font-size: 0.95em; padding: 10px; border-radius: 5px; }
        .error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; }
        .success-message { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; }
        .link-success { color: #4b3d36; font-weight: 700; text-decoration: none; }
        .link-success:hover { text-decoration: underline; }
        .links-group { margin-top: 25px; display: flex; justify-content: center; gap: 20px; font-size: 0.9em; }
        .links-group a { color: #c1a88e; text-decoration: none; transition: color 0.3s; }
        .links-group a:hover { color: #4b3d36; text-decoration: underline; }
    </style>
</head>
<body>

    <div class="register-container">
        <h2>TẠO TÀI KHOẢN KHÁCH HÀNG</h2>
        
        <form method="post">
            <div class="input-group">
                <input type="text" name="username" placeholder="Tài khoản" required 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                <i class="fas fa-user-plus"></i>
            </div>
            
            <div class="input-group">
                <input type="password" name="password" placeholder="Mật khẩu" required>
                <i class="fas fa-lock"></i>
            </div>
            
            <div class="input-group">
                <input type="password" name="confirm" placeholder="Xác nhận mật khẩu" required>
                <i class="fas fa-key"></i>
            </div>
            
            <button type="submit" name="register">
                <i class="fas fa-sign-in-alt"></i> ĐĂNG KÝ
            </button>
        </form>
        
        <?php if($error): ?>
            <p class="message-box error-message">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </p>
        <?php endif; ?>
        
        <?php if($success): ?>
            <p class="message-box success-message">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </p>
        <?php endif; ?>

        <div class="links-group">
            <a href="login.php">
                <i class="fas fa-sign-in-alt"></i> Quay lại Đăng nhập
            </a>
            
            <a href="../index.php">
                <i class="fas fa-home"></i> Trang chủ
            </a>
        </div>
        
    </div>

</body>
</html>