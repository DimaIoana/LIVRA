<?php

require_once __DIR__ . '/BaseRepository.php';

/**
 * Acces la date pentru tabela `inventory`.
 *
 * Atentie: tabela e un istoric lunar de stoc, nu un catalog. Acelasi
 * Product_ID apare o data pentru fiecare luna (coloana `Date`).
 */
class InventoryRepository extends BaseRepository
{
    /** Prefixul codului de produs; dupa el urmeaza numarul, cu CIFRE_COD cifre. */
    const PREFIX_COD = 'PRD-';
    const CIFRE_COD = 4;

    protected function table()
    {
        return 'inventory';
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
             FROM inventory
             WHERE Product_ID LIKE :prefix'
        );
        $stmt->execute([
            'start' => strlen(self::PREFIX_COD) + 1,
            'prefix' => self::PREFIX_COD . '%',
        ]);

        $ultim = (int) $stmt->fetchColumn();

        return self::PREFIX_COD . str_pad($ultim + 1, self::CIFRE_COD, '0', STR_PAD_LEFT);
    }

    /** Numele produsului care poarta deja codul asta, sau null daca e liber. */
    private function numeExistent($cod)
    {
        $stmt = $this->pdo->prepare(
            'SELECT Product_Name FROM inventory WHERE Product_ID = :cod LIMIT 1'
        );
        $stmt->execute(['cod' => $cod]);

        $nume = $stmt->fetchColumn();

        return $nume === false ? null : (string) $nume;
    }

    protected function primaryKey()
    {
        return 'InventoryID';
    }

    protected function columns()
    {
        return ['Product_ID', 'Product_Name', 'Category', 'Stock_Level', 'Reorder_Point', 'Monthly_Sales', 'Unit_Cost', 'Cost_Unitar', 'Date', 'poze', 'depozit'];
    }

    protected function sortableColumns()
    {
        return ['InventoryID', 'Product_ID', 'Product_Name', 'Category', 'Stock_Level', 'Reorder_Point', 'Monthly_Sales', 'Unit_Cost', 'Cost_Unitar', 'Date'];
    }

    protected function searchableColumns()
    {
        return ['Product_ID', 'Product_Name', 'Category'];
    }

    public function validate(array $input)
    {
        $errors = [];

        $product_name = $this->validText($errors, $input, 'Product_Name', 'Numele produsului', 100);

        // Codul: lasat gol inseamna "produs nou", deci se genereaza urmatorul.
        // Formularul il vine oricum precompletat; golul e plasa de siguranta.
        $product_id = trim((string) ($input['Product_ID'] ?? ''));

        if ($product_id === '') {
            $product_id = $this->codNou();
        } else {
            $product_id = $this->validText($errors, $input, 'Product_ID', 'Codul produsului', 10);
        }

        // La adaugare (nu la editare), un cod deja folosit inseamna ca se adauga
        // o luna noua la un produs existent - deci numele trebuie sa fie al lui.
        // Un nume diferit e aproape sigur o greseala: doua produse ar ajunge sa
        // imparta acelasi cod si n-ar mai putea fi deosebite nicaieri.
        $esteAdaugare = trim((string) ($input[$this->primaryKey()] ?? '')) === '';

        if ($esteAdaugare && !$errors && $product_id !== '') {
            $numeExistent = $this->numeExistent($product_id);

            if ($numeExistent !== null && mb_strtolower($numeExistent) !== mb_strtolower($product_name)) {
                $errors[] = 'Codul "' . $product_id . '" este deja al produsului "' . $numeExistent
                    . '". Lasa campul gol ca sa primesti un cod nou (' . $this->codNou()
                    . '), sau scrie acelasi nume daca adaugi o luna noua la acest produs.';
            }
        }
        $category = $this->validText($errors, $input, 'Category', 'Categoria', 50);

        $stock_level = $this->validInt($errors, $input, 'Stock_Level', 'Stocul', 0);
        $reorder_point = $this->validInt($errors, $input, 'Reorder_Point', 'Pragul de recomanda', 0);
        $monthly_sales = $this->validInt($errors, $input, 'Monthly_Sales', 'Vanzarile lunare', 0);
        // Unit_Cost = pretul unitar afisat clientului; Cost_Unitar = costul de
        // achizitie pe firma (optional, se poate completa mai tarziu).
        $unit_cost = $this->validDecimal($errors, $input, 'Unit_Cost', 'Pretul unitar', 0);
        $cost_unitar = $this->validDecimal($errors, $input, 'Cost_Unitar', 'Costul unitar', 0, false);

        $date = trim((string) ($input['Date'] ?? ''));
        if ($date === '') {
            $date = date('Y-m-d');
        } else {
            $date = $this->validDate($errors, $input, 'Date', 'Data');
        }

        // Poza: numele fisierului deja mutat in folderul `poze/` (setat de
        // stratul de upload din _crud_page.php). Optionala.
        $poze = trim((string) ($input['poze'] ?? ''));
        if (mb_strlen($poze) > 200) {
            $errors[] = 'Numele pozei poate avea maxim 200 de caractere.';
        }

        // Depozitul unde se afla produsul: 1 = Arad, 2 = Braila, 3 = Pitesti.
        $depozit = trim((string) ($input['depozit'] ?? ''));
        if (!in_array($depozit, ['1', '2', '3'], true)) {
            $errors[] = 'Alege locatia produsului (Arad, Braila sau Pitesti).';
            $depozit = 0;
        } else {
            $depozit = (int) $depozit;
        }

        return [
            'errors' => $errors,
            'data' => [
                'Product_ID' => $product_id,
                'Product_Name' => $product_name,
                'Category' => $category,
                'Stock_Level' => $stock_level,
                'Reorder_Point' => $reorder_point,
                'Monthly_Sales' => $monthly_sales,
                'Unit_Cost' => $unit_cost,
                'Cost_Unitar' => $cost_unitar,
                'Date' => $date,
                'poze' => $poze === '' ? null : $poze,
                'depozit' => $depozit,
            ],
        ];
    }
}
