<?php

/**
 * Copyright (c) 2007-2015, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Various functions related to URL validation and presentation. It follows
 * RFC 3986, and the preferred syntax as mentioned in RFC 1034 section 3.5 and
 * RFC 1123 section 2.1.
 *
 * Take note of the following which differ from the specification:
 * - Only the http:// and https:// schemes will validate. URLs without a scheme
 *   get http:// prefixed.
 * - User part in authority is not recognized, and will not validate.
 * - IPv4 addresses only.
 * - TLDs as in http://data.iana.org/TLD/tlds-alpha-by-domain.txt (locally
 *   stored, can be updated at will).
 * - The root domain is excluded from the FQDN (not from the other elements).
 * - Square brackets must be percent encoded.
 */
class urltools
{
	private static $regexp_callback = '';
	private static $regexp_complete = '';
	private static $valid_tlds = [];

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
		 * Assemble the regular expression if not already done so.
		 */
		if (self::$regexp_complete === '') {
			$domain = '(?<domain>[a-z0-9]([a-z0-9-]{0,61}?[a-z0-9]|[a-z0-9]{0,62})?(\.[a-z0-9]([a-z0-9-]{0,61}?[a-z0-9]|[a-z0-9]{0,62})?)*)';
			$tld = '(?<tld>\.[a-z0-9]([a-z0-9-]{0,61}?[a-z0-9]|[a-z0-9]{0,62})?)';
			$fqdn = '(?<fqdn>'.$domain.$tld.')\.?';
			$ipv4address = '(?<ipv4address>(25[0-5]|(2[0-4]|1[0-9]|[1-9])?[0-9])(\.(25[0-5]|(2[0-4]|1[0-9]|[1-9])?[0-9])){3})';
			$port = '(?<port>(6553[0-5]|(655[0-2]|(65[0-4]|(6[0-4]|[1-5][0-9]|[1-9])[0-9]|[1-9])[0-9]|[1-9])?[0-9]))';
			$authority = '(?<authority>('.$ipv4address.'|'.$fqdn.')(:'.$port.')?)';
			$unreserved = '[a-z0-9_.~-]';
			$pct_encoded = '%[0-9a-f]{2}';
			$sub_delims = '[!$&\'()*+,;=]';
			$pchar = '('.$unreserved.'|'.$pct_encoded.'|'.$sub_delims.'|[:@])';
			$fragment = '(?<fragment>(#('.$pchar.'|[\/?])*)?)';
			$path = '(?<path>(\/\/?('.$pchar.'+\/?)*)?)';
			$query = '(?<query>(\?('.$pchar.'|[\/?])*)?)';
			$scheme = '(?<scheme>https?:\/\/)';
			self::$regexp_callback = '/^'.$scheme.'?'.$authority.'/i';
			self::$regexp_complete = '/^(?<url>'.$scheme.'?'.$authority.$path.$query.$fragment.')$/i';

			/**
			 * Read "tlds-alpha-by-domain.txt" and put all TLDs in an array against which we
			 * can validate found URLs. If the aforementioned file does not exist or fails
			 * to be read, the TLD check will not be done. This would be an unexpected and
			 * undesired exception though.
			 */
			if (($tlds = file('tlds-alpha-by-domain.txt')) === false) {
				output::output('notice', __METHOD__.'(): failed to open file: \'tlds-alpha-by-domain.txt\', tld validation disabled');
			} else {
				foreach ($tlds as $tld) {
					$tld = trim($tld);

					if ($tld !== '' && strpos($tld, '#') === false) {
						self::$valid_tlds[] = '.'.strtolower($tld);
					}
				}
			}
		}

		/**
		 * Convert scheme and authority to lower case.
		 */
		$url = preg_replace_callback(self::$regexp_callback, function ($matches) {
			return strtolower($matches[0]);
		}, $url);

		/**
		 * Validate and further process the URL.
		 */
		if (!preg_match(self::$regexp_complete, $url, $matches)) {
			return false;
		}

		/**
		 * Verify if the TLD is valid. If the validation array is empty we skip this
		 * step.
		 */
		if (!empty(self::$valid_tlds) && !empty($matches['tld']) && !in_array($matches['tld'], self::$valid_tlds)) {
			return false;
		}

		/**
		 * The maximum allowed length of the FQDN (root domain excluded) is 254
		 * characters.
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
	}
}
