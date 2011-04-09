<?php

/**
 * Copyright (c) 2007-2011, Jos de Ruijter <jos@dutnie.nl>
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
 * Class for handling word data.
 */
final class word extends base
{
	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $word = '';
	protected $length = 0;
	protected $total = 0;

	public function __construct($word)
	{
		parent::__construct();
		$this->word = $word;
	}

	public function write_data($mysqli)
	{
		/**
		 * Write data to database table "words".
		 */
		@mysqli_query($mysqli, 'insert into `words` set `word` = \''.mysqli_real_escape_string($mysqli, $this->word).'\', `length` = '.$this->length.', `total` = '.$this->total.' on duplicate key update `total` = `total` + '.$this->total) or $this->output('critical', 'mysqli: '.mysqli_error($mysqli));
	}
}

?>
