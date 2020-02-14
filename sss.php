<?php

/**
 * Copyright (c) 2009-2020, Jos de Ruijter <jos@dutnie.nl>
 */

declare(strict_types=1);

/**
 * Override php.ini directives.
 */
ini_set('display_errors', 'stdout');
ini_set('error_reporting', '-1');
ini_set('pcre.jit', '0');

/**
 * Check if all required extensions are loaded.
 */
if (!extension_loaded('sqlite3')) {
	exit('sqlite3 extension isn\'t loaded'."\n");
}

if (!extension_loaded('mbstring')) {
	exit('mbstring extension isn\'t loaded'."\n");
}

/**
 * Autoloader. This code handles on the fly inclusion of classes and traits at
 * time of instantiation.
 */
spl_autoload_register(function (string $class): void {
	if (strpos($class, 'parser_') === 0) {
		require_once(__DIR__.'/parsers/'.$class.'.php');
	} else {
		require_once(__DIR__.'/'.$class.'.php');
	}
});

/**
 * Main class.
 */
class sss
{
	use config;

	/**
	 * Variables listed in $settings_allow_override[] can have their default value
	 * overridden through the config file.
	 */
	private array $config = [];
	private array $settings_allow_override = ['autolink_nicks', 'database', 'logfile_dateformat', 'parser', 'timezone'];
	private array $settings_required = ['channel', 'database', 'logfile_dateformat', 'parser', 'timezone'];
	private bool $autolink_nicks = true;
	private string $database = '';
	private string $logfile_dateformat = '';
	private string $parser = '';
	private string $timezone = '';

	public function __construct()
	{
		/**
		 * Explicitly set the locale to C (POSIX) for all categories so there hopefully
		 * won't be any unexpected results between platforms.
		 */
		setlocale(LC_ALL, 'C');

		/**
		 * Use UTC until config specified timezone is loaded.
		 */
		date_default_timezone_set('UTC');

		/**
		 * Launch main function.
		 */
		$this->main();
	}

