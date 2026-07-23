<?php

require __DIR__ . '/_shop.php';

$page = 'magazin.php';

// --- Filtre din URL ---
$search = trim((string) ($_GET['search'] ?? ''));
$categorie = trim((string) ($_GET['categorie'] ?? ''));

// Query string cu filtrele curente, pentru redirect si linkuri.
$filterParams = [];
if ($search !== '') {
    $filterParams['search'] = $search;
}
if ($categorie !== '') {
    $filterParams['categorie'] = $categorie;
}
$filterQuery = $filterParams ? '?' . http_build_query($filterParams) : '';

// --- Adaugare in cos (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $selectate = $_POST['sel'] ?? [];      // [cod => "1"] pentru bifate
    $cantitati = $_POST['qty'] ?? [];      // [cod => cantitate]

    $adaugari = [];
    foreach (array_keys($selectate) as $code) {
        $adaugari[$code] = $cantitati[$code] ?? 1;
    }

    if ($adaugari) {
        cos_adauga($adaugari);
        $nr = count($adaugari);
        shop_flash_set($nr === 1 ? 'Un produs adaugat in cos.' : $nr . ' produse adaugate in cos.', 'ok');
    } else {
        shop_flash_set('Nu ai bifat niciun produs.', 'fail');
    }

    header('Location: ' . $page . $filterQuery);
    exit;
}

// --- Date pentru afisare ---
$produse = $magazin->catalog($search, $categorie);
$categorii = $magazin->categorii();
$flash = shop_flash_get();

?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PRIMUL - Magazin</title>
  <link rel="stylesheet" href="css/app.css?v=<?= filemtime(__DIR__ . '/css/app.css') ?>">
</head>
<body>

<header class="header">
  <div class="header__inner">
    <a class="logo" href="../../index.php">PRIMUL</a>
    <span class="header__subtitle">Magazin online</span>
    <nav class="nav">
      <a class="nav__link nav__link--active" href="magazin.php">Produse</a>
      <a class="nav__link" href="cos.php">Cos<?= cos_bucati() ? ' (' . (int) cos_bucati() . ')' : '' ?></a>
      <a class="nav__link" href="portal_client.php">Login clienti</a>
    </nav>
  </div>
</header>

<main class="container">
  <div class="shop-hero">
    <h1 class="shop-hero__title">Catalog produse</h1>
    <p class="shop-hero__subtitle">Bifeaza produsele dorite, alege cantitatea si adauga-le in cos.</p>
  </div>

  <form class="toolbar" method="get" action="magazin.php">
    <div class="toolbar__field">
      <span class="toolbar__icon" aria-hidden="true">🔍</span>
      <input class="input toolbar__search" type="search" name="search"
             value="<?= h($search) ?>" placeholder="Cauta dupa nume, cod sau categorie...">
    </div>
    <select class="input shop-catselect" name="categorie">
      <option value="">Toate categoriile</option>
      <?php foreach ($categorii as $cat): ?>
        <option value="<?= h($cat) ?>" <?= $cat === $categorie ? 'selected' : '' ?>><?= h($cat) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn--ghost" type="submit">Filtreaza</button>
  </form>

  <?php if ($flash): ?>
    <div class="alert alert--<?= h($flash['state']) ?>"><?= h($flash['message']) ?></div>
  <?php endif; ?>

  <?php if (!$produse): ?>
    <div class="card"><p class="empty">Nu s-au gasit produse.</p></div>
  <?php else: ?>
    <form method="post" action="<?= h($page . $filterQuery) ?>">
      <input type="hidden" name="action" value="add">

      <div class="shop-actionbar">
        <span class="shop-actionbar__hint"><?= count($produse) ?> produse disponibile</span>
        <button class="btn" type="submit">Adauga selectatele in cos</button>
      </div>

      <div class="shop-grid">
        <?php foreach ($produse as $p): ?>
          <?php
            $code = $p['Product_ID'];
            $stoc = (int) $p['Stock_Level'];
            $epuizat = $stoc <= 0;
            // Culoare stabila per categorie, ca fiecare tigla sa fie colorata (mozaic).
            $hue = crc32($p['Category']) % 360;
            $poza = poza_url($p['poze'] ?? '');
          ?>
          <label class="product<?= $epuizat ? ' product--out' : '' ?>" style="--h: <?= $hue ?>">
            <div class="product__media<?= $poza ? ' product__media--photo' : '' ?>">
              <?php if ($poza): ?>
                <img class="product__img" src="<?= h($poza) ?>" alt="<?= h($p['Product_Name']) ?>" loading="lazy">
              <?php endif; ?>
              <?php if ($epuizat): ?>
                <span class="product__out-flag">Epuizat</span>
              <?php else: ?>
                <input class="product__check" type="checkbox" name="sel[<?= h($code) ?>]" value="1"
                       aria-label="Selecteaza <?= h($p['Product_Name']) ?>">
                <span class="product__flag" aria-hidden="true">Selectat</span>
              <?php endif; ?>
              <span class="product__cat"><?= h($p['Category']) ?></span>
            </div>

            <div class="product__body">
              <h3 class="product__name"><?= h($p['Product_Name']) ?></h3>

              <div class="product__stock<?= $epuizat ? ' product__stock--out' : '' ?>">
                <?= $epuizat ? 'Stoc epuizat' : 'In stoc: ' . $stoc ?>
              </div>

              <div class="product__foot">
                <span class="product__price"><?= h(lei($p['Unit_Cost'])) ?></span>
                <?php if (!$epuizat): ?>
                  <div class="product__qty">
                    <span class="product__qty-label">Cant.</span>
                    <input class="input product__qty-input" type="number" name="qty[<?= h($code) ?>]"
                           value="1" min="1" max="<?= $stoc ?>">
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </label>
        <?php endforeach; ?>
      </div>

      <div class="shop-actionbar shop-actionbar--sticky">
        <span class="shop-actionbar__hint">Ai terminat selectia?</span>
        <button class="btn" type="submit">Adauga selectatele in cos</button>
      </div>
    </form>
  <?php endif; ?>
</main>

</body>
</html>
