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
 * Super Serious Stats
 * Parser.class.php
 *
 * General parse instructions. This class will be extended by a class with logfile format specific parse instructions.
 */

abstract class Parser extends Parser_MySQL
{
	/**
	 * Don't change any of the default settings below unless you know what you're doing!
	 * Documented changes can be made from the startup script.
	 */
	private $maxStreak = 5;
	private $nick_minLen = 1;
	private $nick_maxLen = 15;
	private $host_maxLen = 255;
	private $URL_maxLen = 255;
	private $quote_minLen = 25;
	private $quote_maxLen = 120;
	private $outputLevel = 1;
	private $wordTracking = FALSE;

	// Default percentages used by the randomizer.
	private $random_actions = 50;
	private $random_exclamations = 40;
	private $random_questions = 20;
	private $random_quote = 5;
	private $random_uppercased = 50;

	// Variables used in database table "channel".
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

	// Variables used in database table "words".
	protected $words_list = array();
	protected $words_objs = array();

	// Other variables.
	protected $nicks_list = array();
	protected $nicks_objs = array();
	protected $prevLine = '';
	protected $lineNum = 0;
	private $URLTools;
	private $streak = 0;
	private $prevNick = '';
	private $prevOutput = array();
	private $smileys = array('=]' => 's_01'
				,'=)' => 's_02'
				,';x' => 's_03'
				,';p' => 's_04'
				,';]' => 's_05'
				,';-)' => 's_06'
				,';)' => 's_07'
				,';(' => 's_08'
				,':x' => 's_09'
				,':p' => 's_10'
				,':d' => 's_11'
				,':>' => 's_12'
				,':]' => 's_13'
				,':\\' => 's_14'
				,':/' => 's_15'
				,':-)' => 's_16'
				,':)' => 's_17'
				,':(' => 's_18'
				,'\\o/' => 's_19');

	final public function __construct()
	{
		$this->URLTools = new URLTools();
	}

	// Function to change and set variables. Used only from the startup script.
	final public function setValue($var, $value)
	{
		$this->$var = $value;
	}

	// Output given messages to the console.
	final protected function output($type, $msg)
	{
		// Don't output the same thing twice, like mode errors and repeated lines.
		if (!in_array($msg, $this->prevOutput)) {
			switch ($type) {
				case 'notice':
					if ($this->outputLevel >= 3)
						echo '  Notice ['.date('H:i.s').'] '.$msg."\n";
					break;
				case 'warning':
					if ($this->outputLevel >= 2)
						echo ' Warning ['.date('H:i.s').'] '.$msg."\n";
					break;
				case 'critical':
					if ($this->outputLevel >= 1)
						echo 'Critical ['.date('H:i.s').'] '.$msg."\n";
					exit;
			}

			$this->prevOutput[] = $msg;
		}
	}

	// Main function with general parse instructions.
	final public function parseLog($logfile)
	{
		if (file_exists($logfile)) {
			$fp = @fopen($logfile, 'rb');

			if ($fp) {
				$this->output('notice', 'parseLog(): parsing logfile: \''.$logfile.'\'');

				while (!feof($fp)) {
					$line = fgets($fp);

					/**
					 * Normalize the line:
					 * 1. Remove ISO-8859-1 control codes: characters x00 to x1F (except x09) and x7F to x9F. Treat x03 differently since it is used for (mIRC) color codes.
					 * 2. Remove multiple adjacent spaces (x20) and all tabs (x09).
					 * 3. Remove whitespaces at the beginning and end of a line.
					 */
					$line = preg_replace(array('/[\x00-\x02\x04-\x08\x0A-\x1F\x7F-\x9F]|\x03([0-9]{1,2}(,[0-9]{1,2})?)?/', '/\x09[\x09\x20]*|\x20[\x09\x20]+/', '/^\x20|\x20$/'), array('', ' ', ''), $line);

					// Pass on the normalized line to the logfile format specific parser class extending this class.
					$this->lineNum++;
					$this->parseLine($line);
					$this->prevLine = $line;
				}

				fclose($fp);
				$this->output('notice', 'parseLog(): parsing completed');
			} else
				$this->output('critical', 'parseLog(): failed to open file: \''.$logfile.'\'');
		} else
			$this->output('critical', 'parseLog(): no such file: \''.$logfile.'\'');
	}

