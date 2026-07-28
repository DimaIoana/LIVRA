<?php

/**
 * Bootstrap comun pentru paginile de magazin (magazin.php, cos.php):
 * sesiune, conexiune, repository si helperi pentru cosul din sesiune.
 *
 * Cosul traieste in $_SESSION['cosuri'][ClientID] ca harta Product_ID => cantitate,
 * deci fiecare client are cosul lui chiar daca se logheaza mai multi pe acelasi
 * browser. Fara client logat nu exista cos.
 * Tot fluxul e server-rendered: formulare POST + redirect, fara API/JS.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cosul vechi era comun pe sesiune (vizibil la orice client care se loga dupa).
// Il stergem la prima incarcare, ca sesiunile deja deschise sa nu ramana cu el.
unset($_SESSION['cos']);

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

/** Cosul clientului logat: Product_ID => cantitate. Gol daca nu e nimeni logat. */
function cos_get()
{
    $client = client_logat();
    if (!$client) {
        return [];
    }

    $cos = $_SESSION['cosuri'][$client['id']] ?? null;

    return is_array($cos) ? $cos : [];
}

function cos_set(array $cos)
{
    $client = client_logat();
    if (!$client) {
        return;
    }

    $_SESSION['cosuri'][$client['id']] = $cos;
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
    $client = client_logat();
    if ($client) {
        unset($_SESSION['cosuri'][$client['id']]);
    }
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

// --- Autentificare client (login usor: alege numele din lista, fara parola) ---

/** Clientul logat (id + nume) sau null daca nu e nimeni logat. */
function client_logat()
{
    if (isset($_SESSION['client_id'])) {
        return ['id' => (int) $_SESSION['client_id'], 'nume' => $_SESSION['client_nume'] ?? ''];
    }

    return null;
}

function client_login($id, $nume)
{
    // ID nou de sesiune la fiecare login (protectie session fixation).
    session_regenerate_id(true);

    $_SESSION['client_id'] = (int) $id;
    $_SESSION['client_nume'] = $nume;
}

function client_logout()
{
    unset($_SESSION['client_id'], $_SESSION['client_nume']);
}

/** Cere autentificare: daca nu e niciun client logat, trimite la login. */
function cere_login()
{
    if (!client_logat()) {
        header('Location: login.php');
        exit;
    }
}
