<?php

require_once __DIR__ . '/BaseRepository.php';

/**
 * Acces la date pentru tabela `expedieri`.
 *
 * Are chei straine catre clienti, soferi si rute, deci listarea face JOIN ca sa
 * afiseze nume in loc de ID-uri.
 */
class ExpediereRepository extends BaseRepository
{
    const STATUSURI = ['In tranzit', 'Livrat', 'Returnat', 'Intarziat', 'Anulat'];

    /** Statusuri la care coletul nu a ajuns inca la destinatar. */
    const STATUSURI_NELIVRAT = ['In tranzit', 'Returnat', 'Anulat'];

    /** Orasul depozitului (origine ruta) -> codul de depozit din inventory. */
    const DEPOZIT_COD = ['Arad' => 1, 'Braila' => 2, 'Pitesti' => 3];

    protected function table()
    {
        return 'expedieri';
    }

    protected function primaryKey()
    {
        return 'ExpediereID';
    }

    protected function columns()
    {
        return [
            'ClientID', 'SoferID', 'RutaID', 'Data_expediere',
            'Data_livrare_estimata', 'Data_livrare_efectiva',
            'Status_expediere', 'Valoare_expediere',
        ];
    }

    protected function sortableColumns()
    {
        return [
            'ExpediereID', 'awb', 'ClientNume', 'SoferNume', 'Ruta', 'Data_expediere',
            'Data_livrare_estimata', 'Data_livrare_efectiva',
            'Status_expediere', 'Valoare_expediere',
        ];
    }

    protected function searchableColumns()
    {
        return [];
    }

    /**
     * Aduce si numele clientului/soferului si descrierea rutei, pentru afisare.
     */
    protected function selectFrom()
    {
        return 'SELECT e.ExpediereID, e.awb, e.ClientID, e.SoferID, e.RutaID, e.LinieID,
                       e.Data_expediere, e.Data_livrare_estimata, e.Data_livrare_efectiva,
                       e.Status_expediere, e.Valoare_expediere, e.cost_carburant, e.stoc_scazut,
                       c.Nume AS ClientNume,
                       s.Nume AS SoferNume,
                       CONCAT(r.Oras_origine, " - ", r.Oras_destinatie) AS Ruta
                FROM expedieri e
                JOIN clienti c ON c.ClientID = e.ClientID
                JOIN soferi s ON s.SoferID = e.SoferID
                JOIN rute r ON r.RutaID = e.RutaID';
    }

