<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_admissions':
        getAdmissions();
        break;
    case 'add_admission':
        addAdmission();
        break;
    case 'update_admission':
        updateAdmission();
        break;
    case 'delete_admission':
        deleteAdmission();
        break;
    case 'search_admissions':
        searchAdmissions();
        break;
    case 'get_statistics':
        getStatistics();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
}

function getAdmissions() {
    $conn = getDbConnection();
    
    // Check if month filter is provided
    if (isset($_GET['month']) && !empty($_GET['month'])) {
        $month = $_GET['month']; // Format: YYYY-MM
        $timeType = $_GET['time_type'] ?? '';
        
        // Parse month
        list($year, $monthNum) = explode('-', $month);
        $startDate = "$year-$monthNum-01";
        $endDate = date("Y-m-t", strtotime($startDate));
        
        $sql = "SELECT * FROM hospital_admissions 
                WHERE ngay_vao_vien BETWEEN ? AND ?";
        
        // Add time type filter if specified
        if (!empty($timeType)) {
            $sql .= " AND admission_time_type = ?";
        }
        
        $sql .= " ORDER BY ngay_vao_vien DESC, admission_datetime DESC";
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($timeType)) {
            $stmt->bind_param("sss", $startDate, $endDate, $timeType);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $admissions = [];
        while ($row = $result->fetch_assoc()) {
            $admissions[] = $row;
        }
        
        $stmt->close();
        $conn->close();
        
        echo json_encode([
            'success' => true,
            'data' => $admissions,
            'count' => count($admissions)
        ]);
        return;
    }
    
    // Default behavior - get recent admissions
    $limit = $_GET['limit'] ?? 50;
    $offset = $_GET['offset'] ?? 0;
    $orderBy = $_GET['orderBy'] ?? 'ngay_vao_vien DESC';
    
    $sql = "SELECT * FROM hospital_admissions 
            ORDER BY $orderBy 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $admissions = [];
    while ($row = $result->fetch_assoc()) {
        $admissions[] = $row;
    }
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM hospital_admissions";
    $countResult = $conn->query($countSql);
    $total = $countResult->fetch_assoc()['total'];
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'data' => $admissions,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
}

function addAdmission() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $required = ['ma_kcb', 'ho_ten_bn', 'ngay_vao_vien'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Thiếu trường: $field"]);
            return;
        }
    }
    
    $conn = getDbConnection();
    
    $sql = "INSERT INTO hospital_admissions 
            (ma_kcb, ho_ten_bn, tuoi, gioi_tinh, dia_chi, ngay_vao_vien, khoa_vao_vien, chan_doan, bac_si_chi_dinh) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssissssss",
        $data['ma_kcb'],
        $data['ho_ten_bn'],
        $data['tuoi'],
        $data['gioi_tinh'],
        $data['dia_chi'],
        $data['ngay_vao_vien'],
        $data['khoa_vao_vien'],
        $data['chan_doan'],
        $data['bac_si_chi_dinh']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Đã thêm bệnh nhân', 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $conn->error]);
    }
    
    $stmt->close();
    $conn->close();
}

function updateAdmission() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'Thiếu ID']);
        return;
    }
    
    $conn = getDbConnection();
    
    $sql = "UPDATE hospital_admissions SET 
            ma_kcb = ?, ho_ten_bn = ?, tuoi = ?, gioi_tinh = ?, 
            dia_chi = ?, ngay_vao_vien = ?, khoa_vao_vien = ?, 
            chan_doan = ?, bac_si_chi_dinh = ?
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssissssssi",
        $data['ma_kcb'],
        $data['ho_ten_bn'],
        $data['tuoi'],
        $data['gioi_tinh'],
        $data['dia_chi'],
        $data['ngay_vao_vien'],
        $data['khoa_vao_vien'],
        $data['chan_doan'],
        $data['bac_si_chi_dinh'],
        $data['id']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Đã cập nhật']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $conn->error]);
    }
    
    $stmt->close();
    $conn->close();
}

function deleteAdmission() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'Thiếu ID']);
        return;
    }
    
    $conn = getDbConnection();
    
    $sql = "DELETE FROM hospital_admissions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $data['id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Đã xóa']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $conn->error]);
    }
    
    $stmt->close();
    $conn->close();
}

function searchAdmissions() {
    $keyword = $_GET['keyword'] ?? '';
    $khoa = $_GET['khoa'] ?? '';
    $fromDate = $_GET['from_date'] ?? '';
    $toDate = $_GET['to_date'] ?? '';
    
    $conn = getDbConnection();
    
    $sql = "SELECT * FROM hospital_admissions WHERE 1=1";
    $params = [];
    $types = '';
    
    if (!empty($keyword)) {
        $sql .= " AND (ho_ten_bn LIKE ? OR ma_kcb LIKE ? OR chan_doan LIKE ?)";
        $searchTerm = "%$keyword%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'sss';
    }
    
    if (!empty($khoa)) {
        $sql .= " AND khoa_vao_vien LIKE ?";
        $params[] = "%$khoa%";
        $types .= 's';
    }
    
    if (!empty($fromDate)) {
        $sql .= " AND ngay_vao_vien >= ?";
        $params[] = $fromDate;
        $types .= 's';
    }
    
    if (!empty($toDate)) {
        $sql .= " AND ngay_vao_vien <= ?";
        $params[] = $toDate;
        $types .= 's';
    }
    
    $sql .= " ORDER BY ngay_vao_vien DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $admissions = [];
    while ($row = $result->fetch_assoc()) {
        $admissions[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode(['success' => true, 'data' => $admissions]);
}

function getStatistics() {
    $conn = getDbConnection();
    
    // Total admissions
    $totalSql = "SELECT COUNT(*) as total FROM hospital_admissions";
    $totalResult = $conn->query($totalSql);
    $total = $totalResult->fetch_assoc()['total'];
    
    // By department
    $deptSql = "SELECT khoa_vao_vien, COUNT(*) as count 
                FROM hospital_admissions 
                GROUP BY khoa_vao_vien 
                ORDER BY count DESC";
    $deptResult = $conn->query($deptSql);
    $byDepartment = [];
    while ($row = $deptResult->fetch_assoc()) {
        $byDepartment[] = $row;
    }
    
    // By gender
    $genderSql = "SELECT gioi_tinh, COUNT(*) as count 
                  FROM hospital_admissions 
                  GROUP BY gioi_tinh";
    $genderResult = $conn->query($genderSql);
    $byGender = [];
    while ($row = $genderResult->fetch_assoc()) {
        $byGender[] = $row;
    }
    
    // Today's admissions
    $todaySql = "SELECT COUNT(*) as count 
                 FROM hospital_admissions 
                 WHERE DATE(ngay_vao_vien) = CURDATE()";
    $todayResult = $conn->query($todaySql);
    $today = $todayResult->fetch_assoc()['count'];
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'statistics' => [
            'total' => $total,
            'today' => $today,
            'by_department' => $byDepartment,
            'by_gender' => $byGender
        ]
    ]);
}
?>
