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

// 📌 1. ຕອນກົດປຸ່ມບັນທຶກຂໍ້ມູນ (ເພີ່ມຜູ້ສະໜອງໃໝ່)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_save'])) {
    $sup_name = trim($_POST['sup_name']);
    $gender = isset($_POST['gender']) ? $_POST['gender'] : null;
    $company = trim($_POST['company']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    if (!empty($sup_name) && !empty($company)) {
        try {
            // 🔍 ກວດສອບກ່ອນວ່າ ຊື່ບໍລິສັດ ຫຼື ຊື່ຜູ້ສະໜອງນີ້ມີແລ້ວບໍ່
            $check = $conn->prepare("SELECT company FROM supplier WHERE company = :company LIMIT 1");
            $check->execute(['company' => $company]);

            if ($check->rowCount() > 0) {
                $msg = "⚠️ ຊື່ບໍລິສັດຜູ້ສະໜອງນີ້ ມີຢູ່ໃນລະບົບແລ້ວ!";
                $msg_type = "warning";
            } else {
                // 💾 ສັ່ງ Insert ລົງຕາຕະລາງ supplier ຕາມໂຄງສ້າງ
                $sql = "INSERT INTO supplier (sup_name, gender, company, phone, email, address) 
                        VALUES (:sup_name, :gender, :company, :phone, :email, :address)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'sup_name' => $sup_name,
                    'gender' => $gender,
                    'company' => $company,
                    'phone' => $phone,
                    'email' => $email,
                    'address' => $address
                ]);

                $msg = "ເພີ່ມຂໍ້ມູນຜູ້ສະໜອງໃໝ່ສຳເລັດແລ້ວ!";
                $msg_type = "success";
            }
        } catch (PDOException $e) {
            $msg = "❌ ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
            $msg_type = "danger";
        }
    } else {
        $msg = "⚠️ ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບຖ້ວນ (ຊື່ຜູ້ສະໜອງ ແລະ ບໍລິສັດ)!";
        $msg_type = "warning";
    }
}

// 📌 2. ດຶງຂໍ້ມູນຜູ້ສະໜອງທັງໝົດ ຫຼື ຄົ້ນຫາ
$search = "";
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

