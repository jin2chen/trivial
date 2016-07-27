/**
 * 游标应用
 *
 * @author mole <mole.chen@foxmail.com>
 */
DROP PROCEDURE IF EXISTS p;
DELIMITER $$
CREATE PROCEDURE p ()
BEGIN
	DECLARE xsalary FLOAT(20,14);
	DECLARE done TINYINT DEFAULT 0;
	DECLARE cur1 CURSOR FOR SELECT salary FROM t;
	DECLARE cur2 CURSOR FOR SELECT salary FROM t;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

	OPEN cur1;
	read_loop: LOOP
		FETCH FROM cur1 INTO xsalary;
		IF done THEN 
			LEAVE read_loop; 
		END IF;
		SELECT 1, xsalary;
	END LOOP;
	CLOSE cur1;
	
	SET xsalary = NULL;
	SET done = 0;
	OPEN cur2;
	read_loop: LOOP
		FETCH FROM cur2 INTO xsalary;
		IF done THEN 
			LEAVE read_loop; 
		END IF;
		SELECT 2, xsalary;
	END LOOP;
	CLOSE cur2;
END
$$
DELIMITER ;


-- -- MySQL dump 10.13  Distrib 5.5.15, for Win32 (x86)
-- --
-- -- Host: localhost    Database: test
-- -- ------------------------------------------------------
-- -- Server version	5.5.15
-- 
-- /*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
-- /*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
-- /*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
-- /*!40101 SET NAMES utf8 */;
-- /*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
-- /*!40103 SET TIME_ZONE='+00:00' */;
-- /*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
-- /*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
-- /*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
-- /*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
-- 
-- --
-- -- Table structure for table `t`
-- --
-- 
-- DROP TABLE IF EXISTS `t`;
-- /*!40101 SET @saved_cs_client     = @@character_set_client */;
-- /*!40101 SET character_set_client = utf8 */;
-- CREATE TABLE `t` (
--   `salary` float(20,14) NOT NULL
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
-- /*!40101 SET character_set_client = @saved_cs_client */;
-- 
-- --
-- -- Dumping data for table `t`
-- --
-- 
-- LOCK TABLES `t` WRITE;
-- /*!40000 ALTER TABLE `t` DISABLE KEYS */;
-- INSERT INTO `t` VALUES (1.00000107288361),(1.00000000000000),(1.00000095367432);
-- /*!40000 ALTER TABLE `t` ENABLE KEYS */;
-- UNLOCK TABLES;
-- /*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
-- 
-- /*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
-- /*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
-- /*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
-- /*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
-- /*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
-- /*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
-- /*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
-- 
-- -- Dump completed on 2011-10-13  0:11:48
