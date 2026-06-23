<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

require 'cmt_db.php'; // ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ cmt_db

if (isset($_GET['id'])) {
    $pro_id = $_GET['id'];

    try {
        // 🔍 1. ດຶງຂໍ້ມູນຮູບພາບອອກມາກ່ອນ ເພື່ອເອົາໄປລົບໄຟລ໌ໃນ Folder
        $stmt_img = $conn->prepare("SELECT pro_img FROM product WHERE pro_id = :pro_id LIMIT 1");
        $stmt_img->execute(['pro_id' => $pro_id]);
        $row = $stmt_img->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // 🗑️ 2. ສັ່ງລົບສິນຄ້າອອກຈາກຕາຕະລາງ product 
            $sql = "DELETE FROM product WHERE pro_id = :pro_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['pro_id' => $pro_id]);

            // 📂 3. ຖ້າລົບໃນ Database ສຳເລັດ ແລະ ມີໄຟລ໌ຮູບຢູ່ແທ້ ໃຫ້ລົບຮູບອອກຈາກ Folder uploads
            if (!empty($row['pro_img']) && file_exists("uploads/" . $row['pro_img'])) {
                unlink("uploads/" . $row['pro_img']);
            }

            echo "<script>
                alert('🎉 ລົບຂໍ້ມູນສິນຄ້າ ແລະ ຮູບພາບຮຽບຮ້ອຍແລ້ວ!');
                location='form_product.php';
            </script>";
            exit;
        } else {
            echo "<script>
                alert('⚠️ ບໍ່ພົບລະຫັດສິນຄ້ານີ້ໃນລະບົບ!');
                location='form_product.php';
            </script>";
            exit;
        }

    } catch (PDOException $e) {
        // ⚠️ ກໍລະນີສິນຄ້ານີ້ຖືກເອົາໄປຜູກ ຫຼື ໃຊ້ໃນຕາຕະລາງອື່ນແລ້ວ (ເຊັ່ນ: ມີການສັ່ງຊື້, ນຳເຂົ້າ, ຫຼື ຈ່າຍອອກ) ມັນຈະຟ້ອງ Foreign Key Constraint
        echo "<script>
            alert('❌ ບໍ່ສາມາດລົບໄດ້: ສິນຄ້ານີ້ມີປະຫວັດການເຄື່ອນໄຫວ (ນຳເຂົ້າ/ຈ່າຍອອກ) ໃນລະບົບແລ້ວ!');
            location='form_product.php';
        </script>";
        exit;
    }
} else {
    header("Location: form_product.php");
    exit;
}
?>