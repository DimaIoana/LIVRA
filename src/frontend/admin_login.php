<?php

/**
 * Login de back office: user + parola criptata (tabela `users`).
 *
 * Toate paginile de administrare (index, clienti, comenzi, expedieri, soferi,
 * rute, produse, business, laborator) trec pe aici. Parola nu se tine nicaieri
 * in clar - se compara hash-uri, vezi src/backend/UserRepository.php.
 *
 * Login-ul de client (magazin) e separat, in `login.php`.
 */

require __DIR__ . '/_auth.php';

/**
 * Contul de test, aratat pe pagina: proiectul e o demonstratie, deci cine il
 * vede prima data trebuie sa poata intra fara sa ceara datele nimanui.
 *
 * Definite o singura data: aceleasi valori se si afiseaza, si se trimit de
 * butonul "(click aici)". Altfel s-ar putea schimba una fara cealalta si
 * butonul ar duce la "User sau parola gresita".
 */
const DEMO_USER = 'ioana';
const DEMO_PAROLA = '1234';

/** Scapa text pentru HTML. */
function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Delogare.
if (isset($_GET['logout'])) {
    admin_logout();
    admin_flash_set('Te-ai delogat.', 'ok');
    header('Location: admin_login.php');
    exit;
}

// Deja conectat -> nu are ce cauta pe pagina de login.
if (admin_logat()) {
    header('Location: ' . admin_base_url() . '/index.php');
    exit;
}

$eroare = '';
$login = '';
$blocatSecunde = admin_blocat_secunde();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim((string) ($_POST['login'] ?? ''));
    $parola = (string) ($_POST['parola'] ?? '');

    if ($blocatSecunde > 0) {
        $eroare = 'Prea multe incercari. Mai asteapta ' . $blocatSecunde . ' secunde.';
    } else {
        $user = $utilizatori->autentifica($login, $parola);

        if ($user === null) {
            // Acelasi mesaj si la user gresit, si la parola gresita: altfel am
            // spune celui care ghiceste care login-uri exista.
            admin_inregistreaza_esec();
            $eroare = 'User sau parola gresita.';
            $blocatSecunde = admin_blocat_secunde();
        } else {
            admin_reseteaza_incercari();
            admin_login($user);
            header('Location: ' . admin_retur_url());
            exit;
        }
    }
}

$flash = admin_flash_get();

// Fara niciun cont in baza nu se poate intra deloc - spunem cum se creeaza unul.
$faraConturi = $utilizatori->numarConturi() === 0;

?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LIVRA - Panou administrare</title>
  <link rel="stylesheet" href="css/app.css?v=<?= filemtime(__DIR__ . '/css/app.css') ?>">
  <link rel="stylesheet" href="css/auth.css?v=<?= filemtime(__DIR__ . '/css/auth.css') ?>">
  <?php require_once __DIR__ . '/_analytics.php'; ?>
</head>
<body class="auth-body">

<main class="auth">

  <!-- Panoul de prezentare: firma de curierat -->
  <section class="auth__brand">
    <div class="auth__brand-top">
      <img class="auth__logo" src="img/logo-livra.png" width="355" height="260"
           alt="LIVRA - livram incredere">
      <p class="auth__tagline">Curierat rapid, rute optimizate, clienti multumiti.</p>
    </div>

    <div class="auth__scena" aria-hidden="true">
      <span class="auth__duba">🚚</span>
      <span class="auth__drum"></span>
      <span class="auth__colete">📦 📦 📦</span>
    </div>

    <p class="auth__autor">Proiect realizat de Dima Ioana</p>
  </section>

  <!-- Formularul de autentificare -->
  <section class="auth__panou">
    <div class="auth__coloana">

      <!-- Intoarcerea la prezentarea proiectului, de unde se ajunge aici.
           admin_base_url() da radacina aplicatiei, ca linkul sa fie corect si
           local (/CLAUDE/PRIMUL), si pe server (/app). -->
      <a class="btn auth__inapoi" href="<?= h(admin_base_url()) ?>/proiect.php">
        &larr; Inapoi
      </a>

      <div class="auth__form-box">

      <h1 class="auth__titlu">Panou administrare</h1>
      <p class="auth__subtitlu">Autentifica-te ca sa administrezi comenzile, coletele si rutele.</p>

      <!-- Contul de test, cu intrare directa: butonul trimite chiar datele de mai
           jos catre acelasi formular de login, deci trece prin aceleasi verificari
           (inclusiv blocarea dupa prea multe incercari). -->
      <div class="auth__demo">
        <form class="auth__demo-cap" method="post" action="admin_login.php">
          <span class="auth__demo-titlu">Cont de test</span>
          <input type="hidden" name="login" value="<?= h(DEMO_USER) ?>">
          <input type="hidden" name="parola" value="<?= h(DEMO_PAROLA) ?>">
          <button class="btn btn--mic auth__demo-btn" type="submit" <?= $blocatSecunde > 0 ? 'disabled' : '' ?>>
            (click aici)
          </button>
        </form>

        <dl class="auth__demo-lista">
          <dt>User</dt>
          <dd><?= h(DEMO_USER) ?></dd>
          <dt>Parola</dt>
          <dd><?= h(DEMO_PAROLA) ?></dd>
        </dl>
      </div>

      <?php if ($flash): ?>
        <div class="alert alert--<?= h($flash['state']) ?>"><?= h($flash['message']) ?></div>
      <?php endif; ?>

      <?php if ($faraConturi): ?>
        <div class="alert alert--fail">
          Nu exista niciun cont in tabela <code>users</code>. Creeaza unul cu:
          <code>php tools/hash_parola.php</code>
        </div>
      <?php endif; ?>

      <form class="form" method="post" action="admin_login.php">
        <?php if ($eroare !== ''): ?>
          <p class="form__error"><?= h($eroare) ?></p>
        <?php endif; ?>

        <label class="field">
          <span class="field__label">User</span>
          <input class="input" type="text" name="login" value="<?= h($login) ?>"
                 autocomplete="username" autofocus required maxlength="50" placeholder="ex: ioana">
        </label>

        <label class="field">
          <span class="field__label">Parola</span>
          <input class="input" type="password" name="parola"
                 autocomplete="current-password" required placeholder="••••••••">
        </label>

        <div class="form__actions">
          <button class="btn auth__btn" type="submit" <?= $blocatSecunde > 0 ? 'disabled' : '' ?>>
            Intra in cont
          </button>
        </div>
      </form>

        <p class="auth__nota">
          Parola e criptata in baza de date (bcrypt) - nu poate fi citita de nimeni,
          nici din phpMyAdmin.
        </p>

      </div>
    </div>
  </section>

</main>

</body>
</html>
