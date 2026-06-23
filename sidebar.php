<?php
$current_page = basename($_SERVER['PHP_SELF']);
// Load system settings
$_settings_file = __DIR__ . '/settings.json';
$_settings = file_exists($_settings_file) ? (json_decode(file_get_contents($_settings_file), true) ?? []) : [];
$_sys_name = $_settings['system_name'] ?? 'ລະບົບສາງເຄື່ອງສໍາອາງ';
$_sys_logo = $_settings['system_logo'] ?? '';
?>
<style>
.custom-sidebar {
    background-color: #A0243D !important;
    font-family: 'noto-sans-lao-regular', sans-serif !important;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    border-right: none !important;
    padding: 0 !important;
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

@media (min-width: 768px) {
    .custom-sidebar {
        position: fixed !important;
        top: 0;
        left: 0;
        bottom: 0;
        height: 100vh !important;
        flex: 0 0 280px !important;
        width: 280px !important;
        max-width: 280px !important;
        overflow: hidden !important; /* Hide scrollbar here */
        z-index: 1000;
    }
    .custom-sidebar + div, .custom-sidebar + .col-md-10 {
        margin-left: 280px !important;
        flex: 1 !important;
        width: calc(100% - 280px) !important;
        max-width: calc(100% - 280px) !important;
        transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1), 
                    width 0.3s cubic-bezier(0.4, 0, 0.2, 1), 
                    max-width 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
}

/* Collapsed state styles */
@media (min-width: 768px) {
    body.sidebar-collapsed .custom-sidebar {
        width: 80px !important;
        max-width: 80px !important;
        flex: 0 0 80px !important;
    }
    body.sidebar-collapsed .custom-sidebar + div, 
    body.sidebar-collapsed .custom-sidebar + .col-md-10 {
        margin-left: 80px !important;
        width: calc(100% - 80px) !important;
        max-width: calc(100% - 80px) !important;
    }
}

/* Hide header title in collapsed state */
body.sidebar-collapsed .custom-sidebar-title {
    opacity: 0 !important;
    visibility: hidden !important;
    width: 0 !important;
    height: 0 !important;
    overflow: hidden !important;
    margin: 0 !important;
    transition: opacity 0.2s ease, visibility 0.2s ease !important;
}

/* Adjust header logo box position in collapsed state */
body.sidebar-collapsed .custom-sidebar-header {
    justify-content: center !important;
    padding: 0 !important;
    gap: 0 !important;
}

/* Adjust menu items to show only icon */
body.sidebar-collapsed .custom-sidebar-item {
    justify-content: center !important;
    padding: 13px 0 !important;
    font-size: 0 !important; /* Hide raw text */
    gap: 0 !important;
}

body.sidebar-collapsed .custom-sidebar-icon {
    margin: 0 !important;
}

/* Adjust logout button in collapsed state */
body.sidebar-collapsed .custom-sidebar-footer {
    padding: 12px 0 !important;
    display: flex !important;
    justify-content: center !important;
}

body.sidebar-collapsed .custom-logout-btn {
    width: 30px !important;
    height: 30px !important;
    padding: 0 !important;
    margin: 0 auto !important; /* Center horizontally */
    border-radius: 6px !important; /* Rounded square shape */
    background: #ffffff !important; /* Solid white background */
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1) !important;
    justify-content: center !important;
    align-items: center !important;
    gap: 0 !important;
}

body.sidebar-collapsed .custom-logout-btn:hover {
    background: #ffffff !important;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2) !important;
    transform: scale(1.05);
}

body.sidebar-collapsed .custom-logout-btn .logout-text {
    display: none !important;
}

body.sidebar-collapsed .custom-logout-btn .logout-arrow {
    background-color: transparent !important; /* Remove grey background circle */
    color: #D4879A !important; /* Teal arrow matching background color */
    width: auto !important;
    height: auto !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    margin: 0 !important;
}

body.sidebar-collapsed .custom-logout-btn .logout-arrow svg {
    width: 14px !important;
    height: 14px !important;
}
body.sidebar-collapsed .sidebar-version-text {
    display: none !important;
}

/* Scrollable container for header, menu and footer */
.custom-sidebar-content {
    display: flex;
    flex-direction: column;
    height: 100vh !important;
    overflow-y: auto !important;
    flex-grow: 1;
}

