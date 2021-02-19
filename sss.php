<?php declare(strict_types=1);

/**
 * Copyright (c) 2009-2021, Jos de Ruijter <jos@dutnie.nl>
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
foreach (['sqlite3', 'mbstring'] as $module) {
	extension_loaded($module) or exit('>> php\'s '.$module.' extension isn\'t loaded <<'."\n");
}

/**
 * Autoloader. This code handles on the fly inclusion of classes and traits at
 * time of instantiation.
 */
spl_autoload_register(function (string $class): void {
	if (str_starts_with($class, 'parser_')) {
		require __DIR__.'/parsers/'.substr($class, 7).'.php';
	} else {
		require __DIR__.'/'.$class.'.php';
	}
});

/**
 * Read options from the command line. Show a hint on invalid input.
 */
$options = getopt('c:e:i:m:o:qv');
ksort($options);
preg_match('/^c?(e|i|i?o|m)[qv]?$/', implode('', array_keys($options))) or exit('usage: php sss.php [-q | -v] [-c config] [-i <logfile or directory>] [-o html]'."\n");

/**
 * Main class.
 */
class sss
{
	use common;

	private bool $need_maintenance = false;
	private bool $with_zlib = false;
	private string $database = '';
	private string $parser = '';
	private string $timezone = '';

	public function __construct(array $options)
	{
		/**
		 * Explicitly set the locale to C (POSIX) for all categories so there hopefully
		 * won't be any unexpected results between platforms.
		 */
		setlocale(LC_ALL, 'C');

		/**
		 * Use UTC until config specified timezone is set.
		 */
		date_default_timezone_set('UTC');

		/**
		 * Set the character encoding used by all mbstring functions.
		 */
		mb_internal_encoding('UTF-8');

		/**
		 * Set output verbosity if applicable.
		 */
		if (isset($options['q'])) {
			out::set_verbosity(0);
		} elseif (isset($options['v'])) {
			out::set_verbosity(2);
		}

		/**
		 * Read either the user provided config file or the default one.
		 */
		$settings = $this->read_config($options['c'] ?? __DIR__.'/sss.conf');

		/**
		 * Set the proper timezone.
		 */
		date_default_timezone_set($this->timezone) or out::put('critical', 'invalid timezone: \''.$this->timezone.'\'');
		out::put('debug', 'timezone set to: \''.$this->timezone.'\'');

		/**
		 * Check if the zlib extension is loaded.
		 */
		if (extension_loaded('zlib')) {
			$this->with_zlib = true;
		} else {
			out::put('notice', 'php\'s zlib extension isn\'t loaded, skipping any gzipped logs');
		}

		/**
		 * Open the database connection and store config settings.
		 */
		db::set_database($this->database);
		db::connect();
		db::query_exec('DELETE FROM settings');

		foreach ($settings as $setting => $value) {
			db::query_exec('INSERT INTO settings (setting, value) VALUES (\''.$setting.'\', \''.preg_replace('/\'/', '\'\'', $value).'\')');
		}

		/**
		 * Init done, move to main.
		 */
		$this->main($options);

		/**
		 * Synchronize and finalize.
		 */
		db::disconnect();
		out::put('notice', 'kthxbye');
	}

	private function create_html(string $file): void
	{
		if (($fp = fopen($file, 'wb')) === false) {
			out::put('notice', 'failed to open file: \''.$file.'\', cannot create stats page');
			return;
		}

		out::put('notice', 'creating stats page');
		$html = new html();
		fwrite($fp, $html->get_contents());
		fclose($fp);
	}

	private function export_nicks(string $file): void
	{
		if (($total = db::query_single_col('SELECT COUNT(*) FROM uid_details')) === 0) {
			out::put('notice', 'database is empty, nothing to export');
			return;
		}

		out::put('notice', 'exporting nicks');
		$results = db::query('SELECT status, csnick, (SELECT GROUP_CONCAT(csnick) FROM uid_details WHERE ruid = t1.ruid AND status = 2) AS aliases FROM uid_details AS t1 WHERE status IN (1,3,4) ORDER BY csnick ASC');
		$contents = '';

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			$contents .= $result['status'].','.$result['csnick'].(!is_null($result['aliases']) ? ','.$result['aliases'] : '')."\n";
		}

		if (!is_null($unlinked = db::query_single_col('SELECT GROUP_CONCAT(csnick) FROM uid_details WHERE status = 0'))) {
			$contents .= '*,'.$unlinked."\n";
		}

		if (($fp = fopen($file, 'wb')) === false) {
			out::put('critical', 'failed to open file: \''.$file.'\'');
		}