	private function main(): void
	{
		/**
		 * Read options from the command line. Print a hint on invalid input.
		 */
		$options = getopt('c:e:i:n:o:qv');
		ksort($options);
		$options_keys = implode('', array_keys($options));

		if (!preg_match('/^c?(e|i|i?o|n)[qv]?$/', $options_keys)) {
			exit('usage: php sss.php [-q | -v] [-c config] [-i <logfile or directory>] [-o html]'."\n\n".'See the MANUAL for an overview of all available options.'."\n");
		}

		/**
		 * Read the config file.
		 */
		if (array_key_exists('c', $options)) {
			$this->read_config($options['c']);
		} else {
			$this->read_config(__DIR__.'/sss.conf');
		}

		/**
		 * Apply settings from the config file.
		 */
		$this->apply_settings($this->config);

		/**
		 * Set the timezone.
		 */
		if (!date_default_timezone_set($this->timezone)) {
			output::output('critical', __METHOD__.'(): invalid timezone: \''.$this->timezone.'\'');
		}

		/**
		 * Set the level of output verbosity.
		 */
		if (array_key_exists('q', $options)) {
			output::set_verbosity(0);
		} elseif (array_key_exists('v', $options)) {
			output::set_verbosity(2);
		}

		/**
		 * Open the database connection.
		 */
		try {
			$sqlite3 = new SQLite3($this->database, SQLITE3_OPEN_READWRITE);
			$sqlite3->busyTimeout(60000);
		} catch (Exception $e) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$e->getMessage());
		}

		/**
		 * Setup the SQLite3 connection:
		 *  - Disable the rollback journal.
		 *  - Continue without syncing as soon as data is handed off to the operating
		 *    system.
		 *  - Temporary tables and indices are kept in memory.
		 *  - Enable foreign key constraints.
		 */
		$pragmas = [
			'journal_mode' => 'OFF',
			'synchronous' => 'OFF',
			'temp_store' => 'MEMORY',
			'foreign_keys' => 'ON'];

		foreach ($pragmas as $pragma => $value) {
			$sqlite3->exec('PRAGMA '.$pragma.' = '.$value);
		}

		output::output('notice', __METHOD__.'(): succesfully connected to database: \''.$this->database.'\'');

		if (array_key_exists('e', $options)) {
			$this->export_nicks($sqlite3, $options['e']);
		}

		if (array_key_exists('n', $options)) {
			$this->import_nicks($sqlite3, $options['n']);
			$this->maintenance($sqlite3);
		}

		/**
		 * Below, "i" should execute before "o".
		 */
		if (array_key_exists('i', $options)) {
			$this->parse_log($sqlite3, $options['i']);
		}

		if (array_key_exists('o', $options)) {
			$this->create_html($sqlite3, $options['o']);
		}

		$sqlite3->exec('PRAGMA optimize');
		$sqlite3->close();
		output::output('notice', __METHOD__.'(): kthxbye');
	}

	/**
	 * Maintenance routines should always be run after writing data to the database.
	 */
	private function maintenance(object $sqlite3): void
	{
		/**
		 * Search for new aliases if $autolink_nicks is true.
		 */
		if ($this->autolink_nicks) {
			$this->link_nicks($sqlite3);
		}

		$maintenance = new maintenance($sqlite3);
	}

	private function export_nicks(object $sqlite3, string $file): void
	{
		output::output('notice', __METHOD__.'(): exporting nicks');
		$query = $sqlite3->query('SELECT csnick, ruid, status FROM uid_details ORDER BY csnick ASC') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		if (($result = $query->fetchArray(SQLITE3_ASSOC)) === false) {
			output::output('critical', __METHOD__.'(): database is empty');
		}

		$query->reset();

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			if ($result['status'] === 1 || $result['status'] === 3 || $result['status'] === 4) {
				$registered_nicks[$result['ruid']] = strtolower($result['csnick']);
				$statuses[$result['ruid']] = $result['status'];
			} elseif ($result['status'] === 2) {
				$aliases[$result['ruid']][] = strtolower($result['csnick']);
			} else {
				$unlinked[] = strtolower($result['csnick']);
			}
		}

		$i = 0;
		$output = '';

		if (isset($registered_nicks)) {
			$i += count($registered_nicks);

			foreach ($registered_nicks as $ruid => $nick) {
				$output .= $statuses[$ruid].','.$nick;

				if (isset($aliases[$ruid])) {
					$i += count($aliases[$ruid]);
					$output .= ','.implode(',', $aliases[$ruid]);
				}

				$output .= "\n";
			}
		}

		if (isset($unlinked)) {
			$i += count($unlinked);
			$output .= '*,'.implode(',', $unlinked)."\n";
		}

		if (($fp = fopen($file, 'wb')) === false) {
			output::output('critical', __METHOD__.'(): failed to open file: \''.$file.'\'');
		}

		fwrite($fp, $output);
		fclose($fp);
		output::output('notice', __METHOD__.'(): '.number_format($i).' nicks exported');
	}

	private function import_nicks(object $sqlite3, string $file): void
	{
		output::output('notice', __METHOD__.'(): importing nicks');
		$query = $sqlite3->query('SELECT uid, csnick FROM uid_details') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		if (($result = $query->fetchArray(SQLITE3_ASSOC)) === false) {
			output::output('critical', __METHOD__.'(): database is empty');
		}

		$query->reset();

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$uids[strtolower($result['csnick'])] = $result['uid'];
		}

		if (($rp = realpath($file)) === false) {
			output::output('critical', __METHOD__.'(): no such file: \''.$file.'\'');
		}

		if (($fp = fopen($rp, 'rb')) === false) {
			output::output('critical', __METHOD__.'(): failed to open file: \''.$rp.'\'');
		}

		while (($line = fgets($fp)) !== false) {
			$line = preg_replace('/\s+/', '', $line);

			/**
			 * Skip lines we can't work with.
			 */
			if (!preg_match('/^[134],\S+(,\S+)*$/', $line)) {
				continue;
			}

			$lineparts = explode(',', strtolower($line));
			$status = (int) $lineparts[0];

			/**
			 * The first nick on each line will be the initial registered nick and its uid
			 * will become the ruid to which aliases are linked. If the nick is not in the
			 * database we skip the line.
			 */
			if (array_key_exists($lineparts[1], $uids)) {
				$ruid = $uids[$lineparts[1]];
				$ruids[] = $ruid;
				$statuses[$ruid] = $status;

				for ($i = 2, $j = count($lineparts); $i < $j; ++$i) {
					if (isset($lineparts[$i]) && array_key_exists($lineparts[$i], $uids)) {
						$aliases[$ruid][] = $uids[$lineparts[$i]];
					}
				}
			}
		}

		fclose($fp);

		if (!isset($ruids)) {
			output::output('critical', __METHOD__.'(): no user relations found to import');
		}

		$i = count($ruids);

		/**
		 * Set all nicks to their default status before updating them according to
		 * imported data.
		 */
		$sqlite3->exec('BEGIN TRANSACTION') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$sqlite3->exec('UPDATE uid_details SET ruid = uid, status = 0') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		foreach ($ruids as $ruid) {
			$sqlite3->exec('UPDATE uid_details SET status = '.$statuses[$ruid].' WHERE uid = '.$ruid) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

			if (isset($aliases[$ruid])) {
				$i += count($aliases[$ruid]);
				$sqlite3->exec('UPDATE uid_details SET ruid = '.$ruid.', status = 2 WHERE uid IN ('.implode(',', $aliases[$ruid]).')') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		}

		$sqlite3->exec('COMMIT') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		output::output('notice', __METHOD__.'(): '.number_format($i).' nicks imported');
	}

	/**
	 * This function tries to link unlinked nicks to any other nick that is
	 * identical after stripping them both from any non-alphanumeric characters (at
	 * any position in the nick) and trailing numerics. The results are compared in
	 * a case insensitive manner.
	 */
	private function link_nicks($sqlite3)
	{
		output::output('notice', __METHOD__.'(): looking for possible aliases');
		$query = $sqlite3->query('SELECT uid, csnick, ruid, status FROM uid_details') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$strippednicks = [];

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$nicks[$result['uid']] = [
				'nick' => $result['csnick'],
				'ruid' => $result['ruid'],
				'status' => $result['status']];
			$strippednick = preg_replace(['/[^a-z0-9]/', '/[0-9]+$/'], '', strtolower($result['csnick']));

			/**
			 * The stripped nick must consist of at least two characters.
			 */
			if (strlen($strippednick) >= 2) {
				/**
				 * Maintain an array for each stripped nick, containing the uids of every nick
				 * that matches it. Put the uid of the matching nick at the start of the array
				 * if the nick is already linked (status != 0), otherwise put it at the end.
				 */
				if ($result['status'] !== 0 && isset($strippednicks[$strippednick])) {
					array_unshift($strippednicks[$strippednick], $result['uid']);
				} else {
					$strippednicks[$strippednick][] = $result['uid'];
				}
			}
		}

		$sqlite3->exec('BEGIN TRANSACTION') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		foreach ($strippednicks as $uids) {
			/**
			 * If there is only one match for the stripped nick, there is nothing to link.
			 */
			if (count($uids) === 1) {
				continue;
			}

			$newalias = false;

			for ($i = 1, $j = count($uids); $i < $j; ++$i) {
				/**
				 * Use the ruid that belongs to the first uid in the array to link all
				 * succeeding _unlinked_ nicks to.
				 */
				if ($nicks[$uids[$i]]['status'] === 0) {
					$newalias = true;
					$sqlite3->exec('UPDATE uid_details SET ruid = '.$nicks[$uids[0]]['ruid'].', status = 2 WHERE uid = '.$uids[$i]) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
					output::output('debug', __METHOD__.'(): linked \''.$nicks[$uids[$i]]['nick'].'\' to \''.$nicks[$nicks[$uids[0]]['ruid']]['nick'].'\'');
				}
			}

			/**
			 * If there are aliases found, and the first nick in the array is unlinked
			 * (status = 0), make it a registered nick (status = 1).
			 */
			if ($newalias && $nicks[$uids[0]]['status'] === 0) {
				$sqlite3->exec('UPDATE uid_details SET status = 1 WHERE uid = '.$uids[0]) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		}

		$sqlite3->exec('COMMIT') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
	}

	private function html(object $sqlite3, string $file): void
	{
		$html = new html($this->config);
		$output = $html->html($sqlite3);

		if (($fp = fopen($file, 'wb')) === false) {
			output::output('critical', __METHOD__.'(): failed to open file: \''.$file.'\'');
		}

		fwrite($fp, $output);
		fclose($fp);
	}

	private function parse_log($sqlite3, $filedir)
	{
		if (($rp = realpath($filedir)) === false) {
			output::output('critical', __METHOD__.'(): no such file or directory: \''.$filedir.'\'');
		}

		$files = [];

		if (is_dir($rp)) {
			if (($dh = opendir($rp)) === false) {
				output::output('critical', __METHOD__.'(): failed to open directory: \''.$rp.'\'');
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
			if (($date = date_create_from_format($this->logfile_dateformat, basename($file))) !== false) {
				$logfiles[date_format($date, 'Y-m-d')] = $file;
			}
		}

		if (!isset($logfiles)) {
			output::output('critical', __METHOD__.'(): no logfiles found matching \'logfile_dateformat\' setting');
		}

		/**
		 * Sort the files on the date found in the filename.
		 */
		ksort($logfiles);

		/**
		 * Get the date of the last log parsed.
		 */
		if (($date_lastlogparsed = $sqlite3->querySingle('SELECT MAX(date) FROM parse_history')) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$needmaintenance = false;

		foreach ($logfiles as $date => $logfile) {
			/**
			 * Skip logs that have already been processed.
			 */
			if (!is_null($date_lastlogparsed) && strtotime($date) < strtotime($date_lastlogparsed)) {
				continue;
			}

			$parser = new $this->parser($date, $this->config);

			/**
			 * Get the streak history. This will assume logs are parsed in chronological
			 * order with no gaps.
			 */
			if (($result = $sqlite3->querySingle('SELECT nick_prev, streak FROM streak_history', true)) === false) {
				output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			if (!empty($result)) {
				$parser->set_str('nick_prev', $result['nick_prev']);
				$parser->set_num('streak', $result['streak']);
			}

			/**
			 * Get the parse history and set the line number on which to start parsing the
			 * log.
			 */
			if (($firstline = $sqlite3->querySingle('SELECT lines_parsed FROM parse_history WHERE date = \''.$date.'\'')) === false) {
				output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			if (!is_null($firstline)) {
				++$firstline;
			} else {
				$firstline = 1;
			}

			/**
			 * Check if the log is gzipped and call the appropriate parser.
			 */
			if (preg_match('/\.gz$/', $logfile)) {
				if (!extension_loaded('zlib')) {
					output::output('critical', __METHOD__.'(): zlib extension isn\'t loaded: can\'t parse gzipped logs'."\n");
				}

				$parser->gzparse_log($logfile, $firstline);
			} else {
				$parser->parse_log($logfile, $firstline);
			}

			/**
			 * Update the parse history when there are actual (non-empty) lines parsed.
			 */
			if ($parser->get_num('linenum_lastnonempty') >= $firstline) {
				$sqlite3->exec('INSERT INTO parse_history (date, lines_parsed) VALUES (\''.$date.'\', '.$parser->get_num('linenum_lastnonempty').') ON CONFLICT (date) DO UPDATE SET lines_parsed = '.$parser->get_num('linenum_lastnonempty')) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

				/**
				 * Write data to database and set $needmaintenance to true if there was any data
				 * stored.
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
			$this->maintenance($sqlite3);
		}
	}

	/**
	 * Put settings from the config file into $config[] so they can be passed along
	 * to other classes.
	 */
	private function read_config(string $file): void
	{
		if (($rp = realpath($file)) === false) {
			output::output('critical', __METHOD__.'(): no such file: \''.$file.'\'');
		}

		if (($fp = fopen($rp, 'rb')) === false) {
			output::output('critical', __METHOD__.'(): failed to open file: \''.$rp.'\'');
		}

		while (($line = fgets($fp)) !== false) {
			if (preg_match('/^\s*(?<setting>\w+)\s*=\s*"(?<value>.+?)"/', $line, $matches)) {
				$this->config[$matches['setting']] = $matches['value'];
			}
		}

		fclose($fp);

		/**
		 * Exit if any crucial setting is missing.
		 */
		foreach ($this->settings_required as $setting) {
			if (!array_key_exists($setting, $this->config)) {
				output::output('critical', __METHOD__.'(): missing required setting: \''.$setting.'\'');
			}
		}
	}
}

/**
 * Get ready for the launch.
 */
$sss = new sss();
