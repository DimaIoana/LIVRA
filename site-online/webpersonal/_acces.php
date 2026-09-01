<?php

/**
 * Poarta de acces a site-ului personal.
 *
 * Se include pe prima linie din `index.php`, inainte de orice iesire:
 *     <?php require __DIR__ . '/_acces.php'; ?>
 *
 * Daca vizitatorul nu e autentificat, functia afiseaza formularul de login si
 * opreste pagina; daca e, se intoarce si pagina se randeaza normal. Tot fluxul e
 * server-rendered (POST + redirect), ca in restul proiectului.
 *
 * Conturile sunt cele din tabela `users`, aceleasi cu ale back office-ului, dar
 * NU oricare: doar cele din CV_CONTURI_PERMISE. Motivul e important - pagina de
 * login a aplicatiei afiseaza la vedere contul de demonstratie `ioana` cu parola
 * lui, ca sa poata intra oricine vrea sa vada proiectul. Daca ar fi acceptat si
 * aici, poarta asta n-ar tine pe nimeni afara.
 *
 * Deconectare: adauga ?logout in adresa.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Ce login-uri din tabela `users` au voie la site-ul personal. */
const CV_CONTURI_PERMISE = ['cvpers'];

/** Dupa atatea secunde de inactivitate se cere parola din nou. */
const CV_INACTIVITATE_MAX = 2 * 60 * 60;

/** Dupa atatea greseli consecutive, browserul asteapta CV_BLOCARE_SEC secunde. */
const CV_INCERCARI_MAX = 5;
const CV_BLOCARE_SEC = 60;

/**
 * Radacina aplicatiei LIVRA, de unde imprumutam conexiunea la baza si
 * UserRepository. Difera intre local si server, de aceea o cautam:
 *   local   - proiectul e langa webpersonal/  ->  ../src/
 *   server  - aplicatia e in webpersonal/app/ ->  ./app/src/
 */
$cv_pe_server = is_dir(__DIR__ . '/app/src/backend');
$cv_app = $cv_pe_server ? __DIR__ . '/app' : dirname(__DIR__);

/**
 * Linkul catre prezentarea proiectului, din aceeasi socoteala: pe server e in
 * `app/`, local e cu un nivel mai sus. Asa fisierul ramane identic in ambele
 * locuri si nu mai are nimeni de reaplicat vreo modificare la fiecare urcare.
 */
$cv_link_proiect = $cv_pe_server ? 'app/proiect.php' : '../proiect.php';

// Pagina depinde acum de baza de date, ceea ce inainte, cand era HTML simplu, nu
// se intampla. Daca baza nu raspunde, nu putem verifica parola nimanui - deci
// poarta ramane inchisa (fail closed), dar cu un mesaj citibil, nu pagina alba
// de eroare pe care ar da-o exceptia netratata.
try {
    require_once $cv_app . '/src/database/db_connection.php';
    require_once $cv_app . '/src/backend/UserRepository.php';
} catch (Throwable $e) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    header('Retry-After: 300');

    exit('<!DOCTYPE html><html lang="ro"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Momentan indisponibil</title></head>'
        . '<body style="font-family:system-ui,Segoe UI,Arial,sans-serif;max-width:34rem;'
        . 'margin:20vh auto;padding:0 1rem;line-height:1.6;color:#10151C">'
        . '<h1 style="font-size:1.3rem">Pagina e momentan indisponibila</h1>'
        . '<p>Baza de date nu raspunde, asa ca accesul nu poate fi verificat. '
        . 'Incearca din nou peste cateva minute.</p>'
        . '</body></html>');
}

/** Vizitatorul autentificat, sau null. Aici expira si sesiunea stata prea mult. */
function cv_logat()
{
    if (!isset($_SESSION['cv'])) {
        return null;
    }

    if (time() - (int) ($_SESSION['cv_ultima_activitate'] ?? 0) > CV_INACTIVITATE_MAX) {
        cv_logout();

        return null;
    }

    $_SESSION['cv_ultima_activitate'] = time();

    return $_SESSION['cv'];
}

