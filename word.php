<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2020, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Class for handling word data.
 */
class word
{
	use common;

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
	public function store_data(): void
	{
		/**
		 * Store data in database table "words".
		 */
		db::query_exec('INSERT INTO words (word, length, total) VALUES (\''.$this->word.'\', '.$this->length.', '.$this->total.') ON CONFLICT (word) DO UPDATE SET total = total + '.$this->total);
	}
}
