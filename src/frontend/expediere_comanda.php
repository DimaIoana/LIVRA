<?php

/**
 * Back office: expedierea unei comenzi cu optimizarea rutelor.
 *
 * Pentru fiecare linie de produs din comanda se afiseaza cele mai optimizate rute
 * (depozit -> oras client), ordonate dupa timpul ajustat. Operatorul alege o ruta,
 * iar la selectie se creeaza expedierea + AWB. Server-rendered, POST + redirect.
 *
 * Vezi docs/algoritm_optimizare_rute.md si backend/OptimizareRuteService.php.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../database/db_connection.php';
require __DIR__ . '/../backend/OptimizareRuteService.php';

$service = new OptimizareRuteService($pdo);

/** Scapa text pentru HTML. */
function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Minute -> "6 h 30 min" / "45 min". */
function timp_fmt($minutes)
{
    $m = (int) $minutes;
    $h = intdiv($m, 60);
    $min = $m % 60;

    if ($h === 0) {
        return $min . ' min';
    }

    return $min === 0 ? $h . ' h' : $h . ' h ' . $min . ' min';
}

/** 1234.5 -> 1.234,50 lei */
function lei($v)
{
    return number_format((float) $v, 2, ',', '.') . ' lei';
}

/** Data in format european: 2025-03-01 14:30:00 -> 01.03.2025 14:30; gol -> '-'. */
function fmt_eu($value)
{
    if ($value === null || $value === '') {
        return '-';
    }

    $parts = explode(' ', trim((string) $value));
    $d = explode('-', $parts[0]);
    if (count($d) !== 3) {
        return $value;
    }

    $out = $d[2] . '.' . $d[1] . '.' . $d[0];
    if (isset($parts[1]) && $parts[1] !== '') {
        $t = explode(':', $parts[1]);
        $out .= ' ' . $t[0] . ':' . ($t[1] ?? '00');
    }

    return $out;
}

// --- POST: creeaza expedierea pentru o linie, pe ruta aleasa ---

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'creeaza') {
    $linieId = (int) ($_POST['LinieID'] ?? 0);
    $rutaId = (int) ($_POST['RutaID'] ?? 0);
    $comandaId = (int) ($_POST['ComandaID'] ?? 0);

    $rezultat = $service->creeazaExpediere($linieId, $rutaId);

    if ($rezultat['errors']) {
        $_SESSION['exp_flash'] = ['message' => implode(' ', $rezultat['errors']), 'state' => 'fail'];
    } else {
        $_SESSION['exp_flash'] = ['message' => 'Expediere creata. AWB: ' . $rezultat['awb'], 'state' => 'ok'];
    }

    header('Location: expediere_comanda.php?comanda=' . $comandaId);
    exit;
}

$flash = $_SESSION['exp_flash'] ?? null;
unset($_SESSION['exp_flash']);

$comandaId = (int) ($_GET['comanda'] ?? 0);

// Datele comenzii + liniile ei (daca s-a cerut o comanda anume).
$comanda = null;
$linii = [];
if ($comandaId > 0) {
    $stmt = $pdo->prepare(
        'SELECT co.ComandaID, co.Data_comanda, co.Total, cl.Nume AS client, cl.Oras AS oras_client
         FROM comenzi co JOIN clienti cl ON cl.ClientID = co.ClientID
         WHERE co.ComandaID = :id'
    );
    $stmt->execute(['id' => $comandaId]);
    $comanda = $stmt->fetch();

    if ($comanda !== false) {
        $stmt = $pdo->prepare(
            'SELECT LinieID, Product_ID, Product_Name, Cantitate, Subtotal
             FROM comenzi_produse WHERE ComandaID = :id ORDER BY LinieID'
        );
        $stmt->execute(['id' => $comandaId]);
        $linii = $stmt->fetchAll();
    } else {
        $comanda = null;
    }
}

// Daca nu s-a cerut o comanda anume, listam comenzile.
$comenzi = [];
if ($comanda === null) {
    $comenzi = $pdo->query(
        'SELECT co.ComandaID, co.Data_comanda, co.Total, co.Status, cl.Nume AS client, cl.Oras AS oras_client
         FROM comenzi co JOIN clienti cl ON cl.ClientID = co.ClientID
         ORDER BY co.ComandaID DESC'
    )->fetchAll();
}

$navLinks = [
    'clienti' => ['clienti.php', 'Clienti'],
    'comenzi' => ['comenzi.php', 'Comenzi'],
    'expedieri' => ['expedieri.php', 'Expedieri'],
    'soferi' => ['soferi.php', 'Soferi'],
    'rute' => ['rute.php', 'Rute'],
    'produse' => ['produse.php', 'Produse'],
];

?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PRIMUL - Expediere comanda</title>
  <link rel="stylesheet" href="css/app.css?v=<?= filemtime(__DIR__ . '/css/app.css') ?>">
</head>
<body>

<header class="header">
  <div class="header__inner">
    <a class="logo" href="../../index.php">PRIMUL</a>
    <span class="header__subtitle">Expediere comanda (optimizare rute)</span>
    <nav class="nav">
      <?php foreach ($navLinks as $key => $link): ?>
        <a class="nav__link<?= $key === 'comenzi' ? ' nav__link--active' : '' ?>" href="<?= h($link[0]) ?>"><?= h($link[1]) ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
</header>

