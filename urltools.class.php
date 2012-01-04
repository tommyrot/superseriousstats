<?php

/**
 * Copyright (c) 2007-2012, Jos de Ruijter <jos@dutnie.nl>
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
 * Various functions related to URL validation and presentation. This class is far from complete
 * and only serves the basic needs of the superseriousstats program. It tries to follow RFC 3986.
 * Take note of the following caveats if you want to use this class in other projects:
 *
 * Scheme:
 * - http:// and https:// only.
 * - URLs without a scheme get http:// prefixed.
 *
 * Authority:
 * - Following preferred syntax, RFC 1034 section 3.5 and RFC 1123 section 2.1.
 * - There is no overall length check, only labels are checked on length (max 63 characters).
 * - No user part.
 *
 * IP:
 * - IPv4 only.
 * - 0.0.0.0 to 255.255.255.255
 * - No leading zeros.
 *
 * Port:
 * - 0 to 65535
 * - No leading zeros.
 *
 * TLD:
 * - http://data.iana.org/TLD/tlds-alpha-by-domain.txt (2011121600).
 * - No ASCII variants of internationalized country codes (xn--).
 *
 * Other:
 * - Square brackets must be percent encoded.
 */
final class urltools
{
	private $authority = '';
	private $domain = '(?<domain>[a-z0-9]([a-z0-9-]{0,61}?[a-z0-9]|[a-z0-9]{0,62})?(\.[a-z0-9]([a-z0-9-]{0,61}?[a-z0-9]|[a-z0-9]{0,62})?)*)';
	private $fqdn = '';
	private $fragment = '';
	private $gen_delims = '[]:\/?#[@]';
	private $ipv4address = '(?<ipv4address>(25[0-5]|(2[0-4]|1[0-9]|[1-9])?[0-9])(\.(25[0-5]|(2[0-4]|1[0-9]|[1-9])?[0-9])){3})';
	private $path = '';
	private $pchar = '';
	private $pct_encoded = '%[0-9a-f]{2}';
	private $port = '(?<port>(6553[0-5]|(655[0-2]|(65[0-4]|(6[0-4]|[1-5][0-9]|[1-9])[0-9]|[1-9])[0-9]|[1-9])?[0-9]))';
	private $query = '';
	private $reserved = '';
	private $scheme = '(?<scheme>https?:\/\/)';
	private $sub_delims = '[!$&\'()*+,;=]';
	private $tld = '(?<tld>\.(ac|ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|arpa|as|asia|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cat|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|coop|cr|cu|cv|cw|cx|cy|cz|de|dj|dk|dm|do|dz|ec|edu|ee|eg|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|info|int|io|iq|ir|is|it|je|jm|jo|jobs|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mil|mk|ml|mm|mn|mo|mobi|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|name|nc|ne|net|nf|ng|ni|nl|no|np|nr|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pro|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sx|sy|sz|tc|td|tel|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|travel|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|xxx|ye|yt|za|zm|zw))';
	private $unreserved = '[a-z0-9_.~-]';

	public function __construct()
	{
		$this->reserved = '('.$this->gen_delims.'|'.$this->sub_delims.')';
		$this->pchar = '('.$this->unreserved.'|'.$this->pct_encoded.'|'.$this->sub_delims.'|[:@])';
		$this->fqdn = '(?<fqdn>'.$this->domain.$this->tld.'\.?)';
		$this->authority = '(?<authority>('.$this->ipv4address.'|'.$this->fqdn.')(:'.$this->port.')?)';
		$this->fragment = '(?<fragment>(#('.$this->pchar.'|[\/?])*)?)';
		$this->path = '(?<path>(\/\/?('.$this->pchar.'+\/?)*)?)';
		$this->query = '(?<query>(\?('.$this->pchar.'|[\/?])*)?)';
	}

	/**
	 * Normalize and validate a URL and return an array with its elements.
	 */
	public function get_elements($url) {
		/**
		 * Convert scheme and authority to lower case.
		 */
		$url = preg_replace('/^'.$this->scheme.'?'.$this->authority.'/ei', 'strtolower(\'$0\')', $url);

		/**
		 * Validate and further process the URL.
		 */
		if (preg_match('/^(?<url>'.$this->scheme.'?'.$this->authority.$this->path.$this->query.$this->fragment.')$/i', $url, $matches)) {
			/**
			 * If the URL has no scheme we assume the "http://" one; update the elements we just found.
			 */
			if (empty($matches['scheme'])) {
				$matches['url'] = 'http://'.$matches['url'];
				$matches['scheme'] = 'http://';
			}

			/**
			 * Create and return an array with all the elements of this URL.
			 */
			$elements = array('url', 'scheme', 'authority', 'ipv4address', 'fqdn', 'domain', 'tld', 'path', 'query', 'fragment');

			foreach ($elements as $element) {
				if (empty($matches[$element])) {
					/**
					 * Always pass along an empty string for nonexistent elements.
					 */
					$urldata[$element] = '';
				} else {
					$urldata[$element] = $matches[$element];
				}
			}

			/**
			 * Make sure the only numeric element isn't passed along as a string.
			 */
			if (empty($matches['port'])) {
				$urldata['port'] = 0;
			} else {
				$urldata['port'] = (int) $matches['port'];
			}

			return $urldata;
		} else {
			return false;
		}
	}
}

?>
