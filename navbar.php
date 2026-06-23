<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load system settings for system name
$_nb_settings_file = __DIR__ . '/settings.json';
$_nb_settings = file_exists($_nb_settings_file) ? (json_decode(file_get_contents($_nb_settings_file), true) ?? []) : [];
$_nb_sys_name = $_nb_settings['system_name'] ?? 'ລະບົບສາງເຄື່ອງສໍາອາງ';

$current_page = basename($_SERVER['PHP_SELF']);
if (!isset($page_title)) {
    switch ($current_page) {
        case 'form_dashboard.php':
            $page_title = 'ພາບລວມລະບົບ';
            break;
        case 'form_product.php':
        case 'product_add.php':
        case 'product_edit.php':
            $page_title = 'ຈັດການຂໍ້ມູນສິນຄ້າ';
            break;
        case 'form_product_type.php':
        case 'product_type_edit.php':
            $page_title = 'ຈັດການປະເພດສິນຄ້າ';
            break;
        case 'form_unit.php':
        case 'unit_edit.php':
            $page_title = 'ຈັດການຫົວໜ່ວຍ';
            break;
        case 'form_location.php':
        case 'location_edit.php':
            $page_title = 'ຈັດການບ່ອນຈັດເກັບ';
            break;
        case 'form_supplier.php':
        case 'supplier_edit.php':
            $page_title = 'ຈັດການຜູ້ສະໜອງ';
            break;
        case 'form_orders.php':
        case 'orders_add.php':
        case 'orders_view.php':
            $page_title = 'ລາຍການສັ່ງຊື້';
            break;
        case 'form_import.php':
        case 'import_add.php':
        case 'import_view.php':
            $page_title = 'ນຳເຂົ້າສິນຄ້າ';
            break;
        case 'form_export.php':
        case 'export_add.php':
        case 'export_view.php':
            $page_title = 'ຈ່າຍອອກສິນຄ້າ';
            break;
        case 'form_employee.php':
        case 'add_employee.php':
        case 'employee_edit.php':
            $page_title = 'ຈັດການພະນັກງານ';
            break;
        case 'form_statistics_report.php':
            $page_title = 'ສະຖິຕິ ແລະ ລາຍງານ';
            break;
        case 'form_settings.php':
            $page_title = 'ຕັ້ງຄ່າລະບົບ';
            break;
        case 'form_notification.php':
            $page_title = 'ແຈ້ງເຕື່ອນລະບົບ';
            break;
        default:
            $page_title = $_nb_sys_name;
            break;
    }
}

$username = $_SESSION['username'] ?? 'User';
$userid = $_SESSION['userid'] ?? null;
$user_role_raw = $_SESSION['role'] ?? '';
switch($user_role_raw) {
    case 'admin':     $user_role_label = 'Admin'; break;
    case 'warehouse': $user_role_label = 'Warehouse'; break;
    case 'sales':     $user_role_label = 'Sales'; break;
    default:          $user_role_label = ucfirst($user_role_raw); break;
}

$user_image = null;
if ($userid) {
    try {
        if (!isset($conn)) {
            require_once 'cmt_db.php';
        }
        $stmt_user = $conn->prepare("SELECT image FROM employee WHERE emp_id = :id");
        $stmt_user->execute(['id' => $userid]);
        $user_row = $stmt_user->fetch(PDO::FETCH_ASSOC);
        if ($user_row && !empty($user_row['image']) && file_exists("employees/" . $user_row['image'])) {
            $user_image = "employees/" . $user_row['image'];
        }
    } catch (Exception $e) {
        // Fallback silently
    }
}
?>
<script>
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        document.body.classList.add('sidebar-collapsed');
    }
</script>
<style>
.custom-navbar {
    background-color: #7A1530 !important; /* Deep red crimson */
    height: 70px !important;
    padding: 0 24px !important;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    border-radius: 0 !important;
    margin-bottom: 24px !important;
    border: none !important;
    color: #ffffff !important;
}

.navbar-left-section {
    display: flex;
    align-items: center;
    gap: 22px; /* Wider gap like image 2 */
}

.navbar-hamburger-btn {
    cursor: pointer;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.2s ease;
}

.navbar-hamburger-btn:hover {
    opacity: 0.8;
}

.navbar-page-title-text {
    font-size: 18px;
    font-weight: bold;
    color: #ffffff !important;
    margin: 0;
    font-family: 'noto-sans-lao-regular', sans-serif !important;
    letter-spacing: 0.5px;
}

