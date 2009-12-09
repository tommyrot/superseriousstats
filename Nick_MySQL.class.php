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
 * Class for writing user data to the database.
 */
abstract class Nick_MySQL
{
	/**
	 * The array $user_tables should contain "user_details" as its first element.
	 */
	private $user_tables = array('user_details', 'user_events', 'user_lines', 'user_smileys');
	private $user_details = array('UID', 'csNick', 'firstSeen', 'lastSeen');
	private $user_events = array('UID', 'm_op', 'm_opped', 'm_voice', 'm_voiced', 'm_deOp', 'm_deOpped', 'm_deVoice', 'm_deVoiced', 'joins', 'parts', 'quits', 'kicks', 'kicked', 'nickchanges', 'topics', 'ex_kicks', 'ex_kicked');
	private $user_lines = array('UID', 'l_00', 'l_01', 'l_02', 'l_03', 'l_04', 'l_05', 'l_06', 'l_07', 'l_08', 'l_09', 'l_10', 'l_11', 'l_12', 'l_13', 'l_14', 'l_15', 'l_16', 'l_17', 'l_18', 'l_19', 'l_20', 'l_21', 'l_22', 'l_23', 'l_night', 'l_morning', 'l_afternoon', 'l_evening', 'l_total', 'l_mon_night', 'l_mon_morning', 'l_mon_afternoon', 'l_mon_evening', 'l_tue_night', 'l_tue_morning', 'l_tue_afternoon', 'l_tue_evening', 'l_wed_night', 'l_wed_morning', 'l_wed_afternoon', 'l_wed_evening', 'l_thu_night', 'l_thu_morning', 'l_thu_afternoon', 'l_thu_evening', 'l_fri_night', 'l_fri_morning', 'l_fri_afternoon', 'l_fri_evening', 'l_sat_night', 'l_sat_morning', 'l_sat_afternoon', 'l_sat_evening', 'l_sun_night', 'l_sun_morning', 'l_sun_afternoon', 'l_sun_evening', 'URLs', 'words', 'characters', 'monologues', 'topMonologue', 'activeDays', 'slaps', 'slapped', 'exclamations', 'questions', 'actions', 'uppercased', 'quote', 'ex_exclamations', 'ex_questions', 'ex_actions', 'ex_uppercased', 'lastTalked');
	private $user_smileys = array('UID', 's_01', 's_02', 's_03', 's_04', 's_05', 's_06', 's_07', 's_08', 's_09', 's_10', 's_11', 's_12', 's_13', 's_14', 's_15', 's_16', 's_17', 's_18', 's_19');

	/**
	 * Write user data to the database.
	 */
	final public function writeData($mysqli)
	{
		/**
		 * Write data to database tables "user_details", "user_status", "user_events", "user_lines" and "user_smileys".
		 */
		foreach ($this->user_tables as $table) {
			if ($table == 'user_details') {
				if (!$query = @mysqli_query($mysqli, 'SELECT * FROM `'.$table.'` WHERE `csNick` = \''.mysqli_real_escape_string($mysqli, $this->csNick).'\' LIMIT 1')) {
					return FALSE;
				}
			} else {
				if (!$query = @mysqli_query($mysqli, 'SELECT * FROM `'.$table.'` WHERE `UID` = '.$this->UID.' LIMIT 1')) {
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
			if (!@mysqli_query($mysqli, 'INSERT INTO `user_activity` (`UID`, `date`, `l_night`, `l_morning`, `l_afternoon`, `l_evening`, `l_total`) VALUES ('.$this->UID.', \''.mysqli_real_escape_string($mysqli, DATE).'\', '.$this->l_night.', '.$this->l_morning.', '.$this->l_afternoon.', '.$this->l_evening.', '.$this->l_total.')')) {
				return FALSE;
			}
		}

		/**
		 * Write data to database table "user_hosts".
		 */
		if (!empty($this->hosts_list)) {
			foreach ($this->hosts_list as $host) {
				if (!$query = @mysqli_query($mysqli, 'SELECT * FROM `user_hosts` WHERE `UID` = '.$this->UID.' AND `host` = \''.mysqli_real_escape_string($mysqli, $host).'\' LIMIT 1')) {
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
					if (!$query = @mysqli_query($mysqli, 'SELECT * FROM `user_hosts` WHERE `host` = \''.mysqli_real_escape_string($mysqli, $host).'\' LIMIT 1')) {
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
				if (!$query = @mysqli_query($mysqli, 'SELECT * FROM `user_topics` WHERE `UID` = '.$this->UID.' AND `csTopic` = \''.mysqli_real_escape_string($mysqli, $topic['csTopic']).'\' AND `setDate` = \''.mysqli_real_escape_string($mysqli, $topic['setDate']).'\' LIMIT 1')) {
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
					if (!$query = @mysqli_query($mysqli, 'SELECT * FROM `user_topics` WHERE `csTopic` = \''.mysqli_real_escape_string($mysqli, $topic['csTopic']).'\' LIMIT 1')) {
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
