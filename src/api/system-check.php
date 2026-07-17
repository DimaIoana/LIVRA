<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../backend/SystemCheck.php';

try {
    $checker = new SystemCheck(__DIR__ . '/../database/db_connection.php');
    $checks = $checker->runAll();

    $all_ok = true;
    foreach ($checks as $check) {
        if (!$check['ok']) {
            $all_ok = false;
            break;
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'all_ok' => $all_ok,
            'checked_at' => date('d.m.Y H:i:s'),
            'checks' => $checks,
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
