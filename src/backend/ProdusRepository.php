<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/StocCurent.php';

/**
 * Acces la date pentru tabela `produse` - catalogul.
 *
 * Cate un rand pe produs: codul, numele, categoria, pretul de vanzare si poza.
 * Stocul nu e aici; el sta in `inventory`, cate un rand pe produs si luna, legat
 * prin `Product_ID` (cheie straina). Deci aici se raspunde la "ce produse
 * vindem", iar in `inventory` la "cate bucati avem, cand si in ce depozit".
 *
 * Codul de produs (`Product_ID`) e unic in baza, deci nu mai pot exista doua
 * produse cu acelasi cod. Cheia primara ramane `ProdusID`, un numar, pentru ca
 * pagina CRUD generica lucreaza cu chei primare numerice.
 */
class ProdusRepository extends BaseRepository
{
    /** Prefixul codului de produs; dupa el urmeaza numarul, cu CIFRE_COD cifre. */
    const PREFIX_COD = 'PRD-';
    const CIFRE_COD = 4;

    protected function table()
    {
        return 'produse';
    }

    protected function primaryKey()
    {
        return 'ProdusID';
    }

    protected function columns()
    {
        return ['Product_ID', 'Product_Name', 'Category', 'Unit_Cost', 'poze'];
    }

    /**
     * Listarea aduce si stocul, ca sa se vada in catalog ce produse avem, cat
     * stoc si la ce pret - tot pe un rand.
     *
     * Stocul nu e coloana a catalogului si nu se copiaza aici: se calculeaza din
     * `inventory` dupa regula din StocCurent, adica suma peste depozite a
     * ultimului rand din fiecare depozit. Asa catalogul si magazinul arata
     * acelasi numar. Un produs fara nicio luna de stoc iese cu 0, nu dispare.
     */
    protected function selectFrom()
    {
        return 'SELECT `produse`.`ProdusID`, `produse`.`Product_ID`, `produse`.`Product_Name`,
                       `produse`.`Category`, `produse`.`Unit_Cost`, `produse`.`poze`,
                       COALESCE(s.Stoc_total, 0) AS `Stock_Level`,
                       COALESCE(s.Stoc_1, 0) AS `Stoc_Arad`,
                       COALESCE(s.Stoc_2, 0) AS `Stoc_Braila`,
                       COALESCE(s.Stoc_3, 0) AS `Stoc_Pitesti`,
                       COALESCE(s.Depozite_cu_stoc, 0) AS `Depozite`,
                       (SELECT COUNT(*) FROM inventory i3
                         WHERE i3.Product_ID = `produse`.`Product_ID`) AS `Luni_stoc`
                  FROM `produse`
                  LEFT JOIN (' . StocCurent::subinterogare() . ') s
                         ON s.Product_ID = `produse`.`Product_ID`';
    }

    protected function sortableColumns()
    {
        return ['ProdusID', 'Product_ID', 'Product_Name', 'Category', 'Unit_Cost',
                'Stock_Level', 'Stoc_Arad', 'Stoc_Braila', 'Stoc_Pitesti',
                'Depozite', 'Luni_stoc'];
    }

    protected function searchableColumns()
    {
        // Calificate cu numele tabelei: listarea face LEFT JOIN cu stocul, iar
        // tabela derivata are si ea Product_ID - fara prefix, WHERE e ambiguu.
        return ['produse.Product_ID', 'produse.Product_Name', 'produse.Category'];
    }

    protected function defaultSort()
    {
        return 'Product_ID';
    }

    /**
     * Urmatorul cod de produs liber: PRD-0001, PRD-0002, ... Se ia numarul cel
     * mai mare folosit si se adauga unu, deci codurile cresc mereu si nu se
     * refolosesc nici dupa stergerea unui produs.
     *
     * Codurile care nu respecta formatul (daca a scris cineva ceva de mana) sunt
     * ignorate la numarat, dar raman in tabela; ele nu pot bloca generarea.
     */
    public function codNou()
    {
        $stmt = $this->pdo->prepare(
            'SELECT MAX(CAST(SUBSTRING(Product_ID, :start) AS UNSIGNED))
             FROM produse
             WHERE Product_ID LIKE :prefix'
        );
        $stmt->execute([
            'start' => strlen(self::PREFIX_COD) + 1,
            'prefix' => self::PREFIX_COD . '%',
        ]);

        $ultim = (int) $stmt->fetchColumn();

        return self::PREFIX_COD . str_pad($ultim + 1, self::CIFRE_COD, '0', STR_PAD_LEFT);
    }

