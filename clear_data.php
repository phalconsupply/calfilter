<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$action = $_GET['action'] ?? '';

if ($action === 'clear') {
    clearAllData();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action. Use ?action=clear']);
}

function clearAllData() {
    if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng xác nhận xóa dữ liệu bằng cách thêm &confirm=yes vào URL',
            'warning' => 'Lệnh này sẽ XÓA TOÀN BỘ dữ liệu trong bảng hospital_admissions!'
        ]);
        return;
    }
    
    $conn = getDbConnection();
    
    try {
        // Get current count
        $countResult = $conn->query("SELECT COUNT(*) as total FROM hospital_admissions");
        $currentCount = $countResult->fetch_assoc()['total'];
        
        // Truncate table
        $conn->query("TRUNCATE TABLE hospital_admissions");
        
        $conn->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa thành công toàn bộ dữ liệu',
            'deleted_records' => $currentCount
        ]);
        
    } catch (Exception $e) {
        $conn->close();
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ]);
    }
}
