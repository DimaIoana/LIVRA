<?php

require_once __DIR__ . '/ExpediereRepository.php';
require_once __DIR__ . '/OptimizareRuteService.php';

/**
 * Date agregate pentru dashboard-ul de business (doar citire, fara CRUD, deci
 * nu extinde BaseRepository).
 *
 * Toate rapoartele care tin de bani se uita numai la expedierile livrate
 * (`Status_expediere = 'Livrat'`), grupate dupa ziua livrarii efective: doar
 * atunci vanzarea e incasata si cheltuiala e consumata.
 */
class BusinessRepository
{
    /** Statusul expedierilor care intra in rapoartele financiare. */
    const STATUS_LIVRAT = 'Livrat';

    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Vanzarile pe zi: suma valorilor expedierilor livrate in ziua respectiva.
     *
     * @return array randuri ['zi', 'total', 'expedieri'], crescator dupa zi
     */
    public function vanzariPeZi()
    {
        return $this->pdo->query(
            'SELECT DATE(e.Data_livrare_efectiva) AS zi,
                    SUM(e.Valoare_expediere) AS total,
                    COUNT(*) AS expedieri
             FROM expedieri e
             WHERE e.Status_expediere = ' . $this->pdo->quote(self::STATUS_LIVRAT) . '
               AND e.Data_livrare_efectiva IS NOT NULL
             GROUP BY zi
             ORDER BY zi'
        )->fetchAll();
    }

    /**
     * Cheltuielile pe zi, despartite pe cele doua feluri:
     *   - produse   = cantitatea din linia de comanda x costul de achizitie
     *                 (`inventory.Cost_Unitar`);
     *   - carburant = `expedieri.cost_carburant`, calculat la expediere.
     *
     * O expediere are cel mult o linie de comanda (`LinieID`), deci JOIN-ul nu
     * multiplica randurile. Costul unitar se ia de la depozitul de plecare al
     * rutei (cel mai recent rand de stoc de acolo), cu revenire pe cel mai
     * recent rand al produsului daca depozitul acela n-are stoc inregistrat.
     *
     * @return array randuri ['zi', 'cost_produse', 'cost_carburant'], crescator
     */
    public function cheltuieliPeZi()
    {
        $depozit = $this->cazDepozit('r');

        return $this->pdo->query(
            'SELECT zi, SUM(cost_produse) AS cost_produse, SUM(cost_carburant) AS cost_carburant
             FROM (
                SELECT DATE(e.Data_livrare_efectiva) AS zi,
                       COALESCE(cp.Cantitate, 0) * COALESCE(
                           (SELECT i.Cost_Unitar FROM inventory i
                             WHERE i.Product_ID = cp.Product_ID AND i.depozit = ' . $depozit . '
                             ORDER BY i.`Date` DESC LIMIT 1),
                           (SELECT i2.Cost_Unitar FROM inventory i2
                             WHERE i2.Product_ID = cp.Product_ID
                             ORDER BY i2.`Date` DESC LIMIT 1),
                           0
                       ) AS cost_produse,
                       COALESCE(e.cost_carburant, 0) AS cost_carburant
                FROM expedieri e
                JOIN rute r ON r.RutaID = e.RutaID
                LEFT JOIN comenzi_produse cp ON cp.LinieID = e.LinieID
                WHERE e.Status_expediere = ' . $this->pdo->quote(self::STATUS_LIVRAT) . '
                  AND e.Data_livrare_efectiva IS NOT NULL
             ) t
             GROUP BY zi
             ORDER BY zi'
        )->fetchAll();
    }

    /**
     * Numarul de comenzi pe orasul clientului care le-a plasat.
     *
     * @return array randuri ['oras', 'comenzi'], descrescator dupa numar
     */
    public function comenziPeOras()
    {
        return $this->pdo->query(
            'SELECT c.Oras AS oras, COUNT(*) AS comenzi
             FROM comenzi o
             JOIN clienti c ON c.ClientID = o.ClientID
             GROUP BY c.Oras
             ORDER BY comenzi DESC, c.Oras'
        )->fetchAll();
    }

