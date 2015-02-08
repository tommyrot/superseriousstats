<?php

/**
 * Copyright (c) 2007-2015, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Class for handling user data.
 */
class nick
{
	use base;
	private $actions = 0;
	private $characters = 0;
	private $csnick = '';
	private $date = '';
	private $ex_actions = '';
	private $ex_actions_stack = [];
	private $ex_exclamations = '';
	private $ex_exclamations_stack = [];
	private $ex_kicked = '';
	private $ex_kicks = '';
	private $ex_questions = '';
	private $ex_questions_stack = [];
	private $ex_uppercased = '';
	private $ex_uppercased_stack = [];
	private $exclamations = 0;
	private $firstseen = '';
	private $joins = 0;
	private $kicked = 0;
	private $kicks = 0;
	private $l_00 = 0;
	private $l_01 = 0;
	private $l_02 = 0;
	private $l_03 = 0;
	private $l_04 = 0;
	private $l_05 = 0;
	private $l_06 = 0;
	private $l_07 = 0;
	private $l_08 = 0;
	private $l_09 = 0;
	private $l_10 = 0;
	private $l_11 = 0;
	private $l_12 = 0;
	private $l_13 = 0;
	private $l_14 = 0;
	private $l_15 = 0;
	private $l_16 = 0;
	private $l_17 = 0;
	private $l_18 = 0;
	private $l_19 = 0;
	private $l_20 = 0;
	private $l_21 = 0;
	private $l_22 = 0;
	private $l_23 = 0;
	private $l_afternoon = 0;
	private $l_evening = 0;
	private $l_fri_afternoon = 0;
	private $l_fri_evening = 0;
	private $l_fri_morning = 0;
	private $l_fri_night = 0;
	private $l_mon_afternoon = 0;
	private $l_mon_evening = 0;
	private $l_mon_morning = 0;
	private $l_mon_night = 0;
	private $l_morning = 0;
	private $l_night = 0;
	private $l_sat_afternoon = 0;
	private $l_sat_evening = 0;
	private $l_sat_morning = 0;
	private $l_sat_night = 0;
	private $l_sun_afternoon = 0;
	private $l_sun_evening = 0;
	private $l_sun_morning = 0;
	private $l_sun_night = 0;
	private $l_thu_afternoon = 0;
	private $l_thu_evening = 0;
	private $l_thu_morning = 0;
	private $l_thu_night = 0;
	private $l_total = 0;
	private $l_tue_afternoon = 0;
	private $l_tue_evening = 0;
	private $l_tue_morning = 0;
	private $l_tue_night = 0;
	private $l_wed_afternoon = 0;
	private $l_wed_evening = 0;
	private $l_wed_morning = 0;
	private $l_wed_night = 0;
	private $lastseen = '';
	private $lasttalked = '';
	private $m_deop = 0;
	private $m_deopped = 0;
	private $m_devoice = 0;
	private $m_devoiced = 0;
	private $m_op = 0;
	private $m_opped = 0;
	private $m_voice = 0;
	private $m_voiced = 0;
	private $monologues = 0;
	private $nickchanges = 0;
	private $parts = 0;
	private $questions = 0;
	private $quits = 0;
	private $quote = '';
	private $quote_stack = [];
	private $s_01 = 0;
	private $s_02 = 0;
	private $s_03 = 0;
	private $s_04 = 0;
	private $s_05 = 0;
	private $s_06 = 0;
	private $s_07 = 0;
	private $s_08 = 0;
	private $s_09 = 0;
	private $s_10 = 0;
	private $s_11 = 0;
	private $s_12 = 0;
	private $s_13 = 0;
	private $s_14 = 0;
	private $s_15 = 0;
	private $s_16 = 0;
	private $s_17 = 0;
	private $s_18 = 0;
	private $s_19 = 0;
	private $s_20 = 0;
	private $s_21 = 0;
	private $s_22 = 0;
	private $s_23 = 0;
	private $s_24 = 0;
	private $s_25 = 0;
	private $s_26 = 0;
	private $s_27 = 0;
	private $s_28 = 0;
	private $s_29 = 0;
	private $s_30 = 0;
	private $s_31 = 0;
	private $s_32 = 0;
	private $s_33 = 0;
	private $s_34 = 0;
	private $s_35 = 0;
	private $s_36 = 0;
	private $s_37 = 0;
	private $s_38 = 0;
	private $s_39 = 0;
	private $s_40 = 0;
	private $s_41 = 0;
	private $s_42 = 0;
	private $s_43 = 0;
	private $s_44 = 0;
	private $s_45 = 0;
	private $s_46 = 0;
	private $s_47 = 0;
	private $s_48 = 0;
	private $s_49 = 0;
	private $s_50 = 0;
	private $slapped = 0;
	private $slaps = 0;
	private $topics = 0;
	private $topmonologue = 0;
	private $uppercased = 0;
	private $urls = 0;
	private $words = 0;

	public function __construct($csnick)
	{
		$this->csnick = $csnick;
	}

