<?php

/**
 * Laborator: ce face algoritmul de optimizare a rutelor si cat de bine a ales.
 *
 * Pagina are doua jumatati:
 *   1. EXPLICATIA - pasii algoritmului si criteriile cu care lucreaza. Pragurile
 *      nu sunt scrise de mana, ci obtinute chemand metodele algoritmului
 *      (`AnalizaRuteService::reguliViteza()` / `reguliTipDrum()`), ca sa nu poata
 *      ramane in urma daca se schimba regulile in cod.
 *   2. PERFORMANTA - masurata pe expedierile din baza: pe cate a plecat cursa pe
 *      ruta cea mai rapida catre orasul ei si cat au costat cele care n-au facut-o.
 *
 * Pentru o ruta anume, fereastra "Algoritm" din `rute.php` arata acelasi calcul
 * pe randul acela; aici e privirea de ansamblu.
 *
 * Server-rendered, fara JS.
 */

require __DIR__ . '/../database/db_connection.php';
require __DIR__ . '/../backend/AnalizaRuteService.php';
require_once __DIR__ . '/_grafic_scara.php';

/** Scapa text pentru HTML. */
function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** 1234.5 -> "1.234,50 lei". */
function lei($value)
{
    return number_format((float) $value, 2, ',', '.') . ' lei';
}

/** 1234 -> "1.234". */
function numar($value, $zecimale = 0)
{
    return number_format((float) $value, $zecimale, ',', '.');
}

/** 1234.5 -> "1.235"; fara zecimale si fara "lei", pentru axa si etichetele barelor. */
function lei_scurt($value)
{
    return number_format((float) $value, 0, ',', '.');
}

/** 155 -> "2 h 35 min"; peste o zi de condus, si in zile de 8 h. */
function durata($minute)
{
    $minute = (int) round($minute);
    $h = intdiv($minute, 60);

    return $h > 0 ? $h . ' h ' . ($minute % 60) . ' min' : $minute . ' min';
}

/**
 * Numarul urmat de substantiv, cu "de" acolo unde cere limba: 18 rute, dar
 * 97 de curse. "De" se pune cand ultimele doua cifre sunt 00 sau 20-99.
 */
function cu_de($numar, $substantiv)
{
    $numar = (int) $numar;
    $ultimele = $numar % 100;
    $deseparte = $numar >= 20 && ($ultimele === 0 || $ultimele >= 20);

    return $numar . ($deseparte ? ' de ' : ' ') . $substantiv;
}

/** Minutele unei ajustari, cu semn: "+30 min", "-30 min", "0 min". */
function cu_semn($minute)
{
    $minute = (int) $minute;

    return ($minute > 0 ? '+' : '') . $minute . ' min';
}

/** Clasa care coloreaza o ajustare: rosu daca adauga timp, verde daca scade. */
function clasa_ajustare($minute)
{
    if ((int) $minute > 0) {
        return 'alg__minute--rau';
    }

    return (int) $minute < 0 ? 'alg__minute--bun' : '';
}

$analiza = new AnalizaRuteService($pdo);

$reguliViteza = $analiza->reguliViteza();
$reguliTip = $analiza->reguliTipDrum();
$performanta = $analiza->performanta();
$comparatie = $analiza->comparatieFinanciara();
$exemplu = $analiza->exempluLive();
$grupe = $analiza->ruteDupaDestinatie();

// Cate criterii intra in timpul ajustat: durata prestabilita + cele trei ajustari.
$criterii = 4;

// Cele doua grafice de comparatie financiara. Seriile sunt aceleasi ca in
// Aceleasi culori ca in Business Intelligence: incasare --s1, cost --s2, profit --s3.
const SERII_BANI = [
    'incasare' => ['titlu' => 'Incasare', 'slot' => 's1'],
    'cost' => ['titlu' => 'Cost', 'slot' => 's2'],
    'profit' => ['titlu' => 'Profit', 'slot' => 's3'],
];

// O SINGURA scara pentru amandoua panourile: cu scari diferite, doua bare de
// inaltimi egale ar insemna sume diferite si comparatia ar fi falsa.
$maxBani = 0;
$minBani = 0;
foreach (['optimizat', 'neoptimizat'] as $model) {
    foreach (array_keys(SERII_BANI) as $serie) {
        $maxBani = max($maxBani, $comparatie[$model][$serie]);
        $minBani = min($minBani, $comparatie[$model][$serie]);
    }
}

