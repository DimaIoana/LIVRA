<?php

require_once __DIR__ . '/_crud.php';

require_method('GET');

try {
    json_ok((new ExpediereRepository($pdo))->getOptiuni());
} catch (Throwable $e) {
    json_server_error('citire optiuni expedieri', $e);
}
