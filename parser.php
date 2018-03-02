<?php

/**
 * Copyright (c) 2007-2018, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * General parse instructions. This class will be extended by a class with
 * logfile format specific parse instructions.
 */
class parser
{
	use base;

	/**
	 * Variables listed in $settings_list[] can have their default value overridden
	 * in the configuration file.
	 */
	private $date = '';
	private $hex_latin1supplement = '[\x80-\xFF]';
	private $hex_validutf8 = '([\x00-\x7F]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})';
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
	private $l_morning = 0;
	private $l_night = 0;
	private $l_total = 0;
	private $linenum_lastnonempty = 0;
	private $newline = '';
	private $nick_objs = [];
	private $prevnick = '';
	private $settings_list = ['wordtracking' => 'bool'];
	private $smileys = [
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
		';o' => 's_50'];
	private $streak = 0;
	private $topic_objs = [];
	private $url_objs = [];
	private $word_objs = [];
	private $wordtracking = true;
	protected $linenum = 0;
	protected $prevline = '';

	public function __construct($settings)
	{
		/**
		 * If set, override variables listed in $settings_list[].
		 */
		foreach ($this->settings_list as $setting => $type) {
			if (!array_key_exists($setting, $settings)) {
				continue;
			}

			/**
			 * Do some explicit type casting because everything is initially a string.
			 */
			if ($type === 'string') {
				$this->$setting = $settings[$setting];
			} elseif ($type === 'int') {
				if (preg_match('/^\d+$/', $settings[$setting])) {
					$this->$setting = (int) $settings[$setting];
				}
			} elseif ($type === 'bool') {
				if (strtolower($settings[$setting]) === 'true') {
					$this->$setting = true;
				} elseif (strtolower($settings[$setting]) === 'false') {
					$this->$setting = false;
				}
			}
		}
	}

	/**
	 * Create an object of the nick if it doesn't already exist, otherwise update
	 * $csnick. Return the lowercase nick for further referencing by the calling
	 * function.
	 */
	private function add_nick($time, $csnick)
	{
		$nick = strtolower($csnick);

		if (!array_key_exists($nick, $this->nick_objs)) {
			$this->nick_objs[$nick] = new nick($csnick);
		} else {
			$this->nick_objs[$nick]->set_value('csnick', $csnick);
		}

		/**
		 * $time is null if the nick hasn't actually been seen and was only referenced.
		 */
		if (!is_null($time)) {
			if ($this->nick_objs[$nick]->get_value('firstseen') === '') {
				$this->nick_objs[$nick]->set_value('firstseen', $this->date.' '.$time);
			}

			$this->nick_objs[$nick]->set_value('lastseen', $this->date.' '.$time);
		}

		return $nick;
	}

	/**
	 * Keep track of every topic set. These are handled (and stored) while
	 * preserving case.
	 */
	private function add_topic($time, $nick, $topic)
	{
		if (!array_key_exists($topic, $this->topic_objs)) {
			$this->topic_objs[$topic] = new topic($topic);
		}

		$this->topic_objs[$topic]->add_uses($this->date.' '.$time, $nick);
	}

	/**
	 * Keep track of every URL. These are handled (and stored) while preserving
	 * case.
	 */
	private function add_url($time, $nick, $urldata)
	{
		$url = $urldata['url'];

		if (!array_key_exists($url, $this->url_objs)) {
			$this->url_objs[$url] = new url($urldata);
		}

		$this->url_objs[$url]->add_uses($this->date.' '.$time, $nick);
	}

	/**
	 * Words are stored in lower case. Handle UTF-8 conversion appropriately.
	 */
	private function add_word($csword, $length)
	{
		/**
		 * The multibyte strtolower function is significantly slower than its
		 * single-byte counterpart. Only use it if necessary.
		 */
		if (preg_match('/^[\x00-\x7F]+$/', $csword)) {
			$word = strtolower($csword);
		} else {
			$word = mb_strtolower($csword, 'UTF-8');
		}

		if (!array_key_exists($word, $this->word_objs)) {
			$this->word_objs[$word] = new word($word);
			$this->word_objs[$word]->set_value('length', $length);
		}

		$this->word_objs[$word]->add_value('total', 1);
	}

