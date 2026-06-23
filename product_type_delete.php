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
    $type_id = $_GET['id'];

    try {
        // 🛠️ ສັ່ງລົບຂໍ້ມູນຈາກຕາຕະລາງ product_type ໂດຍໃຊ້ type_id
        $sql = "DELETE FROM product_type WHERE type_id = :type_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['type_id' => $type_id]);

        echo "<script>
            alert('🎉 ລົບຂໍ້ມູນປະເພດສິນຄ້າຮຽບຮ້ອຍແລ້ວ!');
            location='form_product_type.php';
        </script>";
        exit;

    } catch (PDOException $e) {
        // ⚠️ ກໍລະນີເກີດ Error ເພາະ ID ນີ້ ຖືກນຳໄປຜູກກັບຕາຕະລາງສິນຄ້າ (product) ຢູ່
        echo "<script>
            alert('❌ ບໍ່ສາມາດລົບໄດ້: ປະເພດສິນຄ້ານີ້ຖືກນຳໄປໃຊ້ກັບຂໍ້ມູນສິນຄ້າໃນລະບົບແລ້ວ!');
            location='form_product_type.php';
        </script>";
        exit;
    }
} else {
    header("Location: form_product_type.php");
    exit;
}
?>