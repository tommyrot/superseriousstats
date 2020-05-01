<?php declare(strict_types=1);

/**
 * Copyright (c) 2012-2020, Jos de Ruijter <jos@dutnie.nl>
 */

class parser_hexchat extends parser
{
	protected function parse_line(string $line): void
	{
		// "Normal" lines.
		if (preg_match('/^\S{3} \d{2} (?<time>\d{2}:\d{2}(:\d{2})?) <(?<nick>\S+)> (?<line>.+)$/', $line, $matches)) {
			$this->set_normal($matches['time'], $matches['nick'], $matches['line']);

		// "Join" lines.
		} elseif (preg_match('/^\S{3} \d{2} (?<time>\d{2}:\d{2}(:\d{2})?) \* (?<nick>\S+) \(\S+\) has joined [#&!+]\S+$/', $line, $matches)) {
			$this->set_join($matches['time'], $matches['nick']);

		// "Quit" lines.
		} elseif (preg_match('/^\S{3} \d{2} (?<time>\d{2}:\d{2}(:\d{2})?) \* (?<nick>\S+) has quit \(.*\)$/', $line, $matches)) {
			$this->set_quit($matches['time'], $matches['nick']);

		// "Mode" lines.
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

		// "Nickchange" lines.
		} elseif (preg_match('/^\S{3} \d{2} (?<time>\d{2}:\d{2}(:\d{2})?) \* (?<nick_performing>\S+) is now known as (?<nick_undergoing>\S+)$/', $line, $matches)) {
			$this->set_nickchange($matches['time'], $matches['nick_performing'], $matches['nick_undergoing']);

		// "Part" lines.
		} elseif (preg_match('/^\S{3} \d{2} (?<time>\d{2}:\d{2}(:\d{2})?) \* (?<nick>\S+) \(\S+\) has left [#&!+]\S+( \(.*\))?$/', $line, $matches)) {
			$this->set_part($matches['time'], $matches['nick']);

		// "Topic" lines.
		} elseif (preg_match('/^\S{3} \d{2} (?<time>\d{2}:\d{2}(:\d{2})?) \* (?<nick>\S+) has changed the topic to: (?<line>.+)$/', $line, $matches)) {
			$this->set_topic($matches['time'], $matches['nick'], $matches['line']);

		// "Kick" lines.
		} elseif (preg_match('/^\S{3} \d{2} (?<time>\d{2}:\d{2}(:\d{2})?) \* (?<line>(?<nick_performing>\S+) has kicked (?<nick_undergoing>\S+) from [#&!+]\S+ \(.*\))$/', $line, $matches)) {
			$this->set_kick($matches['time'], $matches['nick_performing'], $matches['nick_undergoing'], $matches['line']);

		// Skip everything else.
		} elseif ($line !== '') {
			output::msg('debug', 'skipping line '.$this->linenum.': \''.$line.'\'');
		}
	}
}
