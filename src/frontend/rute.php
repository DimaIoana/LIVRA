<?php

// Back office: doar pentru utilizatorii autentificati (vezi _auth.php).
require __DIR__ . '/_auth.php';
cere_admin();

require_once __DIR__ . '/../database/db_connection.php';
require __DIR__ . '/../backend/RutaRepository.php';

$repo = new RutaRepository($pdo);

// Tipurile de drum, de la cel mai usor/rapid la cel mai greu/lent.
// autostrada > dn (drum national) > drum judetean > drum comunal.
$tipuriStrada = RutaRepository::TIPURI_STRADA;

$config = [
    'active' => 'rute',
    'title' => 'Gestionare rute',
    'entityLabel' => 'ruta',
    'addLabel' => 'Ruta noua',
    'searchPlaceholder' => 'Cauta dupa depozit sau destinatie client...',
    'pk' => 'RutaID',
    'defaultSort' => ['column' => 'RutaID', 'dir' => 'desc'],
    'deleteBlockedMessage' => 'Ruta nu poate fi stearsa: are expedieri inregistrate. Sterge intai expedierile de pe ea.',
    'options' => [],

    'rowLabel' => function ($row) {
        return $row['Oras_origine'] . ' - ' . $row['Oras_destinatie'];
    },

    'columns' => [
        ['key' => 'RutaID', 'label' => 'ID', 'type' => 'id'],
        ['key' => 'Oras_origine', 'label' => 'Depozit'],
        ['key' => 'Oras_destinatie', 'label' => 'Destinatie client'],
        ['key' => 'Distanta_km', 'label' => 'Distanta (km)', 'type' => 'number'],
        [
            'key' => 'Durata_min',
            'label' => 'Timp condus',
            'type' => 'number',
            'format' => function ($row) {
                return fmt_durata($row['Durata_min']);
            },
        ],
        [
            'key' => 'viteza',
            'label' => 'Viteza',
            'type' => 'number',
            'format' => function ($row) {
                return $row['viteza'] . ' km/h';
            },
        ],
        [
            'key' => 'tip_strada',
            'label' => 'Tip drum',
            'type' => 'tag',
            'format' => function ($row) use ($tipuriStrada) {
                return $tipuriStrada[$row['tip_strada']] ?? $row['tip_strada'];
            },
        ],
    ],

    'fields' => [
        ['name' => 'Oras_origine', 'label' => 'Depozit', 'type' => 'text', 'maxlength' => 50, 'required' => true],
        ['name' => 'Oras_destinatie', 'label' => 'Destinatie client', 'type' => 'text', 'maxlength' => 50, 'required' => true],
        ['name' => 'Distanta_km', 'label' => 'Distanta (km)', 'type' => 'text', 'required' => true],
        ['name' => 'Durata_min', 'label' => 'Timp de condus (minute)', 'type' => 'text', 'required' => true, 'hint' => 'Timp aproximativ de condus, in minute (ex: 285 = 4 h 45 min).'],
        ['name' => 'viteza', 'label' => 'Viteza (km/h)', 'type' => 'text', 'required' => true, 'hint' => 'Viteza medie pe ruta, in km/h.'],
        [
            'name' => 'tip_strada',
            'label' => 'Tip drum',
            'type' => 'select',
            'options' => array_map(function ($id, $text) {
                return ['id' => $id, 'text' => $text];
            }, array_keys($tipuriStrada), array_values($tipuriStrada)),
            'hint' => 'De la cel mai rapid la cel mai greu: autostrada > DN > judetean > comunal.',
        ],
    ],
];

require __DIR__ . '/_crud_page.php';
