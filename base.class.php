<?php

/**
 * Copyright (c) 2010, Jos de Ruijter <jos@dutnie.nl>
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
	 * Default settings for this script, can be overridden in the config file.
	 * These should all appear in $settings_list[] along with their type.
	 */
	protected $outputbits = 1;

	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $prevoutput = array();

	final public function add_value($var, $value)
	{
		$this->$var += $value;
	}

	/**
	 * Create part of the mysql insert query containing new data.
	 */
	final protected function create_insert_query($columns)
	{
		$changes = false;
		$query = '';

		foreach ($columns as $key) {
			if (is_int($this->$key) && $this->$key != 0) {
				$query .= ' `'.$key.'` = '.$this->$key.',';
				$changes = true;
			} elseif (is_string($this->$key) && $this->$key != '') {
				$query .= ' `'.$key.'` = \''.mysqli_real_escape_string($this->mysqli, $this->$key).'\',';
				$changes = true;
			}
		}

		if ($changes) {
			return rtrim($query, ',');
		} else {
			return;
		}
	}

	/**
	 * Create part of the mysql update query containing new data.
	 */
	final protected function create_update_query($columns, $exclude)
	{
		$changes = false;
		$query = '';

		foreach ($columns as $key => $value) {
			if (in_array($key, $exclude)) {
				continue;
			}

			if ($key == 'firstseen' && $value != '0000-00-00 00:00:00' && strtotime($this->firstseen) >= strtotime($value)) {
				continue;
			} elseif ($key == 'firstused' && $value != '0000-00-00 00:00:00' && strtotime($this->firstused) >= strtotime($value)) {
				continue;
			} elseif ($key == 'lastseen' && $value != '0000-00-00 00:00:00' && strtotime($this->lastseen) <= strtotime($value)) {
				continue;
			} elseif ($key == 'lastused' && $value != '0000-00-00 00:00:00' && strtotime($this->lastused) <= strtotime($value)) {
				continue;
			} elseif ($key == 'lasttalked' && $value != '0000-00-00 00:00:00' && strtotime($this->lasttalked) <= strtotime($value)) {
				continue;
			} elseif ($key == 'topmonologue' && $this->topmonologue <= (int) $value) {
				continue;
			}

			if (is_int($this->$key) && $this->$key != 0) {
				$query .= ' `'.$key.'` = '.((int) $value + $this->$key).',';
				$changes = true;
			} elseif (is_string($this->$key) && $this->$key != '') {
				$query .= ' `'.$key.'` = \''.mysqli_real_escape_string($this->mysqli, $this->$key).'\',';
				$changes = true;
			}
		}

		if ($changes) {
			return rtrim($query, ',');
		} else {
			return;
		}
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
			return;
		}

		$this->prevoutput[] = $msg;
		$datetime = date('M d H:i:s');

		if (substr($datetime, 4, 1) === '0') {
			$datetime = substr_replace($datetime, ' ', 4, 1);
		}

		switch ($type) {
			case 'critical':
				if ($this->outputbits & 1) {
					echo $datetime.' [critical] '.$msg."\n";
				}

				exit;
			case 'warning':
				if ($this->outputbits & 2) {
					echo $datetime.' [warning] '.$msg."\n";
				}

				break;
			case 'notice':
				if ($this->outputbits & 4) {
					echo $datetime.' [notice] '.$msg."\n";
				}

				break;
			case 'debug':
				if ($this->outputbits & 8) {
					echo $datetime.' [debug] '.$msg."\n";
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
