<?php
// user/booking.php
include "../config/db.php";
session_start();

// --- Helpers ---
function format_currency($amount) {
    return number_format((float)$amount, 0, ',', '.') . ' VNĐ';
}

// --- Init / tránh undefined warnings ---
$error_message   = '';
$success_message = '';
$preview_booking = null;
$room_data       = null;
$show_confirmation = false;

// Kiểm tra đăng nhập (bắt buộc)
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['user_username'] ?? '';

if (!$is_logged_in) {
    // Nếu chưa đăng nhập thì chuyển về trang login
    header("Location: login.php");
    exit();
}

// Lấy room_id từ GET (trang rooms sẽ dẫn tới booking.php?room_id=NN)
$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : (isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0);

// Nếu có room_id, lấy thông tin phòng để hiển thị tóm tắt
if ($room_id > 0) {
    // room_id ở đây thực chất là type_id (loại phòng)
    $sql_room = "SELECT T.id, T.type_name, MIN(R.price) AS price, T.image_path AS img
                 FROM room_types T
                 JOIN rooms R ON T.id = R.type_id
                 WHERE T.id = ? 
                 GROUP BY T.id, T.type_name, T.image_path
                 LIMIT 1";
    $stmt_room = $conn->prepare($sql_room);
    if ($stmt_room) {
        $stmt_room->bind_param("i", $room_id);
        $stmt_room->execute();
        $res_room = $stmt_room->get_result();
        if ($res_room && $res_room->num_rows > 0) {
            $r = $res_room->fetch_assoc();
            // Prepare minimal room_data for UI
            $room_data = [
                'id' => $r['id'],
                'name' => $r['type_name'],
                'price' => (float)$r['price'],
                'img' => $r['img'],
            ];
        }
        $stmt_room->close();
    }
}

// XỬ LÝ FORM - BƯỚC 1: XEM & PREVIEW (book_room)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_room'])) {
    // Lấy dữ liệu POST
    $check_in = trim($_POST['check_in'] ?? '');
    $check_out = trim($_POST['check_out'] ?? '');
    $num_rooms = max(1, (int)($_POST['num_rooms'] ?? 1));
    $total_price = (float)($_POST['total_price'] ?? 0);
    $special_requests = trim($_POST['special_requests'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'cod');
    $room_id = (int)($_POST['room_id'] ?? 0);

    // Validate cơ bản
    if (empty($check_in) || empty($check_out)) {
        $error_message = "Vui lòng chọn ngày nhận phòng và ngày trả phòng.";
    } elseif (strtotime($check_in) >= strtotime($check_out)) {
        $error_message = "Ngày trả phòng phải sau ngày nhận phòng.";
    } elseif ($num_rooms < 1) {
        $error_message = "Số lượng phòng phải ít nhất là 1.";
    } elseif ($room_id <= 0) {
        $error_message = "Loại phòng không hợp lệ. Vui lòng chọn lại phòng.";
    } else {
        // room_id ở đây thực chất là type_id (loại phòng), không cần lấy type từ room nữa
        $type_id = $room_id;
        
        // Count available rooms of same type (excluding booked ones overlapping)
                $sql_available = "SELECT COUNT(*) AS cnt FROM rooms
                                  WHERE type_id = ?
                                    AND status = 'available'
                                    AND id NOT IN (
                                        SELECT DISTINCT room_id FROM bookings
                                        WHERE status != 'cancelled'
                                        AND check_in < ? 
                                        AND check_out > ?
                                    )";
                $stmt_available = $conn->prepare($sql_available);
                if ($stmt_available) {
                    $stmt_available->bind_param("iss", $type_id, $check_out, $check_in);
                    $stmt_available->execute();
                    $res_av = $stmt_available->get_result();
                    $available = $res_av->fetch_assoc();
                    $available_count = (int)$available['cnt'];

                    if ($available_count < $num_rooms) {
                        $error_message = "Không đủ phòng trống cho khoảng thời gian này. Chỉ còn " . $available_count . " phòng.";
                    } else {
                        // Lấy ID các phòng trống để preview
                        $sql_available_rooms = "SELECT id FROM rooms
                                                WHERE type_id = ?
                                                  AND status = 'available'
                                                  AND id NOT IN (
                                                      SELECT DISTINCT room_id FROM bookings
                                                      WHERE status != 'cancelled'
                                                      AND check_in < ? 
                                                      AND check_out > ?
                                                  )
                                                LIMIT ?";
                        $stmt_available_rooms = $conn->prepare($sql_available_rooms);
                        if ($stmt_available_rooms) {
                            $stmt_available_rooms->bind_param("issi", $type_id, $check_out, $check_in, $num_rooms);
                            $stmt_available_rooms->execute();
                            $res_rooms = $stmt_available_rooms->get_result();

                            $available_room_ids = [];
                            while ($rr = $res_rooms->fetch_assoc()) {
                                $available_room_ids[] = (int)$rr['id'];
                            }

                            // Build preview
                            $preview_booking = [
                                'check_in' => $check_in,
                                'check_out' => $check_out,
                                'num_rooms' => $num_rooms,
                                'room_ids' => $available_room_ids,
                                'total_price' => $total_price,
                                'special_requests' => $special_requests,
                                'type_id' => $type_id,
                                'payment_method' => $payment_method,
                            ];
                            $show_confirmation = true;
                            $stmt_available_rooms->close();
                        } else {
                            $error_message = "Lỗi hệ thống (stmt_available_rooms): " . $conn->error;
                        }
                    }
                    $stmt_available->close();
                } else {
                    $error_message = "Lỗi hệ thống (stmt_available): " . $conn->error;
                }
            }
        }