	/**
	 * Create parts of the SQLite3 query.
	 */
	private function get_queryparts($sqlite3, $columns)
	{
		$queryparts = [];

		foreach ($columns as $key) {
			if (is_int($this->$key)) {
				if ($this->$key !== 0) {
					$queryparts['columns'][] = $key;
					$queryparts['values'][] = $this->$key;
					$queryparts['update-assignments'][] = $key.' = '.$key.' + '.$this->$key;
				}
			} elseif (is_string($this->$key)) {
				if ($this->$key !== '') {
					$value = '\''.$sqlite3->escapeString($this->$key).'\'';
					$queryparts['columns'][] = $key;
					$queryparts['values'][] = $value;
					$queryparts['update-assignments'][] = $key.' = '.$value;
				}
			}
		}

		return $queryparts;
	}

	/**
	 * Parser function for gzipped logs. This function requires the zlib extension.
	 */
	public function gzparse_log($logfile, $firstline)
	{
		if (($zp = gzopen($logfile, 'rb')) === false) {
			output::output('critical', __METHOD__.'(): failed to open gzip file: \''.$logfile.'\'');
		}

		output::output('notice', __METHOD__.'(): parsing logfile: \''.$logfile.'\' from line '.$firstline);

		while (!gzeof($zp)) {
			$line = gzgets($zp);
			$this->linenum++;

			if ($this->linenum < $firstline) {
				continue;
			}

			$line = $this->normalize_line($line);

			if ($line === '') {
				continue;
			}

			/**
			 * Pass on the non-empty normalized line to the logfile format specific parser
			 * class extending this class. Remember the number of said line.
			 */
			$this->linenum_lastnonempty = $this->linenum;
			$this->parse_line($line);
			$this->prevline = $line;
		}

		gzclose($zp);
	}

	/**
	 * Checks if a line is valid UTF-8 and convert all non-valid bytes into valid
	 * multibyte UTF-8.
	 */
	private function normalize_line($line)
	{
		if (!preg_match('/^'.$this->hex_validutf8.'+$/', $line)) {
			$this->newline = '';

			while ($line !== '') {
				/**
				 * 1. Match the first valid multibyte character or otherwise a single byte.
				 * 2. Pass it on to rebuild_line() and replace the character with an empty
				 *    string effectively making $line shorter.
				 * 3. Continue until $line is zero bytes in length.
				 */
				$line = preg_replace_callback('/^('.$this->hex_validutf8.'|.)/s', [$this, 'rebuild_line'], $line);
			}

			/**
			 * Set $line to the rebuilt $newline.
			 */
			$line = $this->newline;
		}

		/**
		 * 1. Remove control codes from the Basic Latin (7-bit ASCII) and Latin-1
		 *    Supplement character sets (the latter after conversion to multibyte).
		 *    0x03 is used for (mIRC) color codes and may be followed by additional
		 *    characters; remove those as well.
		 * 2. Replace all possible formations of adjacent tabs and spaces (including
		 *    the multibyte no-break space) with a single space.
		 * 3. Remove whitespace characters at the beginning and end of a line.
		 */
		$line = preg_replace(['/[\x00-\x02\x04-\x08\x0A-\x1F\x7F]|\x03([0-9]{1,2}(,[0-9]{1,2})?)?|\xC2[\x80-\x9F]/S', '/([\x09\x20]|\xC2\xA0)+/S', '/^\x20|\x20$/'], ['', ' ', ''], $line);
		return $line;
	}

