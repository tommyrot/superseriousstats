<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2020, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Class for performing database maintenance.
 */
class maintenance
{
	public function __construct()
	{
		$this->main();
	}

	/**
	 * Calculate on which date a user reached certain milestones.
	 */
	private function calculate_milestones(): void
	{
		db::query_exec('DELETE FROM ruid_milestones');
		$results = db::query('SELECT ruid_activity_by_day.ruid AS ruid, date, l_total FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) ORDER BY ruid ASC, date ASC');

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			if (!isset($l_total[$result['ruid']])) {
				$l_total[$result['ruid']] = $result['l_total'];
				$milestones = [1000, 2500, 5000, 10000, 25000, 50000, 100000, 250000, 500000, 1000000];
				$milestone = array_shift($milestones);
			} else {
				$l_total[$result['ruid']] += $result['l_total'];
			}

			while (!is_null($milestone) && $l_total[$result['ruid']] >= $milestone) {
				db::query_exec('INSERT INTO ruid_milestones (ruid, milestone, date) VALUES ('.$result['ruid'].', '.$milestone.', \''.$result['date'].'\')');
				$milestone = array_shift($milestones);
			}
		}
	}

	/**
	 * Create materialized views, which are actual stored copies of virtual tables
	 * (simply called views).
	 */
	private function create_materialized_views(): void
	{
		/**
		 * Data from the views below (v_ruid_*) will be stored as materialized views
		 * (ruid_*) in the database. The order in which they are processed is important,
		 * as some views depend on materialized views created prior to them.
		 */
		$views = [
			'v_ruid_activity_by_day' => 'ruid_activity_by_day',
			'v_ruid_activity_by_month' => 'ruid_activity_by_month',
			'v_ruid_activity_by_year' => 'ruid_activity_by_year',
			'v_ruid_smileys' => 'ruid_smileys',
			'v_ruid_events' => 'ruid_events',
			'v_ruid_lines' => 'ruid_lines'];

		foreach ($views as $view => $table) {
			db::query_exec('DELETE FROM '.$table);
			db::query_exec('INSERT INTO '.$table.' SELECT * FROM '.$view);
		}
	}

	/**
	 * The file "tlds-alpha-by-domain.txt" contains all TLDs which are currently
	 * active on the internet. Cross-match this list with the TLDs we have stored
	 * in our database and deactivate those that do not match. Optional feature.
	 */
	private function deactivate_fqdns(): void
	{
		if (($rp = realpath('tlds-alpha-by-domain.txt')) === false) {
			out::put('debug', 'no such file: \'tlds-alpha-by-domain.txt\', skipping tld validation');
			return;
		}

		if (($fp = fopen($rp, 'rb')) === false) {
			out::put('notice', 'failed to open file: \''.$rp.'\', skipping tld validation');
			return;
		}

		while (($line = fgets($fp)) !== false) {
			if (preg_match('/^(?<tld>[a-z0-9-]+)$/i', $line, $matches)) {
				$tlds_active[] = '\''.strtolower($matches['tld']).'\'';
			}
		}

		fclose($fp);

		if (isset($tlds_active)) {
			db::query_exec('UPDATE fqdns SET active = 1');
			db::query_exec('UPDATE fqdns SET active = 0 WHERE tld NOT IN ('.implode(',', $tlds_active).')');
			out::put('debug', 'deactivated '.db::changes().' invalid fqdn'.(db::changes() !== 1 ? 's' : ''));
		}
	}

	/**
	 * Upon class instantiation automatically start the main function below.
	 */
	private function main(): void
	{
		$this->register_most_active_aliases();
		$this->create_materialized_views();
		$this->calculate_milestones();
		$this->deactivate_fqdns();
	}

	/**
	 * Make the alias with the most lines the new registered nick for the user or
	 * bot it is linked to.
	 */
	private function register_most_active_aliases(): void
	{
		$results = db::query('SELECT status, csnick, ruid, (SELECT uid_details.uid AS uid FROM uid_details JOIN uid_lines ON uid_details.uid = uid_lines.uid WHERE ruid = t1.ruid ORDER BY l_total DESC, uid ASC LIMIT 1) AS new_ruid FROM uid_details AS t1 WHERE status IN (1,3,4) AND new_ruid IS NOT NULL AND ruid != new_ruid');

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			$old_registered_nick = $result['csnick'];
			$new_registered_nick = db::query_single_col('SELECT csnick FROM uid_details WHERE uid = '.$result['new_ruid']);
			db::query_exec('UPDATE uid_details SET ruid = '.$result['new_ruid'].', status = '.$result['status'].' WHERE uid = '.$result['new_ruid']);
			db::query_exec('UPDATE uid_details SET ruid = '.$result['new_ruid'].', status = 2 WHERE ruid = '.$result['ruid']);
			out::put('debug', '\''.$new_registered_nick.'\' new registered nick for \''.$old_registered_nick.'\'');
		}
	}
}
