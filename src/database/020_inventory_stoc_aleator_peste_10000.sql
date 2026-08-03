-- Date (nu schema): pune stocuri aleatoare, toate peste 10.000 de bucati.
--
-- `Stock_Level` primeste o valoare intre 10.001 si 50.000 pe fiecare rand din
-- `inventory` (adica pe fiecare luna a fiecarui produs, in fiecare depozit).
--
-- Efecte de stiut:
--   - niciun produs nu mai e sub pragul de reaprovizionare (`Reorder_Point`
--     ramane la 10-100), deci cifra "sub prag" din back office devine 0;
--   - toate depozitele au acum produsul pe stoc, deci algoritmul de optimizare
--     are mereu toti candidatii si va alege ruta cea mai rapida - procentul de
--     curse "pe ruta optima" din laborator va creste pentru expedierile noi;
--   - magazinul nu mai respinge comenzi pentru stoc insuficient.
--
-- ATENTIE: foloseste RAND(), deci la fiecare rulare ies alte cifre (dar mereu
-- peste 10.000). Stocurile de dinainte se pierd - erau oricum date de test.

UPDATE inventory
SET Stock_Level = FLOOR(10001 + RAND() * 39999);
