<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

require 'cmt_db.php'; // ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ

if (!isset($_GET['id'])) {
    header("Location: form_export.php");
    exit;
}

$export_id = trim($_GET['id']);

try {
    // 📌 1. ດຶງຂໍ້ມູນຫຼັກຂອງໃບບິນຈ່າຍອອກ (Master)
    $sql_master = "SELECT e.*, emp.emp_fname, emp.emp_lname 
                   FROM export e
                   LEFT JOIN employee emp ON e.emp_id = emp.emp_id
                   WHERE e.export_id = :export_id LIMIT 1";
    $stmt_m = $conn->prepare($sql_master);
    $stmt_m->execute(['export_id' => $export_id]);
    $export = $stmt_m->fetch(PDO::FETCH_ASSOC);

    if (!$export) {
        echo "<script>alert('⚠️ ບໍ່ພົບຂໍ້ມູນໃບບິນຈ່າຍອອກນີ້!'); location='form_export.php';</script>";
        exit;
    }

    // 📌 2. ດຶງລາຍການສິນຄ້າທາງໃນ (Details) ຕາມໂຄງສ້າງ export_detail ຂອງເຈົ້າ
    $sql_detail = "SELECT d.*, p.pro_name, p.pro_brand, u.unit_name 
                   FROM export_detail d
                   LEFT JOIN product p ON d.pro_id = p.pro_id
                   LEFT JOIN unit u ON p.unit_id = u.unit_id
                   WHERE d.export_id = :export_id
                   ORDER BY d.export_de_id ASC";
    $stmt_d = $conn->prepare($sql_detail);
    $stmt_d->execute(['export_id' => $export_id]);
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
<title>ລາຍລະອຽດໃບບິນຈ່າຍອອກສິນຄ້າ</title>
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
        <h4 class="fw-bold text-dark">👁️ ລາຍລະອຽດໃບບິນຈ່າຍອອກສິນຄ້າ</h4>
        <div>
            <button onclick="window.print()" class="btn btn-success me-2">🖨️ ພິມເອກະສານ (Print)</button>
            <a href="form_export.php" class="btn btn-secondary">🔙 ກັບຄືນ</a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-body p-5">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="fw-bold text-danger">📤 ລະບົບຄຸ້ມຄອງສາງເຄື່ອງສໍາອາງ</h5>
                    <p class="text-muted small">ແຂວງວຽງຈັນ, ປະເທດລາວ<br>ເບີໂທ: 020 XXXXXXXX</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h3 class="fw-bold text-dark mb-1">ໃບບິນຈ່າຍອອກສິນຄ້າ</h3>
                    <h5 class="text-danger fw-bold mb-2"><?= htmlspecialchars($export['export_id']) ?></h5>
                    <p class="small text-muted mb-1">ວັນທີ-ເວລາຈ່າຍອອກ: <strong><?= date('d/m/Y H:i:s', strtotime($export['export_date'])) ?></strong></p>
                </div>
            </div>

            <hr class="text-secondary">

            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="fw-bold text-secondary mb-2">👥 ພະນັກງານຜູ້ເຮັດລາຍການຈ່າຍ (Issuer):</h6>
                    <p class="mb-1"><strong><?= htmlspecialchars($export['emp_fname'] . " " . $export['emp_lname']) ?></strong></p>
                    <p class="small text-muted mb-0">ຕຳແໜ່ງ: Stock Controller</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6 class="fw-bold text-secondary mb-2">📋 ປະເພດລາຍການ:</h6>
                    <p class="mb-1 text-danger fw-bold">ຈ່າຍອອກສິນຄ້າ / ຕັດສະຕັອກສາງ</p>
                    <p class="small text-muted">ສະຖານະ: ບັນທຶກສຳເລັດ (Stock Updated)</p>
                </div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle">
                    <thead class="table-light text-center small fw-bold">
                        <tr>
                            <th width="60">ລ/ດ</th>
                            <th width="120">ລະຫັດສິນຄ້າ</th>
                            <th>ชື່ສິນຄ້າ ແລະ ແບຣນ</th>
                            <th width="120">ຈຳນວນຈ່າຍ</th>
                            <th width="120">ຫົວໜ່ວຍ</th>
                            <th width="180">ລາຄາຕໍ່ໜ່ວຍ</th>
                            <th width="200">ມູນຄ່າລວມ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grand_total = 0;
                        foreach($details as $i => $d): 
                            // ຄຳນວນມູນຄ່າລວມ (export_qty * price)
                            $row_total = $d['export_qty'] * $d['price'];
                            $grand_total += $row_total;
                        ?>
                        <tr>
                            <td class="text-center"><?= $i + 1 ?></td>
                            <td class="text-center fw-bold text-secondary"><?= htmlspecialchars($d['pro_id']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($d['pro_name']) ?></strong>
                                <br><small class="text-muted">Brand: <?= htmlspecialchars($d['pro_brand']) ?></small>
                            </td>
                            <td class="text-center fw-bold text-danger"><?= number_format($d['export_qty']) ?></td>
                            <td class="text-center text-muted small"><?= htmlspecialchars($d['unit_name'] ?: '-') ?></td>
                            <td class="text-end"><?= number_format($d['price'], 2) ?> ₭</td>
                            <td class="text-end fw-bold text-primary"><?= number_format($row_total, 2) ?> ₭</td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-light fw-bold">
                            <td colspan="6" class="text-end fs-6 py-3">💰 ມູນຄ່າລວມທັງໝົດທີ່ຈ່າຍອອກ:</td>
                            <td class="text-end text-danger fs-5 py-3"><?= number_format($grand_total, 2) ?> ₭</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="row mt-5 pt-4 text-center">
                <div class="col-md-4">
                    <p class="small text-muted mb-5">ພະນັກງານຜູ້ຈ່າຍສິນຄ້າ</p>
                    <p class="mb-0">........................................</p>
                    <p class="small text-muted mt-1">(<?= htmlspecialchars($export['emp_fname'] . " " . $export['emp_lname']) ?>)</p>
                </div>
                <div class="col-md-4">
                    </div>
                <div class="col-md-4">
                    <p class="small text-muted mb-5">ຜູ້ຮັບສິນຄ້າ / ຝ່າຍຮັບເຄື່ອງ</p>
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