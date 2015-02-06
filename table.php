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
 * Class for creating small, medium and large generic tables.
 */
final class table extends base
{
	private $head = '';
	private $maxrows = 0;
	private $minrows = 0;
	private $urltools;
	protected $cid = '';
	protected $decimals = 1;
	protected $keys = [];
	protected $medium = false;
	protected $percentage = false;
	protected $queries = [];
	protected $total = 0;
	protected $v3a = false;

	public function __construct($head, $minrows, $maxrows)
	{
		$this->head = $head;
		$this->maxrows = $maxrows;
		$this->minrows = $minrows;
	}

	/**
	 * Check if there are URLs in the string and if so, make hyperlinks out of them.
	 */
	private function find_urls($string)
	{
		if (empty($this->urltools)) {
			$urltools = new urltools();
		}

		$newstring = '';
		$words = explode(' ', $string);

		foreach ($words as $word) {
			if (preg_match('/^(www\.|https?:\/\/)/i', $word) && ($urldata = $urltools->get_elements($word)) !== false) {
				$newstring .= '<a href="'.htmlspecialchars($urldata['url']).'">'.htmlspecialchars($urldata['url']).'</a> ';
			} else {
				$newstring .= htmlspecialchars($word).' ';
			}
		}

		return rtrim($newstring);
	}

	public function make_table($sqlite3)
	{
		/**
		 * Detect which class to use. Class medium should be set explicitly by setting $medium to true.
		 */
		if ($this->medium) {
			$class = 'medium';
		} elseif (array_key_exists('v3', $this->keys)) {
			$class = 'large';
		} else {
			$class = 'small';
		}

		/**
		 * Run the "total" query if present.
		 */
		if (!empty($this->queries['total'])) {
			if (($this->total = $sqlite3->querySingle($this->queries['total'])) === false) {
				output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		}

		/**
		 * Create the table head.
		 */
		if ($class === 'small') {
			$tr0 = '<colgroup><col class="c1"><col class="pos"><col class="c2">';
			$tr1 = '<tr><th colspan="3">'.(!empty($this->total) ? '<span class="title">'.$this->head.'</span><span class="title-right">'.number_format($this->total).' Total</span>' : $this->head);
			$tr2 = '<tr><td class="k1">'.$this->keys['k1'].'<td class="pos"><td class="k2">'.$this->keys['k2'];
			$trx = '';
		} else {
			$tr0 = '<colgroup><col class="c1"><col class="pos"><col class="c2"><col class="c3">';
			$tr1 = '<tr><th colspan="4">'.(!empty($this->total) ? '<span class="title">'.$this->head.'</span><span class="title-right">'.number_format($this->total).' Total</span>' : $this->head);
			$tr2 = '<tr><td class="k1">'.$this->keys['k1'].'<td class="pos"><td class="k2">'.$this->keys['k2'].'<td class="k3">'.$this->keys['k3'];
			$trx = '';
		}

		/**
		 * Run the "main" query and structure the table contents.
		 */
		$i = 0;
		$query = $sqlite3->query($this->queries['main']) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$i++;

			foreach ($this->keys as $key => $type) {
				/**
				 * Skip irrelevant keys.
				 */
				if (strpos($key, 'v') === false) {
					continue;
				}

				switch ($type) {
					case 'string':
						${$key} = htmlspecialchars($result[$key]);
						break;
					case 'int':
						${$key} = number_format($result[$key]);
						break;
					case 'float':
						${$key} = number_format($result[$key], $this->decimals).($this->percentage ? '%' : '');
						break;
					case 'date':
						${$key} = date('j M \'y', strtotime($result[$key]));
						break;
					case 'date-norepeat':
						${$key} = date('j M \'y', strtotime($result[$key]));

						if (!empty($prevdate) && ${$key} === $prevdate) {
							${$key} = '';
						} else {
							$prevdate = ${$key};
						}

						break;
					case 'url':
						${$key} = '<a href="'.htmlspecialchars($result[$key]).'">'.htmlspecialchars($result[$key]).'</a>';
						break;
					case 'string-url':
						${$key} = $this->find_urls($result[$key]);
						break;
					case 'userstats':
						${$key} = '<a href="user.php?cid='.urlencode($this->cid).'&amp;nick='.urlencode($result[$key]).'">'.htmlspecialchars($result[$key]).'</a>';
						break;
				}
			}

			if ($class === 'small') {
				$trx .= '<tr><td class="v1">'.$v1.'<td class="pos">'.$i.'<td class="v2">'.$v2;
			} else {
				/**
				 * Class v3a doesn't use ellipsis.
				 */
				$trx .= '<tr><td class="v1">'.$v1.'<td class="pos">'.$i.'<td class="v2">'.$v2.'<td class="'.($this->v3a ? 'v3a' : 'v3').'">'.$v3;
			}
		}

		if ($i < $this->minrows) {
			return null;
		}

		for ($i; $i < $this->maxrows; $i++) {
			if ($class === 'small') {
				$trx .= '<tr><td class="v1"><td class="pos">&nbsp;<td class="v2">';
			} else {
				$trx .= '<tr><td class="v1"><td class="pos">&nbsp;<td class="v2"><td class="v3">';
			}
		}

		return '<table class="'.$class.'">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}
}
