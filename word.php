<?php

/**
 * Copyright (c) 2007-2015, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Class for handling word data.
 */
class word
{
	use base;
	private $word = '';
	private $length = 0;
	private $total = 0;

	public function __construct($word)
	{
		$this->word = $word;
	}

	public function write_data($sqlite3)
	{
		/**
		 * Write data to database table "words".
		 */
		$sqlite3->exec('INSERT OR IGNORE INTO words (word, length, total) VALUES (\''.$sqlite3->escapeString($this->word).'\', '.$this->length.', '.$this->total.')') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$sqlite3->exec('UPDATE words SET total = total + '.$this->total.' WHERE CHANGES() = 0 AND word = \''.$sqlite3->escapeString($this->word).'\'') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
	}
}
