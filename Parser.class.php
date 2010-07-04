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
 * General parse instructions. This class will be extended by a class with logfile format specific parse instructions.
 */
abstract class Parser extends Base
{
	/**
	 * Default settings for this script, can be overridden in the config file.
	 * These should all appear in $settings_list[] along with their type.
	 */
	private $minStreak = 5;
	private $nick_maxLen = 255;
	private $nick_minLen = 1;
	private $quote_prefLen = 25;
	private $wordTracking = FALSE;

	/**
	 * Variables used in database table "channel".
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

	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $URLTools;
	private $nicks_objs = array();
	private $settings_list = array(
		'minStreak' => 'int',
		'nick_maxLen' => 'int',
		'nick_minLen' => 'int',
		'outputbits' => 'int',
		'quote_prefLen' => 'int',
		'wordTracking' => 'bool');
	private $smileys = array(
		'=]' => 's_01',
		'=)' => 's_02',
		';x' => 's_03',
		';p' => 's_04',
		';]' => 's_05',
		';-)' => 's_06',
		';)' => 's_07',
		';(' => 's_08',
		':x' => 's_09',
		':p' => 's_10',
		':d' => 's_11',
		':>' => 's_12',
		':]' => 's_13',
		':\\' => 's_14',
		':/' => 's_15',
		':-)' => 's_16',
		':)' => 's_17',
		':(' => 's_18',
		'\\o/' => 's_19');
	private $words_objs = array();
	protected $date = '';
	protected $lineNum = 0;
	protected $mysqli;
	protected $prevLine = '';
	protected $prevNick = '';
	protected $streak = 0;

	/**
	 * Constructor.
	 */
	final public function __construct($settings)
	{
		foreach ($this->settings_list as $key => $type) {
			if (!array_key_exists($key, $settings)) {
				continue;
			}

			if ($type == 'string') {
				$this->$key = $settings[$key];
			} elseif ($type == 'int') {
				$this->$key = (int) $settings[$key];
			} elseif ($type == 'bool') {
				if (strtoupper($settings[$key]) == 'TRUE') {
					$this->$key = TRUE;
				} elseif (strtoupper($settings[$key]) == 'FALSE') {
					$this->$key = FALSE;
				}
			}
		}

		$this->URLTools = new URLTools();
	}

	/**
	 * Create an object of the nick if it doesn't already exist.
	 * Return the lowercase nick for further referencing by the calling function.
	 */
	final private function addNick($csNick, $dateTime)
	{
		$nick = strtolower($csNick);

		if (!array_key_exists($nick, $this->nicks_objs)) {
			$this->nicks_objs[$nick] = new Nick($csNick);
			$this->nicks_objs[$nick]->setValue('date', $this->date);
		} else {
			$this->nicks_objs[$nick]->setValue('csNick', $csNick);
		}

		if (!is_null($dateTime)) {
			$this->nicks_objs[$nick]->lastSeen($dateTime);
		}

		return $nick;
	}

	/**
	 * Function for the global word tracking. This feature can be enabled or disabled in the config file.
	 * Due to the in_array() function being shitty inefficient, searching large arrays is a big performance hit.
	 */
	final private function addWord($csWord)
	{
		$word = strtolower($csWord);

		if (!array_key_exists($word, $this->words_objs)) {
			$this->words_objs[$word] = new Word($word);
		}

		$this->words_objs[$word]->addValue('total', 1);
	}

