<?php

/**
 * Copyright (c) 2007-2009, Jos de Ruijter <jos@dutnie.nl>
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
 * Class for handling user data.
 */
final class Nick
{
	/**
	 * Variables used in database table "user_details".
	 */
	private $csNick = '';
	private $firstSeen = '';
	private $lastSeen = '';

	/**
	 * Variables used in database table "user_hosts".
	 */
	private $hosts_list = array();

	/**
	 * Variables used in database table "user_topics".
	 */
	private $topics_list = array();

	/**
	 * Variables used in database table "user_URLs".
	 */
	private $URLs_list = array();
	private $URLs_objs = array();

	/**
	 * Variables used in database table "user_events".
	 */
	private $m_op = 0;
	private $m_opped = 0;
	private $m_voice = 0;
	private $m_voiced = 0;
	private $m_deOp = 0;
	private $m_deOpped = 0;
	private $m_deVoice = 0;
	private $m_deVoiced = 0;
	private $joins = 0;
	private $parts = 0;
	private $quits = 0;
	private $kicks = 0;
	private $kicked = 0;
	private $nickchanges = 0;
	private $topics = 0;
	private $ex_kicks = '';
	private $ex_kicked = '';

	/**
	 * Variables used in database table "user_lines".
	 */
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
	private $l_night = 0;
	private $l_morning = 0;
	private $l_afternoon = 0;
	private $l_evening = 0;
	private $l_total = 0;
	private $l_mon_night = 0;
	private $l_mon_morning = 0;
	private $l_mon_afternoon = 0;
	private $l_mon_evening = 0;
	private $l_tue_night = 0;
	private $l_tue_morning = 0;
	private $l_tue_afternoon = 0;
	private $l_tue_evening = 0;
	private $l_wed_night = 0;
	private $l_wed_morning = 0;
	private $l_wed_afternoon = 0;
	private $l_wed_evening = 0;
	private $l_thu_night = 0;
	private $l_thu_morning = 0;
	private $l_thu_afternoon = 0;
	private $l_thu_evening = 0;
	private $l_fri_night = 0;
	private $l_fri_morning = 0;
	private $l_fri_afternoon = 0;
	private $l_fri_evening = 0;
	private $l_sat_night = 0;
	private $l_sat_morning = 0;
	private $l_sat_afternoon = 0;
	private $l_sat_evening = 0;
	private $l_sun_night = 0;
	private $l_sun_morning = 0;
	private $l_sun_afternoon = 0;
	private $l_sun_evening = 0;
	private $URLs = 0;
	private $words = 0;
	private $characters = 0;
	private $monologues = 0;
	private $topMonologue = 0;
	private $activeDays = 0;
	private $slaps = 0;
	private $slapped = 0;
	private $exclamations = 0;
	private $questions = 0;
	private $actions = 0;
	private $uppercased = 0;
	private $quote = '';
	private $ex_exclamations = '';
	private $ex_questions = '';
	private $ex_actions = '';
	private $ex_uppercased = '';
	private $lastTalked = '';

	/**
	 * Variables used in database table "user_smileys".
	 */
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

