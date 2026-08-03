<?php

require __DIR__ . '/../database/db_connection.php';
require __DIR__ . '/../backend/InventoryRepository.php';

$repo = new InventoryRepository($pdo);

// Depozitele (locatiile) produselor. Codul se salveaza in coloana `depozit`.
$depozite = [1 => 'Arad', 2 => 'Braila', 3 => 'Pitesti'];

$config = [
    'active' => 'produse',
    'title' => 'Produse (stoc lunar)',
    'entityLabel' => 'inregistrarea',
    'addLabel' => 'Inregistrare noua',
    'searchPlaceholder' => 'Cauta dupa cod, nume sau categorie...',
    'pk' => 'InventoryID',
    'defaultSort' => ['column' => 'Date', 'dir' => 'desc'],
    'deleteBlockedMessage' => null,
    'options' => [],

    'rowLabel' => function ($row) {
        return $row['Product_Name'] . ' (' . $row['Date'] . ')';
    },

    'columns' => [
        ['key' => 'InventoryID', 'label' => 'ID', 'type' => 'id'],
        ['key' => 'poze', 'label' => 'Poza', 'type' => 'image', 'urlPrefix' => '../../poze/'],
        ['key' => 'Product_ID', 'label' => 'Cod'],
        ['key' => 'Product_Name', 'label' => 'Produs'],
        ['key' => 'Category', 'label' => 'Categorie', 'type' => 'tag'],
        [
            'key' => 'depozit',
            'label' => 'Depozit',
            'type' => 'tag',
            'format' => function ($row) use ($depozite) {
                return $depozite[(int) $row['depozit']] ?? 'Nespecificat';
            },
        ],
        [
            'key' => 'Stock_Level',
            'label' => 'Stoc',
            'type' => 'number',
            // Sub pragul de recomanda = trebuie recomandat, deci se evidentiaza.
            'cellClass' => function ($row) {
                return (int) $row['Stock_Level'] <= (int) $row['Reorder_Point'] ? 'cell--alert' : '';
            },
        ],
        ['key' => 'Reorder_Point', 'label' => 'Prag', 'type' => 'number'],
        ['key' => 'Monthly_Sales', 'label' => 'Vanzari/luna', 'type' => 'number'],
        ['key' => 'Unit_Cost', 'label' => 'Pret unitar', 'type' => 'money'],
        ['key' => 'Cost_Unitar', 'label' => 'Cost unitar', 'type' => 'money'],
        ['key' => 'Date', 'label' => 'Luna', 'type' => 'date'],
    ],

    'fields' => [
        [
            'name' => 'Product_ID',
            'label' => 'Cod produs',
            'type' => 'text',
            'maxlength' => 10,
            // Vine precompletat cu urmatorul cod liber, calculat la deschiderea
            // formularului. Nu e obligatoriu: lasat gol, se genereaza tot asa.
            'default' => function () use ($repo) {
                return $repo->codNou();
            },
            'hint' => 'Generat automat, incremental. Lasa-l asa pentru un produs nou; '
                . 'pune codul unui produs existent doar daca adaugi o luna noua la el.',
        ],
        ['name' => 'Product_Name', 'label' => 'Nume produs', 'type' => 'text', 'maxlength' => 100, 'required' => true],
        [
            'name' => 'poze',
            'label' => 'Poza produs',
            'type' => 'image',
            'uploadDir' => __DIR__ . '/../../poze',
            'urlPrefix' => '../../poze/',
            'accept' => 'image/*',
            'hint' => 'JPG, PNG, GIF sau WEBP, max 2 MB. La editare, lasa gol ca sa pastrezi poza actuala.',
        ],
        ['name' => 'Category', 'label' => 'Categorie', 'type' => 'text', 'maxlength' => 50, 'required' => true],
        [
            'name' => 'depozit',
            'label' => 'Depozit',
            'type' => 'select',
            'options' => array_merge(
                [['id' => '', 'text' => '— alege depozit —']],
                array_map(function ($id, $name) {
                    return ['id' => $id, 'text' => $name];
                }, array_keys($depozite), array_values($depozite))
            ),
            'hint' => 'Unde se afla fizic produsul: Arad, Braila sau Pitesti.',
        ],
        ['name' => 'Stock_Level', 'label' => 'Stoc', 'type' => 'text', 'required' => true],
        ['name' => 'Reorder_Point', 'label' => 'Prag de recomanda', 'type' => 'text', 'required' => true],
        ['name' => 'Monthly_Sales', 'label' => 'Vanzari lunare', 'type' => 'text', 'required' => true],
        [
            'name' => 'Unit_Cost',
            'label' => 'Pret unitar (lei)',
            'type' => 'text',
            'required' => true,
            'hint' => 'Pretul de vanzare, cel afisat clientului in magazin.',
        ],
        [
            'name' => 'Cost_Unitar',
            'label' => 'Cost unitar (lei)',
            'type' => 'text',
            'hint' => 'Cat costa produsul pe firma (achizitie). Optional.',
        ],
        [
            'name' => 'Date',
            'label' => 'Luna (data raportarii)',
            'type' => 'date',
            'default' => 'today',
            'hint' => 'Fiecare produs are cate o inregistrare pe luna.',
        ],
    ],
];

require __DIR__ . '/_crud_page.php';
