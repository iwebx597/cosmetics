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

// 📌 1. ດຶງຂໍ້ມູນເກົ່າຂອງຜູ້ສະໜອງທີ່ເລືອກມາສະແດງໃນຟອມ (ໃຊ້ sup_id)
if (isset($_GET['id'])) {
    $sup_id = $_GET['id'];
    try {
        $stmt = $conn->prepare("SELECT * FROM supplier WHERE sup_id = :sup_id LIMIT 1");
        $stmt->execute(['sup_id' => $sup_id]);
        $sup_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$sup_data) {
            die("❌ ບໍ່ພົບຂໍ້ມູນຜູ້ສະໜອງນີ້ໃນລະບົບ");
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: form_supplier.php");
    exit;
}

// 📌 2. ຕອນກົດປຸ່ມບັນທຶກການແກ້ໄຂຂໍ້ມູນ (Submit Form)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $company = trim($_POST['company']);
    $sup_name = trim($_POST['sup_name']);
    $gender = isset($_POST['gender']) ? $_POST['gender'] : null;
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    if (!empty($company) && !empty($sup_name)) {
        try {
            // 🔍 ກວດສອບກ່ອນວ່າ ຊື່ບໍລິສັດໃໝ່ນີ້ໄປຊ້ຳກັບ ID ອື່ນທີ່ມີຢູ່ແລ້ວບໍ່
            $check_stmt = $conn->prepare("SELECT sup_id FROM supplier WHERE company = :company AND sup_id != :sup_id LIMIT 1");
            $check_stmt->execute(['company' => $company, 'sup_id' => $sup_id]);
            
            if ($check_stmt->rowCount() > 0) {
                $msg = "⚠️ ປ່ຽນບໍ່ສຳເລັດ: ຊື່ບໍລິສັດຜູ້ສະໜອງນີ້ມີຢູ່ໃນລະບົບແລ້ວ!";
                $msg_type = "warning";
            } else {
                // 💾 ສັ່ງອັບເດດຂໍ້ມູນຕາມໂຄງສ້າງຕາຕະລາງ
                $sql = "UPDATE supplier set 
                            company = :company, 
                            sup_name = :sup_name, 
                            gender = :gender, 
                            phone = :phone, 
                            email = :email, 
                            address = :address 
                        WHERE sup_id = :sup_id";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'company' => $company,
                    'sup_name' => $sup_name,
                    'gender' => $gender,
                    'phone' => $phone,
                    'email' => $email,
                    'address' => $address,
                    'sup_id' => $sup_id
                ]);

                // ໂຫຼດຂໍ້ມູນໃໝ່ຫຼັງຈາກອັບເດດສຳເລັດ ເພື່ອເອົາມາສະແດງໃນຟອມ
                $sup_data['company'] = $company;
                $sup_data['sup_name'] = $sup_name;
                $sup_data['gender'] = $gender;
                $sup_data['phone'] = $phone;
                $sup_data['email'] = $email;
                $sup_data['address'] = $address;

                $msg = "🎉 ແກ້ໄຂຂໍ້ມູນຜູ້ສະໜອງສຳເລັດແລ້ວ!";
                $msg_type = "success";
            }
        } catch (PDOException $e) {
            $msg = "❌ ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
            $msg_type = "danger";
        }
    } else {
        $msg = "⚠️ ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບຖ້ວນ (ຊື່ບໍລິສັດ ແລະ ຊື່ຜູ້ຕິດຕໍ່)!";
        $msg_type = "warning";
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<link href="style/fonts.css" rel="stylesheet">
<meta charset="UTF-8">
<title>ແກ້ໄຂຂໍ້ມູນຜູ້ສະໜອງ</title>

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

<div class="container px-4" style="max-width: 650px; margin: 0 auto;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold text-dark">📝 ແກ້ໄຂຂໍ້ມູນຜູ້ສະໜອງ</h4>
        <a href="form_supplier.php" class="btn btn-secondary">🔙 ກັບຄືນ</a>
    </div>

    <?php if(!empty($msg)): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= $msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-warning text-dark py-3">
            <h6 class="mb-0 fw-bold">ຟອມແກ້ໄຂຂໍ້ມູນຜູ້ສະໜອງ (ລະຫັດ: <?= htmlspecialchars($sup_data['sup_id']) ?>)</h6>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="supplier_edit.php?id=<?= htmlspecialchars($sup_data['sup_id']) ?>">
                
                <div class="mb-3">
                    <label class="form-label fw-bold">ຊື່ບໍລິສັດ / ຮ້ານ <span class="text-danger">*</span></label>
                    <input type="text" name="company" class="form-control" value="<?= htmlspecialchars($sup_data['company']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">ชື່ຜູ້ຕິດຕໍ່ <span class="text-danger">*</span></label>
                    <input type="text" name="sup_name" class="form-control" value="<?= htmlspecialchars($sup_data['sup_name']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">ເພດ</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="gender" id="genderM" value="M" <?= $sup_data['gender'] == 'M' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="genderM">ຊາຍ (M)</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="gender" id="genderF" value="F" <?= $sup_data['gender'] == 'F' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="genderF">ຍິງ (F)</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">ເບີໂທລະສັບ</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($sup_data['phone']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">ອີເມວ</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($sup_data['email']) ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">ທີ່ຢູ່</label>
                    <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($sup_data['address']) ?>">
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