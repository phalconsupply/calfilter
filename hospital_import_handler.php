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

// Include dependencies first
if (!file_exists(__DIR__ . '/config.php')) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'File config.php không tồn tại'], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once __DIR__ . '/config.php';

if (!file_exists(__DIR__ . '/PhpSpreadsheet/vendor/autoload.php')) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'PhpSpreadsheet chưa được cài đặt'], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once __DIR__ . '/PhpSpreadsheet/vendor/autoload.php';

// Use statements MUST be at top level
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    
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
        
        if (count($rows) < 1) {
            throw new Exception('File không có dữ liệu');
        }

        // Detect and remove header row only if present.
        // File xuất từ hệ thống KHÔNG có dòng tiêu đề (dòng 1 là dữ liệu thật),
        // còn file mẫu tải về thì CÓ. Ô "Ngày vào viện" (cột G) của dòng tiêu đề
        // không phải ngày giờ hợp lệ, nên dùng nó để phân biệt.
        $firstRow = $rows[0];
        if (!empty(array_filter($firstRow)) && parseAdmissionDateTime($firstRow[6] ?? null) === null) {
            array_shift($rows);
        }
        
        $conn = getDbConnection();

        // Nạp lịch nghỉ MỘT LẦN vào mảng (tránh truy vấn DB cho từng dòng)
        $daysOffMap = loadDaysOffMap($conn);

        // Chuẩn bị sẵn các câu lệnh, tái sử dụng trong vòng lặp.
        // Chống trùng theo (ma_kcb, ngay_vao_vien): cùng bệnh nhân + cùng ngày = 1 lượt
        // (cập nhật); khác ngày = lượt nhập viện mới (bản ghi mới) để tính thành tích bác sĩ.
        $findStmt = $conn->prepare(
            "SELECT id FROM hospital_admissions WHERE ma_kcb = ? AND ngay_vao_vien = ? LIMIT 1"
        );
        $insertStmt = $conn->prepare(
            "INSERT INTO hospital_admissions
                (ma_kcb, ho_ten_bn, tuoi, gioi_tinh, dia_chi, ngay_vao_vien,
                 admission_datetime, admission_time_type, khoa_vao_vien, chan_doan, bac_si_chi_dinh)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $updateStmt = $conn->prepare(
            "UPDATE hospital_admissions SET
                ho_ten_bn = ?, tuoi = ?, gioi_tinh = ?, dia_chi = ?,
                admission_datetime = ?, admission_time_type = ?,
                khoa_vao_vien = ?, chan_doan = ?, bac_si_chi_dinh = ?
             WHERE id = ?"
        );

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
                
                // Extract data - Cột A là STT (số thứ tự), bỏ qua
                $rawDateTime = trim((string)($row[6] ?? '')); // Cột G: ngày + giờ gộp chung
                $data = [
                    'ma_kcb' => trim((string)($row[1] ?? '')),        // Cột B
                    'ho_ten_bn' => trim((string)($row[2] ?? '')),     // Cột C
                    'tuoi' => trim((string)($row[3] ?? '')),          // Cột D
                    'gioi_tinh' => trim((string)($row[4] ?? '')),     // Cột E
                    'dia_chi' => trim((string)($row[5] ?? '')),       // Cột F
                    'khoa_vao_vien' => trim((string)($row[7] ?? '')), // Cột H
                    'chan_doan' => trim((string)($row[8] ?? '')),     // Cột I
                    'bac_si_chi_dinh' => trim((string)($row[9] ?? '')) // Cột J
                ];

                // Validate required fields
                if (empty($data['ma_kcb'])) {
                    throw new Exception("Mã KCB không được để trống");
                }

                // Bóc tách ngày giờ từ cột G (vd: "01/06/2026 8:40:00 AM" = dd/mm/yyyy)
                $parsed = parseAdmissionDateTime($rawDateTime);
                if ($parsed === null) {
                    throw new Exception("Không đọc được ngày giờ vào viện: '$rawDateTime'");
                }
                $ngayVaoVien = $parsed['date'];           // Y-m-d cho cột ngay_vao_vien (DATE)
                $admissionDatetime = $parsed['datetime']; // Y-m-d H:i:s cho admission_datetime

                // Đối chiếu rule + lịch nghỉ (đã nạp sẵn) để phân loại giờ nhập viện
                $timeType = calculateAdmissionTimeType($admissionDatetime, $daysOffMap);

                // Chống trùng: tìm bản ghi cùng (ma_kcb, ngay_vao_vien)
                $findStmt->bind_param('ss', $data['ma_kcb'], $ngayVaoVien);
                $findStmt->execute();
                $existingRow = $findStmt->get_result()->fetch_assoc();
                $existingId = $existingRow ? $existingRow['id'] : null;

                if ($existingId !== null) {
                    // Cùng bệnh nhân + cùng ngày → cập nhật bản ghi hiện có
                    $updateStmt->bind_param(
                        'sssssssssi',
                        $data['ho_ten_bn'],
                        $data['tuoi'],
                        $data['gioi_tinh'],
                        $data['dia_chi'],
                        $admissionDatetime,
                        $timeType,
                        $data['khoa_vao_vien'],
                        $data['chan_doan'],
                        $data['bac_si_chi_dinh'],
                        $existingId
                    );
                    $exec = $updateStmt->execute();
                    $err = $updateStmt->error;
                } else {
                    // Lượt nhập viện mới
                    $insertStmt->bind_param(
                        'sssssssssss',
                        $data['ma_kcb'],
                        $data['ho_ten_bn'],
                        $data['tuoi'],
                        $data['gioi_tinh'],
                        $data['dia_chi'],
                        $ngayVaoVien,
                        $admissionDatetime,
                        $timeType,
                        $data['khoa_vao_vien'],
                        $data['chan_doan'],
                        $data['bac_si_chi_dinh']
                    );
                    $exec = $insertStmt->execute();
                    $err = $insertStmt->error;
                }

                if ($exec) {
                    $successCount++;
                    $importedData[] = array_merge($data, [
                        'ngay_vao_vien' => $ngayVaoVien,
                        'admission_datetime' => $admissionDatetime,
                        'admission_time_type' => $timeType
                    ]);
                } else {
                    throw new Exception($err);
                }
                
            } catch (Exception $e) {
                $errorCount++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => $e->getMessage()
                ];
            }
        }
        
        $findStmt->close();
        $insertStmt->close();
        $updateStmt->close();
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

