-- Adauga timpul aproximativ de condus (in minute) pentru fiecare ruta.
-- Se afiseaza formatat (ex: "6 h 30 min") in src/frontend/rute.php.

ALTER TABLE rute
    ADD COLUMN Durata_min INT NOT NULL DEFAULT 0 AFTER Distanta_km;
