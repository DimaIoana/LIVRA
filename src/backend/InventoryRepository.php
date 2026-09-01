<?php

require_once __DIR__ . '/BaseRepository.php';

/**
 * Acces la date pentru tabela `inventory` - stocul.
 *
 * Atentie: tabela e un istoric lunar de stoc, nu un catalog. Acelasi
 * Product_ID apare o data pentru fiecare luna (coloana `Date`).
 *
 * Produsul in sine (nume, categorie, pret de vanzare, poza) sta in `produse`,
 * catalogul; aici e doar codul lui, ca legatura (cheie straina). Deci nu se
 * poate inregistra stoc pentru un produs care nu e in catalog, iar numele unui
 * produs se schimba intr-un singur loc.
 *
 * Listarea face JOIN cu catalogul, ca in tabel sa se vada tot randul (cod, nume,
 * categorie, pret), dar scrisul atinge numai coloanele de stoc.
 */
class InventoryRepository extends BaseRepository
{
    protected function table()
    {
        return 'inventory';
    }

    protected function primaryKey()
    {
        return 'InventoryID';
    }

    /** Doar coloanele de stoc: astea se scriu la adaugare/editare. */
    protected function columns()
    {
        return ['Product_ID', 'Stock_Level', 'Reorder_Point', 'Monthly_Sales', 'Cost_Unitar', 'Date', 'depozit'];
    }

    /**
     * Listarea aduce si datele produsului din catalog. `produse.Product_ID` nu
     * se selecteaza: e acelasi cu cel din inventory, iar daca ar aparea de doua
     * ori, sortarea dupa Product_ID ar deveni ambigua.
     */
    protected function selectFrom()
    {
        // Fara alias de tabela: BaseRepository::getById filtreaza pe
        // `inventory`.`InventoryID`, deci tabela trebuie sa-si pastreze numele.
        return 'SELECT `inventory`.`InventoryID`, `inventory`.`Product_ID`,
                       `inventory`.`Stock_Level`, `inventory`.`Reorder_Point`,
                       `inventory`.`Monthly_Sales`, `inventory`.`Cost_Unitar`,
                       `inventory`.`Date`, `inventory`.`depozit`,
                       `produse`.`Product_Name`, `produse`.`Category`,
                       `produse`.`Unit_Cost`, `produse`.`poze`
                  FROM `inventory`
                  JOIN `produse` ON `produse`.`Product_ID` = `inventory`.`Product_ID`';
    }

    protected function sortableColumns()
    {
        // Nume simple: sortarea se face pe coloanele rezultatului, care sunt unice.
        return ['InventoryID', 'Product_ID', 'Product_Name', 'Category', 'Stock_Level',
                'Reorder_Point', 'Monthly_Sales', 'Unit_Cost', 'Cost_Unitar', 'Date'];
    }

    protected function searchableColumns()
    {
        // Aici numele trebuie calificate: WHERE nu vede numele din SELECT, iar
        // Product_ID exista in ambele tabele.
        return ['inventory.Product_ID', 'produse.Product_Name', 'produse.Category'];
    }

    public function validate(array $input)
    {
        $errors = [];

        // Produsul se alege din catalog, deci se verifica doar ca respectivul cod
        // chiar exista acolo. Numele, categoria si pretul nu se mai scriu aici.
        $product_id = trim((string) ($input['Product_ID'] ?? ''));

        if ($product_id === '') {
            $errors[] = 'Alege produsul din catalog.';
        } else {
            $stmt = $this->pdo->prepare('SELECT 1 FROM produse WHERE Product_ID = :cod');
            $stmt->execute(['cod' => $product_id]);

            if ($stmt->fetchColumn() === false) {
                $errors[] = 'Produsul "' . $product_id . '" nu exista in catalog. '
                    . 'Adauga-l intai in Catalog de produse.';
            }
        }

        $stock_level = $this->validInt($errors, $input, 'Stock_Level', 'Stocul', 0);
        $reorder_point = $this->validInt($errors, $input, 'Reorder_Point', 'Pragul de recomanda', 0);
        $monthly_sales = $this->validInt($errors, $input, 'Monthly_Sales', 'Vanzarile lunare', 0);

        // Cost_Unitar = costul de achizitie pe firma, care chiar difera de la o
        // luna/depozit la alta, deci ramane pe randul de stoc. Optional.
        $cost_unitar = $this->validDecimal($errors, $input, 'Cost_Unitar', 'Costul unitar', 0, false);

        $date = trim((string) ($input['Date'] ?? ''));
        if ($date === '') {
            $date = date('Y-m-d');
        } else {
            $date = $this->validDate($errors, $input, 'Date', 'Data');
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
                'Stock_Level' => $stock_level,
                'Reorder_Point' => $reorder_point,
                'Monthly_Sales' => $monthly_sales,
                'Cost_Unitar' => $cost_unitar,
                'Date' => $date,
                'depozit' => $depozit,
            ],
        ];
    }
}
