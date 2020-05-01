<?php declare(strict_types=1);

/**
 * Copyright (c) 2012-2020, Jos de Ruijter <jos@dutnie.nl>
 */

class parser_nodelog extends parser
{
	protected function parse_line(string $line): void
	{
		// "Normal" lines.
		if (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<nick>\S+): (?<line>.+)$/', $line, $matches)) {
			$this->set_normal($matches['time'], $matches['nick'], $matches['line']);

		// "Join" lines.
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<nick>\S+) has joined the channel$/', $line, $matches)) {
			$this->set_join($matches['time'], $matches['nick']);

		// "Part" lines.
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<nick>\S+) has left the channel$/', $line, $matches)) {
			$this->set_part($matches['time'], $matches['nick']);

		// Skip everything else.
		} elseif ($line !== '') {
			output::msg('debug', 'skipping line '.$this->linenum.': \''.$line.'\'');
		}
	}
}
