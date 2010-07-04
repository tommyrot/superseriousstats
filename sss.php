<?php

/**
 * Copyright (c) 2009-2010, Jos de Ruijter <jos@dutnie.nl>
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
 * Class for controlling all features of the program.
 */
final class sss extends Base
{
	/**
	 * Default settings for this script, can be overridden in the config file.
	 * These should all appear in $settings_list[] along with their type.
	 */
	private $db_host = '';
	private $db_name = '';
	private $db_pass = '';
	private $db_port = 0;
	private $db_user = '';
	private $logfileDateFormat = '';
	private $logfileFormat = '';
	private $logfilePrefix = '';
	private $logfileSuffix = '';
	private $timezone = '';
	private $writeData = TRUE;

	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $mysqli;
	private $settings = array();
	private $settings_list = array(
		'db_host' => 'string',
		'db_name' => 'string',
		'db_pass' => 'string',
		'db_port' => 'int',
		'db_user' => 'string',
		'logfileDateFormat' => 'string',
		'logfileFormat' => 'string',
		'logfilePrefix' => 'string',
		'logfileSuffix' => 'string',
		'outputbits' => 'int',
		'timezone' => 'string',
		'writeData' => 'bool');
	private $settings_required_list = array('channel', 'db_host', 'db_name', 'db_pass', 'db_port', 'db_user', 'logfileDateFormat', 'logfileFormat', 'logfilePrefix', 'logfileSuffix', 'timezone');

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
		$options = getopt('b:c:i:mo:');

		if (empty($options)) {
			$this->printManual();
		}

		if (array_key_exists('c', $options)) {
			$this->readConfig($options['c']);
		} else {
			$this->readConfig(dirname(__FILE__).'/sss.conf');
		}

		if (array_key_exists('b', $options)) {
			$this->settings['sectionbits'] = (int) $options['b'];
		}

		$this->mysqli = @mysqli_connect($this->db_host, $this->db_user, $this->db_pass, $this->db_name, $this->db_port) or $this->output('critical', 'MySQLi: '.mysqli_connect_error());
		$this->output('notice', '__construct(): succesfully connected to '.$this->db_host.':'.$this->db_port.', database: \''.$this->db_name.'\'');

		if (array_key_exists('i', $options)) {
			$this->parseLog($options['i']);
		}

		if (array_key_exists('m', $options)) {
			$this->doMaintenance();
		}

		if (array_key_exists('o', $options)) {
			$this->makeHTML($options['o']);
		}

