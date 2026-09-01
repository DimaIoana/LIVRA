-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Gazdă: 127.0.0.1
-- Timp de generare: aug. 17, 2026 la 03:40 PM
-- Versiune server: 10.4.32-MariaDB
-- Versiune PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Bază de date: `sameday_company`
--

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `users`
--

CREATE TABLE `users` (
  `ID` int(11) NOT NULL,
  `login` varchar(50) NOT NULL,
  `parola` varchar(255) NOT NULL COMMENT 'hash bcrypt (password_hash), niciodata parola in clar',
  `Nume` varchar(100) NOT NULL DEFAULT '',
  `Rol` varchar(30) NOT NULL DEFAULT 'admin',
  `Creat_la` datetime NOT NULL DEFAULT current_timestamp(),
  `Ultima_logare` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Eliminarea datelor din tabel `users`
--

INSERT INTO `users` (`ID`, `login`, `parola`, `Nume`, `Rol`, `Creat_la`, `Ultima_logare`) VALUES
(1, 'ioana', '$2y$10$Gbs59droLEB1MJpCPDDAp.RcupmRPS9hMQ10BLVFJqUNDE3QbA1im', 'Ioana', 'admin', '2026-08-04 15:57:30', '2026-08-17 16:35:41'),
(2, 'cvpers', '$2y$10$tfuDnCFHuB.RuYhH20MEeu9MGUvC.r74AdX9jhxDDRuBn2CNVsgAe', 'Vizitator CV', 'admin', '2026-08-17 16:31:33', '2026-08-17 16:36:38');

--
-- Indexuri pentru tabele eliminate
--

--
-- Indexuri pentru tabele `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uq_users_login` (`login`);

--
-- AUTO_INCREMENT pentru tabele eliminate
--

--
-- AUTO_INCREMENT pentru tabele `users`
--
ALTER TABLE `users`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
