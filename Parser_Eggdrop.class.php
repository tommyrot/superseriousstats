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
 * Parse instructions for the Eggdrop logfile format.
 *
 * +------------+-------------------------------------------------------+->
 * | Line	| Format						| Notes
 * +------------+-------------------------------------------------------+->
 * | Normal	| <NICK> MSG						| Skip empty lines.
 * | Action	| Action: NICK MSG					| Skip empty actions.
 * | Slap	| Action: NICK slaps MSG				| Slaps may lack a (valid) target.
 * | Nickchange	| Nick change: NICK -> NICK				|
 * | Join	| NICK (HOST) joined CHAN.				|
 * | Part	| NICK (HOST) left CHAN (MSG).				| Part message may be absent, or empty due to normalization.
 * | Quit	| NICK (HOST) left irc: MSG				| Quit message may be empty due to normalization.
 * | Mode	| CHAN: mode change '+o-v NICK NICK' by NICK!HOST	| Only check for combinations of ops (+o) and voices (+v). Host may be absent.
 * | Topic	| Topic changed on CHAN by NICK!HOST: MSG		| Skip empty topics. Host may be absent.
 * | Kick	| NICK kicked from CHAN by NICK: MSG			| Kick message may be empty due to normalization.
 * | Repeat	| Last message repeated NUM time(s).			|
 * +------------+-------------------------------------------------------+->
 *
 * Notes:
 * - parseLog() normalizes all lines before passing them on to parseLine().
 * - Given that nicks can't contain "<", ">" or ":" the order of the regular expressions below is irrelevant (current order aims for best performance).
 * - The most common channel prefixes are "#&!+" and the most common nick prefixes are "~&@%+!*".
 * - If there are multiple nicks we want to catch in our regular expression match we name the "performing" nick "nick1" and the "undergoing" nick "nick2".
 * - Some converted mIRC logs do include "!" in "mode" and "topic" lines while there is no host.
 * - In certain cases $matches[] won't contain index items if these optionally appear at the end of a line. We use empty() to check whether an index is both set and has a value.
 */
final class Parser_Eggdrop extends Parser
{
	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $repeatLock = FALSE;

	/**
	 * Parse a line for various chat data.
	 */
	protected function parseLine($line)
	{
		/**
		 * "Normal" lines.
		 */
		if (preg_match('/^\[(?<time>\d{2}:\d{2})\] <(?<nick>\S+)> (?<line>.+)$/', $line, $matches)) {
			$this->setNormal($this->date.' '.$matches['time'], $matches['nick'], $matches['line']);

		/**
		 * "Join" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2})\] (?<nick>\S+) \(~?(?<host>\S+)\) joined [#&!+]\S+\.$/', $line, $matches)) {
			$this->setJoin($this->date.' '.$matches['time'], $matches['nick'], $matches['host']);

		/**
		 * "Quit" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2})\] (?<nick>\S+) \(~?(?<host>\S+)\) left irc:( .+)?$/', $line, $matches)) {
			$this->setQuit($this->date.' '.$matches['time'], $matches['nick'], $matches['host']);

		/**
		 * "Mode" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2})\] [#&!+]\S+: mode change \'(?<modes>[-+][ov]+([-+][ov]+)?) (?<nicks>\S+( \S+)*)\' by (?<nick>\S+?)(!(~?(?<host>\S+))?)?$/', $line, $matches)) {
			$nicks = explode(' ', $matches['nicks']);
			$modeNum = 0;

			for ($i = 0, $j = strlen($matches['modes']); $i < $j; $i++) {
				$mode = substr($matches['modes'], $i, 1);

				if ($mode == '-' || $mode == '+') {
					$modeSign = $mode;
				} else {
					$this->setMode($this->date.' '.$matches['time'], $matches['nick'], $nicks[$modeNum], $modeSign.$mode, (!empty($matches['host']) ? $matches['host'] : NULL));
					$modeNum++;
				}
			}

		/**
		 * "Action" and "slap" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2})\] Action: (?<line>(?<nick1>\S+) ((?<slap>[sS][lL][aA][pP][sS]( (?<nick2>\S+)( .+)?)?)|(.+)))$/', $line, $matches)) {
			if (!empty($matches['slap'])) {
				$this->setSlap($this->date.' '.$matches['time'], $matches['nick1'], (!empty($matches['nick2']) ? $matches['nick2'] : NULL));
			}

			$this->setAction($this->date.' '.$matches['time'], $matches['nick1'], $matches['line']);

		/**
		 * "Nickchange" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2})\] Nick change: (?<nick1>\S+) -> (?<nick2>\S+)$/', $line, $matches)) {
			$this->setNickchange($this->date.' '.$matches['time'], $matches['nick1'], $matches['nick2']);

		/**
		 * "Part" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2})\] (?<nick>\S+) \(~?(?<host>\S+)\) left [#&!+]\S+( \(.*\))?\.$/', $line, $matches)) {
			$this->setPart($this->date.' '.$matches['time'], $matches['nick'], $matches['host']);

		/**
		 * "Topic" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2})\] Topic changed on [#&!+]\S+ by (?<nick>\S+?)(!(~?(?<host>\S+))?)?: (?<line>.+)$/', $line, $matches)) {
			$this->setTopic($this->date.' '.$matches['time'], $matches['nick'], (!empty($matches['host']) ? $matches['host'] : NULL), $matches['line']);

		/**
		 * "Kick" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2})\] (?<line>(?<nick2>\S+) kicked from [#&!+]\S+ by (?<nick1>\S+):( .+)?)$/', $line, $matches)) {
			$this->setKick($this->date.' '.$matches['time'], $matches['nick1'], $matches['nick2'], $matches['line']);

		/**
		 * Eggdrop logs repeated lines (case insensitive matches) in the format: "Last message repeated NUM time(s).".
		 * We process the previous line NUM times.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2})\] Last message repeated (?<num>\d+) time\(s\)\.$/', $line, $matches)) {
			/**
			 * Prevent the parser from repeating a preceding repeat line.
			 * Also, skip processing if we find a repeat line on the first line of the logfile. We can't look back across files.
			 */
			if ($this->lineNum == 1 || $this->repeatLock) {
				return;
			}

			$this->repeatLock = TRUE;
			$this->lineNum--;
			$this->output('notice', 'parseLine(): repeating line '.$this->lineNum.': '.$matches['num'].' time'.(($matches['num'] != '1') ? 's' : ''));

			for ($i = 1, $j = (int) $matches['num']; $i <= $j; $i++) {
				$this->parseLine($this->prevLine);
			}

			$this->lineNum++;
			$this->repeatLock = FALSE;

		/**
		 * Skip everything else.
		 */
		} elseif ($line != '') {
			$this->output('debug', 'parseLine(): skipping line '.$this->lineNum.': \''.$line.'\'');
		}
	}
}

?>
