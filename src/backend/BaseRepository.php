<?php

/**
 * Baza comuna pentru accesul la o tabela: listare cu cautare/sortare + CRUD.
 *
 * Subclasele descriu tabela prin metodele table()/primaryKey()/columns()/
 * sortableColumns()/searchableColumns() si isi implementeaza validate().
 */
abstract class BaseRepository
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Numele tabelei. */
    abstract protected function table();

    /** Numele coloanei cheie primara. */
    abstract protected function primaryKey();

    /** Coloanele care pot fi scrise de utilizator (fara cheia primara). */
    abstract protected function columns();

    /**
     * Coloanele dupa care se poate sorta. Numele de coloana nu poate fi legat
     * ca parametru PDO, deci se accepta doar valori din aceasta lista.
     */
    abstract protected function sortableColumns();

    /** Coloanele in care cauta caseta de search. */
    abstract protected function searchableColumns();

    /**
     * Valideaza si normalizeaza input-ul.
     *
     * @return array ['errors' => string[], 'data' => array]
     */
    abstract public function validate(array $input);

    /**
     * Pune identificatorul intre backtick-uri. Necesar pentru coloane care sunt
     * cuvinte rezervate in MySQL (ex: `Date` in tabela inventory).
     */
    protected function quote($identifier)
    {
        return '`' . $identifier . '`';
    }

    /**
     * Clauza SELECT ... FROM. Subclasele o pot suprascrie pentru JOIN-uri.
     */
    protected function selectFrom()
    {
        $cols = array_map([$this, 'quote'], array_merge([$this->primaryKey()], $this->columns()));

        return 'SELECT ' . implode(', ', $cols) . ' FROM ' . $this->quote($this->table());
    }

    /** Sortarea implicita cand nu se cere alta. */
    protected function defaultSort()
    {
        return $this->primaryKey();
    }

    public function getAll($search = '', $sort = null, $dir = 'desc')
    {
        $sql = $this->selectFrom();
        $params = [];

        $search = trim($search);
        if ($search !== '' && $this->searchableColumns()) {
            $conditions = [];

            // Cu EMULATE_PREPARES dezactivat, fiecare placeholder trebuie sa fie unic.
            foreach ($this->searchableColumns() as $i => $column) {
                $key = 'search_' . $i;
                $conditions[] = $this->quote($column) . ' LIKE :' . $key;
                $params[$key] = '%' . $search . '%';
            }

            $sql .= ' WHERE ' . implode(' OR ', $conditions);
        }

        if (!in_array($sort, $this->sortableColumns(), true)) {
            $sort = $this->defaultSort();
        }

        $dir = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';

        // $sort vine din lista alba, $dir e unul din doi literali - sigur de interpolat.
        $sql .= ' ORDER BY ' . $this->quote($sort) . ' ' . $dir;

        // Ordine stabila cand valorile sortate sunt egale.
        if ($sort !== $this->primaryKey()) {
            $sql .= ', ' . $this->quote($this->primaryKey()) . ' DESC';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        $sql = $this->selectFrom()
            . ' WHERE ' . $this->quote($this->table()) . '.' . $this->quote($this->primaryKey()) . ' = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return int cheia primara nou creata.
     */
    public function create(array $data)
    {
        $columns = $this->columns();
        $quoted = array_map([$this, 'quote'], $columns);
        $placeholders = array_map(function ($c) {
            return ':' . $c;
        }, $columns);

        $sql = 'INSERT INTO ' . $this->quote($this->table())
            . ' (' . implode(', ', $quoted) . ')'
            . ' VALUES (' . implode(', ', $placeholders) . ')';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindable($data));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return bool false daca inregistrarea nu exista.
     */
    public function update($id, array $data)
    {
        if ($this->getById($id) === null) {
            return false;
        }

        $sets = array_map(function ($c) {
            return $this->quote($c) . ' = :' . $c;
        }, $this->columns());

        $sql = 'UPDATE ' . $this->quote($this->table())
            . ' SET ' . implode(', ', $sets)
            . ' WHERE ' . $this->quote($this->primaryKey()) . ' = :pk_id';

        $params = $this->bindable($data);
        $params['pk_id'] = $id;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return true;
    }

    /**
     * @return bool false daca inregistrarea nu exista.
     */
    public function delete($id)
    {
        $sql = 'DELETE FROM ' . $this->quote($this->table())
            . ' WHERE ' . $this->quote($this->primaryKey()) . ' = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Pastreaza din $data doar coloanele scriibile, in ordinea declarata.
     */
    private function bindable(array $data)
    {
        $params = [];

        foreach ($this->columns() as $column) {
            $params[$column] = $data[$column] ?? null;
        }

        return $params;
    }

    // --- Helperi de validare, folositi de subclase ---

    protected function validText(array &$errors, $input, $key, $label, $maxLength, $required = true)
    {
        $value = trim((string) ($input[$key] ?? ''));

        if ($value === '') {
            if ($required) {
                $errors[] = $label . ' este obligatoriu.';
            }
        } elseif (mb_strlen($value) > $maxLength) {
            $errors[] = $label . ' poate avea maxim ' . $maxLength . ' caractere.';
        }

        return $value;
    }

    protected function validDate(array &$errors, $input, $key, $label, $required = true)
    {
        $value = trim((string) ($input[$key] ?? ''));

        if ($value === '') {
            if ($required) {
                $errors[] = $label . ' este obligatorie.';
            }

            return $required ? $value : null;
        }

        $date = DateTime::createFromFormat('Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            $errors[] = $label . ' trebuie sa fie in formatul AAAA-LL-ZZ.';
        }

        return $value;
    }

    /**
     * Valideaza o data cu ora. Accepta "Y-m-d H:i", "Y-m-d H:i:s" sau formatul
     * de la input-ul datetime-local ("Y-m-dTH:i"). Normalizeaza la "Y-m-d H:i:s".
     */
    protected function validDateTime(array &$errors, $input, $key, $label, $required = true)
    {
        $value = str_replace('T', ' ', trim((string) ($input[$key] ?? '')));

        if ($value === '') {
            if ($required) {
                $errors[] = $label . ' este obligatorie.';
            }

            return $required ? $value : null;
        }

        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $value);
        if ($dt === false) {
            $dt = DateTime::createFromFormat('Y-m-d H:i', $value);
        }

        if ($dt === false) {
            $errors[] = $label . ' trebuie sa fie o data si ora valide.';

            return $value;
        }

        return $dt->format('Y-m-d H:i:s');
    }

    protected function validEnum(array &$errors, $input, $key, $label, array $allowed)
    {
        $value = (string) ($input[$key] ?? '');

        if (!in_array($value, $allowed, true)) {
            $errors[] = $label . ' trebuie sa fie: ' . implode(', ', $allowed) . '.';
        }

        return $value;
    }

    protected function validInt(array &$errors, $input, $key, $label, $min = 0, $required = true)
    {
        $raw = $input[$key] ?? '';

        if (trim((string) $raw) === '') {
            if ($required) {
                $errors[] = $label . ' este obligatoriu.';
            }

            return $required ? 0 : null;
        }

        $value = filter_var($raw, FILTER_VALIDATE_INT);

        if ($value === false || $value < $min) {
            $errors[] = $label . ' trebuie sa fie un numar intreg de la ' . $min . ' in sus.';

            return 0;
        }

        return $value;
    }

    protected function validDecimal(array &$errors, $input, $key, $label, $min = 0, $required = true)
    {
        $raw = str_replace(',', '.', trim((string) ($input[$key] ?? '')));

        if ($raw === '') {
            if ($required) {
                $errors[] = $label . ' este obligatoriu.';
            }

            return $required ? 0 : null;
        }

        $value = filter_var($raw, FILTER_VALIDATE_FLOAT);

        if ($value === false || $value < $min) {
            $errors[] = $label . ' trebuie sa fie un numar de la ' . $min . ' in sus.';

            return 0;
        }

        return round($value, 2);
    }

    /**
     * Verifica existenta unei chei straine.
     */
    protected function validForeignKey(array &$errors, $input, $key, $label, $table, $pk)
    {
        $id = filter_var($input[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($id === false) {
            $errors[] = $label . ' este obligatoriu.';

            return 0;
        }

        // $table si $pk sunt literali din cod, nu input de utilizator.
        $stmt = $this->pdo->prepare('SELECT 1 FROM ' . $table . ' WHERE ' . $pk . ' = :id');
        $stmt->execute(['id' => $id]);

        if ($stmt->fetchColumn() === false) {
            $errors[] = $label . ' selectat nu exista.';
        }

        return $id;
    }
}
