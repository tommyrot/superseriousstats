<?php

/**
 * Copyright (c) 2009-2010, Jos de Ruijter <jos@dutnie.nl>
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
 * +------------+-------------------------------------------------------+->
 * | Line	| Format						| Notes
 * +------------+-------------------------------------------------------+->
 * | Normal	| <NICK> MSG						| Skip empty lines.
 * | Action	| * NICK MSG						| Skip empty actions.
 * | Slap	| * NICK slaps MSG					| Slaps may lack a (valid) target.
 * | Nickchange	| -!- NICK is now known as NICK				|
 * | Join	| -!- NICK [HOST] has joined CHAN			|
 * | Part	| -!- NICK [HOST] has left CHAN [MSG]			| Part message may be absent, or empty due to normalization.
 * | Quit	| -!- NICK [HOST] has quit [MSG]			| Quit message may be empty due to normalization.
 * | Mode	| -!- mode/CHAN [+o-v NICK NICK] by NICK		| Only check for combinations of ops (+o) and voices (+v).
 * | Mode	| -!- ServerMode/CHAN [+o-v NICK NICK] by NICK		| "
 * | Topic	| -!- NICK changed the topic of CHAN to: MSG		| Skip empty topics.
 * | Kick	| -!- NICK was kicked from CHAN by NICK [MSG]		| Kick message may be empty due to normalization.
 * +------------+-------------------------------------------------------+->
 *
 * Notes:
 * - parseLog() normalizes all lines before passing them on to parseLine().
 * - The order of the regular expressions below is irrelevant (current order aims for best performance).
 * - We have to be mindful that nicks can contain "[" and "]".
 * - The most common channel prefixes are "#&!+" and the most common nick prefixes are "~&@%+!*".
 * - If there are multiple nicks we want to catch in our regular expression match we name the "performing" nick "nick1" and the "undergoing" nick "nick2".
 * - Irssi may log multiple "performing" nicks separated by commas. We use only the first one.
 * - In certain cases $matches[] won't contain index items if these optionally appear at the end of a line. We use empty() to check whether an index is both set and has a value. The consequence is that neither nicks nor hosts can have 0 as a value.
 */
final class Parser_Irssi extends Parser
{
	/**
	 * Parse a line for various chat data.
	 */
	protected function parseLine($line)
	{
		/**
		 * "Normal" lines.
		 */
		if (preg_match('/^(?<time>\d{2}:\d{2}) <[\x20~&@%+!*](?<nick>\S+)> (?<line>.+)$/', $line, $matches)) {
			$this->setNormal($this->date.' '.$matches['time'], $matches['nick'], $matches['line']);

		/**
		 * "Join" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}) -!- (?<nick>\S+) \[~?(?<host>\S+)\] has joined [#&!+]\S+$/', $line, $matches)) {
			$this->setJoin($this->date.' '.$matches['time'], $matches['nick'], $matches['host']);

		/**
		 * "Quit" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}) -!- (?<nick>\S+) \[~?(?<host>\S+)\] has quit \[.*\]$/', $line, $matches)) {
			$this->setQuit($this->date.' '.$matches['time'], $matches['nick'], $matches['host']);

		/**
		 * "Mode" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}) -!- (ServerMode|mode)\/[#&!+]\S+ \[(?<modes>[-+][ov]+([-+][ov]+)?) (?<nicks>\S+( \S+)*)\] by (?<nick>\S+)(, \S+)*$/', $line, $matches)) {
			$nicks = explode(' ', $matches['nicks']);
			$modeNum = 0;

			for ($i = 0, $j = strlen($matches['modes']); $i < $j; $i++) {
				$mode = substr($matches['modes'], $i, 1);

				if ($mode == '-' || $mode == '+') {
					$modeSign = $mode;
				} else {
					$this->setMode($this->date.' '.$matches['time'], $matches['nick'], $nicks[$modeNum], $modeSign.$mode, NULL);
					$modeNum++;
				}
			}

		/**
		 * "Action" and "slap" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}) \* (?<line>(?<nick1>\S+) ((?<slap>[sS][lL][aA][pP][sS]( (?<nick2>\S+)( .+)?)?)|(.+)))$/', $line, $matches)) {
			if (!empty($matches['slap'])) {
				$this->setSlap($this->date.' '.$matches['time'], $matches['nick1'], (!empty($matches['nick2']) ? $matches['nick2'] : NULL));
			}

			$this->setAction($this->date.' '.$matches['time'], $matches['nick1'], $matches['line']);

		/**
		 * "Nickchange" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}) -!- (?<nick1>\S+) is now known as (?<nick2>\S+)$/', $line, $matches)) {
			$this->setNickchange($this->date.' '.$matches['time'], $matches['nick1'], $matches['nick2']);

		/**
		 * "Part" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}) -!- (?<nick>\S+) \[~?(?<host>\S+)\] has left [#&!+]\S+ \[.*\]$/', $line, $matches)) {
			$this->setPart($this->date.' '.$matches['time'], $matches['nick'], $matches['host']);

		/**
		 * "Topic" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}) -!- (?<nick>\S+) changed the topic of [#&!+]\S+ to: (?<line>.+)$/', $line, $matches)) {
			$this->setTopic($this->date.' '.$matches['time'], $matches['nick'], NULL, $matches['line']);

		/**
		 * "Kick" lines.
		 */
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}) -!- (?<line>(?<nick2>\S+) was kicked from [#&!+]\S+ by (?<nick1>\S+) \[.*\])$/', $line, $matches)) {
			$this->setKick($this->date.' '.$matches['time'], $matches['nick1'], $matches['nick2'], $matches['line']);

		/**
		 * Skip everything else.
		 */
		} elseif ($line != '') {
			$this->output('debug', 'parseLine(): skipping line '.$this->lineNum.': \''.$line.'\'');
		}
	}
}

?>
