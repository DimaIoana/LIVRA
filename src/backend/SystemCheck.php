<?php

/**
 * Verificari de mediu pentru pagina de test.
 */
class SystemCheck
{
    private $db_path;

    public function __construct($db_path)
    {
        $this->db_path = $db_path;
    }

    /**
     * Ruleaza toate verificarile si intoarce o lista de rezultate.
     * Fiecare rezultat: ['name' => ..., 'ok' => bool, 'detail' => string]
     */
    public function runAll()
    {
        $checks = [];

        $checks[] = $this->checkPhpVersion();
        $checks[] = $this->checkPdoDriver();

        $pdo = null;
        $connection = $this->checkDatabaseConnection($pdo);
        $checks[] = $connection;

        if ($pdo instanceof PDO) {
            $checks[] = $this->checkCharset($pdo);
            $checks[] = $this->checkTables($pdo);
        }

        $checks[] = $this->checkWritableTemp();

        return $checks;
    }

    private function result($name, $ok, $detail)
    {
        return ['name' => $name, 'ok' => (bool) $ok, 'detail' => $detail];
    }

    private function checkPhpVersion()
    {
        $ok = version_compare(PHP_VERSION, '7.4.0', '>=');

        return $this->result('Versiune PHP', $ok, PHP_VERSION);
    }

    private function checkPdoDriver()
    {
        $drivers = PDO::getAvailableDrivers();
        $ok = in_array('mysql', $drivers, true);

        return $this->result(
            'Driver PDO MySQL',
            $ok,
            $ok ? 'pdo_mysql incarcat' : 'lipseste pdo_mysql (drivere: ' . implode(', ', $drivers) . ')'
        );
    }

    private function checkDatabaseConnection(&$pdo)
    {
        try {
            require $this->db_path;

            if (!isset($pdo) || !$pdo instanceof PDO) {
                return $this->result('Conexiune baza de date', false, 'db_connection.php nu a definit $pdo');
            }

            $version = $pdo->query('SELECT VERSION()')->fetchColumn();

            return $this->result('Conexiune baza de date', true, 'MySQL ' . $version);
        } catch (Throwable $e) {
            return $this->result('Conexiune baza de date', false, $e->getMessage());
        }
    }

    private function checkCharset(PDO $pdo)
    {
        try {
            $charset = $pdo->query("SELECT @@character_set_database")->fetchColumn();
            $ok = strpos($charset, 'utf8') === 0;

            return $this->result('Charset baza de date', $ok, $charset);
        } catch (Throwable $e) {
            return $this->result('Charset baza de date', false, $e->getMessage());
        }
    }

    private function checkTables(PDO $pdo)
    {
        try {
            $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            $count = count($tables);

            if ($count === 0) {
                return $this->result('Tabele in baza de date', false, 'Baza de date nu contine nicio tabela');
            }

            return $this->result('Tabele in baza de date', true, $count . ' tabele: ' . implode(', ', $tables));
        } catch (Throwable $e) {
            return $this->result('Tabele in baza de date', false, $e->getMessage());
        }
    }

    private function checkWritableTemp()
    {
        $dir = sys_get_temp_dir();
        $ok = is_writable($dir);

        return $this->result('Director temporar scriibil', $ok, $dir);
    }
}
