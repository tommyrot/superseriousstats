<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2021, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Class for handling word data.
 */
class word
{
	use common;

	private int $total = 0;
	private string $firstused = '';
	private string $word = '';

	public function __construct(string $word, string $firstused)
	{
		$this->word = $word;
		$this->firstused = $firstused;
	}

	/**
	 * Store everything in the database.
	 */
	public function store_data(): void
	{
		/**
		 * Store data in database table "words".
		 */
		db::query_exec('INSERT INTO words (word, length, total, firstused) VALUES (\''.$this->word.'\', LENGTH(\''.$this->word.'\'), '.$this->total.', \''.$this->firstused.'\') ON CONFLICT (word) DO UPDATE SET total = total + excluded.total');
	}
}
