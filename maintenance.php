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
 * Class for performing database maintenance.
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
		 * If set, override variables listed in $settings_list[].
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

	/**
	 * Calculate on which dates a user reached certain milestones.
	 */
	public function calculate_milestones($sqlite3)
	{
		$query = $sqlite3->query('SELECT ruid_activity_by_day.ruid AS ruid, date, l_total FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) ORDER BY ruid ASC, date ASC') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$result = $query->fetchArray(SQLITE3_ASSOC);

		if ($result === false) {
			return null;
		}

		$query->reset();

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			if (!isset($l_total[$result['ruid']])) {
				$l_total[$result['ruid']] = $result['l_total'];
				$milestones = array(1000, 2500, 5000, 10000, 25000, 50000, 100000, 250000, 500000, 1000000);
				$nextmilestone = array_shift($milestones);
			} else {
				$l_total[$result['ruid']] += $result['l_total'];
			}

			while (!is_null($nextmilestone) && $l_total[$result['ruid']] >= $nextmilestone) {
				$values[] = '('.$result['ruid'].', '.$nextmilestone.', \''.$result['date'].'\')';
				$nextmilestone = array_shift($milestones);
			}
		}

		if (!empty($values)) {
			$sqlite3->exec('DELETE FROM ruid_milestones') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

			foreach ($values as $value) {
				$sqlite3->exec('INSERT INTO ruid_milestones (ruid, milestone, date) VALUES '.$value) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		}
	}

	/**
	 * Cumulate monthly channel and user activity.
	 */
	public function cumulate_activity($sqlite3)
	{
		if (($date_firstactivity = $sqlite3->querySingle('SELECT MIN(SUBSTR(date, 1, 7)) AS date FROM channel_activity')) === false) {
			$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		if (is_null($date_firstactivity)) {
			return null;
		}

		/**
		 * Create an array filled with all the dates since first activity. This will be the default array for ruids.
		 */
		$defaultarray = array();

		while ($date_firstactivity != date('Y-m', mktime(0, 0, 0, (int) date('n') + 1, 1, (int) date('Y')))) {
			$defaultarray[$date_firstactivity] = 0;
			$date_firstactivity = date('Y-m', mktime(0, 0, 0, (int) substr($date_firstactivity, 5, 2) + 1, 1, (int) substr($date_firstactivity, 0, 4)));
		}

		$query = $sqlite3->query('SELECT ruid, date, l_total FROM ruid_activity_by_month ORDER BY ruid ASC, date ASC') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$result = $query->fetchArray(SQLITE3_ASSOC);

		if ($result === false) {
			return null;
		}

		$query->reset();
		$ruid_activity_by_month = array();

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			if (!array_key_exists($result['ruid'], $ruid_activity_by_month)) {
				$ruid_activity_by_month[$result['ruid']] = $defaultarray;
			}

			$ruid_activity_by_month[$result['ruid']][$result['date']] = $result['l_total'];
		}

		foreach ($ruid_activity_by_month as $ruid => $dates) {
			$prev_l_total = 0;

			foreach ($dates as $date => $l_total) {
				$cumulative_l_total = $prev_l_total + $l_total;

				if ($cumulative_l_total != 0) {
					$values[] = '('.$ruid.', \''.$date.'\', '.$cumulative_l_total.')';
				}

				$prev_l_total = $cumulative_l_total;
			}
		}

		$sqlite3->exec('DELETE FROM ruid_activity_by_month_cumulative') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		foreach ($values as $value) {
			$sqlite3->exec('INSERT INTO ruid_activity_by_month_cumulative (ruid, date, l_total) VALUES '.$value) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$sqlite3->exec('DELETE FROM channel_activity_by_month_cumulative') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$sqlite3->exec('INSERT INTO channel_activity_by_month_cumulative SELECT date, SUM(l_total) FROM ruid_activity_by_month_cumulative GROUP BY date') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
	}

	public function do_maintenance($sqlite3)
	{
		$this->output('notice', 'do_maintenance(): performing database maintenance routines');
		$sqlite3->exec('BEGIN TRANSACTION') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$this->register_most_active_alias($sqlite3);
		$this->make_materialized_views($sqlite3);
		$this->calculate_milestones($sqlite3);
		//$this->cumulate_activity($sqlite3); --disabled until fully complete
		$sqlite3->exec('COMMIT') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
	}

	/**
	 * Make materialized views, which are actual stored copies of virtual tables (views).
	 */
	private function make_materialized_views($sqlite3)
	{
		/**
		 * The results from the view in the left column will be stored as the materialized view in the right
		 * column. The order in which they are listed is important, as some views depend on materialized views
		 * created before them.
		 */
		$views = array(
			'v_ruid_activity_by_day' => 'ruid_activity_by_day',
			'v_ruid_activity_by_month' => 'ruid_activity_by_month',
			'v_ruid_activity_by_year' => 'ruid_activity_by_year',
			'v_activedays' => 'mv_activedays',
			'v_events' => 'mv_events',
			'v_ex_actions' => 'mv_ex_actions',
			'v_ex_exclamations' => 'mv_ex_exclamations',
			'v_ex_kicked' => 'mv_ex_kicked',
			'v_ex_kicks' => 'mv_ex_kicks',
			'v_ex_questions' => 'mv_ex_questions',
			'v_ex_uppercased' => 'mv_ex_uppercased',
			'v_quote' => 'mv_quote',
			'v_lines' => 'mv_lines',
			'v_ruid_smileys' => 'ruid_smileys',
			'v_ruid_events' => 'ruid_events',
			'v_ruid_lines' => 'ruid_lines');

		foreach ($views as $view => $table) {
			$sqlite3->exec('DELETE FROM '.$table) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$sqlite3->exec('INSERT INTO '.$table.' SELECT * FROM '.$view) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}
	}

	/**
	 * Make the alias with the most lines the new registered nick for the user or bot it is linked to.
	 */
	private function register_most_active_alias($sqlite3)
	{
		$query = $sqlite3->query('SELECT status, csnick, ruid, (SELECT uid_details.uid AS uid FROM uid_details JOIN uid_lines ON uid_details.uid = uid_lines.uid WHERE ruid = t1.ruid ORDER BY l_total DESC, uid ASC LIMIT 1) AS newruid FROM uid_details AS t1 WHERE status IN (1,3,4) AND newruid IS NOT NULL AND ruid != newruid') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$result = $query->fetchArray(SQLITE3_ASSOC);

		if ($result === false) {
			return null;
		}

		$query->reset();

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$registered = $result['csnick'];

			if (($alias = $sqlite3->querySingle('SELECT csnick FROM uid_details WHERE uid = '.$result['newruid'])) === false) {
				$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			$sqlite3->exec('UPDATE uid_details SET ruid = '.$result['newruid'].', status = '.$result['status'].' WHERE uid = '.$result['newruid']) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$sqlite3->exec('UPDATE uid_details SET ruid = '.$result['newruid'].', status = 2 WHERE ruid = '.$result['ruid']) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$this->output('debug', 'register_most_active_alias(): \''.$alias.'\' set to new registered for \''.$registered.'\'');
		}
	}
}

?>
