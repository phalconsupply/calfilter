# RankFiler — Tài liệu dự án

Hệ thống quản lý **Lịch Việt Nam** và **theo dõi ca nhập viện** cho bệnh viện. Đây là tài liệu kỹ thuật tổng hợp cho việc phát triển, cài đặt và vận hành (đã gộp thông tin từ các script cài đặt/tiện ích một lần trước đây).

## 1. Tổng quan

- **Frontend:** HTML5, CSS3, JavaScript thuần (vanilla)
- **Backend:** PHP 7.4+ (mysqli), chạy trên XAMPP/Apache
- **Database:** MySQL/MariaDB — database `bvrank`, charset `utf8mb4`
- **Thư viện:** PhpSpreadsheet 1.29 (xử lý Excel) — commit sẵn trong `PhpSpreadsheet/vendor/` để deploy dễ dàng lên cPanel không cần composer

## 2. Cấu trúc thư mục (file production)

### Trang (UI)
| File | Mô tả |
|------|-------|
| `index.php` | Lịch Việt Nam (âm/dương lịch, ngày nghỉ, ghi chú) |
| `hospital_import.php` | Import dữ liệu nhập viện từ Excel + xem/lọc dữ liệu |
| `statistics.php` | Thống kê, xếp hạng bác sĩ, xuất báo cáo Excel |
| `header.php` | Menu điều hướng dùng chung cho 3 trang |

### API / Handler backend
| File | Endpoint (`?action=`) |
|------|-----------------------|
| `api.php` | `get_days_off`, `save_day_off`, `save_multiple_days_off`, `remove_day_off` |
| `hospital_api.php` | `get_admissions`, `add_admission`, `update_admission`, `delete_admission`, `search_admissions`, `get_statistics` |
| `statistics_api.php` | `generate`, `export` |
| `hospital_import_handler.php` | POST: upload & xử lý Excel; `?action=download_template`: tải file mẫu |
| `update_admission_times.php` | POST: **tính lại** `admission_time_type` cho toàn bộ bản ghi theo lịch nghỉ hiện tại (gọi từ `statistics.php` và `script.js` khi lịch thay đổi) |

### Tài nguyên tĩnh
- `style.css` — giao diện chung
- `script.js` — logic lịch phía client
- `lunar.js` — thuật toán chuyển đổi âm lịch
- `config.php` — cấu hình DB + khởi tạo bảng `calendar_days_off`
- `database/bvrank.sql` — dump schema + dữ liệu mẫu đầy đủ
- `thi_dua.csv` — dữ liệu tham chiếu

## 3. Cấu hình Database

Sửa `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');     // đổi theo môi trường
define('DB_PASS', '');         // đổi theo môi trường
define('DB_NAME', 'bvrank');
```
`config.php` tự động gọi `initDatabase()` tạo bảng `calendar_days_off` nếu chưa có.

## 4. Schema Database

### Bảng `calendar_days_off`
| Cột | Kiểu | Ghi chú |
|-----|------|---------|
| `id` | INT PK AUTO_INCREMENT | |
| `day_date` | DATE | UNIQUE, có index |
| `note` | TEXT | Ghi chú |
| `type` | VARCHAR(20) | `day_off` (nghỉ) hoặc `working_day` (làm bù), mặc định `day_off` |
| `created_at` / `updated_at` | TIMESTAMP | |

### Bảng `hospital_admissions`
| Cột | Kiểu | Ghi chú |
|-----|------|---------|
| `id` | INT PK AUTO_INCREMENT | |
| `ma_kcb` | VARCHAR(50) | Mã KCB, có index |
| `ho_ten_bn` | VARCHAR(255) | Họ tên bệnh nhân |
| `tuoi` | INT | Tuổi |
| `gioi_tinh` | VARCHAR(10) | Giới tính |
| `dia_chi` | TEXT | Địa chỉ |
| `ngay_vao_vien` | DATE | Ngày vào viện (có index) |
| `admission_datetime` | DATETIME | Ngày + giờ nhập viện (dùng để phân loại) |
| `admission_time_type` | VARCHAR(20) | `Đúng giờ` / `Ngoài giờ` |
| `khoa_vao_vien` | VARCHAR(255) | Khoa (có index) |
| `chan_doan` | TEXT | Chẩn đoán |
| `bac_si_chi_dinh` | VARCHAR(255) | Bác sĩ chỉ định |
| `created_at` / `updated_at` | TIMESTAMP | |

## 5. Cài đặt mới (fresh install)

1. Tạo database `bvrank` (phpMyAdmin / cPanel → MySQL Databases).
2. Import `database/bvrank.sql` — file này đã chứa **đầy đủ** cấu trúc 2 bảng (kèm các cột `type`, `admission_datetime`, `admission_time_type` đã migrate) và dữ liệu mẫu.
3. Cập nhật `config.php` với thông tin DB.
4. Đảm bảo PHP extensions: `zip`, `xml`, `gd`, `mbstring`.
5. Truy cập: `http://localhost/rankfiler/index.php`

> Lưu ý: Các script khởi tạo/migration cũ (`create_hospital_table.php`, `update_database.php`) đã được gỡ bỏ. Toàn bộ schema đã được hợp nhất vào `database/bvrank.sql`. Nếu cần tạo bảng thủ công, xem mục 4.

## 6. Lịch sử migration schema (tham khảo)

