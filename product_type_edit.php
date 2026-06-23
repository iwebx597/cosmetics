<?php
session_start();
// ກວດສອບສິດການເຂົ້າເຖິງ (ຖ້າບໍ່ມີ session ໃຫ້ເດັ້ງກັບໄປໜ້າ login)
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

require 'cmt_db.php'; // ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ

$msg = "";
$msg_type = "";

// 📌 1. ດຶງຂໍ້ມູນເກົ່າຂອງປະເພດສິນຄ້າທີ່ເລືອກມາສະແດງໃນຟອມ (ໃຊ້ type_id)
if (isset($_GET['id'])) {
    $type_id = $_GET['id'];
    try {
        $stmt = $conn->prepare("SELECT * FROM product_type WHERE type_id = :type_id LIMIT 1");
        $stmt->execute(['type_id' => $type_id]);
        $type_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$type_data) {
            die("❌ ບໍ່ພົບຂໍ້ມູນປະເພດສິນຄ້ານີ້ໃນລະບົບ");
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: form_product_type.php");
    exit;
}

// 📌 2. ຕອນກົດປຸ່ມບັນທຶກການແກ້ໄຂຂໍ້ມູນ (Submit Form)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type_name = trim($_POST['type_name']);

    if (!empty($type_name)) {
        try {
            // 🔍 ກວດສອບກ່ອນວ່າ ຊື່ປະເພດສິນຄ້າໃໝ່ນີ້ໄປຊ້ຳກັບ ID ອື່ນທີ່ມີຢູ່ແລ້ວບໍ່
            $check_stmt = $conn->prepare("SELECT type_id FROM product_type WHERE type_name = :type_name AND type_id != :type_id LIMIT 1");
            $check_stmt->execute(['type_name' => $type_name, 'type_id' => $type_id]);
            
            if ($check_stmt->rowCount() > 0) {
                $msg = "⚠️ ປ່ຽນບໍ່ສຳເລັດ: ຊື່ປະເພດສິນຄ້ານີ້ມີຢູ່ໃນລະບົບແລ້ວ!";
                $msg_type = "warning";
            } else {
                // 💾 ສັ່ງອັບເດດຂໍ້ມູນ
                $sql = "UPDATE product_type SET type_name = :type_name WHERE type_id = :type_id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'type_name' => $type_name,
                    'type_id' => $type_id
                ]);

                // ໂຫຼດຂໍ້ມູນໃໝ່ຫຼັງຈາກອັບເດດສຳເລັດ ເພື່ອເອົາມາສະແດງໃນຟອມ
                $type_data['type_name'] = $type_name;

                $msg = "🎉 ແກ້ໄຂຂໍ້ມູນປະເພດສິນຄ້າສຳເລັດແລ້ວ!";
                $msg_type = "success";
            }
        } catch (PDOException $e) {
            $msg = "❌ ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
            $msg_type = "danger";
        }
    } else {
        $msg = "⚠️ ກະລຸນາປ້ອນຊື່ປະເພດສິນຄ້າ!";
        $msg_type = "warning";
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<link href="style/fonts.css" rel="stylesheet">
<meta charset="UTF-8">
<title>ແກ້ໄຂປະເພດສິນຄ້າ</title>

<link href="bootstrap-5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="bootstrap-5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<style>
body{font-family: 'noto-sans-lao-regular', sans-serif;}
.sidebar{min-height:100vh; background:#212529;}
.sidebar a{color:#adb5bd;text-decoration:none;display:block;padding:12px 20px;}
.sidebar a:hover{background:#343a40; color: #fff;}
.sidebar .active-menu{background:#495057; color: #fff !important;}
</style>
</head>

<body>
<div class="container-fluid">
<div class="row">

<?php include 'sidebar.php'; ?>

<div class="col-md-10 bg-light p-0">

<?php include 'navbar.php'; ?>

<div class="container px-4" style="max-width: 600px; margin: 0 auto;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold text-dark">📝 ແກ້ໄຂປະເພດສິນຄ້າ</h4>
        <a href="form_product_type.php" class="btn btn-secondary">🔙 ກັບຄືນ</a>
    </div>

    <?php if(!empty($msg)): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= $msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-warning text-dark py-3">
            <h6 class="mb-0 fw-bold">ຟອມແກ້ໄຂຂໍ້ມູນ (ລະຫັດ: <?= htmlspecialchars($type_data['type_id']) ?>)</h6>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="product_type_edit.php?id=<?= htmlspecialchars($type_data['type_id']) ?>">
                
                <div class="mb-3">
                    <label class="form-label fw-bold">ລະຫັດປະເພດສິນຄ້າ</label>
                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($type_data['type_id']) ?>" readonly>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">ຊື່ປະເພດສິນຄ້າ <span class="text-danger">*</span></label>
                    <input type="text" name="type_name" class="form-control" value="<?= htmlspecialchars($type_data['type_name']) ?>" required>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-warning fw-bold text-dark">💾 ບັນທຶກການແກ້ໄຂ</button>
                </div>
            </form>
        </div>
    </div>

</div>

</div>
</div>
</div>
</body>
</html>