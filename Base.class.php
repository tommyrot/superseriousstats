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
abstract class Base
{
	/**
	 * Default settings for this script, can be overridden in the config file.
	 * These should all appear in $settings_list[] along with their type.
	 */
	protected $outputbits = 1;

	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $prevOutput = array();

	/**
	 * Add a value to a variable.
	 */
	final public function addValue($var, $value)
	{
		$this->$var += $value;
	}

	/**
	 * Create part of the MySQL query containing new data.
	 */
	final protected function createInsertQuery($columns) {
		$changes = FALSE;

		foreach ($columns as $c) {
			if (is_int($this->$c) && $this->$c != 0) {
				$query .= ' `'.$c.'` = '.$this->$c.',';
				$changes = TRUE;
			} elseif (is_string($this->$c) && $this->$c != '') {
				$query .= ' `'.$c.'` = \''.mysqli_real_escape_string($this->mysqli, $this->$c).'\',';
				$changes = TRUE;
			}
		}

		if ($changes) {
			return rtrim($query, ',');
		} else {
			return NULL;
		}
	}

	/**
	 * Create part of the MySQL query containing updated data.
	 */
	final protected function createUpdateQuery($columns, $exclude) {
		$changes = FALSE;

		foreach ($columns as $c => $v) {
			if (in_array($c, $exclude)) {
				continue;
			}

			if ($c == 'firstSeen' && strtotime($this->firstSeen) >= strtotime($v)) {
				continue;
			} elseif ($c == 'firstUsed' && strtotime($this->firstUsed) >= strtotime($v)) {
				continue;
			} elseif ($c == 'lastSeen' && strtotime($this->lastSeen) <= strtotime($v)) {
				continue;
			} elseif ($c == 'lastUsed' && strtotime($this->lastUsed) <= strtotime($v)) {
				continue;
			} elseif ($c == 'lastTalked' && strtotime($this->lastTalked) <= strtotime($v)) {
				continue;
			} elseif ($c == 'topMonologue' && $this->topMonologue <= $v) {
				continue;
			}

			if (is_int($this->$c) && $this->$c != 0) {
				$query .= ' `'.$c.'` = '.($v + $this->$c).',';
				$changes = TRUE;
			} elseif (is_string($this->$c) && $this->$c != '') {
				$query .= ' `'.$c.'` = \''.mysqli_real_escape_string($this->mysqli, $this->$c).'\',';
				$changes = TRUE;
			}
		}

		if ($changes) {
			return rtrim($query, ',');
		} else {
			return NULL;
		}
	}

	/**
	 * Get the value of a variable.
	 */
	final public function getValue($var)
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
		if (in_array($msg, $this->prevOutput)) {
			return;
		}

		$this->prevOutput[] = $msg;
		$dateTime = date('M d H:i:s');

		if (substr($dateTime, 4, 1) === '0') {
			$dateTime = substr_replace($dateTime, ' ', 4, 1);
		}

		switch ($type) {
			case 'debug':
				if ($this->outputbits & 8) {
					echo $dateTime.' [debug] '.$msg."\n";
				}

				break;
			case 'notice':
				if ($this->outputbits & 4) {
					echo $dateTime.' [notice] '.$msg."\n";
				}

				break;
			case 'warning':
				if ($this->outputbits & 2) {
					echo $dateTime.' [warning] '.$msg."\n";
				}

				break;
			case 'critical':
				if ($this->outputbits & 1) {
					echo $dateTime.' [critical] '.$msg."\n";
				}

				exit;
		}
	}

	/**
	 * Set the value of a variable.
	 */
	final public function setValue($var, $value)
	{
		$this->$var = $value;
	}
}

?>