Schema hiện tại đã bao gồm các thay đổi sau (đã áp dụng, giữ lại để tham khảo lịch sử):
- Thêm cột `type` vào `calendar_days_off` (`day_off` / `working_day`).
- Thêm cột `admission_datetime` (DATETIME) vào `hospital_admissions`, copy từ `ngay_vao_vien`.
- Thêm cột `admission_time_type` (VARCHAR) vào `hospital_admissions`.
- Chuyển `ngay_vao_vien` từ DATETIME → DATE (tách ngày và giờ).

## 7. Quy tắc phân loại giờ nhập viện

Logic nằm trong `update_admission_times.php` (`calculateAdmissionTimeType`) và `hospital_import_handler.php`. Dựa trên `admission_datetime` và bảng ngày nghỉ.

### `Ngoài giờ` — nếu thỏa **1** trong các điều kiện:
- Cuối tuần: Thứ 7 hoặc Chủ nhật
- Trước giờ làm: < 7:00
- Giờ nghỉ trưa: 11:30 – 13:29 (690–809 phút)
- Sau giờ làm: ≥ 17:00
- Ngày được đánh dấu `day_off` trong lịch

### `Đúng giờ` — phải thỏa **tất cả**:
- Là ngày làm việc (T2–T6, không phải `day_off`)
- Sáng 7:00 – 11:29 **hoặc** chiều 13:30 – 16:59

> Ngày `working_day` (làm bù) không tự động biến cuối tuần thành đúng giờ trong logic hiện tại — chỉ `day_off` được kiểm tra. Cân nhắc khi mở rộng.

## 8. Import Excel

- Định dạng: `.xlsx`, `.xls`
- Handler: `hospital_import_handler.php` (POST multipart) — trả JSON
- Tải file mẫu: `hospital_import_handler.php?action=download_template`
- File mẫu tham chiếu: `docs/du_lieu_mau.xlsx`

### Cấu trúc file import (file xuất từ hệ thống bệnh viện)
File thật **không có dòng tiêu đề** (dữ liệu bắt đầu ngay từ dòng 1). Handler tự phát hiện và bỏ dòng tiêu đề nếu có (dùng file mẫu tải về), nên chấp nhận cả 2 loại.

| Cột | Nội dung | Ghi chú |
|-----|----------|---------|
| A | STT | Bỏ qua |
| B | Mã KCB | Bắt buộc |
| C | Họ tên BN | |
| D | Tuổi | |
| E | Giới tính | |
| F | Địa chỉ | |
| G | **Ngày giờ vào viện** | Gộp chung, vd `01/06/2026 8:40:00 AM` — **dd/mm/yyyy** + giờ 12h AM/PM |
| H | Khoa vào viện | |
| I | Chẩn đoán | |
| J | Bác sĩ chỉ định | Dùng để group xếp hạng |

### Xử lý (bóc tách → phân loại → lưu)
1. `parseAdmissionDateTime()` bóc tách cột G thành `ngay_vao_vien` (DATE) + `admission_datetime` (DATETIME). Hỗ trợ cả số serial Excel lẫn chuỗi `dd/mm/yyyy` kèm giờ 12h/24h. Ngày hiểu theo **dd/mm** (không phải kiểu Mỹ mm/dd).
2. `loadDaysOffMap()` nạp danh sách ngày nghỉ **một lần** vào mảng trước vòng lặp; `calculateAdmissionTimeType($datetime, $daysOffMap)` đối chiếu rule (mục 7) + lịch nghỉ → `Đúng giờ`/`Ngoài giờ` (không truy vấn DB cho từng dòng).
3. Lưu vào `hospital_admissions`.

### Quy tắc chống trùng
Khoá logic: **(`ma_kcb`, `ngay_vao_vien`)**.
- Cùng bệnh nhân + **cùng ngày** → coi là **1 lượt**, cập nhật bản ghi hiện có.
- Cùng bệnh nhân + **khác ngày** → **lượt nhập viện mới** (bản ghi mới).

Hệ thống chỉ đếm **số lượt** để xếp hạng thành tích bác sĩ, nên `ma_kcb` **không** unique toàn cục. Chống trùng thực hiện ở tầng code (tìm theo `ma_kcb + ngay_vao_vien` rồi UPDATE/INSERT), không cần ràng buộc UNIQUE ở DB.

> Lưu ý kỹ thuật: định dạng ngày `dd/mm/yyyy` là điểm dễ sai — `new DateTime()` / `strtotime()` mặc định hiểu dấu `/` theo kiểu Mỹ (mm/dd). Luôn dùng `parseAdmissionDateTime()` (đã cố định thứ tự dd/mm).

## 9. Triển khai cPanel

1. Upload toàn bộ code lên `public_html/` (bao gồm đầy đủ `PhpSpreadsheet/vendor/`).
2. Tạo database qua cPanel → MySQL Databases, import `database/bvrank.sql`.
3. Cập nhật `config.php` với thông tin DB từ cPanel.
4. Kiểm tra PHP extensions đã bật.

## 10. Quy ước phát triển

- PHP thuần, không framework; dùng `mysqli` với prepared statements.
- API trả JSON: `{ success: bool, message: string, ... }`.
- Charset toàn hệ thống `utf8mb4` (dữ liệu tiếng Việt có dấu).
- Khi thay đổi ngày nghỉ trong lịch, cần gọi `update_admission_times.php` để tính lại phân loại giờ cho dữ liệu cũ.

---
*Tài liệu này thay thế các file test/debug/tiện ích một lần đã được dọn dẹp.*
