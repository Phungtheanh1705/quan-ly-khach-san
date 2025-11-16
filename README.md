# 🏨 Hệ thống Quản lý Khách sạn (Hotel Management System)

Hệ thống quản lý khách sạn trên nền tảng web, được xây dựng bằng **PHP thuần** và **MySQL**. Dự án cung cấp giao diện quản trị viên (Admin Panel) toàn diện để quản lý phòng, đặt phòng, thanh toán và báo cáo doanh thu.

## 🚀 Tính năng nổi bật

### 1. Dashboard (Bảng điều khiển)
- Hiển thị tổng quan: Tổng số phòng, booking, khách hàng và tổng doanh thu.
- Danh sách các lượt đặt phòng gần đây.
- Bộ lọc tìm kiếm booking nâng cao (theo tên, trạng thái, ngày tháng).

### 2. Quản lý Phòng (Rooms Management)
- **Loại phòng:** Quản lý các hạng phòng (Deluxe, Standard, Suite...) với giá và mô tả chi tiết.
- **Danh sách phòng:** Quản lý trạng thái từng phòng cụ thể (Trống, Đang ở, Đã đặt trước).

### 3. Quản lý Đặt phòng (Bookings)
- Theo dõi quy trình đặt phòng: `Chờ xử lý` -> `Đã xác nhận` -> `Đã nhận phòng` -> `Đã trả phòng` -> `Hủy`.
- Xem chi tiết thông tin khách hàng và lịch sử đặt.

### 4. Quản lý Thanh toán (Payments)
- Theo dõi lịch sử giao dịch, phương thức thanh toán (Tiền mặt, Chuyển khoản).
- **Chức năng Hoàn tiền (Refund):** Cập nhật trạng thái giao dịch khi hoàn tiền cho khách.
- **Xóa giao dịch:** Xóa các giao dịch rác hoặc sai sót (có cảnh báo).

### 5. Báo cáo & Thống kê (Reports)
- **Biểu đồ Doanh thu:** Sử dụng **Chart.js** để vẽ biểu đồ biến động doanh thu theo từng tháng.
- **Top Phòng:** Thống kê các loại phòng được đặt nhiều nhất (Best Seller).
- **Xuất Excel:** Tính năng xuất báo cáo doanh thu ra file Excel (.xls) để lưu trữ offline.

---

## 🛠️ Công nghệ sử dụng

* **Backend:** PHP 8.x (Native), MySQL (MySQLi).
* **Frontend:** HTML5, CSS3, Bootstrap 5.3.
* **JavaScript:** Chart.js (Biểu đồ), FontAwesome 6 (Icons).
* **Server:** Apache (XAMPP / Laragon).

---

## ⚙️ Hướng dẫn Cài đặt

### Bước 1: Chuẩn bị môi trường
1. Cài đặt **XAMPP** (hoặc WAMP/Laragon).
2. Khởi động module **Apache** và **MySQL**.

### Bước 2: Cấu hình Mã nguồn
1. Tải source code về máy.
2. Giải nén và copy thư mục dự án vào thư mục `htdocs` của XAMPP.
   * Đường dẫn ví dụ: `C:\xampp\htdocs\hotel_management`