	/**
	 * Validate a given nick.
	 * Check on syntax and variable length.
	 */
	final private function validateNick($csNick)
	{
		if (preg_match('/^[]\[\^\{}\|\\\`_0-9a-z-]{'.$this->nick_minLen.','.$this->nick_maxLen.'}$/i', $csNick))
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * Validate a given host.
	 * Only check for length since standards don't apply to network specific spoofed hosts.
	 * These are needed for nicklinking however.
	 */
	final private function validateHost($csHost)
	{
		if (strlen($csHost) <= $this->host_maxLen)
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * Validate a given quote.
	 * Since all non printable ISO-8859-1 characters are stripped from the lines we can suffice with checking on min and max length.
	 */
	final private function validateQuote($line)
	{
		if (strlen($line) >= $this->quote_minLen && strlen($line) <= $this->quote_maxLen)
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * Validate a given URL.
	 * The max length variable is used to keep stored URLs within boundaries of our database field.
	 * URL validation is done by an external class.
	 */
	final private function validateURL($csURL)
	{
		if (strlen($csURL) <= $this->URL_maxLen && $this->URLTools->validateURL($csURL))
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * An $int percent chance to return TRUE.
	 * Used for making the selection of quotes more dynamic.
	 */
	final private function random($int)
	{
		if (mt_rand(1, 100) <= $int)
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * Function for the global word tracking. Word tracking can be turned off by setting $wordTracking to FALSE from the startup script.
	 * Due to the in_array() function being shitty inefficient, searching large arrays is a big performance hit.
	 */
	final private function addWord($csWord)
	{
		$word = strtolower($csWord);

		if (!in_array($word, $this->words_list)) {
			$this->words_list[] = $word;
			$this->words_objs[$word] = new Word($word);
		}

		$this->words_objs[$word]->addValue('total', 1);
	}

	/**
	 * Create an object of the nick if it doesn't already exist.
	 * Return the lowercase nick for further referencing by the calling function.
	 */
	final private function addNick($csNick, $dateTime)
	{
		$nick = strtolower($csNick);

		if (!in_array($nick, $this->nicks_list)) {
			$this->nicks_list[] = $nick;
			$this->nicks_objs[$nick] = new Nick($csNick);
		} else
			$this->nicks_objs[$nick]->setValue('csNick', $csNick);

		if (!is_null($dateTime))
			$this->nicks_objs[$nick]->lastSeen($dateTime);

		return $nick;
	}

	// Do stuff with "normal" lines data.
	final protected function setNormal($dateTime, $csNick, $line)
	{
		if ($this->validateNick($csNick)) {
			$nick = $this->addNick($csNick, $dateTime);
			$this->nicks_objs[$nick]->lastTalked($dateTime);
			$this->nicks_objs[$nick]->setValue('activeDays', 1);
			$this->nicks_objs[$nick]->addValue('characters', strlen($line));

			if ($nick == $this->prevNick)
				$this->streak++;
			else {
				if ($this->streak >= $this->maxStreak) {
					$this->nicks_objs[$this->prevNick]->addValue('monologues', 1);

					if ($this->streak > $this->nicks_objs[$this->prevNick]->getValue('topMonologue'))
						$this->nicks_objs[$this->prevNick]->setValue('topMonologue', $this->streak);
				}

				$this->streak = 1;
				$this->prevNick = $nick;
			}

			$hour = substr($dateTime, 11, 2);

			if (preg_match('/^0[0-5]$/', $hour)) {
				$this->l_night++;
				$this->nicks_objs[$nick]->addValue('l_night', 1);
				$this->nicks_objs[$nick]->addValue('l_'.DAY.'_night', 1);
			} elseif (preg_match('/^(0[6-9]|1[01])$/', $hour)) {
				$this->l_morning++;
				$this->nicks_objs[$nick]->addValue('l_morning', 1);
				$this->nicks_objs[$nick]->addValue('l_'.DAY.'_morning', 1);
			} elseif (preg_match('/^1[2-7]$/', $hour)) {
				$this->l_afternoon++;
				$this->nicks_objs[$nick]->addValue('l_afternoon', 1);
				$this->nicks_objs[$nick]->addValue('l_'.DAY.'_afternoon', 1);
			} elseif (preg_match('/^(1[89]|2[0-3])$/', $hour)) {
				$this->l_evening++;
				$this->nicks_objs[$nick]->addValue('l_evening', 1);
				$this->nicks_objs[$nick]->addValue('l_'.DAY.'_evening', 1);
			}

			$this->{'l_'.$hour}++;
			$this->l_total++;
			$this->nicks_objs[$nick]->addValue('l_'.$hour, 1);
			$this->nicks_objs[$nick]->addValue('l_total', 1);
			$validateQuote = $this->validateQuote($line);

			if (strlen($line) >= 2 && strtoupper($line) == $line && strlen(preg_replace('/[A-Z]/', '', $line)) * 2 < strlen($line)) {
				$this->nicks_objs[$nick]->addValue('uppercased', 1);

				if ($validateQuote && $this->random($this->random_uppercased))
					$this->nicks_objs[$nick]->setValue('ex_uppercased', $line);
			}

			if (preg_match('/!$/', $line)) {
				$this->nicks_objs[$nick]->addValue('exclamations', 1);

				if ($validateQuote && $this->random($this->random_exclamations))
					$this->nicks_objs[$nick]->setValue('ex_exclamations', $line);
			} elseif (preg_match('/\?$/', $line)) {
				$this->nicks_objs[$nick]->addValue('questions', 1);

				if ($validateQuote && $this->random($this->random_questions))
					$this->nicks_objs[$nick]->setValue('ex_questions', $line);
			}

			if ($validateQuote && $this->random($this->random_quote))
				$this->nicks_objs[$nick]->setValue('quote', $line);

			$lineParts = explode(' ', $line);

			foreach ($lineParts as $csWord) {
				/**
				 * The "words" counter below has no relation with the real words which are stored in the database.
				 * It simply counts all character groups seperated by whitespace.
				 */
				$this->nicks_objs[$nick]->addValue('words', 1);

				if (preg_match('/^(=[]\)]|;([]\(\)xp]|-\))|:([]\/\(\)\\\>xpd]|-\))|\\\o\/)$/i', $csWord))
					$this->nicks_objs[$nick]->addValue($this->smileys[strtolower($csWord)], 1);
				elseif (preg_match('/^(www\.|https?:\/\/)/i', $csWord)) {
					// Put "http://" scheme in front of all URLs beginning with just "www.".
					$csURL = preg_replace('/^www\./i', 'http://$0', $csWord);

					if ($this->validateURL($csURL)) {
						$csURL = $this->URLTools->normalizeURL($csURL);
						$this->nicks_objs[$nick]->addURL($csURL, $dateTime);
						$this->nicks_objs[$nick]->addValue('URLs', 1);
					} else
						$this->output('notice', 'setNormal(): invalid URL: \''.$csWord.'\' on line '.$this->lineNum);
				} elseif ($this->wordTracking && preg_match('/^[a-z]{1,255}$/i', $csWord))
					/**
					 * To keep it simple we only track words composed of the characters A through Z.
					 * Words of 30+ characters are most likely not real words but then again we're not a dictionary.
					 */
					$this->addWord($csWord);
			}
		} else
			$this->output('warning', 'setNormal(): invalid nick: \''.$csNick.'\' on line '.$this->lineNum);
	}

	// Do stuff with "mode" lines data.
	final protected function setMode($dateTime, $csNick_performing, $csNick_undergoing, $mode, $csHost)
	{
		if ($this->validateNick($csNick_performing)) {
			if ($this->validateNick($csNick_undergoing)) {
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

				if (!is_null($csHost))
					if ($this->validateHost($csHost))
						$this->nicks_objs[$nick_performing]->addHost($csHost);
					else
						$this->output('warning', 'setMode(): invalid host: \''.$csHost.'\' on line '.$this->lineNum);
			} else
				$this->output('warning', 'setMode(): invalid "undergoing" nick: \''.$csNick_undergoing.'\' on line '.$this->lineNum);
		} else
			$this->output('warning', 'setMode(): invalid "performing" nick: \''.$csNick_performing.'\' on line '.$this->lineNum);
	}

	// Do stuff with "join" lines data.
	final protected function setJoin($dateTime, $csNick, $csHost)
	{
		if ($this->validateNick($csNick)) {
			$nick = $this->addNick($csNick, $dateTime);
			$this->nicks_objs[$nick]->addValue('joins', 1);

			if ($this->validateHost($csHost))
				$this->nicks_objs[$nick]->addHost($csHost);
			else
				$this->output('warning', 'setJoin(): invalid host: \''.$csHost.'\' on line '.$this->lineNum);
		} else
			$this->output('warning', 'setJoin(): invalid nick: \''.$csNick.'\' on line '.$this->lineNum);
	}

	// Do stuff with "part" lines data.
	final protected function setPart($dateTime, $csNick, $csHost)
	{
		if ($this->validateNick($csNick)) {
			$nick = $this->addNick($csNick, $dateTime);
			$this->nicks_objs[$nick]->addValue('parts', 1);

			if ($this->validateHost($csHost))
				$this->nicks_objs[$nick]->addHost($csHost);
			else
				$this->output('warning', 'setPart(): invalid host: \''.$csHost.'\' on line '.$this->lineNum);
		} else
			$this->output('warning', 'setPart(): invalid nick: \''.$csNick.'\' on line '.$this->lineNum);
	}

	// Do stuff with "quit" lines data.
	final protected function setQuit($dateTime, $csNick, $csHost)
	{
		if ($this->validateNick($csNick)) {
			$nick = $this->addNick($csNick, $dateTime);
			$this->nicks_objs[$nick]->addValue('quits', 1);

			if ($this->validateHost($csHost))
				$this->nicks_objs[$nick]->addHost($csHost);
			else
				$this->output('warning', 'setQuit(): invalid host: \''.$csHost.'\' on line '.$this->lineNum);
		} else
			$this->output('warning', 'setQuit(): invalid nick: \''.$csNick.'\' on line '.$this->lineNum);
	}

	// Do stuff with "kick" lines data.
	final protected function setKick($dateTime, $csNick_performing, $csNick_undergoing, $line)
	{
		if ($this->validateNick($csNick_performing)) {
			if ($this->validateNick($csNick_undergoing)) {
				$nick_performing = $this->addNick($csNick_performing, $dateTime);
				$nick_undergoing = $this->addNick($csNick_undergoing, $dateTime);
				$this->nicks_objs[$nick_performing]->addValue('kicks', 1);
				$this->nicks_objs[$nick_undergoing]->addValue('kicked', 1);

				if ($this->validateQuote($line)) {
					$this->nicks_objs[$nick_performing]->setValue('ex_kicks', $line);
					$this->nicks_objs[$nick_undergoing]->setValue('ex_kicked', $line);
				}
			} else
				$this->output('warning', 'setKick(): invalid "undergoing" nick: \''.$csNick_undergoing.'\' on line '.$this->lineNum);
		} else
			$this->output('warning', 'setKick(): invalid "performing" nick: \''.$csNick_performing.'\' on line '.$this->lineNum);
	}

	// Do stuff with "nickchange" lines data.
	final protected function setNickchange($dateTime, $csNick_performing, $csNick_undergoing)
	{
		if ($this->validateNick($csNick_performing)) {
			if ($this->validateNick($csNick_undergoing)) {
				$nick_performing = $this->addNick($csNick_performing, $dateTime);
				$nick_undergoing = $this->addNick($csNick_undergoing, $dateTime);
				$this->nicks_objs[$nick_performing]->addValue('nickchanges', 1);
			} else
				$this->output('warning', 'setNickchange(): invalid "undergoing" nick: \''.$csNick_undergoing.'\' on line '.$this->lineNum);
		} else
			$this->output('warning', 'setNickchange(): invalid "performing" nick: \''.$csNick_performing.'\' on line '.$this->lineNum);
	}

	// Do stuff with "topic" lines data.
	final protected function setTopic($dateTime, $csNick, $csHost, $line)
	{
		if ($this->validateNick($csNick)) {
			$nick = $this->addNick($csNick, $dateTime);
			$this->nicks_objs[$nick]->addValue('topics', 1);

			if (!is_null($csHost))
				if ($this->validateHost($csHost))
					$this->nicks_objs[$nick]->addHost($csHost);
				else
					$this->output('warning', 'setTopic(): invalid host: \''.$csHost.'\' on line '.$this->lineNum);

			// Keep track of every single topic set.
			if (!is_null($line))
				$this->nicks_objs[$nick]->addTopic($line, $dateTime);
		} else
			$this->output('warning', 'setTopic(): invalid nick: \''.$csNick.'\' on line '.$this->lineNum);
	}

	// Do stuff with "action" lines data.
	final protected function setAction($dateTime, $csNick, $line)
	{
		if ($this->validateNick($csNick)) {
			$nick = $this->addNick($csNick, $dateTime);
			$this->nicks_objs[$nick]->addValue('actions', 1);

			if ($this->validateQuote($line) && $this->random($this->random_actions))
				$this->nicks_objs[$nick]->setValue('ex_actions', $line);
		} else
			$this->output('warning', 'setAction(): invalid nick: \''.$csNick.'\' on line '.$this->lineNum);
	}

	// Do stuff with "slap" lines data.
	final protected function setSlap($dateTime, $csNick_performing, $csNick_undergoing)
	{
		if ($this->validateNick($csNick_performing)) {
			$nick_performing = $this->addNick($csNick_performing, $dateTime);
			$this->nicks_objs[$nick_performing]->addValue('slaps', 1);

			if (!is_null($csNick_undergoing)) {
				// Clean possible network prefix (psyBNC) from undergoing nick.
				if (substr_count($csNick_undergoing, '~') + substr_count($csNick_undergoing, '\'') == 1) {
					$this->output('notice', 'setSlap(): cleaning "undergoing" nick: \''.$csNick_undergoing.'\' on line '.$this->lineNum);
					$tmp = preg_split('/[~\']/', $csNick_undergoing, 2);
					$csNick_undergoing = $tmp[1];
				}

				if ($this->validateNick($csNick_undergoing)) {
					// Don't pass a time when adding the undergoing nick while it may only be referred to instead of being seen for real.
					$dateTime = NULL;
					$nick_undergoing = $this->addNick($csNick_undergoing, $dateTime);
					$this->nicks_objs[$nick_undergoing]->addValue('slapped', 1);
				} else
					$this->output('warning', 'setSlap(): invalid "undergoing" nick: \''.$csNick_undergoing.'\' on line '.$this->lineNum);
			}
		} else
			$this->output('warning', 'setSlap(): invalid "performing" nick: \''.$csNick_performing.'\' on line '.$this->lineNum);
	}
}

?>
