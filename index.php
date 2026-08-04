<?php

// Back office: doar pentru utilizatorii autentificati (vezi src/frontend/_auth.php).
require __DIR__ . '/src/frontend/_auth.php';
cere_admin();

$admin = admin_logat();

require_once __DIR__ . '/src/database/db_connection.php';
require __DIR__ . '/src/backend/ComandaRepository.php';

$versiune_mysql = $pdo->query('SELECT VERSION()')->fetchColumn();

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
        'titlu' => 'Produse',
        'descriere' => 'Stocul lunar pe produs.',
        'link' => 'src/frontend/produse.php',
        'total' => $pdo->query('SELECT COUNT(DISTINCT Product_ID) FROM inventory')->fetchColumn(),
        'unitate' => 'produse',
        // Doar cea mai recenta luna a fiecarui produs: altfel un produs care a
        // fost sub prag candva ar fi numarat desi stocul de azi e in regula.
        'extra' => $pdo->query(
            'SELECT COUNT(*) FROM inventory i
             WHERE i.`Date` = (SELECT MAX(i2.`Date`) FROM inventory i2 WHERE i2.Product_ID = i.Product_ID)
               AND i.Stock_Level <= i.Reorder_Point'
        )->fetchColumn() . ' sub prag',
    ],
    [
        'titlu' => 'Business Intelligence si analiza de date',
        'descriere' => 'Vanzari, cheltuieli si profit pe zi, comenzi pe oras, activitatea soferilor si raportul Power BI.',
        'link' => 'src/frontend/business.php',
        // Cele opt rapoarte proprii, plus raportul Power BI incorporat.
        'total' => 9,
        'unitate' => 'rapoarte',
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
    ],
];
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LIVRA</title>
    <link rel="stylesheet" href="src/frontend/css/app.css?v=<?= filemtime(__DIR__ . '/src/frontend/css/app.css') ?>">
    <link rel="stylesheet" href="src/frontend/css/index.css?v=<?= filemtime(__DIR__ . '/src/frontend/css/index.css') ?>">
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

        <div class="cta">
            <a class="btn cta__btn" href="src/frontend/portal_client.php">Urmarire colet &rarr;</a>
            <span class="cta__text">Clientul isi alege numele si urmareste coletul dupa AWB: produs, distanta, timp si cat timp a trecut.</span>
        </div>

        <p class="subsol">
            MySQL <?= htmlspecialchars($versiune_mysql) ?> &middot;
            <a href="src/frontend/test.php">Pagina de test</a>
        </p>
    </main>
</body>
</html>
