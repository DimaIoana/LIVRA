-- 026_catalog_produse.sql
--
-- Separa catalogul de produse de istoricul de stoc.
--
-- Pana acum tabela `inventory` tinea si produsul (nume, categorie, pret de
-- vanzare, poza) si stocul lunar, deci identitatea produsului era copiata pe
-- fiecare luna: 55 de randuri pentru 19 produse. Puteai schimba numele intr-o
-- luna si nu in alta, iar catalogul din magazin trebuia ghicit din "cel mai
-- recent rand al fiecarui produs".
--
-- De aici incolo:
--   `produse`   = catalogul: cate un rand pe produs, cu Product_ID unic;
--   `inventory` = stocul:    cate un rand pe produs si luna, legat prin Product_ID.
--
-- Legatura e cheie straina reala (inventory.Product_ID = produse.Product_ID),
-- deci nu mai poate exista o linie de stoc pentru un produs care nu e in catalog,
-- si nici doua produse cu acelasi cod.
--
-- Ce ramane pe stoc, si de ce:
--   Stock_Level, Reorder_Point, Monthly_Sales, `Date`, depozit - tin de luna si
--   de locul unde sta marfa, nu de produs;
--   Cost_Unitar - costul de achizitie, care chiar difera de la o luna/depozit la
--   alta (18 din 19 produse au valori diferite), deci e istoric real.
-- Ce se muta in catalog: Product_Name, Category, Unit_Cost (pretul de vanzare,
--   identic pe toate lunile fiecarui produs) si poza.
--
-- Backup inainte de rulare:
--   src/database/backups/sameday_company_2026-08-10_inainte_de_026.sql

-- --------------------------------------------------------------------------
-- 1. Catalogul
-- --------------------------------------------------------------------------
-- ProdusID (auto_increment) e cheia tehnica, cea cu care lucreaza pagina CRUD
-- generica (_crud_page.php trateaza cheia primara ca numar).
-- Product_ID e codul de business, PRD-0001: unic, si pe el se face legatura.
-- Colatia e utf8mb4_general_ci, aceeasi cu comenzi_produse.Product_ID, ca sa
-- se poata face join fara conversii.

CREATE TABLE IF NOT EXISTS `produse` (
  `ProdusID`     INT(11)       NOT NULL AUTO_INCREMENT,
  `Product_ID`   VARCHAR(10)   NOT NULL,
  `Product_Name` VARCHAR(100)  NOT NULL,
  `Category`     VARCHAR(50)   NOT NULL,
  `Unit_Cost`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `poze`         VARCHAR(200)  DEFAULT NULL,
  PRIMARY KEY (`ProdusID`),
  UNIQUE KEY `uq_produse_cod` (`Product_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------------------------
-- 2. Umplerea catalogului din stocul existent
-- --------------------------------------------------------------------------
-- Se ia randul cel mai recent al fiecarui produs. Ordinea (`Date` IS NULL,
-- `Date` DESC, InventoryID DESC) e aceeasi folosita de magazin: lunile
-- completate inaintea celor NULL, cea mai recenta prima. Asa produsele cu luna
-- necompletata nu se pierd.
--
-- Poza se ia separat, de pe cel mai recent rand care chiar are poza: in datele
-- de acum fiecare luna a primit alta poza, iar luna curenta poate fi fara.

INSERT INTO `produse` (`Product_ID`, `Product_Name`, `Category`, `Unit_Cost`, `poze`)
SELECT
    i.`Product_ID`,
    COALESCE(NULLIF(TRIM(i.`Product_Name`), ''), i.`Product_ID`),
    COALESCE(NULLIF(TRIM(i.`Category`), ''), 'Nespecificat'),
    COALESCE(i.`Unit_Cost`, 0.00),
    (SELECT ip.`poze`
       FROM `inventory` ip
      WHERE ip.`Product_ID` = i.`Product_ID`
        AND ip.`poze` IS NOT NULL
        AND ip.`poze` <> ''
      ORDER BY ip.`Date` IS NULL, ip.`Date` DESC, ip.`InventoryID` DESC
      LIMIT 1)
FROM `inventory` i
WHERE i.`Product_ID` IS NOT NULL
  AND i.`Product_ID` <> ''
  AND i.`InventoryID` = (
      SELECT i2.`InventoryID`
        FROM `inventory` i2
       WHERE i2.`Product_ID` = i.`Product_ID`
       ORDER BY i2.`Date` IS NULL, i2.`Date` DESC, i2.`InventoryID` DESC
       LIMIT 1
  );

-- --------------------------------------------------------------------------
-- 3. Legatura stoc -> catalog
-- --------------------------------------------------------------------------
-- Coloana era nullable si pe utf8_general_ci; cheia straina cere aceeasi
-- colatie ca in `produse` si o valoare mereu prezenta.

ALTER TABLE `inventory`
  MODIFY `Product_ID` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

ALTER TABLE `inventory`
  ADD CONSTRAINT `fk_inventory_produs`
  FOREIGN KEY (`Product_ID`) REFERENCES `produse` (`Product_ID`)
  ON UPDATE CASCADE
  ON DELETE RESTRICT;

-- --------------------------------------------------------------------------
-- 4. Scoaterea coloanelor mutate
-- --------------------------------------------------------------------------
-- Datele lor traiesc acum in `produse`. Lasate aici, ar putea ajunge sa spuna
-- altceva decat catalogul, adica exact problema pe care o rezolva migrarea.

ALTER TABLE `inventory`
  DROP COLUMN `Product_Name`,
  DROP COLUMN `Category`,
  DROP COLUMN `Unit_Cost`,
  DROP COLUMN `poze`;
