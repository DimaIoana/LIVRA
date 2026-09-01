<?php

require_once __DIR__ . '/OptimizareRuteService.php';
require_once __DIR__ . '/ExpediereRepository.php';
require_once __DIR__ . '/BusinessRepository.php';

/**
 * Analiza algoritmului de optimizare a rutelor: cu ce criterii lucreaza si cat
 * de bine a ales pe datele reale din baza. Doar citire, nu atinge nimic.
 *
 * Regulile nu se scriu de mana aici: se obtin chemand chiar metodele
 * `OptimizareRuteService::ajustareViteza()` / `ajustareTip()`, deci daca pragurile
 * se schimba in algoritm, se schimba si ce scrie in pagina de laborator.
 *
 * Masurarea foloseste doar partea DETERMINISTA a timpului ajustat (durata
 * prestabilita + viteza + tipul drumului). Vremea se lasa deoparte pentru ca e
 * luata in timp real la momentul expedierii si nu se poate reconstitui acum;
 * altfel aceeasi cursa ar da alt scor la fiecare incarcare a paginii.
 */
class AnalizaRuteService
{
    /** Vitezele cu care se arata pragurile de ajustare (cate una din fiecare interval). */
    const VITEZE_EXEMPLU = [55, 65, 75, 95];

    /** Tipurile de drum, de la cel mai bun la cel mai greu. */
    const TIPURI_DRUM = ['autostrada', 'dn', 'drum judetean', 'drum comunal'];

    private $pdo;
    private $optimizare;

    public function __construct(PDO $pdo, OptimizareRuteService $optimizare = null)
    {
        $this->pdo = $pdo;
        $this->optimizare = $optimizare !== null ? $optimizare : new OptimizareRuteService($pdo);
    }

    /**
     * Timpul ajustat fara vreme: durata prestabilita + viteza + tipul drumului.
     * Asta e partea care depinde numai de ruta, deci se poate recalcula oricand.
     */
    public static function timpDeterminist(array $ruta)
    {
        return (int) $ruta['Durata_min']
            + OptimizareRuteService::ajustareViteza($ruta['viteza'])
            + OptimizareRuteService::ajustareTip($ruta['tip_strada']);
    }

    /**
     * Pragurile de viteza, luate din algoritm.
     *
     * @return array randuri ['interval', 'ajustare']
     */
    public function reguliViteza()
    {
        $etichete = ['<= 60 km/h', '61 - 70 km/h', '71 - 80 km/h', 'peste 80 km/h'];

        $reguli = [];
        foreach (self::VITEZE_EXEMPLU as $i => $viteza) {
            $reguli[] = [
                'interval' => $etichete[$i],
                'ajustare' => OptimizareRuteService::ajustareViteza($viteza),
            ];
        }

        return $reguli;
    }

    /**
     * Ajustarea pe tip de drum, luata din algoritm.
     *
     * @return array randuri ['tip', 'ajustare']
     */
    public function reguliTipDrum()
    {
        $reguli = [];
        foreach (self::TIPURI_DRUM as $tip) {
            $reguli[] = ['tip' => $tip, 'ajustare' => OptimizareRuteService::ajustareTip($tip)];
        }

        return $reguli;
    }

    /**
     * Toate rutele, grupate pe orasul de destinatie, cu timpul determinist si
     * un semn pe cea mai rapida si pe cea mai scurta din grup.
     *
     * @return array oras => ['rute' => array, 'best_timp' => int, 'best_km' => int]
     */
    public function ruteDupaDestinatie()
    {
        $rute = $this->pdo->query(
            'SELECT RutaID, Oras_origine, Oras_destinatie, Distanta_km, Durata_min, viteza, tip_strada
             FROM rute ORDER BY Oras_destinatie, Oras_origine'
        )->fetchAll();

        $grupe = [];
        foreach ($rute as $r) {
            $r['timp'] = self::timpDeterminist($r);
            $grupe[$r['Oras_destinatie']]['rute'][] = $r;
        }

        foreach ($grupe as $oras => $g) {
            $timpi = array_column($g['rute'], 'timp');
            $kmuri = array_column($g['rute'], 'Distanta_km');

            $grupe[$oras]['best_timp'] = min($timpi);
            $grupe[$oras]['best_km'] = min($kmuri);

            // Ordonat ca in back office: cea mai rapida prima.
            usort($grupe[$oras]['rute'], function ($a, $b) {
                return $a['timp'] <=> $b['timp'];
            });
        }

        return $grupe;
    }

