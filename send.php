<?php
// send.php - сохраняем заявки в JSON

declare(strict_types=1);

require_once __DIR__ . '/config.php';

error_reporting(E_ALL);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

// Получаем данные
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');
$file = $_FILES['file'] ?? null;

function fail(string $m): void {
    echo json_encode(['success' => false, 'message' => $m], JSON_UNESCAPED_UNICODE);
    exit;
}

// Антиспам: honeypot, согласие, лимит по IP
if (($_POST['website'] ?? '') !== '') { fail('❌ Ошибка отправки'); }
if (($_POST['consent'] ?? '') !== '1') { fail('❌ Подтвердите согласие на обработку данных'); }
$rl_file = sys_get_temp_dir() . '/rl_' . md5($_SERVER['REMOTE_ADDR'] ?? 'x') . '.json';
$hits = is_file($rl_file) ? (json_decode((string)file_get_contents($rl_file), true) ?: []) : [];
$hits = array_values(array_filter($hits, fn($t) => $t > time() - 3600));
if (count($hits) >= RATE_LIMIT_PER_HOUR) { fail('❌ Слишком много заявок. Попробуйте позже или позвоните.'); }
$hits[] = time();
file_put_contents($rl_file, json_encode($hits), LOCK_EX);

// Валидация
if ($name === '' || $phone === '') {
    echo json_encode([
        'success' => false,
        'message' => '❌ Заполните имя и телефон'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (strlen($name) > 100) {
    echo json_encode([
        'success' => false,
        'message' => '❌ Имя слишком длинное'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (strlen($phone) > 50) {
    echo json_encode([
        'success' => false,
        'message' => '❌ Телефон слишком длинный'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (strlen($message) > 3000) {
    echo json_encode([
        'success' => false,
        'message' => '❌ Сообщение слишком длинное'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Создаём папки если нет
foreach ([ORDERS_DIR, UPLOADS_DIR, BACKUP_DIR] as $folder) {
    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }
}

// Генерируем ID заявки
$order_id = 'order_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));
$timestamp = date('Y-m-d H:i:s');

// Обрабатываем файл если есть
$file_info = null;

if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'success' => false,
            'message' => '❌ Ошибка загрузки файла'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        echo json_encode([
            'success' => false,
            'message' => '❌ Файл слишком большой. Максимум 15 МБ'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    $allowed_ext = [
        'jpg', 'jpeg', 'png', 'webp',
        'pdf',
        'zip', 'rar',
        'stl', 'step', 'stp', 'obj'
    ];

    if (!in_array($file_ext, $allowed_ext, true)) {
        echo json_encode([
            'success' => false,
            'message' => '❌ Недопустимый тип файла'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) { fail('❌ Файл не является изображением'); }
    }

    $month_dir = UPLOADS_DIR . '/' . date('Y-m');

    if (!is_dir($month_dir)) {
        mkdir($month_dir, 0755, true);
    }

    $original_name = basename($file['name']);
    $safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $original_name);
    $new_filename = $order_id . '_' . $safe_filename;

    $file_path = $month_dir . '/' . $new_filename;

    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        echo json_encode([
            'success' => false,
            'message' => '❌ Не удалось сохранить файл'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $file_info = [
        'name' => $original_name,
        'path' => 'uploads/' . date('Y-m') . '/' . $new_filename,
        'size' => $file['size'],
        'type' => $file['type'] ?? ''
    ];
}

// 4. Формируем данные заявки
$order_data = [
    'id' => $order_id,
    'timestamp' => $timestamp,
    'name' => $name,
    'phone' => $phone,
    'message' => $message,
    'file' => $file_info,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'status' => 'new'
];

// 5. Сохраняем в JSON файл
$json_file = ORDERS_DIR . '/' . $order_id . '.json';
$json_content = json_encode($order_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if (file_put_contents($json_file, $json_content)) {
    // 6. Также сохраняем в общий лог (для простоты просмотра)
    $log_entry = date('H:i:s d.m.Y') . " | $name | $phone | " . substr($message, 0, 50) . "\n";
    file_put_contents(__DIR__ . '/orders.log', $log_entry, FILE_APPEND);
    
    // 7. Резервная копия
    copy($json_file, BACKUP_DIR . '/' . $order_id . '.json');
    
    // Telegram-уведомление
    if (TELEGRAM_BOT_TOKEN !== '' && TELEGRAM_CHAT_ID !== '') {
        $text = "🆕 Новая заявка $order_id\n👤 $name\n📞 $phone\n💬 " . mb_substr($message, 0, 500)
              . ($file_info ? "\n📎 " . $file_info['name'] : '');
        @file_get_contents('https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage?' . http_build_query([
            'chat_id' => TELEGRAM_CHAT_ID, 'text' => $text
        ]));
    }

    // 8. Отправляем успешный ответ
    echo json_encode([
    'success' => true,
    'message' => '✅ Заявка принята! Я свяжусь с вами в течение 24 часов.',
    'order_id' => $order_id,
    'file_uploaded' => ($file_info !== null)
    ], JSON_UNESCAPED_UNICODE);
} else {
    // Если не удалось сохранить JSON
    echo json_encode([
        'success' => false,
        'message' => '❌ Ошибка сохранения заявки. Попробуйте ещё раз.'
    ], JSON_UNESCAPED_UNICODE);
}
?>