/* Custom Scrollbar for sidebar content */
.custom-sidebar-content::-webkit-scrollbar {
    width: 6px;
}
.custom-sidebar-content::-webkit-scrollbar-track {
    background: transparent;
}
.custom-sidebar-content::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.25);
    border-radius: 3px;
}
.custom-sidebar-content::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.4);
}

.custom-sidebar-header {
    background-color: #7A1530 !important;
    height: 70px !important;
    min-height: 70px !important;
    flex-shrink: 0 !important;
    padding: 0 20px !important;
    display: flex;
    align-items: center;
    gap: 14px;
    border-bottom: none !important; /* Remove bottom border */
    transition: padding 0.3s ease, gap 0.3s ease !important;
}

.custom-sidebar-logo-box {
    background-color: #ffffff;
    color: #333333;
    font-size: 12px;
    font-weight: bold;
    width: 40px !important;
    height: 40px !important;
    display: flex;
    align-items: center;
    justify-content: center;   
    flex-shrink: 0;
    letter-spacing: 0.5px;
}

.custom-sidebar-title {
    color: #ffffff;
    font-size: 18px !important;
    font-weight: bold;
    margin: 0;
    line-height: 1.3;
}

.custom-sidebar-menu {
    display: flex;
    flex-direction: column;
    padding: 10px 0;
}

.custom-sidebar-item {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    gap: 14px !important;
    padding: 13px 24px !important;
    color: #ffffff !important;
    text-decoration: none !important;
    font-size: 17px !important;
    font-weight: 500 !important;
    transition: background-color 0.2s ease !important;
    border-bottom: none !important;
    background: transparent !important;
    white-space: nowrap !important;
}

.custom-sidebar-item:hover {
    background-color: #cc5472ff !important;
    color: #ffffff !important;
}

.custom-sidebar-item.active-menu {
    background-color: #cc5472ff !important;
    color: #ffffff !important;
    font-weight: bold;
}

.custom-sidebar-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    color: #ffffff;
    flex-shrink: 0;
}

.custom-sidebar-footer {
    padding: 12px 20px 20px 20px;
    margin-top: 1px !important;
}

.custom-logout-btn {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: center !important;
    background: #ec285cff !important;
    color: #ffffff !important;
    padding: 10px 18px !important;
    border-radius: 50px !important;
    text-decoration: none !important;
    font-weight: bold !important;
    font-size: 16px !important;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15) !important;
    transition: all 0.2s ease !important;
    width: 75% !important;
    margin: 0 auto !important;
    box-sizing: border-box !important;
    gap: 10px !important;
}

.custom-logout-btn:hover {
    background: #7A1530 !important;
    box-shadow: 0 5px 12px rgba(0, 0, 0, 0.2) !important;
    color: #ffffff !important;
}

.custom-logout-btn .logout-text {
    flex-shrink: 0 !important;
    color: #ffffff !important;
}

.custom-logout-btn .logout-arrow {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
    width: 24px !important;
    height: 24px !important;
    color: #ffffff !important;
    background: none !important;
}

.custom-logout-btn .logout-arrow svg circle {
    fill: #ffffff !important;
    transition: fill 0.2s ease;
}

.custom-logout-btn .logout-arrow svg path {
    stroke: #ff9100 !important;
    transition: stroke 0.2s ease;
}

/* Override for collapsed state */
body.sidebar-collapsed .custom-logout-btn .logout-arrow svg circle {
    fill: none !important;
}

body.sidebar-collapsed .custom-logout-btn .logout-arrow svg path {
    stroke: #00ADA2 !important;
}
</style>

