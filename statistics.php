<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống Kê Nhập Viện</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 30px;
        }

        header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }

        header h1 {
            color: #333;
            font-size: 2.2em;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #666;
            font-size: 1.1em;
        }

        .control-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            font-size: 1.1em;
        }

        .form-controls {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .form-controls > div {
            flex: 1;
            min-width: 200px;
        }

        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1em;
            transition: border-color 0.3s ease;
        }

        select:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1.05em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .btn-delete {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
        }

        .btn-export {
            background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
        }

        .loading {
            text-align: center;
            padding: 40px;
            display: none;
        }

        .loading.active {
            display: block;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .report-section {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .report-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }

        .report-header h2 {
            font-size: 1.8em;
            margin-bottom: 10px;
        }

        .report-header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .ranking-section {
            margin-bottom: 40px;
        }

        .ranking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .ranking-header h3 {
            color: #333;
            font-size: 1.5em;
        }

        .ranking-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .ranking-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .ranking-table th,
        .ranking-table td {
            padding: 15px;
            text-align: left;
        }

        .ranking-table th {
            font-weight: bold;
            font-size: 1.05em;
        }

        .ranking-table tbody tr {
            border-bottom: 1px solid #e0e0e0;
            transition: background-color 0.2s ease;
        }

        .ranking-table tbody tr:hover {
            background-color: #f5f5f5;
        }

        .ranking-table tbody tr:last-child {
            border-bottom: none;
        }

        .rank-cell {
            font-weight: bold;
            font-size: 1.2em;
            text-align: center;
            width: 80px;
        }

        .rank-1 {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #333;
        }

        .rank-2 {
            background: linear-gradient(135deg, #c0c0c0 0%, #e8e8e8 100%);
            color: #333;
        }

        .rank-3 {
            background: linear-gradient(135deg, #cd7f32 0%, #daa520 100%);
            color: white;
        }

        .medal {
            font-size: 1.5em;
            margin-right: 5px;
        }

        .count-cell {
            font-weight: bold;
            color: #667eea;
            font-size: 1.1em;
            text-align: center;
        }

        .off-hours {
            color: #ff9800;
        }

        .on-time {
            color: #4caf50;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 1.1em;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            header h1 {
                font-size: 1.5em;
            }

            .form-controls {
                flex-direction: column;
            }

            .form-controls > div {
                width: 100%;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .ranking-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .ranking-table {
                font-size: 0.9em;
            }

            .ranking-table th,
            .ranking-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 Thống Kê Nhập Viện</h1>
            <p class="subtitle">Báo cáo và xếp hạng bác sĩ theo ca nhập viện</p>
        </header>

        <?php include 'header.php'; ?>

        <div class="control-section">
            <h3 style="margin-bottom: 20px; color: #333;">⚙️ Tạo báo cáo thống kê</h3>
            
            <div class="form-controls">
                <div class="form-group">
                    <label for="periodType">Loại thống kê:</label>
                    <select id="periodType">
                        <option value="month">Theo tháng</option>
                        <option value="year">Theo năm</option>
                    </select>
                </div>

                <div class="form-group" id="monthSelectGroup">
                    <label for="monthSelect">Chọn tháng:</label>
                    <select id="monthSelect">
                        <option value="">-- Chọn tháng --</option>
                    </select>
                </div>

                <div class="form-group" id="yearSelectGroup" style="display: none;">
                    <label for="yearSelect">Chọn năm:</label>
                    <select id="yearSelect">
                        <option value="">-- Chọn năm --</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button class="btn" id="generateReport" onclick="generateReport()">
                        <span>📊</span>
                        <span>Tạo báo cáo</span>
                    </button>
                    <button class="btn btn-delete" id="deleteReport" onclick="deleteAndRegenerate()" style="display: none;">
                        <span>🗑️</span>
                        <span>Xóa & Tạo lại</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Đang tạo báo cáo...</p>
        </div>

        <div class="report-section" id="reportSection">
            <div class="report-header">
                <h2 id="reportTitle">Báo cáo thống kê</h2>
                <p id="reportPeriod"></p>
            </div>

            <!-- Ranking for Off-hours Admissions -->
            <div class="ranking-section">
                <div class="ranking-header">
                    <h3>🌙 Xếp hạng ca Ngoài giờ</h3>
                    <button class="btn btn-export" onclick="exportToExcel('off-hours')">
                        <span>📥</span>
                        <span>Xuất Excel</span>
                    </button>
                </div>
                <div style="overflow-x: auto;">
                    <table class="ranking-table">
                        <thead>
                            <tr>
                                <th style="text-align: center;">Hạng</th>
                                <th>Bác sĩ</th>
                                <th style="text-align: center;">Số ca Ngoài giờ</th>
                            </tr>
                        </thead>
                        <tbody id="offHoursTable">
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ranking for On-time Admissions -->
            <div class="ranking-section">
                <div class="ranking-header">
                    <h3>☀️ Xếp hạng ca Đúng giờ</h3>
                    <button class="btn btn-export" onclick="exportToExcel('on-time')">
                        <span>📥</span>
                        <span>Xuất Excel</span>
                    </button>
                </div>
                <div style="overflow-x: auto;">
                    <table class="ranking-table">
                        <thead>
                            <tr>
                                <th style="text-align: center;">Hạng</th>
                                <th>Bác sĩ</th>
                                <th style="text-align: center;">Số ca Đúng giờ</th>
                            </tr>
                        </thead>
                        <tbody id="onTimeTable">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Generate month and year options
        function generateOptions() {
            const monthSelect = document.getElementById('monthSelect');
            const yearSelect = document.getElementById('yearSelect');
            const currentDate = new Date();
            
            // Generate months (last 24 months)
            for (let i = 0; i < 24; i++) {
                const date = new Date(currentDate.getFullYear(), currentDate.getMonth() - i, 1);
                const year = date.getFullYear();
                const month = date.getMonth() + 1;
                const value = `${year}-${month.toString().padStart(2, '0')}`;
                const text = `Tháng ${month}/${year}`;
                
                const option = document.createElement('option');
                option.value = value;
                option.textContent = text;
                
                if (i === 0) {
                    option.selected = true;
                }
                
                monthSelect.appendChild(option);
            }

            // Generate years (last 5 years)
            const currentYear = currentDate.getFullYear();
            for (let i = 0; i < 5; i++) {
                const year = currentYear - i;
                const option = document.createElement('option');
                option.value = year;
                option.textContent = `Năm ${year}`;
                
                if (i === 0) {
                    option.selected = true;
                }
                
                yearSelect.appendChild(option);
            }
        }

        // Toggle between month and year selection
        document.getElementById('periodType').addEventListener('change', function() {
            const isMonth = this.value === 'month';
            document.getElementById('monthSelectGroup').style.display = isMonth ? 'block' : 'none';
            document.getElementById('yearSelectGroup').style.display = isMonth ? 'none' : 'block';
        });

        // Generate report
        async function generateReport() {
            const periodType = document.getElementById('periodType').value;
            const period = periodType === 'month' 
                ? document.getElementById('monthSelect').value 
                : document.getElementById('yearSelect').value;
            
            if (!period) {
                alert('Vui lòng chọn thời gian thống kê');
                return;
            }

            const loading = document.getElementById('loading');
            const reportSection = document.getElementById('reportSection');
            const generateBtn = document.getElementById('generateReport');
            const deleteBtn = document.getElementById('deleteReport');
            
            loading.classList.add('active');
            reportSection.classList.remove('active');
            generateBtn.disabled = true;

            try {
                const response = await fetch(`statistics_api.php?action=generate&type=${periodType}&period=${period}`);
                const result = await response.json();

                if (result.success) {
                    displayReport(result.data, periodType, period);
                    deleteBtn.style.display = 'inline-flex';
                } else {
                    alert(`Lỗi: ${result.message}`);
                }
            } catch (error) {
                alert(`Lỗi kết nối: ${error.message}`);
            } finally {
                loading.classList.remove('active');
                generateBtn.disabled = false;
            }
        }

        // Display report data
        function displayReport(data, periodType, period) {
            const reportSection = document.getElementById('reportSection');
            const reportTitle = document.getElementById('reportTitle');
            const reportPeriod = document.getElementById('reportPeriod');
            const offHoursTable = document.getElementById('offHoursTable');
            const onTimeTable = document.getElementById('onTimeTable');

            // Set title
            if (periodType === 'month') {
                const [year, month] = period.split('-');
                reportTitle.textContent = `Báo cáo tháng ${parseInt(month)}/${year}`;
                reportPeriod.textContent = `Thống kê ca nhập viện tháng ${parseInt(month)}/${year}`;
            } else {
                reportTitle.textContent = `Báo cáo năm ${period}`;
                reportPeriod.textContent = `Thống kê ca nhập viện năm ${period}`;
            }

            // Render off-hours ranking
            offHoursTable.innerHTML = '';
            if (data.off_hours && data.off_hours.length > 0) {
                data.off_hours.forEach((item, index) => {
                    offHoursTable.appendChild(createRankingRow(index + 1, item.bac_si_chi_dinh, item.count, 'off-hours'));
                });
            } else {
                offHoursTable.innerHTML = '<tr><td colspan="3" class="no-data">Không có dữ liệu</td></tr>';
            }

            // Render on-time ranking
            onTimeTable.innerHTML = '';
            if (data.on_time && data.on_time.length > 0) {
                data.on_time.forEach((item, index) => {
                    onTimeTable.appendChild(createRankingRow(index + 1, item.bac_si_chi_dinh, item.count, 'on-time'));
                });
            } else {
                onTimeTable.innerHTML = '<tr><td colspan="3" class="no-data">Không có dữ liệu</td></tr>';
            }

            reportSection.classList.add('active');
        }

        // Create ranking row
        function createRankingRow(rank, doctor, count, type) {
            const tr = document.createElement('tr');
            
            // Rank cell
            const rankTd = document.createElement('td');
            rankTd.className = 'rank-cell';
            if (rank <= 3) {
                rankTd.classList.add(`rank-${rank}`);
            }
            
            const medals = ['🥇', '🥈', '🥉'];
            rankTd.innerHTML = rank <= 3 
                ? `<span class="medal">${medals[rank - 1]}</span>${rank}` 
                : rank;
            
            // Doctor name cell
            const doctorTd = document.createElement('td');
            doctorTd.textContent = doctor || '(Không có tên)';
            
            // Count cell
            const countTd = document.createElement('td');
            countTd.className = `count-cell ${type === 'off-hours' ? 'off-hours' : 'on-time'}`;
            countTd.textContent = count;
            
            tr.appendChild(rankTd);
            tr.appendChild(doctorTd);
            tr.appendChild(countTd);
            
            return tr;
        }

        // Delete and regenerate report
        async function deleteAndRegenerate() {
            if (!confirm('Bạn có chắc muốn xóa và tạo lại báo cáo? Hệ thống sẽ cập nhật dữ liệu theo lịch mới nhất.')) {
                return;
            }

            // First update admission times
            const updateResponse = await fetch('update_admission_times.php', {
                method: 'POST'
            });
            
            const updateResult = await updateResponse.json();
            
            if (updateResult.success) {
                // Then regenerate report
                await generateReport();
            } else {
                alert(`Lỗi cập nhật dữ liệu: ${updateResult.message}`);
            }
        }

        // Export to Excel
        async function exportToExcel(type) {
            const periodType = document.getElementById('periodType').value;
            const period = periodType === 'month' 
                ? document.getElementById('monthSelect').value 
                : document.getElementById('yearSelect').value;
            
            if (!period) {
                alert('Vui lòng tạo báo cáo trước khi xuất Excel');
                return;
            }

            window.location.href = `statistics_api.php?action=export&type=${periodType}&period=${period}&time_type=${type}`;
        }

        // Initialize
        generateOptions();
    </script>
</body>
</html>
