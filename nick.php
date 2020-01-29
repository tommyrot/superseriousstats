<?php

/**
 * Copyright (c) 2007-2020, Jos de Ruijter <jos@dutnie.nl>
 */

declare(strict_types=1);

/**
 * Class for handling user data.
 */
class nick
{
	use base, queryparts;

	private array $ex_actions_stack = [];
	private array $ex_exclamations_stack = [];
	private array $ex_questions_stack = [];
	private array $ex_uppercased_stack = [];
	private array $quote_stack = [];
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
	private int $s_01 = 0;
	private int $s_02 = 0;
	private int $s_03 = 0;
	private int $s_04 = 0;
	private int $s_05 = 0;
	private int $s_06 = 0;
	private int $s_07 = 0;
	private int $s_08 = 0;
	private int $s_09 = 0;
	private int $s_10 = 0;
	private int $s_11 = 0;
	private int $s_12 = 0;
	private int $s_13 = 0;
	private int $s_14 = 0;
	private int $s_15 = 0;
	private int $s_16 = 0;
	private int $s_17 = 0;
	private int $s_18 = 0;
	private int $s_19 = 0;
	private int $s_20 = 0;
	private int $s_21 = 0;
	private int $s_22 = 0;
	private int $s_23 = 0;
	private int $s_24 = 0;
	private int $s_25 = 0;
	private int $s_26 = 0;
	private int $s_27 = 0;
	private int $s_28 = 0;
	private int $s_29 = 0;
	private int $s_30 = 0;
	private int $s_31 = 0;
	private int $s_32 = 0;
	private int $s_33 = 0;
	private int $s_34 = 0;
	private int $s_35 = 0;
	private int $s_36 = 0;
	private int $s_37 = 0;
	private int $s_38 = 0;
	private int $s_39 = 0;
	private int $s_40 = 0;
	private int $s_41 = 0;
	private int $s_42 = 0;
	private int $s_43 = 0;
	private int $s_44 = 0;
	private int $s_45 = 0;
	private int $s_46 = 0;
	private int $s_47 = 0;
	private int $s_48 = 0;
	private int $s_49 = 0;
	private int $s_50 = 0;
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

	/**
	 * Keep a stack of the 10 most recent quotes of each type along with their
	 * length.
	 */
	public function add_quote(string $type, string $line, int $line_length): void
	{
		/**
		 * $line_length should be the first value in each array since we sort on this
		 * column with rsort() later.
		 */
		$this->{$type.'_stack'}[] = [
			'length' => $line_length,
			'line' => $line];

		if (count($this->{$type.'_stack'}) > 10) {
			/**
			 * Shift the first (oldest) entry off the stack.
			 */
			array_shift($this->{$type.'_stack'});
		}
	}

