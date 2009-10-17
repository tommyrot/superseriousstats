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
 * Super Serious Stats
 * Word_MySQL.class.php
 *
 * Class for writing word data to the database.
 */

abstract class Word_MySQL
{
	final public function writeData($mysqli)
	{
		/**
		 * Write data to database table "words".
		 */
		if (!@mysqli_query($mysqli, 'INSERT INTO `words` (`word`, `total`) VALUES (\''.mysqli_real_escape_string($mysqli, $this->word).'\', '.$this->total.') ON DUPLICATE KEY UPDATE `total` = `total` + '.$this->total))
			return FALSE;

		return TRUE;
	}
}

?>
