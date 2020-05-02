<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2020, Jos de Ruijter <jos@dutnie.nl>
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
			$fid = db::query_single_col('SELECT fid FROM fqdns WHERE fqdn = \''.$this->fqdn.'\'');

			if (is_null($fid)) {
				$fid = db::query_exec('INSERT INTO fqdns (fid, fqdn, tld) VALUES (NULL, \''.$this->fqdn.'\', \''.$this->tld.'\')');
			}
		}

		/**
		 * Write data to database tables "urls" and "uid_urls".
		 */
		$lid = db::query_single_col('SELECT lid FROM urls WHERE url = \''.preg_replace('/\'/', '\'\'', $this->url).'\'');

		if (is_null($lid)) {
			$lid = db::query_exec('INSERT INTO urls (lid, url'.($this->fqdn !== '' ? ', fid' : '').') VALUES (NULL, \''.preg_replace('/\'/', '\'\'', $this->url).'\''.($this->fqdn !== '' ? ', '.$fid : '').')');
		}

		foreach ($this->uses as [$datetime, $nick]) {
			db::query_exec('INSERT INTO uid_urls (uid, lid, datetime) VALUES ((SELECT uid FROM uid_details WHERE csnick = \''.$nick.'\'), '.$lid.', DATETIME(\''.$datetime.'\'))');
		}
	}
}
