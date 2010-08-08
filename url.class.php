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
 * Class for handling url data.
 */
final class url extends base
{
	/**
	 * Variables that shouldn't be tampered with.
	 */
	protected $csurl = '';
	protected $firstused = '';
	protected $lastused = '';
	protected $mysqli;
	protected $total = 0;

	public function __construct($csurl)
	{
		$this->csurl = $csurl;
	}

	/**
	 * Also keep track of firstused.
	 */
	public function set_lastused($datetime)
	{
		if ($this->firstused == '' || strtotime($datetime) < strtotime($this->firstused)) {
			$this->firstused = $datetime;
		}

		if ($this->lastused == '' || strtotime($datetime) > strtotime($this->lastused)) {
			$this->lastused = $datetime;
		}
	}

	public function write_data($mysqli, $uid)
	{
		$this->mysqli = $mysqli;

		/**
		 * Write data to database table "user_urls".
		 */
		$query = @mysqli_query($this->mysqli, 'select `lid` from `user_urls` where `csurl` = \''.mysqli_real_escape_string($this->mysqli, $this->csurl).'\' group by `csurl`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			$createdquery = $this->create_insert_query(array('csurl', 'total', 'firstused', 'lastused'));
			@mysqli_query($this->mysqli, 'insert into `user_urls` set `lid` = 0, `uid` = '.$uid.','.$createdquery) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		} else {
			$result = mysqli_fetch_object($query);
			$query = @mysqli_query($this->mysqli, 'select * from `user_urls` where `lid` = '.$result->lid.' and `uid` = '.$uid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query);

			if (empty($rows)) {
				$createdquery = $this->create_insert_query(array('csurl', 'total', 'firstused', 'lastused'));
				@mysqli_query($this->mysqli, 'insert into `user_urls` set `lid` = '.$result->lid.', `uid` = '.$uid.','.$createdquery) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			} else {
				$result = mysqli_fetch_object($query);
				$createdquery = $this->create_update_query($result, array('lid', 'uid'));

				if (!is_null($createdquery)) {
					@mysqli_query($mysqli, 'update `user_urls` set'.$createdquery.' where `lid` = '.$result->lid.' and `uid` = '.$uid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				}
			}
		}
	}
}

?>
