<?php

/**
 * Butoanele din josul ferestrei de decizie a unei comenzi (inclus din
 * `comenzi.php`).
 *
 * Cele doua optiuni sunt formulare POST catre aceeasi pagina, tratate de
 * `rowActions` din `_crud_page.php`. O comanda care nu mai e deschisa (trimisa,
 * anulata sau cu linii deja expediate) n-are ce decide, deci ramane doar
 * butonul de inchidere.
 *
 * Variabile asteptate: $row (comanda), $inapoi (adresa de intoarcere),
 * $deschisa (mai poate fi decisa).
 */

?>
<div class="form__actions form__actions--decizie">
  <a class="btn btn--ghost" href="<?= h($inapoi) ?>">Inchide</a>

  <?php if ($deschisa): ?>
    <form method="post" action="<?= h($inapoi) ?>">
      <input type="hidden" name="action" value="anuleaza">
      <input type="hidden" name="ComandaID" value="<?= (int) $row['ComandaID'] ?>">
      <button class="btn btn--danger-solid" type="submit">Anuleaza</button>
    </form>

    <form method="post" action="<?= h($inapoi) ?>">
      <input type="hidden" name="action" value="trimite">
      <input type="hidden" name="ComandaID" value="<?= (int) $row['ComandaID'] ?>">
      <button class="btn" type="submit">Trimite</button>
    </form>
  <?php endif; ?>
</div>
