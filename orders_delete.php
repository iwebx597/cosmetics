<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

require 'cmt_db.php'; // ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ

if (isset($_GET['id'])) {
    $order_id = intval($_GET['id']);

    try {
        // ກວດສອບສະຖານະໃບບິນກ່ອນລົບ 
        $check = $conn->prepare("SELECT status FROM orders WHERE order_id = :order_id LIMIT 1");
        $check->execute(['order_id' => $order_id]);
        $row = $check->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // ⚠️ ປ້ອງກັນ: ຖ້າໃບບິນນີ້ນຳເຂົ້າສາງສຳເລັດແລ້ວ (Completed) ບໍ່ຄວນໃຫ້ລົບ ເພາະຈະເຮັດໃຫ້ສະຕັອກເພ້
            if ($row['status'] == 'Completed' || $row['status'] == 'ສຳເລັດ') {
                echo "<script>
                    alert('❌ ບໍ່ສາມາດລົບໄດ້: ໃບບິນນີ້ໄດ້ທຳການນຳເຂົ້າສາງສຳເລັດແລ້ວ!');
                    location='form_orders.php';
                </script>";
                exit;
            }

            // 🗑️ ສັ່ງລົບໃບບິນ (ລາຍການໃນ order_detail ຈະຫາຍໄປນຳດ້ວຍ ON DELETE CASCADE)
            $sql = "DELETE FROM orders WHERE order_id = :order_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['order_id' => $order_id]);

            echo "<script>
                alert('🎉 ລົບໃບບິນສັ່ງຊື້ສິນຄ້າຮຽບຮ້ອຍແລ້ວ!');
                location='form_orders.php';
            </script>";
            exit;
        } else {
            echo "<script>alert('⚠️ ບໍ່ພົບໃບບິນນີ້ໃນລະບົບ!'); location='form_orders.php';</script>";
            exit;
        }

    } catch (PDOException $e) {
        echo "<script>
            alert('❌ ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage() . "');
            location='form_orders.php';
        </script>";
        exit;
    }
} else {
    header("Location: form_orders.php");
    exit;
}
?>