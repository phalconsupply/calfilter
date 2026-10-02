<?php
require_once 'config.php';

$action = $_GET['action'] ?? '';

// 'export' trả về file nhị phân nên KHÔNG đặt Content-Type JSON cho nhánh đó.
switch ($action) {
    case 'generate':
        header('Content-Type: application/json; charset=utf-8');
        generateStatistics();
        break;
    case 'export':
        exportToExcel();
        break;
    default:
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Dựng mệnh đề WHERE theo kỳ báo cáo, TRẢ VỀ placeholder + tham số (chống SQL injection).
 * @return array [whereClause, params[], typesString]
 */
function buildPeriodWhere($type, $period) {
    if ($type === 'month') {
        // Format: YYYY-MM
        list($year, $month) = array_pad(explode('-', $period), 2, '');
        $startDate = sprintf('%04d-%02d-01', (int)$year, (int)$month);
        $endDate = date('Y-m-t', strtotime($startDate));
        return ['ngay_vao_vien BETWEEN ? AND ?', [$startDate, $endDate], 'ss'];
    }
    // Year format: YYYY
    return ['YEAR(ngay_vao_vien) = ?', [(int)$period], 'i'];
}

/**
 * Nhãn hiển thị kỳ báo cáo.
 */
function periodLabel($type, $period) {
    if ($type === 'month') {
        list($year, $month) = array_pad(explode('-', $period), 2, '');
        return "Tháng " . (int)$month . "/" . (int)$year;
    }
    return "Năm " . (int)$period;
}

/**
 * Tạo tên sheet hợp lệ cho Excel.
 * - Cắt theo KÝ TỰ (mb_substr) chứ không theo byte: cắt bằng substr() làm đứt ký tự
 *   UTF-8 nhiều byte (tên tiếng Việt có dấu) -> workbook.xml sai UTF-8 -> Excel mở
 *   được file nhưng không đọc được dữ liệu.
 * - Bỏ các ký tự Excel không cho phép trong tên sheet: * : / \ ? [ ]
 * - Đảm bảo không rỗng và không trùng tên sheet đã dùng.
 */
function makeSheetTitle($rank, $doctorName, array &$usedTitles) {
    $prefix = "Top{$rank}_";
    $name = preg_replace('/[*:\/\\\\?\[\]]/u', ' ', (string)$doctorName);
    $name = trim(preg_replace('/\s+/u', ' ', $name));

    $title = mb_substr($prefix . $name, 0, 31, 'UTF-8');
    if (trim($title) === '') {
        $title = 'Sheet' . $rank;
    }

    // Tên sau khi cắt 31 ký tự có thể trùng nhau -> setTitle() sẽ ném exception
    $base = $title;
    $i = 2;
    while (isset($usedTitles[mb_strtolower($title, 'UTF-8')])) {
        $suffix = '_' . $i;
        $title = mb_substr($base, 0, 31 - mb_strlen($suffix, 'UTF-8'), 'UTF-8') . $suffix;
        $i++;
    }
    $usedTitles[mb_strtolower($title, 'UTF-8')] = true;

    return $title;
}

/**
 * Gửi header tải file với tên UTF-8 đúng chuẩn RFC 6266
 * (header HTTP chỉ chấp nhận ASCII, nên cần bản dự phòng ASCII + filename*).
 */
function sendDownloadHeaders($filename) {
    $ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', boDauTiengViet($filename));
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"$ascii\"; filename*=UTF-8''" . rawurlencode($filename));
    header('Cache-Control: max-age=0');
    header('Pragma: public');
}

/**
 * Bỏ dấu tiếng Việt để làm tên file dự phòng dạng ASCII.
 */
