<?php

/**
 * Utilitar de linie de comanda pentru conturile de back office (tabela `users`).
 *
 * Parolele fiind criptate, nu se mai pot pune de mana in phpMyAdmin - de aceea
 * exista scriptul asta.
 *
 * Folosire (din radacina proiectului):
 *   php tools/hash_parola.php lista
 *   php tools/hash_parola.php adauga <login> <parola> [nume]
 *   php tools/hash_parola.php schimba <login> <parola noua>
 *   php tools/hash_parola.php hash <parola>       -> doar afiseaza hash-ul
 *
 * Daca `php` nu e in PATH pe Windows: C:\xampp\php\php.exe tools\hash_parola.php ...
 */

if (PHP_SAPI !== 'cli') {
    exit("Scriptul se ruleaza doar din linia de comanda.\n");
}

require __DIR__ . '/../src/database/db_connection.php';
require __DIR__ . '/../src/backend/UserRepository.php';

$utilizatori = new UserRepository($pdo);

$comanda = $argv[1] ?? '';

switch ($comanda) {
    case 'lista':
        $randuri = $pdo->query('SELECT ID, `login`, Nume, Rol, Ultima_logare FROM users ORDER BY ID')->fetchAll();

        if (!$randuri) {
            exit("Nu exista niciun cont. Adauga unul: php tools/hash_parola.php adauga <login> <parola>\n");
        }

        foreach ($randuri as $r) {
            printf(
                "#%d  %-20s %-20s %-10s ultima logare: %s\n",
                $r['ID'],
                $r['login'],
                $r['Nume'],
                $r['Rol'],
                $r['Ultima_logare'] ?? '-'
            );
        }
        break;

    case 'adauga':
        $login = $argv[2] ?? '';
        $parola = $argv[3] ?? '';
        $nume = $argv[4] ?? $login;

        if ($login === '' || $parola === '') {
            exit("Folosire: php tools/hash_parola.php adauga <login> <parola> [nume]\n");
        }

        if ($utilizatori->loginExista($login)) {
            exit("Login-ul '{$login}' exista deja. Foloseste 'schimba' daca vrei alta parola.\n");
        }

        $id = $utilizatori->creeaza($login, $parola, $nume);
        echo "Cont creat: #{$id} {$login}\n";
        break;

    case 'schimba':
        $login = $argv[2] ?? '';
        $parola = $argv[3] ?? '';

        if ($login === '' || $parola === '') {
            exit("Folosire: php tools/hash_parola.php schimba <login> <parola noua>\n");
        }

        $stmt = $pdo->prepare('SELECT ID FROM users WHERE `login` = :login');
        $stmt->execute(['login' => $login]);
        $id = $stmt->fetchColumn();

        if ($id === false) {
            exit("Nu exista contul '{$login}'.\n");
        }

        $utilizatori->schimbaParola($id, $parola);
        echo "Parola schimbata pentru '{$login}'.\n";
        break;

    case 'hash':
        $parola = $argv[2] ?? '';

        if ($parola === '') {
            exit("Folosire: php tools/hash_parola.php hash <parola>\n");
        }

        echo UserRepository::hash($parola), "\n";
        break;

    default:
        echo "Conturi de back office (tabela users).\n\n";
        echo "  php tools/hash_parola.php lista\n";
        echo "  php tools/hash_parola.php adauga <login> <parola> [nume]\n";
        echo "  php tools/hash_parola.php schimba <login> <parola noua>\n";
        echo "  php tools/hash_parola.php hash <parola>\n";
}