/**
 * Bóc tách ô ngày giờ vào viện (cột G) thành ngày và ngày-giờ.
 * Hỗ trợ:
 *  - Số serial Excel (khi ô được đọc dạng số) → có sẵn cả ngày và giờ.
 *  - Chuỗi "dd/mm/yyyy" kèm giờ 12h (AM/PM) hoặc 24h, vd "01/06/2026 8:40:00 AM".
 * Trả về ['date' => 'Y-m-d', 'datetime' => 'Y-m-d H:i:s'] hoặc null nếu không đọc được.
 */
function parseAdmissionDateTime($value) {
    if ($value === null || trim((string)$value) === '') {
        return null;
    }

    // Số serial của Excel (chứa cả ngày lẫn giờ)
    if (is_numeric($value)) {
        try {
            $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value);
            return ['date' => $dt->format('Y-m-d'), 'datetime' => $dt->format('Y-m-d H:i:s')];
        } catch (Exception $e) {
            return null;
        }
    }

    $value = trim((string)$value);

    // Các định dạng chuỗi có thể gặp, ưu tiên dd/mm/yyyy (không phải kiểu Mỹ mm/dd)
    $formats = [
        'd/m/Y g:i:s A', 'd/m/Y g:i A', 'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y',
        'd-m-Y g:i:s A', 'd-m-Y H:i:s', 'd-m-Y',
        'Y-m-d H:i:s', 'Y-m-d',
    ];

    foreach ($formats as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $value);
        if ($dt !== false) {
            $errors = DateTime::getLastErrors();
            if ($errors === false || ($errors['warning_count'] == 0 && $errors['error_count'] == 0)) {
                return ['date' => $dt->format('Y-m-d'), 'datetime' => $dt->format('Y-m-d H:i:s')];
            }
        }
    }

    return null;
}

/**
 * Nạp danh sách ngày nghỉ (type = 'day_off') vào mảng dạng [ 'Y-m-d' => true ].
 * Gọi một lần trước vòng lặp import để tránh truy vấn DB cho từng dòng.
 * Đồng bộ với logic trong update_admission_times.php.
 */
function loadDaysOffMap($conn) {
    $daysOffMap = [];
    $result = $conn->query("SELECT day_date FROM calendar_days_off WHERE type = 'day_off'");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $daysOffMap[$row['day_date']] = true;
        }
    }
    return $daysOffMap;
}

/**
 * Phân loại "Đúng giờ" / "Ngoài giờ" dựa trên ngày giờ và mảng ngày nghỉ đã nạp sẵn.
 * Quy tắc trùng khớp update_admission_times.php (nguồn chuẩn khi tính lại theo lịch).
 */
function calculateAdmissionTimeType($datetimeStr, $daysOffMap = []) {
    try {
        $dt = new DateTime($datetimeStr);
        $date = $dt->format('Y-m-d');
        $hour = (int)$dt->format('H');
        $minute = (int)$dt->format('i');
        $timeInMinutes = $hour * 60 + $minute;
        $dayOfWeek = (int)$dt->format('N'); // 1 = Monday, 7 = Sunday

        // Cuối tuần (Thứ 7 / Chủ nhật)
        if ($dayOfWeek >= 6) {
            return 'Ngoài giờ';
        }

        // Ngày được đánh dấu nghỉ trong lịch
        if (isset($daysOffMap[$date])) {
            return 'Ngoài giờ';
        }

        // Trước 7:00
        if ($hour < 7) {
            return 'Ngoài giờ';
        }

        // Nghỉ trưa 11:30 - 13:29 (690 - 809 phút)
        if ($timeInMinutes >= 690 && $timeInMinutes <= 809) {
            return 'Ngoài giờ';
        }

        // Sau 17:00
        if ($hour >= 17) {
            return 'Ngoài giờ';
        }

        return 'Đúng giờ';

    } catch (Exception $e) {
        return 'Ngoài giờ';
    }
}

function downloadTemplate() {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set headers - khớp đúng layout file import thật (cột A là STT, cột G gộp ngày + giờ)
    $headers = [
        'STT',
        'Mã KCB',
        'Họ tên BN',
        'Tuổi',
        'Giới tính',
        'Địa chỉ',
        'Ngày giờ vào viện',
        'Khoa vào viện',
        'Chẩn đoán',
        'Bác sĩ chỉ định'
    ];

    $sheet->fromArray($headers, null, 'A1');

    // Dòng ví dụ minh hoạ định dạng ngày giờ (dd/mm/yyyy h:mm:ss AM/PM)
    $sample = [
        '1',
        '2600097694',
        'NGUYỄN VĂN A',
        '65',
        'Nam',
        'Xã Đức Trọng, , Tỉnh Lâm Đồng',
        '01/06/2026 8:40:00 AM',
        'Khoa Nội',
        'Nhiễm trùng đường ruột do vi khuẩn khác',
        'Hà Xuân Hoà'
    ];
    $sheet->fromArray($sample, null, 'A2');
    
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
