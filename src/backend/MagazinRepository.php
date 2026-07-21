<?php

/**
 * Logica magazinului: catalogul de produse si plasarea comenzilor.
 *
 * Catalogul se construieste din tabela `inventory`, care e un istoric lunar de
 * stoc. Pentru magazin ne intereseaza doar cea mai recenta luna a fiecarui
 * produs (stocul si pretul curent), nu tot istoricul.
 *
 * Pretul afisat clientului este `Unit_Cost` din inventory. Comanda nu modifica
 * stocul (inventory ramane istoric), dar verifica sa nu se comande mai mult
 * decat stocul disponibil.
 */
class MagazinRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Doar randul cel mai recent al fiecarui produs (stocul si pretul curent).
     */
    private function currentFrom()
    {
        return 'FROM inventory i
                WHERE i.`Date` = (
                    SELECT MAX(i2.`Date`) FROM inventory i2 WHERE i2.Product_ID = i.Product_ID
                )';
    }

    /**
     * Produsele curente, optional filtrate dupa text si/sau categorie.
     *
     * @return array[] fiecare cu Product_ID, Product_Name, Category, Stock_Level, Unit_Cost
     */
    public function catalog($search = '', $categorie = '')
    {
        // Poza tine de produs, nu de luna de stoc: o luam de pe cel mai recent
        // rand al produsului care are poza setata (indiferent de luna curenta).
        $sql = 'SELECT i.Product_ID, i.Product_Name, i.Category, i.Stock_Level, i.Unit_Cost,
                (SELECT ip.poze FROM inventory ip
                  WHERE ip.Product_ID = i.Product_ID AND ip.poze IS NOT NULL AND ip.poze <> \'\'
                  ORDER BY ip.`Date` DESC LIMIT 1) AS poze '
            . $this->currentFrom();
        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $sql .= ' AND (i.Product_Name LIKE :s1 OR i.Product_ID LIKE :s2 OR i.Category LIKE :s3)';
            $term = '%' . $search . '%';
            $params['s1'] = $term;
            $params['s2'] = $term;
            $params['s3'] = $term;
        }

        $categorie = trim($categorie);
        if ($categorie !== '') {
            $sql .= ' AND i.Category = :cat';
            $params['cat'] = $categorie;
        }

        $sql .= ' ORDER BY i.Category, i.Product_Name';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Categoriile distincte ale produselor curente.
     *
     * @return string[]
     */
    public function categorii()
    {
        $sql = 'SELECT DISTINCT i.Category ' . $this->currentFrom() . ' ORDER BY i.Category';

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Produsele curente pentru un set de coduri, indexate dupa Product_ID.
     * Folosit la validarea si evaluarea cosului.
     *
     * @param string[] $codes
     * @return array<string, array>
     */
    public function produseCurente(array $codes)
    {
        $codes = array_values(array_unique(array_filter($codes, function ($c) {
            return $c !== '';
        })));

        if (!$codes) {
            return [];
        }

        // Placeholder unic per cod (EMULATE_PREPARES e dezactivat).
        $placeholders = [];
        $params = [];
        foreach ($codes as $i => $code) {
            $key = 'c' . $i;
            $placeholders[] = ':' . $key;
            $params[$key] = $code;
        }

        $sql = 'SELECT i.Product_ID, i.Product_Name, i.Category, i.Stock_Level, i.Unit_Cost '
            . $this->currentFrom()
            . ' AND i.Product_ID IN (' . implode(', ', $placeholders) . ')';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[$row['Product_ID']] = $row;
        }

        return $map;
    }

    /**
     * Clientii pentru dropdown-ul de la finalizarea comenzii.
     *
     * @return array[] cu id si text
     */
    public function clientiOptiuni()
    {
        return $this->pdo
            ->query('SELECT ClientID AS id, Nume AS text FROM clienti ORDER BY Nume')
            ->fetchAll();
    }

    /**
     * Plaseaza o comanda pentru un client.
     *
     * @param mixed  $clientId
     * @param array  $items       harta Product_ID => cantitate
     * @param string $observatii
     * @return array ['errors' => string[], 'id' => int|null]
     */
    public function creeazaComanda($clientId, array $items, $observatii = '')
    {
        $errors = [];

        $clientId = filter_var($clientId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($clientId === false) {
            $errors[] = 'Alege un client valid.';
        } else {
            $stmt = $this->pdo->prepare('SELECT 1 FROM clienti WHERE ClientID = :id');
            $stmt->execute(['id' => $clientId]);
            if ($stmt->fetchColumn() === false) {
                $errors[] = 'Clientul selectat nu exista.';
            }
        }

        // Pastreaza doar liniile cu cantitate intreaga pozitiva.
        $curatate = [];
        foreach ($items as $code => $qty) {
            $qty = filter_var($qty, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($qty !== false) {
                $curatate[$code] = $qty;
            }
        }

        if (!$curatate) {
            $errors[] = 'Cosul este gol.';
            return ['errors' => $errors, 'id' => null];
        }

        $produse = $this->produseCurente(array_keys($curatate));

        $linii = [];
        $total = 0;
        foreach ($curatate as $code => $qty) {
            if (!isset($produse[$code])) {
                $errors[] = 'Produsul „' . $code . '” nu mai este disponibil.';
                continue;
            }

            $p = $produse[$code];
            if ($qty > (int) $p['Stock_Level']) {
                $errors[] = 'Stoc insuficient pentru „' . $p['Product_Name'] . '” (disponibil: ' . (int) $p['Stock_Level'] . ').';
                continue;
            }

            $pret = (float) $p['Unit_Cost'];
            $subtotal = round($pret * $qty, 2);
            $total += $subtotal;

            $linii[] = [
                'Product_ID' => $p['Product_ID'],
                'Product_Name' => $p['Product_Name'],
                'Pret_unitar' => $pret,
                'Cantitate' => $qty,
                'Subtotal' => $subtotal,
            ];
        }

        $observatii = trim((string) $observatii);
        if (mb_strlen($observatii) > 500) {
            $errors[] = 'Observatiile pot avea maxim 500 de caractere.';
        }

        if ($errors) {
            return ['errors' => $errors, 'id' => null];
        }

        // Comanda + liniile ei intr-o singura tranzactie.
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                'INSERT INTO comenzi (ClientID, Data_comanda, Status, Total, Observatii)
                 VALUES (:client, NOW(), :status, :total, :obs)'
            );
            $stmt->execute([
                'client' => $clientId,
                'status' => 'Noua',
                'total' => round($total, 2),
                'obs' => $observatii === '' ? null : $observatii,
            ]);

            $comandaId = (int) $this->pdo->lastInsertId();

            $stmtLinie = $this->pdo->prepare(
                'INSERT INTO comenzi_produse (ComandaID, Product_ID, Product_Name, Pret_unitar, Cantitate, Subtotal)
                 VALUES (:comanda, :pid, :pname, :pret, :cant, :sub)'
            );

            foreach ($linii as $l) {
                $stmtLinie->execute([
                    'comanda' => $comandaId,
                    'pid' => $l['Product_ID'],
                    'pname' => $l['Product_Name'],
                    'pret' => $l['Pret_unitar'],
                    'cant' => $l['Cantitate'],
                    'sub' => $l['Subtotal'],
                ]);
            }

            $this->pdo->commit();

            return ['errors' => [], 'id' => $comandaId];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return ['errors' => ['Nu s-a putut salva comanda. Incearca din nou.'], 'id' => null];
        }
    }
}
