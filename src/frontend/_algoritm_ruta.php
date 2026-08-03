<?php

/**
 * Continutul ferestrei "Algoritm" de la o expediere (inclus din `expedieri.php`).
 *
 * Arata de ce a ajuns coletul pe traseul asta: verdictul algoritmului si un
 * tabel de KPI cu o coloana pentru fiecare ruta candidata catre acelasi oras,
 * cea aleasa fiind marcata. Randurile tabelului sunt indicatorii, deci acelasi
 * KPI se compara intre rute citind pe orizontala.
 *
 * Variabile asteptate: $rutaRand (randul din `rute`), $optimizare
 * (OptimizareRuteService), $tipuriStrada (numele citibile ale tipurilor de drum)
 * si, pentru partea de bani, $expediere (randul de expediere) + $repoExpedieri
 * (ExpediereRepository, pentru costul marfii la fiecare depozit). Fara ele se
 * arata doar partea de timp. Helperii `h()` si `fmt_money()` vin din
 * `_crud_page.php`.
 */

if ($rutaRand === null) {
    echo '<p class="empty">Ruta expedierii nu mai exista.</p>';
    return;
}

$explicatie = $optimizare->explicaRuta($rutaRand);
$ruta = $explicatie['ruta'];

/** Minutele unei ajustari, cu semn: "+30 min", "-30 min", "0 min". */
$cuSemn = function ($minute) {
    $minute = (int) $minute;

    return ($minute > 0 ? '+' : '') . $minute . ' min';
};

?>
<?php if (!empty($expediere)): ?>
  <p class="alg__context">
    Coletul <strong><?= h($expediere['awb']) ?></strong> a plecat pe
    <strong><?= h($ruta['Oras_origine']) ?> → <?= h($ruta['Oras_destinatie']) ?></strong>
    la <?= h(fmt_date($expediere['Data_expediere'])) ?>,
    cu <?= h($expediere['SoferNume']) ?>.
    Estimat: <?= h(fmt_date($expediere['Data_livrare_estimata'])) ?>.
    <?php if (!empty($expediere['Data_livrare_efectiva'])): ?>
      Livrat: <?= h(fmt_date($expediere['Data_livrare_efectiva'])) ?>.
    <?php endif; ?>
  </p>
<?php endif; ?>

<?php
// Fiecare numar din verdict e insotit de ruta lui, ca sa nu se confunde un timp
// (cat dureaza o ruta) cu o diferenta (cu cat e o ruta mai buna decat alta).
$urmatoarea = isset($explicatie['clasament'][1]) ? $explicatie['clasament'][1] : null;
?>
<p class="alg__verdict">
  <?php if ($explicatie['loc'] === 1): ?>
    <span class="tag tag--ok">Ruta castigatoare</span>
    Ruta asta face <strong><?= (int) $ruta['timp_ajustat'] ?> min</strong>, cel mai mic timp ajustat
    dintre toate rutele catre <strong><?= h($ruta['Oras_destinatie']) ?></strong>, deci algoritmul o pune prima.
    <?php if ($urmatoarea !== null): ?>
      Urmatoarea, din <?= h($urmatoarea['Oras_origine']) ?>, ar face
      <strong><?= (int) $urmatoarea['timp_ajustat'] ?> min</strong>,
      adica cu <?= (int) $urmatoarea['timp_ajustat'] - (int) $ruta['timp_ajustat'] ?> min mai mult.
    <?php endif; ?>
  <?php else: ?>
    <span class="tag tag--warn">Locul <?= (int) $explicatie['loc'] ?></span>
    Ruta asta face <strong><?= (int) $ruta['timp_ajustat'] ?> min</strong>. Catre
    <strong><?= h($ruta['Oras_destinatie']) ?></strong> algoritmul ar alege ruta din
    <strong><?= h($explicatie['castigator']['Oras_origine']) ?></strong>, care face
    <strong><?= (int) $explicatie['castigator']['timp_ajustat'] ?> min</strong>,
    adica cu <?= (int) $explicatie['diferenta'] ?> min mai putin.
    Ruta asta se foloseste doar daca depozitul castigator nu are produsul pe stoc.
  <?php endif; ?>
</p>

<h3 class="alg__titlu">KPI pe fiecare ruta catre <?= h($ruta['Oras_destinatie']) ?></h3>

<?php
// Tabelul se citeste pe verticala: o coloana pentru fiecare ruta candidata, cu
// ruta scrisa in cap de coloana, si cate un rand pentru fiecare indicator. Asa
// se compara acelasi KPI intre rute, cu ochiul pe orizontala.
$primul = $explicatie['clasament'][0];

