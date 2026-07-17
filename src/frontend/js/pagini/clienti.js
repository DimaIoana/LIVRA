initCrudPage({
  title: 'Gestionare clienti',
  entityLabel: 'clientul',
  addLabel: 'Client nou',
  searchPlaceholder: 'Cauta dupa nume, email sau oras...',
  pk: 'ClientID',

  endpoints: {
    list: 'get-clienti.php',
    create: 'create-client.php',
    update: 'update-client.php',
    delete: 'delete-client.php',
  },

  defaultSort: { column: 'ClientID', dir: 'desc' },

  rowLabel: function (row) {
    return row.Nume;
  },

  columns: [
    { key: 'ClientID', label: 'ID', type: 'id' },
    { key: 'Nume', label: 'Nume' },
    { key: 'Tip', label: 'Tip', type: 'tag' },
    { key: 'Email', label: 'Email' },
    { key: 'Telefon', label: 'Telefon' },
    { key: 'Oras', label: 'Oras' },
    { key: 'Data_inregistrare', label: 'Inregistrat', type: 'date' },
  ],

  fields: [
    { name: 'Nume', label: 'Nume', type: 'text', maxlength: 50, required: true },
    { name: 'Tip', label: 'Tip', type: 'select', options: ['Persoana fizica', 'Persoana juridica'] },
    { name: 'Email', label: 'Email', type: 'text', maxlength: 150, required: true },
    { name: 'Telefon', label: 'Telefon', type: 'text', maxlength: 20 },
    { name: 'Oras', label: 'Oras', type: 'text', maxlength: 30, required: true },
    { name: 'Data_inregistrare', label: 'Data inregistrare', type: 'date', default: 'today' },
  ],
});
