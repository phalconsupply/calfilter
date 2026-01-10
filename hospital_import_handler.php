<?php
// Start output buffering to prevent any output before JSON
ob_start();

// Error handling
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-errors.log');

// Set JSON header early
header('Content-Type: application/json; charset=utf-8');

try {
    // Include dependencies
    if (!file_exists(__DIR__ . '/config.php')) {
        throw new Exception('File config.php không tồn tại');
    }
    require_once __DIR__ . '/config.php';
    
    if (!file_exists(__DIR__ . '/PhpSpreadsheet/vendor/autoload.php')) {
        throw new Exception('PhpSpreadsheet chưa được cài đặt. Vui lòng upload folder vendor');
    }
    require_once __DIR__ . '/PhpSpreadsheet/vendor/autoload.php';
    
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    
    // Handle download template
    if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
        downloadTemplate();
        exit;
    }
    
    // Handle file upload
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Không có file được tải lên hoặc có lỗi khi tải file');
    }
    
    $file = $_FILES['file'];
    $allowedExtensions = ['xlsx', 'xls'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        throw new Exception('File không đúng định dạng. Chỉ chấp nhận .xlsx hoặc .xls');
    }
    
    // Process the file
    $result = processExcelFile($file['tmp_name']);
    
    // Clear any buffered output and send JSON
    ob_end_clean();
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Clear any buffered output
    ob_end_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    // Catch fatal errors too
    ob_end_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}

function processExcelFile($filePath) {
    try {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        if (count($rows) < 2) {
            throw new Exception('File không có dữ liệu');
        }
        
        // Remove header row
        array_shift($rows);
        
        $conn = getDbConnection();
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        $importedData = [];
        
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 vì đã bỏ header và index bắt đầu từ 0
            
            try {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Extract data
                $data = [
                    'ma_kcb' => trim($row[0] ?? ''),
                    'ho_ten_bn' => trim($row[1] ?? ''),
                    'tuoi' => trim($row[2] ?? ''),
                    'gioi_tinh' => trim($row[3] ?? ''),
                    'dia_chi' => trim($row[4] ?? ''),
                    'ngay_vao_vien' => trim($row[5] ?? ''),
                    'gio_nhap_vien' => trim($row[6] ?? ''),
                    'khoa_vao_vien' => trim($row[7] ?? ''),
                    'chan_doan' => trim($row[8] ?? ''),
                    'bac_si_chi_dinh' => trim($row[9] ?? '')
                ];
                
                // Validate required fields
                if (empty($data['ma_kcb'])) {
                    throw new Exception("Mã KCB không được để trống");
                }
                
                // Process datetime
                $admissionDatetime = processDateTime($data['ngay_vao_vien'], $data['gio_nhap_vien']);
                
                // Calculate admission time type
                $timeType = calculateAdmissionTimeType($admissionDatetime);
                
                // Insert into database
                $sql = "INSERT INTO hospital_admissions 
                        (ma_kcb, ho_ten_bn, tuoi, gioi_tinh, dia_chi, ngay_vao_vien, 
                         admission_datetime, admission_time_type, khoa_vao_vien, chan_doan, bac_si_chi_dinh) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                        ho_ten_bn = VALUES(ho_ten_bn),
                        tuoi = VALUES(tuoi),
                        gioi_tinh = VALUES(gioi_tinh),
                        dia_chi = VALUES(dia_chi),
                        ngay_vao_vien = VALUES(ngay_vao_vien),
                        admission_datetime = VALUES(admission_datetime),
                        admission_time_type = VALUES(admission_time_type),
                        khoa_vao_vien = VALUES(khoa_vao_vien),
                        chan_doan = VALUES(chan_doan),
                        bac_si_chi_dinh = VALUES(bac_si_chi_dinh)";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    'sssssssssss',
                    $data['ma_kcb'],
                    $data['ho_ten_bn'],
                    $data['tuoi'],
                    $data['gioi_tinh'],
                    $data['dia_chi'],
                    $data['ngay_vao_vien'],
                    $admissionDatetime,
                    $timeType,
                    $data['khoa_vao_vien'],
                    $data['chan_doan'],
                    $data['bac_si_chi_dinh']
                );
                
                if ($stmt->execute()) {
                    $successCount++;
                    $importedData[] = array_merge($data, [
                        'admission_datetime' => $admissionDatetime,
                        'admission_time_type' => $timeType
                    ]);
                } else {
                    throw new Exception($stmt->error);
                }
                
            } catch (Exception $e) {
                $errorCount++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => $e->getMessage()
                ];
            }
        }
        
        $conn->close();
        
        return [
            'success' => true,
            'total_rows' => count($rows),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => $errors,
            'data' => $importedData
        ];
        
    } catch (Exception $e) {
        throw new Exception('Lỗi đọc file Excel: ' . $e->getMessage());
    }
}

