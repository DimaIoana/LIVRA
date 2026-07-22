-- Adauga coloana `viteza` (km/h) pentru fiecare ruta si o completeaza cu o
-- valoare aleatoare, rotunjita, intre 50 si 100 (multiplu de 5).
--
-- Formula: 50 + 5 * FLOOR(RAND()*11) => unul din 50,55,60,...,100.
-- Nota: valorile fiind aleatoare, o re-rulare a UPDATE-ului le schimba.

ALTER TABLE rute
    ADD COLUMN viteza INT NOT NULL DEFAULT 0 AFTER Durata_min;

UPDATE rute SET viteza = 50 + 5 * FLOOR(RAND() * 11);
