<?php

require __DIR__ . '/../database/db_connection.php';
require __DIR__ . '/../backend/ComandaRepository.php';

$repo = new ComandaRepository($pdo);

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
