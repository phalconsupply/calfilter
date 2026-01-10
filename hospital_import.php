<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Dữ Liệu Nhập Viện</title>
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

        .import-section {
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

        .form-group select,
        .form-group input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1em;
            transition: border-color 0.3s ease;
        }

        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            background: white;
            border: 3px dashed #667eea;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.1em;
            color: #667eea;
        }

        .file-input-label:hover {
            background: #f0f4ff;
            border-color: #5568d3;
        }

        .file-input-label.has-file {
            background: #e8f5e9;
            border-color: #4caf50;
            color: #2e7d32;
        }

        input[type="file"] {
            display: none;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
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

        .btn-download {
            background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
        }

        .btn-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .result-section {
            display: none;
            margin-top: 30px;
            padding: 25px;
            border-radius: 15px;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .result-section.success {
            background: #e8f5e9;
            border: 2px solid #4caf50;
        }

        .result-section.error {
            background: #ffebee;
            border: 2px solid #f44336;
        }

        .result-header {
            font-size: 1.3em;
            font-weight: bold;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .result-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .data-table-section {
            margin-top: 30px;
            display: none;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .table-header h3 {
            color: #333;
        }

        .search-box {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            width: 300px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .data-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
        }

        .data-table th {
            font-weight: bold;
            font-size: 0.95em;
        }

        .data-table tbody tr {
            border-bottom: 1px solid #e0e0e0;
            transition: background-color 0.2s ease;
        }

        .data-table tbody tr:hover {
            background-color: #f5f5f5;
        }

        .data-table tbody tr:last-child {
            border-bottom: none;
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

        .error-list {
            background: white;
            padding: 15px;
            border-radius: 8px;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 15px;
        }

        .error-item {
            padding: 8px;
            border-left: 3px solid #f44336;
            margin-bottom: 8px;
            background: #ffebee;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            header h1 {
                font-size: 1.5em;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .search-box {
                width: 100%;
            }

            .data-table {
                font-size: 0.9em;
            }

            .data-table th,
            .data-table td {
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🏥 Import Dữ Liệu Nhập Viện</h1>
            <p class="subtitle">Nhập dữ liệu từ file Excel (.xlsx, .xls)</p>
        </header>

        <?php include 'header.php'; ?>

        <div class="import-section">
            <form id="importForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label>📂 Chọn file Excel:</label>
                    <div class="file-input-wrapper">
                        <label for="fileInput" class="file-input-label" id="fileLabel">
                            <span id="fileIcon">📁</span>
                            <span id="fileName">Chọn file hoặc kéo thả vào đây</span>
                        </label>
                        <input type="file" id="fileInput" name="file" accept=".xlsx,.xls" required>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn" id="uploadBtn">
                        <span>📤</span>
                        <span>Tải lên và Import</span>
                    </button>
                    <button type="button" class="btn btn-download" onclick="downloadTemplate()">
                        <span>📥</span>
                        <span>Tải file mẫu</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Đang xử lý dữ liệu...</p>
        </div>

        <!-- View Data Section -->
        <div class="import-section" style="margin-top: 30px;">
            <h2 style="color: #333; margin-bottom: 20px;">📊 Xem Dữ Liệu Đã Nhập</h2>
            
            <div class="form-group" style="display: flex; gap: 15px; align-items: flex-end;">
                <div style="flex: 1;">
                    <label for="viewMonthSelect">📅 Chọn tháng xem:</label>
                    <select id="viewMonthSelect" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 1em;">
                        <option value="">-- Chọn tháng --</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label for="timeTypeFilter">⏰ Lọc theo loại giờ:</label>
                    <select id="timeTypeFilter" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 1em;">
                        <option value="">Tất cả</option>
                        <option value="Đúng giờ">Đúng giờ</option>
                        <option value="Ngoài giờ">Ngoài giờ</option>
                    </select>
                </div>
                <button type="button" class="btn" onclick="loadAdmissionData()" style="flex: 0 0 auto;">
                    <span>🔍</span>
                    <span>Xem dữ liệu</span>
                </button>
            </div>
        </div>

        <div class="data-table-section" id="dataTableSection">
            <div class="table-header">
                <h3 id="dataTableTitle">Dữ liệu nhập viện</h3>
                <input type="text" class="search-box" id="searchBox" placeholder="🔍 Tìm kiếm..." onkeyup="filterTable()">
            </div>
            
            <div style="overflow-x: auto;">
                <table class="data-table" id="dataTable">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã KCB</th>
                            <th>Họ tên BN</th>
                            <th>Tuổi</th>
                            <th>Giới tính</th>
                            <th>Địa chỉ</th>
                            <th>Ngày vào viện</th>
                            <th>Giờ nhập viện</th>
                            <th>Loại giờ</th>
                            <th>Khoa</th>
                            <th>Chẩn đoán</th>
                            <th>Bác sĩ</th>
                        </tr>
                    </thead>
                    <tbody id="dataTableBody">
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 15px; text-align: center; color: #666;" id="tableStats">
            </div>
        </div>

        <div class="result-section" id="resultSection">
            <div class="result-header" id="resultHeader"></div>
            <div class="result-stats" id="resultStats"></div>
            <div class="error-list" id="errorList" style="display: none;"></div>
        </div>

        <div class="data-table-section" id="dataTableSection">
            <div class="table-header">
                <h3>📋 Dữ liệu đã import</h3>
                <input type="text" class="search-box" id="searchBox" placeholder="🔍 Tìm kiếm...">
            </div>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã KCB</th>
                            <th>Họ tên BN</th>
                            <th>Tuổi</th>
                            <th>Giới tính</th>
                            <th>Địa chỉ</th>
                            <th>Ngày vào viện</th>
                            <th>Khoa</th>
                            <th>Chẩn đoán</th>
                            <th>Bác sĩ</th>
                        </tr>
                    </thead>
                    <tbody id="dataTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Generate month options for view section only
        function generateMonthOptions() {
            const select = document.getElementById('viewMonthSelect');
            const currentDate = new Date();
            
            for (let i = 0; i < 24; i++) {
                const date = new Date(currentDate.getFullYear(), currentDate.getMonth() - i, 1);
                const month = date.getMonth() + 1;
                const year = date.getFullYear();
                const value = `${year}-${month.toString().padStart(2, '0')}`;
                const text = `Tháng ${month}/${year}`;
                
                const option = document.createElement('option');
                option.value = value;
                option.textContent = text;
                
                if (i === 0) {
                    option.selected = true;
                }
                
                select.appendChild(option);
            }
        }

        // File input handling
        const fileInput = document.getElementById('fileInput');
        const fileLabel = document.getElementById('fileLabel');
        const fileName = document.getElementById('fileName');
        const fileIcon = document.getElementById('fileIcon');

        fileInput.addEventListener('change', function(e) {
            if (this.files.length > 0) {
                const file = this.files[0];
                fileName.textContent = file.name;
                fileIcon.textContent = '✅';
                fileLabel.classList.add('has-file');
            } else {
                fileName.textContent = 'Chọn file hoặc kéo thả vào đây';
                fileIcon.textContent = '📁';
                fileLabel.classList.remove('has-file');
            }
        });

        // Drag and drop
        fileLabel.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.background = '#e3f2fd';
        });

        fileLabel.addEventListener('dragleave', function(e) {
            this.style.background = '';
        });

        fileLabel.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.background = '';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });

        // Form submission
        document.getElementById('importForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const uploadBtn = document.getElementById('uploadBtn');
            const loading = document.getElementById('loading');
            const resultSection = document.getElementById('resultSection');
            
            uploadBtn.disabled = true;
            loading.classList.add('active');
            resultSection.style.display = 'none';
            
            try {
                const response = await fetch('hospital_import_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                loading.classList.remove('active');
                displayResult(result);
                
                if (result.success && result.data.length > 0) {
                    displayDataTable(result.data);
                }
                
            } catch (error) {
                loading.classList.remove('active');
                displayResult({
                    success: false,
                    message: 'Lỗi kết nối: ' + error.message
                });
            } finally {
                uploadBtn.disabled = false;
            }
        });

        function displayResult(result) {
            const resultSection = document.getElementById('resultSection');
            const resultHeader = document.getElementById('resultHeader');
            const resultStats = document.getElementById('resultStats');
            const errorList = document.getElementById('errorList');
            
            resultSection.style.display = 'block';
            
            if (result.success) {
                resultSection.className = 'result-section success';
                resultHeader.innerHTML = '<span>✅</span> Import thành công!';
                
                resultStats.innerHTML = `
                    <div class="stat-card">
                        <div class="stat-value">${result.total_rows || 0}</div>
                        <div class="stat-label">Tổng số dòng</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: #4caf50;">${result.success_count || 0}</div>
                        <div class="stat-label">Thành công</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: #f44336;">${result.error_count || 0}</div>
                        <div class="stat-label">Lỗi</div>
                    </div>
                `;
                
                if (result.errors && result.errors.length > 0) {
                    errorList.style.display = 'block';
                    errorList.innerHTML = '<strong>Chi tiết lỗi:</strong><br>' +
                        result.errors.map(err => `<div class="error-item">Dòng ${err.row}: ${err.message}</div>`).join('');
                } else {
                    errorList.style.display = 'none';
                }
            } else {
                resultSection.className = 'result-section error';
                resultHeader.innerHTML = '<span>❌</span> Import thất bại!';
                resultStats.innerHTML = `<p>${result.message}</p>`;
                errorList.style.display = 'none';
            }
        }

        // Load admission data by month
        async function loadAdmissionData() {
            const monthSelect = document.getElementById('viewMonthSelect');
            const timeTypeFilter = document.getElementById('timeTypeFilter');
            const selectedMonth = monthSelect.value;
            
            if (!selectedMonth) {
                alert('Vui lòng chọn tháng để xem dữ liệu');
                return;
            }
            
            const loading = document.getElementById('loading');
            loading.classList.add('active');
            
            try {
                const response = await fetch(`hospital_api.php?action=get_admissions&month=${selectedMonth}&time_type=${timeTypeFilter.value}`);
                const result = await response.json();
                
                loading.classList.remove('active');
                
                if (result.success) {
                    displayAdmissionData(result.data, selectedMonth);
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                loading.classList.remove('active');
                alert('Lỗi kết nối: ' + error.message);
            }
        }

        function displayAdmissionData(data, month) {
            const dataTableSection = document.getElementById('dataTableSection');
            const dataTableBody = document.getElementById('dataTableBody');
            const dataTableTitle = document.getElementById('dataTableTitle');
            const tableStats = document.getElementById('tableStats');
            const timeTypeFilter = document.getElementById('timeTypeFilter').value;
            
            dataTableSection.style.display = 'block';
            
            // Update title
            let titleText = `Dữ liệu nhập viện tháng ${month}`;
            if (timeTypeFilter) {
                titleText += ` - Loại giờ: ${timeTypeFilter}`;
            }
            dataTableTitle.textContent = titleText;
            
            // Clear existing data
            dataTableBody.innerHTML = '';
            
            if (data.length === 0) {
                dataTableBody.innerHTML = '<tr><td colspan="12" style="text-align: center; padding: 30px; color: #999;">Không có dữ liệu</td></tr>';
                tableStats.textContent = '';
                return;
            }
            
            // Count by time type
            const onTimeCount = data.filter(row => row.admission_time_type === 'Đúng giờ').length;
            const offTimeCount = data.filter(row => row.admission_time_type === 'Ngoài giờ').length;
            
            // Display data
            data.forEach((row, index) => {
                const tr = document.createElement('tr');
                
                // Format datetime
                let timeDisplay = '';
                if (row.admission_datetime) {
                    const dt = new Date(row.admission_datetime);
                    timeDisplay = dt.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
                }
                
                // Format date
                let dateDisplay = row.ngay_vao_vien;
                if (row.ngay_vao_vien) {
                    const d = new Date(row.ngay_vao_vien);
                    dateDisplay = d.toLocaleDateString('vi-VN');
                }
                
                // Style for time type
                const timeTypeClass = row.admission_time_type === 'Đúng giờ' ? 
                    'style="color: #4caf50; font-weight: bold;"' : 
                    'style="color: #ff9800; font-weight: bold;"';
                
                tr.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${row.ma_kcb || ''}</td>
                    <td style="font-weight: 500;">${row.ho_ten_bn || ''}</td>
                    <td>${row.tuoi || ''}</td>
                    <td>${row.gioi_tinh || ''}</td>
                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;" title="${row.dia_chi || ''}">${row.dia_chi || ''}</td>
                    <td>${dateDisplay}</td>
                    <td>${timeDisplay}</td>
                    <td ${timeTypeClass}>${row.admission_time_type || ''}</td>
                    <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis;" title="${row.khoa_vao_vien || ''}">${row.khoa_vao_vien || ''}</td>
                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;" title="${row.chan_doan || ''}">${row.chan_doan || ''}</td>
                    <td>${row.bac_si_chi_dinh || ''}</td>
                `;
                
                dataTableBody.appendChild(tr);
            });
            
            // Display statistics
            tableStats.innerHTML = `
                <strong>Tổng số ca: ${data.length}</strong> | 
                <span style="color: #4caf50;">Đúng giờ: ${onTimeCount}</span> | 
                <span style="color: #ff9800;">Ngoài giờ: ${offTimeCount}</span>
            `;
        }

        function filterTable() {
            const searchBox = document.getElementById('searchBox');
            const filter = searchBox.value.toLowerCase();
            const table = document.getElementById('dataTable');
            const tbody = table.getElementsByTagName('tbody')[0];
            const rows = tbody.getElementsByTagName('tr');
            
            let visibleCount = 0;
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length; j++) {
                    const cell = cells[j];
                    if (cell) {
                        const textValue = cell.textContent || cell.innerText;
                        if (textValue.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                if (found) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }
            
            // Update row numbers for visible rows
            let visibleIndex = 1;
            for (let i = 0; i < rows.length; i++) {
                if (rows[i].style.display !== 'none') {
                    rows[i].cells[0].textContent = visibleIndex++;
                }
            }
        }

        function displayDataTable(data) {
            const tableSection = document.getElementById('dataTableSection');
            const tableBody = document.getElementById('dataTableBody');
            
            tableBody.innerHTML = '';
            
            data.forEach((row, index) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${row.ma_kcb || ''}</td>
                    <td>${row.ho_ten_bn || ''}</td>
                    <td>${row.tuoi || ''}</td>
                    <td>${row.gioi_tinh || ''}</td>
                    <td>${row.dia_chi || ''}</td>
                    <td>${row.ngay_vao_vien ? formatDateTime(row.ngay_vao_vien) : ''}</td>
                    <td>${row.khoa_vao_vien || ''}</td>
                    <td>${row.chan_doan || ''}</td>
                    <td>${row.bac_si_chi_dinh || ''}</td>
                `;
                tableBody.appendChild(tr);
            });
            
            tableSection.style.display = 'block';
        }

        function formatDateTime(dateStr) {
            const date = new Date(dateStr);
            const day = date.getDate().toString().padStart(2, '0');
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            const year = date.getFullYear();
            const hours = date.getHours().toString().padStart(2, '0');
            const minutes = date.getMinutes().toString().padStart(2, '0');
            return `${day}/${month}/${year} ${hours}:${minutes}`;
        }

        // Search functionality
        document.getElementById('searchBox').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#dataTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Download template
        function downloadTemplate() {
            window.location.href = 'hospital_import_handler.php?action=download_template';
        }

        // Initialize
        generateMonthOptions();
        
        // Auto-reload data when filter changes
        document.getElementById('timeTypeFilter').addEventListener('change', function() {
            if (document.getElementById('viewMonthSelect').value) {
                loadAdmissionData();
            }
        });
    </script>
</body>
</html>
