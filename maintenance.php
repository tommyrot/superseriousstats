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
	 * Calculate user rankings by month.
	 */
	public function calculate_rankings($sqlite3)
	{
		/**
		 * Create an array with all dates since first user activity. This will serve as the default scope for ruids.
		 */
		if (($date = $sqlite3->querySingle('SELECT MIN(SUBSTR(date, 1, 7)) AS date FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4)')) === false) {
			$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		if (is_null($date)) {
			return null;
		}

		$defaultscope = array();

		while ($date != date('Y-m', mktime(0, 0, 0, (int) date('n') + 1, 1, (int) date('Y')))) {
			$defaultscope[$date] = 0;
			$date = date('Y-m', mktime(0, 0, 0, (int) substr($date, 5, 2) + 1, 1, (int) substr($date, 0, 4)));
		}

		$query = $sqlite3->query('SELECT ruid_activity_by_month.ruid AS ruid, date, l_total FROM ruid_activity_by_month JOIN uid_details ON ruid_activity_by_month.ruid = uid_details.uid WHERE status NOT IN (3,4) ORDER BY ruid ASC, date ASC') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$result = $query->fetchArray(SQLITE3_ASSOC);

		if ($result === false) {
			return null;
		}

		$query->reset();
		$ruid_activity_by_month = array();

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			if (!array_key_exists($result['ruid'], $ruid_activity_by_month)) {
				$ruid_activity_by_month[$result['ruid']] = $defaultscope;
			}

			$ruid_activity_by_month[$result['ruid']][$result['date']] = $result['l_total'];
		}

		foreach ($ruid_activity_by_month as $ruid => $dates) {
			$prev_l_total = 0;

			foreach ($dates as $date => $l_total) {
				$cumulative_l_total = $prev_l_total + $l_total;

				/**
				 * Don't insert any records until the ruid was first active.
				 */
				if ($cumulative_l_total != 0) {
					$values[] = '('.$ruid.', \''.$date.'\', '.$cumulative_l_total.')';
				}

				$prev_l_total = $cumulative_l_total;
			}
		}

		/**
		 * Create a temporary "in-memory" database for the cumulated activity data. PRAGMAs as described in sss.php.
		 */
		try {
			$sqlite3temp = new SQLite3(':memory:');
		} catch (Exception $e) {
			$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$e->getMessage());
		}

		$pragmas = array(
			'journal_mode' => 'OFF',
			'synchronous' => 'OFF',
			'temp_store' => 'MEMORY');

		foreach ($pragmas as $key => $value) {
			$sqlite3temp->exec('PRAGMA '.$key.' = '.$value);
		}

		$queries = array(
			'CREATE TABLE ruid_ca (ruid INT NOT NULL, date TEXT NOT NULL, l_total INT NOT NULL)',
			'CREATE INDEX ruid_ca_date ON ruid_ca (date)',
			'CREATE TABLE channel_ca (date TEXT PRIMARY KEY NOT NULL, l_total INT NOT NULL)');

		foreach ($queries as $query) {
			$sqlite3temp->exec($query) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3temp->lastErrorMsg());
		}

		foreach ($values as $value) {
			$sqlite3temp->exec('INSERT INTO ruid_ca (ruid, date, l_total) VALUES '.$value) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3temp->lastErrorMsg());
		}

		$sqlite3temp->exec('INSERT INTO channel_ca SELECT date, SUM(l_total) AS l_total FROM ruid_ca GROUP BY date') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3temp->lastErrorMsg());

		/**
		 * Calculate user rankings and store them on disk.
		 */
		$query = $sqlite3temp->query('SELECT ruid, ruid_ca.date AS date, ruid_ca.l_total AS l_total, ROUND((CAST(ruid_ca.l_total AS REAL) / channel_ca.l_total) * 100, 2) AS percentage FROM ruid_ca JOIN channel_ca ON ruid_ca.date = channel_ca.date ORDER BY date ASC, ruid_ca.l_total DESC, ruid ASC') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3temp->lastErrorMsg());
		$result = $query->fetchArray(SQLITE3_ASSOC);

		if ($result === false) {
			return null;
		}

		$query->reset();
		unset($values);

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			if (empty($prevdate) || $result['date'] != $prevdate) {
				$rank = 1;
			}

			$values[] = '('.$result['ruid'].', \''.$result['date'].'\', '.$rank.', '.$result['l_total'].', '.$result['percentage'].')';
			$prevdate = $result['date'];
			$rank++;
		}

		$sqlite3->exec('DELETE FROM ruid_rankings') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		foreach ($values as $value) {
			$sqlite3->exec('INSERT INTO ruid_rankings (ruid, date, rank, l_total, percentage) VALUES '.$value) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$sqlite3temp->close();
	}

	public function do_maintenance($sqlite3)
	{
		$this->output('notice', 'do_maintenance(): performing database maintenance routines');
		$sqlite3->exec('BEGIN TRANSACTION') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$this->register_most_active_alias($sqlite3);
		$this->make_materialized_views($sqlite3);
		$this->calculate_milestones($sqlite3);
		$this->calculate_rankings($sqlite3);
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