		@mysqli_close($this->mysqli);
	}

	/**
	 * Run the database maintenance scripts. Userstats of all linked nicks will be accumulated, sanity checks will be done on the userstatuses and more.
	 */
	private function doMaintenance()
	{
		$maintenance = new Maintenance($this->settings);
		$maintenance->doMaintenance();
	}

	/**
	 * Create the statspage.
	 */
	private function makeHTML($file)
	{
		$HTML = new HTML($this->settings);
		$output = $HTML->makeHTML();

		if (($fp = @fopen($file, 'wb')) === FALSE) {
			$this->output('critical', 'makeHTML(): failed to open file: \''.$file.'\'');
		}

		fwrite($fp, $output);
		fclose($fp);
	}

	/**
	 * Parse a logfile, or all logfiles in the given logdir.
	 */
	private function parseLog($filedir)
	{
		$filedir = preg_replace('/YESTERDAY/', date($this->logfileDateFormat, strtotime('yesterday')), $filedir);

		if (($rp = realpath($filedir)) === FALSE) {
			$this->output('critical', 'parseLog(): no such file or directory: \''.$filedir.'\'');
		}

		if (is_dir($rp)) {
			if (($dh = @opendir($rp)) === FALSE) {
				$this->output('critical', 'parseLog(): failed to open directory: \''.$rp.'\'');
			}

			while (($file = readdir($dh)) !== FALSE) {
				$logfiles[] = realpath($rp.'/'.$file);
			}

			closedir($dh);
		} else {
			$logfiles[] = $rp;
		}

		sort($logfiles);

		/**
		 * Variable to track if we modified our database and therefore need maintenance.
		 */
		$needMaintenance = FALSE;

		foreach ($logfiles as $logfile) {
			if ((!empty($this->logfilePrefix) && stripos(basename($logfile), $this->logfilePrefix) === FALSE) || (!empty($this->logfileSuffix) && stripos(basename($logfile), $this->logfileSuffix) === FALSE)) {
				continue;
			}

			$date = str_replace(array($this->logfilePrefix, $this->logfileSuffix), '', basename($logfile));
			$date = date('Y-m-d', strtotime($date));
			$parser_class = 'Parser_'.$this->logfileFormat;
			$parser = new $parser_class($this->settings);
			$parser->setValue('date', $date);

			/**
			 * Get the streak history, we assume that logs are being parsed in chronological order.
			 */
			$query = @mysqli_query($this->mysqli, 'SELECT * FROM `streak_history`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query);

			if (!empty($rows)) {
				$result = mysqli_fetch_object($query);
				$parser->setValue('prevNick', $result->prevNick);
				$parser->setValue('streak', (int) $result->streak);
			}

			/**
			 * Get the parse history for the current logfile.
			 */
			$query = @mysqli_query($this->mysqli, 'SELECT `lines_parsed` FROM `parse_history` WHERE `date` = \''.mysqli_real_escape_string($this->mysqli, $date).'\'') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query);

			if (!empty($rows)) {
				$result = mysqli_fetch_object($query);
				/**
				 * Have the parser start at the last line we parsed on previous run. The final value of $lineNum is that of the line which contains EOF.
				 * This line contains no chat data and should be parsed again on the next run when it possibly does contain data.
				 */
				$firstLine = (int) $result->lines_parsed;
			} else {
				$firstLine = 1;
			}

			$parser->parseLog($logfile, $firstLine);

			/**
			 * If the stored number of parsed lines is equal to the amount of lines in the logfile we can skip writing to db and performing maintenance.
			 */
			if ($this->writeData && $parser->getValue('lineNum') > (int) $result->lines_parsed) {
				$parser->writeData($this->mysqli);
				@mysqli_query($this->mysqli, 'INSERT INTO `parse_history` SET `date` = \''.mysqli_real_escape_string($this->mysqli, $date).'\', `lines_parsed` = '.$parser->getValue('lineNum').' ON DUPLICATE KEY UPDATE `lines_parsed` = '.$parser->getValue('lineNum')) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
				$needMaintenance = TRUE;
			}
		}

		if ($needMaintenance) {
			$this->doMaintenance();
		}
	}

	/**
	 * Print the manual and exit.
	 */
	private function printManual()
	{
		$man = 'usage: php sss.php [-c <config>] [-i <logfile|logdir>]'."\n"
		     . '		   [-o <statspage> [-b <sectionbits>]]'."\n"
		     . '       php sss.php [-c <config>] [-m]'."\n\n"
		     . 'The options are:'."\n"
		     . '       -b	Set <sectionbits>, add up the bits corresponding to the sections'."\n"
		     . '		you want to be included on the statspage:'."\n"
		     . '		     1  Activity'."\n"
		     . '		     2  General Chat'."\n"
		     . '		     4  Modes'."\n"
		     . '		     8  Events'."\n"
		     . '		    16  Smileys'."\n"
		     . '		    32  URLs'."\n"
		     . '		If this option is omitted all sections will be included.'."\n"
		     . '	-c	Read settings from <config>.'."\n"
		     . '		If unspecified sss.conf will be used.'."\n"
		     . '	-i	Input <logfile>, or all logfiles in <logdir>. Database'."\n"
		     . '		maintenance will always be run after parsing the last logfile.'."\n"
		     . '	-m	Perform maintenance routines on the database.'."\n"
		     . '	-o	Generate statistics and output to <statspage>.'."\n";
		exit($man);
	}

	/**
	 * Read settings from the config file and put them into $settings[] so we can pass them along to other classes.
	 */
	private function readConfig($file)
	{
		if (($rp = realpath($file)) === FALSE) {
			$this->output('critical', 'readConfig(): no such file: \''.$file.'\'');
		}

		if (($fp = @fopen($rp, 'rb')) === FALSE) {
			$this->output('critical', 'readConfig(): failed to open file: \''.$rp.'\'');
		}

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
		foreach ($this->settings_required_list as $key) {
			if (!array_key_exists($key, $this->settings)) {
				$this->output('critical', 'readConfig(): missing setting: \''.$key.'\'');
			}
		}

		foreach ($this->settings_list as $key => $type) {
			if (!array_key_exists($key, $this->settings)) {
				continue;
			}

			if ($type == 'string') {
				$this->$key = $this->settings[$key];
			} elseif ($type == 'int') {
				$this->$key = (int) $this->settings[$key];
			} elseif ($type == 'bool') {
				if (strtoupper($this->settings[$key]) == 'TRUE') {
					$this->$key = TRUE;
				} elseif (strtoupper($this->settings[$key]) == 'FALSE') {
					$this->$key = FALSE;
				}
			}
		}

		if (date_default_timezone_set($this->timezone) == FALSE) {
			$this->output('critical', 'readConfig(): invalid timezone: \''.$this->timezone.'\'');
		}
	}
}

if (substr(phpversion(), 0, 3) != '5.3') {
	exit('PHP version 5.3 required, currently running with version '.phpversion()."\n");
}

if (!extension_loaded('mysqli')) {
	exit('MySQLi extension isn\'t loaded'."\n");
}

/**
 * Class autoloader.
 */
function __autoload($class)
{
	require_once(dirname(__FILE__).'/'.$class.'.class.php');
}

$sss = new sss();

?>
