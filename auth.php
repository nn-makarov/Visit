<?php
declare(strict_types=1);

session_start();

function require_admin(): void
{
    if (empty($_SESSION['admin_logged_in'])) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Access denied'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}