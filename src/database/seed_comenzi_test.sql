-- SEED (date de test, nu schema): comenzi pentru clienti si produse diferite,
-- ca sa avem cu ce testa fluxul magazin -> comenzi -> expediere -> livrare.
--
-- Preturile si numele produselor se iau din inventory (randul cel mai recent al
-- fiecarui produs), deci raman corelate cu catalogul chiar daca se schimba pretul.
-- Totalul comenzii se recalculeaza la final din suma liniilor.
--
-- Atentie: fisierul NU e idempotent - la fiecare rulare adauga alt set de comenzi.
-- Rulare: mysql -u root sameday_company < src/database/seed_comenzi_test.sql

-- De unde incep comenzile adaugate acum (pentru recalcularea totalurilor la final).
SET @prima_comanda = (SELECT COALESCE(MAX(ComandaID), 0) + 1 FROM comenzi);

-- Produsele curente: pret si nume, un singur rand per produs.
CREATE TEMPORARY TABLE tmp_produse_curente AS
SELECT i.Product_ID,
       MAX(i.Product_Name) AS Product_Name,
       MAX(i.Unit_Cost) AS Unit_Cost
FROM inventory i
WHERE i.`Date` = (SELECT MAX(i2.`Date`) FROM inventory i2 WHERE i2.Product_ID = i.Product_ID)
GROUP BY i.Product_ID;


-- 1. Ana Popescu (Bucuresti) - laptop + cabluri
INSERT INTO comenzi (ClientID, Data_comanda, Status, Total, Observatii) VALUES
    (1, DATE(NOW()) - INTERVAL 9 DAY + INTERVAL '9:20' HOUR_MINUTE, 'Noua', 0, 'Livrare la birou, dupa ora 10.');
SET @c = LAST_INSERT_ID();
INSERT INTO comenzi_produse (ComandaID, Product_ID, Product_Name, Pret_unitar, Cantitate, Subtotal)
SELECT @c, p.Product_ID, p.Product_Name, p.Unit_Cost, x.qty, ROUND(p.Unit_Cost * x.qty, 2)
FROM tmp_produse_curente p
JOIN (SELECT 'P001' AS cod, 1 AS qty UNION ALL SELECT 'P005', 2) x ON x.cod = p.Product_ID;

-- 2. Mihai Ionescu (Cluj-Napoca) - o singura linie
INSERT INTO comenzi (ClientID, Data_comanda, Status, Total, Observatii) VALUES
    (2, DATE(NOW()) - INTERVAL 8 DAY + INTERVAL '14:05' HOUR_MINUTE, 'Noua', 0, 'Sunati inainte de livrare.');
SET @c = LAST_INSERT_ID();
INSERT INTO comenzi_produse (ComandaID, Product_ID, Product_Name, Pret_unitar, Cantitate, Subtotal)
SELECT @c, p.Product_ID, p.Product_Name, p.Unit_Cost, x.qty, ROUND(p.Unit_Cost * x.qty, 2)
FROM tmp_produse_curente p
JOIN (SELECT 'P002' AS cod, 3 AS qty) x ON x.cod = p.Product_ID;

-- 3. TechSol SRL (Bucuresti) - comanda de firma
INSERT INTO comenzi (ClientID, Data_comanda, Status, Total, Observatii) VALUES
    (3, DATE(NOW()) - INTERVAL 7 DAY + INTERVAL '11:40' HOUR_MINUTE, 'In procesare', 0, 'Factura pe firma, cod fiscal in contract.');
SET @c = LAST_INSERT_ID();
INSERT INTO comenzi_produse (ComandaID, Product_ID, Product_Name, Pret_unitar, Cantitate, Subtotal)
SELECT @c, p.Product_ID, p.Product_Name, p.Unit_Cost, x.qty, ROUND(p.Unit_Cost * x.qty, 2)
FROM tmp_produse_curente p
JOIN (SELECT 'P006' AS cod, 2 AS qty UNION ALL SELECT 'P005', 4) x ON x.cod = p.Product_ID;

-- 4. Elena Dumitru (Timisoara) - mobilier + lampa, fara observatii
INSERT INTO comenzi (ClientID, Data_comanda, Status, Total, Observatii) VALUES
    (4, DATE(NOW()) - INTERVAL 6 DAY + INTERVAL '16:15' HOUR_MINUTE, 'Noua', 0, NULL);
SET @c = LAST_INSERT_ID();
INSERT INTO comenzi_produse (ComandaID, Product_ID, Product_Name, Pret_unitar, Cantitate, Subtotal)
SELECT @c, p.Product_ID, p.Product_Name, p.Unit_Cost, x.qty, ROUND(p.Unit_Cost * x.qty, 2)
FROM tmp_produse_curente p
JOIN (SELECT 'P003' AS cod, 1 AS qty UNION ALL SELECT 'P004', 2) x ON x.cod = p.Product_ID;

-- 5. Globex Trading SRL (Cluj-Napoca) - comanda cu 3 linii
INSERT INTO comenzi (ClientID, Data_comanda, Status, Total, Observatii) VALUES
    (5, DATE(NOW()) - INTERVAL 5 DAY + INTERVAL '8:50' HOUR_MINUTE, 'Noua', 0, 'Comanda mare, atentie la ambalare.');
