# Vietnamese Calendar & Hospital Admission Tracking System

Hệ thống quản lý lịch Việt Nam và theo dõi ca nhập viện cho bệnh viện.

> 📄 Tài liệu kỹ thuật chi tiết (kiến trúc, schema, API, luồng import/xử lý, quy tắc chống trùng): xem [PROJECT.md](PROJECT.md).

## Tính năng

### 1. Lịch Việt Nam (index.php)
- Hiển thị lịch dương với thông tin âm lịch
- Đánh dấu ngày nghỉ và ngày làm bù
- Chế độ chọn nhiều ngày
- Lưu ghi chú cho từng ngày
- Hiển thị các ngày lễ Việt Nam

### 2. Import Dữ Liệu (hospital_import.php)
- Import dữ liệu nhập viện từ file Excel (.xlsx, .xls)
- Tự động tính toán loại giờ nhập viện (Đúng giờ/Ngoài giờ)
- Xem và lọc dữ liệu đã import theo tháng
- Tải file mẫu Excel

### 3. Thống Kê (statistics.php)
- Báo cáo thống kê theo tháng hoặc năm
- Xếp hạng bác sĩ theo số ca Đúng giờ/Ngoài giờ
- Xuất báo cáo Excel chi tiết
- Cập nhật và tạo lại báo cáo

## Công nghệ sử dụng

- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Backend:** PHP 7.4+
- **Database:** MySQL/MariaDB
- **Libraries:** PhpSpreadsheet 1.29 (Excel processing)

## Cài đặt

### Yêu cầu
- PHP >= 7.4
- MySQL/MariaDB
- Apache/Nginx web server
- PHP Extensions: zip, xml, gd, mbstring

### Các bước cài đặt

1. **Clone repository**
```bash
git clone https://github.com/phalconsupply/calfilter.git
cd calfilter
```

2. **Tạo database**
- Tạo database mới tên `bvrank`
- Import schema + dữ liệu mẫu từ file `database/bvrank.sql` (đã chứa đầy đủ cấu trúc 2 bảng)

3. **Cấu hình database**
Sửa file `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'bvrank');
```

4. **Phân quyền**
```bash
chmod 755 -R .
chmod 644 *.php
```

5. **Truy cập**
- Mở trình duyệt: `http://localhost/rankfiler/index.php`

## Cấu trúc Database

### Bảng `calendar_days_off`
- `id` - Primary key
- `day_date` - Ngày (DATE)
- `type` - Loại (day_off/working_day)
- `note` - Ghi chú
- `created_at`, `updated_at` - Timestamps

### Bảng `hospital_admissions`
- `id` - Primary key
- `ma_kcb` - Mã khám chữa bệnh (chống trùng theo `ma_kcb` + `ngay_vao_vien`)
- `ho_ten_bn` - Họ tên bệnh nhân
- `tuoi` - Tuổi
- `gioi_tinh` - Giới tính
- `dia_chi` - Địa chỉ
- `ngay_vao_vien` - Ngày vào viện (DATE)
- `admission_datetime` - Ngày giờ nhập viện (DATETIME)
- `admission_time_type` - Loại giờ (Đúng giờ/Ngoài giờ)
- `khoa_vao_vien` - Khoa
- `chan_doan` - Chẩn đoán
- `bac_si_chi_dinh` - Bác sĩ chỉ định

## Quy tắc phân loại giờ nhập viện

### Ngoài giờ (nếu thỏa 1 trong các điều kiện):
- Cuối tuần (Thứ 7 hoặc Chủ nhật)
- Trước giờ làm (< 7:00 AM)
- Giờ nghỉ trưa (11:30 AM - 1:29 PM)
- Sau giờ làm (≥ 5:00 PM)
- Ngày được đánh dấu nghỉ trong lịch

### Đúng giờ (phải thỏa tất cả):
- Sáng: 7:00 AM - 11:29 AM
- Chiều: 1:30 PM - 4:59 PM
- Ngày làm việc (Thứ 2-6, không phải ngày nghỉ)

## Triển khai trên cPanel

1. Upload toàn bộ code lên `public_html/`
2. Đảm bảo upload đầy đủ folder `PhpSpreadsheet/vendor/`
3. Tạo database qua cPanel → MySQL Databases
4. Import database schema
5. Cập nhật `config.php` với thông tin database từ cPanel
6. Kiểm tra PHP extensions đã enable

## Tác giả

Developed for Hospital Ranking System

## License

Proprietary - All rights reserved