function processDateTime($dateStr, $timeStr) {
    try {
        // Handle Excel date format (numeric)
        if (is_numeric($dateStr)) {
            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateStr);
            $dateStr = $date->format('Y-m-d');
        } else {
            // Convert dd/mm/yyyy to yyyy-mm-dd
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateStr, $matches)) {
                $dateStr = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
            }
        }
        
        // Handle time
        $time = '00:00:00';
        if (!empty($timeStr)) {
            if (is_numeric($timeStr)) {
                // Excel time format
                $timeObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($timeStr);
                $time = $timeObj->format('H:i:s');
            } else {
                // Parse time string
                if (preg_match('/^(\d{1,2}):(\d{2})/', $timeStr, $matches)) {
                    $time = sprintf('%02d:%02d:00', $matches[1], $matches[2]);
                }
            }
        }
        
        return $dateStr . ' ' . $time;
        
    } catch (Exception $e) {
        return date('Y-m-d H:i:s');
    }
}

function calculateAdmissionTimeType($datetimeStr) {
    try {
        $dt = new DateTime($datetimeStr);
        $date = $dt->format('Y-m-d');
        $hour = (int)$dt->format('H');
        $minute = (int)$dt->format('i');
        $timeInMinutes = $hour * 60 + $minute;
        $dayOfWeek = (int)$dt->format('N'); // 1 = Monday, 7 = Sunday
        
        // Check if it's weekend
        if ($dayOfWeek >= 6) { // Saturday or Sunday
            return 'Ngoài giờ';
        }
        
        // Check if it's marked as day off in calendar
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT type FROM calendar_days_off WHERE day_date = ?");
        $stmt->bind_param('s', $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $conn->close();
            return $row['type'] === 'working_day' ? 'Đúng giờ' : 'Ngoài giờ';
        }
        $conn->close();
        
        // Working hours: 7:00 AM - 5:00 PM (420 - 1020 minutes)
        // Lunch break: 11:30 AM - 1:29 PM (690 - 809 minutes)
        if ($timeInMinutes >= 420 && $timeInMinutes < 1020) {
            // Check lunch break
            if ($timeInMinutes >= 690 && $timeInMinutes < 810) {
                return 'Ngoài giờ';
            }
            return 'Đúng giờ';
        }
        
        return 'Ngoài giờ';
        
    } catch (Exception $e) {
        return 'Ngoài giờ';
    }
}

function downloadTemplate() {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set headers
    $headers = [
        'Mã KCB',
        'Họ tên BN',
        'Tuổi',
        'Giới tính',
        'Địa chỉ',
        'Ngày vào viện',
        'Giờ nhập viện',
        'Khoa vào viện',
        'Chẩn đoán',
        'Bác sĩ chỉ định'
    ];
    
    $sheet->fromArray($headers, null, 'A1');
    
    // Style header
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4472C4']
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ];
    
    $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
    
    // Auto-size columns
    foreach (range('A', 'J') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Output
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="mau_nhap_vien.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

function getDaysOffMap() {
    $conn = getDbConnection();
    $sql = "SELECT day_date, type FROM calendar_days_off";
    $result = $conn->query($sql);
    
    $daysOff = [];
    while ($row = $result->fetch_assoc()) {
        $daysOff[$row['day_date']] = $row['type'];
    }
    
    $conn->close();
    return $daysOff;
}
