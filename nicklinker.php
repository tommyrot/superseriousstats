<?php

/**
 * Copyright (c) 2009-2011, Jos de Ruijter <jos@dutnie.nl>
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
 * Class for linking nicks.
 */
final class nicklinker extends base
{
	/**
	 * Default settings for this script, can be overridden in the config file.
	 * These should all appear in $settings_list[] along with their type.
	 */
	private $autolinknicks = true;
	private $db_host = '127.0.0.1';
	private $db_name = 'sss';
	private $db_pass = '';
	private $db_port = 3306;
	private $db_user = '';
	private $timezone = 'UTC';

	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $mysqli;
	private $settings = array();
	private $settings_list = array(
		'autolinknicks' => 'bool',
		'db_host' => 'string',
		'db_name' => 'string',
		'db_pass' => 'string',
		'db_port' => 'int',
		'db_user' => 'string',
		'outputbits' => 'int',
		'timezone' => 'string');
	private $settings_list_required = array('db_pass', 'db_user');

	public function __construct()
	{
		parent::__construct();

		/**
		 * Use UTC until user specified timezone is loaded.
		 */
		date_default_timezone_set('UTC');

		/**
		 * Read options from the command line.
		 */
		$options = getopt('c:i:o:');

		if (empty($options)) {
			$this->print_manual();
		}

		if (array_key_exists('c', $options)) {
			$this->read_config($options['c']);
		} else {
			$this->read_config(dirname(__FILE__).'/sss.conf');
		}

		$this->mysqli = @mysqli_connect($this->db_host, $this->db_user, $this->db_pass, $this->db_name, $this->db_port) or $this->output('critical', 'mysqli: '.mysqli_connect_error());
		$this->output('notice', '__construct(): succesfully connected to '.$this->db_host.':'.$this->db_port.', database: \''.$this->db_name.'\'');
		@mysqli_query($this->mysqli, 'set names \'utf8\'') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));

		if (array_key_exists('i', $options)) {
			$this->import($options['i']);
		}

		if (array_key_exists('o', $options)) {
			$this->export($options['o']);
		}

		@mysqli_close($this->mysqli);
	}

	/**
	 * Export nicks sorted on lines typed. Unlinked nicks are in alphabetical order.
	 */
	private function export($file)
	{
		$this->output('notice', 'export(): exporting nicks');
		$query = @mysqli_query($this->mysqli, 'select `user_status`.`uid`, `ruid`, `status`, `csnick` from `user_status` join `user_details` on `user_status`.`uid` = `user_details`.`uid` left join `user_lines` on `user_status`.`uid` = `user_lines`.`uid` order by `l_total` desc, `csnick` asc') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			$this->output('critical', 'export(): database is empty');
		}

		while ($result = mysqli_fetch_object($query)) {
			$statuses[$result->uid] = (int) $result->status;
			$users[$result->ruid][] = strtolower($result->csnick);
		}

		$output = '';
		$i = 0;

		foreach ($users as $ruid => $aliases) {
			if ($statuses[$ruid] == 1 || $statuses[$ruid] == 3) {
				$output .= $statuses[$ruid];

				foreach ($aliases as $nick) {
					$output .= ','.$nick;
					$i++;
				}

				$output .= "\n";
			} else {
				/**
				 * There is only one nick linked to a user with status 0; itself. Other options fail at the end of this method.
				 */
				$unlinked[] = $aliases[0];
			}
		}

		if (!empty($unlinked)) {
			sort($unlinked);
			$output .= '*';

			foreach ($unlinked as $nick) {
				$output .= ','.$nick;
				$i++;
			}

			$output .= "\n";
		}

		if ($i != $rows) {
			$this->output('critical', 'export(): something is wrong, run "php sss.php -m" before export');
		}

		if (($fp = @fopen($file, 'wb')) === false) {
			$this->output('critical', 'export(): failed to open file: \''.$file.'\'');
		}

		fwrite($fp, $output);
		fclose($fp);
		$this->output('notice', 'export(): '.number_format($i).' nicks exported');
	}

	/**
	 * Import nicks from file. First nick on each line is the initial registered nick to which aliases are linked.
	 */
	private function import($file)
	{
		$this->output('notice', 'import(): importing nicks');
		$query = @mysqli_query($this->mysqli, 'select `uid`, `csnick` from `user_details`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			$this->output('critical', 'import(): database is empty');
		}

		while ($result = mysqli_fetch_object($query)) {
			$uids[strtolower($result->csnick)] = $result->uid;
		}

		if (($rp = realpath($file)) === false) {
			$this->output('critical', 'import(): no such file: \''.$file.'\'');
		}

		if (($fp = @fopen($rp, 'rb')) === false) {
			$this->output('critical', 'import(): failed to open file: \''.$file.'\'');
		}

		while (!feof($fp)) {
			$line = fgets($fp);
			$line = preg_replace('/\s/', '', $line);
			$lineparts = explode(',', strtolower($line));

			if (($lineparts[0] == '1' || $lineparts[0] == '3') && !empty($lineparts[1])) {
				$status = (int) $lineparts[0];
				$nick = $lineparts[1];
				$uid = $uids[$nick];
				$statuses[$uid] = $status;
				$users[$uid][] = $nick;
				$linked2uid[$nick] = $uid;

				for ($i = 2, $j = count($lineparts); $i < $j; $i++) {
					if (!empty($lineparts[$i])) {
						$nick = $lineparts[$i];
						$users[$uid][] = $nick;
						$linked2uid[$nick] = $uid;
					}
				}
			} elseif ($lineparts[0] == '*') {
				for ($i = 1, $j = count($lineparts); $i < $j; $i++) {
					if (!empty($lineparts[$i])) {
						$nick = $lineparts[$i];
						$unlinked[] = $nick;
					}
				}
			}
		}

		if ($this->autolinknicks && !empty($unlinked)) {
			foreach ($unlinked as $nick) {
				/**
				 * We attempt to link nicks with special chars in them to nicks that don't. Not the other way around.
				 */
				$tmpnick = preg_replace('/[^a-z0-9]+/', '', $nick);

				/**
				 * Nicks of length 1 are bogus, we skip those.
				 */
				if ($tmpnick != $nick && strlen($tmpnick) > 1) {
					/**
					 * See if the trimmed nick exists.
					 */
					if (!empty($uids[$tmpnick])) {
						/**
						 * Trimmed nick exists, is it linked or unlinked?
						 */
						if (!empty($linked2uid[$tmpnick])) {
							/**
							 * It's linked, use the uid to link the untrimmed nick to.
							 * Untrimmed nick doesn't need a pointer to the nick it's linked to since no other nick will link to it.
							 */
							$uid = $linked2uid[$tmpnick];
							$users[$uid][] = $nick;
						} else {
							/**
							 * It's unlinked, create user for uid of trimmed nick and link both trimmed and untrimmed nicks to it.
							 * Untrimmed nick doesn't need a pointer to the nick it's linked to since no other nick will link to it.
							 */
							$uid = $uids[$tmpnick];
							$statuses[$uid] = 1;
							$users[$uid][] = $tmpnick;
							$linked2uid[$tmpnick] = $uid;
							$users[$uid][] = $nick;
						}

						$this->output('debug', 'import(): linked \''.$nick.'\' to \''.$tmpnick.'\'');
					}
				}
			}
		}

		/**
		 * Set all nicks to their default status before updating them according to new data.
		 */
		@mysqli_query($this->mysqli, 'update `user_status` set `ruid` = `uid`, `status` = 0') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));

		foreach ($users as $uid => $aliases) {
			@mysqli_query($this->mysqli, 'update `user_status` set `ruid` = `uid`, `status` = '.$statuses[$uid].' where `uid` = '.$uid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));

			for ($i = 1, $j = count($aliases); $i < $j; $i++) {
				@mysqli_query($this->mysqli, 'update `user_status` set `ruid` = '.$uid.', `status` = 2 where `uid` = '.$uids[$aliases[$i]]) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			}
		}

		fclose($fp);
		$this->output('notice', 'import(): import completed, don\'t forget to run "php sss.php -m"');
	}

	private function print_manual()
	{
		$man = 'usage:	php nicklinker.php [-c <config>] [-i <file>]'."\n"
		     . '	php nicklinker.php [-c <config>] [-o <file>]'."\n\n"
		     . 'options:'."\n"
		     . '	-c	Read settings from <config>.'."\n"
		     . '		If unspecified sss.conf will be used.'."\n"
		     . '	-i	Import all user relations from <file> to the database. It is'."\n"
		     . '		strongly advised to make an export first to serve as a backup.'."\n"
		     . '		All stored user relations in the database will be unset before'."\n"
		     . '		reading the contents of <file>. The script will skip all nicks'."\n"
		     . '		found in <file> which are not in the database. The syntax of'."\n"
		     . '		nicks contained in <file> is case insensitive.'."\n"
		     . '	-o	Export all user relations from the database to <file>.'."\n";
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

			if (preg_match('/^(\w+)\s*=\s*"(.*)"$/', $line, $matches)) {
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

		if (!date_default_timezone_set($this->timezone)) {
			$this->output('critical', 'read_config(): invalid timezone: \''.$this->timezone.'\'');
		}
	}
}

if (substr(phpversion(), 0, 3) != '5.3') {
	echo 'php version 5.3 is recommended, you are running with version '.phpversion()."\n";
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

$nicklinker = new nicklinker();

?>
