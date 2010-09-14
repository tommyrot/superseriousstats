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
final class sss extends base
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
	private $logfile_dateformat = '';
	private $logfile_format = '';
	private $logfile_prefix = '';
	private $logfile_suffix = '';
	private $timezone = '';

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
		'logfile_dateformat' => 'string',
		'logfile_format' => 'string',
		'logfile_prefix' => 'string',
		'logfile_suffix' => 'string',
		'outputbits' => 'int',
		'timezone' => 'string');
	private $settings_list_required = array('channel', 'db_host', 'db_name', 'db_pass', 'db_port', 'db_user', 'logfile_dateformat', 'logfile_format', 'logfile_prefix', 'logfile_suffix', 'timezone');

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
			$this->print_manual();
		}

		if (array_key_exists('c', $options)) {
			$this->read_config($options['c']);
		} else {
			$this->read_config(dirname(__FILE__).'/sss.conf');
		}

		if (array_key_exists('b', $options)) {
			$this->settings['sectionbits'] = (int) $options['b'];
		}

		$this->mysqli = @mysqli_connect($this->db_host, $this->db_user, $this->db_pass, $this->db_name, $this->db_port) or $this->output('critical', 'mysqli: '.mysqli_connect_error());
		$this->output('notice', '__construct(): succesfully connected to '.$this->db_host.':'.$this->db_port.', database: \''.$this->db_name.'\'');

		if (array_key_exists('i', $options)) {
			$this->parse_log($options['i']);
		}

		if (array_key_exists('m', $options)) {
			$this->do_maintenance();
		}

		if (array_key_exists('o', $options)) {
			$this->make_html($options['o']);
		}

		@mysqli_close($this->mysqli);
	}

	private function do_maintenance()
	{
		$maintenance = new maintenance($this->settings);
		$maintenance->do_maintenance($this->mysqli);
	}

	private function make_html($file)
	{
		$html = new html($this->settings);
		$output = $html->make_html($this->mysqli);

		if (($fp = @fopen($file, 'wb')) === false) {
			$this->output('critical', 'make_html(): failed to open file: \''.$file.'\'');
		}

		fwrite($fp, $output);
		fclose($fp);
	}

	private function parse_log($filedir)
	{
		$filedir = preg_replace('/YESTERDAY/', date($this->logfile_dateformat, strtotime('yesterday')), $filedir);

		if (($rp = realpath($filedir)) === false) {
			$this->output('critical', 'parse_log(): no such file or directory: \''.$filedir.'\'');
		}

		if (is_dir($rp)) {
			if (($dh = @opendir($rp)) === false) {
				$this->output('critical', 'parse_log(): failed to open directory: \''.$rp.'\'');
			}

			while (($file = readdir($dh)) !== false) {
				$logfiles[] = realpath($rp.'/'.$file);
			}

			closedir($dh);
		} else {
			$logfiles[] = $rp;
		}

		sort($logfiles);

		/**
		 * Retrieve the date of the last log parsed from the database.
		 */
		$query = @mysqli_query($this->mysqli, 'select `date` from `parse_history` order by `date` desc limit 1') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (!empty($rows)) {
			$result = mysqli_fetch_object($query);
			$date_lastlogparsed = $result->date;
		} else {
			$date_lastlogparsed = null;
		}

		/**
		 * Variable to track if we modified our database and therefore need maintenance.
		 */
		$needmaintenance = false;

		foreach ($logfiles as $logfile) {
			if ((!empty($this->logfile_prefix) && substr_compare(basename($logfile), $this->logfile_prefix, 0, strlen($this->logfile_prefix)) !== 0) || (!empty($this->logfile_suffix) && substr_compare(basename($logfile), $this->logfile_suffix, -strlen($this->logfile_suffix)) !== 0)) {
				continue;
			}

			$date = str_replace(array($this->logfile_prefix, $this->logfile_suffix), '', basename($logfile));
			$date = date('Y-m-d', strtotime($date));

			/**
			 * Logs must be parsed in chronological order. If current log is older than the last log parsed we skip it.
			 * We do process the log with the same date as the last log parsed as it may contain new lines that haven't been processed yet.
			 */
			if (!is_null($date_lastlogparsed) && strtotime($date) < strtotime($date_lastlogparsed)) {
				continue;
			}

			$parser_class = 'parser_'.$this->logfile_format;
			$parser = new $parser_class($this->settings);
			$parser->set_value('date', $date);

			/**
			 * Get the streak history.
			 */
			$query = @mysqli_query($this->mysqli, 'select * from `streak_history`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query);

			if (!empty($rows)) {
				$result = mysqli_fetch_object($query);
				$parser->set_value('prevnick', $result->prevnick);
				$parser->set_value('streak', (int) $result->streak);
			}

			/**
			 * Get the parse history for the current logfile.
			 */
			$query = @mysqli_query($this->mysqli, 'select `lines_parsed` from `parse_history` where `date` = \''.mysqli_real_escape_string($this->mysqli, $date).'\'') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query);

			if (!empty($rows)) {
				$result = mysqli_fetch_object($query);
				/**
				 * Have the parser start at the last line we parsed on previous run. The final value of $linenum is that of the line which contains EOF.
				 * This line contains no chat data and should be parsed again on the next run when it possibly does contain data.
				 */
				$firstline = (int) $result->lines_parsed;
			} else {
				$firstline = 1;
			}

			$parser->parse_log($logfile, $firstline);

			/**
			 * If the stored number of parsed lines is equal to the amount of lines in the logfile we can skip writing to db and performing maintenance.
			 */
			if ($parser->get_value('linenum') > $firstline) {
				$parser->write_data($this->mysqli);
				@mysqli_query($this->mysqli, 'insert into `parse_history` set `date` = \''.mysqli_real_escape_string($this->mysqli, $date).'\', `lines_parsed` = '.$parser->get_value('linenum').' on duplicate key update `lines_parsed` = '.$parser->get_value('linenum')) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				$needmaintenance = true;
			}
		}

		if ($needmaintenance) {
			$this->do_maintenance();
		}
	}

	private function print_manual()
	{
		$man = 'usage:	php sss.php [-c <config>] [-i <logfile|logdir>]'."\n"
		     . '		    [-o <statspage> [-b <sectionbits>]]'."\n"
		     . '	php sss.php [-c <config>] [-m]'."\n\n"
		     . 'options:'."\n"
		     . '	-b	Set <sectionbits>, add up the bits corresponding to the sections'."\n"
		     . '		you want to be included on the statspage:'."\n"
		     . '		     1  Activity'."\n"
		     . '		     2  General Chat'."\n"
		     . '		     4  Modes'."\n"
		     . '		     8  Events'."\n"
		     . '		    16  Smileys'."\n"
		     . '		    32  urls'."\n"
		     . '		    64  Words'."\n"
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
	private function read_config($file)
	{
		if (($rp = realpath($file)) === false) {
			$this->output('critical', 'read_config(): no such file: \''.$file.'\'');
		}

		if (($fp = @fopen($rp, 'rb')) === false) {
			$this->output('critical', 'read_config(): failed to open file: \''.$rp.'\'');
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
		foreach ($this->settings_list_required as $key) {
			if (!array_key_exists($key, $this->settings)) {
				$this->output('critical', 'read_config(): missing setting: \''.$key.'\'');
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
				if (strtolower($this->settings[$key]) == 'true') {
					$this->$key = true;
				} elseif (strtolower($this->settings[$key]) == 'false') {
					$this->$key = false;
				}
			}
		}

		if (date_default_timezone_set($this->timezone) == false) {
			$this->output('critical', 'read_config(): invalid timezone: \''.$this->timezone.'\'');
		}
	}
}

if (substr(phpversion(), 0, 3) != '5.3') {
	exit('php version 5.3 required, currently running with version '.phpversion()."\n");
}

if (!extension_loaded('mysqli')) {
	exit('mysqli extension isn\'t loaded'."\n");
}

/**
 * Class autoloader. Important piece of code right here.
 */
function __autoload($class)
{
	require_once(dirname(__FILE__).'/'.$class.'.class.php');
}

$sss = new sss();

?>
