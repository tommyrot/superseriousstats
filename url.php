<?php

/**
 * Copyright (c) 2007-2020, Jos de Ruijter <jos@dutnie.nl>
 */

declare(strict_types=1);

/**
 * Class for handling URL data.
 */
class url
{
	private array $uses = [];
	private object $sqlite3;
	private string $fqdn = '';
	private string $tld = '';
	private string $url = '';

	public function __construct(array $url_components, object $sqlite3)
	{
		$this->fqdn = $url_components['fqdn'];
		$this->tld = $url_components['tld'];
		$this->url = $url_components['url'];
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
		 * Write data to database table "fqdns".
		 */
		if ($this->fqdn !== '') {
			if (($fid = $this->sqlite3->querySingle('SELECT fid FROM fqdns WHERE fqdn = \''.$this->fqdn.'\'')) === false) {
				output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
			}

			if (is_null($fid)) {
				$this->sqlite3->exec('INSERT INTO fqdns (fid, fqdn, tld) VALUES (NULL, \''.$this->fqdn.'\', \''.$this->tld.'\')') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
				$fid = $this->sqlite3->lastInsertRowID();
			}
		}

		/**
		 * Write data to database tables "urls" and "uid_urls".
		 */
		if (($lid = $this->sqlite3->querySingle('SELECT lid FROM urls WHERE url = \''.preg_replace('/\'/', '\'\'', $this->url).'\'')) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
		}

		if (is_null($lid)) {
			$this->sqlite3->exec('INSERT INTO urls (lid, url'.($this->fqdn !== '' ? ', fid' : '').') VALUES (NULL, \''.preg_replace('/\'/', '\'\'', $this->url).'\''.($this->fqdn !== '' ? ', '.$fid : '').')') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
			$lid = $this->sqlite3->lastInsertRowID();
		}

		foreach ($this->uses as [$datetime, $nick]) {
			$this->sqlite3->exec('INSERT INTO uid_urls (uid, lid, datetime) VALUES ((SELECT uid FROM uid_details WHERE csnick = \''.$nick.'\'), '.$lid.', DATETIME(\''.$datetime.'\'))') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
		}
	}
}
