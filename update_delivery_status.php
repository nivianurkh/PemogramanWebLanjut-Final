<?php
require_once 'koneksi.php';

$data = json_decode(file_get_contents("php://input"), true);
$order_id = $data['order_id'] ?? '';
$status_payment = $data['status'] ?? '';

if ($order_id && $status_payment === 'Paid') {
    $stmt = $db->prepare("UPDATE delivery_orders 
        SET status_payment = 'Paid', status = 'Being Prepared' 
        WHERE midtrans_order_id = ?");
    $stmt->execute([$order_id]);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'reason' => 'Missing or invalid data']);
}
