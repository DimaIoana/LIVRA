-- Seed (date, nu schema): reconstruieste rutele pe modelul depozit -> oras client.
--
-- Depozite: Arad, Braila, Pitesti. Orase client: cele 6 din tabela clienti.
-- Fiecare oras client primeste cate o ruta din TOATE cele 3 depozite => 18 rute,
-- fiecare cu distanta (km) si timp de condus aproximativ (Durata_min, in minute).
--
-- Expedierile existente se muta pe ruta din depozitul cel mai apropiat de orasul
-- clientului lor, apoi rutele vechi (RutaID <= 10) se sterg. Ordinea (creeaza ->
-- remapeaza -> sterge) e obligatorie: expedieri.RutaID are FK RESTRICT si NOT NULL.
--
-- Necesita coloana Durata_min (vezi 005_rute_durata.sql). Reluabil doar dupa un
-- reset - a doua rulare ar dubla rutele.
--
-- Distantele (km) vin din tabelul tools/utilities/distanta_orase.xlsx, iar
-- timpul de condus (Durata_min, in minute) din tools/utilities/timp_orase.xlsx.
-- Ambele includ Braila ca linie proprie. Valorile sunt cele reale din fisiere,
-- nu estimari.

START TRANSACTION;

-- 1) Rutele noi: depozit -> oras client, km (distanta_orase.xlsx), timp (timp_orase.xlsx).
INSERT INTO rute (Oras_origine, Oras_destinatie, Distanta_km, Durata_min) VALUES
  ('Arad',    'Brasov',      418, 463),
  ('Arad',    'Bucuresti',   547, 592),
  ('Arad',    'Cluj-Napoca', 268, 268),
  ('Arad',    'Constanta',   813, 858),
  ('Arad',    'Iasi',        755, 800),
  ('Arad',    'Timisoara',    52,  52),
  ('Braila',  'Brasov',      294, 294),
  ('Braila',  'Bucuresti',   244, 244),
  ('Braila',  'Cluj-Napoca', 568, 613),
  ('Braila',  'Constanta',   205, 205),
  ('Braila',  'Iasi',        252, 252),
  ('Braila',  'Timisoara',   717, 757),
  ('Pitesti', 'Brasov',      135, 135),
  ('Pitesti', 'Bucuresti',   114, 114),
  ('Pitesti', 'Cluj-Napoca', 326, 326),
  ('Pitesti', 'Constanta',   380, 410),
  ('Pitesti', 'Iasi',        446, 476),
  ('Pitesti', 'Timisoara',   467, 502);

-- 2) Muta expedierile pe ruta din depozitul cel mai apropiat de orasul clientului.
--    Brasov,Bucuresti -> Pitesti; Cluj-Napoca,Timisoara -> Arad; Constanta,Iasi -> Braila.
UPDATE expedieri e JOIN clienti c ON c.ClientID = e.ClientID
  JOIN rute r ON r.Oras_origine = 'Pitesti' AND r.Oras_destinatie = 'Brasov'
  SET e.RutaID = r.RutaID WHERE c.Oras = 'Brasov';
UPDATE expedieri e JOIN clienti c ON c.ClientID = e.ClientID
  JOIN rute r ON r.Oras_origine = 'Pitesti' AND r.Oras_destinatie = 'Bucuresti'
  SET e.RutaID = r.RutaID WHERE c.Oras = 'Bucuresti';
UPDATE expedieri e JOIN clienti c ON c.ClientID = e.ClientID
  JOIN rute r ON r.Oras_origine = 'Arad' AND r.Oras_destinatie = 'Cluj-Napoca'
  SET e.RutaID = r.RutaID WHERE c.Oras = 'Cluj-Napoca';
UPDATE expedieri e JOIN clienti c ON c.ClientID = e.ClientID
  JOIN rute r ON r.Oras_origine = 'Braila' AND r.Oras_destinatie = 'Constanta'
  SET e.RutaID = r.RutaID WHERE c.Oras = 'Constanta';
UPDATE expedieri e JOIN clienti c ON c.ClientID = e.ClientID
  JOIN rute r ON r.Oras_origine = 'Braila' AND r.Oras_destinatie = 'Iasi'
  SET e.RutaID = r.RutaID WHERE c.Oras = 'Iasi';
UPDATE expedieri e JOIN clienti c ON c.ClientID = e.ClientID
  JOIN rute r ON r.Oras_origine = 'Arad' AND r.Oras_destinatie = 'Timisoara'
  SET e.RutaID = r.RutaID WHERE c.Oras = 'Timisoara';

-- 3) Sterge rutele vechi (acum nereferite de nicio expediere).
DELETE FROM rute WHERE RutaID <= 10;

COMMIT;
