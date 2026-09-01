<?php

/**
 * Butoanele din josul ferestrei de decizie a unei comenzi (inclus din
 * `comenzi.php`).
 *
 * Optiunile sunt formulare POST catre aceeasi pagina, tratate de `rowActions`
 * din `_crud_page.php`.
 *
 * Decizia e a utilizatorului, nu a aplicatiei: "Trimite" accepta comanda si cand
 * estimarea iese pe pierdere, iar o comanda anulata din greseala se poate
 * redeschide, cat timp n-a plecat nimic din ea.
 *
 * "Trimite" e butonul plin (actiunea obisnuita), iar "Anuleaza" e conturat, ca
 * sa nu fie apasat din greseala in locul celuilalt - stau unul langa altul.
 *
 * Variabile asteptate: $row (comanda), $inapoi (adresa de intoarcere),
 * $deschisa (mai poate fi decisa), $redeschidere (e anulata si nimic expediat).
 */

?>
<div class="form__actions form__actions--decizie">
  <a class="btn btn--ghost" href="<?= h($inapoi) ?>">Inchide</a>

  <?php if ($deschisa): ?>
    <form method="post" action="<?= h($inapoi) ?>">
      <input type="hidden" name="action" value="anuleaza">
      <input type="hidden" name="ComandaID" value="<?= (int) $row['ComandaID'] ?>">
      <button class="btn btn--ghost btn--danger" type="submit">Anuleaza comanda</button>
    </form>

    <form method="post" action="<?= h($inapoi) ?>">
      <input type="hidden" name="action" value="trimite">
      <input type="hidden" name="ComandaID" value="<?= (int) $row['ComandaID'] ?>">
      <button class="btn" type="submit">Trimite comanda</button>
    </form>

  <?php elseif ($redeschidere): ?>
    <form method="post" action="<?= h($inapoi) ?>">
      <input type="hidden" name="action" value="redeschide">
      <input type="hidden" name="ComandaID" value="<?= (int) $row['ComandaID'] ?>">
      <button class="btn" type="submit">Redeschide comanda</button>
    </form>
  <?php endif; ?>
</div>
