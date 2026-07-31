-- Date (nu schema): rescrie comenzile deja existente la preturile curente.
--
-- In mod normal o comanda isi pastreaza pretul de la momentul plasarii
-- (`comenzi_produse.Pret_unitar` / `Subtotal`), iar expedierea valoarea ei
-- (`expedieri.Valoare_expediere`) - de aia migrarile de pret (017, 018) nu
-- schimbau nimic in rapoartele pe zile deja livrate.
--
-- Migrarea asta face exact opusul, la cerere explicita: aduce tot istoricul la
-- pretul de vanzare curent din `inventory.Unit_Cost` (dupa 017 si 018). Dupa ea,
-- vanzarile din Business dashboard se recalculeaza pentru toate zilele.
--
-- ATENTIE: se pierde pretul cu care s-a vandut efectiv fiecare comanda. Nu se
-- poate reveni din date - doar din backup. Rulata de doua ori da acelasi
-- rezultat (idempotenta), fiindca scrie mereu pretul curent, nu unul inmultit.
--
-- Pretul unui produs se ia de pe cel mai recent rand al lui din `inventory`,
-- aceeasi regula ca in magazin (`MagazinRepository::currentFrom()`).

-- 1. Liniile de comanda: pretul unitar si subtotalul.
UPDATE comenzi_produse cp
JOIN (
    SELECT i.Product_ID, MAX(i.Unit_Cost) AS Unit_Cost
    FROM inventory i
    WHERE i.`Date` = (
        SELECT MAX(i2.`Date`) FROM inventory i2 WHERE i2.Product_ID = i.Product_ID
    )
    GROUP BY i.Product_ID
) pret ON pret.Product_ID = cp.Product_ID
SET cp.Pret_unitar = pret.Unit_Cost,
    cp.Subtotal = ROUND(pret.Unit_Cost * cp.Cantitate, 2);

-- 2. Totalul comenzii = suma liniilor ei.
UPDATE comenzi c
SET c.Total = COALESCE(
    (SELECT ROUND(SUM(cp.Subtotal), 2) FROM comenzi_produse cp WHERE cp.ComandaID = c.ComandaID),
    0
);

-- 3. Valoarea expedierii = subtotalul liniei pe care o duce.
UPDATE expedieri e
JOIN comenzi_produse cp ON cp.LinieID = e.LinieID
SET e.Valoare_expediere = cp.Subtotal;
