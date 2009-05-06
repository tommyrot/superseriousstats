#!/usr/local/bin/php5
<?php

/*
 * Input wrapper, this script will:
 * - Read your settings from settings.php
 * - Parse a logfile
 * - Store data in the database
 * - Do some maintenance on the database and data in it
 *
 * You normally only have to edit the settings.php file for things to work.
 */

if (!@include('settings.php'))
        exit('Settings file (settings.php) cannot be opened, it should be in the same directory as this script..'."\n");

if (!isset($argv[1]))
        exit('Usage: ./input.php /full/path/to/logfile'."\n");

date_default_timezone_set($timezone);

// Extract date and day from the filename. Be crafty and replace ".yesterday" with yesterday's date! Useful when running this script from a cronjob.
$logfile = preg_replace('/yesterday$/', date('Ymd', strtotime('yesterday')), $argv[1]);

if (!preg_match('/(19[7-9][0-9]|20[0-9]{2})(0[1-9]|1[0-2])(0[1-9]|[12][0-9]|3[01])$/', $logfile))
        exit('The logfile doesn\'t appear to have a valid date in it\'s filename.'."\n");

$logfile_date = substr($logfile, strlen($logfile) - 8);
$date = date('Y-m-d', strtotime($logfile_date));
$day = strtolower(date('D', strtotime($logfile_date)));

// Define some constants used throughout the scripts.
define('PATH', $path);
define('MYSQL_HOST', $db_host);
define('MYSQL_USER', $db_user);
define('MYSQL_PASS', $db_pass);
define('MYSQL_DB', $db_name);
define('DATE', $date);
define('DAY', $day);

function __autoload($class)
{
        require_once(rtrim(PATH, '/').'/'.$class.'.class.php');
}

// Get this baby running!
$sss_parser = new Parser_Eggdrop();
$sss_parser->setValue('outputLevel', $outputLevel);
$sss_parser->setValue('wordTracking', $wordTracking);
$sss_parser->parseLog($logfile);

if ($writeData)
        $sss_parser->writeData();

if ($doMaintenance) {
        $sss_maintenance = new Maintenance_MySQL();
	$sss_maintenance->setValue('outputLevel', $outputLevel);
	$sss_maintenance->setValue('sanitisationDay', $sanitisationDay);
	$sss_maintenance->doMaintenance();
}

?>
