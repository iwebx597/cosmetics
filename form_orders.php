<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

require 'cmt_db.php'; // ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ

// 📌 ດຶງຂໍ້ມູນໃບບິນສັ່ງຊື້ທັງໝົດ
try {
    $sql = "SELECT o.*, s.sup_name, e.emp_fname, e.emp_lname,
            (SELECT SUM(d.total) FROM order_detail d WHERE d.order_id = o.order_id) as grand_total
            FROM orders o
            LEFT JOIN supplier s ON o.sup_id = s.sup_id
            LEFT JOIN employee e ON o.emp_id = e.emp_id
            ORDER BY o.order_id DESC";
    $orders = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Query Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <link href="style/fonts.css" rel="stylesheet">
    <title>ລາຍການສັ່ງຊື້ສິນຄ້າ</title>
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
            justify-content: space-between;
        }

        .pg-header-title {
            color: #1a1a1a !important;
            font-weight: 700 !important;
            font-size: 16px !important;
            margin: 0 !important;
        }

        /* Add Order Button (Crimson) */
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

        /* Search card (dark red) */
        .pg-search-card {
            background: #7A1530 !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 16px 20px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            margin-bottom: 0 !important;
        }

        .pg-search-label {
            font-weight: 700 !important;
            font-size: 15px !important;
            color: #ffffff !important;
        }

        .pg-search-group {
            display: flex !important;
            gap: 0 !important;
        }

        .pg-search-input {
            border-radius: 0 !important;
            border: 1px solid #ccc !important;
            padding: 8px 14px !important;
            font-size: 14px !important;
            width: 260px !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
        }

        .pg-search-input:focus {
            outline: none !important;
            box-shadow: none !important;
            border-color: #C9956A !important;
        }

        .pg-search-btn {
            background-color: #C9956A !important;
            color: #1a1a1a !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 8px 20px !important;
            font-weight: 700 !important;
            font-size: 14px !important;
            cursor: pointer !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
        }

        .pg-search-btn:hover {
            background-color: #B88458 !important;
        }

        /* Main table card */
        .pg-table-card {
            border: none !important;
            border-radius: 0 !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
            background-color: #ffffff !important;
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

        /* Style for sortable columns */
        th.sortable {
            cursor: pointer;
            position: relative;
        }
        th.sortable:hover {
            background-color: #B88458 !important;
        }
        th.sortable::after { content: ' ↕'; opacity: 0.5; font-size: 0.75rem; }
        th.sort-asc::after  { content: ' ▲'; opacity: 1; }
        th.sort-desc::after { content: ' ▼'; opacity: 1; }

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
            padding: 12px 14px !important;
            border: none !important;
            white-space: nowrap !important;
        }

        .pg-table tbody td {
            padding: 12px 14px !important;
            font-size: 15px !important;
            font-weight: bold !important;
            color: #333333 !important;
            border-bottom: 1px solid #EAEAEA !important;
            background-color: #ffffff !important;
            vertical-align: middle !important;
            white-space: nowrap !important;
        }

        /* Badges styled flat */
        .badge-flat {
            border-radius: 0 !important;
            padding: 4px 8px !important;
            font-weight: 700 !important;
            font-size: 12px !important;
        }

        /* Action buttons */
        .btn-view-icon {
            background: none !important;
            border: none !important;
            padding: 4px 6px !important;
            cursor: pointer !important;
            color: #555 !important;
            font-size: 18px !important;
            text-decoration: none !important;
            display: inline-block !important;
        }
        .btn-view-icon:hover { color: #7A1530 !important; }

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
        <h4 class="pg-header-title">ຈັດການການສັ່ງຊື້ສິນຄ້າ</h4>
        <a href="orders_add.php" class="pg-add-btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            ສ້າງໃບບິນສັ່ງຊື້ໃໝ່
        </a>
    </div>

    <!-- Content below header -->
    <div class="pg-content-wrapper">

        <!-- Search Card (Crimson) -->
        <div class="pg-search-card mb-4">
            <div class="pg-search-label">ຄົ້ນຫາຂໍ້ມູນການສິ່ງຊື້ໃນລະບົບ</div>
            <div class="pg-search-group">
                <input type="text" id="searchInput" class="pg-search-input" placeholder="ຄົ້ນຫາ..." onkeyup="filterTable()">
                <button class="pg-search-btn" onclick="filterTable()">ຄົ້ນຫາ</button>
            </div>
        </div>

        <!-- Card for History List -->
        <div class="pg-table-card">
            
            <div class="table-responsive-custom">
                <table class="pg-table w-100 mb-0" id="ordersTable">
                    <thead>
                        <tr>
                            <th style="width:70px; text-align:center;">ລ/ດ</th>
                            <th class="sortable" onclick="sortTable(1)">ເລກທີໃບບິນ</th>
                            <th class="sortable" onclick="sortTable(2)">ຜູ້ສະໜອງ (Supplier)</th>
                            <th class="sortable" onclick="sortTable(3)">ວັນທີສັ່ງຊື້</th>
                            <th class="sortable" onclick="sortTable(4)">ກຳນົດສົ່ງ</th>
                            <th class="sortable" onclick="sortTable(5)">ມູນຄ່າລວມ</th>
                            <th class="sortable" style="text-align:center;" onclick="sortTable(6)">ສະຖານະ</th>
                            <th class="sortable" onclick="sortTable(7)">ຜູ້ບັນທຶກ</th>
                            <th style="width:120px; text-align:center;">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($orders) > 0): ?>
                            <?php foreach($orders as $i => $o): ?>
                            <tr>
                                <td style="text-align:center;" class="row-number-cell"></td>
                                <td style="color: #1a1a1a;"><?= htmlspecialchars($o['order_no']) ?></td>
                                <td style="color: #1a1a1a;"><?= htmlspecialchars($o['sup_name'] ?? 'ບໍ່ລະບຸ') ?></td>
                                <td style="color: #1a1a1a;" data-sort="<?= $o['order_date'] ?>">
                                    <?= date('d/m/Y', strtotime($o['order_date'])) ?>
                                </td>
                                <td style="color: #1a1a1a;" data-sort="<?= $o['expected_date'] ?>">
                                    <?= date('d/m/Y', strtotime($o['expected_date'])) ?>
                                </td>
                                <td style="color: #1a1a1a;" data-sort="<?= $o['grand_total'] ?>">
                                    <?= number_format($o['grand_total'] ?? 0, 2) ?> ₭
                                </td>
                                <td style="text-align:center;" data-sort="<?= $o['status'] ?>">
                                    <?php if($o['status'] == 'Pending' || $o['status'] == 'ລໍຖ້າກວດສອບ'): ?>
                                        <span class="badge badge-flat bg-warning text-dark">⏳ ລໍຖ້າສົ່ງເຄື່ອງ</span>
                                    <?php elseif($o['status'] == 'Completed' || $o['status'] == 'ສຳເລັດ'): ?>
                                        <span class="badge badge-flat bg-success text-white">✅ ນຳເຂົ້າສາງແລ້ວ</span>
                                    <?php else: ?>
                                        <span class="badge badge-flat bg-secondary text-white"><?= htmlspecialchars($o['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: #1a1a1a;"><?= htmlspecialchars($o['emp_fname'] . " " . $o['emp_lname']) ?></td>
                                <td style="text-align:center; white-space:nowrap;">
                                    <a href="orders_view.php?id=<?= $o['order_id'] ?>" class="btn-view-icon" title="ເບິ່ງ">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                    </a>
                                    <a href="orders_delete.php?id=<?= $o['order_id'] ?>" class="btn-del-icon" title="ລົບ" onclick="confirmDelete(event, this.href)">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19a2 2 0 002 2h8a2 2 0 002-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align:center; color:#999; padding:40px 0; font-weight:bold;">
                                    ❌ ບໍ່ມີປະຫວັດການສັ່ງຊື້ໃນລະບົບ
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer / Pagination Wrapper -->
            <div class="pg-pagination-wrapper">
                <div class="pg-table-footer-info" id="tableSummaryInfo">
                    ຈຳນວນ <?= count($orders) ?> ລາຍການທັງໝົດໃນລະບົບ
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
    const table = document.getElementById("ordersTable");
    const tbody = table.tBodies[0];
    const allRows = Array.from(tbody.rows);
    const input = document.getElementById("searchInput").value.toLowerCase();

    // Filter rows based on search input
    const filteredRows = allRows.filter(row => {
        // Exclude the 'no data found' row from being search-filtered if it exists
        if (row.cells.length === 1 && row.cells[0].colSpan > 1) return false;
        
        const text = row.textContent.toLowerCase();
        return text.includes(input);
    });

    const totalItems = filteredRows.length;
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

    filteredRows.forEach((row, index) => {
        // Dynamic row number
        const numCell = row.querySelector(".row-number-cell");
        if (numCell) {
            numCell.textContent = index + 1;
        }

        if (index >= startIndex && index < endIndex) {
            row.style.display = "";
        }
    });

    // Update summary text
    const summaryInfo = document.getElementById("tableSummaryInfo");
    if (summaryInfo) {
        summaryInfo.textContent = `ຈຳນວນ ${totalItems} ລາຍການທັງໝົດໃນລະບົບ`;
    }

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

function filterTable() {
    currentPage = 1; // Reset to page 1 on new search
    renderPagination();
}

// SweetAlert2 Delete Confirmation Dialog
function confirmDelete(event, url) {
    event.preventDefault();
    Swal.fire({
        title: 'ຢືນຢັນການລົບ',
        text: 'ທ່ານຕ້ອງການລົບໃບບິນສັ່ງຊື້ສະບັບນີ້ແທ້ບໍ? (ລາຍການສິນຄ້າທາງໃນຈະຖືກລົບນຳ)',
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

function sortTable(colIndex) {
    const table = document.getElementById("ordersTable");
    const tbody = table.tBodies[0];
    const rows = Array.from(tbody.querySelectorAll("tr"));
    const th = table.querySelectorAll("thead th")[colIndex];
    
    const isAscending = th.classList.contains("sort-asc");
    table.querySelectorAll("thead th").forEach(header => header.classList.remove("sort-asc", "sort-desc"));
    
    const direction = isAscending ? -1 : 1;
    th.classList.add(isAscending ? "sort-desc" : "sort-asc");
    
    rows.sort((rowA, rowB) => {
        const cellA = rowA.cells[colIndex];
        const cellB = rowB.cells[colIndex];
        const valA = cellA.getAttribute("data-sort") || cellA.textContent.trim();
        const valB = cellB.getAttribute("data-sort") || cellB.textContent.trim();
        return valA.localeCompare(valB, 'lo', { numeric: true }) * direction;
    });
    
    rows.forEach(row => tbody.appendChild(row));
    
    renderPagination(); // Re-render pagination after sorting
}

// Initialize pagination on load
document.addEventListener("DOMContentLoaded", function() {
    renderPagination();
});
</script>
</body>
</html>