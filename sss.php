<?php

/**
 * This script should be called from the commandline with PHP version 5.3.0 or up.
 *
 * usage: php sss.php -i <log_file>
 *        php sss.php -o <html_file>
 *
 * Make sure to use absolute paths when specifying either <log_file> or <html_file>.
 *
 * The options are:
 * 	-i	parse <log_file>
 * 	-o	generate statistics and write those to <html_file>
 */

if (substr(phpversion(), 0, 3) != '5.3')
	exit('unsupported php version: '.phpversion()."\n");

if (!@include('settings.php'))
	exit('cannot open: '.dirname(__FILE__).'/settings.php'."\n");

if (count($argv) != 3 || !preg_match('/^-[io]$/', $argv[1]))
	exit('usage: php '.basename(__FILE__).' -i <log_file>'."\n".'       php '.basename(__FILE__).' -o <html_file>'."\n");

define('DB_HOST', $db_host);
define('DB_PORT', $db_port);
define('DB_USER', $db_user);
define('DB_PASS', $db_pass);
define('DB_NAME', $db_name);
date_default_timezone_set($timezone);

function __autoload($class)
{
	require_once($class.'.class.php');
}

if ($argv[1] == '-i') {
	$logfile = preg_replace('/yesterday$/', date($date_format, strtotime('yesterday')), $argv[2]);
	$tmp = substr($logfile, strlen($logfile) - 8);
	define('DATE', date('Y-m-d', strtotime($tmp)));
	define('DAY', strtolower(date('D', strtotime($tmp))));

	$tmp = 'Parser_'.$logfile_format;
	$sss_parser = new $tmp();
	$sss_parser->setValue('outputLevel', $outputLevel);
	$sss_parser->setValue('wordTracking', $wordTracking);
	$sss_parser->parseLog($logfile);

	if ($writeData)
		$sss_parser->writeData();

	if ($doMaintenance) {
		$tmp = 'Maintenance_'.$database_server;
		$sss_maintenance = new $tmp();
		$sss_maintenance->setValue('outputLevel', $outputLevel);
		$sss_maintenance->setValue('sanitisationDay', $sanitisationDay);
		$sss_maintenance->doMaintenance();
	}
} elseif ($argv[1] == '-o') {
	$tmp = 'HTML_'.$database_server;
	$sss_output = new $tmp();
	$sss_output->setValue('channel', $channel);

	if ($handle = @fopen($argv[2], 'wb')) {
		fwrite($handle, $sss_output->makeHTML());
	} else
		exit('cannot open: '.$argv[2]."\n");

	fclose($handle);
}

?>
