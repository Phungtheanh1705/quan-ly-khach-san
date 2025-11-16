<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); 
    exit();
}
include "../../config/db.php"; 

$user_id = $_POST['id'] ?? null;

if (empty($user_id) || !is_numeric($user_id)) {
    $_SESSION['message'] = "ID người dùng không hợp lệ.";
    $_SESSION['msg_type'] = "danger";
    header("Location: index.php");
    exit();
}

// Lấy username để hiển thị thông báo
$username = '';
$check_sql = "SELECT username FROM users WHERE id = ?";
if ($stmt = $conn->prepare($check_sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($username);
    $stmt->fetch();
    $stmt->close();
}

// Kiểm tra: Không cho phép tự xóa tài khoản đang đăng nhập (Nếu bạn lưu ID)
// Giả định: $_SESSION['admin_username'] lưu username của admin hiện tại
if ($username == ($_SESSION['admin_username'] ?? '')) {
    $_SESSION['message'] = "Bạn không thể tự xóa tài khoản đang đăng nhập.";
    $_SESSION['msg_type'] = "danger";
    header("Location: index.php");
    exit();
}


// --- Thực hiện xóa người dùng ---
$sql = "DELETE FROM users WHERE id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
             $_SESSION['message'] = "Xóa người dùng **{$username}** thành công!";
             $_SESSION['msg_type'] = "success";
        } else {
             $_SESSION['message'] = "Không tìm thấy người dùng để xóa.";
             $_SESSION['msg_type'] = "warning";
        }
    } else {
        // Lỗi thường gặp: FOREIGN KEY constraint (Người dùng này có booking liên quan)
        $_SESSION['message'] = "Lỗi khi xóa người dùng: " . $stmt->error;
        $_SESSION['msg_type'] = "danger";
    }
    $stmt->close();
}

$conn->close();
header("Location: index.php");
exit();