	/**
	 * Parser function for normal (uncompressed) logs.
	 */
	public function parse_log($logfile, $firstline)
	{
		if (($fp = fopen($logfile, 'rb')) === false) {
			output::output('critical', __METHOD__.'(): failed to open file: \''.$logfile.'\'');
		}

		output::output('notice', __METHOD__.'(): parsing logfile: \''.$logfile.'\' from line '.$firstline);

		while (!feof($fp)) {
			$line = fgets($fp);
			$this->linenum++;

			if ($this->linenum < $firstline) {
				continue;
			}

			$line = $this->normalize_line($line);

			if ($line === '') {
				continue;
			}

			/**
			 * Pass on the non-empty normalized line to the logfile format specific parser
			 * class extending this class. Remember the number of said line.
			 */
			$this->linenum_lastnonempty = $this->linenum;
			$this->parse_line($line);
			$this->prevline = $line;
		}

		fclose($fp);
	}

	/**
	 * Build a new line consisting of valid UTF-8 from the characters passed along
	 * in $char.
	 */
	private function rebuild_line($matches)
	{
		/**
		 * $char is passed along as the first element of $matches[] (see
		 * preg_replace_callback()).
		 */
		$char = $matches[0];

		/**
		 * 1. Valid UTF-8 is passed along unmodified.
		 * 2. Single-byte characters from the Latin-1 Supplement are converted to
		 *    multibyte unicode.
		 * 3. Everything else is converted to the unicode questionmark sign (commonly
		 *    used to depict unknown characters).
		 */
		if (preg_match('/^'.$this->hex_validutf8.'$/', $char)) {
			$this->newline .= $char;
		} elseif (preg_match('/^'.$this->hex_latin1supplement.'$/', $char)) {
			$char = preg_replace_callback('/^'.$this->hex_latin1supplement.'$/', function ($matches) {
				return pack('C*', (ord($matches[0]) >> 6) | 0xC0, (ord($matches[0]) & 0x3F) | 0x80);
			}, $char);
			$this->newline .= $char;
		} else {
			$this->newline .= "\xEF\xBF\xBD";
		}

		/**
		 * Return an empty string; see normalize_line() for it to make sense.
		 */
		return '';
	}

	protected function set_action($time, $csnick, $line)
	{
		if (!$this->validate_nick($csnick)) {
			output::output('debug', __METHOD__.'(): invalid nick: \''.$csnick.'\' on line '.$this->linenum);
			return;
		}

		$nick = $this->add_nick($time, $csnick);
		$this->nick_objs[$nick]->add_value('actions', 1);

		/**
		 * Track quotes/example lines of up to a sensible limit of 256 characters in
		 * length.
		 */
		if (($line_length = mb_strlen($line, 'UTF-8')) <= 256) {
			$this->nick_objs[$nick]->add_quote('ex_actions', $line, $line_length);
		}
	}

	protected function set_join($time, $csnick)
	{
		if (!$this->validate_nick($csnick)) {
			output::output('debug', __METHOD__.'(): invalid nick: \''.$csnick.'\' on line '.$this->linenum);
			return;
		}

		$nick = $this->add_nick($time, $csnick);
		$this->nick_objs[$nick]->add_value('joins', 1);
	}

	protected function set_kick($time, $csnick_performing, $csnick_undergoing, $line)
	{
		if (!$this->validate_nick($csnick_performing)) {
			output::output('debug', __METHOD__.'(): invalid "performing" nick: \''.$csnick_performing.'\' on line '.$this->linenum);
			return;
		} elseif (!$this->validate_nick($csnick_undergoing)) {
			output::output('debug', __METHOD__.'(): invalid "undergoing" nick: \''.$csnick_undergoing.'\' on line '.$this->linenum);
			return;
		}

		$nick_performing = $this->add_nick($time, $csnick_performing);
		$nick_undergoing = $this->add_nick($time, $csnick_undergoing);
		$this->nick_objs[$nick_performing]->add_value('kicks', 1);
		$this->nick_objs[$nick_undergoing]->add_value('kicked', 1);

		/**
		 * Track kick messages of up to a limit of 307 characters in length. The
		 * majority of IRC servers are within this limit.
		 */
		if (mb_strlen($line, 'UTF-8') <= 307) {
			$this->nick_objs[$nick_performing]->set_value('ex_kicks', $line);
			$this->nick_objs[$nick_undergoing]->set_value('ex_kicked', $line);
		}
	}

