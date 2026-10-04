<?php
// get_orders.php - возвращает все заявки в JSON
require_once __DIR__ . '/auth.php';
require_admin();
header('Content-Type: application/json');

$orders = [];

// Проверяем папку orders
if (is_dir('orders')) {
    $files = scandir('orders', SCANDIR_SORT_DESCENDING);
    
    foreach ($files as $file) {
        if (strpos($file, '.json') !== false) {
            $file_path = 'orders/' . $file;
            $content = file_get_contents($file_path);
            $order = json_decode($content, true);
            
            if ($order) {
                // Добавляем ID из имени файла если нет в данных
                if (!isset($order['id'])) {
                    $order['id'] = basename($file, '.json');
                }
                $orders[] = $order;
            }
        }
    }
}

echo json_encode($orders, JSON_UNESCAPED_UNICODE);
?>