<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

require 'cmt_db.php'; // ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ

try {
    // 📌 1. ດຶງຕົວເລກສະຫຼຸບພາບລວມ (Cards)
    // 1.1 ຈຳນວນລາຍການສິນຄ້າທັງໝົດ
    $total_pro = $conn->query("SELECT COUNT(*) FROM product")->fetchColumn();
    
    // 1.2 จຳນວນໃບບິນສັ່ງຊື້ (PO) ທີ່ຍັງຄ້າງສົ່ງ (Pending)
    $pending_po = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending' OR status = 'ລໍຖ້າກວດສອບ'")->fetchColumn();
    
    // 1.3 ມູນຄ່າລວມທີ່ນຳເຂົ້າສາງທັງໝົດ (import_qty * cost_price)
    $total_import_value = $conn->query("SELECT SUM(import_qty * cost_price) FROM import_detail")->fetchColumn() ?? 0;
    
    // 1.4 ມູນຄ່າລວມທີ່ຈ່າຍອອກສາງທັງໝົດ (export_qty * price)
    $total_export_value = $conn->query("SELECT SUM(export_qty * price) FROM export_detail")->fetchColumn() ?? 0;

    // 📌 2. Chart 1: ສິນຄ້າທັງໝົດ - Grouped Bar Chart (ນຳເຂົ້າ vs ຈ່າຍອອກ)
    $sql_chart1 = "SELECT p.pro_id, p.pro_name, 
                          COALESCE((SELECT SUM(import_qty) FROM import_detail WHERE pro_id = p.pro_id), 0) as total_imported,
                          COALESCE((SELECT SUM(export_qty) FROM export_detail WHERE pro_id = p.pro_id), 0) as total_exported
                   FROM product p
                   ORDER BY p.pro_id ASC";
    $chart1_data = $conn->query($sql_chart1)->fetchAll(PDO::FETCH_ASSOC);

    $c1_labels = [];
    $c1_imported = [];
    $c1_exported = [];
    foreach ($chart1_data as $row) {
        $c1_labels[] = $row['pro_name'];
        $c1_imported[] = intval($row['total_imported']);
        $c1_exported[] = intval($row['total_exported']);
    }

    // 📌 5. Chart 2: ບິນ PO ຄ້າງສົ່ງ - Line Chart
    $sql_chart2 = "SELECT order_date,
                          COUNT(*) as total_po,
                          SUM(CASE WHEN status = 'Pending' OR status = 'ລໍຖ້າກວດສອບ' THEN 1 ELSE 0 END) as pending_po
                   FROM orders
                   GROUP BY order_date
                   ORDER BY order_date ASC";
    $chart2_data = $conn->query($sql_chart2)->fetchAll(PDO::FETCH_ASSOC);

    $c2_labels = [];
    $c2_total = [];
    $c2_pending = [];
    foreach ($chart2_data as $row) {
        $c2_labels[] = date('d/m/Y', strtotime($row['order_date']));
        $c2_total[] = intval($row['total_po']);
        $c2_pending[] = intval($row['pending_po']);
    }

    // 📌 6. Chart 3: ມູນຄ່າການນຳເຂົ້າ - Grouped Bar (Qty vs Value)
    $sql_chart3 = "SELECT p.pro_name,
                          COALESCE(SUM(d.import_qty), 0) as total_qty,
                          COALESCE(SUM(d.import_qty * d.cost_price), 0) as total_value
                   FROM product p
                   LEFT JOIN import_detail d ON p.pro_id = d.pro_id
                   GROUP BY p.pro_id, p.pro_name
                   ORDER BY p.pro_id ASC";
    $chart3_data = $conn->query($sql_chart3)->fetchAll(PDO::FETCH_ASSOC);

    $c3_labels = [];
    $c3_qty = [];
    $c3_value = [];
    foreach ($chart3_data as $row) {
        $c3_labels[] = $row['pro_name'];
        $c3_qty[] = intval($row['total_qty']);
        $c3_value[] = floatval($row['total_value']);
    }

    // 📌 7. Chart 4: ມູນຄ່າການຈ່າຍອອກ - Grouped Bar (Qty vs Value)
    $sql_chart4 = "SELECT p.pro_name,
                          COALESCE(SUM(d.export_qty), 0) as total_qty,
                          COALESCE(SUM(d.export_qty * d.price), 0) as total_value
                   FROM product p
                   LEFT JOIN export_detail d ON p.pro_id = d.pro_id
                   GROUP BY p.pro_id, p.pro_name
                   ORDER BY p.pro_id ASC";
    $chart4_data = $conn->query($sql_chart4)->fetchAll(PDO::FETCH_ASSOC);

    $c4_labels = [];
    $c4_qty = [];
    $c4_value = [];
    foreach ($chart4_data as $row) {
        $c4_labels[] = $row['pro_name'];
        $c4_qty[] = intval($row['total_qty']);
        $c4_value[] = floatval($row['total_value']);
    }

    // 📌 8. Alert Table 1: Low Stock (qty <= 10)
    $sql_low_stock = "SELECT p.pro_id, p.pro_name, p.qty, u.unit_name, l.loc_name
                      FROM product p
                      LEFT JOIN unit u ON p.unit_id = u.unit_id
                      LEFT JOIN location l ON p.loc_id = l.loc_id
                      WHERE p.qty <= 10
                      ORDER BY p.qty ASC";
    $low_stock_list = $conn->query($sql_low_stock)->fetchAll(PDO::FETCH_ASSOC);

    // 📌 9. Alert Table 2: Expired Products (exp_date < today)
    $sql_expired = "SELECT DISTINCT p.pro_id, p.pro_name, id.lot_no, id.exp_date
                    FROM import_detail id
                    LEFT JOIN product p ON id.pro_id = p.pro_id
                    WHERE id.exp_date IS NOT NULL AND id.exp_date < CURDATE()
                    ORDER BY id.exp_date ASC";
    $expired_list = $conn->query($sql_expired)->fetchAll(PDO::FETCH_ASSOC);

    // 📌 10. Alert Table 3: Expiring Soon (exp_date within 90 days)
    $sql_expiring_soon = "SELECT DISTINCT p.pro_id, p.pro_name, id.lot_no, id.exp_date,
                                 DATEDIFF(id.exp_date, CURDATE()) as days_left
                          FROM import_detail id
                          LEFT JOIN product p ON id.pro_id = p.pro_id
                          WHERE id.exp_date IS NOT NULL
                            AND id.exp_date >= CURDATE()
                            AND id.exp_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
                          ORDER BY id.exp_date ASC";
    $expiring_soon_list = $conn->query($sql_expiring_soon)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <link href="style/fonts.css" rel="stylesheet">
    <title>ໜ້າຫຼັກ - ພາບລວມລະບົບ (Dashboard)</title>
    <link href="bootstrap-5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="bootstrap-5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Include Chart.js library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Cream background */
        body {
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            background-color: #FAF4EB !important;
        }

        .col-md-10 {
            background-color: #FAF4EB !important;
        }

        /* Remove navbar margin-bottom so header title is flush against it */
        .custom-navbar {
            margin-bottom: 0 !important;
        }

        /* Dashboard Header title */
        .db-header-title-box {
            background: linear-gradient(90deg, #ffffffff 0%, #C9956A 100%) !important;
            height: 60px !important;
            padding: 0 24px !important;
            margin-bottom: 0 !important;
            border-radius: 0px !important;
            border: none !important;
            display: flex !important;
            align-items: center !important;
        }

        .db-header-title {
            color: #1a1a1a !important;
            font-weight: 700 !important;
            font-size: 16px !important;
            margin: 0 !important;
        }

        .db-content-wrapper {
            padding: 24px 24px !important;
        }

        /* Stats Cards */
        .db-stat-card {
            background-color: #ffffff !important;
            border: none !important;
            border-radius: 0px !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
            padding: 20px 15px !important;
            text-align: center !important;
            height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .db-stat-icon-box {
            margin-bottom: 12px !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center;
        }

        .db-stat-title {
            font-size: 18px !important;
            font-weight: 700 !important;
            color: #7A1530 !important; /* Crimson brand color for title */
            margin-bottom: 6px !important;
        }

        .db-stat-value {
            font-size: 15px !important;
            font-weight: bold !important;
            color: #2d2d2d !important; /* Dark charcoal for values */
            margin-bottom: 8px !important;
        }

        .db-stat-link {
            font-size: 13px !important;
            color: #C9956A !important; /* Sand/Gold theme for links */
            text-decoration: none !important; /* No underline */
            font-weight: 500 !important;
        }

        .db-stat-link:hover {
            color: #7A1530 !important; /* Crimson on hover */
            text-decoration: none !important;
        }

        /* Alert Tables Cards */
        .db-alert-card {
            border: none !important;
            border-radius: 0px !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
            background-color: #ffffff !important;
            margin-bottom: 24px !important;
        }

        .db-alert-header {
            background-color: #7A1530 !important; /* Crimson brand color */
            color: #ffffff !important;
            font-weight: bold !important;
            font-size: 16px !important;
            padding: 12px 20px !important;
            border-radius: 0px !important;
        }

        /* Custom Table style */
        .db-custom-table {
            width: 100% !important;
            margin-bottom: 0 !important;
            border-collapse: collapse !important;
        }

        .db-custom-table th {
            background-color: #00E5EE !important; /* Bright Cyan */
            color: #333333 !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            padding: 10px 16px !important;
            border: none !important;
        }

        .db-custom-table td {
            padding: 12px 16px !important;
            font-size: 15px !important;
            color: #333333 !important;
            border-bottom: 1px solid #EAEAEA !important;
            background-color: #ffffff !important;
        }

        /* Alert Tables style */
        .db-alert-table {
            width: 100% !important;
            margin-bottom: 0 !important;
            border-collapse: collapse !important;
        }

        .db-alert-table th {
            background-color: #C9956A !important; /* Sand/Gold theme */
            color: #000000 !important;
            font-weight: bold !important;
            font-size: 15px !important;
            padding: 12px 16px !important;
            border: none !important;
            text-align: center !important;
        }

        .db-alert-table td {
            padding: 12px 16px !important;
            font-size: 15px !important;
            color: #333333 !important;
            border-bottom: 1px solid #EAEAEA !important;
            background-color: #ffffff !important;
            text-align: center !important;
            font-weight: bold !important;
        }

        .db-alert-table th:nth-child(2), .db-alert-table td:nth-child(2) {
            text-align: left !important; /* Align product name to left */
        }

        .db-alert-table th:first-child, .db-alert-table td:first-child {
            text-align: left !important; /* Align ID to left */
        }

        /* Table Footer Summary */
        .db-table-summary-footer {
            background-color: #ffffff !important;
            padding: 12px 20px !important;
            font-size: 14px !important;
            color: #333333 !important;
            border-top: 2px solid #A0A0A0 !important;
            font-weight: bold !important;
        }
    </style>
</head>
<body>
<div class="container-fluid">
<div class="row">

<?php include 'sidebar.php'; ?>

<div class="col-md-10 p-0">
<?php include 'navbar.php'; ?>
<!-- Override navbar margin so header title is flush against it -->
<style>.custom-navbar { margin-bottom: 0 !important; }</style>

<div class="container-fluid p-0">
    <!-- Header Title: flush against Navbar -->
    <div class="db-header-title-box">
        <h4 class="db-header-title">ພາບລວມລະບົບ</h4>
    </div>
    <div class="db-content-wrapper">

    <!-- Cards Row -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="db-stat-card">
                <div class="db-stat-icon-box">
                    <!-- Icon 1: Box -->
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="#DE2C5B">
                        <rect x="2" y="3" width="20" height="5" rx="1.5"/>
                        <path d="M4 9h16v10.5c0 1.38-1.12 2.5-2.5 2.5h-11C5.12 22 4 20.88 4 19.5V9z"/>
                        <rect x="10" y="13" width="4" height="1.5" fill="#ffffff" rx="0.5"/>
                    </svg>
                </div>
                <div class="db-stat-title">ສິນຄ້າທັງໝົດ</div>
                <div class="db-stat-value"><?= number_format($total_pro) ?> ລາຍການ</div>
                <a href="form_product.php" class="db-stat-link">ເບິ່ງລາຍການສິນຄ້າ</a>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="db-stat-card">
                <div class="db-stat-icon-box">
                    <!-- Icon 2: Circle P -->
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="#DE2C5B">
                        <circle cx="12" cy="12" r="11"/>
                        <text x="12" y="16.5" fill="#ffffff" font-size="13" font-family="Arial, Helvetica, sans-serif" font-weight="bold" text-anchor="middle">P</text>
                    </svg>
                </div>
                <div class="db-stat-title">ບິນ PO ຄ້າງສົ່ງ</div>
                <div class="db-stat-value"><?= number_format($pending_po) ?> ລາຍການ</div>
                <a href="form_orders.php" class="db-stat-link">ກວດສອບໃບບິນສັ່ງຊື້</a>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="db-stat-card">
                <div class="db-stat-icon-box">
                    <!-- Icon 3: Clipboard Dollar -->
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="#DE2C5B">
                        <path d="M5 3a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2H5V3z" fill="#DE2C5B"/>
                        <path d="M5 6h12a2 2 0 0 1 2 2v7.17a6.5 6.5 0 0 0-4.67 6.83H7a2 2 0 0 1-2-2V6z" fill="#DE2C5B"/>
                        <circle cx="17.5" cy="17.5" r="5.5" fill="#DE2C5B" stroke="#ffffff" stroke-width="1.5"/>
                        <text x="17.5" y="21.0" fill="#ffffff" font-size="9" font-family="Arial" font-weight="bold" text-anchor="middle">$</text>
                    </svg>
                </div>
                <div class="db-stat-title">ມູນຄ່າການນຳເຂົ້າ</div>
                <div class="db-stat-value"><?= number_format($total_import_value, 2) ?> ₭</div>
                <a href="form_import.php" class="db-stat-link">ປະຫວັດການຮັບເຄື່ອງ</a>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="db-stat-card">
                <div class="db-stat-icon-box">
                    <!-- Icon 4: Document Arrow -->
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="#DE2C5B">
                        <rect x="5" y="2" width="14" height="20" rx="2"/>
                        <rect x="8" y="5" width="8" height="2" fill="#ffffff" rx="0.5"/>
                        <path d="M9 13h6M13 10l3 3-3 3" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    </svg>
                </div>
                <div class="db-stat-title">ມູນຄ່າການຈ່າຍອອກ</div>
                <div class="db-stat-value"><?= number_format($total_export_value, 2) ?> ₭</div>
                <a href="form_export.php" class="db-stat-link">ປະຫວັດການຕັດສະຕັອກ</a>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="row mb-4">
        <!-- Chart 1: สืนค้าทั้งหมด Grouped Bar Chart -->
        <div class="col-md-6 mb-3">
            <div class="db-alert-card" style="margin-bottom:0px !important;">
                <div class="db-alert-header" style="background-color: #C9956A !important;">
                    ສິນຄ້າທັງໝົດ (ນຳເຂົ້າ ແລະ ຈ່າຍອອກ)
                </div>
                <div class="p-3" style="background-color: #ffffff;">
                    <canvas id="allProductsChart" style="max-height: 280px; width: 100%;"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart 2: บิน PO ค้างส่ง Line Chart -->
        <div class="col-md-6 mb-3">
            <div class="db-alert-card" style="margin-bottom:0px !important;">
                <div class="db-alert-header" style="background-color: #C9956A !important;">
                    ບິນ PO ຄ້າງສົ່ງ (ແນວໂນ້ມທັງໝົດ ແລະ ຄ້າງສົ່ງ)
                </div>
                <div class="p-3" style="background-color: #ffffff;">
                    <canvas id="pendingPoChart" style="max-height: 280px; width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row mb-4">
        <!-- Chart 3: มูลค่านำเข้า Grouped Bar Chart -->
        <div class="col-md-6 mb-3">
            <div class="db-alert-card" style="margin-bottom:0px !important;">
                <div class="db-alert-header" style="background-color: #C9956A !important;">
                    ມູນຄ່າການນຳເຂົ້າ (ຈຳນວນ ແລະ ມູນຄ່າ)
                </div>
                <div class="p-3" style="background-color: #ffffff;">
                    <canvas id="importValueChart" style="max-height: 280px; width: 100%;"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart 4: มูลค่าจ่ายออก Grouped Bar Chart -->
        <div class="col-md-6 mb-3">
            <div class="db-alert-card" style="margin-bottom:0px !important;">
                <div class="db-alert-header" style="background-color: #C9956A !important;">
                    ມູນຄ່າການຈ່າຍອອກ (ຈຳນວນ ແລະ ມູນຄ່າ)
                </div>
                <div class="p-3" style="background-color: #ffffff;">
                    <canvas id="exportValueChart" style="max-height: 280px; width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>


    <!-- ==================== ALERT TABLES SECTION ==================== -->

    <!-- Alert Table 1: Low Stock -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="db-alert-card">
                <div class="db-alert-header" style="background-color: #7A1530 !important;">
                    ສິນຄ້າໃກ້ໝົດສາງ (Low Stock &le; 10)
                </div>
                <div class="table-responsive">
                    <table class="db-alert-table">
                        <thead>
                            <tr>
                                <th>ລະຫັດ</th>
                                <th>ຊື່ສິນຄ້າ</th>
                                <th>ຈຳນວນຄົງເຫຼືອ</th>
                                <th>ຫົວໜ່ວຍ</th>
                                <th>ບ່ອນຈັດເກັບ (Location)</th>
                                <th>ສະຖານະ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($low_stock_list)): ?>
                            <tr><td colspan="6" style="text-align:center; color:#888; padding:18px;">ບໍ່ມີສິນຄ້າໃກ້ໝົດສາງ</td></tr>
                            <?php else: foreach ($low_stock_list as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['pro_id']) ?></td>
                                <td><?= htmlspecialchars($row['pro_name']) ?></td>
                                <td style="color:#C9956A; font-weight:bold;"><?= number_format($row['qty']) ?></td>
                                <td><?= htmlspecialchars($row['unit_name'] ?? 'ກ່ອງ') ?></td>
                                <td><?= htmlspecialchars($row['loc_name'] ?? '-') ?></td>
                                <td>
                                    <span style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:26px;background:#C9956A;border-radius:0px;color:#ffffff;font-size:13px;font-weight:bold;">ຕ່ຳ</span>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                        <tfoot>
                            <tr><td colspan="6" style="padding:12px 14px; font-weight:bold; border-top:2px solid #333333; font-size:15px;">ຈຳນວນ <?= count($low_stock_list) ?> ລາຍການ</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Table 2: Expired Products -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="db-alert-card">
                <div class="db-alert-header" style="background-color: #7A1530 !important;">
                    ສິນຄ້າໝົດອາຍຸແລ້ວ (Expired Products)
                </div>
                <div class="table-responsive">
                    <table class="db-alert-table">
                        <thead>
                            <tr>
                                <th>ລະຫັດ</th>
                                <th>ຊື່ສິນຄ້າ</th>
                                <th>ເລກ Lot</th>
                                <th>ວັນໝົດອາຍຸ</th>
                                <th>ສະຖານະ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($expired_list)): ?>
                            <tr><td colspan="5" style="text-align:center; color:#888; padding:18px;">ບໍ່ມີສິນຄ້າໝົດອາຍຸ</td></tr>
                            <?php else: foreach ($expired_list as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['pro_id']) ?></td>
                                <td><?= htmlspecialchars($row['pro_name']) ?></td>
                                <td><?= htmlspecialchars($row['lot_no'] ?? '-') ?></td>
                                <td style="color:#C0392B; font-weight:bold;"><?= $row['exp_date'] ? date('d/m/Y', strtotime($row['exp_date'])) : '-' ?></td>
                                <td>
                                    <span style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:26px;background:#C0392B;border-radius:0px;color:#ffffff;font-size:13px;font-weight:bold;">ໝົດ</span>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                        <tfoot>
                            <tr><td colspan="5" style="padding:12px 14px; font-weight:bold; border-top:2px solid #333333; font-size:15px;">ຈຳນວນ <?= count($expired_list) ?> ລາຍການ</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Table 3: Expiring Soon (within 90 days) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="db-alert-card">
                <div class="db-alert-header" style="background-color: #7A1530 !important;">
                    ສິນຄ້າໃກ້ໝົດອາຍຸ ພາຍໃນ 90 ວັນ (Expiring Soon)
                </div>
                <div class="table-responsive">
                    <table class="db-alert-table">
                        <thead>
                            <tr>
                                <th>ລະຫັດ</th>
                                <th>ຊື່ສິນຄ້າ</th>
                                <th>ເລກ Lot</th>
                                <th>ວັນໝົດອາຍຸ</th>
                                <th>ເຫຼືອ (ວັນ)</th>
                                <th>ສະຖານະ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($expiring_soon_list)): ?>
                            <tr><td colspan="6" style="text-align:center; color:#888; padding:18px;">ບໍ່ມີສິນຄ້າໃກ້ໝົດອາຍຸ</td></tr>
                            <?php else: foreach ($expiring_soon_list as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['pro_id']) ?></td>
                                <td><?= htmlspecialchars($row['pro_name']) ?></td>
                                <td><?= htmlspecialchars($row['lot_no'] ?? '-') ?></td>
                                <td style="color:#E67E22; font-weight:bold;"><?= $row['exp_date'] ? date('d/m/Y', strtotime($row['exp_date'])) : '-' ?></td>
                                <td style="color:#E67E22; font-weight:bold;"><?= $row['days_left'] ?> ວັນ</td>
                                <td>
                                    <span style="display:inline-flex;align-items:center;justify-content:center;width:55px;height:26px;background:#E67E22;border-radius:0px;color:#ffffff;font-size:13px;font-weight:bold;">ໃກ້ໝົດ</span>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                        <tfoot>
                            <tr><td colspan="6" style="padding:12px 14px; font-weight:bold; border-top:2px solid #333333; font-size:15px;">ຈຳນວນ <?= count($expiring_soon_list) ?> ລາຍການ</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div><!-- end db-content-wrapper -->
