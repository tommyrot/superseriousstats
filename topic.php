<?php

/**
 * Copyright (c) 2007-2020, Jos de Ruijter <jos@dutnie.nl>
 */

declare(strict_types=1);

/**
 * Class for handling topic data.
 */
class topic
{
	private array $uses = [];
	private string $topic = '';
	private SQLite3 $sqlite3;

	public function __construct(string $topic, SQLite3 $sqlite3)
	{
		$this->topic = $topic;
		$this->sqlite3 = $sqlite3;
	}

	public function add_uses(string $datetime, string $nick): void
	{
		$this->uses[] = [$datetime, $nick];
	}

	/**
	 * Store everything in the database.
	 */
	public function write_data(): void
	{
		/**
		 * Write data to database tables "topics" and "uid_topics".
		 */
		if (($tid = $this->sqlite3->querySingle('SELECT tid FROM topics WHERE topic = \''.preg_replace('/\'/', '\'\'', $this->topic).'\'')) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
		}

		if (is_null($tid)) {
			$this->sqlite3->exec('INSERT INTO topics (tid, topic) VALUES (NULL, \''.preg_replace('/\'/', '\'\'', $this->topic).'\')') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
			$tid = $this->sqlite3->lastInsertRowID();
		}

		foreach ($this->uses as [$datetime, $nick]) {
			$this->sqlite3->exec('INSERT INTO uid_topics (uid, tid, datetime) VALUES ((SELECT uid FROM uid_details WHERE csnick = \''.$nick.'\'), '.$tid.', DATETIME(\''.$datetime.'\'))') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
		}
	}
}