    /**
     * Cat de bine a ales algoritmul pe expedierile deja facute.
     *
     * Fiecare cursa se compara cu toate rutele catre acelasi oras: daca a plecat
     * pe cea mai rapida, e "pe optim". Cand nu e, diferenta NU inseamna neaparat
     * ca algoritmul a gresit - el alege doar dintre depozitele care aveau produsul
     * pe stoc, deci minutele in plus sunt costul asezarii marfii in depozite.
     *
     * @return array cu totalurile si randurile pe oras
     */
    public function performanta()
    {
        $grupe = $this->ruteDupaDestinatie();

        $curse = $this->pdo->query(
            'SELECT r.Oras_origine, r.Oras_destinatie, r.Distanta_km, r.Durata_min,
                    r.viteza, r.tip_strada, COUNT(*) AS curse
             FROM expedieri e
             JOIN rute r ON r.RutaID = e.RutaID
             GROUP BY r.RutaID, r.Oras_origine, r.Oras_destinatie, r.Distanta_km,
                      r.Durata_min, r.viteza, r.tip_strada'
        )->fetchAll();

        $total = 0;
        $peOptim = 0;
        $minuteInPlus = 0;
        $kmInPlus = 0;
        $minutePeCelMaiProst = 0;   // cat ar fi durat daca s-ar fi ales mereu cel mai prost candidat
        $minuteAles = 0;
        $orase = [];

        foreach ($curse as $c) {
            $oras = $c['Oras_destinatie'];
            $n = (int) $c['curse'];
            $timp = self::timpDeterminist($c);
            $bestTimp = $grupe[$oras]['best_timp'];
            $bestKm = $grupe[$oras]['best_km'];
            $celMaiProst = max(array_column($grupe[$oras]['rute'], 'timp'));

            $optim = $timp === $bestTimp;

            $total += $n;
            $peOptim += $optim ? $n : 0;
            $minuteInPlus += ($timp - $bestTimp) * $n;
            $kmInPlus += ((int) $c['Distanta_km'] - $bestKm) * $n;
            $minuteAles += $timp * $n;
            $minutePeCelMaiProst += $celMaiProst * $n;

            if (!isset($orase[$oras])) {
                $orase[$oras] = [
                    'oras' => $oras,
                    'curse' => 0,
                    'pe_optim' => 0,
                    'minute_in_plus' => 0,
                    'km_in_plus' => 0,
                    'ruta_optima' => $grupe[$oras]['rute'][0]['Oras_origine'],
                    'timp_optim' => $bestTimp,
                ];
            }

            $orase[$oras]['curse'] += $n;
            $orase[$oras]['pe_optim'] += $optim ? $n : 0;
            $orase[$oras]['minute_in_plus'] += ($timp - $bestTimp) * $n;
            $orase[$oras]['km_in_plus'] += ((int) $c['Distanta_km'] - $bestKm) * $n;
        }

        // Cate orase au ruta cea mai rapida diferita de cea mai scurta: acolo
        // criteriul de timp ar costa kilometri (deci carburant) in plus.
        $conflicte = [];
        foreach ($grupe as $oras => $g) {
            if ((int) $g['rute'][0]['Distanta_km'] !== (int) $g['best_km']) {
                $conflicte[] = $oras;
            }
        }

        $litriInPlus = $this->optimizare->litri($kmInPlus);

        // Ordonat de la orasul cu cele mai multe minute pierdute.
        usort($orase, function ($a, $b) {
            return [$b['minute_in_plus'], $b['curse']] <=> [$a['minute_in_plus'], $a['curse']];
        });

        return [
            'curse' => $total,
            'pe_optim' => $peOptim,
            'procent_optim' => $total > 0 ? $peOptim / $total * 100 : 0,
            'minute_in_plus' => $minuteInPlus,
            'km_in_plus' => $kmInPlus,
            'litri_in_plus' => $litriInPlus,
            'lei_in_plus' => $this->optimizare->costCarburant($kmInPlus),
            'minute_alese' => $minuteAles,
            'minute_cel_mai_prost' => $minutePeCelMaiProst,
            'minute_economisite' => $minutePeCelMaiProst - $minuteAles,
            'conflicte_timp_km' => $conflicte,
            'orase' => $orase,
        ];
    }