// --- Banii, pe fiecare ruta ---
//
// Pe ruta selectata sunt cifrele reale ale expedierii: incasarea si costul de
// carburant inregistrate atunci. Pe celelalte e o simulare: acelasi colet, dus
// pe alta ruta, cu marfa luata din depozitul acela si cu carburantul calculat la
// pretul de azi. Marfa difera de la o coloana la alta fiindca fiecare depozit
// isi are propriul cost de achizitie.
$areBani = !empty($expediere) && !empty($expediere['LinieID']) && !empty($repoExpedieri);
$economie = [];

if ($areBani) {
    $incasare = (float) $expediere['Valoare_expediere'];

    foreach ($explicatie['clasament'] as $i => $r) {
        $real = !empty($r['este_asta']);
        $marfa = $repoExpedieri->costMarfa($expediere['LinieID'], $r['Oras_origine']);
        $carburant = $real ? (float) $expediere['cost_carburant'] : (float) $r['cost_carburant'];
        $cost = $marfa + $carburant;

        $economie[$i] = [
            'real' => $real,
            'incasare' => $incasare,
            'marfa' => $marfa,
            'carburant' => $carburant,
            'cost' => $cost,
            'profit' => $incasare - $cost,
        ];
    }
}

/** Valoarea unui KPI pentru o ruta, gata formatata. */
$kpiuri = [
    [
        'nume' => 'Loc dupa algoritm',
        'valoare' => function ($r, $i) {
            return $i + 1;
        },
    ],
    [
        'nume' => 'Timp ajustat',
        'tare' => true,
        'valoare' => function ($r) {
            return (int) $r['timp_ajustat'] . ' min';
        },
    ],
    [
        'nume' => 'Fata de locul 1',
        'valoare' => function ($r) use ($primul) {
            $d = (int) $r['timp_ajustat'] - (int) $primul['timp_ajustat'];

            return $d === 0 ? '-' : '+' . $d . ' min';
        },
    ],
    [
        'nume' => 'Timp prestabilit',
        'valoare' => function ($r) {
            return (int) $r['Durata_min'] . ' min';
        },
    ],
    [
        'nume' => 'Viteza',
        'valoare' => function ($r) use ($cuSemn) {
            return (int) $r['viteza'] . ' km/h (' . $cuSemn($r['ajustare_viteza']) . ')';
        },
    ],
    [
        'nume' => 'Tip drum',
        'valoare' => function ($r) use ($cuSemn, $tipuriStrada) {
            return ($tipuriStrada[$r['tip_strada']] ?? $r['tip_strada']) . ' (' . $cuSemn($r['ajustare_tip']) . ')';
        },
    ],
    [
        'nume' => 'Vreme acum',
        'valoare' => function ($r) use ($cuSemn) {
            return $r['vreme_text'] . ' (' . $cuSemn($r['ajustare_vreme']) . ')';
        },
    ],
    [
        'nume' => 'Distanta',
        'valoare' => function ($r) {
            return (int) $r['Distanta_km'] . ' km';
        },
    ],
    [
        'nume' => 'Motorina',
        'valoare' => function ($r) use ($optimizare) {
            return number_format($optimizare->litri($r['Distanta_km']), 1, ',', '.') . ' L';
        },
    ],
    [
        'nume' => 'Cost carburant',
        'valoare' => function ($r, $i) use ($areBani, $economie) {
            return fmt_money($areBani ? $economie[$i]['carburant'] : $r['cost_carburant']);
        },
    ],
];

// Randurile de bani apar doar cand fereastra e deschisa de la o expediere:
// fara colet nu exista incasare, deci nici profit.
if ($areBani) {
    $kpiuri[] = [
        'nume' => 'Cost marfa',
        'valoare' => function ($r, $i) use ($economie) {
            return fmt_money($economie[$i]['marfa']);
        },
    ];
    $kpiuri[] = [
        'nume' => 'Cost total',
        'tare' => true,
        'valoare' => function ($r, $i) use ($economie) {
            return fmt_money($economie[$i]['cost']);
        },
    ];
    $kpiuri[] = [
        'nume' => 'Incasare',
        'valoare' => function ($r, $i) use ($economie) {
            return fmt_money($economie[$i]['incasare']);
        },
    ];
    $kpiuri[] = [
        'nume' => 'Profit',
        'tare' => true,
        'clasa' => function ($i) use ($economie) {
            return $economie[$i]['profit'] < 0 ? 'alg__minute--rau' : 'alg__minute--bun';
        },
        'valoare' => function ($r, $i) use ($economie) {
            return fmt_money($economie[$i]['profit']);
        },
    ];
}
?>

