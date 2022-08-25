<?php declare(strict_types=1);

/**
 * Copyright (c) 2012-2022, Jos de Ruijter <jos@dutnie.nl>
 */

class parser_hexchat extends parser
{
	protected function parse_line(string $line): void
	{
		$timestamp = '\S{3} \d{2} (?<time>\d{2}:\d{2}(:\d{2})?) ';

		if (preg_match('/^'.$timestamp.'<(?<nick>\S+)> (?<line>.+)$/n', $line, $matches)) {
			$this->set_normal($matches['time'], $matches['nick'], $matches['line']);
		} elseif (preg_match('/^'.$timestamp.'\* (?<nick>\S+) \(\S+\) has joined/n', $line, $matches)) {
			$this->set_join($matches['time'], $matches['nick']);
		} elseif (preg_match('/^'.$timestamp.'\* (?<nick>\S+) has quit/n', $line, $matches)) {
			$this->set_quit($matches['time'], $matches['nick']);
		} elseif (preg_match('/^'.$timestamp.'\* (?<line>(?<nick_performing>\S+) slaps (?<nick_undergoing>\S+).*)$/in', $line, $matches)) {
			$this->set_slap($matches['time'], $matches['nick_performing'], $matches['nick_undergoing']);
			$this->set_action($matches['time'], $matches['nick_performing'], $matches['line']);
		} elseif (preg_match('/^'.$timestamp.'\* (?<nick_performing>\S+) is now known as (?<nick_undergoing>\S+)$/n', $line, $matches)) {
			$this->set_nickchange($matches['time'], $matches['nick_performing'], $matches['nick_undergoing']);
		} elseif (preg_match('/^'.$timestamp.'\* (?<nick_performing>\S+) (?<mode_sign>gives|removes) (?<mode>channel operator status|voice) (to|from) (?<nicks_undergoing>\S+( \S+)*)$/n', $line, $matches)) {
			$nicks_undergoing = explode(' ', $matches['nicks_undergoing']);

			if ($matches['mode_sign'] === 'gives') {
				$mode_sign = '+';
			} else {
				$mode_sign = '-';
			}

			if ($matches['mode'] === 'channel operator status') {
				$mode = 'o';
			} else {
				$mode = 'v';
			}

			foreach ($nicks_undergoing as $nick_undergoing) {
				$this->set_mode($matches['time'], $matches['nick_performing'], $nick_undergoing, $mode_sign.$mode);
			}
		} elseif (preg_match('/^'.$timestamp.'\* (?<nick>\S+) \(\S+\) has left/n', $line, $matches)) {
			$this->set_part($matches['time'], $matches['nick']);
		} elseif (preg_match('/^'.$timestamp.'\* (?<nick>\S+) has changed the topic to: (?<line>.+)$/n', $line, $matches)) {
			$this->set_topic($matches['time'], $matches['nick'], $matches['line']);
		} elseif (preg_match('/^'.$timestamp.'\* (?<line>(?<nick_performing>\S+) has kicked (?<nick_undergoing>\S+) from \S+ )(?<reason>\(.*\))$/n', $line, $matches)) {
			$this->set_kick($matches['time'], $matches['nick_performing'], $matches['nick_undergoing'], $matches['line'].(preg_match('/^\( ?\)$/', $matches['reason']) ? '('.$matches['nick_undergoing'].')' : $matches['reason']));
		} else {
			out::put('debug', 'skipping line '.$this->linenum.': \''.$line.'\'');
		}
	}
}
