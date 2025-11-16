<?php
session_start();

// 1. Kiểm tra đăng nhập Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// 2. Kết nối CSDL
include "../../config/db.php";

// 3. Lấy ID và kiểm tra hợp lệ
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    // Chuẩn bị câu lệnh xóa
    $sql = "DELETE FROM payments WHERE id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // Kiểm tra xem có dòng nào thực sự bị xóa không
            if ($stmt->affected_rows > 0) {
                $_SESSION['message'] = "Đã xóa vĩnh viễn giao dịch #" . $id;
                $_SESSION['msg_type'] = "success";
            } else {
                $_SESSION['message'] = "Giao dịch không tồn tại hoặc đã bị xóa trước đó.";
                $_SESSION['msg_type'] = "warning";
            }
        } else {
            $_SESSION['message'] = "Lỗi xóa: Có thể giao dịch này đang liên kết với dữ liệu quan trọng khác (Booking).";
            $_SESSION['msg_type'] = "danger";
        }
        $stmt->close();
    }
} else {
    $_SESSION['message'] = "ID giao dịch không hợp lệ.";
    $_SESSION['msg_type'] = "danger";
}

$conn->close();

// 4. Quay về trang danh sách
header("Location: index.php");
exit();
?>