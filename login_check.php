<?php
session_start();
require 'cmt_db.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$sql = "SELECT emp_id, username, password, position 
        FROM employee 
        WHERE username = :username 
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute(['username' => $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {

    // ກວດ password
    if (password_verify($password, $user['password'])) {
/*
        // ກວດ status
        if ($user['status'] != 'active') {
            echo "<script>
                alert('ຜູ້ນໍາໃຊ້ນີ້ຖືກປິດການໃຊ້ງານ');
                location='index.php';
            </script>";
            exit;
        }
*/
        //
        if ($user['position'] == 'warehouse' || $user['position'] == 'sales' || $user['position'] == 'admin') {

            $_SESSION['userid']   = $user['emp_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['position']; // ໃຊ້ role ຄືເກົ່າໃນ session ເພື່ອບໍ່ໃຫ້ກະທົບໜ້າອື່ນ
            $_SESSION['authuser'] = true;

            header("Location: form_dashboard.php");
            exit;

        } else {
            echo "<script>
                alert('ທ່ານບໍ່ມີສິດເຂົ້າໃຊ້ລະບົບ');
                location='index.php';
            </script>";
            exit;
        }

    } else {
        echo "<script>
            alert('ຊື່ ຫຼື ລະຫັດຜ່ານບໍ່ຖືກຕ້ອງ');
            location='index.php';
            </script>";
            exit;
    }

} else {
    echo "<script>
        alert('ຜິດພາດ');
        location='index.php';
    </script>";
    exit;
}