<?php

/**
 * Catalog de produse (back office): sursa unica a produselor.
 *
 * Aici se adauga, se modifica si se sterg produsele: cod, nume, categorie, pret
 * de vanzare si poza. Exact lista de aici e ce vede clientul in magazin
 * (magazin.php citeste tabela `produse` prin MagazinRepository), deci un produs
 * adaugat aici apare imediat in magazin.
 *
 * Diferenta fata de "Control de stocks" (produse.php):
 *   - aici e cate un rand pe produs - produsul in sine, cu pretul lui de acum;
 *   - acolo e cate un rand pe produs si luna - cate bucati avem, cand si in ce
 *     depozit. Randurile de stoc se leaga de catalog prin codul produsului
 *     (inventory.Product_ID = produse.Product_ID), deci nu poti inregistra stoc
 *     pentru un produs care nu e in catalog.
 *
 * Coloana "Stoc" de mai jos e stocul din ultima luna inregistrata, adus din
 * `inventory` doar ca sa se vada; el se modifica in Control de stocks.
 */

// Back office: doar pentru utilizatorii autentificati (vezi _auth.php).
require __DIR__ . '/_auth.php';
cere_admin();

require_once __DIR__ . '/../database/db_connection.php';
require __DIR__ . '/../backend/ProdusRepository.php';

$repo = new ProdusRepository($pdo);

$config = [
    'active' => 'catalog',
    'title' => 'Catalog de produse',
    'entityLabel' => 'produsul',
    'addLabel' => 'Produs nou',
    'searchPlaceholder' => 'Cauta dupa cod, nume sau categorie...',
    'pk' => 'ProdusID',
    'defaultSort' => ['column' => 'Product_ID', 'dir' => 'asc'],

    // Cheia straina din inventory e ON DELETE RESTRICT: un produs cu istoric de
    // stoc nu se poate sterge, ca sa nu ramana randuri orfane in stoc.
    'deleteBlockedMessage' => 'Produsul are inregistrari de stoc si nu poate fi sters. '
        . 'Sterge intai lunile lui din Control de stocks.',
    'options' => [],

    'rowLabel' => function ($row) {
        return $row['Product_Name'] . ' (' . $row['Product_ID'] . ')';
    },

    // Legatura cu stocul: de pe produs se sare la lunile lui de stoc.
    'rowLinks' => [
        [
            'label' => 'Stoc lunar',
            'href' => function ($row) {
                return 'produse.php?search=' . urlencode($row['Product_ID']);
            },
        ],
    ],

    'columns' => [
        ['key' => 'poze', 'label' => 'Poza', 'type' => 'image', 'urlPrefix' => '../../poze/'],
        ['key' => 'Product_ID', 'label' => 'Cod'],
        ['key' => 'Product_Name', 'label' => 'Produs'],
        ['key' => 'Category', 'label' => 'Categorie', 'type' => 'tag'],
        ['key' => 'Unit_Cost', 'label' => 'Pret unitar', 'type' => 'money'],
        [
            'key' => 'Stock_Level',
            'label' => 'Stoc total',
            'type' => 'number',
            'cellClass' => function ($row) {
                return (int) $row['Stock_Level'] <= 0 ? 'cell--alert' : '';
            },
        ],
        // Defalcarea pe depozit: totalul de mai sus e suma acestor trei coloane.
        ['key' => 'Stoc_Arad', 'label' => 'Arad', 'type' => 'number'],
        ['key' => 'Stoc_Braila', 'label' => 'Braila', 'type' => 'number'],
        ['key' => 'Stoc_Pitesti', 'label' => 'Pitesti', 'type' => 'number'],
        [
            'key' => 'Luni_stoc',
            'label' => 'Luni de stoc',
            'type' => 'number',
            // Un produs fara nicio luna e in catalog, dar n-a fost inventariat
            // niciodata - de aceea apare in magazin ca epuizat.
            'cellClass' => function ($row) {
                return (int) $row['Luni_stoc'] === 0 ? 'cell--alert' : '';
            },
        ],
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
            'hint' => 'Generat automat, incremental. Codul e unic si e legatura cu stocul, '
                . 'deci schimbarea lui muta si lunile de stoc ale produsului.',
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
            'name' => 'Unit_Cost',
            'label' => 'Pret unitar (lei)',
            'type' => 'text',
            'required' => true,
            'hint' => 'Pretul de vanzare, cel afisat clientului in magazin.',
        ],

        // Stocul de pornire: obligatoriu, si numai la produs nou.
        // Obligatoriu, pentru ca de aici incepe evidenta din Control de stocks -
        // un produs fara inregistrare de stoc n-ar avea din ce sa se scada pe
        // masura ce se consuma.
        // Numai la adaugare, pentru ca dupa aceea cifra traieste in Control de
        // stocks; altfel ar exista doua locuri din care se schimba acelasi numar.
        [
            'name' => 'Stoc_initial',
            'label' => 'Stoc de pornire (bucati)',
            'type' => 'text',
            'onlyOnAdd' => true,
            'required' => true,
            'hint' => 'Cate bucati ai acum din produs. De aici pleaca evidenta: numarul '
                . 'asta se scade din Control de stocks pe masura ce produsul se consuma.',
        ],
        [
            'name' => 'Depozit_initial',
            'label' => 'Depozit (unde se afla stocul)',
            'type' => 'select',
            'onlyOnAdd' => true,
            'required' => true,
            'options' => array_merge(
                [['id' => '', 'text' => '— alege depozit —']],
                array_map(function ($id, $name) {
                    return ['id' => $id, 'text' => $name];
                }, array_keys(StocCurent::DEPOZITE), array_values(StocCurent::DEPOZITE))
            ),
            'hint' => 'Unde se afla fizic marfa. La salvare se creeaza automat prima '
                . 'inregistrare in Control de stocks, cu data de azi.',
        ],
    ],
];

require __DIR__ . '/_crud_page.php';
