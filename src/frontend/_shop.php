<?php

/**
 * Bootstrap comun pentru paginile de magazin (magazin.php, cos.php):
 * sesiune, conexiune, repository si helperi pentru cosul din sesiune.
 *
 * Cosul traieste in $_SESSION['cos'] ca harta Product_ID => cantitate.
 * Tot fluxul e server-rendered: formulare POST + redirect, fara API/JS.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database/db_connection.php';
require_once __DIR__ . '/../backend/MagazinRepository.php';

$magazin = new MagazinRepository($pdo);

/** Scapa text pentru HTML. */
function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** 1234.5 -> 1.234,50 lei */
function lei($value)
{
    return number_format((float) $value, 2, ',', '.') . ' lei';
}

/**
 * Converteste valoarea din coloana `poze` intr-un URL web utilizabil in <img>.
 * In DB pot fi cai absolute de disc (C:\...\poze\x.webp) sau doar numele
 * fisierului; browserul are nevoie de un URL, nu de o cale de disc. Folderul
 * `poze/` e la radacina proiectului, adica doua nivele peste src/frontend/.
 */
function poza_url($stored)
{
    $stored = trim((string) $stored);
    if ($stored === '') {
        return '';
    }

    $file = basename(str_replace('\\', '/', $stored));

    return '../../poze/' . rawurlencode($file);
}

/** Cosul curent: Product_ID => cantitate. */
function cos_get()
{
    return isset($_SESSION['cos']) && is_array($_SESSION['cos']) ? $_SESSION['cos'] : [];
}

function cos_set(array $cos)
{
    $_SESSION['cos'] = $cos;
}

/** Numarul total de bucati din cos (pentru indicatorul din antet). */
function cos_bucati()
{
    return array_sum(cos_get());
}

/** Adauga cantitati in cos (harta cod => cantitate); le insumeaza pe cele existente. */
function cos_adauga(array $adaugari)
{
    $cos = cos_get();

    foreach ($adaugari as $code => $qty) {
        $qty = filter_var($qty, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($code === '' || $qty === false) {
            continue;
        }

        $cos[$code] = ($cos[$code] ?? 0) + $qty;
    }

    cos_set($cos);
}

/** Seteaza cantitatea exacta pentru un produs; 0 sau invalid il scoate din cos. */
function cos_seteaza($code, $qty)
{
    $cos = cos_get();
    $qty = filter_var($qty, FILTER_VALIDATE_INT);

    if ($qty === false || $qty <= 0) {
        unset($cos[$code]);
    } else {
        $cos[$code] = $qty;
    }

    cos_set($cos);
}

function cos_scoate($code)
{
    $cos = cos_get();
    unset($cos[$code]);
    cos_set($cos);
}

function cos_goleste()
{
    unset($_SESSION['cos']);
}

/** Pune un mesaj care va fi aratat dupa redirect. */
function shop_flash_set($message, $state)
{
    $_SESSION['shop_flash'] = ['message' => $message, 'state' => $state];
}

function shop_flash_get()
{
    $flash = $_SESSION['shop_flash'] ?? null;
    unset($_SESSION['shop_flash']);

    return $flash;
}
