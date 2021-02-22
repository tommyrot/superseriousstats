<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2021, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Class for handling URL data.
 */
class url
{
	private array $uses = [];
	private string $fqdn = '';
	private string $tld = '';
	private string $url = '';

	public function __construct(array $urlparts)
	{
		$this->fqdn = $urlparts['fqdn'];
		$this->tld = $urlparts['tld'];
		$this->url = $urlparts['url'];
	}

	/**
	 * Record each and every use of this URL.
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
		 * Store data in database table "fqdns".
		 */
		if ($this->fqdn !== '') {
			if (is_null($fid = db::query_single_col('SELECT fid FROM fqdns WHERE fqdn = \''.$this->fqdn.'\''))) {
				$fid = db::query_exec('INSERT INTO fqdns (fqdn, tld) VALUES (\''.$this->fqdn.'\', \''.$this->tld.'\')');
			}
		}

		/**
		 * Store data in database tables "urls" and "uid_urls".
		 */
		if (is_null($lid = db::query_single_col('SELECT lid FROM urls WHERE url = \''.preg_replace('/\'/', '\'\'', $this->url).'\''))) {
			$lid = db::query_exec('INSERT INTO urls (url'.($this->fqdn !== '' ? ', fid' : '').') VALUES (\''.preg_replace('/\'/', '\'\'', $this->url).'\''.($this->fqdn !== '' ? ', '.$fid : '').')');
		}

		foreach ($this->uses as [$datetime, $nick]) {
			db::query_exec('INSERT INTO uid_urls (uid, lid, datetime) VALUES ((SELECT uid FROM uid_details WHERE csnick = \''.$nick.'\'), '.$lid.', \''.$datetime.'\')');
		}
	}
}