	/**
	 * Other variables that shouldn't be tampered with.
	 */
	private $UID = 0;
	private $date = '';
	private $long_ex_actions_list = array();
	private $long_ex_exclamations_list = array();
	private $long_ex_questions_list = array();
	private $long_ex_uppercased_list = array();
	private $long_quote_list = array();
	private $short_ex_actions_list = array();
	private $short_ex_exclamations_list = array();
	private $short_ex_questions_list = array();
	private $short_ex_uppercased_list = array();
	private $short_quote_list = array();
	private $user_details = array('UID', 'csNick', 'firstSeen', 'lastSeen');
	private $user_events = array('UID', 'm_op', 'm_opped', 'm_voice', 'm_voiced', 'm_deOp', 'm_deOpped', 'm_deVoice', 'm_deVoiced', 'joins', 'parts', 'quits', 'kicks', 'kicked', 'nickchanges', 'topics', 'ex_kicks', 'ex_kicked');
	private $user_lines = array('UID', 'l_00', 'l_01', 'l_02', 'l_03', 'l_04', 'l_05', 'l_06', 'l_07', 'l_08', 'l_09', 'l_10', 'l_11', 'l_12', 'l_13', 'l_14', 'l_15', 'l_16', 'l_17', 'l_18', 'l_19', 'l_20', 'l_21', 'l_22', 'l_23', 'l_night', 'l_morning', 'l_afternoon', 'l_evening', 'l_total', 'l_mon_night', 'l_mon_morning', 'l_mon_afternoon', 'l_mon_evening', 'l_tue_night', 'l_tue_morning', 'l_tue_afternoon', 'l_tue_evening', 'l_wed_night', 'l_wed_morning', 'l_wed_afternoon', 'l_wed_evening', 'l_thu_night', 'l_thu_morning', 'l_thu_afternoon', 'l_thu_evening', 'l_fri_night', 'l_fri_morning', 'l_fri_afternoon', 'l_fri_evening', 'l_sat_night', 'l_sat_morning', 'l_sat_afternoon', 'l_sat_evening', 'l_sun_night', 'l_sun_morning', 'l_sun_afternoon', 'l_sun_evening', 'URLs', 'words', 'characters', 'monologues', 'topMonologue', 'activeDays', 'slaps', 'slapped', 'exclamations', 'questions', 'actions', 'uppercased', 'quote', 'ex_exclamations', 'ex_questions', 'ex_actions', 'ex_uppercased', 'lastTalked');
	private $user_smileys = array('UID', 's_01', 's_02', 's_03', 's_04', 's_05', 's_06', 's_07', 's_08', 's_09', 's_10', 's_11', 's_12', 's_13', 's_14', 's_15', 's_16', 's_17', 's_18', 's_19');
	private $user_tables = array('user_details', 'user_events', 'user_lines', 'user_smileys');

        /**
         * Constructor.
         */
	public function __construct($csNick)
	{
		$this->csNick = $csNick;
	}

        /**
         * Keep a list of hosts this user has been seen using.
         */
	public function addHost($csHost)
	{
		$host = strtolower($csHost);

		if (!in_array($host, $this->hosts_list)) {
			$this->hosts_list[] = $host;
		}
	}

        /**
         * Keep two lists of the various types of quotes for the user; one with short quotes and one with long quotes.
         */
	public function addQuote($type, $length, $line)
	{
		$this->{$length.'_'.$type.'_list'}[] = $line;
	}

        /**
         * Keep a list of topics set by the user.
         */
	public function addTopic($csTopic, $dateTime)
	{
		$this->topics_list[] = array('csTopic' => $csTopic
					    ,'setDate' => $dateTime);
	}

        /**
         * Keep a list of URLs pasted by the user. The case of the last mentioned version of the URL will be stored.
         */
	public function addURL($csURL, $dateTime)
	{
		$URL = strtolower($csURL);

		if (!in_array($URL, $this->URLs_list)) {
			$this->URLs_list[] = $URL;
			$this->URLs_objs[$URL] = new URL($csURL);
		} else {
			$this->URLs_objs[$URL]->setValue('csURL', $csURL);
		}

		$this->URLs_objs[$URL]->addValue('total', 1);
		$this->URLs_objs[$URL]->lastUsed($dateTime);
	}

        /**
         * Add a value to a variable.
         */
	public function addValue($var, $value)
	{
		$this->$var += $value;
	}

        /**
         * Get the value of a variable.
         */
	public function getValue($var)
	{
		return $this->$var;
	}

        /**
         * Store the date and time the user was first and last seen.
         */
	public function lastSeen($dateTime)
	{
		if ($this->firstSeen == '' || $dateTime < $this->firstSeen) {
			$this->firstSeen = $dateTime;
		}

		if ($this->lastSeen == '' || $dateTime > $this->lastSeen) {
			$this->lastSeen = $dateTime;
		}
	}

        /**
         * Store the date and time the user last typed a "normal" line.
         */
	public function lastTalked($dateTime)
	{
		if ($this->lastTalked == '' || $dateTime > $this->lastTalked) {
			$this->lastTalked = $dateTime;
		}
	}

