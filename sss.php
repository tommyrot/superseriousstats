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

if (!extension_loaded('sqlite3')) {
	exit('sqlite3 extension isn\'t loaded'."\n");
}

if (!extension_loaded('mbstring')) {
	exit('mbstring extension isn\'t loaded'."\n");
}

/**
 * Class autoloader, new style. Important piece of code right here.
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
	 * Default settings for this script, can be overridden in the config file. These should all appear in $settings_list[] along with their type.
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
		 * Explicitly set the locale to C (POSIX) for all categories so we won't run into unexpected results between platforms.
		 */
		setlocale(LC_ALL, 'C');

		/**
		 * Use UTC until user specified timezone is loaded.
		 */
		date_default_timezone_set('UTC');

		/**
		 * Read options from the command line. If an illegal combination of valid options is given the program will print the manual on screen and exit.
		 */
		$options = getopt('b:c:e:i:mn:o:s');
		ksort($options);
		$options_keys = implode('', array_keys($options));

		if (!preg_match('/^(bc?i?o|c|c?(e|i|i?o|m|n|s))$/', $options_keys)) {
			$this->print_manual();
		}

		/**
		 * Some options require additional settings to be set in the configuration file.
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
		 * Make the database connection. Always needed.
		 */
		try {
			$sqlite3 = new SQLite3($this->database, SQLITE3_OPEN_READWRITE);
		} catch (Exception $e) {
			$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$e->getMessage());
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

		if (array_key_exists('m', $options)) {
			$this->do_maintenance($sqlite3);
		}

		if (array_key_exists('n', $options)) {
			$this->import_nicks($sqlite3, $options['n']);
		}

		if (array_key_exists('o', $options)) {
			$this->make_html($sqlite3, $options['o']);
		}

		$sqlite3->close();
	}

	private function do_maintenance($sqlite3)
	{
		/**
		 * Scan for new aliases when $autolinknicks is enabled.
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
		$query = @mysqli_query($this->mysqli, 'select `user_details`.`uid`, `ruid`, `csnick`, `status` from `user_details` join `user_status` on `user_details`.`uid` = `user_status`.`uid` order by `csnick` asc') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			$this->output('warning', 'export_nicks(): database is empty, nothing to do');
			return null;
		}

		while ($result = mysqli_fetch_object($query)) {
			if ((int) $result->status == 1 || (int) $result->status == 3) {
				$registered[strtolower($result->csnick)] = (int) $result->uid;
				$statuses[(int) $result->uid] = (int) $result->status;
			} elseif ((int) $result->status == 2) {
				$aliases[(int) $result->ruid][] = strtolower($result->csnick);
			} else {
				$unlinked[] = strtolower($result->csnick);
			}
		}

		$output = '';
		$i = 0;

		if (!empty($registered)) {
			foreach ($registered as $user => $uid) {
				$output .= $statuses[$uid].','.$user;
				$i++;

				if (!empty($aliases[$uid])) {
					foreach ($aliases[$uid] as $alias) {
						$output .= ','.$alias;
						$i++;
					}
				}

				$output .= "\n";
			}
		}

		if (!empty($unlinked)) {
			$output .= '*';

			foreach ($unlinked as $nick) {
				$output .= ','.$nick;
				$i++;
			}

			$output .= "\n";
		}

		if ($i != $rows) {
			$this->output('critical', 'export_nicks(): something is wrong, run "php sss.php -m" before export');
		}

		if (($fp = @fopen($file, 'wb')) === false) {
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
		$query = @mysqli_query($this->mysqli, 'select `uid`, `csnick` from `user_details`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			$this->output('warning', 'import_nicks(): database is empty, nothing to do');
			return null;
		}

		while ($result = mysqli_fetch_object($query)) {
			$uids[strtolower($result->csnick)] = (int) $result->uid;
		}

		if (($rp = realpath($file)) === false) {
			$this->output('critical', 'import_nicks(): no such file: \''.$file.'\'');
		}

		if (($fp = @fopen($rp, 'rb')) === false) {
			$this->output('critical', 'import_nicks(): failed to open file: \''.$file.'\'');
		}

		while (!feof($fp)) {
			$line = fgets($fp);
			$line = preg_replace('/\s/', '', $line);
			$lineparts = explode(',', strtolower($line));

			/**
			 * First nick on each line is the initial registered nick which aliases are linked to.
			 */
			if (((int) $lineparts[0] == 1 || (int) $lineparts[0] == 3) && !empty($lineparts[1]) && array_key_exists($lineparts[1], $uids)) {
				$uid = $uids[$lineparts[1]];
				$registered[] = $uid;
				$statuses[$uid] = (int) $lineparts[0];

				for ($i = 2, $j = count($lineparts); $i < $j; $i++) {
					if (!empty($lineparts[$i]) && array_key_exists($lineparts[$i], $uids)) {
						$aliases[$uid][] = $uids[$lineparts[$i]];
					}
				}
			}
		}

		fclose($fp);

		if (empty($registered)) {
			$this->output('warning', 'import_nicks(): no user relations found to import');
		} else {
			/**
			 * Set all nicks to their default status before updating them according to new data.
			 */
			@mysqli_query($this->mysqli, 'update `user_status` set `ruid` = `uid`, `status` = 0') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));

			foreach ($registered as $uid) {
				@mysqli_query($this->mysqli, 'update `user_status` set `ruid` = `uid`, `status` = '.$statuses[$uid].' where `uid` = '.$uid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));

				if (!empty($aliases[$uid])) {
					@mysqli_query($this->mysqli, 'update `user_status` set `ruid` = '.$uid.', `status` = 2 where `uid` in ('.implode(',', $aliases[$uid]).')') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				}
			}

			$this->output('notice', 'import_nicks(): import completed, don\'t forget to run "php sss.php -m"');
		}
	}

	private function link_nicks($sqlite3)
	{
		/**
		 * This function tries to link unlinked nicks to any other nick that is identical after stripping them from non-alphanumeric characters (at any
		 * position in the nick) and numerics (only at the end of the nick). The results are compared in a case insensitive manner.
		 *
		 * Example before:
		 *
		 * | nick	| uid		| ruid		| status	| description
		 * +------------+---------------+---------------+---------------+----------------------------------
		 * | Jack	| 80		| 80		| 1		| registered nick (linked manually)
		 * | Jack|away	| 120		| 80		| 2		| alias (linked manually)
		 * | Jack-away	| 550		| 550		| 0		| unlinked nick
		 * | Jack|4w4y	| 551		| 551		| 0		| unlinked nick
		 * | ^jack^	| 552		| 552		| 0		| unlinked nick
		 * | Jack|brb	| 553		| 553		| 0		| unlinked nick
		 * | Jack[brb]	| 554		| 554		| 0		| unlinked nick
		 * | Jack^1337^	| 555		| 555		| 0		| unlinked nick
		 *
		 *
		 * Example after:
		 *
		 * | nick	| uid		| ruid		| status	| description
		 * +------------+---------------+---------------+---------------+--------------------------------------------
		 * | Jack	| 80		| 80		| 1		| registered nick
		 * | Jack|away	| 120		| 80		| 2		| existing alias
		 * | Jack-away	| 550		| 80		| 2		| new alias pointing to the ruid of its match
		 * | Jack|4w4y	| 551		| 551		| 0		| remains unlinked
		 * | ^jack^	| 552		| 80		| 2		| new alias
		 * | Jack|brb	| 553		| 553		| 1		| new registered nick
		 * | Jack[brb]	| 554		| 553		| 2		| new alias
		 * | Jack^1337^ | 555		| 80		| 2		| new alias
		 *
		 * This method has very little false positives and is therefore enabled by default.
		 */
		$this->output('notice', 'link_nicks(): looking for possible aliases');
		$query = @mysqli_query($this->mysqli, 'select `user_details`.`uid`, `ruid`, `csnick`, `status` from `user_details` join `user_status` on `user_details`.`uid` = `user_status`.`uid`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			$this->output('warning', 'link_nicks(): database is empty, nothing to do');
			return null;
		}

		$strippednicks = array();

		while ($result = mysqli_fetch_object($query)) {
			$nicks[(int) $result->uid] = array(
				'nick' => $result->csnick,
				'ruid' => (int) $result->ruid,
				'status' => (int) $result->status);

			/**
			 * We keep an array with uids for each stripped nick. If we encounter a linked nick we put its uid at the start of the array, otherwise
			 * just append the uid.
			 */
			$strippednick = preg_replace(array('/[^a-z0-9]/', '/[0-9]+$/'), '', strtolower($result->csnick));

			/**
			 * Only proceed if the stripped nick consists of more than one character.
			 */
			if (strlen($strippednick) > 1) {
				if ((int) $result->status != 0 && !empty($strippednicks[$strippednick])) {
					array_unshift($strippednicks[$strippednick], (int) $result->uid);
				} else {
					$strippednicks[$strippednick][] = (int) $result->uid;
				}
			}
		}

		$nickslinked = 0;

		foreach ($strippednicks as $uids) {
			/**
			 * If there is only one uid for the stripped nick we don't have anything to link.
			 */
			if (count($uids) == 1) {
				continue;
			}

			/**
			 * Use the ruid that belongs to the first uid in the array to link all succeeding unlinked uids to. If the first uid is unlinked
			 * (status = 0) we update its record to become a registered nick (status = 1) when there is at least one new alias found for it
			 * (any succeeding uid with status = 0).
			 */
			$aliasfound = false;

			for ($i = 1, $j = count($uids); $i < $j; $i++) {
				if ($nicks[$uids[$i]]['status'] == 0) {
					@mysqli_query($this->mysqli, 'update `user_status` set `ruid` = '.$nicks[$uids[0]]['ruid'].', `status` = 2 where `uid` = '.$uids[$i]) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
					$this->output('debug', 'link_nicks(): linked \''.$nicks[$uids[$i]]['nick'].'\' to \''.$nicks[$nicks[$uids[0]]['ruid']]['nick'].'\'');
					$nickslinked++;
					$aliasfound = true;
				}
			}

			if ($aliasfound && $nicks[$uids[0]]['status'] == 0) {
				@mysqli_query($this->mysqli, 'update `user_status` set `status` = 1 where `uid` = '.$uids[0]) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			}
		}

		if ($nickslinked == 0) {
			$this->output('notice', 'link_nicks(): no new aliases found');
		}
	}

	private function make_html($sqlite3, $file)
	{
		$html = new html($this->settings);
		$output = $html->make_html($sqlite3);

		if (($fp = @fopen($file, 'wb')) === false) {
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
			if (($dh = @opendir($rp)) === false) {
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
			 * If the filename doesn't match the pattern provided by $logfile_dateformat this condition will not be met.
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
		 * Get the date of the last log that has been parsed.
		 */
		$date_lastlogparsed = @$sqlite3->querySingle('SELECT MAX(date) FROM parse_history') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		/**
		 * $logsparsed increases after each log parsed.
		 */
		$logsparsed = 0;

		/**
		 * $needmaintenance becomes true when there are actual lines parsed. Maintenance routines are only run once after all logs are parsed.
		 */
		$needmaintenance = false;

		foreach ($logfiles as $date => $logfile) {
			if (!is_null($date_lastlogparsed) && strtotime($date) < strtotime($date_lastlogparsed)) {
				continue;
			}

			$parser = new $this->parser($this->settings);
			$parser->set_value('date', $date);

			/**
			 * Get the streak history. This will assume logs are parsed in chronological order with no gaps. If this is not the case the correctness
			 * of the streak stats might be affected.
			 */
			$result = @$sqlite3->querySingle('SELECT prevnick, streak FROM streak_history', true) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

			if (!empty($result)) {
				$parser->set_value('prevnick', $result['prevnick']);
				$parser->set_value('streak', $result['streak']);
			}

			/**
			 * Get the parse history and set the line number on which to start parsing the log.
			 */
			$firstline = @$sqlite3->querySingle('SELECT lines_parsed + 1 FROM parse_history WHERE date = \''.$date.'\'') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

			if (is_null($firstline)) {
				$firstline = 1;
			}

			/**
			 * Check if we are dealing with a gzipped log.
			 */
			if (preg_match('/\.gz$/', $logfile)) {
				if (!extension_loaded('zlib')) {
					$this->output('critical', 'parse_log(): zlib extension isn\'t loaded: can\'t parse gzipped logs'."\n");
				}

				$parser->gzparse_log($logfile, $firstline);
			} else {
				$parser->parse_log($logfile, $firstline);
			}

			$logsparsed++;

			/**
			 * Update the parse history when there are actual (non empty) lines parsed.
			 */
			if ($parser->get_value('linenum_lastnonempty') >= $firstline) {
				@$sqlite3->exec('INSERT OR IGNORE INTO parse_history (date, lines_parsed) VALUES (\''.$date.'\', '.$parser->get_value('linenum_lastnonempty').')') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
				@$sqlite3->exec('UPDATE parse_history SET lines_parsed = '.$parser->get_value('linenum_lastnonempty').' WHERE CHANGES() = 0 AND date = \''.$date.'\'') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			/**
			 * When new data is found write it to the database and set $needmaintenance to true.
			 */
			if ($parser->get_value('newdata')) {
				$parser->write_data($sqlite3);
				$needmaintenance = true;
			} else {
				$this->output('notice', 'parse_log(): no new data to write to database');
			}
		}

		/**
		 * If there are no logs parsed, output the reason.
		 */
		if ($logsparsed == 0) {
			$this->output('notice', 'parse_log(): skipped all logs predating latest parse progress');
		}

		/**
		 * Finally run maintenance routines if needed.
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

			if (preg_match('/^(\w+)\s*=\s*"(.*?)"(\s*#.*)?$/', $line, $matches)) {
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

		/**
		 * The variables that are listed in $settings_list will have their values overridden by those found in the config file.
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
