-- Momentul exact al crearii expedierii, ca sa putem calcula corect "timpul
-- trecut" (Data_expediere e doar data, fara ora, deci masura din miezul noptii
-- umfla timpul scurs). Vezi docs/algoritm_optimizare_rute.md si portal_client.php.

ALTER TABLE expedieri
    ADD COLUMN creata_la DATETIME NULL AFTER Data_expediere;

-- Backfill: expedierile legate de o comanda mostenesc ora comenzii, DAR niciodata
-- inainte de propria data de expediere (o expediere poate fi creata la zile dupa
-- comanda). GREATEST alege ora comenzii doar daca e in aceeasi zi sau dupa.
UPDATE expedieri e
JOIN comenzi_produse cp ON cp.LinieID = e.LinieID
JOIN comenzi co ON co.ComandaID = cp.ComandaID
SET e.creata_la = GREATEST(co.Data_comanda, TIMESTAMP(e.Data_expediere, '07:00:00'))
WHERE e.creata_la IS NULL;

-- Restul (expedieri vechi fara comanda): ora 07:00 (inceputul programului) din
-- ziua expedierii, ca fallback rezonabil.
UPDATE expedieri
SET creata_la = CONCAT(Data_expediere, ' 07:00:00')
WHERE creata_la IS NULL;