	protected function set_mode($time, $csnick_performing, $csnick_undergoing, $mode)
	{
		if (!$this->validate_nick($csnick_performing)) {
			output::output('debug', __METHOD__.'(): invalid "performing" nick: \''.$csnick_performing.'\' on line '.$this->linenum);
			return;
		} elseif (!$this->validate_nick($csnick_undergoing)) {
			output::output('debug', __METHOD__.'(): invalid "undergoing" nick: \''.$csnick_undergoing.'\' on line '.$this->linenum);
			return;
		}

		$nick_performing = $this->add_nick($time, $csnick_performing);
		$nick_undergoing = $this->add_nick($time, $csnick_undergoing);

		switch ($mode) {
			case '+o':
				$this->nick_objs[$nick_performing]->add_value('m_op', 1);
				$this->nick_objs[$nick_undergoing]->add_value('m_opped', 1);
				break;
			case '+v':
				$this->nick_objs[$nick_performing]->add_value('m_voice', 1);
				$this->nick_objs[$nick_undergoing]->add_value('m_voiced', 1);
				break;
			case '-o':
				$this->nick_objs[$nick_performing]->add_value('m_deop', 1);
				$this->nick_objs[$nick_undergoing]->add_value('m_deopped', 1);
				break;
			case '-v':
				$this->nick_objs[$nick_performing]->add_value('m_devoice', 1);
				$this->nick_objs[$nick_undergoing]->add_value('m_devoiced', 1);
				break;
		}
	}

	protected function set_nickchange($time, $csnick_performing, $csnick_undergoing)
	{
		if (!$this->validate_nick($csnick_performing)) {
			output::output('debug', __METHOD__.'(): invalid "performing" nick: \''.$csnick_performing.'\' on line '.$this->linenum);
			return;
		} elseif (!$this->validate_nick($csnick_undergoing)) {
			output::output('debug', __METHOD__.'(): invalid "undergoing" nick: \''.$csnick_undergoing.'\' on line '.$this->linenum);
			return;
		}

		$nick_performing = $this->add_nick($time, $csnick_performing);
		$nick_undergoing = $this->add_nick($time, $csnick_undergoing);
		$this->nick_objs[$nick_performing]->add_value('nickchanges', 1);
	}

