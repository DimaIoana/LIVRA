-- Login de back office cu parola criptata.
--
-- Tabela `users` a fost creata cu parola in text clar (`varchar(50)`). O trecem
-- pe hash bcrypt generat de `password_hash()` din PHP:
--   - hash-ul bcrypt are 60 de caractere, deci coloana urca la VARCHAR(255)
--     (loc si pentru algoritmi mai noi, ex. argon2id, care sunt mai lungi);
--   - parolele NU se mai pot citi din baza de date, nici de admin. Verificarea
--     se face doar cu `password_verify()` (vezi src/backend/UserRepository.php).
--
-- Coloane noi:
--   Nume          - numele afisat in back office ("Buna, Ioana")
--   Rol           - deocamdata doar 'admin'; lasat pentru cand vor exista si
--                   operatori cu drepturi mai mici
--   Creat_la      - cand a fost facut contul
--   Ultima_logare - actualizata la fiecare login reusit
--
-- `login` devine UNIQUE: doi utilizatori cu acelasi login ar face autentificarea
-- ambigua.
--
-- ATENTIE: nu e idempotent (ADD COLUMN / ADD UNIQUE pica daca exista deja).
-- Se ruleaza o singura data.

ALTER TABLE `users`
    MODIFY `parola` VARCHAR(255) NOT NULL COMMENT 'hash bcrypt (password_hash), niciodata parola in clar',
    ADD COLUMN `Nume` VARCHAR(100) NOT NULL DEFAULT '' AFTER `parola`,
    ADD COLUMN `Rol` VARCHAR(30) NOT NULL DEFAULT 'admin' AFTER `Nume`,
    ADD COLUMN `Creat_la` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `Rol`,
    ADD COLUMN `Ultima_logare` DATETIME NULL DEFAULT NULL AFTER `Creat_la`,
    ADD UNIQUE KEY `uq_users_login` (`login`);

-- Contul existent (login `ioana`, parola in clar `1234`) primeste hash-ul
-- parolei `1234`. Hash-ul e generat cu password_hash('1234', PASSWORD_DEFAULT);
-- bcrypt include sarea in interiorul sirului, de aceea arata asa lung.
--
-- Parola ramane `1234` la login, dar in baza de date nu mai apare nicaieri.
-- Se schimba cu: php tools/hash_parola.php <parola noua>
UPDATE `users`
   SET `parola` = '$2y$10$Gbs59droLEB1MJpCPDDAp.RcupmRPS9hMQ10BLVFJqUNDE3QbA1im',
       `Nume` = 'Ioana',
       `Rol` = 'admin'
 WHERE `login` = 'ioana';
