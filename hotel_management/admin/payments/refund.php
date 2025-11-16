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
    // Kiểm tra trạng thái hiện tại của giao dịch
    $check_sql = "SELECT status, amount FROM payments WHERE id = ?";
    if ($stmt = $conn->prepare($check_sql)) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($current_status, $amount);
        $stmt->fetch();
        $stmt->close();

        if ($current_status) {
            // Logic: Chỉ hoàn tiền nếu trạng thái đang là 'completed'
            if ($current_status === 'completed') {
                
                // Cập nhật trạng thái sang 'refunded'
                $update_sql = "UPDATE payments SET status = 'refunded' WHERE id = ?";
                
                if ($update_stmt = $conn->prepare($update_sql)) {
                    $update_stmt->bind_param("i", $id);
                    
                    if ($update_stmt->execute()) {
                        $_SESSION['message'] = "Đã hoàn tiền thành công cho giao dịch #" . $id . ". Số tiền: " . number_format($amount) . " VNĐ.";
                        $_SESSION['msg_type'] = "warning"; // Màu vàng cảnh báo
                    } else {
                        $_SESSION['message'] = "Lỗi hệ thống: Không thể cập nhật trạng thái.";
                        $_SESSION['msg_type'] = "danger";
                    }
                    $update_stmt->close();
                }
            } elseif ($current_status === 'refunded') {
                $_SESSION['message'] = "Giao dịch này đã được hoàn tiền trước đó rồi!";
                $_SESSION['msg_type'] = "info";
            } else {
                $_SESSION['message'] = "Không thể hoàn tiền! Chỉ áp dụng cho giao dịch đã hoàn tất (Completed).";
                $_SESSION['msg_type'] = "danger";
            }
        } else {
            $_SESSION['message'] = "Không tìm thấy giao dịch.";
            $_SESSION['msg_type'] = "danger";
        }
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