    /**
     * Compara financiar doua moduri de a alege ruta, pe aceleasi livrari:
     *
     *   - OPTIMIZAT   = ce s-a intamplat cu adevarat. Ruta pe care a plecat
     *                   coletul, cu costul de carburant inregistrat pe expediere.
     *   - NEOPTIMIZAT = acelasi colet, dar plecat "la nimereala": media tuturor
     *                   rutelor catre orasul ala. Media, nu cea mai proasta ruta,
     *                   fiindca fara algoritm nu alegi anume varianta cea mai rea,
     *                   ci una oarecare - media e rezultatul asteptat.
     *
     * Incasarea e aceeasi in ambele: vanzarea nu depinde de drumul ales. Se
     * schimba costul, din doua motive - alt drum inseamna alt carburant, iar alt
     * depozit de plecare inseamna alt cost de achizitie pentru aceeasi marfa.
     *
     * Pretul carburantului nu se ia din API pentru varianta imaginara, ci se
     * deduce din expedierea reala (lei inregistrati / km parcursi), ca ambele
     * scenarii sa fie socotite la acelasi pret.
     *
     * Intra numai expedierile livrate, ca in rapoartele de bani din dashboard.
     *
     * @return array ['optimizat' => array, 'neoptimizat' => array, 'livrari' => int]
     */
    public function comparatieFinanciara()
    {
        $grupe = $this->ruteDupaDestinatie();
        $costMarfa = $this->costuriMarfa();

        $livrari = $this->pdo->query(
            'SELECT e.Valoare_expediere, e.cost_carburant,
                    r.Oras_origine, r.Oras_destinatie, r.Distanta_km,
                    cp.Product_ID, cp.Cantitate
             FROM expedieri e
             JOIN rute r ON r.RutaID = e.RutaID
             LEFT JOIN comenzi_produse cp ON cp.LinieID = e.LinieID
             WHERE e.Status_expediere = ' . $this->pdo->quote(BusinessRepository::STATUS_LIVRAT) . '
               AND e.Data_livrare_efectiva IS NOT NULL'
        )->fetchAll();

        $gol = ['incasare' => 0.0, 'cost_marfa' => 0.0, 'cost_carburant' => 0.0, 'km' => 0.0];
        $optimizat = $gol;
        $neoptimizat = $gol;

        foreach ($livrari as $l) {
            $km = (float) $l['Distanta_km'];
            $incasare = (float) $l['Valoare_expediere'];
            $carburant = (float) $l['cost_carburant'];

            // Lei pe kilometru, luati chiar din expedierea asta. Daca lipsesc,
            // se cade pe pretul de azi, ca sa nu iasa zero.
            $leiPeKm = $km > 0 && $carburant > 0
                ? $carburant / $km
                : $this->optimizare->costCarburant(100) / 100;

            $optimizat['incasare'] += $incasare;
            $optimizat['cost_carburant'] += $carburant;
            $optimizat['cost_marfa'] += $this->marfa($costMarfa, $l['Product_ID'], $l['Cantitate'], $l['Oras_origine']);
            $optimizat['km'] += $km;

            // Varianta fara algoritm: media candidatilor catre acelasi oras.
            $candidati = isset($grupe[$l['Oras_destinatie']]) ? $grupe[$l['Oras_destinatie']]['rute'] : [];
            $n = count($candidati);

            $neoptimizat['incasare'] += $incasare;

            if ($n === 0) {
                // Fara rute candidate nu exista varianta alternativa: ramane ce a fost.
                $neoptimizat['cost_carburant'] += $carburant;
                $neoptimizat['cost_marfa'] += $this->marfa($costMarfa, $l['Product_ID'], $l['Cantitate'], $l['Oras_origine']);
                $neoptimizat['km'] += $km;
                continue;
            }

            foreach ($candidati as $c) {
                $kmC = (float) $c['Distanta_km'];

                $neoptimizat['km'] += $kmC / $n;
                $neoptimizat['cost_carburant'] += $kmC * $leiPeKm / $n;
                $neoptimizat['cost_marfa'] +=
                    $this->marfa($costMarfa, $l['Product_ID'], $l['Cantitate'], $c['Oras_origine']) / $n;
            }
        }

        return [
            'livrari' => count($livrari),
            'optimizat' => $this->incheie($optimizat),
            'neoptimizat' => $this->incheie($neoptimizat),
        ];
    }

