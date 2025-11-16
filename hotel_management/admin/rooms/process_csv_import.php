<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); 
    exit();
}
include "../../config/db.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['csv_file'])) {
    
    $file_info = $_FILES['csv_file'];
    
    if ($file_info['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['message'] = "Lỗi upload file: " . $file_info['error'];
        $_SESSION['msg_type'] = "danger";
        header("Location: index.php");
        exit();
    }

    $mime_type = mime_content_type($file_info['tmp_name']);
    if ($mime_type != 'text/csv' && $mime_type != 'text/plain' && $file_info['type'] != 'text/csv') {
        $_SESSION['message'] = "File tải lên không phải là định dạng CSV hợp lệ.";
        $_SESSION['msg_type'] = "danger";
        header("Location: index.php");
        exit();
    }

    $inserted_count = 0;
    $error_count = 0;
    $duplicate_count = 0;

    if (($handle = fopen($file_info['tmp_name'], "r")) !== FALSE) {
        $conn->begin_transaction(); 
        
        fgetcsv($handle); // Bỏ qua hàng tiêu đề nếu có
        
        $insert_stmt = $conn->prepare("INSERT INTO rooms (room_number, type_id, status) VALUES (?, ?, ?)");
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // room_number, type_id, status
            if (count($data) < 3) {
                $error_count++;
                continue;
            }

            $room_number = trim($data[0]);
            $type_id = (int)trim($data[1]);
            $status = trim($data[2]);

            // Kiểm tra số phòng đã tồn tại 
            $check_sql = "SELECT id FROM rooms WHERE room_number = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $room_number);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $duplicate_count++;
                $check_stmt->close();
                continue; 
            }
            $check_stmt->close();

            // Thực hiện insert
            $insert_stmt->bind_param("sis", $room_number, $type_id, $status);
            if ($insert_stmt->execute()) {
                $inserted_count++;
            } else {
                $error_count++;
            }
        }
        
        $insert_stmt->close();
        fclose($handle);
        
        if ($error_count == 0) {
            $conn->commit();
            $_SESSION['message'] = "Nhập dữ liệu phòng thành công: **{$inserted_count}** phòng đã được thêm. ({$duplicate_count} phòng trùng lặp đã bị bỏ qua).";
            $_SESSION['msg_type'] = "success";
        } else {
            $conn->rollback();
            $_SESSION['message'] = "Lỗi khi nhập phòng: Thêm thành công {$inserted_count} phòng, nhưng có {$error_count} lỗi xảy ra hoặc dữ liệu không hợp lệ. ({$duplicate_count} phòng trùng lặp đã bị bỏ qua). Đã rollback.";
            $_SESSION['msg_type'] = "danger";
        }

    } else {
        $_SESSION['message'] = "Không thể mở file CSV.";
        $_SESSION['msg_type'] = "danger";
    }

    $conn->close();
    header("Location: index.php");
    exit();

} else {
    header("Location: index.php");
    exit();
}