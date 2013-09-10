<?php

/**
 * Copyright (c) 2009-2013, Jos de Ruijter <jos@dutnie.nl>
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
 * Suppress any error output.
 */
ini_set('display_errors', '0');
ini_set('error_reporting', 0);

/**
 * Check if all required extension are loaded.
 */
if (!extension_loaded('sqlite3')) {
	exit('sqlite3 extension isn\'t loaded'."\n");
}

if (!extension_loaded('mbstring')) {
	exit('mbstring extension isn\'t loaded'."\n");
}

/**
 * Class autoloader. This code handles on the fly inclusion of class files at time of instantiation.
 */
spl_autoload_register(function ($class) {
	require_once(rtrim(dirname(__FILE__), '/\\').'/'.$class.'.php');
});

/**
 * Class for controlling all main features of the program.
 */
final class sss extends base
{
	/**
	 * Default settings for this script, which can be overridden in the configuration file. These variables should
	 * all appear in $settings_list[] along with their type.
	 */
	private $autolinknicks = true;
	private $database = 'sss.db3';
	private $logfile_dateformat = '';
	private $parser = '';
	private $timezone = 'UTC';

	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $settings = array();
	private $settings_list = array(
		'autolinknicks' => 'bool',
		'database' => 'string',
		'logfile_dateformat' => 'string',
		'outputbits' => 'int',
		'parser' => 'string',
		'timezone' => 'string');
	private $settings_list_required = array();

	public function __construct()
	{
		/**
		 * Explicitly set the locale to C (POSIX) for all categories so there hopefully won't be any unexpected
		 * results between platforms.
		 */
		setlocale(LC_ALL, 'C');

		/**
		 * Use UTC until the default, or user specified, timezone is loaded.
		 */
		date_default_timezone_set('UTC');

		/**
		 * Read options from the command line. Print the manual on invalid input.
		 */
		$options = getopt('b:c:e:i:n:o:s');
		ksort($options);
		$options_keys = implode('', array_keys($options));

		if (!preg_match('/^(bc?i?o|c|c?(e|i|i?o|n|s))$/', $options_keys)) {
			$this->print_manual();
		}

		/**
		 * Some options require additional settings to be set in the configuration file. Add those to the list.
		 */
		if (strpos($options_keys, 'i') !== false) {
			array_push($this->settings_list_required, 'parser', 'logfile_dateformat');
		}

		if (strpos($options_keys, 'o') !== false) {
			$this->settings_list_required[] = 'channel';
		}

		/**
		 * Read the configuration file.
		 */
		if (array_key_exists('c', $options)) {
			$this->read_config($options['c']);
		} else {
			$this->read_config(dirname(__FILE__).'/sss.conf');
		}

		/**
		 * Export settings from the configuration file in the format vars.php accepts them.
		 */
		if (array_key_exists('s', $options)) {
			$this->export_settings();
		}

		/**
		 * Open the database connection. Always needed from this point forward.
		 */
		try {
			$sqlite3 = new SQLite3($this->database, SQLITE3_OPEN_READWRITE);
			$sqlite3->busyTimeout(60000);
		} catch (Exception $e) {
			$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$e->getMessage());
		}

		/**
		 * Set SQLite3 PRAGMAs:
		 *  journal_mode = OFF - Disable the rollback journal completely.
		 *  synchronous = OFF - Continue without syncing as soon as data is handed off to the operating system.
		 *  temp_store = MEMORY - Temporary tables and indices are kept in memory.
		 */
		$pragmas = array(
			'journal_mode' => 'OFF',
			'synchronous' => 'OFF',
			'temp_store' => 'MEMORY');

		foreach ($pragmas as $key => $value) {
			$sqlite3->exec('PRAGMA '.$key.' = '.$value);
		}

		$this->output('notice', 'sss(): succesfully connected to database: \''.$this->database.'\'');

		/**
		 * The following options are listed in order of execution. Ie. "i" before "o", "b" before "o".
		 */
		if (array_key_exists('b', $options)) {
			$this->settings['sectionbits'] = (int) $options['b'];
		}

		if (array_key_exists('e', $options)) {
			$this->export_nicks($sqlite3, $options['e']);
		}

		if (array_key_exists('i', $options)) {
			$this->parse_log($sqlite3, $options['i']);
		}

		if (array_key_exists('n', $options)) {
			$this->import_nicks($sqlite3, $options['n']);

			/**
			 * Run maintenance after import.
			 */
			$this->do_maintenance($sqlite3);
		}

		if (array_key_exists('o', $options)) {
			$this->make_html($sqlite3, $options['o']);
		}

