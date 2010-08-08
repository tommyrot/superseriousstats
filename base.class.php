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

		foreach ($columns as $c) {
			if (is_int($this->$c) && $this->$c != 0) {
				$query .= ' `'.$c.'` = '.$this->$c.',';
				$changes = true;
			} elseif (is_string($this->$c) && $this->$c != '') {
				$query .= ' `'.$c.'` = \''.mysqli_real_escape_string($this->mysqli, $this->$c).'\',';
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

		foreach ($columns as $c => $v) {
			if (in_array($c, $exclude)) {
				continue;
			}

			if ($c == 'firstseen' && $v != '0000-00-00 00:00:00' && strtotime($this->firstseen) >= strtotime($v)) {
				continue;
			} elseif ($c == 'firstused' && $v != '0000-00-00 00:00:00' && strtotime($this->firstused) >= strtotime($v)) {
				continue;
			} elseif ($c == 'lastseen' && $v != '0000-00-00 00:00:00' && strtotime($this->lastseen) <= strtotime($v)) {
				continue;
			} elseif ($c == 'lastused' && $v != '0000-00-00 00:00:00' && strtotime($this->lastused) <= strtotime($v)) {
				continue;
			} elseif ($c == 'lasttalked' && $v != '0000-00-00 00:00:00' && strtotime($this->lasttalked) <= strtotime($v)) {
				continue;
			} elseif ($c == 'topmonologue' && $this->topmonologue <= $v) {
				continue;
			}

			if (is_int($this->$c) && $this->$c != 0) {
				$query .= ' `'.$c.'` = '.($v + $this->$c).',';
				$changes = true;
			} elseif (is_string($this->$c) && $this->$c != '') {
				$query .= ' `'.$c.'` = \''.mysqli_real_escape_string($this->mysqli, $this->$c).'\',';
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
			case 'debug':
				if ($this->outputbits & 8) {
					echo $datetime.' [debug] '.$msg."\n";
				}

				break;
			case 'notice':
				if ($this->outputbits & 4) {
					echo $datetime.' [notice] '.$msg."\n";
				}

				break;
			case 'warning':
				if ($this->outputbits & 2) {
					echo $datetime.' [warning] '.$msg."\n";
				}

				break;
			case 'critical':
				if ($this->outputbits & 1) {
					echo $datetime.' [critical] '.$msg."\n";
				}

				exit;
		}
	}

	final public function set_value($var, $value)
	{
		$this->$var = $value;
	}
}

?>