### Bước 3: Cấu hình Cơ sở dữ liệu (Database)
1. Truy cập `http://localhost/phpmyadmin`.
2. Tạo một database mới tên là: `hotel_management`.
3. Nhập (Import) file `database.sql` vào database vừa tạo (File này chứa cấu trúc bảng users, rooms, bookings...).
4. Mở file `config/db.php` trong dự án và kiểm tra thông tin kết nối:
   ```php
   $servername = "localhost";
   $username = "root"; // Mặc định của XAMPP
   $password = "";     // Mặc định để trống
   $dbname = "hotel_management";
### Bước 4: Bb
-- ===========================
-- 0. XÓA DATABASE CŨ (nếu có)
-- ===========================
DROP DATABASE IF EXISTS hotel_db;

-- ===========================
-- 1. TẠO DATABASE
-- ===========================
CREATE DATABASE hotel_db;
USE hotel_db;

-- ===========================
-- 2. BẢNG USERS (Người dùng)
-- ===========================
	CREATE TABLE users (
		id INT AUTO_INCREMENT PRIMARY KEY,
		username VARCHAR(50) NOT NULL UNIQUE,
		password_hash VARCHAR(255) NOT NULL,
		role ENUM('admin','user') NOT NULL DEFAULT 'user',
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
	);

-- Thêm cột email
ALTER TABLE users ADD COLUMN email VARCHAR(100) NULL AFTER username;

-- Thêm cột phone
ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER email;

-- Thêm cột address (địa chỉ)
ALTER TABLE users ADD COLUMN address VARCHAR(255) NULL AFTER phone;

	-- Dữ liệu mẫu (password được hash bằng password_hash())
	INSERT INTO users (username, password_hash, role) VALUES
	('admin', '$2y$10$ggz7cUiBOLXThj677uoYd.lpxuj684yxnKgkQjuyD8i/quub5slxO', 'admin'),
	('user1', '$2y$10$YIjlrDflS5XQeaYMTps6O.Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5', 'user'),
	('user2', '$2y$10$YIjlrDflS5XQeaYMTps6O.Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5', 'user'),
	('user3', '$2y$10$YIjlrDflS5XQeaYMTps6O.Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5Z5', 'user');
-- Thêm cột full_name (Tên đầy đủ của người dùng)
ALTER TABLE users ADD COLUMN full_name VARCHAR(150) NULL AFTER email;
ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role;

-- ===========================
-- 3. BẢNG ROOM TYPES (Loại phòng)
-- ===========================
CREATE TABLE room_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL,
    short_description VARCHAR(255) NULL, 
    full_description TEXT NULL,
    max_guests INT NOT NULL DEFAULT 2,
    area_sqm INT NOT NULL,
    image_path VARCHAR(255) NOT NULL 
);
-- Thêm cột price_per_night vào bảng room_types
ALTER TABLE room_types ADD COLUMN price_per_night DECIMAL(10, 2) NOT NULL DEFAULT 0.00 AFTER area_sqm;

-- Dữ liệu mẫu loại phòng (9 loại để hiển thị 2 trang)
INSERT INTO room_types (type_name, short_description, full_description, max_guests, area_sqm, image_path) VALUES
('Deluxe Double', 'Phòng Deluxe rộng rãi, có cửa sổ lớn.', 'Phòng Deluxe rộng rãi, có cửa sổ lớn nhìn ra phố, trang bị giường King size thoải mái, phòng tắm sang trọng.', 2, 35, 'https://thecapphotel.webhotel.vn/files/images/Room/5.jpg'),
('Suite View Hồ Tây', 'Căn Suite sang trọng với phòng khách riêng.', 'Căn Suite sang trọng với phòng khách riêng biệt, bồn tắm jacuzzi, ban công hướng hồ thơ mộng.', 2, 60, 'https://thecapphotel.webhotel.vn/files/images/Room/7.jpg'),
('Executive Twin', 'Thiết kế hiện đại, hai giường đơn lớn.', 'Thiết kế hiện đại, bao gồm hai giường đơn lớn, phù hợp cho bạn bè hoặc đồng nghiệp.', 2, 40, 'https://thecapphotel.webhotel.vn/files/images/Room/5.jpg'),
('Presidential Penthouse', 'Trải nghiệm đỉnh cao của sự xa hoa.', 'Trải nghiệm đỉnh cao của sự xa hoa với tầm nhìn toàn cảnh thành phố và dịch vụ quản gia riêng.', 4, 120, 'https://thecapphotel.webhotel.vn/files/images/Room/7.jpg'),
('Junior Suite', 'Phòng ngủ thoáng mát, sang trọng.', 'Phòng ngủ thoáng mát kết hợp phòng khách nhỏ, với đồ nội thất hiện đại và tiện nghi đầy đủ.', 2, 50, 'https://thecapphotel.webhotel.vn/files/images/Room/5.jpg'),
('Family Room', 'Phòng rộng phù hợp cho gia đình.', 'Phòng rộng rãi với 2-3 giường, phòng khách riêng, phù hợp cho gia đình 3-4 người.', 4, 70, 'https://thecapphotel.webhotel.vn/files/images/Room/7.jpg'),
('Deluxe Studio', 'Studio hiện đại, tiện lợi.', 'Studio hiện đại với khu vực làm việc, phòng ngủ và phòng khách kết hợp, lý tưởng cho du khách công tác.', 2, 45, 'https://thecapphotel.webhotel.vn/files/images/Room/5.jpg'),
('Standard Room', 'Phòng chuẩn, tiện nghi cơ bản.', 'Phòng chuẩn với tiện nghi cơ bản, giường ngủ thoải mái, phòng tắm với sen nước nóng lạnh.', 2, 28, 'https://thecapphotel.webhotel.vn/files/images/Room/7.jpg'),
('Superior Twin', 'Phòng Twin cao cấp với 2 giường.', 'Phòng Twin cao cấp với 2 giường đơn rộng rãi, view đẹp, dịch vụ 24/7.', 2, 38, 'https://thecapphotel.webhotel.vn/files/images/Room/5.jpg');
-- Dán và chạy lại các lệnh UPDATE này trong phpMyAdmin hoặc công cụ quản lý DB của bạn
-- Cập nhật giá bằng ID (ID có thể thay đổi tùy thuộc vào thứ tự INSERT của bạn)
UPDATE room_types SET price_per_night = 1800000 WHERE id = 1; -- Deluxe Double
UPDATE room_types SET price_per_night = 4500000 WHERE id = 2; -- Suite View Hồ Tây
UPDATE room_types SET price_per_night = 2200000 WHERE id = 3; -- Executive Twin
UPDATE room_types SET price_per_night = 15000000 WHERE id = 4; -- Presidential Penthouse
UPDATE room_types SET price_per_night = 3000000 WHERE id = 5; -- Junior Suite
UPDATE room_types SET price_per_night = 4000000 WHERE id = 6; -- Family Room
UPDATE room_types SET price_per_night = 2500000 WHERE id = 7; -- Deluxe Studio
UPDATE room_types SET price_per_night = 1200000 WHERE id = 8; -- Standard Room
UPDATE room_types SET price_per_night = 1600000 WHERE id = 9; -- Superior Twin

-- ===========================
-- 4. BẢNG ROOMS (Phòng cụ thể)
-- ===========================
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL UNIQUE,
    type_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    status ENUM('available','booked','maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (type_id) REFERENCES room_types(id) ON DELETE CASCADE
);

-- Dữ liệu mẫu phòng (mỗi loại phòng 2-3 phòng)
INSERT INTO rooms (room_number, type_id, price, status) VALUES
('101', 1, 2500000, 'available'), -- Deluxe Double
('102', 1, 2500000, 'available'),
('103', 2, 4800000, 'available'), -- Suite View
('104', 2, 4800000, 'booked'),
('105', 3, 3200000, 'available'), -- Executive Twin
('106', 3, 3200000, 'available'),
('107', 4, 15000000, 'available'), -- Presidential
('108', 5, 3500000, 'available'), -- Junior Suite
('109', 5, 3500000, 'available'),
('110', 6, 5000000, 'available'), -- Family Room
('111', 6, 5000000, 'booked'),
('112', 7, 3800000, 'available'), -- Deluxe Studio
('113', 7, 3800000, 'available'),
('114', 8, 1800000, 'available'), -- Standard Room
('115', 8, 1800000, 'available'),
('116', 9, 2800000, 'available'); -- Superior Twin

-- ===========================
-- 5. BẢNG BOOKINGS
-- ===========================
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    room_id INT,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    status ENUM('pending','confirmed','checked_in','checked_out','cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50) DEFAULT 'cod',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
);
-- Thêm cột guest_count vào bảng bookings (Nếu chưa tồn tại)
ALTER TABLE bookings ADD COLUMN guest_count INT NOT NULL DEFAULT 1 AFTER room_id;
-- Dữ liệu mẫu booking
INSERT INTO bookings (user_id, room_id, check_in, check_out, status, payment_method) VALUES
(2, 1, '2025-11-20', '2025-11-22', 'confirmed', 'cod'),
(3, 4, '2025-11-18', '2025-11-20', 'cancelled', 'cod'),
(2, 5, '2025-11-25', '2025-11-27', 'pending', 'vnpay'),
(3, 7, '2025-12-01', '2025-12-05', 'confirmed', 'bank');
ALTER TABLE bookings
ADD COLUMN total_price DECIMAL(10, 2) DEFAULT 0.00 AFTER check_out;
-- ===========================
-- 6. BẢNG PAYMENTS
-- ===========================
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','banking','creditcard') DEFAULT 'cash',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);
-- Thêm cột transaction_id (Mã giao dịch)
ALTER TABLE payments ADD COLUMN transaction_id VARCHAR(255) NULL AFTER payment_method;

-- Thêm cột status (Trạng thái thanh toán)
ALTER TABLE payments ADD COLUMN status ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending' AFTER transaction_id;
ALTER TABLE payments 
MODIFY COLUMN status ENUM('pending', 'completed', 'failed', 'refunded') 
NOT NULL DEFAULT 'pending';
-- Dữ liệu mẫu thanh toán
INSERT INTO payments (booking_id, amount, payment_method) VALUES
(1, 5000000, 'cash'),
(4, 60000000, 'banking');

-- ===============================================
-- 7. BẢNG ROOM_AMENITIES
-- ===============================================
CREATE TABLE `room_amenities` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `amenity_name` VARCHAR(100) NOT NULL UNIQUE,
    `icon_class` VARCHAR(50) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dữ liệu mẫu tiện nghi
INSERT INTO `room_amenities` (`amenity_name`, `icon_class`) VALUES
('Máy lạnh', 'fas fa-wind'),
('TV màn hình phẳng', 'fas fa-tv'),
('Wifi miễn phí', 'fas fa-wifi'),
('Minibar', 'fas fa-glass-martini'),
('Bồn tắm', 'fas fa-bath'),
('Ban công riêng', 'fas fa-umbrella-beach'),
('Tủ an toàn', 'fas fa-lock'),
('Điện thoại', 'fas fa-phone'),
('Máy sấy tóc', 'fas fa-fan'),
('Quần áo tắm', 'fas fa-swimming-pool');

-- =======================================================
-- 8. BẢNG ROOM_TYPE_AMENITIES
-- =======================================================
CREATE TABLE `room_type_amenities` (
    `type_id` INT(11) UNSIGNED NOT NULL,
    `amenity_id` INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY (`type_id`, `amenity_id`),
    FOREIGN KEY (`type_id`) REFERENCES `room_types`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`amenity_id`) REFERENCES `room_amenities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dữ liệu liên kết mẫu
