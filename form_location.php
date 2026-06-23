<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

require 'cmt_db.php'; 

$msg = "";
$msg_type = "";

// 📌 1. ຕອນກົດປຸ່ມບັນທຶກຂໍ້ມູນ (ເພີ່ມສະຖານທີ່ເກັບໃໝ່)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_save'])) {
    $loc_name = trim($_POST['loc_name']);

    if (!empty($loc_name)) {
        try {
            // ກວດສອບຊື່ຊ້ຳ (ສົມມຸດຕາຕະລາງຊື່ location ມີຟີລ loc_id, loc_name)
            $check = $conn->prepare("SELECT loc_name FROM location WHERE loc_name = :loc_name LIMIT 1");
            $check->execute(['loc_name' => $loc_name]);

            if ($check->rowCount() > 0) {
                $msg = "⚠️ ມີຢູ່ໃນລະບົບແລ້ວ!";
                $msg_type = "warning";
            } else {
                $sql = "INSERT INTO location (loc_name) VALUES (:loc_name)";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['loc_name' => $loc_name]);

                $msg = "ເພີ່ມສະຖານທີ່ເກັບໃໝ່ສຳເລັດແລ້ວ!";
                $msg_type = "success";
            }
        } catch (PDOException $e) {
            $msg = "❌ ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
            $msg_type = "danger";
        }
    } else {
        $msg = "⚠️ ກະລຸນາປ້ອນຊື່!";
        $msg_type = "warning";
    }
}

// 📌 2. ດຶງຂໍ້ມູນສະຖານທີ່ເກັບທັງໝົດມາສະແດງ
try {
    $stmt = $conn->query("SELECT * FROM location ORDER BY loc_id ASC");
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $locations = [];
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <link href="style/fonts.css" rel="stylesheet">
    <title>📍 ຈັດການສະຖານທີ່ເກັບສິນຄ້າ</title>
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
            margin-bottom: 12px !important;
            display: block !important;
            text-align: center !important;
        }

        .form-control-custom {
            display: block !important;
            width: 100% !important;
            max-width: 500px !important;
            margin: 0 auto !important;
            border-radius: 0 !important;
            border: 1px solid #cccccc !important;
            background-color: #eeeeee !important;
            padding: 10px 14px !important;
            font-size: 15px !important;
            outline: none !important;
            box-shadow: none !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            text-align: center !important;
        }

        .form-control-custom:focus {
            background-color: #ffffff !important;
            border-color: #C9956A !important;
        }

        /* Button group */
        .btn-submit-group {
            display: flex !important;
            justify-content: center !important;
            gap: 16px !important;
            margin-top: 20px !important;
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
        <h4 class="pg-header-title">ຈັດການສະຖານທີ່ເກັບສິນຄ້າ</h4>
    </div>

    <!-- Content below header -->
    <div class="pg-content-wrapper">

        <?php if(!empty($msg)): ?>
            <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm rounded-0 mb-4"><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- Form Card (Add Location) -->
        <div class="pg-form-card">
            <div class="pg-form-card-header">
                <h6 class="pg-form-card-title">ເພີ່ມສະຖານທີ່ເກັບໃໝ່</h6>
            </div>
            <div class="pg-form-body">
                <form method="POST" action="form_location.php">
                    <div class="mb-3 text-center">
                        <label class="form-label-custom">ຊື່ສະຖານທີ່ເກັບ</label>
                        <input type="text" name="loc_name" class="form-control-custom" placeholder="ຕົວຢ່າງ : C101, C202" required>
                    </div>
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

        <!-- Table Card (List Locations) -->
        <div class="pg-form-card">
            <div class="pg-form-card-header">
                <h6 class="pg-form-card-title">ລາຍການສະຖານທີ່ເກັບທັງໝົດໃນລະບົບ</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="pg-table w-100" id="locationTable">
                        <thead>
                            <tr>
                                <th style="width:100px; text-align:center;">ລ/ດ</th>
                                <th>ຊື່ສະຖານທີ່ເກັບສິນຄ້າ</th>
                                <th style="width:150px; text-align:center;">ຈັດການ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($locations) > 0): ?>
                                <?php foreach($locations as $i => $loc): ?>
                                <tr>
                                    <td style="text-align:center;" class="row-number-cell"></td>
                                    <td><?= htmlspecialchars($loc['loc_name']) ?></td>
                                    <td style="text-align:center; white-space:nowrap;">
                                        <a href="location_edit.php?id=<?= $loc['loc_id'] ?>" class="btn-edit-icon" title="ແກ້ໄຂ">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                        </a>
                                        <a href="location_delete.php?id=<?= $loc['loc_id'] ?>" class="btn-del-icon" title="ລົບ" onclick="confirmDelete(event, this.href)">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19a2 2 0 002 2h8a2 2 0 002-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align:center; color:#999; padding:40px 0;">
                                        ບໍ່ມີຂໍ້ມູນສະຖານທີ່ເກັບໃນລະບົບ
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="pg-pagination-wrapper">
                <div class="pg-table-footer-info">
                    ຈຳນວນ <?= count($locations) ?> ລາຍການສະຖານທີ່ເກັບໃນລະບົບ
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
    const table = document.getElementById("locationTable");
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
        text: 'ທ່ານຕ້ອງການລົບສະຖານທີ່ນີ້ແທ້ບໍ?',
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