<?php

// Back office: doar pentru utilizatorii autentificati (vezi src/frontend/_auth.php).
require __DIR__ . '/src/frontend/_auth.php';
cere_admin();

$admin = admin_logat();

require_once __DIR__ . '/src/database/db_connection.php';
require __DIR__ . '/src/backend/ComandaRepository.php';

// Semaforul de pe cutia Comenzi: galben = comenzi noi, rosu = comenzi pe
// pierdere (inclusiv cele anulate tocmai fiindca pierdeau bani), verde = comenzi
// acceptate la trimitere.
//
// Rosul cere estimarea de profit, care trece prin algoritmul de rute, deci se
// calculeaza doar pentru comenzile care n-au plecat inca - pe cele plecate se
// stiu deja cifrele reale, iar pe toate ar fi munca degeaba.
$repoComenzi = new ComandaRepository($pdo);
$optimizareComenzi = new OptimizareRuteService($pdo);

$comenziNoi = (int) $pdo->query("SELECT COUNT(*) FROM comenzi WHERE Status = 'Noua'")->fetchColumn();
$comenziPePierdere = 0;

foreach ($repoComenzi->faraExpediere() as $comanda) {
    if ($repoComenzi->situatieFinanciara($comanda, $optimizareComenzi)['profit'] < 0) {
        $comenziPePierdere++;
    }
}

$comenziAcceptate = $repoComenzi->numarAcceptate();

// Cifrele pentru cele doua cutii de produse, fiecare din tabela ei:
//   - Control de stocks  -> `inventory`, toate inregistrarile (produs x luna)
//   - Catalog de produse -> `produse`, cate un rand pe produs
$inregistrariStoc = (int) $pdo->query('SELECT COUNT(*) FROM inventory')->fetchColumn();
$produseDistincte = (int) $pdo->query('SELECT COUNT(*) FROM produse')->fetchColumn();
$nrCategorii = (int) $pdo->query('SELECT COUNT(DISTINCT Category) FROM produse')->fetchColumn();

// Randul curent al fiecarui produs, ales la fel ca in MagazinRepository: pe rand,
// nu pe luna, ca sa nu scape produsele cu luna necompletata si sa nu fie numarat
// acelasi produs de doua ori daca are doua inregistrari in aceeasi luna.
$subPrag = (int) $pdo->query(
    'SELECT COUNT(*) FROM inventory i
     WHERE i.InventoryID = (
             SELECT i2.InventoryID FROM inventory i2
             WHERE i2.Product_ID = i.Product_ID
             ORDER BY i2.`Date` IS NULL, i2.`Date` DESC, i2.InventoryID DESC
             LIMIT 1
           )
       AND i.Stock_Level <= i.Reorder_Point'
)->fetchColumn();

