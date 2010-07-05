<?php

/**
 * Copyright (c) 2007-2010, Jos de Ruijter <jos@dutnie.nl>
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
 * Various functions for URLs following RFC 3986 and others.
 *
 * Scheme:
 * - http:// and https:// only
 *
 * Authority:
 * - Following preferred syntax, RFC 1034 section 3.5 and RFC 1123 section 2.1
 * - There is no overall length check, only labels are checked on length (max 63 characters)
 * - No user part
 *
 * IP:
 * - IPv4 only
 * - 0.0.0.0 to 255.255.255.255
 * - No leading zeros
 *
 * Port:
 * - 0 to 65535
 * - No leading zeros
 *
 * TLD:
 * - http://data.iana.org/TLD/tlds-alpha-by-domain.txt # Version 2010070400, Last Updated Sun Jul  4 14:07:01 2010 UTC
 *
 * Other:
 * - Square brackets must be percent encoded
 */
final class URLTools
{
	/**
	 * Character groups as described in RFC 3986.
	 */
	private $gen_delims = '';
	private $pchar = '';
	private $pct_encoded = '';
	private $reserved = '';
	private $sub_delims = '';
	private $unreserved = '';

	/**
	 * Elements of the regular expression.
	 */
	private $IPv4address = '';
	private $authority = '';
	private $domain = '';
	private $fqdn = '';
	private $fragment = '';
	private $path = '';
	private $port = '';
	private $query = '';
	private $scheme = '';
	private $tld = '';

	/**
	 * The regular expression itself.
	 */
	private $regexp = '';

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		/**
		 * Character groups.
		 */
		$this->unreserved = '[a-z0-9_.~-]';
		$this->pct_encoded = '%[0-9a-f]{2}';
		$this->gen_delims = '[]:\/?#[@]';
		$this->sub_delims = '[!$&\'()*+,;=]';
		$this->reserved = '('.$this->gen_delims.'|'.$this->sub_delims.')';
		$this->pchar = '('.$this->unreserved.'|'.$this->pct_encoded.'|'.$this->sub_delims.'|[:@])';

		/**
		 * Elements.
		 */
		$this->scheme = 'https?:\/\/';
		$this->IPv4address = '(25[0-5]|(2[0-4]|1[0-9]|[1-9])?[0-9])(\.(25[0-5]|(2[0-4]|1[0-9]|[1-9])?[0-9])){3}';
		$this->port = '(6553[0-5]|(655[0-2]|(65[0-4]|(6[0-4]|[1-5][0-9]|[1-9])[0-9]|[1-9])[0-9]|[1-9])?[0-9])';
		$this->domain = '[a-z0-9]([a-z0-9-]{0,61}?[a-z0-9]|[a-z0-9]{0,62})?(\.[a-z0-9]([a-z0-9-]{0,61}?[a-z0-9]|[a-z0-9]{0,62})?)*';
		$this->tld = '\.(ac|ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|arpa|as|asia|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cat|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|coop|cr|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|edu|ee|eg|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|info|int|io|iq|ir|is|it|je|jm|jo|jobs|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mil|mk|ml|mm|mn|mo|mobi|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|name|nc|ne|net|nf|ng|ni|nl|no|np|nr|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pro|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tel|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|travel|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|xn--0zwm56d|xn--11b5bs3a9aj6g|xn--80akhbyknj4f|xn--9t4b11yi5a|xn--deba0ad|xn--g6w251d|xn--hgbk6aj7f53bba|xn--hlcj6aya9esc7a|xn--jxalpdlp|xn--kgbechtv|xn--mgbaam7a8h|xn--mgberp4a5d4ar|xn--p1ai|xn--wgbh1c|xn--zckzah|ye|yt|za|zm|zw)\.?';
		$this->fqdn = $this->domain.$this->tld;
		$this->authority = '('.$this->IPv4address.'|'.$this->fqdn.')(:'.$this->port.')?';
		$this->path = '(\/\/?('.$this->pchar.'+\/?)*)?';
		$this->query = '(\?('.$this->pchar.'|[\/?])*)?';
		$this->fragment = '(#('.$this->pchar.'|[\/?])*)?';

		/**
		 * Build regular expression. Case insensitive.
		 */
		$this->regexp = '/^'.$this->scheme.$this->authority.$this->path.$this->query.$this->fragment.'$/i';
	}

	/**
	 * Normalize a URL.
	 */
	public function normalizeURL($csURL)
	{
		/**
		 * 1. Convert scheme and authority to lower case.
		 * 2. Strip trailing slashes from path or authority.
		 */
		$csURL = preg_replace(array('/^'.$this->scheme.$this->authority.'/ei', '/^'.$this->scheme.$this->authority.$this->path.'/ei'), array("strtolower('$0')", "rtrim('$0', '/')"), $csURL);
		return $csURL;
	}

	/**
	 * Validate a URL.
	 */
	public function validateURL($csURL)
	{
		if (preg_match($this->regexp, $csURL)) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}

?>
