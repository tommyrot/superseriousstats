<?php

/**
 * Copyright (c) 2007-2009, Jos de Ruijter <jos@dutnie.nl>
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
 * Super Serious Stats
 * Maintenance_MySQL.class.php
 *
 * Class for performing database maintenance.
 *
 * One should understand the following basic variables before continuing to read through this file.
 * Comments in this file are likely to be confusing and not of any help with understanding how things work.
 *
 * UID = User ID
 * RUID = UID of the registered user
 *
 * The registered user has the same RUID as UID and can be identified accordingly.
 * Its aliases have their own unique UIDs and a RUID which is set to the UID of the registered user.
 */

final class Maintenance_MySQL
{
	// If you want to override the default settings below you encouraged to do so from the startup script.
	private $outputLevel = 1;
	private $sanitisationDay = 'mon';

	// Function to change and set variables. Should only be used from the startup script.
	public function setValue($var, $value)
	{
		$this->$var = $value;
	}

	// Output given messages to the console.
	private function output($type, $msg)
	{
		switch ($type) {
			case 'debug':
				if ($this->outputLevel >= 4)
					echo '   Debug ['.date('H:i.s').'] '.$msg."\n";
				break;
			case 'notice':
				if ($this->outputLevel >= 3)
					echo '  Notice ['.date('H:i.s').'] '.$msg."\n";
				break;
			case 'critical':
				if ($this->outputLevel >= 1)
					echo 'Critical ['.date('H:i.s').'] '.$msg."\n";
				exit;
		}
	}

	public function doMaintenance()
	{
		$this->output('notice', 'doMaintenance(): performing database maintenance routines');
		@mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or $this->output('critical', 'MySQL: '.mysql_error());
		@mysql_select_db(MYSQL_DB) or $this->output('critical', 'MySQL: '.mysql_error());
		$this->fixUserStatusErrors();
		$this->registerMostActiveAlias();
		$this->makeQuerytables();
		$this->optimizeTables();
		@mysql_close();
		$this->output('notice', 'doMaintenance(): maintenance completed');
	}

	/**
	 * Nicks are stored with their UID, RUID and status. For new nicks the UID = RUID and status = 0.
	 *
	 * The possible statusses for nicks are:
	 * 0. Unlinked
	 * 1. Registered
	 * 2. Alias
	 * 3. Bot (registered)
	 *
	 * Registered nicks have UID = RUID set and status = 1 or 3.
	 * Aliases are linked to a registered nick by setting their RUID to the UID of the registered nick. Aliases have status = 2.
	 */
	private function fixUserStatusErrors()
	{
		// Nicks with UID = RUID can only have status = 0, 1 or 3. Set back to 0 if status = 2.
		$query = @mysql_query('SELECT `UID` FROM `user_status` WHERE `UID` = `RUID` AND `status` = 2 ORDER BY `UID` ASC') or $this->output('critical', 'MySQL: '.mysql_error());

		while ($result = @mysql_fetch_object($query)) {
			$this->output('debug', 'fixUserStatusErrors(): (UID=RUID&status=2) UID='.$result->UID.':status=0');
			@mysql_query('UPDATE `user_status` SET `status` = 0 WHERE `UID` = '.$result->UID) or $this->output('critical', 'MySQL: '.mysql_error());
		}

		// Nicks with UID != RUID can only have status = 2. Set back to 0 if status != 2 and set RUID = UID.
		$query = @mysql_query('SELECT `UID` FROM `user_status` WHERE `UID` != `RUID` AND `status` != 2 ORDER BY `UID` ASC') or $this->output('critical', 'MySQL: '.mysql_error());

		while ($result = @mysql_fetch_object($query)) {
			$this->output('debug', 'fixUserStatusErrors(): (UID!=RUID&status!=2) UID='.$result->UID.':RUID='.$result->UID.'&status=0');
			@mysql_query('UPDATE `user_status` SET `RUID` = '.$result->UID.', `status` = 0 WHERE `UID` = '.$result->UID) or $this->output('critical', 'MySQL: '.mysql_error());
		}

		// Every alias must have a RUID which has UID = RUID and status = 1 or 3. Unlink aliases pointing to invalid RUIDs.
		$query_valid_RUIDs = @mysql_query('SELECT `RUID` FROM `user_status` WHERE `UID` = `RUID` AND (`status` = 1 OR `status` = 3) ORDER BY `RUID` ASC') or $this->output('critical', 'MySQL: '.mysql_error());
		$rows = @mysql_num_rows($query_valid_RUIDs);

		// If there aren't any registered nicks we can skip this one.
		if (!empty($rows)) {
			$query_linked_RUIDs = @mysql_query('SELECT DISTINCT `RUID` FROM `user_status` WHERE `UID` != `RUID` AND `status` = 2 ORDER BY `RUID` ASC') or $this->output('critical', 'MySQL: '.mysql_error());
			$rows = @mysql_num_rows($query_linked_RUIDs);

			// And if there aren't any aliases we can stop right here.
			if (!empty($rows)) {
				while ($result_valid_RUIDs = @mysql_fetch_object($query_valid_RUIDs))
					$valid_RUIDs_list[] = $result_valid_RUIDs->RUID;

				while ($result_linked_RUIDs = @mysql_fetch_object($query_linked_RUIDs))
					$linked_RUIDs_list[] = $result_linked_RUIDs->RUID;

				// Do what we're here to do, unlink when appropriate.
				foreach ($linked_RUIDs_list as $RUID)
					if (!in_array($RUID, $valid_RUIDs_list)) {
						$query = @mysql_query('SELECT `UID` FROM `user_status` WHERE `RUID` = '.$RUID.' AND `status` = 2 ORDER BY `UID` ASC') or $this->output('critical', 'MySQL: '.mysql_error());

						while ($result = @mysql_fetch_object($query)) {
							$this->output('debug', 'fixUserStatusErrors(): (!RUID) UID='.$result->UID.':RUID='.$result->UID.'&status=0');
							@mysql_query('UPDATE `user_status` SET `RUID` = '.$result->UID.', `status` = 0 WHERE `UID` = '.$result->UID) or $this->output('critical', 'MySQL: '.mysql_error());
						}
					}
			}
		}
	}

