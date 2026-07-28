<?php

/**
 * Utilitar de test: creeaza expedieri pentru liniile de comanda care inca nu au
 * una, ca sa avem date complete de testat (comenzi -> expedieri -> livrare).
 *
 * Nu inventeaza date: foloseste aceleasi clase ca back office-ul
 * (`OptimizareRuteService` pentru ruta + AWB, `ExpediereRepository` pentru
 * livrare si scaderea stocului), deci rezultatul e identic cu ce ar iesi daca
 * un operator ar apasa manual "Expediaza" pentru fiecare linie.
 *
 * Pasi:
 *   1. creeaza expedierea lipsa pentru fiecare linie (comenzile "Anulata" se sar),
 *      pe ruta cea mai optimizata;
 *   2. aliniaza datele la comanda: expedierea pleaca la 2 h de program dupa
 *      comanda, iar livrarea estimata se muta cu acelasi timp de mers. Altfel
 *      toate expedierile ar pleca "acum", desi comenzile sunt din zilele trecute;
 *   3. expedierile cu livrarea estimata deja trecuta se marcheaza "Livrat"
 *      (prin repository, deci se scade si stocul), restul raman "In tranzit";
 *   4. comanda cu toate liniile expediate trece pe statusul "Trimisa".
 *
 * ATENTIE: pasul 2 rescrie datele TUTUROR expedierilor in functie de comanda lor.
 * E un utilitar pentru date de test, nu de rulat peste date reale de productie.
 * Rulabil de mai multe ori: rezultatul e acelasi (nu dubleaza expedieri).
 *
 * Rulare: php tools/seed_expedieri_test.php
 */

require __DIR__ . '/../src/database/db_connection.php';
require __DIR__ . '/../src/backend/OptimizareRuteService.php';
require __DIR__ . '/../src/backend/ExpediereRepository.php';

/** Cat sta comanda in depozit pana pleaca (minute de program). */
const MINUTE_PANA_LA_EXPEDIERE = 120;

$service = new OptimizareRuteService($pdo);
$expedieri = new ExpediereRepository($pdo);

/**
 * Cate minute de program (07:00-22:00) sunt intre doua momente. E inversul lui
 * OptimizareRuteService::adaugaMinuteProgram, ca sa putem muta o expediere in
 * alta zi fara sa pierdem timpul real de mers.
 */
function minute_program_intre($start, $end)
{
    $minute = 0;
    $pas = 60;

    // Cauta grosier (din ora in ora), apoi fin (din minut in minut).
    while (OptimizareRuteService::adaugaMinuteProgram($start, $minute + $pas) <= $end) {
        $minute += $pas;
    }

    while (OptimizareRuteService::adaugaMinuteProgram($start, $minute + 1) <= $end) {
        $minute++;
    }

    return $minute;
}

// --- 1. Expedieri pentru liniile care nu au inca una ---

$linii = $pdo->query(
    'SELECT cp.LinieID, cp.ComandaID, cp.Product_ID, cp.Product_Name, cp.Cantitate,
            cl.Nume AS ClientNume, cl.Oras AS OrasClient
     FROM comenzi_produse cp
     JOIN comenzi co ON co.ComandaID = cp.ComandaID
     JOIN clienti cl ON cl.ClientID = co.ClientID
     LEFT JOIN expedieri e ON e.LinieID = cp.LinieID
     WHERE co.Status <> \'Anulata\' AND e.ExpediereID IS NULL
     ORDER BY cp.ComandaID, cp.LinieID'
)->fetchAll();

echo "1. Linii fara expediere: " . count($linii) . "\n";

$create = 0;
foreach ($linii as $l) {
    $rute = $service->ruteOptimizate($l['Product_ID'], $l['OrasClient']);

    if (!$rute) {
        echo '   [!] Comanda #' . $l['ComandaID'] . ' - ' . $l['Product_Name']
            . ': nicio ruta catre ' . $l['OrasClient'] . " (stoc 0 in depozite?)\n";
        continue;
    }

    $rezultat = $service->creeazaExpediere($l['LinieID'], $rute[0]['RutaID']);

    if ($rezultat['errors']) {
        echo '   [!] Comanda #' . $l['ComandaID'] . ' - ' . $l['Product_Name'] . ': '
            . implode(' ', $rezultat['errors']) . "\n";
        continue;
    }

    $create++;
    echo '   OK  Comanda #' . $l['ComandaID'] . ' (' . $l['ClientNume'] . ') - '
        . $l['Cantitate'] . ' x ' . $l['Product_Name']
        . ': ' . $rute[0]['Oras_origine'] . ' -> ' . $rute[0]['Oras_destinatie']
        . ', AWB ' . $rezultat['awb'] . "\n";
}
echo '   Expedieri create: ' . $create . "\n\n";

