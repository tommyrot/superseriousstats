<?php

/**
 * Copyright (c) 2007-2020, Jos de Ruijter <jos@dutnie.nl>
 */

declare(strict_types=1);

/**
 * Class for performing database maintenance.
 */
class maintenance
{
	public function __construct(object $sqlite3)
	{
		output::output('notice', __METHOD__.'(): performing database maintenance routines');
		$sqlite3->exec('BEGIN TRANSACTION') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		output::output('notice', __METHOD__.'(): (1/3) registering most active aliases');
		$this->register_most_active_aliases($sqlite3);
		output::output('notice', __METHOD__.'(): (2/3) creating materialized views');
		$this->create_materialized_views($sqlite3);
		output::output('notice', __METHOD__.'(): (3/3) calculating milestones');
		$this->calculate_milestones($sqlite3);
		output::output('notice', __METHOD__.'(): committing data');
		$sqlite3->exec('COMMIT') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
	}

	/**
	 * Calculate on which date a user reached certain milestones.
	 */
	private function calculate_milestones(object $sqlite3): void
	{
		$query = $sqlite3->query('SELECT ruid_activity_by_day.ruid AS ruid, date, l_total FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) ORDER BY ruid ASC, date ASC') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			if (!isset($l_total[$result['ruid']])) {
				$l_total[$result['ruid']] = $result['l_total'];
				$milestones = [1000, 2500, 5000, 10000, 25000, 50000, 100000, 250000, 500000, 1000000];
				$milestone = array_shift($milestones);
			} else {
				$l_total[$result['ruid']] += $result['l_total'];
			}

			while (!is_null($milestone) && $l_total[$result['ruid']] >= $milestone) {
				$queryparts[] = '('.$result['ruid'].', '.$milestone.', \''.$result['date'].'\')';
				$milestone = array_shift($milestones);
			}
		}

		if (!empty($queryparts)) {
			$sqlite3->exec('DELETE FROM ruid_milestones') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

			foreach ($queryparts as $values) {
				$sqlite3->exec('INSERT INTO ruid_milestones (ruid, milestone, date) VALUES '.$values) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		}
	}

	/**
	 * Create materialized views, which are actual stored copies of virtual tables
	 * (views).
	 */
	private function create_materialized_views(object $sqlite3): void
	{
		/**
		 * The results from the view in the left column will be stored as the
		 * materialized view in the right column. The order in which they are listed is
		 * important, as some views depend on materialized views created before them.
		 */
		$views = [
			'v_ruid_activity_by_day' => 'ruid_activity_by_day',
			'v_ruid_activity_by_month' => 'ruid_activity_by_month',
			'v_ruid_activity_by_year' => 'ruid_activity_by_year',
			'v_ruid_smileys' => 'ruid_smileys',
			'v_ruid_events' => 'ruid_events',
			'v_ruid_lines' => 'ruid_lines'];

		foreach ($views as $view => $table) {
			$sqlite3->exec('DELETE FROM '.$table) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$sqlite3->exec('INSERT INTO '.$table.' SELECT * FROM '.$view) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}
	}

	/**
	 * Make the alias with the most lines the new registered nick for the user or
	 * bot it is linked to.
	 */
	private function register_most_active_aliases(object $sqlite3): void
	{
		$query = $sqlite3->query('SELECT status, csnick, ruid, (SELECT uid_details.uid AS uid FROM uid_details JOIN uid_lines ON uid_details.uid = uid_lines.uid WHERE ruid = t1.ruid ORDER BY l_total DESC, uid ASC LIMIT 1) AS new_ruid FROM uid_details AS t1 WHERE status IN (1,3,4) AND new_ruid IS NOT NULL AND ruid != new_ruid') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$old_registered_nick = $result['csnick'];

			if (($new_registered_nick = $sqlite3->querySingle('SELECT csnick FROM uid_details WHERE uid = '.$result['new_ruid'])) === false) {
				output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			$sqlite3->exec('UPDATE uid_details SET ruid = '.$result['new_ruid'].', status = '.$result['status'].' WHERE uid = '.$result['new_ruid']) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$sqlite3->exec('UPDATE uid_details SET ruid = '.$result['new_ruid'].', status = 2 WHERE ruid = '.$result['ruid']) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			output::output('debug', __METHOD__.'(): \''.$new_registered_nick.'\' new registered nick for \''.$old_registered_nick.'\'');
		}
	}
}