    /**
     * Produsul care poarta deja codul asta, sau null daca e liber.
     * $exceptaId lasa produsul curent afara, ca la editare sa nu se acuze singur.
     *
     * @return array|null
     */
    private function dupaCod($cod, $exceptaId = null)
    {
        $sql = 'SELECT ProdusID, Product_Name FROM produse WHERE Product_ID = :cod';
        $params = ['cod' => $cod];

        if ($exceptaId !== null) {
            $sql .= ' AND ProdusID <> :id';
            $params['id'] = $exceptaId;
        }

        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Produsele pentru dropdown-ul din Control de stocks: cod + nume, ca sa se
     * vada ce se alege. Valoarea trimisa e codul, adica exact ce se scrie in
     * `inventory.Product_ID`.
     *
     * @return array[] cu id (codul) si text
     */
    public function optiuni()
    {
        return $this->pdo
            ->query('SELECT Product_ID AS id, CONCAT(Product_ID, " - ", Product_Name) AS text
                     FROM produse ORDER BY Product_ID')
            ->fetchAll();
    }

    /**
     * Salveaza produsul impreuna cu prima lui inregistrare de stoc.
     *
     * Un produs nou intra mereu cu stoc de pornire si depozit: de acolo incepe
     * evidenta din Control de stocks, cea din care se scade pe masura ce
     * produsul se consuma (vezi ExpediereRepository, care scade cantitatea
     * livrata din depozitul de plecare). Un produs fara inregistrare de stoc
     * n-ar avea din ce sa se scada, deci nu se poate crea.
     *
     * Stocul nu se scrie in catalog - el sta tot in `inventory`, ca orice alta
     * luna de stoc. Aici doar se face randul de pornire, cu data de azi.
     *
     * Cele doua scrieri sunt intr-o tranzactie: daca randul de stoc nu intra, nu
     * ramane nici produsul pe jumatate creat.
     */
    public function create(array $data)
    {
        $stoc = (int) ($data['Stoc_initial'] ?? 0);
        $depozit = (int) ($data['Depozit_initial'] ?? 0);

        // validate() opreste asta inainte sa se ajunga aici; daca totusi se
        // ajunge, e o greseala de programare si trebuie sa se vada, nu sa lase
        // in urma un produs fara stoc.
        if (!isset(StocCurent::DEPOZITE[$depozit])) {
            throw new InvalidArgumentException(
                'Un produs nou are nevoie de depozit pentru stocul de pornire.'
            );
        }

        $tranzactieProprie = !$this->pdo->inTransaction();
        if ($tranzactieProprie) {
            $this->pdo->beginTransaction();
        }

        try {
            // Se citeste inainte de a scrie in inventory: altfel lastInsertId()
            // ar da randul de stoc, nu produsul.
            $produsId = parent::create($data);

            $this->pdo->prepare(
                'INSERT INTO inventory (Product_ID, Stock_Level, Reorder_Point, Monthly_Sales,
                                        Cost_Unitar, `Date`, depozit)
                 VALUES (:pid, :stoc, 0, 0, NULL, :data, :dep)'
            )->execute([
                'pid' => $data['Product_ID'],
                'stoc' => $stoc,
                'data' => date('Y-m-d'),
                'dep' => $depozit,
            ]);

            if ($tranzactieProprie) {
                $this->pdo->commit();
            }

            return $produsId;
        } catch (Throwable $e) {
            if ($tranzactieProprie && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function validate(array $input)
    {
        $errors = [];

        $product_name = $this->validText($errors, $input, 'Product_Name', 'Numele produsului', 100);

        // La editare stim ce produs e, ca sa nu-si vada propriul cod ca "ocupat".
        // Lipsa cheii primare inseamna produs nou, si numai atunci se cere stocul
        // de pornire (formularul de editare nici nu-l arata).
        $produsId = trim((string) ($input[$this->primaryKey()] ?? ''));
        $produsId = $produsId === '' ? null : (int) $produsId;
        $esteAdaugare = $produsId === null;

        // Codul: lasat gol inseamna "produs nou", deci se genereaza urmatorul.
        // Formularul il vine oricum precompletat; golul e plasa de siguranta.
        $product_id = trim((string) ($input['Product_ID'] ?? ''));

        if ($product_id === '') {
            $product_id = $this->codNou();
        } else {
            $product_id = $this->validText($errors, $input, 'Product_ID', 'Codul produsului', 10);
        }

        // Codul e unic in baza (cheie unica); verificam si aici ca utilizatorul sa
        // primeasca un mesaj clar, nu o eroare de baza de date.
        if (!$errors && $product_id !== '') {
            $altul = $this->dupaCod($product_id, $produsId);

            if ($altul !== null) {
                $errors[] = 'Codul "' . $product_id . '" este deja al produsului "'
                    . $altul['Product_Name'] . '". Lasa campul gol ca sa primesti un cod nou ('
                    . $this->codNou() . ').';
            }
        }

        $category = $this->validText($errors, $input, 'Category', 'Categoria', 50);

        // Pretul de vanzare, cel afisat clientului in magazin.
        $unit_cost = $this->validDecimal($errors, $input, 'Unit_Cost', 'Pretul unitar', 0);

        // Poza: numele fisierului deja mutat in folderul `poze/` (setat de
        // stratul de upload din _crud_page.php). Optionala.
        $poze = trim((string) ($input['poze'] ?? ''));
        if (mb_strlen($poze) > 200) {
            $errors[] = 'Numele pozei poate avea maxim 200 de caractere.';
        }

        // Stocul de pornire se cere numai la produs nou (campuri 'onlyOnAdd'),
        // dar acolo e obligatoriu: el creeaza prima inregistrare din Control de
        // stocks, cea din care se scade pe masura ce produsul se consuma. Un
        // produs fara ea n-ar avea evidenta de stoc deloc.
        $stoc_initial = 0;
        $depozit_initial = '';

        if ($esteAdaugare) {
            $stoc_initial = $this->validInt($errors, $input, 'Stoc_initial', 'Stocul de pornire', 0);

            $depozit_initial = trim((string) ($input['Depozit_initial'] ?? ''));

            if ($depozit_initial === '') {
                $errors[] = 'Alege depozitul in care se afla stocul de pornire.';
            } elseif (!isset(StocCurent::DEPOZITE[(int) $depozit_initial])) {
                $errors[] = 'Alege un depozit valid pentru stocul de pornire.';
                $depozit_initial = '';
            }
        }

        return [
            'errors' => $errors,
            'data' => [
                'Product_ID' => $product_id,
                'Product_Name' => $product_name,
                'Category' => $category,
                'Unit_Cost' => $unit_cost,
                'poze' => $poze === '' ? null : $poze,
                // Nu sunt coloane ale catalogului: BaseRepository le ignora la
                // scriere, iar create() le foloseste pentru randul de stoc.
                'Stoc_initial' => $stoc_initial,
                'Depozit_initial' => $depozit_initial,
            ],
        ];
    }
}
