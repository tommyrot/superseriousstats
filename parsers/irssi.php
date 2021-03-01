<?php declare(strict_types=1);

/**
 * Copyright (c) 2009-2020, Jos de Ruijter <jos@dutnie.nl>
 */

class parser_irssi extends parser
{
	protected function parse_line(string $line): void
	{
		$timestamp = '(?<time>\d{2}:\d{2}(:\d{2})?) ?';

		if (preg_match('/^'.$timestamp.'<[ ~&@%+!]?(?<nick>\S+)> (?<line>.+)$/', $line, $matches)) {
			$this->set_normal($matches['time'], $matches['nick'], $matches['line']);
		} elseif (preg_match('/^'.$timestamp.'-!- (?<nick>\S+) \[\S+\] has joined [#&!+]\S+$/', $line, $matches)) {
			$this->set_join($matches['time'], $matches['nick']);
		} elseif (preg_match('/^'.$timestamp.'-!- (?<nick>\S+) \[\S+\] has quit \[.*\]$/', $line, $matches)) {
			$this->set_quit($matches['time'], $matches['nick']);
		} elseif (preg_match('/^'.$timestamp.'\* (?<line>(?<nick_performing>\S+) ((?<slap>slaps( (?<nick_undergoing>\S+)( .+)?))|(.+)))$/i', $line, $matches, PREG_UNMATCHED_AS_NULL)) {
			if (!is_null($matches['slap'])) {
				$this->set_slap($matches['time'], $matches['nick_performing'], $matches['nick_undergoing']);
			}

			$this->set_action($matches['time'], $matches['nick_performing'], $matches['line']);
		} elseif (preg_match('/^'.$timestamp.'-!- (?<nick_performing>\S+) is now known as (?<nick_undergoing>\S+)$/', $line, $matches)) {
			$this->set_nickchange($matches['time'], $matches['nick_performing'], $matches['nick_undergoing']);
		} elseif (preg_match('/^'.$timestamp.'-!- (ServerMode|mode)\/[#&!+]\S+ \[(?<modes>[-+][ov]+([-+][ov]+)?) (?<nicks_undergoing>\S+( \S+)*)\] by (?<nick_performing>\S+)(, \S+)*$/', $line, $matches)) {
			$mode_num = 0;
			$nicks_undergoing = explode(' ', $matches['nicks_undergoing']);

			for ($i = 0, $j = strlen($matches['modes']); $i < $j; ++$i) {
				$mode = $matches['modes'][$i];

				if ($mode === '-' || $mode === '+') {
					$mode_sign = $mode;
				} else {
					$this->set_mode($matches['time'], $matches['nick_performing'], $nicks_undergoing[$mode_num], $mode_sign.$mode);
					++$mode_num;
				}
			}
		} elseif (preg_match('/^'.$timestamp.'-!- (?<nick>\S+) \[\S+\] has left [#&!+]\S+ \[.*\]$/', $line, $matches)) {
			$this->set_part($matches['time'], $matches['nick']);
		} elseif (preg_match('/^'.$timestamp.'-!- (?<nick>\S+) changed the topic of [#&!+]\S+ to: (?<line>.+)$/', $line, $matches)) {
			$this->set_topic($matches['time'], $matches['nick'], $matches['line']);
		} elseif (preg_match('/^'.$timestamp.'-!- (?<line>(?<nick_undergoing>\S+) was kicked from [#&!+]\S+ by (?<nick_performing>\S+) \[.*\])$/', $line, $matches)) {
			$this->set_kick($matches['time'], $matches['nick_performing'], $matches['nick_undergoing'], $matches['line']);
		} else {
			out::put('debug', 'skipping line '.$this->linenum.': \''.$line.'\'');
		}
	}
}
