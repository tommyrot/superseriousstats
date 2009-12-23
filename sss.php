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

$man = 'usage: php sss.php [-i <logfile|logdir> [-o <statspage> [-b <outputbits>]]]'."\n"
     . '       php sss.php [-o <statspage> [-b <outputbits>]]'."\n"
     . '       php sss.php [-m]'."\n\n"
     . 'the options are:'."\n"
     . '       -b	set <outputbits>, add up the bits corresponding to the sections'."\n"
     . '		you want to be included on the statspage:'."\n"
     . '		     1  activity'."\n"
     . '		     2  general chat'."\n"
     . '		     4  modes'."\n"
     . '		     8  events'."\n"
     . '		    16  smileys'."\n"
     . '		if this option is omitted all sections will be included'."\n"
     . '	-i	input <logfile>, or all logfiles in <logdir>'."\n"
     . '		database maintenance will be run after parsing the last logfile'."\n"
     . '		unless "doMaintenance" is set to FALSE in settings.php'."\n"
     . '	-m	perform maintenance routines on the database'."\n"
     . '	-o	generate statistics and output to <statspage>'."\n";

if (substr(phpversion(), 0, 3) != '5.3') {
	exit('unsupported php version: '.phpversion()."\n");
}

if (!@include('settings.php')) {
	exit('cannot open: '.realpath(dirname(__FILE__).'/settings.php')."\n");
}

if ($cfg['db_server'] == 'MySQL' && !extension_loaded('mysqli')) {
	exit('the mysqli extension isn\'t loaded'."\n");
}

if (!(count($argv) == 2 && $argv[1] == '-m') &&
    !(count($argv) == 3 && ($argv[1] == '-i' || $argv[1] == '-o')) &&
    !(count($argv) == 5 && (($argv[1] == '-i' && $argv[3] == '-o') || ($argv[1] == '-o' && $argv[3] == '-b'))) &&
    !(count($argv) == 7 && $argv[1] == '-i' && $argv[3] == '-o' && $argv[5] == '-b')) {
	exit($man);
}

define('DB_HOST', $cfg['db_host']);
define('DB_PORT', $cfg['db_port']);
define('DB_USER', $cfg['db_user']);
define('DB_PASS', $cfg['db_pass']);
define('DB_NAME', $cfg['db_name']);
date_default_timezone_set($cfg['timezone']);

/**
 * Class autoloader.
 */
function __autoload($class)
{
	require_once($class.'.class.php');
}

/**
 * Run the database maintenance scripts. Userstats of all linked nicks will be accumulated, sanity checks will be done on the userstatuses and more.
 */
function doMaintenance($cfg)
{
	$maintenance_class = 'Maintenance_'.$cfg['db_server'];
	$maintenance = new $maintenance_class();

	if (isset($cfg['maintenance'])) {
		foreach ($cfg['maintenance'] as $key => $value) {
			$maintenance->setValue($key, $value);
		}
	}

	$maintenance->doMaintenance();
}

/**
 * Parse a logfile, or all logfiles in the given logdir.
 */
function input($cfg, $logfile)
{
	if (($path = realpath($logfile)) !== FALSE) {
		if (is_dir($path)) {
			if (($dh = @opendir($path)) !== FALSE) {
				while (($file = readdir($dh)) !== FALSE) {
					$logfiles[] = realpath($path.'/'.$file);
				}

				closedir($dh);
			} else {
				exit('cannot open: '.$path."\n");
			}
		} else {
			$logfiles[] = $path;
		}

		sort($logfiles);

		foreach ($logfiles as $logfile) {
			echo $logfile."\n";
			if ((empty($cfg['logfilePrefix']) || stripos(basename($logfile), $cfg['logfilePrefix']) !== FALSE) && (empty($cfg['logfileSuffix']) || stripos(basename($logfile), $cfg['logfileSuffix']) !== FALSE)) {
				echo 'ja'."\n";
				$logfile = preg_replace('/YESTERDAY/', date($cfg['dateFormat'], strtotime('yesterday')), $logfile);
				$date = str_replace(array($cfg['logfilePrefix'], $cfg['logfileSuffix']), '', basename($logfile));
				$date = date('Y-m-d', strtotime($date));
				$day = strtolower(date('D', strtotime($date)));

				if ($date == date('Y-m-d')) {
					echo 'The logfile you are trying to parse appears to be of today. If logging'."\n"
					   . 'hasn\'t completed for today it is advisable to skip parsing this file'."\n"
					   . 'until tomorrow, when it is complete.'."\n"
					   . 'Skip \''.basename($logfile).'\'? [yes] ';
					$yn = trim(fgets(STDIN));

					if (empty($yn) || $yn == 'y' || $yn == 'yes') {
						break;
					}
				}

				$parser_class = 'Parser_'.$cfg['logfileFormat'];
				$parser = new $parser_class();
				$parser->setValue('date', $date);
				$parser->setValue('day', $day);

				if (isset($cfg['parser'])) {
					foreach ($cfg['parser'] as $key => $value) {
						$parser->setValue($key, $value);
					}
				}

				$parser->parseLog($logfile);

				if ($cfg['writeData']) {
					$parser->writeData();
				}
			}
		}

		if ($cfg['doMaintenance']) {
			doMaintenance($cfg);
		}
	} else {
		exit('no such file: '.$logfile."\n");
	}
}

/**
 * Create the statspage.
 */
function output($cfg, $statspage, $outputbits = NULL)
{
	$HTML_class = 'HTML_'.$cfg['db_server'];
	$HTML = new $HTML_class();

	foreach ($cfg['HTML'] as $key => $value) {
		$HTML->setValue($key, $value);
	}

	if (is_numeric($outputbits)) {
		$outputbits = intval($outputbits);
		$HTML->setValue('outputbits', $outputbits);
	}

	if (($fp = @fopen($statspage, 'wb')) !== FALSE) {
		fwrite($fp, $HTML->makeHTML());
		fclose($fp);
	} else {
		exit('cannot open: '.$statspage."\n");
	}
}

/**
 * Same checks we already used at the beginning of the script.
 */
if (count($argv) == 2 && $argv[1] == '-m') {
	doMaintenance($cfg);
} elseif (count($argv) == 3 && ($argv[1] == '-i' || $argv[1] == '-o')) {
	if ($argv[1] == '-i') {
		input($cfg, $argv[2]);
	} else {
		output($cfg, $argv[2]);
	}
} elseif (count($argv) == 5 && (($argv[1] == '-i' && $argv[3] == '-o') || ($argv[1] == '-o' && $argv[3] == '-b'))) {
	if ($argv[1] == '-i') {
		input($cfg, $argv[2]);
		output($cfg, $argv[4]);
	} else {
		output($cfg, $argv[2], $argv[4]);
	}
} elseif (count($argv) == 7 && $argv[1] == '-i' && $argv[3] == '-o' && $argv[5] == '-b') {
	input($cfg, $argv[2]);
	output($cfg, $argv[2], $argv[4]);
}

?>
