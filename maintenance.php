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
	 * Default settings for this script, which can be overridden in the configuration file. These variables should
	 * all appear in $settings_list[] along with their type.
	 */
	private $rankings = false;

	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $settings_list = array(
		'outputbits' => 'int',
		'rankings' => 'bool');

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
	 * Calculate user rankings by month.
	 */
	public function calculate_rankings($sqlite3)
	{
		/**
		 * Create an array with all dates since first channel activity. This helps define the scope for ruids.
		 */
		if (($date_firstactivity = $sqlite3->querySingle('SELECT MIN(date) FROM ruid_activity_by_month JOIN uid_details ON ruid_activity_by_month.ruid = uid_details.uid WHERE status NOT IN (3,4)')) === false) {
			$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		if (is_null($date_firstactivity)) {
			return null;
		}

		for ($i = $date_firstactivity, $j = date('Y-m', mktime(0, 0, 0, (int) date('n') + 1, 1, (int) date('Y'))); $i < $j; $i = date('Y-m', mktime(0, 0, 0, (int) substr($i, 5, 2) + 1, 1, (int) substr($i, 0, 4)))) {
			$scope[] = $i;
		}

		/**
		 * Retrieve all user activity.
		 */
		$query = $sqlite3->query('SELECT ruid_activity_by_month.ruid AS ruid, date, l_total FROM ruid_activity_by_month JOIN uid_details ON ruid_activity_by_month.ruid = uid_details.uid WHERE status NOT IN (3,4)') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$result = $query->fetchArray(SQLITE3_ASSOC);

		if ($result === false) {
			return null;
		}

		$query->reset();
		$channel_activity_by_month = array_fill_keys($scope, 0);
		$ruid_activity_by_month = array();

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			if (!array_key_exists($result['ruid'], $ruid_activity_by_month)) {
				$ruid_activity_by_month[$result['ruid']] = array_fill_keys(array_slice($scope, array_search($result['date'], $scope)), 0);
			}

			$channel_activity_by_month[$result['date']] += $result['l_total'];
			$ruid_activity_by_month[$result['ruid']][$result['date']] = $result['l_total'];
		}

		/**
		 * Calculate cumulative channel activity.
		 */
		$cumulative_l_total = 0;

		foreach ($channel_activity_by_month as $date => $l_total) {
			$cumulative_l_total += $l_total;
			$channel_activity_by_month_cumulative[$date] = $cumulative_l_total;
		}

		/**
		 * Calculate cumulative user activity.
		 */
		foreach ($ruid_activity_by_month as $ruid => $dates) {
			$cumulative_l_total = 0;

			foreach ($dates as $date => $l_total) {
				$cumulative_l_total += $l_total;
				$ruid_activity_by_month_cumulative[] = array(
					'ruid' => $ruid,
					'date' => $date,
					'l_total' => $cumulative_l_total);
				$sort_dates[] = $date;
				$sort_l_total[] = $cumulative_l_total;
				$sort_ruids[] = $ruid;
			}
		}

		/**
		 * Sort data and store on disk.
		 */
		array_multisort($sort_dates, SORT_ASC, $sort_l_total, SORT_DESC, $sort_ruids, SORT_ASC, $ruid_activity_by_month_cumulative);
		$sqlite3->exec('DELETE FROM ruid_rankings') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		foreach ($ruid_activity_by_month_cumulative as $key => $values) {
			if (empty($prevdate) || $values['date'] != $prevdate) {
				$rank = 1;
			}

			$sqlite3->exec('INSERT INTO ruid_rankings (ruid, date, rank, l_total, percentage) VALUES ('.$values['ruid'].', \''.$values['date'].'\', '.$rank.', '.$values['l_total'].', '.round(($values['l_total'] / $channel_activity_by_month_cumulative[$values['date']]) * 100, 2).')') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$prevdate = $values['date'];
			$rank++;
		}
	}

	public function do_maintenance($sqlite3)
	{
		$this->output('notice', 'do_maintenance(): performing database maintenance routines');
		$sqlite3->exec('BEGIN TRANSACTION') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$this->register_most_active_alias($sqlite3);
		$this->make_materialized_views($sqlite3);
		$this->calculate_milestones($sqlite3);

		if ($this->rankings) {
			$this->calculate_rankings($sqlite3);
		}

		$sqlite3->exec('COMMIT') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$sqlite3->exec('ANALYZE') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
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
