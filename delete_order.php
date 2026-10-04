<?php
// delete_order.php - удаление заявки
require_once __DIR__ . '/auth.php';
require_admin();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$order_id = (string)($data['id'] ?? '');
if (!preg_match('/^order_[0-9]{8}_[0-9]{6}_[0-9a-f]{6}$|^order_[0-9]+_[0-9]+$/', $order_id)) {
    echo json_encode(['success' => false]);
    exit;
}

$file_path = "orders/{$order_id}.json";
if ($order_id && file_exists($file_path)) {
    unlink($file_path);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>