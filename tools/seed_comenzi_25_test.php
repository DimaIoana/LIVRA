<?php

/**
 * Utilitar de test: adauga 25 de comenzi, fiecare pe un client aleator si cu mai
 * multe produse (2-4 linii), nu cu unul singur.
 *
 * Nu scrie direct in tabele: foloseste `MagazinRepository::creeazaComanda()`,
 * exact metoda pe care o apeleaza si cosul din magazin, deci comenzile ies cu
 * aceleasi verificari de stoc, aceleasi preturi curente (`inventory.Unit_Cost`)
 * si acelasi calcul de subtotal/total ca o comanda pusa de un client real.
 *
 * Doua lucruri se ajusteaza dupa creare, fiindca magazinul nu le poate face:
 *   - `Data_comanda` se muta inapoi, imprastiata pe ultimele ZILE_INAPOI zile, in
 *     orele de program. Altfel toate cele 25 ar cadea azi si graficele pe zile
 *     n-ar avea ce arata;
 *   - unele comenzi primesc observatii, ca datele sa nu fie toate la fel.
 *
 * Cantitatile sunt limitate de un buget pe produs (BUGET_STOC din stocul curent),
 * ca sa nu goleasca stocul: un produs cu stoc 0 nu mai are depozit de plecare,
 * deci nu i s-ar mai putea face expediere.
 *
 * Expedierile NU se fac aici. Dupa acest script se ruleaza procesul normal:
 *
 *   php tools/seed_comenzi_25_test.php
 *   php tools/seed_expedieri_test.php
 *
 * al doilea creeaza expedierea fiecarei linii pe ruta cea mai optimizata (AWB,
 * sofer, cost carburant), aliniaza datele la comanda, marcheaza ca livrate
 * expedierile a caror data estimata a trecut (si scade stocul) si trece comenzile
 * complet expediate pe "Trimisa".
 *
 * ATENTIE: la fiecare rulare adauga inca un lot de comenzi (nu e idempotent).
 * E un utilitar pentru date de test, nu de rulat peste date reale.
 */

require __DIR__ . '/../src/database/db_connection.php';
require __DIR__ . '/../src/backend/MagazinRepository.php';

/** Cate comenzi se creeaza (se poate da alt numar ca argument in linia de comanda). */
const COMENZI = 25;

/** Pe cate zile in urma se imprastie comenzile. */
const ZILE_INAPOI = 14;

/** Cat din stocul curent al unui produs poate consuma lotul asta, ca sa nu-l goleasca. */
const BUGET_STOC = 0.6;

/** Cate linii poate avea o comanda. */
const LINII_MIN = 2;
const LINII_MAX = 4;

// Acelasi lot la fiecare rulare, ca rezultatul sa se poata reproduce si discuta.
mt_srand(20260729);

$cate = isset($argv[1]) ? max(1, (int) $argv[1]) : COMENZI;

$magazin = new MagazinRepository($pdo);

// --- Clientii si produsele disponibile ---

$clienti = $pdo->query('SELECT ClientID, Nume, Oras FROM clienti ORDER BY ClientID')->fetchAll();
if (!$clienti) {
    exit("Nu exista clienti in baza de date.\n");
}

// Catalogul curent (acelasi pe care il vede clientul in magazin).
$produse = $magazin->catalog();
if (count($produse) < LINII_MIN) {
    exit("Nu exista destule produse in catalog.\n");
}

// Bugetul de bucati pe produs si cate bucati poate lua o linie. Un produs scump
// se ia in 1-2 bucati, unul ieftin in mai multe - ca la o comanda adevarata.
$buget = [];
$maxLinie = [];
foreach ($produse as $p) {
    $cod = $p['Product_ID'];
    $buget[$cod] = (int) floor((int) $p['Stock_Level'] * BUGET_STOC);
    $pret = (float) $p['Unit_Cost'];

    if ($pret >= 500) {
        $maxLinie[$cod] = 1;
    } elseif ($pret >= 100) {
        $maxLinie[$cod] = 2;
    } elseif ($pret >= 30) {
        $maxLinie[$cod] = 3;
    } else {
        $maxLinie[$cod] = 5;
    }
}

