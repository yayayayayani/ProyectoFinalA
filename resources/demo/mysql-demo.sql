-- MySQL dump 10.13  Distrib 8.4.9, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: cantera_torneo_test
-- ------------------------------------------------------
-- Server version	8.4.9

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `equipo`
--

INSERT INTO `equipo` VALUES (4,'Academia Norte · Demo'),(5,'Halcones FC · Demo'),(6,'Deportivo Aurora · Demo'),(7,'Leones del Sur · Demo');

--
-- Dumping data for table `jugador`
--

INSERT INTO `jugador` VALUES (5,'Mateo','García','2015-01-10','bad4b08f1e5de3ca2668abc358010f40.png',4),(6,'Sofía','López','2015-02-11','8c469807e5d3c463c1442064dd011c9a.png',4),(7,'Daniel','Pérez','2015-03-12','7e4705773bc2175ce2c2108af1f9f9bb.png',4),(8,'Valentina','Castillo','2015-04-13','7ca9251ade8bae6f60493f2858134297.png',5),(9,'Diego','Hernández','2015-05-14','35a1c29b544f313eca466aa177cb11e0.png',5),(10,'Emilia','Morales','2015-06-15','aad0746b39883c547a2f01cb3190540a.png',5),(11,'Santiago','Ramírez','2015-07-16','4d02145c61ed96ba14d310d73e962399.png',6),(12,'Lucía','Flores','2015-08-17','90ceac48f1cf480dd85e117551cbf94c.png',6),(13,'Nicolás','Ruiz','2015-09-18','5e9c13b6a9ff3f12a2012c96913f0066.png',6),(14,'Camila','Vásquez','2015-10-19','3030fc911c6398dbefd350e6a6cfb024.png',7),(15,'Sebastián','Méndez','2015-11-20','7d8b6b0a1015dd9ce8c1b9d3292fca91.png',7),(16,'Isabella','Torres','2015-12-21','11678b736692881fcbca4b8af31252ab.png',7);

--
-- Dumping data for table `jornada`
--

INSERT INTO `jornada` VALUES (4,'Jornada 1 · Demo','2026-10-10'),(5,'Jornada 2 · Demo','2026-10-17'),(6,'Jornada 3 · Demo','2026-10-24'),(7,'Jornada 4 · Demo','2026-10-31');

--
-- Dumping data for table `gol`
--

INSERT INTO `gol` VALUES (5,4,5,4),(6,2,6,4),(7,1,7,4),(8,3,8,4),(9,5,9,4),(10,1,10,4),(11,2,11,4),(12,3,12,4),(13,1,13,4),(14,2,14,4),(15,1,15,4),(16,4,16,4);

--
-- Dumping data for table `incidencia`
--

INSERT INTO `incidencia` VALUES (2,'Ejemplo: amonestación por una falta durante el juego.','amarilla','2026-10-10',NULL,5),(3,'Ejemplo: expulsión por una falta durante el juego.','roja','2026-10-10','2026-10-17',8),(4,'Ejemplo: amonestación por una falta durante el juego.','amarilla','2026-10-10',NULL,11),(5,'Ejemplo: amonestación por una falta durante el juego.','amarilla','2026-10-10',NULL,14);
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-14 21:28:20
