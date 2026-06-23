<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

require 'cmt_db.php';

try {
    $sql_prod = "SELECT p.*, t.type_name, u.unit_name, l.loc_name 
                 FROM product p
                 LEFT JOIN product_type t ON p.type_id = t.type_id
                 LEFT JOIN unit u ON p.unit_id = u.unit_id
                 LEFT JOIN location l ON p.loc_id = l.loc_id
                 ORDER BY p.pro_id DESC";
    $products = $conn->query($sql_prod)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Query Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <link href="style/fonts.css" rel="stylesheet">
    <title>ຈັດການຂໍ້ມູນສິນຄ້າ</title>
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

        /* Remove navbar margin-bottom so header is flush */
        .custom-navbar {
            margin-bottom: 0 !important;
        }

        /* Page Header Title (Cyan gradient, same as dashboard) */
        .pg-header-box {
            background: linear-gradient(90deg, #ffffffff 0%, #C9956A 100%) !important;
            height: 60px !important;
            padding: 0 24px !important;
            margin-bottom: 0 !important;
            border-radius: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
        }

        .pg-header-title {
            color: #1a1a1a !important;
            font-weight: 700 !important;
            font-size: 16px !important;
            margin: 0 !important;
        }

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
            transition: background 0.15s, color 0.15s !important;
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
        }

        /* Product image */
        .prod-img-thumb {
            width: 44px !important;
            height: 44px !important;
            object-fit: cover !important;
            border-radius: 4px !important;
            border: 1px solid #ddd !important;
        }

        /* Placeholder image */
        .prod-img-placeholder {
            width: 44px !important;
            height: 44px !important;
            background-color: #e0e0e0 !important;
            border-radius: 4px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
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

        /* Table footer summary */
        .pg-table-footer {
            background-color: #ffffff !important;
            padding: 12px 20px !important;
            font-size: 14px !important;
            color: #333333 !important;
            border-top: 2px solid #A0A0A0 !important;
            font-weight: bold !important;
        }

        /* Sortable th */
        th.sortable { cursor: pointer; }
        th.sortable:hover { background-color: #B88458 !important; }
        th.sortable::after { content: ' ↕'; opacity: 0.5; font-size: 0.75rem; }
        th.sort-asc::after  { content: ' ▲'; opacity: 1; }
        th.sort-desc::after { content: ' ▼'; opacity: 1; }

        /* Prevent text wrapping in table cells */
        .pg-table td, .pg-table th {
            white-space: nowrap !important;
        }

        /* Styled horizontal scrollbar */
        .pg-table-scroll {
            overflow-x: auto !important;
            overflow-y: visible !important;
        }
        .pg-table-scroll::-webkit-scrollbar {
            height: 8px !important;
        }
        .pg-table-scroll::-webkit-scrollbar-track {
            background: #f1f1f1 !important;
            border-radius: 4px !important;
        }
        .pg-table-scroll::-webkit-scrollbar-thumb {
            background: #C9956A !important;
            border-radius: 4px !important;
        }
        .pg-table-scroll::-webkit-scrollbar-thumb:hover {
            background: #B88458 !important;
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
        <h4 class="pg-header-title">ຈັດການຂໍ້ມູນສິນຄ້າ</h4>
        <a href="product_add.php" class="pg-add-btn">
            ເພີ່ມສິນຄ້າໃໝ່
        </a>
    </div>

    <!-- Content below header -->
    <div class="pg-content-wrapper">

        <!-- Search Card -->
        <div class="pg-search-card mb-4">
            <span class="pg-search-label">ຄົ້ນຫາຂໍ້ມູນສິນຄ້າໃນລະບົບ</span>
            <div class="pg-search-group">
                <input type="text" id="searchInput" class="pg-search-input" placeholder="ຄົ້ນຫາຂໍ້ມູນສິນຄ້າ" oninput="filterTable()">
                <button class="pg-search-btn" onclick="filterTable()">ຄົ້ນຫາ</button>
            </div>
        </div>

        <!-- Product Table -->
        <div class="pg-table-card">
            <div class="pg-table-scroll">
                <table class="pg-table" style="min-width:900px; width:100%;" id="productTable">
                    <thead>
                        <tr>
                            <th style="width:50px; text-align:center;">ລ/ດ</th>
                            <th style="width:60px; text-align:center;">ຮູບສິນຄ້າ</th>
                            <th class="sortable" onclick="sortTable(2)" style="width:110px;">ລະຫັດສິນຄ້າ</th>
                            <th class="sortable" onclick="sortTable(3)">ຊື່ສິນຄ້າ</th>
                            <th class="sortable" onclick="sortTable(4)" style="width:110px;">ປະເພດ</th>
                            <th class="sortable" onclick="sortTable(5)" style="width:120px;">ບ່ອນຈັດເກັບ</th>
                            <th class="sortable" onclick="sortTable(6)" style="width:130px; text-align:center;">ຈຳນວນຄ່ງເຫຼືອ</th>
                            <th style="width:80px; text-align:center;">ໜ່ວຍ</th>
                            <th class="sortable" onclick="sortTable(8)" style="width:140px; text-align:right; padding-right:20px;">ລາຄາຕໍ່ໜ່ວຍ</th>
                            <th style="width:90px; text-align:center;">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($products) > 0): ?>
                            <?php foreach($products as $i => $p): ?>
                            <tr>
                                <!-- Row number -->
                                <td style="text-align:center; font-weight:bold;" class="row-number-cell"></td>
                                <!-- Image -->
                                <td style="text-align:center;">
                                    <?php if(!empty($p['pro_img']) && file_exists("uploads/".$p['pro_img'])): ?>
                                        <img src="uploads/<?= $p['pro_img'] ?>" class="prod-img-thumb">
                                    <?php else: ?>
                                        <div class="prod-img-placeholder">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="#aaaaaa">
                                                <rect x="3" y="3" width="18" height="18" rx="2" stroke="#aaa" stroke-width="1.5" fill="none"/>
                                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                                <path d="M21 15l-5-5L5 21" stroke="#aaa" stroke-width="1.5" fill="none"/>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <!-- ID -->
                                <td style="font-weight:bold;" data-sort="<?= htmlspecialchars($p['pro_id']) ?>"><?= htmlspecialchars($p['pro_id']) ?></td>
                                <!-- Name -->
                                <td style="font-weight:bold;" data-sort="<?= htmlspecialchars($p['pro_name']) ?>"><?= htmlspecialchars($p['pro_name']) ?></td>
                                <!-- Type -->
                                <td data-sort="<?= htmlspecialchars($p['type_name'] ?? '') ?>"><?= htmlspecialchars($p['type_name'] ?? '-') ?></td>
                                <!-- Location -->
                                <td data-sort="<?= htmlspecialchars($p['loc_name'] ?? '') ?>"><?= htmlspecialchars($p['loc_name'] ?? '-') ?></td>
                                <!-- Qty -->
                                <td style="text-align:center; font-weight:bold;" data-sort="<?= $p['qty'] ?>"><?= number_format($p['qty']) ?></td>
                                <!-- Unit -->
                                <td style="text-align:center;"><?= htmlspecialchars($p['unit_name'] ?? '-') ?></td>
                                <!-- Price -->
                                <td style="text-align:right; padding-right:20px; font-weight:bold; color:#333;" data-sort="<?= $p['price'] ?>"><?= number_format($p['price'], 2) ?> ₭</td>
                                <!-- Actions -->
                                <td style="text-align:center; white-space:nowrap;">
                                    <a href="product_edit.php?id=<?= $p['pro_id'] ?>" class="btn-edit-icon" title="ແກ້ໄຂ">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                    </a>
                                    <a href="product_delete.php?id=<?= $p['pro_id'] ?>" class="btn-del-icon" title="ລົບ" onclick="confirmDelete(event, this.href)">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19a2 2 0 002 2h8a2 2 0 002-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10" style="text-align:center; color:#999; padding:40px 0;">ບໍ່ມີຂໍ້ມູນສິນຄ້າໃນລະບົບ</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="pg-pagination-wrapper">
                <div class="pg-table-footer-info">
                    ຈຳນວນ <?= count($products) ?> ລາຍການສິນຄ້າໃນລະບົບ
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
    const table = document.getElementById("productTable");
    const tbody = table.tBodies[0];
    const allRows = Array.from(tbody.rows);
    const input = document.getElementById("searchInput").value.toLowerCase();

    // Filter rows based on search input
    const filteredRows = allRows.filter(row => {
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

// Sort table by column index
function sortTable(colIndex) {
    const table = document.getElementById("productTable");
    const tbody = table.tBodies[0];
    const rows = Array.from(tbody.querySelectorAll("tr"));
    const th = table.querySelectorAll("thead th")[colIndex];

    const isAscending = th.classList.contains("sort-asc");

    table.querySelectorAll("thead th").forEach(h => h.classList.remove("sort-asc", "sort-desc"));

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

// Live search filter
function filterTable() {
    currentPage = 1; // Reset to page 1 on new search
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
        text: 'ທ່ານຕ້ອງການລົບສິນຄ້ານີ້ແທ້ບໍ?',
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