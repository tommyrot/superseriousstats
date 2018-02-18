<?php

/**
 * Copyright (c) 2007-2018, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Class for handling topic data.
 */
class topic
{
	private $topic = '';
	private $uses = [];

	public function __construct($topic)
	{
		$this->topic = $topic;
	}

	public function add_uses($datetime, $nick)
	{
		$this->uses[] = [$datetime, $nick];
	}

	/**
	 * Write data to database tables "topics" and "uid_topics".
	 */
	public function write_data($sqlite3)
	{
		if (($tid = $sqlite3->querySingle('SELECT tid FROM topics WHERE topic = \''.$sqlite3->escapeString($this->topic).'\'')) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		if (is_null($tid)) {
			$sqlite3->exec('INSERT INTO topics (tid, topic) VALUES (NULL, \''.$sqlite3->escapeString($this->topic).'\')') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$tid = $sqlite3->lastInsertRowID();
		}

		foreach ($this->uses as $key => list($datetime, $nick)) {
			$sqlite3->exec('INSERT INTO uid_topics (uid, tid, datetime) VALUES ((SELECT uid FROM uid_details WHERE csnick = \''.$nick.'\'), '.$tid.', DATETIME(\''.$datetime.'\'))') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}
	}
}
