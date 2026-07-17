/**
 * Pagina CRUD generica: tabel cu cautare + sortare si formular in modal.
 * Fiecare pagina o configureaza prin initCrudPage({...}) - vezi js/pagini/.
 */

const API = '../api/';

function callApi(endpoint, options) {
  return fetch(API + endpoint, options)
    .then(function (response) {
      return response.json();
    })
    .then(function (result) {
      if (!result.success) {
        throw new Error(result.error);
      }

      return result.data;
    });
}

function postJson(endpoint, payload) {
  return callApi(endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
}

/** 2025-03-01 -> 01.03.2025; null/gol -> '-' */
function formatDate(value) {
  if (!value) {
    return '-';
  }

  const parts = value.split('-');

  return parts[2] + '.' + parts[1] + '.' + parts[0];
}

function formatMoney(value) {
  if (value === null || value === '') {
    return '-';
  }

  return Number(value).toLocaleString('ro-RO', { minimumFractionDigits: 2 }) + ' lei';
}

function initCrudPage(config) {
  const searchInput = document.getElementById('search');
  const tbody = document.getElementById('rows');
  const thead = document.getElementById('head-row');
  const empty = document.getElementById('empty');
  const alertBox = document.getElementById('alert');
  const modal = document.getElementById('modal');
  const modalTitle = document.getElementById('modal-title');
  const form = document.getElementById('crud-form');
  const formError = document.getElementById('form-error');
  const saveButton = document.getElementById('save');
  const addButton = document.getElementById('add');

  let sortColumn = config.defaultSort.column;
  let sortDir = config.defaultSort.dir;
  let searchTimer = null;
  let optiuni = {};

  // --- Constructie initiala a paginii din config ---

  document.getElementById('page-subtitle').textContent = config.title;
  document.title = 'PRIMUL - ' + config.title;
  searchInput.placeholder = config.searchPlaceholder;
  addButton.textContent = '+ ' + config.addLabel;

  config.columns.forEach(function (col) {
    const th = document.createElement('th');
    th.textContent = col.label;
    th.dataset.sort = col.key;
    thead.appendChild(th);
  });
  thead.appendChild(document.createElement('th'));

  config.fields.forEach(function (field) {
    const label = document.createElement('label');
    label.className = 'field';

    const span = document.createElement('span');
    span.className = 'field__label';
    span.textContent = field.label;
    label.appendChild(span);

    let input;

    if (field.type === 'select') {
      input = document.createElement('select');
    } else {
      input = document.createElement('input');
      input.type = field.type === 'date' ? 'date' : 'text';

      if (field.maxlength) {
        input.maxLength = field.maxlength;
      }
    }

    input.className = 'input';
    input.name = field.name;

    if (field.required) {
      input.required = true;
    }

    if (field.hint) {
      const hint = document.createElement('span');
      hint.className = 'field__hint';
      hint.textContent = field.hint;
      label.appendChild(input);
      label.appendChild(hint);
    } else {
      label.appendChild(input);
    }

    form.insertBefore(label, formError);
  });

  // --- Randare ---

  function cellValue(col, row) {
    const raw = row[col.key];

    if (col.type === 'date') {
      return formatDate(raw);
    }

    if (col.type === 'money') {
      return formatMoney(raw);
    }

    return raw === null || raw === '' ? '-' : String(raw);
  }

  function renderRow(row) {
    const tr = document.createElement('tr');

    config.columns.forEach(function (col) {
      const td = document.createElement('td');
      const classes = [];

      if (col.type === 'date' || col.type === 'id') {
        classes.push('cell--nowrap');
      } else if (col.type === 'money' || col.type === 'number') {
        classes.push('cell--number');
      }

      if (col.cellClass) {
        classes.push(col.cellClass(row));
      }

      if (col.type === 'tag') {
        const tag = document.createElement('span');
        tag.className = 'tag' + (col.tagClass ? ' ' + col.tagClass(row) : '');
        tag.textContent = row[col.key];
        td.appendChild(tag);
      } else {
        td.textContent = cellValue(col, row);
      }

      td.className = classes.join(' ').trim();
      tr.appendChild(td);
    });

    const actions = document.createElement('td');
    actions.className = 'row-actions';

    const editBtn = document.createElement('button');
    editBtn.className = 'btn--link';
    editBtn.type = 'button';
    editBtn.textContent = 'Editeaza';
    editBtn.addEventListener('click', function () {
      openModal(row);
    });

    const deleteBtn = document.createElement('button');
    deleteBtn.className = 'btn--link btn--danger';
    deleteBtn.type = 'button';
    deleteBtn.textContent = 'Sterge';
    deleteBtn.addEventListener('click', function () {
      removeRow(row);
    });

    actions.appendChild(editBtn);
    actions.appendChild(deleteBtn);
    tr.appendChild(actions);

    return tr;
  }

  function renderSortIndicators() {
    thead.querySelectorAll('th[data-sort]').forEach(function (th) {
      const active = th.dataset.sort === sortColumn;

      th.classList.toggle('th--sorted', active);
      th.dataset.dir = active ? sortDir : '';
      th.setAttribute('aria-sort', active ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none');
    });
  }

  function showAlert(message, state) {
    alertBox.textContent = message;
    alertBox.className = 'alert alert--' + state;
    alertBox.hidden = false;

    setTimeout(function () {
      alertBox.hidden = true;
    }, 5000);
  }

  function loadRows() {
    const query = new URLSearchParams({
      search: searchInput.value.trim(),
      sort: sortColumn,
      dir: sortDir,
    });

    return callApi(config.endpoints.list + '?' + query.toString())
      .then(function (rows) {
        tbody.innerHTML = '';
        rows.forEach(function (row) {
          tbody.appendChild(renderRow(row));
        });

        empty.hidden = rows.length > 0;
        renderSortIndicators();
      })
      .catch(function (error) {
        showAlert(error.message, 'fail');
      });
  }

  function sortBy(column) {
    if (sortColumn === column) {
      sortDir = sortDir === 'asc' ? 'desc' : 'asc';
    } else {
      sortColumn = column;

      const col = config.columns.find(function (c) {
        return c.key === column;
      });

      // Datele si ID-urile incep descrescator (cele noi primele), textul crescator.
      sortDir = col && (col.type === 'date' || col.type === 'id') ? 'desc' : 'asc';
    }

    loadRows();
  }

  // --- Formular ---

  function fillSelects() {
    config.fields.forEach(function (field) {
      if (field.type !== 'select') {
        return;
      }

      const select = form.elements[field.name];
      select.innerHTML = '';

      const values = field.options || optiuni[field.optionsFrom] || [];

      values.forEach(function (option) {
        const el = document.createElement('option');

        if (typeof option === 'string') {
          el.value = option;
          el.textContent = option;
        } else {
          el.value = option.id;
          el.textContent = option.text;
        }

        select.appendChild(el);
      });
    });
  }

  function openModal(row) {
    form.reset();
    formError.hidden = true;
    fillSelects();

    if (row) {
      modalTitle.textContent = 'Editeaza ' + config.entityLabel;
      form.elements[config.pk].value = row[config.pk];

      config.fields.forEach(function (field) {
        const value = row[field.name];
        form.elements[field.name].value = value === null ? '' : value;
      });
    } else {
      modalTitle.textContent = config.addLabel;
      form.elements[config.pk].value = '';

      config.fields.forEach(function (field) {
        if (field.default === 'today') {
          form.elements[field.name].value = new Date().toISOString().slice(0, 10);
        }
      });
    }

    modal.hidden = false;
    form.elements[config.fields[0].name].focus();
  }

  function closeModal() {
    modal.hidden = true;
  }

  function saveRow(event) {
    event.preventDefault();
    formError.hidden = true;
    saveButton.disabled = true;

    const payload = Object.fromEntries(new FormData(form).entries());
    const isEdit = payload[config.pk] !== '';

    postJson(isEdit ? config.endpoints.update : config.endpoints.create, payload)
      .then(function () {
        closeModal();
        showAlert(isEdit ? 'Inregistrare modificata.' : 'Inregistrare adaugata.', 'ok');
        loadRows();
      })
      .catch(function (error) {
        formError.textContent = error.message;
        formError.hidden = false;
      })
      .finally(function () {
        saveButton.disabled = false;
      });
  }

  function removeRow(row) {
    if (!confirm('Stergi ' + config.entityLabel + ' "' + config.rowLabel(row) + '"?')) {
      return;
    }

    const payload = {};
    payload[config.pk] = row[config.pk];

    postJson(config.endpoints.delete, payload)
      .then(function () {
        showAlert('Inregistrare stearsa.', 'ok');
        loadRows();
      })
      .catch(function (error) {
        showAlert(error.message, 'fail');
      });
  }

  // --- Evenimente ---

  addButton.addEventListener('click', function () {
    openModal(null);
  });

  thead.addEventListener('click', function (event) {
    const th = event.target.closest('th[data-sort]');

    if (th) {
      sortBy(th.dataset.sort);
    }
  });

  form.addEventListener('submit', saveRow);

  modal.addEventListener('click', function (event) {
    if (event.target.hasAttribute('data-close')) {
      closeModal();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.hidden) {
      closeModal();
    }
  });

  searchInput.addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadRows, 250);
  });

  // --- Pornire ---

  const ready = config.endpoints.options
    ? callApi(config.endpoints.options).then(function (data) {
        optiuni = data;
      })
    : Promise.resolve();

  ready.then(loadRows);
}
