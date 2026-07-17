initCrudPage({
  title: 'Gestionare soferi',
  entityLabel: 'soferul',
  addLabel: 'Sofer nou',
  searchPlaceholder: 'Cauta dupa nume sau oras de baza...',
  pk: 'SoferID',

  endpoints: {
    list: 'get-soferi.php',
    create: 'create-sofer.php',
    update: 'update-sofer.php',
    delete: 'delete-sofer.php',
  },

  defaultSort: { column: 'SoferID', dir: 'desc' },

  rowLabel: function (row) {
    return row.Nume;
  },

  columns: [
    { key: 'SoferID', label: 'ID', type: 'id' },
    { key: 'Nume', label: 'Nume' },
    { key: 'Telefon', label: 'Telefon' },
    { key: 'Oras_baza', label: 'Oras de baza' },
    { key: 'Data_angajare', label: 'Angajat', type: 'date' },
  ],

  fields: [
    { name: 'Nume', label: 'Nume', type: 'text', maxlength: 50, required: true },
    { name: 'Telefon', label: 'Telefon', type: 'text', maxlength: 20 },
    { name: 'Oras_baza', label: 'Oras de baza', type: 'text', maxlength: 50, required: true },
    { name: 'Data_angajare', label: 'Data angajare', type: 'date', default: 'today' },
  ],
});
