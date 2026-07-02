<?php
/**
 * Quy tắc phân loại giờ nhập viện — DÙNG CHUNG cho:
 *  - hospital_import_handler.php (lúc import)
 *  - update_admission_times.php (nút "Cập nhật loại giờ nhập viện" / tạo lại báo cáo)
 *
 * Tách riêng để hai luồng luôn dùng CHUNG một logic, tránh lệch nhau.
 */

/**
 * Nạp lịch (cả ngày nghỉ lẫn ngày làm bù) vào mảng:
 *   [ 'Y-m-d' => 'day_off' | 'working_day' ]
 * Gọi một lần trước vòng lặp để tránh truy vấn DB cho từng dòng.
 */
function loadCalendarMap($conn) {
    $map = [];
    $result = $conn->query("SELECT day_date, type FROM calendar_days_off");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $map[$row['day_date']] = $row['type'];
        }
    }
    return $map;
}

/**
 * Phân loại "Đúng giờ" / "Ngoài giờ" dựa trên ngày giờ và lịch đã nạp sẵn.
 *
 * Quy tắc:
 *  - Ngày đánh dấu nghỉ (day_off) → luôn Ngoài giờ.
 *  - Cuối tuần (Thứ 7 / CN) mà KHÔNG được đánh dấu làm bù (working_day) → Ngoài giờ.
 *  - Ngày làm việc (ngày thường không nghỉ, HOẶC cuối tuần được tick làm bù)
 *    → xét khung giờ như ngày thường:
 *      • trước 7:00, hoặc nghỉ trưa 11:30–13:29, hoặc từ 17:00 trở đi → Ngoài giờ
 *      • còn lại (7:00–11:29 và 13:30–16:59) → Đúng giờ
 */
function classifyAdmissionTimeType($datetimeStr, $calendarMap = []) {
    try {
        $dt = new DateTime($datetimeStr);
        $date = $dt->format('Y-m-d');
        $hour = (int)$dt->format('H');
        $minute = (int)$dt->format('i');
        $timeInMinutes = $hour * 60 + $minute;
        $dayOfWeek = (int)$dt->format('N'); // 1 = Thứ 2 ... 7 = Chủ nhật
        $calType = $calendarMap[$date] ?? null;

        // Ngày nghỉ tường minh
        if ($calType === 'day_off') {
            return 'Ngoài giờ';
        }

        // Cuối tuần và KHÔNG phải ngày làm bù
        if ($dayOfWeek >= 6 && $calType !== 'working_day') {
            return 'Ngoài giờ';
        }

        // Tới đây là NGÀY LÀM VIỆC (ngày thường, hoặc cuối tuần làm bù) → xét khung giờ

        // Trước 7:00
        if ($hour < 7) {
            return 'Ngoài giờ';
        }

        // Nghỉ trưa 11:30 - 13:29 (690 - 809 phút)
        if ($timeInMinutes >= 690 && $timeInMinutes <= 809) {
            return 'Ngoài giờ';
        }

        // Từ 17:00 trở đi
        if ($hour >= 17) {
            return 'Ngoài giờ';
        }

        return 'Đúng giờ';

    } catch (Exception $e) {
        return 'Ngoài giờ';
    }
}
