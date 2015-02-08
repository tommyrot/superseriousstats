<?php

/**
 * Copyright (c) 2007-2015, Jos de Ruijter <jos@dutnie.nl>
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
 * Various functions related to URL validation and presentation. It follows RFC 3986, and the preferred syntax as
 * mentioned in RFC 1034 section 3.5 and RFC 1123 section 2.1.
 *
 * Take note of the following which may differ from the specification:
 * - Only the http:// and https:// schemes will validate. URLs without a scheme get http:// prefixed.
 * - User part in authority is not recognized, and will not validate.
 * - IPv4 addresses only.
 * - TLDs as in http://data.iana.org/TLD/tlds-alpha-by-domain.txt but no internationalized country codes (xn--).
 * - The root domain is excluded from the FQDN (not from the other elements).
 * - Square brackets must be percent encoded.
 */
class urltools
{
	private static $authority = '';
	private static $domain = '(?<domain>[a-z0-9]([a-z0-9-]{0,61}?[a-z0-9]|[a-z0-9]{0,62})?(\.[a-z0-9]([a-z0-9-]{0,61}?[a-z0-9]|[a-z0-9]{0,62})?)*)';
	private static $fqdn = '';
	private static $fragment = '';
	private static $gen_delims = '[]:\/?#[@]';
	private static $ipv4address = '(?<ipv4address>(25[0-5]|(2[0-4]|1[0-9]|[1-9])?[0-9])(\.(25[0-5]|(2[0-4]|1[0-9]|[1-9])?[0-9])){3})';
	private static $path = '';
	private static $pchar = '';
	private static $pct_encoded = '%[0-9a-f]{2}';
	private static $port = '(?<port>(6553[0-5]|(655[0-2]|(65[0-4]|(6[0-4]|[1-5][0-9]|[1-9])[0-9]|[1-9])[0-9]|[1-9])?[0-9]))';
	private static $query = '';
	private static $reserved = '';
	private static $scheme = '(?<scheme>https?:\/\/)';
	private static $sub_delims = '[!$&\'()*+,;=]';
	private static $tld = '(?<tld>\.(museum|travel|aero|arpa|asia|coop|info|jobs|mobi|name|post|biz|cat|com|edu|gov|int|mil|net|org|pro|tel|xxx|ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cu|cv|cw|cx|cy|cz|de|dj|dk|dm|do|dz|ec|ee|eg|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sx|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|za|zm|zw))';
	private static $unreserved = '[a-z0-9_.~-]';

	private function __construct()
	{
		/**
		 * This is a static class and should not be instantiated.
		 */
	}

	/**
	 * Normalize and validate a URL and return an array with its elements.
	 */
	public static function get_elements($url)
	{
		/**
		 * Assemble parts of the regular expression if not already done so.
		 */
		if (empty(self::$fqdn)) {
			self::$fqdn = '(?<fqdn>'.self::$domain.self::$tld.')\.?';
			self::$authority = '(?<authority>('.self::$ipv4address.'|'.self::$fqdn.')(:'.self::$port.')?)';
			self::$pchar = '('.self::$unreserved.'|'.self::$pct_encoded.'|'.self::$sub_delims.'|[:@])';
			self::$fragment = '(?<fragment>(#('.self::$pchar.'|[\/?])*)?)';
			self::$path = '(?<path>(\/\/?('.self::$pchar.'+\/?)*)?)';
			self::$query = '(?<query>(\?('.self::$pchar.'|[\/?])*)?)';
			self::$reserved = '('.self::$gen_delims.'|'.self::$sub_delims.')';
		}

		/**
		 * Convert scheme and authority to lower case.
		 */
		$url = preg_replace_callback('/^'.self::$scheme.'?'.self::$authority.'/i', function ($matches) {
			return strtolower($matches[0]);
		}, $url);

		/**
		 * Validate and further process the URL.
		 */
		if (preg_match('/^(?<url>'.self::$scheme.'?'.self::$authority.self::$path.self::$query.self::$fragment.')$/i', $url, $matches)) {
			/**
			 * The maximum allowed length of the FQDN (root domain excluded) is 254 characters.
			 */
			if (strlen($matches['fqdn']) > 254) {
				return false;
			}

			/**
			 * If the URL has no scheme, http:// is assumed. Update the elements.
			 */
			if (empty($matches['scheme'])) {
				$matches['scheme'] = 'http://';
				$matches['url'] = 'http://'.$matches['url'];
			}

			/**
			 * Create and return an array with all the elements of this URL.
			 */
			$elements = ['url', 'scheme', 'authority', 'ipv4address', 'fqdn', 'domain', 'tld', 'path', 'query', 'fragment'];

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
