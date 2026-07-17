initCrudPage({
  title: 'Produse (stoc lunar)',
  entityLabel: 'inregistrarea',
  addLabel: 'Inregistrare noua',
  searchPlaceholder: 'Cauta dupa cod, nume sau categorie...',
  pk: 'InventoryID',

  endpoints: {
    list: 'get-inventory.php',
    create: 'create-inventory.php',
    update: 'update-inventory.php',
    delete: 'delete-inventory.php',
  },

  defaultSort: { column: 'Date', dir: 'desc' },

  rowLabel: function (row) {
    return row.Product_Name + ' (' + row.Date + ')';
  },

  columns: [
    { key: 'InventoryID', label: 'ID', type: 'id' },
    { key: 'Product_ID', label: 'Cod' },
    { key: 'Product_Name', label: 'Produs' },
    { key: 'Category', label: 'Categorie', type: 'tag' },
    {
      key: 'Stock_Level',
      label: 'Stoc',
      type: 'number',
      // Sub pragul de recomanda = trebuie recomandat, deci se evidentiaza.
      cellClass: function (row) {
        return Number(row.Stock_Level) <= Number(row.Reorder_Point) ? 'cell--alert' : '';
      },
    },
    { key: 'Reorder_Point', label: 'Prag', type: 'number' },
    { key: 'Monthly_Sales', label: 'Vanzari/luna', type: 'number' },
    { key: 'Unit_Cost', label: 'Cost unitar', type: 'money' },
    { key: 'Date', label: 'Luna', type: 'date' },
  ],

  fields: [
    { name: 'Product_ID', label: 'Cod produs', type: 'text', maxlength: 10, required: true },
    { name: 'Product_Name', label: 'Nume produs', type: 'text', maxlength: 100, required: true },
    { name: 'Category', label: 'Categorie', type: 'text', maxlength: 50, required: true },
    { name: 'Stock_Level', label: 'Stoc', type: 'text', required: true },
    { name: 'Reorder_Point', label: 'Prag de recomanda', type: 'text', required: true },
    { name: 'Monthly_Sales', label: 'Vanzari lunare', type: 'text', required: true },
    { name: 'Unit_Cost', label: 'Cost unitar (lei)', type: 'text', required: true },
    {
      name: 'Date',
      label: 'Luna (data raportarii)',
      type: 'date',
      default: 'today',
      hint: 'Fiecare produs are cate o inregistrare pe luna.',
    },
  ],
});
