-- MySQL dump 10.13  Distrib 5.1.48, for unknown-openbsd4.8 (i386)
--
-- Host: localhost    Database: sss
-- ------------------------------------------------------
-- Server version	5.1.48-log

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `channel` (
  `date` date NOT NULL DEFAULT '0000-00-00',
  `l_00` int(10) unsigned NOT NULL DEFAULT '0',
  `l_01` int(10) unsigned NOT NULL DEFAULT '0',
  `l_02` int(10) unsigned NOT NULL DEFAULT '0',
  `l_03` int(10) unsigned NOT NULL DEFAULT '0',
  `l_04` int(10) unsigned NOT NULL DEFAULT '0',
  `l_05` int(10) unsigned NOT NULL DEFAULT '0',
  `l_06` int(10) unsigned NOT NULL DEFAULT '0',
  `l_07` int(10) unsigned NOT NULL DEFAULT '0',
  `l_08` int(10) unsigned NOT NULL DEFAULT '0',
  `l_09` int(10) unsigned NOT NULL DEFAULT '0',
  `l_10` int(10) unsigned NOT NULL DEFAULT '0',
  `l_11` int(10) unsigned NOT NULL DEFAULT '0',
  `l_12` int(10) unsigned NOT NULL DEFAULT '0',
  `l_13` int(10) unsigned NOT NULL DEFAULT '0',
  `l_14` int(10) unsigned NOT NULL DEFAULT '0',
  `l_15` int(10) unsigned NOT NULL DEFAULT '0',
  `l_16` int(10) unsigned NOT NULL DEFAULT '0',
  `l_17` int(10) unsigned NOT NULL DEFAULT '0',
  `l_18` int(10) unsigned NOT NULL DEFAULT '0',
  `l_19` int(10) unsigned NOT NULL DEFAULT '0',
  `l_20` int(10) unsigned NOT NULL DEFAULT '0',
  `l_21` int(10) unsigned NOT NULL DEFAULT '0',
  `l_22` int(10) unsigned NOT NULL DEFAULT '0',
  `l_23` int(10) unsigned NOT NULL DEFAULT '0',
  `l_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_total` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`date`),
  KEY `l_total` (`l_total`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mv_activedays`
--

DROP TABLE IF EXISTS `mv_activedays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mv_activedays` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `activedays` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ruid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mv_events`
--

DROP TABLE IF EXISTS `mv_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mv_events` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `m_op` int(10) unsigned NOT NULL DEFAULT '0',
  `m_opped` int(10) unsigned NOT NULL DEFAULT '0',
  `m_voice` int(10) unsigned NOT NULL DEFAULT '0',
  `m_voiced` int(10) unsigned NOT NULL DEFAULT '0',
  `m_deop` int(10) unsigned NOT NULL DEFAULT '0',
  `m_deopped` int(10) unsigned NOT NULL DEFAULT '0',
  `m_devoice` int(10) unsigned NOT NULL DEFAULT '0',
  `m_devoiced` int(10) unsigned NOT NULL DEFAULT '0',
  `joins` int(10) unsigned NOT NULL DEFAULT '0',
  `parts` int(10) unsigned NOT NULL DEFAULT '0',
  `quits` int(10) unsigned NOT NULL DEFAULT '0',
  `kicks` int(10) unsigned NOT NULL DEFAULT '0',
  `kicked` int(10) unsigned NOT NULL DEFAULT '0',
  `nickchanges` int(10) unsigned NOT NULL DEFAULT '0',
  `topics` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ruid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mv_ex_actions`
--

DROP TABLE IF EXISTS `mv_ex_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mv_ex_actions` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `ex_actions` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ruid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mv_ex_exclamations`
--

DROP TABLE IF EXISTS `mv_ex_exclamations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mv_ex_exclamations` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `ex_exclamations` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ruid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mv_ex_kicked`
--

DROP TABLE IF EXISTS `mv_ex_kicked`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mv_ex_kicked` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `ex_kicked` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ruid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mv_ex_kicks`
--

DROP TABLE IF EXISTS `mv_ex_kicks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mv_ex_kicks` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `ex_kicks` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ruid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mv_ex_questions`
--

DROP TABLE IF EXISTS `mv_ex_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mv_ex_questions` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `ex_questions` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ruid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mv_ex_uppercased`
--

DROP TABLE IF EXISTS `mv_ex_uppercased`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mv_ex_uppercased` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `ex_uppercased` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ruid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mv_lines`
--

DROP TABLE IF EXISTS `mv_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mv_lines` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `l_00` int(10) unsigned NOT NULL DEFAULT '0',
  `l_01` int(10) unsigned NOT NULL DEFAULT '0',
  `l_02` int(10) unsigned NOT NULL DEFAULT '0',
  `l_03` int(10) unsigned NOT NULL DEFAULT '0',
  `l_04` int(10) unsigned NOT NULL DEFAULT '0',
  `l_05` int(10) unsigned NOT NULL DEFAULT '0',
  `l_06` int(10) unsigned NOT NULL DEFAULT '0',
  `l_07` int(10) unsigned NOT NULL DEFAULT '0',
  `l_08` int(10) unsigned NOT NULL DEFAULT '0',
  `l_09` int(10) unsigned NOT NULL DEFAULT '0',
  `l_10` int(10) unsigned NOT NULL DEFAULT '0',
  `l_11` int(10) unsigned NOT NULL DEFAULT '0',
  `l_12` int(10) unsigned NOT NULL DEFAULT '0',
  `l_13` int(10) unsigned NOT NULL DEFAULT '0',
  `l_14` int(10) unsigned NOT NULL DEFAULT '0',
  `l_15` int(10) unsigned NOT NULL DEFAULT '0',
  `l_16` int(10) unsigned NOT NULL DEFAULT '0',
  `l_17` int(10) unsigned NOT NULL DEFAULT '0',
  `l_18` int(10) unsigned NOT NULL DEFAULT '0',
  `l_19` int(10) unsigned NOT NULL DEFAULT '0',
  `l_20` int(10) unsigned NOT NULL DEFAULT '0',
  `l_21` int(10) unsigned NOT NULL DEFAULT '0',
  `l_22` int(10) unsigned NOT NULL DEFAULT '0',
  `l_23` int(10) unsigned NOT NULL DEFAULT '0',
  `l_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_total` int(10) unsigned NOT NULL DEFAULT '0',
  `l_mon_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_mon_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_mon_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_mon_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_tue_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_tue_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_tue_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_tue_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_wed_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_wed_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_wed_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_wed_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_thu_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_thu_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_thu_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_thu_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_fri_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_fri_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_fri_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_fri_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sat_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sat_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sat_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sat_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sun_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sun_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sun_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sun_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `urls` int(10) unsigned NOT NULL DEFAULT '0',
  `words` int(10) unsigned NOT NULL DEFAULT '0',
  `characters` int(10) unsigned NOT NULL DEFAULT '0',
  `monologues` int(10) unsigned NOT NULL DEFAULT '0',
  `topmonologue` int(10) unsigned NOT NULL DEFAULT '0',
  `slaps` int(10) unsigned NOT NULL DEFAULT '0',
  `slapped` int(10) unsigned NOT NULL DEFAULT '0',
  `exclamations` int(10) unsigned NOT NULL DEFAULT '0',
  `questions` int(10) unsigned NOT NULL DEFAULT '0',
  `actions` int(10) unsigned NOT NULL DEFAULT '0',
  `uppercased` int(10) unsigned NOT NULL DEFAULT '0',
  `lasttalked` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`ruid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mv_quote`
--

DROP TABLE IF EXISTS `mv_quote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mv_quote` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `quote` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ruid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `parse_history`
--

DROP TABLE IF EXISTS `parse_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parse_history` (
  `date` date NOT NULL DEFAULT '0000-00-00',
  `lines_parsed` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `q_activity_by_day`
--

DROP TABLE IF EXISTS `q_activity_by_day`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `q_activity_by_day` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `date` date NOT NULL DEFAULT '0000-00-00',
  `l_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_total` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ruid`,`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `q_activity_by_month`
--

DROP TABLE IF EXISTS `q_activity_by_month`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `q_activity_by_month` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `date` varchar(7) NOT NULL DEFAULT '0000-00',
  `l_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_total` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ruid`,`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `q_activity_by_year`
--

DROP TABLE IF EXISTS `q_activity_by_year`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `q_activity_by_year` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `date` year(4) NOT NULL DEFAULT '0000',
  `l_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_total` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ruid`,`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `q_events`
--

DROP TABLE IF EXISTS `q_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `q_events` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `m_op` int(10) unsigned NOT NULL DEFAULT '0',
  `m_opped` int(10) unsigned NOT NULL DEFAULT '0',
  `m_voice` int(10) unsigned NOT NULL DEFAULT '0',
  `m_voiced` int(10) unsigned NOT NULL DEFAULT '0',
  `m_deop` int(10) unsigned NOT NULL DEFAULT '0',
  `m_deopped` int(10) unsigned NOT NULL DEFAULT '0',
  `m_devoice` int(10) unsigned NOT NULL DEFAULT '0',
  `m_devoiced` int(10) unsigned NOT NULL DEFAULT '0',
  `joins` int(10) unsigned NOT NULL DEFAULT '0',
  `parts` int(10) unsigned NOT NULL DEFAULT '0',
  `quits` int(10) unsigned NOT NULL DEFAULT '0',
  `kicks` int(10) unsigned NOT NULL DEFAULT '0',
  `kicked` int(10) unsigned NOT NULL DEFAULT '0',
  `nickchanges` int(10) unsigned NOT NULL DEFAULT '0',
  `topics` int(10) unsigned NOT NULL DEFAULT '0',
  `ex_kicks` varchar(255) NOT NULL DEFAULT '',
  `ex_kicked` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ruid`),
  KEY `m_op` (`m_op`),
  KEY `m_opped` (`m_opped`),
  KEY `m_voice` (`m_voice`),
  KEY `m_voiced` (`m_voiced`),
  KEY `m_deop` (`m_deop`),
  KEY `m_deopped` (`m_deopped`),
  KEY `m_devoice` (`m_devoice`),
  KEY `m_devoiced` (`m_devoiced`),
  KEY `joins` (`joins`),
  KEY `parts` (`parts`),
  KEY `quits` (`quits`),
  KEY `kicks` (`kicks`),
  KEY `kicked` (`kicked`),
  KEY `nickchanges` (`nickchanges`),
  KEY `topics` (`topics`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `q_lines`
--

DROP TABLE IF EXISTS `q_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `q_lines` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `l_00` int(10) unsigned NOT NULL DEFAULT '0',
  `l_01` int(10) unsigned NOT NULL DEFAULT '0',
  `l_02` int(10) unsigned NOT NULL DEFAULT '0',
  `l_03` int(10) unsigned NOT NULL DEFAULT '0',
  `l_04` int(10) unsigned NOT NULL DEFAULT '0',
  `l_05` int(10) unsigned NOT NULL DEFAULT '0',
  `l_06` int(10) unsigned NOT NULL DEFAULT '0',
  `l_07` int(10) unsigned NOT NULL DEFAULT '0',
  `l_08` int(10) unsigned NOT NULL DEFAULT '0',
  `l_09` int(10) unsigned NOT NULL DEFAULT '0',
  `l_10` int(10) unsigned NOT NULL DEFAULT '0',
  `l_11` int(10) unsigned NOT NULL DEFAULT '0',
  `l_12` int(10) unsigned NOT NULL DEFAULT '0',
  `l_13` int(10) unsigned NOT NULL DEFAULT '0',
  `l_14` int(10) unsigned NOT NULL DEFAULT '0',
  `l_15` int(10) unsigned NOT NULL DEFAULT '0',
  `l_16` int(10) unsigned NOT NULL DEFAULT '0',
  `l_17` int(10) unsigned NOT NULL DEFAULT '0',
  `l_18` int(10) unsigned NOT NULL DEFAULT '0',
  `l_19` int(10) unsigned NOT NULL DEFAULT '0',
  `l_20` int(10) unsigned NOT NULL DEFAULT '0',
  `l_21` int(10) unsigned NOT NULL DEFAULT '0',
  `l_22` int(10) unsigned NOT NULL DEFAULT '0',
  `l_23` int(10) unsigned NOT NULL DEFAULT '0',
  `l_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_total` int(10) unsigned NOT NULL DEFAULT '0',
  `l_mon_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_mon_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_mon_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_mon_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_tue_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_tue_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_tue_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_tue_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_wed_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_wed_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_wed_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_wed_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_thu_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_thu_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_thu_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_thu_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_fri_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_fri_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_fri_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_fri_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sat_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sat_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sat_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sat_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sun_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sun_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sun_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sun_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `urls` int(10) unsigned NOT NULL DEFAULT '0',
  `words` int(10) unsigned NOT NULL DEFAULT '0',
  `characters` int(10) unsigned NOT NULL DEFAULT '0',
  `monologues` int(10) unsigned NOT NULL DEFAULT '0',
  `topmonologue` int(10) unsigned NOT NULL DEFAULT '0',
  `activedays` int(10) unsigned NOT NULL DEFAULT '0',
  `slaps` int(10) unsigned NOT NULL DEFAULT '0',
  `slapped` int(10) unsigned NOT NULL DEFAULT '0',
  `exclamations` int(10) unsigned NOT NULL DEFAULT '0',
  `questions` int(10) unsigned NOT NULL DEFAULT '0',
  `actions` int(10) unsigned NOT NULL DEFAULT '0',
  `uppercased` int(10) unsigned NOT NULL DEFAULT '0',
  `quote` varchar(255) NOT NULL DEFAULT '',
  `ex_exclamations` varchar(255) NOT NULL DEFAULT '',
  `ex_questions` varchar(255) NOT NULL DEFAULT '',
  `ex_actions` varchar(255) NOT NULL DEFAULT '',
  `ex_uppercased` varchar(255) NOT NULL DEFAULT '',
  `lasttalked` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`ruid`),
  KEY `l_total` (`l_total`),
  KEY `urls` (`urls`),
  KEY `words` (`words`),
  KEY `characters` (`characters`),
  KEY `monologues` (`monologues`),
  KEY `topmonologue` (`topmonologue`),
  KEY `activedays` (`activedays`),
  KEY `slaps` (`slaps`),
  KEY `slapped` (`slapped`),
  KEY `exclamations` (`exclamations`),
  KEY `questions` (`questions`),
  KEY `actions` (`actions`),
  KEY `uppercased` (`uppercased`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `q_smileys`
--

DROP TABLE IF EXISTS `q_smileys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `q_smileys` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `s_01` int(10) unsigned NOT NULL DEFAULT '0',
  `s_02` int(10) unsigned NOT NULL DEFAULT '0',
  `s_03` int(10) unsigned NOT NULL DEFAULT '0',
  `s_04` int(10) unsigned NOT NULL DEFAULT '0',
  `s_05` int(10) unsigned NOT NULL DEFAULT '0',
  `s_06` int(10) unsigned NOT NULL DEFAULT '0',
  `s_07` int(10) unsigned NOT NULL DEFAULT '0',
  `s_08` int(10) unsigned NOT NULL DEFAULT '0',
  `s_09` int(10) unsigned NOT NULL DEFAULT '0',
  `s_10` int(10) unsigned NOT NULL DEFAULT '0',
  `s_11` int(10) unsigned NOT NULL DEFAULT '0',
  `s_12` int(10) unsigned NOT NULL DEFAULT '0',
  `s_13` int(10) unsigned NOT NULL DEFAULT '0',
  `s_14` int(10) unsigned NOT NULL DEFAULT '0',
  `s_15` int(10) unsigned NOT NULL DEFAULT '0',
  `s_16` int(10) unsigned NOT NULL DEFAULT '0',
  `s_17` int(10) unsigned NOT NULL DEFAULT '0',
  `s_18` int(10) unsigned NOT NULL DEFAULT '0',
  `s_19` int(10) unsigned NOT NULL DEFAULT '0',
  `s_20` int(10) unsigned NOT NULL DEFAULT '0',
  `s_21` int(10) unsigned NOT NULL DEFAULT '0',
  `s_22` int(10) unsigned NOT NULL DEFAULT '0',
  `s_23` int(10) unsigned NOT NULL DEFAULT '0',
  `s_24` int(10) unsigned NOT NULL DEFAULT '0',
  `s_25` int(10) unsigned NOT NULL DEFAULT '0',
  `s_26` int(10) unsigned NOT NULL DEFAULT '0',
  `s_27` int(10) unsigned NOT NULL DEFAULT '0',
  `s_28` int(10) unsigned NOT NULL DEFAULT '0',
  `s_29` int(10) unsigned NOT NULL DEFAULT '0',
  `s_30` int(10) unsigned NOT NULL DEFAULT '0',
  `s_31` int(10) unsigned NOT NULL DEFAULT '0',
  `s_32` int(10) unsigned NOT NULL DEFAULT '0',
  `s_33` int(10) unsigned NOT NULL DEFAULT '0',
  `s_34` int(10) unsigned NOT NULL DEFAULT '0',
  `s_35` int(10) unsigned NOT NULL DEFAULT '0',
  `s_36` int(10) unsigned NOT NULL DEFAULT '0',
  `s_37` int(10) unsigned NOT NULL DEFAULT '0',
  `s_38` int(10) unsigned NOT NULL DEFAULT '0',
  `s_39` int(10) unsigned NOT NULL DEFAULT '0',
  `s_40` int(10) unsigned NOT NULL DEFAULT '0',
  `s_41` int(10) unsigned NOT NULL DEFAULT '0',
  `s_42` int(10) unsigned NOT NULL DEFAULT '0',
  `s_43` int(10) unsigned NOT NULL DEFAULT '0',
  `s_44` int(10) unsigned NOT NULL DEFAULT '0',
  `s_45` int(10) unsigned NOT NULL DEFAULT '0',
  `s_46` int(10) unsigned NOT NULL DEFAULT '0',
  `s_47` int(10) unsigned NOT NULL DEFAULT '0',
  `s_48` int(10) unsigned NOT NULL DEFAULT '0',
  `s_49` int(10) unsigned NOT NULL DEFAULT '0',
  `s_50` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ruid`),
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
  KEY `s_19` (`s_19`),
  KEY `s_20` (`s_20`),
  KEY `s_21` (`s_21`),
  KEY `s_22` (`s_22`),
  KEY `s_23` (`s_23`),
  KEY `s_24` (`s_24`),
  KEY `s_25` (`s_25`),
  KEY `s_26` (`s_26`),
  KEY `s_27` (`s_27`),
  KEY `s_28` (`s_28`),
  KEY `s_29` (`s_29`),
  KEY `s_30` (`s_30`),
  KEY `s_31` (`s_31`),
  KEY `s_32` (`s_32`),
  KEY `s_33` (`s_33`),
  KEY `s_34` (`s_34`),
  KEY `s_35` (`s_35`),
  KEY `s_36` (`s_36`),
  KEY `s_37` (`s_37`),
  KEY `s_38` (`s_38`),
  KEY `s_39` (`s_39`),
  KEY `s_40` (`s_40`),
  KEY `s_41` (`s_41`),
  KEY `s_42` (`s_42`),
  KEY `s_43` (`s_43`),
  KEY `s_44` (`s_44`),
  KEY `s_45` (`s_45`),
  KEY `s_46` (`s_46`),
  KEY `s_47` (`s_47`),
  KEY `s_48` (`s_48`),
  KEY `s_49` (`s_49`),
  KEY `s_50` (`s_50`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `streak_history`
--

DROP TABLE IF EXISTS `streak_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `streak_history` (
  `prevnick` varchar(255) NOT NULL DEFAULT '',
  `streak` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `t_mv_activedays`
--

DROP TABLE IF EXISTS `t_mv_activedays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_mv_activedays` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `activedays` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ruid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `t_mv_events`
--

DROP TABLE IF EXISTS `t_mv_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_mv_events` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `m_op` int(10) unsigned NOT NULL DEFAULT '0',
  `m_opped` int(10) unsigned NOT NULL DEFAULT '0',
  `m_voice` int(10) unsigned NOT NULL DEFAULT '0',
  `m_voiced` int(10) unsigned NOT NULL DEFAULT '0',
  `m_deop` int(10) unsigned NOT NULL DEFAULT '0',
  `m_deopped` int(10) unsigned NOT NULL DEFAULT '0',
  `m_devoice` int(10) unsigned NOT NULL DEFAULT '0',
  `m_devoiced` int(10) unsigned NOT NULL DEFAULT '0',
  `joins` int(10) unsigned NOT NULL DEFAULT '0',
  `parts` int(10) unsigned NOT NULL DEFAULT '0',
  `quits` int(10) unsigned NOT NULL DEFAULT '0',
  `kicks` int(10) unsigned NOT NULL DEFAULT '0',
  `kicked` int(10) unsigned NOT NULL DEFAULT '0',
  `nickchanges` int(10) unsigned NOT NULL DEFAULT '0',
  `topics` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ruid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `t_mv_ex_actions`
--

DROP TABLE IF EXISTS `t_mv_ex_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_mv_ex_actions` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `ex_actions` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ruid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `t_mv_ex_exclamations`
--

DROP TABLE IF EXISTS `t_mv_ex_exclamations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_mv_ex_exclamations` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `ex_exclamations` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ruid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `t_mv_ex_kicked`
--

DROP TABLE IF EXISTS `t_mv_ex_kicked`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_mv_ex_kicked` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `ex_kicked` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ruid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `t_mv_ex_kicks`
--

DROP TABLE IF EXISTS `t_mv_ex_kicks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_mv_ex_kicks` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `ex_kicks` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ruid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `t_mv_ex_questions`
--

DROP TABLE IF EXISTS `t_mv_ex_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_mv_ex_questions` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `ex_questions` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ruid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `t_mv_ex_uppercased`
--

DROP TABLE IF EXISTS `t_mv_ex_uppercased`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_mv_ex_uppercased` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `ex_uppercased` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ruid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `t_mv_lines`
--

DROP TABLE IF EXISTS `t_mv_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_mv_lines` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `l_00` int(10) unsigned NOT NULL DEFAULT '0',
  `l_01` int(10) unsigned NOT NULL DEFAULT '0',
  `l_02` int(10) unsigned NOT NULL DEFAULT '0',
  `l_03` int(10) unsigned NOT NULL DEFAULT '0',
  `l_04` int(10) unsigned NOT NULL DEFAULT '0',
  `l_05` int(10) unsigned NOT NULL DEFAULT '0',
  `l_06` int(10) unsigned NOT NULL DEFAULT '0',
  `l_07` int(10) unsigned NOT NULL DEFAULT '0',
  `l_08` int(10) unsigned NOT NULL DEFAULT '0',
  `l_09` int(10) unsigned NOT NULL DEFAULT '0',
  `l_10` int(10) unsigned NOT NULL DEFAULT '0',
  `l_11` int(10) unsigned NOT NULL DEFAULT '0',
  `l_12` int(10) unsigned NOT NULL DEFAULT '0',
  `l_13` int(10) unsigned NOT NULL DEFAULT '0',
  `l_14` int(10) unsigned NOT NULL DEFAULT '0',
  `l_15` int(10) unsigned NOT NULL DEFAULT '0',
  `l_16` int(10) unsigned NOT NULL DEFAULT '0',
  `l_17` int(10) unsigned NOT NULL DEFAULT '0',
  `l_18` int(10) unsigned NOT NULL DEFAULT '0',
  `l_19` int(10) unsigned NOT NULL DEFAULT '0',
  `l_20` int(10) unsigned NOT NULL DEFAULT '0',
  `l_21` int(10) unsigned NOT NULL DEFAULT '0',
  `l_22` int(10) unsigned NOT NULL DEFAULT '0',
  `l_23` int(10) unsigned NOT NULL DEFAULT '0',
  `l_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_total` int(10) unsigned NOT NULL DEFAULT '0',
  `l_mon_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_mon_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_mon_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_mon_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_tue_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_tue_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_tue_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_tue_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_wed_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_wed_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_wed_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_wed_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_thu_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_thu_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_thu_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_thu_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_fri_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_fri_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_fri_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_fri_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sat_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sat_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sat_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sat_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sun_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sun_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sun_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sun_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `urls` int(10) unsigned NOT NULL DEFAULT '0',
  `words` int(10) unsigned NOT NULL DEFAULT '0',
  `characters` int(10) unsigned NOT NULL DEFAULT '0',
  `monologues` int(10) unsigned NOT NULL DEFAULT '0',
  `topmonologue` int(10) unsigned NOT NULL DEFAULT '0',
  `slaps` int(10) unsigned NOT NULL DEFAULT '0',
  `slapped` int(10) unsigned NOT NULL DEFAULT '0',
  `exclamations` int(10) unsigned NOT NULL DEFAULT '0',
  `questions` int(10) unsigned NOT NULL DEFAULT '0',
  `actions` int(10) unsigned NOT NULL DEFAULT '0',
  `uppercased` int(10) unsigned NOT NULL DEFAULT '0',
  `lasttalked` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`ruid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `t_mv_quote`
--

DROP TABLE IF EXISTS `t_mv_quote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_mv_quote` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `quote` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ruid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `t_q_activity_by_day`
--

DROP TABLE IF EXISTS `t_q_activity_by_day`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_q_activity_by_day` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `date` date NOT NULL DEFAULT '0000-00-00',
  `l_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_total` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ruid`,`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `t_q_activity_by_month`
--

DROP TABLE IF EXISTS `t_q_activity_by_month`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_q_activity_by_month` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `date` varchar(7) NOT NULL DEFAULT '0000-00',
  `l_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_total` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ruid`,`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `t_q_activity_by_year`
--

DROP TABLE IF EXISTS `t_q_activity_by_year`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_q_activity_by_year` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `date` year(4) NOT NULL DEFAULT '0000',
  `l_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_total` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ruid`,`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `t_q_events`
--

DROP TABLE IF EXISTS `t_q_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_q_events` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `m_op` int(10) unsigned NOT NULL DEFAULT '0',
  `m_opped` int(10) unsigned NOT NULL DEFAULT '0',
  `m_voice` int(10) unsigned NOT NULL DEFAULT '0',
  `m_voiced` int(10) unsigned NOT NULL DEFAULT '0',
  `m_deop` int(10) unsigned NOT NULL DEFAULT '0',
  `m_deopped` int(10) unsigned NOT NULL DEFAULT '0',
  `m_devoice` int(10) unsigned NOT NULL DEFAULT '0',
  `m_devoiced` int(10) unsigned NOT NULL DEFAULT '0',
  `joins` int(10) unsigned NOT NULL DEFAULT '0',
  `parts` int(10) unsigned NOT NULL DEFAULT '0',
  `quits` int(10) unsigned NOT NULL DEFAULT '0',
  `kicks` int(10) unsigned NOT NULL DEFAULT '0',
  `kicked` int(10) unsigned NOT NULL DEFAULT '0',
  `nickchanges` int(10) unsigned NOT NULL DEFAULT '0',
  `topics` int(10) unsigned NOT NULL DEFAULT '0',
  `ex_kicks` varchar(255) NOT NULL DEFAULT '',
  `ex_kicked` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ruid`),
  KEY `m_op` (`m_op`),
  KEY `m_opped` (`m_opped`),
  KEY `m_voice` (`m_voice`),
  KEY `m_voiced` (`m_voiced`),
  KEY `m_deop` (`m_deop`),
  KEY `m_deopped` (`m_deopped`),
  KEY `m_devoice` (`m_devoice`),
  KEY `m_devoiced` (`m_devoiced`),
  KEY `joins` (`joins`),
  KEY `parts` (`parts`),
  KEY `quits` (`quits`),
  KEY `kicks` (`kicks`),
  KEY `kicked` (`kicked`),
  KEY `nickchanges` (`nickchanges`),
  KEY `topics` (`topics`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `t_q_lines`
--

DROP TABLE IF EXISTS `t_q_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_q_lines` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `l_00` int(10) unsigned NOT NULL DEFAULT '0',
  `l_01` int(10) unsigned NOT NULL DEFAULT '0',
  `l_02` int(10) unsigned NOT NULL DEFAULT '0',
  `l_03` int(10) unsigned NOT NULL DEFAULT '0',
  `l_04` int(10) unsigned NOT NULL DEFAULT '0',
  `l_05` int(10) unsigned NOT NULL DEFAULT '0',
  `l_06` int(10) unsigned NOT NULL DEFAULT '0',
  `l_07` int(10) unsigned NOT NULL DEFAULT '0',
  `l_08` int(10) unsigned NOT NULL DEFAULT '0',
  `l_09` int(10) unsigned NOT NULL DEFAULT '0',
  `l_10` int(10) unsigned NOT NULL DEFAULT '0',
  `l_11` int(10) unsigned NOT NULL DEFAULT '0',
  `l_12` int(10) unsigned NOT NULL DEFAULT '0',
  `l_13` int(10) unsigned NOT NULL DEFAULT '0',
  `l_14` int(10) unsigned NOT NULL DEFAULT '0',
  `l_15` int(10) unsigned NOT NULL DEFAULT '0',
  `l_16` int(10) unsigned NOT NULL DEFAULT '0',
  `l_17` int(10) unsigned NOT NULL DEFAULT '0',
  `l_18` int(10) unsigned NOT NULL DEFAULT '0',
  `l_19` int(10) unsigned NOT NULL DEFAULT '0',
  `l_20` int(10) unsigned NOT NULL DEFAULT '0',
  `l_21` int(10) unsigned NOT NULL DEFAULT '0',
  `l_22` int(10) unsigned NOT NULL DEFAULT '0',
  `l_23` int(10) unsigned NOT NULL DEFAULT '0',
  `l_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_total` int(10) unsigned NOT NULL DEFAULT '0',
  `l_mon_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_mon_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_mon_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_mon_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_tue_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_tue_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_tue_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_tue_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_wed_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_wed_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_wed_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_wed_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_thu_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_thu_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_thu_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_thu_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_fri_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_fri_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_fri_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_fri_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sat_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sat_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sat_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sat_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sun_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sun_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sun_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sun_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `urls` int(10) unsigned NOT NULL DEFAULT '0',
  `words` int(10) unsigned NOT NULL DEFAULT '0',
  `characters` int(10) unsigned NOT NULL DEFAULT '0',
  `monologues` int(10) unsigned NOT NULL DEFAULT '0',
  `topmonologue` int(10) unsigned NOT NULL DEFAULT '0',
  `activedays` int(10) unsigned NOT NULL DEFAULT '0',
  `slaps` int(10) unsigned NOT NULL DEFAULT '0',
  `slapped` int(10) unsigned NOT NULL DEFAULT '0',
  `exclamations` int(10) unsigned NOT NULL DEFAULT '0',
  `questions` int(10) unsigned NOT NULL DEFAULT '0',
  `actions` int(10) unsigned NOT NULL DEFAULT '0',
  `uppercased` int(10) unsigned NOT NULL DEFAULT '0',
  `quote` varchar(255) NOT NULL DEFAULT '',
  `ex_exclamations` varchar(255) NOT NULL DEFAULT '',
  `ex_questions` varchar(255) NOT NULL DEFAULT '',
  `ex_actions` varchar(255) NOT NULL DEFAULT '',
  `ex_uppercased` varchar(255) NOT NULL DEFAULT '',
  `lasttalked` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`ruid`),
  KEY `l_total` (`l_total`),
  KEY `urls` (`urls`),
  KEY `words` (`words`),
  KEY `characters` (`characters`),
  KEY `monologues` (`monologues`),
  KEY `topmonologue` (`topmonologue`),
  KEY `activedays` (`activedays`),
  KEY `slaps` (`slaps`),
  KEY `slapped` (`slapped`),
  KEY `exclamations` (`exclamations`),
  KEY `questions` (`questions`),
  KEY `actions` (`actions`),
  KEY `uppercased` (`uppercased`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `t_q_smileys`
--

DROP TABLE IF EXISTS `t_q_smileys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_q_smileys` (
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `s_01` int(10) unsigned NOT NULL DEFAULT '0',
  `s_02` int(10) unsigned NOT NULL DEFAULT '0',
  `s_03` int(10) unsigned NOT NULL DEFAULT '0',
  `s_04` int(10) unsigned NOT NULL DEFAULT '0',
  `s_05` int(10) unsigned NOT NULL DEFAULT '0',
  `s_06` int(10) unsigned NOT NULL DEFAULT '0',
  `s_07` int(10) unsigned NOT NULL DEFAULT '0',
  `s_08` int(10) unsigned NOT NULL DEFAULT '0',
  `s_09` int(10) unsigned NOT NULL DEFAULT '0',
  `s_10` int(10) unsigned NOT NULL DEFAULT '0',
  `s_11` int(10) unsigned NOT NULL DEFAULT '0',
  `s_12` int(10) unsigned NOT NULL DEFAULT '0',
  `s_13` int(10) unsigned NOT NULL DEFAULT '0',
  `s_14` int(10) unsigned NOT NULL DEFAULT '0',
  `s_15` int(10) unsigned NOT NULL DEFAULT '0',
  `s_16` int(10) unsigned NOT NULL DEFAULT '0',
  `s_17` int(10) unsigned NOT NULL DEFAULT '0',
  `s_18` int(10) unsigned NOT NULL DEFAULT '0',
  `s_19` int(10) unsigned NOT NULL DEFAULT '0',
  `s_20` int(10) unsigned NOT NULL DEFAULT '0',
  `s_21` int(10) unsigned NOT NULL DEFAULT '0',
  `s_22` int(10) unsigned NOT NULL DEFAULT '0',
  `s_23` int(10) unsigned NOT NULL DEFAULT '0',
  `s_24` int(10) unsigned NOT NULL DEFAULT '0',
  `s_25` int(10) unsigned NOT NULL DEFAULT '0',
  `s_26` int(10) unsigned NOT NULL DEFAULT '0',
  `s_27` int(10) unsigned NOT NULL DEFAULT '0',
  `s_28` int(10) unsigned NOT NULL DEFAULT '0',
  `s_29` int(10) unsigned NOT NULL DEFAULT '0',
  `s_30` int(10) unsigned NOT NULL DEFAULT '0',
  `s_31` int(10) unsigned NOT NULL DEFAULT '0',
  `s_32` int(10) unsigned NOT NULL DEFAULT '0',
  `s_33` int(10) unsigned NOT NULL DEFAULT '0',
  `s_34` int(10) unsigned NOT NULL DEFAULT '0',
  `s_35` int(10) unsigned NOT NULL DEFAULT '0',
  `s_36` int(10) unsigned NOT NULL DEFAULT '0',
  `s_37` int(10) unsigned NOT NULL DEFAULT '0',
  `s_38` int(10) unsigned NOT NULL DEFAULT '0',
  `s_39` int(10) unsigned NOT NULL DEFAULT '0',
  `s_40` int(10) unsigned NOT NULL DEFAULT '0',
  `s_41` int(10) unsigned NOT NULL DEFAULT '0',
  `s_42` int(10) unsigned NOT NULL DEFAULT '0',
  `s_43` int(10) unsigned NOT NULL DEFAULT '0',
  `s_44` int(10) unsigned NOT NULL DEFAULT '0',
  `s_45` int(10) unsigned NOT NULL DEFAULT '0',
  `s_46` int(10) unsigned NOT NULL DEFAULT '0',
  `s_47` int(10) unsigned NOT NULL DEFAULT '0',
  `s_48` int(10) unsigned NOT NULL DEFAULT '0',
  `s_49` int(10) unsigned NOT NULL DEFAULT '0',
  `s_50` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ruid`),
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
  KEY `s_19` (`s_19`),
  KEY `s_20` (`s_20`),
  KEY `s_21` (`s_21`),
  KEY `s_22` (`s_22`),
  KEY `s_23` (`s_23`),
  KEY `s_24` (`s_24`),
  KEY `s_25` (`s_25`),
  KEY `s_26` (`s_26`),
  KEY `s_27` (`s_27`),
  KEY `s_28` (`s_28`),
  KEY `s_29` (`s_29`),
  KEY `s_30` (`s_30`),
  KEY `s_31` (`s_31`),
  KEY `s_32` (`s_32`),
  KEY `s_33` (`s_33`),
  KEY `s_34` (`s_34`),
  KEY `s_35` (`s_35`),
  KEY `s_36` (`s_36`),
  KEY `s_37` (`s_37`),
  KEY `s_38` (`s_38`),
  KEY `s_39` (`s_39`),
  KEY `s_40` (`s_40`),
  KEY `s_41` (`s_41`),
  KEY `s_42` (`s_42`),
  KEY `s_43` (`s_43`),
  KEY `s_44` (`s_44`),
  KEY `s_45` (`s_45`),
  KEY `s_46` (`s_46`),
  KEY `s_47` (`s_47`),
  KEY `s_48` (`s_48`),
  KEY `s_49` (`s_49`),
  KEY `s_50` (`s_50`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_activity`
--

DROP TABLE IF EXISTS `user_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_activity` (
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `date` date NOT NULL DEFAULT '0000-00-00',
  `l_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_total` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`,`date`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_details`
--

DROP TABLE IF EXISTS `user_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_details` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `csnick` varchar(255) NOT NULL DEFAULT '',
  `firstseen` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastseen` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `csnick` (`csnick`),
  KEY `firstseen` (`firstseen`),
  KEY `lastseen` (`lastseen`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_events`
--

DROP TABLE IF EXISTS `user_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_events` (
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `m_op` int(10) unsigned NOT NULL DEFAULT '0',
  `m_opped` int(10) unsigned NOT NULL DEFAULT '0',
  `m_voice` int(10) unsigned NOT NULL DEFAULT '0',
  `m_voiced` int(10) unsigned NOT NULL DEFAULT '0',
  `m_deop` int(10) unsigned NOT NULL DEFAULT '0',
  `m_deopped` int(10) unsigned NOT NULL DEFAULT '0',
  `m_devoice` int(10) unsigned NOT NULL DEFAULT '0',
  `m_devoiced` int(10) unsigned NOT NULL DEFAULT '0',
  `joins` int(10) unsigned NOT NULL DEFAULT '0',
  `parts` int(10) unsigned NOT NULL DEFAULT '0',
  `quits` int(10) unsigned NOT NULL DEFAULT '0',
  `kicks` int(10) unsigned NOT NULL DEFAULT '0',
  `kicked` int(10) unsigned NOT NULL DEFAULT '0',
  `nickchanges` int(10) unsigned NOT NULL DEFAULT '0',
  `topics` int(10) unsigned NOT NULL DEFAULT '0',
  `ex_kicks` varchar(255) NOT NULL DEFAULT '',
  `ex_kicked` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_lines`
--

DROP TABLE IF EXISTS `user_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_lines` (
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `l_00` int(10) unsigned NOT NULL DEFAULT '0',
  `l_01` int(10) unsigned NOT NULL DEFAULT '0',
  `l_02` int(10) unsigned NOT NULL DEFAULT '0',
  `l_03` int(10) unsigned NOT NULL DEFAULT '0',
  `l_04` int(10) unsigned NOT NULL DEFAULT '0',
  `l_05` int(10) unsigned NOT NULL DEFAULT '0',
  `l_06` int(10) unsigned NOT NULL DEFAULT '0',
  `l_07` int(10) unsigned NOT NULL DEFAULT '0',
  `l_08` int(10) unsigned NOT NULL DEFAULT '0',
  `l_09` int(10) unsigned NOT NULL DEFAULT '0',
  `l_10` int(10) unsigned NOT NULL DEFAULT '0',
  `l_11` int(10) unsigned NOT NULL DEFAULT '0',
  `l_12` int(10) unsigned NOT NULL DEFAULT '0',
  `l_13` int(10) unsigned NOT NULL DEFAULT '0',
  `l_14` int(10) unsigned NOT NULL DEFAULT '0',
  `l_15` int(10) unsigned NOT NULL DEFAULT '0',
  `l_16` int(10) unsigned NOT NULL DEFAULT '0',
  `l_17` int(10) unsigned NOT NULL DEFAULT '0',
  `l_18` int(10) unsigned NOT NULL DEFAULT '0',
  `l_19` int(10) unsigned NOT NULL DEFAULT '0',
  `l_20` int(10) unsigned NOT NULL DEFAULT '0',
  `l_21` int(10) unsigned NOT NULL DEFAULT '0',
  `l_22` int(10) unsigned NOT NULL DEFAULT '0',
  `l_23` int(10) unsigned NOT NULL DEFAULT '0',
  `l_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_total` int(10) unsigned NOT NULL DEFAULT '0',
  `l_mon_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_mon_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_mon_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_mon_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_tue_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_tue_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_tue_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_tue_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_wed_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_wed_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_wed_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_wed_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_thu_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_thu_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_thu_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_thu_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_fri_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_fri_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_fri_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_fri_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sat_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sat_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sat_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sat_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sun_night` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sun_morning` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sun_afternoon` int(10) unsigned NOT NULL DEFAULT '0',
  `l_sun_evening` int(10) unsigned NOT NULL DEFAULT '0',
  `urls` int(10) unsigned NOT NULL DEFAULT '0',
  `words` int(10) unsigned NOT NULL DEFAULT '0',
  `characters` int(10) unsigned NOT NULL DEFAULT '0',
  `monologues` int(10) unsigned NOT NULL DEFAULT '0',
  `topmonologue` int(10) unsigned NOT NULL DEFAULT '0',
  `activedays` int(10) unsigned NOT NULL DEFAULT '0',
  `slaps` int(10) unsigned NOT NULL DEFAULT '0',
  `slapped` int(10) unsigned NOT NULL DEFAULT '0',
  `exclamations` int(10) unsigned NOT NULL DEFAULT '0',
  `questions` int(10) unsigned NOT NULL DEFAULT '0',
  `actions` int(10) unsigned NOT NULL DEFAULT '0',
  `uppercased` int(10) unsigned NOT NULL DEFAULT '0',
  `quote` varchar(255) NOT NULL DEFAULT '',
  `ex_exclamations` varchar(255) NOT NULL DEFAULT '',
  `ex_questions` varchar(255) NOT NULL DEFAULT '',
  `ex_actions` varchar(255) NOT NULL DEFAULT '',
  `ex_uppercased` varchar(255) NOT NULL DEFAULT '',
  `lasttalked` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_smileys`
--

DROP TABLE IF EXISTS `user_smileys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_smileys` (
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `s_01` int(10) unsigned NOT NULL DEFAULT '0',
  `s_02` int(10) unsigned NOT NULL DEFAULT '0',
  `s_03` int(10) unsigned NOT NULL DEFAULT '0',
  `s_04` int(10) unsigned NOT NULL DEFAULT '0',
  `s_05` int(10) unsigned NOT NULL DEFAULT '0',
  `s_06` int(10) unsigned NOT NULL DEFAULT '0',
  `s_07` int(10) unsigned NOT NULL DEFAULT '0',
  `s_08` int(10) unsigned NOT NULL DEFAULT '0',
  `s_09` int(10) unsigned NOT NULL DEFAULT '0',
  `s_10` int(10) unsigned NOT NULL DEFAULT '0',
  `s_11` int(10) unsigned NOT NULL DEFAULT '0',
  `s_12` int(10) unsigned NOT NULL DEFAULT '0',
  `s_13` int(10) unsigned NOT NULL DEFAULT '0',
  `s_14` int(10) unsigned NOT NULL DEFAULT '0',
  `s_15` int(10) unsigned NOT NULL DEFAULT '0',
  `s_16` int(10) unsigned NOT NULL DEFAULT '0',
  `s_17` int(10) unsigned NOT NULL DEFAULT '0',
  `s_18` int(10) unsigned NOT NULL DEFAULT '0',
  `s_19` int(10) unsigned NOT NULL DEFAULT '0',
  `s_20` int(10) unsigned NOT NULL DEFAULT '0',
  `s_21` int(10) unsigned NOT NULL DEFAULT '0',
  `s_22` int(10) unsigned NOT NULL DEFAULT '0',
  `s_23` int(10) unsigned NOT NULL DEFAULT '0',
  `s_24` int(10) unsigned NOT NULL DEFAULT '0',
  `s_25` int(10) unsigned NOT NULL DEFAULT '0',
  `s_26` int(10) unsigned NOT NULL DEFAULT '0',
  `s_27` int(10) unsigned NOT NULL DEFAULT '0',
  `s_28` int(10) unsigned NOT NULL DEFAULT '0',
  `s_29` int(10) unsigned NOT NULL DEFAULT '0',
  `s_30` int(10) unsigned NOT NULL DEFAULT '0',
  `s_31` int(10) unsigned NOT NULL DEFAULT '0',
  `s_32` int(10) unsigned NOT NULL DEFAULT '0',
  `s_33` int(10) unsigned NOT NULL DEFAULT '0',
  `s_34` int(10) unsigned NOT NULL DEFAULT '0',
  `s_35` int(10) unsigned NOT NULL DEFAULT '0',
  `s_36` int(10) unsigned NOT NULL DEFAULT '0',
  `s_37` int(10) unsigned NOT NULL DEFAULT '0',
  `s_38` int(10) unsigned NOT NULL DEFAULT '0',
  `s_39` int(10) unsigned NOT NULL DEFAULT '0',
  `s_40` int(10) unsigned NOT NULL DEFAULT '0',
  `s_41` int(10) unsigned NOT NULL DEFAULT '0',
  `s_42` int(10) unsigned NOT NULL DEFAULT '0',
  `s_43` int(10) unsigned NOT NULL DEFAULT '0',
  `s_44` int(10) unsigned NOT NULL DEFAULT '0',
  `s_45` int(10) unsigned NOT NULL DEFAULT '0',
  `s_46` int(10) unsigned NOT NULL DEFAULT '0',
  `s_47` int(10) unsigned NOT NULL DEFAULT '0',
  `s_48` int(10) unsigned NOT NULL DEFAULT '0',
  `s_49` int(10) unsigned NOT NULL DEFAULT '0',
  `s_50` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_status`
--

DROP TABLE IF EXISTS `user_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_status` (
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `ruid` int(10) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `ruid` (`ruid`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_topics`
--

DROP TABLE IF EXISTS `user_topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_topics` (
  `tid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `topic` varchar(1024) NOT NULL DEFAULT '',
  `setdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`tid`,`uid`,`setdate`),
  KEY `uid` (`uid`),
  KEY `topic` (`topic`(333)),
  KEY `setdate` (`setdate`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_urls`
--

DROP TABLE IF EXISTS `user_urls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_urls` (
  `lid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `url` varchar(1024) NOT NULL DEFAULT '',
  `scheme` varchar(1024) NOT NULL DEFAULT '',
  `authority` varchar(255) NOT NULL DEFAULT '',
  `ipv4address` varchar(1024) NOT NULL DEFAULT '',
  `fqdn` varchar(1024) NOT NULL DEFAULT '',
  `domain` varchar(1024) NOT NULL DEFAULT '',
  `tld` varchar(255) NOT NULL DEFAULT '',
  `port` smallint(5) unsigned NOT NULL DEFAULT '0',
  `path` varchar(1024) NOT NULL DEFAULT '',
  `query` varchar(1024) NOT NULL DEFAULT '',
  `fragment` varchar(1024) NOT NULL DEFAULT '',
  `extension` varchar(255) NOT NULL DEFAULT '',
  `total` int(10) unsigned NOT NULL DEFAULT '0',
  `firstused` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastused` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`lid`,`uid`),
  KEY `uid` (`uid`),
  KEY `url` (`url`(333)),
  KEY `authority` (`authority`),
  KEY `fqdn` (`fqdn`(333)),
  KEY `tld` (`tld`),
  KEY `extension` (`extension`),
  KEY `total` (`total`),
  KEY `firstused` (`firstused`),
  KEY `lastused` (`lastused`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `v_activedays`
--

DROP TABLE IF EXISTS `v_activedays`;
/*!50001 DROP VIEW IF EXISTS `v_activedays`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_activedays` (
  `ruid` int(10) unsigned,
  `activedays` bigint(21)
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_events`
--

DROP TABLE IF EXISTS `v_events`;
/*!50001 DROP VIEW IF EXISTS `v_events`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_events` (
  `ruid` int(10) unsigned,
  `m_op` decimal(33,0),
  `m_opped` decimal(33,0),
  `m_voice` decimal(33,0),
  `m_voiced` decimal(33,0),
  `m_deop` decimal(33,0),
  `m_deopped` decimal(33,0),
  `m_devoice` decimal(33,0),
  `m_devoiced` decimal(33,0),
  `joins` decimal(33,0),
  `parts` decimal(33,0),
  `quits` decimal(33,0),
  `kicks` decimal(33,0),
  `kicked` decimal(33,0),
  `nickchanges` decimal(33,0),
  `topics` decimal(33,0)
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ex_actions`
--

DROP TABLE IF EXISTS `v_ex_actions`;
/*!50001 DROP VIEW IF EXISTS `v_ex_actions`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_ex_actions` (
  `ruid` int(10) unsigned,
  `ex_actions` varchar(255)
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ex_actions_1`
--

DROP TABLE IF EXISTS `v_ex_actions_1`;
/*!50001 DROP VIEW IF EXISTS `v_ex_actions_1`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_ex_actions_1` (
  `ruid` int(10) unsigned,
  `ex_actions` varchar(255),
  `lastseen` datetime
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ex_actions_2`
--

DROP TABLE IF EXISTS `v_ex_actions_2`;
/*!50001 DROP VIEW IF EXISTS `v_ex_actions_2`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_ex_actions_2` (
  `ruid` int(10) unsigned,
  `lastseen` datetime
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ex_exclamations`
--

DROP TABLE IF EXISTS `v_ex_exclamations`;
/*!50001 DROP VIEW IF EXISTS `v_ex_exclamations`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_ex_exclamations` (
  `ruid` int(10) unsigned,
  `ex_exclamations` varchar(255)
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ex_exclamations_1`
--

DROP TABLE IF EXISTS `v_ex_exclamations_1`;
/*!50001 DROP VIEW IF EXISTS `v_ex_exclamations_1`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_ex_exclamations_1` (
  `ruid` int(10) unsigned,
  `ex_exclamations` varchar(255),
  `lasttalked` datetime
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ex_exclamations_2`
--

DROP TABLE IF EXISTS `v_ex_exclamations_2`;
/*!50001 DROP VIEW IF EXISTS `v_ex_exclamations_2`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_ex_exclamations_2` (
  `ruid` int(10) unsigned,
  `lasttalked` datetime
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ex_kicked`
--

DROP TABLE IF EXISTS `v_ex_kicked`;
/*!50001 DROP VIEW IF EXISTS `v_ex_kicked`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_ex_kicked` (
  `ruid` int(10) unsigned,
  `ex_kicked` varchar(255)
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ex_kicked_1`
--

DROP TABLE IF EXISTS `v_ex_kicked_1`;
/*!50001 DROP VIEW IF EXISTS `v_ex_kicked_1`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_ex_kicked_1` (
  `ruid` int(10) unsigned,
  `ex_kicked` varchar(255)
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ex_kicks`
--

DROP TABLE IF EXISTS `v_ex_kicks`;
/*!50001 DROP VIEW IF EXISTS `v_ex_kicks`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_ex_kicks` (
  `ruid` int(10) unsigned,
  `ex_kicks` varchar(255)
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ex_kicks_1`
--

DROP TABLE IF EXISTS `v_ex_kicks_1`;
/*!50001 DROP VIEW IF EXISTS `v_ex_kicks_1`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_ex_kicks_1` (
  `ruid` int(10) unsigned,
  `ex_kicks` varchar(255)
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ex_questions`
--

DROP TABLE IF EXISTS `v_ex_questions`;
/*!50001 DROP VIEW IF EXISTS `v_ex_questions`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_ex_questions` (
  `ruid` int(10) unsigned,
  `ex_questions` varchar(255)
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ex_questions_1`
--

DROP TABLE IF EXISTS `v_ex_questions_1`;
/*!50001 DROP VIEW IF EXISTS `v_ex_questions_1`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_ex_questions_1` (
  `ruid` int(10) unsigned,
  `ex_questions` varchar(255),
  `lasttalked` datetime
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ex_questions_2`
--

DROP TABLE IF EXISTS `v_ex_questions_2`;
/*!50001 DROP VIEW IF EXISTS `v_ex_questions_2`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_ex_questions_2` (
  `ruid` int(10) unsigned,
  `lasttalked` datetime
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ex_uppercased`
--

DROP TABLE IF EXISTS `v_ex_uppercased`;
/*!50001 DROP VIEW IF EXISTS `v_ex_uppercased`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_ex_uppercased` (
  `ruid` int(10) unsigned,
  `ex_uppercased` varchar(255)
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ex_uppercased_1`
--

DROP TABLE IF EXISTS `v_ex_uppercased_1`;
/*!50001 DROP VIEW IF EXISTS `v_ex_uppercased_1`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_ex_uppercased_1` (
  `ruid` int(10) unsigned,
  `ex_uppercased` varchar(255),
  `lasttalked` datetime
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ex_uppercased_2`
--

DROP TABLE IF EXISTS `v_ex_uppercased_2`;
/*!50001 DROP VIEW IF EXISTS `v_ex_uppercased_2`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_ex_uppercased_2` (
  `ruid` int(10) unsigned,
  `lasttalked` datetime
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_lines`
--

DROP TABLE IF EXISTS `v_lines`;
/*!50001 DROP VIEW IF EXISTS `v_lines`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_lines` (
  `ruid` int(10) unsigned,
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
  `urls` decimal(33,0),
  `words` decimal(33,0),
  `characters` decimal(33,0),
  `monologues` decimal(33,0),
  `topmonologue` int(10) unsigned,
  `slaps` decimal(33,0),
  `slapped` decimal(33,0),
  `exclamations` decimal(33,0),
  `questions` decimal(33,0),
  `actions` decimal(33,0),
  `uppercased` decimal(33,0),
  `lasttalked` datetime
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_q_activity_by_day`
--

DROP TABLE IF EXISTS `v_q_activity_by_day`;
/*!50001 DROP VIEW IF EXISTS `v_q_activity_by_day`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_q_activity_by_day` (
  `ruid` int(10) unsigned,
  `date` date,
  `l_night` decimal(33,0),
  `l_morning` decimal(33,0),
  `l_afternoon` decimal(33,0),
  `l_evening` decimal(33,0),
  `l_total` decimal(33,0)
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_q_activity_by_month`
--

DROP TABLE IF EXISTS `v_q_activity_by_month`;
/*!50001 DROP VIEW IF EXISTS `v_q_activity_by_month`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_q_activity_by_month` (
  `ruid` int(10) unsigned,
  `date` varchar(7),
  `l_night` decimal(33,0),
  `l_morning` decimal(33,0),
  `l_afternoon` decimal(33,0),
  `l_evening` decimal(33,0),
  `l_total` decimal(33,0)
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_q_activity_by_year`
--

DROP TABLE IF EXISTS `v_q_activity_by_year`;
/*!50001 DROP VIEW IF EXISTS `v_q_activity_by_year`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_q_activity_by_year` (
  `ruid` int(10) unsigned,
  `date` int(4),
  `l_night` decimal(33,0),
  `l_morning` decimal(33,0),
  `l_afternoon` decimal(33,0),
  `l_evening` decimal(33,0),
  `l_total` decimal(33,0)
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_q_events`
--

DROP TABLE IF EXISTS `v_q_events`;
/*!50001 DROP VIEW IF EXISTS `v_q_events`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_q_events` (
  `ruid` int(10) unsigned,
  `m_op` int(10) unsigned,
  `m_opped` int(10) unsigned,
  `m_voice` int(10) unsigned,
  `m_voiced` int(10) unsigned,
  `m_deop` int(10) unsigned,
  `m_deopped` int(10) unsigned,
  `m_devoice` int(10) unsigned,
  `m_devoiced` int(10) unsigned,
  `joins` int(10) unsigned,
  `parts` int(10) unsigned,
  `quits` int(10) unsigned,
  `kicks` int(10) unsigned,
  `kicked` int(10) unsigned,
  `nickchanges` int(10) unsigned,
  `topics` int(10) unsigned,
  `ex_kicks` varchar(255),
  `ex_kicked` varchar(255)
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_q_lines`
--

DROP TABLE IF EXISTS `v_q_lines`;
/*!50001 DROP VIEW IF EXISTS `v_q_lines`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_q_lines` (
  `ruid` int(10) unsigned,
  `l_00` int(10) unsigned,
  `l_01` int(10) unsigned,
  `l_02` int(10) unsigned,
  `l_03` int(10) unsigned,
  `l_04` int(10) unsigned,
  `l_05` int(10) unsigned,
  `l_06` int(10) unsigned,
  `l_07` int(10) unsigned,
  `l_08` int(10) unsigned,
  `l_09` int(10) unsigned,
  `l_10` int(10) unsigned,
  `l_11` int(10) unsigned,
  `l_12` int(10) unsigned,
  `l_13` int(10) unsigned,
  `l_14` int(10) unsigned,
  `l_15` int(10) unsigned,
  `l_16` int(10) unsigned,
  `l_17` int(10) unsigned,
  `l_18` int(10) unsigned,
  `l_19` int(10) unsigned,
  `l_20` int(10) unsigned,
  `l_21` int(10) unsigned,
  `l_22` int(10) unsigned,
  `l_23` int(10) unsigned,
  `l_night` int(10) unsigned,
  `l_morning` int(10) unsigned,
  `l_afternoon` int(10) unsigned,
  `l_evening` int(10) unsigned,
  `l_total` int(10) unsigned,
  `l_mon_night` int(10) unsigned,
  `l_mon_morning` int(10) unsigned,
  `l_mon_afternoon` int(10) unsigned,
  `l_mon_evening` int(10) unsigned,
  `l_tue_night` int(10) unsigned,
  `l_tue_morning` int(10) unsigned,
  `l_tue_afternoon` int(10) unsigned,
  `l_tue_evening` int(10) unsigned,
  `l_wed_night` int(10) unsigned,
  `l_wed_morning` int(10) unsigned,
  `l_wed_afternoon` int(10) unsigned,
  `l_wed_evening` int(10) unsigned,
  `l_thu_night` int(10) unsigned,
  `l_thu_morning` int(10) unsigned,
  `l_thu_afternoon` int(10) unsigned,
  `l_thu_evening` int(10) unsigned,
  `l_fri_night` int(10) unsigned,
  `l_fri_morning` int(10) unsigned,
  `l_fri_afternoon` int(10) unsigned,
  `l_fri_evening` int(10) unsigned,
  `l_sat_night` int(10) unsigned,
  `l_sat_morning` int(10) unsigned,
  `l_sat_afternoon` int(10) unsigned,
  `l_sat_evening` int(10) unsigned,
  `l_sun_night` int(10) unsigned,
  `l_sun_morning` int(10) unsigned,
  `l_sun_afternoon` int(10) unsigned,
  `l_sun_evening` int(10) unsigned,
  `urls` int(10) unsigned,
  `words` int(10) unsigned,
  `characters` int(10) unsigned,
  `monologues` int(10) unsigned,
  `topmonologue` int(10) unsigned,
  `activedays` int(10) unsigned,
  `slaps` int(10) unsigned,
  `slapped` int(10) unsigned,
  `exclamations` int(10) unsigned,
  `questions` int(10) unsigned,
  `actions` int(10) unsigned,
  `uppercased` int(10) unsigned,
  `quote` varchar(255),
  `ex_exclamations` varchar(255),
  `ex_questions` varchar(255),
  `ex_actions` varchar(255),
  `ex_uppercased` varchar(255),
  `lasttalked` datetime
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_q_smileys`
--

DROP TABLE IF EXISTS `v_q_smileys`;
/*!50001 DROP VIEW IF EXISTS `v_q_smileys`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_q_smileys` (
  `ruid` int(10) unsigned,
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
  `s_19` decimal(33,0),
  `s_20` decimal(33,0),
  `s_21` decimal(33,0),
  `s_22` decimal(33,0),
  `s_23` decimal(33,0),
  `s_24` decimal(33,0),
  `s_25` decimal(33,0),
  `s_26` decimal(33,0),
  `s_27` decimal(33,0),
  `s_28` decimal(33,0),
  `s_29` decimal(33,0),
  `s_30` decimal(33,0),
  `s_31` decimal(33,0),
  `s_32` decimal(33,0),
  `s_33` decimal(33,0),
  `s_34` decimal(33,0),
  `s_35` decimal(33,0),
  `s_36` decimal(33,0),
  `s_37` decimal(33,0),
  `s_38` decimal(33,0),
  `s_39` decimal(33,0),
  `s_40` decimal(33,0),
  `s_41` decimal(33,0),
  `s_42` decimal(33,0),
  `s_43` decimal(33,0),
  `s_44` decimal(33,0),
  `s_45` decimal(33,0),
  `s_46` decimal(33,0),
  `s_47` decimal(33,0),
  `s_48` decimal(33,0),
  `s_49` decimal(33,0),
  `s_50` decimal(33,0)
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_quote`
--

DROP TABLE IF EXISTS `v_quote`;
/*!50001 DROP VIEW IF EXISTS `v_quote`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_quote` (
  `ruid` int(10) unsigned,
  `quote` varchar(255)
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_quote_1`
--

DROP TABLE IF EXISTS `v_quote_1`;
/*!50001 DROP VIEW IF EXISTS `v_quote_1`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_quote_1` (
  `ruid` int(10) unsigned,
  `quote` varchar(255),
  `lasttalked` datetime
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_quote_2`
--

DROP TABLE IF EXISTS `v_quote_2`;
/*!50001 DROP VIEW IF EXISTS `v_quote_2`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `v_quote_2` (
  `ruid` int(10) unsigned,
  `lasttalked` datetime
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `words`
--

DROP TABLE IF EXISTS `words`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `words` (
  `wid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `word` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `length` int(10) unsigned NOT NULL DEFAULT '0',
  `total` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`wid`),
  UNIQUE KEY `word` (`word`),
  KEY `length` (`length`),
  KEY `total` (`total`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Final view structure for view `v_activedays`
--

/*!50001 DROP TABLE IF EXISTS `v_activedays`*/;
/*!50001 DROP VIEW IF EXISTS `v_activedays`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_activedays` AS select `user_status`.`ruid` AS `ruid`,count(distinct `user_activity`.`date`) AS `activedays` from (`user_activity` join `user_status` on((`user_activity`.`uid` = `user_status`.`uid`))) group by `user_status`.`ruid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_events`
--

/*!50001 DROP TABLE IF EXISTS `v_events`*/;
/*!50001 DROP VIEW IF EXISTS `v_events`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_events` AS select `user_status`.`ruid` AS `ruid`,sum(`user_events`.`m_op`) AS `m_op`,sum(`user_events`.`m_opped`) AS `m_opped`,sum(`user_events`.`m_voice`) AS `m_voice`,sum(`user_events`.`m_voiced`) AS `m_voiced`,sum(`user_events`.`m_deop`) AS `m_deop`,sum(`user_events`.`m_deopped`) AS `m_deopped`,sum(`user_events`.`m_devoice`) AS `m_devoice`,sum(`user_events`.`m_devoiced`) AS `m_devoiced`,sum(`user_events`.`joins`) AS `joins`,sum(`user_events`.`parts`) AS `parts`,sum(`user_events`.`quits`) AS `quits`,sum(`user_events`.`kicks`) AS `kicks`,sum(`user_events`.`kicked`) AS `kicked`,sum(`user_events`.`nickchanges`) AS `nickchanges`,sum(`user_events`.`topics`) AS `topics` from (`user_events` join `user_status` on((`user_events`.`uid` = `user_status`.`uid`))) group by `user_status`.`ruid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ex_actions`
--

/*!50001 DROP TABLE IF EXISTS `v_ex_actions`*/;
/*!50001 DROP VIEW IF EXISTS `v_ex_actions`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_ex_actions` AS select `v_ex_actions_1`.`ruid` AS `ruid`,`v_ex_actions_1`.`ex_actions` AS `ex_actions` from (`v_ex_actions_1` join `v_ex_actions_2` on((`v_ex_actions_1`.`ruid` = `v_ex_actions_2`.`ruid`))) where (`v_ex_actions_1`.`lastseen` = `v_ex_actions_2`.`lastseen`) group by `v_ex_actions_1`.`ruid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ex_actions_1`
--

/*!50001 DROP TABLE IF EXISTS `v_ex_actions_1`*/;
/*!50001 DROP VIEW IF EXISTS `v_ex_actions_1`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_ex_actions_1` AS select `user_status`.`ruid` AS `ruid`,`user_lines`.`ex_actions` AS `ex_actions`,`user_details`.`lastseen` AS `lastseen` from ((`user_lines` join `user_status` on((`user_lines`.`uid` = `user_status`.`uid`))) join `user_details` on((`user_lines`.`uid` = `user_details`.`uid`))) where (`user_lines`.`ex_actions` <> '') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ex_actions_2`
--

/*!50001 DROP TABLE IF EXISTS `v_ex_actions_2`*/;
/*!50001 DROP VIEW IF EXISTS `v_ex_actions_2`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_ex_actions_2` AS select `v_ex_actions_1`.`ruid` AS `ruid`,max(`v_ex_actions_1`.`lastseen`) AS `lastseen` from `v_ex_actions_1` group by `v_ex_actions_1`.`ruid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ex_exclamations`
--

/*!50001 DROP TABLE IF EXISTS `v_ex_exclamations`*/;
/*!50001 DROP VIEW IF EXISTS `v_ex_exclamations`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_ex_exclamations` AS select `v_ex_exclamations_1`.`ruid` AS `ruid`,`v_ex_exclamations_1`.`ex_exclamations` AS `ex_exclamations` from (`v_ex_exclamations_1` join `v_ex_exclamations_2` on((`v_ex_exclamations_1`.`ruid` = `v_ex_exclamations_2`.`ruid`))) where (`v_ex_exclamations_1`.`lasttalked` = `v_ex_exclamations_2`.`lasttalked`) group by `v_ex_exclamations_1`.`ruid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ex_exclamations_1`
--

/*!50001 DROP TABLE IF EXISTS `v_ex_exclamations_1`*/;
/*!50001 DROP VIEW IF EXISTS `v_ex_exclamations_1`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_ex_exclamations_1` AS select `user_status`.`ruid` AS `ruid`,`user_lines`.`ex_exclamations` AS `ex_exclamations`,`user_lines`.`lasttalked` AS `lasttalked` from (`user_lines` join `user_status` on((`user_lines`.`uid` = `user_status`.`uid`))) where (`user_lines`.`ex_exclamations` <> '') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ex_exclamations_2`
--

/*!50001 DROP TABLE IF EXISTS `v_ex_exclamations_2`*/;
/*!50001 DROP VIEW IF EXISTS `v_ex_exclamations_2`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_ex_exclamations_2` AS select `v_ex_exclamations_1`.`ruid` AS `ruid`,max(`v_ex_exclamations_1`.`lasttalked`) AS `lasttalked` from `v_ex_exclamations_1` group by `v_ex_exclamations_1`.`ruid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ex_kicked`
--

/*!50001 DROP TABLE IF EXISTS `v_ex_kicked`*/;
/*!50001 DROP VIEW IF EXISTS `v_ex_kicked`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_ex_kicked` AS select `v_ex_kicked_1`.`ruid` AS `ruid`,`v_ex_kicked_1`.`ex_kicked` AS `ex_kicked` from `v_ex_kicked_1` group by `v_ex_kicked_1`.`ruid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ex_kicked_1`
--

/*!50001 DROP TABLE IF EXISTS `v_ex_kicked_1`*/;
/*!50001 DROP VIEW IF EXISTS `v_ex_kicked_1`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=TEMPTABLE */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_ex_kicked_1` AS select `user_status`.`ruid` AS `ruid`,`user_events`.`ex_kicked` AS `ex_kicked` from (`user_events` join `user_status` on((`user_events`.`uid` = `user_status`.`uid`))) where (`user_events`.`ex_kicked` <> '') order by rand() */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ex_kicks`
--

/*!50001 DROP TABLE IF EXISTS `v_ex_kicks`*/;
/*!50001 DROP VIEW IF EXISTS `v_ex_kicks`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_ex_kicks` AS select `v_ex_kicks_1`.`ruid` AS `ruid`,`v_ex_kicks_1`.`ex_kicks` AS `ex_kicks` from `v_ex_kicks_1` group by `v_ex_kicks_1`.`ruid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ex_kicks_1`
--

/*!50001 DROP TABLE IF EXISTS `v_ex_kicks_1`*/;
/*!50001 DROP VIEW IF EXISTS `v_ex_kicks_1`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=TEMPTABLE */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_ex_kicks_1` AS select `user_status`.`ruid` AS `ruid`,`user_events`.`ex_kicks` AS `ex_kicks` from (`user_events` join `user_status` on((`user_events`.`uid` = `user_status`.`uid`))) where (`user_events`.`ex_kicks` <> '') order by rand() */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ex_questions`
--

/*!50001 DROP TABLE IF EXISTS `v_ex_questions`*/;
/*!50001 DROP VIEW IF EXISTS `v_ex_questions`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_ex_questions` AS select `v_ex_questions_1`.`ruid` AS `ruid`,`v_ex_questions_1`.`ex_questions` AS `ex_questions` from (`v_ex_questions_1` join `v_ex_questions_2` on((`v_ex_questions_1`.`ruid` = `v_ex_questions_2`.`ruid`))) where (`v_ex_questions_1`.`lasttalked` = `v_ex_questions_2`.`lasttalked`) group by `v_ex_questions_1`.`ruid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ex_questions_1`
--

/*!50001 DROP TABLE IF EXISTS `v_ex_questions_1`*/;
/*!50001 DROP VIEW IF EXISTS `v_ex_questions_1`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_ex_questions_1` AS select `user_status`.`ruid` AS `ruid`,`user_lines`.`ex_questions` AS `ex_questions`,`user_lines`.`lasttalked` AS `lasttalked` from (`user_lines` join `user_status` on((`user_lines`.`uid` = `user_status`.`uid`))) where (`user_lines`.`ex_questions` <> '') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ex_questions_2`
--

/*!50001 DROP TABLE IF EXISTS `v_ex_questions_2`*/;
/*!50001 DROP VIEW IF EXISTS `v_ex_questions_2`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_ex_questions_2` AS select `v_ex_questions_1`.`ruid` AS `ruid`,max(`v_ex_questions_1`.`lasttalked`) AS `lasttalked` from `v_ex_questions_1` group by `v_ex_questions_1`.`ruid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ex_uppercased`
--

/*!50001 DROP TABLE IF EXISTS `v_ex_uppercased`*/;
/*!50001 DROP VIEW IF EXISTS `v_ex_uppercased`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_ex_uppercased` AS select `v_ex_uppercased_1`.`ruid` AS `ruid`,`v_ex_uppercased_1`.`ex_uppercased` AS `ex_uppercased` from (`v_ex_uppercased_1` join `v_ex_uppercased_2` on((`v_ex_uppercased_1`.`ruid` = `v_ex_uppercased_2`.`ruid`))) where (`v_ex_uppercased_1`.`lasttalked` = `v_ex_uppercased_2`.`lasttalked`) group by `v_ex_uppercased_1`.`ruid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ex_uppercased_1`
--

/*!50001 DROP TABLE IF EXISTS `v_ex_uppercased_1`*/;
/*!50001 DROP VIEW IF EXISTS `v_ex_uppercased_1`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_ex_uppercased_1` AS select `user_status`.`ruid` AS `ruid`,`user_lines`.`ex_uppercased` AS `ex_uppercased`,`user_lines`.`lasttalked` AS `lasttalked` from (`user_lines` join `user_status` on((`user_lines`.`uid` = `user_status`.`uid`))) where (`user_lines`.`ex_uppercased` <> '') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ex_uppercased_2`
--

/*!50001 DROP TABLE IF EXISTS `v_ex_uppercased_2`*/;
/*!50001 DROP VIEW IF EXISTS `v_ex_uppercased_2`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_ex_uppercased_2` AS select `v_ex_uppercased_1`.`ruid` AS `ruid`,max(`v_ex_uppercased_1`.`lasttalked`) AS `lasttalked` from `v_ex_uppercased_1` group by `v_ex_uppercased_1`.`ruid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_lines`
--

/*!50001 DROP TABLE IF EXISTS `v_lines`*/;
/*!50001 DROP VIEW IF EXISTS `v_lines`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_lines` AS select `user_status`.`ruid` AS `ruid`,sum(`user_lines`.`l_00`) AS `l_00`,sum(`user_lines`.`l_01`) AS `l_01`,sum(`user_lines`.`l_02`) AS `l_02`,sum(`user_lines`.`l_03`) AS `l_03`,sum(`user_lines`.`l_04`) AS `l_04`,sum(`user_lines`.`l_05`) AS `l_05`,sum(`user_lines`.`l_06`) AS `l_06`,sum(`user_lines`.`l_07`) AS `l_07`,sum(`user_lines`.`l_08`) AS `l_08`,sum(`user_lines`.`l_09`) AS `l_09`,sum(`user_lines`.`l_10`) AS `l_10`,sum(`user_lines`.`l_11`) AS `l_11`,sum(`user_lines`.`l_12`) AS `l_12`,sum(`user_lines`.`l_13`) AS `l_13`,sum(`user_lines`.`l_14`) AS `l_14`,sum(`user_lines`.`l_15`) AS `l_15`,sum(`user_lines`.`l_16`) AS `l_16`,sum(`user_lines`.`l_17`) AS `l_17`,sum(`user_lines`.`l_18`) AS `l_18`,sum(`user_lines`.`l_19`) AS `l_19`,sum(`user_lines`.`l_20`) AS `l_20`,sum(`user_lines`.`l_21`) AS `l_21`,sum(`user_lines`.`l_22`) AS `l_22`,sum(`user_lines`.`l_23`) AS `l_23`,sum(`user_lines`.`l_night`) AS `l_night`,sum(`user_lines`.`l_morning`) AS `l_morning`,sum(`user_lines`.`l_afternoon`) AS `l_afternoon`,sum(`user_lines`.`l_evening`) AS `l_evening`,sum(`user_lines`.`l_total`) AS `l_total`,sum(`user_lines`.`l_mon_night`) AS `l_mon_night`,sum(`user_lines`.`l_mon_morning`) AS `l_mon_morning`,sum(`user_lines`.`l_mon_afternoon`) AS `l_mon_afternoon`,sum(`user_lines`.`l_mon_evening`) AS `l_mon_evening`,sum(`user_lines`.`l_tue_night`) AS `l_tue_night`,sum(`user_lines`.`l_tue_morning`) AS `l_tue_morning`,sum(`user_lines`.`l_tue_afternoon`) AS `l_tue_afternoon`,sum(`user_lines`.`l_tue_evening`) AS `l_tue_evening`,sum(`user_lines`.`l_wed_night`) AS `l_wed_night`,sum(`user_lines`.`l_wed_morning`) AS `l_wed_morning`,sum(`user_lines`.`l_wed_afternoon`) AS `l_wed_afternoon`,sum(`user_lines`.`l_wed_evening`) AS `l_wed_evening`,sum(`user_lines`.`l_thu_night`) AS `l_thu_night`,sum(`user_lines`.`l_thu_morning`) AS `l_thu_morning`,sum(`user_lines`.`l_thu_afternoon`) AS `l_thu_afternoon`,sum(`user_lines`.`l_thu_evening`) AS `l_thu_evening`,sum(`user_lines`.`l_fri_night`) AS `l_fri_night`,sum(`user_lines`.`l_fri_morning`) AS `l_fri_morning`,sum(`user_lines`.`l_fri_afternoon`) AS `l_fri_afternoon`,sum(`user_lines`.`l_fri_evening`) AS `l_fri_evening`,sum(`user_lines`.`l_sat_night`) AS `l_sat_night`,sum(`user_lines`.`l_sat_morning`) AS `l_sat_morning`,sum(`user_lines`.`l_sat_afternoon`) AS `l_sat_afternoon`,sum(`user_lines`.`l_sat_evening`) AS `l_sat_evening`,sum(`user_lines`.`l_sun_night`) AS `l_sun_night`,sum(`user_lines`.`l_sun_morning`) AS `l_sun_morning`,sum(`user_lines`.`l_sun_afternoon`) AS `l_sun_afternoon`,sum(`user_lines`.`l_sun_evening`) AS `l_sun_evening`,sum(`user_lines`.`urls`) AS `urls`,sum(`user_lines`.`words`) AS `words`,sum(`user_lines`.`characters`) AS `characters`,sum(`user_lines`.`monologues`) AS `monologues`,max(`user_lines`.`topmonologue`) AS `topmonologue`,sum(`user_lines`.`slaps`) AS `slaps`,sum(`user_lines`.`slapped`) AS `slapped`,sum(`user_lines`.`exclamations`) AS `exclamations`,sum(`user_lines`.`questions`) AS `questions`,sum(`user_lines`.`actions`) AS `actions`,sum(`user_lines`.`uppercased`) AS `uppercased`,max(`user_lines`.`lasttalked`) AS `lasttalked` from (`user_lines` join `user_status` on((`user_lines`.`uid` = `user_status`.`uid`))) group by `user_status`.`ruid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_q_activity_by_day`
--

/*!50001 DROP TABLE IF EXISTS `v_q_activity_by_day`*/;
/*!50001 DROP VIEW IF EXISTS `v_q_activity_by_day`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_q_activity_by_day` AS select `user_status`.`ruid` AS `ruid`,`user_activity`.`date` AS `date`,sum(`user_activity`.`l_night`) AS `l_night`,sum(`user_activity`.`l_morning`) AS `l_morning`,sum(`user_activity`.`l_afternoon`) AS `l_afternoon`,sum(`user_activity`.`l_evening`) AS `l_evening`,sum(`user_activity`.`l_total`) AS `l_total` from (`user_activity` join `user_status` on((`user_activity`.`uid` = `user_status`.`uid`))) group by `user_status`.`ruid`,`user_activity`.`date` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_q_activity_by_month`
--

/*!50001 DROP TABLE IF EXISTS `v_q_activity_by_month`*/;
/*!50001 DROP VIEW IF EXISTS `v_q_activity_by_month`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_q_activity_by_month` AS select `user_status`.`ruid` AS `ruid`,date_format(`user_activity`.`date`,'%Y-%m') AS `date`,sum(`user_activity`.`l_night`) AS `l_night`,sum(`user_activity`.`l_morning`) AS `l_morning`,sum(`user_activity`.`l_afternoon`) AS `l_afternoon`,sum(`user_activity`.`l_evening`) AS `l_evening`,sum(`user_activity`.`l_total`) AS `l_total` from (`user_activity` join `user_status` on((`user_activity`.`uid` = `user_status`.`uid`))) group by `user_status`.`ruid`,date_format(`user_activity`.`date`,'%Y-%m') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_q_activity_by_year`
--

/*!50001 DROP TABLE IF EXISTS `v_q_activity_by_year`*/;
/*!50001 DROP VIEW IF EXISTS `v_q_activity_by_year`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_q_activity_by_year` AS select `user_status`.`ruid` AS `ruid`,year(`user_activity`.`date`) AS `date`,sum(`user_activity`.`l_night`) AS `l_night`,sum(`user_activity`.`l_morning`) AS `l_morning`,sum(`user_activity`.`l_afternoon`) AS `l_afternoon`,sum(`user_activity`.`l_evening`) AS `l_evening`,sum(`user_activity`.`l_total`) AS `l_total` from (`user_activity` join `user_status` on((`user_activity`.`uid` = `user_status`.`uid`))) group by `user_status`.`ruid`,year(`user_activity`.`date`) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_q_events`
--

/*!50001 DROP TABLE IF EXISTS `v_q_events`*/;
/*!50001 DROP VIEW IF EXISTS `v_q_events`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_q_events` AS select `mv_events`.`ruid` AS `ruid`,`mv_events`.`m_op` AS `m_op`,`mv_events`.`m_opped` AS `m_opped`,`mv_events`.`m_voice` AS `m_voice`,`mv_events`.`m_voiced` AS `m_voiced`,`mv_events`.`m_deop` AS `m_deop`,`mv_events`.`m_deopped` AS `m_deopped`,`mv_events`.`m_devoice` AS `m_devoice`,`mv_events`.`m_devoiced` AS `m_devoiced`,`mv_events`.`joins` AS `joins`,`mv_events`.`parts` AS `parts`,`mv_events`.`quits` AS `quits`,`mv_events`.`kicks` AS `kicks`,`mv_events`.`kicked` AS `kicked`,`mv_events`.`nickchanges` AS `nickchanges`,`mv_events`.`topics` AS `topics`,`mv_ex_kicks`.`ex_kicks` AS `ex_kicks`,`mv_ex_kicked`.`ex_kicked` AS `ex_kicked` from ((`mv_events` left join `mv_ex_kicks` on((`mv_events`.`ruid` = `mv_ex_kicks`.`ruid`))) left join `mv_ex_kicked` on((`mv_events`.`ruid` = `mv_ex_kicked`.`ruid`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_q_lines`
--

/*!50001 DROP TABLE IF EXISTS `v_q_lines`*/;
/*!50001 DROP VIEW IF EXISTS `v_q_lines`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_q_lines` AS select `mv_lines`.`ruid` AS `ruid`,`mv_lines`.`l_00` AS `l_00`,`mv_lines`.`l_01` AS `l_01`,`mv_lines`.`l_02` AS `l_02`,`mv_lines`.`l_03` AS `l_03`,`mv_lines`.`l_04` AS `l_04`,`mv_lines`.`l_05` AS `l_05`,`mv_lines`.`l_06` AS `l_06`,`mv_lines`.`l_07` AS `l_07`,`mv_lines`.`l_08` AS `l_08`,`mv_lines`.`l_09` AS `l_09`,`mv_lines`.`l_10` AS `l_10`,`mv_lines`.`l_11` AS `l_11`,`mv_lines`.`l_12` AS `l_12`,`mv_lines`.`l_13` AS `l_13`,`mv_lines`.`l_14` AS `l_14`,`mv_lines`.`l_15` AS `l_15`,`mv_lines`.`l_16` AS `l_16`,`mv_lines`.`l_17` AS `l_17`,`mv_lines`.`l_18` AS `l_18`,`mv_lines`.`l_19` AS `l_19`,`mv_lines`.`l_20` AS `l_20`,`mv_lines`.`l_21` AS `l_21`,`mv_lines`.`l_22` AS `l_22`,`mv_lines`.`l_23` AS `l_23`,`mv_lines`.`l_night` AS `l_night`,`mv_lines`.`l_morning` AS `l_morning`,`mv_lines`.`l_afternoon` AS `l_afternoon`,`mv_lines`.`l_evening` AS `l_evening`,`mv_lines`.`l_total` AS `l_total`,`mv_lines`.`l_mon_night` AS `l_mon_night`,`mv_lines`.`l_mon_morning` AS `l_mon_morning`,`mv_lines`.`l_mon_afternoon` AS `l_mon_afternoon`,`mv_lines`.`l_mon_evening` AS `l_mon_evening`,`mv_lines`.`l_tue_night` AS `l_tue_night`,`mv_lines`.`l_tue_morning` AS `l_tue_morning`,`mv_lines`.`l_tue_afternoon` AS `l_tue_afternoon`,`mv_lines`.`l_tue_evening` AS `l_tue_evening`,`mv_lines`.`l_wed_night` AS `l_wed_night`,`mv_lines`.`l_wed_morning` AS `l_wed_morning`,`mv_lines`.`l_wed_afternoon` AS `l_wed_afternoon`,`mv_lines`.`l_wed_evening` AS `l_wed_evening`,`mv_lines`.`l_thu_night` AS `l_thu_night`,`mv_lines`.`l_thu_morning` AS `l_thu_morning`,`mv_lines`.`l_thu_afternoon` AS `l_thu_afternoon`,`mv_lines`.`l_thu_evening` AS `l_thu_evening`,`mv_lines`.`l_fri_night` AS `l_fri_night`,`mv_lines`.`l_fri_morning` AS `l_fri_morning`,`mv_lines`.`l_fri_afternoon` AS `l_fri_afternoon`,`mv_lines`.`l_fri_evening` AS `l_fri_evening`,`mv_lines`.`l_sat_night` AS `l_sat_night`,`mv_lines`.`l_sat_morning` AS `l_sat_morning`,`mv_lines`.`l_sat_afternoon` AS `l_sat_afternoon`,`mv_lines`.`l_sat_evening` AS `l_sat_evening`,`mv_lines`.`l_sun_night` AS `l_sun_night`,`mv_lines`.`l_sun_morning` AS `l_sun_morning`,`mv_lines`.`l_sun_afternoon` AS `l_sun_afternoon`,`mv_lines`.`l_sun_evening` AS `l_sun_evening`,`mv_lines`.`urls` AS `urls`,`mv_lines`.`words` AS `words`,`mv_lines`.`characters` AS `characters`,`mv_lines`.`monologues` AS `monologues`,`mv_lines`.`topmonologue` AS `topmonologue`,`mv_activedays`.`activedays` AS `activedays`,`mv_lines`.`slaps` AS `slaps`,`mv_lines`.`slapped` AS `slapped`,`mv_lines`.`exclamations` AS `exclamations`,`mv_lines`.`questions` AS `questions`,`mv_lines`.`actions` AS `actions`,`mv_lines`.`uppercased` AS `uppercased`,`mv_quote`.`quote` AS `quote`,`mv_ex_exclamations`.`ex_exclamations` AS `ex_exclamations`,`mv_ex_questions`.`ex_questions` AS `ex_questions`,`mv_ex_actions`.`ex_actions` AS `ex_actions`,`mv_ex_uppercased`.`ex_uppercased` AS `ex_uppercased`,`mv_lines`.`lasttalked` AS `lasttalked` from ((((((`mv_lines` left join `mv_activedays` on((`mv_lines`.`ruid` = `mv_activedays`.`ruid`))) left join `mv_quote` on((`mv_lines`.`ruid` = `mv_quote`.`ruid`))) left join `mv_ex_exclamations` on((`mv_lines`.`ruid` = `mv_ex_exclamations`.`ruid`))) left join `mv_ex_questions` on((`mv_lines`.`ruid` = `mv_ex_questions`.`ruid`))) left join `mv_ex_actions` on((`mv_lines`.`ruid` = `mv_ex_actions`.`ruid`))) left join `mv_ex_uppercased` on((`mv_lines`.`ruid` = `mv_ex_uppercased`.`ruid`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_q_smileys`
--

/*!50001 DROP TABLE IF EXISTS `v_q_smileys`*/;
/*!50001 DROP VIEW IF EXISTS `v_q_smileys`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_q_smileys` AS select `user_status`.`ruid` AS `ruid`,sum(`user_smileys`.`s_01`) AS `s_01`,sum(`user_smileys`.`s_02`) AS `s_02`,sum(`user_smileys`.`s_03`) AS `s_03`,sum(`user_smileys`.`s_04`) AS `s_04`,sum(`user_smileys`.`s_05`) AS `s_05`,sum(`user_smileys`.`s_06`) AS `s_06`,sum(`user_smileys`.`s_07`) AS `s_07`,sum(`user_smileys`.`s_08`) AS `s_08`,sum(`user_smileys`.`s_09`) AS `s_09`,sum(`user_smileys`.`s_10`) AS `s_10`,sum(`user_smileys`.`s_11`) AS `s_11`,sum(`user_smileys`.`s_12`) AS `s_12`,sum(`user_smileys`.`s_13`) AS `s_13`,sum(`user_smileys`.`s_14`) AS `s_14`,sum(`user_smileys`.`s_15`) AS `s_15`,sum(`user_smileys`.`s_16`) AS `s_16`,sum(`user_smileys`.`s_17`) AS `s_17`,sum(`user_smileys`.`s_18`) AS `s_18`,sum(`user_smileys`.`s_19`) AS `s_19`,sum(`user_smileys`.`s_20`) AS `s_20`,sum(`user_smileys`.`s_21`) AS `s_21`,sum(`user_smileys`.`s_22`) AS `s_22`,sum(`user_smileys`.`s_23`) AS `s_23`,sum(`user_smileys`.`s_24`) AS `s_24`,sum(`user_smileys`.`s_25`) AS `s_25`,sum(`user_smileys`.`s_26`) AS `s_26`,sum(`user_smileys`.`s_27`) AS `s_27`,sum(`user_smileys`.`s_28`) AS `s_28`,sum(`user_smileys`.`s_29`) AS `s_29`,sum(`user_smileys`.`s_30`) AS `s_30`,sum(`user_smileys`.`s_31`) AS `s_31`,sum(`user_smileys`.`s_32`) AS `s_32`,sum(`user_smileys`.`s_33`) AS `s_33`,sum(`user_smileys`.`s_34`) AS `s_34`,sum(`user_smileys`.`s_35`) AS `s_35`,sum(`user_smileys`.`s_36`) AS `s_36`,sum(`user_smileys`.`s_37`) AS `s_37`,sum(`user_smileys`.`s_38`) AS `s_38`,sum(`user_smileys`.`s_39`) AS `s_39`,sum(`user_smileys`.`s_40`) AS `s_40`,sum(`user_smileys`.`s_41`) AS `s_41`,sum(`user_smileys`.`s_42`) AS `s_42`,sum(`user_smileys`.`s_43`) AS `s_43`,sum(`user_smileys`.`s_44`) AS `s_44`,sum(`user_smileys`.`s_45`) AS `s_45`,sum(`user_smileys`.`s_46`) AS `s_46`,sum(`user_smileys`.`s_47`) AS `s_47`,sum(`user_smileys`.`s_48`) AS `s_48`,sum(`user_smileys`.`s_49`) AS `s_49`,sum(`user_smileys`.`s_50`) AS `s_50` from (`user_smileys` join `user_status` on((`user_smileys`.`uid` = `user_status`.`uid`))) group by `user_status`.`ruid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_quote`
--

/*!50001 DROP TABLE IF EXISTS `v_quote`*/;
/*!50001 DROP VIEW IF EXISTS `v_quote`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_quote` AS select `v_quote_1`.`ruid` AS `ruid`,`v_quote_1`.`quote` AS `quote` from (`v_quote_1` join `v_quote_2` on((`v_quote_1`.`ruid` = `v_quote_2`.`ruid`))) where (`v_quote_1`.`lasttalked` = `v_quote_2`.`lasttalked`) group by `v_quote_1`.`ruid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_quote_1`
--

/*!50001 DROP TABLE IF EXISTS `v_quote_1`*/;
/*!50001 DROP VIEW IF EXISTS `v_quote_1`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_quote_1` AS select `user_status`.`ruid` AS `ruid`,`user_lines`.`quote` AS `quote`,`user_lines`.`lasttalked` AS `lasttalked` from (`user_lines` join `user_status` on((`user_lines`.`uid` = `user_status`.`uid`))) where (`user_lines`.`quote` <> '') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_quote_2`
--

/*!50001 DROP TABLE IF EXISTS `v_quote_2`*/;
/*!50001 DROP VIEW IF EXISTS `v_quote_2`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=CURRENT_USER SQL SECURITY DEFINER */
/*!50001 VIEW `v_quote_2` AS select `v_quote_1`.`ruid` AS `ruid`,max(`v_quote_1`.`lasttalked`) AS `lasttalked` from `v_quote_1` group by `v_quote_1`.`ruid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2011-05-17  1:07:00