		fwrite($fp, $contents);
		fclose($fp);
		out::put('debug', $total.' nick'.($total !== 1 ? 's' : '').' exported');
	}

	private function import_nicks(string $file): void
	{
		out::put('notice', 'importing nicks');

		if (($rp = realpath($file)) === false) {
			out::put('critical', 'no such file: \''.$file.'\'');
		}

		if (($fp = fopen($rp, 'rb')) === false) {
			out::put('critical', 'failed to open file: \''.$rp.'\'');
		}

		/**
		 * Set all nicks to their default state before updating them according to
		 * imported data.
		 */
		db::query_exec('UPDATE uid_details SET ruid = uid, status = 0');

		while (($line = fgets($fp)) !== false) {
			/**
			 * Skip lines we can't work with. This check is very loose and can only save so
			 * many from shooting themselves in the foot.
			 */
			if (!preg_match('/^(?<status>[134]),(?<registered_nick>[^,*\']+)(,(?<aliases>[^,*\']+(,[^,*\']+)*)?)?$/', preg_replace('/\s+/', '', $line), $matches, PREG_UNMATCHED_AS_NULL)) {
				continue;
			}

			db::query_exec('UPDATE uid_details SET status = '.$matches['status'].' WHERE csnick = \''.$matches['registered_nick'].'\'');

			if (!is_null($matches['aliases'])) {
				db::query_exec('UPDATE uid_details SET status = 2, ruid = (SELECT uid FROM uid_details WHERE csnick = \''.$matches['registered_nick'].'\') WHERE csnick IN (\''.preg_replace('/,/', '\',\'', $matches['aliases']).'\')');
			}
		}

		fclose($fp);
	}

	/**
	 * Take action based on given command line arguments.
	 */
	private function main(array $options): void
	{
		if (isset($options['e'])) {
			$this->export_nicks($options['e']);
		}

		if (isset($options['m'])) {
			$this->import_nicks($options['m']);
			$this->need_maintenance = true;
		}

		if (isset($options['i'])) {
			$this->parse_log($options['i']);
		}

		if ($this->need_maintenance) {
			$maintenance = new maintenance();
		}

		if (isset($options['o'])) {
			$this->create_html($options['o']);
		}
	}

	private function parse_log(string $filedir): void
	{
		if (($rp = realpath($filedir)) === false) {
			out::put('critical', 'no such file or directory: \''.$filedir.'\'');
		}

		$files = [];

		if (is_dir($rp)) {
			if (($dh = opendir($rp)) === false) {
				out::put('critical', 'failed to open directory: \''.$rp.'\'');
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
				if (preg_match('/\.gz$/', $file) && !$this->with_zlib) {
					continue;
				}

				$logfiles[$matches['year'].'-'.$matches['month'].'-'.$matches['day']] = $file;
			}
		}

		if (!isset($logfiles)) {
			out::put('critical', 'no logfiles found having a date in their name (e.g. #chatroom.'.date('Ymd').'.log)');
		}

		/**
		 * Sort the files on the date found in the filename.
		 */
		ksort($logfiles);

		foreach ($logfiles as $date => $logfile) {
			/**
			 * Skip logs that have already been processed.
			 */
			if (!is_null($date_last_log_parsed = db::query_single_col('SELECT MAX(date) FROM parse_history')) && $date < $date_last_log_parsed) {
				continue;
			}

			$parser = new $this->parser($date);

			/**
			 * Get the streak history. This will assume logs are parsed in chronological
			 * order with no gaps.
			 */
			if (!is_null($result = db::query_single_row('SELECT nick_prev, streak FROM parse_state'))) {
				$parser->set_string('nick_prev', $result['nick_prev']);
				$parser->set_int('streak', $result['streak']);
			}

			/**
			 * Get the parse history and set the line number on which to start parsing the
			 * log. This would be 1 for a fresh log and +1 for a log with a parse history.
			 */
			if (!is_null($linenum_start = db::query_single_col('SELECT lines_parsed FROM parse_history WHERE date = \''.$date.'\''))) {
				++$linenum_start;
			} else {
				$linenum_start = 1;
			}

			out::put('notice', 'parsing logfile: \''.$logfile.'\' from line '.$linenum_start);
			$parser->parse_log($logfile, $linenum_start, (preg_match('/\.gz$/', $logfile) ? true : false));

			/**
			 * Update the parse history when there are actual (non-empty) lines parsed.
			 */
			if ($parser->get_int('linenum_last_nonempty') >= $linenum_start) {
				db::query_exec('INSERT INTO parse_history (date, lines_parsed) VALUES (\''.$date.'\', '.$parser->get_int('linenum_last_nonempty').') ON CONFLICT (date) DO UPDATE SET lines_parsed = excluded.lines_parsed');

				/**
				 * Store data in the database. We will need maintenance if any data is stored.
				 */
				if ($parser->store_data()) {
					$this->need_maintenance = true;
				}
			}
		}
	}

	/**
	 * Read settings from the config file.
	 */
	private function read_config(string $file): array
	{
		if (($rp = realpath($file)) === false) {
			out::put('critical', 'no such file: \''.$file.'\'');
		}

		if (($fp = fopen($rp, 'rb')) === false) {
			out::put('critical', 'failed to open file: \''.$rp.'\'');
		}

		$settings_required = ['database', 'parser', 'timezone'];

		while (($line = fgets($fp)) !== false) {
			if (!preg_match('/^\s*(?<setting>\w+)\s*=\s*"(?<value>.+?)"/', $line, $matches)) {
				continue;
			}

			$setting = $matches['setting'];
			$value = $matches['value'];
			$settings[$setting] = $value;

			/**
			 * Apply and keep track of required settings.
			 */
			if (in_array($setting, $settings_required)) {
				$settings_missing = array_diff($settings_missing ?? $settings_required, [$setting]);
				$this->$setting = $value;
			}
		}

		fclose($fp);

		/**
		 * Check if all required settings were present.
		 */
		if (!empty($settings_missing)) {
			out::put('critical', 'missing required setting'.(count($settings_missing) !== 1 ? 's' : '').': \''.implode('\', \'', $settings_missing).'\'');
		}

		return $settings;
	}
}

/**
 * Launch superseriousstats!
 */
$sss = new sss($options);
