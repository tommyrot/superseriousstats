<?php

/**
 * Copyright (c) 2007-2015, Jos de Ruijter <jos@dutnie.nl>
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
 * Class for handling topic data.
 */
class topic
{
	use base;
	private $datetime = [];
	private $topic = '';

	public function __construct($topic)
	{
		$this->topic = $topic;
	}

	public function add_datetime($datetime)
	{
		$this->datetime[] = $datetime;
	}

	/**
	 * Write data to database tables "topics" and "uid_topics".
	 */
	public function write_data($sqlite3, $uid)
	{
		if (($tid = $sqlite3->querySingle('SELECT tid FROM topics WHERE topic = \''.$sqlite3->escapeString($this->topic).'\'')) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		if (is_null($tid)) {
			$sqlite3->exec('INSERT INTO topics (tid, topic) VALUES (NULL, \''.$sqlite3->escapeString($this->topic).'\')') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$tid = $sqlite3->lastInsertRowID();
		}

		foreach ($this->datetime as $datetime) {
			$sqlite3->exec('INSERT INTO uid_topics (uid, tid, datetime) VALUES ('.$uid.', '.$tid.', DATETIME(\''.$datetime.'\'))') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}
	}
}
