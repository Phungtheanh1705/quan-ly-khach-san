<?php
session_start();
// Đảm bảo file db.php tồn tại và kết nối $conn đã được thiết lập
include "../../config/db.php"; 

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); 
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- Lấy và làm sạch dữ liệu ---
    $user_id = empty($_POST['user_id']) ? null : (int)$_POST['user_id'];
    $room_id = (int)$_POST['room_id'];
    $guest_count = (int)$_POST['guest_count'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $payment_method = $_POST['payment_method']; // Đã được sửa giá trị trong create.php
    $status = $_POST['status'];
    $notes = trim($_POST['notes']);
    $total_price = (float)$_POST['total_price']; // Giá trị từ JS

    // --- Validation cơ bản ---
    if (empty($room_id) || empty($check_in) || empty($check_out) || empty($status) || empty($payment_method)) {
        $_SESSION['message'] = "Vui lòng điền đầy đủ các trường bắt buộc (*).";
        $_SESSION['msg_type'] = "danger";
        header("Location: create.php");
        exit();
    }

    if ($check_in >= $check_out) {
        $_SESSION['message'] = "Ngày Check-out phải lớn hơn ngày Check-in.";
        $_SESSION['msg_type'] = "danger";
        header("Location: create.php");
        exit();
    }
    
    // Bắt đầu Transaction để đảm bảo tính toàn vẹn dữ liệu
    $conn->begin_transaction();
    $success = true;

    try {
        // 1. Chèn vào bảng bookings
        $booking_sql = "INSERT INTO bookings (user_id, room_id, guest_count, check_in, check_out, total_price, status, payment_method, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        if ($stmt = $conn->prepare($booking_sql)) {
            // "i" cho user_id, room_id, guest_count; "d" cho total_price; "s" cho các trường còn lại
            $stmt->bind_param("iiisssssd", $user_id, $room_id, $guest_count, $check_in, $check_out, $total_price, $status, $payment_method, $notes);
            
            if (!$stmt->execute()) {
                $success = false;
                throw new Exception("Lỗi khi tạo booking: " . $stmt->error);
            }
            $booking_id = $conn->insert_id;
            $stmt->close();
        } else {
            throw new Exception("Lỗi chuẩn bị truy vấn booking: " . $conn->error);
        }
        
        // 2. Chèn vào bảng payments (Đã sửa truy vấn SQL)
        // Sử dụng cột: booking_id, amount, payment_method, transaction_id, status, payment_date
        $payment_status = 'pending'; 
        
        // SỬA TRUY VẤN: Sử dụng 'payment_date' và chèn NULL cho 'transaction_id'
        $payment_sql = "INSERT INTO payments (booking_id, amount, payment_method, transaction_id, status, payment_date) VALUES (?, ?, ?, NULL, ?, NOW())";
        
        if ($stmt = $conn->prepare($payment_sql)) {
            // Tham số bind: i (booking_id), d (amount/total_price), s (payment_method), s (status)
            $stmt->bind_param("idss", $booking_id, $total_price, $payment_method, $payment_status);
            
            if (!$stmt->execute()) {
                $success = false;
                throw new Exception("Lỗi khi tạo thanh toán: " . $stmt->error);
            }
            $stmt->close();
        } else {
            throw new Exception("Lỗi chuẩn bị truy vấn thanh toán: " . $conn->error);
        }

        // --- Hoàn thành Transaction ---
        if ($success) {
            $conn->commit();
            $_SESSION['message'] = "Tạo Booking #{$booking_id} thành công!";
            $_SESSION['msg_type'] = "success";
            header("Location: index.php"); // Chuyển về trang danh sách
            exit();
        }
        
    } catch (Exception $e) {
        // --- Xử lý lỗi và Rollback ---
        $conn->rollback();
        $_SESSION['message'] = "Lỗi hệ thống khi tạo booking: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
        header("Location: create.php"); // Quay lại form với thông báo lỗi
        exit();
    }

} else {
    // Nếu truy cập trực tiếp file process mà không qua POST
    header("Location: create.php");
    exit();
}
?>