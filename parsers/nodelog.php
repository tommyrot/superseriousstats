<?php declare(strict_types=1);

/**
 * Copyright (c) 2012-2020, Jos de Ruijter <jos@dutnie.nl>
 */

class parser_nodelog extends parser
{
	protected function parse_line(string $line): void
	{
		$timestamp = '\[(?<time>\d{2}:\d{2})(:\d{2})?\] ';

		if (preg_match('/^'.$timestamp.'(?<nick>\S+): (?<line>.+)$/', $line, $matches)) {
			$this->set_normal($matches['time'], $matches['nick'], $matches['line']);
		} elseif (preg_match('/^'.$timestamp.'(?<nick>\S+) has joined the channel$/', $line, $matches)) {
			$this->set_join($matches['time'], $matches['nick']);
		} elseif (preg_match('/^'.$timestamp.'(?<nick>\S+) has left the channel$/', $line, $matches)) {
			$this->set_part($matches['time'], $matches['nick']);
		} else {
			out::put('debug', 'skipping line '.$this->linenum.': \''.$line.'\'');
		}
	}
}
