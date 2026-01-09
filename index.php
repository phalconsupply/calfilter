<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Việt Nam - Âm Dương Lịch</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🗓️ Lịch Việt Nam</h1>
            <p class="subtitle">Âm Lịch - Dương Lịch - Ngày Lễ</p>
        </header>

        <?php include 'header.php'; ?>

        <div class="calendar-controls">
            <button id="prevMonth" class="btn">◀ Tháng trước</button>
            <div class="current-month">
                <h2 id="currentMonth"></h2>
                <p id="lunarMonth" class="lunar-info"></p>
            </div>
            <button id="nextMonth" class="btn">Tháng sau ▶</button>
        </div>

        <div class="action-section">
            <div class="action-group">
                <h4>📅 Đánh dấu ngày nghỉ</h4>
                <button id="toggleMultiSelect" class="btn btn-multi">🏖️ Chọn nhiều ngày nghỉ</button>
            </div>
            <div class="action-group">
                <h4>💼 Đánh dấu ngày làm bù</h4>
                <button id="toggleWorkingDaySelect" class="btn btn-working">📅 Chọn ngày làm bù</button>
            </div>
            <div class="action-group">
                <h4>🔄 Cập nhật dữ liệu</h4>
                <button id="updateAdmissionsData" class="btn btn-update">🔄 Cập nhật loại giờ nhập viện</button>
            </div>
        </div>

        <div id="selectedDaysInfo" class="selected-days-info" style="display: none;">
            <span id="selectedCount">Đã chọn: 0 ngày</span>
            <span id="selectionMode" class="selection-mode"></span>
            <button id="clearSelection" class="btn btn-small">✖ Xóa chọn</button>
            <button id="saveMultipleDays" class="btn btn-small btn-save">💾 Lưu tất cả</button>
        </div>

        <div class="today-info">
            <div class="info-card">
                <h3>Hôm nay</h3>
                <p id="todaySolar"></p>
                <p id="todayLunar" class="lunar-text"></p>
            </div>
        </div>

        <div class="calendar-grid">
            <div class="weekday">CN</div>
            <div class="weekday">T2</div>
            <div class="weekday">T3</div>
            <div class="weekday">T4</div>
            <div class="weekday">T5</div>
            <div class="weekday">T6</div>
            <div class="weekday">T7</div>
        </div>

        <div id="calendarDays" class="calendar-days"></div>

        <div class="holidays-section">
            <h3>📅 Các Ngày Lễ Lớn Năm <span id="currentYear"></span></h3>
            <div id="holidaysList" class="holidays-list"></div>
        </div>
    </div>

    <!-- Modal for Day Off Note -->
    <div id="dayOffModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">📝 Đánh dấu ngày nghỉ</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p id="selectedDate" class="selected-date"></p>
                <div id="selectedDatesList" class="selected-dates-list" style="display: none;"></div>
                <label for="noteInput">Ghi chú:</label>
                <textarea id="noteInput" rows="4" placeholder="Nhập ghi chú cho ngày nghỉ..."></textarea>
                <div class="existing-note" id="existingNote" style="display: none;">
                    <strong>Ghi chú hiện tại:</strong>
                    <p id="currentNote"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button id="saveDayOff" class="btn btn-save">💾 Lưu ngày nghỉ</button>
                <button id="removeDayOff" class="btn btn-remove" style="display: none;">🗑️ Xóa đánh dấu</button>
                <button id="cancelModal" class="btn btn-cancel">❌ Hủy</button>
            </div>
        </div>
    </div>

    <script src="lunar.js"></script>
    <script src="script.js"></script>
</body>
</html>
