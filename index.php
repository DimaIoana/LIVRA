<?php

require __DIR__ . '/src/database/db_connection.php';

$versiune_mysql = $pdo->query('SELECT VERSION()')->fetchColumn();

// Cifrele afisate pe carduri; cheia e si numele tabelei.
$sectiuni = [
    [
        'titlu' => 'Clienti',
        'descriere' => 'Persoane fizice si juridice care trimit colete.',
        'link' => 'src/frontend/clienti.html',
        'total' => $pdo->query('SELECT COUNT(*) FROM clienti')->fetchColumn(),
        'unitate' => 'clienti',
    ],
    [
        'titlu' => 'Expedieri',
        'descriere' => 'Coletele, cu sofer, ruta si status de livrare.',
        'link' => 'src/frontend/expedieri.html',
        'total' => $pdo->query('SELECT COUNT(*) FROM expedieri')->fetchColumn(),
        'unitate' => 'expedieri',
        'extra' => $pdo->query("SELECT COUNT(*) FROM expedieri WHERE Status_expediere = 'In tranzit'")->fetchColumn() . ' in tranzit',
    ],
    [
        'titlu' => 'Soferi',
        'descriere' => 'Curierii si orasul lor de baza.',
        'link' => 'src/frontend/soferi.html',
        'total' => $pdo->query('SELECT COUNT(*) FROM soferi')->fetchColumn(),
        'unitate' => 'soferi',
    ],
    [
        'titlu' => 'Produse',
        'descriere' => 'Stocul lunar pe produs.',
        'link' => 'src/frontend/produse.html',
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
];
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PRIMUL</title>
    <link rel="stylesheet" href="src/frontend/css/app.css">
    <link rel="stylesheet" href="src/frontend/css/index.css">
</head>
<body>
    <header class="header">
        <div class="header__inner">
            <span class="logo">PRIMUL</span>
            <span class="header__subtitle">Gestiune comenzi si livrari</span>
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
                </a>
            <?php endforeach; ?>
        </div>

        <p class="subsol">
            MySQL <?= htmlspecialchars($versiune_mysql) ?> &middot;
            <a href="src/frontend/test.html">Pagina de test</a>
        </p>
    </main>
</body>
</html>
