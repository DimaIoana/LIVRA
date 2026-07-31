-- Date (nu schema): creste pretul de vanzare al produselor cu 40%.
--
-- `Unit_Cost` e pretul afisat clientului in magazin. Costul de achizitie
-- (`Cost_Unitar`) nu se atinge, deci marja creste: dupa migrarile 015 si 016
-- costul era 32%-55% din pret, acum ajunge la 23%-39%.
--
-- ATENTIE: nu e idempotent - rulat de doua ori creste de doua ori. Se ruleaza o
-- singura data. Revenirea aproximativa: Unit_Cost = ROUND(Unit_Cost / 1.40, 2).
--
-- Pretul nou se aplica doar comenzilor viitoare: `comenzi_produse` isi pastreaza
-- `Pret_unitar` si `Subtotal` de la momentul comenzii, iar expedierile isi
-- pastreaza `Valoare_expediere`. Rapoartele de vanzari deja livrate nu se schimba.

UPDATE inventory
SET Unit_Cost = ROUND(Unit_Cost * 1.40, 2)
WHERE Unit_Cost IS NOT NULL;
