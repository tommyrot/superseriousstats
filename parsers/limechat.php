<?php declare(strict_types=1);

/**
 * Copyright (c) 2012-2020, Jos de Ruijter <jos@dutnie.nl>
 */

class parser_limechat extends parser
{
	protected function parse_line(string $line): void
	{
		$timestamp = '(?<time>\d{2}:\d{2}(:\d{2})?) ';

		if (preg_match('/^'.$timestamp.'(?<nick>\S+): (?<line>.+)$/', $line, $matches)) {
			$this->set_normal($matches['time'], $matches['nick'], $matches['line']);
		} elseif (preg_match('/^'.$timestamp.'(?<nick>\S+) has joined \(\S+\)$/', $line, $matches)) {
			$this->set_join($matches['time'], $matches['nick']);
		} elseif (preg_match('/^'.$timestamp.'(?<nick>\S+) has left IRC \(.*\)$/', $line, $matches)) {
			$this->set_quit($matches['time'], $matches['nick']);
		} elseif (preg_match('/^'.$timestamp.'(?<nick_performing>\S+) has changed mode: (?<modes>[-+][ov]+([-+][ov]+)?) (?<nicks_undergoing>\S+( \S+)*)$/', $line, $matches)) {
			$mode_num = 0;
			$nicks_undergoing = explode(' ', $matches['nicks_undergoing']);

			for ($i = 0, $j = strlen($matches['modes']); $i < $j; ++$i) {
				$mode = substr($matches['modes'][$i];

				if ($mode === '-' || $mode === '+') {
					$mode_sign = $mode;
				} else {
					$this->set_mode($matches['time'], $matches['nick_performing'], $nicks_undergoing[$mode_num], $mode_sign.$mode);
					++$mode_num;
				}
			}
		} elseif (preg_match('/^'.$timestamp.'(?<nick_performing>\S+) is now known as (?<nick_undergoing>\S+)$/', $line, $matches)) {
			$this->set_nickchange($matches['time'], $matches['nick_performing'], $matches['nick_undergoing']);
		} elseif (preg_match('/^'.$timestamp.'(?<nick>\S+) has left \(.*\)$/', $line, $matches)) {
			$this->set_part($matches['time'], $matches['nick']);
		} elseif (preg_match('/^'.$timestamp.'(?<nick>\S+) has set topic: (?<line>.+)$/', $line, $matches)) {
			$this->set_topic($matches['time'], $matches['nick'], $matches['line']);
		} elseif (preg_match('/^'.$timestamp.'(?<line>(?<nick_performing>\S+) has kicked (?<nick_undergoing>\S+) \(.*\))$/', $line, $matches)) {
			$this->set_kick($matches['time'], $matches['nick_performing'], $matches['nick_undergoing'], $matches['line']);
		} else {
			out::put('debug', 'skipping line '.$this->linenum.': \''.$line.'\'');
		}
	}
}
