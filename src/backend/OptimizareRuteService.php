<?php

require_once __DIR__ . '/MeteoService.php';
require_once __DIR__ . '/CarburantService.php';

/**
 * Algoritm de optimizare a rutelor la expedierea unei comenzi.
 *
 * Reguli complete in docs/algoritm_optimizare_rute.md. Pe scurt: pentru o linie
 * de comanda (un produs) se iau depozitele care au produsul, se calculeaza timpul
 * ajustat pe ruta depozit -> oras client si se ordoneaza crescator (cel mai mic
 * timp = cea mai optimizata ruta). Operatorul alege o ruta, iar la selectie se
 * creeaza expedierea cu un AWB unic.
 */
class OptimizareRuteService
{
    /** Codul de depozit din inventory.depozit -> orasul (origine ruta). */
    const DEPOZITE = [1 => 'Arad', 2 => 'Braila', 3 => 'Pitesti'];

    /** Programul curierilor: se conduce doar in acest interval, in toate zilele. */
    const PROGRAM_START = 7;   // 07:00
    const PROGRAM_END = 22;    // 22:00

    /** Consumul mediu al dubei de curier (motorina), in litri la 100 km. */
    const CONSUM_L_100KM = 12;

    private $pdo;
    private $meteo;
    private $carburant;

    public function __construct(PDO $pdo, MeteoService $meteo = null, CarburantService $carburant = null)
    {
        $this->pdo = $pdo;
        $this->meteo = $meteo !== null ? $meteo : new MeteoService();
        $this->carburant = $carburant !== null ? $carburant : new CarburantService();
    }

    /** Costul de carburant al unei rute (lei) = km/100 x consum x pret motorina. */
    public function costCarburant($km)
    {
        $litri = ((float) $km / 100) * self::CONSUM_L_100KM;

        return round($litri * $this->carburant->pretMotorina(), 2);
    }

    /**
     * Ajustarea de timp (minute) dupa viteza, pe intervale.
     * <=60 => +60, 61-70 => +40, 71-80 => +30, >80 => +0.
     */
    public static function ajustareViteza($viteza)
    {
        $viteza = (int) $viteza;

        if ($viteza <= 60) {
            return 60;
        }
        if ($viteza <= 70) {
            return 40;
        }
        if ($viteza <= 80) {
            return 30;
        }

        return 0;
    }

    /** Ajustarea de timp (minute) dupa tipul de drum. */
    public static function ajustareTip($tip)
    {
        switch ($tip) {
            case 'autostrada':
                return -30;
            case 'drum judetean':
                return 10;
            case 'drum comunal':
                return 20;
            case 'dn':
            default:
                return 0;
        }
    }

    /**
     * Adauga un numar de minute de mers la un moment de start, numarand doar
     * orele de program (07:00-22:00), in toate zilele. Intoarce data+ora sosirii
     * ca "Y-m-d H:i:s".
     */
    public static function adaugaMinuteProgram($start, $minute)
    {
        $t = new DateTime($start);
        $ramase = max(0, (int) $minute);

        while ($ramase > 0) {
            $fereastraStart = (clone $t)->setTime(self::PROGRAM_START, 0);
            $fereastraEnd = (clone $t)->setTime(self::PROGRAM_END, 0);

            if ($t < $fereastraStart) {
                $t = $fereastraStart;
            } elseif ($t >= $fereastraEnd) {
                $t = (clone $t)->modify('+1 day')->setTime(self::PROGRAM_START, 0);
                continue;
            }

            $disponibil = intdiv($fereastraEnd->getTimestamp() - $t->getTimestamp(), 60);
            if ($ramase <= $disponibil) {
                $t->modify('+' . $ramase . ' minutes');
                $ramase = 0;
            } else {
                $ramase -= $disponibil;
                $t = (clone $t)->modify('+1 day')->setTime(self::PROGRAM_START, 0);
            }
        }

        return $t->format('Y-m-d H:i:s');
    }

    /**
     * Rutele candidate pentru un produs livrat intr-un oras, ordonate de la cea
     * mai optimizata (timp ajustat minim) la cea mai putin optimizata.
     *
     * Fiecare element are datele rutei plus: ajustare_viteza, ajustare_tip,
     * timp_ajustat, depozit_cod, explicatie.
     *
     * @return array[]
     */
    public function ruteOptimizate($productId, $orasClient)
    {
        // Depozitele care au produsul pe stoc.
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT depozit FROM inventory
             WHERE Product_ID = :pid AND depozit IN (1, 2, 3) AND Stock_Level > 0'
        );
        $stmt->execute(['pid' => $productId]);
        $coduri = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $rezultate = [];
        foreach ($coduri as $cod) {
            $orasDepozit = self::DEPOZITE[(int) $cod] ?? null;
            if ($orasDepozit === null) {
                continue;
            }

            $r = $this->pdo->prepare(
                'SELECT * FROM rute WHERE Oras_origine = :o AND Oras_destinatie = :d'
            );
            $r->execute(['o' => $orasDepozit, 'd' => $orasClient]);
            $ruta = $r->fetch();
            if ($ruta === false) {
                continue; // nu exista ruta depozit -> oras client
            }

            $rezultate[] = $this->augmenteaza($ruta, (int) $cod);
        }

