<?php
// Script to add 'type' column to calendar_days_off table and admission_time_type to hospital_admissions
require_once 'config.php';

$conn = getDbConnection();

echo "<h2>Database Update Script</h2>";

// 1. Add type column to calendar_days_off if it doesn't exist
$checkColumnSql = "SHOW COLUMNS FROM calendar_days_off LIKE 'type'";
$result = $conn->query($checkColumnSql);

if ($result->num_rows == 0) {
    // Column doesn't exist, add it
    $alterSql = "ALTER TABLE calendar_days_off ADD COLUMN type VARCHAR(20) DEFAULT 'day_off' AFTER note";
    
    if ($conn->query($alterSql) === TRUE) {
        echo "✅ Column 'type' added to calendar_days_off successfully<br>";
        
        // Update existing records to have 'day_off' type
        $updateSql = "UPDATE calendar_days_off SET type = 'day_off' WHERE type IS NULL";
        if ($conn->query($updateSql) === TRUE) {
            echo "✅ Updated existing records with default type<br>";
        } else {
            echo "❌ Error updating records: " . $conn->error . "<br>";
        }
    } else {
        echo "❌ Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ Column 'type' already exists in calendar_days_off<br>";
}

// 2. Add admission_datetime column to hospital_admissions if it doesn't exist
$checkAdmissionDatetimeSql = "SHOW COLUMNS FROM hospital_admissions LIKE 'admission_datetime'";
$result2 = $conn->query($checkAdmissionDatetimeSql);

if ($result2->num_rows == 0) {
    // Column doesn't exist, add it
    $alterSql2 = "ALTER TABLE hospital_admissions ADD COLUMN admission_datetime DATETIME DEFAULT NULL AFTER ngay_vao_vien";
    
    if ($conn->query($alterSql2) === TRUE) {
        echo "✅ Column 'admission_datetime' added to hospital_admissions successfully<br>";
        
        // Copy data from ngay_vao_vien to admission_datetime for existing records
        $updateSql2 = "UPDATE hospital_admissions SET admission_datetime = ngay_vao_vien WHERE admission_datetime IS NULL";
        if ($conn->query($updateSql2) === TRUE) {
            echo "✅ Copied existing ngay_vao_vien to admission_datetime<br>";
        }
    } else {
        echo "❌ Error adding admission_datetime column: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ Column 'admission_datetime' already exists in hospital_admissions<br>";
}

// 3. Add admission_time_type column to hospital_admissions if it doesn't exist
$checkAdmissionTypeSql = "SHOW COLUMNS FROM hospital_admissions LIKE 'admission_time_type'";
$result3 = $conn->query($checkAdmissionTypeSql);

if ($result3->num_rows == 0) {
    // Column doesn't exist, add it
    $alterSql3 = "ALTER TABLE hospital_admissions ADD COLUMN admission_time_type VARCHAR(20) DEFAULT NULL AFTER admission_datetime";
    
    if ($conn->query($alterSql3) === TRUE) {
        echo "✅ Column 'admission_time_type' added to hospital_admissions successfully<br>";
    } else {
        echo "❌ Error adding admission_time_type column: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ Column 'admission_time_type' already exists in hospital_admissions<br>";
}

// 4. Change ngay_vao_vien to DATE type if it's still DATETIME
$checkNgayVaoVienType = "SHOW COLUMNS FROM hospital_admissions WHERE Field = 'ngay_vao_vien'";
$result4 = $conn->query($checkNgayVaoVienType);
$fieldInfo = $result4->fetch_assoc();

if (stripos($fieldInfo['Type'], 'datetime') !== false) {
    // Convert ngay_vao_vien from DATETIME to DATE
    $alterSql4 = "ALTER TABLE hospital_admissions MODIFY COLUMN ngay_vao_vien DATE NOT NULL";
    
    if ($conn->query($alterSql4) === TRUE) {
        echo "✅ Changed 'ngay_vao_vien' to DATE type<br>";
    } else {
        echo "❌ Error changing ngay_vao_vien type: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ Column 'ngay_vao_vien' is already DATE type<br>";
}

$conn->close();
echo "<br><strong>Database update complete!</strong><br>";
echo "<a href='index.html'>← Quay về lịch</a> | <a href='hospital_import.html'>Đi đến Import dữ liệu →</a>";
?>
