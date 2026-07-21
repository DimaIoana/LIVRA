<?php

require __DIR__ . '/../database/db_connection.php';
require __DIR__ . '/../backend/ComandaRepository.php';

$repo = new ComandaRepository($pdo);

$config = [
    'active' => 'comenzi',
    'title' => 'Gestionare comenzi',
    'entityLabel' => 'comanda',
    'addLabel' => 'Comanda noua',
    'searchPlaceholder' => 'Cauta dupa client, status sau observatii...',
    'pk' => 'ComandaID',
    'defaultSort' => ['column' => 'ComandaID', 'dir' => 'desc'],
    'deleteBlockedMessage' => null,
    'options' => $repo->getOptiuni(),

    'rowLabel' => function ($row) {
        return '#' . $row['ComandaID'] . ' - ' . $row['ClientNume'];
    },

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
        ['key' => 'Total', 'label' => 'Total', 'type' => 'money'],
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
