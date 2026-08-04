<?php

/**
 * Login client pentru magazin: clientul isi alege numele din lista (fara parola)
 * si intra ca sa poata cumpara. Doar clientii existenti (adaugati de admin).
 *
 * Sesiunea tine clientul logat (vezi _shop.php: client_login / client_logat).
 */

require __DIR__ . '/_shop.php';

// Delogare.
if (isset($_GET['logout'])) {
    client_logout();
    shop_flash_set('Te-ai delogat.', 'ok');
    header('Location: login.php');
    exit;
}

// Deja logat -> direct la magazin.
if (client_logat()) {
    header('Location: magazin.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($_POST['ClientID'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($id === false) {
        $errors[] = 'Alege-ti numele din lista.';
    } else {
        $stmt = $pdo->prepare('SELECT Nume FROM clienti WHERE ClientID = :id');
        $stmt->execute(['id' => $id]);
        $nume = $stmt->fetchColumn();

        if ($nume === false) {
            $errors[] = 'Clientul selectat nu exista.';
        } else {
            client_login($id, $nume);
            header('Location: magazin.php');
            exit;
        }
    }
}

$clienti = $magazin->clientiOptiuni();
$flash = shop_flash_get();

?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LIVRA - Login client</title>
  <link rel="stylesheet" href="css/app.css?v=<?= filemtime(__DIR__ . '/css/app.css') ?>">
</head>
<body>

<header class="header">
  <div class="header__inner">
    <a class="logo" href="magazin.php">LIVRA</a>
    <span class="header__subtitle">Login client</span>
  </div>
</header>

<main class="container">

  <?php if ($flash): ?>
    <div class="alert alert--<?= h($flash['state']) ?>"><?= h($flash['message']) ?></div>
  <?php endif; ?>

  <div class="shop-hero">
    <h1 class="shop-hero__title">Intra in cont</h1>
    <p class="shop-hero__subtitle">Alege-ti numele ca sa poti cumpara produse.</p>
  </div>

  <div class="card" style="padding:22px;max-width:460px">
    <form class="form" method="post" action="login.php">
      <?php if ($errors): ?>
        <p class="form__error"><?= h(implode(' ', $errors)) ?></p>
      <?php endif; ?>

      <label class="field">
        <span class="field__label">Client</span>
        <select class="input" name="ClientID" required>
          <option value="">— alege-ti numele —</option>
          <?php foreach ($clienti as $c): ?>
            <option value="<?= (int) $c['id'] ?>"><?= h($c['text']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <div class="form__actions">
        <button class="btn" type="submit">Intra</button>
      </div>
    </form>
  </div>

  <p class="subsol" style="margin-top:20px">
    Vrei doar sa urmaresti un colet? <a href="portal_client.php">Urmarire colet dupa AWB</a>.
  </p>

</main>

</body>
</html>