function cv_logout()
{
    unset($_SESSION['cv'], $_SESSION['cv_ultima_activitate']);
}

/** Cate secunde mai are de asteptat cine a gresit de prea multe ori; 0 daca poate incerca. */
function cv_blocat_secunde()
{
    if ((int) ($_SESSION['cv_incercari'] ?? 0) < CV_INCERCARI_MAX) {
        return 0;
    }

    $ramas = CV_BLOCARE_SEC - (time() - (int) ($_SESSION['cv_ultima_incercare'] ?? 0));

    if ($ramas <= 0) {
        unset($_SESSION['cv_incercari'], $_SESSION['cv_ultima_incercare']);

        return 0;
    }

    return $ramas;
}

/** Scapa text pentru HTML. Numele difera de `h()` din aplicatie ca sa nu se bata cap in cap. */
function cv_h($valoare)
{
    return htmlspecialchars((string) $valoare, ENT_QUOTES, 'UTF-8');
}

// --- Fluxul propriu-zis ---

// Adresa paginii, fara ?logout: dupa POST ne intoarcem aici (Post-Redirect-Get).
$cv_pagina = strtok((string) ($_SERVER['REQUEST_URI'] ?? ''), '?');

if (isset($_GET['logout'])) {
    cv_logout();
    header('Location: ' . $cv_pagina);
    exit;
}

$cv_eroare = '';
$cv_login = '';
$cv_blocat = cv_blocat_secunde();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !cv_logat()) {
    $cv_login = trim((string) ($_POST['login'] ?? ''));
    $cv_parola = (string) ($_POST['parola'] ?? '');

    if ($cv_blocat > 0) {
        $cv_eroare = 'Prea multe incercari. Mai asteapta ' . $cv_blocat . ' secunde.';
    } else {
        $cv_utilizatori = new UserRepository($pdo);
        $cv_user = $cv_utilizatori->autentifica($cv_login, $cv_parola);

        // Contul trebuie sa existe SI sa fie pe lista celor cu drept de acces aici.
        if ($cv_user === null || !in_array($cv_user['login'], CV_CONTURI_PERMISE, true)) {
            $_SESSION['cv_incercari'] = (int) ($_SESSION['cv_incercari'] ?? 0) + 1;
            $_SESSION['cv_ultima_incercare'] = time();
            $cv_blocat = cv_blocat_secunde();

            // Acelasi mesaj si la login gresit, si la parola gresita: altfel am
            // spune care conturi exista.
            $cv_eroare = 'Login sau parola gresita.';
        } else {
            unset($_SESSION['cv_incercari'], $_SESSION['cv_ultima_incercare']);

            // ID nou de sesiune la fiecare intrare (protectie session fixation).
            session_regenerate_id(true);

            $_SESSION['cv'] = $cv_user;
            $_SESSION['cv_ultima_activitate'] = time();

            header('Location: ' . $cv_pagina);
            exit;
        }
    }
}

if (cv_logat()) {
    // Trecut de poarta: `index.php` isi randeaza continutul mai departe.
    return;
}

// Motoarele de cautare n-au ce indexa aici.
header('X-Robots-Tag: noindex, nofollow');

?><!DOCTYPE html>
<html lang="ro" data-theme="">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ioana Dima &mdash; acces</title>
<meta name="robots" content="noindex, nofollow">

<link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="css/style.css">

<!-- Tema salvata se aplica inainte de primul randat, ca sa nu palpaie ecranul -->
<script>
  (function () {
    try {
      var t = localStorage.getItem("tema");
      if (t) { document.documentElement.setAttribute("data-theme", t); }
    } catch (e) {}
  })();
</script>

<!-- Google Analytics: si poarta se masoara, ca sa se vada cati ajung aici fata
     de cati intra efectiv. Doar de pe domeniul public - vezi index.php. -->
