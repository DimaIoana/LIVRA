<?php

require __DIR__ . '/../database/db_connection.php';
require __DIR__ . '/../backend/RutaRepository.php';

$repo = new RutaRepository($pdo);

$config = [
    'active' => 'rute',
    'title' => 'Gestionare rute',
    'entityLabel' => 'ruta',
    'addLabel' => 'Ruta noua',
    'searchPlaceholder' => 'Cauta dupa oras de origine sau destinatie...',
    'pk' => 'RutaID',
    'defaultSort' => ['column' => 'RutaID', 'dir' => 'desc'],
    'deleteBlockedMessage' => 'Ruta nu poate fi stearsa: are expedieri inregistrate. Sterge intai expedierile de pe ea.',
    'options' => [],

    'rowLabel' => function ($row) {
        return $row['Oras_origine'] . ' - ' . $row['Oras_destinatie'];
    },

    'columns' => [
        ['key' => 'RutaID', 'label' => 'ID', 'type' => 'id'],
        ['key' => 'Oras_origine', 'label' => 'Origine'],
        ['key' => 'Oras_destinatie', 'label' => 'Destinatie'],
        ['key' => 'Distanta_km', 'label' => 'Distanta (km)', 'type' => 'number'],
    ],

    'fields' => [
        ['name' => 'Oras_origine', 'label' => 'Oras de origine', 'type' => 'text', 'maxlength' => 50, 'required' => true],
        ['name' => 'Oras_destinatie', 'label' => 'Oras de destinatie', 'type' => 'text', 'maxlength' => 50, 'required' => true],
        ['name' => 'Distanta_km', 'label' => 'Distanta (km)', 'type' => 'text', 'required' => true],
    ],
];

require __DIR__ . '/_crud_page.php';
