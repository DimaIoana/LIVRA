-- Date (nu schema): a doua scadere de 20% a costului de achizitie, peste cea
-- din migrarea 015 (cerere explicita a userului).
--
-- Cumulat cu 015, costul ajunge la 64% din cel initial (0.80 x 0.80), adica o
-- scadere totala de 36%. Pretul de vanzare (`Unit_Cost`) ramane neschimbat, deci
-- costul iese acum intre ~32% si ~55% din pret.
--
-- ATENTIE: nu e idempotent - rulat de doua ori scade de doua ori. Se ruleaza o
-- singura data. Revenirea aproximativa: Cost_Unitar = ROUND(Cost_Unitar / 0.80, 2)
-- (pot ramane diferente de un ban din rotunjire).

UPDATE inventory
SET Cost_Unitar = ROUND(Cost_Unitar * 0.80, 2)
WHERE Cost_Unitar IS NOT NULL;
