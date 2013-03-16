<?php

/**
 * Copyright (c) 2007-2013, Jos de Ruijter <jos@dutnie.nl>
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
 * Class for performing database maintenance. Crucial to keep data up2date and usable.
 */
final class maintenance extends base
{
	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $settings_list = array('outputbits' => 'int');

	public function __construct($settings)
	{
		/**
		 * The variables that are listed in $settings_list will have their values overridden by those found in the config file.
		 */
		foreach ($this->settings_list as $key => $type) {
			if (!array_key_exists($key, $settings)) {
				continue;
			}

			if ($type == 'string') {
				$this->$key = $settings[$key];
			} elseif ($type == 'int') {
				$this->$key = (int) $settings[$key];
			} elseif ($type == 'bool') {
				if (strtolower($settings[$key]) == 'true') {
					$this->$key = true;
				} elseif (strtolower($settings[$key]) == 'false') {
					$this->$key = false;
				}
			}
		}
	}

	private function calculate_milestones($sqlite3)
	{
		$query = @$sqlite3->query('SELECT q_activity_by_day.ruid AS ruid, date, l_total FROM q_activity_by_day JOIN user_status ON q_activity_by_day.ruid = user_status.uid WHERE status != 3 ORDER BY ruid ASC, date ASC') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$result = $query->fetchArray(SQLITE3_ASSOC);

		/**
		* If there is no user activity we can stop here.
		*/
		if ($result === false) {
			return null;
		}

		$values = '';
		$result->reset();

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			if (!isset($l_total[$result['ruid']])) {
				$l_total[$result['ruid']] = $result['l_total'];
				$milestones = array(1000, 2500, 5000, 10000, 25000, 50000, 100000, 250000, 500000, 1000000);
				$nextmilestone = array_shift($milestones);
			} else {
				$l_total[$result['ruid']] += $result['l_total'];
			}

			while (!is_null($nextmilestone) && $l_total[$result['ruid']] >= $nextmilestone) {
				$values .= ', ('.$result['ruid'].', '.$nextmilestone.', \''.$result['date'].'\')';
				$nextmilestone = array_shift($milestones);
			}
		}

