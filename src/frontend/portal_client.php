<?php

/**
 * Portal client (front office): "login" simplu pentru clienti + urmarire colet.
 *
 * Pasii:
 *   1. lista clientilor in ordine alfabetica, cu nr. de expedieri in tranzit;
 *   2. se alege un client (?client=ID) -> se cere AWB-ul;
 *   3. daca AWB-ul coincide (si apartine clientului ales) -> se arata produsul,
 *      km, timpul rutei si cat timp a trecut de la inregistrarea expedierii
 *      (Data_expediere).
 *
 * Server-rendered, fara JS. "Login"-ul e o verificare usoara: clientul se
 * identifica prin nume + AWB-ul propriului colet.
 */

require __DIR__ . '/../database/db_connection.php';

/** Scapa text pentru HTML. */
function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Minute -> "6 h 30 min" / "45 min". */
function timp_fmt($minutes)
{
    $m = (int) $minutes;
    $hh = intdiv($m, 60);
    $mm = $m % 60;

    if ($hh === 0) {
        return $mm . ' min';
    }

    return $mm === 0 ? $hh . ' h' : $hh . ' h ' . $mm . ' min';
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

// Programul curierilor: se conduce doar intre aceste ore, in toate zilele
// (inclusiv sambata si duminica).
const PROGRAM_START = 7;   // 07:00
const PROGRAM_END = 22;    // 22:00

/**
 * Minutele efective de program (07:00-22:00) intre doua momente. Timpul din
 * afara programului (noaptea) nu se numara; toate zilele conteaza, si weekendul.
 */
function minute_program($start, $end)
{
    try {
        $s = new DateTime($start);
        $e = new DateTime($end);
    } catch (Exception $ex) {
        return 0;
    }

    if ($e <= $s) {
        return 0;
    }

    $total = 0;
    $zi = new DateTime($s->format('Y-m-d') . ' 00:00:00');
    $ultima = new DateTime($e->format('Y-m-d') . ' 00:00:00');

    while ($zi <= $ultima) {
        $fereastraStart = (clone $zi)->setTime(PROGRAM_START, 0);
        $fereastraEnd = (clone $zi)->setTime(PROGRAM_END, 0);

        // Suprapunerea intervalului [s, e] cu fereastra de program a zilei.
        $a = max($s->getTimestamp(), $fereastraStart->getTimestamp());
        $b = min($e->getTimestamp(), $fereastraEnd->getTimestamp());
        if ($b > $a) {
            $total += $b - $a;
        }

        $zi->modify('+1 day');
    }

    return intdiv($total, 60);
}

// --- Starea paginii ---

$clientId = (int) ($_GET['client'] ?? 0);
$client = null;
$awb = '';
$expediere = null;
$eroareAwb = '';

if ($clientId > 0) {
    $stmt = $pdo->prepare('SELECT ClientID, Nume, Oras FROM clienti WHERE ClientID = :id');
    $stmt->execute(['id' => $clientId]);
    $client = $stmt->fetch();
    if ($client === false) {
        $client = null;
        $clientId = 0;
    }
}

// Verificarea AWB-ului (dupa ce s-a ales un client).
if ($client !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $awb = trim((string) ($_POST['awb'] ?? ''));

    if ($awb === '') {
        $eroareAwb = 'Introdu numarul AWB.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT e.awb, e.Data_expediere, e.Data_livrare_estimata,
                    e.Data_livrare_efectiva, e.Status_expediere,
                    r.Oras_origine, r.Oras_destinatie, r.Distanta_km, r.Durata_min,
                    cp.Product_ID, cp.Product_Name, cp.Cantitate
             FROM expedieri e
             JOIN rute r ON r.RutaID = e.RutaID
             LEFT JOIN comenzi_produse cp ON cp.LinieID = e.LinieID
             WHERE e.awb = :awb AND e.ClientID = :client'
        );
        $stmt->execute(['awb' => $awb, 'client' => $clientId]);
        $expediere = $stmt->fetch();

        if ($expediere === false) {
            $expediere = null;
            $eroareAwb = 'AWB gresit sau nu apartine acestui client.';
        }
    }
}

