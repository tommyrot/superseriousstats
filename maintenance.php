<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2021, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Class for performing database maintenance.
 */
class maintenance
{
	use common;

	private bool $auto_link_nicks = true;

	public function __construct()
	{
		$this->apply_vars('settings', ['auto_link_nicks']);
		$this->main();
	}

	/**
	 * Calculate on which date a user reached certain milestones. Skip bots and
	 * excluded users.
	 */
	private function calculate_milestones(): void
	{
		/**
		 * Only continue if "uid_activity" was modified since last maintenance.
		 */
		if (db::query_single_col('SELECT modified FROM table_state WHERE table_name = \'uid_activity\'') === 0) {
			return;
		}

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
		 * Retrieve the modification state from the database and decide which views
		 * should be materialized. Make sure the order in which they are processed is
		 * correct as some views depend on materialized views created prior to them.
		 */
		if (db::query_single_col('SELECT modified FROM table_state WHERE table_name = \'uid_details\'') === 1) {
			$views = ['v_ruid_activity_by_day', 'v_ruid_activity_by_month', 'v_ruid_activity_by_year', 'v_ruid_lines', 'v_ruid_smileys', 'v_ruid_urls', 'v_ruid_events'];
		} else {
			if (db::query_single_col('SELECT modified FROM table_state WHERE table_name = \'uid_activity\'') === 1) {
				$views = ['v_ruid_activity_by_day', 'v_ruid_activity_by_month', 'v_ruid_activity_by_year', 'v_ruid_lines'];

				if (db::query_single_col('SELECT modified FROM table_state WHERE table_name = \'uid_smileys\'') === 1) {
					$views[] = 'v_ruid_smileys';
				}

				if (db::query_single_col('SELECT modified FROM table_state WHERE table_name = \'uid_urls\'') === 1) {
					$views[] = 'v_ruid_urls';
				}
			} elseif (db::query_single_col('SELECT modified FROM table_state WHERE table_name = \'uid_lines\'') === 1) {
				$views[] = 'v_ruid_lines';
			}

			if (db::query_single_col('SELECT modified FROM table_state WHERE table_name = \'uid_events\'') === 1) {
				$views[] = 'v_ruid_events';
			}
		}

		foreach ($views as $view) {
			$table = substr($view, 2);
			db::query_exec('DELETE FROM '.$table);
			db::query_exec('INSERT INTO '.$table.' SELECT * FROM '.$view);
		}

		if (!empty($views)) {
			out::put('debug', 'materialized views: \''.preg_replace('/v_ruid_/', '', implode('\', \'', $views)).'\'');
		}
	}

