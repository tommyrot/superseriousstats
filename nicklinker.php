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

final class nicklinker
{
	/**
	 * Default settings, can be overridden in the config file.
	 */
	private $db_host = '';
        private $db_name = '';
        private $db_pass = '';
        private $db_port = 0;
        private $db_user = '';
	private $outputLevel = 1;

	/**
	 * Other variables that shouldn't be tampered with.
	 */
	private $settings = array();
	private $settings_list = array('db_host' => 'string'
			              ,'db_name' => 'string'
				      ,'db_pass' => 'string'
				      ,'db_port' => 'int'
				      ,'db_user' => 'string'
				      ,'outputLevel' => 'int');
	private $settings_required_list = array('db_host', 'db_name', 'db_pass', 'db_port', 'db_user', 'timezone');

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		/**
		 * Use UTC until user specified timezone is loaded.
		 */
		date_default_timezone_set('UTC');

		/**
		 * Read options from the command line.
		 */
		if (($options = getopt('c:i:o:')) !== FALSE) {
			if (empty($options)) {
				$this->printManual();
			}

			if (array_key_exists('c', $options)) {
				$this->readConfig($options['c']);
			} else {
				$this->readConfig(dirname(__FILE__).'/sss.conf');
			}

			if (array_key_exists('i', $options)) {
				$this->import($options['i']);
			}

			if (array_key_exists('o', $options)) {
				$this->export($options['o']);
			}
		} else {
			$this->printManual();
		}
	}

	/**
	 * Export nicks.
	 */
	private function export($file)
	{
		if (($fp = @fopen($file, 'wb')) !== FALSE) {
			$mysqli = @mysqli_connect($this->db_host, $this->db_user, $this->db_pass, $this->db_name, $this->db_port) or $this->output('critical', 'MySQL: '.mysqli_connect_error());
			$query = @mysqli_query($mysqli, 'SELECT `RUID`, `status` FROM `user_status` WHERE `status` = 1 OR `status` = 3 ORDER BY `RUID` ASC') or $this->output('critical', 'MySQL: '.mysqli_error($mysqli));
			$rows = mysqli_num_rows($query);
			$output = '';

			if (!empty($rows)) {
				while ($result = mysqli_fetch_object($query)) {
					$RUIDs[] = $result->RUID;
					$status[$result->RUID] = $result->status;
				}

				foreach ($RUIDs as $RUID) {
					$output .= $status[$RUID];
					$query = @mysqli_query($mysqli, 'SELECT `csNick` FROM `user_details` JOIN `user_status` ON `user_details`.`UID` = `user_status`.`UID` AND `RUID` = '.$RUID.' ORDER BY `csNick` ASC') or $this->output('critical', 'MySQL: '.mysqli_error($mysqli));
					$rows = mysqli_num_rows($query);

					if (!empty($rows)) {
						while ($result = mysqli_fetch_object($query)) {
							$output .= ','.$result->csNick;
						}
					}

					$output .= "\n";
				}
			}

			$query = @mysqli_query($mysqli, 'SELECT `csNick` FROM `user_details` JOIN `user_status` ON `user_details`.`UID` = `user_status`.`UID` WHERE STATUS = 0 ORDER BY `csNick` ASC') or $this->output('critical', 'MySQL: '.mysqli_error($mysqli));
			$rows = mysqli_num_rows($query);

			if (!empty($rows)) {
				$output .= '*';

				while ($result = mysqli_fetch_object($query)) {
					$output .= ','.$result->csNick;
				}

				$output .= "\n";
			}

			fwrite($fp, $output);
			fclose($fp);
		} else {
			$this->output('critical', 'export(): failed to open file: \''.$file.'\'');
		}
	}

	/**
	 * Import nicks.
	 */
	private function import($file)
	{
		if (($rp = realpath($file)) !== FALSE) {
			if (($fp = @fopen($rp, 'rb')) !== FALSE) {
				$mysqli = @mysqli_connect($this->db_host, $this->db_user, $this->db_pass, $this->db_name, $this->db_port) or $this->output('critical', 'MySQL: '.mysqli_connect_error());
				$query = @mysqli_query($mysqli, 'SELECT `UID`, `csNick` FROM `user_details`') or $this->output('critical', 'MySQL: '.mysqli_error($mysqli));
				$rows = mysqli_num_rows($query);

				if (!empty($rows)) {
					while ($result = mysqli_fetch_object($query)) {
						$nick2UID[strtolower($result->csNick)] = $result->UID;
					}

					/**
					 * Set all nicks to their default status before updating any records from the input file.
					 */
					@mysqli_query($mysqli, 'UPDATE `user_status` SET `RUID` = `UID`, `status` = 0') or $this->output('critical', 'MySQL: '.mysqli_error($mysqli));

					while (!feof($fp)) {
						$line = fgets($fp);
						$lineParts = explode(',', strtolower($line));
						$status = trim($lineParts[0]);

						/**
						 * Only lines starting with the number 1 (normal user) or 3 (bot) will be used when updating the user records.
						 * The first nick on each line will initially be used as the "main" nick, and gets the status 1 or 3, as specified in the imported nicks file.
						 * Additional nicks on the same line will be linked to this "main" nick and get the status 2, indicating it being an alias.
						 * Run "php sss.php -m" afterwards to start database maintenance. This will ensure all userstats are properly accumulated according to your latest changes.
						 * More info on http://code.google.com/p/superseriousstats/wiki/Nicklinker
						 */
						if ($status == 1 || $status == 3) {
							$nick_main = trim($lineParts[1]);

							if (!empty($nick_main)) {
								@mysqli_query($mysqli, 'UPDATE `user_status` SET `RUID` = `UID`, `status` = '.$status.' WHERE `UID` = '.$nick2UID[$nick_main]) or $this->output('critical', 'MySQL: '.mysqli_error($mysqli));

								for ($i = 2; $i < count($lineParts); $i++) {
									$nick = trim($lineParts[$i]);

									if (!empty($nick)) {
										@mysqli_query($mysqli, 'UPDATE `user_status` SET `RUID` = '.$nick2UID[$nick_main].', `status` = 2 WHERE `UID` = '.$nick2UID[$nick]) or $this->output('critical', 'MySQL: '.mysqli_error($mysqli));
									}
								}
							}
						}
					}
				}

				fclose($fp);
			} else {
				$this->output('critical', 'import(): failed to open file: \''.$file.'\'');
			}
		} else {
			$this->output('critical', 'import(): no such file: \''.$file.'\'');
		}
	}

	/**
	 * Output given messages to the console.
	 */
	private function output($type, $msg)
	{
		$dateTime = date('M d H:i:s');

		if (substr($dateTime, 4, 1) === '0') {
			$dateTime = substr_replace($dateTime, ' ', 4, 1);
		}

		switch ($type) {
			case 'notice':
				if ($this->outputLevel >= 3) {
					echo $dateTime.' [notice] '.$msg."\n";
				}

				break;
			case 'warning':
				if ($this->outputLevel >= 2) {
					echo $dateTime.' [warning] '.$msg."\n";
				}

				break;
			case 'critical':
				if ($this->outputLevel >= 1) {
					echo $dateTime.' [critical] '.$msg."\n";
				}

				exit;
		}
	}

	/**
	 * Print the manual and exit.
	 */
	private function printManual()
	{
		$man = 'usage: php nicklinker.php [-i <file>]'."\n"
		     . '       php nicklinker.php [-o <file>]'."\n\n"
		     . 'the options are:'."\n"
		     . '	-i	import all users from <file> to the database'."\n"
		     . '	-o	export all users from the database to <file>'."\n";
		exit($man);
	}

	/**
	 * Read settings from the config file.
	 */
	private function readConfig($file)
	{
		if (($rp = realpath($file)) !== FALSE) {
			if (($fp = @fopen($rp, 'rb')) !== FALSE) {
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
				foreach ($this->settings_required_list as $key) {
					if (!array_key_exists($key, $this->settings)) {
						$this->output('critical', 'readConfig(): missing setting: \''.$key.'\'');
					}
				}

				foreach ($this->settings_list as $key => $type) {
					if (array_key_exists($key, $this->settings)) {
						if ($type == 'string') {
							$this->$key = (string) $this->settings[$key];
						} elseif ($type == 'int') {
							$this->$key = (int) $this->settings[$key];
						} elseif ($type == 'bool') {
							if (strcasecmp($this->settings[$key], 'TRUE') == 0) {
								$this->$key = TRUE;
							} elseif (strcasecmp($this->settings[$key], 'FALSE') == 0) {
								$this->$key = FALSE;
							}
						}
					}
				}

				if (date_default_timezone_set($this->settings['timezone']) !== FALSE) {
					$this->output('notice', 'readConfig(): switched to timezone: \''.$this->settings['timezone'].'\'');
				} else {
					$this->output('critical', 'readConfig(): invalid timezone: \''.$this->settings['timezone'].'\'');
				}
			} else {
				$this->output('critical', 'readConfig(): failed to open file: \''.$rp.'\'');
			}
		} else {
			$this->output('critical', 'readConfig(): no such file: \''.$file.'\'');
		}
	}
}

if (substr(phpversion(), 0, 3) != '5.3') {
	exit('PHP version 5.3 required, currently running with version '.phpversion()."\n");
}

if (!extension_loaded('mysqli')) {
	exit('MySQLi extension isn\'t loaded'."\n");
}

$nicklinker = new nicklinker();

?>