<main class="container">

  <?php if ($flash): ?>
    <div class="alert alert--<?= h($flash['state']) ?>"><?= h($flash['message']) ?></div>
  <?php endif; ?>

  <?php if ($comanda === null): ?>

    <div class="card__head" style="margin-bottom:16px">
      <div>
        <h1 class="card__title">Alege o comanda de expediat</h1>
        <p class="card__desc">Selecteaza o comanda ca sa vezi rutele optimizate pentru fiecare produs.</p>
      </div>
    </div>

    <div class="card">
      <table class="table">
        <thead>
          <tr><th>ID</th><th>Client</th><th>Oras</th><th>Data</th><th>Status</th><th>Total</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($comenzi as $c): ?>
            <tr>
              <td>#<?= (int) $c['ComandaID'] ?></td>
              <td><?= h($c['client']) ?></td>
              <td><?= h($c['oras_client']) ?></td>
              <td class="cell--nowrap"><?= h(fmt_eu($c['Data_comanda'])) ?></td>
              <td><span class="tag"><?= h($c['Status']) ?></span></td>
              <td class="cell--number"><?= h(lei($c['Total'])) ?></td>
              <td class="row-actions">
                <a class="btn--link" href="expediere_comanda.php?comanda=<?= (int) $c['ComandaID'] ?>">Expediaza</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (!$comenzi): ?>
        <p class="empty">Nu exista comenzi.</p>
      <?php endif; ?>
    </div>

  <?php else: ?>

    <div class="card__head" style="margin-bottom:16px">
      <div>
        <h1 class="card__title">Comanda #<?= (int) $comanda['ComandaID'] ?> - <?= h($comanda['client']) ?></h1>
        <p class="card__desc">
          Livrare in <strong><?= h($comanda['oras_client']) ?></strong> ·
          <?= h(fmt_eu($comanda['Data_comanda'])) ?> · total <?= h(lei($comanda['Total'])) ?>
        </p>
      </div>
      <a class="btn btn--ghost" href="expediere_comanda.php">Toate comenzile</a>
    </div>

    <?php foreach ($linii as $linie): ?>
      <?php
        $expediere = $service->expediereePentruLinie($linie['LinieID']);
        $rute = $expediere === null
            ? $service->ruteOptimizate($linie['Product_ID'], $comanda['oras_client'])
            : [];
        $best = $rute ? $rute[0]['timp_ajustat'] : null;
      ?>
      <div class="card" style="margin-bottom:18px;padding:18px">
        <div class="card__head">
          <div>
            <h2 class="card__title" style="font-size:17px">
              <?= h($linie['Product_Name']) ?>
              <span class="field__hint" style="display:inline">(<?= h($linie['Product_ID']) ?> · <?= (int) $linie['Cantitate'] ?> buc · <?= h(lei($linie['Subtotal'])) ?>)</span>
            </h2>
          </div>
        </div>

        <?php if ($expediere !== null): ?>
          <div class="alert alert--ok" style="margin-top:12px;margin-bottom:0">
            Expediat pe ruta <?= h($expediere['ruta']) ?> · AWB <strong><?= h($expediere['awb']) ?></strong> · status <?= h($expediere['Status_expediere']) ?>
          </div>
        <?php elseif (!$rute): ?>
          <p class="empty" style="text-align:left;padding:12px 0 0">
            Nicio ruta disponibila: produsul nu e pe stoc intr-un depozit cu ruta catre <?= h($comanda['oras_client']) ?>.
          </p>
        <?php else: ?>
          <table class="table" style="margin-top:8px">
            <thead>
              <tr><th>Loc</th><th>Depozit → client</th><th>Timp ajustat</th><th>De ce</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($rute as $i => $r): ?>
                <tr>
                  <td class="cell--nowrap">
                    <?php if ($i === 0): ?>
                      <span class="tag tag--ok">#1 optima</span>
                    <?php else: ?>
                      <span class="tag">#<?= $i + 1 ?></span>
                    <?php endif; ?>
                  </td>
                  <td><?= h($r['Oras_origine']) ?> → <?= h($r['Oras_destinatie']) ?></td>
                  <td class="cell--nowrap"><strong><?= h(timp_fmt($r['timp_ajustat'])) ?></strong></td>
                  <td class="field__hint" style="max-width:360px">
                    <?= h($r['explicatie']) ?>
                    <?php if ($i > 0): ?>
                      <br>cu <?= (int) ($r['timp_ajustat'] - $best) ?> min mai mult decat cea optima.
                    <?php else: ?>
                      <br>Cel mai mic timp, deci cea mai optimizata.
                    <?php endif; ?>
                  </td>
                  <td class="row-actions">
                    <form method="post" action="expediere_comanda.php">
                      <input type="hidden" name="action" value="creeaza">
                      <input type="hidden" name="ComandaID" value="<?= (int) $comanda['ComandaID'] ?>">
                      <input type="hidden" name="LinieID" value="<?= (int) $linie['LinieID'] ?>">
                      <input type="hidden" name="RutaID" value="<?= (int) $r['RutaID'] ?>">
                      <button class="btn<?= $i === 0 ? '' : ' btn--ghost' ?>" type="submit">Alege</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <?php if (!$linii): ?>
      <div class="card"><p class="empty">Comanda nu are produse.</p></div>
    <?php endif; ?>

  <?php endif; ?>

</main>

</body>
</html>
