<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$conn = getDbConnection();

// Check total records
$totalSql = "SELECT COUNT(*) as total FROM hospital_admissions";
$totalResult = $conn->query($totalSql);
$total = $totalResult->fetch_assoc()['total'];

// Check records with admission_time_type
$withTypeSql = "SELECT COUNT(*) as count FROM hospital_admissions WHERE admission_time_type IS NOT NULL AND admission_time_type != ''";
$withTypeResult = $conn->query($withTypeSql);
$withType = $withTypeResult->fetch_assoc()['count'];

// Check records with admission_datetime
$withDatetimeSql = "SELECT COUNT(*) as count FROM hospital_admissions WHERE admission_datetime IS NOT NULL";
$withDatetimeResult = $conn->query($withDatetimeSql);
$withDatetime = $withDatetimeResult->fetch_assoc()['count'];

// Get sample data
$sampleSql = "SELECT id, ma_kcb, ho_ten_bn, ngay_vao_vien, admission_datetime, admission_time_type FROM hospital_admissions LIMIT 5";
$sampleResult = $conn->query($sampleSql);
$samples = [];
while ($row = $sampleResult->fetch_assoc()) {
    $samples[] = $row;
}

// Check distinct time types
$distinctSql = "SELECT admission_time_type, COUNT(*) as count FROM hospital_admissions GROUP BY admission_time_type";
$distinctResult = $conn->query($distinctSql);
$timeTypes = [];
while ($row = $distinctResult->fetch_assoc()) {
    $timeTypes[] = $row;
}

$conn->close();

echo json_encode([
    'success' => true,
    'total_records' => $total,
    'with_admission_time_type' => $withType,
    'with_admission_datetime' => $withDatetime,
    'time_type_distribution' => $timeTypes,
    'sample_data' => $samples
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
