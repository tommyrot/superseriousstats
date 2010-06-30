<?php

/**
 * Copyright (c) 2007-2010, Jos de Ruijter <jos@dutnie.nl>
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
final class Nick extends Base
{
	/**
	 * Variables used in database table "user_details".
	 */
	private $UID = 0;
	protected $csNick = '';
	protected $firstSeen = '';
	protected $lastSeen = '';

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
	private $URLs_objs = array();

	/**
	 * Variables used in database table "user_events".
	 */
	protected $m_op = 0;
	protected $m_opped = 0;
	protected $m_voice = 0;
	protected $m_voiced = 0;
	protected $m_deOp = 0;
	protected $m_deOpped = 0;
	protected $m_deVoice = 0;
	protected $m_deVoiced = 0;
	protected $joins = 0;
	protected $parts = 0;
	protected $quits = 0;
	protected $kicks = 0;
	protected $kicked = 0;
	protected $nickchanges = 0;
	protected $topics = 0;
	protected $ex_kicks = '';
	protected $ex_kicked = '';

	/**
	 * Variables used in database table "user_lines".
	 */
	protected $l_00 = 0;
	protected $l_01 = 0;
	protected $l_02 = 0;
	protected $l_03 = 0;
	protected $l_04 = 0;
	protected $l_05 = 0;
	protected $l_06 = 0;
	protected $l_07 = 0;
	protected $l_08 = 0;
	protected $l_09 = 0;
	protected $l_10 = 0;
	protected $l_11 = 0;
	protected $l_12 = 0;
	protected $l_13 = 0;
	protected $l_14 = 0;
	protected $l_15 = 0;
	protected $l_16 = 0;
	protected $l_17 = 0;
	protected $l_18 = 0;
	protected $l_19 = 0;
	protected $l_20 = 0;
	protected $l_21 = 0;
	protected $l_22 = 0;
	protected $l_23 = 0;
	protected $l_night = 0;
	protected $l_morning = 0;
	protected $l_afternoon = 0;
	protected $l_evening = 0;
	protected $l_total = 0;
	protected $l_mon_night = 0;
	protected $l_mon_morning = 0;
	protected $l_mon_afternoon = 0;
	protected $l_mon_evening = 0;
	protected $l_tue_night = 0;
	protected $l_tue_morning = 0;
	protected $l_tue_afternoon = 0;
	protected $l_tue_evening = 0;
	protected $l_wed_night = 0;
	protected $l_wed_morning = 0;
	protected $l_wed_afternoon = 0;
	protected $l_wed_evening = 0;
	protected $l_thu_night = 0;
	protected $l_thu_morning = 0;
	protected $l_thu_afternoon = 0;
	protected $l_thu_evening = 0;
	protected $l_fri_night = 0;
	protected $l_fri_morning = 0;
	protected $l_fri_afternoon = 0;
	protected $l_fri_evening = 0;
	protected $l_sat_night = 0;
	protected $l_sat_morning = 0;
	protected $l_sat_afternoon = 0;
	protected $l_sat_evening = 0;
	protected $l_sun_night = 0;
	protected $l_sun_morning = 0;
	protected $l_sun_afternoon = 0;
	protected $l_sun_evening = 0;
	protected $URLs = 0;
	protected $words = 0;
	protected $characters = 0;
	protected $monologues = 0;
	protected $topMonologue = 0;
	protected $activeDays = 0;
	protected $slaps = 0;
	protected $slapped = 0;
	protected $exclamations = 0;
	protected $questions = 0;
	protected $actions = 0;
	protected $uppercased = 0;
	protected $quote = '';
	protected $ex_exclamations = '';
	protected $ex_questions = '';
	protected $ex_actions = '';
	protected $ex_uppercased = '';
	protected $lastTalked = '';

	/**
	 * Variables used in database table "user_smileys".
	 */
	protected $s_01 = 0;
	protected $s_02 = 0;
	protected $s_03 = 0;
	protected $s_04 = 0;
	protected $s_05 = 0;
	protected $s_06 = 0;
	protected $s_07 = 0;
	protected $s_08 = 0;
	protected $s_09 = 0;
	protected $s_10 = 0;
	protected $s_11 = 0;
	protected $s_12 = 0;
	protected $s_13 = 0;
	protected $s_14 = 0;
	protected $s_15 = 0;
	protected $s_16 = 0;
	protected $s_17 = 0;
	protected $s_18 = 0;
	protected $s_19 = 0;

	/**
	 * Variables that shouldn't be tampered with.
	 */
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
	protected $date = '';

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
	 * Keep a list of URLs pasted by the user. The last used case sensitivity will be stored for the specific URL.
	 */
	public function addURL($csURL, $dateTime)
	{
		$URL = strtolower($csURL);

		if (!array_key_exists($URL, $this->URLs_objs)) {
			$this->URLs_objs[$URL] = new URL($csURL);
		} else {
			$this->URLs_objs[$URL]->setValue('csURL', $csURL);
		}

		$this->URLs_objs[$URL]->addValue('total', 1);
		$this->URLs_objs[$URL]->lastUsed($dateTime);
	}

	/**
	 * Store the date and time of when the user was first and last seen.
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
	 * Store the date and time of when the user last typed a "normal" line.
	 */
	public function lastTalked($dateTime)
	{
		if ($this->lastTalked == '' || $dateTime > $this->lastTalked) {
			$this->lastTalked = $dateTime;
		}
	}

	/**
	 * Write user data to the database.
	 */
	public function writeData($mysqli)
	{
		/**
		 * Pick a random line from either the list of long quotes or, when there are no long quotes, from the list of short quotes.
		 * Long quotes are preferred since these look better on the statspage and give away more about the subject.
		 */
		$types = array('ex_actions', 'ex_exclamations', 'ex_questions', 'ex_uppercased', 'quote');

		foreach ($types as $type) {
			if (!empty($this->{'long_'.$type.'_list'})) {
				$this->$type = $this->{'long_'.$type.'_list'}[mt_rand(0, count($this->{'long_'.$type.'_list'}) - 1)];
			} elseif (!empty($this->{'short_'.$type.'_list'})) {
				$this->$type = $this->{'short_'.$type.'_list'}[mt_rand(0, count($this->{'short_'.$type.'_list'}) - 1)];
			}
		}

		/**
		 * Write data to database tables "user_details" and "user_status".
		 */
		if (($query = @mysqli_query($mysqli, 'SELECT * FROM `user_details` WHERE `csNick` = \''.mysqli_real_escape_string($mysqli, $this->csNick).'\'')) === FALSE) {
			return FALSE;
		}

		$rows = mysqli_num_rows($query);
		$submit = FALSE;

		if (empty($rows)) {
			if (!@mysqli_query($mysqli, 'INSERT INTO `user_details` (`UID`, `csNick`, `firstSeen`, `lastSeen`) VALUES (0, \''.mysqli_real_escape_string($mysqli, $this->csNick).'\', \''.mysqli_real_escape_string($mysqli, $this->firstSeen).'\', \''.mysqli_real_escape_string($mysqli, $this->lastSeen).'\')')) {
				return FALSE;
			}

			$this->UID = mysqli_insert_id($mysqli);

			if (!@mysqli_query($mysqli, 'INSERT INTO `user_status` (`UID`, `RUID`, `status`) VALUES ('.$this->UID.', '.$this->UID.', 0)')) {
				return FALSE;
			}
		} else {
			$result = mysqli_fetch_object($query);
			$this->UID = $result->UID;
			$query = 'UPDATE `'.$table.'` SET';
			$columns = array('csNick', 'firstSeen', 'lastSeen');

			foreach ($columns as $key) {
				if ($this->$key != '' && $this->$key != $result->$key && !($key == 'firstSeen' && $this->firstSeen.':00' >= $result->firstSeen) && !($key == 'lastSeen' && $this->lastSeen.':00' <= $result->lastSeen)) {
					$query .= ' `'.$key.'` = \''.mysqli_real_escape_string($mysqli, $this->$key).'\',';
					$submit = TRUE;
				}
			}

			/**
			 * Don't send anything to the database if user data is empty or hasn't changed.
			 */
			if ($submit) {
				$query = rtrim($query, ',').' WHERE `UID` = '.$this->UID;

				if (!@mysqli_query($mysqli, $query)) {
					return FALSE;
				}
			}
		}

		/**
		 * Write data to database tables "user_events", "user_lines" and "user_smileys".
		 */
		$tables = array('user_events', 'user_lines', 'user_smileys');

		foreach ($tables as $table) {
			switch ($table) {
				case 'user_events':
					$columns = array('m_op', 'm_opped', 'm_voice', 'm_voiced', 'm_deOp', 'm_deOpped', 'm_deVoice', 'm_deVoiced', 'joins', 'parts', 'quits', 'kicks', 'kicked', 'nickchanges', 'topics', 'ex_kicks', 'ex_kicked');
					break;
				case 'user_lines':
					$columns = array('l_00', 'l_01', 'l_02', 'l_03', 'l_04', 'l_05', 'l_06', 'l_07', 'l_08', 'l_09', 'l_10', 'l_11', 'l_12', 'l_13', 'l_14', 'l_15', 'l_16', 'l_17', 'l_18', 'l_19', 'l_20', 'l_21', 'l_22', 'l_23', 'l_night', 'l_morning', 'l_afternoon', 'l_evening', 'l_total', 'l_mon_night', 'l_mon_morning', 'l_mon_afternoon', 'l_mon_evening', 'l_tue_night', 'l_tue_morning', 'l_tue_afternoon', 'l_tue_evening', 'l_wed_night', 'l_wed_morning', 'l_wed_afternoon', 'l_wed_evening', 'l_thu_night', 'l_thu_morning', 'l_thu_afternoon', 'l_thu_evening', 'l_fri_night', 'l_fri_morning', 'l_fri_afternoon', 'l_fri_evening', 'l_sat_night', 'l_sat_morning', 'l_sat_afternoon', 'l_sat_evening', 'l_sun_night', 'l_sun_morning', 'l_sun_afternoon', 'l_sun_evening', 'URLs', 'words', 'characters', 'monologues', 'topMonologue', 'activeDays', 'slaps', 'slapped', 'exclamations', 'questions', 'actions', 'uppercased', 'quote', 'ex_exclamations', 'ex_questions', 'ex_actions', 'ex_uppercased', 'lastTalked');
					break;
				case 'user_smileys':
					$columns = array('s_01', 's_02', 's_03', 's_04', 's_05', 's_06', 's_07', 's_08', 's_09', 's_10', 's_11', 's_12', 's_13', 's_14', 's_15', 's_16', 's_17', 's_18', 's_19');
					break;
			}

			if (($query = @mysqli_query($mysqli, 'SELECT * FROM `'.$table.'` WHERE `UID` = '.$this->UID)) === FALSE) {
				return FALSE;
			}

			$rows = mysqli_num_rows($query);
			$submit = FALSE;

			if (empty($rows)) {
				$query = 'INSERT INTO `'.$table.'` SET `UID` = '.$this->UID.',';

				foreach ($columns as $key) {
					if (is_int($this->$key) && $this->$key != 0) {
						$query .= ' `'.$key.'` = '.$this->$key.',';
						$submit = TRUE;
					} elseif (is_string($this->$key) && $this->$key != '') {
						$query .= ' `'.$key.'` = \''.mysqli_real_escape_string($mysqli, $this->$key).'\',';
						$submit = TRUE;
					}
				}

				/**
				 * Don't send anything to the database if user data is empty or hasn't changed.
				 */
				if ($submit) {
					$query = rtrim($query, ',');

					if (!@mysqli_query($mysqli, $query)) {
						return FALSE;
					}
				}
			} else {
				$result = mysqli_fetch_object($query);
				$query = 'UPDATE `'.$table.'` SET';

				foreach ($table as $key) {
					if ($key == 'topMonologue') {
						if ($this->topMonologue > (int) $result->topMonologue) {
							$query .= ' `topMonologue` = '.$this->topMonologue.',';
						}
					} elseif (is_int($this->$key) && $this->$key != 0) {
						$query .= ' `'.$key.'` = '.((int) $result->$key + $this->$key).',';
						$submit = TRUE;
					} elseif (is_string($this->$key) && $this->$key != '' && $this->$key != $result->$key && !($key == 'lastTalked' && $this->lastTalked.':00' <= $result->lastTalked)) {
						$query .= ' `'.$key.'` = \''.mysqli_real_escape_string($mysqli, $this->$key).'\',';
						$submit = TRUE;
					}
				}

				/**
				 * Don't send anything to the database if user data is empty or hasn't changed.
				 */
				if ($submit) {
					$query = rtrim($query, ',').' WHERE `UID` = '.$this->UID;

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
			if (!@mysqli_query($mysqli, 'INSERT INTO `user_activity` (`UID`, `date`, `l_night`, `l_morning`, `l_afternoon`, `l_evening`, `l_total`) VALUES ('.$this->UID.', \''.mysqli_real_escape_string($mysqli, $this->date).'\', '.$this->l_night.', '.$this->l_morning.', '.$this->l_afternoon.', '.$this->l_evening.', '.$this->l_total.') ON DUPLICATE KEY UPDATE `l_night` = `l_night` + '.$this->l_night.', `l_morning` = `l_morning` + '.$this->l_morning.', `l_afternoon` = `l_afternoon` + '.$this->l_afternoon.', `l_evening` = `l_evening` + '.$this->l_evening.', `l_total` = `l_total` + '.$this->l_total)) {
				return FALSE;
			}
		}

		/**
		 * Write data to database table "user_hosts".
		 */
		if (!empty($this->hosts_list)) {
			foreach ($this->hosts_list as $host) {
				if (($query = @mysqli_query($mysqli, 'SELECT * FROM `user_hosts` WHERE `UID` = '.$this->UID.' AND `host` = \''.mysqli_real_escape_string($mysqli, $host).'\'')) === FALSE) {
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
					if (($query = @mysqli_query($mysqli, 'SELECT `HID` FROM `user_hosts` WHERE `host` = \''.mysqli_real_escape_string($mysqli, $host).'\' GROUP BY `host`')) === FALSE) {
						return FALSE;
					}

					$rows = mysqli_num_rows($query);

					if (empty($rows)) {
						if (!@mysqli_query($mysqli, 'INSERT INTO `user_hosts` (`HID`, `UID`, `host`) VALUES (0, '.$this->UID.', \''.mysqli_real_escape_string($mysqli, $host).'\')')) {
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
				if (($query = @mysqli_query($mysqli, 'SELECT * FROM `user_topics` WHERE `UID` = '.$this->UID.' AND `csTopic` = \''.mysqli_real_escape_string($mysqli, $topic['csTopic']).'\' AND `setDate` = \''.mysqli_real_escape_string($mysqli, $topic['setDate']).'\'')) === FALSE) {
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
					if (($query = @mysqli_query($mysqli, 'SELECT `TID` FROM `user_topics` WHERE `csTopic` = \''.mysqli_real_escape_string($mysqli, $topic['csTopic']).'\' GROUP BY `csTopic`')) === FALSE) {
						return FALSE;
					}

					$rows = mysqli_num_rows($query);

					if (empty($rows)) {
						if (!@mysqli_query($mysqli, 'INSERT INTO `user_topics` (`TID`, `UID`, `csTopic`, `setDate`) VALUES (0, '.$this->UID.', \''.mysqli_real_escape_string($mysqli, $topic['csTopic']).'\', \''.mysqli_real_escape_string($mysqli, $topic['setDate']).'\')')) {
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
		foreach ($this->URLs_objs as $URL) {
			if (!$URL->writeData($mysqli, $this->UID)) {
				return FALSE;
			}
		}

		return TRUE;
	}
}

?>
