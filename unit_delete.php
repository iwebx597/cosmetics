<?php
session_start();
// ກວດສອບສິດການເຂົ້າເຖິງ (ຖ້າບໍ່ມີ session ໃຫ້ເດັ້ງກັບໄປໜ້າ login)
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

require 'cmt_db.php'; // ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ cmt_db

// ກວດສອບວ່າມີການສົ່ງ ID ມາເພື່ອລົບຫຼືບໍ່
if (isset($_GET['id'])) {
    $unit_id = $_GET['id'];

    try {
        // 🛠️ ສັ່ງລົບຂໍ້ມູນຈາກຕາຕະລາງ unit
        $sql = "DELETE FROM unit WHERE unit_id = :unit_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['unit_id' => $unit_id]);

        // ສະແດງແຈ້ງເຕືອນເມື່ອລົບສຳເລັດ
        echo "<script>
            alert('🎉 ລົບຂໍ້ມູນໜ່ວຍນັບສິນຄ້າຮຽບຮ້ອຍແລ້ວ!');
            location='form_unit.php';
        </script>";
        exit;

    } catch (PDOException $e) {
        // ⚠️ ກໍລະນີ Error ເພາະ ID ໜ່ວຍນັບນີ້ ຖືກນຳໄປຜູກກັບຕາຕະລາງສິນຄ້າ (product) ຢູ່
        echo "<script>
            alert('❌ ບໍ່ສາມາດລົບໄດ້: ໜ່ວຍນັບນີ້ຖືກນຳໄປໃຊ້ກັບຂໍ້ມູນສິນຄ້າໃນລະບົບແລ້ວ!');
            location='form_unit.php';
        </script>";
        exit;
    }
} else {
    // ຖ້າບໍ່ມີການສົ່ງ ID ມາ ໃຫ້ເດັ້ງກັບໜ້າຫຼັກຈັດການໜ່ວຍນັບ
    header("Location: form_unit.php");
    exit;
}
?>