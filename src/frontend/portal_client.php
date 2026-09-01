<?php

/**
 * Urmarire colet (front office): coletele clientului logat.
 *
 * Pagina arata cate un rand pentru fiecare colet din comenzile clientului:
 * AWB, poza produsului, numele si pretul. Langa fiecare e un buton care adauga
 * AWB-ul la urmarire, si abia atunci randul isi arata statusul livrarii si
 * restul detaliilor (traseu, timp trecut, timp ramas, livrare estimata).
 *
 * Ce AWB-uri sunt adaugate se tine in sesiune ($_SESSION['awb_urmarite']), deci
 * nu se scrie nimic in baza de date.
 *
 * Server-rendered, fara JS: butoanele fac POST catre aceeasi pagina, iar dupa
 * fiecare operatie se face redirect (Post-Redirect-Get).
 */

// Sesiune, conexiune si helperii comuni de magazin: h(), lei(), poza_url(),
// client_logat(), cere_login().
require __DIR__ . '/_shop.php';

// Coletele sunt ale cuiva: trebuie sa stim al cui e cosul de comenzi.
cere_login();

$pagina = 'portal_client.php';
$client = client_logat();

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

/** AWB-urile pentru care clientul a cerut sa vada statusul. */
function awb_urmarite()
{
    $lista = $_SESSION['awb_urmarite'] ?? [];

    return is_array($lista) ? $lista : [];
}

// --- Actiuni (POST + redirect) ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $awb = trim((string) ($_POST['awb'] ?? ''));

    // Se accepta doar AWB-uri care chiar sunt ale clientului logat: altfel
    // butonul ar putea fi folosit ca sa se urmareasca coletul altcuiva.
    $alMeu = false;
    if ($awb !== '') {
        $stmt = $pdo->prepare('SELECT 1 FROM expedieri WHERE awb = :awb AND ClientID = :client');
        $stmt->execute(['awb' => $awb, 'client' => $client['id']]);
        $alMeu = $stmt->fetchColumn() !== false;
    }

    $lista = awb_urmarite();

    if ($action === 'adauga' && $alMeu && !in_array($awb, $lista, true)) {
        $lista[] = $awb;
        $_SESSION['awb_urmarite'] = $lista;
    } elseif ($action === 'scoate') {
        $_SESSION['awb_urmarite'] = array_values(array_filter($lista, function ($a) use ($awb) {
            return $a !== $awb;
        }));
    } elseif ($action === 'toate') {
        // "Adauga toate": statusul pentru tot ce are clientul, dintr-o apasare.
        $_SESSION['awb_urmarite'] = $pdo
            ->query('SELECT awb FROM expedieri WHERE ClientID = ' . (int) $client['id'])
            ->fetchAll(PDO::FETCH_COLUMN);
    } elseif ($action === 'niciunul') {
        unset($_SESSION['awb_urmarite']);
    }

    header('Location: ' . $pagina);
    exit;
}

// --- Date pentru afisare ---

// Coletele clientului: cate un rand pe expediere, cu produsul din comanda si
// poza lui din catalog. Cele mai noi sus.
$stmt = $pdo->prepare(
    'SELECT e.awb, e.Data_expediere, e.Data_livrare_estimata,
            e.Data_livrare_efectiva, e.Status_expediere,
            r.Oras_origine, r.Oras_destinatie, r.Distanta_km, r.Durata_min,
            cp.Product_ID, cp.Product_Name, cp.Cantitate, cp.Pret_unitar, cp.Subtotal,
            cp.ComandaID, p.poze
       FROM expedieri e
       JOIN rute r ON r.RutaID = e.RutaID
       LEFT JOIN comenzi_produse cp ON cp.LinieID = e.LinieID
       LEFT JOIN produse p ON p.Product_ID = cp.Product_ID
      WHERE e.ClientID = :client
      ORDER BY e.Data_expediere DESC'
);
$stmt->execute(['client' => $client['id']]);
$colete = $stmt->fetchAll();

$urmarite = awb_urmarite();

// "Acum" se ia din DB (acelasi ceas care a scris Data_expediere), ca sa nu apara
// diferente din fusul orar al PHP. O singura citire pentru toata lista.
$acum = $pdo->query('SELECT NOW()')->fetchColumn();

?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LIVRA - Urmarire colet</title>
  <link rel="stylesheet" href="css/app.css?v=<?= filemtime(__DIR__ . '/css/app.css') ?>">
  <?php require_once __DIR__ . '/_analytics.php'; ?>
</head>
<body>

<header class="header">
  <div class="header__inner">
    <a class="logo" href="magazin.php">LIVRA</a>
    <span class="header__subtitle">Urmarire colet</span>
    <nav class="nav">
      <a class="nav__link" href="magazin.php">Produse</a>
      <a class="nav__link" href="cos.php">Cos<?= cos_bucati() ? ' (' . (int) cos_bucati() . ')' : '' ?></a>
      <a class="nav__link nav__link--active" href="portal_client.php">Urmarire colet</a>
      <span class="nav__link" style="color:var(--text-muted)">Salut, <?= h($client['nume']) ?></span>
      <a class="nav__link" href="login.php?logout=1">Iesire</a>
    </nav>
  </div>
</header>

<main class="container">
<!-- Coloana ingusta, centrata: randurile de colet sunt scurte, deci intinse pe
     toata latimea ecranului ar lasa un gol mare in dreapta. -->
