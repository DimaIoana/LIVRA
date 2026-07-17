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
    protected function table()
    {
        return 'inventory';
    }

    protected function primaryKey()
    {
        return 'InventoryID';
    }

    protected function columns()
    {
        return ['Product_ID', 'Product_Name', 'Category', 'Stock_Level', 'Reorder_Point', 'Monthly_Sales', 'Unit_Cost', 'Date'];
    }

    protected function sortableColumns()
    {
        return ['InventoryID', 'Product_ID', 'Product_Name', 'Category', 'Stock_Level', 'Reorder_Point', 'Monthly_Sales', 'Unit_Cost', 'Date'];
    }

    protected function searchableColumns()
    {
        return ['Product_ID', 'Product_Name', 'Category'];
    }

    public function validate(array $input)
    {
        $errors = [];

        $product_id = $this->validText($errors, $input, 'Product_ID', 'Codul produsului', 10);
        $product_name = $this->validText($errors, $input, 'Product_Name', 'Numele produsului', 100);
        $category = $this->validText($errors, $input, 'Category', 'Categoria', 50);

        $stock_level = $this->validInt($errors, $input, 'Stock_Level', 'Stocul', 0);
        $reorder_point = $this->validInt($errors, $input, 'Reorder_Point', 'Pragul de recomanda', 0);
        $monthly_sales = $this->validInt($errors, $input, 'Monthly_Sales', 'Vanzarile lunare', 0);
        $unit_cost = $this->validDecimal($errors, $input, 'Unit_Cost', 'Costul unitar', 0);

        $date = trim((string) ($input['Date'] ?? ''));
        if ($date === '') {
            $date = date('Y-m-d');
        } else {
            $date = $this->validDate($errors, $input, 'Date', 'Data');
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
                'Date' => $date,
            ],
        ];
    }
}
