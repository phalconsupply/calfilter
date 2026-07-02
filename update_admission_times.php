<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';
require_once 'admission_rules.php';

// Update all admission time types based on current calendar settings
function updateAdmissionTimes() {
    $conn = getDbConnection();

    try {
        // Nạp lịch (ngày nghỉ + ngày làm bù) một lần
        $calendarMap = loadCalendarMap($conn);
        
        // Get all admission records
        $sql = "SELECT id, admission_datetime FROM hospital_admissions WHERE admission_datetime IS NOT NULL";
        $result = $conn->query($sql);
        
        if (!$result) {
            throw new Exception("Error fetching admissions: " . $conn->error);
        }
        
        $updated = 0;
        $failed = 0;
        $updateStmt = $conn->prepare("UPDATE hospital_admissions SET admission_time_type = ? WHERE id = ?");
        
        while ($row = $result->fetch_assoc()) {
            $id = $row['id'];
            $datetimeStr = $row['admission_datetime'];
            
            // Calculate new admission time type
            $timeType = classifyAdmissionTimeType($datetimeStr, $calendarMap);
            
            // Update record
            $updateStmt->bind_param("si", $timeType, $id);
            if ($updateStmt->execute()) {
                $updated++;
            } else {
                $failed++;
            }
        }
        
        $updateStmt->close();
        $conn->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật dữ liệu thành công',
            'updated' => $updated,
            'failed' => $failed
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ]);
    }
}

// Handle the request
updateAdmissionTimes();
?>
