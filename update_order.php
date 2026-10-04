<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';
require_admin();
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id = (string)($data['id'] ?? '');
$status = (string)($data['status'] ?? 'processed');

if (!preg_match('/^order_[0-9]{8}_[0-9]{6}_[0-9a-f]{6}$|^order_[0-9]+_[0-9]+$/', $id) || !in_array($status, ['new', 'processed'], true)) {
    echo json_encode(['success' => false, 'message' => 'Bad request']);
    exit;
}
$path = ORDERS_DIR . "/$id.json";
$order = is_file($path) ? json_decode(file_get_contents($path), true) : null;
if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Not found']);
    exit;
}
$order['status'] = $status;
file_put_contents($path, json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
echo json_encode(['success' => true]);