// Trei diviziuni, nu patru: pe cifrele de acum umple graficul mai bine.
$scaraBani = scara_y($maxBani, 3);
$pasBani = round(100 / (count($scaraBani['gradatii']) - 1), 3);

// Diferentele dintre cele doua modele, pentru tabel si pentru concluzie.
$diferente = [];
foreach (['incasare', 'cost_marfa', 'cost_carburant', 'cost', 'profit', 'km'] as $cheie) {
    $diferente[$cheie] = $comparatie['optimizat'][$cheie] - $comparatie['neoptimizat'][$cheie];
}

// Cate rute sunt in total; nu se presupune ca fiecare oras are cate una din
// fiecare depozit, se numara ce e in grupe.
$totalRute = 0;
foreach ($grupe as $g) {
    $totalRute += count($g['rute']);
}

$navLinks = [
    'clienti' => ['clienti.php', 'Clienti'],
    'comenzi' => ['comenzi.php', 'Comenzi'],
    'expedieri' => ['expedieri.php', 'Expedieri'],
    'soferi' => ['soferi.php', 'Soferi'],
    'rute' => ['rute.php', 'Rute'],
    'produse' => ['produse.php', 'Produse'],
    'business' => ['business.php', 'Business'],
    'laborator' => ['laborator_rute.php', 'Laborator'],
];

?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PRIMUL - Laborator: algoritm de optimizare rute</title>
  <link rel="stylesheet" href="css/app.css?v=<?= filemtime(__DIR__ . '/css/app.css') ?>">
  <link rel="stylesheet" href="css/business.css?v=<?= filemtime(__DIR__ . '/css/business.css') ?>">
  <link rel="stylesheet" href="css/laborator.css?v=<?= filemtime(__DIR__ . '/css/laborator.css') ?>">
</head>
<body>

<header class="header">
  <div class="header__inner">
    <a class="logo" href="../../index.php">PRIMUL</a>
    <span class="header__subtitle">Laborator</span>
    <nav class="nav">
      <?php foreach ($navLinks as $key => $link): ?>
        <a class="nav__link<?= $key === 'laborator' ? ' nav__link--active' : '' ?>" href="<?= h($link[0]) ?>"><?= h($link[1]) ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
</header>

