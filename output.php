<?php

/**
 * Copyright (c) 2010-2019, Jos de Ruijter <jos@dutnie.nl>
 */

declare(strict_types=1);

/**
 * Class for handling output messages.
 */
class output
{
	private static int $verbosity = 1;
	private static string $prev_message = '';

	private function __construct()
	{
		/**
		 * This is a static class and should not be instantiated.
		 */
	}

	/**
	 * Output a given message to the console.
	 */
	public static function output(string $type, string $message): void
	{
		$datetime = date('M d H:i:s');

		if (substr($datetime, 4, 1) === '0') {
			$datetime = substr_replace($datetime, ' ', 4, 1);
		}

		/**
		 * Critical messages will always display and are followed by the termination of
		 * the program.
		 */
		if ($type === 'critical') {
			exit($datetime.' [C] '.$message."\n");
		}

		/**
		 * Avoid repeating the same message multiple times in a row, e.g. repeated lines
		 * and errors related to mode changes.
		 */
		if ($message === self::$prev_message) {
			return;
		}

		if ($type === 'notice') {
			if (self::$verbosity & 1) {
				echo $datetime.' [ ] '.$message."\n";
			}
		} elseif ($type === 'debug') {
			if (self::$verbosity & 2) {
				echo $datetime.' [D] '.$message."\n";
			}
		}

		/**
		 * Remember the last message displayed.
		 */
		self::$prev_message = $message;
	}

	/**
	 * Set output verbosity. This is a bitwise value which defaults to 1 until
	 * overridden during init with the value from the config file.
	 *
	 *  0  Critical events (will always display)
	 *  1  Notices
	 *  2  Debug messages
	 */
	public static function set_verbosity(int $verbosity): void
	{
		self::$verbosity = $verbosity;
	}
}
