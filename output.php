<?php

/**
 * Copyright (c) 2010-2015, Jos de Ruijter <jos@dutnie.nl>
 *
 * Permission to use, copy, modify, and/or distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

/**
 * Class for handling output messages.
 */
class output
{
	/**
	 * By default all but debug messages will be displayed. This can be changed in the configuration file.
	 */
	private static $outputbits = 1;

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
		$datetime = date('M d H:i:s');

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
	 * Used to set the amount of bits corresponding to the type of output messages displayed.
	 *  -  Critical events (will always display)
	 *  1  Notices
	 *  2  Debug messages
	 */
	public static function set_outputbits($outputbits)
	{
		self::$outputbits = $outputbits;
	}
}
