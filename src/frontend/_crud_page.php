<?php

/**
 * Pagina CRUD generica, randata integral pe server (fara API/AJAX/JS).
 *
 * Fiecare pagina (clienti.php, expedieri.php, ...) pregateste $pdo, $repo si
 * $config, apoi include acest fisier. Aici se trateaza POST-ul (adauga /
 * modifica / sterge) prin repository si se randeaza tabelul, formularul si
 * confirmarea de stergere in HTML.
 *
 * Formularele fac POST clasic catre aceeasi pagina; dupa o operatie reusita se
 * face redirect (Post-Redirect-Get) ca reincarcarea sa nu retrimita datele.
 * Starea listei (cautare / sortare) calatoreste prin query string.
 *
 * $config asteptat:
 *   active                -> cheia de navigatie (clienti|expedieri|soferi|rute|produse)
 *   title, entityLabel, addLabel, searchPlaceholder, pk
 *   defaultSort           -> ['column' => ..., 'dir' => 'asc'|'desc']
 *   deleteBlockedMessage  -> mesaj cand stergerea e blocata de o cheie straina (sau null)
 *   options               -> optiuni pentru select-uri (sau [] daca nu exista)
 *   rowLabel              -> callable($row): string, pentru confirmarea de stergere
 *   columns               -> coloanele tabelului
 *   fields                -> campurile formularului
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pk = $config['pk'];
$page = basename($_SERVER['PHP_SELF']);

// --- Helperi ---

/** Scapa text pentru HTML. */
function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** 2025-03-01 -> 01.03.2025; gol -> '-'. */
function fmt_date($value)
{
    if ($value === null || $value === '') {
        return '-';
    }

    $parts = explode('-', $value);

    return count($parts) === 3 ? $parts[2] . '.' . $parts[1] . '.' . $parts[0] : $value;
}

/** 1234.5 -> 1.234,50 lei; gol -> '-'. */
function fmt_money($value)
{
    if ($value === null || $value === '') {
        return '-';
    }

    return number_format((float) $value, 2, ',', '.') . ' lei';
}

/** Pune un mesaj care va fi aratat dupa redirect. */
function flash_set($message, $state)
{
    $_SESSION['flash'] = ['message' => $message, 'state' => $state];
}

/** Query string cu starea listei (cautare + sortare) dintr-o sursa GET/POST. */
function list_query(array $source, $defaultSort, $defaultDir)
{
    $params = [
        'search' => trim((string) ($source['search'] ?? '')),
        'sort' => (string) ($source['sort'] ?? $defaultSort),
        'dir' => (string) ($source['dir'] ?? $defaultDir),
    ];

    if ($params['search'] === '') {
        unset($params['search']);
    }

    return '?' . http_build_query($params);
}

// --- Starea listei (valabila si pentru GET, si pentru POST) ---

$search = trim((string) ($_REQUEST['search'] ?? ''));
$sort = (string) ($_REQUEST['sort'] ?? $config['defaultSort']['column']);
$dir = strtolower((string) ($_REQUEST['dir'] ?? $config['defaultSort']['dir'])) === 'asc' ? 'asc' : 'desc';
$listQuery = list_query($_REQUEST, $config['defaultSort']['column'], $config['defaultSort']['dir']);

// Starea formularului / confirmarii.
$formOpen = false;
$formMode = 'add';
$formValues = [];
$formErrors = [];
$confirmRow = null;

// --- Tratare POST (adauga / modifica / sterge) ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // Starea listei (cautare/sortare) vine din query string-ul actiunii formularului,
    // deci se citeste din $_REQUEST (GET + POST), nu doar din corpul POST.
    $target = $page . list_query($_REQUEST, $config['defaultSort']['column'], $config['defaultSort']['dir']);

    if ($action === 'delete') {
        $id = (int) ($_POST[$pk] ?? 0);

        try {
            if ($repo->delete($id)) {
                flash_set('Inregistrare stearsa.', 'ok');
            } else {
                flash_set('Inregistrarea nu exista.', 'fail');
            }
        } catch (PDOException $e) {
            // 23000 = constrangere de cheie straina (inregistrarea e referita).
            if ($e->getCode() === '23000' && !empty($config['deleteBlockedMessage'])) {
                flash_set($config['deleteBlockedMessage'], 'fail');
            } else {
                flash_set('Nu s-a putut sterge inregistrarea.', 'fail');
            }
        }

        header('Location: ' . $target);
        exit;
    }

    if ($action === 'save') {
        $result = $repo->validate($_POST);
        $id = trim((string) ($_POST[$pk] ?? ''));

        if ($result['errors']) {
            // Reafisam formularul cu valorile introduse si erorile.
            $formOpen = true;
            $formMode = $id !== '' ? 'edit' : 'add';
            $formValues = $_POST;
            $formErrors = $result['errors'];
        } elseif ($id !== '') {
            if ($repo->update((int) $id, $result['data'])) {
                flash_set('Inregistrare modificata.', 'ok');
            } else {
                flash_set('Inregistrarea nu exista.', 'fail');
            }

            header('Location: ' . $target);
            exit;
        } else {
            $repo->create($result['data']);
            flash_set('Inregistrare adaugata.', 'ok');

            header('Location: ' . $target);
            exit;
        }
    }
}

