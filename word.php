<?php

/**
 * Copyright (c) 2007-2020, Jos de Ruijter <jos@dutnie.nl>
 */

declare(strict_types=1);

/**
 * Class for handling word data.
 */
class word
{
	use base;

	private int $length = 0;
	private int $total = 0;
	private string $word = '';

	public function __construct(string $word)
	{
		$this->word = $word;
	}

	public function write_data(object $sqlite3): void
	{
		/**
		 * Write data to database table "words".
		 */
		$sqlite3->exec('INSERT INTO words (word, length, total) VALUES (\''.$sqlite3->escapeString($this->word).'\', '.$this->length.', '.$this->total.') ON CONFLICT (word) DO UPDATE SET total = total + '.$this->total) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
	}
}
