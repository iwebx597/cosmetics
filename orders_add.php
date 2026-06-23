<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

require 'cmt_db.php'; // ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ

$msg = "";
$msg_type = "";

// 📌 1. ສ້າງເລກທີໃບບິນອັດຕະໂນມັດ (ຕົວຢ່າງ: PO-202606030001)
$po_prefix = "PO-" . date('Ymd');
try {
    $stmt_po = $conn->prepare("SELECT order_no FROM orders WHERE order_no LIKE :prefix ORDER BY order_id DESC LIMIT 1");
    $stmt_po->execute(['prefix' => $po_prefix . '%']);
    $last_po = $stmt_po->fetch(PDO::FETCH_ASSOC);
    
    if ($last_po) {
        $last_num = intval(substr($last_po['order_no'], -4));
        $next_num = str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $next_num = "0001";
    }
    $auto_order_no = $po_prefix . $next_num;
} catch (PDOException $e) {
    $auto_order_no = $po_prefix . "0001";
}

// 📌 2. ຕອນກົດປຸ່ມບັນທຶກໃບບິນສັ່ງຊື້
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_save_order'])) {
    $order_no = trim($_POST['order_no']);
    $sup_id = $_POST['sup_id'];
    $emp_id = $_SESSION['userid']; // ດຶງ ID ພະນັກງານທີ່ Login ຢູ່ປັດຈຸບັນ
    $order_date = $_POST['order_date'];
    $expected_date = $_POST['expected_date'];
    $status = "Pending"; // ຕັ້ງສະຖານະເລີ່ມຕົ້ນເປັນ ລໍຖ້າສົ່ງເຄື່ອງ
    $remark = trim($_POST['remark']);

    // ລາຍການສິນຄ້າ ( arrays ຈາກຟອມ )
    $pro_ids = $_POST['pro_id'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $prices = $_POST['price'] ?? [];
    $unit_ids = $_POST['unit_id'] ?? [];

    if (!empty($order_no) && !empty($sup_id) && count($pro_ids) > 0) {
        try {
            // ເລີ່ມຕົ້ນ Transaction ເພື່ອຄວາມປອດໄພຂອງຂໍ້ມູນ
            $conn->beginTransaction();

            // 💾 2.1 ບັນທຶກລົງຕາຕະລາງ orders (ຫົວບິນ)
            $sql_order = "INSERT INTO orders (order_no, sup_id, emp_id, order_date, expected_date, status, remark) 
                          VALUES (:order_no, :sup_id, :emp_id, :order_date, :expected_date, :status, :remark)";
            $stmt_o = $conn->prepare($sql_order);
            $stmt_o->execute([
                'order_no' => $order_no,
                'sup_id' => $sup_id,
                'emp_id' => $emp_id,
                'order_date' => $order_date,
                'expected_date' => $expected_date,
                'status' => $status,
                'remark' => $remark
            ]);

            // ດຶງ order_id ທີ່ຫາກໍ່ Insert ເຂົ້າໄປມື້ກີ້
            $order_id = $conn->lastInsertId();

            // 💾 2.2 ບັນທຶກລົງຕາຕະລາງ order_detail (ລາຍການສິນຄ້າ)
            $sql_detail = "INSERT INTO order_detail (order_id, pro_id, qty, unit_id, price) 
                           VALUES (:order_id, :pro_id, :qty, :unit_id, :price)";
            $stmt_d = $conn->prepare($sql_detail);

            foreach ($pro_ids as $index => $pro_id) {
                if (!empty($pro_id)) {
                    $stmt_d->execute([
                        'order_id' => $order_id,
                        'pro_id' => $pro_id,
                        'qty' => intval($qtys[$index]),
                        'unit_id' => intval($unit_ids[$index]),
                        'price' => floatval($prices[$index])
                    ]);
                }
            }

            // ຖ້າບໍ່ມີຫຍັງຜິດພາດ ໃຫ້ Commit ຂໍ້ມູນລົງ Database ພ້ອມກັນ
            $conn->commit();

            echo "<script>
                alert('🎉 ບັນທຶກໃບບິນສັ່ງຊື້ສິນຄ້າສຳເລັດແລ້ວ!');
                location='form_orders.php';
            </script>";
            exit;

        } catch (PDOException $e) {
            // ຖ້າເກີດ Error ໃຫ້ Rollback ຍົກເລີກທັງໝົດ
            $conn->rollBack();
            $msg = "❌ ບໍ່ສາມາດບັນທຶກໄດ້: " . $e->getMessage();
            $msg_type = "danger";
        }
    } else {
        $msg = "⚠️ ກະລຸນາເລືອກຜູ້ສະໜອງ ແລະ ເພີ່ມລາຍການສິນຄ້າຢ່າງໜ້ອຍ 1 ລາຍການ!";
        $msg_type = "warning";
    }
}

