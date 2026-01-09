// Vietnamese Calendar Application
let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
const timeZone = 7;
let daysOffData = [];
let selectedDayElement = null;
let selectedDateInfo = null;

// Multi-select mode
let isMultiSelectMode = false;
let isWorkingDayMode = false;
let selectedDaysForBatch = new Set();
let currentSelectionType = 'day_off';

// Vietnamese holidays
const solarHolidays = {
    '1-1': 'Tết Dương Lịch', '2-14': 'Valentine', '3-8': 'Quốc tế Phụ nữ',
    '4-30': 'Ngày Giải phóng miền Nam', '5-1': 'Ngày Quốc tế Lao động',
    '6-1': 'Ngày Quốc tế Thiếu nhi', '9-2': 'Quốc khánh Việt Nam',
    '10-20': 'Ngày Phụ nữ Việt Nam', '11-20': 'Ngày Nhà giáo Việt Nam',
    '12-24': 'Đêm Noel', '12-25': 'Giáng sinh'
};

const lunarHolidays = {
    '1-1': 'Tết Nguyên Đán', '1-2': 'Mùng 2 Tết', '1-3': 'Mùng 3 Tết',
    '1-15': 'Tết Nguyên Tiêu', '3-10': 'Giỗ Tổ Hùng Vương', '4-15': 'Lễ Phật Đản',
    '5-5': 'Tết Đoan Ngọ', '7-15': 'Vu Lan', '8-15': 'Tết Trung Thu',
    '10-10': 'Tết Khmer', '12-23': 'Ông Táo chầu trời'
};

const canNames = ["Giáp", "Ất", "Bính", "Đinh", "Mậu", "Kỷ", "Canh", "Tân", "Nhâm", "Quý"];
const chiNames = ["Tý", "Sửu", "Dần", "Mão", "Thìn", "Tỵ", "Ngọ", "Mùi", "Thân", "Dậu", "Tuất", "Hợi"];
const monthNames = ["Tháng 1", "Tháng 2", "Tháng 3", "Tháng 4", "Tháng 5", "Tháng 6",
                   "Tháng 7", "Tháng 8", "Tháng 9", "Tháng 10", "Tháng 11", "Tháng 12"];

function getCanChi(year) {
    return `${canNames[(year + 6) % 10]} ${chiNames[(year + 8) % 12]}`;
}

function getLunarMonthName(lunarMonth, lunarLeap) {
    return lunarLeap ? `Tháng ${lunarMonth} nhuận` : `Tháng ${lunarMonth}`;
}

function formatDate(day, month, year) {
    return `${day.toString().padStart(2, '0')}/${month.toString().padStart(2, '0')}/${year}`;
}

function renderCalendar() {
    const firstDay = new Date(currentYear, currentMonth, 1);
    const lastDay = new Date(currentYear, currentMonth + 1, 0);
    const prevLastDay = new Date(currentYear, currentMonth, 0);
    
    const firstDayIndex = firstDay.getDay();
    const lastDayDate = lastDay.getDate();
    const prevLastDayDate = prevLastDay.getDate();
    
    document.getElementById('currentMonth').textContent = `${monthNames[currentMonth]} năm ${currentYear}`;
    
    const midMonthLunar = convertSolar2Lunar(15, currentMonth + 1, currentYear, timeZone);
    document.getElementById('lunarMonth').textContent = 
        `${getLunarMonthName(midMonthLunar[1], midMonthLunar[3])} năm ${getCanChi(midMonthLunar[2])}`;
    
    const today = new Date();
    const todayLunar = convertSolar2Lunar(today.getDate(), today.getMonth() + 1, today.getFullYear(), timeZone);
    document.getElementById('todaySolar').textContent = 
        `Dương lịch: ${formatDate(today.getDate(), today.getMonth() + 1, today.getFullYear())}`;
    document.getElementById('todayLunar').textContent = 
        `Âm lịch: ${todayLunar[0]}/${todayLunar[1]}/${todayLunar[2]} (${getCanChi(todayLunar[2])})`;
    
    const calendarDays = document.getElementById('calendarDays');
    calendarDays.innerHTML = '';
    
    for (let i = firstDayIndex - 1; i >= 0; i--) {
        calendarDays.appendChild(createDayElement(prevLastDayDate - i, currentMonth, currentYear, true));
    }
    
    for (let day = 1; day <= lastDayDate; day++) {
        calendarDays.appendChild(createDayElement(day, currentMonth + 1, currentYear, false));
    }
    
    const remainingDays = 42 - (firstDayIndex + lastDayDate);
    for (let day = 1; day <= remainingDays; day++) {
        calendarDays.appendChild(createDayElement(day, currentMonth + 2, currentYear, true));
    }
    
    document.getElementById('currentYear').textContent = currentYear;
    renderHolidaysList();
    loadDaysOff();
}

