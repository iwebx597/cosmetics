<?php
session_start();
// ກວດສອບສິດການເຂົ້າເຖິງ (ຖ້າບໍ່ມີ session ໃຫ້ເດັ້ງກັບໄປໜ້າ login)
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

require 'cmt_db.php'; // ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ cmt_db

$msg = "";
$msg_type = "";

// 📌 1. ດຶງຂໍ້ມູນເກົ່າຂອງບ່ອນຈັດເກັບທີ່ເລືອກມາສະແດງໃນຟອມ (ໃຊ້ loc_id)
if (isset($_GET['id'])) {
    $loc_id = $_GET['id'];
    try {
        $stmt = $conn->prepare("SELECT * FROM location WHERE loc_id = :loc_id LIMIT 1");
        $stmt->execute(['loc_id' => $loc_id]);
        $loc_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$loc_data) {
            die("❌ ບໍ່ພົບຂໍ້ມູນບ່ອນຈັດເກັບນີ້ໃນລະບົບ");
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: form_location.php");
    exit;
}

// 📌 2. ຕອນກົດປຸ່ມບັນທຶກການແກ້ໄຂຂໍ້ມູນ (Submit Form)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $loc_name = trim($_POST['loc_name']);

    if (!empty($loc_name)) {
        try {
            // 🔍 ກວດສອບກ່ອນວ່າ ຊື່ບ່ອນຈັດເກັບໃໝ່ນີ້ໄປຊ້ຳກັບ ID ອື່ນທີ່ມີຢູ່ແລ້ວບໍ່
            $check_stmt = $conn->prepare("SELECT loc_id FROM location WHERE loc_name = :loc_name AND loc_id != :loc_id LIMIT 1");
            $check_stmt->execute(['loc_name' => $loc_name, 'loc_id' => $loc_id]);
            
            if ($check_stmt->rowCount() > 0) {
                $msg = "⚠️ ປ່ຽນບໍ່ສຳເລັດ: ຊື່ບ່ອນຈັດເກັບນີ້ມີຢູ່ໃນລະບົບແລ້ວ!";
                $msg_type = "warning";
            } else {
                // 💾 ສັ່ງອັບເດດຂໍ້ມູນ
                $sql = "UPDATE location SET loc_name = :loc_name WHERE loc_id = :loc_id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'loc_name' => $loc_name,
                    'loc_id' => $loc_id
                ]);

                // ໂຫຼດຂໍ້ມູນໃໝ່ຫຼັງຈາກອັບເດດສຳເລັດ ເພື່ອເອົາມາສະແດງໃນຟອມ
                $loc_data['loc_name'] = $loc_name;

                $msg = "🎉 ແກ້ໄຂຂໍ້ມູນບ່ອນຈັດເກັບສຳເລັດແລ້ວ!";
                $msg_type = "success";
            }
        } catch (PDOException $e) {
            $msg = "❌ ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
            $msg_type = "danger";
        }
    } else {
        $msg = "⚠️ ກະລຸນາປ້ອນຊື່ສະຖານທີ່ບ່ອນຈັດເກັບ!";
        $msg_type = "warning";
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<link href="style/fonts.css" rel="stylesheet">
<meta charset="UTF-8">
<title>ແກ້ໄຂບ່ອນຈັດເກັບ</title>

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
        <h4 class="mb-0 fw-bold text-dark">📝 ແກ້ໄຂບ່ອນຈັດເກັບ</h4>
        <a href="form_location.php" class="btn btn-secondary">🔙 ກັບຄືນ</a>
    </div>

    <?php if(!empty($msg)): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= $msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-warning text-dark py-3">
            <h6 class="mb-0 fw-bold">ຟອມແກ້ໄຂຂໍ້ມູນ (ລະຫັດ: <?= htmlspecialchars($loc_data['loc_id']) ?>)</h6>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="location_edit.php?id=<?= htmlspecialchars($loc_data['loc_id']) ?>">
                
                <div class="mb-3">
                    <label class="form-label fw-bold">ລະຫັດບ່ອນຈັດເກັບ</label>
                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($loc_data['loc_id']) ?>" readonly>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">ຊື່ບ່ອນຈັດເກັບສິນຄ້າ <span class="text-danger">*</span></label>
                    <input type="text" name="loc_name" class="form-control" value="<?= htmlspecialchars($loc_data['loc_name']) ?>" required>
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