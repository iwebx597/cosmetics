<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

require 'cmt_db.php'; // ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ

// 📌 1. ຮັບຄ່າ ແລະ ທຳຄວາມສະອາດຂໍ້ມູນການກັ່ນຕອງ (Sanitize and Parse Filter Inputs)
$filter_type = preg_replace('/[^a-zA-Z_]/', '', $_GET['filter_type'] ?? 'all');
$date_start = preg_replace('/[^0-9\-]/', '', $_GET['date_start'] ?? '');
$date_end = preg_replace('/[^0-9\-]/', '', $_GET['date_end'] ?? '');

// Default to date_range if dates are provided
if (!empty($date_start) || !empty($date_end)) {
    $filter_type = 'date_range';
}

// 📌 2. ສ້າງເງື່ອນໄຂ SQL WHERE ຕາມປະເພດການກັ່ນຕອງ (Build WHERE Clauses)
$import_where = " WHERE 1=1 ";
$export_where = " WHERE 1=1 ";
$order_where = " WHERE 1=1 ";

$filter_description = "ສະແດງຂໍ້ມູນທັງໝົດ";

if ($filter_type === 'date_range' && !empty($date_start) && !empty($date_end)) {
    $import_where .= " AND DATE(i.import_date) BETWEEN '$date_start' AND '$date_end' ";
    $export_where .= " AND DATE(e.export_date) BETWEEN '$date_start' AND '$date_end' ";
    $order_where .= " AND DATE(o.order_date) BETWEEN '$date_start' AND '$date_end' ";
    $filter_description = "ຕັ້ງແຕ່ວັນທີ: " . date('d/m/Y', strtotime($date_start)) . " ຫາ " . date('d/m/Y', strtotime($date_end));
} elseif ($filter_type === 'date_range' && !empty($date_start)) {
    $import_where .= " AND DATE(i.import_date) >= '$date_start' ";
    $export_where .= " AND DATE(e.export_date) >= '$date_start' ";
    $order_where .= " AND DATE(o.order_date) >= '$date_start' ";
    $filter_description = "ຕັ້ງແຕ່ວັນທີ: " . date('d/m/Y', strtotime($date_start));
} elseif ($filter_type === 'date_range' && !empty($date_end)) {
    $import_where .= " AND DATE(i.import_date) <= '$date_end' ";
    $export_where .= " AND DATE(e.export_date) <= '$date_end' ";
    $order_where .= " AND DATE(o.order_date) <= '$date_end' ";
    $filter_description = "ຈົນເຖິງວັນທີ: " . date('d/m/Y', strtotime($date_end));
}

