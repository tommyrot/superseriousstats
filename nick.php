<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2021, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Class for handling user data.
 */
class nick
{
	use common, queryparts;

	private array $smileys = [];
	private int $actions = 0;
	private int $characters = 0;
	private int $exclamations = 0;
	private int $joins = 0;
	private int $kicked = 0;
	private int $kicks = 0;
	private int $l_00 = 0;
	private int $l_01 = 0;
	private int $l_02 = 0;
	private int $l_03 = 0;
	private int $l_04 = 0;
	private int $l_05 = 0;
	private int $l_06 = 0;
	private int $l_07 = 0;
	private int $l_08 = 0;
	private int $l_09 = 0;
	private int $l_10 = 0;
	private int $l_11 = 0;
	private int $l_12 = 0;
	private int $l_13 = 0;
	private int $l_14 = 0;
	private int $l_15 = 0;
	private int $l_16 = 0;
	private int $l_17 = 0;
	private int $l_18 = 0;
	private int $l_19 = 0;
	private int $l_20 = 0;
	private int $l_21 = 0;
	private int $l_22 = 0;
	private int $l_23 = 0;
	private int $l_afternoon = 0;
	private int $l_evening = 0;
	private int $l_fri_afternoon = 0;
	private int $l_fri_evening = 0;
	private int $l_fri_morning = 0;
	private int $l_fri_night = 0;
	private int $l_mon_afternoon = 0;
	private int $l_mon_evening = 0;
	private int $l_mon_morning = 0;
	private int $l_mon_night = 0;
	private int $l_morning = 0;
	private int $l_night = 0;
	private int $l_sat_afternoon = 0;
	private int $l_sat_evening = 0;
	private int $l_sat_morning = 0;
	private int $l_sat_night = 0;
	private int $l_sun_afternoon = 0;
	private int $l_sun_evening = 0;
	private int $l_sun_morning = 0;
	private int $l_sun_night = 0;
	private int $l_thu_afternoon = 0;
	private int $l_thu_evening = 0;
	private int $l_thu_morning = 0;
	private int $l_thu_night = 0;
	private int $l_total = 0;
	private int $l_tue_afternoon = 0;
	private int $l_tue_evening = 0;
	private int $l_tue_morning = 0;
	private int $l_tue_night = 0;
	private int $l_wed_afternoon = 0;
	private int $l_wed_evening = 0;
	private int $l_wed_morning = 0;
	private int $l_wed_night = 0;
	private int $m_deop = 0;
	private int $m_deopped = 0;
	private int $m_devoice = 0;
	private int $m_devoiced = 0;
	private int $m_op = 0;
	private int $m_opped = 0;
	private int $m_voice = 0;
	private int $m_voiced = 0;
	private int $monologues = 0;
	private int $nickchanges = 0;
	private int $parts = 0;
	private int $questions = 0;
	private int $quits = 0;
	private int $slapped = 0;
	private int $slaps = 0;
	private int $topics = 0;
	private int $topmonologue = 0;
	private int $uppercased = 0;
	private int $urls = 0;
	private int $words = 0;
	private string $csnick = '';
	private string $ex_actions = '';
	private string $ex_exclamations = '';
	private string $ex_kicked = '';
	private string $ex_kicks = '';
	private string $ex_questions = '';
	private string $ex_uppercased = '';
	private string $firstseen = '';
	private string $lastseen = '';
	private string $lasttalked = '';
	private string $quote = '';

	public function __construct(string $csnick)
	{
		$this->csnick = $csnick;
	}

	public function add_smiley(int $sid, int $value): void
	{
		if (!isset($this->smileys[$sid])) {
			$this->smileys[$sid] = $value;
		} else {
			$this->smileys[$sid] += $value;
		}
	}

