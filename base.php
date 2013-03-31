<?php

/**
 * Copyright (c) 2010-2013, Jos de Ruijter <jos@dutnie.nl>
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
 * Class with common functions.
 */
abstract class base
{
	/**
	 * Default settings for this script, which can be overridden in the configuration file. These variables should
	 * all appear in $settings_list[] along with their type.
	 */
	protected $outputbits = 3;

	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $prevoutput = array();

	final public function add_value($var, $value)
	{
		$this->$var += $value;
	}

	/**
	 * Create parts of the SQLite3 query.
	 */
	final protected function get_queryparts($sqlite3, $columns)
	{
		$queryparts = array();

		foreach ($columns as $key) {
			if (is_int($this->$key) && $this->$key != 0) {
				$queryparts['columnlist'][] = $key;
				$queryparts['values'][] = $this->$key;
				$queryparts['update-assignments'][] = $key.' = '.$key.' + '.$this->$key;
			} elseif (is_string($this->$key) && $this->$key != '') {
				$value = '\''.$sqlite3->escapeString($this->$key).'\'';
				$queryparts['columnlist'][] = $key;
				$queryparts['values'][] = $value;
				$queryparts['update-assignments'][] = $key.' = '.$value;
			}
		}

		return $queryparts;
	}

	final public function get_value($var)
	{
		return $this->$var;
	}

	/**
	 * Output given messages to the console.
	 */
	final protected function output($type, $msg)
	{
		/**
		 * Don't output the same thing twice, like mode errors and repeated lines.
		 */
		if (in_array($msg, $this->prevoutput)) {
			return null;
		}

		$this->prevoutput[] = $msg;
		$datetime = date('M d H:i:s');

		if (substr($datetime, 4, 1) === '0') {
			$datetime = substr_replace($datetime, ' ', 4, 1);
		}

		switch ($type) {
			case 'critical':
				if ($this->outputbits & 1) {
					echo $datetime.' [C] '.$msg."\n";
				}

				exit;
			case 'notice':
				if ($this->outputbits & 2) {
					echo $datetime.' [ ] '.$msg."\n";
				}

				break;
			case 'debug':
				if ($this->outputbits & 4) {
					echo $datetime.' [D] '.$msg."\n";
				}

				break;
		}
	}

	final public function set_value($var, $value)
	{
		$this->$var = $value;
	}
}

?>
