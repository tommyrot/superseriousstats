<?php

/**
 * Copyright (c) 2007-2013, Jos de Ruijter <jos@dutnie.nl>
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
	 * Default settings for this script, can be overridden in the config file. These should all appear in $settings_list[] along with their type.
	 */
	private $wordtracking = true;

	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $hex_latin1supplement = '[\x80-\xFF]';
	private $hex_validutf8 = '([\x00-\x7F]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})';
	private $newline = '';
	private $nicks_objs = array();
	private $settings_list = array(
		'outputbits' => 'int',
		'wordtracking' => 'bool');
	private $smileys = array(
		':)' => 's_01',
		';)' => 's_02',
		':(' => 's_03',
		':p' => 's_04',
		':d' => 's_05',
		';(' => 's_06',
		':/' => 's_07',
		'\\o/' => 's_08',
		':))' => 's_09',
		'<3' => 's_10',
		':o' => 's_11',
		'=)' => 's_12',
		':-)' => 's_13',
		':x' => 's_14',
		':\\' => 's_15',
		'd:' => 's_16',
		':|' => 's_17',
		';-)' => 's_18',
		';p' => 's_19',
		'=]' => 's_20',
		':3' => 's_21',
		'8)' => 's_22',
		':<' => 's_23',
		':>' => 's_24',
		'=p' => 's_25',
		';x' => 's_26',
		':-d' => 's_27',
		';))' => 's_28',
		':]' => 's_29',
		';d' => 's_30',
		'-_-' => 's_31',
		':s' => 's_32',
		'=/' => 's_33',
		'=\\' => 's_34',
		':((' => 's_35',
		'=d' => 's_36',
		':-/' => 's_37',
		':-p' => 's_38',
		';_;' => 's_39',
		';/' => 's_40',
		';]' => 's_41',
		':-(' => 's_42',
		':\'(' => 's_43',
		'=(' => 's_44',
		'-.-' => 's_45',
		';((' => 's_46',
		'=x' => 's_47',
		':[' => 's_48',
		'>:(' => 's_49',
		';o' => 's_50');
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
	protected $l_afternoon = 0;
	protected $l_evening = 0;
	protected $l_morning = 0;
	protected $l_night = 0;
	protected $l_total = 0;
	protected $linenum = 0;
	protected $linenum_lastnonempty = 0;
	protected $newdata = false;
	protected $prevline = '';
	protected $prevnick = '';
	protected $streak = 0;

	final public function __construct($settings)
	{
		$this->urltools = new urltools();

		/**
		 * The variables that are listed in $settings_list will have their values overridden by those found in the config file.
		 */
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
	}

	/**
	 * Create an object of the nick if it doesn't already exist. If it does already exist, update $csnick. Return the lowercase nick for further referencing
	 * by the calling function.
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
			if ($this->nicks_objs[$nick]->get_value('firstseen') == '') {
				$this->nicks_objs[$nick]->set_value('firstseen', $datetime);
			}

			$this->nicks_objs[$nick]->set_value('lastseen', $datetime);
		}

		return $nick;
	}

	/**
	 * Words are stored in lower case. Since they can contain UTF-8 encoded characters we should be careful on how to convert words to lower case.
	 */
	final private function add_word($csword, $length)
	{
		/**
		 * The multibyte strtolower function is significantly slower than its single-byte counterpart so we throw in a little if-else statement to check
		 * if its use is needed.
		 */
		if (preg_match('/^[\x00-\x7F]+$/', $csword)) {
			$word = strtolower($csword);
		} else {
			$word = mb_strtolower($csword, 'UTF-8');
		}

		if (!array_key_exists($word, $this->words_objs)) {
			$this->words_objs[$word] = new word($word);
			$this->words_objs[$word]->set_value('length', $length);
		}

		$this->words_objs[$word]->add_value('total', 1);
	}

	/**
	 * Parser function for gzipped logs. The zlib extension must be loaded for this function to work (see sss.php).
	 */
	final public function gzparse_log($logfile, $firstline)
	{
		if (($zp = @gzopen($logfile, 'rb')) === false) {
			$this->output('critical', 'gzparse_log(): failed to open gzip file: \''.$logfile.'\'');
		}

		$this->output('notice', 'gzparse_log(): parsing logfile: \''.$logfile.'\' from line '.$firstline);

		while (!gzeof($zp)) {
			$line = gzgets($zp);
			$this->linenum++;

			if ($this->linenum < $firstline) {
				continue;
			}

			$line = $this->normalize_line($line);

			/**
			 * Pass on the normalized line to the logfile format specific parser class extending this class. Empty lines are ignored. Remember the
			 * line number of the last non empty line so we can store the correct parse history instead of guessing.
			 */
			if (!empty($line)) {
				$this->parse_line($line);
				$this->prevline = $line;
				$this->linenum_lastnonempty = $this->linenum;
			}
		}

		gzclose($zp);
		$this->output('notice', 'gzparse_log(): parsing completed');

		/**
		 * The $newdata variable can be used outside of the parser class to decide whether we want to run additional routines or skip them. Whenever the
		 * $nicks_objs count is zero there won't be any new data because the data we are interested in is always related to one or more valid nicks.
		 */
		if (!empty($this->nicks_objs)) {
			$this->newdata = true;
		}
	}

	/**
	 * Checks if a line is valid UTF-8 and convert all non valid bytes into valid multibyte UTF-8.
	 */
	final private function normalize_line($line)
	{
		if (!preg_match('/^'.$this->hex_validutf8.'+$/', $line)) {
			$this->newline = '';

			while ($line != '') {
				/**
				 * 1. Match the first valid multibyte character or otherwise a single byte.
				 * 2. Pass it on to rebuild_line() and replace the character with an empty string effectively making $line shorter.
				 * 3. Continue until $line is zero bytes in length.
				 */
				$line = preg_replace_callback('/^('.$this->hex_validutf8.'|.)/s', array($this, 'rebuild_line'), $line);
			}

			/*
			 * Set $line to the rebuilt $newline.
			 */
			$line = $this->newline;
		}

		/**
		 * 1. Remove control codes from the Basic Latin (7-bit ASCII) and Latin-1 Supplement character sets (the latter after conversion to multibyte).
		 *    0x03 is used for (mIRC) color codes and may be followed by additional characters; remove those as well.
		 * 2. Replace all possible formations of adjacent spaces and tabs, including the no-break space (multibyte), with a single space.
		 * 3. Remove whitespace characters at the beginning and end of a line.
		 */
		$line = preg_replace(array('/[\x00-\x02\x04-\x08\x0A-\x1F\x7F]|\x03([0-9]{1,2}(,[0-9]{1,2})?)?|\xC2[\x80-\x9F]/', '/([\x09\x20]|\xC2\xA0)+/', '/^\x20|\x20$/'), array('', ' ', ''), $line);
		return $line;
	}

	/**
	 * Parser function for normal logs.
	 */
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

			$line = $this->normalize_line($line);

			/**
			 * Pass on the normalized line to the logfile format specific parser class extending this class. Empty lines are ignored. Remember the
			 * line number of the last non empty line so we can store the correct parse history instead of guessing.
			 */
			if (!empty($line)) {
				$this->parse_line($line);
				$this->prevline = $line;
				$this->linenum_lastnonempty = $this->linenum;
			}
		}

		fclose($fp);
		$this->output('notice', 'parse_log(): parsing completed');

		/**
		 * The $newdata variable can be used outside of the parser class to decide whether we want to run additional routines or skip them. Whenever the
		 * $nicks_objs count is zero there won't be any new data because the data we are interested in is always related to one or more valid nicks.
		 */
		if (!empty($this->nicks_objs)) {
			$this->newdata = true;
		}
	}

	/**
	 * Build a new line consisting of valid UTF-8 from the characters passed along in $char.
	 */
	final private function rebuild_line($matches)
	{
		/**
		 * $char is passed along as the first element of the array $matches (see preg_replace_callback).
		 */
		$char = $matches[0];

		/**
		 * 1. Valid UTF-8 is passed along unmodified.
		 * 2. Single byte characters from the Latin-1 Supplement are converted to multibyte unicode.
		 * 3. Everything else is converted to the unicode questionmark sign (commonly used to depict unknown characters).
		 */
		if (preg_match('/^'.$this->hex_validutf8.'$/', $char)) {
			$this->newline .= $char;
		} elseif (preg_match('/^'.$this->hex_latin1supplement.'$/', $char)) {
			$char = preg_replace_callback('/^'.$this->hex_latin1supplement.'$/', create_function('$matches', 'return pack(\'C*\', (ord($matches[0]) >> 6) | 0xC0, (ord($matches[0]) & 0x3F) | 0x80);'), $char);
			$this->newline .= $char;
		} else {
			$this->newline .= "\xEF\xBF\xBD";
		}

		/**
		 * Returns an empty string; see normalize_line() for it to make sense.
		 */
		return '';
	}

	final protected function set_action($datetime, $csnick, $line)
	{
		if (!$this->validate_nick($csnick)) {
			$this->output('warning', 'set_action(): invalid nick: \''.$csnick.'\' on line '.$this->linenum);
		} else {
			$nick = $this->add_nick($csnick, $datetime);
			$this->nicks_objs[$nick]->add_value('actions', 1);
			$line_length = mb_strlen($line, 'UTF-8');

			/**
			 * Track quotes/example lines of up to a sensible limit of 255 characters in length.
			 */
			if ($line_length <= 255) {
				$this->nicks_objs[$nick]->add_quote('ex_actions', $line, $line_length);
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

			/**
			 * Track kick messages of up to a limit of 307 characters in length. The majority of IRC servers are within this limit.
			 */
			if (mb_strlen($line, 'UTF-8') <= 307) {
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
			$line_length = mb_strlen($line, 'UTF-8');
			$this->nicks_objs[$nick]->add_value('characters', $line_length);
			$this->nicks_objs[$nick]->set_value('lasttalked', $datetime);

			/**
			 * Keeping track of monologues.
			 */
			if ($nick == $this->prevnick) {
				$this->streak++;
			} else {
				/**
				 * Someone else typed a line and the previous streak is interrupted. Check if the streak qualifies as a monologue and store it.
				 */
				if ($this->streak >= 5) {
					/**
					 * If the current line count is 0 then $prevnick is not known to us yet (only seen in previous parse run). It's safe to
					 * assume that $prevnick is a valid nick since it was set by set_normal(). We will create an object for it here so we
					 * can add the monologue data. Don't worry about $prevnick being lowercase, we won't update "user_details" if $prevnick
					 * isn't seen plus $csnick will get a refresh on any other activity.
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
			$hour = (int) substr($datetime, 11, 2);

			if ($hour >= 0 && $hour <= 5) {
				$this->l_night++;
				$this->nicks_objs[$nick]->add_value('l_night', 1);
				$this->nicks_objs[$nick]->add_value('l_'.$day.'_night', 1);
			} elseif ($hour >= 6 && $hour <= 11) {
				$this->l_morning++;
				$this->nicks_objs[$nick]->add_value('l_morning', 1);
				$this->nicks_objs[$nick]->add_value('l_'.$day.'_morning', 1);
			} elseif ($hour >= 12 && $hour <= 17) {
				$this->l_afternoon++;
				$this->nicks_objs[$nick]->add_value('l_afternoon', 1);
				$this->nicks_objs[$nick]->add_value('l_'.$day.'_afternoon', 1);
			} elseif ($hour >= 18 && $hour <= 23) {
				$this->l_evening++;
				$this->nicks_objs[$nick]->add_value('l_evening', 1);
				$this->nicks_objs[$nick]->add_value('l_'.$day.'_evening', 1);
			}

			$this->{'l_'.($hour < 10 ? '0'.$hour : $hour)}++;
			$this->l_total++;
			$this->nicks_objs[$nick]->add_value('l_'.($hour < 10 ? '0'.$hour : $hour), 1);
			$this->nicks_objs[$nick]->add_value('l_total', 1);

			/**
			 * "Words" are simply character groups separated by whitespace.
			 */
			$words = explode(' ', $line);
			$this->nicks_objs[$nick]->add_value('words', count($words));
			$skipquote = false;

			foreach ($words as $csword) {
				/**
				 * Behold the amazing smileys regexp.
				 */
				if (preg_match('/^(:([][)(pd\/ox\\\|3<>s]|-[)d\/p(]|\'\()|;([])(pxd\/o]|-\)|_;)|[:;](\)\)|\(\()|\\\o\/|<3|=[])p\/\\\d(x]|d:|8\)|-[_.]-|>:\()$/i', $csword)) {
					$this->nicks_objs[$nick]->add_value($this->smileys[strtolower($csword)], 1);

				/**
				 * Only catch URLs which were intended to be clicked on; most clients can handle URLs that begin with "www." or "http://" and
				 * such. If we would apply a more liberal approach we are likely to run into filenames (e.g. .py .com), libraries (e.g. .so) and
				 * other words that validate as a URL.
				 */
				} elseif (preg_match('/^(www\.|https?:\/\/)/i', $csword)) {
					/**
					 * Regardless of it being a valid URL or not we set $skipquote to true. This variable enables us to exclude quotes that
					 * have a URL (or something similar looking) in them. This is to safeguard a tidy presentation on the statspage.
					 */
					$skipquote = true;

					if (($urldata = $this->urltools->get_elements($csword)) !== false) {
						/**
						 * Track URLs of up to a (more than) sensible limit of 1024 characters in length.
						 */
						if (strlen($urldata['url']) <= 1024) {
							$this->nicks_objs[$nick]->add_url($urldata, $datetime);
							$this->nicks_objs[$nick]->add_value('urls', 1);
						}
					} else {
						$this->output('debug', 'set_normal(): invalid url: \''.$csword.'\' on line '.$this->linenum);
					}

				/**
				 * We keep track of all character groups composed of the letters found in the Basic Latin and Latin-1 Supplement character sets,
				 * the Hyphen (used properly), and any multibyte characters beyond those two sets (found in UTF-8) regardless of their meaning.
				 * The regexp checks for any characters we don't want in our words - from the aforementioned Latin sets. Keep in mind that
				 * normalize_line() already took all the dirt out. Note that this method of finding words is not 100% accurate - possibly not
				 * even 50% - but it serves our purpose.
				 */
				} elseif ($this->wordtracking && !preg_match('/^-|-$|--|[\x21-\x2C\x2E-\x40\x5B-\x60\x7B-\x7E]|\xC2[\xA1-\xBF]|\xC3\x97|\xC3\xB7|\xEF\xBF\xBD/', $csword)) {
					$word_length = mb_strlen($csword, 'UTF-8');

					/**
					 * Words consisting of 30+ characters are most likely not real words so we skip those.
					 */
					if ($word_length <= 30) {
						$this->add_word($csword, $word_length);
					}
				}
			}

			/**
			 * Track quotes/example lines of up to a sensible limit of 255 characters in length. This applies to all of the types seen below.
			 */
			if (!$skipquote && $line_length <= 255) {
				$this->nicks_objs[$nick]->add_quote('quote', $line, $line_length);
			}

			/**
			 * Uppercased lines should consist of 2 or more characters, be completely uppercased, and have less than 50% non letter characters from
			 * the Basic Latin and Latin-1 Supplement character sets in them.
			 */
			if ($line_length >= 2 && mb_strtoupper($line, 'UTF-8') == $line && mb_strlen(preg_replace('/[\x21-\x40\x5B-\x60\x7B-\x7E]|\xC2[\xA1-\xBF]|\xC3\x97|\xC3\xB7|\xEF\xBF\xBD/', '', $line), 'UTF-8') * 2 > $line_length) {
				$this->nicks_objs[$nick]->add_value('uppercased', 1);

				if (!$skipquote && $line_length <= 255) {
					$this->nicks_objs[$nick]->add_quote('ex_uppercased', $line, $line_length);
				}
			}

			if (preg_match('/!$/', $line)) {
				$this->nicks_objs[$nick]->add_value('exclamations', 1);

				if (!$skipquote && $line_length <= 255) {
					$this->nicks_objs[$nick]->add_quote('ex_exclamations', $line, $line_length);
				}
			} elseif (preg_match('/\?$/', $line)) {
				$this->nicks_objs[$nick]->add_value('questions', 1);

				if (!$skipquote && $line_length <= 255) {
					$this->nicks_objs[$nick]->add_quote('ex_questions', $line, $line_length);
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
				 * Clean possible network prefix (psyBNC) from "undergoing" nick.
				 */
				if (preg_match('/^.*?[~\'](?<nick>.+)$/', $csnick_undergoing, $matches)) {
					$this->output('debug', 'set_slap(): cleaning "undergoing" nick: \''.$csnick_undergoing.'\' on line '.$this->linenum);
					$csnick_undergoing = $matches['nick'];
				}

				if (!$this->validate_nick($csnick_undergoing)) {
					$this->output('warning', 'set_slap(): invalid "undergoing" nick: \''.$csnick_undergoing.'\' on line '.$this->linenum);
				} else {
					/**
					 * Don't pass a time when adding the "undergoing" nick while it may only be referred to instead of being seen for real.
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
			 * Track topics of up to a limit of 390 characters in length. The majority of IRC servers are within this limit.
			 */
			if (mb_strlen($line, 'UTF-8') <= 390) {
				$this->nicks_objs[$nick]->add_topic($line, $datetime);
			}
		}
	}

	/**
	 * Check on syntax and defined lengths. The nick length should not exceed 32 characters which is the maximum database field length. We want to minimize
	 * the key's length to optimize performance. Again, the majority of IRC servers are within this limit.
	 */
	final private function validate_nick($csnick)
	{
		if ($csnick != '0' && preg_match('/^[][^{}|\\\`_0-9a-z-]{1,32}$/i', $csnick)) {
			return true;
		} else {
			return false;
		}
	}

	final public function write_data($sqlite3)
	{
		$this->output('notice', 'write_data(): writing data to database');
		@$sqlite3->exec('BEGIN TRANSACTION') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		/**
		 * Write channel totals to the database.
		 */
		if ($this->l_total != 0) {
			$queryparts = $this->get_queryparts($sqlite3, array('l_00', 'l_01', 'l_02', 'l_03', 'l_04', 'l_05', 'l_06', 'l_07', 'l_08', 'l_09', 'l_10', 'l_11', 'l_12', 'l_13', 'l_14', 'l_15', 'l_16', 'l_17', 'l_18', 'l_19', 'l_20', 'l_21', 'l_22', 'l_23', 'l_night', 'l_morning', 'l_afternoon', 'l_evening', 'l_total'));
			@$sqlite3->exec('INSERT OR IGNORE INTO channel (date, '.implode(', ', $queryparts['columnlist']).') VALUES (\''.$this->date.'\', '.implode(', ', $queryparts['values']).')') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			@$sqlite3->exec('UPDATE channel SET '.implode(', ', $queryparts['update-assignments']).' WHERE CHANGES() = 0 AND date = \''.$this->date.'\'') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		/**
		 * Write user data to the database.
		 */
		foreach ($this->nicks_objs as $nick) {
			$nick->write_data($sqlite3);
		}

		/**
		 * Write word data to the database.
		 */
		foreach ($this->words_objs as $word) {
			$word->write_data($sqlite3);
		}

		/**
		 * Write streak data (history) to the database.
		 */
		if ($this->l_total != 0) {
			@$sqlite3->exec('DELETE FROM streak_history') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			@$sqlite3->exec('INSERT INTO streak_history (prevnick, streak) VALUES (\''.$sqlite3->escapeString($this->prevnick).'\', '.$this->streak.')') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		@$sqlite3->exec('COMMIT') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$this->output('notice', 'write_data(): writing completed');
	}
}

/**
 * Class for handling word data.
 */
final class word extends base
{
	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $word = '';
	protected $length = 0;
	protected $total = 0;

	public function __construct($word)
	{
		$this->word = $word;
	}

	public function write_data($sqlite3)
	{
		/**
		 * Write data to database table "words".
		 */
		$sqlite3->exec('INSERT OR IGNORE INTO words (word, length, total) VALUES (\''.$sqlite3->escapeString($this->word).'\', '.$this->length.', '.$this->total.')') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$sqlite3->exec('UPDATE words SET total = total + '.$this->total.' WHERE CHANGES() = 0 AND word = \''.$sqlite3->escapeString($this->word).'\'') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
	}
}

?>