    /**
     * Activitatea fiecarui sofer, insumata pe toate expedierile lui: cati km si
     * cate minute de condus au avut rutele si cat carburant s-a consumat.
     *
     * Spre deosebire de rapoartele de bani, aici intra si expedierile nelivrate:
     * drumul e facut si carburantul e ars din clipa in care soferul a plecat.
     * Soferii fara expedieri raman in lista, cu zero (de aici LEFT JOIN).
     *
     * Litrii nu se tin in baza de date, se calculeaza din km cu acelasi consum
     * mediu folosit si la costul expedierii (`OptimizareRuteService`).
     *
     * @return array randuri ['Nume', 'Oras_baza', 'expedieri', 'km', 'minute',
     *               'cost_carburant', 'litri'], descrescator dupa km
     */
    public function activitateSoferi()
    {
        $randuri = $this->pdo->query(
            'SELECT s.Nume, s.Oras_baza,
                    COUNT(e.ExpediereID) AS expedieri,
                    COALESCE(SUM(r.Distanta_km), 0) AS km,
                    COALESCE(SUM(r.Durata_min), 0) AS minute,
                    COALESCE(SUM(e.cost_carburant), 0) AS cost_carburant
             FROM soferi s
             LEFT JOIN expedieri e ON e.SoferID = s.SoferID
             LEFT JOIN rute r ON r.RutaID = e.RutaID
             GROUP BY s.SoferID, s.Nume, s.Oras_baza
             ORDER BY km DESC, s.Nume'
        )->fetchAll();

        foreach ($randuri as $i => $r) {
            $randuri[$i]['litri'] = (float) $r['km'] / 100 * OptimizareRuteService::CONSUM_L_100KM;
        }

        return $randuri;
    }

    /**
     * Cat de folosit e fiecare traseu: cate expedieri au mers pe el si cati km
     * s-au facut in total pe traseul acela.
     *
     * Raman in lista si traseele pe care n-a plecat nimeni niciodata (de aici
     * LEFT JOIN): tocmai ele sunt raspunsul la "care e cel mai putin folosit".
     * Se numara toate expedierile, nu doar cele livrate - drumul e facut si
     * daca coletul s-a intors.
     *
     * @return array randuri ['RutaID', 'Oras_origine', 'Oras_destinatie',
     *               'Distanta_km', 'Durata_min', 'tip_strada', 'curse', 'km'],
     *               descrescator dupa numarul de curse
     */
    public function utilizareTrasee()
    {
        return $this->pdo->query(
            'SELECT r.RutaID, r.Oras_origine, r.Oras_destinatie,
                    r.Distanta_km, r.Durata_min, r.tip_strada,
                    COUNT(e.ExpediereID) AS curse,
                    COUNT(e.ExpediereID) * r.Distanta_km AS km
             FROM rute r
             LEFT JOIN expedieri e ON e.RutaID = r.RutaID
             GROUP BY r.RutaID, r.Oras_origine, r.Oras_destinatie,
                      r.Distanta_km, r.Durata_min, r.tip_strada
             ORDER BY curse DESC, km DESC, r.Oras_origine, r.Oras_destinatie'
        )->fetchAll();
    }

    /**
     * Cursele soferilor, una pe rand: cine a condus, pe ce traseu si cati km a
     * avut traseul. Km-ii sunt ai rutei, deci o cursa dus fara intoarcere.
     *
     * @return array randuri ['Nume', 'awb', 'Oras_origine', 'Oras_destinatie',
     *               'Distanta_km', 'Durata_min', 'tip_strada', 'Data_expediere',
     *               'Status_expediere'], grupate pe sofer, cronologic
     */
    public function curseSoferi()
    {
        return $this->pdo->query(
            'SELECT s.Nume, e.awb,
                    r.Oras_origine, r.Oras_destinatie,
                    r.Distanta_km, r.Durata_min, r.tip_strada,
                    e.Data_expediere, e.Status_expediere
             FROM expedieri e
             JOIN soferi s ON s.SoferID = e.SoferID
             JOIN rute r ON r.RutaID = e.RutaID
             ORDER BY s.Nume, e.Data_expediere, e.ExpediereID'
        )->fetchAll();
    }

