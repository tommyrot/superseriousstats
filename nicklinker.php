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
 * Class for linking nicks.
 */
final class nicklinker extends base
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
		'outputbits' => 'int',
		'timezone' => 'string');
	private $settings_list_required = array('db_host', 'db_name', 'db_pass', 'db_port', 'db_user', 'timezone');

	public function __construct()
	{
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

		if (array_key_exists('i', $options)) {
			$this->import($options['i']);
		}

		if (array_key_exists('o', $options)) {
			$this->export($options['o']);
		}

		@mysqli_close($this->mysqli);
	}

	private function export($file)
	{
		$this->output('notice', 'export(): exporting nicks');
		$query = @mysqli_query($this->mysqli, 'select `ruid`, `status` from `user_status` where `status` = 1 or `status` = 3 order by `ruid` asc') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);
		$output = '';

		if (!empty($rows)) {
			while ($result = mysqli_fetch_object($query)) {
				$ruids[] = $result->ruid;
				$status[$result->ruid] = $result->status;
			}

			foreach ($ruids as $ruid) {
				$output .= $status[$ruid];
				$query = @mysqli_query($this->mysqli, 'select `csnick` from `user_details` join `user_status` on `user_details`.`uid` = `user_status`.`uid` and `ruid` = '.$ruid.' order by `csnick` asc') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				$rows = mysqli_num_rows($query);

				if (!empty($rows)) {
					while ($result = mysqli_fetch_object($query)) {
						$output .= ','.$result->csnick;
					}
				}

				$output .= "\n";
			}
		}

		$query = @mysqli_query($this->mysqli, 'select `csnick` from `user_details` join `user_status` on `user_details`.`uid` = `user_status`.`uid` where `status` = 0 order by `csnick` asc') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (!empty($rows)) {
			$output .= '*';

			while ($result = mysqli_fetch_object($query)) {
				$output .= ','.$result->csnick;
			}

			$output .= "\n";
		}

		if (($fp = @fopen($file, 'wb')) === false) {
			$this->output('critical', 'export(): failed to open file: \''.$file.'\'');
		}

		fwrite($fp, $output);
		fclose($fp);
		$this->output('notice', 'export(): export completed');
	}

	private function import($file)
	{
		$this->output('notice', 'import(): importing nicks');
		$query = @mysqli_query($this->mysqli, 'select `uid`, `csnick` from `user_details`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		/**
		 * Stop if there are no nicks in the database since there wouldn't be anything to link..
		 */
		if (empty($rows)) {
			return;
		}

		while ($result = mysqli_fetch_object($query)) {
			$nick2uid[strtolower($result->csnick)] = $result->uid;
		}

		/**
		 * Set all nicks to their default status before updating any records from the input file.
		 */
		@mysqli_query($this->mysqli, 'update `user_status` set `ruid` = `uid`, `status` = 0') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));

		if (($rp = realpath($file)) === false) {
			$this->output('critical', 'import(): no such file: \''.$file.'\'');
		}

		if (($fp = @fopen($rp, 'rb')) === false) {
			$this->output('critical', 'import(): failed to open file: \''.$file.'\'');
		}

		while (!feof($fp)) {
			$line = fgets($fp);
			$lineparts = explode(',', strtolower($line));
			$status = trim($lineparts[0]);

			/**
			 * Only lines starting with the number 1 (normal user) or 3 (bot) will be used when updating the user records.
			 * The first nick on each line will initially be used as the "main" nick, and gets the status 1 or 3, as specified in the imported nicks file.
			 * Additional nicks on the same line will be linked to this "main" nick and get the status 2, indicating it being an alias.
			 * Run "php sss.php -m" afterwards to start database maintenance. This will ensure all userstats are properly accumulated according to your latest changes.
			 * More info on http://code.google.com/p/superseriousstats/wiki/Nicklinker
			 */
			if ($status != '1' && $status != '3') {
				continue;
			}

			$nick_main = trim($lineparts[1]);

			if (!empty($nick_main) && array_key_exists($nick_main, $nick2uid)) {
				@mysqli_query($this->mysqli, 'update `user_status` set `ruid` = `uid`, `status` = '.$status.' where `uid` = '.$nick2uid[$nick_main]) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));

				for ($i = 2, $j = count($lineparts); $i < $j; $i++) {
					$nick = trim($lineparts[$i]);

					if (!empty($nick) && array_key_exists($nick, $nick2uid)) {
						@mysqli_query($this->mysqli, 'update `user_status` set `ruid` = '.$nick2uid[$nick_main].', `status` = 2 where `uid` = '.$nick2uid[$nick]) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
					}
				}
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

$nicklinker = new nicklinker();

?>
