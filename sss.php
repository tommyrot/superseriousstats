<?php declare(strict_types=1);

/**
 * Copyright (c) 2009-2020, Jos de Ruijter <jos@dutnie.nl>
 */

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
	exit('>> php\'s sqlite3 extension isn\'t loaded <<'."\n");
}

if (!extension_loaded('mbstring')) {
	exit('>> php\'s mbstring extension isn\'t loaded <<'."\n");
}

if (!extension_loaded('zlib')) {
	exit('>> php\'s zlib extension isn\'t loaded <<'."\n");
}

/**
 * Autoloader. This code handles on the fly inclusion of classes and traits at
 * time of instantiation.
 */
spl_autoload_register(function (string $class): void {
	if (strpos($class, 'parser_') === 0) {
		require_once(__DIR__.'/parsers/'.substr($class, 7).'.php');
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
	private array $settings_allow_override = ['auto_link_nicks', 'database', 'parser', 'timezone'];
	private array $settings_required = ['channel', 'database', 'parser', 'timezone'];
	private bool $auto_link_nicks = true;
	private bool $need_maintenance = false;
	private string $database = '';
	private string $parser = '';
	private string $timezone = '';
	public static SQLite3 $db;

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

	private function create_html(string $file): void
	{
		if (($fp = fopen($file, 'wb')) === false) {
			output::msg('notice', 'failed to open file: \''.$file.'\', cannot create stats page');
			return;
		}

		output::msg('notice', 'creating stats page');
		$html = new html($this->config);
		fwrite($fp, $html->get_contents());
		fclose($fp);
	}

	private function export_nicks(string $file): void
	{
		$total = db::query_single_col('SELECT COUNT(*) FROM uid_details');

		if ($total === 0) {
			output::msg('notice', 'database is empty, nothing to export');
			return;
		}

		output::msg('notice', 'exporting nicks');
		$results = db::query('SELECT status, csnick, (SELECT GROUP_CONCAT(csnick) FROM uid_details WHERE ruid = t1.ruid AND status = 2) AS aliases FROM uid_details AS t1 WHERE status IN (1,3,4) ORDER BY csnick ASC');
		$contents = '';

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			$contents .= $result['status'].','.$result['csnick'].(!is_null($result['aliases']) ? ','.$result['aliases'] : '')."\n";
		}

		$aliases = db::query_single_col('SELECT GROUP_CONCAT(csnick) FROM uid_details WHERE status = 0');

		if (!is_null($aliases)) {
			$contents .= '*,'.$aliases."\n";
		}

		if (($fp = fopen($file, 'wb')) === false) {
			output::msg('critical', 'failed to open file: \''.$file.'\'');
		}

		fwrite($fp, $contents);
		fclose($fp);
		output::msg('debug', $total.' nick'.($total !== 1 ? 's' : '').' exported');
	}

	private function import_nicks(string $file): void
	{
		output::msg('notice', 'importing nicks');

		if (($rp = realpath($file)) === false) {
			output::msg('critical', 'no such file: \''.$file.'\'');
		}

		if (($fp = fopen($rp, 'rb')) === false) {
			output::msg('critical', 'failed to open file: \''.$rp.'\'');
		}

		/**
		 * Set all nicks to their default status before updating them according to
		 * imported data.
		 */
		db::query_exec('UPDATE uid_details SET ruid = uid, status = 0');

		while (($line = fgets($fp)) !== false) {
			$line = preg_replace('/\s+/', '', $line);

			/**
			 * Skip lines we can't work with. This check is very loose and can only save so
			 * many from shooting themselves in the foot.
			 */
			if (!preg_match('/^(?<status>[134]),(?<registered_nick>[^,*\']+)(,(?<aliases>[^,*\']+(,[^,*\']+)*)?)?$/', $line, $matches, PREG_UNMATCHED_AS_NULL)) {
				continue;
			}

			db::query_exec('UPDATE uid_details SET status = '.$matches['status'].' WHERE csnick = \''.$matches['registered_nick'].'\'');

			if (!is_null($matches['aliases'])) {
				db::query_exec('UPDATE OR IGNORE uid_details SET status = 2, ruid = (SELECT uid FROM uid_details WHERE csnick = \''.$matches['registered_nick'].'\') WHERE csnick IN (\''.preg_replace('/,/', '\',\'', $matches['aliases']).'\')');
			}
		}

		fclose($fp);
	}

	/**
	 * This function tries to link unlinked nicks to any other nick that is
	 * identical after stripping them both from any non-alphanumeric characters (at
	 * any position in the nick) and trailing numerics. The results are compared in
	 * a case insensitive manner.
	 */
	private function link_nicks(): void
	{
		$results = db::query('SELECT uid, csnick, ruid, status FROM uid_details');
		$nicks_stripped = [];

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			$nicks[$result['uid']] = [
				'nick' => $result['csnick'],
				'ruid' => $result['ruid'],
				'status' => $result['status']];
			$nick_stripped = preg_replace(['/[^\p{L}\p{N}]/u', '/\p{N}+$/u'], '', mb_strtolower($result['csnick']));

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
					db::query_exec('UPDATE uid_details SET ruid = '.$nicks[$uids[0]]['ruid'].', status = 2 WHERE uid = '.$uids[$i]);
					output::msg('debug', 'linked \''.$nicks[$uids[$i]]['nick'].'\' to \''.$nicks[$nicks[$uids[0]]['ruid']]['nick'].'\'');
				}
			}

			/**
			 * If there are aliases found, and the first nick in the array is unlinked
			 * (status = 0), make it a registered nick (status = 1).
			 */
			if ($new_alias && $nicks[$uids[0]]['status'] === 0) {
				db::query_exec('UPDATE uid_details SET status = 1 WHERE uid = '.$uids[0]);
			}
		}
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
			output::msg('critical', 'invalid timezone: \''.$this->timezone.'\'');
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
		db::set_database($this->database);
		db::connect();

		if (array_key_exists('e', $options)) {
			$this->export_nicks($options['e']);
		}

		if (array_key_exists('n', $options)) {
			$this->import_nicks($options['n']);
			$this->maintenance();
		}

		if (array_key_exists('i', $options)) {
			$this->parse_log($options['i']);

			if ($this->need_maintenance) {
				$this->maintenance();
			}
		}

		if (array_key_exists('o', $options)) {
			$this->create_html($options['o']);
		}

		db::disconnect();
		output::msg('notice', 'kthxbye');
	}

	/**
	 * Maintenance ensures we have a usable, consistent dataset.
	 */
	private function maintenance(): void
	{
		/**
		 * Search for new aliases if $auto_link_nicks is true.
		 */
		if ($this->auto_link_nicks) {
			output::msg('notice', 'looking for possible aliases');
			$this->link_nicks();
		}

		output::msg('notice', 'performing database maintenance routines');
		$maintenance = new maintenance();
	}

	private function parse_log(string $filedir): void
	{
		if (($rp = realpath($filedir)) === false) {
			output::msg('critical', 'no such file or directory: \''.$filedir.'\'');
		}

		$files = [];

		if (is_dir($rp)) {
			if (($dh = opendir($rp)) === false) {
				output::msg('critical', 'failed to open directory: \''.$rp.'\'');
			}

			while (($entry = readdir($dh)) !== false) {
				$entry = realpath($rp.'/'.$entry);

				if (!is_dir($entry)) {
					$files[] = $entry;
				}
			}

			closedir($dh);
		} else {
			$files[] = $rp;
		}

		foreach ($files as $file) {
			/**
			 * Each filename must contain a date formatted like "Ymd" or "Y-m-d".
			 */
			if (preg_match('/(?<!\d)(?<year>\d{4})-?(?<month>\d{2})-?(?<day>\d{2})(?!\d)/', $file, $matches)) {
				$logfiles[$matches['year'].'-'.$matches['month'].'-'.$matches['day']] = $file;
			}
		}

		if (!isset($logfiles)) {
			output::msg('critical', 'no logfiles found having a date in their name (e.g. #chatroom.'.date('Ymd').'.log)');
		}

		/**
		 * Sort the files on the date found in the filename.
		 */
		ksort($logfiles);

		/**
		 * Get the date of the last log parsed.
		 */
		$date_last_log_parsed = db::query_single_col('SELECT MAX(date) FROM parse_history');

		foreach ($logfiles as $date => $logfile) {
			/**
			 * Skip logs that have already been processed.
			 */
			if (!is_null($date_last_log_parsed) && strtotime($date) < strtotime($date_last_log_parsed)) {
				continue;
			}

			$parser = new $this->parser($date);

			/**
			 * Get the streak history. This will assume logs are parsed in chronological
			 * order with no gaps.
			 */
			$result = db::query_single_row('SELECT nick_prev, streak FROM streak_history');

			if (!is_null($result)) {
				$parser->set_str('nick_prev', $result['nick_prev']);
				$parser->set_num('streak', $result['streak']);
			}

			/**
			 * Get the parse history and set the line number on which to start parsing the
			 * log. This would be 1 for a fresh log and +1 for a log with a parse history.
			 */
			$linenum_start = db::query_single_col('SELECT lines_parsed FROM parse_history WHERE date = \''.$date.'\'');

			if (!is_null($linenum_start)) {
				++$linenum_start;
			} else {
				$linenum_start = 1;
			}

			output::msg('notice', 'parsing logfile: \''.$logfile.'\' from line '.$linenum_start);

			/**
			 * Check if the log is gzipped and call the appropriate parser.
			 */
			if (preg_match('/\.gz$/', $logfile)) {
				$parser->gzparse_log($logfile, $linenum_start);
			} else {
				$parser->parse_log($logfile, $linenum_start);
			}

			/**
			 * Update the parse history when there are actual (non-empty) lines parsed.
			 */
			if ($parser->get_num('linenum_last_nonempty') >= $linenum_start) {
				db::query_exec('INSERT INTO parse_history (date, lines_parsed) VALUES (\''.$date.'\', '.$parser->get_num('linenum_last_nonempty').') ON CONFLICT (date) DO UPDATE SET lines_parsed = '.$parser->get_num('linenum_last_nonempty'));

				/**
				 * Write data to database. Set $need_maintenance to true if there has been any
				 * data written to the database.
				 */
				if ($parser->write_data()) {
					$this->need_maintenance = true;
				}
			}
		}
	}

	/**
	 * Put settings from the config file into $config[] so they can be passed along
	 * to other classes.
	 */
	private function read_config(string $file): void
	{
		if (($rp = realpath($file)) === false) {
			output::msg('critical', 'no such file: \''.$file.'\'');
		}

		if (($fp = fopen($rp, 'rb')) === false) {
			output::msg('critical', 'failed to open file: \''.$rp.'\'');
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
				output::msg('critical', 'missing required setting: \''.$setting.'\'');
			}
		}
	}
}

/**
 * Get ready for the launch.
 */
$sss = new sss();