// --- Tratare GET (deschide formular de adaugare / editare / confirmare) ---

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$formOpen) {
    if (isset($_GET['add'])) {
        $formOpen = true;
        $formMode = 'add';

        foreach ($config['fields'] as $field) {
            if (($field['default'] ?? '') === 'today') {
                $formValues[$field['name']] = date('Y-m-d');
            }
        }
    } elseif (isset($_GET['edit'])) {
        $row = $repo->getById((int) $_GET['edit']);

        if ($row !== null) {
            $formOpen = true;
            $formMode = 'edit';
            $formValues = $row;
        }
    } elseif (isset($_GET['delete'])) {
        $confirmRow = $repo->getById((int) $_GET['delete']);
    }
}

// --- Date pentru afisare ---

$rows = $repo->getAll($search, $sort, $dir);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/** href-ul pentru sortarea dupa o coloana (comuta directia daca e deja activa). */
function sort_href($page, $col, $search, $sort, $dir)
{
    if ($col['key'] === $sort) {
        $nextDir = $dir === 'asc' ? 'desc' : 'asc';
    } else {
        $type = $col['type'] ?? 'text';
        $nextDir = ($type === 'date' || $type === 'id') ? 'desc' : 'asc';
    }

    $params = ['sort' => $col['key'], 'dir' => $nextDir];

    if ($search !== '') {
        $params['search'] = $search;
    }

    return $page . '?' . http_build_query($params);
}

$navLinks = [
    'clienti' => ['clienti.php', 'Clienti'],
    'comenzi' => ['comenzi.php', 'Comenzi'],
    'expedieri' => ['expedieri.php', 'Expedieri'],
    'soferi' => ['soferi.php', 'Soferi'],
    'rute' => ['rute.php', 'Rute'],
    'produse' => ['produse.php', 'Produse'],
];

?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PRIMUL - <?= h($config['title']) ?></title>
  <link rel="stylesheet" href="css/app.css?v=<?= filemtime(__DIR__ . '/css/app.css') ?>">
</head>
<body>

<header class="header">
  <div class="header__inner">
    <a class="logo" href="../../index.php">PRIMUL</a>
    <span class="header__subtitle"><?= h($config['title']) ?></span>
    <nav class="nav">
      <?php foreach ($navLinks as $key => $link): ?>
        <a class="nav__link<?= $key === $config['active'] ? ' nav__link--active' : '' ?>" href="<?= h($link[0]) ?>"><?= h($link[1]) ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
</header>

<main class="container">
  <form class="toolbar" method="get" action="<?= h($page) ?>">
    <input class="input toolbar__search" type="search" name="search"
           value="<?= h($search) ?>" placeholder="<?= h($config['searchPlaceholder']) ?>">
    <input type="hidden" name="sort" value="<?= h($sort) ?>">
    <input type="hidden" name="dir" value="<?= h($dir) ?>">
    <button class="btn btn--ghost" type="submit">Cauta</button>
    <a class="btn" href="<?= h($page . $listQuery) ?>&add=1">+ <?= h($config['addLabel']) ?></a>
  </form>

  <?php if ($flash): ?>
    <div class="alert alert--<?= h($flash['state']) ?>"><?= h($flash['message']) ?></div>
  <?php endif; ?>

  <div class="card">
    <table class="table">
      <thead>
        <tr>
          <?php foreach ($config['columns'] as $col): ?>
            <?php
              $active = $col['key'] === $sort;
              $thClass = $active ? 'th--sorted' : '';
            ?>
            <th data-sort="<?= h($col['key']) ?>" <?= $active ? 'data-dir="' . h($dir) . '"' : '' ?> class="<?= $thClass ?>">
              <a class="th__link" href="<?= h(sort_href($page, $col, $search, $sort, $dir)) ?>"><?= h($col['label']) ?></a>
            </th>
          <?php endforeach; ?>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <?php foreach ($config['columns'] as $col): ?>
              <?php
                $type = $col['type'] ?? 'text';
                $classes = [];

                if ($type === 'date' || $type === 'id') {
                    $classes[] = 'cell--nowrap';
                } elseif ($type === 'money' || $type === 'number') {
                    $classes[] = 'cell--number';
                }

                if (isset($col['cellClass'])) {
                    $extra = $col['cellClass']($row);
                    if ($extra !== '') {
                        $classes[] = $extra;
                    }
                }
              ?>
              <td class="<?= h(trim(implode(' ', $classes))) ?>">
                <?php if ($type === 'tag'): ?>
                  <span class="tag<?= isset($col['tagClass']) ? ' ' . h($col['tagClass']($row)) : '' ?>"><?= h($row[$col['key']]) ?></span>
                <?php elseif ($type === 'date'): ?>
                  <?= h(fmt_date($row[$col['key']])) ?>
                <?php elseif ($type === 'money'): ?>
                  <?= h(fmt_money($row[$col['key']])) ?>
                <?php else: ?>
                  <?= ($row[$col['key']] === null || $row[$col['key']] === '') ? '-' : h($row[$col['key']]) ?>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
            <td class="row-actions">
              <a class="btn--link" href="<?= h($page . $listQuery) ?>&edit=<?= (int) $row[$pk] ?>">Editeaza</a>
              <a class="btn--link btn--danger" href="<?= h($page . $listQuery) ?>&delete=<?= (int) $row[$pk] ?>">Sterge</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$rows): ?>
      <p class="empty">Nu exista inregistrari.</p>
    <?php endif; ?>
  </div>