	/**
	 * Main function with general parse instructions.
	 */
	final public function parseLog($logfile, $firstLine)
	{
		if (($fp = @fopen($logfile, 'rb')) === FALSE) {
			$this->output('critical', 'parseLog(): failed to open file: \''.$logfile.'\'');
		}

		$this->output('notice', 'parseLog(): parsing logfile: \''.$logfile.'\' from line '.$firstLine);

		while (!feof($fp)) {
			$line = fgets($fp);
			$this->lineNum++;

			if ($this->lineNum < $firstLine) {
				continue;
			}

			/**
			 * Normalize the line:
			 * 1. Remove ISO-8859-1 control codes: characters x00 to x1F (except x09) and x7F to x9F. Treat x03 differently since it is used for (mIRC) color codes.
			 * 2. Remove multiple adjacent spaces (x20) and all tabs (x09).
			 * 3. Remove whitespace characters at the beginning and end of a line.
			 */
			$line = preg_replace(array('/[\x00-\x02\x04-\x08\x0A-\x1F\x7F-\x9F]|\x03([0-9]{1,2}(,[0-9]{1,2})?)?/', '/\x09[\x09\x20]*|\x20[\x09\x20]+/', '/^\x20|\x20$/'), array('', ' ', ''), $line);

			/**
			 * Pass on the normalized line to the logfile format specific parser class extending this class.
			 */
			$this->parseLine($line);
			$this->prevLine = $line;
		}

		fclose($fp);
		$this->output('notice', 'parseLog(): parsing completed');
	}

	/**
	 * Do stuff with "action" lines data.
	 */
	final protected function setAction($dateTime, $csNick, $line)
	{
		if (!$this->validateNick($csNick)) {
			$this->output('warning', 'setAction(): invalid nick: \''.$csNick.'\' on line '.$this->lineNum);
		} else {
			$nick = $this->addNick($csNick, $dateTime);
			$this->nicks_objs[$nick]->addValue('actions', 1);

			if (strlen($line) <= 255) {
				if (strlen($line) >= $this->quote_prefLen) {
					$this->nicks_objs[$nick]->addQuote('ex_actions', 'long', $line);
				} else {
					$this->nicks_objs[$nick]->addQuote('ex_actions', 'short', $line);
				}
			}
		}
	}

	/**
	 * Do stuff with "join" lines data.
	 */
	final protected function setJoin($dateTime, $csNick, $csHost)
	{
		if (!$this->validateNick($csNick)) {
			$this->output('warning', 'setJoin(): invalid nick: \''.$csNick.'\' on line '.$this->lineNum);
		} else {
			$nick = $this->addNick($csNick, $dateTime);
			$this->nicks_objs[$nick]->addValue('joins', 1);

			if ($csHost == '0' || strlen($csHost) > 255) {
				$this->output('warning', 'setJoin(): invalid host: \''.$csHost.'\' on line '.$this->lineNum);
			} else {
				$this->nicks_objs[$nick]->addHost($csHost);
			}
		}
	}

	/**
	 * Do stuff with "kick" lines data.
	 */
	final protected function setKick($dateTime, $csNick_performing, $csNick_undergoing, $line)
	{
		if (!$this->validateNick($csNick_performing)) {
			$this->output('warning', 'setKick(): invalid "performing" nick: \''.$csNick_performing.'\' on line '.$this->lineNum);
		} elseif (!$this->validateNick($csNick_undergoing)) {
			$this->output('warning', 'setKick(): invalid "undergoing" nick: \''.$csNick_undergoing.'\' on line '.$this->lineNum);
		} else {
			$nick_performing = $this->addNick($csNick_performing, $dateTime);
			$nick_undergoing = $this->addNick($csNick_undergoing, $dateTime);
			$this->nicks_objs[$nick_performing]->addValue('kicks', 1);
			$this->nicks_objs[$nick_undergoing]->addValue('kicked', 1);

			if (strlen($line) <= 255) {
				$this->nicks_objs[$nick_performing]->setValue('ex_kicks', $line);
				$this->nicks_objs[$nick_undergoing]->setValue('ex_kicked', $line);
			}
		}
	}