// XỬ LÝ FORM - BƯỚC 2: XÁC NHẬN (confirm_book)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_book'])) {
    $confirm_check_in = trim($_POST['check_in'] ?? '');
    $confirm_check_out = trim($_POST['check_out'] ?? '');
    $confirm_num_rooms = max(1, (int)($_POST['num_rooms'] ?? 1));
    $confirm_total_price = (float)($_POST['total_price'] ?? 0);
    $confirm_special = trim($_POST['special_requests'] ?? '');
    $confirm_type_id = (int)($_POST['type_id'] ?? 0);
    $confirm_payment = trim($_POST['payment_method'] ?? 'cod');

    if (empty($confirm_check_in) || empty($confirm_check_out) || $confirm_type_id <= 0) {
        $error_message = "Dữ liệu xác nhận không hợp lệ.";
    } else {
        // Re-check availability and insert booking rows
        $sql_available_rooms = "SELECT id FROM rooms
                                WHERE type_id = ?
                                  AND status = 'available'
                                  AND id NOT IN (
                                      SELECT DISTINCT room_id FROM bookings
                                      WHERE status != 'cancelled'
                                      AND check_in < ? 
                                      AND check_out > ?
                                  )
                                LIMIT ?";
        $stmt_available_rooms = $conn->prepare($sql_available_rooms);
        if ($stmt_available_rooms) {
            $stmt_available_rooms->bind_param("issi", $confirm_type_id, $confirm_check_out, $confirm_check_in, $confirm_num_rooms);
            $stmt_available_rooms->execute();
            $res_rooms = $stmt_available_rooms->get_result();

            $available_room_ids = [];
            while ($rr = $res_rooms->fetch_assoc()) {
                $available_room_ids[] = (int)$rr['id'];
            }

            if (count($available_room_ids) < $confirm_num_rooms) {
                $error_message = "Rất tiếc, một số phòng đã được đặt trước khi bạn xác nhận. Vui lòng thử lại.";
            } else {
                // Xử lý thanh toán
                $payment_status = 'pending';
                $payment_error = '';
                
                if ($confirm_payment === 'vnpay') {
                    // Gọi VNPay API (tạm mock, thực tế cần tích hợp VNPay SDK)
                    $payment_status = 'pending'; // Sẽ callback từ VNPay
                    // Ví dụ: Tạo VNPay transaction
                    // $vnpay_url = createVNPayPaymentURL($confirm_total_price, ...);
                    // header("Location: " . $vnpay_url);
                } elseif ($confirm_payment === 'bank') {
                    $payment_status = 'pending'; // Chờ xác nhận chuyển khoản
                } else {
                    // COD - cash on arrival
                    $payment_status = 'pending'; // Chờ thanh toán khi nhận phòng
                }
                
                // Insert booking per room
                $success = true;

                $sql_insert = "INSERT INTO bookings (user_id, room_id, check_in, check_out, status, created_at)
                               VALUES (?, ?, ?, ?, ?, NOW())";
                $stmt_insert = $conn->prepare($sql_insert);
                if (!$stmt_insert) {
                    $error_message = "Lỗi hệ thống (stmt_insert prepare): " . $conn->error;
                    $success = false;
                } else {
                    foreach ($available_room_ids as $idx => $bid_room_id) {
                        $stmt_insert->bind_param("iisss", $user_id, $bid_room_id, $confirm_check_in, $confirm_check_out, $payment_status);
                        if (!$stmt_insert->execute()) {
                            $success = false;
                            $error_message = "Lỗi khi lưu đặt phòng: " . $stmt_insert->error;
                            break;
                        }
                    }
                    $stmt_insert->close();
                }

                if ($success) {
                    if ($confirm_payment === 'cod') {
                        $success_message = "✓ Đặt phòng thành công! Bạn sẽ thanh toán khi nhận phòng.";
                    } elseif ($confirm_payment === 'vnpay') {
                        $success_message = "✓ Đặt phòng thành công! Vui lòng hoàn tất thanh toán VNPay.";
                    } else {
                        $success_message = "✓ Đặt phòng thành công! Vui lòng chuyển khoản theo thông tin được gửi.";
                    }
                    // Clear preview so the form shows success area
                    $preview_booking = null;
                    $show_confirmation = false;
                }
            }
            $stmt_available_rooms->close();
        } else {
            $error_message = "Lỗi hệ thống (stmt_available_rooms prepare): " . $conn->error;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ĐẶT PHÒNG - THE CAPPA LUXURY HOTEL</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* (Giữ nguyên CSS từ bản trước, rút gọn để ngắn gọn) */
        :root { --color-primary:#524741; --color-secondary:#a38c71; --color-background:#f7f3ed; --color-white:#fff; }
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Roboto',sans-serif;color:var(--color-primary);background:var(--color-background);padding-top:60px}
        .navbar{padding:15px 30px;display:flex;justify-content:space-between;align-items:center;position:fixed;width:100%;top:0;background:rgba(0,0,0,0.15);height:60px;z-index:1000;transition:all .3s}
        .navbar.scrolled{background:var(--color-white);box-shadow:0 2px 10px rgba(0,0,0,.1)}
        .logo a{font-family:'Lora',serif;font-size:1.2rem;color:var(--color-white);text-decoration:none}
        .navbar.scrolled .logo a{color:var(--color-primary)}
        .nav-links{display:flex;align-items:center;margin-left:auto}
        .nav-links a{color:var(--color-white);margin-left:20px;text-decoration:none;font-weight:500;transition:all .2s}
        .nav-links a:hover{color:var(--color-secondary)}
        .navbar.scrolled .nav-links a{color:var(--color-primary)}
        .user-menu{position:relative;margin-left:25px}
        .user-icon{font-size:1.8em;color:var(--color-white);cursor:pointer;transition:all .3s}
        .navbar.scrolled .user-icon{color:var(--color-primary)}
        .user-icon:hover{transform:scale(1.1)}
        .dropdown-content{display:none;position:absolute;right:0;background:var(--color-white);min-width:180px;box-shadow:0px 8px 16px rgba(0,0,0,0.2);z-index:10;border-radius:8px;opacity:0;transform:translateY(10px);transition:all .3s}
        .dropdown-content.show{display:block;opacity:1;transform:translateY(0)}
        .dropdown-content a{color:var(--color-primary);padding:12px 16px;text-decoration:none;display:block;font-weight:500;transition:all .2s}
        .dropdown-content a:hover{background:#f1f1f1;color:var(--color-secondary);padding-left:20px}
        .booking-container{max-width:1200px;margin:80px auto;padding:20px}
        .booking-header{text-align:center;margin-bottom:30px}
        .booking-header h1{font-family:'Lora',serif;font-size:2.4rem}
        .booking-content{display:grid;grid-template-columns:2fr 1fr;gap:30px}
        .booking-form{background:linear-gradient(135deg,#fafafa,var(--color-white));padding:30px;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.06)}
        .form-group{margin-bottom:16px}
        label{display:block;margin-bottom:8px;font-weight:600}
        input,textarea,select{width:100%;padding:10px;border-radius:8px;border:1px solid #e6e6e6}
        .btn-book{display:inline-block;padding:14px 20px;background:linear-gradient(135deg,var(--color-secondary),var(--color-primary));color:#fff;border:0;border-radius:12px;font-weight:700;cursor:pointer}
        .btn-back{display:inline-block;padding:12px 20px;background:#f5f5f5;color:var(--color-primary);border:none;border-radius:10px;font-weight:600;cursor:pointer;text-decoration:none;margin-bottom:20px;transition:all .3s}
        .btn-back:hover{background:var(--color-secondary);color:var(--color-white)}
        .room-summary{background:linear-gradient(135deg,#fafafa,var(--color-white));padding:20px;border-radius:14px}
        .alert{padding:14px;border-radius:10px;margin-bottom:18px}
        .alert-error{background:#ffecec;color:#c00}
        .alert-success{background:#eaffea;color:#0a0}
        @media(max-width:768px){.booking-content{grid-template-columns:1fr}}
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo"><a href="index.php">THE CAPPA LUXURY HOTEL</a></div>
        <div class="nav-links">
            <a href="index.php">TRANG CHỦ</a>
            <a href="about.php">GIỚI THIỆU</a>
            <a href="rooms.php">PHÒNG & GIÁ</a>
            <a href="index.php#services">DỊCH VỤ</a>
            <a href="contact.php">LIÊN HỆ</a>
            <div class="user-menu">
            <div class="user-menu">
                <i class="fas fa-user-circle user-icon" id="userIcon"></i>
                <div class="dropdown-content">
                    <a href="profile.php">Thông tin cá nhân</a>
                    <a href="dashboard.php">Đơn đặt phòng</a>
                    <a href="logout.php" style="color:#dc3545;">Đăng xuất</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="booking-container">
        <a href="room_detail.php?room_id=<?php echo $room_id; ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i> QUAY LẠI
        </a>
        <div class="booking-header">
            <h1>ĐẶT PHÒNG</h1>
            <p>Chọn ngày và hoàn tất thông tin để đặt phòng tại The Cappa Luxury Hotel</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <div class="booking-content">
            <!-- LEFT: Form / Preview -->
            <div>
                <?php if ($show_confirmation && !empty($preview_booking)): ?>
                    <!-- PREVIEW + CONFIRM -->
                    <div class="booking-form">
                        <h2 style="margin-bottom:12px">Xác nhận đặt phòng</h2>
                        <p>Kiểm tra lại thông tin trước khi xác nhận.</p>

                        <div style="margin-top:16px;padding:16px;background:#f5f5f5;border-radius:8px;border-left:4px solid var(--color-secondary)">
                            <h3 style="color:var(--color-primary);margin-bottom:12px">Chi tiết đặt phòng</h3>
                            
                            <div class="form-group"><strong>Phòng:</strong> <?php echo htmlspecialchars($room_data['name']); ?></div>
                            <div class="form-group"><strong>Ngày nhận:</strong> <?php echo htmlspecialchars($preview_booking['check_in']); ?></div>
                            <div class="form-group"><strong>Ngày trả:</strong> <?php echo htmlspecialchars($preview_booking['check_out']); ?></div>
                            
                            <?php 
                            $check_in_ts = strtotime($preview_booking['check_in']);
                            $check_out_ts = strtotime($preview_booking['check_out']);
                            $nights = ceil(($check_out_ts - $check_in_ts) / 86400);
                            ?>
                            
                            <div style="padding:12px;background:#fff;border-radius:6px;margin:12px 0;border:1px solid #e0e0e0">
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                                    <span>Số đêm:</span>
                                    <strong><?php echo $nights; ?></strong>
                                </div>
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                                    <span>Số phòng:</span>
                                    <strong><?php echo (int)$preview_booking['num_rooms']; ?></strong>
                                </div>
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                                    <span>Giá/đêm:</span>
                                    <strong><?php echo format_currency($room_data['price']); ?></strong>
                                </div>
                                <hr style="border:none;border-top:1px solid #e0e0e0;margin:8px 0">
                                <div style="display:flex;justify-content:space-between;font-size:1.2em;color:var(--color-secondary)">
                                    <strong>Tổng cộng:</strong>
                                    <strong><?php echo format_currency($preview_booking['total_price']); ?></strong>
                                </div>
                            </div>

                            <div class="form-group"><strong>Phương thức thanh toán:</strong> 
                                <?php 
                                $payment_labels = [
                                    'cod' => '💵 Thanh toán tại quán',
                                    'vnpay' => '📱 VNPay - Ví điện tử',
                                    'bank' => '🏦 Chuyển khoản ngân hàng'
                                ];
                                echo $payment_labels[$preview_booking['payment_method']] ?? 'N/A';
                                ?>
                            </div>

                            <?php if (!empty($preview_booking['special_requests'])): ?>
                                <div class="form-group"><strong>Yêu cầu đặc biệt:</strong> <?php echo htmlspecialchars($preview_booking['special_requests']); ?></div>
                            <?php endif; ?>
                        </div>

                        <form method="post" style="margin-top:18px">
                            <input type="hidden" name="check_in" value="<?php echo htmlspecialchars($preview_booking['check_in']); ?>">
                            <input type="hidden" name="check_out" value="<?php echo htmlspecialchars($preview_booking['check_out']); ?>">
                            <input type="hidden" name="num_rooms" value="<?php echo (int)$preview_booking['num_rooms']; ?>">
                            <input type="hidden" name="total_price" value="<?php echo (float)$preview_booking['total_price']; ?>">
                            <input type="hidden" name="special_requests" value="<?php echo htmlspecialchars($preview_booking['special_requests']); ?>">
                            <input type="hidden" name="type_id" value="<?php echo (int)$preview_booking['type_id']; ?>">
                            <input type="hidden" name="payment_method" value="<?php echo htmlspecialchars($preview_booking['payment_method']); ?>">
                            
                            <div style="display:flex;gap:12px">
                                <button type="submit" name="confirm_book" class="btn-book" style="flex:1"><i class="fas fa-check"></i> XÁC NHẬN & THANH TOÁN</button>
                                <a href="booking.php?room_id=<?php echo $room_id; ?>" style="flex:1;display:flex;align-items:center;justify-content:center;padding:12px 18px;border-radius:10px;background:#eee;color:#333;text-decoration:none;font-weight:600">Chỉnh sửa</a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- MAIN BOOKING FORM -->
                    <form method="post" class="booking-form" onsubmit="return validateDates();">
                        <h2 style="margin-bottom:12px">Thông tin đặt phòng</h2>

                        <input type="hidden" name="room_id" value="<?php echo (int)$room_id; ?>">
                        <div class="form-group">
                            <label>Ngày nhận phòng <span style="color:red">*</span></label>
                            <input type="date" name="check_in" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Ngày trả phòng <span style="color:red">*</span></label>
                            <input type="date" name="check_out" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                        </div>

                        <div class="form-group">
                            <label>Số lượng phòng</label>
                            <input type="number" name="num_rooms" value="1" min="1" max="10" required onchange="updateTotal()">
                        </div>

                        <div class="form-group">
                            <label>Yêu cầu đặc biệt (không bắt buộc)</label>
                            <textarea name="special_requests" placeholder="Ghi chú..."></textarea>
                        </div>

                        <div class="form-group">
                            <label>Phương thức thanh toán <span style="color:red">*</span></label>
                            <select name="payment_method" required style="padding: 12px;">
                                <option value="cod">💵 Thanh toán tại quán (COD)</option>
                                <option value="vnpay">📱 VNPay - Ví điện tử</option>
                                <option value="bank">🏦 Chuyển khoản ngân hàng</option>
                            </select>
                        </div>

                        <input type="hidden" name="total_price" id="total_price" value="0">
                        <button type="submit" name="book_room" class="btn-book"><i class="fas fa-search"></i> XEM & XÁC NHẬN</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- RIGHT: Room Summary -->
            <div class="room-summary">
                <h3>Tóm tắt phòng</h3>
                <?php if ($room_data): ?>
                    <div style="margin:14px 0">
                        <div style="overflow:hidden;border-radius:8px;height:180px;margin-bottom:12px">
                            <img src="<?php echo htmlspecialchars($room_data['img']); ?>" alt="<?php echo htmlspecialchars($room_data['name']); ?>" style="width:100%;height:100%;object-fit:cover">
                        </div>
                        <div style="margin-bottom:8px"><strong><?php echo htmlspecialchars($room_data['name']); ?></strong></div>
                        <div style="color:#666;margin-bottom:12px">Giá/đêm: <strong><?php echo format_currency($room_data['price']); ?></strong></div>

                        <div style="padding:12px;background:#fafafa;border-radius:8px">
                            <div style="display:flex;justify-content:space-between;margin-bottom:8px"><span>Số đêm</span><span id="num_nights">0</span></div>
                            <div style="display:flex;justify-content:space-between;margin-bottom:8px"><span>Số phòng</span><span id="display_num_rooms">1</span></div>
                            <div style="display:flex;justify-content:space-between;"><strong>Tổng</strong><strong id="display_total"><?php echo format_currency(0); ?></strong></div>
                        </div>
                    </div>
                <?php else: ?>
                    <p>Không có dữ liệu phòng. Vui lòng chọn phòng từ trang <a href="rooms.php">PHÒNG & GIÁ</a>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer style="padding:40px 20px;text-align:center;background:#111;color:#fff;margin-top:40px">
        <div style="max-width:1200px;margin:0 auto">
            <p style="margin-bottom:6px">THE CAPPA LUXURY HOTEL</p>
            <small>Copyright © <?php echo date("Y"); ?> All rights reserved.</small>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const mainNav = document.getElementById('mainNav') || document.querySelector('.navbar');
            const userIcon = document.getElementById('userIcon');
            const dropdown = document.querySelector('.dropdown-content');

            // Navbar scroll effect
            if (mainNav) {
                window.addEventListener('scroll', function() {
                    if (window.scrollY > 100) {
                        mainNav.classList.add('scrolled');
                    } else {
                        mainNav.classList.remove('scrolled');
                    }
                });
            }

            // Dropdown menu
            if (userIcon && dropdown) {
                userIcon.addEventListener('click', function(e) {
                    dropdown.classList.toggle('show');
                    e.stopPropagation();
                });

                document.addEventListener('click', function(e) {
                    if (!userIcon.closest('.user-menu').contains(e.target)) {
                        dropdown.classList.remove('show');
                    }
                });
            }

            // Validation & update total
            const dateInputs = document.querySelectorAll('input[name="check_in"], input[name="check_out"]');
            dateInputs.forEach(i => i.addEventListener('change', updateTotal));
            const numRoomsInput = document.querySelector('input[name="num_rooms"]');
            if (numRoomsInput) numRoomsInput.addEventListener('change', updateTotal);
            updateTotal();
        });

        function validateDates(){
            const inDate = document.querySelector('input[name="check_in"]').value;
            const outDate = document.querySelector('input[name="check_out"]').value;
            if (!inDate || !outDate) {
                alert('Vui lòng chọn cả ngày nhận và ngày trả.');
                return false;
            }
            if (new Date(inDate) >= new Date(outDate)) {
                alert('Ngày trả phải sau ngày nhận.');
                return false;
            }
            return true;
        }

        function updateTotal(){
            const checkInInput = document.querySelector('input[name="check_in"]');
            const checkOutInput = document.querySelector('input[name="check_out"]');
            const numRoomsInput = document.querySelector('input[name="num_rooms"]');
            const totalPriceInput = document.getElementById('total_price');

            const pricePerRoom = <?php echo $room_data['price'] ?? 0; ?>;

            if (!checkInInput || !checkOutInput || !numRoomsInput) return;

            const checkIn = checkInInput.value;
            const checkOut = checkOutInput.value;
            const numRooms = parseInt(numRoomsInput.value) || 1;

            if (checkIn && checkOut) {
                const d1 = new Date(checkIn);
                const d2 = new Date(checkOut);
                const diffTime = d2 - d1;
                const numNights = Math.max(0, Math.ceil(diffTime / (1000*60*60*24)));
                const total = numNights * numRooms * pricePerRoom;

                document.getElementById('num_nights').textContent = numNights;
                document.getElementById('display_num_rooms').textContent = numRooms;
                document.getElementById('display_total').textContent = new Intl.NumberFormat('vi-VN').format(total) + ' VNĐ';
                totalPriceInput.value = total;
            }
        }
    </script>
</body>
</html>
