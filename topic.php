<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2021, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Class for handling topic data.
 */
class topic
{
	private array $uses = [];
	private string $topic = '';

	public function __construct(string $topic)
	{
		$this->topic = $topic;
	}

	/**
	 * Record each and every use of this topic.
	 */
	public function add_uses(string $datetime, string $nick): void
	{
		$this->uses[] = [$datetime, $nick];
	}

	/**
	 * Store everything in the database.
	 */
	public function store_data(): void
	{
		/**
		 * Store data in database tables "topics" and "uid_topics".
		 */
		if (is_null($tid = db::query_single_col('SELECT tid FROM topics WHERE topic = \''.preg_replace('/\'/', '\'\'', $this->topic).'\''))) {
			$tid = db::query_exec('INSERT INTO topics (topic) VALUES (\''.preg_replace('/\'/', '\'\'', $this->topic).'\')');
		}

		foreach ($this->uses as [$datetime, $nick]) {
			db::query_exec('INSERT INTO uid_topics (uid, tid, datetime) VALUES ((SELECT uid FROM uid_details WHERE csnick = \''.$nick.'\'), '.$tid.', DATETIME(\''.$datetime.'\'))');
		}
	}
}
