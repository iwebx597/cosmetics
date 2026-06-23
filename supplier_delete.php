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
    $sup_id = $_GET['id'];

    try {
        // 🛠️ ສັ່ງລົບຂໍ້ມູນຈາກຕາຕະລາງ supplier ໂດຍໃຊ້ sup_id ຕົງຕາມໂຄງສ້າງຂອງເຈົ້າ
        $sql = "DELETE FROM supplier WHERE sup_id = :sup_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['sup_id' => $sup_id]);

        // ສະແດງແຈ້ງເຕືອນເມື່ອລົບສຳເລັດ
        echo "<script>
            alert('🎉 ລົບຂໍ້ມູນຜູ້ສະໜອງຮຽບຮ້ອຍແລ້ວ!');
            location='form_supplier.php';
        </script>";
        exit;

    } catch (PDOException $e) {
        // ⚠️ ກໍລະນີ Error ເພາະ ID ຜູ້ສະໜອງນີ້ ຖືກນຳໄປຜູກກັບຕາຕະລາງອື່ນຢູ່ (ເຊັ່ນ: ຕາຕະລາງນຳເຂົ້າ ຫຼື ຕາຕະລາງສິນຄ້າ)
        echo "<script>
            alert('❌ ບໍ່ສາມາດລົບໄດ້: ຂໍ້ມູນຜູ້ສະໜອງນີ້ຖືກນຳໄປໃຊ້ໃນລະບົບແລ້ວ!');
            location='form_supplier.php';
        </script>";
        exit;
    }
} else {
    // ຖ້າບໍ່ມີການສົ່ງ ID ມາ ໃຫ້ເດັ້ງກັບໜ້າຫຼັກຈັດການຜູ້ສະໜອງ
    header("Location: form_supplier.php");
    exit;
}
?>