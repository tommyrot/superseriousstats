<?php

/**
 * Copyright (c) 2010, Jos de Ruijter <jos@dutnie.nl>
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
 * Parse instructions for the muh2 logfile format.
 *
 * +------------+-------------------------------------------------------+->
 * | Line	| Format						| Notes
 * +------------+-------------------------------------------------------+->
 * | Normal	| <NICK> MSG						| Skip empty lines.
 * | Action	| * NICK MSG						| Skip empty actions.
 * | Slap	| * NICK slaps MSG					| Slaps may lack a (valid) target.
 * | Nickchange	| NICK is now known as NICK				|
 * | Join	| *** Joins: NICK (HOST)				|
 * | Part	| *** Parts: NICK (HOST)				|
 * | Quit	| *** Quits: NICK (HOST) (MSG)				| Quit message may be empty due to normalization.
 * | Mode	| *** NICK sets mode: +o-v NICK NICK			| Only check for combinations of ops (+o) and voices (+v).
 * | Topic	| *** NICK changes topic to 'MSG'			| Skip empty topics.
 * | Kick	| *** NICK was kicked by NICK (MSG)			| Kick message may be empty due to normalization.
 * +------------+-------------------------------------------------------+->
 *
 * Notes:
 * - parse_log() normalizes all lines before passing them on to parse_line().
 * - Given that nicks can't contain ":" the order of the regular expressions below is irrelevant (current order aims for best performance).
 * - If there are multiple nicks we want to catch in our regular expression match we name the "performing" nick "nick1" and the "undergoing" nick "nick2".
 * - In certain cases $matches[] won't contain index items if these optionally appear at the end of a line. We use empty() to check whether an index is both set and has a value.
 */
final class parser_muh2 extends parser
{
	/**
	 * Parse a line for various chat data.
	 */
	protected function parse_line($line)
	{
		/**
		 * "Normal" lines.
		 */
		if (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] <(?<nick>\S+)> (?<line>.+)$/', $line, $matches)) {
			$this->set_normal($this->date.' '.$matches['time'], $matches['nick'], $matches['line']);

		/**
		 * "Join" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] \*\*\* Joins: (?<nick>\S+) \(~?(?<host>\S+)\)$/', $line, $matches)) {
			$this->set_join($this->date.' '.$matches['time'], $matches['nick'], $matches['host']);

		/**
		 * "Quit" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] \*\*\* Quits: (?<nick>\S+) \(~?(?<host>\S+)\) \(.*\)$/', $line, $matches)) {
			$this->set_quit($this->date.' '.$matches['time'], $matches['nick'], $matches['host']);

		/**
		 * "Mode" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] \*\*\* (?<nick>\S+) sets mode: (?<modes>[-+][ov]+([-+][ov]+)?) (?<nicks>\S+( \S+)*)$/', $line, $matches)) {
			$nicks = explode(' ', $matches['nicks']);
			$modenum = 0;

			for ($i = 0, $j = strlen($matches['modes']); $i < $j; $i++) {
				$mode = substr($matches['modes'], $i, 1);

				if ($mode == '-' || $mode == '+') {
					$modesign = $mode;
				} else {
					$this->set_mode($this->date.' '.$matches['time'], $matches['nick'], $nicks[$modenum], $modesign.$mode, null);
					$modenum++;
				}
			}

		/**
		 * "Action" and "slap" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] \* (?<line>(?<nick1>\S+) ((?<slap>[sS][lL][aA][pP][sS]( (?<nick2>\S+)( .+)?)?)|(.+)))$/', $line, $matches)) {
			if (!empty($matches['slap'])) {
				$this->set_slap($this->date.' '.$matches['time'], $matches['nick1'], (!empty($matches['nick2']) ? $matches['nick2'] : null));
			}

			$this->set_action($this->date.' '.$matches['time'], $matches['nick1'], $matches['line']);

		/**
		 * "Nickchange" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] \*\*\* (?<nick1>\S+) is now known as (?<nick2>\S+)$/', $line, $matches)) {
			$this->set_nickchange($this->date.' '.$matches['time'], $matches['nick1'], $matches['nick2']);

		/**
		 * "Part" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] \*\*\* Parts: (?<nick>\S+) \(~?(?<host>\S+)\)$/', $line, $matches)) {
			$this->set_part($this->date.' '.$matches['time'], $matches['nick'], $matches['host']);

		/**
		 * "Topic" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] \*\*\* (?<nick>\S+) changes topic to \'(?<line>.+)\'$/', $line, $matches)) {
			if ($matches['line'] != ' ') {
				$this->set_topic($this->date.' '.$matches['time'], $matches['nick'], null, $matches['line']);
			}

		/**
		 * "Kick" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] \*\*\* (?<line>(?<nick2>\S+) was kicked by (?<nick1>\S+) \(.*\))$/', $line, $matches)) {
			$this->set_kick($this->date.' '.$matches['time'], $matches['nick1'], $matches['nick2'], $matches['line']);

		/**
		 * Skip everything else.
		 */
		} elseif ($line != '') {
			$this->output('debug', 'parse_line(): skipping line '.$this->linenum.': \''.$line.'\'');
		}
	}
}

?>