function createDayElement(day, month, year, isOtherMonth) {
    const dayDiv = document.createElement('div');
    dayDiv.className = 'day';
    
    let actualMonth = month;
    let actualYear = year;
    if (month === 0) { actualMonth = 12; actualYear = year - 1; }
    else if (month === 13) { actualMonth = 1; actualYear = year + 1; }
    
    if (isOtherMonth) dayDiv.classList.add('other-month');
    
    const today = new Date();
    if (day === today.getDate() && actualMonth === today.getMonth() + 1 && actualYear === today.getFullYear()) {
        dayDiv.classList.add('today');
    }
    
    const date = new Date(actualYear, actualMonth - 1, day);
    if (date.getDay() === 0) dayDiv.classList.add('sunday');
    
    const solarDiv = document.createElement('div');
    solarDiv.className = 'solar-date';
    solarDiv.textContent = day;
    dayDiv.appendChild(solarDiv);
    
    const lunarDate = convertSolar2Lunar(day, actualMonth, actualYear, timeZone);
    const lunarDiv = document.createElement('div');
    lunarDiv.className = 'lunar-date';
    lunarDiv.textContent = lunarDate[0] === 1 ? `${lunarDate[0]}/${lunarDate[1]}` : lunarDate[0];
    dayDiv.appendChild(lunarDiv);
    
    const holidayKey = `${actualMonth}-${day}`;
    if (solarHolidays[holidayKey]) {
        const holidayDiv = document.createElement('div');
        holidayDiv.className = 'holiday-label';
        holidayDiv.textContent = solarHolidays[holidayKey];
        dayDiv.appendChild(holidayDiv);
        dayDiv.classList.add('holiday');
    }
    
    const lunarHolidayKey = `${lunarDate[1]}-${lunarDate[0]}`;
    if (lunarHolidays[lunarHolidayKey]) {
        const holidayDiv = document.createElement('div');
        holidayDiv.className = 'holiday-label lunar-holiday';
        holidayDiv.textContent = lunarHolidays[lunarHolidayKey];
        dayDiv.appendChild(holidayDiv);
        dayDiv.classList.add('holiday');
    }
    
    const dateString = `${actualYear}-${actualMonth.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
    dayDiv.dataset.date = dateString;
    dayDiv.dataset.displayDate = formatDate(day, actualMonth, actualYear);
    
    if (!isOtherMonth) {
        dayDiv.style.cursor = 'pointer';
        dayDiv.addEventListener('click', function(e) {
            e.stopPropagation();
            if (isMultiSelectMode || isWorkingDayMode) {
                toggleDaySelection(this, dateString, formatDate(day, actualMonth, actualYear));
            } else {
                openDayOffModal(this, dateString, formatDate(day, actualMonth, actualYear));
            }
        });
    }
    
    return dayDiv;
}

function renderHolidaysList() {
    const holidaysList = document.getElementById('holidaysList');
    holidaysList.innerHTML = '';
    
    for (const [date, name] of Object.entries(solarHolidays)) {
        const [month, day] = date.split('-').map(Number);
        const holidayDiv = document.createElement('div');
        holidayDiv.className = 'holiday-item';
        holidayDiv.innerHTML = `
            <div class="holiday-date">${day.toString().padStart(2, '0')}/${month.toString().padStart(2, '0')}/${currentYear}</div>
            <div class="holiday-title">${name}</div>
        `;
        holidaysList.appendChild(holidayDiv);
    }
    
    for (const [date, name] of Object.entries(lunarHolidays)) {
        const [lunarMonth, lunarDay] = date.split('-').map(Number);
        const solarDate = convertLunar2Solar(lunarDay, lunarMonth, currentYear, 0, timeZone);
        const holidayDiv = document.createElement('div');
        holidayDiv.className = 'holiday-item lunar';
        holidayDiv.innerHTML = `
            <div class="holiday-date">${solarDate[0].toString().padStart(2, '0')}/${solarDate[1].toString().padStart(2, '0')}/${solarDate[2]} (${lunarDay}/${lunarMonth} AL)</div>
            <div class="holiday-title">${name}</div>
        `;
        holidaysList.appendChild(holidayDiv);
    }
}

async function loadDaysOff() {
    try {
        const response = await fetch(`api.php?action=get_days_off&month=${(currentMonth + 1).toString().padStart(2, '0')}&year=${currentYear}`);
        const result = await response.json();
        if (result.success) {
            daysOffData = result.data;
            updateDaysOffDisplay();
        }
    } catch (error) {
        console.error('Lỗi:', error);
    }
}

function updateDaysOffDisplay() {
    document.querySelectorAll('.day').forEach(day => {
        day.classList.remove('day-off', 'working-day');
        const existingNote = day.querySelector('.day-note');
        if (existingNote) existingNote.remove();
    });
    
    daysOffData.forEach(dayOff => {
        const dayElement = document.querySelector(`[data-date="${dayOff.date}"]`);
        if (dayElement) {
            dayElement.classList.add(dayOff.type === 'working_day' ? 'working-day' : 'day-off');
            if (dayOff.note) {
                const noteDiv = document.createElement('div');
                noteDiv.className = 'day-note';
                noteDiv.textContent = '📝';
                noteDiv.title = dayOff.note;
                dayElement.appendChild(noteDiv);
            }
        }
    });
}

function openDayOffModal(dayElement, dateString, displayDate) {
    selectedDayElement = dayElement;
    selectedDateInfo = { date: dateString, display: displayDate };
    
    const modal = document.getElementById('dayOffModal');
    const modalTitle = document.getElementById('modalTitle');
    const selectedDateEl = document.getElementById('selectedDate');
    const selectedDatesListEl = document.getElementById('selectedDatesList');
    const noteInput = document.getElementById('noteInput');
    const existingNoteDiv = document.getElementById('existingNote');
    const currentNoteEl = document.getElementById('currentNote');
    const removeDayOffBtn = document.getElementById('removeDayOff');
    
    modalTitle.innerHTML = currentSelectionType === 'working_day' ? '💼 Đánh dấu ngày làm bù' : '🏖️ Đánh dấu ngày nghỉ';
    
    if (selectedDaysForBatch.size > 0) {
        selectedDateEl.style.display = 'none';
        selectedDatesListEl.style.display = 'block';
        let datesHtml = `<strong>Đã chọn ${selectedDaysForBatch.size} ngày:</strong><br>`;
        selectedDaysForBatch.forEach(dateInfo => {
            const dateObj = JSON.parse(dateInfo);
            datesHtml += `<span class="date-chip">${dateObj.display}</span>`;
        });
        selectedDatesListEl.innerHTML = datesHtml;
        noteInput.value = '';
        existingNoteDiv.style.display = 'none';
        removeDayOffBtn.style.display = 'none';
    } else {
        selectedDateEl.style.display = 'block';
        selectedDatesListEl.style.display = 'none';
        selectedDateEl.textContent = `Ngày: ${displayDate}`;
        
        const existingDayOff = daysOffData.find(d => d.date === dateString);
        if (existingDayOff) {
            noteInput.value = existingDayOff.note || '';
            if (existingDayOff.note) {
                currentNoteEl.textContent = existingDayOff.note;
                existingNoteDiv.style.display = 'block';
            } else {
                existingNoteDiv.style.display = 'none';
            }
            removeDayOffBtn.style.display = 'inline-block';
        } else {
            noteInput.value = '';
            existingNoteDiv.style.display = 'none';
            removeDayOffBtn.style.display = 'none';
        }
    }
    
    modal.style.display = 'block';
}

function closeDayOffModal() {
    document.getElementById('dayOffModal').style.display = 'none';
}

async function saveDayOffToDatabase() {
    const note = document.getElementById('noteInput').value.trim();
    
    if (selectedDaysForBatch.size > 0) {
        await saveMultipleDaysOff(note);
        return;
    }
    
    if (!selectedDateInfo) return;
    
    try {
        const response = await fetch('api.php?action=save_day_off', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                date: selectedDateInfo.date,
                note: note,
                type: currentSelectionType
            })
        });
        
        const result = await response.json();
        if (result.success) {
            const existingIndex = daysOffData.findIndex(d => d.date === selectedDateInfo.date);
            if (existingIndex >= 0) {
                daysOffData[existingIndex].note = note;
                daysOffData[existingIndex].type = currentSelectionType;
            } else {
                daysOffData.push({ date: selectedDateInfo.date, note: note, type: currentSelectionType });
            }
            updateDaysOffDisplay();
            closeDayOffModal();
            showNotification(currentSelectionType === 'working_day' ? '✅ Đã lưu ngày làm bù!' : '✅ Đã lưu ngày nghỉ!', 'success');
        } else {
            showNotification('❌ Lỗi: ' + result.message, 'error');
        }
    } catch (error) {
        showNotification('❌ Không thể kết nối server', 'error');
    }
}

async function removeDayOffFromDatabase() {
    if (!selectedDateInfo || !confirm('Xóa đánh dấu này?')) return;
    
    try {
        const response = await fetch('api.php?action=remove_day_off', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ date: selectedDateInfo.date })
        });
        
        const result = await response.json();
        if (result.success) {
            daysOffData = daysOffData.filter(d => d.date !== selectedDateInfo.date);
            updateDaysOffDisplay();
            closeDayOffModal();
            showNotification('✅ Đã xóa!', 'success');
        }
    } catch (error) {
        showNotification('❌ Lỗi khi xóa', 'error');
    }
}

function toggleMultiSelectMode() {
    isMultiSelectMode = !isMultiSelectMode;
    isWorkingDayMode = false;
    currentSelectionType = 'day_off';
    
    const toggleBtn = document.getElementById('toggleMultiSelect');
    const workingBtn = document.getElementById('toggleWorkingDaySelect');
    const info = document.getElementById('selectedDaysInfo');
    const cal = document.getElementById('calendarDays');
    const mode = document.getElementById('selectionMode');
    
    if (isMultiSelectMode) {
        toggleBtn.classList.add('active');
        toggleBtn.textContent = '✓ Đang chọn ngày nghỉ';
        if (workingBtn) workingBtn.textContent = '📅 Chọn ngày làm bù';
        info.style.display = 'flex';
        cal.classList.add('multi-select-mode');
        mode.textContent = '(Chọn ngày nghỉ)';
        mode.style.display = 'inline';
    } else {
        toggleBtn.classList.remove('active');
        toggleBtn.textContent = '🏖️ Chọn nhiều ngày nghỉ';
        info.style.display = 'none';
        cal.classList.remove('multi-select-mode');
        mode.style.display = 'none';
        clearDaySelection();
    }
}

function toggleWorkingDayMode() {
    isWorkingDayMode = !isWorkingDayMode;
    isMultiSelectMode = false;
    currentSelectionType = 'working_day';
    
    const toggleBtn = document.getElementById('toggleMultiSelect');
    const workingBtn = document.getElementById('toggleWorkingDaySelect');
    const info = document.getElementById('selectedDaysInfo');
    const cal = document.getElementById('calendarDays');
    const mode = document.getElementById('selectionMode');
    
    if (isWorkingDayMode) {
        workingBtn.classList.add('active');
        workingBtn.textContent = '✓ Đang chọn ngày làm bù';
        if (toggleBtn) toggleBtn.textContent = '🏖️ Chọn nhiều ngày nghỉ';
        info.style.display = 'flex';
        cal.classList.add('multi-select-mode');
        mode.textContent = '(Chọn ngày làm bù)';
        mode.style.display = 'inline';
    } else {
        workingBtn.classList.remove('active');
        workingBtn.textContent = '📅 Chọn ngày làm bù';
        info.style.display = 'none';
        cal.classList.remove('multi-select-mode');
        mode.style.display = 'none';
        clearDaySelection();
    }
}

function toggleDaySelection(dayElement, dateString, displayDate) {
    const dateInfo = JSON.stringify({ date: dateString, display: displayDate });
    
    if (selectedDaysForBatch.has(dateInfo)) {
        selectedDaysForBatch.delete(dateInfo);
        dayElement.classList.remove('selected-for-batch', 'selected-for-working');
    } else {
        selectedDaysForBatch.add(dateInfo);
        dayElement.classList.add(currentSelectionType === 'working_day' ? 'selected-for-working' : 'selected-for-batch');
    }
    
    updateSelectedDaysCount();
}

function clearDaySelection() {
    selectedDaysForBatch.clear();
    document.querySelectorAll('.selected-for-batch, .selected-for-working').forEach(day => {
        day.classList.remove('selected-for-batch', 'selected-for-working');
    });
    updateSelectedDaysCount();
}

function updateSelectedDaysCount() {
    document.getElementById('selectedCount').textContent = `Đã chọn: ${selectedDaysForBatch.size} ngày`;
}

function openBatchModal() {
    if (selectedDaysForBatch.size === 0) {
        showNotification('⚠️ Chọn ít nhất một ngày', 'warning');
        return;
    }
    openDayOffModal(null, null, null);
}

async function saveMultipleDaysOff(note) {
    if (selectedDaysForBatch.size === 0) return;
    
    const dates = Array.from(selectedDaysForBatch).map(info => JSON.parse(info).date);
    
    try {
        const response = await fetch('api.php?action=save_multiple_days_off', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ dates, note, type: currentSelectionType })
        });
        
        const result = await response.json();
        if (result.success) {
            dates.forEach(date => {
                const idx = daysOffData.findIndex(d => d.date === date);
                if (idx >= 0) {
                    daysOffData[idx].note = note;
                    daysOffData[idx].type = currentSelectionType;
                } else {
                    daysOffData.push({ date, note, type: currentSelectionType });
                }
            });
            
            updateDaysOffDisplay();
            closeDayOffModal();
            clearDaySelection();
            if (isMultiSelectMode) toggleMultiSelectMode();
            if (isWorkingDayMode) toggleWorkingDayMode();
            
            showNotification(
                currentSelectionType === 'working_day' 
                    ? `✅ Đã lưu ${dates.length} ngày làm bù!`
                    : `✅ Đã lưu ${dates.length} ngày nghỉ!`,
                'success'
            );
        }
    } catch (error) {
        showNotification('❌ Lỗi kết nối', 'error');
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed; top: 20px; right: 20px; padding: 15px 25px;
        background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#ff9800'};
        color: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10000; animation: slideIn 0.3s ease-out;
    `;
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(400px); opacity: 0; } }
`;
document.head.appendChild(style);

function initCalendar() {
    document.getElementById('prevMonth').addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 0) { currentMonth = 11; currentYear--; }
        renderCalendar();
    });

    document.getElementById('nextMonth').addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 11) { currentMonth = 0; currentYear++; }
        renderCalendar();
    });

    document.querySelector('.close').addEventListener('click', closeDayOffModal);
    document.getElementById('cancelModal').addEventListener('click', closeDayOffModal);
    document.getElementById('saveDayOff').addEventListener('click', saveDayOffToDatabase);
    document.getElementById('removeDayOff').addEventListener('click', removeDayOffFromDatabase);

    window.addEventListener('click', (e) => {
        if (e.target === document.getElementById('dayOffModal')) closeDayOffModal();
    });

    document.getElementById('toggleMultiSelect').addEventListener('click', toggleMultiSelectMode);
    document.getElementById('toggleWorkingDaySelect').addEventListener('click', toggleWorkingDayMode);
    document.getElementById('clearSelection').addEventListener('click', clearDaySelection);
    document.getElementById('saveMultipleDays').addEventListener('click', openBatchModal);
    document.getElementById('updateAdmissionsData').addEventListener('click', updateAdmissionTimes);

    renderCalendar();
}

// Update admission times based on current calendar
async function updateAdmissionTimes() {
    const button = document.getElementById('updateAdmissionsData');
    button.disabled = true;
    button.textContent = '⏳ Đang cập nhật...';
    
    try {
        const response = await fetch('update_admission_times.php', {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(
                `✅ Cập nhật thành công ${result.updated} bản ghi!`, 
                'success'
            );
        } else {
            showNotification(`❌ Lỗi: ${result.message}`, 'error');
        }
    } catch (error) {
        showNotification(`❌ Lỗi kết nối: ${error.message}`, 'error');
    } finally {
        button.disabled = false;
        button.textContent = '🔄 Cập nhật loại giờ nhập viện';
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCalendar);
} else {
    initCalendar();
}
