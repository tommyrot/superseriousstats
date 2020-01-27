<?php

/**
 * Copyright (c) 2007-2020, Jos de Ruijter <jos@dutnie.nl>
 */

class parser_eggdrop extends parser
{
	private $repeat_lock = false;

	protected function parse_line($line)
	{
		// "Normal" lines.
		if (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] <(?<nick>\S+)> (?<line>.+)$/', $line, $matches)) {
			$this->set_normal($matches['time'], $matches['nick'], $matches['line']);

		// "Join" lines.
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<nick>\S+) \(\S+\) joined [#&!+]\S+\.$/', $line, $matches)) {
			$this->set_join($matches['time'], $matches['nick']);

		// "Quit" lines.
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<nick>\S+) \(\S+\) left irc:( .+)?$/', $line, $matches)) {
			$this->set_quit($matches['time'], $matches['nick']);

		// "Mode" lines.
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] [#&!+]\S+: mode change \'(?<modes>[-+][ov]+([-+][ov]+)?) (?<nicks_undergoing>\S+( \S+)*)\' by (?<nick_performing>\S+?)(!(\S+)?)?$/', $line, $matches)) {
			$modenum = 0;
			$nicks_undergoing = explode(' ', $matches['nicks_undergoing']);

			for ($i = 0, $j = strlen($matches['modes']); $i < $j; ++$i) {
				$mode = substr($matches['modes'], $i, 1);

				if ($mode === '-' || $mode === '+') {
					$modesign = $mode;
				} else {
					$this->set_mode($matches['time'], $matches['nick_performing'], $nicks_undergoing[$modenum], $modesign.$mode);
					++$modenum;
				}
			}

		// "Action" and "slap" lines.
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] Action: (?<line>(?<nick_performing>\S+) ((?<slap>[sS][lL][aA][pP][sS]( (?<nick_undergoing>\S+)( .+)?)?)|(.+)))$/', $line, $matches, PREG_UNMATCHED_AS_NULL)) {
			if (!is_null($matches['slap'])) {
				$this->set_slap($matches['time'], $matches['nick_performing'], $matches['nick_undergoing']);
			}

			$this->set_action($matches['time'], $matches['nick_performing'], $matches['line']);

		// "Nickchange" lines.
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] Nick change: (?<nick_performing>\S+) -> (?<nick_undergoing>\S+)$/', $line, $matches)) {
			$this->set_nickchange($matches['time'], $matches['nick_performing'], $matches['nick_undergoing']);

		// "Part" lines.
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<nick>\S+) \(\S+\) left [#&!+]\S+( \(.*\))?\.$/', $line, $matches)) {
			$this->set_part($matches['time'], $matches['nick']);

		// "Topic" lines.
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] Topic changed on [#&!+]\S+ by (?<nick>\S+?)(!(\S+)?)?: (?<line>.+)$/', $line, $matches)) {
			$this->set_topic($matches['time'], $matches['nick'], $matches['line']);


		// "Kick" lines.
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<line>(?<nick_undergoing>\S+) kicked from [#&!+]\S+ by (?<nick_performing>\S+):( .+)?)$/', $line, $matches)) {
			$this->set_kick($matches['time'], $matches['nick_performing'], $matches['nick_undergoing'], $matches['line']);

		/**
		 * Eggdrop logs repeated lines (case insensitive matches) in the format: "Last
		 * message repeated NUM time(s).". We process the previous line NUM times.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] Last message repeated (?<num>\d+) time\(s\)\.$/', $line, $matches)) {
			/**
			 * Prevent the parser from repeating a preceding repeat line. Also, skip
			 * processing if we find a repeat line but $line_prev isn't set.
			 */
			if ($this->line_prev === '' || $this->repeat_lock) {
				return;
			}

			--$this->linenum;
			$this->repeat_lock = true;
			output::output('debug', __METHOD__.'(): repeating line '.$this->linenum.': '.$matches['num'].' time'.($matches['num'] !== '1' ? 's' : ''));

			for ($i = 1, $j = (int) $matches['num']; $i <= $j; ++$i) {
				$this->parse_line($this->line_prev);
			}

			++$this->linenum;
			$this->repeat_lock = false;

		// Skip everything else.
		} elseif ($line !== '') {
			output::output('debug', __METHOD__.'(): skipping line '.$this->linenum.': \''.$line.'\'');
		}
	}
}