// 📌 3. ດຶງຂໍ້ມູນມາສະແດງໃນ Select Option
$suppliers = $conn->query("SELECT * FROM supplier ORDER BY sup_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$products = $conn->query("SELECT p.pro_id, p.pro_name, p.pro_brand, p.price, p.unit_id, u.unit_name 
                          FROM product p 
                          LEFT JOIN unit u ON p.unit_id = u.unit_id 
                          ORDER BY p.pro_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <link href="style/fonts.css" rel="stylesheet">
    <title>ສ້າງໃບບິນສັ່ງຊື້ໃໝ່</title>
    <link href="bootstrap-5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="bootstrap-5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            background-color: #FAF4EB !important;
        }
        
        .col-md-10 {
            background-color: #FAF4EB !important;
        }

        /* Override navbar margin so header is flush */
        .custom-navbar {
            margin-bottom: 0 !important;
        }

        /* Page Header Title (Sand/Beige gradient) */
        .pg-header-box {
            background: linear-gradient(90deg, #ffffff 0%, #C9956A 100%) !important;
            height: 60px !important;
            padding: 0 24px !important;
            margin-bottom: 0 !important;
            border-radius: 0 !important;
            display: flex !important;
            align-items: center;
            justify-content: space-between;
        }

        .pg-header-title {
            color: #1a1a1a !important;
            font-weight: 700 !important;
            font-size: 16px !important;
            margin: 0 !important;
        }

        /* Back Button styled as flat Crimson rect */
        .pg-back-btn {
            background-color: #7A1530 !important;
            color: #ffffff !important;
            border-radius: 0 !important;
            font-weight: 700 !important;
            font-size: 14px !important;
            padding: 7px 18px !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            transition: background 0.15s, color 0.15s !important;
        }

        .pg-back-btn:hover {
            background-color: #5a0f22 !important;
            color: #ffffff !important;
        }

        /* Content wrapper */
        .pg-content-wrapper {
            padding: 24px !important;
        }

        /* Card Container */
        .pg-card {
            border: none !important;
            border-radius: 0 !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
            background-color: #ffffff !important;
            margin-bottom: 24px !important;
        }

        /* Card Header Crimson Background */
        .pg-card-header {
            background-color: #7A1530 !important;
            padding: 17px 24px !important;
            border: none !important;
            border-radius: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
        }

        .pg-card-title {
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            margin: 0 !important;
        }

        /* Form Controls */
        .pg-label {
            font-weight: 700 !important;
            font-size: 15px !important;
            color: #1a1a1a !important;
            margin-bottom: 6px !important;
            display: block !important;
        }

        .pg-input-master {
            border: 1px solid #cccccc !important;
            border-radius: 0 !important;
            font-size: 16px !important;
            font-weight: 700 !important;
            padding: 10px 14px !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            background-color: #eeeeee !important;
            color: #7A1530 !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }

        .pg-input-master:focus {
            background-color: #ffffff !important;
            border-color: #C9956A !important;
            outline: none !important;
        }

        .pg-input-master-select {
            border: 1px solid #cccccc !important;
            border-radius: 0 !important;
            font-size: 15px !important;
            font-weight: 700 !important;
            padding: 10px 14px !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            background-color: #ffffff !important;
            color: #1a1a1a !important;
            width: 100% !important;
            box-sizing: border-box !important;
            appearance: auto !important;
        }

        .pg-input-master-select:focus {
            outline: none !important;
            border-color: #C9956A !important;
        }

        /* Customize native date picker calendar icon color */
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(19%) sepia(91%) saturate(5464%) hue-rotate(352deg) brightness(85%) contrast(100%);
            cursor: pointer;
        }

        /* Add Item Button in Card Header */
        .btn-add-item {
            background-color: #C9956A !important;
            color: #1a1a1a !important;
            font-weight: 700 !important;
            font-size: 14px !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 6px 16px !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            transition: background 0.15s !important;
        }

        .btn-add-item:hover {
            background-color: #B88458 !important;
        }

        /* Table custom scrollbar */
        .table-responsive-custom {
            overflow-x: auto !important;
            width: 100% !important;
            display: block !important;
        }

        /* Table header - Sand/Beige background */
        .pg-table thead th {
            background-color: #C9956A !important;
            color: #1a1a1a !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            padding: 12px 14px !important;
            border: 1px solid #cccccc !important;
            white-space: nowrap !important;
            text-align: center !important;
        }

        .pg-table tbody td {
            padding: 8px 8px !important;
            font-size: 15px !important;
            font-weight: bold !important;
            color: #333333 !important;
            border: 1px solid #cccccc !important;
            background-color: #ffffff !important;
            vertical-align: middle !important;
        }

        /* Table Input elements */
        .tbl-select {
            border: 1px solid #cccccc !important;
            border-radius: 0 !important;
            background-color: #ffffff !important;
            color: #1a1a1a !important;
            font-size: 15px !important;
            padding: 8px 12px !important;
            width: 100% !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            appearance: auto !important;
            font-weight: bold !important;
        }

        .tbl-select:focus {
            outline: none !important;
            border-color: #C9956A !important;
        }

        .tbl-input-stock {
            border: 1px solid #cccccc !important;
            border-radius: 0 !important;
            background-color: #eeeeee !important;
            color: #666666 !important;
            font-size: 15px !important;
            padding: 8px 12px !important;
            text-align: center !important;
            width: 100% !important;
            font-weight: 700 !important;
        }

        .tbl-input-qty {
            border: 1px solid #cccccc !important;
            border-radius: 0 !important;
            background-color: #ffffff !important;
            color: #7A1530 !important;
            font-size: 15px !important;
            padding: 8px 12px !important;
            text-align: center !important;
            width: 100% !important;
            font-weight: 700 !important;
        }

        .tbl-input-qty:focus {
            outline: none !important;
            border-color: #C9956A !important;
        }

        .tbl-input-price {
            border: 1px solid #cccccc !important;
            border-radius: 0 !important;
            background-color: #ffffff !important;
            color: #1a1a1a !important;
            font-size: 15px !important;
            padding: 8px 12px !important;
            text-align: center !important;
            width: 100% !important;
            font-weight: bold !important;
        }

        .tbl-input-price:focus {
            outline: none !important;
            border-color: #C9956A !important;
        }

        .tbl-input-total {
            border: 1px solid #cccccc !important;
            border-radius: 0 !important;
            background-color: #eeeeee !important;
            color: #7A1530 !important;
            font-size: 15px !important;
            padding: 8px 12px !important;
            text-align: center !important;
            width: 100% !important;
            font-weight: 700 !important;
        }

        .btn-tbl-delete {
            background-color: #ffffff !important;
            border: 1px solid #ff8a80 !important;
            color: #ff5252 !important;
            padding: 4px 10px !important;
            border-radius: 0 !important;
            cursor: pointer !important;
            font-size: 16px !important;
            font-weight: bold !important;
        }

        .btn-tbl-delete:hover {
            background-color: #ffebee !important;
        }

        /* Divider lines for grand total */
        .total-box {
            text-align: right !important;
            padding-right: 24px !important;
            font-size: 20px !important;
            font-weight: 700 !important;
            color: #7A1530 !important;
        }

        .total-bottom-divider-line {
            border-top: 3px solid #7A1530 !important;
            margin-top: 8px !important;
            margin-bottom: 24px !important;
            width: 100% !important;
        }

        /* Action Buttons */
        .btn-save {
            background-color: #7A1530 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 10px 32px !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
        }

        .btn-save:hover {
            background-color: #5a0f22 !important;
        }

        .btn-reset {
            background-color: #c02828 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 10px 32px !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
        }

        .btn-reset:hover {
            background-color: #a01c1c !important;
        }
    </style>
</head>
<body>
<div class="container-fluid">
<div class="row">

<?php include 'sidebar.php'; ?>

<div class="col-md-10 p-0">
<?php include 'navbar.php'; ?>
<!-- Override navbar margin so header is flush against it -->
<style>.custom-navbar { margin-bottom: 0 !important; }</style>

<div class="container-fluid p-0">
    
    <!-- Page Header -->
    <div class="pg-header-box">
        <h4 class="pg-header-title">ສ້າງໃບບິນສັ່ງຊື້ສິນຄ້າໃໝ່</h4>
        <a href="form_orders.php" class="pg-back-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            ກັບຄືນ
        </a>
    </div>

    <!-- Content wrapper -->
    <div class="pg-content-wrapper">

        <?php if(!empty($msg)): ?>
            <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show border-0" style="border-radius:0 !important; font-weight:bold;">
                <?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="orders_add.php">
            
            <!-- Master Info Card -->
            <div class="pg-card">
                <div class="pg-card-header">
                    <h6 class="pg-card-title">ຂໍ້ມູນຫຼັກຂອງໃບບິນສັ່ງຊື້ (Master)</h6>
                </div>
                <div class="pg-card-body" style="padding: 24px !important;">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="pg-label">ເລກທີໃບບິນ (Auto)</label>
                            <input type="text" name="order_no" class="pg-input-master" value="<?= $auto_order_no ?>" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="pg-label">ເລືອກຜູ້ສະໜອງ (Supplier)</label>
                            <select name="sup_id" class="pg-input-master-select" required>
                                <option value="">-- ເລືອກຜູ້ສະໜອງ --</option>
                                <?php foreach($suppliers as $s): ?>
                                    <option value="<?= $s['sup_id'] ?>"><?= htmlspecialchars($s['sup_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="pg-label">ວັນທີສັ່ງຊື້</label>
                            <input type="date" name="order_date" class="pg-input-master" value="<?= date('Y-m-d') ?>" required style="color: #7A1530 !important;">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="pg-label">ວັນທີກຳນົດສົ່ງ (Expected Date)</label>
                            <input type="date" name="expected_date" class="pg-input-master" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required style="color: #7A1530 !important;">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="pg-label">ໝາຍເຫດ (Remark)</label>
                            <input type="text" name="remark" class="pg-input-master-select" placeholder="ເພີ່ມໝາຍເຫດ (ຖ້າມີ)..." style="font-weight: normal !important;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details Table Card -->
            <div class="pg-card">
                <div class="pg-card-header">
                    <h6 class="pg-card-title">ລາຍການສິນຄ້າທີ່ຕ້ອງການສັ່ງຊື້ (Details)</h6>
                    <button type="button" class="btn-add-item" onclick="addRow()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="margin-bottom: 2px;"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                        ເພີ່ມລາຍການສິນຄ້າ
                    </button>
                </div>
                <div class="pg-card-body p-0">
                    <div class="table-responsive-custom">
                        <table class="pg-table w-100 mb-0" id="detailTable">
                            <thead>
                                <tr>
                                    <th style="width:70px;">ລ/ດ</th>
                                    <th>ເລືອກສິນຄ້າ</th>
                                    <th style="width:160px;">ຈຳນວນສັ່ງຊື້</th>
                                    <th style="width:160px;">ຫົວໜ່ວຍ</th>
                                    <th style="width:200px;">ລາຄາຕໍ່ໜ່ວຍ (LAK)</th>
                                    <th style="width:200px;">มູນຄ່າລວມ (LAK)</th>
                                    <th style="width:90px;">ລົບ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr id="row_1">
                                    <td style="text-align:center; font-weight:bold;" class="row-num">1</td>
                                    <td>
                                        <select name="pro_id[]" class="tbl-select select-pro" required onchange="productChanged(this, 1)">
                                            <option value="">-- ເລືອກສິນຄ້າ --</option>
                                            <?php foreach($products as $p): ?>
                                                <option value="<?= $p['pro_id'] ?>" data-price="<?= $p['price'] ?>" data-unitid="<?= $p['unit_id'] ?>" data-unitname="<?= htmlspecialchars($p['unit_name']) ?>">
                                                    <?= htmlspecialchars($p['pro_id'] . " - " . $p['pro_name'] . " (" . $p['pro_brand'] . ")") ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="qty[]" class="tbl-input-qty qty-input" value="1" min="1" required oninput="calculateRow(1)">
                                    </td>
                                    <td>
                                        <input type="text" class="tbl-input-stock unit-name-text" value="-" readonly>
                                        <input type="hidden" name="unit_id[]" class="unit-id-value">
                                    </td>
                                    <td>
                                        <input type="number" name="price[]" class="tbl-input-price price-input" value="0" step="0.01" required oninput="calculateRow(1)">
                                    </td>
                                    <td>
                                        <input type="text" class="tbl-input-total total-text" value="0.00" readonly>
                                    </td>
                                    <td style="text-align:center;">
                                        <button type="button" class="btn-tbl-delete" onclick="removeRow(1)">✕</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Grand Total Section -->
                    <div class="total-box" style="margin-top: 24px;">
                        ລວມທັງໝົດ: <span id="grandTotalText">0.00 ₭</span>
                    </div>
                    <div class="total-bottom-divider-line" style="margin-bottom: 24px;"></div>
                    <!-- Action Submit/Reset Buttons -->
                    <div style="display:flex; justify-content:center; gap:20px; padding-bottom: 24px;">
                        <button type="submit" name="btn_save_order" class="btn-save">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                            ບັນທຶກ
                        </button>
                        <button type="reset" class="btn-reset" onclick="resetForm()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                            ລ້າງຟອມ
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div><!-- end pg-content-wrapper -->
</div><!-- end container-fluid -->

</div>
</div>
</div>

<script>
let rowCount = 1;

// ຟັງຊັນເພີ່ມແຖວໃໝ່
function addRow() {
    rowCount++;
    const tableBody = document.querySelector("#detailTable tbody");
    const firstRow = document.querySelector("#row_1");
    
    // Clone ແຖວທຳອິດມາສ້າງເປັນແຖວໃໝ່
    const newRow = firstRow.cloneNode(true);
    newRow.id = "row_" + rowCount;
    
    // ເຄຼຍຄ່າເກົ່າໃນແຖວໃໝ່ອອກ
    newRow.querySelector("select").value = "";
    newRow.querySelector(".qty-input").value = "1";
    newRow.querySelector(".unit-name-text").value = "-";
    newRow.querySelector(".unit-id-value").value = "";
    newRow.querySelector(".price-input").value = "0";
    newRow.querySelector(".total-text").value = "0.00";
    
    // ປ່ຽນ Arguments ໃນ Attributes ໃຫ້ຕົງກັບ ID ຂອງແຖວໃໝ່
    newRow.querySelector("select").setAttribute("onchange", `productChanged(this, ${rowCount})`);
    newRow.querySelector(".qty-input").setAttribute("oninput", `calculateRow(${rowCount})`);
    newRow.querySelector(".price-input").setAttribute("oninput", `calculateRow(${rowCount})`);
    newRow.querySelector("button").setAttribute("onclick", `removeRow(${rowCount})`);
    
    tableBody.appendChild(newRow);
    updateRowNumbers();
}

// ຟັງຊັນລົບແຖວ
function removeRow(id) {
    if(document.querySelectorAll("#detailTable tbody tr").length <= 1) {
        alert("⚠️ ຕ້ອງມີລາຍການສິນຄ້າຢ່າງໜ້ອຍ 1 ລາຍການໃນໃບບິນ!");
        return;
    }
    const row = document.getElementById("row_" + id);
    row.remove();
    updateRowNumbers();
    calculateGrandTotal();
}

// ອັບເດດເລກ ລ/ດ (1, 2, 3...) ໃຫ້ລຽງກັນງາມໆຫຼັງເພີ່ມ/ລົບແຖວ
function updateRowNumbers() {
    const rows = document.querySelectorAll("#detailTable tbody tr");
    rows.forEach((row, idx) => {
        row.querySelector(".row-num").textContent = idx + 1;
    });
}

// ເມື່ອມີການເລືອກສິນຄ້າ ໃຫ້ດຶງລາຄາ ແລະ ຫົວໜ່ວຍມາໃສ່ອັດຕະໂນມັດ
function productChanged(selectElement, id) {
    const row = document.getElementById("row_" + id);
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    
    if(selectedOption.value !== "") {
        const price = selectedOption.getAttribute("data-price");
        const unitId = selectedOption.getAttribute("data-unitid");
        const unitName = selectedOption.getAttribute("data-unitname");
        
        row.querySelector(".price-input").value = price;
        row.querySelector(".unit-name-text").value = unitName;
        row.querySelector(".unit-id-value").value = unitId;
    } else {
        row.querySelector(".price-input").value = "0";
        row.querySelector(".unit-name-text").value = "-";
        row.querySelector(".unit-id-value").value = "";
    }
    calculateRow(id);
}

// ຄຳນວນມູນຄ່າລວມຂອງແຕ່ລະແຖວ (Qty * Price)
function calculateRow(id) {
    const row = document.getElementById("row_" + id);
    const qty = parseInt(row.querySelector(".qty-input").value) || 0;
    const price = parseFloat(row.querySelector(".price-input").value) || 0;
    
    const total = qty * price;
    row.querySelector(".total-text").value = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    
    calculateGrandTotal();
}

// ຄຳນວນຍອດລວມທັງໝົດຂອງໃບບິນ (Grand Total)
function calculateGrandTotal() {
    let grandTotal = 0;
    const rows = document.querySelectorAll("#detailTable tbody tr");
    
    rows.forEach(row => {
        const qty = parseInt(row.querySelector(".qty-input").value) || 0;
        const price = parseFloat(row.querySelector(".price-input").value) || 0;
        grandTotal += (qty * price);
    });
    
    document.getElementById("grandTotalText").textContent = grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " ₭";
}

function resetForm() {
    setTimeout(() => {
        const tbody = document.querySelector("#detailTable tbody");
        tbody.innerHTML = "";
        rowCount = 0;
        addRow();
        calculateGrandTotal();
    }, 10);
}
</script>
</body>
</html>