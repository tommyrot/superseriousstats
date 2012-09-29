<?php

/**
 * Copyright (c) 2007-2012, Jos de Ruijter <jos@dutnie.nl>
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
	private $mysqli;
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

	private function calculate_milestones()
	{
		$query = @mysqli_query($this->mysqli, 'select `q_activity_by_day`.`ruid`, `date`, `l_total` from `q_activity_by_day` join `user_status` on `q_activity_by_day`.`ruid` = `user_status`.`uid` where `status` != 3 order by `ruid` asc, `date` asc') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		/**
		* If there is no user activity we can stop here.
		*/
		if (empty($rows)) {
			return null;
		}

		$values = '';

		while ($result = mysqli_fetch_object($query)) {
			if (!isset($l_total[(int) $result->ruid])) {
				$l_total[(int) $result->ruid] = (int) $result->l_total;
				$milestones = array(1000, 2500, 5000, 10000, 25000, 50000, 100000, 250000, 500000, 1000000);
				$nextmilestone = array_shift($milestones);
			} else {
				$l_total[(int) $result->ruid] += (int) $result->l_total;
			}

			while (!is_null($nextmilestone) && $l_total[(int) $result->ruid] >= $nextmilestone) {
				$values .= ', ('.$result->ruid.', '.$nextmilestone.', \''.$result->date.'\')';
				$nextmilestone = array_shift($milestones);
			}
		}

		if (!empty($values)) {
			@mysqli_query($this->mysqli, 'truncate table `q_milestones`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			@mysqli_query($this->mysqli, 'insert into `q_milestones` (`ruid`, `milestone`, `date`) values '.ltrim($values, ', ')) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		}
	}

	public function do_maintenance($mysqli)
	{
		$this->mysqli = $mysqli;
		$this->output('notice', 'do_maintenance(): performing database maintenance routines');
		$query = @mysqli_query($this->mysqli, 'select count(*) as `usercount` from `user_status`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (!empty($rows)) {
			$result = mysqli_fetch_object($query);
		}

		if (empty($result->usercount)) {
			$this->output('warning', 'do_maintenance(): database is empty, nothing to do');
		} else {
			$this->fix_user_status_errors();
			$this->register_most_active_alias();
			$this->make_materialized_views();
			$this->calculate_milestones();
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
	private function fix_user_status_errors()
	{
		/**
		 * Nicks with uid = ruid can only have status = 0, 1 or 3. Set back to 0 if status = 2.
		 */
		@mysqli_query($this->mysqli, 'update `user_status` set `status` = 0 where `uid` = `ruid` and `status` = 2') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows_affected = mysqli_affected_rows($this->mysqli);

		if (!empty($rows_affected)) {
			$this->output('debug', 'fix_user_status_errors(): '.$rows_affected.' uid'.(($rows_affected > 1) ? 's' : '').' set to default (alias of self)');
		}

		/**
		 * Nicks with uid != ruid can only have status = 2. Set back to 0 if status != 2 and set uid = ruid accordingly.
		 */
		@mysqli_query($this->mysqli, 'update `user_status` set `ruid` = `uid`, `status` = 0 where `uid` != `ruid` and `status` != 2') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows_affected = mysqli_affected_rows($this->mysqli);

		if (!empty($rows_affected)) {
			$this->output('debug', 'fix_user_status_errors(): '.$rows_affected.' uid'.(($rows_affected > 1) ? 's' : '').' set to default (alias with invalid status)');
		}

		/**
		 * Every alias must have their ruid set to the uid of a registered nick, which in turn has uid = ruid and status = 1 or 3. Unlink aliases
		 * pointing to non ruids.
		 */
		$query = @mysqli_query($this->mysqli, 'select `ruid` from `user_status` where `status` in (1,3) order by `uid` asc') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (!empty($rows)) {
			$ruids = '';

			while ($result = mysqli_fetch_object($query)) {
				$ruids .= ','.$result->ruid;
			}

			if (!empty($ruids)) {
				@mysqli_query($this->mysqli, 'update `user_status` set `ruid` = `uid`, `status` = 0 where `status` = 2 and `ruid` not in ('.ltrim($ruids, ',').')') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				$rows_affected = mysqli_affected_rows($this->mysqli);

				if (!empty($rows_affected)) {
					$this->output('debug', 'fix_user_status_errors(): '.$rows_affected.' uid'.(($rows_affected > 1) ? 's' : '').' set to default (alias of non registered)');
				}
			}
		}
	}

	/**
	 * Make materialized views, which are stored copies of dynamic views. Query tables are top level materialized views based on various sub views and
	 * contain accumulated stats per ruid. Legend: mv_ materialized view, q_ query table, t_ template, v_ view. Combinations do exist.
	 */
	private function make_materialized_views()
	{
		/**
		 * Create materialized views based on templates.
		 */
		$tables = array('activedays', 'events', 'ex_actions', 'ex_exclamations', 'ex_kicked', 'ex_kicks', 'ex_questions', 'ex_uppercased', 'lines', 'quote');

		foreach ($tables as $table) {
			@mysqli_query($this->mysqli, 'drop table if exists `new_mv_'.$table.'`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			@mysqli_query($this->mysqli, 'create table `new_mv_'.$table.'` like `t_mv_'.$table.'`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			@mysqli_query($this->mysqli, 'insert into `new_mv_'.$table.'` select * from `v_'.$table.'`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			@mysqli_query($this->mysqli, 'drop table if exists `mv_'.$table.'`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			@mysqli_query($this->mysqli, 'rename table `new_mv_'.$table.'` to `mv_'.$table.'`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		}

		/**
		 * Create query tables based on templates and possibly *requiring* previously created materialized views.
		 */
		$tables = array('activity_by_day', 'activity_by_month', 'activity_by_year', 'events', 'lines', 'smileys');

		foreach ($tables as $table) {
			@mysqli_query($this->mysqli, 'drop table if exists `new_q_'.$table.'`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			@mysqli_query($this->mysqli, 'create table `new_q_'.$table.'` like `t_q_'.$table.'`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			@mysqli_query($this->mysqli, 'insert into `new_q_'.$table.'` select * from `v_q_'.$table.'`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			@mysqli_query($this->mysqli, 'drop table if exists `q_'.$table.'`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			@mysqli_query($this->mysqli, 'rename table `new_q_'.$table.'` to `q_'.$table.'`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		}
	}

	/**
	 * Make the alias with the most lines the new registered nick for the user or bot it is linked to.
	 */
	private function register_most_active_alias()
	{
		/**
		 * Find out which alias (uid) has the most lines for each registered user or bot (ruid).
		 */
		$query = @mysqli_query($this->mysqli, 'select `ruid`, `csnick`, (select `user_status`.`uid` from `user_status` join `user_lines` on `user_status`.`uid` = `user_lines`.`uid` where `ruid` = `t1`.`ruid` order by `l_total` desc, `user_status`.`uid` asc limit 1) as `uid`, `status` from `user_status` as `t1` join `user_details` on `t1`.`uid` = `user_details`.`uid` where `status` in (1,3)') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			return null;
		}

		while ($result = mysqli_fetch_object($query)) {
			/**
			 * No records need to be updated if:
			 * - All aliases linked to the registered user or bot have zero lines. The uid value will be null in this case.
			 * - The alias with the most lines is already set to be the registered user or bot.
			 */
			if (!is_null($result->uid) && $result->uid != $result->ruid) {
				$registered = $result->csnick;
				$query_alias = @mysqli_query($this->mysqli, 'select `csnick` from `user_details` where `uid` = '.$result->uid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				$result_alias = mysqli_fetch_object($query_alias);
				$alias = $result_alias->csnick;

				/**
				 * Update records:
				 * - Make the alias (uid) the new registered nick for the user or bot by setting ruid = uid. The status will be set to either
				 *   1 or 3, identical to previous value.
				 * - Update the ruid field of all records that still point to the old registered nick (ruid) and set it to the new one (uid).
				 *   Explicitly set the status to 2 so all records including the old registered nick are marked as alias.
				 */
				@mysqli_query($this->mysqli, 'update `user_status` set `ruid` = '.$result->uid.', `status` = '.$result->status.' where `uid` = '.$result->uid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				@mysqli_query($this->mysqli, 'update `user_status` set `ruid` = '.$result->uid.', `status` = 2 where `ruid` = '.$result->ruid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				$this->output('debug', 'register_most_active_alias(): \''.$alias.'\' set to new registered for \''.$registered.'\'');
			}
		}
	}
}

?>