<main class="container">
  <h1 class="dash__title">Laborator: algoritm de optimizare rute si performanta lui</h1>
  <p class="dash__lead">
    Ce decide algoritmul, dupa ce criterii o face si cat de bine a ales pe cursele deja plecate.
    Cifrele de mai jos se recalculeaza din baza de date la fiecare incarcare a paginii.
  </p>

  <div class="kpi">
    <div class="kpi__item">
      <span class="kpi__label">Criterii de decizie</span>
      <span class="kpi__value"><?= (int) $criterii ?></span>
    </div>
    <div class="kpi__item">
      <span class="kpi__label">Curse analizate</span>
      <span class="kpi__value"><?= (int) $performanta['curse'] ?></span>
    </div>
    <div class="kpi__item">
      <span class="kpi__label">Plecate pe ruta cea mai rapida</span>
      <span class="kpi__value"><?= h(numar($performanta['procent_optim'], 1)) ?>%</span>
    </div>
    <div class="kpi__item">
      <span class="kpi__label">Timp economisit</span>
      <span class="kpi__value kpi__value--plus"><?= h(numar($performanta['minute_economisite'])) ?> min</span>
    </div>
    <div class="kpi__item">
      <span class="kpi__label">Carburant in plus</span>
      <span class="kpi__value kpi__value--minus"><?= h(lei($performanta['lei_in_plus'])) ?></span>
    </div>
  </div>

  <!-- 1. Ce face -->
  <section class="card lab__card">
    <div class="dash__head">
      <h2 class="card__title">Ce face algoritmul</h2>
      <p class="card__desc">
        Raspunde la o singura intrebare: <strong>din ce depozit pleaca acest produs catre acest client?</strong>
        Ruleaza la expedierea unei linii de comanda, in back office.
      </p>
    </div>

    <!-- Pasii la stanga, schema la dreapta. Poza sta in `src/backend/imagini/`,
         acolo unde a pus-o userul; e servita direct de Apache (nu exista .htaccess
         care sa blocheze folderul), deci se leaga cu cale relativa. -->
    <div class="lab__ceface">
      <ol class="lab__pasi">
      <li class="lab__pas">
        <span class="lab__pas-nr">1</span>
        <span class="lab__pas-text">
          <strong>Intrarea:</strong> o linie de comanda - un produs, o cantitate - si orasul clientului.
        </span>
      </li>
      <li class="lab__pas">
        <span class="lab__pas-nr">2</span>
        <span class="lab__pas-text">
          <strong>Candidatii:</strong> depozitele care au produsul pe stoc
          (<?= h(implode(', ', OptimizareRuteService::DEPOZITE)) ?>) si care au ruta catre orasul clientului.
          Un depozit fara stoc nu intra in cursa, oricat de aproape ar fi.
        </span>
      </li>
      <li class="lab__pas">
        <span class="lab__pas-nr">3</span>
        <span class="lab__pas-text">
          <strong>Scorul:</strong> pentru fiecare candidat se calculeaza <em>timpul ajustat</em>, din cele
          patru criterii de mai jos.
        </span>
      </li>
      <li class="lab__pas">
        <span class="lab__pas-nr">4</span>
        <span class="lab__pas-text">
          <strong>Ordonarea:</strong> crescator dupa timpul ajustat. Cel mai mic timp = cea mai optimizata ruta.
        </span>
      </li>
      <li class="lab__pas">
        <span class="lab__pas-nr">5</span>
        <span class="lab__pas-text">
          <strong>Decizia:</strong> lista se arata operatorului, cu explicatia fiecarei rute.
          <strong>Operatorul alege</strong> - algoritmul recomanda, nu expediaza singur.
        </span>
      </li>
      <li class="lab__pas">
        <span class="lab__pas-nr">6</span>
        <span class="lab__pas-text">
          <strong>Iesirea:</strong> expedierea cu AWB unic, sofer (ales aleator), data de livrare estimata
          si costul de carburant al rutei.
        </span>
      </li>
      </ol>

      <figure class="lab__schema">
        <!-- Schema are scris pe ea, iar la 260px textul e mic: link catre poza
             intreaga, deschisa in fila noua, ca sa se poata citi la nevoie. -->
        <a class="lab__schema-link" href="../backend/imagini/schema%20algoritm.jpg" target="_blank" rel="noopener">
          <img class="lab__schema-img"
               src="../backend/imagini/schema%20algoritm.jpg"
               alt="Schema fluxului: comanda vine din magazinul web, intra in algoritmul de ruta optima pornit din back office, iar din el ies ruta aleasa pe harta si expedierea cu duba catre client."
               width="768" height="1024" loading="lazy">
        </a>
        <figcaption class="lab__schema-nota">
          Fluxul, pe scurt: comanda vine din magazin, algoritmul de ruta optima e pornit din back office,
          iar din el ies ruta aleasa si expedierea catre client.
          <a href="../backend/imagini/schema%20algoritm.jpg" target="_blank" rel="noopener">Vezi schema mare</a>.
        </figcaption>
      </figure>
    </div>
  </section>

  <!-- 2. Criteriile -->
  <section class="card lab__card">
    <div class="dash__head">
      <h2 class="card__title">Cu ce criterii lucreaza</h2>
      <p class="card__desc">
        Toate patru se masoara in minute si se aduna intr-un singur scor. Pragurile de mai jos sunt
        citite din algoritm, nu copiate: daca se schimba in cod, se schimba si aici.
      </p>
    </div>

    <div class="lab__criterii">
      <div class="lab__criteriu">
        <h3 class="lab__criteriu-titlu">1. Timpul de condus prestabilit</h3>
        <p class="lab__criteriu-desc">Punctul de plecare al calculului: valoarea din ruta.</p>
        <table class="table alg__tabel">
          <tbody>
            <tr>
              <td><span class="alg__criteriu">rute.Durata_min</span></td>
              <td class="cell--number cell--nowrap">baza</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="lab__criteriu">
        <h3 class="lab__criteriu-titlu">2. Viteza medie a rutei</h3>
        <p class="lab__criteriu-desc">Cu cat se merge mai incet, cu atat penalizarea e mai mare.</p>
        <table class="table alg__tabel">
          <tbody>
            <?php foreach ($reguliViteza as $r): ?>
              <tr>
                <td><span class="alg__criteriu"><?= h($r['interval']) ?></span></td>
                <td class="cell--number cell--nowrap <?= h(clasa_ajustare($r['ajustare'])) ?>"><?= h(cu_semn($r['ajustare'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="lab__criteriu">
        <h3 class="lab__criteriu-titlu">3. Tipul drumului</h3>
        <p class="lab__criteriu-desc">Singurul criteriu care poate si <em>scadea</em> timpul.</p>
        <table class="table alg__tabel">
          <tbody>
            <?php foreach ($reguliTip as $r): ?>
              <tr>
                <td><span class="alg__criteriu"><?= h($r['tip']) ?></span></td>
                <td class="cell--number cell--nowrap <?= h(clasa_ajustare($r['ajustare'])) ?>"><?= h(cu_semn($r['ajustare'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="lab__criteriu">
        <h3 class="lab__criteriu-titlu">4. Vremea, in timp real</h3>
        <p class="lab__criteriu-desc">
          Din API-ul public ANM, pentru ambele orase ale rutei; se ia vremea cea mai grea dintre ele.
        </p>
        <table class="table alg__tabel">
          <tbody>
            <tr><td><span class="alg__criteriu">ninsoare</span></td><td class="cell--number cell--nowrap alg__minute--rau">+60 min</td></tr>
            <tr><td><span class="alg__criteriu">ploaie</span></td><td class="cell--number cell--nowrap alg__minute--rau">+30 min</td></tr>
            <tr><td><span class="alg__criteriu">fara / necunoscut</span></td><td class="cell--number cell--nowrap">+0 min</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="lab__formula">
      <span class="lab__formula-eticheta">Scorul dupa care se ordoneaza</span>
      <code class="lab__formula-cod">timp_ajustat = Durata_min + ajustare_viteza + ajustare_tip + ajustare_vreme</code>
      <span class="lab__formula-nota">Cel mai mic timp ajustat castiga. Distanta si carburantul se arata, dar nu intra in scor.</span>
    </div>
  </section>

  <!-- 3. Exemplu live -->
  <section class="card lab__card">
    <div class="dash__head">
      <h2 class="card__title">Algoritmul, rulat acum</h2>
      <p class="card__desc">
        <?php if ($exemplu['produs'] !== null): ?>
          Ce ar raspunde in clipa asta pentru <strong><?= h($exemplu['produs']['Product_Name']) ?></strong>
          (<?= h($exemplu['produs']['Product_ID']) ?>) catre <strong><?= h($exemplu['oras']) ?></strong>.
          Ajustarea de vreme e cea de acum, deci rezultatul se poate schimba de la o ora la alta.
        <?php else: ?>
          Nu exista produse pe stoc pentru un exemplu.
        <?php endif; ?>
      </p>
    </div>

    <?php if (!$exemplu['rute']): ?>
      <p class="empty">Niciun depozit nu are produsul pe stoc cu ruta catre <?= h($exemplu['oras']) ?>.</p>
    <?php else: ?>
      <table class="table alg__tabel">
        <thead>
          <tr><th>Loc</th><th>Depozit</th><th>Prestabilit</th><th>Viteza</th><th>Drum</th><th>Vreme</th><th>Timp ajustat</th><th class="cell--number">Km</th><th class="cell--number">Carburant</th></tr>
        </thead>
        <tbody>
          <?php foreach ($exemplu['rute'] as $i => $r): ?>
            <tr<?= $i === 0 ? ' class="alg__rand--asta"' : '' ?>>
              <td class="cell--nowrap">
                <?php if ($i === 0): ?>
                  <span class="tag tag--ok">#1 optima</span>
                <?php else: ?>
                  <span class="tag">#<?= $i + 1 ?></span>
                <?php endif; ?>
              </td>
              <td class="cell--nowrap"><?= h($r['Oras_origine']) ?></td>
              <td class="cell--number cell--nowrap"><?= (int) $r['Durata_min'] ?> min</td>
              <td class="cell--number cell--nowrap <?= h(clasa_ajustare($r['ajustare_viteza'])) ?>"><?= h(cu_semn($r['ajustare_viteza'])) ?></td>
              <td class="cell--number cell--nowrap <?= h(clasa_ajustare($r['ajustare_tip'])) ?>"><?= h(cu_semn($r['ajustare_tip'])) ?></td>
              <td class="cell--number cell--nowrap <?= h(clasa_ajustare($r['ajustare_vreme'])) ?>"><?= h(cu_semn($r['ajustare_vreme'])) ?></td>
              <td class="cell--number cell--nowrap"><strong><?= (int) $r['timp_ajustat'] ?> min</strong></td>
              <td class="cell--number"><?= (int) $r['Distanta_km'] ?> km</td>
              <td class="cell--number cell--nowrap"><?= h(lei($r['cost_carburant'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <!-- 4. Performanta masurata -->
  <section class="card lab__card">
    <div class="dash__head">
      <h2 class="card__title">Performanta, masurata pe <?= h(cu_de($performanta['curse'], 'curse')) ?></h2>
      <p class="card__desc">
        Fiecare cursa deja plecata se compara cu toate rutele catre acelasi oras. Se foloseste doar
        partea stabila a scorului (prestabilit + viteza + drum); vremea e din momentul expedierii si
        nu se poate reconstitui, altfel aceeasi cursa ar da alt rezultat la fiecare incarcare.
      </p>
    </div>

    <div class="lab__constatari">
      <div class="lab__constatare">
        <span class="lab__constatare-cifra"><?= h(numar($performanta['procent_optim'], 1)) ?>%</span>
        <span class="lab__constatare-text">
          din curse (<?= (int) $performanta['pe_optim'] ?> din <?= (int) $performanta['curse'] ?>)
          au plecat pe ruta cea mai rapida catre orasul lor.
        </span>
      </div>
      <div class="lab__constatare">
        <span class="lab__constatare-cifra lab__constatare-cifra--bun"><?= h(numar($performanta['minute_economisite'])) ?> min</span>
        <span class="lab__constatare-text">
          economisiti fata de varianta in care s-ar fi ales de fiecare data cel mai prost candidat
          (<?= h(durata($performanta['minute_economisite'])) ?>).
        </span>
      </div>
      <div class="lab__constatare">
        <span class="lab__constatare-cifra lab__constatare-cifra--rau"><?= h(numar($performanta['km_in_plus'])) ?> km</span>
        <span class="lab__constatare-text">
          in plus fata de ruta cea mai rapida: <?= h(numar($performanta['litri_in_plus'])) ?> L motorina,
          <?= h(lei($performanta['lei_in_plus'])) ?>.
        </span>
      </div>
    </div>

    <p class="lab__nota lab__nota--important">
      <strong>Kilometrii in plus nu sunt greseli ale algoritmului.</strong> El alege doar dintre
      depozitele care aveau produsul pe stoc; cand cel mai apropiat depozit nu-l avea, cursa a plecat
      de mai departe. Cifra de mai sus masoara deci <strong>cat costa asezarea marfii in depozite</strong>,
      nu calitatea deciziei. Ca sa scada, produsele trebuie sa stea in depozitul potrivit, nu ruta sa fie aleasa altfel.
    </p>

    <h3 class="lab__titlu">Pe orase</h3>

    <table class="table">
      <thead>
        <tr>
          <th>Oras client</th>
          <th>Depozitul cel mai rapid</th>
          <th class="cell--number">Curse</th>
          <th class="cell--number">De acolo</th>
          <th class="cell--number">Minute in plus</th>
          <th class="cell--number">Km in plus</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($performanta['orase'] as $o): ?>
          <tr>
            <td><strong><?= h($o['oras']) ?></strong></td>
            <td class="cell--nowrap">
              <?= h($o['ruta_optima']) ?>
              <span class="alg__detaliu"><?= (int) $o['timp_optim'] ?> min</span>
            </td>
            <td class="cell--number"><?= (int) $o['curse'] ?></td>
            <td class="cell--number">
              <?= (int) $o['pe_optim'] ?>
              <span class="alg__detaliu"><?= h(numar($o['curse'] > 0 ? $o['pe_optim'] / $o['curse'] * 100 : 0, 0)) ?>%</span>
            </td>
            <td class="cell--number <?= $o['minute_in_plus'] > 0 ? 'cell--minus' : '' ?>"><?= h(numar($o['minute_in_plus'])) ?></td>
            <td class="cell--number <?= $o['km_in_plus'] > 0 ? 'cell--minus' : '' ?>"><?= h(numar($o['km_in_plus'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td>Total</td>
          <td></td>
          <td class="cell--number"><?= (int) $performanta['curse'] ?></td>
          <td class="cell--number"><?= (int) $performanta['pe_optim'] ?></td>
          <td class="cell--number"><?= h(numar($performanta['minute_in_plus'])) ?></td>
          <td class="cell--number"><?= h(numar($performanta['km_in_plus'])) ?></td>
        </tr>
      </tfoot>
    </table>

    <h3 class="lab__titlu">Ce ruta castiga catre fiecare oras</h3>

    <details class="chart__data">
      <summary class="chart__summary">Arata toate cele <?= h(cu_de($totalRute, 'rute')) ?>, grupate pe destinatie</summary>
      <table class="table">
        <thead>
          <tr><th>Destinatie</th><th>Depozit</th><th class="cell--number">Prestabilit</th><th class="cell--number">Timp ajustat</th><th class="cell--number">Km</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($grupe as $oras => $g): ?>
            <?php foreach ($g['rute'] as $i => $r): ?>
              <tr<?= $i === 0 ? ' class="alg__rand--asta"' : '' ?>>
                <td><?= $i === 0 ? '<strong>' . h($oras) . '</strong>' : '' ?></td>
                <td class="cell--nowrap"><?= h($r['Oras_origine']) ?></td>
                <td class="cell--number cell--nowrap"><?= (int) $r['Durata_min'] ?> min</td>
                <td class="cell--number cell--nowrap"><?= (int) $r['timp'] ?> min</td>
                <td class="cell--number"><?= (int) $r['Distanta_km'] ?> km</td>
                <td>
                  <?php if ($i === 0): ?>
                    <span class="tag tag--ok">cea mai rapida</span>
                  <?php endif; ?>
                  <?php if ((int) $r['Distanta_km'] === (int) $g['best_km'] && $i !== 0): ?>
                    <span class="tag">cea mai scurta</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </details>

    <p class="lab__nota">
      <?php if (!$performanta['conflicte_timp_km']): ?>
        Pe reteaua de acum, ruta cea mai rapida catre un oras e si cea mai scurta, in
        <strong>toate cele <?= count($grupe) ?> orase</strong>. Criteriul de timp nu costa deci
        niciun kilometru in plus - dar asta e o proprietate a datelor, nu o garantie a algoritmului:
        daca apare o ruta lunga si rapida, timpul ar castiga in fata carburantului.
      <?php else: ?>
        In <?= count($performanta['conflicte_timp_km']) ?> orase
        (<?= h(implode(', ', $performanta['conflicte_timp_km'])) ?>) ruta cea mai rapida
        <strong>nu</strong> e si cea mai scurta: acolo criteriul de timp costa kilometri, deci carburant.
      <?php endif; ?>
      Pentru calculul pe o ruta anume, vezi butonul <strong>Algoritm</strong> din
      <a href="rute.php">pagina de rute</a>.
    </p>
  </section>

  <!-- 5. Comparatia financiara a celor doua modele -->
  <section class="card lab__card">
    <div class="dash__head">
      <h2 class="card__title">Performanta financiara: cu si fara optimizare</h2>
      <p class="card__desc">
        Aceleasi <?= h(cu_de($comparatie['livrari'], 'livrari')) ?> facute, socotite in doua feluri.
        <strong>Stanga</strong>: rutele pe care au plecat coletele cu adevarat, cu costul de carburant
        inregistrat pe expediere. <strong>Dreapta</strong>: aceleasi colete, dar plecate fara algoritm -
        media tuturor rutelor catre orasul lor, adica ce iese daca alegi un depozit la intamplare.
        Amandoua graficele au aceeasi scara, deci inaltimile se pot compara direct.
      </p>
    </div>

    <div class="legend">
      <?php foreach (SERII_BANI as $serie): ?>
        <span class="legend__item"><span class="swatch swatch--<?= h($serie['slot']) ?>"></span><?= h($serie['titlu']) ?></span>
      <?php endforeach; ?>
    </div>

    <div class="lab__comparatie">
      <?php
        $panouri = [
            'optimizat' => ['titlu' => 'Rute optimizate', 'sub' => 'ce s-a intamplat cu adevarat'],
            'neoptimizat' => ['titlu' => 'Rute neoptimizate', 'sub' => 'media rutelor catre acelasi oras'],
        ];
      ?>
      <?php foreach ($panouri as $model => $panou): ?>
        <div class="lab__panou">
          <h3 class="lab__panou-titlu">
            <?= h($panou['titlu']) ?>
            <span class="lab__panou-sub"><?= h($panou['sub']) ?></span>
          </h3>

          <div class="chart chart--bani">
            <div class="chart__yaxis">
              <?php foreach ($scaraBani['gradatii'] as $i => $g): ?>
                <span class="chart__tick" style="bottom: <?= round(100 - $i * 100 / (count($scaraBani['gradatii']) - 1), 2) ?>%"><?= h(lei_scurt($g)) ?></span>
              <?php endforeach; ?>
            </div>

            <div class="chart__plot" style="--pas: <?= $pasBani ?>%">
              <div class="chart__cols">
                <?php foreach (SERII_BANI as $cheie => $serie): ?>
                  <?php $valoare = (float) $comparatie[$model][$cheie]; ?>
                  <div class="chart__col">
                    <div class="chart__bar chart__bar--<?= h($serie['slot']) ?><?= $valoare > 0 ? ' chart__bar--minim' : '' ?>"
                         style="height: <?= inaltime($valoare, $scaraBani['max']) ?>%">
                      <!-- Eticheta poarta valoarea reala si cand bara nu se poate
                           desena (profit negativ), ca cifra sa nu fie citita gresit. -->
                      <span class="chart__value<?= $valoare < 0 ? ' cell--minus' : '' ?>"><?= h(lei_scurt($valoare)) ?></span>
                      <span class="chart__tip"><?= h($panou['titlu']) ?> &middot; <?= h($serie['titlu']) ?>: <?= h(lei($valoare)) ?></span>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="chart__xaxis">
              <?php foreach (SERII_BANI as $serie): ?>
                <span class="chart__xlabel"><?= h($serie['titlu']) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="lab__constatari">
      <div class="lab__constatare">
        <span class="lab__constatare-cifra lab__constatare-cifra--bun"><?= h(lei($diferente['profit'])) ?></span>
        <span class="lab__constatare-text">
          profit in plus adus de optimizare, pe aceleasi livrari
          (<?= h(numar($comparatie['neoptimizat']['profit'] > 0 ? $diferente['profit'] / $comparatie['neoptimizat']['profit'] * 100 : 0, 1)) ?>%
          peste varianta fara algoritm).
        </span>
      </div>
      <div class="lab__constatare">
        <span class="lab__constatare-cifra"><?= h(lei(-$diferente['cost_carburant'])) ?></span>
        <span class="lab__constatare-text">
          carburant economisit - de aici vine aproape tot castigul:
          <?= h(numar(-$diferente['km'])) ?> km mai putin de condus.
        </span>
      </div>
      <div class="lab__constatare">
        <span class="lab__constatare-cifra"><?= h(lei($comparatie['optimizat']['incasare'])) ?></span>
        <span class="lab__constatare-text">
          incasari, <strong>identice</strong> in ambele modele: vanzarea nu depinde de drumul ales,
          deci tot ce se schimba e costul.
        </span>
      </div>
    </div>

    <p class="lab__nota lab__nota--important">
      <strong>De ce "media" si nu "cea mai proasta ruta".</strong> Fara algoritm nu alegi anume varianta
      cea mai rea, ci una oarecare - media candidatilor e rezultatul la care te astepti. Comparatia cu
      cel mai prost candidat ar arata un castig mult mai mare, dar n-ar fi cinstita.
    </p>

    <details class="chart__data">
      <summary class="chart__summary">Vezi cifrele</summary>
      <table class="table">
        <thead>
          <tr><th></th><th class="cell--number">Rute optimizate</th><th class="cell--number">Rute neoptimizate</th><th class="cell--number">Diferenta</th></tr>
        </thead>
        <tbody>
          <?php
            $randuri = [
                'incasare' => 'Incasare',
                'cost_marfa' => 'Cost marfa (achizitie)',
                'cost_carburant' => 'Cost carburant',
                'cost' => 'Cost total',
                'profit' => 'Profit',
            ];
          ?>
          <?php foreach ($randuri as $cheie => $eticheta): ?>
            <?php $totalizator = in_array($cheie, ['cost', 'profit'], true); ?>
            <tr>
              <td><?= $totalizator ? '<strong>' . h($eticheta) . '</strong>' : h($eticheta) ?></td>
              <td class="cell--number"><?= h(lei($comparatie['optimizat'][$cheie])) ?></td>
              <td class="cell--number"><?= h(lei($comparatie['neoptimizat'][$cheie])) ?></td>
              <td class="cell--number <?= $diferente[$cheie] < 0 ? 'cell--minus' : '' ?>">
                <?= $diferente[$cheie] > 0 ? '+' : '' ?><?= h(lei($diferente[$cheie])) ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <tr>
            <td>Kilometri condusi</td>
            <td class="cell--number"><?= h(numar($comparatie['optimizat']['km'])) ?> km</td>
            <td class="cell--number"><?= h(numar($comparatie['neoptimizat']['km'])) ?> km</td>
            <td class="cell--number <?= $diferente['km'] < 0 ? 'cell--minus' : '' ?>">
              <?= $diferente['km'] > 0 ? '+' : '' ?><?= h(numar($diferente['km'])) ?> km
            </td>
          </tr>
        </tbody>
      </table>
    </details>

    <p class="lab__nota">
      Costul marfii se schimba si el putin intre modele: alt depozit de plecare inseamna alt cost de
      achizitie pentru acelasi produs. Pretul carburantului e acelasi in ambele - se ia din expedierea
      reala (lei inregistrati impartiti la km parcursi), nu din API, ca varianta imaginara sa nu fie
      socotita la alt pret. Intra numai expedierile livrate, ca in rapoartele de bani din dashboard;
      de aceea coloana din stanga da exact cifrele din Business Intelligence si analiza de date.
    </p>
  </section>

  <!-- 6. Limite -->
  <section class="card lab__card">
    <div class="dash__head">
      <h2 class="card__title">Ce nu face algoritmul</h2>
      <p class="card__desc">Limitele lui de acum - utile de stiut cand se citesc cifrele de mai sus.</p>
    </div>

    <ul class="lab__limite">
      <li>
        <strong>Nu optimizeaza costul.</strong> Scorul e numai timp. Distanta si carburantul se
        calculeaza si se arata, dar nu schimba ordinea. O ruta cu 200 km mai mult castiga daca
        ajunge cu un minut mai devreme.
      </li>
      <li>
        <strong>Nu grupeaza coletele.</strong> Fiecare linie de comanda se optimizeaza separat si
        pleaca in cursa ei, cu plinul ei - o comanda cu patru produse face patru drumuri.
      </li>
      <li>
        <strong>Nu alege soferul.</strong> Soferul se trage la sorti dintre toti, indiferent de
        orasul lui de baza sau de cate curse are deja.
      </li>
      <li>
        <strong>Nu muta marfa.</strong> Ia stocul asa cum e; nu propune sa se aduca produsul in
        depozitul apropiat de client, desi acolo se pierd cei
        <?= h(numar($performanta['km_in_plus'])) ?> km in plus.
      </li>
      <li>
        <strong>Nu tine minte vremea.</strong> Ajustarea meteo se ia in timp real la expediere si nu
        se salveaza, deci nu se poate verifica mai tarziu ce a influentat decizia.
      </li>
    </ul>
  </section>
</main>

</body>
</html>
