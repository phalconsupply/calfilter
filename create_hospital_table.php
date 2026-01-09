<?php
require_once 'config.php';

// Create admissions table
function createAdmissionsTable() {
    $conn = getDbConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS hospital_admissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ma_kcb VARCHAR(50) NOT NULL,
        ho_ten_bn VARCHAR(255) NOT NULL,
        tuoi INT,
        gioi_tinh VARCHAR(10),
        dia_chi TEXT,
        ngay_vao_vien DATETIME NOT NULL,
        khoa_vao_vien VARCHAR(255),
        chan_doan TEXT,
        bac_si_chi_dinh VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_ma_kcb (ma_kcb),
        INDEX idx_ngay_vao_vien (ngay_vao_vien),
        INDEX idx_khoa (khoa_vao_vien)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ Bảng hospital_admissions đã được tạo thành công!\n\n";
        return true;
    } else {
        echo "❌ Lỗi khi tạo bảng: " . $conn->error . "\n";
        return false;
    }
}

// Insert demo data
function insertDemoData() {
    $conn = getDbConnection();
    
    // Check if data already exists
    $checkSql = "SELECT COUNT(*) as count FROM hospital_admissions";
    $result = $conn->query($checkSql);
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo "ℹ️ Bảng đã có dữ liệu, bỏ qua việc thêm dữ liệu demo.\n";
        return;
    }
    
    // Demo data
    $demoData = [
        [
            'ma_kcb' => '2500232705',
            'ho_ten_bn' => 'HOÀNG NGỌC ẤN',
            'tuoi' => 72,
            'gioi_tinh' => 'Nam',
            'dia_chi' => 'Xã Đức Trọng, , Tỉnh Lâm Đồng',
            'ngay_vao_vien' => '2025-12-01 10:15:00',
            'khoa_vao_vien' => 'Khoa Ngoại - LCK',
            'chan_doan' => 'Gãy xương các ngón tay khác',
            'bac_si_chi_dinh' => 'Hà Xuân Hoà'
        ],
        [
            'ma_kcb' => '2500232706',
            'ho_ten_bn' => 'NGUYỄN VĂN AN',
            'tuoi' => 45,
            'gioi_tinh' => 'Nam',
            'dia_chi' => 'Xã Xuân Thọ, Huyện Đà Lạt, Tỉnh Lâm Đồng',
            'ngay_vao_vien' => '2025-12-02 08:30:00',
            'khoa_vao_vien' => 'Khoa Nội - Tim mạch',
            'chan_doan' => 'Tăng huyết áp',
            'bac_si_chi_dinh' => 'Trần Thị Bình'
        ],
        [
            'ma_kcb' => '2500232707',
            'ho_ten_bn' => 'TRẦN THỊ BÍCH',
            'tuoi' => 38,
            'gioi_tinh' => 'Nữ',
            'dia_chi' => 'Phường 1, Thành phố Đà Lạt, Tỉnh Lâm Đồng',
            'ngay_vao_vien' => '2025-12-03 14:20:00',
            'khoa_vao_vien' => 'Khoa Sản',
            'chan_doan' => 'Thai 38 tuần',
            'bac_si_chi_dinh' => 'Lê Thị Hoa'
        ],
        [
            'ma_kcb' => '2500232708',
            'ho_ten_bn' => 'LÊ VĂN CƯỜNG',
            'tuoi' => 55,
            'gioi_tinh' => 'Nam',
            'dia_chi' => 'Xã Đạ Sar, Huyện Lạc Dương, Tỉnh Lâm Đồng',
            'ngay_vao_vien' => '2025-12-04 09:45:00',
            'khoa_vao_vien' => 'Khoa Nội - Tiêu hóa',
            'chan_doan' => 'Viêm dạ dày cấp',
            'bac_si_chi_dinh' => 'Phạm Văn Đức'
        ],
        [
            'ma_kcb' => '2500232709',
            'ho_ten_bn' => 'PHẠM THỊ DIỆU',
            'tuoi' => 29,
            'gioi_tinh' => 'Nữ',
            'dia_chi' => 'Phường 3, Thành phố Đà Lạt, Tỉnh Lâm Đồng',
            'ngay_vao_vien' => '2025-12-05 11:10:00',
            'khoa_vao_vien' => 'Khoa Nhi',
            'chan_doan' => 'Sốt virus',
            'bac_si_chi_dinh' => 'Võ Thị Lan'
        ]
    ];
    
    $sql = "INSERT INTO hospital_admissions 
            (ma_kcb, ho_ten_bn, tuoi, gioi_tinh, dia_chi, ngay_vao_vien, khoa_vao_vien, chan_doan, bac_si_chi_dinh) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    $successCount = 0;
    foreach ($demoData as $data) {
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
            $successCount++;
        }
    }
    
    $stmt->close();
    $conn->close();
    
    echo "✅ Đã thêm $successCount bản ghi demo vào bảng!\n";
}

// Run the setup
echo "========================================\n";
echo "THIẾT LẬP BẢNG NHẬP VIỆN\n";
echo "========================================\n\n";

if (createAdmissionsTable()) {
    insertDemoData();
}

echo "\n========================================\n";
echo "✅ HOÀN TẤT!\n";
echo "========================================\n";
echo "\nTruy cập: http://localhost/rankfiler/create_hospital_table.php\n";
echo "để chạy script này.\n";
?>
