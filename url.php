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
	private string $fqdn = '';
	private string $tld = '';
	private string $url = '';

	public function __construct(array $url_components)
	{
		$this->fqdn = $url_components['fqdn'];
		$this->tld = $url_components['tld'];
		$this->url = $url_components['url'];
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
			if (($fid = db::$conn->querySingle('SELECT fid FROM fqdns WHERE fqdn = \''.$this->fqdn.'\'')) === false) {
				output::msg('critical', 'fail in '.basename(__FILE__).'#'.__LINE__.': '.db::$conn->lastErrorMsg());
			}

			if (is_null($fid)) {
				db::$conn->exec('INSERT INTO fqdns (fid, fqdn, tld) VALUES (NULL, \''.$this->fqdn.'\', \''.$this->tld.'\')') or output::msg('critical', 'fail in '.basename(__FILE__).'#'.__LINE__.': '.db::$conn->lastErrorMsg());
				$fid = db::$conn->lastInsertRowID();
			}
		}

		/**
		 * Write data to database tables "urls" and "uid_urls".
		 */
		if (($lid = db::$conn->querySingle('SELECT lid FROM urls WHERE url = \''.preg_replace('/\'/', '\'\'', $this->url).'\'')) === false) {
			output::msg('critical', 'fail in '.basename(__FILE__).'#'.__LINE__.': '.db::$conn->lastErrorMsg());
		}

		if (is_null($lid)) {
			db::$conn->exec('INSERT INTO urls (lid, url'.($this->fqdn !== '' ? ', fid' : '').') VALUES (NULL, \''.preg_replace('/\'/', '\'\'', $this->url).'\''.($this->fqdn !== '' ? ', '.$fid : '').')') or output::msg('critical', 'fail in '.basename(__FILE__).'#'.__LINE__.': '.db::$conn->lastErrorMsg());
			$lid = db::$conn->lastInsertRowID();
		}

		foreach ($this->uses as [$datetime, $nick]) {
			db::$conn->exec('INSERT INTO uid_urls (uid, lid, datetime) VALUES ((SELECT uid FROM uid_details WHERE csnick = \''.$nick.'\'), '.$lid.', DATETIME(\''.$datetime.'\'))') or output::msg('critical', 'fail in '.basename(__FILE__).'#'.__LINE__.': '.db::$conn->lastErrorMsg());
		}
	}
}