	/**
	 * Do stuff with "mode" lines data.
	 */
	final protected function setMode($dateTime, $csNick_performing, $csNick_undergoing, $mode, $csHost)
	{
		if (!$this->validateNick($csNick_performing)) {
			$this->output('warning', 'setMode(): invalid "performing" nick: \''.$csNick_performing.'\' on line '.$this->lineNum);
		} elseif (!$this->validateNick($csNick_undergoing)) {
			$this->output('warning', 'setMode(): invalid "undergoing" nick: \''.$csNick_undergoing.'\' on line '.$this->lineNum);
		} else {
			$nick_performing = $this->addNick($csNick_performing, $dateTime);
			$nick_undergoing = $this->addNick($csNick_undergoing, $dateTime);

			switch ($mode) {
				case '+o':
					$this->nicks_objs[$nick_performing]->addValue('m_op', 1);
					$this->nicks_objs[$nick_undergoing]->addValue('m_opped', 1);
					break;
				case '+v':
					$this->nicks_objs[$nick_performing]->addValue('m_voice', 1);
					$this->nicks_objs[$nick_undergoing]->addValue('m_voiced', 1);
					break;
				case '-o':
					$this->nicks_objs[$nick_performing]->addValue('m_deOp', 1);
					$this->nicks_objs[$nick_undergoing]->addValue('m_deOpped', 1);
					break;
				case '-v':
					$this->nicks_objs[$nick_performing]->addValue('m_deVoice', 1);
					$this->nicks_objs[$nick_undergoing]->addValue('m_deVoiced', 1);
					break;
			}

			if (!is_null($csHost)) {
				if ($csHost == '0' || strlen($csHost) > 255) {
					$this->output('warning', 'setMode(): invalid host: \''.$csHost.'\' on line '.$this->lineNum);
				} else {
					$this->nicks_objs[$nick_performing]->addHost($csHost);
				}
			}
		}
	}

	/**
	 * Do stuff with "nickchange" lines data.
	 */
	final protected function setNickchange($dateTime, $csNick_performing, $csNick_undergoing)
	{
		if (!$this->validateNick($csNick_performing)) {
			$this->output('warning', 'setNickchange(): invalid "performing" nick: \''.$csNick_performing.'\' on line '.$this->lineNum);
		} elseif (!$this->validateNick($csNick_undergoing)) {
			$this->output('warning', 'setNickchange(): invalid "undergoing" nick: \''.$csNick_undergoing.'\' on line '.$this->lineNum);
		} else {
			$nick_performing = $this->addNick($csNick_performing, $dateTime);
			$nick_undergoing = $this->addNick($csNick_undergoing, $dateTime);
			$this->nicks_objs[$nick_performing]->addValue('nickchanges', 1);
		}
	}

