<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
include "../../config/db.php";

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$filename = "Bao_cao_doanh_thu_" . $year . ".xls";

// Header để tải file
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Lấy dữ liệu
$sql = "SELECT MONTH(payment_date) as month, COUNT(id) as count, SUM(amount) as total 
        FROM payments 
        WHERE YEAR(payment_date) = ? AND status = 'completed' 
        GROUP BY MONTH(payment_date) ORDER BY month ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $year);
$stmt->execute();
$result = $stmt->get_result();

echo "\xEF\xBB\xBF"; // BOM for UTF-8
?>
<table border="1">
    <thead>
        <tr style="background-color:#0d6efd; color:white;">
            <th>Tháng</th>
            <th>Số lượng Giao dịch</th>
            <th>Doanh thu (VNĐ)</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td>Tháng <?= $row['month'] ?></td>
            <td><?= $row['count'] ?></td>
            <td><?= number_format($row['total'], 0, ',', '.') ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<?php
$stmt->close();
$conn->close();
exit();
?>