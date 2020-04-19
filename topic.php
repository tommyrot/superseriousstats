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

	public function __construct(string $topic)
	{
		$this->topic = $topic;
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
		if (($tid = sss::$db->querySingle('SELECT tid FROM topics WHERE topic = \''.preg_replace('/\'/', '\'\'', $this->topic).'\'')) === false) {
			output::msg('critical', 'fail in '.basename(__FILE__).'#'.__LINE__.': '.sss::$db->lastErrorMsg());
		}

		if (is_null($tid)) {
			sss::$db->exec('INSERT INTO topics (tid, topic) VALUES (NULL, \''.preg_replace('/\'/', '\'\'', $this->topic).'\')') or output::msg('critical', 'fail in '.basename(__FILE__).'#'.__LINE__.': '.sss::$db->lastErrorMsg());
			$tid = sss::$db->lastInsertRowID();
		}

		foreach ($this->uses as [$datetime, $nick]) {
			sss::$db->exec('INSERT INTO uid_topics (uid, tid, datetime) VALUES ((SELECT uid FROM uid_details WHERE csnick = \''.$nick.'\'), '.$tid.', DATETIME(\''.$datetime.'\'))') or output::msg('critical', 'fail in '.basename(__FILE__).'#'.__LINE__.': '.sss::$db->lastErrorMsg());
		}
	}
}
