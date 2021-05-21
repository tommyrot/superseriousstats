<?php declare(strict_types=1);

/**
 * Copyright (c) 2010-2020, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Class for handling output messages.
 */
class out
{
	private static int $verbosity = 1;
	private static string $message_prev = '';

	/**
	 * This is a static class and should not be instantiated.
	 */
	private function __construct() {}

	/**
	 * Output a given message to the console.
	 */
	public static function put(string $type, string $message): void
	{
		$datetime = preg_replace('/(?<=[a-z] )0/', ' ', date('M d H:i:s'));

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
		if ($message === self::$message_prev) {
			return;
		}

		if ($type === 'notice') {
			if (self::$verbosity >= 1) {
				echo $datetime.' [ ] '.$message."\n";
			}
		} elseif ($type === 'debug') {
			if (self::$verbosity >= 2) {
				echo $datetime.' [D] '.$message."\n";
			}
		}

		/**
		 * Remember the last message displayed.
		 */
		self::$message_prev = $message;
	}

	/**
	 * Set the level of output verbosity. All message types with a lower numeric
	 * value will also be displayed. Default value is 1 and can be overridden by
	 * using the command line flag -q (quiet) for 0 or -v (verbose) for 2.
	 *
	 *  0  Critical events (these will always display)
	 *  1  Notices
	 *  2  Debug messages
	 */
	public static function set_verbosity(int $verbosity): void
	{
		self::$verbosity = $verbosity;
	}
}
