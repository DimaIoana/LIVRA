-- Costul de carburant al rutei pentru fiecare expediere (lei).
-- Se calculeaza din: distanta rutei (km) x consumul mediu (vezi
-- OptimizareRuteService::CONSUM_L_100KM, momentan 12 L/100km) x pretul motorinei
-- standard luat din API-ul PretCarburant.ro (media nationala).
-- Se completeaza automat cand se creeaza expedierea din optimizarea de rute.

ALTER TABLE expedieri
    ADD COLUMN cost_carburant DECIMAL(10,2) NULL AFTER Valoare_expediere;
