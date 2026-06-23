<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

require 'cmt_db.php'; // ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ

$msg = "";
$msg_type = "";

// 📌 1. ສ້າງລະຫັດນຳເຂົ້າອັດຕະໂນມັດ (ຕົວຢ່າງ: IMP00001) ໃຫ້ຕົງກັບ VARCHAR(10)
try {
    $stmt_id = $conn->query("SELECT import_id FROM import ORDER BY import_id DESC LIMIT 1");
    $last_id = $stmt_id->fetch(PDO::FETCH_ASSOC);
    if ($last_id) {
        $num = intval(substr($last_id['import_id'], 3));
        $next_id = "IMP" . str_pad($num + 1, 5, '0', STR_PAD_LEFT);
    } else {
        $next_id = "IMP00001";
    }
} catch (PDOException $e) {
    $next_id = "IMP00001";
}

// 📌 2. ຕອນກົດປຸ່ມບັນທຶກຮັບສິນຄ້າເຂົ້າສາງ
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_save_import'])) {
    $import_id = $_POST['import_id'];
    $import_date = $_POST['import_date'] . ' ' . date('H:i:s');
    $order_id = !empty($_POST['order_id']) ? intval($_POST['order_id']) : null;
    $sup_id = !empty($_POST['sup_id']) ? intval($_POST['sup_id']) : null;
    $emp_id = $_SESSION['userid'];

    // arrays ຂໍ້ມູນສິນຄ້າຈາກຟອມ
    $pro_ids = $_POST['pro_id'] ?? [];
    $import_qtys = $_POST['import_qty'] ?? [];
    $cost_prices = $_POST['cost_price'] ?? [];
    $lot_nos = $_POST['lot_no'] ?? [];
    $mfg_dates = $_POST['mfg_date'] ?? [];
    $exp_dates = $_POST['exp_date'] ?? [];

    if (count($pro_ids) > 0) {
        try {
            $conn->beginTransaction();

            // 💾 2.1 ບັນທຶກລົງຕາຕະລາງ import
            $sql_imp = "INSERT INTO import (import_id, import_date, order_id, sup_id, emp_id) 
                        VALUES (:import_id, :import_date, :order_id, :sup_id, :emp_id)";
            $stmt_i = $conn->prepare($sql_imp);
            $stmt_i->execute([
                'import_id' => $import_id,
                'import_date' => $import_date,
                'order_id' => $order_id,
                'sup_id' => $sup_id,
                'emp_id' => $emp_id
            ]);

            // 💾 2.2 ບັນທຶກລົງຕາຕະລາງ import_detail, ບວກສະຕັອກສິນຄ້າ
            $sql_det = "INSERT INTO import_detail (import_id, pro_id, import_qty, cost_price, lot_no, mfg_date, exp_date) 
                        VALUES (:import_id, :pro_id, :import_qty, :cost_price, :lot_no, :mfg_date, :exp_date)";
            $stmt_d = $conn->prepare($sql_det);

            // ໂຄດສັ່ງ Update ບວກຈຳນວນໃນຕາຕະລາງ product
            $sql_up_stock = "UPDATE product SET qty = qty + :add_qty WHERE pro_id = :pro_id";
            $stmt_u = $conn->prepare($sql_up_stock);

            foreach ($pro_ids as $idx => $pro_id) {
                if (!empty($pro_id)) {
                    $qty_in = intval($import_qtys[$idx]);
                    $mfg = !empty($mfg_dates[$idx]) ? $mfg_dates[$idx] : null;
                    $exp = !empty($exp_dates[$idx]) ? $exp_dates[$idx] : null;

                    // Insert ລາຍລະອຽດການນຳເຂົ້າ
                    $stmt_d->execute([
                        'import_id' => $import_id,
                        'pro_id' => $pro_id,
                        'import_qty' => $qty_in,
                        'cost_price' => floatval($cost_prices[$idx]),
                        'lot_no' => trim($lot_nos[$idx]),
                        'mfg_date' => $mfg,
                        'exp_date' => $exp
                    ]);

                    // 🔄 ບວກຈຳນວນເຄື່ອງເຂົ້າໃນສະຕັອກສິນຄ້າ (Product Table)
                    $stmt_u->execute([
                        'add_qty' => $qty_in,
                        'pro_id' => $pro_id
                    ]);
                }
            }

            // 🔄 2.3 ຖ້າມີການອ້າງອີງບິນ PO ໃຫ້ປ່ຽນສະຖານະໃບບິນ PO ນັ້ນເປັນ 'Completed' (ສຳເລັດ)
            if ($order_id) {
                $stmt_po = $conn->prepare("UPDATE orders SET status = 'Completed' WHERE order_id = :order_id");
                $stmt_po->execute(['order_id' => $order_id]);
            }

            $conn->commit();
            echo "<script>alert('🎉 ນຳເຂົ້າສິນຄ້າ ແລະ ອັບເດດສະຕັອກສຳເລັດແລ້ວ!'); location='form_import.php';</script>";
            exit;

        } catch (PDOException $e) {
            $conn->rollBack();
            $msg = "❌ Error: " . $e->getMessage();
            $msg_type = "danger";
        }
    }
}