		if (!empty($values)) {
			@$sqlite3->exec('DELETE FROM q_milestones') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			@$sqlite3->exec('INSERT INTO q_milestones (ruid, milestone, date) VALUES '.ltrim($values, ', ')) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}
	}

	public function do_maintenance($sqlite3)
	{
		$this->output('notice', 'do_maintenance(): performing database maintenance routines');

		if (($usercount = @$sqlite3->querySingle('SELECT COUNT(*) FROM user_status')) === false) {
			$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		if ($usercount == 0) {
			$this->output('warning', 'do_maintenance(): database is empty, nothing to do');
		} else {
			$this->fix_user_status_errors($sqlite3);
			$this->register_most_active_alias($sqlite3);
			$this->make_materialized_views($sqlite3);
			$this->calculate_milestones($sqlite3);
			$this->output('notice', 'do_maintenance(): maintenance completed');
		}
	}

	/**
	 * Fix user status errors.
	 *
	 * | uid	| ruid		| status	| type
	 * +------------+---------------+---------------+----------------------------------
	 * | x		| x		| 0		| unlinked (default)
	 * | x		| x		| 1		| registered nick, can have aliases
	 * | x		| y		| 2		| alias
	 * | x		| x		| 3		| registered bot, can have aliases
	 *
	 * Conditions that don't fit the schema depicted above will be set to the default, unlinked state.
	 */
	private function fix_user_status_errors($sqlite3)
	{
		/**
		 * Nicks with uid = ruid can only have status = 0, 1 or 3. Set back to 0 if status = 2.
		 */
		@$sqlite3->exec('UPDATE user_status SET status = 0 WHERE uid = ruid AND status = 2') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$rows_affected = $sqlite3->changes();

		if ($rows_affected > 0) {
			$this->output('debug', 'fix_user_status_errors(): '.$rows_affected.' uid'.(($rows_affected > 1) ? 's' : '').' set to default (alias of self)');
		}

		/**
		 * Nicks with uid != ruid can only have status = 2. Set back to 0 if status != 2 and set uid = ruid accordingly.
		 */
		@$sqlite3->exec('UPDATE user_status SET ruid = uid, status = 0 WHERE uid != ruid AND status != 2') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$rows_affected = $sqlite3->changes();

		if ($rows_affected > 0) {
			$this->output('debug', 'fix_user_status_errors(): '.$rows_affected.' uid'.(($rows_affected > 1) ? 's' : '').' set to default (alias with invalid status)');
		}

		/**
		 * Every alias must have their ruid set to the uid of a registered nick, which in turn has uid = ruid and status = 1 or 3. Unlink aliases
		 * pointing to non ruids.
		 */
		$query = @$sqlite3->query('SELECT ruid FROM user_status WHERE status IN (1,3) ORDER BY uid ASC') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$result = $query->fetchArray(SQLITE3_ASSOC);

		if ($result !== false) {
			$ruids = '';
			$result->reset();

			while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
				$ruids .= ','.$result['ruid'];
			}

			if (!empty($ruids)) {
				@$sqlite3->exec('UPDATE user_status SET ruid = uid, status = 0 WHERE status = 2 AND ruid NOT IN ('.ltrim($ruids, ',').')') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
				$rows_affected = $sqlite3->changes();

				if ($rows_affected > 0) {
					$this->output('debug', 'fix_user_status_errors(): '.$rows_affected.' uid'.(($rows_affected > 1) ? 's' : '').' set to default (alias of non registered)');
				}
			}
		}
	}

	/**
	 * Make materialized views, which are stored copies of dynamic views. Query tables are top level materialized views based on various sub views and
	 * contain accumulated stats per ruid. Legend: mv_ materialized view, q_ query table, v_ view.
	 */
	private function make_materialized_views($sqlite3)
	{
		/**
		 * Create materialized views.
		 */
		$tables = array('activedays', 'events', 'ex_actions', 'ex_exclamations', 'ex_kicked', 'ex_kicks', 'ex_questions', 'ex_uppercased', 'lines', 'quote');

		foreach ($tables as $table) {
			/**
			 * 1. Get schema of the final table stored in "sqlite_master".
			 * 2. Create temporary table.
			 * 3. Insert data from view into temporary table.
			 * 4. Drop old final table.
			 * 5. Rename temporary table to final table.
			 */
			if (($sql = @$sqlite3->querySingle('SELECT sql FROM sqlite_master WHERE type = \'table\' AND name = \'mv_'.$table.'\'')) === false) {
				$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			@$sqlite3->exec(preg_replace('/mv_'.$table.'/', 'tmp_mv_'.$table, $sql)) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			@$sqlite3->exec('INSERT INTO tmp_mv_'.$table.' SELECT * FROM v_'.$table) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			@$sqlite3->exec('DROP TABLE mv_'.$table) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			@$sqlite3->exec('ALTER TABLE tmp_mv_'.$table.' RENAME TO mv_'.$table) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		/**
		 * Create query tables. Some of these depend on the materialized views we created above.
		 */
		$tables = array('activity_by_day', 'activity_by_month', 'activity_by_year', 'events', 'lines', 'smileys');

		foreach ($tables as $table) {
			/**
			 * 1. Get schema of the final table stored in "sqlite_master".
			 * 2. Create temporary table.
			 * 3. Likewise, if applicable, get and create indexes for temporary table.
			 * 4. Insert data from view into temporary table.
			 * 5. Drop old final table.
			 * 6. Rename temporary table to final table.
			 */
			if (($sql = @$sqlite3->querySingle('SELECT sql FROM sqlite_master WHERE type = \'table\' AND name = \'q_'.$table.'\'')) === false) {
				$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			@$sqlite3->exec(preg_replace('/q_'.$table.'/', 'tmp_q_'.$table, $sql)) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

			if (in_array($table, array('events', 'lines', 'smileys'))) {
				$query = @$sqlite3->query('SELECT sql FROM sqlite_master WHERE type = \'index\' AND tbl_name = \'q_'.$table.'\' AND sql IS NOT NULL') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

				while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
					@$sqlite3->exec(preg_replace('/q_'.$table.'/', 'tmp_q_'.$table, $result['sql'])) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
				}
			}

			@$sqlite3->exec('INSERT INTO tmp_q_'.$table.' SELECT * FROM v_q_'.$table) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			@$sqlite3->exec('DROP TABLE q_'.$table) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			@$sqlite3->exec('ALTER TABLE tmp_q_'.$table.' RENAME TO q_'.$table) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}
	}

	/**
	 * Make the alias with the most lines the new registered nick for the user or bot it is linked to.
	 */
	private function register_most_active_alias($sqlite3)
	{
		/**
		 * Find out which alias (uid) has the most lines for each registered user or bot (ruid).
		 */
		$query = @$sqlite3->query('SELECT ruid, csnick, (SELECT user_status.uid AS uid FROM user_status JOIN user_lines ON user_status.uid = user_lines.uid WHERE ruid = t1.ruid ORDER BY l_total DESC, uid ASC LIMIT 1) AS uid, status FROM user_status AS t1 JOIN user_details ON t1.uid = user_details.uid WHERE status IN (1,3)') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$result = $query->fetchArray(SQLITE3_ASSOC);

		if ($result === false) {
			return null;
		}

		$result->reset();

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			/**
			 * No records need to be updated if:
			 * - All aliases linked to the registered user or bot have zero lines. The uid value will be null in this case.
			 * - The alias with the most lines is already set to be the registered user or bot.
			 */
			if (!is_null($result['uid']) && $result['uid'] != $result['ruid']) {
				$registered = $result['csnick'];

				if (($alias = @$sqlite3->querySingle('SELECT csnick FROM user_details WHERE uid = '.$result['uid'])) === false) {
					$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
				}

				/**
				 * Update records:
				 * - Make the alias (uid) the new registered nick for the user or bot by setting ruid = uid. The status will be set to either
				 *   1 or 3, identical to previous value.
				 * - Update the ruid field of all records that still point to the old registered nick (ruid) and set it to the new one (uid).
				 *   Explicitly set the status to 2 so all records including the old registered nick are marked as alias.
				 */
				@$sqlite3->exec('UPDATE user_status SET ruid = '.$result['uid'].', status = '.$result['status'].' WHERE uid = '.$result['uid']) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
				@$sqlite3->exec('UPDATE user_status SET ruid = '.$result['uid'].', status = 2 WHERE ruid = '.$result['ruid']) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
				$this->output('debug', 'register_most_active_alias(): \''.$alias.'\' set to new registered for \''.$registered.'\'');
			}
		}
	}
}

?>