// Cifrele afisate pe carduri; cheia e si numele tabelei.
$sectiuni = [
    [
        'titlu' => 'Clienti',
        'descriere' => 'Persoane fizice si juridice care trimit colete.',
        'link' => 'src/frontend/clienti.php',
        'total' => $pdo->query('SELECT COUNT(*) FROM clienti')->fetchColumn(),
        'unitate' => 'clienti',
    ],
    [
        'titlu' => 'Expedieri',
        'descriere' => 'Coletele, cu sofer, ruta si status de livrare.',
        'link' => 'src/frontend/expedieri.php',
        'total' => $pdo->query('SELECT COUNT(*) FROM expedieri')->fetchColumn(),
        'unitate' => 'expedieri',
        'extra' => $pdo->query("SELECT COUNT(*) FROM expedieri WHERE Status_expediere = 'In tranzit'")->fetchColumn() . ' in tranzit',
    ],
    [
        'titlu' => 'Comenzi',
        'descriere' => 'Comenzile plasate de clienti din magazin.',
        'link' => 'src/frontend/comenzi.php',
        'total' => $pdo->query('SELECT COUNT(*) FROM comenzi')->fetchColumn(),
        'unitate' => 'comenzi',
        // Semafor: doar starile care exista chiar acum ajung pe cutie.
        'stari' => [
            ['cheie' => 'nou', 'numar' => $comenziNoi, 'text' => 'noi'],
            ['cheie' => 'pierdere', 'numar' => $comenziPePierdere, 'text' => 'pe pierdere'],
            ['cheie' => 'acceptat', 'numar' => $comenziAcceptate, 'text' => 'acceptate'],
        ],
    ],
    [
        'titlu' => 'Soferi',
        'descriere' => 'Curierii si orasul lor de baza.',
        'link' => 'src/frontend/soferi.php',
        'total' => $pdo->query('SELECT COUNT(*) FROM soferi')->fetchColumn(),
        'unitate' => 'soferi',
    ],
    [
        'titlu' => 'Rute',
        'descriere' => 'Traseele intre orase si distantele lor.',
        'link' => 'src/frontend/rute.php',
        'total' => $pdo->query('SELECT COUNT(*) FROM rute')->fetchColumn(),
        'unitate' => 'rute',
    ],
    [
        'titlu' => 'Catalog de produse',
        'descriere' => 'Produsele de vanzare, cate unul pe rand, cu pretul unitar de acum.',
        'link' => 'src/frontend/catalog_produse.php',
        'total' => $produseDistincte,
        'unitate' => 'produse in vanzare',
        'extra' => $nrCategorii . ' categorii',
    ],
    [
        'titlu' => 'Control de stocks',
        'descriere' => 'Istoricul de stoc: cate o inregistrare pe produs si pe luna.',
        'link' => 'src/frontend/produse.php',
        'total' => $inregistrariStoc,
        'unitate' => 'inregistrari de stoc',
        // Pragul se judeca pe luna cea mai recenta a fiecarui produs: altfel un
        // produs care a fost sub prag candva ar fi numarat desi stocul de azi e
        // in regula. De aceea numaratoarea trece prin randul curent, nu prin luna.
        'extra' => $produseDistincte . ' produse, ' . $subPrag . ' sub prag',
    ],
    [
        'titlu' => 'Business Intelligence si analiza de date',
        'descriere' => 'Vanzari, cheltuieli si profit pe zi, comenzi pe oras, activitatea soferilor si raportul Power BI.',
        'link' => 'src/frontend/business.php',
        // Cele opt rapoarte proprii, plus raportul Power BI incorporat.
        'total' => 9,
        'unitate' => 'rapoarte',
        'icona' => 'grafic',
    ],
    [
        'titlu' => 'Laborator: algoritm de optimizare rute si performanta lui',
        'descriere' => 'Ce decide algoritmul, dupa ce criterii, si cat de bine a ales pe cursele deja plecate.',
        'link' => 'src/frontend/laborator_rute.php',
        // Cele patru criterii care compun timpul ajustat: durata prestabilita,
        // viteza, tipul drumului si vremea.
        'total' => 4,
        'unitate' => 'criterii',
        'extra' => $pdo->query('SELECT COUNT(*) FROM expedieri')->fetchColumn() . ' curse analizate',
        'icona' => 'eprubete',
    ],
];

/**
 * Iconita din coltul din dreapta al cutiei, desenata direct in pagina (SVG),
 * ca sa nu depinda de fisiere de imagine.
 *
 * Desenele sunt colorate ca lucrurile pe care le reprezinta - bare de grafic
 * in culori diferite, sticla si lichid la eprubete - nu monocrome.
 */