	/**
	 * The file "tlds-alpha-by-domain.txt" contains all TLDs which are currently
	 * active on the internet. Cross-match this list with the TLDs we have stored
	 * in our database and deactivate those that do not match. Optional feature.
	 */
	private function deactivate_fqdns(): void
	{
		/**
		 * Reset all inactive TLDs to active state, as is the default upon entry.
		 */
		db::query_exec('UPDATE fqdns SET active = 1 WHERE active = 0');

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
			db::query_exec('UPDATE fqdns SET active = 0 WHERE tld NOT IN ('.implode(',', $tlds_active).')');
			out::put('debug', 'deactivated '.db::changes().' invalid fqdn'.(db::changes() !== 1 ? 's' : ''));
		}
	}

	/**
	 * Try to link unlinked nicks to any other nick that is identical after
	 * stripping them both from any non-letter and non-numeric characters as well as
	 * any trailing numerics. The results are compared in a case insensitive manner.
	 */
	private function link_nicks(): void
	{
		/**
		 * Only continue if "uid_details" was modified since last maintenance.
		 */
		if (db::query_single_col('SELECT modified FROM table_state WHERE table_name = \'uid_details\'') === 0) {
			return;
		}

		$results = db::query('SELECT uid, csnick, ruid, status FROM uid_details');
		$nicks_stripped = [];

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			$nicks[$result['uid']] = [
				'csnick' => $result['csnick'],
				'ruid' => $result['ruid'],
				'status' => $result['status']];
			$nick_stripped = preg_replace(['/[^\p{L}\p{N}]+/u', '/\p{N}+$/u'], '', mb_strtolower($result['csnick']));

			/**
			 * The stripped nick must consist of at least two characters.
			 */
			if (mb_strlen($nick_stripped) >= 2) {
				/**
				 * Maintain an array for each stripped nick, containing the uids of every nick
				 * that matches it. Put the uid of a matching nick at the start of the array if
				 * it is already linked (status != 0), otherwise put it at the end.
				 */
				if ($result['status'] !== 0 && isset($nicks_stripped[$nick_stripped])) {
					array_unshift($nicks_stripped[$nick_stripped], $result['uid']);
				} else {
					$nicks_stripped[$nick_stripped][] = $result['uid'];
				}
			}
		}

		foreach ($nicks_stripped as $uids) {
			/**
			 * If there is only one match for the stripped nick, there is nothing to link.
			 */
			if (count($uids) === 1) {
				continue;
			}

			$new_alias = false;

			for ($i = 1, $j = count($uids); $i < $j; ++$i) {
				/**
				 * Use the ruid that belongs to the first uid in the array to link all
				 * succeeding _unlinked_ nicks to.
				 */
				if ($nicks[$uids[$i]]['status'] === 0) {
					$new_alias = true;
					db::query_exec('UPDATE uid_details SET ruid = '.$nicks[$uids[0]]['ruid'].', status = 2 WHERE uid = '.$uids[$i]);
					out::put('debug', 'linked \''.$nicks[$uids[$i]]['csnick'].'\' to \''.$nicks[$nicks[$uids[0]]['ruid']]['csnick'].'\'');
				}
			}

			/**
			 * If there are aliases found, and the first nick in the array is unlinked
			 * (status = 0), make it a registered nick (status = 1).
			 */
			if ($new_alias && $nicks[$uids[0]]['status'] === 0) {
				db::query_exec('UPDATE uid_details SET status = 1 WHERE uid = '.$uids[0]);
			}
		}
	}

	/**
	 * The following routines ensure we have a usable, consistent dataset.
	 */
	private function main(): void
	{
		out::put('notice', 'performing database maintenance routines');

		if ($this->auto_link_nicks) {
			$this->link_nicks();
		}

		$this->register_most_active_aliases();
		$this->create_materialized_views();
		$this->calculate_milestones();
		$this->deactivate_fqdns();

		/**
		 * Reset the modification state for all tables.
		 */
		db::query_exec('UPDATE table_state SET modified = 0 WHERE modified = 1');
	}

	/**
	 * Make the alias with the most lines the new registered nick for the user or
	 * bot it is linked to.
	 */
	private function register_most_active_aliases(): void
	{
		$results = db::query('SELECT status, csnick, ruid, (SELECT uid_details.uid AS uid FROM uid_details JOIN uid_lines ON uid_details.uid = uid_lines.uid WHERE ruid = t1.ruid ORDER BY l_total DESC, uid ASC LIMIT 1) AS new_ruid FROM uid_details AS t1 WHERE status IN (1,3,4) AND IFNULL(new_ruid, ruid) != ruid');

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			$old_registered_nick = $result['csnick'];
			$new_registered_nick = db::query_single_col('SELECT csnick FROM uid_details WHERE uid = '.$result['new_ruid']);
			db::query_exec('UPDATE uid_details SET ruid = '.$result['new_ruid'].', status = '.$result['status'].' WHERE uid = '.$result['new_ruid']);
			db::query_exec('UPDATE uid_details SET ruid = '.$result['new_ruid'].', status = 2 WHERE ruid = '.$result['ruid']);
			out::put('debug', '\''.$new_registered_nick.'\' new registered nick for \''.$old_registered_nick.'\'');
		}
	}
}
