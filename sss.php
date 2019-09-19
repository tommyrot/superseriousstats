<?php

/**
 * Copyright (c) 2009-2019, Jos de Ruijter <jos@dutnie.nl>
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
	require_once(__DIR__.'/'.$class.'.php');
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
	private $autolink_nicks = true;
	private $config = [];
	private $database = 'sss.db3';
	private $logfile_dateformat = '';
	private $outputbits = 1;
	private $parser = '';
	private $settings_allow_override = [
		'autolink_nicks' => 'boolean',
		'database' => 'string',
		'logfile_dateformat' => 'string',
		'outputbits' => 'integer',
		'parser' => 'string',
		'timezone' => 'string'];
	private $settings_required = [];
	private $timezone = 'UTC';

	public function __construct()
	{
		/**
		 * Explicitly set the locale to C (POSIX) for all categories so there hopefully
		 * won't be any unexpected results between platforms.
		 */
		setlocale(LC_ALL, 'C');

		/**
		 * Use the default value until a user specified timezone is loaded.
		 */
		date_default_timezone_set($this->timezone);

		/**
		 * Read options from the command line. Print the manual on invalid input.
		 */
		$options = getopt('b:c:e:i:n:o:qs');
		ksort($options);
		$options_keys = implode('', array_keys($options));

		if (!preg_match('/^(bc?i?oq?|cq?|c?(e|i|i?o|n)q?|c?s)$/', $options_keys)) {
			$this->print_manual();
		}

		/**
		 * Some options require additional settings to be set in the config file.
		 * Add those to $settings_required[].
		 */
		if (array_key_exists('i', $options)) {
			array_push($this->settings_required, 'parser', 'logfile_dateformat');
		}

		if (array_key_exists('o', $options) || array_key_exists('s', $options)) {
			$this->settings_required[] = 'channel';
		}

		/**
		 * Read the config file and have settings take effect.
		 */
		if (array_key_exists('c', $options)) {
			$this->read_config($options['c']);
		} else {
			$this->read_config(__DIR__.'/sss.conf');
		}

		/**
		 * After reading the config file we can now update the timezone.
		 */
		if (!date_default_timezone_set($this->timezone)) {
			output::output('critical', __METHOD__.'(): invalid timezone: \''.$this->timezone.'\'');
		}

		/**
		 * Up until this point the value of $outputbits didn't matter as there could
		 * have been only critical messages which always display (even in quiet mode).
		 */
		if (array_key_exists('q', $options)) {
			output::set_outputbits(0);
		} else {
			output::set_outputbits($this->outputbits);
		}

		/**
		 * Export settings from the config file in the format vars.php accepts them.
		 */
		if (array_key_exists('s', $options)) {
			$this->export_settings();
		}

		/**
		 * Open the database connection. Always needed from this point forward.
		 */
		try {
			$sqlite3 = new SQLite3($this->database, SQLITE3_OPEN_READONLY);
			$sqlite3->busyTimeout(60000);
		} catch (Exception $e) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$e->getMessage());
		}

		/**
		 * Set SQLite3 PRAGMAs:
		 *  journal_mode = OFF - Disable the rollback journal completely.
		 *  synchronous = OFF - Continue without syncing as soon as data is handed off
		 *                       to the operating system.
		 *  temp_store = MEMORY - Temporary tables and indices are kept in memory.
		 */
		$pragmas = [
			'journal_mode' => 'OFF',
			'synchronous' => 'OFF',
			'temp_store' => 'MEMORY'];

		foreach ($pragmas as $key => $value) {
			$sqlite3->exec('PRAGMA '.$key.' = '.$value);
		}

		output::output('notice', __METHOD__.'(): succesfully connected to database: \''.$this->database.'\'');

		/**
		 * The following options are listed in order of execution. Ie. "i" before "o",
		 * "b" before "o".
		 */
		if (array_key_exists('b', $options) && preg_match('/^\d+$/', $options['b'])) {
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
			$this->maintenance($sqlite3);
		}

		if (array_key_exists('o', $options)) {
			$this->html($sqlite3, $options['o']);
		}

		$sqlite3->close();
		output::output('notice', __METHOD__.'(): kthxbye');
	}

	/**
	 * The maintenance routines ensure that all relevant user data is accumulated
	 * properly.
	 */
	private function maintenance(object $sqlite3): void
	{
		/**
		 * Search for new aliases if $autolink_nicks is enabled.
		 */
		if ($this->autolink_nicks) {
			$this->link_nicks($sqlite3);
		}

		$maintenance = new maintenance($this->config);
		$maintenance->maintenance($sqlite3);
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
				$registerednicks[$result['ruid']] = strtolower($result['csnick']);
				$statuses[$result['ruid']] = $result['status'];
			} elseif ($result['status'] === 2) {
				$aliases[$result['ruid']][] = strtolower($result['csnick']);
			} else {
				$unlinked[] = strtolower($result['csnick']);
			}
		}

		$i = 0;
		$output = '';

		if (isset($registerednicks)) {
			$i += count($registerednicks);

			foreach ($registerednicks as $ruid => $nick) {
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

	private function export_settings()
	{
		/**
		 * The following is a list of settings accepted by history.php and/or user.php
		 * along with their type.
		 */
		$settings_list = [
			'channel' => 'string',
			'database' => 'string',
			'mainpage' => 'string',
			'maxrows_people_month' => 'int',
			'maxrows_people_timeofday' => 'int',
			'maxrows_people_year' => 'int',
			'rankings' => 'bool',
			'stylesheet' => 'string',
			'timezone' => 'string',
			'userstats' => 'bool',
			'userpics' => 'bool',
			'userpics_dir' => 'string',
			'userpics_default' => 'string'];
		$vars = '$settings[\''.(isset($this->settings['cid']) ? $this->settings['cid'] : $this->settings['channel']).'\'] = [';

		foreach ($settings_list as $key => $type) {
			if (!array_key_exists($key, $this->settings)) {
				continue;
			}

			if ($type === 'string') {
				$vars .= "\n\t".'\''.$key.'\' => \''.$this->settings[$key].'\',';
			} elseif ($type === 'int' && preg_match('/^\d+$/', $this->settings[$key])) {
				$vars .= "\n\t".'\''.$key.'\' => '.$this->settings[$key].',';
			} elseif ($type === 'bool' && preg_match('/^(true|false)$/i', $this->settings[$key])) {
				$vars .= "\n\t".'\''.$key.'\' => '.strtolower($this->settings[$key]).',';
			}
		}

		exit(rtrim($vars, ',').'];'."\n");
	}

	private function import_nicks($sqlite3, $file)
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
			$line = preg_replace('/\s/', '', $line);

			/**
			 * Skip lines which we can't interpret.
			 */
			if (!preg_match('/^[134],\S+(,\S+)*$/', $line)) {
				continue;
			}

			$lineparts = explode(',', strtolower($line));
			$status = (int) $lineparts[0];

			/**
			 * The first nick on each line will be the initial registered nick and its uid
			 * will become the ruid to which aliases are linked.
			 */
			if (array_key_exists($lineparts[1], $uids)) {
				$ruid = $uids[$lineparts[1]];
				$ruids[] = $ruid;
				$statuses[$ruid] = $status;

				for ($i = 2, $j = count($lineparts); $i < $j; $i++) {
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

			for ($i = 1, $j = count($uids); $i < $j; $i++) {
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

			$parser = new $this->parser($this->settings);
			$parser->set_str('date', $date);

			/**
			 * Get the streak history. This will assume logs are parsed in chronological
			 * order with no gaps.
			 */
			if (($result = $sqlite3->querySingle('SELECT prevnick, streak FROM streak_history', true)) === false) {
				output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			if (!empty($result)) {
				$parser->set_str('prevnick', $result['prevnick']);
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
				$firstline++;
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
				$sqlite3->exec('INSERT OR IGNORE INTO parse_history (date, lines_parsed) VALUES (\''.$date.'\', '.$parser->get_num('linenum_lastnonempty').')') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
				$sqlite3->exec('UPDATE parse_history SET lines_parsed = '.$parser->get_num('linenum_lastnonempty').' WHERE CHANGES() = 0 AND date = \''.$date.'\'') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

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
			$this->do_maintenance($sqlite3);
		}
	}

	private function print_manual(): void
	{
		$man = 'usage:  php sss.php [-c <file>] [-i <file|directory>]'."\n"
			. '                    [-o <file> [-b <numbits>]] [-q]'."\n\n"
			. 'See the MANUAL file for an overview of all available options.'."\n";
		exit($man);
	}

	/**
	 * Read and apply settings from the config file and put them into $config[] so
	 * they can be passed along to other classes.
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
			if (preg_match('/^\s*(?<setting>\w+)\s*=\s*"(?<value>([^\s"]+( [^\s"]+)*))"/', $line, $matches)) {
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

		/**
		 * Apply settings from the config file.
		 */
		$this->apply_settings($this->config);
	}
}

/**
 * Get ready for the launch.
 */
$sss = new sss();
