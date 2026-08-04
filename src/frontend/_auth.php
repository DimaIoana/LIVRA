<?php

/**
 * Autentificarea pentru back office (paginile de administrare).
 *
 * Fiecare pagina de back office incepe cu:
 *     require __DIR__ . '/_auth.php';
 *     cere_admin();
 * si de acolo poate folosi `admin_logat()` ca sa afiseze cine e conectat.
 *
 * Login-ul de aici (user + parola criptata, tabela `users`) e altceva decat
 * login-ul de client din `login.php`, unde clientul isi alege doar numele din
 * lista ca sa cumpere din magazin. Cele doua sesiuni pot exista in paralel.
 *
 * Tot fluxul e server-rendered: formular POST + redirect, fara API/JS.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database/db_connection.php';
require_once __DIR__ . '/../backend/UserRepository.php';

$utilizatori = new UserRepository($pdo);

/** Dupa atatea secunde de inactivitate, sesiunea de back office expira. */
define('ADMIN_INACTIVITATE_MAX', 2 * 60 * 60);

// Aici NU se declara `h()`: fiecare pagina care include _auth.php o are deja pe
// a ei (`_crud_page.php`, `business.php`, `_shop.php` ...), iar o a doua
// declaratie ar opri pagina cu "Cannot redeclare h()".

/**
 * Calea web catre radacina proiectului (ex: /CLAUDE/PRIMUL), dedusa din pozitia
 * fisierului fata de document root. Asa functioneaza redirectul spre login
 * la fel din `index.php` (radacina) si din `src/frontend/*.php`.
 */
function admin_base_url()
{
    $root = str_replace('\\', '/', (string) realpath(__DIR__ . '/../..'));
    $docroot = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));

    if ($docroot !== '' && strpos($root, $docroot) === 0) {
        return rtrim(substr($root, strlen($docroot)), '/');
    }

    return '';
}

function admin_login_url()
{
    return admin_base_url() . '/src/frontend/admin_login.php';
}

/**
 * Utilizatorul de back office conectat (ID, login, Nume, Rol) sau null.
 * Tot aici expira sesiunea daca a stat prea mult neatinsa.
 */
function admin_logat()
{
    if (!isset($_SESSION['admin'])) {
        return null;
    }

    $ultima = $_SESSION['admin_ultima_activitate'] ?? 0;

    if (time() - $ultima > ADMIN_INACTIVITATE_MAX) {
        admin_logout();
        admin_flash_set('Sesiunea a expirat. Autentifica-te din nou.', 'fail');

        return null;
    }

    $_SESSION['admin_ultima_activitate'] = time();

    return $_SESSION['admin'];
}

function admin_login(array $user)
{
    // ID nou de sesiune la fiecare login (protectie session fixation).
    session_regenerate_id(true);

    $_SESSION['admin'] = $user;
    $_SESSION['admin_ultima_activitate'] = time();
}

function admin_logout()
{
    unset($_SESSION['admin'], $_SESSION['admin_ultima_activitate'], $_SESSION['admin_retur']);
}

/**
 * Cere autentificare. Daca nu e nimeni conectat, retine pagina ceruta si trimite
 * la login, ca dupa autentificare sa se intoarca exact unde voia utilizatorul.
 */
function cere_admin()
{
    if (admin_logat()) {
        return;
    }

    $_SESSION['admin_retur'] = $_SERVER['REQUEST_URI'] ?? '';

    header('Location: ' . admin_login_url());
    exit;
}

/**
 * Pagina de unde a fost respins utilizatorul, daca e una sigura de folosit.
 * Acceptam doar cai din proiect ("/CLAUDE/PRIMUL/..."), niciodata un URL
 * complet: altfel formularul de login ar putea fi folosit ca redirect
 * catre alt site.
 */
function admin_retur_url()
{
    $retur = (string) ($_SESSION['admin_retur'] ?? '');
    unset($_SESSION['admin_retur']);

    $base = admin_base_url();

    if ($retur === '' || strpos($retur, '//') !== false || strpos($retur, $base . '/') !== 0) {
        return $base . '/index.php';
    }

    // Nu ne intoarcem chiar pe pagina de login.
    if (strpos($retur, 'admin_login.php') !== false) {
        return $base . '/index.php';
    }

    return $retur;
}

/** Mesaj afisat dupa redirect (delogare, sesiune expirata). */
function admin_flash_set($message, $state)
{
    $_SESSION['admin_flash'] = ['message' => $message, 'state' => $state];
}

function admin_flash_get()
{
    $flash = $_SESSION['admin_flash'] ?? null;
    unset($_SESSION['admin_flash']);

    return $flash;
}

// --- Limitarea incercarilor de ghicire a parolei ---
//
// Dupa ADMIN_INCERCARI_MAX greseli consecutive, contul de pe acest browser
// asteapta ADMIN_BLOCARE_SEC secunde. Tinem numaratoarea in sesiune: e o
// aplicatie locala, nu are rost o tabela pentru asta.

define('ADMIN_INCERCARI_MAX', 5);
define('ADMIN_BLOCARE_SEC', 60);

/** Cate secunde mai are de asteptat; 0 daca poate incerca acum. */
function admin_blocat_secunde()
{
    $incercari = (int) ($_SESSION['admin_incercari'] ?? 0);

    if ($incercari < ADMIN_INCERCARI_MAX) {
        return 0;
    }

    $ramas = ADMIN_BLOCARE_SEC - (time() - (int) ($_SESSION['admin_ultima_incercare'] ?? 0));

    if ($ramas <= 0) {
        admin_reseteaza_incercari();

        return 0;
    }

    return $ramas;
}

function admin_inregistreaza_esec()
{
    $_SESSION['admin_incercari'] = (int) ($_SESSION['admin_incercari'] ?? 0) + 1;
    $_SESSION['admin_ultima_incercare'] = time();
}

function admin_reseteaza_incercari()
{
    unset($_SESSION['admin_incercari'], $_SESSION['admin_ultima_incercare']);
}