<div class="col-md-2 sidebar p-0 custom-sidebar">
    <div class="custom-sidebar-content">
    <div class="custom-sidebar-header">
        <div class="custom-sidebar-logo-box">
            <?php if (!empty($_sys_logo) && file_exists(__DIR__ . '/' . $_sys_logo)): ?>
                <img src="<?= htmlspecialchars($_sys_logo) ?>" alt="Logo" style="width:100%; height:100%; object-fit:cover;">
            <?php else: ?>
                <span style="font-size:11px; font-weight:bold; color:#333;">Logo</span>
            <?php endif; ?>
        </div>
        <h5 class="custom-sidebar-title"><?= htmlspecialchars($_sys_name) ?></h5>
    </div>
        <div class="custom-sidebar-menu">
        <a href="form_dashboard.php" class="custom-sidebar-item <?= $current_page === 'form_dashboard.php' ? 'active-menu' : '' ?>">
            <span class="custom-sidebar-icon">
                <!-- Bar Chart -->
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                    <rect x="3" y="10" width="4" height="11" rx="0.5"></rect>
                    <rect x="10" y="3" width="4" height="18" rx="0.5"></rect>
                    <rect x="17" y="13" width="4" height="8" rx="0.5"></rect>
                </svg>
            </span>
            ພາບລວມລະບົບ
        </a>
        
        <a href="form_product.php" class="custom-sidebar-item <?= in_array($current_page, ['form_product.php', 'product_add.php', 'product_edit.php']) ? 'active-menu' : '' ?>">
            <span class="custom-sidebar-icon">
                <!-- Document with check badge -->
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13z"/>
                    <circle cx="17" cy="17" r="4" fill="currentColor"/>
                    <path d="M15.5 17.5l1 1 2-2" stroke="#666" stroke-width="1.5" stroke-linecap="round" fill="none"/>
                </svg>
            </span>
            ຈັດການຂໍ້ມູນສິນຄ້າ
        </a>
        
        <a href="form_product_type.php" class="custom-sidebar-item <?= in_array($current_page, ['form_product_type.php', 'product_type_edit.php']) ? 'active-menu' : '' ?>">
            <span class="custom-sidebar-icon">
                <!-- Drawer / Category box -->
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 6h-2.18c.07-.44.18-.88.18-1.33C18 2.54 15.6 1 13.5 1c-1.3 0-2.4.56-3.16 1.45L9 3.9 7.66 2.45C6.9 1.56 5.8 1 4.5 1 2.4 1 0 2.54 0 4.67 0 5.12.11 5.56.18 6H1c-.55 0-1 .45-1 1v13c0 .55.45 1 1 1h18c.55 0 1-.45 1-1V7c0-.55-.45-1-1-1zM3 6h18v2H3V6zm16 12H3V10h16v8z"/>
                </svg>
            </span>
            ຈັດການປະເພດສິນຄ້າ
        </a>
        
        <a href="form_unit.php" class="custom-sidebar-item <?= in_array($current_page, ['form_unit.php', 'unit_edit.php']) ? 'active-menu' : '' ?>">
            <span class="custom-sidebar-icon">
                <!-- Grid / Unit table -->
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3 3h8v8H3zm0 10h8v8H3zm10-10h8v8h-8zm0 10h8v8h-8z"/>
                </svg>
            </span>
            ຈັດການທົວໜ່ວຍ
        </a>
        
        <a href="form_location.php" class="custom-sidebar-item <?= in_array($current_page, ['form_location.php', 'location_edit.php']) ? 'active-menu' : '' ?>">
            <span class="custom-sidebar-icon">
                <!-- Hand truck / Dolly -->
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18 2H6c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 18V4h5v7l-2-1-2 1V4H6v14zm12 0h-7V4h7v14z"/>
                </svg>
            </span>
            ຈັດການບ່ອນຈັດເກັບ
        </a>
        
        <a href="form_supplier.php" class="custom-sidebar-item <?= in_array($current_page, ['form_supplier.php', 'supplier_edit.php']) ? 'active-menu' : '' ?>">
            <span class="custom-sidebar-icon">
                <!-- Hand holding box / supplier -->
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 8h-3V6c0-1.1-.9-2-2-2H9c-1.1 0-2 .9-2 2v2H4c-1.1 0-2 .9-2 2v10h20V10c0-1.1-.9-2-2-2zm-5 0H9V6h6v2z"/>
                    <path d="M2 22h20v-2H2v2z"/>
                </svg>
            </span>
            ຈັດການຜູ້ສະໜອງ
        </a>
        
        <a href="form_orders.php" class="custom-sidebar-item <?= in_array($current_page, ['form_orders.php', 'orders_add.php', 'orders_view.php']) ? 'active-menu' : '' ?>">
            <span class="custom-sidebar-icon">
                <!-- Shopping Cart -->
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM5.21 4H2V2H0v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.48 17 7 17h12v-2H7l1.1-2h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0020 4H5.21z"/>
                </svg>
            </span>
            ລາຍການສັ່ງຊື້
        </a>
        
        <a href="form_import.php" class="custom-sidebar-item <?= in_array($current_page, ['form_import.php', 'import_add.php', 'import_view.php']) ? 'active-menu' : '' ?>">
            <span class="custom-sidebar-icon">
                <!-- Basket with check -->
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.21 9l-4.38-6.56c-.18-.27-.51-.34-.78-.16-.27.18-.34.51-.16.78L15.6 9H8.4L11.11 3.06c.18-.27.11-.6-.16-.78-.27-.18-.6-.11-.78.16L5.79 9H2v2h1.1l1.72 7.74c.26 1.19 1.3 2.06 2.52 2.06h9.32c1.22 0 2.26-.87 2.52-2.06L20.9 11H22V9h-4.79zm-5.33 7.88l-2.75-2.78 1.41-1.41 1.34 1.34 3.77-3.77 1.41 1.41-5.18 5.21z"/>
                </svg>
            </span>
            ນຳເຂົ້າສິນຄ້າ
        </a>
        
        <a href="form_export.php" class="custom-sidebar-item <?= in_array($current_page, ['form_export.php', 'export_add.php', 'export_view.php']) ? 'active-menu' : '' ?>">
            <span class="custom-sidebar-icon">
                <!-- Export / outbox arrow -->
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/>
                </svg>
            </span>
            ຈ່າຍອອກສິນຄ້າ
        </a>
		        <a href="form_notification.php" class="custom-sidebar-item <?= in_array($current_page, ['form_notification.php']) ? 'active-menu' : '' ?>" style="position:relative;">
            <span class="custom-sidebar-icon" style="position:relative;">
                <!-- Bell notification icon -->
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                </svg>
                <?php
                // Count urgent notifications for badge
                try {
                    if (!isset($conn)) require_once __DIR__ . '/cmt_db.php';
                    $nb_low = (int)$conn->query("SELECT COUNT(*) FROM product WHERE qty <= 10 AND qty > 0")->fetchColumn();
                    $nb_exp = (int)$conn->query("SELECT COUNT(DISTINCT pro_id) FROM import_detail WHERE exp_date IS NOT NULL AND exp_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)")->fetchColumn();
                    $nb_total = $nb_low + $nb_exp;
                    if ($nb_total > 0) echo '<span style="position:absolute;top:-6px;right:-8px;background:#ff4444;color:#fff;font-size:10px;font-weight:bold;padding:1px 5px;border-radius:10px;line-height:1.4;white-space:nowrap;">' . $nb_total . '</span>';
                } catch (Exception $e) { /* silent */ }
                ?>
            </span>
            ແຈ້ງເຕື່ອນ
        </a>
                <a href="form_statistics_report.php" class="custom-sidebar-item <?= in_array($current_page, ['form_statistics_report.php']) ? 'active-menu' : '' ?>">
            <span class="custom-sidebar-icon">
                <!-- Analytics Bar Chart Icon -->
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9 14H7v-7h3v7zm4 0h-3V7h3v10zm4 0h-3v-4h3v4z"/>
                </svg>
            </span>
            ສະຖິຕິ ແລະ ລາຍງານ
        </a>
        <a href="form_employee.php" class="custom-sidebar-item <?= in_array($current_page, ['form_employee.php', 'add_employee.php', 'employee_edit.php']) ? 'active-menu' : '' ?>">
            <span class="custom-sidebar-icon">
                <!-- User Check / Employee -->
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4zm6.75-2.25L15 15.5l-1.75-1.75-.75.75 2.5 2.5 4.25-4.25-.75-.75z"/>
                </svg>
            </span>
            ຈັດການພະນັກງານ
        </a>
        


        <a href="form_settings.php" class="custom-sidebar-item <?= in_array($current_page, ['form_settings.php']) ? 'active-menu' : '' ?>">
            <span class="custom-sidebar-icon">
                <!-- Settings gear icon -->
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19.14 12.94c.04-.3.06-.61.06-.94s-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
                </svg>
            </span>
            ຕັ້ງຄ່າ
        </a>


    </div>
    
    <div class="custom-sidebar-footer" style="text-align: center; padding-bottom: 24px;">
        <a href="logout.php" class="custom-logout-btn">
            <span class="logout-text">ອອກຈາກລະບົບ</span>
            <span class="logout-arrow">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" fill="#ffffff"></circle>
                    <path d="M10.5 8.5L14 12l-3.5 3.5" stroke="#7A1530" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </span>
        </a>
        <div class="sidebar-version-text" style="color: rgba(255, 255, 255, 0.7); font-size: 15px; margin-top: 18px; font-weight: bold; font-family: 'noto-sans-lao-regular', sans-serif;">
            ເວີຊັ່ນ 1.4.9
        </div>
    </div>
    </div>
</div>
