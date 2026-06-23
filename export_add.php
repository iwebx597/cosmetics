<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

require 'cmt_db.php'; // ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ

$msg = "";
$msg_type = "";

// 📌 1. ສ້າງລະຫັດໃບບິນຈ່າຍອອກອັດຕະໂນມັດ (ຕົວຢ່າງ: EXP00001) ໃຫ້ຕົງກັບ VARCHAR(10)
try {
    $stmt_id = $conn->query("SELECT export_id FROM export ORDER BY export_id DESC LIMIT 1");
    $last_id = $stmt_id->fetch(PDO::FETCH_ASSOC);
    if ($last_id) {
        $num = intval(substr($last_id['export_id'], 3));
        $next_id = "EXP" . str_pad($num + 1, 5, '0', STR_PAD_LEFT);
    } else {
        $next_id = "EXP00001";
    }
} catch (PDOException $e) {
    $next_id = "EXP00001";
}

// 📌 2. ຕອນກົດປຸ່ມບັນທຶກການຈ່າຍອອກ
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_save_export'])) {
    $export_id = $_POST['export_id'];
    $export_date = $_POST['export_date'] . ' ' . date('H:i:s');
    $emp_id = $_SESSION['userid'];

    $pro_ids = $_POST['pro_id'] ?? [];
    $export_qtys = $_POST['export_qty'] ?? [];
    $prices = $_POST['price'] ?? [];

    if (count($pro_ids) > 0) {
        try {
            $conn->beginTransaction();

            // 💾 2.1 ບັນທຶກລົງຕາຕະລາງ export
            $sql_exp = "INSERT INTO export (export_id, export_date, emp_id) VALUES (:export_id, :export_date, :emp_id)";
            $stmt_e = $conn->prepare($sql_exp);
            $stmt_e->execute([
                'export_id' => $export_id,
                'export_date' => $export_date,
                'emp_id' => $emp_id
            ]);

            // 💾 2.2 ບັນທຶກລົງຕາຕະລາງ export_detail ແລະ ຕັດສະຕັອກສິນຄ້າ
            $sql_det = "INSERT INTO export_detail (export_id, pro_id, export_qty, price) VALUES (:export_id, :pro_id, :export_qty, :price)";
            $stmt_d = $conn->prepare($sql_det);

            $sql_cut_stock = "UPDATE product SET qty = qty - :cut_qty WHERE pro_id = :pro_id";
            $stmt_c = $conn->prepare($sql_cut_stock);

            foreach ($pro_ids as $idx => $pro_id) {
                if (!empty($pro_id)) {
                    $qty_out = intval($export_qtys[$idx]);
                    $price_val = floatval($prices[$idx]);

                    // ກວດສອບສະຕັອກອີກຄັ້ງໃນຝັ່ງ Server ເພື່ອຄວາມປອດໄພ
                    $stmt_chk = $conn->prepare("SELECT qty FROM product WHERE pro_id = :pro_id");
                    $stmt_chk->execute(['pro_id' => $pro_id]);
                    $p_current = $stmt_chk->fetch(PDO::FETCH_ASSOC);

                    if (!$p_current || $p_current['qty'] < $qty_out) {
                        throw new Exception("⚠️ ສິນຄ້າລະຫັດ $pro_id ມີຈຳນວນບໍ່ພໍໃນສາງ (ເຫຼືອພຽງ " . ($p_current['qty'] ?? 0) . ")");
                    }

                    // Insert ລາຍລະອຽດການຈ່າຍອອກ
                    $stmt_d->execute([
                        'export_id' => $export_id,
                        'pro_id' => $pro_id,
                        'export_qty' => $qty_out,
                        'price' => $price_val
                    ]);

                    // 🔄 ລົບຈຳນວນເຄື່ອງອອກຈາກສະຕັອກ (Product Table)
                    $stmt_c->execute([
                        'cut_qty' => $qty_out,
                        'pro_id' => $pro_id
                    ]);
                }
            }

            $conn->commit();
            echo "<script>alert('🎉 ບັນທຶກການຈ່າຍອອກສິນຄ້າ ແລະ ຕັດສະຕັອກຮຽບຮ້ອຍແລ້ວ!'); location='form_export.php';</script>";
            exit;

        } catch (Exception $e) {
            $conn->rollBack();
            $msg = $e->getMessage();
            $msg_type = "danger";
        }
    } else {
        $msg = "❌ ກະລຸນາເພີ່ມລາຍການສິນຄ້າຢ່າງໜ້ອຍ 1 ລາຍການ";
        $msg_type = "warning";
    }
}

