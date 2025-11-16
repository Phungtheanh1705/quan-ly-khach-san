<?php
session_start();
// Yêu cầu đăng nhập Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// 1. Kiểm tra ID hợp lệ
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Không tìm thấy ID Booking hợp lệ.";
    $_SESSION['msg_type'] = "danger";
    header("Location: index.php");
    exit();
}

include "../../config/db.php"; 

$booking_id = (int)$_GET['id'];
$new_status = 'confirmed'; // Trạng thái mới

// 2. Chuẩn bị câu lệnh SQL UPDATE
// Cập nhật trạng thái và thời gian cập nhật
$update_query = "UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?";

if ($stmt = $conn->prepare($update_query)) {
    
    $stmt->bind_param("si", $new_status, $booking_id);
    
    // 3. Thực thi câu lệnh
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Thành công
            $_SESSION['message'] = "Booking #{$booking_id} đã được **XÁC NHẬN** thành công!";
            $_SESSION['msg_type'] = "success";
        } else {
            // Không có hàng nào bị ảnh hưởng (ví dụ: đã được xác nhận trước đó hoặc ID không tồn tại)
            $_SESSION['message'] = "Booking #{$booking_id} không thay đổi trạng thái (có thể đã được xác nhận).";
            $_SESSION['msg_type'] = "info";
        }
    } else {
        // Lỗi thực thi
        $_SESSION['message'] = "Lỗi khi xác nhận Booking: " . $stmt->error;
        $_SESSION['msg_type'] = "danger";
    }
    
    $stmt->close();
} else {
    // Lỗi chuẩn bị truy vấn
    $_SESSION['message'] = "Lỗi chuẩn bị truy vấn SQL: " . $conn->error;
    $_SESSION['msg_type'] = "danger";
}

$conn->close();

// 4. Chuyển hướng người dùng trở lại trang chi tiết booking
header("Location: detail.php?id=" . $booking_id);
exit();
?>