$observatii = [
    'Sunati inainte de livrare.',
    'Livrare dupa ora 16:00.',
    'Lasati coletul la receptie.',
    'Ambalare pentru produse fragile.',
    'Am nevoie de factura pe firma.',
];

echo "Se creeaza $cate comenzi, fiecare cu " . LINII_MIN . '-' . LINII_MAX . " produse.\n\n";

$create = 0;
$linii = 0;
$valoare = 0.0;
$esuate = 0;

for ($i = 0; $i < $cate; $i++) {
    $client = $clienti[mt_rand(0, count($clienti) - 1)];

    // Produse distincte pentru comanda asta, doar din cele care mai au buget.
    $disponibile = array_values(array_filter($produse, function ($p) use ($buget) {
        return $buget[$p['Product_ID']] > 0;
    }));

    if (count($disponibile) < LINII_MIN) {
        echo "   [!] Nu mai e stoc de impartit pe destule produse; ma opresc la $create comenzi.\n";
        break;
    }

    shuffle($disponibile);
    $cateLinii = min(mt_rand(LINII_MIN, LINII_MAX), count($disponibile));

    $items = [];
    for ($j = 0; $j < $cateLinii; $j++) {
        $cod = $disponibile[$j]['Product_ID'];
        $qty = min(mt_rand(1, $maxLinie[$cod]), $buget[$cod]);

        if ($qty > 0) {
            $items[$cod] = $qty;
        }
    }

    if (count($items) < LINII_MIN) {
        continue;
    }

    $obs = mt_rand(1, 3) === 1 ? $observatii[mt_rand(0, count($observatii) - 1)] : '';

    $rezultat = $magazin->creeazaComanda($client['ClientID'], $items, $obs);

    if ($rezultat['errors']) {
        $esuate++;
        echo '   [!] ' . $client['Nume'] . ': ' . implode(' ', $rezultat['errors']) . "\n";
        continue;
    }

    // Comanda a intrat: scade bugetul cu ce s-a luat efectiv.
    foreach ($items as $cod => $qty) {
        $buget[$cod] -= $qty;
    }

    // Data comenzii, imprastiata pe ultimele zile, in orele de program.
    $zile = mt_rand(0, ZILE_INAPOI - 1);
    $data = date('Y-m-d H:i:s', strtotime(
        '-' . $zile . ' days ' . mt_rand(8, 19) . ':' . str_pad(mt_rand(0, 59), 2, '0', STR_PAD_LEFT) . ':00'
    ));

    $pdo->prepare('UPDATE comenzi SET Data_comanda = :d WHERE ComandaID = :id')
        ->execute(['d' => $data, 'id' => $rezultat['id']]);

    // Totalul, ca sa il putem raporta.
    $total = $pdo->prepare('SELECT Total FROM comenzi WHERE ComandaID = :id');
    $total->execute(['id' => $rezultat['id']]);
    $totalComanda = (float) $total->fetchColumn();

    $create++;
    $linii += count($items);
    $valoare += $totalComanda;

    $descriere = [];
    foreach ($items as $cod => $qty) {
        $descriere[] = $qty . ' x ' . $cod;
    }

    echo '   OK  Comanda #' . $rezultat['id'] . ' - ' . $client['Nume']
        . ' (' . $client['Oras'] . '), ' . substr($data, 0, 16) . ': '
        . implode(', ', $descriere)
        . ' = ' . number_format($totalComanda, 2, ',', '.') . " lei\n";
}

echo "\nComenzi create : $create\n";
echo "Linii de produs: $linii\n";
echo 'Valoare totala : ' . number_format($valoare, 2, ',', '.') . " lei\n";

if ($esuate) {
    echo "Comenzi respinse (stoc/validare): $esuate\n";
}

echo "\nUrmatorul pas - procesul de expediere:\n";
echo "   php tools/seed_expedieri_test.php\n";
