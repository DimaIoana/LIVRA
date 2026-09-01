-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: sameday_company
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `clienti`
--

DROP TABLE IF EXISTS `clienti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clienti` (
  `ClientID` int(11) NOT NULL AUTO_INCREMENT,
  `Nume` varchar(50) NOT NULL,
  `Tip` enum('Persoana fizica','Persoana juridica') NOT NULL,
  `Email` varchar(150) NOT NULL,
  `Telefon` varchar(20) NOT NULL,
  `Oras` varchar(30) NOT NULL,
  `Data_inregistrare` date NOT NULL,
  PRIMARY KEY (`ClientID`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clienti`
--

LOCK TABLES `clienti` WRITE;
/*!40000 ALTER TABLE `clienti` DISABLE KEYS */;
INSERT INTO `clienti` VALUES (1,'Ana Popescu','Persoana fizica','ana.popescu@email.co','0721111111','Bucuresti','2025-01-10'),(2,'Mihai Ionescu','Persoana fizica','mihai.ionescu@email.','0722222222','Cluj-Napoca','2025-01-15'),(3,'TechSol SRL','Persoana juridica','contact@techsol.ro','0711111111','Bucuresti','2025-01-20'),(4,'Elena Dumitru','Persoana fizica','elena.dumitru@email.','0724444444','Timisoara','2025-02-01'),(5,'Globex Trading SRL','Persoana juridica','office@globex.ro','0264555555','Cluj-Napoca','2025-02-05'),(6,'Andrei Stan','Persoana fizica','andrei.stan@email.co','0726666666','Iasi','2025-02-10'),(7,'Carmen Vasilescu','Persoana fizica','carmen.v@email.com','0727777777','Constanta','2025-02-15'),(8,'MediPlus SRL','Persoana juridica','office@mediplus.ro','0256888888','Timisoara','2025-02-20'),(9,'Radu Marin','Persoana fizica','radu.marin@email.com','0728999999','Brasov','2025-03-01'),(10,'FashionHub SRL','Persoana juridica','contact@fashionhub.r','0232101010','Iasi','2025-03-05'),(17,'marcel','Persoana fizica','marcel@mail.com','1111111122','Iasi','2026-07-17');
/*!40000 ALTER TABLE `clienti` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comenzi`
--

DROP TABLE IF EXISTS `comenzi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comenzi` (
  `ComandaID` int(11) NOT NULL AUTO_INCREMENT,
  `ClientID` int(11) NOT NULL,
  `Data_comanda` datetime NOT NULL,
  `Status` enum('Noua','In procesare','Trimisa','Anulata') NOT NULL DEFAULT 'Noua',
  `Total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Observatii` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`ComandaID`),
  KEY `fk_comenzi_client` (`ClientID`),
  CONSTRAINT `fk_comenzi_client` FOREIGN KEY (`ClientID`) REFERENCES `clienti` (`ClientID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comenzi`
--

LOCK TABLES `comenzi` WRITE;
/*!40000 ALTER TABLE `comenzi` DISABLE KEYS */;
INSERT INTO `comenzi` VALUES (1,1,'2026-07-19 09:20:00','Trimisa',2562.00,'Livrare la birou, dupa ora 10.'),(2,2,'2026-07-20 14:05:00','Trimisa',100.80,'Sunati inainte de livrare.'),(3,3,'2026-07-21 11:40:00','Trimisa',2184.00,'Factura pe firma, cod fiscal in contract.'),(4,4,'2026-07-22 16:15:00','Trimisa',714.00,NULL),(5,5,'2026-07-23 08:50:00','Trimisa',6258.00,'Comanda mare, atentie la ambalare.'),(6,6,'2026-07-24 10:30:00','Trimisa',105.00,'Cadou, fara factura in colet.'),(7,7,'2026-07-25 13:00:00','Trimisa',5040.00,'Produs fragil, marcati coletul.'),(8,8,'2026-07-26 09:05:00','Trimisa',277.20,'Livrare in intervalul 09-12.'),(9,9,'2026-07-26 17:45:00','Anulata',1008.00,'Anulata de client, s-a razgandit.'),(10,10,'2026-07-27 12:25:00','Trimisa',3339.00,'Se plateste ramburs la livrare.'),(11,7,'2026-07-28 16:45:23','Trimisa',6090.00,NULL),(12,2,'2026-07-28 15:57:00','Trimisa',7098.00,'Lasati coletul la receptie.'),(13,10,'2026-07-18 10:12:00','Trimisa',1398.60,'Ambalare pentru produse fragile.'),(14,7,'2026-07-25 12:56:00','Trimisa',1709.40,'Livrare dupa ora 16:00.'),(15,4,'2026-07-27 15:28:00','Trimisa',7942.20,NULL),(16,10,'2026-07-24 15:02:00','Trimisa',9702.00,NULL),(17,2,'2026-07-25 19:22:00','Trimisa',9660.00,NULL),(18,8,'2026-07-21 09:28:00','Trimisa',163.80,NULL),(19,6,'2026-07-17 09:09:00','Trimisa',5107.20,NULL),(20,7,'2026-07-22 19:28:00','Trimisa',8148.00,NULL),(21,5,'2026-07-22 16:32:00','Trimisa',6090.00,'Ambalare pentru produse fragile.'),(22,2,'2026-07-16 16:52:00','Trimisa',6392.40,'Sunati inainte de livrare.'),(23,4,'2026-07-25 19:57:00','Trimisa',709.80,NULL),(24,9,'2026-07-19 11:04:00','Trimisa',1218.00,'Am nevoie de factura pe firma.'),(25,3,'2026-07-24 10:18:00','Trimisa',5082.00,'Lasati coletul la receptie.'),(26,4,'2026-07-25 09:56:00','Trimisa',2184.00,NULL),(27,10,'2026-07-23 15:18:00','Trimisa',2331.00,'Lasati coletul la receptie.'),(28,10,'2026-07-16 13:50:00','Trimisa',7656.60,NULL),(29,1,'2026-07-27 16:00:00','Trimisa',5859.00,'Sunati inainte de livrare.'),(30,5,'2026-07-21 16:57:00','Trimisa',3297.00,NULL),(31,4,'2026-07-29 10:03:00','Trimisa',2541.00,NULL),(32,2,'2026-07-16 14:29:00','Trimisa',2814.00,NULL),(33,6,'2026-07-23 18:19:00','Trimisa',3032.40,NULL),(34,7,'2026-07-27 17:55:00','Trimisa',3738.00,NULL),(35,10,'2026-07-24 08:50:00','Trimisa',1289.40,'Am nevoie de factura pe firma.'),(36,17,'2026-07-20 19:11:00','Trimisa',3738.00,NULL),(37,1,'2026-08-01 00:00:00','Anulata',42.00,'comanda de test decizie'),(39,7,'2026-08-03 14:14:03','In procesare',126.00,NULL);
/*!40000 ALTER TABLE `comenzi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comenzi_financiar`
--

DROP TABLE IF EXISTS `comenzi_financiar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comenzi_financiar` (
  `FinanciarID` int(11) NOT NULL AUTO_INCREMENT,
  `ComandaID` int(11) NOT NULL,
  `incasare` decimal(10,2) NOT NULL,
  `cost_marfa` decimal(10,2) NOT NULL,
  `cost_carburant` decimal(10,2) NOT NULL,
  `profit` decimal(10,2) NOT NULL,
  `Data_inregistrare` datetime NOT NULL,
  PRIMARY KEY (`FinanciarID`),
  UNIQUE KEY `uq_financiar_comanda` (`ComandaID`),
  CONSTRAINT `fk_financiar_comanda` FOREIGN KEY (`ComandaID`) REFERENCES `comenzi` (`ComandaID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comenzi_financiar`
--

LOCK TABLES `comenzi_financiar` WRITE;
/*!40000 ALTER TABLE `comenzi_financiar` DISABLE KEYS */;
INSERT INTO `comenzi_financiar` VALUES (2,39,126.00,11.34,261.99,-147.33,'2026-08-03 14:15:13');
/*!40000 ALTER TABLE `comenzi_financiar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comenzi_produse`
--

DROP TABLE IF EXISTS `comenzi_produse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comenzi_produse` (
  `LinieID` int(11) NOT NULL AUTO_INCREMENT,
  `ComandaID` int(11) NOT NULL,
  `Product_ID` varchar(10) NOT NULL,
  `Product_Name` varchar(100) NOT NULL,
  `Pret_unitar` decimal(10,2) NOT NULL,
  `Cantitate` int(11) NOT NULL,
  `Subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`LinieID`),
  KEY `fk_linii_comanda` (`ComandaID`),
  CONSTRAINT `fk_linii_comanda` FOREIGN KEY (`ComandaID`) REFERENCES `comenzi` (`ComandaID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comenzi_produse`
--

LOCK TABLES `comenzi_produse` WRITE;
/*!40000 ALTER TABLE `comenzi_produse` DISABLE KEYS */;
INSERT INTO `comenzi_produse` VALUES (1,1,'PRD-0001','Laptop Pro',2520.00,1,2520.00),(2,1,'PRD-0005','USB-C Cable',21.00,2,42.00),(4,2,'PRD-0002','Mouse Wireless',33.60,3,100.80),(5,3,'PRD-0005','USB-C Cable',21.00,4,84.00),(6,3,'PRD-0006','Monitor 27',1050.00,2,2100.00),(8,4,'PRD-0003','Office Chair',504.00,1,504.00),(9,4,'PRD-0004','Desk Lamp',105.00,2,210.00),(11,5,'PRD-0001','Laptop Pro',2520.00,2,5040.00),(12,5,'PRD-0002','Mouse Wireless',33.60,5,168.00),(13,5,'PRD-0006','Monitor 27',1050.00,1,1050.00),(14,6,'PRD-0004','Desk Lamp',105.00,1,105.00),(15,7,'PRD-0007','pc ioana',5040.00,1,5040.00),(16,8,'PRD-0002','Mouse Wireless',33.60,2,67.20),(17,8,'PRD-0005','USB-C Cable',21.00,10,210.00),(19,9,'PRD-0003','Office Chair',504.00,2,1008.00),(20,10,'PRD-0001','Laptop Pro',2520.00,1,2520.00),(21,10,'PRD-0003','Office Chair',504.00,1,504.00),(22,10,'PRD-0004','Desk Lamp',105.00,3,315.00),(23,11,'PRD-0006','Monitor 27',1050.00,1,1050.00),(24,11,'PRD-0007','pc ioana',5040.00,1,5040.00),(25,12,'PRD-0003','Office Chair',504.00,2,1008.00),(26,12,'PRD-0007','pc ioana',5040.00,1,5040.00),(27,12,'PRD-0006','Monitor 27',1050.00,1,1050.00),(28,13,'PRD-0002','Mouse Wireless',33.60,1,33.60),(29,13,'PRD-0006','Monitor 27',1050.00,1,1050.00),(30,13,'PRD-0004','Desk Lamp',105.00,3,315.00),(31,14,'PRD-0002','Mouse Wireless',33.60,4,134.40),(32,14,'PRD-0005','USB-C Cable',21.00,1,21.00),(33,14,'PRD-0003','Office Chair',504.00,1,504.00),(34,14,'PRD-0006','Monitor 27',1050.00,1,1050.00),(35,15,'PRD-0007','pc ioana',5040.00,1,5040.00),(36,15,'PRD-0002','Mouse Wireless',33.60,2,67.20),(37,15,'PRD-0004','Desk Lamp',105.00,3,315.00),(38,15,'PRD-0001','Laptop Pro',2520.00,1,2520.00),(39,16,'PRD-0007','pc ioana',5040.00,1,5040.00),(40,16,'PRD-0001','Laptop Pro',2520.00,1,2520.00),(41,16,'PRD-0005','USB-C Cable',21.00,2,42.00),(42,16,'PRD-0006','Monitor 27',1050.00,2,2100.00),(43,17,'PRD-0007','pc ioana',5040.00,1,5040.00),(44,17,'PRD-0006','Monitor 27',1050.00,2,2100.00),(45,17,'PRD-0001','Laptop Pro',2520.00,1,2520.00),(46,18,'PRD-0002','Mouse Wireless',33.60,3,100.80),(47,18,'PRD-0005','USB-C Cable',21.00,3,63.00),(48,19,'PRD-0002','Mouse Wireless',33.60,2,67.20),(49,19,'PRD-0007','pc ioana',5040.00,1,5040.00),(50,20,'PRD-0006','Monitor 27',1050.00,2,2100.00),(51,20,'PRD-0007','pc ioana',5040.00,1,5040.00),(52,20,'PRD-0003','Office Chair',504.00,2,1008.00),(53,21,'PRD-0006','Monitor 27',1050.00,1,1050.00),(54,21,'PRD-0007','pc ioana',5040.00,1,5040.00),(55,22,'PRD-0003','Office Chair',504.00,2,1008.00),(56,22,'PRD-0004','Desk Lamp',105.00,2,210.00),(57,22,'PRD-0007','pc ioana',5040.00,1,5040.00),(58,22,'PRD-0002','Mouse Wireless',33.60,4,134.40),(59,23,'PRD-0003','Office Chair',504.00,1,504.00),(60,23,'PRD-0002','Mouse Wireless',33.60,3,100.80),(61,23,'PRD-0004','Desk Lamp',105.00,1,105.00),(62,24,'PRD-0003','Office Chair',504.00,2,1008.00),(63,24,'PRD-0004','Desk Lamp',105.00,2,210.00),(64,25,'PRD-0007','pc ioana',5040.00,1,5040.00),(65,25,'PRD-0005','USB-C Cable',21.00,2,42.00),(66,26,'PRD-0006','Monitor 27',1050.00,2,2100.00),(67,26,'PRD-0005','USB-C Cable',21.00,4,84.00),(68,27,'PRD-0003','Office Chair',504.00,2,1008.00),(69,27,'PRD-0005','USB-C Cable',21.00,5,105.00),(70,27,'PRD-0006','Monitor 27',1050.00,1,1050.00),(71,27,'PRD-0002','Mouse Wireless',33.60,5,168.00),(72,28,'PRD-0005','USB-C Cable',21.00,3,63.00),(73,28,'PRD-0001','Laptop Pro',2520.00,1,2520.00),(74,28,'PRD-0002','Mouse Wireless',33.60,1,33.60),(75,28,'PRD-0007','pc ioana',5040.00,1,5040.00),(76,29,'PRD-0004','Desk Lamp',105.00,3,315.00),(77,29,'PRD-0007','pc ioana',5040.00,1,5040.00),(78,29,'PRD-0003','Office Chair',504.00,1,504.00),(79,30,'PRD-0001','Laptop Pro',2520.00,1,2520.00),(80,30,'PRD-0005','USB-C Cable',21.00,5,105.00),(81,30,'PRD-0003','Office Chair',504.00,1,504.00),(82,30,'PRD-0002','Mouse Wireless',33.60,5,168.00),(83,31,'PRD-0001','Laptop Pro',2520.00,1,2520.00),(84,31,'PRD-0005','USB-C Cable',21.00,1,21.00),(85,32,'PRD-0004','Desk Lamp',105.00,2,210.00),(86,32,'PRD-0005','USB-C Cable',21.00,4,84.00),(87,32,'PRD-0001','Laptop Pro',2520.00,1,2520.00),(88,33,'PRD-0005','USB-C Cable',21.00,3,63.00),(89,33,'PRD-0004','Desk Lamp',105.00,3,315.00),(90,33,'PRD-0001','Laptop Pro',2520.00,1,2520.00),(91,33,'PRD-0002','Mouse Wireless',33.60,4,134.40),(92,34,'PRD-0004','Desk Lamp',105.00,2,210.00),(93,34,'PRD-0003','Office Chair',504.00,2,1008.00),(94,34,'PRD-0001','Laptop Pro',2520.00,1,2520.00),(95,35,'PRD-0003','Office Chair',504.00,2,1008.00),(96,35,'PRD-0002','Mouse Wireless',33.60,4,134.40),(97,35,'PRD-0004','Desk Lamp',105.00,1,105.00),(98,35,'PRD-0005','USB-C Cable',21.00,2,42.00),(99,36,'PRD-0001','Laptop Pro',2520.00,1,2520.00),(100,36,'PRD-0005','USB-C Cable',21.00,2,42.00),(101,36,'PRD-0002','Mouse Wireless',33.60,5,168.00),(102,36,'PRD-0003','Office Chair',504.00,2,1008.00),(103,37,'PRD-0005','USB-C Cable',21.00,2,42.00),(105,39,'PRD-0019','USB Hub 4 Port',126.00,1,126.00);
/*!40000 ALTER TABLE `comenzi_produse` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expedieri`
--

DROP TABLE IF EXISTS `expedieri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expedieri` (
  `ExpediereID` int(11) NOT NULL AUTO_INCREMENT,
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
  `cost_carburant` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`ExpediereID`),
  UNIQUE KEY `awb` (`awb`),
  KEY `ClientID` (`ClientID`),
  KEY `SoferID` (`SoferID`),
  KEY `RutaID` (`RutaID`),
  KEY `fk_expedieri_linie` (`LinieID`),
  CONSTRAINT `expedieri_ibfk_1` FOREIGN KEY (`ClientID`) REFERENCES `clienti` (`ClientID`),
  CONSTRAINT `expedieri_ibfk_2` FOREIGN KEY (`SoferID`) REFERENCES `soferi` (`SoferID`),
  CONSTRAINT `expedieri_ibfk_3` FOREIGN KEY (`RutaID`) REFERENCES `rute` (`RutaID`),
  CONSTRAINT `fk_expedieri_linie` FOREIGN KEY (`LinieID`) REFERENCES `comenzi_produse` (`LinieID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expedieri`
--

LOCK TABLES `expedieri` WRITE;
/*!40000 ALTER TABLE `expedieri` DISABLE KEYS */;
INSERT INTO `expedieri` VALUES (1,'AWB20260719000001',1,5,25,1,'2026-07-19 11:20:00','2026-07-19 13:14:00','2026-07-19 13:14:00','Livrat',1,2520.00,136.94),(2,'AWB20260719000002',1,1,25,2,'2026-07-19 11:20:00','2026-07-19 13:14:00','2026-07-19 13:14:00','Livrat',1,42.00,136.94),(3,'AWB20260720000003',2,1,20,4,'2026-07-20 16:05:00','2026-07-21 10:48:00','2026-07-21 10:48:00','Livrat',1,100.80,682.28),(4,'AWB20260721000004',3,2,25,5,'2026-07-21 13:40:00','2026-07-21 15:34:00','2026-07-21 15:34:00','Livrat',1,84.00,136.94),(5,'AWB20260721000005',3,4,19,6,'2026-07-21 13:40:00','2026-07-21 17:44:00','2026-07-21 17:44:00','Livrat',1,2100.00,293.09),(6,'AWB20260722000006',4,2,17,8,'2026-07-22 18:15:00','2026-07-22 19:37:00','2026-07-22 19:37:00','Livrat',1,504.00,62.46),(7,'AWB20260722000007',4,3,29,9,'2026-07-22 18:15:00','2026-07-23 11:37:00','2026-07-23 11:37:00','Livrat',1,210.00,560.96),(8,'AWB20260723000008',5,6,14,11,'2026-07-23 10:50:00','2026-07-23 15:48:00','2026-07-23 15:48:00','Livrat',1,5040.00,321.92),(9,'AWB20260723000009',5,2,20,12,'2026-07-23 10:50:00','2026-07-23 20:33:00','2026-07-23 20:33:00','Livrat',1,168.00,682.28),(10,'AWB20260723000010',5,4,20,13,'2026-07-23 10:50:00','2026-07-23 20:33:00','2026-07-23 20:33:00','Livrat',1,1050.00,682.28),(11,'AWB20260724000011',6,2,28,14,'2026-07-24 12:30:00','2026-07-24 21:06:00','2026-07-24 21:06:00','Livrat',1,105.00,535.74),(12,'AWB20260725000012',7,2,27,15,'2026-07-25 15:00:00','2026-07-25 22:00:00','2026-07-25 22:00:00','Livrat',1,5040.00,456.46),(13,'AWB20260726000013',8,4,23,16,'2026-07-26 11:05:00','2026-07-27 09:32:00','2026-07-27 09:32:00','Livrat',1,67.20,861.26),(14,'AWB20260726000014',8,6,29,17,'2026-07-26 11:05:00','2026-07-26 19:27:00','2026-07-26 19:27:00','Livrat',1,210.00,560.96),(15,'AWB20260727000015',10,1,28,20,'2026-07-27 14:25:00','2026-07-28 08:01:00','2026-07-28 08:01:00','Livrat',1,2520.00,535.74),(16,'AWB20260727000016',10,6,22,21,'2026-07-27 14:25:00','2026-07-27 19:17:00','2026-07-27 19:17:00','Livrat',1,504.00,302.70),(17,'AWB20260727000017',10,4,28,22,'2026-07-27 14:25:00','2026-07-28 08:01:00','2026-07-28 08:01:00','Livrat',1,315.00,535.74),(18,'AWB20260728000018',7,3,21,23,'2026-07-28 18:45:23','2026-07-29 07:21:00','2026-07-29 07:21:00','Livrat',1,1050.00,246.25),(19,'AWB20260728000019',7,4,27,24,'2026-07-28 18:45:23','2026-07-29 10:46:00','2026-07-29 10:46:00','Livrat',1,5040.00,456.46),(20,'AWB20260728000020',2,5,14,25,'2026-07-28 17:57:00','2026-07-29 07:55:00','2026-07-29 07:55:00','Livrat',1,1008.00,328.68),(21,'AWB20260728000021',2,4,26,26,'2026-07-28 17:57:00','2026-07-29 08:23:00','2026-07-29 08:23:00','Livrat',1,5040.00,399.81),(22,'AWB20260728000022',2,6,20,27,'2026-07-28 17:57:00','2026-07-29 12:40:00','2026-07-29 12:40:00','Livrat',1,1050.00,696.60),(23,'AWB20260718000023',10,2,22,28,'2026-07-18 12:12:00','2026-07-18 17:04:00','2026-07-18 17:04:00','Livrat',1,33.60,309.05),(24,'AWB20260718000024',10,6,22,29,'2026-07-18 12:12:00','2026-07-18 17:04:00','2026-07-18 17:04:00','Livrat',1,1050.00,309.05),(25,'AWB20260718000025',10,5,28,30,'2026-07-18 12:12:00','2026-07-18 20:48:00','2026-07-18 20:48:00','Livrat',1,315.00,546.97),(26,'AWB20260725000026',7,4,21,31,'2026-07-25 14:56:00','2026-07-25 18:31:00','2026-07-25 18:31:00','Livrat',1,134.40,251.41),(27,'AWB20260725000027',7,2,21,32,'2026-07-25 14:56:00','2026-07-25 18:31:00','2026-07-25 18:31:00','Livrat',1,21.00,251.41),(28,'AWB20260725000028',7,2,21,33,'2026-07-25 14:56:00','2026-07-25 18:31:00','2026-07-25 18:31:00','Livrat',1,504.00,251.41),(29,'AWB20260725000029',7,5,21,34,'2026-07-25 14:56:00','2026-07-25 18:31:00','2026-07-25 18:31:00','Livrat',1,1050.00,251.41),(30,'AWB20260727000030',4,6,29,35,'2026-07-27 17:28:00','2026-07-28 10:50:00','2026-07-28 10:50:00','Livrat',1,5040.00,572.73),(31,'AWB20260727000031',4,6,23,36,'2026-07-27 17:28:00','2026-07-28 15:55:00','2026-07-28 15:55:00','Livrat',1,67.20,879.33),(32,'AWB20260727000032',4,2,29,37,'2026-07-27 17:28:00','2026-07-28 10:50:00','2026-07-28 10:50:00','Livrat',1,315.00,572.73),(33,'AWB20260727000033',4,5,17,38,'2026-07-27 17:28:00','2026-07-27 18:50:00','2026-07-27 18:50:00','Livrat',1,2520.00,63.77),(34,'AWB20260724000034',10,3,28,39,'2026-07-24 17:02:00','2026-07-25 10:38:00','2026-07-25 10:38:00','Livrat',1,5040.00,546.97),(35,'AWB20260724000035',10,5,28,40,'2026-07-24 17:02:00','2026-07-25 10:38:00','2026-07-25 10:38:00','Livrat',1,2520.00,546.97),(36,'AWB20260724000036',10,4,22,41,'2026-07-24 17:02:00','2026-07-24 21:54:00','2026-07-24 21:54:00','Livrat',1,42.00,309.05),(37,'AWB20260724000037',10,6,22,42,'2026-07-24 17:02:00','2026-07-24 21:54:00','2026-07-24 21:54:00','Livrat',1,2100.00,309.05),(38,'AWB20260725000038',2,2,26,43,'2026-07-25 21:22:00','2026-07-26 11:48:00','2026-07-26 11:48:00','Livrat',1,5040.00,399.81),(39,'AWB20260725000039',2,3,20,44,'2026-07-25 21:22:00','2026-07-26 16:05:00','2026-07-26 16:05:00','Livrat',1,2100.00,696.60),(40,'AWB20260725000040',2,2,14,45,'2026-07-25 21:22:00','2026-07-26 11:20:00','2026-07-26 11:20:00','Livrat',1,2520.00,328.68),(41,'AWB20260721000041',8,6,23,46,'2026-07-21 11:28:00','2026-07-22 09:55:00','2026-07-22 09:55:00','Livrat',1,100.80,879.33),(42,'AWB20260721000042',8,2,29,47,'2026-07-21 11:28:00','2026-07-21 19:50:00','2026-07-21 19:50:00','Livrat',1,63.00,572.73),(43,'AWB20260717000043',6,3,22,48,'2026-07-17 11:09:00','2026-07-17 16:01:00','2026-07-17 16:01:00','Livrat',1,67.20,309.05),(44,'AWB20260717000044',6,6,28,49,'2026-07-17 11:09:00','2026-07-17 19:45:00','2026-07-17 19:45:00','Livrat',1,5040.00,546.97),(45,'AWB20260722000045',7,3,21,50,'2026-07-22 21:28:00','2026-07-23 10:03:00','2026-07-23 10:03:00','Livrat',1,2100.00,251.41),(46,'AWB20260722000046',7,1,27,51,'2026-07-22 21:28:00','2026-07-23 13:28:00','2026-07-23 13:28:00','Livrat',1,5040.00,466.03),(47,'AWB20260722000047',7,6,21,52,'2026-07-22 21:28:00','2026-07-23 10:03:00','2026-07-23 10:03:00','Livrat',1,1008.00,251.41),(48,'AWB20260722000048',5,1,20,53,'2026-07-22 18:32:00','2026-07-23 13:15:00','2026-07-23 13:15:00','Livrat',1,1050.00,696.60),(49,'AWB20260722000049',5,1,26,54,'2026-07-22 18:32:00','2026-07-23 08:58:00','2026-07-23 08:58:00','Livrat',1,5040.00,399.81),(50,'AWB20260716000050',2,1,14,55,'2026-07-16 18:52:00','2026-07-17 08:50:00','2026-07-17 08:50:00','Livrat',1,1008.00,328.68),(51,'AWB20260716000051',2,5,26,56,'2026-07-16 18:52:00','2026-07-17 09:18:00','2026-07-17 09:18:00','Livrat',1,210.00,399.81),(52,'AWB20260716000052',2,1,26,57,'2026-07-16 18:52:00','2026-07-17 09:18:00','2026-07-17 09:18:00','Livrat',1,5040.00,399.81),(53,'AWB20260716000053',2,5,20,58,'2026-07-16 18:52:00','2026-07-17 13:35:00','2026-07-17 13:35:00','Livrat',1,134.40,696.60),(54,'AWB20260725000054',4,6,17,59,'2026-07-25 21:57:00','2026-07-26 08:19:00','2026-07-26 08:19:00','Livrat',1,504.00,63.77),(55,'AWB20260725000055',4,2,23,60,'2026-07-25 21:57:00','2026-07-26 20:24:00','2026-07-26 20:24:00','Livrat',1,100.80,879.33),(56,'AWB20260725000056',4,4,29,61,'2026-07-25 21:57:00','2026-07-26 15:19:00','2026-07-26 15:19:00','Livrat',1,105.00,572.73),(57,'AWB20260719000057',9,1,24,62,'2026-07-19 13:04:00','2026-07-19 16:19:00','2026-07-19 16:19:00','Livrat',1,1008.00,165.56),(58,'AWB20260719000058',9,1,24,63,'2026-07-19 13:04:00','2026-07-19 16:19:00','2026-07-19 16:19:00','Livrat',1,210.00,165.56),(59,'AWB20260724000059',3,3,25,64,'2026-07-24 12:18:00','2026-07-24 14:12:00','2026-07-24 14:12:00','Livrat',1,5040.00,139.81),(60,'AWB20260724000060',3,5,25,65,'2026-07-24 12:18:00','2026-07-24 14:12:00','2026-07-24 14:12:00','Livrat',1,42.00,139.81),(61,'AWB20260725000061',4,2,23,66,'2026-07-25 11:56:00','2026-07-26 10:23:00','2026-07-26 10:23:00','Livrat',1,2100.00,879.33),(62,'AWB20260725000062',4,4,29,67,'2026-07-25 11:56:00','2026-07-25 20:18:00','2026-07-25 20:18:00','Livrat',1,84.00,572.73),(63,'AWB20260723000063',10,1,22,68,'2026-07-23 17:18:00','2026-07-24 07:10:00','2026-07-24 07:10:00','Livrat',1,1008.00,309.05),(64,'AWB20260723000064',10,4,22,69,'2026-07-23 17:18:00','2026-07-24 07:10:00','2026-07-24 07:10:00','Livrat',1,105.00,309.05),(65,'AWB20260723000065',10,5,22,70,'2026-07-23 17:18:00','2026-07-24 07:10:00','2026-07-24 07:10:00','Livrat',1,1050.00,309.05),(66,'AWB20260723000066',10,2,22,71,'2026-07-23 17:18:00','2026-07-24 07:10:00','2026-07-24 07:10:00','Livrat',1,168.00,309.05),(67,'AWB20260716000067',10,6,22,72,'2026-07-16 15:50:00','2026-07-16 20:42:00','2026-07-16 20:42:00','Livrat',1,63.00,309.05),(68,'AWB20260716000068',10,4,28,73,'2026-07-16 15:50:00','2026-07-17 09:26:00','2026-07-17 09:26:00','Livrat',1,2520.00,546.97),(69,'AWB20260716000069',10,5,22,74,'2026-07-16 15:50:00','2026-07-16 20:42:00','2026-07-16 20:42:00','Livrat',1,33.60,309.05),(70,'AWB20260716000070',10,4,28,75,'2026-07-16 15:50:00','2026-07-17 09:26:00','2026-07-17 09:26:00','Livrat',1,5040.00,546.97),(71,'AWB20260727000071',1,1,25,76,'2026-07-27 18:00:00','2026-07-27 19:54:00','2026-07-27 19:54:00','Livrat',1,315.00,139.81),(72,'AWB20260727000072',1,1,25,77,'2026-07-27 18:00:00','2026-07-27 19:54:00','2026-07-27 19:54:00','Livrat',1,5040.00,139.81),(73,'AWB20260727000073',1,6,25,78,'2026-07-27 18:00:00','2026-07-27 19:54:00','2026-07-27 19:54:00','Livrat',1,504.00,139.81),(74,'AWB20260721000074',5,1,14,79,'2026-07-21 18:57:00','2026-07-22 08:55:00','2026-07-22 08:55:00','Livrat',1,2520.00,328.68),(75,'AWB20260721000075',5,1,26,80,'2026-07-21 18:57:00','2026-07-22 09:23:00','2026-07-22 09:23:00','Livrat',1,105.00,399.81),(76,'AWB20260721000076',5,5,14,81,'2026-07-21 18:57:00','2026-07-22 08:55:00','2026-07-22 08:55:00','Livrat',1,504.00,328.68),(77,'AWB20260721000077',5,2,20,82,'2026-07-21 18:57:00','2026-07-22 13:40:00','2026-07-22 13:40:00','Livrat',1,168.00,696.60),(78,'AWB20260729000078',4,6,17,83,'2026-07-29 12:03:00','2026-07-29 13:25:00','2026-07-29 13:25:00','Livrat',1,2520.00,63.77),(79,'AWB20260729000079',4,2,29,84,'2026-07-29 12:03:00','2026-07-29 20:25:00',NULL,'In tranzit',0,21.00,572.73),(80,'AWB20260716000080',2,6,26,85,'2026-07-16 16:29:00','2026-07-16 21:55:00','2026-07-16 21:55:00','Livrat',1,210.00,399.81),(81,'AWB20260716000081',2,6,26,86,'2026-07-16 16:29:00','2026-07-16 21:55:00','2026-07-16 21:55:00','Livrat',1,84.00,399.81),(82,'AWB20260716000082',2,5,14,87,'2026-07-16 16:29:00','2026-07-16 21:27:00','2026-07-16 21:27:00','Livrat',1,2520.00,328.68),(83,'AWB20260723000083',6,3,22,88,'2026-07-23 20:19:00','2026-07-24 10:11:00','2026-07-24 10:11:00','Livrat',1,63.00,309.05),(84,'AWB20260723000084',6,1,28,89,'2026-07-23 20:19:00','2026-07-24 13:55:00','2026-07-24 13:55:00','Livrat',1,315.00,546.97),(85,'AWB20260723000085',6,2,28,90,'2026-07-23 20:19:00','2026-07-24 13:55:00','2026-07-24 13:55:00','Livrat',1,2520.00,546.97),(86,'AWB20260723000086',6,6,22,91,'2026-07-23 20:19:00','2026-07-24 10:11:00','2026-07-24 10:11:00','Livrat',1,134.40,309.05),(87,'AWB20260727000087',7,6,27,92,'2026-07-27 19:55:00','2026-07-28 11:55:00','2026-07-28 11:55:00','Livrat',1,210.00,466.03),(88,'AWB20260727000088',7,3,21,93,'2026-07-27 19:55:00','2026-07-28 08:30:00','2026-07-28 08:30:00','Livrat',1,1008.00,251.41),(89,'AWB20260727000089',7,6,27,94,'2026-07-27 19:55:00','2026-07-28 11:55:00','2026-07-28 11:55:00','Livrat',1,2520.00,466.03),(90,'AWB20260724000090',10,3,22,95,'2026-07-24 10:50:00','2026-07-24 15:42:00','2026-07-24 15:42:00','Livrat',1,1008.00,309.05),(91,'AWB20260724000091',10,3,22,96,'2026-07-24 10:50:00','2026-07-24 15:42:00','2026-07-24 15:42:00','Livrat',1,134.40,309.05),(92,'AWB20260724000092',10,1,28,97,'2026-07-24 10:50:00','2026-07-24 19:26:00','2026-07-24 19:26:00','Livrat',1,105.00,546.97),(93,'AWB20260724000093',10,4,22,98,'2026-07-24 10:50:00','2026-07-24 15:42:00','2026-07-24 15:42:00','Livrat',1,42.00,309.05),(94,'AWB20260720000094',17,2,28,99,'2026-07-20 21:11:00','2026-07-21 14:47:00','2026-07-21 14:47:00','Livrat',1,2520.00,546.97),(95,'AWB20260720000095',17,3,22,100,'2026-07-20 21:11:00','2026-07-21 11:03:00','2026-07-21 11:03:00','Livrat',1,42.00,309.05),(96,'AWB20260720000096',17,6,22,101,'2026-07-20 21:11:00','2026-07-21 11:03:00','2026-07-21 11:03:00','Livrat',1,168.00,309.05),(97,'AWB20260720000097',17,1,22,102,'2026-07-20 21:11:00','2026-07-21 11:03:00','2026-07-21 11:03:00','Livrat',1,1008.00,309.05);
/*!40000 ALTER TABLE `expedieri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory` (
  `InventoryID` int(11) NOT NULL AUTO_INCREMENT,
  `Product_ID` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `Product_Name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `Category` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `Stock_Level` int(11) DEFAULT NULL,
  `Reorder_Point` int(11) DEFAULT NULL,
  `Monthly_Sales` int(11) DEFAULT NULL,
  `Unit_Cost` decimal(10,2) DEFAULT NULL,
  `Cost_Unitar` decimal(10,2) DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `poze` varchar(200) DEFAULT NULL,
  `depozit` int(1) NOT NULL,
  PRIMARY KEY (`InventoryID`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
INSERT INTO `inventory` VALUES (1,'PRD-0001','Laptop Pro','Electronics',23112,20,15,2520.00,294.54,'2024-01-31','C:\\xampp\\htdocs\\CLAUDE\\PRIMUL\\poze\\2.webp',1),(2,'PRD-0001','Laptop Pro','Electronics',15386,20,16,2520.00,317.63,'2024-02-28','20260807_113515_1a7d67d9.jpg',1),(3,'PRD-0001','Laptop Pro','Electronics',37595,20,14,2520.00,243.74,'2024-03-31','20260807_112604_9590b888.webp',3),(4,'PRD-0002','Mouse Wireless','Electronics',11820,50,45,33.60,2.78,'2024-01-31','C:\\xampp\\htdocs\\CLAUDE\\PRIMUL\\poze\\4.webp',2),(5,'PRD-0002','Mouse Wireless','Electronics',16310,50,40,33.60,3.37,'2024-02-28','20260807_113448_7ee3c493.jpg',2),(6,'PRD-0002','Mouse Wireless','Electronics',36092,50,35,33.60,4.14,'2024-03-31','20260807_112524_34f647bf.webp',2),(7,'PRD-0003','Office Chair','Furniture',41531,15,8,504.00,39.86,'2024-01-31','C:\\xampp\\htdocs\\CLAUDE\\PRIMUL\\poze\\5.webp',1),(8,'PRD-0003','Office Chair','Furniture',49380,15,7,504.00,55.34,'2024-02-28','20260807_113422_c48b7f31.webp',2),(9,'PRD-0003','Office Chair','Furniture',32307,15,6,504.00,64.99,'2024-03-31','20260807_112446_960790df.jpg',3),(10,'PRD-0004','Desk Lamp','Furniture',43395,30,25,105.00,8.31,'2024-01-31','C:\\xampp\\htdocs\\CLAUDE\\PRIMUL\\poze\\1.webp',3),(11,'PRD-0004','Desk Lamp','Furniture',30055,30,22,105.00,9.75,'2024-02-28','20260807_113350_4cba6456.webp',3),(12,'PRD-0004','Desk Lamp','Furniture',10088,30,20,105.00,10.22,'2024-03-31','20260807_112413_467bea27.png',3),(13,'PRD-0005','USB-C Cable','Accessories',30275,100,80,21.00,1.65,'2024-01-31','20260807_114322_6ec0629e.webp',3),(14,'PRD-0005','USB-C Cable','Accessories',31110,100,70,21.00,1.63,'2024-02-28','20260807_113323_9543051f.webp',3),(15,'PRD-0005','USB-C Cable','Accessories',14727,100,60,21.00,1.62,'2024-03-31','20260807_112334_77b121ca.jpg',2),(16,'PRD-0006','Monitor 27','Electronics',10303,10,5,1050.00,80.11,'2024-01-31','C:\\xampp\\htdocs\\CLAUDE\\PRIMUL\\poze\\3.webp',2),(17,'PRD-0006','Monitor 27','Electronics',37332,10,4,1050.00,133.47,'2024-02-28','20260807_113255_a93bf4c9.jpg',2),(18,'PRD-0006','Monitor 27','Electronics',25753,10,3,1050.00,123.02,'2024-03-31','20260807_112306_ea41b700.webp',2),(21,'PRD-0007','pc ioana','Electronics',46768,30,5,5040.00,646.64,'2026-07-22','20260722_154728_0290ec43.webp',3),(22,'PRD-0008','Mechanical Keyboard','Electronics',18420,40,32,420.00,46.20,'2024-01-31','20260807_114308_84fa14cc.jpg',1),(23,'PRD-0008','Mechanical Keyboard','Electronics',24310,40,28,420.00,43.68,'2024-02-28','20260807_113227_e57dee88.jpg',2),(24,'PRD-0008','Mechanical Keyboard','Electronics',31775,40,25,420.00,48.72,'2024-03-31','20260807_112234_def2ee50.jpg',3),(25,'PRD-0009','Wireless Headset','Electronics',15630,35,27,630.00,69.30,'2024-01-31','20260807_114236_fbdac2df.jpg',1),(26,'PRD-0009','Wireless Headset','Electronics',22940,35,24,630.00,75.60,'2024-02-28','20260807_113142_5b7610bd.jpg',2),(27,'PRD-0009','Wireless Headset','Electronics',34180,35,21,630.00,66.15,'2024-03-31','20260807_112200_2dea8a97.webp',3),(28,'PRD-0010','Webcam HD','Electronics',27310,45,38,336.00,33.60,'2024-01-31','20260807_114212_b7e82b53.jpg',1),(29,'PRD-0010','Webcam HD','Electronics',19875,45,35,336.00,36.96,'2024-02-28','20260807_113111_7ee2dff6.jpg',2),(30,'PRD-0010','Webcam HD','Electronics',41260,45,30,336.00,30.24,'2024-03-31','20260807_112117_127991b7.jpg',3),(31,'PRD-0011','Docking Station','Electronics',13540,25,18,840.00,92.40,'2024-01-31','20260807_114140_9e259237.jpg',1),(32,'PRD-0011','Docking Station','Electronics',28110,25,16,840.00,84.00,'2024-02-28','20260807_113027_07601428.webp',2),(33,'PRD-0011','Docking Station','Electronics',36490,25,14,840.00,96.60,'2024-03-31','20260807_112044_0033e6f9.jpg',3),(34,'PRD-0012','External SSD 1TB','Electronics',21870,30,22,714.00,78.54,'2024-01-31','20260807_114104_f810da5f.jpg',1),(35,'PRD-0012','External SSD 1TB','Electronics',16450,30,20,714.00,71.40,'2024-02-28','20260807_113000_2388aca7.webp',2),(36,'PRD-0012','External SSD 1TB','Electronics',39025,30,17,714.00,85.68,'2024-03-31','20260807_110819_d280959f.jpg',3),(37,'PRD-0013','WiFi 6 Router','Electronics',24680,30,26,588.00,64.68,'2024-01-31','20260807_114032_2c82b1de.jpg',1),(38,'PRD-0013','WiFi 6 Router','Electronics',33120,30,23,588.00,58.80,'2024-02-28','20260807_112934_6a218e02.jpg',2),(39,'PRD-0013','WiFi 6 Router','Electronics',17940,30,19,588.00,70.56,'2024-03-31','20260807_110700_adf85713.jpg',3),(40,'PRD-0014','Standing Desk','Furniture',11230,12,9,2100.00,231.00,'2024-01-31','20260807_114008_28f46947.jpg',1),(41,'PRD-0014','Standing Desk','Furniture',26480,12,8,2100.00,210.00,'2024-02-28','20260807_112900_add1a499.jpg',2),(42,'PRD-0014','Standing Desk','Furniture',19760,12,7,2100.00,252.00,'2024-03-31','20260807_110626_d99d69ea.jpg',3),(43,'PRD-0015','Filing Cabinet','Furniture',14890,18,12,966.00,106.26,'2024-01-31','20260807_113856_00abb169.jpg',1),(44,'PRD-0015','Filing Cabinet','Furniture',30540,18,11,966.00,96.60,'2024-02-28','20260807_112827_70a47445.jpg',2),(45,'PRD-0015','Filing Cabinet','Furniture',22375,18,9,966.00,115.92,'2024-03-31','20260807_110543_e9d886bc.jpg',3),(46,'PRD-0016','Monitor Stand','Furniture',35720,40,34,252.00,25.20,'2024-01-31','20260807_113823_beb1c6cf.jpg',1),(47,'PRD-0016','Monitor Stand','Furniture',28460,40,31,252.00,27.72,'2024-02-28','20260807_112759_78b0e3e1.webp',2),(48,'PRD-0016','Monitor Stand','Furniture',16130,40,28,252.00,22.68,'2024-03-31','20260807_110501_3e8ca721.jpg',3),(49,'PRD-0017','Mousepad XL','Accessories',44210,90,85,63.00,5.67,'2024-01-31','20260807_113753_f08d19ec.webp',1),(50,'PRD-0017','Mousepad XL','Accessories',38970,90,78,63.00,6.30,'2024-02-28','20260807_112735_e4cf4d8e.webp',2),(51,'PRD-0017','Mousepad XL','Accessories',29840,90,72,63.00,5.04,'2024-03-31','20260807_110404_f797135d.jpg',3),(52,'PRD-0018','Laptop Bag','Accessories',23150,50,44,294.00,29.40,'2024-01-31','20260807_113726_2ef7908d.webp',1),(53,'PRD-0018','Laptop Bag','Accessories',31690,50,40,294.00,32.34,'2024-02-28','20260807_112705_771a554a.jpg',2),(54,'PRD-0018','Laptop Bag','Accessories',18720,50,36,294.00,26.46,'2024-03-31','20260807_110123_e6ee4268.jpg',3),(55,'PRD-0019','USB Hub 4 Port','Accessories',40580,80,68,126.00,12.60,'2024-01-31','20260807_113645_16e620b8.jpg',1),(56,'PRD-0019','USB Hub 4 Port','Accessories',26310,80,62,126.00,11.34,'2024-02-28','20260807_112637_2560ffba.jpg',2),(57,'PRD-0019','USB Hub 4 Port','Accessories',34870,80,57,126.00,13.86,'2024-03-31','20260807_110213_91a4bb62.webp',3);
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rute`
--

DROP TABLE IF EXISTS `rute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rute` (
  `RutaID` int(11) NOT NULL AUTO_INCREMENT,
  `Oras_origine` varchar(50) NOT NULL,
  `Oras_destinatie` varchar(50) NOT NULL,
  `Distanta_km` int(11) NOT NULL,
  `Durata_min` int(11) NOT NULL DEFAULT 0,
  `viteza` int(11) NOT NULL DEFAULT 0,
  `tip_strada` enum('autostrada','dn','drum judetean','drum comunal') NOT NULL DEFAULT 'dn',
  PRIMARY KEY (`RutaID`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rute`
--

LOCK TABLES `rute` WRITE;
/*!40000 ALTER TABLE `rute` DISABLE KEYS */;
INSERT INTO `rute` VALUES (12,'Arad','Brasov',418,463,95,'autostrada'),(13,'Arad','Bucuresti',547,592,60,'autostrada'),(14,'Arad','Cluj-Napoca',268,268,80,'dn'),(15,'Arad','Constanta',813,858,65,'drum judetean'),(16,'Arad','Iasi',755,800,80,'autostrada'),(17,'Arad','Timisoara',52,52,60,'autostrada'),(18,'Braila','Brasov',294,294,65,'drum comunal'),(19,'Braila','Bucuresti',244,244,90,'dn'),(20,'Braila','Cluj-Napoca',568,613,100,'autostrada'),(21,'Braila','Constanta',205,205,70,'autostrada'),(22,'Braila','Iasi',252,252,70,'dn'),(23,'Braila','Timisoara',717,757,75,'drum comunal'),(24,'Pitesti','Brasov',135,135,70,'drum comunal'),(25,'Pitesti','Bucuresti',114,114,80,'autostrada'),(26,'Pitesti','Cluj-Napoca',326,326,75,'autostrada'),(27,'Pitesti','Constanta',380,410,100,'drum judetean'),(28,'Pitesti','Iasi',446,476,65,'dn'),(29,'Pitesti','Timisoara',467,502,80,'autostrada');
/*!40000 ALTER TABLE `rute` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `soferi`
--

DROP TABLE IF EXISTS `soferi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `soferi` (
  `SoferID` int(11) NOT NULL AUTO_INCREMENT,
  `Nume` varchar(50) NOT NULL,
  `Telefon` varchar(20) NOT NULL,
  `Oras_baza` varchar(50) NOT NULL,
  `Data_angajare` date NOT NULL,
  PRIMARY KEY (`SoferID`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `soferi`
--

LOCK TABLES `soferi` WRITE;
/*!40000 ALTER TABLE `soferi` DISABLE KEYS */;
INSERT INTO `soferi` VALUES (1,'Cristian Tudor','0731111111','Bucuresti','2024-06-01'),(2,'Daniel Pop','0732222222','Cluj-Napoca','2024-07-01'),(3,'Florin Neagu','0733333333','Timisoara','2024-08-01'),(4,'George Lazar','0734444444','Iasi','2024-09-01'),(5,'Ioana Marinescu','0735555555','Constanta','2024-10-01'),(6,'Vlad Constantin','0736666655','Brasov','2024-11-01');
/*!40000 ALTER TABLE `soferi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(50) NOT NULL,
  `parola` varchar(255) NOT NULL COMMENT 'hash bcrypt (password_hash), niciodata parola in clar',
  `Nume` varchar(100) NOT NULL DEFAULT '',
  `Rol` varchar(30) NOT NULL DEFAULT 'admin',
  `Creat_la` datetime NOT NULL DEFAULT current_timestamp(),
  `Ultima_logare` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uq_users_login` (`login`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'ioana','$2y$10$Gbs59droLEB1MJpCPDDAp.RcupmRPS9hMQ10BLVFJqUNDE3QbA1im','Ioana','admin','2026-08-04 15:57:30','2026-08-10 16:24:32');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'sameday_company'
--

--
-- Dumping routines for database 'sameday_company'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-10 17:10:12