SET @c = LAST_INSERT_ID();
INSERT INTO comenzi_produse (ComandaID, Product_ID, Product_Name, Pret_unitar, Cantitate, Subtotal)
SELECT @c, p.Product_ID, p.Product_Name, p.Unit_Cost, x.qty, ROUND(p.Unit_Cost * x.qty, 2)
FROM tmp_produse_curente p
JOIN (SELECT 'P001' AS cod, 2 AS qty UNION ALL SELECT 'P006', 1 UNION ALL SELECT 'P002', 5) x ON x.cod = p.Product_ID;

-- 6. Andrei Stan (Iasi) - comanda deja trimisa
INSERT INTO comenzi (ClientID, Data_comanda, Status, Total, Observatii) VALUES
    (6, DATE(NOW()) - INTERVAL 4 DAY + INTERVAL '10:30' HOUR_MINUTE, 'Trimisa', 0, 'Cadou, fara factura in colet.');
SET @c = LAST_INSERT_ID();
INSERT INTO comenzi_produse (ComandaID, Product_ID, Product_Name, Pret_unitar, Cantitate, Subtotal)
SELECT @c, p.Product_ID, p.Product_Name, p.Unit_Cost, x.qty, ROUND(p.Unit_Cost * x.qty, 2)
FROM tmp_produse_curente p
JOIN (SELECT 'P004' AS cod, 1 AS qty) x ON x.cod = p.Product_ID;

-- 7. Carmen Vasilescu (Constanta) - produs scump / fragil
INSERT INTO comenzi (ClientID, Data_comanda, Status, Total, Observatii) VALUES
    (7, DATE(NOW()) - INTERVAL 3 DAY + INTERVAL '13:00' HOUR_MINUTE, 'Noua', 0, 'Produs fragil, marcati coletul.');
SET @c = LAST_INSERT_ID();
INSERT INTO comenzi_produse (ComandaID, Product_ID, Product_Name, Pret_unitar, Cantitate, Subtotal)
SELECT @c, p.Product_ID, p.Product_Name, p.Unit_Cost, x.qty, ROUND(p.Unit_Cost * x.qty, 2)
FROM tmp_produse_curente p
JOIN (SELECT 'P007' AS cod, 1 AS qty) x ON x.cod = p.Product_ID;

-- 8. MediPlus SRL (Timisoara) - cantitati mari, marfa ieftina
INSERT INTO comenzi (ClientID, Data_comanda, Status, Total, Observatii) VALUES
    (8, DATE(NOW()) - INTERVAL 2 DAY + INTERVAL '9:05' HOUR_MINUTE, 'In procesare', 0, 'Livrare in intervalul 09-12.');
SET @c = LAST_INSERT_ID();
INSERT INTO comenzi_produse (ComandaID, Product_ID, Product_Name, Pret_unitar, Cantitate, Subtotal)
SELECT @c, p.Product_ID, p.Product_Name, p.Unit_Cost, x.qty, ROUND(p.Unit_Cost * x.qty, 2)
FROM tmp_produse_curente p
JOIN (SELECT 'P005' AS cod, 10 AS qty UNION ALL SELECT 'P002', 2) x ON x.cod = p.Product_ID;

-- 9. Radu Marin (Brasov) - comanda anulata (caz de testat separat)
INSERT INTO comenzi (ClientID, Data_comanda, Status, Total, Observatii) VALUES
    (9, DATE(NOW()) - INTERVAL 2 DAY + INTERVAL '17:45' HOUR_MINUTE, 'Anulata', 0, 'Anulata de client, s-a razgandit.');
SET @c = LAST_INSERT_ID();
INSERT INTO comenzi_produse (ComandaID, Product_ID, Product_Name, Pret_unitar, Cantitate, Subtotal)
SELECT @c, p.Product_ID, p.Product_Name, p.Unit_Cost, x.qty, ROUND(p.Unit_Cost * x.qty, 2)
FROM tmp_produse_curente p
JOIN (SELECT 'P003' AS cod, 2 AS qty) x ON x.cod = p.Product_ID;

-- 10. FashionHub SRL (Iasi) - comanda de ieri, 3 linii
INSERT INTO comenzi (ClientID, Data_comanda, Status, Total, Observatii) VALUES
    (10, DATE(NOW()) - INTERVAL 1 DAY + INTERVAL '12:25' HOUR_MINUTE, 'Noua', 0, 'Se plateste ramburs la livrare.');
SET @c = LAST_INSERT_ID();
INSERT INTO comenzi_produse (ComandaID, Product_ID, Product_Name, Pret_unitar, Cantitate, Subtotal)
SELECT @c, p.Product_ID, p.Product_Name, p.Unit_Cost, x.qty, ROUND(p.Unit_Cost * x.qty, 2)
FROM tmp_produse_curente p
JOIN (SELECT 'P001' AS cod, 1 AS qty UNION ALL SELECT 'P003', 1 UNION ALL SELECT 'P004', 3) x ON x.cod = p.Product_ID;


-- Totalul fiecarei comenzi = suma subtotalurilor liniilor ei.
UPDATE comenzi c
SET c.Total = (SELECT COALESCE(SUM(cp.Subtotal), 0) FROM comenzi_produse cp WHERE cp.ComandaID = c.ComandaID)
WHERE c.ComandaID >= @prima_comanda;

DROP TEMPORARY TABLE tmp_produse_curente;