	// Make the alias with the most lines the new registered nick for the user or bot it is linked to.
	private function registerMostActiveAlias()
	{
		/**
		 * First get all valid RUIDs (UID = RUID and status = 1 or 3). Then check for aliases pointing to those RUIDs and determine the one with most lines.
		 * Finally change the registered nick.
		 */
		$query_valid_RUIDs = @mysql_query('SELECT `RUID`, `status` FROM `user_status` WHERE `UID` = `RUID` AND (`status` = 1 OR `status` = 3) ORDER BY `RUID` ASC') or $this->output('critical', 'MySQL: '.mysql_error());

		while ($result_valid_RUIDs = mysql_fetch_object($query_valid_RUIDs)) {
			$query_aliases = @mysql_query('SELECT `user_status`.`UID` FROM `user_status` JOIN `user_lines` ON `user_status`.`UID` = `user_lines`.`UID` WHERE `RUID` = '.$result_valid_RUIDs->RUID.' ORDER BY `l_total` DESC, `user_status`.`UID` ASC LIMIT 1') or $this->output('critical', 'MySQL: '.mysql_error());
			$result_aliases = @mysql_fetch_object($query_aliases);

			if ($result_aliases->UID != $result_valid_RUIDs->RUID) {
				/**
				 * Make the alias the new registered nick; set UID = RUID and status = 1 or 3 depending on the status the old RUID has.
				 * Update all aliases linked to the old RUID and make them point to the new registered nick, including the old RUID.
				 */
				$this->output('debug', 'registerMostActiveAlias(): UID='.$result_aliases->UID.':RUID='.$result_aliases->UID.'&status='.$result_valid_RUIDs->status);
				@mysql_query('UPDATE `user_status` SET `RUID` = '.$result_aliases->UID.', `status` = '.$result_valid_RUIDs->status.' WHERE `UID` = '.$result_aliases->UID) or $this->output('critical', 'MySQL: '.mysql_error());
				@mysql_query('UPDATE `user_status` SET `RUID` = '.$result_aliases->UID.', `status` = 2 WHERE `RUID` = '.$result_valid_RUIDs->RUID) or $this->output('critical', 'MySQL: '.mysql_error());
			}
		}
	}

