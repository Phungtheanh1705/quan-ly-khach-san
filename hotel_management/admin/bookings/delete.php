<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    // Chuyển hướng nếu không phải admin
    header("Location: ../login.php"); 
    exit();
}

include "../../config/db.php"; 

// 1. Lấy ID của Booking cần xóa
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($booking_id > 0) {
    // Bắt đầu transaction để đảm bảo tất cả các thao tác (xóa payment, xóa booking, cập nhật phòng) 
    // đều thành công hoặc thất bại cùng lúc (ALL or NOTHING)
    $conn->begin_transaction();
    
    try {
        // Lấy room_id hiện tại của booking để cập nhật trạng thái phòng sau này
        $room_id_query = "SELECT room_id FROM bookings WHERE id = ?";
        if ($stmt = $conn->prepare($room_id_query)) {
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $booking_data = $result->fetch_assoc();
            $room_id_to_free = $booking_data['room_id'] ?? 0;
            $stmt->close();
        } else {
             // Ném ra Exception nếu truy vấn SQL thất bại
             throw new Exception("Lỗi truy vấn SQL để lấy room_id.");
        }
        
        // --- 2. XÓA BẢN GHI THANH TOÁN (PAYMENTS) ---
        // Xóa các bản ghi liên quan trong bảng payments trước
        $delete_payment_query = "DELETE FROM payments WHERE booking_id = ?";
        if ($stmt = $conn->prepare($delete_payment_query)) {
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $stmt->close();
        } else {
            throw new Exception("Lỗi khi xóa thanh toán liên quan.");
        }

        // --- 3. XÓA BOOKING ---
        $delete_booking_query = "DELETE FROM bookings WHERE id = ?";
        if ($stmt = $conn->prepare($delete_booking_query)) {
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            
            // Kiểm tra xem có dòng nào được xóa không
            if ($stmt->affected_rows === 0) {
                 throw new Exception("Booking không tồn tại trong cơ sở dữ liệu.");
            }
            $stmt->close();
        } else {
             throw new Exception("Lỗi khi xóa booking.");
        }

        // --- 4. CẬP NHẬT TRẠNG THÁI PHÒNG ---
        // Đặt phòng trở lại thành 'available' nếu có room_id hợp lệ
        if ($room_id_to_free > 0) {
            $update_room_query = "UPDATE rooms SET status = 'available' WHERE id = ?";
            if ($stmt = $conn->prepare($update_room_query)) {
                $stmt->bind_param("i", $room_id_to_free);
                $stmt->execute();
                $stmt->close();
            } else {
                throw new Exception("Lỗi khi cập nhật trạng thái phòng.");
            }
        }

        // Nếu mọi thứ đều ổn, commit transaction
        $conn->commit();
        $_SESSION['message'] = "Booking #{$booking_id} và các thanh toán liên quan đã được **XÓA THÀNH CÔNG**. Phòng đã được giải phóng.";
        $_SESSION['msg_type'] = "success";

    } catch (Exception $e) {
        // Nếu có lỗi, rollback transaction (hủy bỏ tất cả các thay đổi DB)
        $conn->rollback();
        $_SESSION['message'] = "Lỗi khi xóa Booking #{$booking_id}: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }
} else {
    $_SESSION['message'] = "ID Booking không hợp lệ.";
    $_SESSION['msg_type'] = "danger";
}

$conn->close();

// Chuyển hướng về trang danh sách
header("Location: index.php");
exit();
?>