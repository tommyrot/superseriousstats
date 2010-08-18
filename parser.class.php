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
abstract class parser extends base
{
	/**
	 * Default settings for this script, can be overridden in the config file.
	 * These should all appear in $settings_list[] along with their type.
	 */
	private $minstreak = 5;
	private $nick_maxlen = 255;
	private $nick_minlen = 1;
	private $quote_preflen = 25;
	private $wordtracking = true;

	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $nicks_objs = array();
	private $settings_list = array(
		'minstreak' => 'int',
		'nick_maxlen' => 'int',
		'nick_minlen' => 'int',
		'outputbits' => 'int',
		'quote_preflen' => 'int',
		'wordtracking' => 'bool');
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
	private $urltools;
	private $words_objs = array();
	protected $date = '';
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
	protected $linenum = 0;
	protected $mysqli;
	protected $prevline = '';
	protected $prevnick = '';
	protected $streak = 0;

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
				if (strtolower($settings[$key]) == 'true') {
					$this->$key = true;
				} elseif (strtolower($settings[$key]) == 'false') {
					$this->$key = false;
				}
			}
		}

		$this->urltools = new urltools();
	}

	/**
	 * Create an object of the nick if it doesn't already exist.
	 * Return the lowercase nick for further referencing by the calling function.
	 */
	final private function add_nick($csnick, $datetime)
	{
		$nick = strtolower($csnick);

		if (!array_key_exists($nick, $this->nicks_objs)) {
			$this->nicks_objs[$nick] = new nick($csnick);
			$this->nicks_objs[$nick]->set_value('date', $this->date);
		} else {
			$this->nicks_objs[$nick]->set_value('csnick', $csnick);
		}

		if (!is_null($datetime)) {
			$this->nicks_objs[$nick]->set_lastseen($datetime);
		}

		return $nick;
	}

	final private function add_word($csword)
	{
		$word = strtolower($csword);

		if (!array_key_exists($word, $this->words_objs)) {
			$this->words_objs[$word] = new word($word);
		}

		$this->words_objs[$word]->add_value('total', 1);
	}

	final public function parse_log($logfile, $firstline)
	{
		if (($fp = @fopen($logfile, 'rb')) === false) {
			$this->output('critical', 'parse_log(): failed to open file: \''.$logfile.'\'');
		}

		$this->output('notice', 'parse_log(): parsing logfile: \''.$logfile.'\' from line '.$firstline);

		while (!feof($fp)) {
			$line = fgets($fp);
			$this->linenum++;

			if ($this->linenum < $firstline) {
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
			$this->parse_line($line);
			$this->prevline = $line;
		}

		fclose($fp);
		$this->output('notice', 'parse_log(): parsing completed');
	}

	final protected function set_action($datetime, $csnick, $line)
	{
		if (!$this->validate_nick($csnick)) {
			$this->output('warning', 'set_action(): invalid nick: \''.$csnick.'\' on line '.$this->linenum);
		} else {
			$nick = $this->add_nick($csnick, $datetime);
			$this->nicks_objs[$nick]->add_value('actions', 1);

			if (strlen($line) <= 255) {
				if (strlen($line) >= $this->quote_preflen) {
					$this->nicks_objs[$nick]->add_quote('ex_actions', 'long', $line);
				} else {
					$this->nicks_objs[$nick]->add_quote('ex_actions', 'short', $line);
				}
			}
		}
	}

	final protected function set_join($datetime, $csnick)
	{
		if (!$this->validate_nick($csnick)) {
			$this->output('warning', 'set_join(): invalid nick: \''.$csnick.'\' on line '.$this->linenum);
		} else {
			$nick = $this->add_nick($csnick, $datetime);
			$this->nicks_objs[$nick]->add_value('joins', 1);
		}
	}

	final protected function set_kick($datetime, $csnick_performing, $csnick_undergoing, $line)
	{
		if (!$this->validate_nick($csnick_performing)) {
			$this->output('warning', 'set_kick(): invalid "performing" nick: \''.$csnick_performing.'\' on line '.$this->linenum);
		} elseif (!$this->validate_nick($csnick_undergoing)) {
			$this->output('warning', 'set_kick(): invalid "undergoing" nick: \''.$csnick_undergoing.'\' on line '.$this->linenum);
		} else {
			$nick_performing = $this->add_nick($csnick_performing, $datetime);
			$nick_undergoing = $this->add_nick($csnick_undergoing, $datetime);
			$this->nicks_objs[$nick_performing]->add_value('kicks', 1);
			$this->nicks_objs[$nick_undergoing]->add_value('kicked', 1);

			if (strlen($line) <= 255) {
				$this->nicks_objs[$nick_performing]->set_value('ex_kicks', $line);
				$this->nicks_objs[$nick_undergoing]->set_value('ex_kicked', $line);
			}
		}
	}

	final protected function set_mode($datetime, $csnick_performing, $csnick_undergoing, $mode)
	{
		if (!$this->validate_nick($csnick_performing)) {
			$this->output('warning', 'set_mode(): invalid "performing" nick: \''.$csnick_performing.'\' on line '.$this->linenum);
		} elseif (!$this->validate_nick($csnick_undergoing)) {
			$this->output('warning', 'set_mode(): invalid "undergoing" nick: \''.$csnick_undergoing.'\' on line '.$this->linenum);
		} else {
			$nick_performing = $this->add_nick($csnick_performing, $datetime);
			$nick_undergoing = $this->add_nick($csnick_undergoing, $datetime);

			switch ($mode) {
				case '+o':
					$this->nicks_objs[$nick_performing]->add_value('m_op', 1);
					$this->nicks_objs[$nick_undergoing]->add_value('m_opped', 1);
					break;
				case '+v':
					$this->nicks_objs[$nick_performing]->add_value('m_voice', 1);
					$this->nicks_objs[$nick_undergoing]->add_value('m_voiced', 1);
					break;
				case '-o':
					$this->nicks_objs[$nick_performing]->add_value('m_deop', 1);
					$this->nicks_objs[$nick_undergoing]->add_value('m_deopped', 1);
					break;
				case '-v':
					$this->nicks_objs[$nick_performing]->add_value('m_devoice', 1);
					$this->nicks_objs[$nick_undergoing]->add_value('m_devoiced', 1);
					break;
			}
		}
	}

	final protected function set_nickchange($datetime, $csnick_performing, $csnick_undergoing)
	{
		if (!$this->validate_nick($csnick_performing)) {
			$this->output('warning', 'set_nickchange(): invalid "performing" nick: \''.$csnick_performing.'\' on line '.$this->linenum);
		} elseif (!$this->validate_nick($csnick_undergoing)) {
			$this->output('warning', 'set_nickchange(): invalid "undergoing" nick: \''.$csnick_undergoing.'\' on line '.$this->linenum);
		} else {
			$nick_performing = $this->add_nick($csnick_performing, $datetime);
			$nick_undergoing = $this->add_nick($csnick_undergoing, $datetime);
			$this->nicks_objs[$nick_performing]->add_value('nickchanges', 1);
		}
	}

	final protected function set_normal($datetime, $csnick, $line)
	{
		if (!$this->validate_nick($csnick)) {
			$this->output('warning', 'set_normal(): invalid nick: \''.$csnick.'\' on line '.$this->linenum);
		} else {
			$nick = $this->add_nick($csnick, $datetime);
			$this->nicks_objs[$nick]->set_lasttalked($datetime);
			$this->nicks_objs[$nick]->set_value('activedays', 1);
			$this->nicks_objs[$nick]->add_value('characters', strlen($line));

			if ($nick == $this->prevnick) {
				$this->streak++;
			} else {
				if ($this->streak >= $this->minstreak) {
					/**
					 * If the current line count is 0 then $prevnick is not known to us yet (only seen in previous parse run).
					 * It's safe to assume that $prevnick is a valid nick since it was set by set_normal().
					 * We will create an object for it here so we can add the monologue data. Don't worry about $prevnick being lowercase,
					 * we won't update "user_details" if $prevnick isn't seen plus $csnick will get a refresh on any other activity.
					 */
					if ($this->l_total == 0) {
						$this->add_nick($this->prevnick, null);
					}

					$this->nicks_objs[$this->prevnick]->add_value('monologues', 1);

					if ($this->streak > $this->nicks_objs[$this->prevnick]->get_value('topmonologue')) {
						$this->nicks_objs[$this->prevnick]->set_value('topmonologue', $this->streak);
					}
				}

				$this->streak = 1;
				$this->prevnick = $nick;
			}

			$day = strtolower(date('D', strtotime($this->date)));
			$hour = substr($datetime, 11, 2);

			if (preg_match('/^0[0-5]$/', $hour)) {
				$this->l_night++;
				$this->nicks_objs[$nick]->add_value('l_night', 1);
				$this->nicks_objs[$nick]->add_value('l_'.$day.'_night', 1);
			} elseif (preg_match('/^(0[6-9]|1[01])$/', $hour)) {
				$this->l_morning++;
				$this->nicks_objs[$nick]->add_value('l_morning', 1);
				$this->nicks_objs[$nick]->add_value('l_'.$day.'_morning', 1);
			} elseif (preg_match('/^1[2-7]$/', $hour)) {
				$this->l_afternoon++;
				$this->nicks_objs[$nick]->add_value('l_afternoon', 1);
				$this->nicks_objs[$nick]->add_value('l_'.$day.'_afternoon', 1);
			} elseif (preg_match('/^(1[89]|2[0-3])$/', $hour)) {
				$this->l_evening++;
				$this->nicks_objs[$nick]->add_value('l_evening', 1);
				$this->nicks_objs[$nick]->add_value('l_'.$day.'_evening', 1);
			}

			$this->{'l_'.$hour}++;
			$this->l_total++;
			$this->nicks_objs[$nick]->add_value('l_'.$hour, 1);
			$this->nicks_objs[$nick]->add_value('l_total', 1);

			if (strlen($line) <= 255) {
				if (strlen($line) >= $this->quote_preflen) {
					$this->nicks_objs[$nick]->add_quote('quote', 'long', $line);
				} else {
					$this->nicks_objs[$nick]->add_quote('quote', 'short', $line);
				}
			}

			if (strlen($line) >= 2 && strtoupper($line) == $line && strlen(preg_replace('/[A-Z]/', '', $line)) * 2 < strlen($line)) {
				$this->nicks_objs[$nick]->add_value('uppercased', 1);

				if (strlen($line) <= 255) {
					if (strlen($line) >= $this->quote_preflen) {
						$this->nicks_objs[$nick]->add_quote('ex_uppercased', 'long', $line);
					} else {
						$this->nicks_objs[$nick]->add_quote('ex_uppercased', 'short', $line);
					}
				}
			}

			if (preg_match('/!$/', $line)) {
				$this->nicks_objs[$nick]->add_value('exclamations', 1);

				if (strlen($line) <= 255) {
					if (strlen($line) >= $this->quote_preflen) {
						$this->nicks_objs[$nick]->add_quote('ex_exclamations', 'long', $line);
					} else {
						$this->nicks_objs[$nick]->add_quote('ex_exclamations', 'short', $line);
					}
				}
			} elseif (preg_match('/\?$/', $line)) {
				$this->nicks_objs[$nick]->add_value('questions', 1);

				if (strlen($line) <= 255) {
					if (strlen($line) >= $this->quote_preflen) {
						$this->nicks_objs[$nick]->add_quote('ex_questions', 'long', $line);
					} else {
						$this->nicks_objs[$nick]->add_quote('ex_questions', 'short', $line);
					}
				}
			}

			/**
			 * The "words" counter below has no relation with the words which are stored in the database.
			 * It simply counts all character groups separated by whitespace.
			 */
			$words = explode(' ', $line);
			$this->nicks_objs[$nick]->add_value('words', count($words));

			foreach ($words as $csword) {
				if (preg_match('/^(=[])]|;([]()xp]|-\))|:([]\/()\\\>xpd]|-\))|\\\o\/)$/i', $csword)) {
					$this->nicks_objs[$nick]->add_value($this->smileys[strtolower($csword)], 1);
				} elseif (preg_match('/^(www\.|https?:\/\/)/i', $csword)) {
					/**
					 * Put "http://" scheme in front of all URLs beginning with just "www.".
					 */
					$csurl = preg_replace('/^www\./i', 'http://$0', $csword);

					if (strlen($csurl) > 510 || !$this->urltools->validate_url($csurl)) {
						$this->output('notice', 'set_normal(): invalid url: \''.$csword.'\' on line '.$this->linenum);
					} else {
						$csurl = $this->urltools->normalize_url($csurl);
						$this->nicks_objs[$nick]->add_url($csurl, $datetime);
						$this->nicks_objs[$nick]->add_value('urls', 1);
					}
				} elseif ($this->wordtracking && preg_match('/^[a-z]{1,255}$/i', $csword)) {
					/**
					 * To keep it simple we only track words composed of the characters A through Z.
					 * Words consisting of 30+ characters are most likely not real words but then again we're not a dictionary.
					 */
					$this->add_word($csword);
				}
			}
		}
	}

	final protected function set_part($datetime, $csnick)
	{
		if (!$this->validate_nick($csnick)) {
			$this->output('warning', 'set_part(): invalid nick: \''.$csnick.'\' on line '.$this->linenum);
		} else {
			$nick = $this->add_nick($csnick, $datetime);
			$this->nicks_objs[$nick]->add_value('parts', 1);
		}
	}

	final protected function set_quit($datetime, $csnick)
	{
		if (!$this->validate_nick($csnick)) {
			$this->output('warning', 'set_quit(): invalid nick: \''.$csnick.'\' on line '.$this->linenum);
		} else {
			$nick = $this->add_nick($csnick, $datetime);
			$this->nicks_objs[$nick]->add_value('quits', 1);
		}
	}

	final protected function set_slap($datetime, $csnick_performing, $csnick_undergoing)
	{
		if (!$this->validate_nick($csnick_performing)) {
			$this->output('warning', 'set_slap(): invalid "performing" nick: \''.$csnick_performing.'\' on line '.$this->linenum);
		} else {
			$nick_performing = $this->add_nick($csnick_performing, $datetime);
			$this->nicks_objs[$nick_performing]->add_value('slaps', 1);

			if (!is_null($csnick_undergoing)) {
				/**
				 * Clean possible network prefix (psyBNC) from undergoing nick.
				 */
				if (substr_count($csnick_undergoing, '~') + substr_count($csnick_undergoing, '\'') == 1) {
					$this->output('notice', 'set_slap(): cleaning "undergoing" nick: \''.$csnick_undergoing.'\' on line '.$this->linenum);
					$tmp = preg_split('/[~\']/', $csnick_undergoing, 2);
					$csnick_undergoing = $tmp[1];
				}

				if (!$this->validate_nick($csnick_undergoing)) {
					$this->output('warning', 'set_slap(): invalid "undergoing" nick: \''.$csnick_undergoing.'\' on line '.$this->linenum);
				} else {
					/**
					 * Don't pass a time when adding the undergoing nick while it may only be referred to instead of being seen for real.
					 */
					$nick_undergoing = $this->add_nick($csnick_undergoing, null);
					$this->nicks_objs[$nick_undergoing]->add_value('slapped', 1);
				}
			}
		}
	}

	final protected function set_topic($datetime, $csnick, $line)
	{
		if (!$this->validate_nick($csnick)) {
			$this->output('warning', 'set_topic(): invalid nick: \''.$csnick.'\' on line '.$this->linenum);
		} else {
			$nick = $this->add_nick($csnick, $datetime);
			$this->nicks_objs[$nick]->add_value('topics', 1);

			/**
			 * Keep track of every single topic set.
			 */
			if (strlen($line) > 510) {
				$this->output('warning', 'set_topic(): invalid topic: \''.$cstopic.'\' on line '.$this->linenum);
			} else {
				$this->nicks_objs[$nick]->add_topic($line, $datetime);
			}
		}
	}

	/**
	 * Check on syntax and defined lengths. Maximum length should not exceed 255 which is the maximum database field length.
	 */
	final private function validate_nick($csnick)
	{
		if ($csnick != '0' && preg_match('/^[][^{}|\\\`_0-9a-z-]{'.$this->nick_minlen.','.($this->nick_maxlen > 255 ? 255 : $this->nick_maxlen).'}$/i', $csnick)) {
			return true;
		} else {
			return false;
		}
	}

	final public function write_data($mysqli)
	{
		$this->mysqli = $mysqli;

		/**
		 * If there are no nicks there is no data.
		 */
		if (empty($this->nicks_objs)) {
			$this->output('notice', 'write_data(): no data to write to database');
		} else {
			$this->output('notice', 'write_data(): writing data to database');

			/**
			 * Write channel totals to the database.
			 */
			$query = @mysqli_query($this->mysqli, 'select * from `channel` where `date` = \''.mysqli_real_escape_string($this->mysqli, $this->date).'\'') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query);

			if (empty($rows)) {
				$createdquery = $this->create_insert_query(array('l_00', 'l_01', 'l_02', 'l_03', 'l_04', 'l_05', 'l_06', 'l_07', 'l_08', 'l_09', 'l_10', 'l_11', 'l_12', 'l_13', 'l_14', 'l_15', 'l_16', 'l_17', 'l_18', 'l_19', 'l_20', 'l_21', 'l_22', 'l_23', 'l_night', 'l_morning', 'l_afternoon', 'l_evening', 'l_total'));

				if (!is_null($createdquery)) {
					@mysqli_query($this->mysqli, 'insert into `channel` set `date` = \''.mysqli_real_escape_string($this->mysqli, $this->date).'\','.$createdquery) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				}
			} else {
				$result = mysqli_fetch_object($query);
				$createdquery = $this->create_update_query($result, array('date'));

				if (!is_null($createdquery)) {
					@mysqli_query($this->mysqli, 'update `channel` set'.$createdquery.' where `date` = \''.mysqli_real_escape_string($this->mysqli, $this->date).'\'') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				}
			}

			/**
			 * Write user data to the database.
			 */
			foreach ($this->nicks_objs as $nick) {
				$nick->write_data($this->mysqli);
			}

			/**
			 * Write streak data (history) to the database.
			 */
			@mysqli_query($this->mysqli, 'truncate table `streak_history`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			@mysqli_query($this->mysqli, 'insert into `streak_history` set `prevnick` = \''.mysqli_real_escape_string($this->mysqli, $this->prevnick).'\', `streak` = '.$this->streak) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));

			/**
			 * Write word data to the database.
			 */
			foreach ($this->words_objs as $word) {
				$word->write_data($this->mysqli);
			}

			$this->output('notice', 'write_data(): writing completed');
		}
	}
}

?>
