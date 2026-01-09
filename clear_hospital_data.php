<?php
require_once 'config.php';

// Clear all data from hospital_admissions table
$conn = getDbConnection();

$sql = "TRUNCATE TABLE hospital_admissions";

if ($conn->query($sql) === TRUE) {
    echo "✅ Đã xóa toàn bộ dữ liệu trong bảng hospital_admissions thành công!<br>";
    echo "Bảng đã được reset về trạng thái rỗng.<br><br>";
    echo "<a href='hospital_import.html'>← Quay lại trang import</a>";
} else {
    echo "❌ Lỗi khi xóa dữ liệu: " . $conn->error;
}

$conn->close();
?>