try {
    if (!empty($search)) {
        $stmt = $conn->prepare("SELECT * FROM supplier WHERE company LIKE :search OR sup_name LIKE :search OR phone LIKE :search ORDER BY sup_id asc");
        $stmt->execute(['search' => "%$search%"]);
        $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $conn->query("SELECT * FROM supplier ORDER BY sup_id asc");
        $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $suppliers = [];
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <link href="style/fonts.css" rel="stylesheet">
    <title>จัดກາານຜູ້ສະໜອງ</title>
    <link href="bootstrap-5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="bootstrap-5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        }

        .pg-header-title {
            color: #1a1a1a !important;
            font-weight: 700 !important;
            font-size: 16px !important;
            margin: 0 !important;
        }

        /* Content wrapper */
        .pg-content-wrapper {
            padding: 24px !important;
        }

        /* Form Card */
        .pg-form-card {
            border: none !important;
            border-radius: 0 !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
            background-color: #ffffff !important;
            margin-bottom: 24px !important;
        }

        .pg-form-card-header {
            background: #7A1530 !important;
            padding: 17px 24px !important;
            border: none !important;
            border-radius: 0 !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
        }

        .pg-form-card-title {
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            margin: 0 !important;
        }

        .pg-form-body {
            padding: 24px !important;
        }

        /* Form controls - material style */
        .form-label-custom {
            font-weight: 700 !important;
            font-size: 15px !important;
            color: #333333 !important;
            margin-bottom: 8px !important;
            display: block !important;
        }

        .form-control-custom {
            width: 100% !important;
            border-radius: 0 !important;
            border: 1px solid #cccccc !important;
            background-color: #eeeeee !important;
            padding: 10px 14px !important;
            font-size: 15px !important;
            outline: none !important;
            box-shadow: none !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
        }

        .form-control-custom:focus {
            background-color: #ffffff !important;
            border-color: #C9956A !important;
        }

        /* Custom radio style */
        .pg-radio-group {
            display: flex !important;
            gap: 24px !important;
            margin-top: 10px !important;
            justify-content: flex-start !important;
        }

        .pg-radio-item {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            cursor: pointer !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            color: #333333 !important;
        }

        .pg-radio-item input[type="radio"] {
            appearance: none !important;
            width: 20px !important;
            height: 20px !important;
            border: 2px solid #888888 !important;
            border-radius: 50% !important;
            outline: none !important;
            display: inline-block !important;
            vertical-align: middle !important;
            position: relative !important;
            cursor: pointer !important;
        }

        .pg-radio-item input[type="radio"]:checked {
            border-color: #7A1530 !important;
            background-color: #ffffff !important;
        }

        .pg-radio-item input[type="radio"]:checked::after {
            content: "" !important;
            position: absolute !important;
            top: 3px !important;
            left: 3px !important;
            width: 10px !important;
            height: 10px !important;
            background-color: #7A1530 !important;
            border-radius: 50% !important;
        }

        /* Form Divider line */
        .pg-form-divider {
            border-top: 3px solid #7A1530 !important;
            margin: 20px 0 !important;
        }

        /* Button group */
        .btn-submit-group {
            display: flex !important;
            justify-content: center !important;
            gap: 16px !important;
        }

        .btn-save {
            background-color: #7A1530 !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 10px 32px !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
        }

        .btn-save:hover {
            background-color: #5a0f22 !important;
        }

        .btn-reset {
            background-color: #c62828 !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 10px 32px !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
        }

        .btn-reset:hover {
            background-color: #b71c1c !important;
        }

        /* Table header - Sand/Beige */
        .pg-table thead th {
            background-color: #C9956A !important;
            color: #1a1a1a !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            padding: 12px 24px !important;
            border: none !important;
            white-space: nowrap !important;
        }

        .pg-table tbody td {
            padding: 12px 24px !important;
            font-size: 15px !important;
            font-weight: bold !important;
            color: #333333 !important;
            border-bottom: 1px solid #EAEAEA !important;
            background-color: #ffffff !important;
            vertical-align: middle !important;
        }

        /* Action buttons */
        .btn-detail-icon {
            background: none !important;
            border: none !important;
            padding: 4px 6px !important;
            cursor: pointer !important;
            color: #555 !important;
            font-size: 20px !important;
            text-decoration: none !important;
            display: inline-block !important;
        }
        
        .btn-detail-icon:hover { color: #C9956A !important; }

        .btn-edit-icon {
            background: none !important;
            border: none !important;
            padding: 4px 6px !important;
            cursor: pointer !important;
            color: #555 !important;
            font-size: 18px !important;
            text-decoration: none !important;
            display: inline-block !important;
        }

        .btn-edit-icon:hover { color: #7A1530 !important; }

        .btn-del-icon {
            background: none !important;
            border: none !important;
            padding: 4px 6px !important;
            cursor: pointer !important;
            color: #555 !important;
            font-size: 18px !important;
            text-decoration: none !important;
            display: inline-block !important;
        }

        .btn-del-icon:hover { color: #e53935 !important; }

        /* Custom search box in card header */
        .search-container-custom {
            display: flex !important;
            background: #ffffff !important;
            border: 1px solid #cccccc !important;
            height: 38px !important;
            align-items: center !important;
            border-radius: 0 !important;
        }

        .search-input-custom {
            border: none !important;
            outline: none !important;
            padding: 6px 12px !important;
            font-size: 14px !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            width: 200px !important;
        }

        .btn-search-custom {
            background-color: #C9956A !important;
            color: #1a1a1a !important;
            border: none !important;
            height: 100% !important;
            padding: 0 16px !important;
            font-weight: bold !important;
            font-size: 14px !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            cursor: pointer !important;
        }

        .btn-search-custom:hover {
            background-color: #B88458 !important;
        }

        /* Pagination Styling */
        .pg-pagination-wrapper {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            background-color: #ffffff !important;
            padding: 12px 20px !important;
            border-top: 2px solid #A0A0A0 !important;
        }

        .pg-table-footer-info {
            font-size: 14px !important;
            color: #333333 !important;
            font-weight: bold !important;
        }

        .pg-pagination {
            display: flex !important;
            gap: 6px !important;
            align-items: center !important;
        }

        .pg-page-btn {
            background-color: #ffffff !important;
            border: 1px solid #cccccc !important;
            color: #333333 !important;
            padding: 6px 12px !important;
            font-size: 14px !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            cursor: pointer !important;
            border-radius: 4px !important;
            transition: all 0.2s !important;
            font-weight: bold !important;
        }

        .pg-page-btn:hover:not(.disabled) {
            background-color: #C9956A !important;
            border-color: #C9956A !important;
            color: #ffffff !important;
        }

        .pg-page-btn.active {
            background-color: #7A1530 !important;
            border-color: #7A1530 !important;
            color: #ffffff !important;
            cursor: default !important;
        }

        .pg-page-btn.disabled {
            background-color: #f1f1f1 !important;
            border-color: #e0e0e0 !important;
            color: #bbbbbb !important;
            cursor: not-allowed !important;
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
        <h4 class="pg-header-title">ຈັດການຂໍ້ມູນຜູ້ສະໜອງ</h4>
    </div>

    <!-- Content below header -->
    <div class="pg-content-wrapper">

        <?php if(!empty($msg)): ?>
            <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm rounded-0 mb-4"><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- Form Card (Add Supplier) -->
        <div class="pg-form-card">
            <div class="pg-form-card-header">
                <h6 class="pg-form-card-title">ເພີ່ມຜູ້ສະໜອງໃໝ່</h6>
            </div>
            <div class="pg-form-body">
                <form method="POST" action="form_supplier.php">
                    <div class="row">
                        <!-- Row 1 -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">ຊື່ບໍລິສັດ/ຮ້ານ</label>
                            <input type="text" name="company" class="form-control-custom" placeholder="ຕົວຢ່າງ : ບໍລິສັດ ບິວຕີ້ລາວ ຈຳກັດ" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">ຊື່ຜູ້ຕິດຕໍ່</label>
                            <input type="text" name="sup_name" class="form-control-custom" placeholder="ຊື່ ແລະ ນາມສະກຸນ" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">ເພດ</label>
                            <div class="pg-radio-group">
                                <label class="pg-radio-item">
                                    <input type="radio" name="gender" value="M" checked>
                                    ຊາຍ (M)
                                </label>
                                <label class="pg-radio-item">
                                    <input type="radio" name="gender" value="F">
                                    ຍິງ (F)
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Row 2 -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">ເບີໂທລະສັບ</label>
                            <input type="text" name="phone" class="form-control-custom" placeholder="ຕົວຢ່າງ : 020XXXXXXXX">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">ອີເມວ</label>
                            <input type="email" name="email" class="form-control-custom" placeholder="ອີເມວ">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label-custom">ທີ່ຢູ່</label>
                            <input type="text" name="address" class="form-control-custom" placeholder="ບ້าน,ເມືອງ,ແຂວງ">
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="pg-form-divider"></div>

                    <!-- Action Buttons -->
                    <div class="btn-submit-group">
                        <button type="submit" name="btn_save" class="btn-save">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                            ບັນທຶກ
                        </button>
                        <button type="reset" class="btn-reset">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19 8H5c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 10h-2v-4h2v4zm3-8H8V6c0-2.21 1.79-4 4-4s4 1.79 4 4v4z"/></svg>
                            ລ້າງຟອມ
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table Card (List Suppliers) -->
        <div class="pg-form-card">
            <div class="pg-form-card-header">
                <h6 class="pg-form-card-title">ລາຍການຜູ້ສະໜອງທັງໝົດໃນລະບົບ</h6>
                
                <!-- Search Box -->
                <form method="GET" action="form_supplier.php">
                    <div class="search-container-custom">
                        <input type="text" name="search" class="search-input-custom" placeholder="ຄົ້ນຫາຂໍ້ມູນຜູ້ສະໜອງ" value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn-search-custom">ຄົ້ນຫາ</button>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="pg-table w-100" id="supplierTable">
                        <thead>
                            <tr>
                                <th style="width:80px; text-align:center;">ລ/ດ</th>
                                <th>ບໍລິສັດ/ຮ້ານ</th>
                                <th>ຜູ້ຕິດຕໍ່</th>
                                <th>ເບີໂທ</th>
                                <th style="width:180px; text-align:center;">ເບິ່ງລາຍລະອຽດ</th>
                                <th style="width:150px; text-align:center;">ຈັດການ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($suppliers) > 0): ?>
                                <?php foreach($suppliers as $i => $sup): ?>
                                <tr>
                                    <td style="text-align:center;" class="row-number-cell"></td>
                                    <td><?= htmlspecialchars($sup['company']) ?></td>
                                    <td><?= htmlspecialchars($sup['sup_name']) ?></td>
                                    <td><?= htmlspecialchars($sup['phone']) ?></td>
                                    <td style="text-align:center;">
                                        <a href="supplier_detail.php?id=<?= $sup['sup_id'] ?>" class="btn-detail-icon" title="ເບິ່ງລາຍລະອຽດ">
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm0 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3zm0 2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1z"/></svg>
                                        </a>
                                    </td>
                                    <td style="text-align:center; white-space:nowrap;">
                                        <a href="supplier_edit.php?id=<?= $sup['sup_id'] ?>" class="btn-edit-icon" title="ແກ້ໄຂ">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                        </a>
                                        <a href="supplier_delete.php?id=<?= $sup['sup_id'] ?>" class="btn-del-icon" title="ລົບ" onclick="confirmDelete(event, this.href)">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19a2 2 0 002 2h8a2 2 0 002-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; color:#999; padding:40px 0;">
                                        ບໍ່ມີຂໍ້ມູນຜູ້ສະໜອງໃນລະບົບ
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="pg-pagination-wrapper">
                <div class="pg-table-footer-info">
                    ຈຳນວນ <?= count($suppliers) ?> ລາຍການຜູ້ສະໜອງໃນລະບົບ
                </div>
                <div class="pg-pagination" id="paginationControls">
                    <!-- Pagination will be rendered here by JS -->
                </div>
            </div>
        </div>

    </div><!-- end pg-content-wrapper -->
</div><!-- end container-fluid -->

</div>
</div>
</div>

<script>
// Pagination state
let currentPage = 1;
const rowsPerPage = 5;

function renderPagination() {
    const table = document.getElementById("supplierTable");
    const tbody = table.tBodies[0];
    const allRows = Array.from(tbody.rows);

    const totalItems = allRows.length;
    const totalPages = Math.ceil(totalItems / rowsPerPage) || 1;

    // Boundary check
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    // Hide all rows first
    allRows.forEach(row => {
        row.style.display = "none";
    });

    // Show only rows for current page
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;

    allRows.forEach((row, index) => {
        // Dynamic row number
        const numCell = row.querySelector(".row-number-cell");
        if (numCell) {
            numCell.textContent = index + 1;
        }

        if (index >= startIndex && index < endIndex) {
            row.style.display = "";
        }
    });

    // Update pagination controls html
    const paginationControls = document.getElementById("paginationControls");
    if (paginationControls) {
        let html = '';

        // Previous button ("ຖອຍກັບ")
        if (currentPage > 1) {
            html += `<button class="pg-page-btn" onclick="changePage(${currentPage - 1})">ຖອຍກັບ</button>`;
        } else {
            html += `<button class="pg-page-btn disabled" disabled>ຖອຍກັບ</button>`;
        }

        // Page numbers 1, 2, 3...
        for (let i = 1; i <= totalPages; i++) {
            if (i === currentPage) {
                html += `<button class="pg-page-btn active">${i}</button>`;
            } else {
                html += `<button class="pg-page-btn" onclick="changePage(${i})">${i}</button>`;
            }
        }

        // Next button ("ໄປໜ້າ")
        if (currentPage < totalPages) {
            html += `<button class="pg-page-btn" onclick="changePage(${currentPage + 1})">ໄປໜ້າ</button>`;
        } else {
            html += `<button class="pg-page-btn disabled" disabled>ໄປໜ້າ</button>`;
        }

        paginationControls.innerHTML = html;
    }
}

function changePage(page) {
    currentPage = page;
    renderPagination();
}

// Initialize pagination on load
document.addEventListener("DOMContentLoaded", function() {
    renderPagination();
});

// SweetAlert2 Delete Confirmation Dialog
function confirmDelete(event, url) {
    event.preventDefault();
    Swal.fire({
        title: 'ຢືນຢັນການລົບ',
        text: 'ທ່ານຕ້ອງການລົບຜູ້ສະໜອງນີ້ແທ້ບໍ?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e53935',
        cancelButtonColor: '#aaaaaa',
        confirmButtonText: 'ລົບຂໍ້ມູນ',
        cancelButtonText: 'ຍົກເລີກ',
        background: '#ffffff',
        customClass: {
            popup: 'rounded-0'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}
</script>
</body>
</html>