<?php declare(strict_types=1);

/**
 * Copyright (c) 2021-2023, Jos de Ruijter <jos@dutnie.nl>
 */

class parser_catgirl extends parser
{
	protected function parse_line(string $line): void
	{
		$timestamp = '\[\d{4}-\d{2}-\d{2}T(?<time>\d{2}:\d{2}:\d{2})\+\d{4}] ';

		if (preg_match('/^'.$timestamp.'<(?<nick>\S+)> ?(?<line>.*)$/', $line, $matches)) {
			$this->set_normal($matches['time'], $matches['nick'], $matches['line']);
		} elseif (preg_match('/^'.$timestamp.'(?<nick>\S+) arrives in/', $line, $matches)) {
			$this->set_join($matches['time'], $matches['nick']);
		} elseif (preg_match('/^'.$timestamp.'(?<nick>\S+) leaves:/', $line, $matches)) {
			$this->set_quit($matches['time'], $matches['nick']);
		} elseif (preg_match('/^'.$timestamp.'\* (?<line>(?<nick_performing>\S+) ((?<slap>slaps (?<nick_undergoing>\S+).*)|.+))$/in', $line, $matches, PREG_UNMATCHED_AS_NULL)) {
			if (!is_null($matches['slap'])) {
				$this->set_slap($matches['time'], $matches['nick_performing'], $matches['nick_undergoing']);
			}

			$this->set_action($matches['time'], $matches['nick_performing'], $matches['line']);
		} elseif (preg_match('/^'.$timestamp.'(?<nick_performing>\S+) is now known as (?<nick_undergoing>\S+)$/', $line, $matches)) {
			$this->set_nickchange($matches['time'], $matches['nick_performing'], $matches['nick_undergoing']);
		} elseif (preg_match('/^'.$timestamp.'(?<nick_performing>\S+) (un)?sets [~&@%+!]?(?<nick_undergoing>\S+) (?<mode>[-+][ov])/n', $line, $matches)) {
			$this->set_mode($matches['time'], $matches['nick_performing'], $matches['nick_undergoing'], $matches['mode']);
		} elseif (preg_match('/^'.$timestamp.'(?<nick>\S+) leaves/', $line, $matches)) {
			$this->set_part($matches['time'], $matches['nick']);
		} elseif (preg_match('/^'.$timestamp.'(?<nick>\S+) places a new sign in \S+: (?<line>.+)$/', $line, $matches)) {
			$this->set_topic($matches['time'], $matches['nick'], $matches['line']);
		} elseif (preg_match('/^'.$timestamp.'(?<line>(?<nick_performing>\S+) kicks (?<nick_undergoing>\S+) out of \S+:)(?<reason>.*)$/', $line, $matches)) {
			$this->set_kick($matches['time'], $matches['nick_performing'], $matches['nick_undergoing'], $matches['line'].($matches['reason'] === '' ? ' '.$matches['nick_undergoing'] : $matches['reason']));
		} else {
			out::put('debug', 'skipping line '.$this->linenum.': \''.$line.'\'');
		}
	}
}