	/**
	 * Do stuff with "normal" lines data.
	 */
	final protected function setNormal($dateTime, $csNick, $line)
	{
		if (!$this->validateNick($csNick)) {
			$this->output('warning', 'setNormal(): invalid nick: \''.$csNick.'\' on line '.$this->lineNum);
		} else {
			$nick = $this->addNick($csNick, $dateTime);
			$this->nicks_objs[$nick]->lastTalked($dateTime);
			$this->nicks_objs[$nick]->setValue('activeDays', 1);
			$this->nicks_objs[$nick]->addValue('characters', strlen($line));

			if ($nick == $this->prevNick) {
				$this->streak++;
			} else {
				if ($this->streak >= $this->minStreak) {
					/**
					 * If the current line count is 0 then $prevNick is not known to us yet (seen in previous parse run).
					 * It's safe to assume that $prevNick is a valid nick since it was set by setNormal().
					 * We will create an object for it here so we can add the monologue data. Don't worry about $prevNick being lowercase,
					 * we won't update "user_details" if $prevNick isn't seen plus $csNick will get a refresh on any other activity.
					 */
					if ($this->l_total == 0) {
						$this->addNick($this->prevNick, NULL);
					}

					$this->nicks_objs[$this->prevNick]->addValue('monologues', 1);

					if ($this->streak > $this->nicks_objs[$this->prevNick]->getValue('topMonologue')) {
						$this->nicks_objs[$this->prevNick]->setValue('topMonologue', $this->streak);
					}
				}

				$this->streak = 1;
				$this->prevNick = $nick;
			}

			$day = strtolower(date('D', strtotime($this->date)));
			$hour = substr($dateTime, 11, 2);

			if (preg_match('/^0[0-5]$/', $hour)) {
				$this->l_night++;
				$this->nicks_objs[$nick]->addValue('l_night', 1);
				$this->nicks_objs[$nick]->addValue('l_'.$day.'_night', 1);
			} elseif (preg_match('/^(0[6-9]|1[01])$/', $hour)) {
				$this->l_morning++;
				$this->nicks_objs[$nick]->addValue('l_morning', 1);
				$this->nicks_objs[$nick]->addValue('l_'.$day.'_morning', 1);
			} elseif (preg_match('/^1[2-7]$/', $hour)) {
				$this->l_afternoon++;
				$this->nicks_objs[$nick]->addValue('l_afternoon', 1);
				$this->nicks_objs[$nick]->addValue('l_'.$day.'_afternoon', 1);
			} elseif (preg_match('/^(1[89]|2[0-3])$/', $hour)) {
				$this->l_evening++;
				$this->nicks_objs[$nick]->addValue('l_evening', 1);
				$this->nicks_objs[$nick]->addValue('l_'.$day.'_evening', 1);
			}

			$this->{'l_'.$hour}++;
			$this->l_total++;
			$this->nicks_objs[$nick]->addValue('l_'.$hour, 1);
			$this->nicks_objs[$nick]->addValue('l_total', 1);

			if (strlen($line) <= 255) {
				if (strlen($line) >= $this->quote_prefLen) {
					$this->nicks_objs[$nick]->addQuote('quote', 'long', $line);
				} else {
					$this->nicks_objs[$nick]->addQuote('quote', 'short', $line);
				}
			}

			if (strlen($line) >= 2 && strtoupper($line) == $line && strlen(preg_replace('/[A-Z]/', '', $line)) * 2 < strlen($line)) {
				$this->nicks_objs[$nick]->addValue('uppercased', 1);

				if (strlen($line) <= 255) {
					if (strlen($line) >= $this->quote_prefLen) {
						$this->nicks_objs[$nick]->addQuote('ex_uppercased', 'long', $line);
					} else {
						$this->nicks_objs[$nick]->addQuote('ex_uppercased', 'short', $line);
					}
				}
			}

			if (preg_match('/!$/', $line)) {
				$this->nicks_objs[$nick]->addValue('exclamations', 1);

				if (strlen($line) <= 255) {
					if (strlen($line) >= $this->quote_prefLen) {
						$this->nicks_objs[$nick]->addQuote('ex_exclamations', 'long', $line);
					} else {
						$this->nicks_objs[$nick]->addQuote('ex_exclamations', 'short', $line);
					}
				}
			} elseif (preg_match('/\?$/', $line)) {
				$this->nicks_objs[$nick]->addValue('questions', 1);

				if (strlen($line) <= 255) {
					if (strlen($line) >= $this->quote_prefLen) {
						$this->nicks_objs[$nick]->addQuote('ex_questions', 'long', $line);
					} else {
						$this->nicks_objs[$nick]->addQuote('ex_questions', 'short', $line);
					}
				}
			}

			/**
			 * The "words" counter below has no relation with the words which are stored in the database.
			 * It simply counts all character groups separated by whitespace.
			 */
			$words = explode(' ', $line);
			$this->nicks_objs[$nick]->addValue('words', count($words));

			foreach ($words as $csWord) {
				if (preg_match('/^(=[])]|;([]()xp]|-\))|:([]\/()\\\>xpd]|-\))|\\\o\/)$/i', $csWord)) {
					$this->nicks_objs[$nick]->addValue($this->smileys[strtolower($csWord)], 1);
				} elseif (preg_match('/^(www\.|https?:\/\/)/i', $csWord)) {
					/**
					 * Put "http://" scheme in front of all URLs beginning with just "www.".
					 */
					$csURL = preg_replace('/^www\./i', 'http://$0', $csWord);

					if (strlen($csURL) > 255 || !$this->URLTools->validateURL($csURL)) {
						$this->output('notice', 'setNormal(): invalid URL: \''.$csWord.'\' on line '.$this->lineNum);
					} else {
						$csURL = $this->URLTools->normalizeURL($csURL);
						$this->nicks_objs[$nick]->addURL($csURL, $dateTime);
						$this->nicks_objs[$nick]->addValue('URLs', 1);
					}
				} elseif ($this->wordTracking && preg_match('/^[a-z]{1,255}$/i', $csWord)) {
					/**
					 * To keep it simple we only track words composed of the characters A through Z.
					 * Words consisting of 30+ characters are most likely not real words but then again we're not a dictionary.
					 */
					$this->addWord($csWord);
				}
			}
		}
	}

