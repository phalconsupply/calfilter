<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'generate':
        generateStatistics();
        break;
    case 'export':
        exportToExcel();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
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
        // Build WHERE clause based on period type
        if ($type === 'month') {
            // Format: YYYY-MM
            list($year, $month) = explode('-', $period);
            $startDate = "$year-$month-01";
            $endDate = date("Y-m-t", strtotime($startDate));
            $whereClause = "ngay_vao_vien BETWEEN '$startDate' AND '$endDate'";
        } else {
            // Year format: YYYY
            $year = $period;
            $whereClause = "YEAR(ngay_vao_vien) = $year";
        }
        
        // Get off-hours ranking
        $offHoursSql = "SELECT bac_si_chi_dinh, COUNT(*) as count 
                        FROM hospital_admissions 
                        WHERE $whereClause AND admission_time_type = 'Ngoài giờ'
                        GROUP BY bac_si_chi_dinh 
                        ORDER BY count DESC";
        
        $offHoursResult = $conn->query($offHoursSql);
        $offHoursData = [];
        if ($offHoursResult) {
            while ($row = $offHoursResult->fetch_assoc()) {
                $offHoursData[] = $row;
            }
        }
        
        // Get on-time ranking
        $onTimeSql = "SELECT bac_si_chi_dinh, COUNT(*) as count 
                      FROM hospital_admissions 
                      WHERE $whereClause AND admission_time_type = 'Đúng giờ'
                      GROUP BY bac_si_chi_dinh 
                      ORDER BY count DESC";
        
        $onTimeResult = $conn->query($onTimeSql);
        $onTimeData = [];
        if ($onTimeResult) {
            while ($row = $onTimeResult->fetch_assoc()) {
                $onTimeData[] = $row;
            }
        }
        
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
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn thời gian']);
        return;
    }
    
    // Check if PhpSpreadsheet is available
    if (!file_exists(__DIR__ . '/PhpSpreadsheet/vendor/autoload.php')) {
        echo json_encode(['success' => false, 'message' => 'PhpSpreadsheet chưa được cài đặt']);
        return;
    }
    
    require_once __DIR__ . '/PhpSpreadsheet/vendor/autoload.php';
    
    $conn = getDbConnection();
    
    try {
        // Build WHERE clause
        if ($type === 'month') {
            list($year, $month) = explode('-', $period);
            $startDate = "$year-$month-01";
            $endDate = date("Y-m-t", strtotime($startDate));
            $whereClause = "ngay_vao_vien BETWEEN '$startDate' AND '$endDate'";
            $periodText = "Tháng $month/$year";
        } else {
            $year = $period;
            $whereClause = "YEAR(ngay_vao_vien) = $year";
            $periodText = "Năm $year";
        }
        
        // Determine admission time type
        $admissionTimeType = $timeType === 'off-hours' ? 'Ngoài giờ' : 'Đúng giờ';
        $rankingTitle = $timeType === 'off-hours' ? 'Xếp hạng ca Ngoài giờ' : 'Xếp hạng ca Đúng giờ';
        
        // Get ranking data
        $sql = "SELECT bac_si_chi_dinh, COUNT(*) as count 
                FROM hospital_admissions 
                WHERE $whereClause AND admission_time_type = ?
                GROUP BY bac_si_chi_dinh 
                ORDER BY count DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $admissionTimeType);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $rankingData = [];
        while ($row = $result->fetch_assoc()) {
            $rankingData[] = $row;
        }
        
        $stmt->close();
        
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
        $conn2 = getDbConnection();
        
        // Get detailed admissions for each doctor
        foreach ($rankingData as $index => $doctorData) {
            $doctorName = $doctorData['bac_si_chi_dinh'];
            $rank = $index + 1;
            
            // Create new sheet for this doctor
            $detailSheet = $spreadsheet->createSheet();
            $sheetName = substr("Top{$rank}_{$doctorName}", 0, 31); // Excel sheet name limit is 31 chars
            $detailSheet->setTitle($sheetName);
            
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
            
            $detailStmt = $conn2->prepare($detailSql);
            $detailStmt->bind_param("ss", $admissionTimeType, $doctorName);
            $detailStmt->execute();
            $detailResult = $detailStmt->get_result();
            
            $detailRow = 5;
            $stt = 1;
            while ($admission = $detailResult->fetch_assoc()) {
                $detailSheet->setCellValue("A$detailRow", $stt);
                $detailSheet->setCellValue("B$detailRow", $admission['ma_kcb']);
                $detailSheet->setCellValue("C$detailRow", $admission['ho_ten_bn']);
                $detailSheet->setCellValue("D$detailRow", $admission['tuoi']);
                $detailSheet->setCellValue("E$detailRow", $admission['gioi_tinh']);
                $detailSheet->setCellValue("F$detailRow", $admission['dia_chi']);
                $detailSheet->setCellValue("G$detailRow", $admission['ngay_vao_vien']);
                
                // Format admission_datetime to show only time
                if ($admission['admission_datetime']) {
                    $datetime = new DateTime($admission['admission_datetime']);
                    $detailSheet->setCellValue("H$detailRow", $datetime->format('H:i:s'));
                }
                
                $detailSheet->setCellValue("I$detailRow", $admission['admission_time_type']);
                $detailSheet->setCellValue("J$detailRow", $admission['khoa_vao_vien']);
                $detailSheet->setCellValue("K$detailRow", $admission['chan_doan']);
                $detailSheet->setCellValue("L$detailRow", $admission['bac_si_chi_dinh']);
                
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
        
        $conn2->close();
        
        // Output file
        $filename = "Thong_ke_{$admissionTimeType}_{$periodText}.xlsx";
        $filename = str_replace(['/', ' '], ['_', '_'], $filename);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        echo "Lỗi: " . $e->getMessage();
    }
}
?>