	protected function set_normal($time, $csnick, $line)
	{
		if (!$this->validate_nick($csnick)) {
			output::output('debug', __METHOD__.'(): invalid nick: \''.$csnick.'\' on line '.$this->linenum);
			return;
		}

		$nick = $this->add_nick($time, $csnick);
		$line_length = mb_strlen($line, 'UTF-8');
		$this->nick_objs[$nick]->add_value('characters', $line_length);
		$this->nick_objs[$nick]->set_value('lasttalked', $this->date.' '.$time);

		/**
		 * Keep track of monologues.
		 */
		if ($nick !== $this->prevnick) {
			/**
			 * Someone else typed a line and the previous streak is interrupted. Check if
			 * the streak qualifies as a monologue and store it.
			 */
			if ($this->streak >= 5) {
				/**
				 * If the current line count is 0 then $prevnick is not known yet (only seen in
				 * previous parse run). It's safe to assume that $prevnick is a valid nick since
				 * it was set by set_normal(). Create an object for it here so the monologue
				 * data can be added. It doesn't matter if $prevnick is lowercase since it won't
				 * be updated before it is actually seen (i.e. on any other activity).
				 */
				if ($this->l_total === 0) {
					$this->add_nick(null, $this->prevnick);
				}

				$this->nick_objs[$this->prevnick]->add_value('monologues', 1);

				if ($this->streak > $this->nick_objs[$this->prevnick]->get_value('topmonologue')) {
					$this->nick_objs[$this->prevnick]->set_value('topmonologue', $this->streak);
				}
			}

			$this->prevnick = $nick;
			$this->streak = 0;
		}

		$this->streak++;

		/**
		 * Increase line counts for relevant day, part of day, and hour.
		 */
		$day = strtolower(date('D', strtotime($this->date)));
		$hour_leadingzero = substr($time, 0, 2);
		$hour = (int) $hour_leadingzero;

		if ($hour >= 0 && $hour <= 5) {
			$this->l_night++;
			$this->nick_objs[$nick]->add_value('l_'.$day.'_night', 1);
			$this->nick_objs[$nick]->add_value('l_night', 1);
		} elseif ($hour >= 6 && $hour <= 11) {
			$this->l_morning++;
			$this->nick_objs[$nick]->add_value('l_'.$day.'_morning', 1);
			$this->nick_objs[$nick]->add_value('l_morning', 1);
		} elseif ($hour >= 12 && $hour <= 17) {
			$this->l_afternoon++;
			$this->nick_objs[$nick]->add_value('l_'.$day.'_afternoon', 1);
			$this->nick_objs[$nick]->add_value('l_afternoon', 1);
		} elseif ($hour >= 18 && $hour <= 23) {
			$this->l_evening++;
			$this->nick_objs[$nick]->add_value('l_'.$day.'_evening', 1);
			$this->nick_objs[$nick]->add_value('l_evening', 1);
		}

		$this->nick_objs[$nick]->add_value('l_'.$hour_leadingzero, 1);
		$this->nick_objs[$nick]->add_value('l_total', 1);
		$this->{'l_'.$hour_leadingzero}++;
		$this->l_total++;

		/**
		 * Words are simply considered character groups separated by whitespace.
		 */
		$skipquote = false;
		$words = explode(' ', $line);
		$this->nick_objs[$nick]->add_value('words', count($words));

		foreach ($words as $csword) {
			/**
			 * Keep track of all character groups composed of the letters found in the Basic
			 * Latin and Latin-1 Supplement character sets, the Hyphen (used properly), and
			 * any multibyte characters beyond those two sets (found in UTF-8) regardless of
			 * their meaning. The regular expression checks for any characters not wanted in
			 * the word - from the aforementioned Latin sets. Note that normalize_line()
			 * already took all the dirt out. This method of finding words is not 100%
			 * accurate, but it serves its purpose.
			 */
			if ($this->wordtracking && !preg_match('/^-|-$|--|[\x21-\x2C\x2E-\x40\x5B-\x60\x7B-\x7E]|\xC2[\xA1-\xBF]|\xC3\x97|\xC3\xB7|\xEF\xBF\xBD/', $csword)) {
				$word_length = mb_strlen($csword, 'UTF-8');

				/**
				 * Words consisting of 30+ characters are most likely not real words.
				 */
				if ($word_length <= 30) {
					$this->add_word($csword, $word_length);
				}

			/**
			 * Behold the amazing smileys regular expression. Cannot evaluate as a word (see
			 * above).
			 */
			} elseif (preg_match('/^(:([][)(pd\/ox\\\|3<>s]|-[)d\/p(]|\'\()|;([])(pxd\/o]|-\)|_;)|[:;](\)\)|\(\()|\\\o\/|<3|=[])p\/\\\d(x]|d:|8\)|-[_.]-|>:\()$/i', $csword)) {
				$this->nick_objs[$nick]->add_value($this->smileys[strtolower($csword)], 1);

			/**
			 * Only catch URLs which were intended to be clicked on. Most clients can handle
			 * URLs that begin with "www." or a scheme like "http://".
			 */
			} elseif (preg_match('/^(www\.|https?:\/\/)/i', $csword)) {
				/**
				 * Regardless of it being a valid URL or not, set $skipquote to true, which
				 * ensures that lines which contain a URL are not used as a quote. Quotes with
				 * URLs in them often look messy/confusing on the stats page.
				 */
				$skipquote = true;

				if (($urldata = urltools::get_elements($csword)) !== false) {
					/**
					 * Track URLs of up to a sensible limit of 512 characters in length.
					 */
					if (strlen($urldata['url']) <= 512) {
						$this->add_url($time, $nick, $urldata);
						$this->nick_objs[$nick]->add_value('urls', 1);
					}
				} else {
					output::output('debug', __METHOD__.'(): invalid url: \''.$csword.'\' on line '.$this->linenum);
				}
			}
		}

		/**
		 * All quotes and example lines below should be within a sensible limit of 256
		 * characters in length.
		 */
		if ($line_length > 256) {
			$skipquote = true;
		}

		if (!$skipquote) {
			$this->nick_objs[$nick]->add_quote('quote', $line, $line_length);
		}

		/**
		 * Uppercased lines should consist of 2 or more characters, be completely
		 * uppercased, and have less than 50% non-letter characters from the Basic Latin
		 * and Latin-1 Supplement character sets in them.
		 */
		if ($line_length >= 2 && mb_strtoupper($line, 'UTF-8') === $line && mb_strlen(preg_replace('/[\x21-\x40\x5B-\x60\x7B-\x7E]|\xC2[\xA1-\xBF]|\xC3\x97|\xC3\xB7|\xEF\xBF\xBD/S', '', $line), 'UTF-8') * 2 > $line_length) {
			$this->nick_objs[$nick]->add_value('uppercased', 1);

			if (!$skipquote) {
				$this->nick_objs[$nick]->add_quote('ex_uppercased', $line, $line_length);
			}
		}

		if (preg_match('/!$/', $line)) {
			$this->nick_objs[$nick]->add_value('exclamations', 1);

			if (!$skipquote) {
				$this->nick_objs[$nick]->add_quote('ex_exclamations', $line, $line_length);
			}
		} elseif (preg_match('/\?$/', $line)) {
			$this->nick_objs[$nick]->add_value('questions', 1);

			if (!$skipquote) {
				$this->nick_objs[$nick]->add_quote('ex_questions', $line, $line_length);
			}
		}
	}

