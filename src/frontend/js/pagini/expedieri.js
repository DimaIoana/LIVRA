initCrudPage({
  title: 'Gestionare expedieri',
  entityLabel: 'expedierea',
  addLabel: 'Expediere noua',
  searchPlaceholder: 'Cauta dupa client, sofer, oras sau status...',
  pk: 'ExpediereID',

  endpoints: {
    list: 'get-expedieri.php',
    create: 'create-expediere.php',
    update: 'update-expediere.php',
    delete: 'delete-expediere.php',
    options: 'get-optiuni-expedieri.php',
  },

  defaultSort: { column: 'ExpediereID', dir: 'desc' },

  rowLabel: function (row) {
    return '#' + row.ExpediereID + ' - ' + row.ClientNume;
  },

  columns: [
    { key: 'ExpediereID', label: 'ID', type: 'id' },
    { key: 'ClientNume', label: 'Client' },
    { key: 'SoferNume', label: 'Sofer' },
    { key: 'Ruta', label: 'Ruta' },
    { key: 'Data_expediere', label: 'Expediat', type: 'date' },
    { key: 'Data_livrare_estimata', label: 'Estimat', type: 'date' },
    { key: 'Data_livrare_efectiva', label: 'Livrat', type: 'date' },
    {
      key: 'Status_expediere',
      label: 'Status',
      type: 'tag',
      tagClass: function (row) {
        if (row.Status_expediere === 'Livrat') {
          return 'tag--ok';
        }

        if (row.Status_expediere === 'Intarziat' || row.Status_expediere === 'Returnat') {
          return 'tag--fail';
        }

        return 'tag--warn';
      },
    },
    { key: 'Valoare_expediere', label: 'Valoare', type: 'money' },
  ],

  fields: [
    { name: 'ClientID', label: 'Client', type: 'select', optionsFrom: 'clienti' },
    { name: 'SoferID', label: 'Sofer', type: 'select', optionsFrom: 'soferi' },
    { name: 'RutaID', label: 'Ruta', type: 'select', optionsFrom: 'rute' },
    { name: 'Status_expediere', label: 'Status', type: 'select', optionsFrom: 'statusuri' },
    { name: 'Data_expediere', label: 'Data expediere', type: 'date', default: 'today' },
    { name: 'Data_livrare_estimata', label: 'Data livrare estimata', type: 'date' },
    {
      name: 'Data_livrare_efectiva',
      label: 'Data livrare efectiva',
      type: 'date',
      hint: 'Se lasa gol cat timp coletul nu a ajuns la client.',
    },
    { name: 'Valoare_expediere', label: 'Valoare (lei)', type: 'text' },
  ],
});
