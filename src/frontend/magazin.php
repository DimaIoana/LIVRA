<?php

require __DIR__ . '/_shop.php';

// Magazinul se vede si nelogat: cine vine de pe pagina de prezentare a
// proiectului trebuie sa dea de produse, nu de un formular gol. Logarea se cere
// abia la cumparat, printr-o fereastra peste catalog.
$clientLogat = client_logat();
$logat = $clientLogat !== null;

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

// --- Fereastra de logare: deschisa sau inchisa ---
// Alegerea se tine in sesiune, nu in adresa: altfel fereastra ar reaparea la
// fiecare filtrare sau la deschiderea unui produs.
if (isset($_GET['vezi'])) {
    $_SESSION['shop_fara_login'] = true;
    header('Location: ' . $page . $filterQuery);
    exit;
}

if (isset($_GET['login'])) {
    unset($_SESSION['shop_fara_login']);
    header('Location: ' . $page . $filterQuery);
    exit;
}

// --- Adaugare in cos (POST) ---
// Fara client logat nu exista cos, deci cererea se opreste aici, oricat de
// mestesugit ar fi trimisa.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add' && !$logat) {
    header('Location: ' . $page . $filterQuery);
    exit;
}

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

// Fereastra de logare apare peste catalog cat timp nu e nimeni logat si nu s-a
// ales "Doar ma uit".
$aratLogin = !$logat && empty($_SESSION['shop_fara_login']);
$clientiLogin = $aratLogin ? $magazin->clientiOptiuni() : [];

// Adresele care inchid, respectiv redeschid fereastra.
$urlVezi = $page . ($filterQuery === '' ? '?vezi=1' : $filterQuery . '&vezi=1');
$urlLogin = $page . ($filterQuery === '' ? '?login=1' : $filterQuery . '&login=1');

// Fereastra cu detaliile unui produs, deschisa de click pe poza. Fiind totul
// randat pe server (fara JS), starea ei sta in adresa: ?produs=<cod>. Produsul
// se ia din catalog, nu din lista de mai sus, ca sa mearga si cand codul din
// adresa nu e printre cele filtrate acum.
$codDetaliu = trim((string) ($_GET['produs'] ?? ''));
$detaliu = null;

if ($codDetaliu !== '') {
    $gasite = $magazin->produseCurente([$codDetaliu]);
    $detaliu = $gasite[$codDetaliu] ?? null;
}

