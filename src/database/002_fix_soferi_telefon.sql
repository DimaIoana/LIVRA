-- 002: Repara coloana Telefon din tabela soferi.
--
-- Motiv: aceeasi problema ca la `clienti` (vezi 001) - int(11) pierde zeroul
-- initial (0731... -> 731...) si nu accepta prefix international sau spatii.

ALTER TABLE `soferi`
    MODIFY `Telefon` varchar(20) NOT NULL;

-- Adauga inapoi zeroul initial pentru numerele de 9 cifre.
UPDATE `soferi`
SET `Telefon` = CONCAT('0', `Telefon`)
WHERE CHAR_LENGTH(`Telefon`) = 9;
