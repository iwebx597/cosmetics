<?php
session_start();
// ກວດສອບສິດການເຂົ້າເຖິງ (ຖ້າບໍ່ມີ session ໃຫ້ເດັ້ງກັບໄປໜ້າ login)
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

require 'cmt_db.php';

// ດຶງຂໍ້ມູນພະນັກງານທັງໝົດອອກມາສະແດງ (ເພີ່ມຟີລ image ເຂົ້າໄປໃນ Query)
try {
    $stmt = $conn->query("SELECT emp_id, emp_fname, emp_lname, gender, phone, address, position, username, image FROM employee ORDER BY emp_id ASC");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <link href="style/fonts.css" rel="stylesheet">
    <title>ຈັດການຂໍ້ມູນພະນັກງານ</title>
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

        /* Add Employee Button */
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

        /* Table Card */
        .pg-table-card {
            border: none !important;
            border-radius: 0 !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
            background-color: #ffffff !important;
        }

        /* Horizontal Scrollbar and Non-wrapping Columns */
        .pg-table-scroll {
            overflow-x: auto !important;
            overflow-y: visible !important;
            width: 100% !important;
            display: block !important;
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

        /* Sortable th */
        th.sortable { cursor: pointer; }
        th.sortable:hover { background-color: #B88458 !important; }
        th.sortable::after { content: ' ↕'; opacity: 0.5; font-size: 0.75rem; }
        th.sort-asc::after  { content: ' ▲'; opacity: 1; }
        th.sort-desc::after { content: ' ▼'; opacity: 1; }

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
        <h4 class="pg-header-title">ຈັດການຂໍ້ມູນພະນັກງານ</h4>
        <a href="add_employee.php" class="pg-add-btn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="margin-bottom: 2px;"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            ເພີ່ມພະນັກງານໃໝ່
        </a>
    </div>

    <!-- Content below header -->
    <div class="pg-content-wrapper">

        <!-- Search Card -->
        <div class="pg-search-card mb-4">
            <span class="pg-search-label">ຄົ້ນຫາຂໍ້ມູນພະນັກງານໃນລະບົບ</span>
            <div class="pg-search-group">
                <input type="text" id="searchInput" class="pg-search-input" placeholder="ຄົ້ນຫາຂໍ້ມູນພະນັກງານ" oninput="filterTable()">
                <button class="pg-search-btn" onclick="filterTable()">ຄົ້ນຫາ</button>
            </div>
        </div>

        <!-- Table Card -->
        <div class="pg-table-card">
            <div class="pg-table-scroll">
                <table class="pg-table w-100 mb-0" id="employeeTable">
                    <thead>
                        <tr>
                            <th style="width:70px; text-align:center;">ລ/ດ</th>
                            <th style="width:90px; text-align:center;">ຮູບ</th>
                            <th class="sortable" onclick="sortTable(2)" style="width:120px;">ລະຫັດ</th>
                            <th class="sortable" onclick="sortTable(3)">ຊື່ ແລະ ນາມສະກຸນ</th>
                            <th style="width:80px;">ເພດ</th>
                            <th>ເບີໂທລະສັບ</th>
                            <th>ທີ່ຢູ່ປະຈຸບັນ</th>
                            <th class="sortable" onclick="sortTable(7)">Username</th>
                            <th>ສິດທິ</th>
                            <th style="width:120px; text-align:center;">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($employees) > 0): ?>
                            <?php foreach($employees as $i => $emp): 
                                // ກວດສອບ ແລະ ຕັ້ງຄ່າຮູບພາບພະນັກງານ
                                $has_image = false;
                                $img_src = "";
                                if (!empty($emp['image']) && file_exists("employees/" . $emp['image'])) {
                                    $img_src = "employees/" . $emp['image'];
                                    $has_image = true;
                                } elseif (!empty($emp['image']) && (strpos($emp['image'], 'http') === 0)) {
                                    $img_src = $emp['image'];
                                    $has_image = true;
                                }
                            ?>
                             <tr>
                                 <td style="text-align:center; font-weight:bold;" class="row-number-cell"></td>
                                 <td style="text-align:center;">
                                     <?php if ($has_image): ?>
                                         <img src="<?= $img_src ?>" alt="Employee Image" style="width: 45px; height: 45px; object-fit: cover; border-radius: 0 !important; border: 1px solid #cccccc;">
                                     <?php else: ?>
                                         <div style="width: 45px; height: 45px; background-color: #e0e0e0; display: inline-flex; align-items: center; justify-content: center;">
                                             <svg width="24" height="24" viewBox="0 0 24 24" fill="#666666"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                         </div>
                                     <?php endif; ?>
                                 </td>
                                 <td style="font-weight:bold;" data-sort="<?= htmlspecialchars($emp['emp_id']) ?>"><?= htmlspecialchars($emp['emp_id']) ?></td>
                                 <td style="font-weight:bold;" data-sort="<?= htmlspecialchars($emp['emp_fname'] . " " . $emp['emp_lname']) ?>"><?= htmlspecialchars($emp['emp_fname'] . " " . $emp['emp_lname']) ?></td>
                                 <td style="font-weight:bold;">
                                     <?= $emp['gender'] == 'M' ? 'ຊາຍ' : 'ຍິງ' ?>
                                 </td>
                                 <td style="font-weight:bold;"><?= htmlspecialchars($emp['phone'] ?? '-') ?></td>
                                 <td style="font-weight:bold;"><?= htmlspecialchars($emp['address'] ?? '-') ?></td>
                                 <td style="font-weight:bold; color: #1a1a1a;" data-sort="<?= htmlspecialchars($emp['username'] ?? '') ?>"><?= htmlspecialchars($emp['username'] ?? '-') ?></td>
                                 <td style="font-weight:bold;">
                                     <?php 
                                         if($emp['position'] == 'admin') {
                                             echo 'Admin';
                                         } elseif($emp['position'] == 'warehouse') {
                                             echo 'Warehouse';
                                         } else {
                                             echo 'Sales';
                                         }
                                     ?>
                                 </td>
                                 <td style="text-align:center; white-space:nowrap;">
                                     <a href="employee_edit.php?id=<?= $emp['emp_id'] ?>" class="btn-edit-icon" title="ແກ້ໄຂ">
                                         <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                     </a>
                                     <a href="employee_delete.php?id=<?= $emp['emp_id'] ?>" class="btn-del-icon" title="ລົບ" onclick="confirmDelete(event, this.href)">
                                         <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19a2 2 0 002 2h8a2 2 0 002-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                     </a>
                                 </td>
                              </tr>
                             <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" style="text-align:center; color:#999; padding:40px 0;">
                                    ບໍ່ມີຂໍ້ມູນພະນັກງານໃນລະບົບ
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Wrapper -->
            <div class="pg-pagination-wrapper">
                <div class="pg-table-footer-info">
                    ຈຳນວນ <?= count($employees) ?> ລາຍການພະນັກງານໃນລະບົບ
                </div>
                <div class="pg-pagination" id="paginationControls">
                    <!-- Pagination controls will be rendered here by JS -->
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
    const table = document.getElementById("employeeTable");
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
    const table = document.getElementById("employeeTable");
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
        text: 'ທ່ານຕ້ອງການລົບຂໍ້ມູນພະນັກງານຄົນນີ້ແທ້ບໍ?',
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