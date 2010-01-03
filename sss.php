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

final class sss
{
	/**
	 * Default settings, can be overridden in the config file.
	 */
	private $settings = array('doMaintenance' => TRUE
				 ,'outputLevel' => 1
				 ,'writeDate' => TRUE);
	/**
	 * Required settings, script will exit if these options aren't present in the config file.
	 */
	private $settings_required = array('channel', 'timezone', 'db_server', 'db_host', 'db_port', 'db_user', 'db_pass', 'db_name', 'logfileFormat', 'logfilePrefix', 'logfileDateFormat', 'logfileSuffix');

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		/**
		 * Use UTC until user specified timezone is loaded.
		 */
		date_default_timezone_set('UTC');

		/**
		 * Read options from the command line.
		 */
		if (($options = getopt('b:c:i:mo:')) !== FALSE) {
			if (empty($options)) {
				$this->printManual();
			}

			if (array_key_exists('c', $options)) {
				$this->readConfig($options['c']);
			} else {
				$this->readConfig(dirname(__FILE__).'/sss.conf');
			}

			if (array_key_exists('b', $options)) {
				$this->settings['outputbits'] = $options['b'];
			}

			if (array_key_exists('i', $options)) {
				$this->parseLog($options['i']);
			}

			if (array_key_exists('m', $options)) {
				$this->doMaintenance();
			}

			if (array_key_exists('o', $options)) {
				$this->makeHTML($options['o']);
			}
		} else {
			$this->printManual();
		}
	}

	/**
	 * Run the database maintenance scripts. Userstats of all linked nicks will be accumulated, sanity checks will be done on the userstatuses and more.
	 */
	private function doMaintenance()
	{
		$maintenance_class = 'Maintenance_'.$this->settings['db_server'];
		$path = dirname(__FILE__).'/'.$maintenance_class.'.class.php';

		if (($rp = realpath($path)) !== FALSE) {
			require($rp);
		} else {
			$this->output('critical', 'doMaintenance(): no such file: \''.$path.'\'');
		}

		$maintenance = new $maintenance_class($this->settings);
		$maintenance->doMaintenance();
	}

	/**
	 * Create the statspage.
	 */
	private function makeHTML($file)
	{
		$HTML_class = 'HTML_'.$this->settings['db_server'];
		require(realpath(dirname(__FILE__).'/'.$HTML_class));
		$HTML = new $HTML_class($this->settings);

		if (($fp = @fopen($file, 'wb')) !== FALSE) {
			fwrite($fp, $HTML->makeHTML());
			fclose($fp);
		} else {
			$this->output('critical', 'makeHTML(): failed to open file: \''.$file.'\'');
		}
	}

	/**
	 * Output given messages to the console.
	 */
	private function output($type, $msg)
	{
		$dateTime = date('M d H:i:s');

		if (substr($dateTime, 4, 1) === '0') {
			$dateTime = substr_replace($dateTime, ' ', 4, 1);
		}

		switch ($type) {
			case 'debug':
				if ($this->settings['outputLevel'] >= 4) {
					echo $dateTime.' [debug] '.$msg."\n";
				}

				break;
			case 'notice':
				if ($this->settings['outputLevel'] >= 3) {
					echo $dateTime.' [notice] '.$msg."\n";
				}

				break;
			case 'warning':
				if ($this->settings['outputLevel'] >= 2) {
					echo $dateTime.' [warning] '.$msg."\n";
				}

				break;
			case 'critical':
				if ($this->settings['outputLevel'] >= 1) {
					echo $dateTime.' [critical] '.$msg."\n";
				}

				exit;
		}
	}

	/**
	 * Parse a logfile, or all logfiles in the given logdir.
	 */
	private function parseLog($path)
	{
		$path = preg_replace('/YESTERDAY/', date($cfg['dateFormat'], strtotime('yesterday')), $path);

		if (($rp = realpath($path)) !== FALSE) {
			if (is_dir($rp)) {
				if (($dh = @opendir($rp)) !== FALSE) {
					while (($file = readdir($dh)) !== FALSE) {
						$logfiles[] = realpath($rp.'/'.$file);
					}

					closedir($dh);
				} else {
					$this->output('critical', 'parseLog(): failed to open directory: \''.$rp.'\'');
				}
			} else {
				$logfiles[] = $rp;
			}

			sort($logfiles);

			foreach ($logfiles as $logfile) {
				if ((empty($this->settings['logfilePrefix']) || stripos(basename($logfile), $this->settings['logfilePrefix']) !== FALSE) && (empty($this->settings['logfileSuffix']) || stripos(basename($logfile), $this->settings['logfileSuffix']) !== FALSE)) {
					$date = str_replace(array($cfg['logfilePrefix'], $cfg['logfileSuffix']), '', basename($logfile));
					$date = date('Y-m-d', strtotime($date));
					$day = strtolower(date('D', strtotime($date)));

					if ($date == date('Y-m-d')) {
						echo 'The logfile you are about to parse appears to be of today. It is recommended'."\n"
						   . 'to skip this file until tomorrow when logging will be completed.'."\n"
						   . 'Skip \''.basename($logfile).'\'? [yes] ';
						$yn = trim(fgets(STDIN));

						if (empty($yn) || $yn == 'y' || $yn == 'yes') {
							break;
						}
					}

					$parser_class = 'Parser_'.$this->settings['logfileFormat'];
					require(realpath(dirname(__FILE__).'/'.$parser_class));
					$parser = new $parser_class($this->settings);
					$parser->setValue('date', $date);
					$parser->setValue('day', $day);
					$parser->parseLog($logfile);

					if ($this->settings['writeData']) {
						$parser->writeData();
					}
				}
			}

			if ($this->settings['doMaintenance']) {
				$this->doMaintenance();
			}
		} else {
			$this->output('critical', 'parseLog(): no such file: \''.$path.'\'');
		}
	}

	/**
	 * Print the manual and exit.
	 */
	private function printManual()
	{
		$man = 'usage: php sss.php [-c <config>] [-i <logfile|logdir>] [-o <statspage> [-b <outputbits>]]'."\n"
		     . '       php sss.php [-c <config>] [-m]'."\n\n"
		     . 'the options are:'."\n"
		     . '       -b	set <outputbits>, add up the bits corresponding to the sections'."\n"
		     . '		you want to be included on the statspage:'."\n"
		     . '		     1  activity'."\n"
		     . '		     2  general chat'."\n"
		     . '		     4  modes'."\n"
		     . '		     8  events'."\n"
		     . '		    16  smileys'."\n"
		     . '		if this option is omitted all sections will be included'."\n"
		     . '	-c	read settings from <config>'."\n"
		     . '		if unspecified sss.conf will be used'."\n"
		     . '	-i	input <logfile>, or all logfiles in <logdir>'."\n"
		     . '		database maintenance will be run after parsing the last logfile'."\n"
		     . '		unless "doMaintenance" is set to FALSE in the config file'."\n"
		     . '	-m	perform maintenance routines on the database'."\n"
		     . '	-o	generate statistics and output to <statspage>'."\n";
		exit($man);
	}

	/**
	 * Read settings from the config file.
	 */
	private function readConfig($path)
	{
		if (($rp = realpath($path)) !== FALSE) {
			if (($fp = @fopen($rp, 'rb')) !== FALSE) {
				$this->output('notice', 'readConfig(): reading settings from config: \''.$fp.'\'');

				while (!feof($fp)) {
					$line = fgets($fp);
					$line = trim($line);

					if (preg_match('/^(\w+)\s*=\s*"(\S*)"$/', $line, $matches)) {
						$this->settings[$matches[1]] = $matches[2];
					}
				}

				fclose($fp);

				/**
				 * Exit if any crucial settings aren't present in the config file.
				 */
				foreach ($this->settings_required as $key) {
					if (!array_key_exists($key, $this->settings)) {
						$this->output('critical', 'readConfig(): missing setting: \''.$key.'\'');
					}
				}

				if (date_default_timezone_set($this->settings['timezone']) !== FALSE) {
					$this->output('notice', 'readConfig(): switched to timezone: \''.$this->settings['timezone'].'\'');
				} else {
					$this->output('critical', 'readConfig(): invalid timezone: \''.$this->settings['timezone'].'\'');
				}

				if ($this->settings['db_server'] == 'MySQL' && !extension_loaded('mysqli')) {
					$this->output('critical', 'readConfig(): the MySQLi extension isn\'t loaded'."\n");
				}
			} else {
				$this->output('critical', 'readConfig(): failed to open file: \''.$rp.'\'');
			}
		} else {
			$this->output('critical', 'readConfig(): no such file: \''.$path.'\'');
		}
	}
}

if (substr(phpversion(), 0, 3) != '5.3') {
	exit('PHP version 5.3 needed, currently running with '.phpversion()."\n");
}

$sss = new sss();

?>