<div class="colete">

  <div class="shop-hero">
    <h1 class="shop-hero__title">Coletele mele</h1>
    <p class="shop-hero__subtitle">
      Fiecare rand e un colet din comenzile tale, cu statusul livrarii langa produs.
      Apasa "Detalii" ca sa vezi traseul si timpii.
    </p>
  </div>

  <?php if (!$colete): ?>
    <div class="card"><p class="empty">Nu ai niciun colet expediat inca.</p></div>
  <?php else: ?>

    <div class="awb-form">
      <span class="awb-form__count">
        <?= count($colete) ?> colete &middot; <?= count($urmarite) ?> cu detalii deschise
      </span>
      <form method="post" action="<?= h($pagina) ?>">
        <input type="hidden" name="action" value="toate">
        <button class="btn btn--ghost" type="submit">Detalii la toate</button>
      </form>
      <?php if ($urmarite): ?>
        <form method="post" action="<?= h($pagina) ?>">
          <input type="hidden" name="action" value="niciunul">
          <button class="btn btn--ghost" type="submit">Ascunde toate</button>
        </form>
      <?php endif; ?>
    </div>

    <?php foreach ($colete as $c): ?>
      <?php
        $poza = poza_url($c['poze'] ?? '');
        $adaugat = in_array($c['awb'], $urmarite, true);
        $livrat = $c['Status_expediere'] === 'Livrat';

        // Culoarea etichetei de status: verde livrat, galben inca pe drum,
        // rosu daca ceva n-a mers (intarziat, returnat, anulat).
        $clasaStatus = [
            'Livrat' => 'tag--ok',
            'In tranzit' => 'tag--warn',
            'Intarziat' => 'tag--fail',
            'Returnat' => 'tag--fail',
            'Anulat' => 'tag--fail',
        ][$c['Status_expediere']] ?? '';
      ?>
      <article class="colet">
        <!-- Randul: AWB + poza + nume + pret, apoi butonul de adaugare -->
        <div class="colet__cap">
          <div class="colet__awb">
            <span class="colet__awb-eticheta">AWB</span>
            <strong class="colet__awb-numar"><?= h($c['awb']) ?></strong>
          </div>

          <div class="colet__media<?= $poza ? ' colet__media--photo' : '' ?>">
            <?php if ($poza): ?>
              <img class="colet__img" src="<?= h($poza) ?>" alt="<?= h($c['Product_Name']) ?>" loading="lazy">
            <?php endif; ?>
          </div>

          <div class="colet__produs">
            <h2 class="colet__nume">
              <?= $c['Product_Name'] !== null ? h($c['Product_Name']) : 'Produs indisponibil' ?>
            </h2>
            <?php if ($c['ComandaID'] !== null): ?>
              <p class="colet__pret-detaliu">Comanda #<?= (int) $c['ComandaID'] ?></p>
            <?php endif; ?>
          </div>

          <!-- Statusul sta intre nume si pret si se vede mereu, la orice colet,
               vechi sau nou - nu doar dupa ce s-au cerut detaliile. -->
          <div class="colet__status">
            <span class="tag <?= h($clasaStatus) ?>"><?= h($c['Status_expediere']) ?></span>
          </div>

          <div class="colet__pret-col">
            <?php if ($c['Pret_unitar'] !== null): ?>
              <p class="colet__pret"><?= h(lei($c['Subtotal'])) ?></p>
              <p class="colet__pret-detaliu"><?= (int) $c['Cantitate'] ?> buc &times; <?= h(lei($c['Pret_unitar'])) ?></p>
            <?php else: ?>
              <p class="colet__pret-detaliu">-</p>
            <?php endif; ?>
          </div>

          <div class="colet__actiune">
            <form method="post" action="<?= h($pagina) ?>">
              <input type="hidden" name="action" value="<?= $adaugat ? 'scoate' : 'adauga' ?>">
              <input type="hidden" name="awb" value="<?= h($c['awb']) ?>">
              <?php if ($adaugat): ?>
                <button class="btn btn--ghost" type="submit">Ascunde</button>
              <?php else: ?>
                <button class="btn" type="submit">Detalii</button>
              <?php endif; ?>
            </form>
          </div>
        </div>

        <?php if ($adaugat): ?>
          <?php
            $prestabilit = (int) $c['Durata_min'];
            $trecut = minute_program($c['Data_expediere'], $acum);
            $ramas = max(0, $prestabilit - $trecut);
          ?>
          <table class="table colet__detalii">
            <tbody>
              <tr>
                <td>Traseu</td>
                <td><strong><?= h($c['Oras_origine']) ?> &rarr; <?= h($c['Oras_destinatie']) ?></strong>
                    <span class="field__hint" style="display:inline">(<?= (int) $c['Distanta_km'] ?> km)</span></td>
              </tr>
              <tr>
                <td>Expediat la</td>
                <td><?= h(fmt_eu($c['Data_expediere'])) ?></td>
              </tr>
              <tr>
                <td>Timp prestabilit (ruta)</td>
                <td><strong><?= h(timp_fmt($prestabilit)) ?></strong></td>
              </tr>

              <?php if ($livrat): ?>
                <tr>
                  <td>Livrat la</td>
                  <td><strong><?= $c['Data_livrare_efectiva'] !== null ? h(fmt_eu($c['Data_livrare_efectiva'])) : 'data indisponibila' ?></strong></td>
                </tr>
              <?php else: ?>
                <tr>
                  <td>Timp trecut (program 07:00&ndash;22:00)</td>
                  <td><strong><?= h(timp_fmt($trecut)) ?></strong></td>
                </tr>
                <tr>
                  <td>Timp ramas estimat</td>
                  <td><strong>
                    <?= $ramas > 0 ? h(timp_fmt($ramas)) : 'timp estimat depasit &ndash; inca in tranzit' ?>
                  </strong></td>
                </tr>
                <tr>
                  <td>Livrare estimata</td>
                  <td><?= h(fmt_eu($c['Data_livrare_estimata'])) ?></td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>

  <?php endif; ?>

</div>
</main>

</body>
</html>