	public function write_data(object $sqlite3): void
	{
		/**
		 * Check if $csnick already exists in the database.
		 */
		if (($uid = $sqlite3->querySingle('SELECT uid FROM uid_details WHERE csnick = \''.$this->csnick.'\'')) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		/**
		 * $csnick does NOT already exist in the database.
		 */
		if (is_null($uid)) {
			if ($this->firstseen === '') {
				/**
				 * If $firstseen is empty there have been no lines, actions or events for this nick.
				 * The only two possibilities left are; this nick has been slapped, or this nick has its streak interrupted.
				 * Since this nick isn't already in the database that rules out a streak.
				 * We won't add the nick to the database just because of a slap to avoid abuse.
				 */
				output::output('debug', __METHOD__.'(): skipping nick: \''.$this->csnick.'\'');
				return;
			} else {
				/**
				 * Write data to database table "uid_details".
				 */
				$sqlite3->exec('INSERT INTO uid_details (uid, csnick, firstseen, lastseen) VALUES (NULL, \''.$this->csnick.'\', DATETIME(\''.$this->firstseen.'\'), DATETIME(\''.$this->lastseen.'\'))') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
				$uid = $sqlite3->lastInsertRowID();
			}

		/**
		 * $csnick DOES already exist in the database.
		 */
		} else {
			if ($this->firstseen === '') {
				/**
				 * If $firstseen is empty there have been no lines, actions or events for this nick.
				 * The only two possibilities left are; this nick has been slapped, or this nick has its streak interrupted.
				 * Update these values if applicable.
				 */
				$queryparts = $this->get_queryparts($sqlite3, ['monologues', 'actions']);

				if ($this->topmonologue !== 0) {
					if (($topmonologue = $sqlite3->querySingle('SELECT topmonologue FROM uid_lines WHERE uid = '.$uid)) === false) {
						output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
					}

					if ($this->topmonologue > $topmonologue) {
						$queryparts['update_assignments'] .= ', topmonologue = '.$this->topmonologue;
					}
				}

				$sqlite3->exec('UPDATE uid_lines SET '.$queryparts['update_assignments'].' WHERE uid = '.$uid) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
				return;
			}
		}

		/**
		 * Write data to database table "uid_events".
		 */
		if (!is_null($queryparts = $this->get_queryparts($sqlite3, ['m_op', 'm_opped', 'm_voice', 'm_voiced', 'm_deop', 'm_deopped', 'm_devoice', 'm_devoiced', 'joins', 'parts', 'quits', 'kicks', 'kicked', 'nickchanges', 'topics', 'ex_kicks', 'ex_kicked']))) {
			$sqlite3->exec('INSERT INTO uid_events (uid, '.$queryparts['insert_columns'].') VALUES ('.$uid.', '.$queryparts['insert_values'].') ON CONFLICT (uid) DO UPDATE SET '.$queryparts['update_assignments']) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		/**
		 * Write data to database table "uid_activity".
		 */
		if ($this->l_total !== 0) {
			$queryparts = $this->get_queryparts($sqlite3, ['l_night', 'l_morning', 'l_afternoon', 'l_evening', 'l_total']);
			$sqlite3->exec('INSERT INTO uid_activity (uid, date, '.$queryparts['insert_columns'].') VALUES ('.$uid.', \''.substr($this->firstseen, 0, 10).'\', '.$queryparts['insert_values'].') ON CONFLICT (uid, date) DO UPDATE SET '.$queryparts['update_assignments']) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

			/**
			 * Write data to database table "uid_smileys".
			 */
			if (!is_null($queryparts = $this->get_queryparts($sqlite3, ['s_01', 's_02', 's_03', 's_04', 's_05', 's_06', 's_07', 's_08', 's_09', 's_10', 's_11', 's_12', 's_13', 's_14', 's_15', 's_16', 's_17', 's_18', 's_19', 's_20', 's_21', 's_22', 's_23', 's_24', 's_25', 's_26', 's_27', 's_28', 's_29', 's_30', 's_31', 's_32', 's_33', 's_34', 's_35', 's_36', 's_37', 's_38', 's_39', 's_40', 's_41', 's_42', 's_43', 's_44', 's_45', 's_46', 's_47', 's_48', 's_49', 's_50']))) {
				$sqlite3->exec('INSERT INTO uid_smileys (uid, '.$queryparts['insert_columns'].') VALUES ('.$uid.', '.$queryparts['insert_values'].') ON CONFLICT (uid) DO UPDATE SET '.$queryparts['update_assignments']) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

		}

		if ($this->l_total !== 0 || $this->actions !== 0) {
			/**
			 * Try to pick the longest unique line from each of the quote stacks.
			 */
			$columns = ['ex_actions', 'ex_uppercased', 'ex_exclamations', 'ex_questions', 'quote'];

			foreach ($columns as $var) {
				if (empty($this->{$var.'_stack'})) {
					continue;
				}

				/**
				* rsort() sorts a multidimensional array on the first value of each contained
				* array, highest to lowest.
				*/
				rsort($this->{$var.'_stack'});
				$this->$var = $this->{$var.'_stack'}[0]['line'];

				/**
				* Try to move away from duplicate quotes. The order of the if/else statement
				* aims to cover most possible cases.
				*/
				if ($var === 'ex_uppercased' || $var === 'ex_actions' || count($this->{$var.'_stack'}) === 1) {
					continue;
				}

				if ($var === 'ex_questions' || $var === 'ex_exclamations') {
					if ($this->$var === $this->ex_uppercased) {
						for ($i = 1, $j = count($this->{$var.'_stack'}); $i < $j; ++$i) {
							if ($this->{$var.'_stack'}[$i]['line'] !== $this->ex_uppercased) {
								$this->$var = $this->{$var.'_stack'}[$i]['line'];
								break;
							}
						}
					}
				} elseif ($var === 'quote') {
					if ($this->quote === $this->ex_uppercased || $this->quote === $this->ex_exclamations || $this->quote === $this->ex_questions) {
						for ($i = 1, $j = count($this->quote_stack); $i < $j; ++$i) {
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
			if (!is_null($queryparts = $this->get_queryparts($sqlite3, ['l_00', 'l_01', 'l_02', 'l_03', 'l_04', 'l_05', 'l_06', 'l_07', 'l_08', 'l_09', 'l_10', 'l_11', 'l_12', 'l_13', 'l_14', 'l_15', 'l_16', 'l_17', 'l_18', 'l_19', 'l_20', 'l_21', 'l_22', 'l_23', 'l_night', 'l_morning', 'l_afternoon', 'l_evening', 'l_total', 'l_mon_night', 'l_mon_morning', 'l_mon_afternoon', 'l_mon_evening', 'l_tue_night', 'l_tue_morning', 'l_tue_afternoon', 'l_tue_evening', 'l_wed_night', 'l_wed_morning', 'l_wed_afternoon', 'l_wed_evening', 'l_thu_night', 'l_thu_morning', 'l_thu_afternoon', 'l_thu_evening', 'l_fri_night', 'l_fri_morning', 'l_fri_afternoon', 'l_fri_evening', 'l_sat_night', 'l_sat_morning', 'l_sat_afternoon', 'l_sat_evening', 'l_sun_night', 'l_sun_morning', 'l_sun_afternoon', 'l_sun_evening', 'urls', 'words', 'characters', 'monologues', 'slaps', 'slapped', 'exclamations', 'questions', 'actions', 'uppercased', 'quote', 'ex_exclamations', 'ex_questions', 'ex_actions', 'ex_uppercased']))) {
				$sqlite3->exec('INSERT INTO uid_lines (uid, '.$queryparts['insert_columns'].($this->lasttalked !== '' ? ', lasttalked' : '').') VALUES ('.$uid.', '.$queryparts['insert_values'].($this->lasttalked !== '' ? ', DATETIME(\''.$this->lasttalked.'\')' : '').') ON CONFLICT (uid) DO UPDATE SET '.$queryparts['update_assignments'].($this->lasttalked !== '' ? ', lasttalked = DATETIME(\''.$this->lasttalked.'\')' : '')) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

				/**
				* Insert (update) $topmonologue separately as we want to keep the highest value
				* instead of the sum.
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
		}
	}
}
