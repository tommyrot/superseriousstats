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
	 * Variables that shouldn't be tampered with.
	 */
	private $mysqli;
	private $settings_list = array('outputbits' => 'int');

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
				$this->$key = $settings[$key];
			} elseif ($type == 'int') {
				$this->$key = (int) $settings[$key];
			} elseif ($type == 'bool') {
				if (strtoupper($settings[$key]) == 'TRUE') {
					$this->$key = TRUE;
				} elseif (strtoupper($settings[$key]) == 'FALSE') {
					$this->$key = FALSE;
				}
			}
		}
	}

	/**
	 * Run the maintenance routines.
	 */
	public function doMaintenance($mysqli)
	{
		$this->mysqli = $mysqli;
		$this->output('notice', 'doMaintenance(): performing database maintenance routines');
		$query = @mysqli_query($this->mysqli, 'SELECT COUNT(*) FROM `user_details`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			$this->output('critical', 'doMaintenance(): database is empty');
		}

		$this->fixUserStatusErrors();
		$this->registerMostActiveAlias();
		$this->makeMaterializedViews();
		$this->output('notice', 'doMaintenance(): maintenance completed');
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

		if (!empty($rows)) {
			$query_linked_RUIDs = @mysqli_query($this->mysqli, 'SELECT DISTINCT `RUID` FROM `user_status` WHERE `UID` != `RUID` AND `status` = 2 ORDER BY `RUID` ASC') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query_linked_RUIDs);

			/**
			 * If there aren't any aliases we can stop here.
			 */
			if (empty($rows)) {
				return;
			}

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

	/**
	 * Make materialized views, which are stored copies of dynamic views.
	 * Query tables are top level materialized views based on various sub views and contain accumulated stats per RUID.
	 */
	private function makeMaterializedViews()
	{
		$views = array('ex_kicks', 'ex_kicked', 'quote', 'ex_exclamations', 'ex_questions', 'ex_actions', 'ex_uppercased');

		foreach ($views as $view) {
			@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `new_mview_'.$view.'`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			@mysqli_query($this->mysqli, 'CREATE TABLE `new_mview_'.$view.'` SELECT * FROM `view_'.$view.'`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `mview_'.$view.'`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			@mysqli_query($this->mysqli, 'RENAME TABLE `new_mview_'.$view.'` TO `mview_'.$view.'`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		}

		$query_tables = array('query_events', 'query_lines', 'query_smileys');

		foreach ($query_tables as $query_table) {
			@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `new_'.$query_table.'`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			@mysqli_query($this->mysqli, 'CREATE TABLE `new_'.$query_table.'` LIKE `template_'.$query_table.'`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			@mysqli_query($this->mysqli, 'INSERT INTO `new_'.$query_table.'` SELECT * FROM `view_'.$query_table.'`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			@mysqli_query($this->mysqli, 'DROP TABLE IF EXISTS `'.$query_table.'`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			@mysqli_query($this->mysqli, 'RENAME TABLE `new_'.$query_table.'` TO `'.$query_table.'`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
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

		/**
		 * If there aren't any registered nicks we can stop here.
		 */
		if (empty($rows)) {
			return;
		}

		while ($result_valid_RUIDs = mysqli_fetch_object($query_valid_RUIDs)) {
			$query_aliases = @mysqli_query($this->mysqli, 'SELECT `user_status`.`UID` FROM `user_status` JOIN `user_lines` ON `user_status`.`UID` = `user_lines`.`UID` WHERE `RUID` = '.$result_valid_RUIDs->RUID.' ORDER BY `l_total` DESC, `user_status`.`UID` ASC LIMIT 1') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query_aliases);

			if (empty($rows)) {
				continue;
			}

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

?>