	protected function set_part($time, $csnick)
	{
		if (!$this->validate_nick($csnick)) {
			output::output('debug', __METHOD__.'(): invalid nick: \''.$csnick.'\' on line '.$this->linenum);
			return;
		}

		$nick = $this->add_nick($time, $csnick);
		$this->nick_objs[$nick]->add_value('parts', 1);
	}

	protected function set_quit($time, $csnick)
	{
		if (!$this->validate_nick($csnick)) {
			output::output('debug', __METHOD__.'(): invalid nick: \''.$csnick.'\' on line '.$this->linenum);
			return;
		}

		$nick = $this->add_nick($time, $csnick);
		$this->nick_objs[$nick]->add_value('quits', 1);
	}

	protected function set_slap($time, $csnick_performing, $csnick_undergoing)
	{
		if (!$this->validate_nick($csnick_performing)) {
			output::output('debug', __METHOD__.'(): invalid "performing" nick: \''.$csnick_performing.'\' on line '.$this->linenum);
			return;
		}

		$nick_performing = $this->add_nick($time, $csnick_performing);
		$this->nick_objs[$nick_performing]->add_value('slaps', 1);

		if (is_null($csnick_undergoing)) {
			return;
		}

		/**
		 * Clean possible network prefix (psyBNC) from the "undergoing" nick.
		 */
		if (preg_match('/^\S+?[~\'](?<nick>\S+)$/', $csnick_undergoing, $matches)) {
			output::output('debug', __METHOD__.'(): cleaning "undergoing" nick: \''.$csnick_undergoing.'\' on line '.$this->linenum);
			$csnick_undergoing = $matches['nick'];
		}

		if (!$this->validate_nick($csnick_undergoing)) {
			output::output('debug', __METHOD__.'(): invalid "undergoing" nick: \''.$csnick_undergoing.'\' on line '.$this->linenum);
			return;
		}

		/**
		 * Don't pass a time when adding the "undergoing" nick while it may only be
		 * referred to instead of being seen for real.
		 */
		$nick_undergoing = $this->add_nick(null, $csnick_undergoing);
		$this->nick_objs[$nick_undergoing]->add_value('slapped', 1);
	}

