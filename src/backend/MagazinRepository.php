<?php

require_once __DIR__ . '/StocCurent.php';

/**
 * Logica magazinului: catalogul de produse si plasarea comenzilor.
 *
 * Ce vede clientul in magazin e catalogul, adica tabela `produse`: cate un rand
 * pe produs, cu numele, categoria, pretul de vanzare (`Unit_Cost`) si poza.
 * Magazinul nu inventeaza si nu filtreaza lista - arata exact catalogul, deci ce
 * se adauga in "Catalog de produse" din back office apare aici.
 *
 * Singurul lucru luat din alta parte e stocul: el sta in `inventory`, cate un
 * rand pe produs, luna si depozit, legat de catalog prin `Product_ID`. Stocul de
 * acum se calculeaza dupa regula unica din StocCurent, aceeasi folosita de
 * catalogul din back office, ca magazinul si catalogul sa nu poata arata numere
 * diferite pentru acelasi produs.
 *
 * Comanda nu modifica stocul (inventory ramane istoric), dar verifica sa nu se
 * comande mai mult decat stocul disponibil.
 */
class MagazinRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * SELECT-ul comun: produsul din catalog plus stocul lui de acum.
     *
     * Stocul vine din `inventory` dupa regula unica din StocCurent (suma peste
     * depozite a ultimului rand din fiecare depozit), aceeasi pe care o
     * foloseste si catalogul din back office - altfel un produs aflat in trei
     * depozite ar arata in magazin doar cat e intr-unul.
     *
     * Un produs din catalog fara nicio luna de stoc iese cu 0, nu dispare:
     * exista in catalog, doar ca e epuizat.
     */
    private function selectProdus()
    {
        return 'SELECT p.Product_ID, p.Product_Name, p.Category, p.Unit_Cost, p.poze,
                       COALESCE(s.Stoc_total, 0) AS Stock_Level
                  FROM produse p
                  LEFT JOIN (' . StocCurent::subinterogare() . ') s
                         ON s.Product_ID = p.Product_ID';
    }

    /**
     * Produsele din catalog, optional filtrate dupa text si/sau categorie.
     *
     * @return array[] fiecare cu Product_ID, Product_Name, Category, Unit_Cost, poze, Stock_Level
     */
    public function catalog($search = '', $categorie = '')
    {
        $sql = $this->selectProdus() . ' WHERE 1';
        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $sql .= ' AND (p.Product_Name LIKE :s1 OR p.Product_ID LIKE :s2 OR p.Category LIKE :s3)';
            $term = '%' . $search . '%';
            $params['s1'] = $term;
            $params['s2'] = $term;
            $params['s3'] = $term;
        }

        $categorie = trim($categorie);
        if ($categorie !== '') {
            $sql .= ' AND p.Category = :cat';
            $params['cat'] = $categorie;
        }

        $sql .= ' ORDER BY p.Category, p.Product_Name';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Categoriile distincte din catalog.
     *
     * @return string[]
     */
    public function categorii()
    {
        return $this->pdo
            ->query('SELECT DISTINCT Category FROM produse ORDER BY Category')
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Produsele din catalog pentru un set de coduri, indexate dupa Product_ID.
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

        $sql = $this->selectProdus()
            . ' WHERE p.Product_ID IN (' . implode(', ', $placeholders) . ')';

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
