<?php

/**
 * Copyright (c) 2007-2010, Jos de Ruijter <jos@dutnie.nl>
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
 * Class for performing database maintenance.
 */
final class Maintenance extends Base
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
	private $sanitisationDay = 'mon';

	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $mysqli;
	private $settings_list = array(
		'db_host' => 'string',
		'db_name' => 'string',
		'db_pass' => 'string',
		'db_port' => 'int',
		'db_user' => 'string',
		'outputbits' => 'int',
		'sanitisationDay' => 'string');

	/**
	 * Constructor.
	 */
	public function __construct($settings)
	{
		foreach ($this->settings_list as $key => $type) {
			if (!array_key_exists($key, $settings)) {
				continue;
			}

			if ($type == 'string') {
				$this->$key = (string) $settings[$key];
			} elseif ($type == 'int') {
				$this->$key = (int) $settings[$key];
			} elseif ($type == 'bool') {
				if (strcasecmp($settings[$key], 'TRUE') == 0) {
					$this->$key = TRUE;
				} elseif (strcasecmp($settings[$key], 'FALSE') == 0) {
					$this->$key = FALSE;
				}
			}
		}
	}

	/**
	 * Run the maintenance routines.
	 */
	public function doMaintenance()
	{
		$this->output('notice', 'doMaintenance(): performing database maintenance routines');
		$this->mysqli = @mysqli_connect($this->db_host, $this->db_user, $this->db_pass, $this->db_name, $this->db_port) or $this->output('critical', 'MySQLi: '.mysqli_connect_error());
		$query = @mysqli_query($this->mysqli, 'SELECT * FROM `user_status` LIMIT 1') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (!empty($rows)) {
			$this->fixUserStatusErrors();
			$this->registerMostActiveAlias();
			$this->makeMaterializedViews();
			$this->optimizeTables();
			$this->output('notice', 'doMaintenance(): maintenance completed');
		} else {
			$this->output('warning', 'doMaintenance(): database is empty');
		}

		@mysqli_close($this->mysqli);
	}

	/**
	 * Fix userstatus errors.
	 *
	 * Nicks are stored with their UID, RUID and status. For new nicks the UID = RUID and status = 0.
	 *
	 * The possible statuses for nicks are:
	 * 0. Unlinked
	 * 1. Normal user (registered)
	 * 2. Alias
	 * 3. Bot (registered)
	 *
	 * Registered nicks have UID = RUID set and status = 1 or 3.
	 * Aliases are linked to a registered nick by setting their RUID to the UID of the registered nick. Aliases have status = 2.
	 */
	private function fixUserStatusErrors()
	{
		/**
		 * Nicks with UID = RUID can only have status = 0, 1 or 3. Set back to 0 if status = 2.
		 */
		$query = @mysqli_query($this->mysqli, 'SELECT `UID` FROM `user_status` WHERE `UID` = `RUID` AND `status` = 2 ORDER BY `UID` ASC') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (!empty($rows)) {
			while ($result = mysqli_fetch_object($query)) {
				@mysqli_query($this->mysqli, 'UPDATE `user_status` SET `status` = 0 WHERE `UID` = '.$result->UID) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
				$this->output('debug', 'fixUserStatusErrors(): UID '.$result->UID.' set to default (alias of self)');
			}
		}

		/**
		 * Nicks with UID != RUID can only have status = 2. Set back to 0 if status != 2 and set UID = RUID accordingly.
		 */
		$query = @mysqli_query($this->mysqli, 'SELECT `UID` FROM `user_status` WHERE `UID` != `RUID` AND `status` != 2 ORDER BY `UID` ASC') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (!empty($rows)) {
			while ($result = mysqli_fetch_object($query)) {
				@mysqli_query($this->mysqli, 'UPDATE `user_status` SET `RUID` = '.$result->UID.', `status` = 0 WHERE `UID` = '.$result->UID) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
				$this->output('debug', 'fixUserStatusErrors(): UID '.$result->UID.' set to default (non alias pointing to non self)');
			}
		}

		/**
		 * Every alias must have their RUID set to the UID of a registered nick. Which in turn has UID = RUID and status = 1 or 3. Unlink aliases pointing to invalid RUIDs.
		 */
		$query_valid_RUIDs = @mysqli_query($this->mysqli, 'SELECT `RUID` FROM `user_status` WHERE `UID` = `RUID` AND (`status` = 1 OR `status` = 3) ORDER BY `RUID` ASC') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query_valid_RUIDs);

		/**
		 * If there aren't any registered nicks we can skip this one.
		 */
		if (!empty($rows)) {
			$query_linked_RUIDs = @mysqli_query($this->mysqli, 'SELECT DISTINCT `RUID` FROM `user_status` WHERE `UID` != `RUID` AND `status` = 2 ORDER BY `RUID` ASC') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query_linked_RUIDs);

			/**
			 * And if there aren't any aliases we can stop right here.
			 */
			if (!empty($rows)) {
				while ($result_valid_RUIDs = mysqli_fetch_object($query_valid_RUIDs)) {
					$valid_RUIDs_list[] = $result_valid_RUIDs->RUID;
				}

				while ($result_linked_RUIDs = mysqli_fetch_object($query_linked_RUIDs)) {
					$linked_RUIDs_list[] = $result_linked_RUIDs->RUID;
				}

				/**
				 * Do what we're here to do, unlink when appropriate.
				 */
				foreach ($linked_RUIDs_list as $RUID) {
					if (in_array($RUID, $valid_RUIDs_list)) {
						continue;
					}

					$query = @mysqli_query($this->mysqli, 'SELECT `UID` FROM `user_status` WHERE `RUID` = '.$RUID.' AND `status` = 2 ORDER BY `UID` ASC') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
					$rows = mysqli_num_rows($query);

					if (!empty($rows)) {
						while ($result = mysqli_fetch_object($query)) {
							@mysqli_query($this->mysqli, 'UPDATE `user_status` SET `RUID` = '.$result->UID.', `status` = 0 WHERE `UID` = '.$result->UID) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
							$this->output('debug', 'fixUserStatusErrors(): UID '.$result->UID.' set to default (pointing to invalid registered)');
						}
					}
				}
			}
		}
	}

	/**
	 * Make materialized views, which are stored copies of dynamic views.
	 * Query tables are top level materialized views based on various sub views and contain accumulated stats per RUID.
	 */
	private function makeMaterializedViews()
	{
		/**
		 * The dependencies are as follows:
		 *
		 *   v = view
		 *  mv = materialized view
		 *
		 * +--------------------+----+--------------------------+----+--------------------------+----+--------------------------+----+--------------------------+----+
		 * | top level		|    | 1st sub level		|    | 2nd sub level		|    | 3rd sub level		|    | 4th sub level		|    |
		 * +--------------------+----+--------------------------+----+--------------------------+----+--------------------------+----+--------------------------+----+
		 * | query_events	| mv | view_events		|  v |				|    |				|    |				|    |
		 * |			|    | mview_ex_kicks		| mv | view_ex_kicks		|  v | view_ex_kicks_1		|  v |				|    |
		 * |			|    | mview_ex_kicked		| mv | view_ex_kicked		|  v | view_ex_kicked_1		|  v |				|    |
		 * +--------------------+----+--------------------------+----+--------------------------+----+--------------------------+----+--------------------------+----+
		 * | query_lines	| mv | view_lines		|  v |				|    |				|    |				|    |
		 * |			|    | view_activeDays		|  v |				|    |				|    |				|    |
		 * |			|    | mview_quote		| mv | view_quote		|  v | view_quote_1		|  v |				|    |
		 * |			|    |				|    |				|    | view_quote_2		|  v | view_quote_1		|  v |
		 * |			|    | mview_ex_exclamations	| mv | view_ex_exclamations	|  v | view_ex_exclamations_1	|  v |				|    |
		 * |			|    |				|    |				|    | view_ex_exclamations_2	|  v | view_ex_exclamations_1	|  v |
		 * |			|    | mview_ex_questions	| mv | view_ex_questions	|  v | view_ex_questions_1	|  v |				|    |
		 * |			|    |				|    |				|    | view_ex_questions_2	|  v | view_ex_questions_1	|  v |
		 * |			|    | mview_ex_actions		| mv | view_ex_actions		|  v | view_ex_actions_1	|  v |				|    |
		 * |			|    |				|    |				|    | view_ex_actions_2	|  v | view_ex_actions_1	|  v |
		 * |			|    | mview_ex_uppercased	| mv | view_ex_uppercased	|  v | view_ex_uppercased_1	|  v |				|    |
		 * |			|    |				|    |				|    | view_ex_uppercased_2	|  v | view_ex_uppercased_1	|  v |
		 * +--------------------+----+--------------------------+----+--------------------------+----+--------------------------+----+--------------------------+----+
		 * | query_smileys	| mv |				|    |				|    |				|    |				|    |
		 * +--------------------+----+--------------------------+----+--------------------------+----+--------------------------+----+--------------------------+----+
		 */

		/**
		 * mview_ex_kicks
		 */
		@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `new_mview_ex_kicks`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'CREATE TABLE `new_mview_ex_kicks` SELECT * FROM `view_ex_kicks`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `mview_ex_kicks`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'RENAME TABLE `new_mview_ex_kicks` TO `mview_ex_kicks`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));

		/**
		 * mview_ex_kicked
		 */
		@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `new_mview_ex_kicked`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'CREATE TABLE `new_mview_ex_kicked` SELECT * FROM `view_ex_kicked`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `mview_ex_kicked`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'RENAME TABLE `new_mview_ex_kicked` TO `mview_ex_kicked`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));

		/**
		 * mview_quote
		 */
		@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `new_mview_quote`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'CREATE TABLE `new_mview_quote` SELECT * FROM `view_quote`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `mview_quote`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'RENAME TABLE `new_mview_quote` TO `mview_quote`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));

		/**
		 * mview_ex_exclamations
		 */
		@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `new_mview_ex_exclamations`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'CREATE TABLE `new_mview_ex_exclamations` SELECT * FROM `view_ex_exclamations`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `mview_ex_exclamations`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'RENAME TABLE `new_mview_ex_exclamations` TO `mview_ex_exclamations`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));

		/**
		 * mview_ex_questions
		 */
		@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `new_mview_ex_questions`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'CREATE TABLE `new_mview_ex_questions` SELECT * FROM `view_ex_questions`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `mview_ex_questions`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'RENAME TABLE `new_mview_ex_questions` TO `mview_ex_questions`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));

		/**
		 * mview_ex_actions
		 */
		@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `new_mview_ex_actions`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'CREATE TABLE `new_mview_ex_actions` SELECT * FROM `view_ex_actions`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `mview_ex_actions`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'RENAME TABLE `new_mview_ex_actions` TO `mview_ex_actions`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));

		/**
		 * mview_ex_uppercased
		 */
		@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `new_mview_ex_uppercased`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'CREATE TABLE `new_mview_ex_uppercased` SELECT * FROM `view_ex_uppercased`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `mview_ex_uppercased`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'RENAME TABLE `new_mview_ex_uppercased` TO `mview_ex_uppercased`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));

		/**
		 * query_events
		 */
		@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `new_query_events`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'CREATE TABLE `new_query_events` LIKE `template_query_events`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'INSERT INTO `new_query_events` SELECT * FROM `view_query_events`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `query_events`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'RENAME TABLE `new_query_events` TO `query_events`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));

		/**
		 * query_lines
		 */
		@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `new_query_lines`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'CREATE TABLE `new_query_lines` LIKE `template_query_lines`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'INSERT INTO `new_query_lines` SELECT * FROM `view_query_lines`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `query_lines`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'RENAME TABLE `new_query_lines` TO `query_lines`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		
		/**
		 * query_smileys
		 */
		@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `new_query_smileys`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'CREATE TABLE `new_query_smileys` LIKE `template_query_smileys`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'INSERT INTO `new_query_smileys` SELECT * FROM `view_query_smileys`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `query_smileys`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		@mysqli_query($this->mysqli, 'RENAME TABLE `new_query_smileys` TO `query_smileys`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
	}

	/**
	 * Optimize the database tables (sort indexes, etc).
	 */
	private function optimizeTables()
	{
		if (strcasecmp($this->sanitisationDay, date('D')) == 0) {
			@mysqli_query($this->mysqli, 'OPTIMIZE TABLE `channel`, `user_activity`, `user_details`, `user_events`, `user_hosts`, `user_lines`, `user_smileys`, `user_status`, `user_topics`, `user_URLs`, `words`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		}
	}

	/**
	 * Make the alias with the most lines the new registered nick for the user or bot it is linked to.
	 */
	private function registerMostActiveAlias()
	{
		/**
		 * First get all valid RUIDs (UID = RUID and status = 1 or 3). Then check for aliases pointing to those RUIDs and determine the one with most lines.
		 * Finally change the registered nick.
		 */
		$query_valid_RUIDs = @mysqli_query($this->mysqli, 'SELECT `RUID`, `status` FROM `user_status` WHERE `UID` = `RUID` AND (`status` = 1 OR `status` = 3) ORDER BY `RUID` ASC') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query_valid_RUIDs);

		if (!empty($rows)) {
			while ($result_valid_RUIDs = mysqli_fetch_object($query_valid_RUIDs)) {
				$query_aliases = @mysqli_query($this->mysqli, 'SELECT `user_status`.`UID` FROM `user_status` JOIN `user_lines` ON `user_status`.`UID` = `user_lines`.`UID` WHERE `RUID` = '.$result_valid_RUIDs->RUID.' ORDER BY `l_total` DESC, `user_status`.`UID` ASC LIMIT 1') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
				$rows = mysqli_num_rows($query_aliases);

				if (!empty($rows)) {
					$result_aliases = mysqli_fetch_object($query_aliases);

					if ($result_aliases->UID != $result_valid_RUIDs->RUID) {
						/**
						 * Make the alias the new registered nick; set UID = RUID and status = 1 or 3 depending on the status the old registered nick had.
						 * Update all nicks linked to the old registered nick and make their RUID point to the new one.
						 */
						@mysqli_query($this->mysqli, 'UPDATE `user_status` SET `RUID` = '.$result_aliases->UID.', `status` = '.$result_valid_RUIDs->status.' WHERE `UID` = '.$result_aliases->UID) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
						@mysqli_query($this->mysqli, 'UPDATE `user_status` SET `RUID` = '.$result_aliases->UID.', `status` = 2 WHERE `RUID` = '.$result_valid_RUIDs->RUID) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
						$this->output('debug', 'registerMostActiveAlias(): UID '.$result_aliases->UID.' set to new registered');
					}
				}
			}
		}
	}
}

?>
