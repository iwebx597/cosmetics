<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

require 'cmt_db.php'; // ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ

if (!isset($_GET['id'])) {
    header("Location: form_orders.php");
    exit;
}

$order_id = intval($_GET['id']);

try {
    // 📌 1. ດຶງຂໍ້ມູນຫຼັກຂອງໃບບິນ (Master)
    $sql_master = "SELECT o.*, s.sup_name, s.phone as sup_phone, s.address as sup_address, s.company, e.emp_fname, e.emp_lname,
                         e.position, e.phone 
                   FROM orders o
                   LEFT JOIN supplier s ON o.sup_id = s.sup_id
                   LEFT JOIN employee e ON o.emp_id = e.emp_id
                   WHERE o.order_id = :order_id LIMIT 1";
    $stmt_m = $conn->prepare($sql_master);
    $stmt_m->execute(['order_id' => $order_id]);
    $order = $stmt_m->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo "<script>alert('⚠️ ບໍ່ພົບຂໍ້ມູນໃບບິນນີ້!'); location='form_orders.php';</script>";
        exit;
    }

    // 📌 2. ດຶງຂໍ້ມູນລາຍການສິນຄ້າໃນໃບບິນ (Details)
    $sql_detail = "SELECT d.*, p.pro_name, p.pro_brand, u.unit_name 
                   FROM order_detail d
                   LEFT JOIN product p ON d.pro_id = p.pro_id
                   LEFT JOIN unit u ON d.unit_id = u.unit_id
                   WHERE d.order_id = :order_id
                   ORDER BY d.order_de_id ASC";
    $stmt_d = $conn->prepare($sql_detail);
    $stmt_d->execute(['order_id' => $order_id]);
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
<title>ລາຍລະອຽດໃບບິນສັ່ງຊື້</title>
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
        <h4 class="fw-bold text-dark">👁️ ລາຍລະອຽດໃບບິນສັ່ງຊື້</h4>
        <div>
            <button onclick="window.print()" class="btn btn-success me-2">🖨️ ພິມໃບບິນ (Print)</button>
            <a href="form_orders.php" class="btn btn-secondary">🔙 ກັບຄືນ</a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-body p-5">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="fw-bold text-primary">📦 ລະບົບຄຸ້ມຄອງສາງເຄື່ອງສໍາອາງ</h5>
                    <p class="text-muted small">ນະຄອນຫຼວງວຽງຈັນ, ປະເທດລາວ<br> </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h3 class="fw-bold text-dark mb-1">ໃບບິນສັ່ງຊື້ສິນຄ້າ</h3>
                    <h5 class="text-danger fw-bold mb-2"><?= htmlspecialchars($order['order_no']) ?></h5>
                    <p class="small text-muted mb-0">ວັນທີສັ່ງຊື້: <strong><?= date('d/m/Y', strtotime($order['order_date'])) ?></strong></p>
                    <p class="small text-muted">ກຳນົດສົ່ງ: <strong><?= date('d/m/Y', strtotime($order['expected_date'])) ?></strong></p>
                </div>
            </div>

            <hr class="text-secondary">

            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="fw-bold text-secondary mb-2">👥 ຜູ້ຮັບຜິດຊອບສັ່ງຊື້ (Employee):</h6>
                    <p class="mb-1"><strong><?= htmlspecialchars($order['emp_fname'] . " " . $order['emp_lname']) ?></strong></p>
                    <p class="small text-muted mb-0">ຕຳແໜ່ງ: <?= htmlspecialchars($order['position'] ?: '-') ?></p>
                    <p class="small text-muted">ເບີໂທ: <?= htmlspecialchars($order['phone'] ?: '-') ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6 class="fw-bold text-secondary mb-2">🏢 ຂໍ້ມູນຜູ້ສະໜອງ (Supplier):</h6>
                    <p class="mb-1"><strong><?= htmlspecialchars($order['sup_name'] ?? 'ບໍ່ລະບຸ') ?>
                        &nbsp (<?= htmlspecialchars($order['company'] ?? '-') ?>)</strong></p>
                    <p class="mb-1 small text-muted">ທີ່ຢູ່: <?= htmlspecialchars($order['sup_address'] ?? '-') ?></p>
                    <p class="small text-muted">ເບີໂທ: <?= htmlspecialchars($order['sup_phone'] ?? '-') ?></p>
                </div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th width="60">ລ/ດ</th>
                            <th width="120">ລະຫັດສິນຄ້າ</th>
                            <th>ຊື່ສິນຄ້າ ແລະ ແບຣນ</th>
                            <th width="120">ຈຳນວນ</th>
                            <th width="120">ຫົວໜ່ວຍ</th>
                            <th width="180">ລາຄາຕໍ່ໜ່ວຍ</th>
                            <th width="200">ມູນຄ່າລວມ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grand_total = 0;
                        foreach($details as $i => $d): 
                            $grand_total += $d['total'];
                        ?>
                        <tr>
                            <td class="text-center"><?= $i + 1 ?></td>
                            <td class="text-center fw-bold text-secondary"><?= htmlspecialchars($d['pro_id']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($d['pro_name']) ?></strong>
                                <br><small class="text-muted">Brand: <?= htmlspecialchars($d['pro_brand']) ?></small>
                            </td>
                            <td class="text-center"><?= number_format($d['qty']) ?></td>
                            <td class="text-center text-muted"><?= htmlspecialchars($d['unit_name']) ?></td>
                            <td class="text-end"><?= number_format($d['price'], 2) ?> ₭</td>
                            <td class="text-end fw-bold"><?= number_format($d['total'], 2) ?> ₭</td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-light fw-bold">
                            <td colspan="6" class="text-end fs-6 py-3">💰 ມູນຄ່າລວມທັງໝົດ (Grand Total):</td>
                            <td class="text-end text-danger fs-5 py-3"><?= number_format($grand_total, 2) ?> ₭</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="row mt-5 pt-3 text-center">
                <div class="col-md-4">
                    <p class="small text-muted mb-5">ຜູ້ສັ່ງຊື້ສິນຄ້າ</p>
                    <p class="mb-0">........................................</p>
                </div>
                <div class="col-md-4">
                    </div>
                <div class="col-md-4">
                    <p class="small text-muted mb-5">ຜູ້ສະໜອງສິນຄ້າ (ຝ່າຍຂາຍ)</p>
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