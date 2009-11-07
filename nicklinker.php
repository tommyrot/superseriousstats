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

/**
 * This script should be called from the commandline with PHP version 5.3.0 or up.
 *
 * usage: php nicklinker.php {-i <nicks_file> | -o <nicks_file>}
 *
 * Make sure to use the absolute path when specifying <nicks_file>.
 *
 * The options are:
 *	-i	import all users from <nicks_file> to the database
 *	-o	export all users from the database to <nicks_file>
 */

if (substr(phpversion(), 0, 3) != '5.3')
	exit('unsupported php version: '.phpversion()."\n");

if (!@include('settings.php'))
	exit('cannot open: '.dirname(__FILE__).'/settings.php'."\n");

if ($cfg['database_server'] != 'MySQL')
	exit('unsupported database server: '.$cfg['database_server']."\n");

if ($cfg['database_server'] == 'MySQL' && !extension_loaded('mysqli'))
	exit('the mysqli extension isn\'t loaded'."\n");

if (!(count($argv) == 3 && ($argv[1] == '-i' || $argv[1] == '-o')))
	exit('usage: php '.basename(__FILE__).' {-i <nicks_file> | -o <nicks_file>}'."\n");

define('DB_HOST', $cfg['db_host']);
define('DB_PORT', $cfg['db_port']);
define('DB_USER', $cfg['db_user']);
define('DB_PASS', $cfg['db_pass']);
define('DB_NAME', $cfg['db_name']);

if ($argv[1] == '-i') {
	$fp = @fopen($argv[2], 'rb');

	if ($fp) {
		$mysqli = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT) or exit('MySQL: '.mysqli_connect_error()."\n");
		$query = @mysqli_query($mysqli, 'SELECT `UID`, `csNick` FROM `user_details`') or exit('MySQL: '.mysqli_error($mysqli)."\n");
		$rows = mysqli_num_rows($query);

		if (!empty($rows)) {
			while ($result = mysqli_fetch_object($query))
				$csNick2UID[$result->csNick] = $result->UID;

			/**
			 * Set all nicks to their default status before updating any records from the input file.
			 */
			@mysqli_query($mysqli, 'UPDATE `user_status` SET `RUID` = `UID`, `status` = 0') or exit('MySQL: '.mysqli_error($mysqli)."\n");

			while (!feof($fp)) {
				$line = fgets($fp);
				$lineParts = explode(',', $line);
				$status = trim($lineParts[0]);

				/**
				 * Only lines starting with the numer 1 (normal user) or 3 (bot) will be used when updating the user records.
				 * The first nick on each line will initially be used as the "main" nick, and gets the status 1 or 3, as specified.
				 * Additional nicks on the same line will be linked to this "main" nick and get the status 2, indicating it being an alias.
				 * The database maintenance routine "registerMostActiveAlias()" should be run to make the most active alias the "main" nick for each user.
				 * This routine is normally automatically invoked after parsing a logfile (see: settings.php and sss.php).
				 */
				if ($status == 1 || $status == 3) {
					$csNick_main = trim($lineParts[1]);

					if (!empty($csNick_main)) {
						@mysqli_query($mysqli, 'UPDATE `user_status` SET `RUID` = `UID`, `status` = '.$status.' WHERE `UID` = '.$csNick2UID[$csNick_main]) or exit('MySQL: '.mysqli_error($mysqli)."\n");

						for ($i = 2; $i < count($lineParts); $i++) {
							$csNick = trim($lineParts[$i]);

							if (!empty($csNick))
								@mysqli_query($mysqli, 'UPDATE `user_status` SET `RUID` = '.$csNick2UID[$csNick_main].', `status` = 2 WHERE `UID` = '.$csNick2UID[$csNick]) or exit('MySQL: '.mysqli_error($mysqli)."\n");
						}
					}
				}
			}
		}

		fclose($fp);
	} else
		exit('cannot open: '.$argv[2]."\n");
} elseif ($argv[1] == '-o') {
	$fp = @fopen($argv[2], 'wb');

	if ($fp) {
		$mysqli = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT) or exit('MySQL: '.mysqli_connect_error()."\n");
		$query = mysqli_query($mysqli, 'SELECT `RUID`, `status` FROM `user_status` WHERE `status` = 1 OR `status` = 3 ORDER BY `RUID` ASC');
		$rows = mysqli_num_rows($query);
		$output = '';

		if (!empty($rows)) {
			while ($result = mysqli_fetch_object($query)) {
				$RUIDs[] = $result->RUID;
				$status[$result->RUID] = $result->status;
			}

			foreach ($RUIDs as $RUID) {
				$output .= $status[$RUID];
				$query = mysqli_query($mysqli, 'SELECT `csNick` FROM `user_details` JOIN `user_status` ON `user_details`.`UID` = `user_status`.`UID` AND `RUID` = '.$RUID.' ORDER BY `csNick` ASC');
				$rows = mysqli_num_rows($query);

				if (!empty($rows))
					while ($result = mysqli_fetch_object($query))
						$output .= ','.$result->csNick;

				$output .= "\n";
			}
		}

		$query = mysqli_query($mysqli, 'SELECT `csNick` FROM `user_details` JOIN `user_status` ON `user_details`.`UID` = `user_status`.`UID` WHERE STATUS = 0 ORDER BY `csNick` ASC');
		$rows = mysqli_num_rows($query);

		if (!empty($rows)) {
			$output .= '*';

			while ($result = mysqli_fetch_object($query))
				$output .= ','.$result->csNick;

			$output .= "\n";
		}

		fwrite($fp, $output);
		fclose($fp);
	} else
		exit('cannot open: '.$argv[2]."\n");
}

?>
