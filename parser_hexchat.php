<?php

/**
 * Copyright (c) 2012-2015, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Parse instructions for the HexChat logfile format.
 *
 * Line         Format                                                  Notes
 * ---------------------------------------------------------------------------------------------------------------------
 * Normal       <NICK> MSG                                              Skip empty lines.
 * Nickchange   * NICK is now known as NICK
 * Join         * NICK (HOST) has joined CHAN
 * Part         * NICK (HOST) has left CHAN (MSG)                       Part message may be absent, or empty due to
 *                                                                      normalization.
 * Quit         * NICK has quit (MSG)                                   Quit message may be empty due to normalization.
 * Mode         * NICK gives channel operator status to NICK NICK       Only check for ops (channel operator status) and
 *                                                                      voices.
 * Mode         * NICK removes voice from NICK NICK                     "
 * Topic        * NICK has changed the topic to: MSG                    Skip empty topics.
 * Kick         * NICK has kicked NICK from CHAN (MSG)                  Kick message may be empty due to normalization.
 * ---------------------------------------------------------------------------------------------------------------------
 *
 * Notes:
 * - normalize_line() scrubs all lines before passing them on to parse_line().
 * - The way HexChat logs actions is pretty dumb, we can spoof nearly all other line types with our actions. Even
 *   non-chat messages are logged with the same syntax. For this reason we won't parse for actions.
 * - The order of the regular expressions below is irrelevant (current order aims for best performance).
 * - The most common channel prefixes are "#&!+".
 */
class parser_hexchat extends parser
{
	/**
	 * Parse a line for various chat data.
	 */
	protected function parse_line($line)
	{
		/**
		 * "Normal" lines.
		 */
		if (preg_match('/^\S{3} \d{2} (?<time>\d{2}:\d{2}(:\d{2})?) <(?<nick>\S+)> (?<line>.+)$/', $line, $matches)) {
			$this->set_normal($matches['time'], $matches['nick'], $matches['line']);

		/**
		 * "Join" lines.
		 */
		} elseif (preg_match('/^\S{3} \d{2} (?<time>\d{2}:\d{2}(:\d{2})?) \* (?<nick>\S+) \(\S+\) has joined [#&!+]\S+$/', $line, $matches)) {
			$this->set_join($matches['time'], $matches['nick']);

		/**
		 * "Quit" lines.
		 */
		} elseif (preg_match('/^\S{3} \d{2} (?<time>\d{2}:\d{2}(:\d{2})?) \* (?<nick>\S+) has quit \(.*\)$/', $line, $matches)) {
			$this->set_quit($matches['time'], $matches['nick']);

		/**
		 * "Mode" lines.
		 */
		} elseif (preg_match('/^\S{3} \d{2} (?<time>\d{2}:\d{2}(:\d{2})?) \* (?<nick_performing>\S+) (?<modesign>gives|removes) (?<mode>channel operator status|voice) (to|from) (?<nicks_undergoing>\S+( \S+)*)$/', $line, $matches)) {
			$nicks_undergoing = explode(' ', $matches['nicks_undergoing']);

			if ($matches['modesign'] === 'gives') {
				$modesign = '+';
			} else {
				$modesign = '-';
			}

			if ($matches['mode'] === 'channel operator status') {
				$mode = 'o';
			} else {
				$mode = 'v';
			}

			foreach ($nicks_undergoing as $nick_undergoing) {
				$this->set_mode($matches['time'], $matches['nick_performing'], $nick_undergoing, $modesign.$mode);
			}

		/**
		 * "Nickchange" lines.
		 */
		} elseif (preg_match('/^\S{3} \d{2} (?<time>\d{2}:\d{2}(:\d{2})?) \* (?<nick_performing>\S+) is now known as (?<nick_undergoing>\S+)$/', $line, $matches)) {
			$this->set_nickchange($matches['time'], $matches['nick_performing'], $matches['nick_undergoing']);

		/**
		 * "Part" lines.
		 */
		} elseif (preg_match('/^\S{3} \d{2} (?<time>\d{2}:\d{2}(:\d{2})?) \* (?<nick>\S+) \(\S+\) has left [#&!+]\S+( \(.*\))?$/', $line, $matches)) {
			$this->set_part($matches['time'], $matches['nick']);

		/**
		 * "Topic" lines.
		 */
		} elseif (preg_match('/^\S{3} \d{2} (?<time>\d{2}:\d{2}(:\d{2})?) \* (?<nick>\S+) has changed the topic to: (?<line>.+)$/', $line, $matches)) {
			$this->set_topic($matches['time'], $matches['nick'], $matches['line']);

		/**
		 * "Kick" lines.
		 */
		} elseif (preg_match('/^\S{3} \d{2} (?<time>\d{2}:\d{2}(:\d{2})?) \* (?<line>(?<nick_performing>\S+) has kicked (?<nick_undergoing>\S+) from [#&!+]\S+ \(.*\))$/', $line, $matches)) {
			$this->set_kick($matches['time'], $matches['nick_performing'], $matches['nick_undergoing'], $matches['line']);

		/**
		 * Skip everything else.
		 */
		} elseif ($line !== '') {
			output::output('debug', __METHOD__.'(): skipping line '.$this->linenum.': \''.$line.'\'');
		}
	}
}
