<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2025, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * General parse instructions. This class will be extended by a class with
 * logfile format specific parse instructions.
 */
class parser
{
	use common, queryparts, urlparts;

	private array $buddy_tracker = [];
	private array $nick_objs = [];
	private array $smileys = [];
	private array $topics = [];
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
	private string $year = '';
	protected int $linenum = 0;
	protected string $line_prev = '';

	public function __construct(string $date)
	{
		$this->date = $date;
		$this->year = substr($date, 0, 4);

		/**
		 * Apply the parse state if any.
		 */
		$this->apply_vars('parse_state', ['nick_prev', 'streak', 'buddy_tracker']);

		/**
		 * Retrieve smiley mappings from the database.
		 */
		$results = db::query('SELECT sid, smiley FROM smileys');

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			$this->smileys[strtolower($result['smiley'])] = $result['sid'];
		}
	}

	/**
	 * Create an object of the nick if it doesn't already exist, otherwise update
	 * $csnick. Return the lower case nick for further referencing by the calling
	 * function.
	 */
	private function create_nick(string $time, string $csnick, bool $real = true): string
	{
		$nick = mb_strtolower($csnick);

		if (!isset($this->nick_objs[$nick])) {
			$this->nick_objs[$nick] = new nick($csnick);
		} elseif ($real) {
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
	 * URLs are handled (and stored) while preserving case for all relevant parts.
	 */
	private function create_url(string $time, string $nick, array $urlparts): void
	{
		$url = $urlparts['url'];

		if (!isset($this->url_objs[$url])) {
			$this->url_objs[$url] = new url($urlparts);
		}

		$this->url_objs[$url]->add_uses($this->date.' '.$time, $nick);
	}

	/**
	 * Words are stored in lower case.
	 */
	private function create_word(string $csword): void
	{
		$word = mb_strtolower($csword);

		if (!isset($this->word_objs[$word])) {
			$this->word_objs[$word] = new word($word, $this->year);
		}

		$this->word_objs[$word]->add_int('total', 1);
	}

	/**
	 * Check if a line is valid UTF-8 and convert all nonvalid bytes into valid
	 * multibyte UTF-8.
	 */
	private function normalize_line(string $line): string
	{
		if (!preg_match('/^'.$this->hex_valid_utf8.'+$/', $line)) {
			/**
			 * Match every continuous chunk of valid UTF-8 encoded characters and all single
			 * bytes that don't validate as such.
			 */
			preg_match_all('/'.$this->hex_valid_utf8.'+|./n', $line, $matches);

			foreach ($matches[0] as $key => $chunk) {
				/**
				 * If $chunk consists of more than one byte it must be valid, otherwise check
				 * against the valid single-byte range. Valid chunks don't need any treatment.
				 */
				if (strlen($chunk) > 1 || preg_match('/[\x00-\x7F]/', $chunk)) {
					continue;

				/**
				 * Single-byte characters from the Latin-1 Supplement are converted to UTF-8.
				 */
				} elseif (preg_match('/'.$this->hex_latin1_supplement.'/', $chunk)) {
					$matches[0][$key] = preg_replace_callback('/'.$this->hex_latin1_supplement.'/', function (array $matches_callback): string {
						return pack('C*', (ord($matches_callback[0]) >> 6) | 0xC0, (ord($matches_callback[0]) & 0x3F) | 0x80);
					}, $chunk);

				/**
				 * Everything else is converted to the unicode replacement character.
				 */
				} else {
					$matches[0][$key] = "\u{FFFD}";
				}
			}

			$line = implode($matches[0]);
		}

		/**
		 * 1. 0x03 is used for color codes and may be followed by a foreground and/or
		 *    background color separated by a comma; strip all valid combinations, both
		 *    the syntax used by mIRC (fg required) as well as EPIC (fg optional).
		 * 2. Replace any and all adjacent tabs (0x09) with a single space (0x20).
		 * 3. Replace any kind of whitespace or invisible separator with a single space.
		 * 4. Temporarily replace the zero width joiner (ZWJ) with a tab to preserve it.
		 *    ZWJ are used to create certain complex emojis.
		 * 5. Strip invisible formatting indicators and unused code points.
		 * 6. Convert tabs back to ZWJ.
		 * 7. Strip ASCII and Latin-1 control characters (note: Latin-1 was converted).
		 * 8. Reduce multiple adjacent spaces to a single space.
		 * 9. Finally, remove spaces at the beginning and end of a line.
		 */
		return preg_replace(['/\x03(\d{1,2})?(,\d{1,2})?/', '/\x09+/', '/\p{Z}+/u', '/(?<!^|[ \p{C}])\x{200D}(?!$|[ \p{C}])/u', '/[\p{Cf}\p{Co}\p{Cs}\p{Cn}]+/u', '/\x09/', '/\p{Cc}+/u', '/ {2,}/', '/^ | $/'], ['', ' ', ' ', "\t", '', "\u{200D}", '', ' ', ''], $line);
	}

	public function parse_log(string $logfile, int $linenum_start, bool $gzip): void
	{
		if (($fp = call_user_func(($gzip ? 'gz' : 'f').'open', $logfile, 'rb')) === false) {
			out::put('critical', 'failed to open logfile: \''.$logfile.'\'');
		}

		while (($line = call_user_func(($gzip ? 'gz' : 'f').'gets', $fp)) !== false) {
			if (++$this->linenum < $linenum_start) {
				continue;
			}

			if (($line = $this->normalize_line($line)) === '') {
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

		call_user_func(($gzip ? 'gz' : 'f').'close', $fp);
	}

	protected function set_action(string $time, string $csnick, string $line): void
	{
		if (!$this->validate_nick($csnick)) {
			return;
		}

		$nick = $this->create_nick($time, $csnick);
		$this->nick_objs[$nick]->add_int('actions', 1);

		/**
		 * Don't store empty actions (i.e. actions with just the nick).
		 */
		if (str_contains($line, ' ')) {
			$this->nick_objs[$nick]->set_string('ex_actions', $line);
		}
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

		if ($mode === '+o') {
			$this->nick_objs[$nick_performing]->add_int('m_op', 1);
			$this->nick_objs[$nick_undergoing]->add_int('m_opped', 1);
		} elseif ($mode === '+v') {
			$this->nick_objs[$nick_performing]->add_int('m_voice', 1);
			$this->nick_objs[$nick_undergoing]->add_int('m_voiced', 1);
		} elseif ($mode === '-o') {
			$this->nick_objs[$nick_performing]->add_int('m_deop', 1);
			$this->nick_objs[$nick_undergoing]->add_int('m_deopped', 1);
		} elseif ($mode === '-v') {
			$this->nick_objs[$nick_performing]->add_int('m_devoice', 1);
			$this->nick_objs[$nick_undergoing]->add_int('m_devoiced', 1);
		}
	}

	protected function set_nickchange(string $time, string $csnick_performing, string $csnick_undergoing): void
	{
		if (!$this->validate_nick($csnick_performing) || !$this->validate_nick($csnick_undergoing)) {
			return;
		}

		$nick_performing = $this->create_nick($time, $csnick_performing);
		$this->create_nick($time, $csnick_undergoing);
		$this->nick_objs[$nick_performing]->add_int('nickchanges', 1);
	}

	protected function set_normal(string $time, string $csnick, string $line): void
	{
		if (!$this->validate_nick($csnick)) {
			return;
		}

		$nick = $this->create_nick($time, $csnick);
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
		 * Increase line counts for appropriate day, time of day, and hour.
		 */
		$day = strtolower(date('D', strtotime($this->date)));
		$hour_leadingzero = substr($time, 0, 2);
		$hour = (int) $hour_leadingzero;
		$time_of_day = ($hour <= 5 ? 'night' : ($hour <= 11 ? 'morning' : ($hour <= 17 ? 'afternoon' : 'evening')));
		$this->nick_objs[$nick]->add_int('l_'.$day.'_'.$time_of_day, 1);
		$this->nick_objs[$nick]->add_int('l_'.$time_of_day, 1);
		$this->nick_objs[$nick]->add_int('l_'.$hour_leadingzero, 1);
		$this->nick_objs[$nick]->add_int('l_total', 1);
		++$this->{'l_'.$time_of_day};
		++$this->{'l_'.$hour_leadingzero};
		++$this->l_total;

		/**
		 * Count lines typed exclusively between two nicks in a timespan of 5 minutes.
		 */
		$datetime = str_pad($this->date.' '.$time, 19, ':00');
		$datetime_minus_5min = date('Y-m-d H:i:s', strtotime('-5 minutes', strtotime($datetime)));
		$nicks = [];

		foreach ($this->buddy_tracker as $key => [$datetime_buddy, $nick_buddy]) {
			if ($datetime_buddy < $datetime_minus_5min) {
				unset($this->buddy_tracker[$key]);
			} elseif ($nick_buddy !== $nick && !in_array($nick_buddy, $nicks)) {
				$nicks[] = $nick_buddy;
			}
		}

		$this->buddy_tracker[] = [$datetime, $nick];

		if (count($nicks) === 1) {
			$this->nick_objs[$nick]->add_buddy($nicks[0], $time_of_day);
		}

		/**
		 * Stop here if the line is empty. It may have consisted of color codes part of
		 * ASCII or ANSI art, and/or other characters stripped during normalization.
		 */
		if ($line === '') {
			return;
		}

		/**
		 * Words are simply considered character groups separated by whitespace.
		 */
		$words = explode(' ', $line);
		$wordcount = count($words);
		$this->nick_objs[$nick]->add_int('words', $wordcount);
		$this->nick_objs[$nick]->add_int('characters', mb_strlen($line));
		$line_symbolized_urls = $line;

		foreach ($words as $csword) {
			$trailing_smiley = false;

			/**
			 * Strip most common punctuation from the beginning and end of the word before
			 * validating with a light sanity check. We look at single code points for our
			 * letters, no adjacent combination marks. This method of finding words is not
			 * 100% accurate but it's good enough for our use case.
			 */
			if (preg_match('/^["\'(*]?(?<csword_trimmed>\p{L}+(-\p{L}+)?)[!?"\'),.:;*]?$/nu', $csword, $matches)) {
				$csword_trimmed = $matches['csword_trimmed'];
				$this->create_word($csword_trimmed);

				/**
				 * Check for textual user expressions, and smileys that matched the previous
				 * regular expression.
				 */
				if (preg_match('/^(hehe[he]*|heh|haha[ha]*|lol|hmm+|wow|huh|meh|ugh|pff+|rofl|lmao|ahh+|brr+|ole+|omg|bah|doh|duh+|wtf|uhm+|yum|woh+|grr+|ehh+|tsk|ffs|uhh+|yay|uhuh|ahem|woo+t|argh|urgh|whut)$/i', $csword_trimmed)) {
					$smiley_textual = preg_replace(['/^hehe[he]+$/', '/^haha[ha]+$/', '/^hmmm+$/', '/^pfff+$/', '/^ahhh+$/', '/^brrr+$/', '/^olee+$/', '/^duhh+$/', '/^uhmm+$/', '/^wohh+$/', '/^grrr+$/', '/^ehhh+$/', '/^uhhh+$/', '/^wooo+t$/'], ['hehe', 'haha', 'hmm', 'pff', 'ahh', 'brr', 'ole', 'duh', 'uhm', 'woh', 'grr', 'ehh', 'uhh', 'woot'], strtolower($csword_trimmed));
					$this->nick_objs[$nick]->add_smiley($this->smileys[$smiley_textual], 1);
				} elseif (preg_match('/^([xX]D|D:)$/', $csword)) {
					$trailing_smiley = true;
					$this->nick_objs[$nick]->add_smiley($this->smileys[strtolower($csword)], 1);
				}

			/**
			 * Regular expression to check for all remaining smileys we're interested in.
			 */
			} elseif (preg_match('/^(:([][)(pPD\/oOxX\\\|3<>sS]|-[)D\/pP(\\\]|\'\()|;([)(pPD]|-\)|_;)|[:;](\)\)+|\(\(+)|\\\[oO]\/|<3|=[])pP\/\\\D(]|8\)|-[_.]-|[oO][_.][oO])$/', $csword)) {
				$trailing_smiley = true;
				$smiley = preg_replace(['/^(:-?|=)[\/\\\]$/', '/^:\)\)\)+$/', '/^:\(\(\(+$/', '/^;\)\)+$/', '/^;\(\(+$/', '/^;d$/', '/^o\.o$/', '/^-\.-$/'], [':/', ':))', ':((', ';)', ';(', ':d', 'o_o', '-_-'], strtolower($csword));
				$this->nick_objs[$nick]->add_smiley($this->smileys[$smiley], 1);

			/**
			 * Only catch URLs which start with "www.", "http://" or "https://".
			 */
			} elseif (preg_match('/^(www\.|https?:\/\/).+/i', $csword)) {
				/**
				 * Replace URLs (regardless of validity) with a unicode hyperlink symbol so the
				 * line (i.e. $line_symbolized_urls) can be used as a quote without looking bad.
				 */
				$line_symbolized_urls = preg_replace('/(www\.|https?:\/\/)\S+/i', "\u{1F517}", $line_symbolized_urls, 1);

				if (!is_null($urlparts = $this->get_urlparts($csword))) {
					$this->create_url($time, $nick, $urlparts);
					$this->nick_objs[$nick]->add_int('urls', 1);
				} else {
					out::put('debug', 'invalid url: \''.$csword.'\' on line '.$this->linenum);
				}
			}
		}

		/**
		 * Upper cased lines may not contain any lower cased characters and must contain
		 * at least two concurrent upper cased characters.
		 */
		if (!preg_match('/\p{Ll}/u', $line) && preg_match('/\p{Lu}{2}/u', $line)) {
			$this->nick_objs[$nick]->add_int('uppercased', 1);

			if ($wordcount >= 3 || $this->nick_objs[$nick]->get_string('ex_uppercased') === '') {
				$this->nick_objs[$nick]->set_string('ex_uppercased', $line_symbolized_urls);
			}
		}

		/**
		 * Look back one word in lines with a trailing smiley before validating whether
		 * it's an exclamation or question.
		 */
		if ((!$trailing_smiley && str_ends_with($line_symbolized_urls, '!')) || ($trailing_smiley && $wordcount > 1 && preg_match('/! \S+$/', $line_symbolized_urls))) {
			$this->nick_objs[$nick]->add_int('exclamations', 1);

			if ($wordcount >= 3 || $this->nick_objs[$nick]->get_string('ex_exclamations') === '') {
				$this->nick_objs[$nick]->set_string('ex_exclamations', $line_symbolized_urls);
			}
		} elseif ((!$trailing_smiley && str_ends_with($line_symbolized_urls, '?')) || ($trailing_smiley && $wordcount > 1 && preg_match('/\? \S+$/', $line_symbolized_urls))) {
			$this->nick_objs[$nick]->add_int('questions', 1);

			if ($wordcount >= 3 || $this->nick_objs[$nick]->get_string('ex_questions') === '') {
				$this->nick_objs[$nick]->set_string('ex_questions', $line_symbolized_urls);
			}
		}

		if ($wordcount >= 3 || $this->nick_objs[$nick]->get_string('quote') === '') {
			$this->nick_objs[$nick]->set_string('quote', $line_symbolized_urls);
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
		 * Stop here if the "undergoing" nick is empty. This also avoids debug output
		 * from validate_nick() later.
		 */
		if ($csnick_undergoing === '') {
			return;
		}

		/**
		 * Strip possible network prefix (e.g. psyBNC) from the "undergoing" nick.
		 */
		if (preg_match('/^[^~]+~(?<nick_trimmed>.+)$/', $csnick_undergoing, $matches)) {
			out::put('debug', 'trimming nick: \''.$csnick_undergoing.'\' on line '.$this->linenum);
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

		/**
		 * Track non-empty topics in a way that preserves the exact order of occurrence.
		 */
		if (!preg_match('/^ ?$/', $line)) {
			$this->topics[] = [$this->date.' '.$time, $nick, $line];
		}
	}

	/**
	 * Syntax check a given nick. Leave unicode constraints mostly up to the server.
	 * Removing certain characters from the disallowed ones in the expression below
	 * can have negative consequences, because of hardcoded expectations.
	 *
	 * The constraints are as follows:
	 *  - Nicks may NOT start with: 1234567890-
	 *  - Nicks may NOT contain: !"#$%&'()*+,./:;<=>?@~
	 */
	private function validate_nick(string $csnick): bool
	{
		if (preg_match('/^[0-9-]|[!"#$%&\'()*+,.\\/:;<=>?@~]/', $csnick)) {
			out::put('debug', 'invalid nick: \''.$csnick.'\' on line '.$this->linenum);
			return false;
		}

		return true;
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
		 * Store data in database tables "channel_activity" and "parse_state".
		 */
		if ($this->l_total !== 0) {
			$queryparts = $this->get_queryparts(['l_00', 'l_01', 'l_02', 'l_03', 'l_04', 'l_05', 'l_06', 'l_07', 'l_08', 'l_09', 'l_10', 'l_11', 'l_12', 'l_13', 'l_14', 'l_15', 'l_16', 'l_17', 'l_18', 'l_19', 'l_20', 'l_21', 'l_22', 'l_23', 'l_night', 'l_morning', 'l_afternoon', 'l_evening', 'l_total']);
			db::query_exec('INSERT INTO channel_activity (date, '.$queryparts['insert_columns'].') VALUES (\''.$this->date.'\', '.$queryparts['insert_values'].') ON CONFLICT (date) DO UPDATE SET '.$queryparts['update_assignments']);
			db::query_exec('INSERT OR REPLACE INTO parse_state (var, value) VALUES (\'nick_prev\', \''.$this->nick_prev.'\'), (\'streak\', \''.$this->streak.'\'), (\'buddy_tracker\', \''.serialize($this->buddy_tracker).'\')');
		}

		/**
		 * Store user data. MUST be done before storing URL, topic and buddy data.
		 */
		foreach ($this->nick_objs as $nick) {
			$nick->store_data();
		}

		/**
		 * Store URL data.
		 */
		foreach ($this->url_objs as $url) {
			$url->store_data();
		}

		/**
		 * Store data in database table "uid_topics".
		 */
		foreach ($this->topics as [$datetime, $nick, $topic]) {
			db::query_exec('INSERT INTO uid_topics (uid, topic, datetime) VALUES ((SELECT uid FROM uid_details WHERE csnick = \''.$nick.'\'), \''.preg_replace('/\'/', '\'\'', $topic).'\', DATETIME(\''.$datetime.'\'))');
		}

		/**
		 * Store buddy data.
		 */
		foreach ($this->nick_objs as $nick) {
			$nick->store_buddies();
		}

		/**
		 * Store word data.
		 */
		foreach ($this->word_objs as $word) {
			$word->store_data();
		}

		return true;
	}
}
