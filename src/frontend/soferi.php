<?php

require __DIR__ . '/../database/db_connection.php';
require __DIR__ . '/../backend/SoferRepository.php';

$repo = new SoferRepository($pdo);

$config = [
    'active' => 'soferi',
    'title' => 'Gestionare soferi',
    'entityLabel' => 'soferul',
    'addLabel' => 'Sofer nou',
    'searchPlaceholder' => 'Cauta dupa nume sau oras de baza...',
    'pk' => 'SoferID',
    'defaultSort' => ['column' => 'SoferID', 'dir' => 'desc'],
    'deleteBlockedMessage' => 'Soferul nu poate fi sters: are expedieri inregistrate. Sterge intai expedierile lui.',
    'options' => [],

    'rowLabel' => function ($row) {
        return $row['Nume'];
    },

    'columns' => [
        ['key' => 'SoferID', 'label' => 'ID', 'type' => 'id'],
        ['key' => 'Nume', 'label' => 'Nume'],
        ['key' => 'Telefon', 'label' => 'Telefon'],
        ['key' => 'Oras_baza', 'label' => 'Oras de baza'],
        ['key' => 'Data_angajare', 'label' => 'Angajat', 'type' => 'date'],
    ],

    'fields' => [
        ['name' => 'Nume', 'label' => 'Nume', 'type' => 'text', 'maxlength' => 50, 'required' => true],
        ['name' => 'Telefon', 'label' => 'Telefon', 'type' => 'text', 'maxlength' => 20],
        ['name' => 'Oras_baza', 'label' => 'Oras de baza', 'type' => 'text', 'maxlength' => 50, 'required' => true],
        ['name' => 'Data_angajare', 'label' => 'Data angajare', 'type' => 'date', 'default' => 'today'],
    ],
];

require __DIR__ . '/_crud_page.php';
