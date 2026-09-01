<?php

/**
 * Control de stocks: cate un rand pe produs si luna - cate bucati avem, cand si
 * in ce depozit.
 *
 * Produsul in sine (nume, categorie, pret de vanzare, poza) nu se editeaza aici,
 * ci in Catalog de produse (catalog_produse.php). Aici se alege produsul din
 * catalog si se completeaza doar cifrele lunii. Coloanele Produs / Categorie /
 * Pret unitar / Poza vin din catalog, ca sa se vada despre ce e vorba.
 */

// Back office: doar pentru utilizatorii autentificati (vezi _auth.php).
require __DIR__ . '/_auth.php';
cere_admin();

require_once __DIR__ . '/../database/db_connection.php';
require __DIR__ . '/../backend/InventoryRepository.php';
require __DIR__ . '/../backend/ProdusRepository.php';

$repo = new InventoryRepository($pdo);
$catalog = new ProdusRepository($pdo);

// Depozitele (locatiile) produselor. Codul se salveaza in coloana `depozit`.
$depozite = [1 => 'Arad', 2 => 'Braila', 3 => 'Pitesti'];

$config = [
    'active' => 'produse',
    'title' => 'Control de stocks',
    'entityLabel' => 'inregistrarea',
    'addLabel' => 'Inregistrare noua',
    'searchPlaceholder' => 'Cauta dupa cod, nume sau categorie...',
    'pk' => 'InventoryID',
    'defaultSort' => ['column' => 'Date', 'dir' => 'desc'],
    'deleteBlockedMessage' => null,
    'options' => [
        // Produsele din catalog, pentru dropdown-ul de mai jos. Prima optiune e
        // goala, ca sa nu se aleaga din greseala primul produs din lista.
        'produse' => array_merge(
            [['id' => '', 'text' => '— alege produs —']],
            $catalog->optiuni()
        ),
    ],

    'rowLabel' => function ($row) {
        return $row['Product_Name'] . ' (' . $row['Date'] . ')';
    },

    // Legatura cu Catalogul de produse: de pe o luna de stoc se sare la produsul
    // ca produs (un rand, pretul de acum), asa cum il vede clientul in magazin.
    'rowLinks' => [
        [
            'label' => 'In catalog',
            'href' => function ($row) {
                return 'catalog_produse.php?search=' . urlencode($row['Product_ID']);
            },
        ],
    ],

    'columns' => [
        ['key' => 'InventoryID', 'label' => 'ID', 'type' => 'id'],
        // Poza, numele, categoria si pretul vin din catalog (JOIN pe Product_ID).
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
            'label' => 'Produs (din catalog)',
            'type' => 'select',
            'optionsFrom' => 'produse',
            'hint' => 'Produsul trebuie sa existe in Catalog de produse. Daca nu-l gasesti '
                . 'in lista, adauga-l intai acolo.',
        ],
        ['name' => 'Stock_Level', 'label' => 'Stoc', 'type' => 'text', 'required' => true],
        ['name' => 'Reorder_Point', 'label' => 'Prag de recomanda', 'type' => 'text', 'required' => true],
        ['name' => 'Monthly_Sales', 'label' => 'Vanzari lunare', 'type' => 'text', 'required' => true],
        [
            'name' => 'Cost_Unitar',
            'label' => 'Cost unitar (lei)',
            'type' => 'text',
            'hint' => 'Cat costa produsul pe firma (achizitie) in luna asta, la depozitul asta. Optional. '
                . 'Pretul de vanzare se pune in catalog.',
        ],
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