INSERT INTO `room_type_amenities` (`type_id`, `amenity_id`) VALUES
-- Deluxe Double (ID 1)
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 7),
-- Suite View (ID 2)
(2, 1), (2, 2), (2, 3), (2, 4), (2, 5), (2, 6), (2, 7), (2, 8),
-- Executive Twin (ID 3)
(3, 1), (3, 2), (3, 3), (3, 4), (3, 7), (3, 8),
-- Presidential (ID 4)
(4, 1), (4, 2), (4, 3), (4, 4), (4, 5), (4, 6), (4, 7), (4, 8), (4, 9), (4, 10),
-- Junior Suite (ID 5)
(5, 1), (5, 2), (5, 3), (5, 4), (5, 7), (5, 8),
-- Family Room (ID 6)
(6, 1), (6, 2), (6, 3), (6, 5), (6, 7), (6, 8),
-- Deluxe Studio (ID 7)
(7, 1), (7, 2), (7, 3), (7, 4), (7, 7), (7, 8), (7, 9),
-- Standard Room (ID 8)
(8, 1), (8, 2), (8, 3), (8, 5), (8, 8),
-- Superior Twin (ID 9)
(9, 1), (9, 2), (9, 3), (9, 4), (9, 7), (9, 8), (9, 9);

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL
);


### Bước 5: Chạy dự án
1. Mở trình duyệt và truy cập đường dẫn: `http://localhost/hotel_management/admin/index.php`