	protected function set_topic($time, $csnick, $line)
	{
		if (!$this->validate_nick($csnick)) {
			output::output('debug', __METHOD__.'(): invalid nick: \''.$csnick.'\' on line '.$this->linenum);
			return;
		}

		$nick = $this->add_nick($time, $csnick);
		$this->nick_objs[$nick]->add_value('topics', 1);

		/**
		 * Track topics of up to a limit of 390 characters in length. The majority of
		 * IRC servers are within this limit.
		 */
		if (mb_strlen($line, 'UTF-8') <= 390) {
			$this->add_topic($time, $nick, $line);
		}
	}

	/**
	 * Check a nick on syntax and defined lengths. The majority of IRC servers are
	 * within this limit.
	 */
	private function validate_nick($csnick)
	{
		if (preg_match('/^[][^{}|\\\`_a-z][][^{}|\\\`_a-z0-9-]{0,31}$/i', $csnick)) {
			return true;
		} else {
			return false;
		}
	}

	public function write_data($sqlite3)
	{
		/**
		 * If there are no nicks there is no data.
		 */
		if (empty($this->nick_objs)) {
			return false;
		}

		output::output('notice', __METHOD__.'(): writing data to database');
		$sqlite3->exec('BEGIN TRANSACTION') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		/**
		 * Write channel totals to database.
		 */
		if ($this->l_total !== 0) {
			$queryparts = $this->get_queryparts($sqlite3, ['l_00', 'l_01', 'l_02', 'l_03', 'l_04', 'l_05', 'l_06', 'l_07', 'l_08', 'l_09', 'l_10', 'l_11', 'l_12', 'l_13', 'l_14', 'l_15', 'l_16', 'l_17', 'l_18', 'l_19', 'l_20', 'l_21', 'l_22', 'l_23', 'l_night', 'l_morning', 'l_afternoon', 'l_evening', 'l_total']);
			$sqlite3->exec('INSERT OR IGNORE INTO channel_activity (date, '.implode(', ', $queryparts['columns']).') VALUES (\''.$this->date.'\', '.implode(', ', $queryparts['values']).')') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$sqlite3->exec('UPDATE channel_activity SET '.implode(', ', $queryparts['update-assignments']).' WHERE CHANGES() = 0 AND date = \''.$this->date.'\'') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		/**
		 * Write user data to database. User data should be written prior to topic and
		 * URL data.
		 */
		foreach ($this->nick_objs as $nick) {
			$nick->write_data($sqlite3);
		}

		/**
		 * Write topic data to database.
		 */
		foreach ($this->topic_objs as $topic) {
			$topic->write_data($sqlite3);
		}

		/**
		 * Write URL data to database.
		 */
		foreach ($this->url_objs as $url) {
			$url->write_data($sqlite3);
		}

		/**
		 * Write word data to database.
		 */
		foreach ($this->word_objs as $word) {
			$word->write_data($sqlite3);
		}

		/**
		 * Write streak data (history) to database.
		 */
		if ($this->l_total !== 0) {
			$sqlite3->exec('DELETE FROM streak_history') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$sqlite3->exec('INSERT INTO streak_history (prevnick, streak) VALUES (\''.$this->prevnick.'\', '.$this->streak.')') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$sqlite3->exec('COMMIT') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		return true;
	}
}
