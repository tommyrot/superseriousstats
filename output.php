<?php

/**
 * Copyright (c) 2010-2015, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Class for handling output messages.
 */
class output
{
	private static $outputbits = 1;
	private static $prevmessage = '';

	private function __construct()
	{
		/**
		 * This is a static class and should not be instantiated.
		 */
	}

	/**
	 * Output given messages to the console.
	 */
	public static function output($type, $message)
	{
		/**
		 * Avoid repeating the same message multiple times in a row, e.g. repeated
		 * lines and mode errors.
		 */
		if ($message === self::$prevmessage) {
			return null;
		}

		$datetime = date('M d H:i:s');
		self::$prevmessage = $message;

		if (substr($datetime, 4, 1) === '0') {
			$datetime = substr_replace($datetime, ' ', 4, 1);
		}

		switch ($type) {
			case 'critical':
				/**
				 * This type of message will always display and is followed up by the termination of the
				 * program.
				 */
				echo $datetime.' [C] '.$message."\n";
				exit;
			case 'notice':
				if (self::$outputbits & 1) {
					echo $datetime.' [ ] '.$message."\n";
				}

				break;
			case 'debug':
				if (self::$outputbits & 2) {
					echo $datetime.' [D] '.$message."\n";
				}

				break;
		}
	}

	/**
	 * Set the amount of bits corresponding to the type(s) of output messages displayed. By default all but debug
	 * messages will be displayed. This can be changed in the configuration file.
	 *  0  Critical events (will always display)
	 *  1  Notices
	 *  2  Debug messages
	 */
	public static function set_outputbits($outputbits)
	{
		self::$outputbits = $outputbits;
	}
}