</div>

</div>
</div>
</div>

<script>
// Set global font family for Chart.js to noto-sans-lao-regular
Chart.defaults.font.family = "'noto-sans-lao-regular', sans-serif";

// PHP variables to JS for Chart 1
const c1Labels = <?php echo json_encode($c1_labels); ?>;
const c1Imported = <?php echo json_encode($c1_imported); ?>;
const c1Exported = <?php echo json_encode($c1_exported); ?>;

// PHP variables to JS for Chart 2
const c2Labels = <?php echo json_encode($c2_labels); ?>;
const c2Total = <?php echo json_encode($c2_total); ?>;
const c2Pending = <?php echo json_encode($c2_pending); ?>;

// PHP variables to JS for Chart 3
const c3Labels = <?php echo json_encode($c3_labels); ?>;
const c3Qty = <?php echo json_encode($c3_qty); ?>;
const c3Value = <?php echo json_encode($c3_value); ?>;

// PHP variables to JS for Chart 4
const c4Labels = <?php echo json_encode($c4_labels); ?>;
const c4Qty = <?php echo json_encode($c4_qty); ?>;
const c4Value = <?php echo json_encode($c4_value); ?>;

// 1. Chart 1: All Products (Grouped Bar Chart)
const ctx1 = document.getElementById('allProductsChart').getContext('2d');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: c1Labels,
        datasets: [
            {
                label: 'ຈຳນວນນຳເຂົ້າລວມ',
                data: c1Imported,
                backgroundColor: '#4DD0E1',
                borderColor: '#00ADA2',
                borderWidth: 1
            },
            {
                label: 'ຈຳນວນຈ່າຍອອກລວມ',
                data: c1Exported,
                backgroundColor: '#FFA726',
                borderColor: '#FB8C00',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: { display: true, text: 'ຈຳນວນສິນຄ້າ' }
            }
        }
    }
});

