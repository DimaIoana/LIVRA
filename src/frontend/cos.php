<?php

require __DIR__ . '/_shop.php';

$page = 'cos.php';
$errors = [];

// --- Actiuni POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        if (isset($_POST['remove'])) {
            // A fost apasat un buton "Scoate" de pe o linie.
            cos_scoate((string) $_POST['remove']);
            shop_flash_set('Produs scos din cos.', 'ok');
        } else {
            foreach (($_POST['qty'] ?? []) as $code => $qty) {
                cos_seteaza((string) $code, $qty);
            }
            shop_flash_set('Cos actualizat.', 'ok');
        }

        header('Location: ' . $page);
        exit;
    }

    if ($action === 'clear') {
        cos_goleste();
        shop_flash_set('Cosul a fost golit.', 'ok');
        header('Location: ' . $page);
        exit;
    }

    if ($action === 'checkout') {
        $rezultat = $magazin->creeazaComanda(
            $_POST['ClientID'] ?? null,
            cos_get(),
            $_POST['observatii'] ?? ''
        );

        if ($rezultat['errors']) {
            $errors = $rezultat['errors'];
        } else {
            cos_goleste();
            shop_flash_set('Comanda #' . $rezultat['id'] . ' a fost trimisa. Multumim!', 'ok');
            header('Location: ' . $page . '?plasata=' . $rezultat['id']);
            exit;
        }
    }
}

// --- Date pentru afisare ---
$cos = cos_get();
$produse = $magazin->produseCurente(array_keys($cos));
$clienti = $magazin->clientiOptiuni();
$flash = shop_flash_get();

// Construieste liniile afisabile + totalul, pastrand ordinea din cos.
$linii = [];
$total = 0;
foreach ($cos as $code => $qty) {
    if (isset($produse[$code])) {
        $p = $produse[$code];
        $pret = (float) $p['Unit_Cost'];
        $subtotal = round($pret * $qty, 2);
        $total += $subtotal;

        $linii[] = [
            'code' => $code,
            'name' => $p['Product_Name'],
            'category' => $p['Category'],
            'price' => $pret,
            'qty' => $qty,
            'stock' => (int) $p['Stock_Level'],
            'subtotal' => $subtotal,
            'disponibil' => true,
        ];
    } else {
        $linii[] = [
            'code' => $code,
            'name' => $code,
            'category' => '',
            'price' => 0,
            'qty' => $qty,
            'stock' => 0,
            'subtotal' => 0,
            'disponibil' => false,
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PRIMUL - Cos de cumparaturi</title>
  <link rel="stylesheet" href="css/app.css?v=<?= filemtime(__DIR__ . '/css/app.css') ?>">
</head>
<body>

<header class="header">
  <div class="header__inner">
    <a class="logo" href="../../index.php">PRIMUL</a>
    <span class="header__subtitle">Cos de cumparaturi</span>
    <nav class="nav">
      <a class="nav__link" href="magazin.php">Produse</a>
      <a class="nav__link nav__link--active" href="cos.php">Cos<?= cos_bucati() ? ' (' . (int) cos_bucati() . ')' : '' ?></a>
      <a class="nav__link" href="portal_client.php">Login clienti</a>
    </nav>
  </div>
</header>

<main class="container">
  <?php if ($flash): ?>
    <div class="alert alert--<?= h($flash['state']) ?>"><?= h($flash['message']) ?></div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="alert alert--fail">
      <?php foreach ($errors as $err): ?>
        <div><?= h($err) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!$linii): ?>
    <div class="card">
      <p class="empty">
        <span style="display:block;font-size:44px;margin-bottom:8px">🛒</span>
        Cosul tau este gol. <a href="magazin.php">Vezi produsele</a>.
      </p>
    </div>
  <?php else: ?>
    <div class="shop-hero">
      <h1 class="shop-hero__title">Cosul tau</h1>
      <p class="shop-hero__subtitle"><?= count($linii) ?> <?= count($linii) === 1 ? 'produs' : 'produse' ?> in cos</p>
    </div>

    <div class="cart">
      <div class="cart__main">
        <form method="post" action="cos.php">
          <input type="hidden" name="action" value="update">
          <div class="card cart__card">
            <table class="table">
              <thead>
                <tr>
                  <th>Produs</th>
                  <th class="cell--number">Pret</th>
                  <th>Cantitate</th>
                  <th class="cell--number">Subtotal</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($linii as $l): ?>
                  <tr>
                    <td>
                      <div class="cart-item__name"><?= h($l['name']) ?></div>
                      <?php if ($l['category'] !== ''): ?>
                        <span class="cart-item__cat"><?= h($l['category']) ?></span>
                      <?php endif; ?>
                      <?php if (!$l['disponibil']): ?>
                        <span class="tag tag--fail">Indisponibil</span>
                      <?php elseif ($l['qty'] > $l['stock']): ?>
                        <span class="tag tag--warn">Peste stoc (disponibil: <?= (int) $l['stock'] ?>)</span>
                      <?php endif; ?>
                    </td>
                    <td class="cell--number"><?= $l['disponibil'] ? h(lei($l['price'])) : '-' ?></td>
                    <td class="cell--nowrap">
                      <input class="input input--qty" type="number" name="qty[<?= h($l['code']) ?>]"
                             value="<?= (int) $l['qty'] ?>" min="1" <?= $l['disponibil'] ? 'max="' . (int) $l['stock'] . '"' : '' ?>>
                    </td>
                    <td class="cell--number"><?= $l['disponibil'] ? h(lei($l['subtotal'])) : '-' ?></td>
                    <td class="row-actions">
                      <button class="btn--link btn--danger" type="submit" name="remove" value="<?= h($l['code']) ?>">Scoate</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="3" class="cell--number"><strong>Total</strong></td>
                  <td class="cell--number"><strong><?= h(lei($total)) ?></strong></td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>

          <div class="shop-actionbar">
            <button class="btn btn--ghost" type="submit">Actualizeaza cantitatile</button>
          </div>
        </form>

        <form method="post" action="cos.php" class="shop-actionbar">
          <input type="hidden" name="action" value="clear">
          <button class="btn btn--ghost btn--danger" type="submit">Goleste cosul</button>
        </form>
      </div>

      <aside class="cart__side">
        <div class="card shop-checkout">
          <h2 class="card__title">Finalizeaza comanda</h2>

          <div class="cart-summary">
            <span class="cart-summary__label">Total de plata</span>
            <span class="cart-summary__value"><?= h(lei($total)) ?></span>
          </div>

          <form method="post" action="cos.php" class="form">
            <input type="hidden" name="action" value="checkout">

            <label class="field">
              <span class="field__label">Client</span>
              <select class="input" name="ClientID" required>
                <option value="">— alege clientul —</option>
                <?php foreach ($clienti as $c): ?>
                  <option value="<?= (int) $c['id'] ?>"><?= h($c['text']) ?></option>
                <?php endforeach; ?>
              </select>
            </label>

            <label class="field">
              <span class="field__label">Observatii (optional)</span>
              <input class="input" type="text" name="observatii" maxlength="500"
                     placeholder="Ex: livrare dupa ora 17">
            </label>

            <div class="form__actions form__actions--split">
              <a class="btn btn--ghost" href="magazin.php">Continua cumparaturile</a>
              <button class="btn" type="submit">Trimite comanda</button>
            </div>
          </form>
        </div>
      </aside>
    </div>
  <?php endif; ?>
</main>

</body>
</html>