    public function getById($id)
    {
        $stmt = $this->pdo->prepare($this->selectFrom() . ' WHERE e.ExpediereID = :id');
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Cautarea acopera nume de client/sofer si orasele rutei, deci se scrie
     * manual peste coloanele din JOIN.
     */
    public function getAll($search = '', $sort = null, $dir = 'desc')
    {
        $sql = $this->selectFrom();
        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $sql .= ' WHERE c.Nume LIKE :s1 OR s.Nume LIKE :s2
                        OR r.Oras_origine LIKE :s3 OR r.Oras_destinatie LIKE :s4
                        OR e.Status_expediere LIKE :s5 OR e.awb LIKE :s6';
            $term = '%' . $search . '%';
            $params = ['s1' => $term, 's2' => $term, 's3' => $term, 's4' => $term, 's5' => $term, 's6' => $term];
        }

        if (!in_array($sort, $this->sortableColumns(), true)) {
            $sort = 'ExpediereID';
        }

        $dir = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';

        $sql .= ' ORDER BY ' . $this->quote($sort) . ' ' . $dir;

        if ($sort !== 'ExpediereID') {
            $sql .= ', e.ExpediereID DESC';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function validate(array $input)
    {
        $errors = [];

        $client_id = $this->validForeignKey($errors, $input, 'ClientID', 'Clientul', 'clienti', 'ClientID');
        $sofer_id = $this->validForeignKey($errors, $input, 'SoferID', 'Soferul', 'soferi', 'SoferID');
        $ruta_id = $this->validForeignKey($errors, $input, 'RutaID', 'Ruta', 'rute', 'RutaID');

        $status = $this->validEnum($errors, $input, 'Status_expediere', 'Statusul', self::STATUSURI);

        $data_expediere = $this->validDateTime($errors, $input, 'Data_expediere', 'Data expedierii');
        $data_estimata = $this->validDateTime($errors, $input, 'Data_livrare_estimata', 'Data livrarii estimate');

        // Optionala: un colet in tranzit nu are inca data de livrare efectiva.
        $data_efectiva = $this->validDateTime($errors, $input, 'Data_livrare_efectiva', 'Data livrarii efective', false);
        if ($data_efectiva === '') {
            $data_efectiva = null;
        }

        $valoare = $this->validDecimal($errors, $input, 'Valoare_expediere', 'Valoarea expedierii', 0);

        if (!$errors) {
            if ($data_estimata < $data_expediere) {
                $errors[] = 'Data livrarii estimate nu poate fi inainte de data expedierii.';
            }

            if ($data_efectiva !== null && $data_efectiva < $data_expediere) {
                $errors[] = 'Data livrarii efective nu poate fi inainte de data expedierii.';
            }

            if ($status === 'Livrat' && $data_efectiva === null) {
                $errors[] = 'O expediere livrata trebuie sa aiba data livrarii efective.';
            }

            if (in_array($status, self::STATUSURI_NELIVRAT, true) && $data_efectiva !== null) {
                $errors[] = 'O expediere "' . $status . '" nu poate avea data de livrare efectiva.';
            }
        }

        return [
            'errors' => $errors,
            'data' => [
                'ClientID' => $client_id,
                'SoferID' => $sofer_id,
                'RutaID' => $ruta_id,
                'Data_expediere' => $data_expediere,
                'Data_livrare_estimata' => $data_estimata,
                'Data_livrare_efectiva' => $data_efectiva,
                'Status_expediere' => $status,
                'Valoare_expediere' => $valoare,
            ],
        ];
    }

    /**
     * La creare/modificare, sincronizeaza si stocul: cand expedierea e livrata,
     * cantitatea livrata se scade din inventory (o singura data).
     */
    public function create(array $data)
    {
        $id = parent::create($data);
        $this->sincronizeazaStoc($id);

        return $id;
    }

    public function update($id, array $data)
    {
        $ok = parent::update($id, $data);
        if ($ok) {
            $this->sincronizeazaStoc($id);
        }

        return $ok;
    }

    /**
     * Daca expedierea e "Livrat" si stocul nu a fost inca scazut, scade
     * cantitatea livrata din inventory (produsul, la depozitul de plecare al
     * rutei) si marcheaza expedierea ca stoc_scazut. Idempotent.
     */
    private function sincronizeazaStoc($id)
    {
        $stmt = $this->pdo->prepare(
            'SELECT Status_expediere, stoc_scazut, LinieID, RutaID
             FROM expedieri WHERE ExpediereID = :id'
        );
        $stmt->execute(['id' => $id]);
        $e = $stmt->fetch();

        // Se scade doar la livrare, o singura data, si doar daca stim linia comenzii.
        if ($e === false
            || $e['Status_expediere'] !== 'Livrat'
            || (int) $e['stoc_scazut'] === 1
            || $e['LinieID'] === null) {
            return;
        }

        // Produsul + cantitatea livrata, si depozitul de plecare (origine ruta).
        $l = $this->pdo->prepare('SELECT Product_ID, Cantitate FROM comenzi_produse WHERE LinieID = :lid');
        $l->execute(['lid' => $e['LinieID']]);
        $linie = $l->fetch();

        $r = $this->pdo->prepare('SELECT Oras_origine FROM rute WHERE RutaID = :rid');
        $r->execute(['rid' => $e['RutaID']]);
        $orasDepozit = $r->fetchColumn();
        $depozit = self::DEPOZIT_COD[$orasDepozit] ?? null;

        $tranzactieProprie = !$this->pdo->inTransaction();
        if ($tranzactieProprie) {
            $this->pdo->beginTransaction();
        }

        try {
            if ($linie !== false && $depozit !== null) {
                // Cel mai recent rand de stoc al produsului, la acel depozit.
                $inv = $this->pdo->prepare(
                    'SELECT InventoryID FROM inventory
                     WHERE Product_ID = :pid AND depozit = :dep
                     ORDER BY `Date` DESC LIMIT 1'
                );
                $inv->execute(['pid' => $linie['Product_ID'], 'dep' => $depozit]);
                $inventoryId = $inv->fetchColumn();

                if ($inventoryId !== false) {
                    $this->pdo->prepare(
                        'UPDATE inventory
                         SET Stock_Level = GREATEST(0, Stock_Level - :qty)
                         WHERE InventoryID = :id'
                    )->execute(['qty' => (int) $linie['Cantitate'], 'id' => $inventoryId]);
                }
            }

            // Marcheaza chiar daca n-am gasit rand de stoc, ca sa nu reincercam.
            $this->pdo->prepare('UPDATE expedieri SET stoc_scazut = 1 WHERE ExpediereID = :id')
                ->execute(['id' => $id]);

            if ($tranzactieProprie) {
                $this->pdo->commit();
            }
        } catch (Throwable $ex) {
            if ($tranzactieProprie && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
        }
    }

    /**
     * Cat costa marfa dintr-o linie de comanda daca pleaca dintr-un anumit
     * depozit: cantitatea x costul de achizitie de acolo (cel mai recent rand de
     * stoc). Daca depozitul acela n-are inregistrat produsul, se ia cel mai
     * recent rand al produsului, oriunde ar fi.
     *
     * Se foloseste si pentru simulari ("cat ar fi costat marfa daca pleca din
     * alt depozit"), de aceea orasul e parametru, nu se citeste din expediere.
     *
     * @return float 0 daca linia nu exista sau produsul n-are cost inregistrat
     */
    public function costMarfa($linieId, $orasDepozit)
    {
        $stmt = $this->pdo->prepare('SELECT Product_ID, Cantitate FROM comenzi_produse WHERE LinieID = :id');
        $stmt->execute(['id' => (int) $linieId]);
        $linie = $stmt->fetch();

        if ($linie === false) {
            return 0.0;
        }

        $depozit = self::DEPOZIT_COD[$orasDepozit] ?? null;
        $cost = false;

        if ($depozit !== null) {
            $q = $this->pdo->prepare(
                'SELECT Cost_Unitar FROM inventory
                 WHERE Product_ID = :pid AND depozit = :dep
                 ORDER BY `Date` DESC LIMIT 1'
            );
            $q->execute(['pid' => $linie['Product_ID'], 'dep' => $depozit]);
            $cost = $q->fetchColumn();
        }

        if ($cost === false || $cost === null) {
            $q = $this->pdo->prepare(
                'SELECT Cost_Unitar FROM inventory
                 WHERE Product_ID = :pid ORDER BY `Date` DESC LIMIT 1'
            );
            $q->execute(['pid' => $linie['Product_ID']]);
            $cost = $q->fetchColumn();
        }

        return $cost === false || $cost === null
            ? 0.0
            : (float) $cost * (int) $linie['Cantitate'];
    }

    /**
     * Optiunile pentru dropdown-urile din formular.
     */
    public function getOptiuni()
    {
        return [
            'clienti' => $this->pdo->query('SELECT ClientID AS id, Nume AS text FROM clienti ORDER BY Nume')->fetchAll(),
            'soferi' => $this->pdo->query('SELECT SoferID AS id, Nume AS text FROM soferi ORDER BY Nume')->fetchAll(),
            'rute' => $this->pdo->query(
                'SELECT RutaID AS id, CONCAT(Oras_origine, " - ", Oras_destinatie, " (", Distanta_km, " km)") AS text
                 FROM rute ORDER BY Oras_origine, Oras_destinatie'
            )->fetchAll(),
            'statusuri' => self::STATUSURI,
        ];
    }
}
