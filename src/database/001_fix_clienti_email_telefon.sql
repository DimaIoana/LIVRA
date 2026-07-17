-- 001: Repara coloanele Email si Telefon din tabela clienti.
--
-- Motiv:
--   Email era varchar(20) - prea scurt, emailurile erau taiate la salvare.
--   Telefon era int(11)   - pierdea zeroul initial (0721... -> 721...) si nu
--                           accepta prefix international (+40) sau spatii.
--
-- Atentie: emailurile deja taiate NU pot fi recuperate automat, trebuie
-- reintroduse manual. Migrarea doar opreste taierea de aici inainte.

ALTER TABLE `clienti`
    MODIFY `Email` varchar(150) NOT NULL,
    MODIFY `Telefon` varchar(20) NOT NULL;

-- Normalizeaza numerele existente: adauga inapoi zeroul initial pentru
-- numerele de 9 cifre (ex: 721111111 -> 0721111111).
UPDATE `clienti`
SET `Telefon` = CONCAT('0', `Telefon`)
WHERE CHAR_LENGTH(`Telefon`) = 9;
