<?php

/**
 * Utilizatorii de back office si autentificarea lor (tabela `users`).
 *
 * Parolele se tin doar ca hash bcrypt (`password_hash`), nu in text clar:
 * cine citeste baza de date nu poate afla parola, iar verificarea se face
 * comparand hash-uri cu `password_verify()`.
 *
 * Un hash bcrypt isi tine sarea in interior, deci nu e nevoie de o coloana
 * separata pentru ea.
 */
class UserRepository
{
    /**
     * Hash de referinta pentru cazul "login inexistent": il verificam degeaba,
     * ca raspunsul sa dureze la fel ca la un login existent cu parola gresita.
     * Altfel timpul de raspuns ar spune care login-uri exista in baza.
     * (E hash-ul sirului 'x', dar valoarea nu conteaza - nu se compara nimic real.)
     */
    const HASH_FICTIV = '$2y$10$Em4l.x0cJUAojGmNvfm7ueFVFf/BJMiv/zQlCsZErLg1dPZswXCRq';

    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Verifica perechea login + parola.
     *
     * @return array|null utilizatorul (ID, login, Nume, Rol) sau null daca
     *                    login-ul nu exista ori parola e gresita. Nu spunem
     *                    care din doua: ar ajuta pe cine ghiceste conturi.
     */
    public function autentifica($login, $parola)
    {
        $login = trim((string) $login);
        $parola = (string) $parola;

        if ($login === '' || $parola === '') {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT ID, `login`, parola, Nume, Rol FROM users WHERE `login` = :login');
        $stmt->execute(['login' => $login]);
        $user = $stmt->fetch();

        if ($user === false) {
            password_verify($parola, self::HASH_FICTIV);

            return null;
        }

        if (!password_verify($parola, $user['parola'])) {
            return null;
        }

        // Daca PHP a trecut intre timp pe un algoritm mai tare (sau pe un cost
        // mai mare), refacem hash-ul acum, cand avem parola in mana.
        if (password_needs_rehash($user['parola'], PASSWORD_DEFAULT)) {
            $this->schimbaParola($user['ID'], $parola);
        }

        $this->marcheazaLogare($user['ID']);

        return [
            'ID' => (int) $user['ID'],
            'login' => $user['login'],
            'Nume' => $user['Nume'] !== '' ? $user['Nume'] : $user['login'],
            'Rol' => $user['Rol'],
        ];
    }

    /**
     * Cont nou de back office.
     *
     * @return int ID-ul creat.
     */
    public function creeaza($login, $parola, $nume = '', $rol = 'admin')
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (`login`, parola, Nume, Rol) VALUES (:login, :parola, :nume, :rol)'
        );

        $stmt->execute([
            'login' => trim((string) $login),
            'parola' => self::hash($parola),
            'nume' => trim((string) $nume),
            'rol' => $rol,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** Pune o parola noua (hash-uita) pe un cont existent. */
    public function schimbaParola($id, $parolaNoua)
    {
        $stmt = $this->pdo->prepare('UPDATE users SET parola = :parola WHERE ID = :id');
        $stmt->execute(['parola' => self::hash($parolaNoua), 'id' => (int) $id]);

        return $stmt->rowCount() > 0;
    }

    public function loginExista($login)
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE `login` = :login');
        $stmt->execute(['login' => trim((string) $login)]);

        return $stmt->fetchColumn() !== false;
    }

    /** Cate conturi de back office exista (folosit ca sa avertizam daca nu e niciunul). */
    public function numarConturi()
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    /** Hash-ul unei parole, cu algoritmul implicit al PHP-ului (azi bcrypt). */
    public static function hash($parola)
    {
        return password_hash((string) $parola, PASSWORD_DEFAULT);
    }

    private function marcheazaLogare($id)
    {
        $stmt = $this->pdo->prepare('UPDATE users SET Ultima_logare = NOW() WHERE ID = :id');
        $stmt->execute(['id' => (int) $id]);
    }
}