/** Adresa care deschide fereastra de detalii, pastrand filtrele curente. */
function url_detaliu($page, array $filterParams, $code)
{
    return $page . '?' . http_build_query($filterParams + ['produs' => $code]);
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LIVRA - Magazin</title>
  <link rel="stylesheet" href="css/app.css?v=<?= filemtime(__DIR__ . '/css/app.css') ?>">
  <?php require_once __DIR__ . '/_analytics.php'; ?>
</head>
<body>

<header class="header">
  <div class="header__inner">
    <a class="logo" href="magazin.php">LIVRA</a>
    <span class="header__subtitle">Magazin online</span>
    <nav class="nav">
      <a class="nav__link nav__link--active" href="magazin.php">Produse</a>
      <a class="nav__link" href="cos.php">Cos<?= cos_bucati() ? ' (' . (int) cos_bucati() . ')' : '' ?></a>
      <a class="nav__link" href="portal_client.php">Urmarire colet</a>
      <?php if ($logat): ?>
        <span class="nav__link" style="color:var(--text-muted)">Salut, <?= h($clientLogat['nume']) ?></span>
        <a class="nav__link" href="login.php?logout=1">Iesire</a>
      <?php else: ?>
        <a class="nav__link" href="<?= h($urlLogin) ?>">Intra in cont</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="container">
  <div class="shop-hero">
    <h1 class="shop-hero__title">Catalog produse</h1>
    <p class="shop-hero__subtitle">
      <?= $logat
          ? 'Bifeaza produsele dorite, alege cantitatea si adauga-le in cos.'
          : 'Rasfoieste catalogul. Ca sa pui produse in cos, intra intai in cont.' ?>
    </p>
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
        <?php if ($logat): ?>
          <button class="btn" type="submit">Adauga selectatele in cos</button>
        <?php else: ?>
          <a class="btn" href="<?= h($urlLogin) ?>">Intra in cont ca sa cumperi</a>
        <?php endif; ?>
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
          <div class="product<?= $epuizat ? ' product--out' : '' ?>" style="--h: <?= $hue ?>">
            <div class="product__media<?= $poza ? ' product__media--photo' : '' ?>">
              <!-- Poza deschide fereastra cu detaliile produsului. E link, nu buton
                   cu JS: tot magazinul e randat pe server, deci starea ferestrei
                   sta in adresa. -->
              <a class="product__zoom" href="<?= h(url_detaliu($page, $filterParams, $code)) ?>"
                 aria-label="Vezi detalii pentru <?= h($p['Product_Name']) ?>">
                <?php if ($poza): ?>
                  <img class="product__img" src="<?= h($poza) ?>" alt="<?= h($p['Product_Name']) ?>" loading="lazy">
                <?php endif; ?>
                <span class="product__zoom-hint" aria-hidden="true">Vezi detalii</span>
              </a>

              <?php if ($epuizat): ?>
                <span class="product__out-flag">Epuizat</span>
              <?php elseif ($logat): ?>
                <input class="product__check" type="checkbox" id="sel-<?= h($code) ?>"
                       name="sel[<?= h($code) ?>]" value="1"
                       aria-label="Selecteaza <?= h($p['Product_Name']) ?>">
                <span class="product__flag" aria-hidden="true">Selectat</span>
              <?php endif; ?>
            </div>

            <div class="product__body">
              <?php
                // Numele, categoria si stocul bifeaza produsul (eticheta pentru
                // checkbox). Casuta de cantitate ramane in afara etichetei, ca un
                // click in ea sa nu schimbe selectia. Fara checkbox (produs
                // epuizat sau vizitator nelogat) nu exista nici eticheta.
                $bifabil = !$epuizat && $logat;
                $deschide = $bifabil ? '<label class="product__pick" for="sel-' . h($code) . '">' : '<div class="product__pick">';
                $inchide = $bifabil ? '</label>' : '</div>';
              ?>
              <?= $deschide ?>
                <h3 class="product__name"><?= h($p['Product_Name']) ?></h3>
                <span class="product__cat"><?= h($p['Category']) ?></span>

                <div class="product__stock<?= $epuizat ? ' product__stock--out' : '' ?>">
                  <?= $epuizat ? 'Stoc epuizat' : 'In stoc: ' . $stoc ?>
                </div>
              <?= $inchide ?>

              <div class="product__foot">
                <span class="product__price"><?= h(lei($p['Unit_Cost'])) ?></span>
                <?php if ($bifabil): ?>
                  <div class="product__qty">
                    <span class="product__qty-label">Cant.</span>
                    <input class="input product__qty-input" type="number" name="qty[<?= h($code) ?>]"
                           value="1" min="1" max="<?= $stoc ?>">
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </form>
  <?php endif; ?>
</main>

<?php if ($aratLogin): ?>
  <!-- Fereastra de logare, peste catalog. Formularul merge la login.php, care
       stie deja sa autentifice si sa trimita inapoi in magazin. -->
  <div class="modal">
    <a class="modal__backdrop" href="<?= h($urlVezi) ?>"
       aria-label="Inchide si rasfoieste catalogul"></a>

    <div class="modal__box" role="dialog" aria-modal="true" aria-labelledby="login-titlu">
      <h2 class="modal__title" id="login-titlu">Intra in cont</h2>
      <p class="field__hint" style="margin:-10px 0 16px">
        Magazinul e o demonstratie: alege-ti numele din lista, fara parola.
      </p>

      <form class="form" method="post" action="login.php">
        <label class="field">
          <span class="field__label">Client</span>
          <select class="input" name="ClientID" required>
            <option value="">— alege-ti numele —</option>
            <?php foreach ($clientiLogin as $c): ?>
              <option value="<?= (int) $c['id'] ?>"><?= h($c['text']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <div class="form__actions">
          <a class="btn btn--ghost" href="<?= h($urlVezi) ?>">Doar ma uit</a>
          <button class="btn" type="submit">Intra</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php if ($detaliu): ?>
  <?php
    $dStoc = (int) $detaliu['Stock_Level'];
    $dEpuizat = $dStoc <= 0;
    $dPoza = poza_url($detaliu['poze'] ?? '');
    $dHue = crc32($detaliu['Category']) % 360;
    // Inchiderea inseamna intoarcerea la lista, fara ?produs= in adresa.
    $inapoi = $page . $filterQuery;
  ?>
  <div class="modal">
    <a class="modal__backdrop" href="<?= h($inapoi) ?>" aria-label="Inchide"></a>

    <div class="modal__box modal__box--lat" role="dialog" aria-modal="true" aria-labelledby="detaliu-nume">
      <div class="produs-detaliu" style="--h: <?= $dHue ?>">
        <!-- Stanga: poza -->
        <div class="produs-detaliu__media<?= $dPoza ? ' produs-detaliu__media--photo' : '' ?>">
          <?php if ($dPoza): ?>
            <img class="produs-detaliu__img" src="<?= h($dPoza) ?>" alt="<?= h($detaliu['Product_Name']) ?>">
          <?php endif; ?>
        </div>

        <!-- Dreapta: numele, stocul, categoria -->
        <div class="produs-detaliu__info">
          <h2 class="produs-detaliu__nume" id="detaliu-nume"><?= h($detaliu['Product_Name']) ?></h2>

          <dl class="produs-detaliu__lista">
            <dt>Stoc</dt>
            <dd class="<?= $dEpuizat ? 'produs-detaliu__stoc--out' : 'produs-detaliu__stoc' ?>">
              <?= $dEpuizat ? 'Stoc epuizat' : 'In stoc: ' . $dStoc . ' buc.' ?>
            </dd>

            <dt>Categorie</dt>
            <dd><span class="product__cat"><?= h($detaliu['Category']) ?></span></dd>
          </dl>

          <a class="btn btn--ghost produs-detaliu__inchide" href="<?= h($inapoi) ?>">Inchide</a>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

</body>
</html>
