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
			if (($fid = sss::$db->querySingle('SELECT fid FROM fqdns WHERE fqdn = \''.$this->fqdn.'\'')) === false) {
				output::msg('critical', 'fail in '.basename(__FILE__).'#'.__LINE__.': '.sss::$db->lastErrorMsg());
			}

			if (is_null($fid)) {
				sss::$db->exec('INSERT INTO fqdns (fid, fqdn, tld) VALUES (NULL, \''.$this->fqdn.'\', \''.$this->tld.'\')') or output::msg('critical', 'fail in '.basename(__FILE__).'#'.__LINE__.': '.sss::$db->lastErrorMsg());
				$fid = sss::$db->lastInsertRowID();
			}
		}

		/**
		 * Write data to database tables "urls" and "uid_urls".
		 */
		if (($lid = sss::$db->querySingle('SELECT lid FROM urls WHERE url = \''.preg_replace('/\'/', '\'\'', $this->url).'\'')) === false) {
			output::msg('critical', 'fail in '.basename(__FILE__).'#'.__LINE__.': '.sss::$db->lastErrorMsg());
		}

		if (is_null($lid)) {
			sss::$db->exec('INSERT INTO urls (lid, url'.($this->fqdn !== '' ? ', fid' : '').') VALUES (NULL, \''.preg_replace('/\'/', '\'\'', $this->url).'\''.($this->fqdn !== '' ? ', '.$fid : '').')') or output::msg('critical', 'fail in '.basename(__FILE__).'#'.__LINE__.': '.sss::$db->lastErrorMsg());
			$lid = sss::$db->lastInsertRowID();
		}

		foreach ($this->uses as [$datetime, $nick]) {
			sss::$db->exec('INSERT INTO uid_urls (uid, lid, datetime) VALUES ((SELECT uid FROM uid_details WHERE csnick = \''.$nick.'\'), '.$lid.', DATETIME(\''.$datetime.'\'))') or output::msg('critical', 'fail in '.basename(__FILE__).'#'.__LINE__.': '.sss::$db->lastErrorMsg());
		}
	}
}