function boDauTiengViet($str) {
    $map = [
        'a' => 'áàảãạăắằẳẵặâấầẩẫậ', 'd' => 'đ', 'e' => 'éèẻẽẹêếềểễệ',
        'i' => 'íìỉĩị', 'o' => 'óòỏõọôốồổỗộơớờởỡợ',
        'u' => 'úùủũụưứừửữự', 'y' => 'ýỳỷỹỵ',
    ];
    foreach ($map as $plain => $accented) {
        $chars = preg_split('//u', $accented, -1, PREG_SPLIT_NO_EMPTY);
        $str = str_replace($chars, $plain, $str);
        $str = str_replace(array_map(function ($c) {
            return mb_strtoupper($c, 'UTF-8');
        }, $chars), mb_strtoupper($plain, 'UTF-8'), $str);
    }
    return $str;
}

/**
 * Truy vấn xếp hạng bác sĩ theo số lượt của một loại giờ, dùng prepared statement.
 */
function fetchRanking($conn, $whereClause, $params, $paramTypes, $admissionTimeType) {
    $sql = "SELECT bac_si_chi_dinh, COUNT(*) as count
            FROM hospital_admissions
            WHERE $whereClause AND admission_time_type = ?
            GROUP BY bac_si_chi_dinh
            ORDER BY count DESC";
    $stmt = $conn->prepare($sql);
    $allParams = array_merge($params, [$admissionTimeType]);
    $stmt->bind_param($paramTypes . 's', ...$allParams);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

function generateStatistics() {
    $type = $_GET['type'] ?? 'month'; // month or year
    $period = $_GET['period'] ?? '';
    
    if (empty($period)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn thời gian']);
        return;
    }
    
    $conn = getDbConnection();

    try {
        // Build WHERE clause based on period type (tham số hoá để tránh SQL injection)
        list($whereClause, $params, $paramTypes) = buildPeriodWhere($type, $period);

        // Get off-hours ranking
        $offHoursData = fetchRanking($conn, $whereClause, $params, $paramTypes, 'Ngoài giờ');

        // Get on-time ranking
        $onTimeData = fetchRanking($conn, $whereClause, $params, $paramTypes, 'Đúng giờ');

        $conn->close();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'off_hours' => $offHoursData,
                'on_time' => $onTimeData
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ]);
    }
}

