-- Date (nu schema): coduri de produs noi, incrementale (PRD-0001, PRD-0002, ...).
--
-- Codul vechi era P001...P007. Cel nou are un prefix clar si patru cifre, deci
-- se poate creste pana la PRD-9999 fara sa-si schimbe forma.
--
-- LEGATURILE: `Product_ID` nu are cheie straina in baza, dar apare in doua
-- tabele - `inventory` (catalogul/istoricul de stoc) si `comenzi_produse`
-- (liniile comenzilor). Amandoua se schimba aici, in aceeasi tranzactie, deci
-- nicio linie de comanda nu ramane fara produs.
--
-- Restul legaturilor merg prin alte chei si NU se ating:
--   comenzi          -> ComandaID
--   comenzi_produse  -> LinieID (si ComandaID)
--   expedieri        -> LinieID -> comenzi_produse  (deci urmeaza automat)
--   inventory        -> InventoryID
-- Adica expedierile isi pastreaza legatura cu produsul prin linia de comanda,
-- fara sa fie atinse.
--
-- Maparea se face o singura data, intr-un tabel temporar, ca ambele tabele sa
-- primeasca exact aceleasi coduri. Ordinea e dupa codul vechi, deci
-- P001 -> PRD-0001, P002 -> PRD-0002 si asa mai departe.
--
-- ATENTIE: nu e idempotent. Rulat a doua oara nu mai gaseste coduri vechi de
-- mapat (tabelul temporar iese gol), deci nu strica nimic, dar nici nu face ceva.
-- Dupa migrare, `seed_comenzi_test.sql` nu mai gaseste produsele: el cauta dupa
-- P001...P007.

CREATE TEMPORARY TABLE cod_produs_nou (
    vechi VARCHAR(10) NOT NULL PRIMARY KEY,
    nou   VARCHAR(10) NOT NULL UNIQUE
);

INSERT INTO cod_produs_nou (vechi, nou)
SELECT Product_ID,
       CONCAT('PRD-', LPAD(ROW_NUMBER() OVER (ORDER BY Product_ID), 4, '0'))
FROM inventory
GROUP BY Product_ID;

START TRANSACTION;

UPDATE inventory i
JOIN cod_produs_nou c ON c.vechi = i.Product_ID
SET i.Product_ID = c.nou;

UPDATE comenzi_produse cp
JOIN cod_produs_nou c ON c.vechi = cp.Product_ID
SET cp.Product_ID = c.nou;

COMMIT;

DROP TEMPORARY TABLE cod_produs_nou;