	// Query tables are generated daily and contain accumulated stats per nick.
	private function makeQuerytables()
	{
		// Clear the old tables so we can insert the new data.
		@mysql_query('TRUNCATE TABLE `query_events`') or $this->output('critical', 'MySQL: '.mysql_error());
		@mysql_query('TRUNCATE TABLE `query_lines`') or $this->output('critical', 'MySQL: '.mysql_error());
		@mysql_query('TRUNCATE TABLE `query_smileys`') or $this->output('critical', 'MySQL: '.mysql_error());
		$query_RUIDs = @mysql_query('SELECT DISTINCT `RUID` FROM `user_status` ORDER BY `RUID` ASC') or $this->output('critical', 'MySQL: '.mysql_error());

		while ($result_RUIDs = @mysql_fetch_object($query_RUIDs)) {
			// Check if the specific registered nick has any event data linked to it.
			$query = @mysql_query('SELECT * FROM `user_events` JOIN `user_status` ON `user_events`.`UID` = `user_status`.`UID` WHERE `RUID` = '.$result_RUIDs->RUID.' LIMIT 1') or $this->output('critical', 'MySQL: '.mysql_error());
			$rows = @mysql_num_rows($query);

			if (!empty($rows)) {
				// Collect all linked data from database table "user_events".
				$query_num_values = @mysql_query('SELECT SUM(`m_op`) AS `m_op`, SUM(`m_opped`) AS `m_opped`, SUM(`m_voice`) AS `m_voice`, SUM(`m_voiced`) AS `m_voiced`, SUM(`m_deOp`) AS `m_deOp`, SUM(`m_deOpped`) AS `m_deOpped`, SUM(`m_deVoice`) AS `m_deVoice`, SUM(`m_deVoiced`) AS `m_deVoiced`, SUM(`joins`) AS `joins`, SUM(`parts`) AS `parts`, SUM(`quits`) AS `quits`, SUM(`kicks`) AS `kicks`, SUM(`kicked`) AS `kicked`, SUM(`nickChanges`) AS `nickChanges`, SUM(`topics`) AS `topics` FROM `user_events` JOIN `user_status` ON `user_events`.`UID` = `user_status`.`UID` WHERE `RUID` = '.$result_RUIDs->RUID) or $this->output('critical', 'MySQL: '.mysql_error());
				$result_num_values = @mysql_fetch_object($query_num_values);
				$query_ex_kicks = @mysql_query('SELECT `ex_kicks` FROM `user_events` JOIN `user_status` ON `user_events`.`UID` = `user_status`.`UID` WHERE `RUID` = '.$result_RUIDs->RUID.' AND `ex_kicks` != \'\' ORDER BY RAND() LIMIT 1') or $this->output('critical', 'MySQL: '.mysql_error());
				$result_ex_kicks = @mysql_fetch_object($query_ex_kicks);
				$query_ex_kicked = @mysql_query('SELECT `ex_kicked` FROM `user_events` JOIN `user_status` ON `user_events`.`UID` = `user_status`.`UID` WHERE `RUID` = '.$result_RUIDs->RUID.' AND `ex_kicked` != \'\' ORDER BY RAND() LIMIT 1') or $this->output('critical', 'MySQL: '.mysql_error());
				$result_ex_kicked = @mysql_fetch_object($query_ex_kicked);

				// Insert data into the database table "query_events".
				@mysql_query('INSERT INTO `query_events` (`UID`, `m_op`, `m_opped`, `m_voice`, `m_voiced`, `m_deOp`, `m_deOpped`, `m_deVoice`, `m_deVoiced`, `joins`, `parts`, `quits`, `kicks`, `kicked`, `nickChanges`, `topics`, `ex_kicks`, `ex_kicked`) VALUES ('.$result_RUIDs->RUID.', '.$result_num_values->m_op.', '.$result_num_values->m_opped.', '.$result_num_values->m_voice.', '.$result_num_values->m_voiced.', '.$result_num_values->m_deOp.', '.$result_num_values->m_deOpped.', '.$result_num_values->m_deVoice.', '.$result_num_values->m_deVoiced.', '.$result_num_values->joins.', '.$result_num_values->parts.', '.$result_num_values->quits.', '.$result_num_values->kicks.', '.$result_num_values->kicked.', '.$result_num_values->nickChanges.', '.$result_num_values->topics.', \''.mysql_real_escape_string($result_ex_kicks->ex_kicks !== '' ? $result_ex_kicks->ex_kicks : '').'\', \''.mysql_real_escape_string($result_ex_kicked->ex_kicked !== '' ? $result_ex_kicked->ex_kicked : '').'\')') or $this->output('critical', 'MySQL: '.mysql_error());
			}

			// Check if the specific registered nick has any line data linked to it.
			$query = @mysql_query('SELECT * FROM `user_lines` JOIN `user_status` ON `user_lines`.`UID` = `user_status`.`UID` WHERE `RUID` = '.$result_RUIDs->RUID.' LIMIT 1') or $this->output('critical', 'MySQL: '.mysql_error());
			$rows = @mysql_num_rows($query);

			if (!empty($rows)) {
				// Collect all linked data from database table "user_lines".
				$query_num_values = @mysql_query('SELECT SUM(`l_00`) AS `l_00`, SUM(`l_01`) AS `l_01`, SUM(`l_02`) AS `l_02`, SUM(`l_03`) AS `l_03`, SUM(`l_04`) AS `l_04`, SUM(`l_05`) AS `l_05`, SUM(`l_06`) AS `l_06`, SUM(`l_07`) AS `l_07`, SUM(`l_08`) AS `l_08`, SUM(`l_09`) AS `l_09`, SUM(`l_10`) AS `l_10`, SUM(`l_11`) AS `l_11`, SUM(`l_12`) AS `l_12`, SUM(`l_13`) AS `l_13`, SUM(`l_14`) AS `l_14`, SUM(`l_15`) AS `l_15`, SUM(`l_16`) AS `l_16`, SUM(`l_17`) AS `l_17`, SUM(`l_18`) AS `l_18`, SUM(`l_19`) AS `l_19`, SUM(`l_20`) AS `l_20`, SUM(`l_21`) AS `l_21`, SUM(`l_22`) AS `l_22`, SUM(`l_23`) AS `l_23`, SUM(`l_night`) AS `l_night`, SUM(`l_morning`) AS `l_morning`, SUM(`l_afternoon`) AS `l_afternoon`, SUM(`l_evening`) AS `l_evening`, SUM(`l_total`) AS `l_total`, SUM(`l_mon_night`) AS `l_mon_night`, SUM(`l_mon_morning`) AS `l_mon_morning`, SUM(`l_mon_afternoon`) AS `l_mon_afternoon`, SUM(`l_mon_evening`) AS `l_mon_evening`, SUM(`l_tue_night`) AS `l_tue_night`, SUM(`l_tue_morning`) AS `l_tue_morning`, SUM(`l_tue_afternoon`) AS `l_tue_afternoon`, SUM(`l_tue_evening`) AS `l_tue_evening`, SUM(`l_wed_night`) AS `l_wed_night`, SUM(`l_wed_morning`) AS `l_wed_morning`, SUM(`l_wed_afternoon`) AS `l_wed_afternoon`, SUM(`l_wed_evening`) AS `l_wed_evening`, SUM(`l_thu_night`) AS `l_thu_night`, SUM(`l_thu_morning`) AS `l_thu_morning`, SUM(`l_thu_afternoon`) AS `l_thu_afternoon`, SUM(`l_thu_evening`) AS `l_thu_evening`, SUM(`l_fri_night`) AS `l_fri_night`, SUM(`l_fri_morning`) AS `l_fri_morning`, SUM(`l_fri_afternoon`) AS `l_fri_afternoon`, SUM(`l_fri_evening`) AS `l_fri_evening`, SUM(`l_sat_night`) AS `l_sat_night`, SUM(`l_sat_morning`) AS `l_sat_morning`, SUM(`l_sat_afternoon`) AS `l_sat_afternoon`, SUM(`l_sat_evening`) AS `l_sat_evening`, SUM(`l_sun_night`) AS `l_sun_night`, SUM(`l_sun_morning`) AS `l_sun_morning`, SUM(`l_sun_afternoon`) AS `l_sun_afternoon`, SUM(`l_sun_evening`) AS `l_sun_evening`, SUM(`URLs`) AS `URLs`, SUM(`words`) AS `words`, SUM(`characters`) AS `characters`, SUM(`monologues`) AS `monologues`, SUM(`slaps`) AS `slaps`, SUM(`slapped`) AS `slapped`, SUM(`exclamations`) AS `exclamations`, SUM(`questions`) AS `questions`, SUM(`actions`) AS `actions`, SUM(`uppercased`) AS `uppercased` FROM `user_status` JOIN `user_lines` ON `user_status`.`UID` = `user_lines`.`UID` WHERE `RUID` = '.$result_RUIDs->RUID) or $this->output('critical', 'MySQL: '.mysql_error());
				$result_num_values = @mysql_fetch_object($query_num_values);
				$query_topMonologue = @mysql_query('SELECT MAX(`topMonologue`) AS `topMonologue` FROM `user_status` JOIN `user_lines` ON `user_status`.`UID` = `user_lines`.`UID` WHERE `RUID` = '.$result_RUIDs->RUID) or $this->output('critical', 'MySQL: '.mysql_error());
				$result_topMonologue = @mysql_fetch_object($query_topMonologue);
				$query_activeDays = @mysql_query('SELECT COUNT(DISTINCT `date`) AS `activeDays` FROM `user_status` JOIN `user_activity` ON `user_status`.`UID` = `user_activity`.`UID` WHERE `RUID` = '.$result_RUIDs->RUID) or $this->output('critical', 'MySQL: '.mysql_error());
				$result_activeDays = @mysql_fetch_object($query_activeDays);
				$query_quote = @mysql_query('SELECT `quote` FROM `user_status` JOIN `user_details` ON `user_status`.`UID` = `user_details`.`UID` JOIN `user_lines` ON `user_status`.`UID` = `user_lines`.`UID` WHERE `RUID` = '.$result_RUIDs->RUID.' AND `quote` != \'\' ORDER BY `lastSeen` DESC LIMIT 1') or $this->output('critical', 'MySQL: '.mysql_error());
				$result_quote = @mysql_fetch_object($query_quote);
				$query_ex_exclamations = @mysql_query('SELECT `ex_exclamations` FROM `user_status` JOIN `user_details` ON `user_status`.`UID` = `user_details`.`UID` JOIN `user_lines` ON `user_status`.`UID` = `user_lines`.`UID` WHERE `RUID` = '.$result_RUIDs->RUID.' AND `ex_exclamations` != \'\' ORDER BY `lastSeen` DESC LIMIT 1') or $this->output('critical', 'MySQL: '.mysql_error());
				$result_ex_exclamations = @mysql_fetch_object($query_ex_exclamations);
				$query_ex_questions = @mysql_query('SELECT `ex_questions` FROM `user_status` JOIN `user_details` ON `user_status`.`UID` = `user_details`.`UID` JOIN `user_lines` ON `user_status`.`UID` = `user_lines`.`UID` WHERE `RUID` = '.$result_RUIDs->RUID.' AND `ex_questions` != \'\' ORDER BY `lastSeen` DESC LIMIT 1') or $this->output('critical', 'MySQL: '.mysql_error());
				$result_ex_questions = @mysql_fetch_object($query_ex_questions);
				$query_ex_actions = @mysql_query('SELECT `ex_actions` FROM `user_status` JOIN `user_details` ON `user_status`.`UID` = `user_details`.`UID` JOIN `user_lines` ON `user_status`.`UID` = `user_lines`.`UID` WHERE `RUID` = '.$result_RUIDs->RUID.' AND `ex_actions` != \'\' ORDER BY `lastSeen` DESC LIMIT 1') or $this->output('critical', 'MySQL: '.mysql_error());
				$result_ex_actions = @mysql_fetch_object($query_ex_actions);
				$query_ex_uppercased = @mysql_query('SELECT `ex_uppercased` FROM `user_status` JOIN `user_details` ON `user_status`.`UID` = `user_details`.`UID` JOIN `user_lines` ON `user_status`.`UID` = `user_lines`.`UID` WHERE `RUID` = '.$result_RUIDs->RUID.' AND `ex_uppercased` != \'\' ORDER BY `lastSeen` DESC LIMIT 1') or $this->output('critical', 'MySQL: '.mysql_error());
				$result_ex_uppercased = @mysql_fetch_object($query_ex_uppercased);
				$query_lastTalked = @mysql_query('SELECT `lastTalked` FROM `user_status` JOIN `user_lines` ON `user_status`.`UID` = `user_lines`.`UID` WHERE `RUID` = '.$result_RUIDs->RUID.' ORDER BY `lastTalked` DESC LIMIT 1') or $this->output('critical', 'MySQL: '.mysql_error());
				$result_lastTalked = @mysql_fetch_object($query_lastTalked);

				// Insert data into the database table "query_lines".
				@mysql_query('INSERT INTO `query_lines` (`UID`, `l_00`, `l_01`, `l_02`, `l_03`, `l_04`, `l_05`, `l_06`, `l_07`, `l_08`, `l_09`, `l_10`, `l_11`, `l_12`, `l_13`, `l_14`, `l_15`, `l_16`, `l_17`, `l_18`, `l_19`, `l_20`, `l_21`, `l_22`, `l_23`, `l_night`, `l_morning`, `l_afternoon`, `l_evening`, `l_total`, `l_mon_night`, `l_mon_morning`, `l_mon_afternoon`, `l_mon_evening`, `l_tue_night`, `l_tue_morning`, `l_tue_afternoon`, `l_tue_evening`, `l_wed_night`, `l_wed_morning`, `l_wed_afternoon`, `l_wed_evening`, `l_thu_night`, `l_thu_morning`, `l_thu_afternoon`, `l_thu_evening`, `l_fri_night`, `l_fri_morning`, `l_fri_afternoon`, `l_fri_evening`, `l_sat_night`, `l_sat_morning`, `l_sat_afternoon`, `l_sat_evening`, `l_sun_night`, `l_sun_morning`, `l_sun_afternoon`, `l_sun_evening`, `URLs`, `words`, `characters`, `monologues`, `topMonologue`, `activeDays`, `slaps`, `slapped`, `exclamations`, `questions`, `actions`, `uppercased`, `quote`, `ex_exclamations`, `ex_questions`, `ex_actions`, `ex_uppercased`, `lastTalked`) VALUES ('.$result_RUIDs->RUID.', '.$result_num_values->l_00.', '.$result_num_values->l_01.', '.$result_num_values->l_02.', '.$result_num_values->l_03.', '.$result_num_values->l_04.', '.$result_num_values->l_05.', '.$result_num_values->l_06.', '.$result_num_values->l_07.', '.$result_num_values->l_08.', '.$result_num_values->l_09.', '.$result_num_values->l_10.', '.$result_num_values->l_11.', '.$result_num_values->l_12.', '.$result_num_values->l_13.', '.$result_num_values->l_14.', '.$result_num_values->l_15.', '.$result_num_values->l_16.','.$result_num_values->l_17.','.$result_num_values->l_18.','.$result_num_values->l_19.', '.$result_num_values->l_20.', '.$result_num_values->l_21.', '.$result_num_values->l_22.', '.$result_num_values->l_23.', '.$result_num_values->l_night.', '.$result_num_values->l_morning.', '.$result_num_values->l_afternoon.', '.$result_num_values->l_evening.', '.$result_num_values->l_total.', '.$result_num_values->l_mon_night.', '.$result_num_values->l_mon_morning.', '.$result_num_values->l_mon_afternoon.', '.$result_num_values->l_mon_evening.', '.$result_num_values->l_tue_night.', '.$result_num_values->l_tue_morning.', '.$result_num_values->l_tue_afternoon.', '.$result_num_values->l_tue_evening.', '.$result_num_values->l_wed_night.', '.$result_num_values->l_wed_morning.', '.$result_num_values->l_wed_afternoon.', '.$result_num_values->l_wed_evening.', '.$result_num_values->l_thu_night.', '.$result_num_values->l_thu_morning.', '.$result_num_values->l_thu_afternoon.', '.$result_num_values->l_thu_evening.', '.$result_num_values->l_fri_night.', '.$result_num_values->l_fri_morning.', '.$result_num_values->l_fri_afternoon.', '.$result_num_values->l_fri_evening.', '.$result_num_values->l_sat_night.', '.$result_num_values->l_sat_morning.', '.$result_num_values->l_sat_afternoon.', '.$result_num_values->l_sat_evening.', '.$result_num_values->l_sun_night.', '.$result_num_values->l_sun_morning.', '.$result_num_values->l_sun_afternoon.', '.$result_num_values->l_sun_evening.', '.$result_num_values->URLs.', '.$result_num_values->words.', '.$result_num_values->characters.', '.$result_num_values->monologues.', '.$result_topMonologue->topMonologue.', '.$result_activeDays->activeDays.', '.$result_num_values->slaps.', '.$result_num_values->slapped.', '.$result_num_values->exclamations.', '.$result_num_values->questions.', '.$result_num_values->actions.', '.$result_num_values->uppercased.', \''.mysql_real_escape_string($result_quote->quote !== '' ? $result_quote->quote : '').'\', \''.mysql_real_escape_string($result_ex_exclamations->ex_exclamations !== '' ? $result_ex_exclamations->ex_exclamations : '').'\', \''.mysql_real_escape_string($result_ex_questions->ex_questions !== '' ? $result_ex_questions->ex_questions : '').'\', \''.mysql_real_escape_string($result_ex_actions->ex_actions !== '' ? $result_ex_actions->ex_actions : '').'\', \''.mysql_real_escape_string($result_ex_uppercased->ex_uppercased !== '' ? $result_ex_uppercased->ex_uppercased : '').'\', \''.mysql_real_escape_string($result_lastTalked->lastTalked).'\')') or $this->output('critical', 'MySQL: '.mysql_error());
			}

			// Check if the specific registered nick has any smiley data linked to it.
			$query = @mysql_query('SELECT * FROM `user_smileys` JOIN `user_status` ON `user_smileys`.`UID` = `user_status`.`UID` WHERE `RUID` = '.$result_RUIDs->RUID.' LIMIT 1') or $this->output('critical', 'MySQL: '.mysql_error());
			$rows = @mysql_num_rows($query);

			if (!empty($rows)) {
				// Collect all linked data from database table "user_smileys".
				$query_num_values = @mysql_query('SELECT SUM(`s_01`) AS `s_01`, SUM(`s_02`) AS `s_02`, SUM(`s_03`) AS `s_03`, SUM(`s_04`) AS `s_04`, SUM(`s_05`) AS `s_05`, SUM(`s_06`) AS `s_06`, SUM(`s_07`) AS `s_07`, SUM(`s_08`) AS `s_08`, SUM(`s_09`) AS `s_09`, SUM(`s_10`) AS `s_10`, SUM(`s_11`) AS `s_11`, SUM(`s_12`) AS `s_12`, SUM(`s_13`) AS `s_13`, SUM(`s_14`) AS `s_14`, SUM(`s_15`) AS `s_15`, SUM(`s_16`) AS `s_16`, SUM(`s_17`) AS `s_17`, SUM(`s_18`) AS `s_18`, SUM(`s_19`) AS `s_19` FROM `user_smileys` JOIN `user_status` ON `user_smileys`.`UID` = `user_status`.`UID` WHERE `RUID` = '.$result_RUIDs->RUID) or $this->output('critical', 'MySQL: '.mysql_error());
				$result_num_values = @mysql_fetch_object($query_num_values);

				// Insert data into the database table "query_smileys".
				@mysql_query('INSERT INTO `query_smileys` (`UID`, `s_01`, `s_02`, `s_03`, `s_04`, `s_05`, `s_06`, `s_07`, `s_08`, `s_09`, `s_10`, `s_11`, `s_12`, `s_13`, `s_14`, `s_15`, `s_16`, `s_17`, `s_18`, `s_19`) VALUES ('.$result_RUIDs->RUID.', '.$result_num_values->s_01.', '.$result_num_values->s_02.', '.$result_num_values->s_03.', '.$result_num_values->s_04.', '.$result_num_values->s_05.', '.$result_num_values->s_06.', '.$result_num_values->s_07.', '.$result_num_values->s_08.', '.$result_num_values->s_09.', '.$result_num_values->s_10.', '.$result_num_values->s_11.', '.$result_num_values->s_12.', '.$result_num_values->s_13.', '.$result_num_values->s_14.', '.$result_num_values->s_15.', '.$result_num_values->s_16.','.$result_num_values->s_17.','.$result_num_values->s_18.','.$result_num_values->s_19.')') or $this->output('critical', 'MySQL: '.mysql_error());
			}
		}
	}

	// Optimize the database tables (sort indexes, etc).
	private function optimizeTables()
	{
		if (strtolower($this->sanitisationDay) == strtolower(date('D')))
			@mysql_query('OPTIMIZE TABLE `channel`, `user_activity`, `user_details`, `user_events`, `user_hosts`, `user_lines`, `user_smileys`, `user_status`, `user_topics`, `user_URLs`, `words`, `query_events`, `query_lines`, `query_smileys`') or $this->output('critical', 'MySQL: '.mysql_error());
		else
			@mysql_query('OPTIMIZE TABLE `query_events`, `query_lines`, `query_smileys`') or $this->output('critical', 'MySQL: '.mysql_error());
	}
}

?>
