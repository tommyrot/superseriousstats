-- MySQL dump 10.11
--
-- Host: localhost    Database: #chan
-- ------------------------------------------------------
-- Server version	5.0.51a

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
-- Table structure for table `channel`
--

DROP TABLE IF EXISTS `channel`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `channel` (
  `date` date NOT NULL default '0000-00-00',
  `l_00` int(10) unsigned NOT NULL default '0',
  `l_01` int(10) unsigned NOT NULL default '0',
  `l_02` int(10) unsigned NOT NULL default '0',
  `l_03` int(10) unsigned NOT NULL default '0',
  `l_04` int(10) unsigned NOT NULL default '0',
  `l_05` int(10) unsigned NOT NULL default '0',
  `l_06` int(10) unsigned NOT NULL default '0',
  `l_07` int(10) unsigned NOT NULL default '0',
  `l_08` int(10) unsigned NOT NULL default '0',
  `l_09` int(10) unsigned NOT NULL default '0',
  `l_10` int(10) unsigned NOT NULL default '0',
  `l_11` int(10) unsigned NOT NULL default '0',
  `l_12` int(10) unsigned NOT NULL default '0',
  `l_13` int(10) unsigned NOT NULL default '0',
  `l_14` int(10) unsigned NOT NULL default '0',
  `l_15` int(10) unsigned NOT NULL default '0',
  `l_16` int(10) unsigned NOT NULL default '0',
  `l_17` int(10) unsigned NOT NULL default '0',
  `l_18` int(10) unsigned NOT NULL default '0',
  `l_19` int(10) unsigned NOT NULL default '0',
  `l_20` int(10) unsigned NOT NULL default '0',
  `l_21` int(10) unsigned NOT NULL default '0',
  `l_22` int(10) unsigned NOT NULL default '0',
  `l_23` int(10) unsigned NOT NULL default '0',
  `l_night` int(10) unsigned NOT NULL default '0',
  `l_morning` int(10) unsigned NOT NULL default '0',
  `l_afternoon` int(10) unsigned NOT NULL default '0',
  `l_evening` int(10) unsigned NOT NULL default '0',
  `l_total` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `channel`
--

LOCK TABLES `channel` WRITE;
/*!40000 ALTER TABLE `channel` DISABLE KEYS */;
/*!40000 ALTER TABLE `channel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `query_events`
--

DROP TABLE IF EXISTS `query_events`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `query_events` (
  `UID` int(10) unsigned NOT NULL default '0',
  `m_op` int(10) unsigned NOT NULL default '0',
  `m_opped` int(10) unsigned NOT NULL default '0',
  `m_voice` int(10) unsigned NOT NULL default '0',
  `m_voiced` int(10) unsigned NOT NULL default '0',
  `m_deOp` int(10) unsigned NOT NULL default '0',
  `m_deOpped` int(10) unsigned NOT NULL default '0',
  `m_deVoice` int(10) unsigned NOT NULL default '0',
  `m_deVoiced` int(10) unsigned NOT NULL default '0',
  `joins` int(10) unsigned NOT NULL default '0',
  `parts` int(10) unsigned NOT NULL default '0',
  `quits` int(10) unsigned NOT NULL default '0',
  `kicks` int(10) unsigned NOT NULL default '0',
  `kicked` int(10) unsigned NOT NULL default '0',
  `nickchanges` int(10) unsigned NOT NULL default '0',
  `topics` int(10) unsigned NOT NULL default '0',
  `ex_kicks` varchar(255) NOT NULL default '',
  `ex_kicked` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`UID`),
  KEY `m_op` (`m_op`),
  KEY `m_opped` (`m_opped`),
  KEY `m_voice` (`m_voice`),
  KEY `m_voiced` (`m_voiced`),
  KEY `m_deOp` (`m_deOp`),
  KEY `m_deOpped` (`m_deOpped`),
  KEY `m_deVoice` (`m_deVoice`),
  KEY `m_deVoiced` (`m_deVoiced`),
  KEY `joins` (`joins`),
  KEY `parts` (`parts`),
  KEY `quits` (`quits`),
  KEY `kicks` (`kicks`),
  KEY `kicked` (`kicked`),
  KEY `nickchanges` (`nickchanges`),
  KEY `topics` (`topics`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `query_events`
--

LOCK TABLES `query_events` WRITE;
/*!40000 ALTER TABLE `query_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `query_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `query_lines`
--

DROP TABLE IF EXISTS `query_lines`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `query_lines` (
  `UID` int(10) unsigned NOT NULL default '0',
  `l_00` int(10) unsigned NOT NULL default '0',
  `l_01` int(10) unsigned NOT NULL default '0',
  `l_02` int(10) unsigned NOT NULL default '0',
  `l_03` int(10) unsigned NOT NULL default '0',
  `l_04` int(10) unsigned NOT NULL default '0',
  `l_05` int(10) unsigned NOT NULL default '0',
  `l_06` int(10) unsigned NOT NULL default '0',
  `l_07` int(10) unsigned NOT NULL default '0',
  `l_08` int(10) unsigned NOT NULL default '0',
  `l_09` int(10) unsigned NOT NULL default '0',
  `l_10` int(10) unsigned NOT NULL default '0',
  `l_11` int(10) unsigned NOT NULL default '0',
  `l_12` int(10) unsigned NOT NULL default '0',
  `l_13` int(10) unsigned NOT NULL default '0',
  `l_14` int(10) unsigned NOT NULL default '0',
  `l_15` int(10) unsigned NOT NULL default '0',
  `l_16` int(10) unsigned NOT NULL default '0',
  `l_17` int(10) unsigned NOT NULL default '0',
  `l_18` int(10) unsigned NOT NULL default '0',
  `l_19` int(10) unsigned NOT NULL default '0',
  `l_20` int(10) unsigned NOT NULL default '0',
  `l_21` int(10) unsigned NOT NULL default '0',
  `l_22` int(10) unsigned NOT NULL default '0',
  `l_23` int(10) unsigned NOT NULL default '0',
  `l_night` int(10) unsigned NOT NULL default '0',
  `l_morning` int(10) unsigned NOT NULL default '0',
  `l_afternoon` int(10) unsigned NOT NULL default '0',
  `l_evening` int(10) unsigned NOT NULL default '0',
  `l_total` int(10) unsigned NOT NULL default '0',
  `l_mon_night` int(10) unsigned NOT NULL default '0',
  `l_mon_morning` int(10) unsigned NOT NULL default '0',
  `l_mon_afternoon` int(10) unsigned NOT NULL default '0',
  `l_mon_evening` int(10) unsigned NOT NULL default '0',
  `l_tue_night` int(10) unsigned NOT NULL default '0',
  `l_tue_morning` int(10) unsigned NOT NULL default '0',
  `l_tue_afternoon` int(10) unsigned NOT NULL default '0',
  `l_tue_evening` int(10) unsigned NOT NULL default '0',
  `l_wed_night` int(10) unsigned NOT NULL default '0',
  `l_wed_morning` int(10) unsigned NOT NULL default '0',
  `l_wed_afternoon` int(10) unsigned NOT NULL default '0',
  `l_wed_evening` int(10) unsigned NOT NULL default '0',
  `l_thu_night` int(10) unsigned NOT NULL default '0',
  `l_thu_morning` int(10) unsigned NOT NULL default '0',
  `l_thu_afternoon` int(10) unsigned NOT NULL default '0',
  `l_thu_evening` int(10) unsigned NOT NULL default '0',
  `l_fri_night` int(10) unsigned NOT NULL default '0',
  `l_fri_morning` int(10) unsigned NOT NULL default '0',
  `l_fri_afternoon` int(10) unsigned NOT NULL default '0',
  `l_fri_evening` int(10) unsigned NOT NULL default '0',
  `l_sat_night` int(10) unsigned NOT NULL default '0',
  `l_sat_morning` int(10) unsigned NOT NULL default '0',
  `l_sat_afternoon` int(10) unsigned NOT NULL default '0',
  `l_sat_evening` int(10) unsigned NOT NULL default '0',
  `l_sun_night` int(10) unsigned NOT NULL default '0',
  `l_sun_morning` int(10) unsigned NOT NULL default '0',
  `l_sun_afternoon` int(10) unsigned NOT NULL default '0',
  `l_sun_evening` int(10) unsigned NOT NULL default '0',
  `URLs` int(10) unsigned NOT NULL default '0',
  `words` int(10) unsigned NOT NULL default '0',
  `characters` int(10) unsigned NOT NULL default '0',
  `monologues` int(10) unsigned NOT NULL default '0',
  `topMonologue` int(10) unsigned NOT NULL default '0',
  `activeDays` int(10) unsigned NOT NULL default '0',
  `slaps` int(10) unsigned NOT NULL default '0',
  `slapped` int(10) unsigned NOT NULL default '0',
  `exclamations` int(10) unsigned NOT NULL default '0',
  `questions` int(10) unsigned NOT NULL default '0',
  `actions` int(10) unsigned NOT NULL default '0',
  `uppercased` int(10) unsigned NOT NULL default '0',
  `quote` varchar(255) NOT NULL default '',
  `ex_exclamations` varchar(255) NOT NULL default '',
  `ex_questions` varchar(255) NOT NULL default '',
  `ex_actions` varchar(255) NOT NULL default '',
  `ex_uppercased` varchar(255) NOT NULL default '',
  `lastTalked` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`UID`),
  KEY `l_total` (`l_total`),
  KEY `URLs` (`URLs`),
  KEY `words` (`words`),
  KEY `characters` (`characters`),
  KEY `monologues` (`monologues`),
  KEY `topMonologue` (`topMonologue`),
  KEY `activeDays` (`activeDays`),
  KEY `slaps` (`slaps`),
  KEY `slapped` (`slapped`),
  KEY `exclamations` (`exclamations`),
  KEY `questions` (`questions`),
  KEY `actions` (`actions`),
  KEY `uppercased` (`uppercased`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `query_lines`
--

LOCK TABLES `query_lines` WRITE;
/*!40000 ALTER TABLE `query_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `query_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `query_smileys`
--

DROP TABLE IF EXISTS `query_smileys`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `query_smileys` (
  `UID` int(10) unsigned NOT NULL default '0',
  `s_01` int(10) unsigned NOT NULL default '0',
  `s_02` int(10) unsigned NOT NULL default '0',
  `s_03` int(10) unsigned NOT NULL default '0',
  `s_04` int(10) unsigned NOT NULL default '0',
  `s_05` int(10) unsigned NOT NULL default '0',
  `s_06` int(10) unsigned NOT NULL default '0',
  `s_07` int(10) unsigned NOT NULL default '0',
  `s_08` int(10) unsigned NOT NULL default '0',
  `s_09` int(10) unsigned NOT NULL default '0',
  `s_10` int(10) unsigned NOT NULL default '0',
  `s_11` int(10) unsigned NOT NULL default '0',
  `s_12` int(10) unsigned NOT NULL default '0',
  `s_13` int(10) unsigned NOT NULL default '0',
  `s_14` int(10) unsigned NOT NULL default '0',
  `s_15` int(10) unsigned NOT NULL default '0',
  `s_16` int(10) unsigned NOT NULL default '0',
  `s_17` int(10) unsigned NOT NULL default '0',
  `s_18` int(10) unsigned NOT NULL default '0',
  `s_19` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`UID`),
  KEY `s_01` (`s_01`),
  KEY `s_02` (`s_02`),
  KEY `s_03` (`s_03`),
  KEY `s_04` (`s_04`),
  KEY `s_05` (`s_05`),
  KEY `s_06` (`s_06`),
  KEY `s_07` (`s_07`),
  KEY `s_08` (`s_08`),
  KEY `s_09` (`s_09`),
  KEY `s_10` (`s_10`),
  KEY `s_11` (`s_11`),
  KEY `s_12` (`s_12`),
  KEY `s_13` (`s_13`),
  KEY `s_14` (`s_14`),
  KEY `s_15` (`s_15`),
  KEY `s_16` (`s_16`),
  KEY `s_17` (`s_17`),
  KEY `s_18` (`s_18`),
  KEY `s_19` (`s_19`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `query_smileys`
--

LOCK TABLES `query_smileys` WRITE;
/*!40000 ALTER TABLE `query_smileys` DISABLE KEYS */;
/*!40000 ALTER TABLE `query_smileys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_URLs`
--

DROP TABLE IF EXISTS `user_URLs`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user_URLs` (
  `LID` int(10) unsigned NOT NULL auto_increment,
  `UID` int(10) unsigned NOT NULL default '0',
  `csURL` varchar(255) NOT NULL,
  `total` int(10) unsigned NOT NULL default '0',
  `firstUsed` datetime NOT NULL default '0000-00-00 00:00:00',
  `lastUsed` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`LID`,`UID`),
  KEY `UID` (`UID`),
  KEY `csURL` (`csURL`),
  KEY `total` (`total`),
  KEY `firstUsed` (`firstUsed`),
  KEY `lastUsed` (`lastUsed`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `user_URLs`
--

LOCK TABLES `user_URLs` WRITE;
/*!40000 ALTER TABLE `user_URLs` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_URLs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_activity`
--

DROP TABLE IF EXISTS `user_activity`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user_activity` (
  `UID` int(10) unsigned NOT NULL default '0',
  `date` date NOT NULL default '0000-00-00',
  `l_night` int(10) unsigned NOT NULL default '0',
  `l_morning` int(10) unsigned NOT NULL default '0',
  `l_afternoon` int(10) unsigned NOT NULL default '0',
  `l_evening` int(10) unsigned NOT NULL default '0',
  `l_total` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`UID`,`date`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `user_activity`
--

LOCK TABLES `user_activity` WRITE;
/*!40000 ALTER TABLE `user_activity` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_details`
--

DROP TABLE IF EXISTS `user_details`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user_details` (
  `UID` int(10) unsigned NOT NULL auto_increment,
  `csNick` varchar(255) NOT NULL default '',
  `firstSeen` datetime NOT NULL default '0000-00-00 00:00:00',
  `lastSeen` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`UID`),
  UNIQUE KEY `csNick` (`csNick`),
  KEY `firstSeen` (`firstSeen`),
  KEY `lastSeen` (`lastSeen`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `user_details`
--

LOCK TABLES `user_details` WRITE;
/*!40000 ALTER TABLE `user_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_events`
--

DROP TABLE IF EXISTS `user_events`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user_events` (
  `UID` int(10) unsigned NOT NULL default '0',
  `m_op` int(10) unsigned NOT NULL default '0',
  `m_opped` int(10) unsigned NOT NULL default '0',
  `m_voice` int(10) unsigned NOT NULL default '0',
  `m_voiced` int(10) unsigned NOT NULL default '0',
  `m_deOp` int(10) unsigned NOT NULL default '0',
  `m_deOpped` int(10) unsigned NOT NULL default '0',
  `m_deVoice` int(10) unsigned NOT NULL default '0',
  `m_deVoiced` int(10) unsigned NOT NULL default '0',
  `joins` int(10) unsigned NOT NULL default '0',
  `parts` int(10) unsigned NOT NULL default '0',
  `quits` int(10) unsigned NOT NULL default '0',
  `kicks` int(10) unsigned NOT NULL default '0',
  `kicked` int(10) unsigned NOT NULL default '0',
  `nickchanges` int(10) unsigned NOT NULL default '0',
  `topics` int(10) unsigned NOT NULL default '0',
  `ex_kicks` varchar(255) NOT NULL default '',
  `ex_kicked` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`UID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `user_events`
--

LOCK TABLES `user_events` WRITE;
/*!40000 ALTER TABLE `user_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_hosts`
--

DROP TABLE IF EXISTS `user_hosts`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user_hosts` (
  `HID` int(10) unsigned NOT NULL auto_increment,
  `UID` int(10) unsigned NOT NULL default '0',
  `host` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`HID`,`UID`),
  KEY `UID` (`UID`),
  KEY `host` (`host`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `user_hosts`
--

LOCK TABLES `user_hosts` WRITE;
/*!40000 ALTER TABLE `user_hosts` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_hosts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_lines`
--

DROP TABLE IF EXISTS `user_lines`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user_lines` (
  `UID` int(10) unsigned NOT NULL default '0',
  `l_00` int(10) unsigned NOT NULL default '0',
  `l_01` int(10) unsigned NOT NULL default '0',
  `l_02` int(10) unsigned NOT NULL default '0',
  `l_03` int(10) unsigned NOT NULL default '0',
  `l_04` int(10) unsigned NOT NULL default '0',
  `l_05` int(10) unsigned NOT NULL default '0',
  `l_06` int(10) unsigned NOT NULL default '0',
  `l_07` int(10) unsigned NOT NULL default '0',
  `l_08` int(10) unsigned NOT NULL default '0',
  `l_09` int(10) unsigned NOT NULL default '0',
  `l_10` int(10) unsigned NOT NULL default '0',
  `l_11` int(10) unsigned NOT NULL default '0',
  `l_12` int(10) unsigned NOT NULL default '0',
  `l_13` int(10) unsigned NOT NULL default '0',
  `l_14` int(10) unsigned NOT NULL default '0',
  `l_15` int(10) unsigned NOT NULL default '0',
  `l_16` int(10) unsigned NOT NULL default '0',
  `l_17` int(10) unsigned NOT NULL default '0',
  `l_18` int(10) unsigned NOT NULL default '0',
  `l_19` int(10) unsigned NOT NULL default '0',
  `l_20` int(10) unsigned NOT NULL default '0',
  `l_21` int(10) unsigned NOT NULL default '0',
  `l_22` int(10) unsigned NOT NULL default '0',
  `l_23` int(10) unsigned NOT NULL default '0',
  `l_night` int(10) unsigned NOT NULL default '0',
  `l_morning` int(10) unsigned NOT NULL default '0',
  `l_afternoon` int(10) unsigned NOT NULL default '0',
  `l_evening` int(10) unsigned NOT NULL default '0',
  `l_total` int(10) unsigned NOT NULL default '0',
  `l_mon_night` int(10) unsigned NOT NULL default '0',
  `l_mon_morning` int(10) unsigned NOT NULL default '0',
  `l_mon_afternoon` int(10) unsigned NOT NULL default '0',
  `l_mon_evening` int(10) unsigned NOT NULL default '0',
  `l_tue_night` int(10) unsigned NOT NULL default '0',
  `l_tue_morning` int(10) unsigned NOT NULL default '0',
  `l_tue_afternoon` int(10) unsigned NOT NULL default '0',
  `l_tue_evening` int(10) unsigned NOT NULL default '0',
  `l_wed_night` int(10) unsigned NOT NULL default '0',
  `l_wed_morning` int(10) unsigned NOT NULL default '0',
  `l_wed_afternoon` int(10) unsigned NOT NULL default '0',
  `l_wed_evening` int(10) unsigned NOT NULL default '0',
  `l_thu_night` int(10) unsigned NOT NULL default '0',
  `l_thu_morning` int(10) unsigned NOT NULL default '0',
  `l_thu_afternoon` int(10) unsigned NOT NULL default '0',
  `l_thu_evening` int(10) unsigned NOT NULL default '0',
  `l_fri_night` int(10) unsigned NOT NULL default '0',
  `l_fri_morning` int(10) unsigned NOT NULL default '0',
  `l_fri_afternoon` int(10) unsigned NOT NULL default '0',
  `l_fri_evening` int(10) unsigned NOT NULL default '0',
  `l_sat_night` int(10) unsigned NOT NULL default '0',
  `l_sat_morning` int(10) unsigned NOT NULL default '0',
  `l_sat_afternoon` int(10) unsigned NOT NULL default '0',
  `l_sat_evening` int(10) unsigned NOT NULL default '0',
  `l_sun_night` int(10) unsigned NOT NULL default '0',
  `l_sun_morning` int(10) unsigned NOT NULL default '0',
  `l_sun_afternoon` int(10) unsigned NOT NULL default '0',
  `l_sun_evening` int(10) unsigned NOT NULL default '0',
  `URLs` int(10) unsigned NOT NULL default '0',
  `words` int(10) unsigned NOT NULL default '0',
  `characters` int(10) unsigned NOT NULL default '0',
  `monologues` int(10) unsigned NOT NULL default '0',
  `topMonologue` int(10) unsigned NOT NULL default '0',
  `activeDays` int(10) unsigned NOT NULL default '0',
  `slaps` int(10) unsigned NOT NULL default '0',
  `slapped` int(10) unsigned NOT NULL default '0',
  `exclamations` int(10) unsigned NOT NULL default '0',
  `questions` int(10) unsigned NOT NULL default '0',
  `actions` int(10) unsigned NOT NULL default '0',
  `uppercased` int(10) unsigned NOT NULL default '0',
  `quote` varchar(255) NOT NULL default '',
  `ex_exclamations` varchar(255) NOT NULL default '',
  `ex_questions` varchar(255) NOT NULL default '',
  `ex_actions` varchar(255) NOT NULL default '',
  `ex_uppercased` varchar(255) NOT NULL default '',
  `lastTalked` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`UID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `user_lines`
--

LOCK TABLES `user_lines` WRITE;
/*!40000 ALTER TABLE `user_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_smileys`
--

DROP TABLE IF EXISTS `user_smileys`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user_smileys` (
  `UID` int(10) unsigned NOT NULL default '0',
  `s_01` int(10) unsigned NOT NULL default '0',
  `s_02` int(10) unsigned NOT NULL default '0',
  `s_03` int(10) unsigned NOT NULL default '0',
  `s_04` int(10) unsigned NOT NULL default '0',
  `s_05` int(10) unsigned NOT NULL default '0',
  `s_06` int(10) unsigned NOT NULL default '0',
  `s_07` int(10) unsigned NOT NULL default '0',
  `s_08` int(10) unsigned NOT NULL default '0',
  `s_09` int(10) unsigned NOT NULL default '0',
  `s_10` int(10) unsigned NOT NULL default '0',
  `s_11` int(10) unsigned NOT NULL default '0',
  `s_12` int(10) unsigned NOT NULL default '0',
  `s_13` int(10) unsigned NOT NULL default '0',
  `s_14` int(10) unsigned NOT NULL default '0',
  `s_15` int(10) unsigned NOT NULL default '0',
  `s_16` int(10) unsigned NOT NULL default '0',
  `s_17` int(10) unsigned NOT NULL default '0',
  `s_18` int(10) unsigned NOT NULL default '0',
  `s_19` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`UID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `user_smileys`
--

LOCK TABLES `user_smileys` WRITE;
/*!40000 ALTER TABLE `user_smileys` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_smileys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_status`
--

DROP TABLE IF EXISTS `user_status`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user_status` (
  `UID` int(10) unsigned NOT NULL default '0',
  `RUID` int(10) unsigned NOT NULL default '0',
  `status` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`UID`),
  KEY `RUID` (`RUID`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `user_status`
--

LOCK TABLES `user_status` WRITE;
/*!40000 ALTER TABLE `user_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_topics`
--

DROP TABLE IF EXISTS `user_topics`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user_topics` (
  `TID` int(10) unsigned NOT NULL auto_increment,
  `UID` int(10) unsigned NOT NULL default '0',
  `csTopic` varchar(255) NOT NULL default '',
  `setDate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`TID`,`UID`,`setDate`),
  KEY `UID` (`UID`),
  KEY `csTopic` (`csTopic`),
  KEY `setDate` (`setDate`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `user_topics`
--

LOCK TABLES `user_topics` WRITE;
/*!40000 ALTER TABLE `user_topics` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_topics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `words`
--

DROP TABLE IF EXISTS `words`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `words` (
  `WID` int(10) unsigned NOT NULL auto_increment,
  `word` varchar(255) NOT NULL default '',
  `total` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`WID`),
  UNIQUE KEY `word` (`word`),
  KEY `total` (`total`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `words`
--

LOCK TABLES `words` WRITE;
/*!40000 ALTER TABLE `words` DISABLE KEYS */;
/*!40000 ALTER TABLE `words` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2009-03-23 20:42:14
