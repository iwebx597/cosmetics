<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}
if ($_SESSION['role'] !== 'admin') {
    echo "<script>
        alert('❌ ທ່ານບໍ່ມີສິດເຂົ້າເຖິງໜ້ານີ້! ສະເພາະ Admin ເທົ່ານັ້ນ.');
        location='form_employee.php';
    </script>";
    exit;
}
require 'cmt_db.php';

if (isset($_GET['id'])) {
    $emp_id = $_GET['id'];

    try {
        // ກວດສອບຫ້າມລົບ ID ຕົວເອງ
        if ($emp_id == $_SESSION['userid']) {
            echo "<script>
                alert('❌ ບໍ່ສາມາດລົບແອັກເຄົ້າ ທີ່ທ່ານກຳລັງໃຊ້ງານຢູ່ໄດ້!');
                location='form_employee.php';
            </script>";
            exit;
        }

        // 📌 1. ປິດການກວດສອບ Foreign Key ຊົ່ວຄາວ ເພື່ອໃຫ້ລົບໄດ້
        $conn->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 🛠️ 2. ສັ່ງລົບຂໍ້ມູນພະນັກງານ
        $sql = "DELETE FROM employee WHERE emp_id = :emp_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['emp_id' => $emp_id]);

        // 📌 3. ເປີດການກວດສອບ Foreign Key ຄືນຄືເກົ່າ ເພື່ອຄວາມປອດໄພ
        $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");

        echo "<script>
            alert('🎉 ລົບຂໍ້ມູນພະນັກງານຮຽບຮ້ອຍແລ້ວ!');
            location='form_employee.php';
        </script>";
        exit;

    } catch (PDOException $e) {
        // ເປີດຄືນກໍລະນີເກີດ Error ລະຫວ່າງເຮັດວຽກ
        $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");
        
        echo "<script>
            alert('❌ ເກີດຂໍ້ຜິດພາດ: " . addslashes($e->getMessage()) . "');
            location='form_employee.php';
        </script>";
        exit;
    }
} else {
    header("Location: form_employee.php");
    exit;
}