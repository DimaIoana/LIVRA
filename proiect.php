<?php

/**
 * Pagina de introducere a proiectului LIVRA (publica).
 *
 * E prima pagina pe care o vede cineva care intra pe proiect de pe site-ul
 * personal: ce e proiectul, cum e structurat, si doua intrari - frontend
 * (eCommerce) si backend (back office).
 *
 * Publica intentionat: nu cere autentificare, altfel vizitatorul ar da de un
 * formular de login fara sa stie unde a ajuns. Autentificarea se cere abia
 * dincolo de butoane.
 *
 * Textul e cel din docs/text-proiect-1.txt, scris fara diacritice ca sa fie
 * consistent cu restul aplicatiei.
 */

$titlu = 'LIVRA - Sistem analitic pentru o companie de curierat';

// Paragrafele de prezentare (docs/text-proiect-1.txt).
$paragrafe = [
    'In cadrul acestui proiect am dorit sa creez un sistem analitic care sa sprijine procesul '
        . 'de luare a deciziilor intr-o companie de curierat pe care am numit-o LIVRA. Sistemul '
        . 'permite crearea unor rapoarte dinamice conectate la date provenite din surse diferite, '
        . 'precum baza de date si API-uri, precum si integrarea cu Power BI.',
    'De asemenea, am dezvoltat un algoritm de optimizare a rutelor cu ajutorul AI (Claude Code), '
        . 'cu scopul de a identifica rute mai eficiente si de a contribui la reducerea costurilor '
        . 'si la cresterea profitabilitatii companiei.',
];

// Restul prezentarii, sub paragrafele de mai sus, in acelasi bloc de text: e
// aceeasi descriere continua, nu o incheiere separata de la finalul paginii.
$paragrafeFinal = [
    'Pentru a asigura rezultate coerente si relevante, am definit in mod clar regulile de calcul '
        . 'si modul in care sunt interpretate datele utilizate in rapoarte.',
    'Sistemul analitic ofera o imagine centralizata asupra activitatii companiei si permite '
        . 'identificarea rapida a principalelor aspecte ale businessului si a nivelului de '
        . 'eficienta al acestuia.',
    'In plus, algoritmul de optimizare selecteaza cea mai rapida ruta, luand in considerare mai '
        . 'multe variabile, precum distanta, costul actual al carburantului, viteza si timpul '
        . 'estimat, tipul de drum si conditiile meteorologice actuale.',
];

// Structura functionala, asa cum e descrisa in text: cele doua jumatati ale
// proiectului, fiecare cu intrarea ei de mai jos.
$structura = [
    [
        'cheie' => 'backend',
        'titlu' => 'Back Office',
        'puncte' => [
            'Gestionarea proceselor de business',
            'Business Intelligence: rapoarte si analize',
            'Integrare cu Power BI Online',
            'Laborator: algoritm de optimizare a rutelor si analiza profitabilitatii',
        ],
        'link' => 'src/frontend/admin_login.php',
        'buton' => 'Acces Back Office',
    ],
    [
        'cheie' => 'frontend',
        'titlu' => 'Front Office',
        'puncte' => [
            'eCommerce: catalog de produse',
            'Comenzi si cos de cumparaturi',
            'Urmarirea comenzilor (AWB)',
        ],
        'link' => 'src/frontend/magazin.php',
        'buton' => 'Acces Front Office (eCommerce)',
    ],
];

?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LIVRA - prezentare proiect</title>
  <link rel="stylesheet" href="src/frontend/css/app.css?v=<?= filemtime(__DIR__ . '/src/frontend/css/app.css') ?>">
  <?php require_once __DIR__ . '/src/frontend/_analytics.php'; ?>
</head>
<body class="pagina-proiect">

<header class="header">
  <div class="header__inner">
    <span class="logo">LIVRA</span>
    <span class="header__subtitle">Prezentare proiect</span>
  </div>
</header>

<main class="container">
  <div class="proiect">

    <div class="proiect__intro">
      <h1 class="proiect__titlu"><?= htmlspecialchars($titlu, ENT_QUOTES, 'UTF-8') ?></h1>
      <?php foreach (array_merge($paragrafe, $paragrafeFinal) as $p): ?>
        <p class="proiect__text"><?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endforeach; ?>
    </div>

    <h2 class="proiect__subtitlu">Structura functionala</h2>

    <div class="proiect__intrari">
      <?php foreach ($structura as $s): ?>
        <section class="intrare intrare--<?= htmlspecialchars($s['cheie'], ENT_QUOTES, 'UTF-8') ?>">
          <h3 class="intrare__titlu"><?= htmlspecialchars($s['titlu'], ENT_QUOTES, 'UTF-8') ?></h3>

          <ul class="intrare__lista">
            <?php foreach ($s['puncte'] as $punct): ?>
              <li><?= htmlspecialchars($punct, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>

          <a class="btn intrare__btn" href="<?= htmlspecialchars($s['link'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($s['buton'], ENT_QUOTES, 'UTF-8') ?>
          </a>
        </section>
      <?php endforeach; ?>
    </div>

  </div>
</main>

</body>
</html>
