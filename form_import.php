<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

require 'cmt_db.php'; // ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ

// 📌 ດຶງຂໍ້ມູນປະຫວັດການນຳເຂົ້າສິນຄ້າທັງໝົດ
try {
    $sql = "SELECT i.import_id, i.import_date, o.order_no, s.sup_name, e.emp_fname, e.emp_lname,
            (SELECT SUM(d.import_qty * d.cost_price) FROM import_detail d WHERE d.import_id = i.import_id) as grand_total
            FROM import i
            LEFT JOIN orders o ON i.order_id = o.order_id
            LEFT JOIN supplier s ON i.sup_id = s.sup_id
            LEFT JOIN employee e ON i.emp_id = e.emp_id
            ORDER BY i.import_date DESC, i.import_id DESC";
            
    $imports = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <link href="style/fonts.css" rel="stylesheet">
    <title>ປະຫວັດການນຳເຂົ້າສິນຄ້າ</title>
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

        /* Add Import Button (Crimson) */
        .pg-add-btn {
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

        .pg-add-btn:hover {
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

        /* Horizontal Scrollbar and Non-wrapping Columns */
        .table-responsive-custom {
            overflow-x: auto !important;
            width: 100% !important;
            display: block !important;
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
            white-space: nowrap !important;
        }

        /* Action/Details button */
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

        .btn-detail-icon:hover {
            color: #7A1530 !important;
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
        <h4 class="pg-header-title">ຈັດການຮັບສິນຄ້ານຳເຂົ້າສາງ (Stock In)</h4>
        <a href="import_add.php" class="pg-add-btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            ຮັບສິນຄ້ານຳເຂົ້າໃໝ່
        </a>
    </div>

    <!-- Content below header -->
    <div class="pg-content-wrapper">

        <!-- Card for History List -->
        <div class="pg-card">
            <div class="pg-card-header">
                <h6 class="pg-card-title">ປະຫວັດການຮັບເຄື່ອງເຂົ້າສາງທັງໝົດ</h6>
            </div>
            
            <div class="table-responsive-custom">
                <table class="pg-table w-100 mb-0" id="importTable">
                    <thead>
                        <tr>
                            <th style="width:70px; text-align:center;">ລ/ດ</th>
                            <th>ລະຫັດນຳເຂົ້າ</th>
                            <th>ອ້າງອີງໃບບິນ PO</th>
                            <th>ຜູ້ສະໜອງ (Supplier)</th>
                            <th>ວັນທີ-ເວລາ ຮັບເຂົ້າ</th>
                            <th>ມູນຄ່າລວມທີ່ຮັບເຂົ້າ</th>
                            <th style="width:100px; text-align:center;">ລາຍລະອຽด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($imports) > 0): ?>
                            <?php foreach($imports as $i => $im): ?>
                            <tr>
                                <td style="text-align:center;" class="row-number-cell"></td>
                                <td style="color: #1a1a1a;"><?= htmlspecialchars($im['import_id']) ?></td>
                                <td style="color: #1a1a1a;"><?= htmlspecialchars($im['order_no'] ?? 'ຮັບເຂົ້າໂດຍຕົງ') ?></td>
                                <td style="color: #1a1a1a;"><?= htmlspecialchars($im['sup_name'] ?? 'ບໍ່ລະບຸ') ?></td>
                                <td style="color: #1a1a1a;">
                                    <?= date('d/m/Y H:i', strtotime($im['import_date'])) ?>
                                </td>
                                <td style="color: #1a1a1a;">
                                    <?= number_format($im['grand_total'] ?? 0, 2) ?> ₭
                                </td>
                                <td style="text-align:center;">
                                    <a href="import_view.php?id=<?= $im['import_id'] ?>" class="btn-detail-icon" title="ເບິ່ງລາຍລະອຽດ">
                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-8 11c-2.5 0-4.63-1.55-5.5-3.75 1-2.2 3.13-3.75 5.5-3.75s4.5 3.55 5.5 3.75c-1 2.2-3.13 3.75-5.5 3.75zm0-5.5c-1 0-1.75.8-1.75 1.75S11 13 12 13s1.75-.8 1.75-1.75S13 10.5 12 10.5z"/></svg>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; color:#999; padding:40px 0; font-weight:bold;">
                                    ບໍ່ມີປະຫວັດການນຳເຂົ້າສິນຄ້າໃນລະບົບ
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer / Pagination Wrapper -->
            <div class="pg-pagination-wrapper">
                <div class="pg-table-footer-info">
                    ຈຳນວນ <?= count($imports) ?> ລາຍການທັງໝົດໃນລະບົບ
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
    const table = document.getElementById("importTable");
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
</script>
</body>
</html>