<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2020, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * General parse instructions. This class will be extended by a class with
 * logfile format specific parse instructions.
 */
class parser
{
	use base, queryparts;

	private array $nick_objs = [];
	private array $smileys = [
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
		'=d' => 's_15',
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
		':-p' => 's_26',
		':-d' => 's_27',
		':-(' => 's_28',
		':]' => 's_29',
		'=(' => 's_30',
		'-_-' => 's_31',
		':s' => 's_32',
		':[' => 's_33',
		':\'(' => 's_34',
		':((' => 's_35',
		'o_o' => 's_36',
		';_;' => 's_37',
		'hehe' => 's_38',
		'heh' => 's_39',
		'haha' => 's_40',
		'lol' => 's_41',
		'hmm' => 's_42',
		'wow' => 's_43',
		'meh' => 's_44',
		'ugh' => 's_45',
		'pff' => 's_46',
		'xd' => 's_47',
		'rofl' => 's_48',
		'lmao' => 's_49',
		'huh' => 's_50'];
	private array $topic_objs = [];
	private array $url_objs = [];
	private array $word_objs = [];
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
	private int $l_morning = 0;
	private int $l_night = 0;
	private int $l_total = 0;
	private int $linenum_last_nonempty = 0;
	private int $streak = 0;
	private string $date = '';
	private string $hex_latin1_supplement = '[\x80-\xFF]';
	private string $hex_valid_utf8 = '([\x00-\x7F]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})';
	private string $line_new = '';
	private string $nick_prev = '';
	protected int $linenum = 0;
	protected string $line_prev = '';

	public function __construct(string $date)
	{
		$this->date = $date;
	}

	/**
	 * Create an object of the nick if it doesn't already exist, otherwise update
	 * $csnick. Return the lower case nick for further referencing by the calling
	 * function.
	 */
	private function create_nick(string $time, string $csnick, bool $real = true): string
	{
		$nick = mb_strtolower($csnick);

		if (!array_key_exists($nick, $this->nick_objs)) {
			$this->nick_objs[$nick] = new nick($csnick);
		} else {
			$this->nick_objs[$nick]->set_string('csnick', $csnick);
		}

		/**
		 * $real is false if the nick hasn't actually been seen and was only referenced.
		 */
		if ($real) {
			if ($this->nick_objs[$nick]->get_string('firstseen') === '') {
				$this->nick_objs[$nick]->set_string('firstseen', $this->date.' '.$time);
			}

			$this->nick_objs[$nick]->set_string('lastseen', $this->date.' '.$time);
		}

		return $nick;
	}

	/**
	 * Keep track of every topic set. These are handled (and stored) while
	 * preserving case.
	 */
	private function create_topic(string $time, string $nick, string $topic): void
	{
		if (!array_key_exists($topic, $this->topic_objs)) {
			$this->topic_objs[$topic] = new topic($topic);
		}

		$this->topic_objs[$topic]->add_uses($this->date.' '.$time, $nick);
	}

	/**
	 * Keep track of every URL. These are handled (and stored) while preserving
	 * case (for the parts where it matters, otherwise lower case).
	 */
	private function create_url(string $time, string $nick, array $url_components): void
	{
		$url = $url_components['url'];

		if (!array_key_exists($url, $this->url_objs)) {
			$this->url_objs[$url] = new url($url_components);
		}

		$this->url_objs[$url]->add_uses($this->date.' '.$time, $nick);
	}

	/**
	 * Words are stored in lower case.
	 */
	private function create_word(string $csword, int $length): void
	{
		$word = mb_strtolower($csword, 'UTF-8');

		if (!array_key_exists($word, $this->word_objs)) {
			$this->word_objs[$word] = new word($word);
			$this->word_objs[$word]->set_int('length', $length);
		}

		$this->word_objs[$word]->add_int('total', 1);
	}

