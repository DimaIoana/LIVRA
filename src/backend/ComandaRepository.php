<?php

require_once __DIR__ . '/BaseRepository.php';

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
        return ['ComandaID', 'ClientNume', 'Data_comanda', 'Status', 'Total'];
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
                       cl.Nume AS ClientNume,
                       (SELECT GROUP_CONCAT(CONCAT(cp.Cantitate, \' x \', cp.Product_Name)
                                            ORDER BY cp.LinieID SEPARATOR \', \')
                          FROM comenzi_produse cp WHERE cp.ComandaID = c.ComandaID) AS Produse,
                       (SELECT COUNT(*) FROM comenzi_produse cp
                         WHERE cp.ComandaID = c.ComandaID) AS NrLinii,
                       (SELECT COALESCE(SUM(cp.Subtotal), 0) FROM comenzi_produse cp
                         WHERE cp.ComandaID = c.ComandaID) AS TotalLinii,
                       (SELECT COUNT(*) FROM expedieri e
                           JOIN comenzi_produse cp ON cp.LinieID = e.LinieID
                          WHERE cp.ComandaID = c.ComandaID) AS NrExpediate
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
}
