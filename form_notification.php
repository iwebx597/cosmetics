<?php
session_start();
if (!isset($_SESSION['userid'])) { header("Location: index.php"); exit; }

require 'cmt_db.php';

try {
    // 1. สินค้าใกล้หมดสาง (qty <= 10)
    $low_stock = $conn->query("SELECT p.pro_id, p.pro_name, p.qty, u.unit_name, l.loc_name FROM product p LEFT JOIN unit u ON p.unit_id = u.unit_id LEFT JOIN location l ON p.loc_id = l.loc_id WHERE p.qty <= 10 AND p.qty > 0 ORDER BY p.qty ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 2. สินค้าหมดสาง (qty = 0)
    $out_stock = $conn->query("SELECT p.pro_id, p.pro_name, u.unit_name, l.loc_name FROM product p LEFT JOIN unit u ON p.unit_id = u.unit_id LEFT JOIN location l ON p.loc_id = l.loc_id WHERE p.qty = 0 ORDER BY p.pro_name ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 3. สินค้าหมดอายุแล้ว
    $expired = $conn->query("SELECT id.lot_no, id.exp_date, id.import_qty, p.pro_name, l.loc_name FROM import_detail id LEFT JOIN product p ON id.pro_id = p.pro_id LEFT JOIN location l ON p.loc_id = l.loc_id WHERE id.exp_date IS NOT NULL AND id.exp_date < CURDATE() ORDER BY id.exp_date ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 4. สินค้าใกล้หมดอายุ (90 วัน)
    $expiring = $conn->query("SELECT id.lot_no, id.exp_date, id.import_qty, p.pro_name, l.loc_name, DATEDIFF(id.exp_date, CURDATE()) as days_left FROM import_detail id LEFT JOIN product p ON id.pro_id = p.pro_id LEFT JOIN location l ON p.loc_id = l.loc_id WHERE id.exp_date IS NOT NULL AND id.exp_date >= CURDATE() AND id.exp_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) ORDER BY id.exp_date ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 5. บิล PO ค้างส่ง
    $pending_po = $conn->query("SELECT o.order_id, o.order_date, s.sup_name FROM orders o LEFT JOIN supplier s ON o.sup_id = s.sup_id WHERE o.status = 'Pending' OR o.status = 'ລໍຖ້າກວດສອບ' ORDER BY o.order_date ASC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

$total_alerts = count($low_stock) + count($out_stock) + count($expired) + count($expiring) + count($pending_po);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <link href="style/fonts.css" rel="stylesheet">
    <title>ແຈ້ງເຕືອນລະບົບ</title>
    <link href="bootstrap-5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="bootstrap-5.3.8/dist/js/bootstrap.bundle.min.js"></script>
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

        /* Page Header Title (Sand/Beige gradient) */
        .pg-header-box {
            background: linear-gradient(90deg, #ffffff 0%, #C9956A 100%) !important;
            height: 60px !important;
            padding: 0 24px !important;
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

        /* Summary badges */
        .notif-summary {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .notif-kpi {
            flex: 1;
            min-width: 150px;
            background: #ffffff;
            border: none;
            border-radius: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            padding: 18px 20px;
            display: flex;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            text-align: center !important;
            gap: 10px;
        }

        .notif-kpi-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-bottom: 4px;
        }

        .notif-kpi-count {
            font-size: 26px;
            font-weight: 800;
            line-height: 1.2;
            text-align: center !important;
        }

        .notif-kpi-label {
            font-size: 13px;
            color: #666666;
            margin-top: 3px;
            font-weight: bold;
            text-align: center !important;
        }

        /* Notification card */
        .notif-card {
            border: none !important;
            border-radius: 0 !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
            background: #ffffff !important;
            margin-bottom: 24px !important;
        }

        .notif-card-header {
            font-weight: 700 !important;
            font-size: 15px !important;
            padding: 17px 24px !important;
            border-radius: 0 !important;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: space-between;
            background-color: #7A1530 !important;
            color: #ffffff !important;
        }

        .notif-table {
            width: 100% !important;
            margin-bottom: 0 !important;
            border-collapse: collapse !important;
        }

        .notif-table thead th {
            background-color: #C9956A !important;
            color: #1a1a1a !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            padding: 12px 14px !important;
            border: none !important;
            white-space: nowrap !important;
        }

        .notif-table tbody td {
            padding: 12px 14px !important;
            font-size: 15px !important;
            font-weight: bold !important;
            color: #333333 !important;
            border-bottom: 1px solid #EAEAEA !important;
            background-color: #ffffff !important;
            vertical-align: middle !important;
            white-space: nowrap !important;
        }

        .badge-pill {
            display: inline-block;
            background: #ffffff;
            color: #7A1530;
            font-size: 12px;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 0;
        }

        .empty-row td {
            text-align: center;
            color: #28a745;
            font-weight: bold;
            padding: 24px !important;
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

    <div class="pg-header-box">
        <h4 class="pg-header-title">ແຈ້ງເຕືອນລະບົບ (System Notifications)</h4>
    </div>

    <div class="pg-content-wrapper">

        <!-- Summary KPI Row -->
        <div class="notif-summary">
            <div class="notif-kpi">
                <div class="notif-kpi-icon" style="background:#FDEDEC;">
                    <svg width="22" height="22" fill="#7A1530" viewBox="0 0 24 24"><path d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.11 0 2-.89 2-2V5c0-1.11-.89-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z"/></svg>
                </div>
                <div>
                    <div class="notif-kpi-label">ສິນຄ້າທັງໝົດ</div>
                    <div class="notif-kpi-count" style="color:#7A1530;"><?= count($out_stock) ?></div>
                </div>
            </div>
            <div class="notif-kpi">
                <div class="notif-kpi-icon" style="background:#FEF9E7;">
                    <svg width="22" height="22" fill="#C9956A" viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
                </div>
                <div>
                    <div class="notif-kpi-label">ຄ້າໃກ້ໝົດ(≤10)</div>
                    <div class="notif-kpi-count" style="color:#C9956A;"><?= count($low_stock) ?></div>
                </div>
            </div>
            <div class="notif-kpi">
                <div class="notif-kpi-icon" style="background:#FDEDEC;">
                    <svg width="22" height="22" fill="#7A1530" viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>
                </div>
                <div>
                    <div class="notif-kpi-label">ສິນຄ້າໝົດອາຍຸ</div>
                    <div class="notif-kpi-count" style="color:#7A1530;"><?= count($expired) ?></div>
                </div>
            </div>
            <div class="notif-kpi">
                <div class="notif-kpi-icon" style="background:#FFF3E0;">
                    <svg width="22" height="22" fill="#C9956A" viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>
                </div>
                <div>
                    <div class="notif-kpi-label">ໃກ້ໝົດອາຍຸ(90ວັນ)</div>
                    <div class="notif-kpi-count" style="color:#C9956A;"><?= count($expiring) ?></div>
                </div>
            </div>
            <div class="notif-kpi">
                <div class="notif-kpi-icon" style="background:#F5EEF8;">
                    <svg width="22" height="22" fill="#7A1530" viewBox="0 0 24 24"><path d="M19 3H5c-1.11 0-2 .89-2 2v14c0 1.11.89 2 2 2h14c1.11 0 2-.89 2-2V5c0-1.11-.89-2-2-2zm-7 14l-5-5 1.41-1.41L12 14.17l7.59-7.59L21 8l-9 9z"/></svg>
                </div>
                <div>
                    <div class="notif-kpi-label">ບິນ PO ຄ້າງສົ່ງ</div>
                    <div class="notif-kpi-count" style="color:#7A1530;"><?= count($pending_po) ?></div>
                </div>
            </div>
        </div>

        <!-- Card 2: Low Stock -->
        <div class="notif-card">
            <div class="notif-card-header">
                <span>ສິນຄ້າໃກ້ໝົດສາງ (Low Stock ≤ 10)</span>
                <span class="badge-pill"><?= count($low_stock) ?> ລາຍการ</span>
            </div>
            <div class="table-responsive">
                <table class="notif-table">
                    <thead><tr><th style="width:120px;">ລະຫັດ</th><th>ຊື່ສິນຄ້າ</th><th style="text-align:center; width:160px;">ຈຳນວນຄົງເຫຼືອ</th><th style="text-align:center; width:140px;">ຫົວໜ່ວຍ</th><th style="text-align:center; width:180px;">ບ່ອນຈັດເກັບ (Location)</th><th style="text-align:center; width:160px;">ສະຖານະ</th></tr></thead>
                    <tbody>
                        <?php if ($low_stock): foreach ($low_stock as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['pro_id']) ?></td>
                            <td style="color: #1a1a1a;"><?= htmlspecialchars($r['pro_name']) ?></td>
                            <td style="text-align:center; color:#C9956A;"><?= number_format($r['qty']) ?></td>
                            <td style="text-align:center;"><?= htmlspecialchars($r['unit_name'] ?? '-') ?></td>
                            <td style="text-align:center;"><?= htmlspecialchars($r['loc_name'] ?? '-') ?></td>
                            <td style="text-align:center;">
                                <?php if ($r['qty'] <= 3): ?>
                                    <span style="background:#7A1530;color:#fff;padding:4px 10px;font-size:12px;font-weight:700;">ວິກິດ</span>
                                <?php elseif ($r['qty'] <= 6): ?>
                                    <span style="background:#C9956A;color:#1a1a1a;padding:4px 10px;font-size:12px;font-weight:700;">ຕ່ຳ</span>
                                <?php else: ?>
                                    <span style="background:#e0e0e0;color:#333;padding:4px 10px;font-size:12px;font-weight:700;">ໃກ້ໝົດ</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr class="empty-row"><td colspan="6">✅ ສິນຄ້າທຸກລายການຍັງຢູ່ໃນລະດັບດີ</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Card 3: Expired -->
        <div class="notif-card">
            <div class="notif-card-header">
                <span>ສິນຄ້າໝົດອາຍຸແລ້ວ (Expired Products)</span>
                <span class="badge-pill"><?= count($expired) ?> ລາຍການ</span>
            </div>
            <div class="table-responsive">
                <table class="notif-table">
                    <thead><tr><th>ຊື່ສິນຄ້າ</th><th style="text-align:center; width:160px;">Lot No.</th><th style="text-align:center; width:180px;">ວັນໝົດອາຍຸ</th><th style="text-align:center; width:140px;">ຈຳນວນ</th><th style="text-align:center; width:180px;">ບ່ອນຈັດເກັບ (Location)</th></tr></thead>
                    <tbody>
                        <?php if ($expired): foreach ($expired as $r): ?>
                        <tr>
                            <td style="color: #1a1a1a;"><?= htmlspecialchars($r['pro_name']) ?></td>
                            <td style="text-align:center;"><?= htmlspecialchars($r['lot_no'] ?? '-') ?></td>
                            <td style="text-align:center; color:#7A1530;"><?= date('d/m/Y', strtotime($r['exp_date'])) ?></td>
                            <td style="text-align:center;"><?= number_format($r['import_qty']) ?></td>
                            <td style="text-align:center;"><?= htmlspecialchars($r['loc_name'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr class="empty-row"><td colspan="5">✅ ບໍ່ມີສິນຄ້າໝົດອາຍຸ</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Card 4: Expiring Soon -->
        <div class="notif-card">
            <div class="notif-card-header">
                <span>ສິນຄ້າໃກ້ໝົດອາຍຸ ພາຍໃນ 90 ວັນ (Expiring Soon)</span>
                <span class="badge-pill"><?= count($expiring) ?> ລາຍການ</span>
            </div>
            <div class="table-responsive">
                <table class="notif-table">
                    <thead><tr><th>ຊື່ສິນຄ້າ</th><th style="text-align:center; width:160px;">Lot No.</th><th style="text-align:center; width:180px;">ວັນໝົດອາຍຸ</th><th style="text-align:center; width:140px;">ຈຳນວນ</th><th style="text-align:center; width:180px;">ບ່ອນຈັດເກັບ (Location)</th><th style="text-align:center; width:160px;">ເຫຼືອ (ວັນ)</th></tr></thead>
                    <tbody>
                        <?php if ($expiring): foreach ($expiring as $r): ?>
                        <tr>
                            <td style="color: #1a1a1a;"><?= htmlspecialchars($r['pro_name']) ?></td>
                            <td style="text-align:center;"><?= htmlspecialchars($r['lot_no'] ?? '-') ?></td>
                            <td style="text-align:center;"><?= date('d/m/Y', strtotime($r['exp_date'])) ?></td>
                            <td style="text-align:center;"><?= number_format($r['import_qty']) ?></td>
                            <td style="text-align:center;"><?= htmlspecialchars($r['loc_name'] ?? '-') ?></td>
                            <td style="text-align:center; color:<?= $r['days_left'] <= 30 ? '#7A1530' : ($r['days_left'] <= 60 ? '#C9956A' : '#333333') ?>;">
                                <?= $r['days_left'] ?> ວັນ
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr class="empty-row"><td colspan="6">✅ ບໍ່ມີສິນຄ້າໃກ້ໝົດອາຍຸໃນ 90 ວັນ</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Card 5: Pending PO -->
        <div class="notif-card">
            <div class="notif-card-header">
                <span>ບິນ PO ຄ້າງສົ່ງ (Pending Purchase Orders)</span>
                <span class="badge-pill"><?= count($pending_po) ?> ບິນ</span>
            </div>
            <div class="table-responsive">
                <table class="notif-table">
                    <thead><tr><th style="width:140px;">ລະຫັດ PO</th><th>ຜູ້ສະໜອງ</th><th style="text-align:center; width:180px;">ວັນທີສັ່ງ</th><th style="text-align:center; width:160px;">ສະຖານະ</th></tr></thead>
                    <tbody>
                        <?php if ($pending_po): foreach ($pending_po as $r): ?>
                        <tr>
                            <td style="color: #1a1a1a;"><?= htmlspecialchars($r['order_id']) ?></td>
                            <td style="color: #1a1a1a;"><?= htmlspecialchars($r['sup_name'] ?? '-') ?></td>
                            <td style="text-align:center;"><?= date('d/m/Y', strtotime($r['order_date'])) ?></td>
                            <td style="text-align:center;"><span style="background:#7A1530;color:#fff;padding:4px 12px;font-size:12px;font-weight:700;">ຄ້າງສົ່ງ</span></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr class="empty-row"><td colspan="4">ບໍ່ມີບິນ PO ຄ้างສົ່ງ</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Card 1: Out of Stock -->
        <div class="notif-card">
            <div class="notif-card-header">
                <span>ສິນຄ້າໝົດສາງແລ້ວ (Out of Stock)</span>
                <span class="badge-pill"><?= count($out_stock) ?> ລາຍການ</span>
            </div>
            <div class="table-responsive">
                <table class="notif-table">
                    <thead><tr><th style="width:120px;">ລະຫັດ</th><th>ຊື່ສິນຄ້າ</th><th style="text-align:center; width:120px;">ຈຳນວນ</th><th style="text-align:center; width:140px;">ຫົວໜ່ວຍ</th><th style="text-align:center; width:180px;">ບ່ອນຈັດເກັບ (Location)</th></tr></thead>
                    <tbody>
                        <?php if ($out_stock): foreach ($out_stock as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['pro_id']) ?></td>
                            <td style="color: #1a1a1a;"><?= htmlspecialchars($r['pro_name']) ?></td>
                            <td style="text-align:center; color:#7A1530; font-weight:800;">0</td>
                            <td style="text-align:center;"><?= htmlspecialchars($r['unit_name'] ?? '-') ?></td>
                            <td style="text-align:center;"><?= htmlspecialchars($r['loc_name'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr class="empty-row"><td colspan="5">ບໍ່ມີສິນຄ້າໝົດສາງ</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>


    </div>
</div>
</div>
</div>
</div>
</body>
</html>