	/**
	 * Do stuff with "part" lines data.
	 */
	final protected function setPart($dateTime, $csNick, $csHost)
	{
		if (!$this->validateNick($csNick)) {
			$this->output('warning', 'setPart(): invalid nick: \''.$csNick.'\' on line '.$this->lineNum);
		} else {
			$nick = $this->addNick($csNick, $dateTime);
			$this->nicks_objs[$nick]->addValue('parts', 1);

			if ($csHost == '0' || strlen($csHost) > 255) {
				$this->output('warning', 'setPart(): invalid host: \''.$csHost.'\' on line '.$this->lineNum);
			} else {
				$this->nicks_objs[$nick]->addHost($csHost);
			}
		}
	}

	/**
	 * Do stuff with "quit" lines data.
	 */
	final protected function setQuit($dateTime, $csNick, $csHost)
	{
		if (!$this->validateNick($csNick)) {
			$this->output('warning', 'setQuit(): invalid nick: \''.$csNick.'\' on line '.$this->lineNum);
		} else {
			$nick = $this->addNick($csNick, $dateTime);
			$this->nicks_objs[$nick]->addValue('quits', 1);

			if ($csHost == '0' || strlen($csHost) > 255) {
				$this->output('warning', 'setQuit(): invalid host: \''.$csHost.'\' on line '.$this->lineNum);
			} else {
				$this->nicks_objs[$nick]->addHost($csHost);
			}
		}
	}

	/**
	 * Do stuff with "slap" lines data.
	 */
	final protected function setSlap($dateTime, $csNick_performing, $csNick_undergoing)
	{
		if (!$this->validateNick($csNick_performing)) {
			$this->output('warning', 'setSlap(): invalid "performing" nick: \''.$csNick_performing.'\' on line '.$this->lineNum);
		} else {
			$nick_performing = $this->addNick($csNick_performing, $dateTime);
			$this->nicks_objs[$nick_performing]->addValue('slaps', 1);

			if (!is_null($csNick_undergoing)) {
				/**
				 * Clean possible network prefix (psyBNC) from undergoing nick.
				 */
				if (substr_count($csNick_undergoing, '~') + substr_count($csNick_undergoing, '\'') == 1) {
					$this->output('notice', 'setSlap(): cleaning "undergoing" nick: \''.$csNick_undergoing.'\' on line '.$this->lineNum);
					$tmp = preg_split('/[~\']/', $csNick_undergoing, 2);
					$csNick_undergoing = $tmp[1];
				}

				if (!$this->validateNick($csNick_undergoing)) {
					$this->output('warning', 'setSlap(): invalid "undergoing" nick: \''.$csNick_undergoing.'\' on line '.$this->lineNum);
				} else {
					/**
					 * Don't pass a time when adding the undergoing nick while it may only be referred to instead of being seen for real.
					 */
					$nick_undergoing = $this->addNick($csNick_undergoing, NULL);
					$this->nicks_objs[$nick_undergoing]->addValue('slapped', 1);
				}
			}
		}
	}