<table class="table alg__kpi">
  <thead>
    <tr>
      <th>KPI</th>
      <?php foreach ($explicatie['clasament'] as $r): ?>
        <th class="<?= $r['este_asta'] ? 'alg__col--selectata' : '' ?>">
          <span class="alg__ruta"><?= h($r['Oras_origine']) ?> → <?= h($r['Oras_destinatie']) ?></span>
          <?php if ($r['este_asta']): ?>
            <span class="tag tag--ok">Selectata</span>
          <?php else: ?>
            <span class="tag">Normala</span>
          <?php endif; ?>
        </th>
      <?php endforeach; ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($kpiuri as $kpi): ?>
      <tr>
        <th scope="row" class="alg__kpi-nume"><?= h($kpi['nume']) ?></th>
        <?php foreach ($explicatie['clasament'] as $i => $r): ?>
          <td class="cell--nowrap <?= $r['este_asta'] ? 'alg__col--selectata' : '' ?><?= !empty($kpi['tare']) ? ' alg__kpi--tare' : '' ?><?= isset($kpi['clasa']) ? ' ' . h($kpi['clasa']($i)) : '' ?>">
            <?= h($kpi['valoare']($r, $i)) ?>
          </td>
        <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>

    <?php if ($areBani): ?>
      <?php
        // Toate graficele impart aceeasi scara, altfel doua coloane alaturate
        // ar arata bare la fel de inalte pentru sume diferite. Scara are si
        // partea de sub zero, cat sa incapa cea mai mare pierdere.
        $maxPozitiv = 0.0;
        $maxNegativ = 0.0;

        foreach ($economie as $ec) {
            $maxPozitiv = max($maxPozitiv, $ec['cost'], $ec['incasare'], $ec['profit']);
            $maxNegativ = max($maxNegativ, -$ec['profit']);
        }

        $intervalBani = $maxPozitiv + $maxNegativ;
        $zeroPct = $intervalBani > 0 ? round($maxNegativ / $intervalBani * 100, 2) : 0;

        /** Inaltimea si pozitia unei bare, in procente din inaltimea graficului. */
        $bara = function ($valoare) use ($intervalBani, $zeroPct) {
            if ($intervalBani <= 0) {
                return ['h' => 0, 'jos' => $zeroPct];
            }

            $h = round(abs($valoare) / $intervalBani * 100, 2);

            return $valoare >= 0
                ? ['h' => $h, 'jos' => $zeroPct]
                : ['h' => $h, 'jos' => round($zeroPct - $h, 2)];
        };
      ?>
      <tr>
        <th scope="row" class="alg__kpi-nume">
          Cost, incasare si profit
          <span class="alg__legenda">
            <span class="alg__cheie"><span class="alg__punct alg__punct--cost"></span>Cost</span>
            <span class="alg__cheie"><span class="alg__punct alg__punct--incasare"></span>Incasare</span>
            <span class="alg__cheie"><span class="alg__punct alg__punct--profit"></span>Profit</span>
          </span>
        </th>

        <?php foreach ($explicatie['clasament'] as $i => $r): ?>
          <?php $ec = $economie[$i]; ?>
          <td class="alg__grafic-celula <?= $r['este_asta'] ? 'alg__col--selectata' : '' ?>">
            <div class="alg__grafic" style="--zero: <?= $zeroPct ?>%">
              <span class="alg__zero" style="bottom: <?= $zeroPct ?>%"></span>

              <?php foreach ([
                  ['cheie' => 'cost', 'nume' => 'Cost', 'valoare' => $ec['cost']],
                  ['cheie' => 'incasare', 'nume' => 'Incasare', 'valoare' => $ec['incasare']],
                  ['cheie' => 'profit', 'nume' => 'Profit', 'valoare' => $ec['profit']],
              ] as $b): ?>
                <?php $poz = $bara($b['valoare']); ?>
                <span class="alg__slot">
                  <span class="alg__bara alg__bara--<?= h($b['cheie']) ?><?= $b['valoare'] < 0 ? ' alg__bara--minus' : '' ?>"
                        style="height: <?= $poz['h'] ?>%; bottom: <?= $poz['jos'] ?>%"
                        title="<?= h($b['nume'] . ': ' . fmt_money($b['valoare'])) ?>"></span>
                </span>
              <?php endforeach; ?>
            </div>

            <span class="alg__fel"><?= $ec['real'] ? 'cifre reale' : 'simulare' ?></span>
          </td>
        <?php endforeach; ?>
      </tr>
    <?php endif; ?>
  </tbody>
</table>

<p class="alg__nota">
  Coloanele sunt asezate dupa timpul ajustat, criteriul dupa care alege algoritmul; "Selectata" e
  ruta pe care a plecat coletul, restul sunt rutele normale catre acelasi oras. La o expediere reala
  intra in cursa doar depozitele care au produsul comandat pe stoc, deci ruta selectata poate fi alta
  decat locul 1. Ajustarea de vreme se recalculeaza la fiecare deschidere, cu datele de acum.
  <?php if ($areBani): ?>
    Banii de pe coloana selectata sunt cei reali ai expedierii (incasarea si carburantul inregistrate
    la plecare); pe celelalte coloane e o simulare a aceluiasi colet dus pe ruta aceea, cu marfa luata
    din depozitul ei si cu carburantul la pretul de azi. Toate graficele au aceeasi scara, deci
    inaltimile se pot compara intre coloane.
  <?php endif; ?>
</p>
