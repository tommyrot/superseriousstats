<?php

/**
 * Copyright (c) 2007-2009, Jos de Ruijter <jos@dutnie.nl>
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
 * Class for handling URL data.
 */
final class URL extends URL_MySQL
{
	/**
	 * Variables used in database table "user_URLs".
	 */
	protected $csURL = '';
	protected $total = 0;
	protected $firstUsed = '';
	protected $lastUsed = '';

	/**
	 * Constructor.
	 */
	public function __construct($csURL)
	{
		$this->csURL = $csURL;
	}

        /**
         * Add a value to a variable.
         */
	public function addValue($var, $value)
	{
		$this->$var += $value;
	}

        /**
         * Store the date and time the URL was first and last typed in the channel.
         */
	public function lastUsed($dateTime)
	{
		if ($this->firstUsed == '' || $dateTime < $this->firstUsed) {
			$this->firstUsed = $dateTime;
		}

		if ($this->lastUsed == '' || $dateTime > $this->lastUsed) {
			$this->lastUsed = $dateTime;
		}
	}

        /**
         * Set the value of a variable.
         */
	public function setValue($var, $value)
	{
		$this->$var = $value;
	}
}

?>
