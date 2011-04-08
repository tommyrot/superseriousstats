<?php

/**
 * Copyright (c) 2007-2011, Jos de Ruijter <jos@dutnie.nl>
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
		parent::__construct();

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

	public function do_maintenance($mysqli)
	{
		$this->mysqli = $mysqli;
		$this->output('notice', 'do_maintenance(): performing database maintenance routines');
		$query = @mysqli_query($this->mysqli, 'select * from `user_status` limit 1') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			$this->output('notice', 'do_maintenance(): database is empty, skipping some tasks');
		} else {
			$this->fix_user_status_errors();
			$this->register_most_active_alias();
		}

		$this->make_materialized_views();
		$this->output('notice', 'do_maintenance(): maintenance completed');
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
		$query = @mysqli_query($this->mysqli, 'select `uid` from `user_status` where `uid` = `ruid` and `status` = 2 order by `uid` asc') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (!empty($rows)) {
			while ($result = mysqli_fetch_object($query)) {
				@mysqli_query($this->mysqli, 'update `user_status` set `status` = 0 where `uid` = '.$result->uid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				$this->output('debug', 'fix_user_status_errors(): uid '.$result->uid.' set to default (alias of self)');
			}
		}

		/**
		 * Nicks with uid != ruid can only have status = 2. Set back to 0 if status != 2 and set uid = ruid accordingly.
		 */
		$query = @mysqli_query($this->mysqli, 'select `uid` from `user_status` where `uid` != `ruid` and `status` != 2 order by `uid` asc') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (!empty($rows)) {
			while ($result = mysqli_fetch_object($query)) {
				@mysqli_query($this->mysqli, 'update `user_status` set `ruid` = '.$result->uid.', `status` = 0 where `uid` = '.$result->uid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				$this->output('debug', 'fix_user_status_errors(): uid '.$result->uid.' set to default (non alias pointing to non self)');
			}
		}

		/**
		 * Every alias must have their ruid set to the uid of a registered nick. Which in turn has uid = ruid and status = 1 or 3. Unlink aliases pointing to invalid ruids.
		 */
		$query_valid_ruids = @mysqli_query($this->mysqli, 'select `ruid` from `user_status` where `uid` = `ruid` and (`status` = 1 or `status` = 3) order by `ruid` asc') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query_valid_ruids);

		if (!empty($rows)) {
			$query_linked_ruids = @mysqli_query($this->mysqli, 'select distinct `ruid` from `user_status` where `uid` != `ruid` and `status` = 2 order by `ruid` asc') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query_linked_ruids);

			/**
			 * If there aren't any aliases we can stop here.
			 */
			if (empty($rows)) {
				return;
			}

			while ($result_valid_ruids = mysqli_fetch_object($query_valid_ruids)) {
				$valid_ruids[] = $result_valid_ruids->ruid;
			}

			while ($result_linked_ruids = mysqli_fetch_object($query_linked_ruids)) {
				$linked_ruids[] = $result_linked_ruids->ruid;
			}

			/**
			 * Do what we're here to do, unlink when appropriate.
			 */
			foreach ($linked_ruids as $ruid) {
				if (in_array($ruid, $valid_ruids)) {
					continue;
				}

				$query = @mysqli_query($this->mysqli, 'select `uid` from `user_status` where `ruid` = '.$ruid.' and `status` = 2 order by `uid` asc') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				$rows = mysqli_num_rows($query);

				if (!empty($rows)) {
					while ($result = mysqli_fetch_object($query)) {
						@mysqli_query($this->mysqli, 'update `user_status` set `ruid` = '.$result->uid.', `status` = 0 where `uid` = '.$result->uid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
						$this->output('debug', 'fix_user_status_errors(): uid '.$result->uid.' set to default (pointing to invalid registered)');
					}
				}
			}
		}
	}

	/**
	 * Make materialized views, which are stored copies of dynamic views.
	 * Query tables are top level materialized views based on various sub views and contain accumulated stats per ruid.
	 * Legend: mv_ materialized view, q_ query table, t_ template, v_ view. Combinations do exist.
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
		 * First get all valid ruids (uid = ruid and status = 1 or 3). Then check for aliases pointing to those ruids and determine the one with most lines.
		 * Finally change the registered nick.
		 */
		$query_valid_ruids = @mysqli_query($this->mysqli, 'select `ruid`, `status` from `user_status` where `uid` = `ruid` and (`status` = 1 or `status` = 3) order by `ruid` asc') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query_valid_ruids);

		/**
		 * If there aren't any registered nicks we can stop here.
		 */
		if (empty($rows)) {
			return;
		}

		while ($result_valid_ruids = mysqli_fetch_object($query_valid_ruids)) {
			$query_aliases = @mysqli_query($this->mysqli, 'select `user_status`.`uid` from `user_status` join `user_lines` on `user_status`.`uid` = `user_lines`.`uid` where `ruid` = '.$result_valid_ruids->ruid.' order by `l_total` desc, `user_status`.`uid` asc limit 1') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query_aliases);

			if (empty($rows)) {
				continue;
			}

			$result_aliases = mysqli_fetch_object($query_aliases);

			if ($result_aliases->uid != $result_valid_ruids->ruid) {
				/**
				 * Make the alias the new registered nick; set uid = ruid and status = 1 or 3 depending on the status the old registered nick had.
				 * Update all nicks linked to the old registered nick and make their ruid point to the new one.
				 */
				@mysqli_query($this->mysqli, 'update `user_status` set `ruid` = '.$result_aliases->uid.', `status` = '.$result_valid_ruids->status.' where `uid` = '.$result_aliases->uid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				@mysqli_query($this->mysqli, 'update `user_status` set `ruid` = '.$result_aliases->uid.', `status` = 2 where `ruid` = '.$result_valid_ruids->ruid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				$this->output('debug', 'register_most_active_alias(): uid '.$result_aliases->uid.' set to new registered');
			}
		}
	}
}

?>