// ດຶງລາຍການສິນຄ້າທັງໝົດເພື່ອໄປເລືອກໃນ Dropdown
$products = $conn->query("SELECT pro_id, pro_name, pro_brand, qty FROM product ORDER BY pro_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <link href="style/fonts.css" rel="stylesheet">
    <title>ສ້າງໃບບິນຈ່າຍອອກສິນຄ້າ</title>
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
        .total-divider-line {
            border-top: 3px solid #7A1530 !important;
            margin-top: 24px !important;
            margin-bottom: 8px !important;
            width: 100% !important;
        }

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
        <h4 class="pg-header-title">ສ້າງໃບບິນຈ່າຍອອກສິນຄ້າ/ຕັດສະຕັອກ</h4>
        <a href="form_export.php" class="pg-back-btn">
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

        <form method="POST" action="export_add.php" onsubmit="return validateForm()">
            
            <!-- Master Info Card -->
            <div class="pg-card">
                <div class="pg-card-header">
                    <h6 class="pg-card-title">ຂໍ້ມູນຫົວໃບບິນຈ່າຍອອກ (Master)</h6>
                </div>
                <div class="pg-card-body" style="padding: 24px !important;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="pg-label">ລະຫັດໃບບິນຈ່າຍອອກ (Auto)</label>
                            <input type="text" name="export_id" class="pg-input-master" value="<?= $next_id ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="pg-label">ວັນທີຈ່າຍອອກສິນຄ້າ</label>
                            <input type="date" name="export_date" class="pg-input-master" value="<?= date('Y-m-d') ?>" required style="color: #7A1530 !important;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details Table Card -->
            <div class="pg-card">
                <div class="pg-card-header">
                    <h6 class="pg-card-title">ລາຍການສິນຄ້າທີ່ຕ້ອງການຈ່າຍອອກ (Details)</h6>
                    <button type="button" class="btn-add-item" onclick="addRow()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="margin-bottom: 2px;"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                        ເພີ່ມລາຍການ
                    </button>
                </div>
                <div class="pg-card-body p-0">
                    <div class="table-responsive-custom">
                        <table class="pg-table w-100 mb-0" id="exportDetailTable">
                            <thead>
                                <tr>
                                    <th style="width:70px;">ລ/ດ</th>
                                    <th>ເລືອກສິນຄ້າ</th>
                                    <th style="width:160px;">ຈຳນວນຄັງເຫຼືອ</th>
                                    <th style="width:160px;">ຈຳນວນຈ່າຍອອກ</th>
                                    <th style="width:200px;">ລາຄາຂາຍ/ໜ່ວຍ (LAK)</th>
                                    <th style="width:200px;">ມູນຄ່າລວມ</th>
                                    <th style="width:90px;">ຈັດການ</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <!-- Grand Total Section -->
                    <div class="total-box" style="margin-top: 24px;">
                        ລວມທັງໝົດ: <span id="grandTotalLabel">0.00</span> ₭
                    </div>
                    <div class="total-bottom-divider-line" style="margin-bottom: 24px;"></div>
                    <!-- Action Submit/Reset Buttons -->
                    <div style="display:flex; justify-content:center; gap:20px; padding-bottom: 24px;">
                        <button type="submit" name="btn_save_export" class="btn-save">
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
let rowCount = 0;
const productList = <?= json_encode($products) ?>;

// ຟັງຊັນເພີ່ມແຖວສິນຄ້າໃໝ່
function addRow() {
    rowCount++;
    const tbody = document.querySelector("#exportDetailTable tbody");
    const tr = document.createElement("tr");
    tr.id = `row_${rowCount}`;

    let options = '<option value="">-- ເລືອກສິນຄ້າ --</option>';
    productList.forEach(p => {
        options += `<option value="${p.pro_id}">[${p.pro_id}] ${p.pro_name} (${p.pro_brand})</option>`;
    });

    tr.innerHTML = `
        <td style="text-align:center; font-weight:bold;" class="row-index">${tbody.children.length + 1}</td>
        <td>
            <select name="pro_id[]" class="tbl-select select-pro" onchange="loadProductInfo(this, ${rowCount})" required>
                ${options}
            </select>
        </td>
        <td>
            <input type="text" id="stock_${rowCount}" class="tbl-input-stock" readonly value="0">
        </td>
        <td>
            <input type="number" name="export_qty[]" id="qty_${rowCount}" class="tbl-input-qty" value="1" min="1" oninput="calculateRow(${rowCount})" required disabled>
        </td>
        <td>
            <input type="number" name="price[]" id="price_${rowCount}" class="tbl-input-price" value="0.00" step="0.01" oninput="calculateRow(${rowCount})" required disabled>
        </td>
        <td>
            <input type="text" id="total_${rowCount}" class="tbl-input-total" value="0.00" readonly>
        </td>
        <td style="text-align:center;">
            <button type="button" class="btn-tbl-delete" onclick="removeRow(${rowCount})">✕</button>
        </td>
    `;
    tbody.appendChild(tr);
}

// ດຶງຂໍ້ມູນລາຄາ ແລະ ສະຕັອກ ຜ່ານ API
function loadProductInfo(selectObj, rowId) {
    const proId = selectObj.value;
    const stockInput = document.getElementById(`stock_${rowId}`);
    const qtyInput = document.getElementById(`qty_${rowId}`);
    const priceInput = document.getElementById(`price_${rowId}`);

    if (!proId) {
        stockInput.value = 0;
        qtyInput.disabled = true;
        priceInput.disabled = true;
        calculateRow(rowId);
        return;
    }

    fetch(`get_product_info.php?pro_id=${proId}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                stockInput.value = data.qty;
                priceInput.value = data.price;
                qtyInput.disabled = false;
                priceInput.disabled = false;
                
                // ຕັ້ງຄ່າຈຳນວນສູງສຸດບໍ່ໃຫ້ເກີນສະຕັອກ
                qtyInput.max = data.qty;
                
                calculateRow(rowId);
            }
        });
}

// ຄຳນວນຍອດລວມຂອງແຖວ ແລະ ບິນ
function calculateRow(rowId) {
    const qty = parseInt(document.getElementById(`qty_${rowId}`).value) || 0;
    const price = parseFloat(document.getElementById(`price_${rowId}`).value) || 0;
    const stock = parseInt(document.getElementById(`stock_${rowId}`).value) || 0;
    const qtyInput = document.getElementById(`qty_${rowId}`);

    // ແຈ້ງເຕືອນ Realtime ຖ້າພິມເກີນສະຕັອກ
    if (qty > stock) {
        qtyInput.classList.add("is-invalid");
        qtyInput.style.borderColor = "#ff5252";
        qtyInput.style.backgroundColor = "#ffebee";
    } else {
        qtyInput.classList.remove("is-invalid");
        qtyInput.style.borderColor = "#cccccc";
        qtyInput.style.backgroundColor = "#ffffff";
    }

    const total = qty * price;
    document.getElementById(`total_${rowId}`).value = total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    calculateGrandTotal();
}

// ຄຳນວນຍອດລວມທັງໝົດ
function calculateGrandTotal() {
    let grandTotal = 0;
    const tbody = document.querySelector("#exportDetailTable tbody");
    const rows = tbody.querySelectorAll("tr");

    rows.forEach(row => {
        const id = row.id.split("_")[1];
        const qty = parseInt(document.getElementById(`qty_${id}`).value) || 0;
        const price = parseFloat(document.getElementById(`price_${id}`).value) || 0;
        grandTotal += (qty * price);
    });

    document.getElementById("grandTotalLabel").textContent = grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function removeRow(rowId) {
    const row = document.getElementById(`row_${rowId}`);
    row.remove();
    calculateGrandTotal();
    
    // ຈັດລຽງເລກ ລ/ດ ໃໝ່
    const rows = document.querySelectorAll("#exportDetailTable tbody tr");
    rows.forEach((r, idx) => {
        r.querySelector(".row-index").textContent = idx + 1;
    });
}

// ກວດສອບກ່ອນສົ່ງຟອມ ບໍ່ໃຫ້ສະຕັອກຕິດລົບ
function validateForm() {
    const tbody = document.querySelector("#exportDetailTable tbody");
    if(tbody.children.length === 0) {
        alert("⚠️ ກະລຸນາເພີ່ມລາຍການສິນຄ້າຢ່າງໜ້ອຍ 1 ລາຍການກ່ອນ!");
        return false;
    }

    let isValid = true;
    const rows = tbody.querySelectorAll("tr");
    rows.forEach(row => {
        const id = row.id.split("_")[1];
        const qty = parseInt(document.getElementById(`qty_${id}`).value) || 0;
        const stock = parseInt(document.getElementById(`stock_${id}`).value) || 0;

        if (qty > stock) {
            alert(`❌ ບໍ່ສາມາດບັນທຶກໄດ້: ມີບາງລາຍການຈ่ายອອກ ເກີນຈຳນວນທີ່ມີໃນສາງ!`);
            isValid = false;
        }
    });

    return isValid;
}

function resetForm() {
    setTimeout(() => {
        const tbody = document.querySelector("#exportDetailTable tbody");
        tbody.innerHTML = "";
        addRow();
        calculateGrandTotal();
    }, 10);
}

// ເປີດໜ້າມາມີໃຫ້ 1 ແຖວເລີຍ
window.onload = addRow;
</script>
</body>
</html>