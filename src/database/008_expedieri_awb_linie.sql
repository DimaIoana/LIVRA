-- Pregateste expedierile pentru functionalitatea de optimizare rute:
--   awb     - numar de expeditie unic, generat automat la crearea expedierii
--   LinieID - leaga expedierea de linia de produs din comanda (comenzi_produse),
--             ca sa stim ce s-a expediat si sa nu expediem de doua ori aceeasi linie
--
-- Vezi docs/algoritm_optimizare_rute.md.

ALTER TABLE expedieri
    ADD COLUMN awb VARCHAR(20) NULL UNIQUE AFTER ExpediereID,
    ADD COLUMN LinieID INT NULL AFTER RutaID,
    ADD CONSTRAINT fk_expedieri_linie
        FOREIGN KEY (LinieID) REFERENCES comenzi_produse (LinieID)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- Backfill AWB pentru expedierile existente: AWB + data + ID pe 6 cifre (unic prin ID).
UPDATE expedieri
SET awb = CONCAT('AWB', DATE_FORMAT(Data_expediere, '%Y%m%d'), LPAD(ExpediereID, 6, '0'))
WHERE awb IS NULL;