<script>
  (function () {
    var gazda = location.hostname;

    if (!gazda || gazda === "localhost" || gazda === "127.0.0.1" || /\.(local|test)$/.test(gazda)) {
      return;
    }

    var id = "G-1LY95M3J22";

    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { dataLayer.push(arguments); };
    gtag("js", new Date());
    gtag("config", id, { zona: "cv-poarta", autentificat: "nu" });

    var s = document.createElement("script");
    s.async = true;
    s.src = "https://www.googletagmanager.com/gtag/js?id=" + id;
    document.head.appendChild(s);
  })();
</script>

<style>
  /* Pagina de acces: o singura cutie centrata, pe aceleasi variabile de culoare
     ca restul site-ului, deci urmeaza automat tema deschisa / intunecata. */
  .acces {
    min-height: 100vh;
    display: grid;
    place-items: center;
    padding: var(--sp-4);
    background: var(--paper);
  }

  .acces-cutie {
    width: min(24rem, 100%);
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: var(--sp-5);
  }

  .acces-titlu {
    font-family: var(--display);
    font-size: var(--step-2);
    line-height: 1.15;
    margin: 0 0 var(--sp-2);
    color: var(--ink);
  }

  .acces-intro {
    color: var(--muted);
    font-size: var(--step--1);
    margin: 0 0 var(--sp-4);
  }

  .acces-camp { display: block; margin-bottom: var(--sp-3); }

  .acces-eticheta {
    display: block;
    font-size: var(--step--1);
    color: var(--muted);
    margin-bottom: var(--sp-1);
  }

  .acces-input {
    width: 100%;
    box-sizing: border-box;
    padding: .6rem .7rem;
    font: inherit;
    color: var(--ink);
    background: var(--paper);
    border: 1px solid var(--line);
    border-radius: var(--radius);
  }

  .acces-input:focus-visible { outline: 2px solid var(--accent); outline-offset: 1px; }

  .acces-btn {
    width: 100%;
    margin-top: var(--sp-2);
    padding: .65rem 1rem;
    font: inherit;
    font-weight: 600;
    cursor: pointer;
    color: var(--accent-ink);
    background: var(--accent);
    border: 1px solid var(--accent);
    border-radius: var(--radius);
  }

  .acces-btn[disabled] { opacity: .5; cursor: not-allowed; }

  .acces-eroare {
    margin: 0 0 var(--sp-3);
    padding: .55rem .7rem;
    font-size: var(--step--1);
    color: var(--ink);
    background: var(--accent-soft);
    border-left: 3px solid var(--data-2);
  }

  .acces-nota {
    margin: var(--sp-4) 0 0;
    font-size: var(--step--1);
    color: var(--muted);
  }

  .acces-nota a { color: var(--accent); }
</style>
</head>

<body>
<main class="acces">
  <div class="acces-cutie">

    <h1 class="acces-titlu">Ioana Dima</h1>
    <p class="acces-intro">Pagina e privata. Introdu datele de acces primite de la mine.</p>

    <?php if ($cv_eroare !== ''): ?>
      <p class="acces-eroare"><?= cv_h($cv_eroare) ?></p>
    <?php endif; ?>

    <form method="post" action="<?= cv_h($cv_pagina) ?>">
      <label class="acces-camp">
        <span class="acces-eticheta">Login</span>
        <input class="acces-input" type="text" name="login" value="<?= cv_h($cv_login) ?>"
               autocomplete="username" autofocus required maxlength="50">
      </label>

      <label class="acces-camp">
        <span class="acces-eticheta">Parola</span>
        <input class="acces-input" type="password" name="parola"
               autocomplete="current-password" required>
      </label>

      <button class="acces-btn" type="submit" <?= $cv_blocat > 0 ? 'disabled' : '' ?>>
        Intra
      </button>
    </form>

    <p class="acces-nota">
      Proiectul LIVRA se poate vedea si fara datele astea:
      <a href="<?= cv_h($cv_link_proiect) ?>">prezentarea aplicatiei</a>.
    </p>

  </div>
</main>
</body>
</html>
<?php
exit;
