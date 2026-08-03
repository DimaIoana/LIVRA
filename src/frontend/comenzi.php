<?php

require __DIR__ . '/../database/db_connection.php';
require __DIR__ . '/../backend/ComandaRepository.php';
// `require_once`: repository-ul il incarca deja, fiindca are nevoie de el la
// estimarea de profit.
require_once __DIR__ . '/../backend/OptimizareRuteService.php';

$repo = new ComandaRepository($pdo);

// Pentru estimarea de profit a unei comenzi neexpediate: costul de carburant se
// socoteste pe ruta pe care ar alege-o algoritmul pentru fiecare produs.
$optimizare = new OptimizareRuteService($pdo);

$config = [
    'active' => 'comenzi',
    'title' => 'Gestionare comenzi',
    'entityLabel' => 'comanda',
    'addLabel' => 'Comanda noua',
    'searchPlaceholder' => 'Cauta dupa client, produs, status sau observatii...',
    'pk' => 'ComandaID',
    'defaultSort' => ['column' => 'ComandaID', 'dir' => 'desc'],
    'deleteBlockedMessage' => null,
    'options' => $repo->getOptiuni(),

    'rowLabel' => function ($row) {
        return '#' . $row['ComandaID'] . ' - ' . $row['ClientNume'];
    },

    // Buton catre back office-ul de expediere cu optimizare rute.
    'rowLinks' => [
        [
            'label' => 'Expediaza',
            'href' => function ($row) {
                return 'expediere_comanda.php?comanda=' . (int) $row['ComandaID'];
            },
        ],
    ],

    'columns' => [
        ['key' => 'ComandaID', 'label' => 'ID', 'type' => 'id'],
        ['key' => 'ClientNume', 'label' => 'Client'],
        ['key' => 'Data_comanda', 'label' => 'Data', 'type' => 'date'],
        [
            'key' => 'Status',
            'label' => 'Status',
            'type' => 'tag',
            'tagClass' => function ($row) {
                if ($row['Status'] === 'Trimisa') {
                    return 'tag--ok';
                }

                if ($row['Status'] === 'Anulata') {
                    return 'tag--fail';
                }

                return 'tag--warn';
            },
        ],
        [
            'key' => 'Produse',
            'label' => 'Produse',
            'format' => function ($row) {
                return $row['Produse'] === null ? 'Fara linii de produs' : $row['Produse'];
            },
        ],
        [
            'key' => 'Total',
            'label' => 'Total',
            'type' => 'money',
            // Totalul comenzii ar trebui sa fie suma liniilor; daca a fost scris
            // manual altfel, se evidentiaza ca sa se vada la verificare.
            'cellClass' => function ($row) {
                return (int) $row['NrLinii'] > 0 && abs((float) $row['Total'] - (float) $row['TotalLinii']) >= 0.01
                    ? 'cell--alert'
                    : '';
            },
        ],
        [
            'key' => 'NrExpediate',
            'label' => 'Expediat',
            'type' => 'tag',
            'format' => function ($row) {
                $linii = (int) $row['NrLinii'];
                $expediate = (int) $row['NrExpediate'];

                if ($linii === 0) {
                    return '-';
                }

                if ($expediate === 0) {
                    return 'Neexpediata';
                }

                return $expediate >= $linii ? 'Complet' : $expediate . ' din ' . $linii . ' linii';
            },
            'tagClass' => function ($row) {
                $linii = (int) $row['NrLinii'];
                $expediate = (int) $row['NrExpediate'];

                if ($linii === 0 || $expediate === 0) {
                    return '';
                }

                return $expediate >= $linii ? 'tag--ok' : 'tag--warn';
            },
        ],
        ['key' => 'Observatii', 'label' => 'Observatii'],
        ['key' => 'profit', 'label' => 'Profit', 'type' => 'modal', 'modal' => 'profit'],
    ],

    'rowModals' => [
        // Decizia de trimitere, luata inainte ca marfa sa plece: butonul apare
        // doar la comenzile inca deschise. O comanda trimisa sau deja expediata
        // nu mai are ce decide, deci in dreptul ei nu apare buton.
        'profit' => [
            'param' => 'profit',
            'buttonLabel' => function ($row) use ($repo, $optimizare) {
                if (!$repo->esteDeschisa($row)) {
                    return '';
                }

                return $repo->situatieFinanciara($row, $optimizare)['profit'] < 0
                    ? 'Neprofitabil'
                    : 'Profitabil';
            },
            'buttonClass' => function ($row) use ($repo, $optimizare) {
                return $repo->situatieFinanciara($row, $optimizare)['profit'] < 0
                    ? 'btn--danger-solid'
                    : 'btn--ghost';
            },
            'wide' => true,
            'title' => function ($row) {
                return 'Comanda #' . $row['ComandaID'] . ' - ' . $row['ClientNume'];
            },
            'body' => function ($row) use ($repo, $optimizare) {
                $comanda = $row;
                $repoComenzi = $repo;

                require __DIR__ . '/_decizie_comanda.php';
            },
            'footer' => function ($row, $inapoi) use ($repo) {
                $deschisa = $repo->esteDeschisa($row);

                require __DIR__ . '/_decizie_butoane.php';
            },
        ],
    ],

    // Cele doua optiuni din fereastra de decizie.
    'rowActions' => [
        'trimite' => function ($row) use ($repo, $optimizare) {
            if (!$repo->trimite($row, $optimizare)) {
                return ['message' => 'Comanda nu mai e deschisa, decizia nu se mai poate lua.', 'state' => 'fail'];
            }

            return ['message' => 'Comanda a trecut pe "In procesare". Cifrele au fost inregistrate.', 'state' => 'ok'];
        },
        'anuleaza' => function ($row) use ($repo) {
            if (!$repo->anuleaza($row)) {
                return ['message' => 'Comanda nu mai e deschisa, decizia nu se mai poate lua.', 'state' => 'fail'];
            }

            return ['message' => 'Comanda a fost anulata. Nu s-a inregistrat nicio cifra financiara.', 'state' => 'ok'];
        },
    ],

    'fields' => [
        ['name' => 'ClientID', 'label' => 'Client', 'type' => 'select', 'optionsFrom' => 'clienti'],
        ['name' => 'Data_comanda', 'label' => 'Data comenzii', 'type' => 'date', 'default' => 'today'],
        ['name' => 'Status', 'label' => 'Status', 'type' => 'select', 'optionsFrom' => 'statusuri'],
        [
            'name' => 'Total',
            'label' => 'Total (lei)',
            'type' => 'text',
            'hint' => 'Valoarea totala a comenzii.',
        ],
        [
            'name' => 'Observatii',
            'label' => 'Observatii',
            'type' => 'text',
            'maxlength' => 500,
        ],
    ],
];

require __DIR__ . '/_crud_page.php';
