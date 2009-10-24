<?php

/**
 * Copyright (c) 2009, Jos de Ruijter <jos@dutnie.nl>
 *
 * Permission to use, copy, modify, and/or distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

/**
 * This script should be called from the commandline with PHP version 5.3.0 or up.
 *
 * usage: php sss.php {-i <log_file> [-o <html_file>] | -m | -o <html_file>}
 *
 * Make sure to use absolute paths when specifying either <log_file> or <html_file>.
 *
 * The options are:
 *	-i	parse <log_file>
 *		this includes performing maintenance routines on the database if
 *		the "doMaintenance" option is set to TRUE in settings.php
 *	-m	perform maintenance routines on the database
 *	-o	generate statistics and output to <html_file>
 */

if (substr(phpversion(), 0, 3) != '5.3')
	exit('unsupported php version: '.phpversion()."\n");

if (!@include('settings.php'))
	exit('cannot open: '.dirname(__FILE__).'/settings.php'."\n");

if (!(count($argv) == 2 && $argv[1] == '-m') && !(count($argv) == 3 && ($argv[1] == '-i' || $argv[1] == '-o')) && !(count($argv) == 5 && $argv[1] == '-i' && $argv[3] == '-o'))
	exit('usage: php '.basename(__FILE__).' {-i <log_file> [-o <html_file>] | -m | -o <html_file>}'."\n");

if ($cfg['database_server'] == 'MySQL' && !extension_loaded('mysqli'))
	exit('the mysqli extension isn\'t loaded'."\n");

define('DB_HOST', $cfg['db_host']);
define('DB_PORT', $cfg['db_port']);
define('DB_USER', $cfg['db_user']);
define('DB_PASS', $cfg['db_pass']);
define('DB_NAME', $cfg['db_name']);
date_default_timezone_set($cfg['timezone']);

function __autoload($class)
{
	require_once($class.'.class.php');
}

function doMaintenance($cfg)
{
	$tmp = 'Maintenance_'.$cfg['database_server'];
	$sss_maintenance = new $tmp();
	$sss_maintenance->setValue('outputLevel', $cfg['outputLevel']);
	$sss_maintenance->setValue('sanitisationDay', $cfg['sanitisationDay']);

	/**
	 * Place your own $sss_maintenance->setValue lines here
	 */

	$sss_maintenance->doMaintenance();
}

function input($cfg, $log_file)
{
	$logfile = preg_replace('/yesterday$/', date($cfg['date_format'], strtotime('yesterday')), $log_file);
	$tmp = substr($logfile, strlen($logfile) - 8);

	if (date('Ymd', strtotime($tmp)) == date('Ymd')) {
		echo 'The logfile you are trying to parse appears to be of today. If logging'."\n"
		   . 'hasn\'t completed for today it is advisable to skip parsing this file'."\n"
		   . 'until tomorrow, when it is complete.'."\n"
		   . 'Skip \''.$log_file.'\'? [yes] ';
		$yn = trim(fgets(STDIN));

		if (empty($yn) || $yn == 'y' || $yn == 'yes')
			exit;
	}

	define('DATE', date('Y-m-d', strtotime($tmp)));
	define('DAY', strtolower(date('D', strtotime($tmp))));
	$tmp = 'Parser_'.$cfg['logfile_format'];
	$sss_parser = new $tmp();
	$sss_parser->setValue('outputLevel', $cfg['outputLevel']);
	$sss_parser->setValue('wordTracking', $cfg['wordTracking']);

	/**
	 * Place your own $sss_parser->setValue lines here
	 */

	$sss_parser->parseLog($logfile);

	if ($cfg['writeData'])
		$sss_parser->writeData();

	if ($cfg['doMaintenance'])
		doMaintenance($cfg);
}

function output($cfg, $html_file)
{
	$tmp = 'HTML_'.$cfg['database_server'];
	$sss_output = new $tmp();
	$sss_output->setValue('channel', $cfg['channel']);

	/**
	 * Place your own $sss_output->setValue lines here
	 */

	$fp = @fopen($html_file, 'wb');

	if ($fp) {
		fwrite($fp, $sss_output->makeHTML());
		fclose($fp);
	} else
		exit('cannot open: '.$html_file."\n");
}

if (count($argv) == 2)
	doMaintenance($cfg);
elseif (count($argv) == 3) {
	if ($argv[1] == '-i') {
		input($cfg, $argv[2]);
	} elseif ($argv[1] == '-o') {
		output($cfg, $argv[2]);
	}
} elseif (count($argv) == 5) {
	input($cfg, $argv[2]);
	output($cfg, $argv[4]);
}

?>
