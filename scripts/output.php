#!/usr/local/bin/php5
<?php

/*
 * Output wrapper, this script will:
 * - Read your settings from settings.php
 * - Generate a HTML page with statistics for you channel
 *
 * You normally only have to edit the settings.php file for things to work.
 */

if (!@include('settings.php'))
        exit('Settings file (settings.php) cannot be opened, it should be in the same directory as this script..'."\n");

date_default_timezone_set($timezone);
$date = date('Y-m-d');
$day = strtolower(date('D'));

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
$sss_output = new HTML_MySQL();
$sss_output->setValue('channel', $channel);
$sss_output->makeHTML();

?>