function icona_sectiune($nume)
{
    $desene = [
        // Grafic cu bare de inaltimi si culori diferite, cu axa dedesubt si
        // linia de tendinta peste ele.
        'grafic' =>
              '<line x1="3" y1="28" x2="30" y2="28" stroke="#94A3B8" stroke-width="1.6" stroke-linecap="round"/>'
            . '<rect x="4"  y="19" width="5" height="8"  rx="1.2" fill="#60A5FA"/>'
            . '<rect x="11" y="14" width="5" height="13" rx="1.2" fill="#2563EB"/>'
            . '<rect x="18" y="17" width="5" height="10" rx="1.2" fill="#F59E0B"/>'
            . '<rect x="25" y="9"  width="5" height="18" rx="1.2" fill="#16A34A"/>'
            . '<polyline points="6.5,17 13.5,12 20.5,15 27.5,7" fill="none" stroke="#111827"'
            . ' stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" opacity=".55"/>'
            . '<circle cx="27.5" cy="7" r="1.7" fill="#111827" opacity=".55"/>',

        // Doua eprubete: cea inclinata toarna lichid verde in cea verticala,
        // care are deja lichid albastru pe fund.
        'eprubete' =>
            // eprubeta verticala: sticla, lichid, luciu si gura
              '<path d="M18 9 V23 a4 4 0 0 0 8 0 V9 Z" fill="#EAF2FA" stroke="#7C8DA6" stroke-width="1.5"/>'
            . '<path d="M18 17 V23 a4 4 0 0 0 8 0 V17 Z" fill="#38BDF8"/>'
            . '<path d="M19.6 11 V22" stroke="#FFFFFF" stroke-width="1.1" stroke-linecap="round" opacity=".7"/>'
            . '<path d="M16.6 9 H27.4" stroke="#5B6B82" stroke-width="1.9" stroke-linecap="round"/>'
            // eprubeta inclinata, rotita ca sa verse spre cea din dreapta
            . '<g transform="translate(9 4) rotate(135 6 3)">'
            . '<path d="M2.5 3 V10 a3.5 3.5 0 0 0 7 0 V3 Z" fill="#EAF2FA" stroke="#7C8DA6" stroke-width="1.5"/>'
            . '<path d="M2.5 3 V7 h7 V3 Z" fill="#22C55E"/>'
            . '<path d="M4 4.5 V9.5" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" opacity=".7"/>'
            . '<path d="M1.4 3 H10.6" stroke="#5B6B82" stroke-width="1.9" stroke-linecap="round"/>'
            . '</g>'
            // firul de lichid care curge dintr-una in alta, plus o picatura
            . '<path d="M15.2 8 Q18.5 9.5 21.2 12.5" fill="none" stroke="#22C55E"'
            . ' stroke-width="2" stroke-linecap="round"/>'
            . '<circle cx="21.9" cy="14.4" r="1.15" fill="#22C55E"/>',
    ];

    if (!isset($desene[$nume])) {
        return '';
    }

    return '<span class="sectiune__icona" aria-hidden="true">'
        . '<svg viewBox="0 0 32 32">' . $desene[$nume] . '</svg>'
        . '</span>';
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LIVRA</title>
    <link rel="stylesheet" href="src/frontend/css/app.css?v=<?= filemtime(__DIR__ . '/src/frontend/css/app.css') ?>">
    <link rel="stylesheet" href="src/frontend/css/index.css?v=<?= filemtime(__DIR__ . '/src/frontend/css/index.css') ?>">
    <?php require_once __DIR__ . '/src/frontend/_analytics.php'; ?>
</head>
<body>
    <header class="header">
        <div class="header__inner">
            <span class="logo">LIVRA</span>
            <span class="header__subtitle">Gestiune comenzi si livrari</span>
            <span class="header__user header__user--singur">
                Conectat: <strong><?= htmlspecialchars($admin['Nume']) ?></strong>
                <a class="header__logout" href="src/frontend/admin_login.php?logout=1">Iesi</a>
            </span>
        </div>
    </header>

    <main class="container">
        <div class="sectiuni">
            <?php foreach ($sectiuni as $s): ?>
                <a class="sectiune" href="<?= $s['link'] ?>">
                    <?php if (!empty($s['icona'])): ?><?= icona_sectiune($s['icona']) ?><?php endif; ?>
                    <span class="sectiune__total"><?= (int) $s['total'] ?></span>
                    <span class="sectiune__unitate"><?= htmlspecialchars($s['unitate']) ?><?php if (!empty($s['extra'])): ?>
                        &middot; <?= htmlspecialchars($s['extra']) ?>
                    <?php endif; ?></span>
                    <h2 class="sectiune__titlu"><?= htmlspecialchars($s['titlu']) ?></h2>
                    <p class="sectiune__descriere"><?= htmlspecialchars($s['descriere']) ?></p>

                    <?php if (!empty($s['stari'])): ?>
                        <span class="stari">
                            <?php foreach ($s['stari'] as $stare): ?>
                                <?php if ((int) $stare['numar'] > 0): ?>
                                    <span class="stare stare--<?= htmlspecialchars($stare['cheie']) ?>">
                                        <span class="stare__bec"></span>
                                        <?= (int) $stare['numar'] ?> <?= htmlspecialchars($stare['text']) ?>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="cta">
            <!-- Magazinul se deschide in tab nou: back office-ul ramane deschis in spate. -->
            <a class="btn cta__btn" href="src/frontend/login.php" target="_blank" rel="noopener">Login client &rarr;</a>
            <span class="cta__text">Clientul se logheaza (isi alege numele) ca sa cumpere din magazin.</span>
        </div>

    </main>
</body>
</html>
