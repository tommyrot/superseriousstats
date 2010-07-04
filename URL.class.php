<?php

/**
 * Copyright (c) 2007-2010, Jos de Ruijter <jos@dutnie.nl>
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
final class URL extends Base
{
	/**
	 * Variables used in database table "user_URLs".
	 */
	protected $csURL = '';
	protected $total = 0;
	protected $firstUsed = '';
	protected $lastUsed = '';

	/**
	 * Variables that shouldn't be tampered with.
	 */
	protected $mysqli;

	/**
	 * Constructor.
	 */
	public function __construct($csURL)
	{
		$this->csURL = $csURL;
	}

	/**
	 * Store the date and time of when the URL was first and last typed in the channel.
	 */
	public function lastUsed($dateTime)
	{
		if ($this->firstUsed == '' || strtotime($dateTime) < strtotime($this->firstUsed)) {
			$this->firstUsed = $dateTime;
		}

		if ($this->lastUsed == '' || strtotime($dateTime) > strtotime($this->lastUsed)) {
			$this->lastUsed = $dateTime;
		}
	}

	/**
	 * Write URL data to the database.
	 */
	public function writeData($mysqli, $UID)
	{
		$this->mysqli = $mysqli;

		/**
		 * Write data to database table "user_URLs".
		 */
		$query = @mysqli_query($this->mysqli, 'SELECT `LID` FROM `user_URLs` WHERE `csURL` = \''.mysqli_real_escape_string($this->mysqli, $this->csURL).'\' GROUP BY `csURL`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			$createdQuery = $this->createInsertQuery(array('csURL', 'total', 'firstUsed', 'lastUsed'));
			@mysqli_query($this->mysqli, 'INSERT INTO `user_URLs` SET `LID` = 0, `UID` = '.$UID.','.$createdQuery) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		} else {
			$result = mysqli_fetch_object($query);
			$query = @mysqli_query($this->mysqli, 'SELECT * FROM `user_URLs` WHERE `LID` = '.$result->LID.' AND `UID` = '.$UID) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query);

			if (empty($rows)) {
				$createdQuery = $this->createInsertQuery(array('csURL', 'total', 'firstUsed', 'lastUsed'));
				@mysqli_query($this->mysqli, 'INSERT INTO `user_URLs` SET `LID` = '.$result->LID.', `UID` = '.$UID.','.$createdQuery) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			} else {
				$result = mysqli_fetch_object($query);
				$createdQuery = $this->createUpdateQuery($result, array('LID', 'UID'));

				if (!is_null($createdQuery)) {
					@mysqli_query($mysqli, 'UPDATE `user_URLs` SET'.$createdQuery.' WHERE `LID` = '.$result->LID.' AND `UID` = '.$UID) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
				}
			}
		}
	}
}

?>
