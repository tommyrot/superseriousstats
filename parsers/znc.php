<?php declare(strict_types=1);

/**
 * Copyright (c) 2011-2023, Jos de Ruijter <jos@dutnie.nl>
 */

class parser_znc extends parser
{
	protected function parse_line(string $line): void
	{
		$timestamp = '\[(?<time>\d{2}:\d{2}(:\d{2})?)] ';

		if (preg_match('/^'.$timestamp.'<(?<nick>\S+)> ?(?<line>.*)$/n', $line, $matches)) {
			$this->set_normal($matches['time'], $matches['nick'], $matches['line']);
		} elseif (preg_match('/^'.$timestamp.'\*\*\* Joins: (?<nick>\S+)/n', $line, $matches)) {
			$this->set_join($matches['time'], $matches['nick']);
		} elseif (preg_match('/^'.$timestamp.'\*\*\* Quits: (?<nick>\S+)/n', $line, $matches)) {
			$this->set_quit($matches['time'], $matches['nick']);
		} elseif (preg_match('/^'.$timestamp.'\* (?<line>(?<nick_performing>\S+)((?<slap> slaps( (?<nick_undergoing>\S+).*)?)|.*))$/in', $line, $matches, PREG_UNMATCHED_AS_NULL)) {
			if (!is_null($matches['slap'])) {
				$this->set_slap($matches['time'], $matches['nick_performing'], $matches['nick_undergoing'] ?? '');
			}

			$this->set_action($matches['time'], $matches['nick_performing'], $matches['line']);
		} elseif (preg_match('/^'.$timestamp.'\*\*\* (?<nick_performing>\S+) is now known as (?<nick_undergoing>\S+)$/n', $line, $matches)) {
			$this->set_nickchange($matches['time'], $matches['nick_performing'], $matches['nick_undergoing']);
		} elseif (preg_match('/^'.$timestamp.'\*\*\* (?<nick_performing>\S+) sets mode: (?<modes>[-+][ov]+([-+][ov]+)?) (?<nicks_undergoing>\S+( \S+)*)$/n', $line, $matches)) {
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
		} elseif (preg_match('/^'.$timestamp.'\*\*\* Parts: (?<nick>\S+)/n', $line, $matches)) {
			$this->set_part($matches['time'], $matches['nick']);
		} elseif (preg_match('/^'.$timestamp.'\*\*\* (?<nick>\S+) changes topic to \'(?<line>.*)\'$/n', $line, $matches)) {
			$this->set_topic($matches['time'], $matches['nick'], $matches['line']);
		} elseif (preg_match('/^'.$timestamp.'\*\*\* (?<line>(?<nick_undergoing>\S+) was kicked by (?<nick_performing>\S+) )(?<reason>\(.*\))$/n', $line, $matches)) {
			$this->set_kick($matches['time'], $matches['nick_performing'], $matches['nick_undergoing'], $matches['line'].(preg_match('/^\( ?\)$/', $matches['reason']) ? '('.$matches['nick_undergoing'].')' : $matches['reason']));
		} else {
			out::put('debug', 'skipping line '.$this->linenum.': \''.$line.'\'');
		}
	}
}