// ດຶງຂໍ້ມູນໃບບິນ PO ທີ່ຍັງຄ້າງສົ່ງ (Pending)
$po_list = $conn->query("SELECT order_id, order_no FROM orders WHERE status = 'Pending' OR status = 'ລໍຖ້າກວດສອບ' ORDER BY order_id DESC")->fetchAll(PDO::FETCH_ASSOC);
// ດຶງຂໍ້ມູນຜູ້ສະໜອງ
$suppliers = $conn->query("SELECT * FROM supplier ORDER BY sup_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <link href="style/fonts.css" rel="stylesheet">
    <title>ຮັບສິນຄ້ານຳເຂົ້າສາງ</title>
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

        .pg-input-master:focus, .pg-input-master-select:focus {
            background-color: #ffffff !important;
            border-color: #C9956A !important;
            outline: none !important;
        }

        /* Customize native date picker calendar icon color */
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(19%) sepia(91%) saturate(5464%) hue-rotate(352deg) brightness(85%) contrast(100%);
            cursor: pointer;
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

        .tbl-input-white {
            border: 1px solid #cccccc !important;
            border-radius: 0 !important;
            background-color: #ffffff !important;
            color: #1a1a1a !important;
            font-size: 15px !important;
            padding: 8px 12px !important;
            text-align: center !important;
            width: 100% !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            font-weight: bold !important;
        }
        .tbl-input-white:focus {
            outline: none !important;
            border-color: #C9956A !important;
        }

        .total-bottom-divider-line {
            border-top: 3px solid #7A1530 !important;
            margin-top: 24px !important;
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
        <h4 class="pg-header-title">ບັນທຶກການຮັບສິນຄ້ານຳເຂົ້າສາງ</h4>
        <a href="form_import.php" class="pg-back-btn">
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

        <form method="POST" action="import_add.php">
            
            <!-- Master Info Card -->
            <div class="pg-card">
                <div class="pg-card-header">
                    <h6 class="pg-card-title">ຂໍ້ມູນຫົວບິນນຳເຂົ້າ (Master)</h6>
                </div>
                <div class="pg-card-body" style="padding: 24px !important;">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="pg-label">ລະຫັດນຳເຂົ້າ (Auto)</label>
                            <input type="text" name="import_id" class="pg-input-master" value="<?= $next_id ?>" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="pg-label">ອ້າງອີງໃບບິນສັ່ງຊື້ (PO) *</label>
                            <select name="order_id" id="order_select" class="pg-input-master-select" onchange="fetchOrderDetails(this.value)" required>
                                <option value="">-- ເລືອກໃບບິນ PO --</option>
                                <?php foreach($po_list as $po): ?>
                                    <option value="<?= $po['order_id'] ?>"><?= htmlspecialchars($po['order_no']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="pg-label">ຜູ້ສະໜອງ (Supplier) *</label>
                            <select name="sup_id" id="sup_select" class="pg-input-master-select" required>
                                <option value="">-- ຜູ້ສະໜອງ --</option>
                                <?php foreach($suppliers as $s): ?>
                                    <option value="<?= $s['sup_id'] ?>"><?= htmlspecialchars($s['sup_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="pg-label">ວັນທີຮັບເຂົ້າສາງ *</label>
                            <input type="date" name="import_date" class="pg-input-master" value="<?= date('Y-m-d') ?>" required style="color: #7A1530 !important;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details Table Card -->
            <div class="pg-card">
                <div class="pg-card-header">
                    <h6 class="pg-card-title">ລາຍການສິນຄ້າທີ່ຮັບເຂົ້າສາງ (Details)</h6>
                </div>
                <div class="pg-card-body p-0">
                    <div class="table-responsive-custom">
                        <table class="pg-table w-100 mb-0" id="importDetailTable">
                            <thead>
                                <tr>
                                    <th style="width:70px;">ລ/ດ</th>
                                    <th>ชື່ສິນຄ້າ</th>
                                    <th style="width:140px;">ຈຳນວນຮັບ</th>
                                    <th style="width:120px;">ຫົວໜ່ວຍ</th>
                                    <th style="width:180px;">ລາຄາທຶນ (LAK)</th>
                                    <th style="width:160px;">Lot No.</th>
                                    <th style="width:180px;">ວັນຜະລິດ (MFG)</th>
                                    <th style="width:180px;">ວັນໝົດອາຍຸ (EXP) *</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="8" class="text-center text-muted py-4">ກະລຸນາເລືອກເລກທີໃບບິນ PO ດ້ານເທິງ ເພື່ອດຶງລາຍການສິນຄ້າ</td></tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="total-bottom-divider-line"></div>

                    <!-- Action Submit/Reset Buttons -->
                    <div style="display:flex; justify-content:center; gap:20px; padding-bottom: 24px;">
                        <button type="submit" name="btn_save_import" class="btn-save">
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
function fetchOrderDetails(orderId) {
    const tbody = document.querySelector("#importDetailTable tbody");
    if (!orderId) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">ກະລຸນາເລືອກເລກທີໃບບິນ PO ດ້ານເທິງ ເພື່ອດຶງລາຍການສິນຄ້າ</td></tr>';
        document.getElementById("sup_select").value = "";
        return;
    }

    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><div class="spinner-border spinner-border-sm text-success"></div> ກຳລັງດຶງຂໍ້ມູນ...</td></tr>';

    fetch(`get_order_details.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // ຢອດຄ່າ Supplier ໃຫ້ອັດຕະໂນມັດ
                document.getElementById("sup_select").value = data.sup_id;

                // ຢອດລາຍການສິນຄ້າລົງຕາຕະລາງ
                let html = "";
                if(data.items.length > 0) {
                    data.items.forEach((item, index) => {
                        html += `
                        <tr>
                            <td class="text-center fw-bold">${index + 1}</td>
                            <td>
                                <strong>${item.pro_name}</strong> <br><small class="text-muted">ID: ${item.pro_id}</small>
                                <input type="hidden" name="pro_id[]" value="${item.pro_id}">
                            </td>
                            <td>
                                <input type="number" name="import_qty[]" class="tbl-input-qty" value="${item.qty}" min="1" required>
                            </td>
                            <td>
                                <input type="text" class="tbl-input-stock" value="${item.unit_name}" readonly>
                            </td>
                            <td>
                                <input type="number" name="cost_price[]" class="tbl-input-price" value="${item.price}" step="0.01" required>
                            </td>
                            <td>
                                <input type="text" name="lot_no[]" class="tbl-input-white" placeholder="LOT-${new Date().getFullYear()}">
                            </td>
                            <td>
                                <input type="date" name="mfg_date[]" class="tbl-input-white">
                            </td>
                            <td>
                                <input type="date" name="exp_date[]" class="tbl-input-white" required>
                            </td>
                        </tr>`;
                    });
                } else {
                    html = '<tr><td colspan="8" class="text-center text-danger py-4">⚠️ ບໍ່ພົບລາຍການສິນຄ້າໃນໃບບິນສັ່ງຊື້ນີ້</td></tr>';
                }
                tbody.innerHTML = html;
            } else {
                alert("❌ ເກີດຂໍ້ຜິດພาด: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("❌ ບໍ່ສາມາດເຊື່ອມຕໍ່ກັບ API ດຶງຂໍ້ມູນໄດ້");
        });
}

function resetForm() {
    setTimeout(() => {
        const tbody = document.querySelector("#importDetailTable tbody");
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">ກະລຸນາເລືອກເລກທີໃບບິນ PO ດ້ານເທิง ເພື່ອດຶງລາຍการສິນຄ້າ</td></tr>';
        document.getElementById("sup_select").value = "";
    }, 10);
}
</script>
</body>
</html>