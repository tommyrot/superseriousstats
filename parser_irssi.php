<?php

/**
 * Copyright (c) 2009-2014, Jos de Ruijter <jos@dutnie.nl>
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
 * Parse instructions for the Irssi logfile format.
 *
 * Line         Format                                                  Notes
 * ---------------------------------------------------------------------------------------------------------------------
 * Normal       <NICK> MSG                                              Skip empty lines.
 * Action       * NICK MSG                                              Skip empty actions.
 * Slap         * NICK slaps MSG                                        Slaps may lack a (valid) target.
 * Nickchange   -!- NICK is now known as NICK
 * Join         -!- NICK [HOST] has joined CHAN
 * Part         -!- NICK [HOST] has left CHAN [MSG]                     Part message may be absent, or empty due to
 *                                                                      normalization.
 * Quit         -!- NICK [HOST] has quit [MSG]                          Quit message may be empty due to normalization.
 * Mode         -!- mode/CHAN [+o-v NICK NICK] by NICK                  Only check for combinations of ops (+o) and
 *                                                                      voices (+v).
 * Mode         -!- ServerMode/CHAN [+o-v NICK NICK] by NICK            "
 * Topic        -!- NICK changed the topic of CHAN to: MSG              Skip empty topics.
 * Kick         -!- NICK was kicked from CHAN by NICK [MSG]             Kick message may be empty due to normalization.
 * ---------------------------------------------------------------------------------------------------------------------
 *
 * Notes:
 * - normalize_line() scrubs all lines before passing them on to parse_line().
 * - Given that nicks can't contain "/" or any of the channel prefixes, the order of the regular expressions below is
 *   irrelevant (current order aims for best performance).
 * - We have to be mindful that nicks can contain "[" and "]".
 * - The most common channel prefixes are "#&!+" and the most common nick prefixes are "~&@%+!*". If one of the nick
 *   prefixes slips through then validate_nick() will fail.
 * - Irssi may log multiple "performing" nicks in "mode" lines separated by commas. We use only the first one.
 * - In certain cases $matches[] won't contain index items if these optionally appear at the end of a line. We use
 *   empty() to check whether an index item is both set and has a value.
 */
final class parser_irssi extends parser
{
	/**
	 * Parse a line for various chat data.
	 */
	protected function parse_line($line)
	{
		/**
		 * "Normal" lines.
		 */
		if (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) <[\x20~&@%+!*]?(?<nick>\S+)> (?<line>.+)$/', $line, $matches)) {
			$this->set_normal($this->date.' '.$matches['time'], $matches['nick'], $matches['line']);

		/**
		 * "Join" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) -!- (?<nick>\S+) \[\S+\] has joined [#&!+]\S+$/', $line, $matches)) {
			$this->set_join($this->date.' '.$matches['time'], $matches['nick']);

		/**
		 * "Quit" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) -!- (?<nick>\S+) \[\S+\] has quit \[.*\]$/', $line, $matches)) {
			$this->set_quit($this->date.' '.$matches['time'], $matches['nick']);

		/**
		 * "Mode" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) -!- (ServerMode|mode)\/[#&!+]\S+ \[(?<modes>[-+][ov]+([-+][ov]+)?) (?<nicks_undergoing>\S+( \S+)*)\] by (?<nick_performing>\S+)(, \S+)*$/', $line, $matches)) {
			$modenum = 0;
			$nicks_undergoing = explode(' ', $matches['nicks_undergoing']);

			for ($i = 0, $j = strlen($matches['modes']); $i < $j; $i++) {
				$mode = substr($matches['modes'], $i, 1);

				if ($mode === '-' || $mode === '+') {
					$modesign = $mode;
				} else {
					$this->set_mode($this->date.' '.$matches['time'], $matches['nick_performing'], $nicks_undergoing[$modenum], $modesign.$mode);
					$modenum++;
				}
			}

		/**
		 * "Action" and "slap" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) \* (?<line>(?<nick_performing>\S+) ((?<slap>[sS][lL][aA][pP][sS]( (?<nick_undergoing>\S+)( .+)?)?)|(.+)))$/', $line, $matches)) {
			if (!empty($matches['slap'])) {
				$this->set_slap($this->date.' '.$matches['time'], $matches['nick_performing'], (!empty($matches['nick_undergoing']) ? $matches['nick_undergoing'] : null));
			}

			$this->set_action($this->date.' '.$matches['time'], $matches['nick_performing'], $matches['line']);

		/**
		 * "Nickchange" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) -!- (?<nick_performing>\S+) is now known as (?<nick_undergoing>\S+)$/', $line, $matches)) {
			$this->set_nickchange($this->date.' '.$matches['time'], $matches['nick_performing'], $matches['nick_undergoing']);

		/**
		 * "Part" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) -!- (?<nick>\S+) \[\S+\] has left [#&!+]\S+ \[.*\]$/', $line, $matches)) {
			$this->set_part($this->date.' '.$matches['time'], $matches['nick']);

		/**
		 * "Topic" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) -!- (?<nick>\S+) changed the topic of [#&!+]\S+ to: (?<line>.+)$/', $line, $matches)) {
			$this->set_topic($this->date.' '.$matches['time'], $matches['nick'], $matches['line']);

		/**
		 * "Kick" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) -!- (?<line>(?<nick_undergoing>\S+) was kicked from [#&!+]\S+ by (?<nick_performing>\S+) \[.*\])$/', $line, $matches)) {
			$this->set_kick($this->date.' '.$matches['time'], $matches['nick_performing'], $matches['nick_undergoing'], $matches['line']);

		/**
		 * Skip everything else.
		 */
		} elseif ($line !== '') {
			$this->output('debug', 'parse_line(): skipping line '.$this->linenum.': \''.$line.'\'');
		}
	}
}
