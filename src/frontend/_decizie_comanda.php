<?php

/**
 * Continutul ferestrei de decizie pentru o comanda (inclus din `comenzi.php`).
 *
 * Se deschide numai pentru comenzile care inca n-au plecat nicaieri. Arata cat
 * ar incasa si cat ar costa comanda daca ar fi expediata acum, produs cu produs,
 * si spune ce inseamna fiecare din cele doua optiuni.
 *
 * Variabile asteptate: $comanda (randul de comanda), $repoComenzi
 * (ComandaRepository) si $optimizare (OptimizareRuteService). Helperii `h()` si
 * `fmt_money()` vin din `_crud_page.php`.
 */

$f = $repoComenzi->situatieFinanciara($comanda, $optimizare);
$inregistrat = $repoComenzi->financiarInregistrat($comanda['ComandaID']);
$deschisa = $repoComenzi->esteDeschisa($comanda);
$pierdere = $f['profit'] < 0;

?>
<p class="alg__context">
  <?= h($comanda['ClientNume']) ?> &middot; <?= h($comanda['ClientOras']) ?> &middot;
  <?= (int) $comanda['NrLinii'] ?> produse &middot; status
  <span class="tag"><?= h($comanda['Status']) ?></span>
</p>

<p class="alg__verdict">
  <?php if ($repoComenzi->poateFiRedeschisa($comanda)): ?>
    <span class="tag tag--fail">Anulata</span>
    Comanda a fost anulata si n-a expediat nimic, deci nu s-a pierdut nimic:
    <strong>Redeschide</strong> o pune la loc pe "Noua" si poti decide din nou.
  <?php elseif (!$deschisa): ?>
    <span class="tag">Decisa</span>
    Comanda nu mai e deschisa: are statusul <strong><?= h($comanda['Status']) ?></strong><?php
      ?><?= (int) $comanda['NrExpediate'] > 0 ? ' si ' . (int) $comanda['NrExpediate'] . ' linii deja expediate' : '' ?>.
  <?php elseif ($pierdere): ?>
    <span class="tag tag--fail">Neprofitabil</span>
    Daca pleaca acum, comanda costa mai mult decat aduce: incasezi
    <strong><?= h(fmt_money($f['incasare'])) ?></strong> si cheltui
    <strong><?= h(fmt_money($f['cost'])) ?></strong>, deci pierzi
    <strong class="alg__minute--rau"><?= h(fmt_money(-$f['profit'])) ?></strong>.
    Estimarea e doar o informatie: <strong>Trimite</strong> accepta comanda oricum, daca asa decizi.
  <?php else: ?>
    <span class="tag tag--ok">Profitabil</span>
    Daca pleaca acum, comanda aduce
    <strong class="alg__minute--bun"><?= h(fmt_money($f['profit'])) ?></strong> profit:
    incasezi <?= h(fmt_money($f['incasare'])) ?> si cheltui <?= h(fmt_money($f['cost'])) ?>.
  <?php endif; ?>
</p>

<table class="table alg__tabel">
  <thead>
    <tr><th>Produs</th><th>Ruta optima</th><th>Incasare</th><th>Marfa</th><th>Carburant</th><th>Profit</th></tr>
  </thead>
  <tbody>
    <?php foreach ($f['linii'] as $l): ?>
      <tr>
        <td><?= (int) $l['cantitate'] ?> x <?= h($l['produs']) ?></td>
        <td class="cell--nowrap">
          <?php if ($l['ruta'] === null): ?>
            <span class="tag tag--warn">Fara stoc</span>
          <?php else: ?>
            <?= h($l['ruta']) ?> <span class="alg__detaliu"><?= (int) $l['km'] ?> km</span>
          <?php endif; ?>
        </td>
        <td class="cell--number cell--nowrap"><?= h(fmt_money($l['subtotal'])) ?></td>
        <td class="cell--number cell--nowrap"><?= h(fmt_money($l['marfa'])) ?></td>
        <td class="cell--number cell--nowrap"><?= h(fmt_money($l['carburant'])) ?></td>
        <td class="cell--number cell--nowrap <?= $l['profit'] < 0 ? 'alg__minute--rau' : 'alg__minute--bun' ?>">
          <?= h(fmt_money($l['profit'])) ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr>
      <td>Total</td>
      <td></td>
      <td class="cell--number cell--nowrap"><?= h(fmt_money($f['incasare'])) ?></td>
      <td class="cell--number cell--nowrap"><?= h(fmt_money($f['marfa'])) ?></td>
      <td class="cell--number cell--nowrap"><?= h(fmt_money($f['carburant'])) ?></td>
      <td class="cell--number cell--nowrap <?= $pierdere ? 'alg__minute--rau' : 'alg__minute--bun' ?>">
        <?= h(fmt_money($f['profit'])) ?>
      </td>
    </tr>
  </tfoot>
</table>

<?php if ($inregistrat !== null): ?>
  <p class="alg__nota">
    Cifrele au fost inregistrate la <?= h(fmt_date($inregistrat['Data_inregistrare'])) ?>:
    incasare <?= h(fmt_money($inregistrat['incasare'])) ?>,
    marfa <?= h(fmt_money($inregistrat['cost_marfa'])) ?>,
    carburant <?= h(fmt_money($inregistrat['cost_carburant'])) ?>,
    profit <?= h(fmt_money($inregistrat['profit'])) ?>.
  </p>
<?php elseif ($deschisa): ?>
  <p class="alg__nota">
    Cifrele sunt o estimare: comanda inca n-are expedieri, deci carburantul e socotit pe ruta pe
    care ar alege-o algoritmul pentru fiecare produs, la pretul motorinei de azi.
    <strong>Trimite</strong> le inregistreaza si trece comanda pe "In procesare", gata de expediat.
    <strong>Anuleaza</strong> nu inregistreaza nimic: comanda trece pe "Anulata" si nu mai pleaca.
  </p>
<?php endif; ?>