try {
    // 📌 3. ດຶງຕົວເລກສະຫຼຸບພາບລວມ (Summary KPI Numbers based on Date Filter)
    $total_pro = $conn->query("SELECT COUNT(*) FROM product")->fetchColumn();
    
    // 3.2 ຈຳນວນບິນ PO ຄ້າງສົ່ງ
    $pending_po = $conn->query("SELECT COUNT(*) FROM orders o $order_where AND (o.status = 'Pending' OR o.status = 'ລໍຖ້າກວດສອບ')")->fetchColumn();
    
    // 3.3 ມູນຄ່າລວມ ແລະ ຈຳນວນທີ່ນຳເຂົ້າ
    $stmt_imp_sum = $conn->query("SELECT SUM(id.import_qty) as qty, SUM(id.import_qty * id.cost_price) as val FROM import_detail id LEFT JOIN import i ON id.import_id = i.import_id $import_where");
    $imp_sum = $stmt_imp_sum->fetch(PDO::FETCH_ASSOC);
    $total_import_qty = $imp_sum['qty'] ?? 0;
    $total_import_value = $imp_sum['val'] ?? 0;
    
    // 3.4 ມູນຄ່າລວມ ແລະ ຈຳນວນທີ່ຈ່າຍອອກ
    $stmt_exp_sum = $conn->query("SELECT SUM(ed.export_qty) as qty, SUM(ed.export_qty * ed.price) as val FROM export_detail ed LEFT JOIN export e ON ed.export_id = e.export_id $export_where");
    $exp_sum = $stmt_exp_sum->fetch(PDO::FETCH_ASSOC);
    $total_export_qty = $exp_sum['qty'] ?? 0;
    $total_export_value = $exp_sum['val'] ?? 0;

    // 3.5 ຈຳນວນສິນຄ້າຄົງເຫຼືອໃນ Stock (ຄ່າປະຈຸບັນ)
    $total_stock_qty = $conn->query("SELECT SUM(qty) FROM product")->fetchColumn() ?? 0;

    // 3.6 ຈຳນວນສິນຄ້າໝົດອາຍຸ (ອີງຕາມວັນນຳເຂົ້າ ແລະ ການກັ່ນຕອງ)
    $total_expired_count = $conn->query("SELECT COUNT(DISTINCT id.pro_id) FROM import_detail id LEFT JOIN import i ON id.import_id = i.import_id $import_where AND id.exp_date IS NOT NULL AND id.exp_date < CURDATE()")->fetchColumn() ?? 0;

    // 3.7 ຈຳນວນສິນຄ້າໃກ້ໝົດອາຍຸ (ພາຍໃນ 90 ວັນ)
    $total_expiring_count = $conn->query("SELECT COUNT(DISTINCT id.pro_id) FROM import_detail id LEFT JOIN import i ON id.import_id = i.import_id $import_where AND id.exp_date IS NOT NULL AND id.exp_date >= CURDATE() AND id.exp_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)")->fetchColumn() ?? 0;

    // 📌 4. ດຶງຂໍ້ມູນລາຍລະອຽດແຕ່ລະຕາຕະລາງ (Detailed Tables)
    // 4.1 ຕາຕະລາງການນຳເຂົ້າແຍກຕາມສິນຄ້າ
    $sql_import_detail = "SELECT p.pro_id, p.pro_name, SUM(id.import_qty) as total_qty, SUM(id.import_qty * id.cost_price) as total_value
                          FROM import_detail id
                          LEFT JOIN import i ON id.import_id = i.import_id
                          LEFT JOIN product p ON id.pro_id = p.pro_id
                          $import_where
                          GROUP BY p.pro_id, p.pro_name
                          ORDER BY p.pro_id ASC";
    $import_details = $conn->query($sql_import_detail)->fetchAll(PDO::FETCH_ASSOC);

    // 4.2 ຕາຕະລາງການຈ່າຍອອກແຍກຕາມສິນຄ້າ
    $sql_export_detail = "SELECT p.pro_id, p.pro_name, SUM(ed.export_qty) as total_qty, SUM(ed.export_qty * ed.price) as total_value
                          FROM export_detail ed
                          LEFT JOIN export e ON ed.export_id = e.export_id
                          LEFT JOIN product p ON ed.pro_id = p.pro_id
                          $export_where
                          GROUP BY p.pro_id, p.pro_name
                          ORDER BY p.pro_id ASC";
    $export_details = $conn->query($sql_export_detail)->fetchAll(PDO::FETCH_ASSOC);

    // 4.3 ຕາຕະລາງສິນຄ້າຄົງເຫຼືອ
    $sql_stock_detail = "SELECT p.pro_id, p.pro_name, p.qty, p.price, (p.qty * p.price) as total_value, u.unit_name
                         FROM product p
                         LEFT JOIN unit u ON p.unit_id = u.unit_id
                         ORDER BY p.qty DESC";
    $stock_details = $conn->query($sql_stock_detail)->fetchAll(PDO::FETCH_ASSOC);

    // 4.4 ຕາຕະລາງສິນຄ້າໝົດອາຍຸ ແລະ ໃກ້ໝົດອາຍຸ
    $sql_exp_detail = "SELECT id.pro_id, p.pro_name, id.lot_no, id.exp_date, id.import_qty as qty,
                              CASE WHEN id.exp_date < CURDATE() THEN 'ໝົດອາຍຸແລ້ວ' ELSE 'ໃກ້ໝົດອາຍຸ' END as status
                       FROM import_detail id
                       LEFT JOIN import i ON id.import_id = i.import_id
                       LEFT JOIN product p ON id.pro_id = p.pro_id
                       $import_where AND id.exp_date IS NOT NULL AND id.exp_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
                       ORDER BY id.exp_date ASC";
    $exp_details = $conn->query($sql_exp_detail)->fetchAll(PDO::FETCH_ASSOC);

    // 📌 5. ດຶງຂໍ້ມູນສຳລັບ Charts (Chart 1 - 4)
    // 5.1 Chart 1: ສິນຄ້າທັງໝົດ - Grouped Bar (ນຳເຂົ້າ vs ຈ່າຍອອກ)
    $sql_chart1 = "SELECT p.pro_id, p.pro_name, 
                          COALESCE((SELECT SUM(import_qty) FROM import_detail id LEFT JOIN import i ON id.import_id = i.import_id $import_where AND id.pro_id = p.pro_id), 0) as total_imported,
                          COALESCE((SELECT SUM(export_qty) FROM export_detail ed LEFT JOIN export e ON ed.export_id = e.export_id $export_where AND ed.pro_id = p.pro_id), 0) as total_exported
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

    // 5.2 Chart 2: ບິນ PO ຄ້າງສົ່ງ - Line Chart
    $sql_chart2 = "SELECT o.order_date,
                          COUNT(*) as total_po,
                          SUM(CASE WHEN o.status = 'Pending' OR o.status = 'ລໍຖ້າກວດສອບ' THEN 1 ELSE 0 END) as pending_po
                   FROM orders o
                   $order_where
                   GROUP BY o.order_date
                   ORDER BY o.order_date ASC";
    $chart2_data = $conn->query($sql_chart2)->fetchAll(PDO::FETCH_ASSOC);

    $c2_labels = [];
    $c2_total = [];
    $c2_pending = [];
    foreach ($chart2_data as $row) {
        $c2_labels[] = date('d/m/Y', strtotime($row['order_date']));
        $c2_total[] = intval($row['total_po']);
        $c2_pending[] = intval($row['pending_po']);
    }

    // 5.3 Chart 3: ມູນຄ່າການນຳເຂົ້າ
    $sql_chart3 = "SELECT p.pro_name,
                          COALESCE(SUM(id.import_qty), 0) as total_qty,
                          COALESCE(SUM(id.import_qty * id.cost_price), 0) as total_value
                   FROM product p
                   LEFT JOIN import_detail id ON p.pro_id = id.pro_id
                   LEFT JOIN import i ON id.import_id = i.import_id
                   $import_where
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

    // 5.4 Chart 4: ມູນຄ່າການຈ່າຍອອກ
    $sql_chart4 = "SELECT p.pro_name,
                          COALESCE(SUM(ed.export_qty), 0) as total_qty,
                          COALESCE(SUM(ed.export_qty * ed.price), 0) as total_value
                   FROM product p
                   LEFT JOIN export_detail ed ON p.pro_id = ed.pro_id
                   LEFT JOIN export e ON ed.export_id = e.export_id
                   $export_where
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

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <link href="style/fonts.css" rel="stylesheet">
    <title>ລາຍງານສະຖິຕິ ແລະ ສັງລວມຂໍ້ມູນສາງ</title>
    <link href="bootstrap-5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="bootstrap-5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- SheetJS Excel Library -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <!-- Html2Pdf Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body {
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            background-color: #FAF4EB !important;
        }

        .col-md-10 {
            background-color: #FAF4EB !important;
        }

        .custom-navbar {
            margin-bottom: 0 !important;
        }

        /* Page Header Box (Sand/Beige gradient) */
        .pg-header-box {
            background: linear-gradient(90deg, #ffffff 0%, #C9956A 100%) !important;
            height: 60px !important;
            padding: 0 24px !important;
            margin-bottom: 0 !important;
            border-radius: 0px !important;
            border: none !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
        }

        .pg-header-title {
            color: #1a1a1a !important;
            font-weight: 700 !important;
            font-size: 16px !important;
            margin: 0 !important;
        }

        .pg-content-wrapper {
            padding: 24px !important;
        }

        /* Export buttons in Page Header */
        .btn-export-pdf {
            background-color: #7A1530 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 14px !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 8px 16px !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            transition: background 0.15s !important;
        }
        .btn-export-pdf:hover { background-color: #5a0f22 !important; }

        .btn-export-excel {
            background-color: #7A1530 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 14px !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 8px 16px !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            transition: background 0.15s !important;
        }
        .btn-export-excel:hover { background-color: #5a0f22 !important; }

        /* Custom Header Inputs */
        .header-date-group {
            width: auto !important;
            border-radius: 0 !important;
            border: none !important;
        }
        
        .header-date-label {
            background-color: #B04A62 !important;
            color: #ffffff !important;
            font-weight: bold !important;
            font-size: 14px !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 6px 12px !important;
        }
        
        .header-date-input {
            border: none !important;
            border-radius: 0 !important;
            background-color: #ffffff !important;
            color: #333333 !important;
            font-weight: bold !important;
            font-size: 14px !important;
            height: 34px !important;
            padding: 4px 10px !important;
            outline: none !important;
        }

        .header-btn-excel {
            background-color: #27AE60 !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 6px 14px !important;
            font-weight: bold !important;
            font-size: 13px !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            cursor: pointer !important;
            height: 34px !important;
        }
        .header-btn-excel:hover { background-color: #219150 !important; }

        .header-btn-pdf {
            background-color: #C0392B !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 6px 14px !important;
            font-weight: bold !important;
            font-size: 13px !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            cursor: pointer !important;
            height: 34px !important;
        }
        .header-btn-pdf:hover { background-color: #A93226 !important; }

        /* Report Cards */
        .report-card {
            border: none !important;
            border-radius: 0 !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
            background: #ffffff !important;
            margin-bottom: 24px !important;
        }

        .report-card-header {
            background-color: #7A1530 !important;
            color: #ffffff !important;
            padding: 12px 20px !important;
            border-radius: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            flex-wrap: wrap !important;
            gap: 15px !important;
        }

        .report-card-title {
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 16px !important;
            margin: 0 !important;
        }

        /* Report Tables */
        .report-table {
            width: 100% !important;
            margin-bottom: 0 !important;
            border-collapse: collapse !important;
        }

        .report-table thead th {
            background-color: #C9956A !important;
            color: #1a1a1a !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            padding: 12px 14px !important;
            border: none !important;
            white-space: nowrap !important;
        }

        .report-table tbody td {
            padding: 12px 14px !important;
            font-size: 15px !important;
            font-weight: bold !important;
            color: #333333 !important;
            border-bottom: 1px solid #EAEAEA !important;
            background-color: #ffffff !important;
            vertical-align: middle !important;
            white-space: nowrap !important;
        }

        .report-table tbody tr:last-child td {
            border-bottom: none !important;
        }

        /* Summary Total Accounting Line Style */
        .summary-row-total td {
            font-size: 16px !important;
            font-weight: bold !important;
            border-top: 3px solid #000000 !important;
            border-bottom: 3px double #000000 !important;
            padding: 15px 14px !important;
            color: #000000 !important;
            background-color: #ffffff !important;
        }

        @media print {
            body {
                background-color: #ffffff !important;
            }
            .col-md-2, .custom-navbar, .pg-header-box, .btn-export-pdf, .btn-export-excel, .report-card-header form, .report-card-header button {
                display: none !important;
            }
            .col-md-10 {
                width: 100% !important;
                margin-left: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
<div class="row">

<?php include 'sidebar.php'; ?>

<div class="col-md-10 p-0">
<?php include 'navbar.php'; ?>
<style>.custom-navbar { margin-bottom: 0 !important; }</style>

<div class="container-fluid p-0">
    
    <!-- Page Header Box -->
    <div class="pg-header-box">
        <h4 class="pg-header-title">ສະຖິຕິ ແລະ ລາຍງານ (Statistics & Reports)</h4>
        <div class="d-flex gap-2">
        </div>
    </div>

    <div class="pg-content-wrapper">

        <div id="report-content">
            <!-- Header printed only on PDF/Print exports -->
            <div class="d-none d-print-block mb-4 text-center">
                <h2>ລາຍງານສະຖິຕິ ແລະ ສັງລວມຂໍ້ມູນສາງສินຄ້າ</h2>
                <h5 class="text-muted"><?= htmlspecialchars($filter_description) ?></h5>
                <hr>
            </div>



            <!-- ==================== TABLES SECTION (TOP) ==================== -->

            <!-- 1. KPI Stats Summary Card -->
            <div class="report-card" id="card-summary">
                <div class="report-card-header">
                    <span class="report-card-title">ຂໍ້ມູນທັງໝົດ</span>
                    
                    <form method="GET" action="form_statistics_report.php" class="d-flex align-items-center gap-2 flex-wrap m-0">
                        <div class="input-group input-group-sm header-date-group">
                            <span class="input-group-text header-date-label">ວັນທີເລີ່ມຕົ້ນ</span>
                            <input type="date" name="date_start" class="form-control header-date-input" value="<?= htmlspecialchars($date_start) ?>" onchange="this.closest('form').submit()">
                        </div>
                        <div class="input-group input-group-sm header-date-group">
                            <span class="input-group-text header-date-label">ວັນທີສິ້ນສຸດ</span>
                            <input type="date" name="date_end" class="form-control header-date-input" value="<?= htmlspecialchars($date_end) ?>" onchange="this.closest('form').submit()">
                        </div>
                        
                        <button type="button" onclick="exportSingleTableToExcel('summaryTable', 'KPI_Summary_Report')" class="header-btn-excel">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13zM8 12h8v2H8v-2zm0 4h8v2H8v-2zm0-8h5v2H8V8z"/></svg>
                            Excel
                        </button>
                        <button type="button" onclick="exportSingleElementToPDF('card-summary', 'KPI_Summary_Report')" class="header-btn-pdf">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-4 6h-4v2h4v2h-4v2h4v2H9V7h6v2z"/></svg>
                            PDF
                        </button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="report-table" id="summaryTable">
                        <thead>
                            <tr>
                                <th>ລາຍການສະຖິຕິຫຼັກ</th>
                                <th style="text-align: center;">ຈຳນວນລວມ (ຊິ້ນ / ລາຍການ)</th>
                                <th style="text-align: right;">ມູນຄ່າລວມທັງໝົດ (₭)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>ການນຳເຂົ້າສິນຄ້າທັງໝົດ</td>
                                <td style="text-align: center;"><?= number_format($total_import_qty) ?></td>
                                <td style="text-align: right; color: #27AE60;"><?= number_format($total_import_value, 2) ?> ₭</td>
                            </tr>
                            <tr>
                                <td>ການຈ່າຍອອກສິນຄ້າທັງໝົດ</td>
                                <td style="text-align: center;"><?= number_format($total_export_qty) ?></td>
                                <td style="text-align: right; color: #E74C3C;"><?= number_format($total_export_value, 2) ?> ₭</td>
                            </tr>
                            <tr>
                                <td>ສິນຄ້າຄົງເຫຼືອໃນສາງ</td>
                                <td style="text-align: center; color: #2980B9;"><?= number_format($total_stock_qty) ?></td>
                                <td style="text-align: right; color: #2980B9;">
                                    <?php
                                        $total_stock_val = 0;
                                        foreach($stock_details as $sd) { $total_stock_val += $sd['total_value']; }
                                        echo number_format($total_stock_val, 2) . " ₭";
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="color: #C0392B;">ສິນຄ້າໝົດອາຍຸແລ້ວ (Expired Items)</td>
                                <td style="text-align: center; color: #C0392B;"><?= number_format($total_expired_count) ?> ລາຍການ</td>
                                <td style="text-align: right; color: #C0392B;">-</td>
                            </tr>
                            <tr>
                                <td style="color: #D35400;">ສິນຄ້າໃກ້ໝົດອາຍຸ (Expiring Items in 90 days)</td>
                                <td style="text-align: center; color: #D35400;"><?= number_format($total_expiring_count) ?> ລາຍການ</td>
                                <td style="text-align: right; color: #D35400;">-</td>
                            </tr>
                            
                            <!-- Total Row -->
                            <tr class="summary-row-total">
                                <td>ລວມທັງໝົດ</td>
                                <td style="text-align: center;">
                                    <?= number_format($total_import_qty + $total_export_qty + $total_stock_qty) ?> ລາຍການ
                                </td>
                                <td style="text-align: right;">
                                    <?= number_format($total_import_value + $total_export_value + $total_stock_val, 2) ?> ₭
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Import Details Card -->
            <div class="report-card" id="card-import-details">
                <div class="report-card-header">
                    <span class="report-card-title">ຂໍ້ມູນການນຳເຂົ້າ</span>
                    
                    <form method="GET" action="form_statistics_report.php" class="d-flex align-items-center gap-2 flex-wrap m-0">
                        <div class="input-group input-group-sm header-date-group">
                            <span class="input-group-text header-date-label">ວັນທີເລີ່ມຕົ້ນ</span>
                            <input type="date" name="date_start" class="form-control header-date-input" value="<?= htmlspecialchars($date_start) ?>" onchange="this.closest('form').submit()">
                        </div>
                        <div class="input-group input-group-sm header-date-group">
                            <span class="input-group-text header-date-label">ວັນທີສິ້ນສຸດ</span>
                            <input type="date" name="date_end" class="form-control header-date-input" value="<?= htmlspecialchars($date_end) ?>" onchange="this.closest('form').submit()">
                        </div>
                        
                        <button type="button" onclick="exportSingleTableToExcel('importTable', 'Import_Details_Report')" class="header-btn-excel">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13zM8 12h8v2H8v-2zm0 4h8v2H8v-2zm0-8h5v2H8V8z"/></svg>
                            Excel
                        </button>
                        <button type="button" onclick="exportSingleElementToPDF('card-import-details', 'Import_Details_Report')" class="header-btn-pdf">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-4 6h-4v2h4v2h-4v2h4v2H9V7h6v2z"/></svg>
                            PDF
                        </button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="report-table" id="importTable">
                        <thead>
                            <tr>
                                <th style="width: 20%;">ລະຫັດສິນຄ້າ</th>
                                <th>ຊື່ສິນຄ້າ</th>
                                <th style="text-align: center; width: 25%;">ຈຳນວນນຳເຂົ້າລວມ (ຊິ້ນ)</th>
                                <th style="text-align: right; width: 25%;">ມູນຄ່ານຳເຂົ້າລວມ (₭)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($import_details) > 0): ?>
                                <?php foreach($import_details as $id): ?>
                                <tr>
                                    <td><?= htmlspecialchars($id['pro_id']) ?></td>
                                    <td><?= htmlspecialchars($id['pro_name']) ?></td>
                                    <td style="text-align: center;"><?= number_format($id['total_qty']) ?></td>
                                    <td style="text-align: right;"><?= number_format($id['total_value'], 2) ?> ₭</td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted">ບໍ່ມີຂໍ້ມູນ</td></tr>
                            <?php endif; ?>
                            <!-- Total Row -->
                            <tr class="summary-row-total">
                                <td colspan="2">ລວມທັງໝົດ</td>
                                <td style="text-align: center;"><?= number_format($total_import_qty) ?> ຊິ້ນ</td>
                                <td style="text-align: right;"><?= number_format($total_import_value, 2) ?> ₭</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. Export Details Card -->
            <div class="report-card" id="card-export-details">
                <div class="report-card-header">
                    <span class="report-card-title">ຂໍ້ມູນການຈ່າຍອອກ</span>
                    
                    <form method="GET" action="form_statistics_report.php" class="d-flex align-items-center gap-2 flex-wrap m-0">
                        <div class="input-group input-group-sm header-date-group">
                            <span class="input-group-text header-date-label">ວັນທີເລີ່ມຕົ້ນ</span>
                            <input type="date" name="date_start" class="form-control header-date-input" value="<?= htmlspecialchars($date_start) ?>" onchange="this.closest('form').submit()">
                        </div>
                        <div class="input-group input-group-sm header-date-group">
                            <span class="input-group-text header-date-label">ວັນທີສິ້ນສຸດ</span>
                            <input type="date" name="date_end" class="form-control header-date-input" value="<?= htmlspecialchars($date_end) ?>" onchange="this.closest('form').submit()">
                        </div>
                        
                        <button type="button" onclick="exportSingleTableToExcel('exportTable', 'Export_Details_Report')" class="header-btn-excel">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13zM8 12h8v2H8v-2zm0 4h8v2H8v-2zm0-8h5v2H8V8z"/></svg>
                            Excel
                        </button>
                        <button type="button" onclick="exportSingleElementToPDF('card-export-details', 'Export_Details_Report')" class="header-btn-pdf">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-4 6h-4v2h4v2h-4v2h4v2H9V7h6v2z"/></svg>
                            PDF
                        </button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="report-table" id="exportTable">
                        <thead>
                            <tr>
                                <th style="width: 20%;">ລະຫັດສິນຄ້າ</th>
                                <th>ຊື່ສິນຄ້າ</th>
                                <th style="text-align: center; width: 25%;">ຈຳນວນຈ່າຍອອກລວມ (ຊິ້ນ)</th>
                                <th style="text-align: right; width: 25%;">ມູນຄ່າຈ່າຍອອກລວມ (₭)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($export_details) > 0): ?>
                                <?php foreach($export_details as $ed): ?>
                                <tr>
                                    <td><?= htmlspecialchars($ed['pro_id']) ?></td>
                                    <td><?= htmlspecialchars($ed['pro_name']) ?></td>
                                    <td style="text-align: center;"><?= number_format($ed['total_qty']) ?></td>
                                    <td style="text-align: right;"><?= number_format($ed['total_value'], 2) ?> ₭</td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted">ບໍ່ມີຂໍ້ມູນ</td></tr>
                            <?php endif; ?>
                            <!-- Total Row -->
                            <tr class="summary-row-total">
                                <td colspan="2">ລວມທັງໝົດ</td>
                                <td style="text-align: center;"><?= number_format($total_export_qty) ?> ຊິ້ນ</td>
                                <td style="text-align: right;"><?= number_format($total_export_value, 2) ?> ₭</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Stock Details Card -->
            <div class="report-card" id="card-stock-details">
                <div class="report-card-header">
                    <span class="report-card-title">ຂໍ້ມູນສິນຄ້າເຫຼືອໃນສາງ</span>
                    
                    <form method="GET" action="form_statistics_report.php" class="d-flex align-items-center gap-2 flex-wrap m-0">
                        <div class="input-group input-group-sm header-date-group">
                            <span class="input-group-text header-date-label">ວັນທີເລີ່ມຕົ້ນ</span>
                            <input type="date" name="date_start" class="form-control header-date-input" value="<?= htmlspecialchars($date_start) ?>" onchange="this.closest('form').submit()">
                        </div>
                        <div class="input-group input-group-sm header-date-group">
                            <span class="input-group-text header-date-label">ວັນທີສິ້ນສຸດ</span>
                            <input type="date" name="date_end" class="form-control header-date-input" value="<?= htmlspecialchars($date_end) ?>" onchange="this.closest('form').submit()">
                        </div>
                        
                        <button type="button" onclick="exportSingleTableToExcel('stockTable', 'Current_Stock_Report')" class="header-btn-excel">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13zM8 12h8v2H8v-2zm0 4h8v2H8v-2zm0-8h5v2H8V8z"/></svg>
                            Excel
                        </button>
                        <button type="button" onclick="exportSingleElementToPDF('card-stock-details', 'Current_Stock_Report')" class="header-btn-pdf">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-4 6h-4v2h4v2h-4v2h4v2H9V7h6v2z"/></svg>
                            PDF
                        </button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="report-table" id="stockTable">
                        <thead>
                            <tr>
                                <th style="width: 20%;">ລະຫັດສິນຄ້າ</th>
                                <th>ຊື່ສິນຄ້າ</th>
                                <th style="text-align: center; width: 15%;">ຈຳນວນໃນສາງ</th>
                                <th style="text-align: center; width: 15%;">ຫົວໜ່ວຍ</th>
                                <th style="text-align: right; width: 25%;">ລາຄາ/ຊິ້ນ (₭)</th>
                                <th style="text-align: right; width: 25%;">ມູນຄ່າລວມ (₭)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($stock_details) > 0): ?>
                                <?php foreach($stock_details as $sd): ?>
                                <tr>
                                    <td><?= htmlspecialchars($sd['pro_id']) ?></td>
                                    <td><?= htmlspecialchars($sd['pro_name']) ?></td>
                                    <td style="text-align: center;"><?= number_format($sd['qty']) ?></td>
                                    <td style="text-align: center;"><?= htmlspecialchars($sd['unit_name'] ?? 'ຊິ້ນ') ?></td>
                                    <td style="text-align: right;"><?= number_format($sd['price'], 2) ?> ₭</td>
                                    <td style="text-align: right;"><?= number_format($sd['total_value'], 2) ?> ₭</td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center text-muted">ບໍ່ມີຂໍ້ມູນ</td></tr>
                            <?php endif; ?>
                            <!-- Total Row -->
                            <tr class="summary-row-total">
                                <td colspan="2">ລວມທັງໝົດ</td>
                                <td style="text-align: center;"><?= number_format($total_stock_qty) ?> ຊິ້ນ</td>
                                <td></td>
                                <td></td>
                                <td style="text-align: right;"><?= number_format($total_stock_val, 2) ?> ₭</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 5. Expired & Expiring Card -->
            <div class="report-card" id="card-expired-details">
                <div class="report-card-header">
                    <span class="report-card-title">ຂໍ້ມູນສິນຄ້າໝົດອາຍຸ ແລະ ໃກ້ໝົດອາຍຸ</span>
                    
                    <form method="GET" action="form_statistics_report.php" class="d-flex align-items-center gap-2 flex-wrap m-0">
                        <div class="input-group input-group-sm header-date-group">
                            <span class="input-group-text header-date-label">ວັນທີເລີ່ມຕົ້ນ</span>
                            <input type="date" name="date_start" class="form-control header-date-input" value="<?= htmlspecialchars($date_start) ?>" onchange="this.closest('form').submit()">
                        </div>
                        <div class="input-group input-group-sm header-date-group">
                            <span class="input-group-text header-date-label">ວັນທີສິ້ນສຸດ</span>
                            <input type="date" name="date_end" class="form-control header-date-input" value="<?= htmlspecialchars($date_end) ?>" onchange="this.closest('form').submit()">
                        </div>
                        
                        <button type="button" onclick="exportSingleTableToExcel('expTable', 'Expired_Expiring_Report')" class="header-btn-excel">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13zM8 12h8v2H8v-2zm0 4h8v2H8v-2zm0-8h5v2H8V8z"/></svg>
                            Excel
                        </button>
                        <button type="button" onclick="exportSingleElementToPDF('card-expired-details', 'Expired_Expiring_Report')" class="header-btn-pdf">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-4 6h-4v2h4v2h-4v2h4v2H9V7h6v2z"/></svg>
                            PDF
                        </button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="report-table" id="expTable">
                        <thead>
                            <tr>
                                <th style="width: 15%;">ລະຫັດສິນຄ້າ</th>
                                <th>ຊື່ສິນຄ້າ</th>
                                <th style="width: 15%; text-align: center;">Lot No.</th>
                                <th style="width: 15%; text-align: center;">ວັນໝົດອາຍຸ</th>
                                <th style="width: 15%; text-align: center;">ຈຳນວນ (ຊິ້ນ)</th>
                                <th style="width: 20%; text-align: center;">ສະຖານະຄວາມສ່ຽງ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_exp_qty = 0;
                            if(count($exp_details) > 0): 
                            ?>
                                <?php foreach($exp_details as $exp): 
                                    $total_exp_qty += $exp['qty'];
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($exp['pro_id']) ?></td>
                                    <td><?= htmlspecialchars($exp['pro_name']) ?></td>
                                    <td style="text-align: center;"><?= htmlspecialchars($exp['lot_no'] ?? '-') ?></td>
                                    <td style="text-align: center;"><?= date('d/m/Y', strtotime($exp['exp_date'])) ?></td>
                                    <td style="text-align: center;"><?= number_format($exp['qty']) ?></td>
                                    <td style="text-align: center; font-weight: bold; color: <?= $exp['status'] == 'ໝົດອາຍຸແລ້ວ' ? '#C0392B' : '#D35400' ?>;">
                                        <?= htmlspecialchars($exp['status']) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center text-muted">🟢 ປອດໄພ! ບໍ່ມີສິນຄ້າໝົດອາຍຸ ຫຼື ໃກ້ໝົດອາຍຸໃນໄລຍະການກັ່ນຕອງນີ້</td></tr>
                            <?php endif; ?>
                            <!-- Total Row -->
                            <tr class="summary-row-total">
                                <td colspan="4">ລວມທັງໝົດ</td>
                                <td style="text-align: center;"><?= number_format($total_exp_qty) ?> ຊິ້ນ</td>
                                <td style="text-align: center;">-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ==================== CHARTS SECTION (BOTTOM) ==================== -->

            <!-- Charts Row 1 -->
            <div class="row mb-4 page-break-before" id="card-charts-row1">
                <div class="col-md-6 mb-3">
                    <div class="report-card">
                        <div class="report-card-header d-flex justify-content-between align-items-center">
                            <span>ສິນຄ້າທັງໝົດ (ນຳເຂົ້າ ແລະ ຈ່າຍອອກ)</span>
                            <button onclick="exportSingleElementToPDF('allProductsChart', 'Products_Import_Export_Chart')" class="btn btn-sm d-print-none" style="background-color: #E74C3C !important; color: #ffffff !important; font-size: 11px;">PDF</button>
                        </div>
                        <div class="p-3">
                            <canvas id="allProductsChart" style="max-height: 250px; width: 100%;"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <div class="report-card">
                        <div class="report-card-header d-flex justify-content-between align-items-center">
                            <span>ບິນ PO ຄ້າງສົ່ງ (ແນວໂນ້ມທັງໝົດ ແລະ ຄ້າງສົ່ງ)</span>
                            <button onclick="exportSingleElementToPDF('pendingPoChart', 'Pending_PO_Trend_Chart')" class="btn btn-sm d-print-none" style="background-color: #E74C3C !important; color: #ffffff !important; font-size: 11px;">PDF</button>
                        </div>
                        <div class="p-3">
                            <canvas id="pendingPoChart" style="max-height: 250px; width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 2 -->
            <div class="row mb-4" id="card-charts-row2">
                <div class="col-md-6 mb-3">
                    <div class="report-card">
                        <div class="report-card-header d-flex justify-content-between align-items-center">
                            <span>ມູນຄ່າການນຳເຂົ້າ (ຈຳນວນ ແລະ ມູນຄ່າ)</span>
                            <button onclick="exportSingleElementToPDF('importValueChart', 'Import_Value_Chart')" class="btn btn-sm d-print-none" style="background-color: #E74C3C !important; color: #ffffff !important; font-size: 11px;">PDF</button>
                        </div>
                        <div class="p-3">
                            <canvas id="importValueChart" style="max-height: 250px; width: 100%;"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <div class="report-card">
                        <div class="report-card-header d-flex justify-content-between align-items-center">
                            <span>ມູນຄ່າການຈ່າຍອອກ (ຈຳນວນ ແລະ ມູນຄ່າ)</span>
                            <button onclick="exportSingleElementToPDF('exportValueChart', 'Export_Value_Chart')" class="btn btn-sm d-print-none" style="background-color: #E74C3C !important; color: #ffffff !important; font-size: 11px;">PDF</button>
                        </div>
                        <div class="p-3">
                            <canvas id="exportValueChart" style="max-height: 250px; width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
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
                backgroundColor: '#C9956A',
                borderColor: '#C9956A',
                borderWidth: 1
            },
            {
                label: 'ຈຳນວນຈ່າຍອອກລວມ',
                data: c1Exported,
                backgroundColor: '#7A1530',
                borderColor: '#7A1530',
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
                borderColor: '#C9956A',
                backgroundColor: 'rgba(201, 149, 106, 0.1)',
                tension: 0.3,
                fill: true,
                borderWidth: 3
            },
            {
                label: 'ບິນ PO ຄ້າງສົ່ງ',
                data: c2Pending.length > 0 ? c2Pending : [0],
                borderColor: '#7A1530',
                backgroundColor: 'rgba(122, 21, 48, 0.1)',
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
                backgroundColor: '#C9956A',
                yAxisID: 'y',
                borderWidth: 1
            },
            {
                label: 'ມູນຄ່າການນຳເຂົ້າ (₭)',
                data: c3Value,
                backgroundColor: '#7A1530',
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
                backgroundColor: '#C9956A',
                yAxisID: 'y',
                borderWidth: 1
            },
            {
                label: 'ມູນຄ່າການຈ່າຍອອກ (₭)',
                data: c4Value,
                backgroundColor: '#7A1530',
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

// 📌 Export Entire Workbook (Multi-Tab Excel)
function exportToExcel() {
    let wb = XLSX.utils.book_new();
    
    let wsSummary = XLSX.utils.table_to_sheet(document.getElementById("summaryTable"));
    XLSX.utils.book_append_sheet(wb, wsSummary, "Overview Summary");
    
    let wsImport = XLSX.utils.table_to_sheet(document.getElementById("importTable"));
    XLSX.utils.book_append_sheet(wb, wsImport, "Import Details");
    
    let wsExport = XLSX.utils.table_to_sheet(document.getElementById("exportTable"));
    XLSX.utils.book_append_sheet(wb, wsExport, "Export Details");
    
    let wsStock = XLSX.utils.table_to_sheet(document.getElementById("stockTable"));
    XLSX.utils.book_append_sheet(wb, wsStock, "Stock Remaining");
    
    let wsExp = XLSX.utils.table_to_sheet(document.getElementById("expTable"));
    XLSX.utils.book_append_sheet(wb, wsExp, "Expired & Expiring");
    
    XLSX.writeFile(wb, "Inventory_Statistics_Report_All.xlsx");
}

// 📌 Export Single Table to Excel
function exportSingleTableToExcel(tableId, filename) {
    let wb = XLSX.utils.book_new();
    let table = document.getElementById(tableId);
    let ws = XLSX.utils.table_to_sheet(table);
    XLSX.utils.book_append_sheet(wb, ws, "Report");
    XLSX.writeFile(wb, filename + ".xlsx");
}

// 📌 Export Entire Page to PDF
function exportToPDF() {
    const element = document.getElementById('report-content');
    const opt = {
        margin:       0.3,
        filename:     'Inventory_Statistics_Report_All.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, logging: false },
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' },
        pagebreak:    { mode: ['css', 'legacy'] }
    };
    html2pdf().set(opt).from(element).save();
}

// 📌 Export Single Card/Element to PDF
function exportSingleElementToPDF(elementId, filename) {
    const element = document.getElementById(elementId);
    const opt = {
        margin:       0.3,
        filename:     filename + '.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, logging: false },
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>
</body>
</html>
