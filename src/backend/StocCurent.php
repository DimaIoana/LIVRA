<?php

/**
 * Definitia unica a "stocului de acum" al unui produs.
 *
 * `inventory` e un istoric: cate un rand pe produs, luna si depozit. Stocul de
 * acum al unui produs nu e un singur rand de acolo, ci suma peste depozite a
 * ultimului rand din fiecare depozit. Fara regula asta, un produs care sta in
 * trei depozite arata doar cat e intr-unul (Office Chair: 32.307 in loc de
 * 123.218), si atunci catalogul si magazinul spun numere diferite.
 *
 * De aceea regula sta intr-un singur loc, iar catalogul si magazinul o folosesc
 * pe aceeasi: stocul nu se copiaza nicaieri, se calculeaza mereu de aici.
 */
class StocCurent
{
    /** Codul de depozit din `inventory`.`depozit` -> orasul. */
    const DEPOZITE = [1 => 'Arad', 2 => 'Braila', 3 => 'Pitesti'];

    /**
     * Sub-interogare cu stocul de acum pe produs: total, defalcat pe depozit si
     * in cate depozite exista stoc inregistrat. Se foloseste ca tabela derivata:
     *
     *   LEFT JOIN (' . StocCurent::subinterogare() . ') s
     *          ON s.Product_ID = produse.Product_ID
     *
     * LEFT JOIN, nu JOIN: un produs din catalog care n-a fost niciodata
     * inventariat trebuie sa apara cu 0, nu sa dispara.
     *
     * Randul curent al unui depozit se alege pe rand, nu pe luna: lunile
     * completate inaintea celor NULL (coloana e nullable), cea mai recenta
     * prima, iar la egalitate cel mai nou InventoryID. Deci exact un rand per
     * produs si depozit.
     */
    public static function subinterogare()
    {
        return 'SELECT i.Product_ID,
                       SUM(i.Stock_Level) AS Stoc_total,
                       SUM(CASE WHEN i.depozit = 1 THEN i.Stock_Level ELSE 0 END) AS Stoc_1,
                       SUM(CASE WHEN i.depozit = 2 THEN i.Stock_Level ELSE 0 END) AS Stoc_2,
                       SUM(CASE WHEN i.depozit = 3 THEN i.Stock_Level ELSE 0 END) AS Stoc_3,
                       COUNT(*) AS Depozite_cu_stoc
                  FROM inventory i
                 WHERE i.InventoryID = (
                           SELECT i2.InventoryID FROM inventory i2
                            WHERE i2.Product_ID = i.Product_ID
                              AND i2.depozit = i.depozit
                            ORDER BY i2.`Date` IS NULL, i2.`Date` DESC, i2.InventoryID DESC
                            LIMIT 1
                       )
                 GROUP BY i.Product_ID';
    }
}