        // Cel mai mic timp ajustat = cea mai optimizata.
        usort($rezultate, function ($a, $b) {
            return $a['timp_ajustat'] <=> $b['timp_ajustat'];
        });

        return $rezultate;
    }

    /**
     * Explicatia algoritmului pentru o singura ruta, asa cum se vede in back
     * office: din ce se compune timpul ei ajustat, cat carburant cere si pe ce
     * loc iese fata de celelalte rute catre acelasi oras.
     *
     * Clasamentul se face pe timp ajustat, exact criteriul dupa care alege
     * `ruteOptimizate()`. Diferenta fata de o expediere reala e ca acolo intra
     * doar depozitele care au produsul pe stoc; aici se compara toate rutele
     * catre orasul respectiv, ca sa se vada potentialul fiecarui depozit.
     *
     * @return array ['ruta', 'clasament', 'loc', 'castigator', 'diferenta']
     *               unde 'diferenta' = minutele pana la locul 1 (0 daca e primul)
     */
    public function explicaRuta(array $ruta)
    {
        $asta = $this->augmenteaza($ruta, $this->codDepozit($ruta['Oras_origine']));

        $stmt = $this->pdo->prepare(
            'SELECT * FROM rute WHERE Oras_destinatie = :d'
        );
        $stmt->execute(['d' => $ruta['Oras_destinatie']]);

        $clasament = [];
        foreach ($stmt->fetchAll() as $r) {
            $r = $this->augmenteaza($r, $this->codDepozit($r['Oras_origine']));
            $r['este_asta'] = (int) $r['RutaID'] === (int) $ruta['RutaID'];
            $clasament[] = $r;
        }

        // Criteriul e timpul ajustat. Kilometrii si numele departajeaza doar
        // afisarea, ca ordinea sa fie mereu aceeasi la egalitate de timp.
        usort($clasament, function ($a, $b) {
            if ($a['timp_ajustat'] !== $b['timp_ajustat']) {
                return $a['timp_ajustat'] <=> $b['timp_ajustat'];
            }

            if ((int) $a['Distanta_km'] !== (int) $b['Distanta_km']) {
                return (int) $a['Distanta_km'] <=> (int) $b['Distanta_km'];
            }

            return strcmp($a['Oras_origine'], $b['Oras_origine']);
        });

        $loc = 0;
        foreach ($clasament as $i => $r) {
            if ($r['este_asta']) {
                $loc = $i + 1;
                break;
            }
        }

        $castigator = $clasament ? $clasament[0] : null;

        return [
            'ruta' => $asta,
            'clasament' => $clasament,
            'loc' => $loc,
            'castigator' => $castigator,
            'diferenta' => $castigator === null ? 0 : $asta['timp_ajustat'] - $castigator['timp_ajustat'],
        ];
    }

    /** Codul de depozit al unui oras de plecare (sau null daca nu e depozit). */
    private function codDepozit($oras)
    {
        $cod = array_search($oras, self::DEPOZITE, true);

        return $cod === false ? null : $cod;
    }

    /** Litrii de motorina ceruti de o ruta, la consumul mediu al dubei. */
    public function litri($km)
    {
        return round(((float) $km / 100) * self::CONSUM_L_100KM, 1);
    }

    /** Pretul curent al motorinei (lei/L), pentru afisare. */
    public function pretMotorina()
    {
        return $this->carburant->pretMotorina();
    }

    /** Adauga pe randul de ruta ajustarile, timpul ajustat si explicatia. */
    private function augmenteaza(array $ruta, $depozitCod)
    {
        $av = self::ajustareViteza($ruta['viteza']);
        $at = self::ajustareTip($ruta['tip_strada']);
        $vreme = $this->meteo->ajustareRuta($ruta['Oras_origine'], $ruta['Oras_destinatie']);

        $ruta['depozit_cod'] = $depozitCod;
        $ruta['ajustare_viteza'] = $av;
        $ruta['ajustare_tip'] = $at;
        $ruta['ajustare_vreme'] = $vreme['ajustare'];
        $ruta['vreme_text'] = $vreme['text'];
        $ruta['timp_ajustat'] = (int) $ruta['Durata_min'] + $av + $at + $vreme['ajustare'];
        $ruta['cost_carburant'] = $this->costCarburant((int) $ruta['Distanta_km']);
        $ruta['explicatie'] = $this->explica($ruta);

        return $ruta;
    }

    /** Text care explica de unde vine timpul ajustat. */
    private function explica(array $ruta)
    {
        $semn = function ($v) {
            return ($v >= 0 ? '+' : '') . $v;
        };

        return 'Timp prestabilit ' . (int) $ruta['Durata_min'] . ' min'
            . ', viteza ' . (int) $ruta['viteza'] . ' km/h (' . $semn($ruta['ajustare_viteza']) . ')'
            . ', ' . $ruta['tip_strada'] . ' (' . $semn($ruta['ajustare_tip']) . ')'
            . ', vreme: ' . $ruta['vreme_text'] . ' (' . $semn($ruta['ajustare_vreme']) . ')'
            . ' = ' . $ruta['timp_ajustat'] . ' min';
    }

    /**
     * Creeaza o expediere pentru o linie de comanda, pe o ruta aleasa, si ii
     * genereaza un AWB unic. Soferul se alege aleator.
     *
     * @return array ['errors' => string[], 'awb' => string|null]
     */
    public function creeazaExpediere($linieId, $rutaId)
    {
        $linieId = (int) $linieId;
        $rutaId = (int) $rutaId;

        // Linia de comanda + comanda + orasul clientului.
        $stmt = $this->pdo->prepare(
            'SELECT cp.LinieID, cp.ComandaID, cp.Product_ID, cp.Subtotal,
                    co.ClientID, cl.Oras AS oras_client
             FROM comenzi_produse cp
             JOIN comenzi co ON co.ComandaID = cp.ComandaID
             JOIN clienti cl ON cl.ClientID = co.ClientID
             WHERE cp.LinieID = :id'
        );
        $stmt->execute(['id' => $linieId]);
        $linie = $stmt->fetch();
        if ($linie === false) {
            return ['errors' => ['Linia de comanda nu exista.'], 'awb' => null];
        }

        // O linie se expediaza o singura data.
        if ($this->expediereePentruLinie($linieId) !== null) {
            return ['errors' => ['Aceasta linie are deja o expediere.'], 'awb' => null];
        }

        // Ruta aleasa trebuie sa fie una din cele candidate pentru acest produs.
        $candidate = $this->ruteOptimizate($linie['Product_ID'], $linie['oras_client']);
        $ruta = null;
        foreach ($candidate as $c) {
            if ((int) $c['RutaID'] === $rutaId) {
                $ruta = $c;
                break;
            }
        }
        if ($ruta === null) {
            return ['errors' => ['Ruta aleasa nu este valida pentru acest produs.'], 'awb' => null];
        }

        // Sofer aleator.
        $soferId = $this->pdo->query('SELECT SoferID FROM soferi ORDER BY RAND() LIMIT 1')->fetchColumn();
        if ($soferId === false) {
            return ['errors' => ['Nu exista soferi in sistem.'], 'awb' => null];
        }

        // Data expedierii = acum, cu ora (din ceasul DB, acelasi cu restul).
        // Data livrarii estimate: se adauga timpul ajustat mergand DOAR in orele
        // de program (07:00-22:00), asa ca iese o data+ora realista.
        $dataExpediere = $this->pdo->query('SELECT NOW()')->fetchColumn();
        $dataEstimata = self::adaugaMinuteProgram($dataExpediere, (int) $ruta['timp_ajustat']);

        try {
            $this->pdo->beginTransaction();

            $ins = $this->pdo->prepare(
                'INSERT INTO expedieri
                    (ClientID, SoferID, RutaID, LinieID, Data_expediere,
                     Data_livrare_estimata, Status_expediere, Valoare_expediere, cost_carburant)
                 VALUES (:client, :sofer, :ruta, :linie, :dexp, :dest, :status, :val, :carb)'
            );
            $ins->execute([
                'client' => $linie['ClientID'],
                'sofer' => $soferId,
                'ruta' => $rutaId,
                'linie' => $linieId,
                'dexp' => $dataExpediere,
                'dest' => $dataEstimata,
                'status' => 'In tranzit',
                'val' => $linie['Subtotal'],
                'carb' => $ruta['cost_carburant'],
            ]);

            $expediereId = (int) $this->pdo->lastInsertId();
            $awb = 'AWB' . date('Ymd', strtotime($dataExpediere)) . str_pad($expediereId, 6, '0', STR_PAD_LEFT);

            $this->pdo->prepare('UPDATE expedieri SET awb = :awb WHERE ExpediereID = :id')
                ->execute(['awb' => $awb, 'id' => $expediereId]);

            $this->pdo->commit();

            return ['errors' => [], 'awb' => $awb];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return ['errors' => ['Nu s-a putut crea expedierea. Incearca din nou.'], 'awb' => null];
        }
    }

    /**
     * Expedierea existenta pentru o linie de comanda (sau null).
     *
     * @return array|null cu ExpediereID, awb, Status_expediere, ruta
     */
    public function expediereePentruLinie($linieId)
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.ExpediereID, e.awb, e.Status_expediere,
                    CONCAT(r.Oras_origine, " - ", r.Oras_destinatie) AS ruta
             FROM expedieri e
             JOIN rute r ON r.RutaID = e.RutaID
             WHERE e.LinieID = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => (int) $linieId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}
