<?php

/**
 * Copyright (c) 2012-2020, Jos de Ruijter <jos@dutnie.nl>
 */

declare(strict_types=1);

class parser_limechat extends parser
{
	protected function parse_line(string $line): void
	{
		// "Normal" lines.
		if (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) (?<nick>\S+): (?<line>.+)$/', $line, $matches)) {
			$this->set_normal($matches['time'], $matches['nick'], $matches['line']);

		// "Join" lines.
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) (?<nick>\S+) has joined \(\S+\)$/', $line, $matches)) {
			$this->set_join($matches['time'], $matches['nick']);

		// "Quit" lines.
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) (?<nick>\S+) has left IRC \(.*\)$/', $line, $matches)) {
			$this->set_quit($matches['time'], $matches['nick']);

		// "Mode" lines.
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) (?<nick_performing>\S+) has changed mode: (?<modes>[-+][ov]+([-+][ov]+)?) (?<nicks_undergoing>\S+( \S+)*)$/', $line, $matches)) {
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

		// "Nickchange" lines.
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) (?<nick_performing>\S+) is now known as (?<nick_undergoing>\S+)$/', $line, $matches)) {
			$this->set_nickchange($matches['time'], $matches['nick_performing'], $matches['nick_undergoing']);

		// "Part" lines.
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) (?<nick>\S+) has left \(.*\)$/', $line, $matches)) {
			$this->set_part($matches['time'], $matches['nick']);

		// "Topic" lines.
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) (?<nick>\S+) has set topic: (?<line>.+)$/', $line, $matches)) {
			$this->set_topic($matches['time'], $matches['nick'], $matches['line']);

		// "Kick" lines.
		} elseif (preg_match('/^(?<time>\d{2}:\d{2}(:\d{2})?) (?<line>(?<nick_performing>\S+) has kicked (?<nick_undergoing>\S+) \(.*\))$/', $line, $matches)) {
			$this->set_kick($matches['time'], $matches['nick_performing'], $matches['nick_undergoing'], $matches['line']);

		// Skip everything else.
		} elseif ($line !== '') {
			output::output('debug', __METHOD__.'(): skipping line '.$this->linenum.': \''.$line.'\'');
		}
	}
}
