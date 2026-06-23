<?php
//session_start();

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit;
    }
}

function requireRole($roles = []) {
    if (!in_array($_SESSION['role'], $roles)) {
        echo "<script>
                alert('❌ ທ່ານບໍ່ມີສິດເຂົ້າໜ້ານີ້');
                location='form_dashboard.php';
            </script>";  
        exit;
    }
}