	/**
	 * Pick a random line from either the list of long quotes or, when there are no long quotes, from the list of short quotes.
	 * Long quotes are preferred since these look better on the statspage and give away more about the subject.
	 */
	public function randomizeQuotes()
	{
		$types = array('ex_actions', 'ex_exclamations', 'ex_questions', 'ex_uppercased', 'quote');

		foreach ($types as $type) {
			if (!empty($this->{'long_'.$type.'_list'})) {
				$this->$type = $this->{'long_'.$type.'_list'}[mt_rand(0, count($this->{'long_'.$type.'_list'}) - 1)];
			} elseif (!empty($this->{'short_'.$type.'_list'})) {
				$this->$type = $this->{'short_'.$type.'_list'}[mt_rand(0, count($this->{'short_'.$type.'_list'}) - 1)];
			}
		}
	}

        /**
         * Set the value of a variable.
         */
	public function setValue($var, $value)
	{
		$this->$var = $value;
	}

	/**
	 * Write user data to the database.
	 */
	public function writeData($mysqli)
	{
		/**
		 * Write data to database tables "user_details", "user_status", "user_events", "user_lines" and "user_smileys".
		 */
		foreach ($this->user_tables as $table) {
			if ($table == 'user_details') {
				if (($query = @mysqli_query($mysqli, 'SELECT * FROM `'.$table.'` WHERE `csNick` = \''.mysqli_real_escape_string($mysqli, $this->csNick).'\' LIMIT 1')) === FALSE) {
					return FALSE;
				}
			} else {
				if (($query = @mysqli_query($mysqli, 'SELECT * FROM `'.$table.'` WHERE `UID` = '.$this->UID.' LIMIT 1')) === FALSE) {
					return FALSE;
				}
			}

			$rows = mysqli_num_rows($query);

			/**
			 * Don't send anything to the database if user data is empty or hasn't changed.
			 */
			$submit = FALSE;

			if (empty($rows)) {
				$query = 'INSERT INTO `'.$table.'` SET';

				foreach ($this->{$table} as $key) {
					if ($key == 'UID' && $this->UID != 0) {
						$query .= ' `UID` = '.$this->UID.',';
					} elseif (is_int($this->$key) && $this->$key != 0) {
						$query .= ' `'.$key.'` = '.$this->$key.',';
						$submit = TRUE;
					} elseif (is_string($this->$key) && $this->$key != '') {
						$query .= ' `'.$key.'` = \''.mysqli_real_escape_string($mysqli, $this->$key).'\',';
						$submit = TRUE;
					}
				}

				$query = rtrim($query, ',');

				if ($submit) {
					if (!@mysqli_query($mysqli, $query)) {
						return FALSE;
					}

					if ($table == 'user_details') {
						$this->UID = mysqli_insert_id($mysqli);

						if (!@mysqli_query($mysqli, 'INSERT INTO `user_status` (`UID`, `RUID`, `status`) VALUES ('.$this->UID.', '.$this->UID.', 0)')) {
							return FALSE;
						}
					}
				}
			} else {
				$result = mysqli_fetch_object($query);

				if ($table == 'user_details') {
					$this->UID = $result->UID;
				}

				$query = 'UPDATE `'.$table.'` SET';

				foreach ($this->{$table} as $key) {
					if (is_int($this->$key) && $this->$key != 0 && $key != 'UID') {
						if ($key == 'topMonologue') {
							if ($this->topMonologue > $result->topMonologue) {
								$query .= ' `topMonologue` = '.$this->topMonologue.',';
							}
						} else {
							$query .= ' `'.$key.'` = '.($this->$key + $result->$key).',';
						}

						$submit = TRUE;
					} elseif (is_string($this->$key) && $this->$key != '' && $this->$key != $result->$key && !($key == 'firstSeen' && $this->$key.':00' >= $result->$key) && !(($key == 'lastSeen' || $key == 'lastTalked') && $this->$key.':00' <= $result->$key)) {
						$query .= ' `'.$key.'` = \''.mysqli_real_escape_string($mysqli, $this->$key).'\',';
						$submit = TRUE;
					}
				}

				$query = rtrim($query, ',').' WHERE `UID` = '.$this->UID;

				if ($submit) {
					if (!@mysqli_query($mysqli, $query)) {
						return FALSE;
					}
				}
			}
		}

		/**
		 * Write data to database table "user_activity".
		 */
		if ($this->l_total != 0) {
			if (!@mysqli_query($mysqli, 'INSERT INTO `user_activity` (`UID`, `date`, `l_night`, `l_morning`, `l_afternoon`, `l_evening`, `l_total`) VALUES ('.$this->UID.', \''.mysqli_real_escape_string($mysqli, $this->date).'\', '.$this->l_night.', '.$this->l_morning.', '.$this->l_afternoon.', '.$this->l_evening.', '.$this->l_total.')')) {
				return FALSE;
			}
		}

		/**
		 * Write data to database table "user_hosts".
		 */
		if (!empty($this->hosts_list)) {
			foreach ($this->hosts_list as $host) {
				if (($query = @mysqli_query($mysqli, 'SELECT * FROM `user_hosts` WHERE `UID` = '.$this->UID.' AND `host` = \''.mysqli_real_escape_string($mysqli, $host).'\' LIMIT 1')) === FALSE) {
					return FALSE;
				}

				$rows = mysqli_num_rows($query);

				/**
				 * Only add hosts for this user which aren't already in the database.
				 */
				if (empty($rows)) {
					/**
					 * Check if the host exists in the database paired with an UID other than mine and if it does, use its HID in my own insert query.
					 */
					if (($query = @mysqli_query($mysqli, 'SELECT * FROM `user_hosts` WHERE `host` = \''.mysqli_real_escape_string($mysqli, $host).'\' LIMIT 1')) === FALSE) {
						return FALSE;
					}

					$rows = mysqli_num_rows($query);

					if (empty($rows)) {
						if (!@mysqli_query($mysqli, 'INSERT INTO `user_hosts` (`UID`, `host`) VALUES ('.$this->UID.', \''.mysqli_real_escape_string($mysqli, $host).'\')')) {
							return FALSE;
						}
					} else {
						$result = mysqli_fetch_object($query);

						if (!@mysqli_query($mysqli, 'INSERT INTO `user_hosts` (`HID`, `UID`, `host`) VALUES ('.$result->HID.', '.$this->UID.', \''.mysqli_real_escape_string($mysqli, $host).'\')')) {
							return FALSE;
						}
					}
				}
			}
		}

		/**
		 * Write data to database table "user_topics".
		 */
		if (!empty($this->topics_list)) {
			foreach ($this->topics_list as $topic) {
				if (($query = @mysqli_query($mysqli, 'SELECT * FROM `user_topics` WHERE `UID` = '.$this->UID.' AND `csTopic` = \''.mysqli_real_escape_string($mysqli, $topic['csTopic']).'\' AND `setDate` = \''.mysqli_real_escape_string($mysqli, $topic['setDate']).'\' LIMIT 1')) === FALSE) {
					return FALSE;
				}

				$rows = mysqli_num_rows($query);

				/**
				 * Don't insert a topic already set by this user at the same time.
				 * The combination of TID/UID/setDate is unique in the database where TID is the identifier of the topic.
				 */
				if (empty($rows)) {
					/**
					 * Check if the topic exists in the database and if it does, use its TID in the insert query.
					 */
					if (($query = @mysqli_query($mysqli, 'SELECT * FROM `user_topics` WHERE `csTopic` = \''.mysqli_real_escape_string($mysqli, $topic['csTopic']).'\' LIMIT 1')) === FALSE) {
						return FALSE;
					}

					$rows = mysqli_num_rows($query);

					if (empty($rows)) {
						if (!@mysqli_query($mysqli, 'INSERT INTO `user_topics` (`UID`, `csTopic`, `setDate`) VALUES ('.$this->UID.', \''.mysqli_real_escape_string($mysqli, $topic['csTopic']).'\', \''.mysqli_real_escape_string($mysqli, $topic['setDate']).'\')')) {
							return FALSE;
						}
					} else {
						$result = mysqli_fetch_object($query);

						if (!@mysqli_query($mysqli, 'INSERT INTO `user_topics` (`TID`, `UID`, `csTopic`, `setDate`) VALUES ('.$result->TID.', '.$this->UID.', \''.mysqli_real_escape_string($mysqli, $topic['csTopic']).'\', \''.mysqli_real_escape_string($mysqli, $topic['setDate']).'\')')) {
							return FALSE;
						}
					}
				}
			}
		}

		/**
		 * Write data to database table "user_URLs".
		 */
		foreach ($this->URLs_list as $URL) {
			if (!$this->URLs_objs[$URL]->writeData($mysqli, $this->UID)) {
				return FALSE;
			}
		}

		return TRUE;
	}
}

?>
