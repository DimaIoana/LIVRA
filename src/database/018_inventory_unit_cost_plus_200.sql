-- Date (nu schema): creste pretul de vanzare al produselor cu 200%.
--
-- "Cu 200%" = pretul se tripleaza (x3.00), la fel cum migrarea 017 a fost "cu
-- 40%" = x1.40. `Unit_Cost` e pretul afisat clientului in magazin; costul de
-- achizitie (`Cost_Unitar`) nu se atinge, deci marja creste mult: dupa 017
-- costul era 23%-39% din pret, acum ajunge la 8%-13%.
--
-- Ex: Laptop Pro 840 -> 2.520 lei, Monitor 27 350 -> 1.050 lei,
--     USB-C Cable 7 -> 21 lei, pc ioana 1.680 -> 5.040 lei.
--
-- ATENTIE: nu e idempotent - rulat de doua ori creste de doua ori. Se ruleaza o
-- singura data. Revenirea aproximativa: Unit_Cost = ROUND(Unit_Cost / 3.00, 2).
--
-- Pretul nou se aplica doar comenzilor viitoare: `comenzi_produse` isi pastreaza
-- `Pret_unitar` si `Subtotal` de la momentul comenzii, iar expedierile isi
-- pastreaza `Valoare_expediere`. Rapoartele de vanzari deja livrate nu se schimba.

UPDATE inventory
SET Unit_Cost = ROUND(Unit_Cost * 3.00, 2)
WHERE Unit_Cost IS NOT NULL;
