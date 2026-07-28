-- Marcaj: stocul a fost deja scazut din inventory pentru aceasta expediere (la
-- livrare). Impiedica scaderea dubla daca o expediere livrata e salvata din nou.
-- Scaderea efectiva o face ExpediereRepository cand statusul devine "Livrat".

ALTER TABLE expedieri
    ADD COLUMN stoc_scazut TINYINT(1) NOT NULL DEFAULT 0 AFTER Status_expediere;
