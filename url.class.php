<?php

/**
 * Copyright (c) 2007-2011, Jos de Ruijter <jos@dutnie.nl>
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
 * Class for handling URL data.
 */
final class url extends base
{
	/**
	 * Variables that shouldn't be tampered with.
	 */
	protected $authority = '';
	protected $domain = '';
	protected $extension = '';
	protected $firstused = '';
	protected $fqdn = '';
	protected $fragment = '';
	protected $ipv4address = '';
	protected $lastused = '';
	protected $mysqli;
	protected $path = '';
	protected $port = 0;
	protected $query = '';
	protected $scheme = '';
	protected $tld = '';
	protected $total = 0;
	protected $url = '';

	public function __construct($urldata)
	{
		parent::__construct();
		$this->url = $urldata['url'];
		$this->scheme = $urldata['scheme'];
		$this->authority = $urldata['authority'];
		$this->ipv4address = $urldata['ipv4address'];
		$this->fqdn = $urldata['fqdn'];
		$this->domain = $urldata['domain'];
		$this->tld = $urldata['tld'];
		$this->port = $urldata['port'];
		$this->path = $urldata['path'];
		$this->query = $urldata['query'];
		$this->fragment = $urldata['fragment'];

		/**
		 * Attempt to get a file extension from $path. This is by no means 100% accurate but it helps us search for content faster.
		 */
		if (preg_match('/\.(?<extension>[a-z0-9]{1,7})$/i', $this->path, $matches)) {
			$this->extension = $matches['extension'];
		}
	}

	public function set_lastused($datetime)
	{
		if ($this->firstused == '' || strtotime($datetime) < strtotime($this->firstused)) {
			$this->firstused = $datetime;
		}

		if ($this->lastused == '' || strtotime($datetime) > strtotime($this->lastused)) {
			$this->lastused = $datetime;
		}
	}

	public function write_data($mysqli, $uid)
	{
		$this->mysqli = $mysqli;

		/**
		 * Write data to database table "user_urls".
		 */
		$query = @mysqli_query($this->mysqli, 'select `lid` from `user_urls` where `url` = \''.mysqli_real_escape_string($this->mysqli, $this->url).'\' group by `url`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			$createdquery = $this->create_insert_query(array('url', 'scheme', 'authority', 'ipv4address', 'fqdn', 'domain', 'tld', 'port', 'path', 'query', 'fragment', 'extension', 'total', 'firstused', 'lastused'));
			@mysqli_query($this->mysqli, 'insert into `user_urls` set `lid` = 0, `uid` = '.$uid.','.$createdquery) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		} else {
			$result = mysqli_fetch_object($query);
			$query = @mysqli_query($this->mysqli, 'select * from `user_urls` where `lid` = '.$result->lid.' and `uid` = '.$uid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query);

			if (empty($rows)) {
				$createdquery = $this->create_insert_query(array('url', 'scheme', 'authority', 'ipv4address', 'fqdn', 'domain', 'tld', 'port', 'path', 'query', 'fragment', 'extension', 'total', 'firstused', 'lastused'));
				@mysqli_query($this->mysqli, 'insert into `user_urls` set `lid` = '.$result->lid.', `uid` = '.$uid.','.$createdquery) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			} else {
				$result = mysqli_fetch_object($query);
				/**
				 * Don't update the elements that don't change for URLs that are already in the database (these are fixed and/or always stored lowercase).
				 */
				$createdquery = $this->create_update_query($result, array('lid', 'uid', 'scheme', 'authority', 'ipv4address', 'fqdn', 'tld', 'port'));

				if (!is_null($createdquery)) {
					@mysqli_query($mysqli, 'update `user_urls` set'.$createdquery.' where `lid` = '.$result->lid.' and `uid` = '.$uid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				}
			}
		}
	}
}

?>
