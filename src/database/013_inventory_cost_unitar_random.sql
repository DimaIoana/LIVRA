-- Date (nu schema): completeaza `Cost_Unitar` cu o valoare aleatoare pentru
-- inregistrarile care nu au inca un cost de achizitie.
--
-- Costul iese intre 50% si 85% din pretul unitar (`Unit_Cost`), deci mereu mai
-- mic decat pretul, cu marja de profit intre 15% si 50%. Rotunjit la 2 zecimale.

UPDATE inventory
SET Cost_Unitar = ROUND(Unit_Cost * (0.50 + RAND() * 0.35), 2)
WHERE Unit_Cost IS NOT NULL
  AND Cost_Unitar IS NULL;
