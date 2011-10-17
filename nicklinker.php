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
		 * Explicitly set the locale to C so we won't run into unexpected results between platforms.
		 */
		setlocale(LC_CTYPE, 'C');

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

	private function export($file)
	{
		$this->output('notice', 'export(): exporting nicks');
		$query = @mysqli_query($this->mysqli, 'select `user_details`.`uid`, `ruid`, `csnick`, `status` from `user_details` join `user_status` on `user_details`.`uid` = `user_status`.`uid` order by `csnick` asc') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			$this->output('critical', 'export(): database is empty');
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
			ksort($registered);

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
			$this->output('critical', 'export(): something is wrong, run "php sss.php -m" before export');
		}

		if (($fp = @fopen($file, 'wb')) === false) {
			$this->output('critical', 'export(): failed to open file: \''.$file.'\'');
		}

		fwrite($fp, $output);
		fclose($fp);
		$this->output('notice', 'export(): '.number_format($i).' nicks exported');
	}

	private function import($file)
	{
		$this->output('notice', 'import(): importing nicks');
		$query = @mysqli_query($this->mysqli, 'select `uid`, `csnick` from `user_details`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			$this->output('critical', 'import(): database is empty');
		}

		while ($result = mysqli_fetch_object($query)) {
			$uids[strtolower($result->csnick)] = (int) $result->uid;
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

			/**
			 * First nick on each line is the initial registered nick which aliases are linked to.
			 */
			if (((int) $lineparts[0] == 1 || (int) $lineparts[0] == 3) && !empty($lineparts[1])) {
				$uid = $uids[$lineparts[1]];
				$registered[] = $uid;
				$statuses[$uid] = (int) $lineparts[0];

				for ($i = 2, $j = count($lineparts); $i < $j; $i++) {
					if (!empty($lineparts[$i])) {
						$aliases[$uid][] = $uids[$lineparts[$i]];
					}
				}
			}
		}

		fclose($fp);

		if (empty($registered)) {
			$this->output('warning', 'import(): no user relations found to import');
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

			$this->output('notice', 'import(): import completed, don\'t forget to run "php sss.php -m"');
		}
	}

	private function print_manual()
	{
		$man = 'usage:	php nicklinker.php [-c <file>] [-i <file>]'."\n"
		     . '	php nicklinker.php [-c <file>] [-o <file>]'."\n\n"
		     . 'options:'."\n"
		     . '	-c <file>'."\n"
		     . '		Read settings from <file>. By default "./sss.conf" is read.'."\n\n"
		     . '	-i <file>'."\n"
		     . '		Import all user relations from <file> into the database. Nicks'."\n"
		     . '		contained in <file> are treated as case insensitive and any'."\n"
		     . '		nicks which are not already present in the database will be'."\n"
		     . '		ignored. *All* stored user relations are unset before reading'."\n"
		     . '		the contents of given <file> so it advisable to make an export'."\n"
		     . '		beforehand to serve as a backup.'."\n\n"
		     . '	-o <file>'."\n"
		     . '		Export all user relations from the database to <file>.'."\n";
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
