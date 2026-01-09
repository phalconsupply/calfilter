<style>
    .nav-menu {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .nav-menu a {
        padding: 10px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .nav-menu a:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    .nav-menu a.active {
        background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
        box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
    }
</style>

<div class="nav-menu">
    <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
        <span>📅</span>
        <span>Lịch</span>
    </a>
    <a href="hospital_import.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'hospital_import.php' ? 'active' : ''; ?>">
        <span>📤</span>
        <span>Import dữ liệu</span>
    </a>
    <a href="statistics.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'statistics.php' ? 'active' : ''; ?>">
        <span>📊</span>
        <span>Thống kê</span>
    </a>
</div>
