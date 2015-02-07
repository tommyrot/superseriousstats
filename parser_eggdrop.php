<?php

/**
 * Copyright (c) 2007-2015, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Parse instructions for the Eggdrop logfile format.
 *
 * Line         Format                                                  Notes
 * ---------------------------------------------------------------------------------------------------------------------
 * Normal       <NICK> MSG                                              Skip empty lines.
 * Action       Action: NICK MSG                                        Skip empty actions.
 * Slap         Action: NICK slaps MSG                                  Slaps may lack a (valid) target.
 * Nickchange   Nick change: NICK -> NICK
 * Join         NICK (HOST) joined CHAN.
 * Part         NICK (HOST) left CHAN (MSG).                            Part message may be absent, or empty due to
 *                                                                      normalization.
 * Quit         NICK (HOST) left irc: MSG                               Quit message may be empty due to normalization.
 * Mode         CHAN: mode change '+o-v NICK NICK' by NICK!HOST         Only check for combinations of ops (+o) and
 *                                                                      voices (+v). Host may be absent.
 * Topic        Topic changed on CHAN by NICK!HOST: MSG                 Skip empty topics. Host may be absent.
 * Kick         NICK kicked from CHAN by NICK: MSG                      Kick message may be empty due to normalization.
 * Repeat       Last message repeated NUM time(s).
 * ---------------------------------------------------------------------------------------------------------------------
 *
 * Notes:
 * - normalize_line() scrubs all lines before passing them on to parse_line().
 * - Given that nicks can't contain "<", ">" or ":" the order of the regular expressions below is irrelevant (current
 *   order aims for best performance).
 * - The most common channel prefixes are "#&!+".
 * - Some converted mIRC logs do include "!" in "mode" and "topic" lines while there is no host. Legacy feature.
 * - In certain cases $matches[] won't contain index items if these optionally appear at the end of a line. We use
 *   empty() to check whether an index item is both set and has a value.
 */
class parser_eggdrop extends parser
{
	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $repeatlock = false;

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
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<nick>\S+) \(\S+\) joined [#&!+]\S+\.$/', $line, $matches)) {
			$this->set_join($this->date.' '.$matches['time'], $matches['nick']);

		/**
		 * "Quit" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<nick>\S+) \(\S+\) left irc:( .+)?$/', $line, $matches)) {
			$this->set_quit($this->date.' '.$matches['time'], $matches['nick']);

		/**
		 * "Mode" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] [#&!+]\S+: mode change \'(?<modes>[-+][ov]+([-+][ov]+)?) (?<nicks_undergoing>\S+( \S+)*)\' by (?<nick_performing>\S+?)(!(\S+)?)?$/', $line, $matches)) {
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
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] Action: (?<line>(?<nick_performing>\S+) ((?<slap>[sS][lL][aA][pP][sS]( (?<nick_undergoing>\S+)( .+)?)?)|(.+)))$/', $line, $matches)) {
			if (!empty($matches['slap'])) {
				$this->set_slap($this->date.' '.$matches['time'], $matches['nick_performing'], (!empty($matches['nick_undergoing']) ? $matches['nick_undergoing'] : null));
			}

			$this->set_action($this->date.' '.$matches['time'], $matches['nick_performing'], $matches['line']);

		/**
		 * "Nickchange" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] Nick change: (?<nick_performing>\S+) -> (?<nick_undergoing>\S+)$/', $line, $matches)) {
			$this->set_nickchange($this->date.' '.$matches['time'], $matches['nick_performing'], $matches['nick_undergoing']);

		/**
		 * "Part" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<nick>\S+) \(\S+\) left [#&!+]\S+( \(.*\))?\.$/', $line, $matches)) {
			$this->set_part($this->date.' '.$matches['time'], $matches['nick']);

		/**
		 * "Topic" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] Topic changed on [#&!+]\S+ by (?<nick>\S+?)(!(\S+)?)?: (?<line>.+)$/', $line, $matches)) {
			$this->set_topic($this->date.' '.$matches['time'], $matches['nick'], $matches['line']);

		/**
		 * "Kick" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<line>(?<nick_undergoing>\S+) kicked from [#&!+]\S+ by (?<nick_performing>\S+):( .+)?)$/', $line, $matches)) {
			$this->set_kick($this->date.' '.$matches['time'], $matches['nick_performing'], $matches['nick_undergoing'], $matches['line']);

		/**
		 * Eggdrop logs repeated lines (case insensitive matches) in the format: "Last message repeated NUM
		 * time(s).". We process the previous line NUM times.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] Last message repeated (?<num>\d+) time\(s\)\.$/', $line, $matches)) {
			/**
			 * Prevent the parser from repeating a preceding repeat line. Also, skip processing if we find a
			 * repeat line on the first line of the logfile. We can't look back across files.
			 */
			if ($this->linenum === 1 || $this->repeatlock) {
				return null;
			}

			$this->linenum--;
			$this->repeatlock = true;
			output::output('debug', 'parse_line(): repeating line '.$this->linenum.': '.$matches['num'].' time'.(($matches['num'] !== '1') ? 's' : ''));

			for ($i = 1, $j = (int) $matches['num']; $i <= $j; $i++) {
				$this->parse_line($this->prevline);
			}

			$this->linenum++;
			$this->repeatlock = false;

		/**
		 * Skip everything else.
		 */
		} elseif ($line !== '') {
			output::output('debug', 'parse_line(): skipping line '.$this->linenum.': \''.$line.'\'');
		}
	}
}
