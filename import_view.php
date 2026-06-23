<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

require 'cmt_db.php'; // ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ

if (!isset($_GET['id'])) {
    header("Location: form_import.php");
    exit;
}

$import_id = trim($_GET['id']);

try {
    // 📌 1. ດຶງຂໍ້ມູນຫຼັກຂອງໃບບິນນຳເຂົ້າ (Master) ຕາມໂຄງສ້າງຂອງເຈົ້າ
    $sql_master = "SELECT i.*, o.order_no, s.sup_name, s.phone as sup_phone, s.address as sup_address, e.emp_fname, e.emp_lname 
                   FROM import i
                   LEFT JOIN orders o ON i.order_id = o.order_id
                   LEFT JOIN supplier s ON i.sup_id = s.sup_id
                   LEFT JOIN employee e ON i.emp_id = e.emp_id
                   WHERE i.import_id = :import_id LIMIT 1";
    $stmt_m = $conn->prepare($sql_master);
    $stmt_m->execute(['import_id' => $import_id]);
    $import = $stmt_m->fetch(PDO::FETCH_ASSOC);

    if (!$import) {
        echo "<script>alert('⚠️ ບໍ່ພົບຂໍ້ມູນໃບບິນນຳເຂົ້າສາງນີ້!'); location='form_import.php';</script>";
        exit;
    }

    // 📌 2. ດຶງລາຍການສິນຄ້າທາງໃນ (Details) ພ້ອມ Lot, MFG, EXP ທີ່ເຈົ້າເກັບໄວ້
    $sql_detail = "SELECT d.*, p.pro_name, p.pro_brand, u.unit_name 
                   FROM import_detail d
                   LEFT JOIN product p ON d.pro_id = p.pro_id
                   LEFT JOIN unit u ON p.unit_id = u.unit_id
                   WHERE d.import_id = :import_id
                   ORDER BY d.import_de_id ASC";
    $stmt_d = $conn->prepare($sql_detail);
    $stmt_d->execute(['import_id' => $import_id]);
    $details = $stmt_d->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Query Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<link href="style/fonts.css" rel="stylesheet">
<meta charset="UTF-8">
<title>ລາຍລະອຽດໃບບິນນຳເຂົ້າສາງ</title>
<link href="bootstrap-5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="bootstrap-5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<style>
body{font-family: 'noto-sans-lao-regular', sans-serif;}
.sidebar{min-height:100vh; background:#212529;}
.sidebar a{color:#adb5bd;text-decoration:none;display:block;padding:12px 20px;}
.sidebar a:hover{background:#343a40; color: #fff;}
.sidebar .active-menu{background:#495057; color: #fff !important;}
@media print {
    .sidebar, .navbar, .btn, hr { display: none !important; }
    .col-md-10 { width: 100% !important; background: #fff !important; }
    .card { border: none !important; box-shadow: none !important; }
}
</style>
</head>
<body>
<div class="container-fluid">
<div class="row">

<?php include 'sidebar.php'; ?>

<div class="col-md-10 bg-light">
<?php include 'navbar.php'; ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold text-dark">👁️ ລາຍລະອຽດໃບບິນຮັບສິນຄ້ານຳເຂົ້າສາງ</h4>
        <div>
            <button onclick="window.print()" class="btn btn-success me-2">🖨️ ພິມເອກະສານ (Print)</button>
            <a href="form_import.php" class="btn btn-secondary">🔙 ກັບຄືນ</a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-body p-5">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="fw-bold text-success">📥 ລະບົບຄຸ້ມຄອງສາງເຄື່ອງສໍາອາງ</h5>
                    <p class="text-muted small">ແຂວງວຽງຈັນ, ປະເທດລາວ<br>ເບີໂທ: 020 XXXXXXXX</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h3 class="fw-bold text-dark mb-1">ໃບບິນຮັບສິນຄ້ານຳເຂົ້າສາງ</h3>
                    <h5 class="text-success fw-bold mb-2"><?= htmlspecialchars($import['import_id']) ?></h5>
                    <p class="small text-muted mb-1">ວັນທີ-ເວລາຮັບເຂົ້າ: <strong><?= date('d/m/Y H:i:s', strtotime($import['import_date'])) ?></strong></p>
                    <p class="small text-muted">ອ້າງອີງໃບບິນ PO: <strong class="text-primary"><?= htmlspecialchars($import['order_no'] ?? 'ຮັບເຂົ້າໂດຍຕົງ (ບໍ່ມີ PO)') ?></strong></p>
                </div>
            </div>

            <hr class="text-secondary">

            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="fw-bold text-secondary mb-2">🏢 ຮັບຈາກຜູ້ສະໜອງ (Supplier):</h6>
                    <p class="mb-1"><strong><?= htmlspecialchars($import['sup_name'] ?? 'ບໍ່ລະບຸ') ?></strong></p>
                    <p class="mb-1 small text-muted">ທີ່ຢູ່: <?= htmlspecialchars($import['sup_address'] ?? '-') ?></p>
                    <p class="small text-muted">\ເບີໂທ: <?= htmlspecialchars($import['sup_phone'] ?? '-') ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6 class="fw-bold text-secondary mb-2">👥 ພະນັກງານຜູ້ກວດຮັບເຄື່ອງ (Staff):</h6>
                    <p class="mb-1"><strong><?= htmlspecialchars($import['emp_fname'] . " " . $import['emp_lname']) ?></strong></p>
                    <p class="small text-muted mb-0">ຕຳແໜ່ງ:Warehouse Officer</p>
                </div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle">
                    <thead class="table-light text-center small fw-bold">
                        <tr>
                            <th width="50">ລ/ດ</th>
                            <th width="100">ລະຫັດສິນຄ້າ</th>
                            <th>ຊື່ສິນຄ້າ ແລະ ແບຣນ</th>
                            <th width="120">Lot No.</th>
                            <th width="120">ວັນຜະລິດ (MFG)</th>
                            <th width="120">ວັນໝົດອາຍຸ (EXP)</th>
                            <th width="100">ຈຳນວນຮັບ</th>
                            <th width="100">ຫົວໜ່ວຍ</th>
                            <th width="150">ລາຄາທຶນ</th>
                            <th width="160">ມູນຄ່າລວມ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grand_total = 0;
                        foreach($details as $i => $d): 
                            // ຄຳນວນມູນຄ່າລວມແຕ່ລະແຖວ (import_qty * cost_price)
                            $row_total = $d['import_qty'] * $d['cost_price'];
                            $grand_total += $row_total;
                        ?>
                        <tr>
                            <td class="text-center"><?= $i + 1 ?></td>
                            <td class="text-center fw-bold text-secondary"><?= htmlspecialchars($d['pro_id']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($d['pro_name']) ?></strong>
                                <br><small class="text-muted">Brand: <?= htmlspecialchars($d['pro_brand']) ?></small>
                            </td>
                            <td class="text-center fw-bold text-dark"><?= htmlspecialchars($d['lot_no'] ?: '-') ?></td>
                            <td class="text-center small">
                                <?= $d['mfg_date'] ? date('d/m/Y', strtotime($d['mfg_date'])) : '-' ?>
                            </td>
                            <td class="text-center small fw-bold <?= (strtotime($d['exp_date']) - time() < 7776000) ? 'text-danger' : 'text-success' ?>">
                                <?= $d['exp_date'] ? date('d/m/Y', strtotime($d['exp_date'])) : '-' ?>
                            </td>
                            <td class="text-center fw-bold text-success"><?= number_format($d['import_qty']) ?></td>
                            <td class="text-center text-muted small"><?= htmlspecialchars($d['unit_name'] ?: '-') ?></td>
                            <td class="text-end"><?= number_format($d['cost_price'], 2) ?> ₭</td>
                            <td class="text-end fw-bold"><?= number_format($row_total, 2) ?> ₭</td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-light fw-bold">
                            <td colspan="9" class="text-end fs-6 py-3">💰 ມູນຄ່າລວມທັງໝົດທີ່ຮັບເຂົ້າສາງ:</td>
                            <td class="text-end text-danger fs-5 py-3"><?= number_format($grand_total, 2) ?> ₭</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="row mt-5 pt-4 text-center">
                <div class="col-md-4">
                    <p class="small text-muted mb-5">ພະນັກງານຜູ້ກວດຮັບເຄື່ອງ</p>
                    <p class="mb-0">........................................</p>
                    <p class="small text-muted mt-1">(<?= htmlspecialchars($import['emp_fname'] . " " . $import['emp_lname']) ?>)</p>
                </div>
                <div class="col-md-4">
                    </div>
                <div class="col-md-4">
                    <p class="small text-muted mb-5">ຫົວໜ້າຄັງສາງ / ຜູ້ອະນຸມັດ</p>
                    <p class="mb-0">........................................</p>
                </div>
            </div>

        </div>
    </div>
</div>

</div>
</div>
</div>
</body>
</html>