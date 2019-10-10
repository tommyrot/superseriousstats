<?php

/**
 * Copyright (c) 2007-2019, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Class for creating small, medium and large generic tables.
 */
class table
{
	use base;
	private $cid = '';
	private $decimals = 1;
	private $head = '';
	private $keys = [];
	private $maxrows = 0;
	private $medium = false;
	private $minrows = 0;
	private $percentage = false;
	private $queries = [];
	private $total = 0;
	private $v3a = false;

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
		$newstring = '';
		$words = explode(' ', $string);

		foreach ($words as $word) {
			if (preg_match('/^(www\.|https?:\/\/)/i', $word) && ($urldata = urltools::get_elements($word)) !== false) {
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
		 * Detect which class to use. Class medium should be set explicitly by setting
		 * $medium to true.
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
			return;
		}

		for ($i; $i < $this->maxrows; $i++) {
			if ($class === 'small') {
				$trx .= '<tr><td class="v1"><td class="pos">&nbsp;<td class="v2">';
			} else {
				/*
				 * Temporary fix to prevent an ugly empty table that doesn't have alignment
				 * requirements.
				 */
				if ($this->head === 'Most Recent URLs') {
					break;
				}

				$trx .= '<tr><td class="v1"><td class="pos">&nbsp;<td class="v2"><td class="v3">';
			}
		}

		return '<table class="'.$class.'">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}
}
