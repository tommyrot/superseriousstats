-- MySQL dump 10.11
--
-- Host: localhost    Database: superseriousstats
-- ------------------------------------------------------
-- Server version	5.0.77

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
-- Table structure for table `mview_ex_actions`
--

DROP TABLE IF EXISTS `mview_ex_actions`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mview_ex_actions` (
  `RUID` int(10) unsigned NOT NULL default '0',
  `ex_actions` varchar(255) NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `mview_ex_actions`
--

LOCK TABLES `mview_ex_actions` WRITE;
/*!40000 ALTER TABLE `mview_ex_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `mview_ex_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mview_ex_exclamations`
--

DROP TABLE IF EXISTS `mview_ex_exclamations`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mview_ex_exclamations` (
  `RUID` int(10) unsigned NOT NULL default '0',
  `ex_exclamations` varchar(255) NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `mview_ex_exclamations`
--

LOCK TABLES `mview_ex_exclamations` WRITE;
/*!40000 ALTER TABLE `mview_ex_exclamations` DISABLE KEYS */;
/*!40000 ALTER TABLE `mview_ex_exclamations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mview_ex_kicked`
--

DROP TABLE IF EXISTS `mview_ex_kicked`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mview_ex_kicked` (
  `RUID` int(10) unsigned NOT NULL default '0',
  `ex_kicked` varchar(255) NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `mview_ex_kicked`
--

LOCK TABLES `mview_ex_kicked` WRITE;
/*!40000 ALTER TABLE `mview_ex_kicked` DISABLE KEYS */;
/*!40000 ALTER TABLE `mview_ex_kicked` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mview_ex_kicks`
--

DROP TABLE IF EXISTS `mview_ex_kicks`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mview_ex_kicks` (
  `RUID` int(10) unsigned NOT NULL default '0',
  `ex_kicks` varchar(255) NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `mview_ex_kicks`
--

LOCK TABLES `mview_ex_kicks` WRITE;
/*!40000 ALTER TABLE `mview_ex_kicks` DISABLE KEYS */;
/*!40000 ALTER TABLE `mview_ex_kicks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mview_ex_questions`
--

DROP TABLE IF EXISTS `mview_ex_questions`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mview_ex_questions` (
  `RUID` int(10) unsigned NOT NULL default '0',
  `ex_questions` varchar(255) NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `mview_ex_questions`
--

LOCK TABLES `mview_ex_questions` WRITE;
/*!40000 ALTER TABLE `mview_ex_questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `mview_ex_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mview_ex_uppercased`
--

DROP TABLE IF EXISTS `mview_ex_uppercased`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mview_ex_uppercased` (
  `RUID` int(10) unsigned NOT NULL default '0',
  `ex_uppercased` varchar(255) NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `mview_ex_uppercased`
--

LOCK TABLES `mview_ex_uppercased` WRITE;
/*!40000 ALTER TABLE `mview_ex_uppercased` DISABLE KEYS */;
/*!40000 ALTER TABLE `mview_ex_uppercased` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mview_quote`
--

DROP TABLE IF EXISTS `mview_quote`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mview_quote` (
  `RUID` int(10) unsigned NOT NULL default '0',
  `quote` varchar(255) NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `mview_quote`
--

LOCK TABLES `mview_quote` WRITE;
/*!40000 ALTER TABLE `mview_quote` DISABLE KEYS */;
/*!40000 ALTER TABLE `mview_quote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `new_query_events`
--

DROP TABLE IF EXISTS `new_query_events`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `new_query_events` (
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
-- Dumping data for table `new_query_events`
--

LOCK TABLES `new_query_events` WRITE;
/*!40000 ALTER TABLE `new_query_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `new_query_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parse_history`
--

DROP TABLE IF EXISTS `parse_history`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `parse_history` (
  `date` date NOT NULL default '0000-00-00',
  `lines_parsed` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `parse_history`
--

LOCK TABLES `parse_history` WRITE;
/*!40000 ALTER TABLE `parse_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `parse_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `streak_history`
--

DROP TABLE IF EXISTS `streak_history`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `streak_history` (
  `prevNick` varchar(255) NOT NULL default '',
  `streak` int(10) unsigned NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `streak_history`
--

LOCK TABLES `streak_history` WRITE;
/*!40000 ALTER TABLE `streak_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `streak_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `template_query_events`
--

DROP TABLE IF EXISTS `template_query_events`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `template_query_events` (
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
-- Dumping data for table `template_query_events`
--

LOCK TABLES `template_query_events` WRITE;
/*!40000 ALTER TABLE `template_query_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `template_query_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `template_query_lines`
--

DROP TABLE IF EXISTS `template_query_lines`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `template_query_lines` (
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
-- Dumping data for table `template_query_lines`
--

LOCK TABLES `template_query_lines` WRITE;
/*!40000 ALTER TABLE `template_query_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `template_query_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `template_query_smileys`
--

DROP TABLE IF EXISTS `template_query_smileys`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `template_query_smileys` (
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
-- Dumping data for table `template_query_smileys`
--

LOCK TABLES `template_query_smileys` WRITE;
/*!40000 ALTER TABLE `template_query_smileys` DISABLE KEYS */;
/*!40000 ALTER TABLE `template_query_smileys` ENABLE KEYS */;
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
  `csURL` varchar(255) NOT NULL default '',
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
-- Temporary table structure for view `view_activeDays`
--

DROP TABLE IF EXISTS `view_activeDays`;
/*!50001 DROP VIEW IF EXISTS `view_activeDays`*/;
/*!50001 CREATE TABLE `view_activeDays` (
  `RUID` int(10) unsigned,
  `activeDays` bigint(21)
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_events`
--

DROP TABLE IF EXISTS `view_events`;
/*!50001 DROP VIEW IF EXISTS `view_events`*/;
/*!50001 CREATE TABLE `view_events` (
  `RUID` int(10) unsigned,
  `m_op` decimal(33,0),
  `m_opped` decimal(33,0),
  `m_voice` decimal(33,0),
  `m_voiced` decimal(33,0),
  `m_deOp` decimal(33,0),
  `m_deOpped` decimal(33,0),
  `m_deVoice` decimal(33,0),
  `m_deVoiced` decimal(33,0),
  `joins` decimal(33,0),
  `parts` decimal(33,0),
  `quits` decimal(33,0),
  `kicks` decimal(33,0),
  `kicked` decimal(33,0),
  `nickchanges` decimal(33,0),
  `topics` decimal(33,0)
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_ex_actions`
--

DROP TABLE IF EXISTS `view_ex_actions`;
/*!50001 DROP VIEW IF EXISTS `view_ex_actions`*/;
/*!50001 CREATE TABLE `view_ex_actions` (
  `RUID` int(10) unsigned,
  `ex_actions` varchar(255)
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_ex_actions_1`
--

DROP TABLE IF EXISTS `view_ex_actions_1`;
/*!50001 DROP VIEW IF EXISTS `view_ex_actions_1`*/;
/*!50001 CREATE TABLE `view_ex_actions_1` (
  `RUID` int(10) unsigned,
  `ex_actions` varchar(255),
  `lastSeen` datetime
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_ex_actions_2`
--

DROP TABLE IF EXISTS `view_ex_actions_2`;
/*!50001 DROP VIEW IF EXISTS `view_ex_actions_2`*/;
/*!50001 CREATE TABLE `view_ex_actions_2` (
  `ruid` int(10) unsigned,
  `lastSeen` datetime
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_ex_exclamations`
--

DROP TABLE IF EXISTS `view_ex_exclamations`;
/*!50001 DROP VIEW IF EXISTS `view_ex_exclamations`*/;
/*!50001 CREATE TABLE `view_ex_exclamations` (
  `RUID` int(10) unsigned,
  `ex_exclamations` varchar(255)
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_ex_exclamations_1`
--

DROP TABLE IF EXISTS `view_ex_exclamations_1`;
/*!50001 DROP VIEW IF EXISTS `view_ex_exclamations_1`*/;
/*!50001 CREATE TABLE `view_ex_exclamations_1` (
  `RUID` int(10) unsigned,
  `ex_exclamations` varchar(255),
  `lastTalked` datetime
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_ex_exclamations_2`
--

DROP TABLE IF EXISTS `view_ex_exclamations_2`;
/*!50001 DROP VIEW IF EXISTS `view_ex_exclamations_2`*/;
/*!50001 CREATE TABLE `view_ex_exclamations_2` (
  `ruid` int(10) unsigned,
  `lastTalked` datetime
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_ex_kicked`
--

DROP TABLE IF EXISTS `view_ex_kicked`;
/*!50001 DROP VIEW IF EXISTS `view_ex_kicked`*/;
/*!50001 CREATE TABLE `view_ex_kicked` (
  `RUID` int(10) unsigned,
  `ex_kicked` varchar(255)
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_ex_kicked_1`
--

DROP TABLE IF EXISTS `view_ex_kicked_1`;
/*!50001 DROP VIEW IF EXISTS `view_ex_kicked_1`*/;
/*!50001 CREATE TABLE `view_ex_kicked_1` (
  `RUID` int(10) unsigned,
  `ex_kicked` varchar(255)
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_ex_kicks`
--

DROP TABLE IF EXISTS `view_ex_kicks`;
/*!50001 DROP VIEW IF EXISTS `view_ex_kicks`*/;
/*!50001 CREATE TABLE `view_ex_kicks` (
  `RUID` int(10) unsigned,
  `ex_kicks` varchar(255)
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_ex_kicks_1`
--

DROP TABLE IF EXISTS `view_ex_kicks_1`;
/*!50001 DROP VIEW IF EXISTS `view_ex_kicks_1`*/;
/*!50001 CREATE TABLE `view_ex_kicks_1` (
  `RUID` int(10) unsigned,
  `ex_kicks` varchar(255)
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_ex_questions`
--

DROP TABLE IF EXISTS `view_ex_questions`;
/*!50001 DROP VIEW IF EXISTS `view_ex_questions`*/;
/*!50001 CREATE TABLE `view_ex_questions` (
  `RUID` int(10) unsigned,
  `ex_questions` varchar(255)
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_ex_questions_1`
--

DROP TABLE IF EXISTS `view_ex_questions_1`;
/*!50001 DROP VIEW IF EXISTS `view_ex_questions_1`*/;
/*!50001 CREATE TABLE `view_ex_questions_1` (
  `RUID` int(10) unsigned,
  `ex_questions` varchar(255),
  `lastTalked` datetime
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_ex_questions_2`
--

DROP TABLE IF EXISTS `view_ex_questions_2`;
/*!50001 DROP VIEW IF EXISTS `view_ex_questions_2`*/;
/*!50001 CREATE TABLE `view_ex_questions_2` (
  `ruid` int(10) unsigned,
  `lastTalked` datetime
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_ex_uppercased`
--

DROP TABLE IF EXISTS `view_ex_uppercased`;
/*!50001 DROP VIEW IF EXISTS `view_ex_uppercased`*/;
/*!50001 CREATE TABLE `view_ex_uppercased` (
  `RUID` int(10) unsigned,
  `ex_uppercased` varchar(255)
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_ex_uppercased_1`
--

DROP TABLE IF EXISTS `view_ex_uppercased_1`;
/*!50001 DROP VIEW IF EXISTS `view_ex_uppercased_1`*/;
/*!50001 CREATE TABLE `view_ex_uppercased_1` (
  `RUID` int(10) unsigned,
  `ex_uppercased` varchar(255),
  `lastTalked` datetime
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_ex_uppercased_2`
--

DROP TABLE IF EXISTS `view_ex_uppercased_2`;
/*!50001 DROP VIEW IF EXISTS `view_ex_uppercased_2`*/;
/*!50001 CREATE TABLE `view_ex_uppercased_2` (
  `ruid` int(10) unsigned,
  `lastTalked` datetime
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_lines`
--

DROP TABLE IF EXISTS `view_lines`;
/*!50001 DROP VIEW IF EXISTS `view_lines`*/;
/*!50001 CREATE TABLE `view_lines` (
  `RUID` int(10) unsigned,
  `l_00` decimal(33,0),
  `l_01` decimal(33,0),
  `l_02` decimal(33,0),
  `l_03` decimal(33,0),
  `l_04` decimal(33,0),
  `l_05` decimal(33,0),
  `l_06` decimal(33,0),
  `l_07` decimal(33,0),
  `l_08` decimal(33,0),
  `l_09` decimal(33,0),
  `l_10` decimal(33,0),
  `l_11` decimal(33,0),
  `l_12` decimal(33,0),
  `l_13` decimal(33,0),
  `l_14` decimal(33,0),
  `l_15` decimal(33,0),
  `l_16` decimal(33,0),
  `l_17` decimal(33,0),
  `l_18` decimal(33,0),
  `l_19` decimal(33,0),
  `l_20` decimal(33,0),
  `l_21` decimal(33,0),
  `l_22` decimal(33,0),
  `l_23` decimal(33,0),
  `l_night` decimal(33,0),
  `l_morning` decimal(33,0),
  `l_afternoon` decimal(33,0),
  `l_evening` decimal(33,0),
  `l_total` decimal(33,0),
  `l_mon_night` decimal(33,0),
  `l_mon_morning` decimal(33,0),
  `l_mon_afternoon` decimal(33,0),
  `l_mon_evening` decimal(33,0),
  `l_tue_night` decimal(33,0),
  `l_tue_morning` decimal(33,0),
  `l_tue_afternoon` decimal(33,0),
  `l_tue_evening` decimal(33,0),
  `l_wed_night` decimal(33,0),
  `l_wed_morning` decimal(33,0),
  `l_wed_afternoon` decimal(33,0),
  `l_wed_evening` decimal(33,0),
  `l_thu_night` decimal(33,0),
  `l_thu_morning` decimal(33,0),
  `l_thu_afternoon` decimal(33,0),
  `l_thu_evening` decimal(33,0),
  `l_fri_night` decimal(33,0),
  `l_fri_morning` decimal(33,0),
  `l_fri_afternoon` decimal(33,0),
  `l_fri_evening` decimal(33,0),
  `l_sat_night` decimal(33,0),
  `l_sat_morning` decimal(33,0),
  `l_sat_afternoon` decimal(33,0),
  `l_sat_evening` decimal(33,0),
  `l_sun_night` decimal(33,0),
  `l_sun_morning` decimal(33,0),
  `l_sun_afternoon` decimal(33,0),
  `l_sun_evening` decimal(33,0),
  `URLs` decimal(33,0),
  `words` decimal(33,0),
  `characters` decimal(33,0),
  `monologues` decimal(33,0),
  `topMonologue` int(10) unsigned,
  `slaps` decimal(33,0),
  `slapped` decimal(33,0),
  `exclamations` decimal(33,0),
  `questions` decimal(33,0),
  `actions` decimal(33,0),
  `uppercased` decimal(33,0),
  `lastTalked` datetime
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_query_events`
--

DROP TABLE IF EXISTS `view_query_events`;
/*!50001 DROP VIEW IF EXISTS `view_query_events`*/;
/*!50001 CREATE TABLE `view_query_events` (
  `UID` int(10) unsigned,
  `m_op` decimal(33,0),
  `m_opped` decimal(33,0),
  `m_voice` decimal(33,0),
  `m_voiced` decimal(33,0),
  `m_deOp` decimal(33,0),
  `m_deOpped` decimal(33,0),
  `m_deVoice` decimal(33,0),
  `m_deVoiced` decimal(33,0),
  `joins` decimal(33,0),
  `parts` decimal(33,0),
  `quits` decimal(33,0),
  `kicks` decimal(33,0),
  `kicked` decimal(33,0),
  `nickchanges` decimal(33,0),
  `topics` decimal(33,0),
  `ex_kicks` varchar(255),
  `ex_kicked` varchar(255)
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_query_lines`
--

DROP TABLE IF EXISTS `view_query_lines`;
/*!50001 DROP VIEW IF EXISTS `view_query_lines`*/;
/*!50001 CREATE TABLE `view_query_lines` (
  `UID` int(10) unsigned,
  `l_00` decimal(33,0),
  `l_01` decimal(33,0),
  `l_02` decimal(33,0),
  `l_03` decimal(33,0),
  `l_04` decimal(33,0),
  `l_05` decimal(33,0),
  `l_06` decimal(33,0),
  `l_07` decimal(33,0),
  `l_08` decimal(33,0),
  `l_09` decimal(33,0),
  `l_10` decimal(33,0),
  `l_11` decimal(33,0),
  `l_12` decimal(33,0),
  `l_13` decimal(33,0),
  `l_14` decimal(33,0),
  `l_15` decimal(33,0),
  `l_16` decimal(33,0),
  `l_17` decimal(33,0),
  `l_18` decimal(33,0),
  `l_19` decimal(33,0),
  `l_20` decimal(33,0),
  `l_21` decimal(33,0),
  `l_22` decimal(33,0),
  `l_23` decimal(33,0),
  `l_night` decimal(33,0),
  `l_morning` decimal(33,0),
  `l_afternoon` decimal(33,0),
  `l_evening` decimal(33,0),
  `l_total` decimal(33,0),
  `l_mon_night` decimal(33,0),
  `l_mon_morning` decimal(33,0),
  `l_mon_afternoon` decimal(33,0),
  `l_mon_evening` decimal(33,0),
  `l_tue_night` decimal(33,0),
  `l_tue_morning` decimal(33,0),
  `l_tue_afternoon` decimal(33,0),
  `l_tue_evening` decimal(33,0),
  `l_wed_night` decimal(33,0),
  `l_wed_morning` decimal(33,0),
  `l_wed_afternoon` decimal(33,0),
  `l_wed_evening` decimal(33,0),
  `l_thu_night` decimal(33,0),
  `l_thu_morning` decimal(33,0),
  `l_thu_afternoon` decimal(33,0),
  `l_thu_evening` decimal(33,0),
  `l_fri_night` decimal(33,0),
  `l_fri_morning` decimal(33,0),
  `l_fri_afternoon` decimal(33,0),
  `l_fri_evening` decimal(33,0),
  `l_sat_night` decimal(33,0),
  `l_sat_morning` decimal(33,0),
  `l_sat_afternoon` decimal(33,0),
  `l_sat_evening` decimal(33,0),
  `l_sun_night` decimal(33,0),
  `l_sun_morning` decimal(33,0),
  `l_sun_afternoon` decimal(33,0),
  `l_sun_evening` decimal(33,0),
  `URLs` decimal(33,0),
  `words` decimal(33,0),
  `characters` decimal(33,0),
  `monologues` decimal(33,0),
  `topMonologue` int(10) unsigned,
  `activeDays` bigint(21),
  `slaps` decimal(33,0),
  `slapped` decimal(33,0),
  `exclamations` decimal(33,0),
  `questions` decimal(33,0),
  `actions` decimal(33,0),
  `uppercased` decimal(33,0),
  `quote` varchar(255),
  `ex_exclamations` varchar(255),
  `ex_questions` varchar(255),
  `ex_actions` varchar(255),
  `ex_uppercased` varchar(255),
  `lastTalked` datetime
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_query_smileys`
--

DROP TABLE IF EXISTS `view_query_smileys`;
/*!50001 DROP VIEW IF EXISTS `view_query_smileys`*/;
/*!50001 CREATE TABLE `view_query_smileys` (
  `UID` int(10) unsigned,
  `s_01` decimal(33,0),
  `s_02` decimal(33,0),
  `s_03` decimal(33,0),
  `s_04` decimal(33,0),
  `s_05` decimal(33,0),
  `s_06` decimal(33,0),
  `s_07` decimal(33,0),
  `s_08` decimal(33,0),
  `s_09` decimal(33,0),
  `s_10` decimal(33,0),
  `s_11` decimal(33,0),
  `s_12` decimal(33,0),
  `s_13` decimal(33,0),
  `s_14` decimal(33,0),
  `s_15` decimal(33,0),
  `s_16` decimal(33,0),
  `s_17` decimal(33,0),
  `s_18` decimal(33,0),
  `s_19` decimal(33,0)
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_quote`
--

DROP TABLE IF EXISTS `view_quote`;
/*!50001 DROP VIEW IF EXISTS `view_quote`*/;
/*!50001 CREATE TABLE `view_quote` (
  `RUID` int(10) unsigned,
  `quote` varchar(255)
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_quote_1`
--

DROP TABLE IF EXISTS `view_quote_1`;
/*!50001 DROP VIEW IF EXISTS `view_quote_1`*/;
/*!50001 CREATE TABLE `view_quote_1` (
  `RUID` int(10) unsigned,
  `quote` varchar(255),
  `lastTalked` datetime
) ENGINE=MyISAM */;

--
-- Temporary table structure for view `view_quote_2`
--

DROP TABLE IF EXISTS `view_quote_2`;
/*!50001 DROP VIEW IF EXISTS `view_quote_2`*/;
/*!50001 CREATE TABLE `view_quote_2` (
  `ruid` int(10) unsigned,
  `lastTalked` datetime
) ENGINE=MyISAM */;

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

--
-- Final view structure for view `view_activeDays`
--

/*!50001 DROP TABLE `view_activeDays`*/;
/*!50001 DROP VIEW IF EXISTS `view_activeDays`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_activeDays` AS select `user_status`.`RUID` AS `RUID`,count(distinct `user_activity`.`date`) AS `activeDays` from (`user_activity` join `user_status` on((`user_activity`.`UID` = `user_status`.`UID`))) group by `user_status`.`RUID` */;

--
-- Final view structure for view `view_events`
--

/*!50001 DROP TABLE `view_events`*/;
/*!50001 DROP VIEW IF EXISTS `view_events`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_events` AS select `user_status`.`RUID` AS `RUID`,sum(`user_events`.`m_op`) AS `m_op`,sum(`user_events`.`m_opped`) AS `m_opped`,sum(`user_events`.`m_voice`) AS `m_voice`,sum(`user_events`.`m_voiced`) AS `m_voiced`,sum(`user_events`.`m_deOp`) AS `m_deOp`,sum(`user_events`.`m_deOpped`) AS `m_deOpped`,sum(`user_events`.`m_deVoice`) AS `m_deVoice`,sum(`user_events`.`m_deVoiced`) AS `m_deVoiced`,sum(`user_events`.`joins`) AS `joins`,sum(`user_events`.`parts`) AS `parts`,sum(`user_events`.`quits`) AS `quits`,sum(`user_events`.`kicks`) AS `kicks`,sum(`user_events`.`kicked`) AS `kicked`,sum(`user_events`.`nickchanges`) AS `nickchanges`,sum(`user_events`.`topics`) AS `topics` from (`user_events` join `user_status` on((`user_events`.`UID` = `user_status`.`UID`))) group by `user_status`.`RUID` */;

--
-- Final view structure for view `view_ex_actions`
--

/*!50001 DROP TABLE `view_ex_actions`*/;
/*!50001 DROP VIEW IF EXISTS `view_ex_actions`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_ex_actions` AS select `view_ex_actions_1`.`RUID` AS `RUID`,`view_ex_actions_1`.`ex_actions` AS `ex_actions` from (`view_ex_actions_1` join `view_ex_actions_2` on((`view_ex_actions_1`.`RUID` = `view_ex_actions_2`.`ruid`))) where (`view_ex_actions_1`.`lastSeen` = `view_ex_actions_2`.`lastSeen`) */;

--
-- Final view structure for view `view_ex_actions_1`
--

/*!50001 DROP TABLE `view_ex_actions_1`*/;
/*!50001 DROP VIEW IF EXISTS `view_ex_actions_1`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_ex_actions_1` AS select `user_status`.`RUID` AS `RUID`,`user_lines`.`ex_actions` AS `ex_actions`,`user_details`.`lastSeen` AS `lastSeen` from ((`user_lines` join `user_status` on((`user_lines`.`UID` = `user_status`.`UID`))) join `user_details` on((`user_lines`.`UID` = `user_details`.`UID`))) where (`user_lines`.`ex_actions` <> _latin1'') */;

--
-- Final view structure for view `view_ex_actions_2`
--

/*!50001 DROP TABLE `view_ex_actions_2`*/;
/*!50001 DROP VIEW IF EXISTS `view_ex_actions_2`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_ex_actions_2` AS select `view_ex_actions_1`.`RUID` AS `ruid`,max(`view_ex_actions_1`.`lastSeen`) AS `lastSeen` from `view_ex_actions_1` group by `view_ex_actions_1`.`RUID` */;

--
-- Final view structure for view `view_ex_exclamations`
--

/*!50001 DROP TABLE `view_ex_exclamations`*/;
/*!50001 DROP VIEW IF EXISTS `view_ex_exclamations`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_ex_exclamations` AS select `view_ex_exclamations_1`.`RUID` AS `RUID`,`view_ex_exclamations_1`.`ex_exclamations` AS `ex_exclamations` from (`view_ex_exclamations_1` join `view_ex_exclamations_2` on((`view_ex_exclamations_1`.`RUID` = `view_ex_exclamations_2`.`ruid`))) where (`view_ex_exclamations_1`.`lastTalked` = `view_ex_exclamations_2`.`lastTalked`) */;

--
-- Final view structure for view `view_ex_exclamations_1`
--

/*!50001 DROP TABLE `view_ex_exclamations_1`*/;
/*!50001 DROP VIEW IF EXISTS `view_ex_exclamations_1`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_ex_exclamations_1` AS select `user_status`.`RUID` AS `RUID`,`user_lines`.`ex_exclamations` AS `ex_exclamations`,`user_lines`.`lastTalked` AS `lastTalked` from (`user_lines` join `user_status` on((`user_lines`.`UID` = `user_status`.`UID`))) where (`user_lines`.`ex_exclamations` <> _latin1'') */;

--
-- Final view structure for view `view_ex_exclamations_2`
--

/*!50001 DROP TABLE `view_ex_exclamations_2`*/;
/*!50001 DROP VIEW IF EXISTS `view_ex_exclamations_2`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_ex_exclamations_2` AS select `view_ex_exclamations_1`.`RUID` AS `ruid`,max(`view_ex_exclamations_1`.`lastTalked`) AS `lastTalked` from `view_ex_exclamations_1` group by `view_ex_exclamations_1`.`RUID` */;

--
-- Final view structure for view `view_ex_kicked`
--

/*!50001 DROP TABLE `view_ex_kicked`*/;
/*!50001 DROP VIEW IF EXISTS `view_ex_kicked`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_ex_kicked` AS select `view_ex_kicked_1`.`RUID` AS `RUID`,`view_ex_kicked_1`.`ex_kicked` AS `ex_kicked` from `view_ex_kicked_1` group by `view_ex_kicked_1`.`RUID` */;

--
-- Final view structure for view `view_ex_kicked_1`
--

/*!50001 DROP TABLE `view_ex_kicked_1`*/;
/*!50001 DROP VIEW IF EXISTS `view_ex_kicked_1`*/;
/*!50001 CREATE ALGORITHM=TEMPTABLE */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_ex_kicked_1` AS select `user_status`.`RUID` AS `RUID`,`user_events`.`ex_kicked` AS `ex_kicked` from (`user_events` join `user_status` on((`user_events`.`UID` = `user_status`.`UID`))) where (`user_events`.`ex_kicked` <> _latin1'') order by rand() */;

--
-- Final view structure for view `view_ex_kicks`
--

/*!50001 DROP TABLE `view_ex_kicks`*/;
/*!50001 DROP VIEW IF EXISTS `view_ex_kicks`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_ex_kicks` AS select `view_ex_kicks_1`.`RUID` AS `RUID`,`view_ex_kicks_1`.`ex_kicks` AS `ex_kicks` from `view_ex_kicks_1` group by `view_ex_kicks_1`.`RUID` */;

--
-- Final view structure for view `view_ex_kicks_1`
--

/*!50001 DROP TABLE `view_ex_kicks_1`*/;
/*!50001 DROP VIEW IF EXISTS `view_ex_kicks_1`*/;
/*!50001 CREATE ALGORITHM=TEMPTABLE */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_ex_kicks_1` AS select `user_status`.`RUID` AS `RUID`,`user_events`.`ex_kicks` AS `ex_kicks` from (`user_events` join `user_status` on((`user_events`.`UID` = `user_status`.`UID`))) where (`user_events`.`ex_kicks` <> _latin1'') order by rand() */;

--
-- Final view structure for view `view_ex_questions`
--

/*!50001 DROP TABLE `view_ex_questions`*/;
/*!50001 DROP VIEW IF EXISTS `view_ex_questions`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_ex_questions` AS select `view_ex_questions_1`.`RUID` AS `RUID`,`view_ex_questions_1`.`ex_questions` AS `ex_questions` from (`view_ex_questions_1` join `view_ex_questions_2` on((`view_ex_questions_1`.`RUID` = `view_ex_questions_2`.`ruid`))) where (`view_ex_questions_1`.`lastTalked` = `view_ex_questions_2`.`lastTalked`) */;

--
-- Final view structure for view `view_ex_questions_1`
--

/*!50001 DROP TABLE `view_ex_questions_1`*/;
/*!50001 DROP VIEW IF EXISTS `view_ex_questions_1`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_ex_questions_1` AS select `user_status`.`RUID` AS `RUID`,`user_lines`.`ex_questions` AS `ex_questions`,`user_lines`.`lastTalked` AS `lastTalked` from (`user_lines` join `user_status` on((`user_lines`.`UID` = `user_status`.`UID`))) where (`user_lines`.`ex_questions` <> _latin1'') */;

--
-- Final view structure for view `view_ex_questions_2`
--

/*!50001 DROP TABLE `view_ex_questions_2`*/;
/*!50001 DROP VIEW IF EXISTS `view_ex_questions_2`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_ex_questions_2` AS select `view_ex_questions_1`.`RUID` AS `ruid`,max(`view_ex_questions_1`.`lastTalked`) AS `lastTalked` from `view_ex_questions_1` group by `view_ex_questions_1`.`RUID` */;

--
-- Final view structure for view `view_ex_uppercased`
--

/*!50001 DROP TABLE `view_ex_uppercased`*/;
/*!50001 DROP VIEW IF EXISTS `view_ex_uppercased`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_ex_uppercased` AS select `view_ex_uppercased_1`.`RUID` AS `RUID`,`view_ex_uppercased_1`.`ex_uppercased` AS `ex_uppercased` from (`view_ex_uppercased_1` join `view_ex_uppercased_2` on((`view_ex_uppercased_1`.`RUID` = `view_ex_uppercased_2`.`ruid`))) where (`view_ex_uppercased_1`.`lastTalked` = `view_ex_uppercased_2`.`lastTalked`) */;

--
-- Final view structure for view `view_ex_uppercased_1`
--

/*!50001 DROP TABLE `view_ex_uppercased_1`*/;
/*!50001 DROP VIEW IF EXISTS `view_ex_uppercased_1`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_ex_uppercased_1` AS select `user_status`.`RUID` AS `RUID`,`user_lines`.`ex_uppercased` AS `ex_uppercased`,`user_lines`.`lastTalked` AS `lastTalked` from (`user_lines` join `user_status` on((`user_lines`.`UID` = `user_status`.`UID`))) where (`user_lines`.`ex_uppercased` <> _latin1'') */;

--
-- Final view structure for view `view_ex_uppercased_2`
--

/*!50001 DROP TABLE `view_ex_uppercased_2`*/;
/*!50001 DROP VIEW IF EXISTS `view_ex_uppercased_2`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_ex_uppercased_2` AS select `view_ex_uppercased_1`.`RUID` AS `ruid`,max(`view_ex_uppercased_1`.`lastTalked`) AS `lastTalked` from `view_ex_uppercased_1` group by `view_ex_uppercased_1`.`RUID` */;

--
-- Final view structure for view `view_lines`
--

/*!50001 DROP TABLE `view_lines`*/;
/*!50001 DROP VIEW IF EXISTS `view_lines`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_lines` AS select `user_status`.`RUID` AS `RUID`,sum(`user_lines`.`l_00`) AS `l_00`,sum(`user_lines`.`l_01`) AS `l_01`,sum(`user_lines`.`l_02`) AS `l_02`,sum(`user_lines`.`l_03`) AS `l_03`,sum(`user_lines`.`l_04`) AS `l_04`,sum(`user_lines`.`l_05`) AS `l_05`,sum(`user_lines`.`l_06`) AS `l_06`,sum(`user_lines`.`l_07`) AS `l_07`,sum(`user_lines`.`l_08`) AS `l_08`,sum(`user_lines`.`l_09`) AS `l_09`,sum(`user_lines`.`l_10`) AS `l_10`,sum(`user_lines`.`l_11`) AS `l_11`,sum(`user_lines`.`l_12`) AS `l_12`,sum(`user_lines`.`l_13`) AS `l_13`,sum(`user_lines`.`l_14`) AS `l_14`,sum(`user_lines`.`l_15`) AS `l_15`,sum(`user_lines`.`l_16`) AS `l_16`,sum(`user_lines`.`l_17`) AS `l_17`,sum(`user_lines`.`l_18`) AS `l_18`,sum(`user_lines`.`l_19`) AS `l_19`,sum(`user_lines`.`l_20`) AS `l_20`,sum(`user_lines`.`l_21`) AS `l_21`,sum(`user_lines`.`l_22`) AS `l_22`,sum(`user_lines`.`l_23`) AS `l_23`,sum(`user_lines`.`l_night`) AS `l_night`,sum(`user_lines`.`l_morning`) AS `l_morning`,sum(`user_lines`.`l_afternoon`) AS `l_afternoon`,sum(`user_lines`.`l_evening`) AS `l_evening`,sum(`user_lines`.`l_total`) AS `l_total`,sum(`user_lines`.`l_mon_night`) AS `l_mon_night`,sum(`user_lines`.`l_mon_morning`) AS `l_mon_morning`,sum(`user_lines`.`l_mon_afternoon`) AS `l_mon_afternoon`,sum(`user_lines`.`l_mon_evening`) AS `l_mon_evening`,sum(`user_lines`.`l_tue_night`) AS `l_tue_night`,sum(`user_lines`.`l_tue_morning`) AS `l_tue_morning`,sum(`user_lines`.`l_tue_afternoon`) AS `l_tue_afternoon`,sum(`user_lines`.`l_tue_evening`) AS `l_tue_evening`,sum(`user_lines`.`l_wed_night`) AS `l_wed_night`,sum(`user_lines`.`l_wed_morning`) AS `l_wed_morning`,sum(`user_lines`.`l_wed_afternoon`) AS `l_wed_afternoon`,sum(`user_lines`.`l_wed_evening`) AS `l_wed_evening`,sum(`user_lines`.`l_thu_night`) AS `l_thu_night`,sum(`user_lines`.`l_thu_morning`) AS `l_thu_morning`,sum(`user_lines`.`l_thu_afternoon`) AS `l_thu_afternoon`,sum(`user_lines`.`l_thu_evening`) AS `l_thu_evening`,sum(`user_lines`.`l_fri_night`) AS `l_fri_night`,sum(`user_lines`.`l_fri_morning`) AS `l_fri_morning`,sum(`user_lines`.`l_fri_afternoon`) AS `l_fri_afternoon`,sum(`user_lines`.`l_fri_evening`) AS `l_fri_evening`,sum(`user_lines`.`l_sat_night`) AS `l_sat_night`,sum(`user_lines`.`l_sat_morning`) AS `l_sat_morning`,sum(`user_lines`.`l_sat_afternoon`) AS `l_sat_afternoon`,sum(`user_lines`.`l_sat_evening`) AS `l_sat_evening`,sum(`user_lines`.`l_sun_night`) AS `l_sun_night`,sum(`user_lines`.`l_sun_morning`) AS `l_sun_morning`,sum(`user_lines`.`l_sun_afternoon`) AS `l_sun_afternoon`,sum(`user_lines`.`l_sun_evening`) AS `l_sun_evening`,sum(`user_lines`.`URLs`) AS `URLs`,sum(`user_lines`.`words`) AS `words`,sum(`user_lines`.`characters`) AS `characters`,sum(`user_lines`.`monologues`) AS `monologues`,max(`user_lines`.`topMonologue`) AS `topMonologue`,sum(`user_lines`.`slaps`) AS `slaps`,sum(`user_lines`.`slapped`) AS `slapped`,sum(`user_lines`.`exclamations`) AS `exclamations`,sum(`user_lines`.`questions`) AS `questions`,sum(`user_lines`.`actions`) AS `actions`,sum(`user_lines`.`uppercased`) AS `uppercased`,max(`user_lines`.`lastTalked`) AS `lastTalked` from (`user_lines` join `user_status` on((`user_lines`.`UID` = `user_status`.`UID`))) group by `user_status`.`RUID` */;

--
-- Final view structure for view `view_query_events`
--

/*!50001 DROP TABLE `view_query_events`*/;
/*!50001 DROP VIEW IF EXISTS `view_query_events`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_query_events` AS select `view_events`.`RUID` AS `UID`,`view_events`.`m_op` AS `m_op`,`view_events`.`m_opped` AS `m_opped`,`view_events`.`m_voice` AS `m_voice`,`view_events`.`m_voiced` AS `m_voiced`,`view_events`.`m_deOp` AS `m_deOp`,`view_events`.`m_deOpped` AS `m_deOpped`,`view_events`.`m_deVoice` AS `m_deVoice`,`view_events`.`m_deVoiced` AS `m_deVoiced`,`view_events`.`joins` AS `joins`,`view_events`.`parts` AS `parts`,`view_events`.`quits` AS `quits`,`view_events`.`kicks` AS `kicks`,`view_events`.`kicked` AS `kicked`,`view_events`.`nickchanges` AS `nickchanges`,`view_events`.`topics` AS `topics`,`mview_ex_kicks`.`ex_kicks` AS `ex_kicks`,`mview_ex_kicked`.`ex_kicked` AS `ex_kicked` from ((`view_events` left join `mview_ex_kicks` on((`view_events`.`RUID` = `mview_ex_kicks`.`RUID`))) left join `mview_ex_kicked` on((`view_events`.`RUID` = `mview_ex_kicked`.`RUID`))) */;

--
-- Final view structure for view `view_query_lines`
--

/*!50001 DROP TABLE `view_query_lines`*/;
/*!50001 DROP VIEW IF EXISTS `view_query_lines`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_query_lines` AS select `view_lines`.`RUID` AS `UID`,`view_lines`.`l_00` AS `l_00`,`view_lines`.`l_01` AS `l_01`,`view_lines`.`l_02` AS `l_02`,`view_lines`.`l_03` AS `l_03`,`view_lines`.`l_04` AS `l_04`,`view_lines`.`l_05` AS `l_05`,`view_lines`.`l_06` AS `l_06`,`view_lines`.`l_07` AS `l_07`,`view_lines`.`l_08` AS `l_08`,`view_lines`.`l_09` AS `l_09`,`view_lines`.`l_10` AS `l_10`,`view_lines`.`l_11` AS `l_11`,`view_lines`.`l_12` AS `l_12`,`view_lines`.`l_13` AS `l_13`,`view_lines`.`l_14` AS `l_14`,`view_lines`.`l_15` AS `l_15`,`view_lines`.`l_16` AS `l_16`,`view_lines`.`l_17` AS `l_17`,`view_lines`.`l_18` AS `l_18`,`view_lines`.`l_19` AS `l_19`,`view_lines`.`l_20` AS `l_20`,`view_lines`.`l_21` AS `l_21`,`view_lines`.`l_22` AS `l_22`,`view_lines`.`l_23` AS `l_23`,`view_lines`.`l_night` AS `l_night`,`view_lines`.`l_morning` AS `l_morning`,`view_lines`.`l_afternoon` AS `l_afternoon`,`view_lines`.`l_evening` AS `l_evening`,`view_lines`.`l_total` AS `l_total`,`view_lines`.`l_mon_night` AS `l_mon_night`,`view_lines`.`l_mon_morning` AS `l_mon_morning`,`view_lines`.`l_mon_afternoon` AS `l_mon_afternoon`,`view_lines`.`l_mon_evening` AS `l_mon_evening`,`view_lines`.`l_tue_night` AS `l_tue_night`,`view_lines`.`l_tue_morning` AS `l_tue_morning`,`view_lines`.`l_tue_afternoon` AS `l_tue_afternoon`,`view_lines`.`l_tue_evening` AS `l_tue_evening`,`view_lines`.`l_wed_night` AS `l_wed_night`,`view_lines`.`l_wed_morning` AS `l_wed_morning`,`view_lines`.`l_wed_afternoon` AS `l_wed_afternoon`,`view_lines`.`l_wed_evening` AS `l_wed_evening`,`view_lines`.`l_thu_night` AS `l_thu_night`,`view_lines`.`l_thu_morning` AS `l_thu_morning`,`view_lines`.`l_thu_afternoon` AS `l_thu_afternoon`,`view_lines`.`l_thu_evening` AS `l_thu_evening`,`view_lines`.`l_fri_night` AS `l_fri_night`,`view_lines`.`l_fri_morning` AS `l_fri_morning`,`view_lines`.`l_fri_afternoon` AS `l_fri_afternoon`,`view_lines`.`l_fri_evening` AS `l_fri_evening`,`view_lines`.`l_sat_night` AS `l_sat_night`,`view_lines`.`l_sat_morning` AS `l_sat_morning`,`view_lines`.`l_sat_afternoon` AS `l_sat_afternoon`,`view_lines`.`l_sat_evening` AS `l_sat_evening`,`view_lines`.`l_sun_night` AS `l_sun_night`,`view_lines`.`l_sun_morning` AS `l_sun_morning`,`view_lines`.`l_sun_afternoon` AS `l_sun_afternoon`,`view_lines`.`l_sun_evening` AS `l_sun_evening`,`view_lines`.`URLs` AS `URLs`,`view_lines`.`words` AS `words`,`view_lines`.`characters` AS `characters`,`view_lines`.`monologues` AS `monologues`,`view_lines`.`topMonologue` AS `topMonologue`,`view_activeDays`.`activeDays` AS `activeDays`,`view_lines`.`slaps` AS `slaps`,`view_lines`.`slapped` AS `slapped`,`view_lines`.`exclamations` AS `exclamations`,`view_lines`.`questions` AS `questions`,`view_lines`.`actions` AS `actions`,`view_lines`.`uppercased` AS `uppercased`,`mview_quote`.`quote` AS `quote`,`mview_ex_exclamations`.`ex_exclamations` AS `ex_exclamations`,`mview_ex_questions`.`ex_questions` AS `ex_questions`,`mview_ex_actions`.`ex_actions` AS `ex_actions`,`mview_ex_uppercased`.`ex_uppercased` AS `ex_uppercased`,`view_lines`.`lastTalked` AS `lastTalked` from ((((((`view_lines` left join `view_activeDays` on((`view_lines`.`RUID` = `view_activeDays`.`RUID`))) left join `mview_quote` on((`view_lines`.`RUID` = `mview_quote`.`RUID`))) left join `mview_ex_exclamations` on((`view_lines`.`RUID` = `mview_ex_exclamations`.`RUID`))) left join `mview_ex_questions` on((`view_lines`.`RUID` = `mview_ex_questions`.`RUID`))) left join `mview_ex_actions` on((`view_lines`.`RUID` = `mview_ex_actions`.`RUID`))) left join `mview_ex_uppercased` on((`view_lines`.`RUID` = `mview_ex_uppercased`.`RUID`))) */;

--
-- Final view structure for view `view_query_smileys`
--

/*!50001 DROP TABLE `view_query_smileys`*/;
/*!50001 DROP VIEW IF EXISTS `view_query_smileys`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_query_smileys` AS select `user_status`.`RUID` AS `UID`,sum(`user_smileys`.`s_01`) AS `s_01`,sum(`user_smileys`.`s_02`) AS `s_02`,sum(`user_smileys`.`s_03`) AS `s_03`,sum(`user_smileys`.`s_04`) AS `s_04`,sum(`user_smileys`.`s_05`) AS `s_05`,sum(`user_smileys`.`s_06`) AS `s_06`,sum(`user_smileys`.`s_07`) AS `s_07`,sum(`user_smileys`.`s_08`) AS `s_08`,sum(`user_smileys`.`s_09`) AS `s_09`,sum(`user_smileys`.`s_10`) AS `s_10`,sum(`user_smileys`.`s_11`) AS `s_11`,sum(`user_smileys`.`s_12`) AS `s_12`,sum(`user_smileys`.`s_13`) AS `s_13`,sum(`user_smileys`.`s_14`) AS `s_14`,sum(`user_smileys`.`s_15`) AS `s_15`,sum(`user_smileys`.`s_16`) AS `s_16`,sum(`user_smileys`.`s_17`) AS `s_17`,sum(`user_smileys`.`s_18`) AS `s_18`,sum(`user_smileys`.`s_19`) AS `s_19` from (`user_smileys` join `user_status` on((`user_smileys`.`UID` = `user_status`.`UID`))) group by `user_status`.`RUID` */;

--
-- Final view structure for view `view_quote`
--

/*!50001 DROP TABLE `view_quote`*/;
/*!50001 DROP VIEW IF EXISTS `view_quote`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_quote` AS select `view_quote_1`.`RUID` AS `RUID`,`view_quote_1`.`quote` AS `quote` from (`view_quote_1` join `view_quote_2` on((`view_quote_1`.`RUID` = `view_quote_2`.`ruid`))) where (`view_quote_1`.`lastTalked` = `view_quote_2`.`lastTalked`) */;

--
-- Final view structure for view `view_quote_1`
--

/*!50001 DROP TABLE `view_quote_1`*/;
/*!50001 DROP VIEW IF EXISTS `view_quote_1`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_quote_1` AS select `user_status`.`RUID` AS `RUID`,`user_lines`.`quote` AS `quote`,`user_lines`.`lastTalked` AS `lastTalked` from (`user_lines` join `user_status` on((`user_lines`.`UID` = `user_status`.`UID`))) where (`user_lines`.`quote` <> _latin1'') */;

--
-- Final view structure for view `view_quote_2`
--

/*!50001 DROP TABLE `view_quote_2`*/;
/*!50001 DROP VIEW IF EXISTS `view_quote_2`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `view_quote_2` AS select `view_quote_1`.`RUID` AS `ruid`,max(`view_quote_1`.`lastTalked`) AS `lastTalked` from `view_quote_1` group by `view_quote_1`.`RUID` */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2010-06-27  2:54:42