// Lista de clienti (doar cand nu s-a ales unul).
$clienti = [];
if ($client === null) {
    $clienti = $pdo->query(
        "SELECT c.ClientID, c.Nume, c.Oras,
                (SELECT COUNT(*) FROM expedieri e
                  WHERE e.ClientID = c.ClientID AND e.Status_expediere = 'In tranzit') AS in_tranzit
         FROM clienti c
         ORDER BY c.Nume ASC"
    )->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LIVRA - Portal client</title>
  <link rel="stylesheet" href="css/app.css?v=<?= filemtime(__DIR__ . '/css/app.css') ?>">
</head>
<body>

<header class="header">
  <div class="header__inner">
    <a class="logo" href="magazin.php">LIVRA</a>
    <span class="header__subtitle">Portal client - urmarire colet</span>
    <nav class="nav">
      <a class="nav__link" href="magazin.php">Produse</a>
      <a class="nav__link" href="cos.php">Cos</a>
      <a class="nav__link nav__link--active" href="portal_client.php">Urmarire colet</a>
    </nav>
  </div>
</header>

<main class="container">

  <?php if ($client === null): ?>

    <div class="shop-hero">
      <h1 class="shop-hero__title">Login clienti</h1>
      <p class="shop-hero__subtitle">Alege-ti numele, apoi introdu AWB-ul coletului ca sa vezi statusul.</p>
    </div>

    <div class="card">
      <table class="table">
        <thead>
          <tr><th>Client</th><th>Oras</th><th>In tranzit</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($clienti as $c): ?>
            <tr>
              <td><?= h($c['Nume']) ?></td>
              <td><?= h($c['Oras']) ?></td>
              <td class="cell--number">
                <?php if ((int) $c['in_tranzit'] > 0): ?>
                  <span class="tag tag--warn"><?= (int) $c['in_tranzit'] ?></span>
                <?php else: ?>
                  <span class="tag">0</span>
                <?php endif; ?>
              </td>
              <td class="row-actions">
                <a class="btn--link" href="portal_client.php?client=<?= (int) $c['ClientID'] ?>">Login</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (!$clienti): ?>
        <p class="empty">Nu exista clienti.</p>
      <?php endif; ?>
    </div>

  <?php else: ?>

    <div class="card__head" style="margin-bottom:16px">
      <div>
        <h1 class="card__title">Buna, <?= h($client['Nume']) ?></h1>
        <p class="card__desc">Introdu AWB-ul coletului tau ca sa vezi statusul livrarii.</p>
      </div>
      <a class="btn btn--ghost" href="portal_client.php">Alt client</a>
    </div>

    <div class="card" style="padding:22px;max-width:520px">
      <form class="form" method="post" action="portal_client.php?client=<?= (int) $clientId ?>">
        <?php if ($eroareAwb !== ''): ?>
          <p class="form__error"><?= h($eroareAwb) ?></p>
        <?php endif; ?>
        <label class="field">
          <span class="field__label">Numar AWB</span>
          <input class="input" type="text" name="awb" value="<?= h($awb) ?>"
                 placeholder="ex: AWB20260723000032" autofocus>
        </label>
        <div class="form__actions">
          <button class="btn" type="submit">Verifica coletul</button>
        </div>
      </form>
    </div>

    <?php if ($expediere !== null): ?>
      <?php
        // Momentul expedierii (Data_expediere are acum si ora).
        $creata = $expediere['Data_expediere'];

        // "Acum" se ia din DB (acelasi ceas care a scris Data_expediere), ca sa
        // nu apara diferente din fusul orar al PHP.
        $acum = $pdo->query('SELECT NOW()')->fetchColumn();

        $prestabilit = (int) $expediere['Durata_min'];
        $trecut = minute_program($creata, $acum);
        $ramas = max(0, $prestabilit - $trecut);

        // Statusul real (setat de operator) e sursa de adevar; timpul ramas e
        // doar o estimare din timpul de condus prestabilit.
        $livrat = $expediere['Status_expediere'] === 'Livrat';
      ?>
      <div class="card" style="padding:22px;margin-top:18px;max-width:640px">
        <div class="card__head">
          <div>
            <h2 class="card__title" style="font-size:18px">Colet <?= h($expediere['awb']) ?></h2>
            <p class="card__desc"><?= h($expediere['Oras_origine']) ?> &rarr; <?= h($expediere['Oras_destinatie']) ?></p>
          </div>
          <span class="tag <?= $expediere['Status_expediere'] === 'Livrat' ? 'tag--ok' : 'tag--warn' ?>">
            <?= h($expediere['Status_expediere']) ?>
          </span>
        </div>

        <table class="table" style="margin-top:12px">
          <tbody>
            <tr>
              <td>Produs</td>
              <td><strong>
                <?php if ($expediere['Product_Name'] !== null): ?>
                  <?= h($expediere['Product_Name']) ?> (<?= h($expediere['Product_ID']) ?>) &middot; <?= (int) $expediere['Cantitate'] ?> buc
                <?php else: ?>
                  indisponibil
                <?php endif; ?>
              </strong></td>
            </tr>
            <tr>
              <td>Distanta</td>
              <td><strong><?= (int) $expediere['Distanta_km'] ?> km</strong></td>
            </tr>
            <tr>
              <td>Timp prestabilit (ruta)</td>
              <td><strong><?= h(timp_fmt($prestabilit)) ?></strong></td>
            </tr>
            <tr>
              <td>Expediat la</td>
              <td><?= h(fmt_eu($creata)) ?></td>
            </tr>
            <tr>
              <td>Status livrare</td>
              <td>
                <span class="tag <?= $livrat ? 'tag--ok' : 'tag--warn' ?>"><?= h($expediere['Status_expediere']) ?></span>
                <span class="field__hint" style="display:inline"> (sursa oficiala)</span>
              </td>
            </tr>

            <?php if ($livrat): ?>
              <tr>
                <td>Livrat la</td>
                <td><strong><?= $expediere['Data_livrare_efectiva'] !== null ? h(fmt_eu($expediere['Data_livrare_efectiva'])) : 'data indisponibila' ?></strong></td>
              </tr>
            <?php else: ?>
              <tr>
                <td>Timp trecut (program 07:00&ndash;22:00)</td>
                <td><strong><?= h(timp_fmt($trecut)) ?></strong></td>
              </tr>
              <tr>
                <td>Timp ramas estimat</td>
                <td><strong>
                  <?php if ($ramas > 0): ?>
                    <?= h(timp_fmt($ramas)) ?>
                  <?php else: ?>
                    timp estimat depasit &ndash; inca in tranzit
                  <?php endif; ?>
                </strong></td>
              </tr>
              <tr>
                <td>Livrare estimata</td>
                <td><?= h(fmt_eu($expediere['Data_livrare_estimata'])) ?></td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  <?php endif; ?>

</main>

</body>
</html>