.navbar-right-section {
    display: flex;
    align-items: center;
}

.navbar-bell-btn {
    background: none;
    border: none;
    color: #ffffff;
    cursor: pointer;
    padding: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.2s ease;
}

.navbar-bell-btn:hover {
    opacity: 0.8;
}

.navbar-v-divider {
    height: 32px; /* Taller divider */
    width: 2px; /* Thicker divider */
    background-color: #ffffff !important;
    opacity: 0.7;
    margin: 0 22px;
}

.navbar-profile-box {
    display: flex;
    align-items: center;
    color: #ffffff;
    font-family: 'noto-sans-lao-regular', sans-serif !important;
    text-decoration: none !important;
    cursor: pointer;
    transition: opacity 0.2s ease;
}

.navbar-profile-box:hover {
    opacity: 0.85;
}

.navbar-avatar-circle-box {
    width: 42px; /* Larger circular avatar */
    height: 42px;
    background-color: #ffffff;
    color: #B22222; /* Crimson icon inside white background */
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.navbar-user-text-box {
    display: flex;
    flex-direction: column;
    justify-content: center;
    line-height: 1.25;
}

.navbar-username-label {
    font-size: 16px;
    font-weight: bold;
    margin: 0;
    color: #ffffff !important;
}

.navbar-status-label {
    font-size: 12px;
    opacity: 0.9;
    margin: 0;
    color: #ffffff !important;
}
</style>

<nav class="navbar custom-navbar">
    <!-- Left: Hamburger & Dynamic Title -->
    <div class="navbar-left-section">
        <div class="navbar-hamburger-btn">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </div>
        <span class="navbar-page-title-text"><?= htmlspecialchars($page_title); ?></span>
    </div>

    <!-- Right: Bell, Divider, Profile -->
    <div class="navbar-right-section">
        <?php
        // Count urgent notifications for navbar badge
        $nb_low = 0;
        $nb_exp = 0;
        $nb_total = 0;
        try {
            if (!isset($conn)) {
                require_once 'cmt_db.php';
            }
            $nb_low = (int)$conn->query("SELECT COUNT(*) FROM product WHERE qty <= 10 AND qty > 0")->fetchColumn();
            $nb_exp = (int)$conn->query("SELECT COUNT(DISTINCT pro_id) FROM import_detail WHERE exp_date IS NOT NULL AND exp_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)")->fetchColumn();
            $nb_total = $nb_low + $nb_exp;
        } catch (Exception $e) {
            // silent fallback
        }
        ?>
        <div style="position: relative; display: inline-block;">
            <a href="form_notification.php" class="navbar-bell-btn" title="ແຈ້ງເຕືອນ" style="text-decoration: none;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor" style="color: #ffffff;">
                    <!-- Ringing / vibrating solid bell -->
                    <path d="M12 22a2 2 0 0 0 2-2h-4a2 2 0 0 0 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                    <!-- sound waves on left and right -->
                    <path d="M21 8.5c.6 1.1.9 2.3.9 3.5s-.3 2.4-.9 3.5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                    <path d="M3 8.5c-.6 1.1-.9 2.3-.9 3.5s.3 2.4.9 3.5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            </a>
            <?php if ($nb_total > 0): ?>
                <span style="position: absolute; top: -2px; right: -2px; background-color: #ff4444; color: #ffffff; border-radius: 50%; padding: 1px 5px; font-size: 10px; font-weight: bold; min-width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; border: 2px solid #B22222; pointer-events: none;">
                    <?= $nb_total ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="navbar-v-divider"></div>

        <a href="employee_edit.php?id=<?= $userid ?>" class="navbar-profile-box">
            <div class="navbar-avatar-circle-box">
                <?php if ($user_image): ?>
                    <img src="<?= $user_image ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                <?php else: ?>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                <?php endif; ?>
            </div>
            <div class="navbar-user-text-box">
                <span class="navbar-username-label"><?= htmlspecialchars(ucfirst($username)); ?></span>
                <span class="navbar-status-label"><?= htmlspecialchars($user_role_label) ?></span>
            </div>
        </a>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hamburgerBtn = document.querySelector('.navbar-hamburger-btn');
    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-collapsed');
            const isCollapsed = document.body.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', isCollapsed ? 'true' : 'false');
        });
    }
});
</script>
