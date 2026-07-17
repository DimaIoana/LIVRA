-- 003: Data_livrare_efectiva devine optionala in tabela expedieri.
--
-- Motiv: coloana era NOT NULL, dar o expediere "In tranzit" sau "Returnat" nu
-- are o data de livrare efectiva. MySQL a acceptat data-zero '0000-00-00', care
-- nu e o data reala: strica sortarea si orice calcul de intarziere
-- (DATEDIFF cu '0000-00-00' da rezultate absurde).
--
-- Dupa migrare, "nelivrat inca" se exprima corect prin NULL.

ALTER TABLE `expedieri`
    MODIFY `Data_livrare_efectiva` date DEFAULT NULL;

UPDATE `expedieri`
SET `Data_livrare_efectiva` = NULL
WHERE `Data_livrare_efectiva` = '0000-00-00';
