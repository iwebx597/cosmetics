<?php
session_start();
require 'cmt_db.php';

header('Content-Type: application/json');

if (isset($_GET['pro_id'])) {
    $pro_id = trim($_GET['pro_id']);
    try {
        // ດຶງຂໍ້ມູນຈຳນວນຄັງເຫຼືອ (qty) ແລະ ລາຄາ (price) ຈາກຕາຕະລາງ product ຂອງເຈົ້າ
        $stmt = $conn->prepare("SELECT qty, price, unit_id FROM product WHERE pro_id = :pro_id LIMIT 1");
        $stmt->execute(['pro_id' => $pro_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            echo json_encode([
                'status' => 'success',
                'qty' => intval($product['qty']),
                'price' => floatval($product['price'])
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Product not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No pro_id provided']);
}