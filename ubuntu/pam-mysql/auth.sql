-- MySQL dump 10.13  Distrib 5.5.31, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: auth
-- ------------------------------------------------------
-- Server version	5.5.31-0ubuntu0.12.10.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `auth`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `auth` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `auth`;

--
-- Table structure for table `grouplist`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grouplist` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `gid` int(11) NOT NULL DEFAULT '0',
  `username` char(16) NOT NULL DEFAULT '',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `gid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(16) NOT NULL DEFAULT '',
  `password` varchar(34) NOT NULL DEFAULT 'x',
  PRIMARY KEY (`gid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(16) NOT NULL DEFAULT '',
  `gid` int(11) NOT NULL DEFAULT '5000',
  `gecos` varchar(128) NOT NULL DEFAULT '',
  `homedir` varchar(255) NOT NULL DEFAULT '',
  `shell` varchar(64) NOT NULL DEFAULT '/bin/bash',
  `password` varchar(100) NOT NULL DEFAULT 'x',
  `lstchg` bigint(20) NOT NULL DEFAULT '1',
  `min` bigint(20) NOT NULL DEFAULT '0',
  `max` bigint(20) NOT NULL DEFAULT '99999',
  `warn` bigint(20) NOT NULL DEFAULT '0',
  `inact` bigint(20) NOT NULL DEFAULT '0',
  `expire` bigint(20) NOT NULL DEFAULT '-1',
  `flag` bigint(20) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Insert data
--

INSERT INTO `grouplist` VALUES (1,5000,'jchen');
INSERT INTO `groups` VALUES (5000,'jchen','x');
INSERT INTO `users` VALUES (5000,'jchen',5000,'jchen','/home/jchen','/bin/bash',MD5('jchen'),1,0,99999,0,0,-1,0);

--
-- GRANT PRIVILEGES
--

GRANT SELECT, INSERT, UPDATE, DELETE ON auth.* TO 'pam'@'%' IDENTIFIED BY 'pam';

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-05-03 11:54:08
