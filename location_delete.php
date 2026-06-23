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
    $loc_id = $_GET['id'];

    try {
        // 🛠️ ສັ່ງລົບຂໍ້ມູນຈາກຕາຕະລາງ location
        $sql = "DELETE FROM location WHERE loc_id = :loc_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['loc_id' => $loc_id]);

        // ສະແດງແຈ້ງເຕືອນເມື່ອລົບສຳເລັດ
        echo "<script>
            alert('🎉 ລົບຂໍ້ມູນບ່ອນຈັດເກັບສິນຄ້າຮຽບຮ້ອຍແລ້ວ!');
            location='form_location.php';
        </script>";
        exit;

    } catch (PDOException $e) {
        // ⚠️ ກໍລະນີ Error ເພາະ ID ບ່ອນຈັດເກັບນີ້ ຖືກນຳໄປຜູກກັບຕາຕະລາງສິນຄ້າ (product) ຢູ່
        echo "<script>
            alert('❌ ບໍ່ສາມາດລົບໄດ້: ບ່ອນຈັດເກັບນີ້ຖືກນຳໄປໃຊ້ກັບຂໍ້ມູນສິນຄ້າໃນລະບົບແລ້ວ!');
            location='form_location.php';
        </script>";
        exit;
    }
} else {
    // ຖ້າບໍ່ມີການສົ່ງ ID ມາ ໃຫ້ເດັ້ງກັບໜ້າຫຼັກຈັດການບ່ອນຈັດເກັບ
    header("Location: form_location.php");
    exit;
}
?>