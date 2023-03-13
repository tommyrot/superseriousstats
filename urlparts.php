<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2022, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Trait with code handling URL validation and disassembly. Returns null if a
 * URL fails the syntax check. Returns an array with the URL parts on success.
 * Missing parts are represented by an empty string, a missing port part by
 * (int) 0.
 *
 * For reference: RFC 3986, RFC 1034 section 3.5, RFC 1123 section 2.1
 *
 * Additional notes:
 *  - The scheme should either be http or https. For URLs without a scheme, http
 *    is assumed.
 *  - User part in authority is not recognized and will not validate.
 *  - IPv6 addresses will not validate.
 *  - Square brackets must be percent encoded.
 *  - Apply various normalizations.
 */
trait urlparts
{
	private string $regexp = '';

	private function get_urlparts(string $url): ?array
	{
		/**
		 * Assemble the regular expression if not already done so.
		 */
		if ($this->regexp === '') {
			$scheme = '((?<scheme>https?):\/\/)';
			$ipv4address = '(?<ipv4address>(25[0-5]|(2[0-4]|1[0-9]|[1-9])?[0-9])(\.(25[0-5]|(2[0-4]|1[0-9]|[1-9])?[0-9])){3})';
			$domain = '(?<domain>[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*)';
			$tld = '(?<tld>[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)';
			$fqdn = '(?<fqdn>'.$domain.'\.'.$tld.')\.?';
			$port = '(?<port>(6553[0-5]|(655[0-2]|(65[0-4]|(6[0-4]|[1-5][0-9]|[1-9])[0-9]|[1-9])[0-9]|[1-9])?[0-9]))';
			$authority = '(?<authority>('.$ipv4address.'|'.$fqdn.')(:'.$port.')?)';
			$unreserved = '[a-z0-9_.~-]';
			$pct_encoded = '%[0-9a-f]{2}';
			$sub_delims = '[!$&\'()*+,;=]';
			$pchar = '('.$unreserved.'|'.$pct_encoded.'|'.$sub_delims.'|[:@])';
			$path = '(?<path>(\/('.$pchar.'|\/)*)?)';
			$query = '(?<query>(\?('.$pchar.'|[\/?])*)?)';
			$fragment = '(?<fragment>(#('.$pchar.'|[\/?])*)?)';
			$this->regexp = '/^'.$scheme.'?'.$authority.$path.$query.$fragment.'$/in';
		}

		/**
		 * Validate the URL.
		 */
		if (!preg_match($this->regexp, $url, $matches, PREG_UNMATCHED_AS_NULL)) {
			return null;
		}

		/**
		 * The TLD may not solely consist of digits.
		 */
		if (!is_null($matches['tld']) && preg_match('/^\d+$/', $matches['tld'])) {
			return null;
		}

		/**
		 * The FQDN (excluding trailing dot) may not exceed 253 characters.
		 */
		if (!is_null($matches['fqdn']) && strlen($matches['fqdn']) > 253) {
			return null;
		}

		/**
		 * Normalize all parts of the URL:
		 *  - All values are of type string except port which is integer (0 = no port).
		 *  - In absense of a scheme assume http.
		 *  - Convert to lower case when appropriate.
		 *  - Reconstruct authority so any trailing dot is removed.
		 *  - Make sure path only has one leading slash and is empty when redundant.
		 *  - Reconstruct the URL to reflect normalizations listed above.
		 */
		foreach (['scheme', 'fqdn', 'domain', 'tld', 'ipv4address', 'port', 'path', 'query', 'fragment'] as $part) {
			switch ($part) {
				case 'scheme':
					$urlparts['scheme'] = strtolower($matches['scheme'] ?? 'http');
					break;
				case 'fqdn':
				case 'domain':
				case 'tld':
					$urlparts[$part] = strtolower($matches[$part] ?? '');
					break;
				case 'port':
					$urlparts['port'] = (int) ($matches['port'] ?? 0);
					break;
				default:
					$urlparts[$part] = $matches[$part] ?? '';
			}
		}

		$urlparts['authority'] = ($urlparts['fqdn'] !== '' ? $urlparts['fqdn'] : $urlparts['ipv4address']).($urlparts['port'] !== 0 ? ':'.$urlparts['port'] : '');

		if ($urlparts['query'] === '' && $urlparts['fragment'] === '') {
			$urlparts['path'] = preg_replace(['/^\/\/+/', '/^\/$/'], ['/', ''], $urlparts['path']);
		} else {
			$urlparts['path'] = preg_replace(['/^\/\/+/', '/^$/'], ['/', '/'], $urlparts['path']);
		}

		$urlparts['url'] = $urlparts['scheme'].'://'.$urlparts['authority'].$urlparts['path'].$urlparts['query'].$urlparts['fragment'];
		return $urlparts;
	}
}
