<?php

/**
 * Copyright (c) 2007-2015, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Class for handling URL data.
 */
class url
{
	private $fqdn = '';
	private $tld = '';
	private $url = '';
	private $uses = [];

	public function __construct($urldata)
	{
		$this->fqdn = $urldata['fqdn'];
		$this->tld = $urldata['tld'];
		$this->url = $urldata['url'];
	}

	public function add_uses($datetime, $nick)
	{
		$this->uses[] = [$datetime, $nick];
	}

	public function write_data($sqlite3)
	{
		/**
		 * Write data to database table "fqdns".
		 */
		if ($this->fqdn !== '') {
			if (($fid = $sqlite3->querySingle('SELECT fid FROM fqdns WHERE fqdn = \''.$this->fqdn.'\'')) === false) {
				output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			if (is_null($fid)) {
				$sqlite3->exec('INSERT INTO fqdns (fid, fqdn, tld) VALUES (NULL, \''.$this->fqdn.'\', \''.$this->tld.'\')') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
				$fid = $sqlite3->lastInsertRowID();
			}
		}

		/**
		 * Write data to database tables "urls" and "uid_urls".
		 */
		if (($lid = $sqlite3->querySingle('SELECT lid FROM urls WHERE url = \''.$sqlite3->escapeString($this->url).'\'')) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		if (is_null($lid)) {
			$sqlite3->exec('INSERT INTO urls (lid, url'.($this->fqdn !== '' ? ', fid' : '').') VALUES (NULL, \''.$sqlite3->escapeString($this->url).'\''.($this->fqdn !== '' ? ', '.$fid : '').')') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$lid = $sqlite3->lastInsertRowID();
		}

		foreach ($this->uses as $key => $values) {
			$sqlite3->exec('INSERT INTO uid_urls (uid, lid, datetime) VALUES ((SELECT uid FROM uid_details WHERE csnick = \''.$values[1].'\'), '.$lid.', DATETIME(\''.$values[0].'\'))') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}
	}
}
