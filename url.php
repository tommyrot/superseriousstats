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
		if (!isset($this->uses[$nick])) {
			$this->uses[$nick]['firstused'] = $datetime;
			$this->uses[$nick]['total'] = 1;
		} else {
			++$this->uses[$nick]['total'];
		}

		$this->uses[$nick]['lastused'] = $datetime;
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
			$lid = db::query_exec('INSERT INTO urls (url, fid) VALUES (\''.preg_replace('/\'/', '\'\'', $this->url).'\', '.($fid ?? 'NULL').')');
		}

		foreach ($this->uses as $nick => ['firstused' => $firstused, 'lastused' => $lastused, 'total' => $total]) {
			db::query_exec('INSERT INTO uid_urls (uid, lid, firstused, lastused, total) VALUES ((SELECT uid FROM uid_details WHERE csnick = \''.$nick.'\'), '.$lid.', \''.$firstused.'\', '.($lastused > $firstused ? '\''.$lastused.'\'' : 'NULL').', '.$total.') ON CONFLICT (uid, lid) DO UPDATE SET lastused = CASE WHEN \''.$lastused.'\' > firstused THEN \''.$lastused.'\' END, total = total + '.$total);
		}
	}
}
