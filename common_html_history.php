<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2020, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Trait with code common between html.php and history.php.
 */
trait common_html_history
{
	/**
	 * Calculate how long ago a given $datetime is.
	 */
	private function ago(string $datetime): string
	{
		$diff = date_diff(date_create($this->now), date_create(substr($datetime, 0, 10)));

		if ($diff->y > 0) {
			$ago = $diff->y.' Year'.($diff->y !== 1 ? 's' : '').' Ago';
		} elseif ($diff->m > 0) {
			$ago = $diff->m.' Month'.($diff->m !== 1 ? 's' : '').' Ago';
		} elseif ($diff->d === 0) {
			$ago = 'Today';
		} elseif ($diff->d === 1) {
			$ago = 'Yesterday';
		} else {
			$ago = $diff->d.' Days Ago';
		}

		return $ago;
	}

	private function create_table_people(): ?string
	{
		$times = ['night', 'morning', 'afternoon', 'evening'];

		/**
		 * Execute the appropriate queries.
		 */
		if (isset($this->year)) {
			if (!is_null($l_total = db::query_single_col('SELECT SUM(t1.l_total) FROM '.(isset($this->month) ? 'ruid_activity_by_month' : 'ruid_activity_by_year').' AS t1 JOIN uid_details ON t1.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date = \''.$this->year.(isset($this-month) ? '-'.($this->month <= 9 ? '0' : '').$this->month : '').'\''))) {
				$results = db::query('SELECT csnick, t1.l_total, t1.l_night, t1.l_morning, t1.l_afternoon, t1.l_evening, lasttalked, quote FROM '.(isset($this->month) ? 'ruid_activity_by_month' : 'ruid_activity_by_year').' AS t1 JOIN uid_details ON t1.ruid = uid_details.uid JOIN ruid_lines ON t1.ruid = ruid_lines.ruid WHERE status NOT IN (3,4) AND date = \''.$this->year.(isset($this-month) ? '-'.($this->month <= 9 ? '0' : '').$this->month : '').'\' ORDER BY t1.l_total DESC, t1.ruid ASC LIMIT 30');
			}
		else {
			if (!is_null($l_total = db::query_single_col('SELECT SUM(l_total) FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4)'))) {
				$results = db::query('SELECT csnick, l_total, l_night, l_morning, l_afternoon, l_evening, lasttalked, quote FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) ORDER BY l_total DESC, ruid_lines.ruid ASC LIMIT 30');
			}
		}

		if (is_null($l_total)) {
			return null;
		}

		/**
		 * Construct the table, processing data as we need it.
		 */
		$title = 'Most Talkative People &ndash; '.(isset($this->month) ? date('F Y', strtotime($this->year.'-'.($this->month <= 9 ? '0' : '').$this->month.'-01')) : ($this->year ?? 'All-Time'));
		$tr0 = '<colgroup><col class="c1"><col class="c2"><col class="pos"><col class="c3"><col class="c4"><col class="c5"><col class="c6">';
		$tr1 = '<tr><th colspan="7">'.($this->linkto_history_php ? '<span class="title">'.$title.'</span><span class="title-right"><a href="history.php">History</a></span>' : $title);
		$tr2 = '<tr><td class="k1">Percentage<td class="k2">Lines<td class="pos"><td class="k3">User<td class="k4">Activity<td class="k5">Last Talked<td class="k6">Quote';
		$trx = '';
		$pos = 0;

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			/**
			 * Due to flooring (intval) it can happen that not all pixels of the full bar
			 * width are used initially. Distribute the leftover pixels among the times
			 * with highest $unclaimed_subpixels.
			 */
			$unclaimed_pixels = 50;
			$unclaimed_subpixels = [];

			foreach ($times as $time) {
				if ($result['l_'.$time] !== 0) {
					$width[$time] = intval(($result['l_'.$time] / $result['l_total']) * 50);
					$unclaimed_pixels -= $width[$time];
					$unclaimed_subpixels[$time] = (($result['l_'.$time] / $result['l_total']) * 50) - $width[$time];
				} else {
					$width[$time] = 0;
				}
			}

			while ($unclaimed_pixels > 0) {
				$high_subpixels = 0;

				foreach ($unclaimed_subpixels as $time => $subpixels) {
					if ($subpixels > $high_subpixels) {
						$high_subpixels = $subpixels;
						$high_time = $time;
					}
				}

				++$width[$high_time];
				$unclaimed_subpixels[$high_time] = 0;
				--$unclaimed_pixels;
			}

			$activity = '';

			foreach ($times as $time) {
				if ($width[$time] !== 0) {
					$activity .= '<li class="'.$time[0].'" style="width:'.$width[$time].'px">';
				}
			}

			$trx .= '<tr><td class="v1">'.number_format(($result['l_total'] / $l_total) * 100, 2).'%<td class="v2">'.number_format($result['l_total']).'<td class="pos">'.++$pos.'<td class="v3">'.($this->linkto_user_php ? '<a href="user.php?nick='.$this->htmlify(urlencode($result['csnick'])).'">'.$this->htmlify($result['csnick']).'</a>' : $this->htmlify($result['csnick'])).'<td class="v4"><ul>'.$activity.'</ul><td class="v5">'.$this->ago($result['lasttalked']).'<td class="v6">'.$this->htmlify($result['quote']);
		}

		return '<table class="ppl">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}

	private function create_table_people_timeofday(): ?string
	{
		$times = ['night', 'morning', 'afternoon', 'evening'];

		/**
		 * Use the highest amount of lines to scale the bar widths.
		 */
		$high_lines = 0;

		foreach ($times as $time) {
			/**
			 * Execute the appropriate query.
			 */
			if (isset($this->month)) {
				$results = db::query('SELECT csnick, l_'.$time.' FROM ruid_activity_by_month JOIN uid_details ON ruid_activity_by_month.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date = \''.$this->year.'-'.($this->month <= 9 ? '0' : '').$this->month.'\' AND l_'.$time.' != 0 ORDER BY l_'.$time.' DESC, ruid_activity_by_month.ruid ASC LIMIT 10');
			} else {
				$results = db::query('SELECT csnick, l_'.$time.' FROM ruid_activity_by_year JOIN uid_details ON ruid_activity_by_year.ruid = uid_details.uid WHERE status NOT IN (3,4)'.(isset($this->year) ? ' AND date = \''.$this->year.'\'' : '').' AND l_'.$time.' != 0 ORDER BY l_'.$time.' DESC, ruid_activity_by_year.ruid ASC LIMIT 10');
			}

			if (($result = $results->fetchArray(SQLITE3_ASSOC)) === false) {
				return null;
			}

			$results->reset();

			/**
			 * Arrange data in a useable format.
			 */
			$i = 0;

			while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
				++$i;
				$lines[$time][$i] = $result['l_'.$time];
				$csnick[$time][$i] = $result['csnick'];

				if ($lines[$time][$i] > $high_lines) {
					$high_lines = $lines[$time][$i];
				}
			}
		}

		$tr0 = '<colgroup><col class="pos"><col class="c"><col class="c"><col class="c"><col class="c">';
		$tr1 = '<tr><th colspan="5">Most Talkative People by Time of Day';
		$tr2 = '<tr><td class="pos"><td class="k">Night<br>0h &ndash; 5h<td class="k">Morning<br>6h &ndash; 11h<td class="k">Afternoon<br>12h &ndash; 17h<td class="k">Evening<br>18h &ndash; 23h';
		$trx = '';

		/**
		 * Construct each row, provided there is at least one column with data per row.
		 */
		for ($i = 1; $i <= 10; ++$i) {
			if (!isset($lines['night'][$i]]) && !isset($lines['morning'][$i]) && !isset($lines['afternoon'][$i]) && !isset($lines['evening'][$i])) {
				break;
			}

			$trx .= '<tr><td class="pos">'.$i;

			foreach ($times as $time) {
				if (!isset($lines[$time][$i])) {
					$trx .= '<td class="v">';
				} else {
					$width = (int) round(($lines[$time][$i] / $high_lines) * 190);
					$trx .= '<td class="v">'.$this->htmlify($csnick[$time][$i]).' &ndash; '.number_format($lines[$time][$i]).($width !== 0 ? '<br><div class="'.$time[0].'" style="width:'.$width.'px"></div>' : '');
				}
			}
		}

		return '<table class="ppl-tod">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}
}
