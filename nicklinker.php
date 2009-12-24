<?php

/**
 * Copyright (c) 2009, Jos de Ruijter <jos@dutnie.nl>
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

$man = 'usage: php nicklinker.php [-i <file>]'."\n"
     . '       php nicklinker.php [-o <file>]'."\n\n"
     . 'the options are:'."\n"
     . '	-i	import all users from <file> to the database'."\n"
     . '	-o	export all users from the database to <file>'."\n";

if (substr(phpversion(), 0, 3) != '5.3') {
	exit('unsupported php version: '.phpversion()."\n");
}

if (!@include('settings.php')) {
	exit('cannot open: '.dirname(__FILE__).'/settings.php'."\n");
}

if ($cfg['db_server'] != 'MySQL') {
	exit('unsupported database server: '.$cfg['db_server']."\n");
}

if ($cfg['db_server'] == 'MySQL' && !extension_loaded('mysqli')) {
	exit('the mysqli extension isn\'t loaded'."\n");
}

if (!(count($argv) == 3 && ($argv[1] == '-i' || $argv[1] == '-o'))) {
	exit($man);
}

define('DB_HOST', $cfg['db_host']);
define('DB_PORT', $cfg['db_port']);
define('DB_USER', $cfg['db_user']);
define('DB_PASS', $cfg['db_pass']);
define('DB_NAME', $cfg['db_name']);

if ($argv[1] == '-i') {
	if (($fp = @fopen($argv[2], 'rb')) !== FALSE) {
		$mysqli = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT) or exit('MySQL: '.mysqli_connect_error()."\n");
		$query = @mysqli_query($mysqli, 'SELECT `UID`, `csNick` FROM `user_details`') or exit('MySQL: '.mysqli_error($mysqli)."\n");
		$rows = mysqli_num_rows($query);

		if (!empty($rows)) {
			while ($result = mysqli_fetch_object($query)) {
				$nick2UID[strtolower($result->csNick)] = $result->UID;
			}

			/**
			 * Set all nicks to their default status before updating any records from the input file.
			 */
			@mysqli_query($mysqli, 'UPDATE `user_status` SET `RUID` = `UID`, `status` = 0') or exit('MySQL: '.mysqli_error($mysqli)."\n");

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
						@mysqli_query($mysqli, 'UPDATE `user_status` SET `RUID` = `UID`, `status` = '.$status.' WHERE `UID` = '.$nick2UID[$nick_main]) or exit('MySQL: '.mysqli_error($mysqli)."\n");

						for ($i = 2; $i < count($lineParts); $i++) {
							$nick = trim($lineParts[$i]);

							if (!empty($nick)) {
								@mysqli_query($mysqli, 'UPDATE `user_status` SET `RUID` = '.$nick2UID[$nick_main].', `status` = 2 WHERE `UID` = '.$nick2UID[$nick]) or exit('MySQL: '.mysqli_error($mysqli)."\n");
							}
						}
					}
				}
			}
		}

		fclose($fp);
	} else {
		exit('cannot open: '.$argv[2]."\n");
	}
} elseif ($argv[1] == '-o') {
	if (($fp = @fopen($argv[2], 'wb')) !== FALSE) {
		$mysqli = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT) or exit('MySQL: '.mysqli_connect_error()."\n");
		$query = @mysqli_query($mysqli, 'SELECT `RUID`, `status` FROM `user_status` WHERE `status` = 1 OR `status` = 3 ORDER BY `RUID` ASC') or exit('MySQL: '.mysqli_error($mysqli)."\n");
		$rows = mysqli_num_rows($query);
		$output = '';

		if (!empty($rows)) {
			while ($result = mysqli_fetch_object($query)) {
				$RUIDs[] = $result->RUID;
				$status[$result->RUID] = $result->status;
			}

			foreach ($RUIDs as $RUID) {
				$output .= $status[$RUID];
				$query = @mysqli_query($mysqli, 'SELECT `csNick` FROM `user_details` JOIN `user_status` ON `user_details`.`UID` = `user_status`.`UID` AND `RUID` = '.$RUID.' ORDER BY `csNick` ASC') or exit('MySQL: '.mysqli_error($mysqli)."\n");
				$rows = mysqli_num_rows($query);

				if (!empty($rows)) {
					while ($result = mysqli_fetch_object($query)) {
						$output .= ','.$result->csNick;
					}
				}

				$output .= "\n";
			}
		}

		$query = @mysqli_query($mysqli, 'SELECT `csNick` FROM `user_details` JOIN `user_status` ON `user_details`.`UID` = `user_status`.`UID` WHERE STATUS = 0 ORDER BY `csNick` ASC') or exit('MySQL: '.mysqli_error($mysqli)."\n");
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
		exit('cannot open: '.$argv[2]."\n");
	}
}

?>