	/**
	 * Parser function for gzipped logs. This function requires the zlib extension.
	 */
	public function gzparse_log(string $logfile, int $linenum_start): void
	{
		if (($fp = gzopen($logfile, 'rb')) === false) {
			out::put('critical', 'failed to open gzip file: \''.$logfile.'\'');
		}

		while (($line = gzgets($fp)) !== false) {
			++$this->linenum;

			if ($this->linenum < $linenum_start) {
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
			$this->linenum_last_nonempty = $this->linenum;
			$this->parse_line($line);
			$this->line_prev = $line;
		}

		gzclose($fp);
	}

	/**
	 * Check if a line is valid UTF-8 and convert all non-valid bytes into valid
	 * multibyte UTF-8.
	 */
	private function normalize_line(string $line): string
	{
		if (!preg_match('/^'.$this->hex_valid_utf8.'+$/', $line)) {
			$this->line_new = '';

			while ($line !== '') {
				/**
				 * 1. Match the first valid multibyte character or otherwise a single byte.
				 * 2. Pass it on to rebuild_line() and replace the character with an empty
				 *    string effectively making $line shorter.
				 * 3. Continue until $line is zero bytes in length.
				 */
				$line = preg_replace_callback('/^('.$this->hex_valid_utf8.'|.)/s', [$this, 'rebuild_line'], $line);
			}

			/**
			 * Set $line to the rebuilt $line_new.
			 */
			$line = $this->line_new;
		}

		/**
		 * 1. 0x03 is used for (mIRC) color codes and may be followed by additional
		 *    characters; completely strip all of these.
		 * 2. Replace any and all adjacent tab characters (0x09) with a single space.
		 * 3. Strip all remaining control characters, unused, reserved and unassigned
		 *    code points as well as any surrogates.
		 * 4. Replace any and all adjacent whitespace (including the multibyte no-break
		 *    space, and the line and paragraph separators) with a single space.
		 * 5. Finally, remove spaces at the beginning and end of a line.
		 */
		return preg_replace(['/\x03([0-9]{1,2}(,[0-9]{1,2})?)?/', '/\x09+/', '/\p{C}+/u', '/( |\xC2\xA0|\xE2\x80[\xA8\xA9])+/', '/^ | $/'], ['', ' ', '', ' ', ''], $line);
	}

	/**
	 * Parser function for normal (uncompressed) logs.
	 */
	public function parse_log(string $logfile, int $linenum_start): void
	{
		if (($fp = fopen($logfile, 'rb')) === false) {
			out::put('critical', 'failed to open file: \''.$logfile.'\'');
		}

		while (($line = fgets($fp)) !== false) {
			++$this->linenum;

			if ($this->linenum < $linenum_start) {
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
			$this->linenum_last_nonempty = $this->linenum;
			$this->parse_line($line);
			$this->line_prev = $line;
		}

		fclose($fp);
	}

	/**
	 * Build $line_new consisting only of valid UTF-8 characters.
	 */
	private function rebuild_line(array $matches): string
	{
		$char = $matches[0];

		/**
		 * 1. Valid UTF-8 is passed along unmodified.
		 * 2. Single-byte characters from the Latin-1 Supplement are converted to
		 *    multibyte unicode.
		 * 3. Everything else is converted to the unicode replacement character.
		 */
		if (preg_match('/^'.$this->hex_valid_utf8.'$/', $char)) {
			$this->line_new .= $char;
		} elseif (preg_match('/^'.$this->hex_latin1_supplement.'$/', $char)) {
			$char = preg_replace_callback('/^'.$this->hex_latin1_supplement.'$/', function (array $matches): string {
				return pack('C*', (ord($matches[0]) >> 6) | 0xC0, (ord($matches[0]) & 0x3F) | 0x80);
			}, $char);
			$this->line_new .= $char;
		} else {
			$this->line_new .= "\xEF\xBF\xBD";
		}

		return '';
	}

	protected function set_action(string $time, string $csnick, string $line): void
	{
		if (!$this->validate_nick($csnick)) {
			return;
		}

		$nick = $this->create_nick($time, $csnick);
		$this->nick_objs[$nick]->add_int('actions', 1);
		$this->nick_objs[$nick]->add_quote('ex_actions', $line, mb_strlen($line, 'UTF-8'));
	}

	protected function set_join(string $time, string $csnick): void
	{
		if (!$this->validate_nick($csnick)) {
			return;
		}

		$nick = $this->create_nick($time, $csnick);
		$this->nick_objs[$nick]->add_int('joins', 1);
	}

	protected function set_kick(string $time, string $csnick_performing, string $csnick_undergoing, string $line): void
	{
		if (!$this->validate_nick($csnick_performing) || !$this->validate_nick($csnick_undergoing)) {
			return;
		}

		$nick_performing = $this->create_nick($time, $csnick_performing);
		$nick_undergoing = $this->create_nick($time, $csnick_undergoing);
		$this->nick_objs[$nick_performing]->add_int('kicks', 1);
		$this->nick_objs[$nick_undergoing]->add_int('kicked', 1);
		$this->nick_objs[$nick_performing]->set_string('ex_kicks', $line);
		$this->nick_objs[$nick_undergoing]->set_string('ex_kicked', $line);
	}

	protected function set_mode(string $time, string $csnick_performing, string $csnick_undergoing, string $mode): void
	{
		if (!$this->validate_nick($csnick_performing) || !$this->validate_nick($csnick_undergoing)) {
			return;
		}

		$nick_performing = $this->create_nick($time, $csnick_performing);
		$nick_undergoing = $this->create_nick($time, $csnick_undergoing);

		switch ($mode) {
			case '+o':
				$this->nick_objs[$nick_performing]->add_int('m_op', 1);
				$this->nick_objs[$nick_undergoing]->add_int('m_opped', 1);
				break;
			case '+v':
				$this->nick_objs[$nick_performing]->add_int('m_voice', 1);
				$this->nick_objs[$nick_undergoing]->add_int('m_voiced', 1);
				break;
			case '-o':
				$this->nick_objs[$nick_performing]->add_int('m_deop', 1);
				$this->nick_objs[$nick_undergoing]->add_int('m_deopped', 1);
				break;
			case '-v':
				$this->nick_objs[$nick_performing]->add_int('m_devoice', 1);
				$this->nick_objs[$nick_undergoing]->add_int('m_devoiced', 1);
				break;
		}
	}

	protected function set_nickchange(string $time, string $csnick_performing, string $csnick_undergoing): void
	{
		if (!$this->validate_nick($csnick_performing) || !$this->validate_nick($csnick_undergoing)) {
			return;
		}

		$nick_performing = $this->create_nick($time, $csnick_performing);
		$nick_undergoing = $this->create_nick($time, $csnick_undergoing);
		$this->nick_objs[$nick_performing]->add_int('nickchanges', 1);
	}

	protected function set_normal(string $time, string $csnick, string $line): void
	{
		if (!$this->validate_nick($csnick)) {
			return;
		}

		$nick = $this->create_nick($time, $csnick);
		$line_length = mb_strlen($line, 'UTF-8');
		$this->nick_objs[$nick]->add_int('characters', $line_length);
		$this->nick_objs[$nick]->set_string('lasttalked', $this->date.' '.$time);

		/**
		 * Keep track of monologues.
		 */
		if ($nick !== $this->nick_prev) {
			/**
			 * Current $nick typed a line and $nick_prev's streak is interrupted. Check if
			 * $nick_prev's streak qualifies as a monologue and store it.
			 */
			if ($this->streak >= 5) {
				/**
				 * If the current line count is 0 then $nick_prev might not be known yet (only
				 * seen in previous parse run). It's safe to assume that $nick_prev is a valid
				 * nick as it was set by set_normal() previously. Create an object for it here
				 * so the streak data can be added.
				 */
				if ($this->l_total === 0) {
					$this->create_nick($time, $this->nick_prev, false);
				}

				$this->nick_objs[$this->nick_prev]->add_int('monologues', 1);

				if ($this->streak > $this->nick_objs[$this->nick_prev]->get_int('topmonologue')) {
					$this->nick_objs[$this->nick_prev]->set_int('topmonologue', $this->streak);
				}
			}

			$this->nick_prev = $nick;
			$this->streak = 0;
		}

		++$this->streak;

		/**
		 * Increase line counts for relevant day, part of day, and hour.
		 */
		$day = strtolower(date('D', strtotime($this->date)));
		$hour_leadingzero = substr($time, 0, 2);
		$hour = (int) $hour_leadingzero;

		if ($hour >= 0 && $hour <= 5) {
			++$this->l_night;
			$this->nick_objs[$nick]->add_int('l_'.$day.'_night', 1);
			$this->nick_objs[$nick]->add_int('l_night', 1);
		} elseif ($hour >= 6 && $hour <= 11) {
			++$this->l_morning;
			$this->nick_objs[$nick]->add_int('l_'.$day.'_morning', 1);
			$this->nick_objs[$nick]->add_int('l_morning', 1);
		} elseif ($hour >= 12 && $hour <= 17) {
			++$this->l_afternoon;
			$this->nick_objs[$nick]->add_int('l_'.$day.'_afternoon', 1);
			$this->nick_objs[$nick]->add_int('l_afternoon', 1);
		} elseif ($hour >= 18 && $hour <= 23) {
			++$this->l_evening;
			$this->nick_objs[$nick]->add_int('l_'.$day.'_evening', 1);
			$this->nick_objs[$nick]->add_int('l_evening', 1);
		}

		$this->nick_objs[$nick]->add_int('l_'.$hour_leadingzero, 1);
		$this->nick_objs[$nick]->add_int('l_total', 1);
		++$this->{'l_'.$hour_leadingzero};
		++$this->l_total;

		/**
		 * Words are simply considered character groups separated by whitespace.
		 */
		$words = explode(' ', $line);
		$this->nick_objs[$nick]->add_int('words', count($words));

		/**
		 * $skip_quote controls whether a line may be used as a quote. We flip this
		 * variable to true when a line contains a URL (because those look bad) and when
		 * a line has already been used as a quote of different type (to avoid duplicate
		 * quotes on the stats page).
		 */
		$skip_quote = false;

		foreach ($words as $csword) {
			/**
			 * Strip most common punctuation from the beginning and end of the word before
			 * validating with a light sanity check. We look at single code points for our
			 * letters, no adjacent combination marks. This method of finding words is not
			 * 100% accurate but it's good enough for our use case.
			 */
			if (preg_match('/^["\'(]?(?<csword_trimmed>\p{L}+(-\p{L}+)?)[!?"\'),.:;]?$/u', $csword, $matches)) {
				$csword_trimmed = $matches['csword_trimmed'];
				$word_length = mb_strlen($csword_trimmed, 'UTF-8');

				/**
				 * Words consisting of 30+ characters are most likely not real words.
				 */
				if ($word_length <= 30) {
					$this->create_word($csword_trimmed, $word_length);
				}

				/**
				 * Check for textual user expressions and a couple of smileys.
				 */
				if (preg_match('/^(hehe[he]*|heh|haha[ha]*|lol|hmm+|wow|huh|meh|ugh|pff+|rofl|lmao)$/i', $csword_trimmed)) {
					$smiley_textual = strtolower($csword_trimmed);
					$smiley_textual = preg_replace(['/^hehe[he]+$/', '/^haha[ha]+$/', '/^hmm+$/', '/^pff+$/'], ['hehe', 'haha', 'hmm', 'pff'], $smiley_textual);
					$this->nick_objs[$nick]->add_int($this->smileys[$smiley_textual], 1);
				} elseif (preg_match('/^([xX]D|D:)$/', $csword)) {
					$this->nick_objs[$nick]->add_int($this->smileys[strtolower($csword)], 1);
				}

			/**
			 * Regular expression to check for all remaining smileys we're interested in.
			 */
			} elseif (preg_match('/^(:([][)(pPD\/oOxX\\\|3<>sS]|-[)D\/pP(\\\]|\'\()|;([)(pPD]|-\)|_;)|[:;](\)\)+|\(\(+)|\\\[oO]\/|<3|=[])pP\/\\\D(]|8\)|-[_.]-|[oO][_.][oO])$/', $csword)) {
				$smiley = strtolower($csword);
				$smiley = preg_replace(['/^(:-?|=)[\/\\\]$/', '/^:\)\)\)+$/', '/^:\(\(\(+$/', '/^;\)\)+$/', '/^;\(\(+$/', '/^;d$/', '/^o\.o$/', '/^-\.-$/'], [':/', ':))', ':((', ';)', ';(', ':d', 'o_o', '-_-'], $smiley);
				$this->nick_objs[$nick]->add_int($this->smileys[$smiley], 1);

			/**
			 * Only catch URLs which start with "www.", "http://" or "https://".
			 */
			} elseif (preg_match('/^(www\.|https?:\/\/).+/i', $csword)) {
				$skip_quote = true;

				if (!is_null($url_components = url_tools::get_components($csword))) {
					/**
					 * Track URLs of up to a sensible limit of 512 characters in length.
					 */
					if (strlen($url_components['url']) <= 512) {
						$this->create_url($time, $nick, $url_components);
						$this->nick_objs[$nick]->add_int('urls', 1);
					}
				} else {
					out::put('debug', 'invalid url: \''.$csword.'\' on line '.$this->linenum);
				}
			}
		}

		/**
		 * Upper cased lines may not contain any lower cased characters and must consist
		 * of more than 50% upper cased characters.
		 */
		if (!preg_match('/\p{Ll}/u', $line)) {
			if (mb_strlen(preg_replace('/\P{Lu}+/u', '', $line), 'UTF-8') * 2 > $line_length) {
				$this->nick_objs[$nick]->add_int('uppercased', 1);

				if (!$skip_quote && $line_length >= $this->nick_objs[$nick]->get_int('ex_uppercased_length')) {
					$this->nick_objs[$nick]->set_string('ex_uppercased', $line);
					$this->nick_objs[$nick]->set_int('ex_uppercased_length', $line_length);
					$skip_quote = true;
				}
			}
		}

		if (preg_match('/!$/', $line)) {
			$this->nick_objs[$nick]->add_int('exclamations', 1);

			if (!$skip_quote && $line_length >= $this->nick_objs[$nick]->get_int('ex_questions_length')) {
				$this->nick_objs[$nick]->set_string('ex_questions', $line);
				$this->nick_objs[$nick]->set_int('ex_questions_length', $line_length);
				$skip_quote = true;
			}
		} elseif (preg_match('/\?$/', $line)) {
			$this->nick_objs[$nick]->add_int('questions', 1);

			if (!$skip_quote && $line_length >= $this->nick_objs[$nick]->get_int('ex_exclamations_length')) {
				$this->nick_objs[$nick]->set_string('ex_exclamations', $line);
				$this->nick_objs[$nick]->set_int('ex_exclamations_length', $line_length);
				$skip_quote = true;
			}
		}

		if (!$skip_quote && $line_length >= $this->nick_objs[$nick]->get_int('quote_length')) {
			$this->nick_objs[$nick]->set_string('quote', $line);
			$this->nick_objs[$nick]->set_int('quote_length', $line_length);
		}
	}

	protected function set_part(string $time, string $csnick): void
	{
		if (!$this->validate_nick($csnick)) {
			return;
		}

		$nick = $this->create_nick($time, $csnick);
		$this->nick_objs[$nick]->add_int('parts', 1);
	}

	protected function set_quit(string $time, string $csnick): void
	{
		if (!$this->validate_nick($csnick)) {
			return;
		}

		$nick = $this->create_nick($time, $csnick);
		$this->nick_objs[$nick]->add_int('quits', 1);
	}

	protected function set_slap(string $time, string $csnick_performing, string $csnick_undergoing): void
	{
		if (!$this->validate_nick($csnick_performing)) {
			return;
		}

		$nick_performing = $this->create_nick($time, $csnick_performing);
		$this->nick_objs[$nick_performing]->add_int('slaps', 1);

		/**
		 * Strip possible network prefix (psyBNC) from the "undergoing" nick.
		 */
		if (preg_match('/^.+?[~\'](?<nick_trimmed>.+)$/', $csnick_undergoing, $matches)) {
			out::put('debug', 'cleaning nick: \''.$csnick_undergoing.'\' on line '.$this->linenum);
			$csnick_undergoing = $matches['nick_trimmed'];
		}

		if (!$this->validate_nick($csnick_undergoing)) {
			return;
		}

		/**
		 * The "undergoing" nick is only referenced and might not be real.
		 */
		$nick_undergoing = $this->create_nick($time, $csnick_undergoing, false);
		$this->nick_objs[$nick_undergoing]->add_int('slapped', 1);
	}

	protected function set_topic(string $time, string $csnick, string $line): void
	{
		if (!$this->validate_nick($csnick)) {
			return;
		}

		$nick = $this->create_nick($time, $csnick);
		$this->nick_objs[$nick]->add_int('topics', 1);
		$this->create_topic($time, $nick, $line);
	}

	/**
	 * Syntax check a given nick. Leave unicode constraints mostly up to the server.
	 * Removing characters from the disallowed ones in the regular expression below
	 * can have serious implications throughout the program. (E.g. 0x27).
	 */
	private function validate_nick(string $csnick): bool
	{
		if (preg_match('/^[0-9-]|[\x21-\x2C\x2E\x2F\x3A-\x40\x7E]/', $csnick)) {
			out::put('debug', 'invalid nick: \''.$csnick.'\' on line '.$this->linenum);
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Store everything in the database.
	 */
	public function store_data(): bool
	{
		/**
		 * If there are no nicks there is no data.
		 */
		if (empty($this->nick_objs)) {
			return false;
		}

		/**
		 * Store channel totals.
		 */
		if ($this->l_total !== 0) {
			$queryparts = $this->get_queryparts(['l_00', 'l_01', 'l_02', 'l_03', 'l_04', 'l_05', 'l_06', 'l_07', 'l_08', 'l_09', 'l_10', 'l_11', 'l_12', 'l_13', 'l_14', 'l_15', 'l_16', 'l_17', 'l_18', 'l_19', 'l_20', 'l_21', 'l_22', 'l_23', 'l_night', 'l_morning', 'l_afternoon', 'l_evening', 'l_total']);
			db::query_exec('INSERT INTO channel_activity (date, '.$queryparts['insert_columns'].') VALUES (\''.$this->date.'\', '.$queryparts['insert_values'].') ON CONFLICT (date) DO UPDATE SET '.$queryparts['update_assignments']);
		}

		/**
		 * Store user data. MUST be done before storing topic and URL data.
		 */
		foreach ($this->nick_objs as $nick) {
			$nick->store_data();
		}

		/**
		 * Store topic data.
		 */
		foreach ($this->topic_objs as $topic) {
			$topic->store_data();
		}

		/**
		 * Store URL data.
		 */
		foreach ($this->url_objs as $url) {
			$url->store_data();
		}

		/**
		 * Store word data.
		 */
		foreach ($this->word_objs as $word) {
			$word->store_data();
		}

		/**
		 * Store streak history.
		 */
		if ($this->l_total !== 0) {
			db::query_exec('DELETE FROM streak_history');
			db::query_exec('INSERT INTO streak_history (nick_prev, streak) VALUES (\''.$this->nick_prev.'\', '.$this->streak.')');
		}

		return true;
	}
}
