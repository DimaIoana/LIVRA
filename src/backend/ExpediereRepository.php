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
    const STATUSURI = ['In tranzit', 'Livrat', 'Returnat', 'Intarziat'];

    /** Statusuri la care coletul nu a ajuns inca la destinatar. */
    const STATUSURI_NELIVRAT = ['In tranzit', 'Returnat'];

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
        return 'SELECT e.ExpediereID, e.awb, e.ClientID, e.SoferID, e.RutaID,
                       e.Data_expediere, e.Data_livrare_estimata, e.Data_livrare_efectiva,
                       e.Status_expediere, e.Valoare_expediere,
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
