<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

// Update all admission time types based on current calendar settings
function updateAdmissionTimes() {
    $conn = getDbConnection();
    
    try {
        // Get all days off from calendar
        $daysOffMap = getDaysOffMap($conn);
        
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
            $timeType = calculateAdmissionTimeType($datetimeStr, $daysOffMap);
            
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

function getDaysOffMap($conn) {
    // Get all days off from calendar regardless of month
    $sql = "SELECT day_date, type FROM calendar_days_off WHERE type = 'day_off'";
    
    $result = $conn->query($sql);
    
    $daysOffMap = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $daysOffMap[$row['day_date']] = true;
        }
    }
    
    return $daysOffMap;
}

function calculateAdmissionTimeType($datetimeStr, $daysOffMap) {
    // Rules for "Ngoài giờ" (Off-hours):
    // 1. Weekend: Saturday (6) or Sunday (0)
    // 2. Before work hours: < 7:00 AM
    // 3. Lunch break: 11:30 AM - 1:29 PM (11:30:00 - 13:29:59)
    // 4. After work hours: >= 5:00 PM (17:00:00)
    // 5. Day marked as day-off in calendar
    
    $datetime = new DateTime($datetimeStr);
    $date = $datetime->format('Y-m-d');
    $dayOfWeek = $datetime->format('w'); // 0 = Sunday, 6 = Saturday
    $hour = (int)$datetime->format('G'); // Hour in 24-hour format without leading zeros
    $minute = (int)$datetime->format('i');
    $timeInMinutes = $hour * 60 + $minute;
    
    // Check if it's a weekend (Saturday or Sunday)
    if ($dayOfWeek == 0 || $dayOfWeek == 6) {
        return 'Ngoài giờ';
    }
    
    // Check if it's marked as day-off in calendar
    if (isset($daysOffMap[$date])) {
        return 'Ngoài giờ';
    }
    
    // Check time rules
    // Before 7:00 AM
    if ($hour < 7) {
        return 'Ngoài giờ';
    }
    
    // Lunch break: 11:30 AM - 1:29 PM (690 minutes to 809 minutes)
    if ($timeInMinutes >= 690 && $timeInMinutes <= 809) {
        return 'Ngoài giờ';
    }
    
    // After 5:00 PM (>= 17:00)
    if ($hour >= 17) {
        return 'Ngoài giờ';
    }
    
    // If all above conditions are not met, it's "Đúng giờ"
    // Working hours: 7:00-11:29 AM and 1:30-4:59 PM on weekdays (not day-off)
    return 'Đúng giờ';
}

// Handle the request
updateAdmissionTimes();
?>
