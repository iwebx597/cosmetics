<?php
session_start();
require 'cmt_db.php';

header('Content-Type: application/json');

if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    try {
        // ດຶງຂໍ້ມູນ sup_id ຈາກ orders
        $stmt_o = $conn->prepare("SELECT sup_id FROM orders WHERE order_id = :order_id LIMIT 1");
        $stmt_o->execute(['order_id' => $order_id]);
        $order = $stmt_o->fetch(PDO::FETCH_ASSOC);

        // ດຶງລາຍການສິນຄ້າໃນ order_detail
        $stmt_d = $conn->prepare("SELECT d.*, p.pro_name, p.pro_brand, u.unit_name 
                                  FROM order_detail d
                                  LEFT JOIN product p ON d.pro_id = p.pro_id
                                  LEFT JOIN unit u ON d.unit_id = u.unit_id
                                  WHERE d.order_id = :order_id");
        $stmt_d->execute(['order_id' => $order_id]);
        $details = $stmt_d->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'sup_id' => $order ? $order['sup_id'] : '',
            'items' => $details
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No order_id provided']);
}