    /** Adauga costul total si profitul peste componentele deja adunate. */
    private function incheie(array $sume)
    {
        $sume['cost'] = $sume['cost_marfa'] + $sume['cost_carburant'];
        $sume['profit'] = $sume['incasare'] - $sume['cost'];

        return $sume;
    }

    /**
     * Costul de achizitie al marfii dintr-o livrare, daca ar pleca din depozitul
     * unui anumit oras. Cantitatea x costul unitar de acolo.
     */
    private function marfa(array $costMarfa, $productId, $cantitate, $orasDepozit)
    {
        if ($productId === null) {
            return 0.0;
        }

        $depozit = ExpediereRepository::DEPOZIT_COD[$orasDepozit] ?? null;

        $cost = $costMarfa[$productId][$depozit] ?? ($costMarfa[$productId]['orice'] ?? 0.0);

        return (float) $cantitate * (float) $cost;
    }

    /**
     * Costul de achizitie pe produs si depozit: cel mai recent rand de stoc de
     * acolo. Cheia 'orice' tine cel mai recent rand al produsului, indiferent de
     * depozit, pentru cazul in care depozitul cerut n-are stoc inregistrat -
     * aceeasi regula ca in rapoartele de bani din dashboard.
     *
     * @return array Product_ID => [depozit => cost, 'orice' => cost]
     */
    private function costuriMarfa()
    {
        $randuri = $this->pdo->query(
            'SELECT Product_ID, depozit, Cost_Unitar, `Date`
             FROM inventory
             WHERE Cost_Unitar IS NOT NULL
             ORDER BY `Date`'
        )->fetchAll();

        // Ordonate crescator dupa data, deci ultima scriere ramane cea recenta.
        $costuri = [];
        foreach ($randuri as $r) {
            $costuri[$r['Product_ID']][(int) $r['depozit']] = (float) $r['Cost_Unitar'];
            $costuri[$r['Product_ID']]['orice'] = (float) $r['Cost_Unitar'];
        }

        return $costuri;
    }

    /**
     * Rularea algoritmului acum, pe un produs si un oras, exact cum o face back
     * office-ul la expedierea unei comenzi (cu vremea din API, deci rezultatul se
     * poate schimba de la o ora la alta).
     *
     * @return array ['produs' => array|null, 'oras' => string, 'rute' => array]
     */
    public function exempluLive($productId = null, $oras = null)
    {
        // Implicit: un produs care exista in mai multe depozite, ca sa aiba ce compara.
        if ($productId === null) {
            $productId = $this->pdo->query(
                'SELECT Product_ID FROM inventory WHERE Stock_Level > 0
                 GROUP BY Product_ID ORDER BY COUNT(DISTINCT depozit) DESC, Product_ID LIMIT 1'
            )->fetchColumn();
        }

        if ($oras === null) {
            $oras = $this->pdo->query(
                'SELECT cl.Oras FROM comenzi co
                 JOIN clienti cl ON cl.ClientID = co.ClientID
                 GROUP BY cl.Oras ORDER BY COUNT(*) DESC LIMIT 1'
            )->fetchColumn();
        }

        $produs = null;
        if ($productId !== false && $productId !== null) {
            // Numele produsului sta in catalog, nu pe randul de stoc.
            $stmt = $this->pdo->prepare(
                'SELECT Product_ID, Product_Name FROM produse WHERE Product_ID = :p LIMIT 1'
            );
            $stmt->execute(['p' => $productId]);
            $produs = $stmt->fetch();
            $produs = $produs === false ? null : $produs;
        }

        return [
            'produs' => $produs,
            'oras' => (string) $oras,
            'rute' => $produs === null ? [] : $this->optimizare->ruteOptimizate($productId, $oras),
        ];
    }
}