	/**
	 * Do stuff with "topic" lines data.
	 */
	final protected function setTopic($dateTime, $csNick, $csHost, $line)
	{
		if (!$this->validateNick($csNick)) {
			$this->output('warning', 'setTopic(): invalid nick: \''.$csNick.'\' on line '.$this->lineNum);
		} else {
			$nick = $this->addNick($csNick, $dateTime);
			$this->nicks_objs[$nick]->addValue('topics', 1);

			if (!is_null($csHost)) {
				if ($csHost == '0' || strlen($csHost) > 255) {
					$this->output('warning', 'setTopic(): invalid host: \''.$csHost.'\' on line '.$this->lineNum);
				} else {
					$this->nicks_objs[$nick]->addHost($csHost);
				}
			}

			/**
			 * Keep track of every single topic set.
			 */
			if (strlen($line) > 510) {
				$this->output('warning', 'setTopic(): invalid topic: \''.$csTopic.'\' on line '.$this->lineNum);
			} else {
				$this->nicks_objs[$nick]->addTopic($line, $dateTime);
			}
		}
	}

	/**
	 * Validate a given nick. Check on syntax and defined lengths. Maximum length should not exceed 255 which is the maximum database field length.
	 */
	final private function validateNick($csNick)
	{
		if ($csNick != '0' && preg_match('/^[][^{}|\\\`_0-9a-z-]{'.$this->nick_minLen.','.($this->nick_maxLen > 255 ? 255 : $this->nick_maxLen).'}$/i', $csNick)) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Write all gathered data to the database.
	 */
	final public function writeData($mysqli)
	{
		$this->mysqli = $mysqli;

		/**
		 * If there are no nicks there is no data.
		 */
		if (empty($this->nicks_objs)) {
			$this->output('notice', 'writeData(): no data to write to database');
		} else {
			$this->output('notice', 'writeData(): writing data to database');

			/**
			 * Write channel totals to the database.
			 */
			$query = @mysqli_query($this->mysqli, 'SELECT * FROM `channel` WHERE `date` = \''.mysqli_real_escape_string($this->mysqli, $this->date).'\'') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query);

			if (empty($rows)) {
				$createdQuery = $this->createInsertQuery(array('l_00', 'l_01', 'l_02', 'l_03', 'l_04', 'l_05', 'l_06', 'l_07', 'l_08', 'l_09', 'l_10', 'l_11', 'l_12', 'l_13', 'l_14', 'l_15', 'l_16', 'l_17', 'l_18', 'l_19', 'l_20', 'l_21', 'l_22', 'l_23', 'l_night', 'l_morning', 'l_afternoon', 'l_evening', 'l_total'));

				if (!is_null($createdQuery)) {
					@mysqli_query($this->mysqli, 'INSERT INTO `channel` SET `date` = \''.mysqli_real_escape_string($this->mysqli, $this->date).'\','.$createdQuery) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
				}
			} else {
				$result = mysqli_fetch_object($query);
				$createdQuery = $this->createUpdateQuery($result, array('date'));

				if (!is_null($createdQuery)) {
					@mysqli_query($this->mysqli, 'UPDATE `channel` SET'.$createdQuery.' WHERE `date` = \''.mysqli_real_escape_string($this->mysqli, $this->date).'\'') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
				}
			}

			/**
			 * Write user data to the database.
			 */
			foreach ($this->nicks_objs as $nick) {
				$nick->writeData($this->mysqli);
			}

			/**
			 * Write streak data to the database.
			 */
			@mysqli_query($this->mysqli, 'TRUNCATE TABLE `streak_history`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			@mysqli_query($this->mysqli, 'INSERT INTO `streak_history` (`prevNick`, `streak`) VALUES (\''.mysqli_real_escape_string($this->mysqli, $this->prevNick).'\', '.$this->streak.')') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));

			/**
			 * Write word data to the database.
			 * To keep our database sane words are not linked to users.
			 */
			foreach ($this->words_objs as $word) {
				$word->writeData($this->mysqli);
			}

			$this->output('notice', 'writeData(): writing completed');
		}
	}
}

?>
