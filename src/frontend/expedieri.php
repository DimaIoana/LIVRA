<?php

require __DIR__ . '/../database/db_connection.php';
require __DIR__ . '/../backend/ExpediereRepository.php';
require __DIR__ . '/../backend/RutaRepository.php';
require __DIR__ . '/../backend/OptimizareRuteService.php';

$repo = new ExpediereRepository($pdo);

// Pentru fereastra "Algoritm": ruta expedierii si serviciul care a ales-o.
$rute = new RutaRepository($pdo);
$optimizare = new OptimizareRuteService($pdo);
$tipuriStrada = RutaRepository::TIPURI_STRADA;

$config = [
    'active' => 'expedieri',
    'title' => 'Gestionare expedieri',
    'entityLabel' => 'expedierea',
    'addLabel' => 'Expediere noua',
    'searchPlaceholder' => 'Cauta dupa client, sofer, oras sau status...',
    'pk' => 'ExpediereID',
    'defaultSort' => ['column' => 'ExpediereID', 'dir' => 'desc'],
    'deleteBlockedMessage' => null,
    'options' => $repo->getOptiuni(),

    'rowLabel' => function ($row) {
        return '#' . $row['ExpediereID'] . ' - ' . $row['ClientNume'];
    },

    'columns' => [
        ['key' => 'ExpediereID', 'label' => 'ID', 'type' => 'id'],
        ['key' => 'awb', 'label' => 'AWB'],
        ['key' => 'ClientNume', 'label' => 'Client'],
        ['key' => 'SoferNume', 'label' => 'Sofer'],
        ['key' => 'Ruta', 'label' => 'Ruta'],
        ['key' => 'Data_expediere', 'label' => 'Expediat', 'type' => 'date'],
        ['key' => 'Data_livrare_estimata', 'label' => 'Estimat', 'type' => 'date'],
        ['key' => 'Data_livrare_efectiva', 'label' => 'Livrat', 'type' => 'date'],
        [
            'key' => 'Status_expediere',
            'label' => 'Status',
            'type' => 'tag',
            'tagClass' => function ($row) {
                if ($row['Status_expediere'] === 'Livrat') {
                    return 'tag--ok';
                }

                if ($row['Status_expediere'] === 'Intarziat' || $row['Status_expediere'] === 'Returnat') {
                    return 'tag--fail';
                }

                return 'tag--warn';
            },
        ],
        ['key' => 'Valoare_expediere', 'label' => 'Valoare', 'type' => 'money'],
        ['key' => 'cost_carburant', 'label' => 'Carburant', 'type' => 'money'],
        ['key' => 'algoritm', 'label' => 'Algoritm', 'type' => 'modal', 'modal' => 'algoritm'],
    ],

    'rowModals' => [
        // Butonul din coloana "Algoritm": arata de ce a ajuns coletul pe traseul
        // asta - calculul timpului ajustat si clasamentul rutelor catre acelasi oras.
        'algoritm' => [
            'param' => 'algoritm',
            'buttonLabel' => 'Explica',
            'wide' => true,
            'title' => function ($row) {
                return 'Algoritm: ' . $row['Ruta'];
            },
            'body' => function ($row) use ($repo, $rute, $optimizare, $tipuriStrada) {
                $rutaRand = $rute->getById((int) $row['RutaID']);
                $expediere = $row;
                $repoExpedieri = $repo;   // pentru costul marfii pe fiecare depozit

                require __DIR__ . '/_algoritm_ruta.php';
            },
        ],

    ],

    'fields' => [
        ['name' => 'ClientID', 'label' => 'Client', 'type' => 'select', 'optionsFrom' => 'clienti'],
        ['name' => 'SoferID', 'label' => 'Sofer', 'type' => 'select', 'optionsFrom' => 'soferi'],
        ['name' => 'RutaID', 'label' => 'Ruta', 'type' => 'select', 'optionsFrom' => 'rute'],
        ['name' => 'Status_expediere', 'label' => 'Status', 'type' => 'select', 'optionsFrom' => 'statusuri'],
        ['name' => 'Data_expediere', 'label' => 'Data expediere', 'type' => 'datetime', 'default' => 'today'],
        ['name' => 'Data_livrare_estimata', 'label' => 'Data livrare estimata', 'type' => 'datetime'],
        [
            'name' => 'Data_livrare_efectiva',
            'label' => 'Data livrare efectiva',
            'type' => 'datetime',
            'hint' => 'Se lasa gol cat timp coletul nu a ajuns la client.',
        ],
        ['name' => 'Valoare_expediere', 'label' => 'Valoare (lei)', 'type' => 'text'],
    ],
];

require __DIR__ . '/_crud_page.php';
