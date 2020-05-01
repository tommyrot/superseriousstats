<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2020, Jos de Ruijter <jos@dutnie.nl>
 */

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

	/**
	 * Store everything in the database.
	 */
	public function write_data(): void
	{
		/**
		 * Write data to database table "words".
		 */
		db::$conn->exec('INSERT INTO words (word, length, total) VALUES (\''.$this->word.'\', '.$this->length.', '.$this->total.') ON CONFLICT (word) DO UPDATE SET total = total + '.$this->total) or output::msg('critical', 'fail in '.basename(__FILE__).'#'.__LINE__.': '.db::$conn->lastErrorMsg());
	}
}
