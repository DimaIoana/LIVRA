-- Separa pretul de vanzare de costul de achizitie in tabela `inventory`.
--
-- `Unit_Cost` ramane coloana existenta, dar semnificatia ei e pretul unitar
-- afisat clientului in magazin (asa e folosita deja in magazin.php / cos.php),
-- deci in back office se numeste de acum "Pret unitar".
-- `Cost_Unitar` e nou: cat costa produsul pe firma (achizitie). Optional,
-- ca inregistrarile existente sa ramana valide pana e completat.

ALTER TABLE inventory
    ADD COLUMN Cost_Unitar DECIMAL(10, 2) NULL DEFAULT NULL AFTER Unit_Cost;
