<?php

/**
 * Copyright (c) 2012-2015, Jos de Ruijter <jos@dutnie.nl>
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
 * Parse instructions for the LimeChat logfile format.
 *
 * Line         Format                                                  Notes
 * ---------------------------------------------------------------------------------------------------------------------
 * Normal       NICK: MSG                                               Skip empty lines.
 * Nickchange   NICK is now known as NICK
 * Join         NICK has joined (HOST)
 * Part         NICK has left (MSG)                                     Part message may be empty due to normalization.
 * Quit         NICK has left IRC (MSG)                                 Quit message may be empty due to normalization.
 *                                                                      IRC is literal.
 * Mode         NICK has changed mode: +o-v NICK NICK                   Only check for combinations of ops (+o) and
 *                                                                      voices (+v).
 * Topic        NICK has set topic: MSG                                 Skip empty topics.
 * Kick         NICK has kicked NICK (MSG)                              Kick message may be empty due to normalization.
 * ---------------------------------------------------------------------------------------------------------------------
 *
 * Notes:
 * - normalize_line() scrubs all lines before passing them on to parse_line().
 * - LimeChat uses the same syntax for actions as "normal" lines. This makes the two indistinguishable. Pretty dumb.
 * - Given that nicks can't contain ":" the order of the regular expressions below is irrelevant (current order aims for
 *   best performance).
 */
class parser_limechat extends parser
{
	/**
	 * Parse a line for various chat data.
	 */
	protected function parse_line($line)
	{
		/**
		 * "Normal" lines.
		 */
		if (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) (?<nick>\S+): (?<line>.+)$/', $line, $matches)) {
			$this->set_normal($this->date.' '.$matches['time'], $matches['nick'], $matches['line']);

		/**
		 * "Join" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) (?<nick>\S+) has joined \(\S+\)$/', $line, $matches)) {
			$this->set_join($this->date.' '.$matches['time'], $matches['nick']);

		/**
		 * "Quit" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) (?<nick>\S+) has left IRC \(.*\)$/', $line, $matches)) {
			$this->set_quit($this->date.' '.$matches['time'], $matches['nick']);

		/**
		 * "Mode" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) (?<nick_performing>\S+) has changed mode: (?<modes>[-+][ov]+([-+][ov]+)?) (?<nicks_undergoing>\S+( \S+)*)$/', $line, $matches)) {
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
		 * "Nickchange" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) (?<nick_performing>\S+) is now known as (?<nick_undergoing>\S+)$/', $line, $matches)) {
			$this->set_nickchange($this->date.' '.$matches['time'], $matches['nick_performing'], $matches['nick_undergoing']);

		/**
		 * "Part" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) (?<nick>\S+) has left \(.*\)$/', $line, $matches)) {
			$this->set_part($this->date.' '.$matches['time'], $matches['nick']);

		/**
		 * "Topic" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) (?<nick>\S+) has set topic: (?<line>.+)$/', $line, $matches)) {
			$this->set_topic($this->date.' '.$matches['time'], $matches['nick'], $matches['line']);

		/**
		 * "Kick" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) (?<line>(?<nick_performing>\S+) has kicked (?<nick_undergoing>\S+) \(.*\))$/', $line, $matches)) {
			$this->set_kick($this->date.' '.$matches['time'], $matches['nick_performing'], $matches['nick_undergoing'], $matches['line']);

		/**
		 * Skip everything else.
		 */
		} elseif ($line !== '') {
			output::output('debug', 'parse_line(): skipping line '.$this->linenum.': \''.$line.'\'');
		}
	}
}