    /**
     * Profitul adus de fiecare sofer pe traseele lui: cat a vandut minus cat a
     * costat marfa si carburantul.
     *
     * Fiind un raport de bani, intra numai expedierile livrate, ca peste tot in
     * dashboard. Costul produselor se calculeaza la fel ca in `cheltuieliPeZi()`.
     * Soferii fara livrari raman in lista, cu zero (de aici LEFT JOIN), altfel
     * ar disparea tocmai cei care n-au adus nimic.
     *
     * @return array randuri ['Nume', 'Oras_baza', 'livrari', 'vanzari',
     *               'cost_produse', 'cost_carburant', 'cost', 'profit'],
     *               descrescator dupa profit
     */
    public function profitSoferi()
    {
        $depozit = $this->cazDepozit('r');

        $randuri = $this->pdo->query(
            'SELECT s.Nume, s.Oras_baza,
                    COUNT(e.ExpediereID) AS livrari,
                    COALESCE(SUM(e.Valoare_expediere), 0) AS vanzari,
                    COALESCE(SUM(
                        COALESCE(cp.Cantitate, 0) * COALESCE(
                            (SELECT i.Cost_Unitar FROM inventory i
                              WHERE i.Product_ID = cp.Product_ID AND i.depozit = ' . $depozit . '
                              ORDER BY i.`Date` DESC LIMIT 1),
                            (SELECT i2.Cost_Unitar FROM inventory i2
                              WHERE i2.Product_ID = cp.Product_ID
                              ORDER BY i2.`Date` DESC LIMIT 1),
                            0
                        )
                    ), 0) AS cost_produse,
                    COALESCE(SUM(e.cost_carburant), 0) AS cost_carburant
             FROM soferi s
             LEFT JOIN expedieri e
                    ON e.SoferID = s.SoferID
                   AND e.Status_expediere = ' . $this->pdo->quote(self::STATUS_LIVRAT) . '
                   AND e.Data_livrare_efectiva IS NOT NULL
             LEFT JOIN rute r ON r.RutaID = e.RutaID
             LEFT JOIN comenzi_produse cp ON cp.LinieID = e.LinieID
             GROUP BY s.SoferID, s.Nume, s.Oras_baza'
        )->fetchAll();

        foreach ($randuri as $i => $r) {
            $cost = (float) $r['cost_produse'] + (float) $r['cost_carburant'];
            $randuri[$i]['cost'] = $cost;
            $randuri[$i]['profit'] = (float) $r['vanzari'] - $cost;
        }

        // Ordonarea dupa profit nu se poate face in SQL, fiindca profitul se
        // compune abia aici; sortam pe loc, descrescator, cu numele ca departajare.
        usort($randuri, function ($a, $b) {
            return $a['profit'] === $b['profit']
                ? strcmp($a['Nume'], $b['Nume'])
                : ($b['profit'] <=> $a['profit']);
        });

        return $randuri;
    }

    /**
     * Expresia SQL care traduce orasul de plecare al rutei in codul de depozit.
     * Se construieste din aceeasi harta folosita la scaderea stocului, ca sa nu
     * existe doua liste de depozite in proiect.
     */
    private function cazDepozit($aliasRuta)
    {
        $cazuri = '';
        foreach (ExpediereRepository::DEPOZIT_COD as $oras => $cod) {
            $cazuri .= ' WHEN ' . $this->pdo->quote($oras) . ' THEN ' . (int) $cod;
        }

        return 'CASE ' . $aliasRuta . '.Oras_origine' . $cazuri . ' END';
    }
}
