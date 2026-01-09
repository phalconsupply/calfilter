<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_days_off':
        getDaysOff();
        break;
    case 'save_day_off':
        saveDayOff();
        break;
    case 'save_multiple_days_off':
        saveMultipleDaysOff();
        break;
    case 'remove_day_off':
        removeDayOff();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
}

function getDaysOff() {
    $month = $_GET['month'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');
    
    $conn = getDbConnection();
    
    $startDate = "$year-$month-01";
    $endDate = date("Y-m-t", strtotime($startDate));
    
    $sql = "SELECT day_date, note, type FROM calendar_days_off 
            WHERE day_date BETWEEN ? AND ?
            ORDER BY day_date";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $daysOff = [];
    while ($row = $result->fetch_assoc()) {
        $daysOff[] = [
            'date' => $row['day_date'],
            'note' => $row['note'],
            'type' => $row['type'] ?? 'day_off'
        ];
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode(['success' => true, 'data' => $daysOff]);
}

function saveDayOff() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['date'])) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin ngày']);
        return;
    }
    
    $date = $data['date'];
    $note = $data['note'] ?? '';
    $type = $data['type'] ?? 'day_off';
    
    $conn = getDbConnection();
    
    // Check if date already exists
    $checkSql = "SELECT id FROM calendar_days_off WHERE day_date = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $date);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        // Update existing record
        $sql = "UPDATE calendar_days_off SET note = ?, type = ? WHERE day_date = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $note, $type, $date);
    } else {
        // Insert new record
        $sql = "INSERT INTO calendar_days_off (day_date, note, type) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $date, $note, $type);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Đã lưu ngày nghỉ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu: ' . $conn->error]);
    }
    
    $checkStmt->close();
    $stmt->close();
    $conn->close();
}

function saveMultipleDaysOff() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['dates']) || !is_array($data['dates']) || empty($data['dates'])) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin ngày']);
        return;
    }
    
    $dates = $data['dates'];
    $note = $data['note'] ?? '';
    $type = $data['type'] ?? 'day_off';
    
    $conn = getDbConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        $sql = "INSERT INTO calendar_days_off (day_date, note, type) VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE note = ?, type = ?";
        $stmt = $conn->prepare($sql);
        
        $successCount = 0;
        foreach ($dates as $date) {
            $stmt->bind_param("sssss", $date, $note, $type, $note, $type);
            if ($stmt->execute()) {
                $successCount++;
            }
        }
        
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        $conn->close();
        
        echo json_encode([
            'success' => true, 
            'message' => "Đã lưu $successCount ngày nghỉ",
            'count' => $successCount
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu: ' . $e->getMessage()]);
    }
}

function removeDayOff() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['date'])) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin ngày']);
        return;
    }
    
    $date = $data['date'];
    
    $conn = getDbConnection();
    
    $sql = "DELETE FROM calendar_days_off WHERE day_date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Đã xóa ngày nghỉ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa: ' . $conn->error]);
    }
    
    $stmt->close();
    $conn->close();
}
?>
