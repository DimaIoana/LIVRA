-- Date (nu schema): scade costul de achizitie al produselor cu 20%.
--
-- `Cost_Unitar` era intre 50% si 85% din pretul unitar (vezi migrarea 013);
-- dupa aceasta scadere ajunge intre 40% si 68%, deci marja de profit creste.
-- Pretul de vanzare (`Unit_Cost`) nu se atinge.
--
-- ATENTIE: nu e idempotent - rulat de doua ori scade de doua ori. Se ruleaza o
-- singura data. Revenirea aproximativa: Cost_Unitar = ROUND(Cost_Unitar / 0.80, 2)
-- (pot ramane diferente de un ban din rotunjire).
--
-- Rapoartele din Business dashboard citesc costul curent din `inventory`, deci
-- si zilele deja livrate se recalculeaza cu noul cost.

UPDATE inventory
SET Cost_Unitar = ROUND(Cost_Unitar * 0.80, 2)
WHERE Cost_Unitar IS NOT NULL;