	/**
	 * Store everything in the database.
	 */
	public function store_data(): void
	{
		/**
		 * Store data in database table "uid_details" and update "uid_lines" if needed.
		 */
		if (is_null($uid = db::query_single_col('SELECT uid FROM uid_details WHERE csnick = \''.$this->csnick.'\''))) {
			if ($this->firstseen === '') {
				/**
				 * If $firstseen is empty there have been no lines, actions or events for this
				 * nick. The only two possibilities left are; this nick has been slapped, or
				 * this nick had its streak interrupted. Since this nick isn't already in the
				 * database that rules out a streak. We won't add the nick to the database just
				 * because of a slap to avoid abuse.
				 */
				out::put('debug', 'skipping empty nick: \''.$this->csnick.'\'');
				return;
			}

			$uid = db::query_exec('INSERT INTO uid_details (uid, csnick, firstseen, lastseen) VALUES (NULL, \''.$this->csnick.'\', DATETIME(\''.$this->firstseen.'\'), DATETIME(\''.$this->lastseen.'\'))');
		} else {
			if ($this->firstseen === '') {
				/**
				 * If $firstseen is empty there have been no lines, actions or events for this
				 * nick. The only two possibilities left are; this nick has been slapped, or
				 * this nick had its streak interrupted. Update the appropriate values.
				 */
				$queryparts = $this->get_queryparts(['monologues', 'slapped']);

				if ($this->topmonologue !== 0 && $this->topmonologue > db::query_single_col('SELECT topmonologue FROM uid_lines WHERE uid = '.$uid)) {
					$queryparts['update_assignments'] .= ', topmonologue = '.$this->topmonologue;
				}

				db::query_exec('UPDATE uid_lines SET '.$queryparts['update_assignments'].' WHERE uid = '.$uid);
				return;
			}

			db::query_exec('UPDATE uid_details SET csnick = \''.$this->csnick.'\', lastseen = DATETIME(\''.$this->lastseen.'\') WHERE uid = '.$uid);
		}

		/**
		 * Store data in database table "uid_events".
		 */
		if (!is_null($queryparts = $this->get_queryparts(['m_op', 'm_opped', 'm_voice', 'm_voiced', 'm_deop', 'm_deopped', 'm_devoice', 'm_devoiced', 'joins', 'parts', 'quits', 'kicks', 'kicked', 'nickchanges', 'topics', 'ex_kicks', 'ex_kicked']))) {
			db::query_exec('INSERT INTO uid_events (uid, '.$queryparts['insert_columns'].') VALUES ('.$uid.', '.$queryparts['insert_values'].') ON CONFLICT (uid) DO UPDATE SET '.$queryparts['update_assignments']);
		}

		/**
		 * Store data in database tables "uid_activity" and "uid_smileys".
		 */
		if ($this->l_total !== 0) {
			$queryparts = $this->get_queryparts(['l_night', 'l_morning', 'l_afternoon', 'l_evening', 'l_total']);
			db::query_exec('INSERT INTO uid_activity (uid, date, '.$queryparts['insert_columns'].') VALUES ('.$uid.', \''.substr($this->firstseen, 0, 10).'\', '.$queryparts['insert_values'].') ON CONFLICT (uid, date) DO UPDATE SET '.$queryparts['update_assignments']);

			if (!empty($this->smileys)) {
				foreach ($this->smileys as $sid => $total) {
					$insert_values[] = '('.$uid.', '.$sid.', '.$total.')';
				}

				db::query_exec('INSERT INTO uid_smileys (uid, sid, total) VALUES '.implode(', ', $insert_values).' ON CONFLICT (uid, sid) DO UPDATE SET total = total + excluded.total');
			}
		}

		/**
		 * Store data in database tables "uid_lines".
		 */
		$columns = ['monologues', 'slaps', 'slapped', 'actions', 'ex_actions'];

		if ($this->l_total !== 0) {
			$columns = array_merge($columns, ['l_00', 'l_01', 'l_02', 'l_03', 'l_04', 'l_05', 'l_06', 'l_07', 'l_08', 'l_09', 'l_10', 'l_11', 'l_12', 'l_13', 'l_14', 'l_15', 'l_16', 'l_17', 'l_18', 'l_19', 'l_20', 'l_21', 'l_22', 'l_23', 'l_night', 'l_morning', 'l_afternoon', 'l_evening', 'l_total', 'l_mon_night', 'l_mon_morning', 'l_mon_afternoon', 'l_mon_evening', 'l_tue_night', 'l_tue_morning', 'l_tue_afternoon', 'l_tue_evening', 'l_wed_night', 'l_wed_morning', 'l_wed_afternoon', 'l_wed_evening', 'l_thu_night', 'l_thu_morning', 'l_thu_afternoon', 'l_thu_evening', 'l_fri_night', 'l_fri_morning', 'l_fri_afternoon', 'l_fri_evening', 'l_sat_night', 'l_sat_morning', 'l_sat_afternoon', 'l_sat_evening', 'l_sun_night', 'l_sun_morning', 'l_sun_afternoon', 'l_sun_evening', 'urls', 'words', 'characters', 'exclamations', 'questions', 'uppercased']);

			foreach (['ex_uppercased', 'ex_exclamations', 'ex_questions', 'quote'] as $var) {
				if ($this->$var !== '' && (substr_count($this->$var, ' ') >= 2 || db::query_single_col('SELECT '.$var.' FROM uid_lines WHERE uid = '.$uid) === '')) {
					$columns[] = $var;
				}
			}
		}

		if (!is_null($queryparts = $this->get_queryparts($columns))) {
			db::query_exec('INSERT INTO uid_lines (uid, '.$queryparts['insert_columns'].($this->lasttalked !== '' ? ', lasttalked' : '').') VALUES ('.$uid.', '.$queryparts['insert_values'].($this->lasttalked !== '' ? ', DATETIME(\''.$this->lasttalked.'\')' : '').') ON CONFLICT (uid) DO UPDATE SET '.$queryparts['update_assignments'].($this->lasttalked !== '' ? ', lasttalked = DATETIME(\''.$this->lasttalked.'\')' : ''));

			if ($this->topmonologue !== 0 && $this->topmonologue > db::query_single_col('SELECT topmonologue FROM uid_lines WHERE uid = '.$uid)) {
				db::query_exec('UPDATE uid_lines SET topmonologue = '.$this->topmonologue.' WHERE uid = '.$uid);
			}
		}
	}
}
