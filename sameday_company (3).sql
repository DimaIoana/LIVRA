-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Gazdă: 127.0.0.1
-- Timp de generare: aug. 17, 2026 la 02:44 PM
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
-- Structură tabel pentru tabel `clienti`
--

CREATE TABLE `clienti` (
  `ClientID` int(11) NOT NULL,
  `Nume` varchar(50) NOT NULL,
  `Tip` enum('Persoana fizica','Persoana juridica') NOT NULL,
  `Email` varchar(150) NOT NULL,
  `Telefon` varchar(20) NOT NULL,
  `Oras` varchar(30) NOT NULL,
  `Data_inregistrare` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Eliminarea datelor din tabel `clienti`
--

INSERT INTO `clienti` (`ClientID`, `Nume`, `Tip`, `Email`, `Telefon`, `Oras`, `Data_inregistrare`) VALUES
(1, 'Ana Popescu', 'Persoana fizica', 'ana.popescu@email.co', '0721111111', 'Bucuresti', '2025-01-10'),
(2, 'Mihai Ionescu', 'Persoana fizica', 'mihai.ionescu@email.', '0722222222', 'Cluj-Napoca', '2025-01-15'),
(3, 'TechSol SRL', 'Persoana juridica', 'contact@techsol.ro', '0711111111', 'Bucuresti', '2025-01-20'),
(4, 'Elena Dumitru', 'Persoana fizica', 'elena.dumitru@email.', '0724444444', 'Timisoara', '2025-02-01'),
(5, 'Globex Trading SRL', 'Persoana juridica', 'office@globex.ro', '0264555555', 'Cluj-Napoca', '2025-02-05'),
(6, 'Andrei Stan', 'Persoana fizica', 'andrei.stan@email.co', '0726666666', 'Iasi', '2025-02-10'),
(7, 'Carmen Vasilescu', 'Persoana fizica', 'carmen.v@email.com', '0727777777', 'Constanta', '2025-02-15'),
(8, 'MediPlus SRL', 'Persoana juridica', 'office@mediplus.ro', '0256888888', 'Timisoara', '2025-02-20'),
(9, 'Radu Marin', 'Persoana fizica', 'radu.marin@email.com', '0728999999', 'Brasov', '2025-03-01'),
(10, 'FashionHub SRL', 'Persoana juridica', 'contact@fashionhub.r', '0232101010', 'Iasi', '2025-03-05'),
(17, 'marcel', 'Persoana fizica', 'marcel@mail.com', '1111111122', 'Iasi', '2026-07-17');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `comenzi`
--

CREATE TABLE `comenzi` (
  `ComandaID` int(11) NOT NULL,
  `ClientID` int(11) NOT NULL,
  `Data_comanda` datetime NOT NULL,
  `Status` enum('Noua','In procesare','Trimisa','Anulata') NOT NULL DEFAULT 'Noua',
  `Total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Observatii` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Eliminarea datelor din tabel `comenzi`
--

INSERT INTO `comenzi` (`ComandaID`, `ClientID`, `Data_comanda`, `Status`, `Total`, `Observatii`) VALUES
(1, 1, '2026-07-19 09:20:00', 'Trimisa', 2562.00, 'Livrare la birou, dupa ora 10.'),
(2, 2, '2026-07-20 14:05:00', 'Trimisa', 100.80, 'Sunati inainte de livrare.'),
(3, 3, '2026-07-21 11:40:00', 'Trimisa', 2184.00, 'Factura pe firma, cod fiscal in contract.'),
(4, 4, '2026-07-22 16:15:00', 'Trimisa', 714.00, NULL),
(5, 5, '2026-07-23 08:50:00', 'Trimisa', 6258.00, 'Comanda mare, atentie la ambalare.'),
(6, 6, '2026-07-24 10:30:00', 'Trimisa', 105.00, 'Cadou, fara factura in colet.'),
(7, 7, '2026-07-25 13:00:00', 'Trimisa', 5040.00, 'Produs fragil, marcati coletul.'),
(8, 8, '2026-07-26 09:05:00', 'Trimisa', 277.20, 'Livrare in intervalul 09-12.'),
(9, 9, '2026-07-26 17:45:00', 'Anulata', 1008.00, 'Anulata de client, s-a razgandit.'),
(10, 10, '2026-07-27 12:25:00', 'Trimisa', 3339.00, 'Se plateste ramburs la livrare.'),
(11, 7, '2026-07-28 16:45:23', 'Trimisa', 6090.00, NULL),
(12, 2, '2026-07-28 15:57:00', 'Trimisa', 7098.00, 'Lasati coletul la receptie.'),
(13, 10, '2026-07-18 10:12:00', 'Trimisa', 1398.60, 'Ambalare pentru produse fragile.'),
(14, 7, '2026-07-25 12:56:00', 'Trimisa', 1709.40, 'Livrare dupa ora 16:00.'),
(15, 4, '2026-07-27 15:28:00', 'Trimisa', 7942.20, NULL),
(16, 10, '2026-07-24 15:02:00', 'Trimisa', 9702.00, NULL),
(17, 2, '2026-07-25 19:22:00', 'Trimisa', 9660.00, NULL),
(18, 8, '2026-07-21 09:28:00', 'Trimisa', 163.80, NULL),
(19, 6, '2026-07-17 09:09:00', 'Trimisa', 5107.20, NULL),
(20, 7, '2026-07-22 19:28:00', 'Trimisa', 8148.00, NULL),
(21, 5, '2026-07-22 16:32:00', 'Trimisa', 6090.00, 'Ambalare pentru produse fragile.'),
(22, 2, '2026-07-16 16:52:00', 'Trimisa', 6392.40, 'Sunati inainte de livrare.'),
(23, 4, '2026-07-25 19:57:00', 'Trimisa', 709.80, NULL),
(24, 9, '2026-07-19 11:04:00', 'Trimisa', 1218.00, 'Am nevoie de factura pe firma.'),
(25, 3, '2026-07-24 10:18:00', 'Trimisa', 5082.00, 'Lasati coletul la receptie.'),
(26, 4, '2026-07-25 09:56:00', 'Trimisa', 2184.00, NULL),
(27, 10, '2026-07-23 15:18:00', 'Trimisa', 2331.00, 'Lasati coletul la receptie.'),
(28, 10, '2026-07-16 13:50:00', 'Trimisa', 7656.60, NULL),
(29, 1, '2026-07-27 16:00:00', 'Trimisa', 5859.00, 'Sunati inainte de livrare.'),
(30, 5, '2026-07-21 16:57:00', 'Trimisa', 3297.00, NULL),
(31, 4, '2026-07-29 10:03:00', 'Trimisa', 2541.00, NULL),
(32, 2, '2026-07-16 14:29:00', 'Trimisa', 2814.00, NULL),
(33, 6, '2026-07-23 18:19:00', 'Trimisa', 3032.40, NULL),
(34, 7, '2026-07-27 17:55:00', 'Trimisa', 3738.00, NULL),
(35, 10, '2026-07-24 08:50:00', 'Trimisa', 1289.40, 'Am nevoie de factura pe firma.'),
(36, 17, '2026-07-20 19:11:00', 'Trimisa', 3738.00, NULL),
(37, 1, '2026-08-01 00:00:00', 'Anulata', 42.00, 'comanda de test decizie'),
(39, 7, '2026-08-03 14:14:03', 'In procesare', 126.00, NULL),
(40, 7, '2026-08-11 16:11:04', 'In procesare', 357.00, NULL),
(42, 7, '2026-08-11 16:22:43', 'In procesare', 126.00, NULL),
(44, 7, '2026-08-11 16:45:25', 'Noua', 126.00, NULL);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `comenzi_financiar`
--

CREATE TABLE `comenzi_financiar` (
  `FinanciarID` int(11) NOT NULL,
  `ComandaID` int(11) NOT NULL,
  `incasare` decimal(10,2) NOT NULL,
  `cost_marfa` decimal(10,2) NOT NULL,
  `cost_carburant` decimal(10,2) NOT NULL,
  `profit` decimal(10,2) NOT NULL,
  `Data_inregistrare` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Eliminarea datelor din tabel `comenzi_financiar`
--

INSERT INTO `comenzi_financiar` (`FinanciarID`, `ComandaID`, `incasare`, `cost_marfa`, `cost_carburant`, `profit`, `Data_inregistrare`) VALUES
(2, 39, 126.00, 11.34, 261.99, -147.33, '2026-08-03 14:15:13'),
(6, 40, 357.00, 38.64, 518.56, -200.20, '2026-08-11 16:20:50'),
(7, 42, 126.00, 11.34, 259.28, -144.62, '2026-08-11 16:23:33');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `comenzi_produse`
--

CREATE TABLE `comenzi_produse` (
  `LinieID` int(11) NOT NULL,
  `ComandaID` int(11) NOT NULL,
  `Product_ID` varchar(10) NOT NULL,
  `Product_Name` varchar(100) NOT NULL,
  `Pret_unitar` decimal(10,2) NOT NULL,
  `Cantitate` int(11) NOT NULL,
  `Subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Eliminarea datelor din tabel `comenzi_produse`
--

INSERT INTO `comenzi_produse` (`LinieID`, `ComandaID`, `Product_ID`, `Product_Name`, `Pret_unitar`, `Cantitate`, `Subtotal`) VALUES
(1, 1, 'PRD-0001', 'Laptop Pro', 2520.00, 1, 2520.00),
(2, 1, 'PRD-0005', 'USB-C Cable', 21.00, 2, 42.00),
(4, 2, 'PRD-0002', 'Mouse Wireless', 33.60, 3, 100.80),
(5, 3, 'PRD-0005', 'USB-C Cable', 21.00, 4, 84.00),
(6, 3, 'PRD-0006', 'Monitor 27', 1050.00, 2, 2100.00),
(8, 4, 'PRD-0003', 'Office Chair', 504.00, 1, 504.00),
(9, 4, 'PRD-0004', 'Desk Lamp', 105.00, 2, 210.00),
(11, 5, 'PRD-0001', 'Laptop Pro', 2520.00, 2, 5040.00),
(12, 5, 'PRD-0002', 'Mouse Wireless', 33.60, 5, 168.00),
(13, 5, 'PRD-0006', 'Monitor 27', 1050.00, 1, 1050.00),
(14, 6, 'PRD-0004', 'Desk Lamp', 105.00, 1, 105.00),
(15, 7, 'PRD-0007', 'pc ioana', 5040.00, 1, 5040.00),
(16, 8, 'PRD-0002', 'Mouse Wireless', 33.60, 2, 67.20),
(17, 8, 'PRD-0005', 'USB-C Cable', 21.00, 10, 210.00),
(19, 9, 'PRD-0003', 'Office Chair', 504.00, 2, 1008.00),
(20, 10, 'PRD-0001', 'Laptop Pro', 2520.00, 1, 2520.00),
(21, 10, 'PRD-0003', 'Office Chair', 504.00, 1, 504.00),
(22, 10, 'PRD-0004', 'Desk Lamp', 105.00, 3, 315.00),
(23, 11, 'PRD-0006', 'Monitor 27', 1050.00, 1, 1050.00),
(24, 11, 'PRD-0007', 'pc ioana', 5040.00, 1, 5040.00),
(25, 12, 'PRD-0003', 'Office Chair', 504.00, 2, 1008.00),
(26, 12, 'PRD-0007', 'pc ioana', 5040.00, 1, 5040.00),
(27, 12, 'PRD-0006', 'Monitor 27', 1050.00, 1, 1050.00),
(28, 13, 'PRD-0002', 'Mouse Wireless', 33.60, 1, 33.60),
(29, 13, 'PRD-0006', 'Monitor 27', 1050.00, 1, 1050.00),
(30, 13, 'PRD-0004', 'Desk Lamp', 105.00, 3, 315.00),
(31, 14, 'PRD-0002', 'Mouse Wireless', 33.60, 4, 134.40),
(32, 14, 'PRD-0005', 'USB-C Cable', 21.00, 1, 21.00),
(33, 14, 'PRD-0003', 'Office Chair', 504.00, 1, 504.00),
(34, 14, 'PRD-0006', 'Monitor 27', 1050.00, 1, 1050.00),
(35, 15, 'PRD-0007', 'pc ioana', 5040.00, 1, 5040.00),
(36, 15, 'PRD-0002', 'Mouse Wireless', 33.60, 2, 67.20),
(37, 15, 'PRD-0004', 'Desk Lamp', 105.00, 3, 315.00),
(38, 15, 'PRD-0001', 'Laptop Pro', 2520.00, 1, 2520.00),
(39, 16, 'PRD-0007', 'pc ioana', 5040.00, 1, 5040.00),
(40, 16, 'PRD-0001', 'Laptop Pro', 2520.00, 1, 2520.00),
(41, 16, 'PRD-0005', 'USB-C Cable', 21.00, 2, 42.00),
(42, 16, 'PRD-0006', 'Monitor 27', 1050.00, 2, 2100.00),
(43, 17, 'PRD-0007', 'pc ioana', 5040.00, 1, 5040.00),
(44, 17, 'PRD-0006', 'Monitor 27', 1050.00, 2, 2100.00),
(45, 17, 'PRD-0001', 'Laptop Pro', 2520.00, 1, 2520.00),
(46, 18, 'PRD-0002', 'Mouse Wireless', 33.60, 3, 100.80),
(47, 18, 'PRD-0005', 'USB-C Cable', 21.00, 3, 63.00),
(48, 19, 'PRD-0002', 'Mouse Wireless', 33.60, 2, 67.20),
(49, 19, 'PRD-0007', 'pc ioana', 5040.00, 1, 5040.00),
(50, 20, 'PRD-0006', 'Monitor 27', 1050.00, 2, 2100.00),
(51, 20, 'PRD-0007', 'pc ioana', 5040.00, 1, 5040.00),
(52, 20, 'PRD-0003', 'Office Chair', 504.00, 2, 1008.00),
(53, 21, 'PRD-0006', 'Monitor 27', 1050.00, 1, 1050.00),
(54, 21, 'PRD-0007', 'pc ioana', 5040.00, 1, 5040.00),
(55, 22, 'PRD-0003', 'Office Chair', 504.00, 2, 1008.00),
(56, 22, 'PRD-0004', 'Desk Lamp', 105.00, 2, 210.00),
(57, 22, 'PRD-0007', 'pc ioana', 5040.00, 1, 5040.00),
(58, 22, 'PRD-0002', 'Mouse Wireless', 33.60, 4, 134.40),
(59, 23, 'PRD-0003', 'Office Chair', 504.00, 1, 504.00),
(60, 23, 'PRD-0002', 'Mouse Wireless', 33.60, 3, 100.80),
(61, 23, 'PRD-0004', 'Desk Lamp', 105.00, 1, 105.00),
(62, 24, 'PRD-0003', 'Office Chair', 504.00, 2, 1008.00),
(63, 24, 'PRD-0004', 'Desk Lamp', 105.00, 2, 210.00),
(64, 25, 'PRD-0007', 'pc ioana', 5040.00, 1, 5040.00),
(65, 25, 'PRD-0005', 'USB-C Cable', 21.00, 2, 42.00),
(66, 26, 'PRD-0006', 'Monitor 27', 1050.00, 2, 2100.00),
(67, 26, 'PRD-0005', 'USB-C Cable', 21.00, 4, 84.00),
(68, 27, 'PRD-0003', 'Office Chair', 504.00, 2, 1008.00),
(69, 27, 'PRD-0005', 'USB-C Cable', 21.00, 5, 105.00),
(70, 27, 'PRD-0006', 'Monitor 27', 1050.00, 1, 1050.00),
(71, 27, 'PRD-0002', 'Mouse Wireless', 33.60, 5, 168.00),
(72, 28, 'PRD-0005', 'USB-C Cable', 21.00, 3, 63.00),
(73, 28, 'PRD-0001', 'Laptop Pro', 2520.00, 1, 2520.00),
(74, 28, 'PRD-0002', 'Mouse Wireless', 33.60, 1, 33.60),
(75, 28, 'PRD-0007', 'pc ioana', 5040.00, 1, 5040.00),
(76, 29, 'PRD-0004', 'Desk Lamp', 105.00, 3, 315.00),
(77, 29, 'PRD-0007', 'pc ioana', 5040.00, 1, 5040.00),
(78, 29, 'PRD-0003', 'Office Chair', 504.00, 1, 504.00),
(79, 30, 'PRD-0001', 'Laptop Pro', 2520.00, 1, 2520.00),
(80, 30, 'PRD-0005', 'USB-C Cable', 21.00, 5, 105.00),
(81, 30, 'PRD-0003', 'Office Chair', 504.00, 1, 504.00),
(82, 30, 'PRD-0002', 'Mouse Wireless', 33.60, 5, 168.00),
(83, 31, 'PRD-0001', 'Laptop Pro', 2520.00, 1, 2520.00),
(84, 31, 'PRD-0005', 'USB-C Cable', 21.00, 1, 21.00),
(85, 32, 'PRD-0004', 'Desk Lamp', 105.00, 2, 210.00),
(86, 32, 'PRD-0005', 'USB-C Cable', 21.00, 4, 84.00),
(87, 32, 'PRD-0001', 'Laptop Pro', 2520.00, 1, 2520.00),
(88, 33, 'PRD-0005', 'USB-C Cable', 21.00, 3, 63.00),
(89, 33, 'PRD-0004', 'Desk Lamp', 105.00, 3, 315.00),
(90, 33, 'PRD-0001', 'Laptop Pro', 2520.00, 1, 2520.00),
(91, 33, 'PRD-0002', 'Mouse Wireless', 33.60, 4, 134.40),
(92, 34, 'PRD-0004', 'Desk Lamp', 105.00, 2, 210.00),
(93, 34, 'PRD-0003', 'Office Chair', 504.00, 2, 1008.00),
(94, 34, 'PRD-0001', 'Laptop Pro', 2520.00, 1, 2520.00),
(95, 35, 'PRD-0003', 'Office Chair', 504.00, 2, 1008.00),
(96, 35, 'PRD-0002', 'Mouse Wireless', 33.60, 4, 134.40),
(97, 35, 'PRD-0004', 'Desk Lamp', 105.00, 1, 105.00),
(98, 35, 'PRD-0005', 'USB-C Cable', 21.00, 2, 42.00),
(99, 36, 'PRD-0001', 'Laptop Pro', 2520.00, 1, 2520.00),
(100, 36, 'PRD-0005', 'USB-C Cable', 21.00, 2, 42.00),
(101, 36, 'PRD-0002', 'Mouse Wireless', 33.60, 5, 168.00),
(102, 36, 'PRD-0003', 'Office Chair', 504.00, 2, 1008.00),
(103, 37, 'PRD-0005', 'USB-C Cable', 21.00, 2, 42.00),
(105, 39, 'PRD-0019', 'USB Hub 4 Port', 126.00, 1, 126.00),
(106, 40, 'PRD-0018', 'Laptop Bag', 294.00, 1, 294.00),
(107, 40, 'PRD-0017', 'Mousepad XL', 63.00, 1, 63.00),
(109, 42, 'PRD-0019', 'USB Hub 4 Port', 126.00, 1, 126.00),
(111, 44, 'PRD-0019', 'USB Hub 4 Port', 126.00, 1, 126.00);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `expedieri`
--

CREATE TABLE `expedieri` (
  `ExpediereID` int(11) NOT NULL,
  `awb` varchar(20) DEFAULT NULL,
  `ClientID` int(11) NOT NULL,
  `SoferID` int(11) NOT NULL,
  `RutaID` int(11) NOT NULL,
  `LinieID` int(11) DEFAULT NULL,
  `Data_expediere` datetime NOT NULL,
  `Data_livrare_estimata` datetime NOT NULL,
  `Data_livrare_efectiva` datetime DEFAULT NULL,
  `Status_expediere` enum('In tranzit','Livrat','Returnat','Intarziat','Anulat') NOT NULL,
  `stoc_scazut` tinyint(1) NOT NULL DEFAULT 0,
  `Valoare_expediere` decimal(10,2) NOT NULL,
  `cost_carburant` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Eliminarea datelor din tabel `expedieri`
--

INSERT INTO `expedieri` (`ExpediereID`, `awb`, `ClientID`, `SoferID`, `RutaID`, `LinieID`, `Data_expediere`, `Data_livrare_estimata`, `Data_livrare_efectiva`, `Status_expediere`, `stoc_scazut`, `Valoare_expediere`, `cost_carburant`) VALUES
(1, 'AWB20260719000001', 1, 5, 25, 1, '2026-07-19 11:20:00', '2026-07-19 13:14:00', '2026-07-19 13:14:00', 'Livrat', 1, 2520.00, 136.94),
(2, 'AWB20260719000002', 1, 1, 25, 2, '2026-07-19 11:20:00', '2026-07-19 13:14:00', '2026-07-19 13:14:00', 'Livrat', 1, 42.00, 136.94),
(3, 'AWB20260720000003', 2, 1, 20, 4, '2026-07-20 16:05:00', '2026-07-21 10:48:00', '2026-07-21 10:48:00', 'Livrat', 1, 100.80, 682.28),
(4, 'AWB20260721000004', 3, 2, 25, 5, '2026-07-21 13:40:00', '2026-07-21 15:34:00', '2026-07-21 15:34:00', 'Livrat', 1, 84.00, 136.94),
(5, 'AWB20260721000005', 3, 4, 19, 6, '2026-07-21 13:40:00', '2026-07-21 17:44:00', '2026-07-21 17:44:00', 'Livrat', 1, 2100.00, 293.09),
(6, 'AWB20260722000006', 4, 2, 17, 8, '2026-07-22 18:15:00', '2026-07-22 19:37:00', '2026-07-22 19:37:00', 'Livrat', 1, 504.00, 62.46),
(7, 'AWB20260722000007', 4, 3, 29, 9, '2026-07-22 18:15:00', '2026-07-23 11:37:00', '2026-07-23 11:37:00', 'Livrat', 1, 210.00, 560.96),
(8, 'AWB20260723000008', 5, 6, 14, 11, '2026-07-23 10:50:00', '2026-07-23 15:48:00', '2026-07-23 15:48:00', 'Livrat', 1, 5040.00, 321.92),
(9, 'AWB20260723000009', 5, 2, 20, 12, '2026-07-23 10:50:00', '2026-07-23 20:33:00', '2026-07-23 20:33:00', 'Livrat', 1, 168.00, 682.28),
(10, 'AWB20260723000010', 5, 4, 20, 13, '2026-07-23 10:50:00', '2026-07-23 20:33:00', '2026-07-23 20:33:00', 'Livrat', 1, 1050.00, 682.28),
(11, 'AWB20260724000011', 6, 2, 28, 14, '2026-07-24 12:30:00', '2026-07-24 21:06:00', '2026-07-24 21:06:00', 'Livrat', 1, 105.00, 535.74),
(12, 'AWB20260725000012', 7, 2, 27, 15, '2026-07-25 15:00:00', '2026-07-25 22:00:00', '2026-07-25 22:00:00', 'Livrat', 1, 5040.00, 456.46),
(13, 'AWB20260726000013', 8, 4, 23, 16, '2026-07-26 11:05:00', '2026-07-27 09:32:00', '2026-07-27 09:32:00', 'Livrat', 1, 67.20, 861.26),
(14, 'AWB20260726000014', 8, 6, 29, 17, '2026-07-26 11:05:00', '2026-07-26 19:27:00', '2026-07-26 19:27:00', 'Livrat', 1, 210.00, 560.96),
(15, 'AWB20260727000015', 10, 1, 28, 20, '2026-07-27 14:25:00', '2026-07-28 08:01:00', '2026-07-28 08:01:00', 'Livrat', 1, 2520.00, 535.74),
(16, 'AWB20260727000016', 10, 6, 22, 21, '2026-07-27 14:25:00', '2026-07-27 19:17:00', '2026-07-27 19:17:00', 'Livrat', 1, 504.00, 302.70),
(17, 'AWB20260727000017', 10, 4, 28, 22, '2026-07-27 14:25:00', '2026-07-28 08:01:00', '2026-07-28 08:01:00', 'Livrat', 1, 315.00, 535.74),
(18, 'AWB20260728000018', 7, 3, 21, 23, '2026-07-28 18:45:23', '2026-07-29 07:21:00', '2026-07-29 07:21:00', 'Livrat', 1, 1050.00, 246.25),
(19, 'AWB20260728000019', 7, 4, 27, 24, '2026-07-28 18:45:23', '2026-07-29 10:46:00', '2026-07-29 10:46:00', 'Livrat', 1, 5040.00, 456.46),
(20, 'AWB20260728000020', 2, 5, 14, 25, '2026-07-28 17:57:00', '2026-07-29 07:55:00', '2026-07-29 07:55:00', 'Livrat', 1, 1008.00, 328.68),
(21, 'AWB20260728000021', 2, 4, 26, 26, '2026-07-28 17:57:00', '2026-07-29 08:23:00', '2026-07-29 08:23:00', 'Livrat', 1, 5040.00, 399.81),
(22, 'AWB20260728000022', 2, 6, 20, 27, '2026-07-28 17:57:00', '2026-07-29 12:40:00', '2026-07-29 12:40:00', 'Livrat', 1, 1050.00, 696.60),
(23, 'AWB20260718000023', 10, 2, 22, 28, '2026-07-18 12:12:00', '2026-07-18 17:04:00', '2026-07-18 17:04:00', 'Livrat', 1, 33.60, 309.05),
(24, 'AWB20260718000024', 10, 6, 22, 29, '2026-07-18 12:12:00', '2026-07-18 17:04:00', '2026-07-18 17:04:00', 'Livrat', 1, 1050.00, 309.05),
(25, 'AWB20260718000025', 10, 5, 28, 30, '2026-07-18 12:12:00', '2026-07-18 20:48:00', '2026-07-18 20:48:00', 'Livrat', 1, 315.00, 546.97),
(26, 'AWB20260725000026', 7, 4, 21, 31, '2026-07-25 14:56:00', '2026-07-25 18:31:00', '2026-07-25 18:31:00', 'Livrat', 1, 134.40, 251.41),
(27, 'AWB20260725000027', 7, 2, 21, 32, '2026-07-25 14:56:00', '2026-07-25 18:31:00', '2026-07-25 18:31:00', 'Livrat', 1, 21.00, 251.41),
(28, 'AWB20260725000028', 7, 2, 21, 33, '2026-07-25 14:56:00', '2026-07-25 18:31:00', '2026-07-25 18:31:00', 'Livrat', 1, 504.00, 251.41),
(29, 'AWB20260725000029', 7, 5, 21, 34, '2026-07-25 14:56:00', '2026-07-25 18:31:00', '2026-07-25 18:31:00', 'Livrat', 1, 1050.00, 251.41),
(30, 'AWB20260727000030', 4, 6, 29, 35, '2026-07-27 17:28:00', '2026-07-28 10:50:00', '2026-07-28 10:50:00', 'Livrat', 1, 5040.00, 572.73),
(31, 'AWB20260727000031', 4, 6, 23, 36, '2026-07-27 17:28:00', '2026-07-28 15:55:00', '2026-07-28 15:55:00', 'Livrat', 1, 67.20, 879.33),
(32, 'AWB20260727000032', 4, 2, 29, 37, '2026-07-27 17:28:00', '2026-07-28 10:50:00', '2026-07-28 10:50:00', 'Livrat', 1, 315.00, 572.73),
(33, 'AWB20260727000033', 4, 5, 17, 38, '2026-07-27 17:28:00', '2026-07-27 18:50:00', '2026-07-27 18:50:00', 'Livrat', 1, 2520.00, 63.77),
(34, 'AWB20260724000034', 10, 3, 28, 39, '2026-07-24 17:02:00', '2026-07-25 10:38:00', '2026-07-25 10:38:00', 'Livrat', 1, 5040.00, 546.97),
(35, 'AWB20260724000035', 10, 5, 28, 40, '2026-07-24 17:02:00', '2026-07-25 10:38:00', '2026-07-25 10:38:00', 'Livrat', 1, 2520.00, 546.97),
(36, 'AWB20260724000036', 10, 4, 22, 41, '2026-07-24 17:02:00', '2026-07-24 21:54:00', '2026-07-24 21:54:00', 'Livrat', 1, 42.00, 309.05),
(37, 'AWB20260724000037', 10, 6, 22, 42, '2026-07-24 17:02:00', '2026-07-24 21:54:00', '2026-07-24 21:54:00', 'Livrat', 1, 2100.00, 309.05),
(38, 'AWB20260725000038', 2, 2, 26, 43, '2026-07-25 21:22:00', '2026-07-26 11:48:00', '2026-07-26 11:48:00', 'Livrat', 1, 5040.00, 399.81),
(39, 'AWB20260725000039', 2, 3, 20, 44, '2026-07-25 21:22:00', '2026-07-26 16:05:00', '2026-07-26 16:05:00', 'Livrat', 1, 2100.00, 696.60),
(40, 'AWB20260725000040', 2, 2, 14, 45, '2026-07-25 21:22:00', '2026-07-26 11:20:00', '2026-07-26 11:20:00', 'Livrat', 1, 2520.00, 328.68),
(41, 'AWB20260721000041', 8, 6, 23, 46, '2026-07-21 11:28:00', '2026-07-22 09:55:00', '2026-07-22 09:55:00', 'Livrat', 1, 100.80, 879.33),
(42, 'AWB20260721000042', 8, 2, 29, 47, '2026-07-21 11:28:00', '2026-07-21 19:50:00', '2026-07-21 19:50:00', 'Livrat', 1, 63.00, 572.73),
(43, 'AWB20260717000043', 6, 3, 22, 48, '2026-07-17 11:09:00', '2026-07-17 16:01:00', '2026-07-17 16:01:00', 'Livrat', 1, 67.20, 309.05),
(44, 'AWB20260717000044', 6, 6, 28, 49, '2026-07-17 11:09:00', '2026-07-17 19:45:00', '2026-07-17 19:45:00', 'Livrat', 1, 5040.00, 546.97),
(45, 'AWB20260722000045', 7, 3, 21, 50, '2026-07-22 21:28:00', '2026-07-23 10:03:00', '2026-07-23 10:03:00', 'Livrat', 1, 2100.00, 251.41),
(46, 'AWB20260722000046', 7, 1, 27, 51, '2026-07-22 21:28:00', '2026-07-23 13:28:00', '2026-07-23 13:28:00', 'Livrat', 1, 5040.00, 466.03),
(47, 'AWB20260722000047', 7, 6, 21, 52, '2026-07-22 21:28:00', '2026-07-23 10:03:00', '2026-07-23 10:03:00', 'Livrat', 1, 1008.00, 251.41),
(48, 'AWB20260722000048', 5, 1, 20, 53, '2026-07-22 18:32:00', '2026-07-23 13:15:00', '2026-07-23 13:15:00', 'Livrat', 1, 1050.00, 696.60),
(49, 'AWB20260722000049', 5, 1, 26, 54, '2026-07-22 18:32:00', '2026-07-23 08:58:00', '2026-07-23 08:58:00', 'Livrat', 1, 5040.00, 399.81),
(50, 'AWB20260716000050', 2, 1, 14, 55, '2026-07-16 18:52:00', '2026-07-17 08:50:00', '2026-07-17 08:50:00', 'Livrat', 1, 1008.00, 328.68),
(51, 'AWB20260716000051', 2, 5, 26, 56, '2026-07-16 18:52:00', '2026-07-17 09:18:00', '2026-07-17 09:18:00', 'Livrat', 1, 210.00, 399.81),
(52, 'AWB20260716000052', 2, 1, 26, 57, '2026-07-16 18:52:00', '2026-07-17 09:18:00', '2026-07-17 09:18:00', 'Livrat', 1, 5040.00, 399.81),
(53, 'AWB20260716000053', 2, 5, 20, 58, '2026-07-16 18:52:00', '2026-07-17 13:35:00', '2026-07-17 13:35:00', 'Livrat', 1, 134.40, 696.60),
(54, 'AWB20260725000054', 4, 6, 17, 59, '2026-07-25 21:57:00', '2026-07-26 08:19:00', '2026-07-26 08:19:00', 'Livrat', 1, 504.00, 63.77),
(55, 'AWB20260725000055', 4, 2, 23, 60, '2026-07-25 21:57:00', '2026-07-26 20:24:00', '2026-07-26 20:24:00', 'Livrat', 1, 100.80, 879.33),
(56, 'AWB20260725000056', 4, 4, 29, 61, '2026-07-25 21:57:00', '2026-07-26 15:19:00', '2026-07-26 15:19:00', 'Livrat', 1, 105.00, 572.73),
(57, 'AWB20260719000057', 9, 1, 24, 62, '2026-07-19 13:04:00', '2026-07-19 16:19:00', '2026-07-19 16:19:00', 'Livrat', 1, 1008.00, 165.56),
(58, 'AWB20260719000058', 9, 1, 24, 63, '2026-07-19 13:04:00', '2026-07-19 16:19:00', '2026-07-19 16:19:00', 'Livrat', 1, 210.00, 165.56),
(59, 'AWB20260724000059', 3, 3, 25, 64, '2026-07-24 12:18:00', '2026-07-24 14:12:00', '2026-07-24 14:12:00', 'Livrat', 1, 5040.00, 139.81),
(60, 'AWB20260724000060', 3, 5, 25, 65, '2026-07-24 12:18:00', '2026-07-24 14:12:00', '2026-07-24 14:12:00', 'Livrat', 1, 42.00, 139.81),
(61, 'AWB20260725000061', 4, 2, 23, 66, '2026-07-25 11:56:00', '2026-07-26 10:23:00', '2026-07-26 10:23:00', 'Livrat', 1, 2100.00, 879.33),
(62, 'AWB20260725000062', 4, 4, 29, 67, '2026-07-25 11:56:00', '2026-07-25 20:18:00', '2026-07-25 20:18:00', 'Livrat', 1, 84.00, 572.73),
(63, 'AWB20260723000063', 10, 1, 22, 68, '2026-07-23 17:18:00', '2026-07-24 07:10:00', '2026-07-24 07:10:00', 'Livrat', 1, 1008.00, 309.05),
(64, 'AWB20260723000064', 10, 4, 22, 69, '2026-07-23 17:18:00', '2026-07-24 07:10:00', '2026-07-24 07:10:00', 'Livrat', 1, 105.00, 309.05),
(65, 'AWB20260723000065', 10, 5, 22, 70, '2026-07-23 17:18:00', '2026-07-24 07:10:00', '2026-07-24 07:10:00', 'Livrat', 1, 1050.00, 309.05),
(66, 'AWB20260723000066', 10, 2, 22, 71, '2026-07-23 17:18:00', '2026-07-24 07:10:00', '2026-07-24 07:10:00', 'Livrat', 1, 168.00, 309.05),
(67, 'AWB20260716000067', 10, 6, 22, 72, '2026-07-16 15:50:00', '2026-07-16 20:42:00', '2026-07-16 20:42:00', 'Livrat', 1, 63.00, 309.05),
(68, 'AWB20260716000068', 10, 4, 28, 73, '2026-07-16 15:50:00', '2026-07-17 09:26:00', '2026-07-17 09:26:00', 'Livrat', 1, 2520.00, 546.97),
(69, 'AWB20260716000069', 10, 5, 22, 74, '2026-07-16 15:50:00', '2026-07-16 20:42:00', '2026-07-16 20:42:00', 'Livrat', 1, 33.60, 309.05),
(70, 'AWB20260716000070', 10, 4, 28, 75, '2026-07-16 15:50:00', '2026-07-17 09:26:00', '2026-07-17 09:26:00', 'Livrat', 1, 5040.00, 546.97),
(71, 'AWB20260727000071', 1, 1, 25, 76, '2026-07-27 18:00:00', '2026-07-27 19:54:00', '2026-07-27 19:54:00', 'Livrat', 1, 315.00, 139.81),
(72, 'AWB20260727000072', 1, 1, 25, 77, '2026-07-27 18:00:00', '2026-07-27 19:54:00', '2026-07-27 19:54:00', 'Livrat', 1, 5040.00, 139.81),
(73, 'AWB20260727000073', 1, 6, 25, 78, '2026-07-27 18:00:00', '2026-07-27 19:54:00', '2026-07-27 19:54:00', 'Livrat', 1, 504.00, 139.81),
(74, 'AWB20260721000074', 5, 1, 14, 79, '2026-07-21 18:57:00', '2026-07-22 08:55:00', '2026-07-22 08:55:00', 'Livrat', 1, 2520.00, 328.68),
(75, 'AWB20260721000075', 5, 1, 26, 80, '2026-07-21 18:57:00', '2026-07-22 09:23:00', '2026-07-22 09:23:00', 'Livrat', 1, 105.00, 399.81),
(76, 'AWB20260721000076', 5, 5, 14, 81, '2026-07-21 18:57:00', '2026-07-22 08:55:00', '2026-07-22 08:55:00', 'Livrat', 1, 504.00, 328.68),
(77, 'AWB20260721000077', 5, 2, 20, 82, '2026-07-21 18:57:00', '2026-07-22 13:40:00', '2026-07-22 13:40:00', 'Livrat', 1, 168.00, 696.60),
(78, 'AWB20260729000078', 4, 6, 17, 83, '2026-07-29 12:03:00', '2026-07-29 13:25:00', '2026-07-29 13:25:00', 'Livrat', 1, 2520.00, 63.77),
(79, 'AWB20260729000079', 4, 2, 29, 84, '2026-07-29 12:03:00', '2026-07-29 20:25:00', NULL, 'In tranzit', 0, 21.00, 572.73),
(80, 'AWB20260716000080', 2, 6, 26, 85, '2026-07-16 16:29:00', '2026-07-16 21:55:00', '2026-07-16 21:55:00', 'Livrat', 1, 210.00, 399.81),
(81, 'AWB20260716000081', 2, 6, 26, 86, '2026-07-16 16:29:00', '2026-07-16 21:55:00', '2026-07-16 21:55:00', 'Livrat', 1, 84.00, 399.81),
(82, 'AWB20260716000082', 2, 5, 14, 87, '2026-07-16 16:29:00', '2026-07-16 21:27:00', '2026-07-16 21:27:00', 'Livrat', 1, 2520.00, 328.68),
(83, 'AWB20260723000083', 6, 3, 22, 88, '2026-07-23 20:19:00', '2026-07-24 10:11:00', '2026-07-24 10:11:00', 'Livrat', 1, 63.00, 309.05),
(84, 'AWB20260723000084', 6, 1, 28, 89, '2026-07-23 20:19:00', '2026-07-24 13:55:00', '2026-07-24 13:55:00', 'Livrat', 1, 315.00, 546.97),
(85, 'AWB20260723000085', 6, 2, 28, 90, '2026-07-23 20:19:00', '2026-07-24 13:55:00', '2026-07-24 13:55:00', 'Livrat', 1, 2520.00, 546.97),
(86, 'AWB20260723000086', 6, 6, 22, 91, '2026-07-23 20:19:00', '2026-07-24 10:11:00', '2026-07-24 10:11:00', 'Livrat', 1, 134.40, 309.05),
(87, 'AWB20260727000087', 7, 6, 27, 92, '2026-07-27 19:55:00', '2026-07-28 11:55:00', '2026-07-28 11:55:00', 'Livrat', 1, 210.00, 466.03),
(88, 'AWB20260727000088', 7, 3, 21, 93, '2026-07-27 19:55:00', '2026-07-28 08:30:00', '2026-07-28 08:30:00', 'Livrat', 1, 1008.00, 251.41),
(89, 'AWB20260727000089', 7, 6, 27, 94, '2026-07-27 19:55:00', '2026-07-28 11:55:00', '2026-07-28 11:55:00', 'Livrat', 1, 2520.00, 466.03),
(90, 'AWB20260724000090', 10, 3, 22, 95, '2026-07-24 10:50:00', '2026-07-24 15:42:00', '2026-07-24 15:42:00', 'Livrat', 1, 1008.00, 309.05),
(91, 'AWB20260724000091', 10, 3, 22, 96, '2026-07-24 10:50:00', '2026-07-24 15:42:00', '2026-07-24 15:42:00', 'Livrat', 1, 134.40, 309.05),
(92, 'AWB20260724000092', 10, 1, 28, 97, '2026-07-24 10:50:00', '2026-07-24 19:26:00', '2026-07-24 19:26:00', 'Livrat', 1, 105.00, 546.97),
(93, 'AWB20260724000093', 10, 4, 22, 98, '2026-07-24 10:50:00', '2026-07-24 15:42:00', '2026-07-24 15:42:00', 'Livrat', 1, 42.00, 309.05),
(94, 'AWB20260720000094', 17, 2, 28, 99, '2026-07-20 21:11:00', '2026-07-21 14:47:00', '2026-07-21 14:47:00', 'Livrat', 1, 2520.00, 546.97),
(95, 'AWB20260720000095', 17, 3, 22, 100, '2026-07-20 21:11:00', '2026-07-21 11:03:00', '2026-07-21 11:03:00', 'Livrat', 1, 42.00, 309.05),
(96, 'AWB20260720000096', 17, 6, 22, 101, '2026-07-20 21:11:00', '2026-07-21 11:03:00', '2026-07-21 11:03:00', 'Livrat', 1, 168.00, 309.05),
(97, 'AWB20260720000097', 17, 1, 22, 102, '2026-07-20 21:11:00', '2026-07-21 11:03:00', '2026-07-21 11:03:00', 'Livrat', 1, 1008.00, 309.05),
(98, 'AWB20260811000098', 7, 2, 21, 109, '2026-08-11 16:25:43', '2026-08-11 20:00:43', NULL, 'In tranzit', 0, 126.00, 259.28);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `inventory`
--

CREATE TABLE `inventory` (
  `InventoryID` int(11) NOT NULL,
  `Product_ID` varchar(10) NOT NULL,
  `Stock_Level` int(11) DEFAULT NULL,
  `Reorder_Point` int(11) DEFAULT NULL,
  `Monthly_Sales` int(11) DEFAULT NULL,
  `Cost_Unitar` decimal(10,2) DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `depozit` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Eliminarea datelor din tabel `inventory`
--

INSERT INTO `inventory` (`InventoryID`, `Product_ID`, `Stock_Level`, `Reorder_Point`, `Monthly_Sales`, `Cost_Unitar`, `Date`, `depozit`) VALUES
(1, 'PRD-0001', 23112, 20, 15, 294.54, '2024-01-31', 1),
(2, 'PRD-0001', 15386, 20, 16, 317.63, '2024-02-28', 1),
(3, 'PRD-0001', 37595, 20, 14, 243.74, '2024-03-31', 3),
(4, 'PRD-0002', 11820, 50, 45, 2.78, '2024-01-31', 2),
(5, 'PRD-0002', 16310, 50, 40, 3.37, '2024-02-28', 2),
(6, 'PRD-0002', 36092, 50, 35, 4.14, '2024-03-31', 2),
(7, 'PRD-0003', 41531, 15, 8, 39.86, '2024-01-31', 1),
(8, 'PRD-0003', 49380, 15, 7, 55.34, '2024-02-28', 2),
(9, 'PRD-0003', 32307, 15, 6, 64.99, '2024-03-31', 3),
(10, 'PRD-0004', 43395, 30, 25, 8.31, '2024-01-31', 3),
(11, 'PRD-0004', 30055, 30, 22, 9.75, '2024-02-28', 3),
(12, 'PRD-0004', 10088, 30, 20, 10.22, '2024-03-31', 3),
(13, 'PRD-0005', 30275, 100, 80, 1.65, '2024-01-31', 3),
(14, 'PRD-0005', 31110, 100, 70, 1.63, '2024-02-28', 3),
(15, 'PRD-0005', 14727, 100, 60, 1.62, '2024-03-31', 2),
(16, 'PRD-0006', 10303, 10, 5, 80.11, '2024-01-31', 2),
(17, 'PRD-0006', 37332, 10, 4, 133.47, '2024-02-28', 2),
(18, 'PRD-0006', 25753, 10, 3, 123.02, '2024-03-31', 2),
(21, 'PRD-0007', 46768, 30, 5, 646.64, '2026-07-22', 3),
(22, 'PRD-0008', 18420, 40, 32, 46.20, '2024-01-31', 1),
(23, 'PRD-0008', 24310, 40, 28, 43.68, '2024-02-28', 2),
(24, 'PRD-0008', 31775, 40, 25, 48.72, '2024-03-31', 3),
(25, 'PRD-0009', 15630, 35, 27, 69.30, '2024-01-31', 1),
(26, 'PRD-0009', 22940, 35, 24, 75.60, '2024-02-28', 2),
(27, 'PRD-0009', 34180, 35, 21, 66.15, '2024-03-31', 3),
(28, 'PRD-0010', 27310, 45, 38, 33.60, '2024-01-31', 1),
(29, 'PRD-0010', 19875, 45, 35, 36.96, '2024-02-28', 2),
(30, 'PRD-0010', 41260, 45, 30, 30.24, '2024-03-31', 3),
(31, 'PRD-0011', 13540, 25, 18, 92.40, '2024-01-31', 1),
(32, 'PRD-0011', 28110, 25, 16, 84.00, '2024-02-28', 2),
(33, 'PRD-0011', 36490, 25, 14, 96.60, '2024-03-31', 3),
(34, 'PRD-0012', 21870, 30, 22, 78.54, '2024-01-31', 1),
(35, 'PRD-0012', 16450, 30, 20, 71.40, '2024-02-28', 2),
(36, 'PRD-0012', 39025, 30, 17, 85.68, '2024-03-31', 3),
(37, 'PRD-0013', 24680, 30, 26, 64.68, '2024-01-31', 1),
(38, 'PRD-0013', 33120, 30, 23, 58.80, '2024-02-28', 2),
(39, 'PRD-0013', 17940, 30, 19, 70.56, '2024-03-31', 3),
(40, 'PRD-0014', 11230, 12, 9, 231.00, '2024-01-31', 1),
(41, 'PRD-0014', 26480, 12, 8, 210.00, '2024-02-28', 2),
(42, 'PRD-0014', 19760, 12, 7, 252.00, '2024-03-31', 3),
(43, 'PRD-0015', 14890, 18, 12, 106.26, '2024-01-31', 1),
(44, 'PRD-0015', 30540, 18, 11, 96.60, '2024-02-28', 2),
(45, 'PRD-0015', 22375, 18, 9, 115.92, '2024-03-31', 3),
(46, 'PRD-0016', 35720, 40, 34, 25.20, '2024-01-31', 1),
(47, 'PRD-0016', 28460, 40, 31, 27.72, '2024-02-28', 2),
(48, 'PRD-0016', 16130, 40, 28, 22.68, '2024-03-31', 3),
(49, 'PRD-0017', 44210, 90, 85, 5.67, '2024-01-31', 1),
(50, 'PRD-0017', 38970, 90, 78, 6.30, '2024-02-28', 2),
(51, 'PRD-0017', 29840, 90, 72, 5.04, '2024-03-31', 3),
(52, 'PRD-0018', 23150, 50, 44, 29.40, '2024-01-31', 1),
(53, 'PRD-0018', 31690, 50, 40, 32.34, '2024-02-28', 2),
(54, 'PRD-0018', 18720, 50, 36, 26.46, '2024-03-31', 3),
(55, 'PRD-0019', 40580, 80, 68, 12.60, '2024-01-31', 1),
(56, 'PRD-0019', 26310, 80, 62, 11.34, '2024-02-28', 2),
(57, 'PRD-0019', 34870, 80, 57, 13.86, '2024-03-31', 3),
(69, 'PRD-0020', 5000, 0, 0, NULL, '2026-08-11', 1);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `produse`
--

CREATE TABLE `produse` (
  `ProdusID` int(11) NOT NULL,
  `Product_ID` varchar(10) NOT NULL,
  `Product_Name` varchar(100) NOT NULL,
  `Category` varchar(50) NOT NULL,
  `Unit_Cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `poze` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Eliminarea datelor din tabel `produse`
--

INSERT INTO `produse` (`ProdusID`, `Product_ID`, `Product_Name`, `Category`, `Unit_Cost`, `poze`) VALUES
(1, 'PRD-0001', 'Laptop Pro', 'Electronics', 2520.00, NULL),
(2, 'PRD-0002', 'Mouse Wireless', 'Electronics', 33.60, '20260807_112524_34f647bf.webp'),
(3, 'PRD-0003', 'Office Chair', 'Furniture', 504.00, '20260807_112446_960790df.jpg'),
(4, 'PRD-0004', 'Desk Lamp', 'Furniture', 105.00, '20260807_112413_467bea27.png'),
(5, 'PRD-0005', 'USB-C Cable', 'Accessories', 21.00, '20260807_112334_77b121ca.jpg'),
(6, 'PRD-0006', 'Monitor 27', 'Electronics', 1050.00, '20260807_112306_ea41b700.webp'),
(7, 'PRD-0007', 'pc ioana', 'Electronics', 5040.00, '20260722_154728_0290ec43.webp'),
(8, 'PRD-0008', 'Mechanical Keyboard', 'Electronics', 420.00, '20260807_112234_def2ee50.jpg'),
(9, 'PRD-0009', 'Wireless Headset', 'Electronics', 630.00, '20260807_112200_2dea8a97.webp'),
(10, 'PRD-0010', 'Webcam HD', 'Electronics', 336.00, '20260807_112117_127991b7.jpg'),
(11, 'PRD-0011', 'Docking Station', 'Electronics', 840.00, '20260807_112044_0033e6f9.jpg'),
(12, 'PRD-0012', 'External SSD 1TB', 'Electronics', 714.00, '20260807_110819_d280959f.jpg'),
(13, 'PRD-0013', 'WiFi 6 Router', 'Electronics', 588.00, '20260807_110700_adf85713.jpg'),
(14, 'PRD-0014', 'Standing Desk', 'Furniture', 2100.00, '20260807_110626_d99d69ea.jpg'),
(15, 'PRD-0015', 'Filing Cabinet', 'Furniture', 966.00, '20260807_110543_e9d886bc.jpg'),
(16, 'PRD-0016', 'Monitor Stand', 'Furniture', 252.00, '20260807_110501_3e8ca721.jpg'),
(17, 'PRD-0017', 'Mousepad XL', 'Accessories', 63.00, '20260807_110404_f797135d.jpg'),
(18, 'PRD-0018', 'Laptop Bag', 'Accessories', 294.00, '20260807_110123_e6ee4268.jpg'),
(19, 'PRD-0019', 'USB Hub 4 Port', 'Accessories', 126.00, '20260807_110213_91a4bb62.webp'),
(39, 'PRD-0020', 'laptop multiecran', 'Electronics', 6655.00, '20260811_145205_4de83a02.webp');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `rute`
--

CREATE TABLE `rute` (
  `RutaID` int(11) NOT NULL,
  `Oras_origine` varchar(50) NOT NULL,
  `Oras_destinatie` varchar(50) NOT NULL,
  `Distanta_km` int(11) NOT NULL,
  `Durata_min` int(11) NOT NULL DEFAULT 0,
  `viteza` int(11) NOT NULL DEFAULT 0,
  `tip_strada` enum('autostrada','dn','drum judetean','drum comunal') NOT NULL DEFAULT 'dn'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Eliminarea datelor din tabel `rute`
--

INSERT INTO `rute` (`RutaID`, `Oras_origine`, `Oras_destinatie`, `Distanta_km`, `Durata_min`, `viteza`, `tip_strada`) VALUES
(12, 'Arad', 'Brasov', 418, 463, 95, 'autostrada'),
(13, 'Arad', 'Bucuresti', 547, 592, 60, 'autostrada'),
(14, 'Arad', 'Cluj-Napoca', 268, 268, 80, 'dn'),
(15, 'Arad', 'Constanta', 813, 858, 65, 'drum judetean'),
(16, 'Arad', 'Iasi', 755, 800, 80, 'autostrada'),
(17, 'Arad', 'Timisoara', 52, 52, 60, 'autostrada'),
(18, 'Braila', 'Brasov', 294, 294, 65, 'drum comunal'),
(19, 'Braila', 'Bucuresti', 244, 244, 90, 'dn'),
(20, 'Braila', 'Cluj-Napoca', 568, 613, 100, 'autostrada'),
(21, 'Braila', 'Constanta', 205, 205, 70, 'autostrada'),
(22, 'Braila', 'Iasi', 252, 252, 70, 'dn'),
(23, 'Braila', 'Timisoara', 717, 757, 75, 'drum comunal'),
(24, 'Pitesti', 'Brasov', 135, 135, 70, 'drum comunal'),
(25, 'Pitesti', 'Bucuresti', 114, 114, 80, 'autostrada'),
(26, 'Pitesti', 'Cluj-Napoca', 326, 326, 75, 'autostrada'),
(27, 'Pitesti', 'Constanta', 380, 410, 100, 'drum judetean'),
(28, 'Pitesti', 'Iasi', 446, 476, 65, 'dn'),
(29, 'Pitesti', 'Timisoara', 467, 502, 80, 'autostrada');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `soferi`
--

CREATE TABLE `soferi` (
  `SoferID` int(11) NOT NULL,
  `Nume` varchar(50) NOT NULL,
  `Telefon` varchar(20) NOT NULL,
  `Oras_baza` varchar(50) NOT NULL,
  `Data_angajare` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Eliminarea datelor din tabel `soferi`
--

INSERT INTO `soferi` (`SoferID`, `Nume`, `Telefon`, `Oras_baza`, `Data_angajare`) VALUES
(1, 'Cristian Tudor', '0731111111', 'Bucuresti', '2024-06-01'),
(2, 'Daniel Pop', '0732222222', 'Cluj-Napoca', '2024-07-01'),
(3, 'Florin Neagu', '0733333333', 'Timisoara', '2024-08-01'),
(4, 'George Lazar', '0734444444', 'Iasi', '2024-09-01'),
(5, 'Ioana Marinescu', '0735555555', 'Constanta', '2024-10-01'),
(6, 'Vlad Constantin', '0736666655', 'Brasov', '2024-11-01');

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
(1, 'ioana', '$2y$10$Gbs59droLEB1MJpCPDDAp.RcupmRPS9hMQ10BLVFJqUNDE3QbA1im', 'Ioana', 'admin', '2026-08-04 15:57:30', '2026-08-17 15:38:47');

--
-- Indexuri pentru tabele eliminate
--

--
-- Indexuri pentru tabele `clienti`
--
ALTER TABLE `clienti`
  ADD PRIMARY KEY (`ClientID`);

--
-- Indexuri pentru tabele `comenzi`
--
ALTER TABLE `comenzi`
  ADD PRIMARY KEY (`ComandaID`),
  ADD KEY `fk_comenzi_client` (`ClientID`);

--
-- Indexuri pentru tabele `comenzi_financiar`
--
ALTER TABLE `comenzi_financiar`
  ADD PRIMARY KEY (`FinanciarID`),
  ADD UNIQUE KEY `uq_financiar_comanda` (`ComandaID`);

--
-- Indexuri pentru tabele `comenzi_produse`
--
ALTER TABLE `comenzi_produse`
  ADD PRIMARY KEY (`LinieID`),
  ADD KEY `fk_linii_comanda` (`ComandaID`);

--
-- Indexuri pentru tabele `expedieri`
--
ALTER TABLE `expedieri`
  ADD PRIMARY KEY (`ExpediereID`),
  ADD UNIQUE KEY `awb` (`awb`),
  ADD KEY `ClientID` (`ClientID`),
  ADD KEY `SoferID` (`SoferID`),
  ADD KEY `RutaID` (`RutaID`),
  ADD KEY `fk_expedieri_linie` (`LinieID`);

--
-- Indexuri pentru tabele `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`InventoryID`),
  ADD KEY `fk_inventory_produs` (`Product_ID`);

--
-- Indexuri pentru tabele `produse`
--
ALTER TABLE `produse`
  ADD PRIMARY KEY (`ProdusID`),
  ADD UNIQUE KEY `uq_produse_cod` (`Product_ID`);

--
-- Indexuri pentru tabele `rute`
--
ALTER TABLE `rute`
  ADD PRIMARY KEY (`RutaID`);

--
-- Indexuri pentru tabele `soferi`
--
ALTER TABLE `soferi`
  ADD PRIMARY KEY (`SoferID`);

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
-- AUTO_INCREMENT pentru tabele `clienti`
--
ALTER TABLE `clienti`
  MODIFY `ClientID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT pentru tabele `comenzi`
--
ALTER TABLE `comenzi`
  MODIFY `ComandaID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT pentru tabele `comenzi_financiar`
--
ALTER TABLE `comenzi_financiar`
  MODIFY `FinanciarID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pentru tabele `comenzi_produse`
--
ALTER TABLE `comenzi_produse`
  MODIFY `LinieID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT pentru tabele `expedieri`
--
ALTER TABLE `expedieri`
  MODIFY `ExpediereID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT pentru tabele `inventory`
--
ALTER TABLE `inventory`
  MODIFY `InventoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT pentru tabele `produse`
--
ALTER TABLE `produse`
  MODIFY `ProdusID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT pentru tabele `rute`
--
ALTER TABLE `rute`
  MODIFY `RutaID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT pentru tabele `soferi`
--
ALTER TABLE `soferi`
  MODIFY `SoferID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pentru tabele `users`
--
ALTER TABLE `users`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constrângeri pentru tabele eliminate
--

--
-- Constrângeri pentru tabele `comenzi`
--
ALTER TABLE `comenzi`
  ADD CONSTRAINT `fk_comenzi_client` FOREIGN KEY (`ClientID`) REFERENCES `clienti` (`ClientID`) ON UPDATE CASCADE;

--
-- Constrângeri pentru tabele `comenzi_financiar`
--
ALTER TABLE `comenzi_financiar`
  ADD CONSTRAINT `fk_financiar_comanda` FOREIGN KEY (`ComandaID`) REFERENCES `comenzi` (`ComandaID`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `comenzi_produse`
--
ALTER TABLE `comenzi_produse`
  ADD CONSTRAINT `fk_linii_comanda` FOREIGN KEY (`ComandaID`) REFERENCES `comenzi` (`ComandaID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constrângeri pentru tabele `expedieri`
--
ALTER TABLE `expedieri`
  ADD CONSTRAINT `expedieri_ibfk_1` FOREIGN KEY (`ClientID`) REFERENCES `clienti` (`ClientID`),
  ADD CONSTRAINT `expedieri_ibfk_2` FOREIGN KEY (`SoferID`) REFERENCES `soferi` (`SoferID`),
  ADD CONSTRAINT `expedieri_ibfk_3` FOREIGN KEY (`RutaID`) REFERENCES `rute` (`RutaID`),
  ADD CONSTRAINT `fk_expedieri_linie` FOREIGN KEY (`LinieID`) REFERENCES `comenzi_produse` (`LinieID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constrângeri pentru tabele `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `fk_inventory_produs` FOREIGN KEY (`Product_ID`) REFERENCES `produse` (`Product_ID`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