		$sqlite3->close();
		$this->output('notice', 'sss(): kthxbye');
	}

	/**
	 * The maintenance routines ensure that all relevant user data is accumulated properly.
	 */
	private function do_maintenance($sqlite3)
	{
		/**
		 * Search for new aliases if $autolinknicks is enabled.
		 */
		if ($this->autolinknicks) {
			$this->link_nicks($sqlite3);
		}

		$maintenance = new maintenance($this->settings);
		$maintenance->do_maintenance($sqlite3);
	}

	private function export_nicks($sqlite3, $file)
	{
		$this->output('notice', 'export_nicks(): exporting nicks');
		$query = $sqlite3->query('SELECT csnick, ruid, status FROM uid_details ORDER BY csnick ASC') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$result = $query->fetchArray(SQLITE3_ASSOC);

		if ($result === false) {
			$this->output('critical', 'export_nicks(): database is empty');
		}

		$query->reset();

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			if ($result['status'] == 1 || $result['status'] == 3 || $result['status'] == 4) {
				$registerednicks[$result['ruid']] = strtolower($result['csnick']);
				$statuses[$result['ruid']] = $result['status'];
			} elseif ($result['status'] == 2) {
				$aliases[$result['ruid']][] = strtolower($result['csnick']);
			} else {
				$unlinked[] = strtolower($result['csnick']);
			}
		}

		$output = '';
		$i = 0;

		if (!empty($registerednicks)) {
			foreach ($registerednicks as $ruid => $nick) {
				$output .= $statuses[$ruid].','.$nick;
				$i++;

				if (!empty($aliases[$ruid])) {
					$output .= ','.implode(',', $aliases[$ruid]);
					$i += count($aliases[$ruid]);
				}

				$output .= "\n";
			}
		}

		if (!empty($unlinked)) {
			$output .= '*,'.implode(',', $unlinked)."\n";
			$i += count($unlinked);
		}

		if (($fp = fopen($file, 'wb')) === false) {
			$this->output('critical', 'export_nicks(): failed to open file: \''.$file.'\'');
		}

		fwrite($fp, $output);
		fclose($fp);
		$this->output('notice', 'export_nicks(): '.number_format($i).' nicks exported');
	}

	private function export_settings()
	{
		if (!empty($this->settings['cid'])) {
			$vars = '$settings[\''.$this->settings['cid'].'\'] = array(';
		} elseif (!empty($this->settings['channel'])) {
			$vars = '$settings[\''.$this->settings['channel'].'\'] = array(';
		} else {
			$this->output('critical', 'export_settings(): both \'cid\' and \'channel\' are empty');
		}

		foreach ($this->settings as $key => $value) {
			$vars .= "\n\t".'\''.$key.'\' => \''.$value.'\',';
		}

		exit(rtrim($vars, ',')."\n".');'."\n");
	}

	private function import_nicks($sqlite3, $file)
	{
		$this->output('notice', 'import_nicks(): importing nicks');
		$query = $sqlite3->query('SELECT uid, csnick FROM uid_details') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$result = $query->fetchArray(SQLITE3_ASSOC);

		if ($result === false) {
			$this->output('critical', 'import_nicks(): database is empty');
		}

		$query->reset();

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$uids[strtolower($result['csnick'])] = $result['uid'];
		}

		if (($rp = realpath($file)) === false) {
			$this->output('critical', 'import_nicks(): no such file: \''.$file.'\'');
		}

		if (($fp = fopen($rp, 'rb')) === false) {
			$this->output('critical', 'import_nicks(): failed to open file: \''.$file.'\'');
		}

		while (!feof($fp)) {
			$line = fgets($fp);
			$line = preg_replace('/\s/', '', $line);
			$lineparts = explode(',', strtolower($line));
			$status = (int) $lineparts[0];

			/**
			 * The first nick on each line will be the initial registered nick which aliases are linked to.
			 */
			if (($status == 1 || $status == 3 || $status == 4) && !empty($lineparts[1]) && array_key_exists($lineparts[1], $uids)) {
				$ruid = $uids[$lineparts[1]];
				$ruids[] = $ruid;
				$statuses[$ruid] = $status;

				for ($i = 2, $j = count($lineparts); $i < $j; $i++) {
					if (!empty($lineparts[$i]) && array_key_exists($lineparts[$i], $uids)) {
						$aliases[$ruid][] = $uids[$lineparts[$i]];
					}
				}
			}
		}

		fclose($fp);

		if (empty($ruids)) {
			$this->output('critical', 'import_nicks(): no user relations found to import');
		} else {
			/**
			 * Set all nicks to their default status before updating them according to imported data.
			 */
			$sqlite3->exec('BEGIN TRANSACTION') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$sqlite3->exec('UPDATE uid_details SET ruid = uid, status = 0') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

			foreach ($ruids as $ruid) {
				$sqlite3->exec('UPDATE uid_details SET status = '.$statuses[$ruid].' WHERE uid = '.$ruid) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

				if (!empty($aliases[$ruid])) {
					$sqlite3->exec('UPDATE uid_details SET ruid = '.$ruid.', status = 2 WHERE uid IN ('.implode(',', $aliases[$ruid]).')') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
				}
			}

			$sqlite3->exec('COMMIT') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}
	}

	/**
	 * This function tries to link unlinked nicks to any other nick that is identical after stripping them both from
	 * any non-alphanumeric characters (at any position in the nick) and trailing numerics. The results are compared
	 * in a case insensitive manner.
	 */
	private function link_nicks($sqlite3)
	{
		$this->output('notice', 'link_nicks(): looking for possible aliases');
		$query = $sqlite3->query('SELECT uid, csnick, ruid, status FROM uid_details') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$strippednicks = array();

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$nicks[$result['uid']] = array(
				'nick' => $result['csnick'],
				'ruid' => $result['ruid'],
				'status' => $result['status']);
			$strippednick = preg_replace(array('/[^a-z0-9]/', '/[0-9]+$/'), '', strtolower($result['csnick']));

			/**
			 * The stripped nick must consist of at least two characters.
			 */
			if (strlen($strippednick) > 1) {
				/**
				 * Maintain an array for each stripped nick, containing the uids of every nick that
				 * matches it. Put the uid of the matching nick at the start of the array if the nick is
				 * already linked (status != 0), otherwise put it at the end.
			 	 */
				if ($result['status'] != 0 && !empty($strippednicks[$strippednick])) {
					array_unshift($strippednicks[$strippednick], $result['uid']);
				} else {
					$strippednicks[$strippednick][] = $result['uid'];
				}
			}
		}

		$sqlite3->exec('BEGIN TRANSACTION') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		foreach ($strippednicks as $uids) {
			/**
			 * If there is only one match for the stripped nick, there is nothing to link.
			 */
			if (count($uids) == 1) {
				continue;
			}

			$newalias = false;

			for ($i = 1, $j = count($uids); $i < $j; $i++) {
				/**
				 * Use the ruid that belongs to the first uid in the array to link all succeeding
				 * _unlinked_ nicks to.
				 */
				if ($nicks[$uids[$i]]['status'] == 0) {
					$sqlite3->exec('UPDATE uid_details SET ruid = '.$nicks[$uids[0]]['ruid'].', status = 2 WHERE uid = '.$uids[$i]) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
					$this->output('debug', 'link_nicks(): linked \''.$nicks[$uids[$i]]['nick'].'\' to \''.$nicks[$nicks[$uids[0]]['ruid']]['nick'].'\'');
					$newalias = true;
				}
			}

			/**
			 * If there are aliases found, and the first nick in the array is unlinked (status = 0), make it
			 * a registered nick (status = 1).
			 */
			if ($newalias && $nicks[$uids[0]]['status'] == 0) {
				$sqlite3->exec('UPDATE uid_details SET status = 1 WHERE uid = '.$uids[0]) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		}

		$sqlite3->exec('COMMIT') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
	}

	private function make_html($sqlite3, $file)
	{
		$html = new html($this->settings);
		$output = $html->make_html($sqlite3);

		if (($fp = fopen($file, 'wb')) === false) {
			$this->output('critical', 'make_html(): failed to open file: \''.$file.'\'');
		}

		fwrite($fp, $output);
		fclose($fp);
	}

	private function parse_log($sqlite3, $filedir)
	{
		if (($rp = realpath($filedir)) === false) {
			$this->output('critical', 'parse_log(): no such file or directory: \''.$filedir.'\'');
		}

		if (is_dir($rp)) {
			if (($dh = opendir($rp)) === false) {
				$this->output('critical', 'parse_log(): failed to open directory: \''.$rp.'\'');
			}

			while (($file = readdir($dh)) !== false) {
				$files[] = realpath($rp.'/'.$file);
			}

			closedir($dh);
		} else {
			$files[] = $rp;
		}

		foreach ($files as $file) {
			/**
			 * The filenames should match the pattern provided by $logfile_dateformat.
			 */
			if (($datetime = date_create_from_format($this->logfile_dateformat, basename($file))) !== false) {
				$logfiles[date_format($datetime, 'Y-m-d')] = $file;
			}
		}

		if (empty($logfiles)) {
			$this->output('critical', 'parse_log(): no logfiles found matching \'logfile_dateformat\' setting');
		}

		/**
		 * Sort the files on the date found in the filename.
		 */
		ksort($logfiles);

		/**
		 * Get the date of the last log parsed.
		 */
		if (($date_lastlogparsed = $sqlite3->querySingle('SELECT MAX(date) FROM parse_history')) === false) {
			$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$needmaintenance = false;

		foreach ($logfiles as $date => $logfile) {
			if (!is_null($date_lastlogparsed) && strtotime($date) < strtotime($date_lastlogparsed)) {
				continue;
			}

			$parser = new $this->parser($this->settings);
			$parser->set_value('date', $date);

			/**
			 * Get the streak history. This will assume logs are parsed in chronological order with no gaps.
			 */
			if (($result = $sqlite3->querySingle('SELECT prevnick, streak FROM streak_history', true)) === false) {
				$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			if (!empty($result)) {
				$parser->set_value('prevnick', $result['prevnick']);
				$parser->set_value('streak', $result['streak']);
			}

			/**
			 * Get the parse history and set the line number on which to start parsing the log.
			 */
			if (($firstline = $sqlite3->querySingle('SELECT lines_parsed + 1 FROM parse_history WHERE date = \''.$date.'\'')) === false) {
				$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			if (is_null($firstline)) {
				$firstline = 1;
			}

			/**
			 * Check if the log is gzipped and call the appropriate parser.
			 */
			if (preg_match('/\.gz$/', $logfile)) {
				if (!extension_loaded('zlib')) {
					$this->output('critical', 'parse_log(): zlib extension isn\'t loaded: can\'t parse gzipped logs'."\n");
				}

				$parser->gzparse_log($sqlite3, $logfile, $firstline);
			} else {
				$parser->parse_log($sqlite3, $logfile, $firstline);
			}

			/**
			 * Update the parse history when there are actual (non empty) lines parsed.
			 */
			if ($parser->get_value('linenum_lastnonempty') >= $firstline) {
				$sqlite3->exec('INSERT OR IGNORE INTO parse_history (date, lines_parsed) VALUES (\''.$date.'\', '.$parser->get_value('linenum_lastnonempty').')') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
				$sqlite3->exec('UPDATE parse_history SET lines_parsed = '.$parser->get_value('linenum_lastnonempty').' WHERE CHANGES() = 0 AND date = \''.$date.'\'') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

				/**
				 * Write data to database and set $needmaintenance to true if there was any data stored.
				 */
				if ($parser->write_data($sqlite3)) {
					$needmaintenance = true;
				}
			}
		}

		/**
		 * Finally, call maintenance if needed.
		 */
		if ($needmaintenance) {
			$this->do_maintenance($sqlite3);
		}
	}

	private function print_manual()
	{
		$man = 'usage:	php sss.php [-c <file>] [-i <file|directory>]'."\n"
		     . '		    [-o <file> [-b <numbits>]]'."\n\n"
		     . 'See the MANUAL file for an overview of all available options.'."\n";
		exit($man);
	}

	/**
	 * Read the settings from the configuration file and put them into $settings[] so they can be passed along to
	 * other classes.
	 */
	private function read_config($file)
	{
		if (($rp = realpath($file)) === false) {
			$this->output('critical', 'read_config(): no such file: \''.$file.'\'');
		}

		if (($fp = fopen($rp, 'rb')) === false) {
			$this->output('critical', 'read_config(): failed to open file: \''.$rp.'\'');
		}

		while (!feof($fp)) {
			$line = fgets($fp);
			$line = trim($line);

			if (preg_match('/^(\w+)\s*=\s*"(.*?)"(\s*#.*)?$/', $line, $matches)) {
				$this->settings[$matches[1]] = $matches[2];
			}
		}

		fclose($fp);

		/**
		 * Exit if any crucial settings aren't present.
		 */
		foreach ($this->settings_list_required as $key) {
			if (!array_key_exists($key, $this->settings)) {
				$this->output('critical', 'read_config(): missing setting: \''.$key.'\'');
			}
		}

		/**
		 * If set, override variables listed in $settings_list[].
		 */
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

		if (!date_default_timezone_set($this->timezone)) {
			$this->output('critical', 'read_config(): invalid timezone: \''.$this->timezone.'\'');
		}
	}
}

/**
 * Get ready for the launch.
 */
$sss = new sss();

?>
