<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); 
    exit();
}
include "../../config/db.php"; 

$id = $_GET['id'] ?? 0;

if (empty($id) || !is_numeric($id)) {
    $_SESSION['message'] = "ID phòng không hợp lệ.";
    $_SESSION['msg_type'] = "danger";
    header("Location: index.php");
    exit();
}

// Lấy số phòng để hiển thị thông báo
$room_number = '';
$check_sql = "SELECT room_number FROM rooms WHERE id = ?";
if ($stmt = $conn->prepare($check_sql)) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($room_number);
    $stmt->fetch();
    $stmt->close();
}

// Nếu phòng không tồn tại (sau khi kiểm tra), dừng lại
if (empty($room_number)) {
    $_SESSION['message'] = "Không tìm thấy phòng để xóa.";
    $_SESSION['msg_type'] = "danger";
    $conn->close();
    header("Location: index.php");
    exit();
}

// --- Thực hiện xóa phòng ---
$sql = "DELETE FROM rooms WHERE id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Xóa phòng **{$room_number}** thành công!";
        $_SESSION['msg_type'] = "success";
        $stmt->close();
        $conn->close();
        header("Location: index.php");
        exit();
    } else {
        // Lỗi thường gặp: FOREIGN KEY constraint (Phòng đang có Booking liên quan)
        $_SESSION['message'] = "Lỗi khi xóa phòng: " . $stmt->error;
        $_SESSION['msg_type'] = "danger";
        $stmt->close();
        $conn->close();
        header("Location: index.php");
        exit();
    }
} else {
    $_SESSION['message'] = "Lỗi chuẩn bị truy vấn xóa: " . $conn->error;
    $_SESSION['msg_type'] = "danger";
    $conn->close();
    header("Location: index.php");
    exit();
}