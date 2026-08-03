<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/OptimizareRuteService.php';

/**
 * Acces la date pentru tabela `comenzi` (comenzile plasate din magazin).
 *
 * Are cheie straina catre clienti, deci listarea face JOIN ca sa afiseze numele
 * clientului in loc de ID. Liniile comenzii (comenzi_produse) au ON DELETE
 * CASCADE, deci stergerea unei comenzi isi sterge singura liniile.
 */
class ComandaRepository extends BaseRepository
{
    const STATUSURI = ['Noua', 'In procesare', 'Trimisa', 'Anulata'];

    /** Estimarile financiare deja calculate in cererea curenta, pe ComandaID. */
    private $situatii = [];

    protected function table()
    {
        return 'comenzi';
    }

    protected function primaryKey()
    {
        return 'ComandaID';
    }

    protected function columns()
    {
        return ['ClientID', 'Data_comanda', 'Status', 'Total', 'Observatii'];
    }

    protected function sortableColumns()
    {
        return ['ComandaID', 'ClientNume', 'Data_comanda', 'Status', 'Total', 'Depozite'];
    }

    protected function searchableColumns()
    {
        return [];
    }

    /**
     * Aduce si numele clientului, pentru afisare. Data_comanda e DATETIME in DB;
     * o reducem la DATE ca sa se potriveasca cu inputul de tip date din formular
     * si cu formatarea din tabel.
     */
    protected function selectFrom()
    {
        return 'SELECT c.ComandaID, c.ClientID, c.Status, c.Total, c.Observatii,
                       DATE(c.Data_comanda) AS Data_comanda,
                       cl.Nume AS ClientNume, cl.Oras AS ClientOras,
                       (SELECT GROUP_CONCAT(CONCAT(cp.Cantitate, \' x \', cp.Product_Name)
                                            ORDER BY cp.LinieID SEPARATOR \', \')
                          FROM comenzi_produse cp WHERE cp.ComandaID = c.ComandaID) AS Produse,
                       (SELECT COUNT(*) FROM comenzi_produse cp
                         WHERE cp.ComandaID = c.ComandaID) AS NrLinii,
                       (SELECT COALESCE(SUM(cp.Subtotal), 0) FROM comenzi_produse cp
                         WHERE cp.ComandaID = c.ComandaID) AS TotalLinii,
                       (SELECT COUNT(*) FROM expedieri e
                           JOIN comenzi_produse cp ON cp.LinieID = e.LinieID
                          WHERE cp.ComandaID = c.ComandaID) AS NrExpediate,
                       (SELECT GROUP_CONCAT(DISTINCT r.Oras_origine
                                            ORDER BY r.Oras_origine SEPARATOR \', \')
                          FROM expedieri e
                          JOIN comenzi_produse cp ON cp.LinieID = e.LinieID
                          JOIN rute r ON r.RutaID = e.RutaID
                         WHERE cp.ComandaID = c.ComandaID) AS Depozite
                FROM comenzi c
                JOIN clienti cl ON cl.ClientID = c.ClientID';
    }

    public function getById($id)
    {
        $stmt = $this->pdo->prepare($this->selectFrom() . ' WHERE c.ComandaID = :id');
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Cautarea acopera numele clientului, statusul, observatiile si produsele din
     * comanda, deci se scrie manual peste coloanele din JOIN.
     */
    public function getAll($search = '', $sort = null, $dir = 'desc')
    {
        $sql = $this->selectFrom();
        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $sql .= ' WHERE cl.Nume LIKE :s1 OR c.Status LIKE :s2 OR c.Observatii LIKE :s3
                      OR EXISTS (SELECT 1 FROM comenzi_produse cps
                                  WHERE cps.ComandaID = c.ComandaID
                                    AND (cps.Product_Name LIKE :s4 OR cps.Product_ID LIKE :s5))';
            $term = '%' . $search . '%';
            $params = ['s1' => $term, 's2' => $term, 's3' => $term, 's4' => $term, 's5' => $term];
        }

        if (!in_array($sort, $this->sortableColumns(), true)) {
            $sort = 'ComandaID';
        }

        $dir = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';

        $sql .= ' ORDER BY ' . $this->quote($sort) . ' ' . $dir;

        if ($sort !== 'ComandaID') {
            $sql .= ', c.ComandaID DESC';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function validate(array $input)
    {
        $errors = [];

        $client_id = $this->validForeignKey($errors, $input, 'ClientID', 'Clientul', 'clienti', 'ClientID');
        $data_comanda = $this->validDate($errors, $input, 'Data_comanda', 'Data comenzii');
        $status = $this->validEnum($errors, $input, 'Status', 'Statusul', self::STATUSURI);
        $total = $this->validDecimal($errors, $input, 'Total', 'Totalul', 0);

        // Optionala: comanda poate sa nu aiba observatii.
        $observatii = $this->validText($errors, $input, 'Observatii', 'Observatiile', 500, false);
        if ($observatii === '') {
            $observatii = null;
        }

        return [
            'errors' => $errors,
            'data' => [
                'ClientID' => $client_id,
                'Data_comanda' => $data_comanda,
                'Status' => $status,
                'Total' => $total,
                'Observatii' => $observatii,
            ],
        ];
    }

    /**
     * Optiunile pentru dropdown-urile din formular.
     */
    public function getOptiuni()
    {
        return [
            'clienti' => $this->pdo->query('SELECT ClientID AS id, Nume AS text FROM clienti ORDER BY Nume')->fetchAll(),
            'statusuri' => self::STATUSURI,
        ];
    }

    /** Statusurile la care comanda inca n-a plecat nicaieri. */
    const STATUSURI_DESCHISE = ['Noua', 'In procesare'];

    /**
     * O comanda e "deschisa" cat timp inca se poate decide asupra ei: nu e nici
     * trimisa, nici anulata, si nu are nicio expediere creata. Doar atunci are
     * rost intrebarea "o trimit sau o anulez".
     */
    public function esteDeschisa(array $comanda)
    {
        return in_array($comanda['Status'], self::STATUSURI_DESCHISE, true)
            && (int) $comanda['NrExpediate'] === 0;
    }

    /** Statusurile comenzilor care n-au plecat: nedecise sau respinse. */
    const STATUSURI_NEEXPEDIATE = ['Noua', 'In procesare', 'Anulata'];

    /**
     * Comenzile care n-au nicio linie expediata, filtrate dupa status.
     *
     * Sunt singurele pentru care se poate calcula o estimare de profit (pe cele
     * plecate se stiu deja cifrele reale, din expedieri), deci si singurele pe
     * care le parcurge pagina principala cand numara comenzile pe pierdere.
     * Implicit intra si cele anulate: o comanda anulata fiindca pierdea bani tot
     * o comanda pe pierdere ramane, si tocmai ea trebuie sa se vada.
     *
     * @param array $statusuri statusurile acceptate
     * @return array[] randuri ca la `getAll()`
     */
    public function faraExpediere(array $statusuri = self::STATUSURI_NEEXPEDIATE)
    {
        if (!$statusuri) {
            return [];
        }

        $sql = $this->selectFrom()
            . ' WHERE c.Status IN (' . implode(', ', array_map([$this->pdo, 'quote'], $statusuri)) . ')
                  AND NOT EXISTS (SELECT 1 FROM expedieri e
                                    JOIN comenzi_produse cp ON cp.LinieID = e.LinieID
                                   WHERE cp.ComandaID = c.ComandaID)
                ORDER BY c.ComandaID DESC';

        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Cate comenzi acceptate la trimitere are firma: cele cu decizia luata
     * (au rand in `comenzi_financiar`) plus cele deja trimise complet.
     */
    public function numarAcceptate()
    {
        return (int) $this->pdo->query(
            'SELECT COUNT(*) FROM comenzi c
              WHERE c.Status = "Trimisa"
                 OR EXISTS (SELECT 1 FROM comenzi_financiar f WHERE f.ComandaID = c.ComandaID)'
        )->fetchColumn();
    }

    /**
     * Estimarea financiara a unei comenzi neexpediate: cat incaseaza, cat costa
     * marfa si carburantul, si ce profit iese.
     *
     * Comanda n-are inca expedieri, deci costul de carburant nu exista nicaieri:
     * se estimeaza pe ruta pe care ar alege-o algoritmul pentru fiecare produs
     * (prima din `ruteOptimizate`), la pretul motorinei de azi. Fiecare linie de
     * comanda pleaca separat, ca in fluxul actual de expediere, deci costurile
     * liniilor se aduna.
     *
     * @return array ['incasare', 'marfa', 'carburant', 'cost', 'profit', 'linii']
     *               unde 'linii' descrie fiecare produs cu ruta lui estimata
     */
    public function situatieFinanciara(array $comanda, OptimizareRuteService $optimizare)
    {
        // Aceeasi comanda e intrebata de mai multe ori pe aceeasi pagina (eticheta
        // butonului, culoarea lui, coloana de depozite), iar calculul trece prin
        // algoritmul de rute. Il facem o singura data pe cerere.
        $cheie = (int) $comanda['ComandaID'];
        if (isset($this->situatii[$cheie])) {
            return $this->situatii[$cheie];
        }

        $stmt = $this->pdo->prepare(
            'SELECT LinieID, Product_ID, Product_Name, Cantitate, Subtotal
             FROM comenzi_produse WHERE ComandaID = :id ORDER BY LinieID'
        );
        $stmt->execute(['id' => (int) $comanda['ComandaID']]);

        $incasare = 0.0;
        $marfa = 0.0;
        $carburant = 0.0;
        $linii = [];

        foreach ($stmt->fetchAll() as $l) {
            $candidate = $optimizare->ruteOptimizate($l['Product_ID'], $comanda['ClientOras']);
            $ruta = $candidate ? $candidate[0] : null;

            $costLinie = $ruta === null
                ? 0.0
                : $this->costMarfaLinie($l['Product_ID'], (int) $l['Cantitate'], $ruta['Oras_origine']);
            $carburantLinie = $ruta === null ? 0.0 : (float) $ruta['cost_carburant'];

            $incasare += (float) $l['Subtotal'];
            $marfa += $costLinie;
            $carburant += $carburantLinie;

            $linii[] = [
                'produs' => $l['Product_Name'],
                'cantitate' => (int) $l['Cantitate'],
                'subtotal' => (float) $l['Subtotal'],
                'depozit' => $ruta === null ? null : $ruta['Oras_origine'],
                'ruta' => $ruta === null ? null : $ruta['Oras_origine'] . ' → ' . $ruta['Oras_destinatie'],
                'km' => $ruta === null ? 0 : (int) $ruta['Distanta_km'],
                'marfa' => $costLinie,
                'carburant' => $carburantLinie,
                'profit' => (float) $l['Subtotal'] - $costLinie - $carburantLinie,
            ];
        }

        $cost = $marfa + $carburant;

        $this->situatii[$cheie] = [
            'incasare' => $incasare,
            'marfa' => $marfa,
            'carburant' => $carburant,
            'cost' => $cost,
            'profit' => $incasare - $cost,
            'linii' => $linii,
        ];

        return $this->situatii[$cheie];
    }

    /**
     * Depozitele din care ar pleca o comanda care inca n-a fost expediata, in
     * ordine alfabetica si fara repetitii. Sunt cele pe care le-ar alege
     * algoritmul pentru produsele ei, deci o estimare, nu un fapt.
     *
     * @return string[] gol daca niciun produs n-are stoc nicaieri
     */
    public function depoziteEstimate(array $comanda, OptimizareRuteService $optimizare)
    {
        $depozite = [];

        foreach ($this->situatieFinanciara($comanda, $optimizare)['linii'] as $linie) {
            if ($linie['depozit'] !== null) {
                $depozite[$linie['depozit']] = true;
            }
        }

        $lista = array_keys($depozite);
        sort($lista);

        return $lista;
    }

    /**
     * Costul de achizitie al unei linii, luat de la depozitul de plecare (cel
     * mai recent rand de stoc de acolo), cu revenire pe cel mai recent rand al
     * produsului daca depozitul acela nu-l are inregistrat.
     */
    private function costMarfaLinie($productId, $cantitate, $orasDepozit)
    {
        $depozit = array_search($orasDepozit, OptimizareRuteService::DEPOZITE, true);
        $cost = false;

        if ($depozit !== false) {
            $q = $this->pdo->prepare(
                'SELECT Cost_Unitar FROM inventory
                 WHERE Product_ID = :pid AND depozit = :dep
                 ORDER BY `Date` DESC LIMIT 1'
            );
            $q->execute(['pid' => $productId, 'dep' => $depozit]);
            $cost = $q->fetchColumn();
        }

        if ($cost === false || $cost === null) {
            $q = $this->pdo->prepare(
                'SELECT Cost_Unitar FROM inventory WHERE Product_ID = :pid ORDER BY `Date` DESC LIMIT 1'
            );
            $q->execute(['pid' => $productId]);
            $cost = $q->fetchColumn();
        }

        return $cost === false || $cost === null ? 0.0 : (float) $cost * $cantitate;
    }

    /**
     * Randul din `comenzi_financiar`, daca decizia a fost deja luata.
     *
     * @return array|null
     */
    public function financiarInregistrat($comandaId)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM comenzi_financiar WHERE ComandaID = :id');
        $stmt->execute(['id' => (int) $comandaId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * "Trimite": inregistreaza estimarea financiara a comenzii si o trece pe
     * "In procesare", adica aprobata pentru expediere. Expedierile propriu-zise
     * se creeaza mai departe din `expediere_comanda.php`.
     *
     * Idempotent: a doua apasare rescrie acelasi rand, nu adauga altul.
     *
     * @return bool false daca comanda nu mai e deschisa
     */
    public function trimite(array $comanda, OptimizareRuteService $optimizare)
    {
        if (!$this->esteDeschisa($comanda)) {
            return false;
        }

        $f = $this->situatieFinanciara($comanda, $optimizare);

        $this->pdo->prepare(
            'INSERT INTO comenzi_financiar
                (ComandaID, incasare, cost_marfa, cost_carburant, profit, Data_inregistrare)
             VALUES (:id, :inc, :marfa, :carb, :profit, NOW())
             ON DUPLICATE KEY UPDATE
                incasare = VALUES(incasare),
                cost_marfa = VALUES(cost_marfa),
                cost_carburant = VALUES(cost_carburant),
                profit = VALUES(profit),
                Data_inregistrare = VALUES(Data_inregistrare)'
        )->execute([
            'id' => (int) $comanda['ComandaID'],
            'inc' => $f['incasare'],
            'marfa' => $f['marfa'],
            'carb' => $f['carburant'],
            'profit' => $f['profit'],
        ]);

        $this->pdo->prepare('UPDATE comenzi SET Status = :s WHERE ComandaID = :id')
            ->execute(['s' => 'In procesare', 'id' => (int) $comanda['ComandaID']]);

        return true;
    }

    /**
     * "Anuleaza": comanda trece pe "Anulata" si nu i se inregistreaza nicio
     * cifra financiara (daca exista deja un rand, se sterge). Nu se atinge
     * stocul: comanda n-a expediat nimic, deci n-a scazut nimic.
     *
     * @return bool false daca comanda nu mai e deschisa
     */
    public function anuleaza(array $comanda)
    {
        if (!$this->esteDeschisa($comanda)) {
            return false;
        }

        $id = (int) $comanda['ComandaID'];

        $this->pdo->prepare('UPDATE comenzi SET Status = :s WHERE ComandaID = :id')
            ->execute(['s' => 'Anulata', 'id' => $id]);

        $this->pdo->prepare('DELETE FROM comenzi_financiar WHERE ComandaID = :id')
            ->execute(['id' => $id]);

        return true;
    }
}
