<?php

// Back office: doar pentru utilizatorii autentificati (vezi _auth.php).
require __DIR__ . '/_auth.php';
cere_admin();

require __DIR__ . '/../backend/SystemCheck.php';

$checker = new SystemCheck(__DIR__ . '/../database/db_connection.php');
$checks = $checker->runAll();

$failed = 0;
foreach ($checks as $check) {
    if (!$check['ok']) {
        $failed++;
    }
}

$allOk = $failed === 0;
$checkedAt = date('Y-m-d H:i:s');

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$navLinks = [
    ['clienti.php', 'Clienti'],
    ['expedieri.php', 'Expedieri'],
    ['soferi.php', 'Soferi'],
    ['rute.php', 'Rute'],
    ['catalog_produse.php', 'Catalog de produse'],
    ['produse.php', 'Control de stocks'],
];

?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LIVRA - Pagina de test</title>
  <link rel="stylesheet" href="css/app.css?v=<?= filemtime(__DIR__ . '/css/app.css') ?>">
  <?php require_once __DIR__ . '/_analytics.php'; ?>
</head>
<body>

<header class="header">
  <div class="header__inner">
    <a class="logo" href="../../index.php">LIVRA</a>
    <span class="header__subtitle">Pagina de test &amp; diagnostic</span>
    <nav class="nav">
      <?php foreach ($navLinks as $link): ?>
        <a class="nav__link" href="<?= h($link[0]) ?>"><?= h($link[1]) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php if ($adminConectat = admin_logat()): ?>
      <span class="header__user">
        <strong><?= h($adminConectat['Nume']) ?></strong>
        <a class="header__logout" href="admin_login.php?logout=1">Iesi</a>
      </span>
    <?php endif; ?>
  </div>
</header>

<main class="container">
  <section class="card" style="padding: 24px;">
    <div class="card__head">
      <div>
        <h1 class="card__title">Stare sistem</h1>
        <p class="card__desc">Verifica lantul pagina PHP &rarr; backend &rarr; baza de date.</p>
      </div>
      <a class="btn" href="test.php">Ruleaza din nou</a>
    </div>

    <div class="summary summary--<?= $allOk ? 'ok' : 'fail' ?>">
      <?php if ($allOk): ?>
        Toate verificarile au trecut (<?= h($checkedAt) ?>)
      <?php else: ?>
        <?= (int) $failed ?> verificari au esuat (<?= h($checkedAt) ?>)
      <?php endif; ?>
    </div>

    <ul class="checks">
      <?php foreach ($checks as $check): ?>
        <li class="check">
          <span class="check__icon check__icon--<?= $check['ok'] ? 'ok' : 'fail' ?>"><?= $check['ok'] ? '✓' : '✕' ?></span>
          <div>
            <div class="check__name"><?= h($check['name']) ?></div>
            <div class="check__detail"><?= h($check['detail']) ?></div>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
</main>

</body>
</html>
