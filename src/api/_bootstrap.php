<?php

/**
 * Pornire comuna pentru endpoint-uri: conexiune, repository-uri,
 * helperi de raspuns JSON si citire input.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../database/db_connection.php';
require_once __DIR__ . '/../backend/ClientRepository.php';
require_once __DIR__ . '/../backend/SoferRepository.php';
require_once __DIR__ . '/../backend/ExpediereRepository.php';
require_once __DIR__ . '/../backend/InventoryRepository.php';

function json_ok($data = null)
{
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

function json_error($message, $status = 400)
{
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

/**
 * Eroare interna neasteptata: detaliul (care poate contine nume de tabele,
 * SQL sau cai de fisiere) merge in log, nu catre client.
 */
function json_server_error($context, Throwable $e)
{
    error_log('[PRIMUL] ' . $context . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());

    json_error('A aparut o eroare interna. Incearca din nou sau verifica log-ul serverului.', 500);
}

function require_method($method)
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        json_error('Metoda permisa: ' . $method, 405);
    }
}

/**
 * Citeste body-ul JSON al cererii.
 */
function json_input()
{
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);

    return is_array($input) ? $input : [];
}

/**
 * Valideaza un ID primit de la client.
 */
function require_id($value)
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($id === false) {
        json_error('ID invalid.');
    }

    return $id;
}