// --- 2. Datele expedierii, aliniate la data comenzii ---

$toate = $pdo->query(
    'SELECT e.ExpediereID, e.awb, e.Data_expediere, e.Data_livrare_estimata,
            e.Data_livrare_efectiva, co.Data_comanda
     FROM expedieri e
     JOIN comenzi_produse cp ON cp.LinieID = e.LinieID
     JOIN comenzi co ON co.ComandaID = cp.ComandaID
     ORDER BY e.ExpediereID'
)->fetchAll();

$mutate = 0;
foreach ($toate as $e) {
    $timpMers = minute_program_intre($e['Data_expediere'], $e['Data_livrare_estimata']);

    $nouaExpediere = OptimizareRuteService::adaugaMinuteProgram($e['Data_comanda'], MINUTE_PANA_LA_EXPEDIERE);
    $nouaEstimata = OptimizareRuteService::adaugaMinuteProgram($nouaExpediere, $timpMers);

    // AWB-ul contine data expedierii, deci se regenereaza odata cu ea.
    $awb = 'AWB' . date('Ymd', strtotime($nouaExpediere)) . str_pad($e['ExpediereID'], 6, '0', STR_PAD_LEFT);

    $pdo->prepare(
        'UPDATE expedieri
            SET Data_expediere = :dexp,
                Data_livrare_estimata = :dest,
                Data_livrare_efectiva = CASE WHEN Data_livrare_efectiva IS NULL THEN NULL ELSE :defe END,
                awb = :awb
          WHERE ExpediereID = :id'
    )->execute([
        'dexp' => $nouaExpediere,
        'dest' => $nouaEstimata,
        'defe' => $nouaEstimata,
        'awb' => $awb,
        'id' => $e['ExpediereID'],
    ]);

    $mutate++;
}

echo '2. Expedieri aliniate la data comenzii: ' . $mutate . "\n\n";

// --- 3. Livrarea celor a caror data estimata a trecut deja ---

$deLivrat = $pdo->query(
    'SELECT ExpediereID FROM expedieri
      WHERE Status_expediere = \'In tranzit\' AND Data_livrare_estimata <= NOW()
      ORDER BY ExpediereID'
)->fetchAll(PDO::FETCH_COLUMN);

echo '3. De marcat ca livrate: ' . count($deLivrat) . "\n";

$livrate = 0;
foreach ($deLivrat as $id) {
    $e = $expedieri->getById($id);
    if ($e === null) {
        continue;
    }

    $input = [
        'ClientID' => $e['ClientID'],
        'SoferID' => $e['SoferID'],
        'RutaID' => $e['RutaID'],
        'Data_expediere' => $e['Data_expediere'],
        'Data_livrare_estimata' => $e['Data_livrare_estimata'],
        'Data_livrare_efectiva' => $e['Data_livrare_estimata'],
        'Status_expediere' => 'Livrat',
        'Valoare_expediere' => $e['Valoare_expediere'],
    ];

    $rezultat = $expedieri->validate($input);

    if ($rezultat['errors']) {
        echo '   [!] ' . $e['awb'] . ': ' . implode(' ', $rezultat['errors']) . "\n";
        continue;
    }

    // update() scade si stocul din inventory (o singura data, prin stoc_scazut).
    $expedieri->update($id, $rezultat['data']);
    $livrate++;
    echo '   OK  ' . $e['awb'] . ' -> Livrat la ' . $e['Data_livrare_estimata'] . "\n";
}
echo '   Livrate: ' . $livrate . "\n\n";

// --- 4. Statusul comenzilor: complet expediata = "Trimisa" ---

$actualizate = $pdo->exec(
    'UPDATE comenzi c
     SET c.Status = \'Trimisa\'
     WHERE c.Status IN (\'Noua\', \'In procesare\')
       AND (SELECT COUNT(*) FROM comenzi_produse cp WHERE cp.ComandaID = c.ComandaID) > 0
       AND (SELECT COUNT(*) FROM comenzi_produse cp WHERE cp.ComandaID = c.ComandaID)
           = (SELECT COUNT(*) FROM comenzi_produse cp
                JOIN expedieri e ON e.LinieID = cp.LinieID
               WHERE cp.ComandaID = c.ComandaID)'
);

echo '4. Comenzi trecute pe "Trimisa": ' . $actualizate . "\n";
