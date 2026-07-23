-- 1. Sterge toate datele de comenzi si expedieri (start curat) si reseteaza
--    numerotarea (ID-urile si AWB-ul o iau de la 1).
DELETE FROM expedieri;
DELETE FROM comenzi_produse;
DELETE FROM comenzi;

ALTER TABLE expedieri AUTO_INCREMENT = 1;
ALTER TABLE comenzi_produse AUTO_INCREMENT = 1;
ALTER TABLE comenzi AUTO_INCREMENT = 1;

-- 2. Data expedierii si datele de livrare devin DATETIME (cu ora). Coloana
--    creata_la (din 009) devine redundanta - Data_expediere pastreaza ora acum.
ALTER TABLE expedieri
    DROP COLUMN creata_la,
    MODIFY COLUMN Data_expediere DATETIME NOT NULL,
    MODIFY COLUMN Data_livrare_estimata DATETIME NOT NULL,
    MODIFY COLUMN Data_livrare_efectiva DATETIME NULL;