	/**
	 * Keep a stack of the 10 most recent quotes of each type along with their length.
	 */
	public function add_quote($type, $line, $length)
	{
		$this->{$type.'_stack'}[] = [
			'length' => $length,
			'line' => $line];

		if (count($this->{$type.'_stack'}) > 10) {
			/**
			 * Shift the first (oldest) entry off the stack.
			 */
			array_shift($this->{$type.'_stack'});
		}
	}

	/**
	 * Create parts of the SQLite3 query.
	 */
	private function get_queryparts($sqlite3, $columns)
	{
		$queryparts = [];

		foreach ($columns as $key) {
			if (is_int($this->$key) && $this->$key !== 0) {
				$queryparts['columnlist'][] = $key;
				$queryparts['update-assignments'][] = $key.' = '.$key.' + '.$this->$key;
				$queryparts['values'][] = $this->$key;
			} elseif (is_string($this->$key) && $this->$key !== '') {
				$value = '\''.$sqlite3->escapeString($this->$key).'\'';
				$queryparts['columnlist'][] = $key;
				$queryparts['update-assignments'][] = $key.' = '.$value;
				$queryparts['values'][] = $value;
			}
		}

		return $queryparts;
	}

	public function write_data($sqlite3)
	{
		/**
		 * Write data to database table "uid_details".
		 */
		if (($result = $sqlite3->querySingle('SELECT uid, firstseen FROM uid_details WHERE csnick = \''.$sqlite3->escapeString($this->csnick).'\'', true)) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		if (empty($result)) {
			$sqlite3->exec('INSERT INTO uid_details (uid, csnick'.($this->firstseen !== '' ? ', firstseen, lastseen' : '').') VALUES (NULL, \''.$sqlite3->escapeString($this->csnick).'\''.($this->firstseen !== '' ? ', DATETIME(\''.$this->firstseen.'\'), DATETIME(\''.$this->lastseen.'\')' : '').')') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$uid = $sqlite3->lastInsertRowID();
		} else {
			$uid = $result['uid'];

			/**
			 * Only update $firstseen if the value stored in the database is zero. We're parsing logs in
			 * chronological order so the stored value of $firstseen can never be lower and the value of
			 * $lastseen can never be higher than the parsed values. (We are not going out of our way to
			 * deal with possible DST nonsense.) Secondly, only update $csnick if the nick was seen. We want
			 * to avoid it from being overwritten by a lowercase $prevnick (streak code) or weirdly cased
			 * nick due to a slap.
			 */
			if ($this->firstseen !== '') {
				$sqlite3->exec('UPDATE uid_details SET csnick = \''.$sqlite3->escapeString($this->csnick).'\''.($result['firstseen'] === '0000-00-00 00:00:00' ? ', firstseen = DATETIME(\''.$this->firstseen.'\')' : '').', lastseen = DATETIME(\''.$this->lastseen.'\') WHERE uid = '.$uid) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		}

		/**
		 * Write data to database table "uid_activity".
		 */
		if ($this->l_total !== 0) {
			$queryparts = $this->get_queryparts($sqlite3, ['l_night', 'l_morning', 'l_afternoon', 'l_evening', 'l_total']);
			$sqlite3->exec('INSERT OR IGNORE INTO uid_activity (uid, date, '.implode(', ', $queryparts['columnlist']).') VALUES ('.$uid.', \''.$this->date.'\', '.implode(', ', $queryparts['values']).')') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$sqlite3->exec('UPDATE uid_activity SET '.implode(', ', $queryparts['update-assignments']).' WHERE CHANGES() = 0 AND uid = '.$uid.' AND date = \''.$this->date.'\'') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		/**
		 * Write data to database table "uid_events".
		 */
		$queryparts = $this->get_queryparts($sqlite3, ['m_op', 'm_opped', 'm_voice', 'm_voiced', 'm_deop', 'm_deopped', 'm_devoice', 'm_devoiced', 'joins', 'parts', 'quits', 'kicks', 'kicked', 'nickchanges', 'topics', 'ex_kicks', 'ex_kicked']);

		if (!empty($queryparts)) {
			$sqlite3->exec('INSERT OR IGNORE INTO uid_events (uid, '.implode(', ', $queryparts['columnlist']).') VALUES ('.$uid.', '.implode(', ', $queryparts['values']).')') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$sqlite3->exec('UPDATE uid_events SET '.implode(', ', $queryparts['update-assignments']).' WHERE CHANGES() = 0 AND uid = '.$uid) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		/**
		 * Try to pick the longest unique line from each of the quote stacks.
		 */
		$types = ['ex_actions', 'ex_uppercased', 'ex_exclamations', 'ex_questions', 'quote'];

		foreach ($types as $type) {
			if (!empty($this->{$type.'_stack'})) {
				rsort($this->{$type.'_stack'});
				$this->$type = $this->{$type.'_stack'}[0]['line'];

				if (($type === 'ex_questions' || $type === 'ex_exclamations') && $this->$type === $this->ex_uppercased && count($this->{$type.'_stack'}) > 1) {
					for ($i = 1, $j = count($this->{$type.'_stack'}); $i < $j; $i++) {
						if ($this->{$type.'_stack'}[$i]['line'] !== $this->ex_uppercased) {
							$this->$type = $this->{$type.'_stack'}[$i]['line'];
							break;
						}
					}
				} elseif ($type === 'quote' && ($this->quote === $this->ex_uppercased || $this->quote === $this->ex_exclamations || $this->quote === $this->ex_questions) && count($this->quote_stack) > 1) {
					for ($i = 1, $j = count($this->quote_stack); $i < $j; $i++) {
						if ($this->quote_stack[$i]['line'] !== $this->ex_uppercased && $this->quote_stack[$i]['line'] !== $this->ex_exclamations && $this->quote_stack[$i]['line'] !== $this->ex_questions) {
							$this->quote = $this->quote_stack[$i]['line'];
							break;
						}
					}
				}
			}
		}

		/**
		 * Write data to database table "uid_lines".
		 */
		$queryparts = $this->get_queryparts($sqlite3, ['l_00', 'l_01', 'l_02', 'l_03', 'l_04', 'l_05', 'l_06', 'l_07', 'l_08', 'l_09', 'l_10', 'l_11', 'l_12', 'l_13', 'l_14', 'l_15', 'l_16', 'l_17', 'l_18', 'l_19', 'l_20', 'l_21', 'l_22', 'l_23', 'l_night', 'l_morning', 'l_afternoon', 'l_evening', 'l_total', 'l_mon_night', 'l_mon_morning', 'l_mon_afternoon', 'l_mon_evening', 'l_tue_night', 'l_tue_morning', 'l_tue_afternoon', 'l_tue_evening', 'l_wed_night', 'l_wed_morning', 'l_wed_afternoon', 'l_wed_evening', 'l_thu_night', 'l_thu_morning', 'l_thu_afternoon', 'l_thu_evening', 'l_fri_night', 'l_fri_morning', 'l_fri_afternoon', 'l_fri_evening', 'l_sat_night', 'l_sat_morning', 'l_sat_afternoon', 'l_sat_evening', 'l_sun_night', 'l_sun_morning', 'l_sun_afternoon', 'l_sun_evening', 'urls', 'words', 'characters', 'monologues', 'slaps', 'slapped', 'exclamations', 'questions', 'actions', 'uppercased', 'quote', 'ex_exclamations', 'ex_questions', 'ex_actions', 'ex_uppercased']);

		if (!empty($queryparts)) {
			$sqlite3->exec('INSERT OR IGNORE INTO uid_lines (uid, '.implode(', ', $queryparts['columnlist']).($this->lasttalked !== '' ? ', lasttalked' : '').') VALUES ('.$uid.', '.implode(', ', $queryparts['values']).($this->lasttalked !== '' ? ', DATETIME(\''.$this->lasttalked.'\')' : '').')') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$sqlite3->exec('UPDATE uid_lines SET '.implode(', ', $queryparts['update-assignments']).($this->lasttalked !== '' ? ', lasttalked = DATETIME(\''.$this->lasttalked.'\')' : '').' WHERE CHANGES() = 0 AND uid = '.$uid) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

			/**
			 * Update $topmonologue separately as we want to keep the highest value instead of the sum.
			 */
			if ($this->topmonologue !== 0) {
				if (($topmonologue = $sqlite3->querySingle('SELECT topmonologue FROM uid_lines WHERE uid = '.$uid)) === false) {
					output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
				}

				if ($this->topmonologue > $topmonologue) {
					$sqlite3->exec('UPDATE uid_lines SET topmonologue = '.$this->topmonologue.' WHERE uid = '.$uid) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
				}
			}
		}

		/**
		 * Write data to database table "uid_smileys".
		 */
		$queryparts = $this->get_queryparts($sqlite3, ['s_01', 's_02', 's_03', 's_04', 's_05', 's_06', 's_07', 's_08', 's_09', 's_10', 's_11', 's_12', 's_13', 's_14', 's_15', 's_16', 's_17', 's_18', 's_19', 's_20', 's_21', 's_22', 's_23', 's_24', 's_25', 's_26', 's_27', 's_28', 's_29', 's_30', 's_31', 's_32', 's_33', 's_34', 's_35', 's_36', 's_37', 's_38', 's_39', 's_40', 's_41', 's_42', 's_43', 's_44', 's_45', 's_46', 's_47', 's_48', 's_49', 's_50']);

		if (!empty($queryparts)) {
			$sqlite3->exec('INSERT OR IGNORE INTO uid_smileys (uid, '.implode(', ', $queryparts['columnlist']).') VALUES ('.$uid.', '.implode(', ', $queryparts['values']).')') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$sqlite3->exec('UPDATE uid_smileys SET '.implode(', ', $queryparts['update-assignments']).' WHERE CHANGES() = 0 AND uid = '.$uid) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}
	}
}