function exportToExcel() {
    $type = $_GET['type'] ?? 'month';
    $period = $_GET['period'] ?? '';
    $timeType = $_GET['time_type'] ?? 'off-hours'; // off-hours or on-time
    
    if (empty($period)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn thời gian']);
        return;
    }

    // Check if PhpSpreadsheet is available
    if (!file_exists(__DIR__ . '/PhpSpreadsheet/vendor/autoload.php')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'PhpSpreadsheet chưa được cài đặt']);
        return;
    }

    require_once __DIR__ . '/PhpSpreadsheet/vendor/autoload.php';

    // Warning/notice in ra giữa luồng sẽ lẫn vào file .xlsx và làm hỏng file.
    // Ghi log thay vì in ra, và dựng file trong buffer để kiểm soát đầu ra.
    @ini_set('display_errors', '0');
    @ini_set('zlib.output_compression', '0');
    @ini_set('memory_limit', '512M');
    @set_time_limit(300);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    ob_start();

    $conn = getDbConnection();

    try {
        // Build WHERE clause (tham số hoá)
        list($whereClause, $params, $paramTypes) = buildPeriodWhere($type, $period);
        $periodText = periodLabel($type, $period);

        // Determine admission time type
        $admissionTimeType = $timeType === 'off-hours' ? 'Ngoài giờ' : 'Đúng giờ';
        $rankingTitle = $timeType === 'off-hours' ? 'Xếp hạng ca Ngoài giờ' : 'Xếp hạng ca Đúng giờ';

        // Get ranking data
        $rankingData = fetchRanking($conn, $whereClause, $params, $paramTypes, $admissionTimeType);

        // Create Excel file
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        
        // ===== SHEET 1: RANKING =====
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Xếp hạng');
        
        // Set title
        $sheet->setCellValue('A1', $rankingTitle);
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Set period
        $sheet->setCellValue('A2', $periodText);
        $sheet->mergeCells('A2:C2');
        $sheet->getStyle('A2')->getFont()->setSize(12);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Set headers
        $sheet->setCellValue('A4', 'Hạng');
        $sheet->setCellValue('B4', 'Bác sĩ');
        $sheet->setCellValue('C4', 'Số ca ' . $admissionTimeType);
        
        // Style headers
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '667eea']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ];
        $sheet->getStyle('A4:C4')->applyFromArray($headerStyle);
        
        // Add data
        $row = 5;
        foreach ($rankingData as $index => $data) {
            $rank = $index + 1;
            $medals = ['🥇', '🥈', '🥉'];
            $rankText = $rank <= 3 ? $medals[$rank - 1] . ' ' . $rank : $rank;
            
            $sheet->setCellValue("A$row", $rankText);
            $sheet->setCellValue("B$row", $data['bac_si_chi_dinh']);
            $sheet->setCellValue("C$row", $data['count']);
            
            // Highlight top 3
            if ($rank <= 3) {
                $colors = ['FFD700', 'C0C0C0', 'CD7F32'];
                $sheet->getStyle("A$row:C$row")->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $colors[$rank - 1]]
                    ],
                    'font' => ['bold' => true]
                ]);
            }
            
            // Center align rank and count
            $sheet->getStyle("A$row")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C$row")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Add borders
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];
        $sheet->getStyle("A4:C" . ($row - 1))->applyFromArray($styleArray);
        
        // ===== SHEET 2: DETAILED ADMISSIONS FOR EACH DOCTOR =====
        $usedTitles = [mb_strtolower($sheet->getTitle(), 'UTF-8') => true];

        // Get detailed admissions for each doctor
        foreach ($rankingData as $index => $doctorData) {
            $doctorName = (string)$doctorData['bac_si_chi_dinh'];
            $rank = $index + 1;

            // Create new sheet for this doctor
            // (tên sheet tối đa 31 KÝ TỰ, phải hợp lệ và không trùng - xem makeSheetTitle)
            $detailSheet = $spreadsheet->createSheet();
            $detailSheet->setTitle(makeSheetTitle($rank, $doctorName, $usedTitles));
            
            // Title
            $detailSheet->setCellValue('A1', "Chi tiết ca bệnh - {$doctorName}");
            $detailSheet->mergeCells('A1:L1');
            $detailSheet->getStyle('A1')->getFont()->setSize(14)->setBold(true);
            $detailSheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            // Subtitle
            $detailSheet->setCellValue('A2', "{$rankingTitle} - {$periodText}");
            $detailSheet->mergeCells('A2:L2');
            $detailSheet->getStyle('A2')->getFont()->setSize(11);
            $detailSheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            // Headers
            $detailSheet->setCellValue('A4', 'STT');
            $detailSheet->setCellValue('B4', 'Mã KCB');
            $detailSheet->setCellValue('C4', 'Họ tên BN');
            $detailSheet->setCellValue('D4', 'Tuổi');
            $detailSheet->setCellValue('E4', 'Giới tính');
            $detailSheet->setCellValue('F4', 'Địa chỉ');
            $detailSheet->setCellValue('G4', 'Ngày vào viện');
            $detailSheet->setCellValue('H4', 'Giờ nhập viện');
            $detailSheet->setCellValue('I4', 'Loại giờ');
            $detailSheet->setCellValue('J4', 'Khoa');
            $detailSheet->setCellValue('K4', 'Chẩn đoán');
            $detailSheet->setCellValue('L4', 'Bác sĩ');
            
            // Style headers
            $detailSheet->getStyle('A4:L4')->applyFromArray($headerStyle);
            
            // Get admissions for this doctor
            $detailSql = "SELECT * FROM hospital_admissions
                          WHERE $whereClause AND admission_time_type = ? AND bac_si_chi_dinh = ?
                          ORDER BY admission_datetime DESC";

            $detailStmt = $conn->prepare($detailSql);
            $detailParams = array_merge($params, [$admissionTimeType, $doctorName]);
            $detailStmt->bind_param($paramTypes . 'ss', ...$detailParams);
            $detailStmt->execute();
            $detailResult = $detailStmt->get_result();
            
            $detailRow = 5;
            $stt = 1;
            while ($admission = $detailResult->fetch_assoc()) {
                $detailSheet->setCellValue("A$detailRow", $stt);
                $detailSheet->setCellValueExplicit(
                    "B$detailRow",
                    (string)($admission['ma_kcb'] ?? ''),
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );
                $detailSheet->setCellValue("C$detailRow", $admission['ho_ten_bn'] ?? '');
                $detailSheet->setCellValue("D$detailRow", $admission['tuoi'] ?? '');
                $detailSheet->setCellValue("E$detailRow", $admission['gioi_tinh'] ?? '');
                $detailSheet->setCellValue("F$detailRow", $admission['dia_chi'] ?? '');
                $detailSheet->setCellValue("G$detailRow", $admission['ngay_vao_vien'] ?? '');

                // Format admission_datetime to show only time
                if (!empty($admission['admission_datetime'])) {
                    $datetime = new DateTime($admission['admission_datetime']);
                    $detailSheet->setCellValue("H$detailRow", $datetime->format('H:i:s'));
                }

                $detailSheet->setCellValue("I$detailRow", $admission['admission_time_type'] ?? '');
                $detailSheet->setCellValue("J$detailRow", $admission['khoa_vao_vien'] ?? '');
                $detailSheet->setCellValue("K$detailRow", $admission['chan_doan'] ?? '');
                $detailSheet->setCellValue("L$detailRow", $admission['bac_si_chi_dinh'] ?? '');
                
                // Center align STT and age
                $detailSheet->getStyle("A$detailRow")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $detailSheet->getStyle("D$detailRow")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $detailSheet->getStyle("E$detailRow")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $detailSheet->getStyle("H$detailRow")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $detailSheet->getStyle("I$detailRow")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                
                // Color code admission time type
                if ($admission['admission_time_type'] === 'Ngoài giờ') {
                    $detailSheet->getStyle("I$detailRow")->getFont()->getColor()->setRGB('FF9800');
                } else {
                    $detailSheet->getStyle("I$detailRow")->getFont()->getColor()->setRGB('4CAF50');
                }
                
                $detailRow++;
                $stt++;
            }
            
            $detailStmt->close();
            
            // Auto-size columns
            foreach (range('A', 'L') as $col) {
                $detailSheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Add borders
            if ($detailRow > 5) {
                $detailSheet->getStyle("A4:L" . ($detailRow - 1))->applyFromArray($styleArray);
            }
        }
        
        $conn->close();

        // Output file
        $filename = "Thong_ke_{$admissionTimeType}_{$periodText}.xlsx";
        $filename = str_replace(['/', ' '], ['_', '_'], $filename);

        // Ghi file vào buffer riêng rồi mới gửi, để output lạ (warning, khoảng trắng)
        // không lẫn vào nội dung .xlsx.
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $xlsx = ob_get_clean();
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        // Bỏ mọi thứ đã in ra trước đó (buffer ngoài cùng mở ở đầu hàm)
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        sendDownloadHeaders($filename);
        header('Content-Length: ' . strlen($xlsx));
        echo $xlsx;
        exit;

    } catch (\Throwable $e) {
        // Không gửi file nửa vời: xoá buffer và trả về thông báo lỗi đọc được.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        error_log('statistics_api export error: ' . $e->getMessage());
        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=utf-8', true, 500);
        }
        echo "Lỗi khi xuất Excel: " . $e->getMessage();
    }
}
?>
