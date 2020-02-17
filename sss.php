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
	private array $settings_allow_override = ['auto_link_nicks', 'database', 'logfile_date_format', 'parser', 'timezone'];
	private array $settings_required = ['channel', 'database', 'logfile_date_format', 'parser', 'timezone'];
	private bool $auto_link_nicks = true;
	private object $sqlite3;
	private string $database = '';
	private string $logfile_date_format = '';
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
		$this->main();
	}

	/**
	 * Upon class instantiation automatically start the main function below.
	 */
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
			$this->sqlite3 = new SQLite3($this->database, SQLITE3_OPEN_READWRITE);
			$this->sqlite3->busyTimeout(60000);
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
			$this->sqlite3->exec('PRAGMA '.$pragma.' = '.$value);
		}

		output::output('notice', __METHOD__.'(): succesfully connected to database: \''.$this->database.'\'');

		if (array_key_exists('e', $options)) {
			$this->export_nicks($options['e']);
		}

		if (array_key_exists('n', $options)) {
			$this->import_nicks($options['n']);
			$this->maintenance();
		}

		/**
		 * Below, "i" should execute before "o".
		 */
		if (array_key_exists('i', $options)) {
			$this->parse_log($options['i']);
		}

		if (array_key_exists('o', $options)) {
			$this->create_html($options['o']);
		}

		$this->sqlite3->exec('PRAGMA optimize');
		$this->sqlite3->close();
		output::output('notice', __METHOD__.'(): kthxbye');
	}

	/**
	 * Maintenance routines should always be run after writing data to the database.
	 */
	private function maintenance(): void
	{
		/**
		 * Search for new aliases if $auto_link_nicks is true.
		 */
		if ($this->auto_link_nicks) {
			$this->link_nicks();
		}

		$maintenance = new maintenance($this->sqlite3);
	}

	private function export_nicks(string $file): void
	{
		output::output('notice', __METHOD__.'(): exporting nicks');

		if (($total = $this->sqlite3->querySingle('SELECT COUNT(*) FROM uid_details')) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
		}

		if ($total === 0) {
			output::output('critical', __METHOD__.'(): database is empty');
		}

		$query = $this->sqlite3->query('SELECT status, csnick, (SELECT GROUP_CONCAT(csnick) FROM uid_details WHERE ruid = t1.ruid AND status = 2) AS aliases FROM uid_details AS t1 WHERE status IN (1,3,4) ORDER BY csnick ASC') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
		$contents = '';

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$contents .= $result['status'].','.$result['csnick'].(!is_null($result['aliases']) ? ','.$result['aliases'] : '')."\n";
		}

		if (($aliases = $this->sqlite3->querySingle('SELECT GROUP_CONCAT(csnick) FROM uid_details WHERE status = 0')) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
		}

		if (!is_null($aliases)) {
			$contents .= '*,'.$aliases."\n";
		}

		if (($fp = fopen($file, 'wb')) === false) {
			output::output('critical', __METHOD__.'(): failed to open file: \''.$file.'\'');
		}

		fwrite($fp, $contents);
		fclose($fp);
		output::output('debug', __METHOD__.'(): '.$total.' nick'.($total !== 1 ? 's' : '').' exported');
	}

	private function import_nicks(string $file): void
	{
		output::output('notice', __METHOD__.'(): importing nicks');

		if (($rp = realpath($file)) === false) {
			output::output('critical', __METHOD__.'(): no such file: \''.$file.'\'');
		}

		if (($fp = fopen($rp, 'rb')) === false) {
			output::output('critical', __METHOD__.'(): failed to open file: \''.$rp.'\'');
		}

		/**
		 * Set all nicks to their default status before updating them according to
		 * imported data.
		 */
		$this->sqlite3->exec('BEGIN TRANSACTION') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
		$this->sqlite3->exec('UPDATE uid_details SET ruid = uid, status = 0') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());

		while (($line = fgets($fp)) !== false) {
			$line = preg_replace('/\s+/', '', $line);

			/**
			 * Skip lines we can't work with.
			 */
			if (!preg_match('/^(?<status>[134]),(?<registered_nick>[^,*\s]+)(,(?<aliases>[^,*\s]+(,[^,*\s]+)*))?$/', $line, $matches, PREG_UNMATCHED_AS_NULL)) {
				continue;
			}

			$this->sqlite3->exec('UPDATE uid_details SET status = '.$matches['status'].' WHERE csnick = \''.$matches['registered_nick'].'\'') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());

			if (!is_null($matches['aliases'])) {
				$this->sqlite3->exec('UPDATE OR IGNORE uid_details SET status = 2, ruid = (SELECT uid FROM uid_details WHERE csnick = \''.$matches['registered_nick'].'\') WHERE csnick IN (\''.preg_replace('/,/', '\',\'', $matches['aliases']).'\')') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
			}
		}

		fclose($fp);
		$this->sqlite3->exec('COMMIT') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
	}

	/**
	 * This function tries to link unlinked nicks to any other nick that is
	 * identical after stripping them both from any non-alphanumeric characters (at
	 * any position in the nick) and trailing numerics. The results are compared in
	 * a case insensitive manner.
	 */
	private function link_nicks()
	{
		output::output('notice', __METHOD__.'(): looking for possible aliases');
		$query = $this->sqlite3->query('SELECT uid, csnick, ruid, status FROM uid_details') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
		$nicks_stripped = [];

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$nicks[$result['uid']] = [
				'nick' => $result['csnick'],
				'ruid' => $result['ruid'],
				'status' => $result['status']];
			$nick_stripped = preg_replace(['/[^a-z0-9]/', '/[0-9]+$/'], '', strtolower($result['csnick']));

			/**
			 * The stripped nick must consist of at least two characters.
			 */
			if (strlen($nick_stripped) >= 2) {
				/**
				 * Maintain an array for each stripped nick, containing the uids of every nick
				 * that matches it. Put the uid of the matching nick at the start of the array
				 * if the nick is already linked (status != 0), otherwise put it at the end.
				 */
				if ($result['status'] !== 0 && isset($nicks_stripped[$nick_stripped])) {
					array_unshift($nicks_stripped[$nick_stripped], $result['uid']);
				} else {
					$nicks_stripped[$nick_stripped][] = $result['uid'];
				}
			}
		}

		$this->sqlite3->exec('BEGIN TRANSACTION') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());

		foreach ($nicks_stripped as $uids) {
			/**
			 * If there is only one match for the stripped nick, there is nothing to link.
			 */
			if (count($uids) === 1) {
				continue;
			}

			$new_alias = false;

			for ($i = 1, $j = count($uids); $i < $j; ++$i) {
				/**
				 * Use the ruid that belongs to the first uid in the array to link all
				 * succeeding _unlinked_ nicks to.
				 */
				if ($nicks[$uids[$i]]['status'] === 0) {
					$new_alias = true;
					$this->sqlite3->exec('UPDATE uid_details SET ruid = '.$nicks[$uids[0]]['ruid'].', status = 2 WHERE uid = '.$uids[$i]) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
					output::output('debug', __METHOD__.'(): linked \''.$nicks[$uids[$i]]['nick'].'\' to \''.$nicks[$nicks[$uids[0]]['ruid']]['nick'].'\'');
				}
			}

			/**
			 * If there are aliases found, and the first nick in the array is unlinked
			 * (status = 0), make it a registered nick (status = 1).
			 */
			if ($new_alias && $nicks[$uids[0]]['status'] === 0) {
				$this->sqlite3->exec('UPDATE uid_details SET status = 1 WHERE uid = '.$uids[0]) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
			}
		}

		$this->sqlite3->exec('COMMIT') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
	}

	private function create_html(string $file): void
	{
		$html = new html($this->config, $this->sqlite3);

		if (($fp = fopen($file, 'wb')) === false) {
			output::output('critical', __METHOD__.'(): failed to open file: \''.$file.'\'');
		}

		fwrite($fp, $html->get_contents());
		fclose($fp);
	}

	private function parse_log(string $filedir): void
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
			 * The filenames should match the pattern provided by $logfile_date_format.
			 */
			if (($date = date_create_from_format($this->logfile_date_format, basename($file))) !== false) {
				$logfiles[date_format($date, 'Y-m-d')] = $file;
			}
		}

		if (!isset($logfiles)) {
			output::output('critical', __METHOD__.'(): no logfiles found matching \'logfile_date_format\' setting');
		}

		/**
		 * Sort the files on the date found in the filename.
		 */
		ksort($logfiles);

		/**
		 * Get the date of the last log parsed.
		 */
		if (($date_lastlogparsed = $this->sqlite3->querySingle('SELECT MAX(date) FROM parse_history')) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
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
			if (($result = $this->sqlite3->querySingle('SELECT nick_prev, streak FROM streak_history', true)) === false) {
				output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
			}

			if (!empty($result)) {
				$parser->set_str('nick_prev', $result['nick_prev']);
				$parser->set_num('streak', $result['streak']);
			}

			/**
			 * Get the parse history and set the line number on which to start parsing the
			 * log.
			 */
			if (($firstline = $this->sqlite3->querySingle('SELECT lines_parsed FROM parse_history WHERE date = \''.$date.'\'')) === false) {
				output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
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
			if ($parser->get_num('linenum_last_nonempty') >= $firstline) {
				$this->sqlite3->exec('INSERT INTO parse_history (date, lines_parsed) VALUES (\''.$date.'\', '.$parser->get_num('linenum_last_nonempty').') ON CONFLICT (date) DO UPDATE SET lines_parsed = '.$parser->get_num('linenum_last_nonempty')) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());

				/**
				 * Write data to database and set $needmaintenance to true if there was any data
				 * stored.
				 */
				if ($parser->write_data($this->sqlite3)) {
					$needmaintenance = true;
				}
			}
		}

		/**
		 * Finally, call maintenance if needed.
		 */
		if ($needmaintenance) {
			$this->maintenance();
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