</main>

<?php if ($formOpen): ?>
  <div class="modal">
    <a class="modal__backdrop" href="<?= h($page . $listQuery) ?>"></a>
    <div class="modal__box" role="dialog" aria-modal="true" aria-labelledby="modal-title">
      <h2 id="modal-title" class="modal__title">
        <?= $formMode === 'edit' ? 'Editeaza ' . h($config['entityLabel']) : h($config['addLabel']) ?>
      </h2>

      <form class="form" method="post" action="<?= h($page . $listQuery) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="<?= h($pk) ?>" value="<?= h($formValues[$pk] ?? '') ?>">

        <?php if ($formErrors): ?>
          <p class="form__error"><?= h(implode(' ', $formErrors)) ?></p>
        <?php endif; ?>

        <?php foreach ($config['fields'] as $field): ?>
          <?php $value = $formValues[$field['name']] ?? ''; ?>
          <label class="field">
            <span class="field__label"><?= h($field['label']) ?></span>

            <?php if (($field['type'] ?? 'text') === 'select'): ?>
              <?php
                $options = $field['options'] ?? ($config['options'][$field['optionsFrom']] ?? []);
              ?>
              <select class="input" name="<?= h($field['name']) ?>">
                <?php foreach ($options as $option): ?>
                  <?php
                    if (is_array($option)) {
                        $optValue = $option['id'];
                        $optText = $option['text'];
                    } else {
                        $optValue = $option;
                        $optText = $option;
                    }
                  ?>
                  <option value="<?= h($optValue) ?>" <?= ((string) $optValue === (string) $value) ? 'selected' : '' ?>><?= h($optText) ?></option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input class="input"
                     type="<?= (($field['type'] ?? 'text') === 'date') ? 'date' : 'text' ?>"
                     name="<?= h($field['name']) ?>"
                     value="<?= h($value) ?>"
                     <?= !empty($field['maxlength']) ? 'maxlength="' . (int) $field['maxlength'] . '"' : '' ?>
                     <?= !empty($field['required']) ? 'required' : '' ?>>
            <?php endif; ?>

            <?php if (!empty($field['hint'])): ?>
              <span class="field__hint"><?= h($field['hint']) ?></span>
            <?php endif; ?>
          </label>
        <?php endforeach; ?>

        <div class="form__actions">
          <a class="btn btn--ghost" href="<?= h($page . $listQuery) ?>">Anuleaza</a>
          <button class="btn" type="submit">Salveaza</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php if ($confirmRow !== null): ?>
  <div class="modal">
    <a class="modal__backdrop" href="<?= h($page . $listQuery) ?>"></a>
    <div class="modal__box" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
      <h2 id="confirm-title" class="modal__title">Confirma stergerea</h2>
      <p>Stergi <?= h($config['entityLabel']) ?> „<?= h($config['rowLabel']($confirmRow)) ?>”?</p>

      <form method="post" action="<?= h($page . $listQuery) ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="<?= h($pk) ?>" value="<?= (int) $confirmRow[$pk] ?>">
        <div class="form__actions">
          <a class="btn btn--ghost" href="<?= h($page . $listQuery) ?>">Anuleaza</a>
          <button class="btn btn--danger-solid" type="submit">Sterge</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

</body>
</html>
