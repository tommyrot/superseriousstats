<?php

/**
 * Copyright (c) 2012-2015, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Parse instructions for the nodelog logfile format.
 *
 * Line         Format                                                  Notes
 * ---------------------------------------------------------------------------------------------------------------------
 * Normal       NICK: MSG                                               Skip empty lines.
 * Join         NICK has joined the channel
 * Part         NICK has left the channel
 * ---------------------------------------------------------------------------------------------------------------------
 *
 * Notes:
 * - normalize_line() scrubs all lines before passing them on to parse_line().
 * - Given that nicks can't contain ":" the order of the regular expressions below is irrelevant (current order aims for
 *   best performance).
 * - Booooring...
 */
class parser_nodelog extends parser
{
	/**
	 * Parse a line for various chat data.
	 */
	protected function parse_line($line)
	{
		/**
		 * "Normal" lines.
		 */
		if (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<nick>\S+): (?<line>.+)$/', $line, $matches)) {
			$this->set_normal($matches['time'], $matches['nick'], $matches['line']);

		/**
		 * "Join" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<nick>\S+) has joined the channel$/', $line, $matches)) {
			$this->set_join($matches['time'], $matches['nick']);

		/**
		 * "Part" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<nick>\S+) has left the channel$/', $line, $matches)) {
			$this->set_part($matches['time'], $matches['nick']);

		/**
		 * Skip everything else.
		 */
		} elseif ($line !== '') {
			output::output('debug', __METHOD__.'(): skipping line '.$this->linenum.': \''.$line.'\'');
		}
	}
}
