-- MySQL dump 10.13  Distrib 5.1.69, for redhat-linux-gnu (i386)
--
-- Host: localhost    Database: bedtime
-- ------------------------------------------------------
-- Server version	5.1.69

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
-- Current Database: `bedtime`
--

/*!40000 DROP DATABASE IF EXISTS `bedtime`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `bedtime` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `bedtime`;

--
-- Table structure for table `child`
--

DROP TABLE IF EXISTS `child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `child` (
  `user_id` mediumint(9) NOT NULL AUTO_INCREMENT COMMENT 'glue record between tables',
  `name` varchar(32) NOT NULL COMMENT 'The child''s name. Why are you even looking this up?',
  `description` varchar(128) DEFAULT NULL COMMENT 'Description',
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='maps child name to description and ID';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `child`
--

LOCK TABLES `child` WRITE;
/*!40000 ALTER TABLE `child` DISABLE KEYS */;
/*!40000 ALTER TABLE `child` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `device`
--

DROP TABLE IF EXISTS `device`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `device` (
  `mac` bigint(24) NOT NULL COMMENT 'MAC address. Select with select hex(mac). Insert like values (x''00FF3A55FFDD'')',
  `description` varchar(128) DEFAULT NULL COMMENT 'Description',
  `user_id` mediumint(9) NOT NULL DEFAULT '0' COMMENT 'ID of the child using the device',
  `first_seen` datetime NOT NULL,
  `ip` int(10) unsigned DEFAULT NULL,
  `manu` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`mac`,`user_id`),
  UNIQUE KEY `mac_UNIQUE` (`mac`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='maps device MAC address to user ID and description';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `device`
--

LOCK TABLES `device` WRITE;
/*!40000 ALTER TABLE `device` DISABLE KEYS */;
/*!40000 ALTER TABLE `device` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ground`
--

DROP TABLE IF EXISTS `ground`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ground` (
  `user_id` mediumint(9) NOT NULL COMMENT 'ID of the miscreant',
  `start` datetime NOT NULL COMMENT 'time their punishment starts',
  `end` datetime NOT NULL COMMENT 'time they are off the hook again',
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Rules in this table take precedence of bedtimes and rewards';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ground`
--

LOCK TABLES `ground` WRITE;
/*!40000 ALTER TABLE `ground` DISABLE KEYS */;
/*!40000 ALTER TABLE `ground` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holiday`
--

DROP TABLE IF EXISTS `holiday`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `holiday` (
  `hol_id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `start` date NOT NULL,
  `stop` date NOT NULL,
  PRIMARY KEY (`hol_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holiday`
--

LOCK TABLES `holiday` WRITE;
/*!40000 ALTER TABLE `holiday` DISABLE KEYS */;
/*!40000 ALTER TABLE `holiday` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parent`
--

DROP TABLE IF EXISTS `parent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parent` (
  `name` varchar(32) NOT NULL COMMENT 'Login name of the parent',
  `password` char(128) NOT NULL COMMENT 'Pass phrase for the parent',
  `description` varchar(128) DEFAULT NULL COMMENT 'Optional description',
  `parent_id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`parent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Authentication table for controlling the app';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parent`
--

LOCK TABLES `parent` WRITE;
/*!40000 ALTER TABLE `parent` DISABLE KEYS */;
/*!40000 ALTER TABLE `parent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reward`
--

DROP TABLE IF EXISTS `reward`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reward` (
  `user_id` mediumint(9) NOT NULL COMMENT 'user ID of the little angel',
  `start` datetime NOT NULL COMMENT 'start of the guaranteed device use',
  `end` datetime NOT NULL COMMENT 'All good things must come to an end',
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Free use of the devices as a reward';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reward`
--

LOCK TABLES `reward` WRITE;
/*!40000 ALTER TABLE `reward` DISABLE KEYS */;
/*!40000 ALTER TABLE `reward` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rules`
--

DROP TABLE IF EXISTS `rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rules` (
  `user_id` mediumint(9) NOT NULL COMMENT 'The user ID of the child with this bedtime rule',
  `night` time NOT NULL COMMENT 'The time to go to bed',
  `morning` time NOT NULL COMMENT 'The time the device is released again',
  `days` tinyint(3) unsigned NOT NULL DEFAULT '254' COMMENT 'Byte flag of the days 0-Mon 7-Sun'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='The bread and butter';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rules`
--

LOCK TABLES `rules` WRITE;
/*!40000 ALTER TABLE `rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `variable` varchar(16) NOT NULL COMMENT 'name of the variable - duh',
  `value` varchar(64) NOT NULL COMMENT 'and the free text value',
  PRIMARY KEY (`variable`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Miscellaneous settings for bedtime itself';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('weekend','12');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-12-19 14:04:57