// 2. Chart 2: Pending PO (Line Chart)
const ctx2 = document.getElementById('pendingPoChart').getContext('2d');
new Chart(ctx2, {
    type: 'line',
    data: {
        labels: c2Labels.length > 0 ? c2Labels : ['ບໍ່ມີຂໍ້ມູນ'],
        datasets: [
            {
                label: 'ບິນ PO ທັງໝົດ',
                data: c2Total.length > 0 ? c2Total : [0],
                borderColor: '#232b2cff',
                backgroundColor: 'rgba(77, 208, 225, 0.1)',
                tension: 0.3,
                fill: true,
                borderWidth: 3
            },
            {
                label: 'ບິນ PO ຄ້າງສົ່ງ',
                data: c2Pending.length > 0 ? c2Pending : [0],
                borderColor: '#C0392B',
                backgroundColor: 'rgba(192, 57, 43, 0.1)',
                tension: 0.3,
                fill: true,
                borderWidth: 3
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 },
                title: { display: true, text: 'ຈຳນວນບິນ' }
            }
        }
    }
});

// 3. Chart 3: Import Value (Grouped Bar Chart - Qty vs Value)
const ctx3 = document.getElementById('importValueChart').getContext('2d');
new Chart(ctx3, {
    type: 'bar',
    data: {
        labels: c3Labels,
        datasets: [
            {
                label: 'ຈຳນວນນຳເຂົ້າ (ຊິ້ນ)',
                data: c3Qty,
                backgroundColor: '#FFA726',
                yAxisID: 'y',
                borderWidth: 1
            },
            {
                label: 'ມູນຄ່າການນຳເຂົ້າ (₭)',
                data: c3Value,
                backgroundColor: '#4DD0E1',
                yAxisID: 'y1',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                title: { display: true, text: 'ຈຳນວນ (ຊິ້ນ)' }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: { drawOnChartArea: false },
                title: { display: true, text: 'ມູນຄ່າ (₭)' },
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString() + ' ₭';
                    }
                }
            }
        }
    }
});

// 4. Chart 4: Export Value (Grouped Bar Chart - Qty vs Value)
const ctx4 = document.getElementById('exportValueChart').getContext('2d');
new Chart(ctx4, {
    type: 'bar',
    data: {
        labels: c4Labels,
        datasets: [
            {
                label: 'ຈຳນວນຈ່າຍອອກ (ຊິ້ນ)',
                data: c4Qty,
                backgroundColor: '#FFA726',
                yAxisID: 'y',
                borderWidth: 1
            },
            {
                label: 'ມູນຄ່າການຈ່າຍອອກ (₭)',
                data: c4Value,
                backgroundColor: '#4DD0E1',
                yAxisID: 'y1',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                title: { display: true, text: 'ຈຳນວນ (ຊິ້ນ)' }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: { drawOnChartArea: false },
                title: { display: true, text: 'ມູນຄ່າ (₭)' },
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString() + ' ₭';
                    }
                }
            }
        }
    }
});
</script>
</body>
</html>