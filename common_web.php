<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2022, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Trait with code common between html.php, user.php and history.php.
 */
trait common_web
{
	/**
	 * Calculate how long ago a given $datetime is.
	 */
	private function ago(string $datetime): string
	{
		$diff = date_diff(date_create($this->now), date_create(substr($datetime, 0, 10)));

		if ($diff->y !== 0) {
			$ago = $diff->y.' Year'.($diff->y !== 1 ? 's' : '').' Ago';
		} elseif ($diff->m !== 0) {
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

	/**
	 * Create small, medium and large generic tables.
	 */
	private function create_table(string $title, array $keys, array $types, array $queries, int $rows = 5): ?string
	{
		/**
		 * Amount of columns the table will have (not counting the position column).
		 */
		$cols = count($keys);

		/**
		 * Retrieve the total for the dataset.
		 */
		if (isset($queries[1])) {
			$total = (is_int($queries[1]) ? $queries[1] : db::query_single_col($queries[1]));

			if (is_null($total) || $total === 0) {
				return null;
			}
		}

		$colgroup = '<colgroup>'.str_repeat('<col>', $cols + 1);
		$thead = '<thead><tr><th colspan="'.($cols + 1).'">'.(isset($total) ? '<span class="title-left">'.$title.'</span><span class="title-right">'.number_format($total).' Total</span>' : $title);
		$thead .= '<tr><td>'.$keys[0].'<td><td>'.$keys[1].($cols === 3 ? '<td>'.$keys[2] : '');
		$tbody = '<tbody>';

		/**
		 * Retrieve the main dataset.
		 */
		$results = db::query($queries[0]);
		$i = 0;

		while (($result = $results->fetchArray(SQLITE3_ASSOC)) && $i < $rows) {
			for ($col = 1; $col <= $cols; ++$col) {
				${'v'.$col} = $result['v'.$col];

				switch ($type = $types[$col - 1]) {
					case 'str':
						${'v'.$col} = $this->htmlify(${'v'.$col});
						break;
					case 'str-url':
						/**
						 * This type is used for topics only and implies wrapping.
						 */
						$words = explode(' ', ${'v'.$col});

						foreach ($words as $key => $csword) {
							if (preg_match('/^(www\.|https?:\/\/).+/i', $csword) && !is_null($urlparts = $this->get_urlparts($csword))) {
								$words[$key] = '<a href="'.$this->htmlify($urlparts['url']).'">'.$this->htmlify($urlparts['url']).'</a> ';
							} else {
								$words[$key] = $this->htmlify($csword);
							}
						}

						${'v'.$col} = implode(' ', $words);
						break;
					case 'str-userstats':
						${'v'.$col} = '<a href="user.php?nick='.$this->htmlify(urlencode(${'v'.$col})).'">'.$this->htmlify(${'v'.$col}).'</a>';
						break;
					case 'date':
						${'v'.$col} = date('j M &\a\p\o\s;y', strtotime(${'v'.$col}));
						break;
					case 'date-norepeat':
						${'v'.$col} = date('j M &\a\p\o\s;y', strtotime(${'v'.$col}));

						if (isset($date_prev) && ${'v'.$col} === $date_prev) {
							${'v'.$col} = '';
						} else {
							$date_prev = ${'v'.$col};
						}

						break;
					case 'url':
						${'v'.$col} = '<a href="'.$this->htmlify(${'v'.$col}).'">'.$this->htmlify(${'v'.$col}).'</a>';
						break;
					default:
						/**
						 * By default columns will be formatted as if containing numeric data. If
						 * specified, the $type string should be of the following syntax:
						 * "num[$x][-perc]". Where "$x" specifies the amount of decimals used, and
						 * "-perc" will append a percent sign to the column value.
						 */
						preg_match('/^num(?<decimals>\d)?(?<percentage>-perc)?$/', $type, $matches, PREG_UNMATCHED_AS_NULL);
						${'v'.$col} = number_format(${'v'.$col}, (!is_null($matches['decimals']) ? (int) $matches['decimals'] : 0)).(!is_null($matches['percentage']) ? '%' : '');

						if (preg_match('/^0\.0+%?$/', ${'v'.$col})) {
							${'v'.$col} = '<span class="grey">'.${'v'.$col}.'</span>';
						}
				}
			}

			$tbody .= '<tr><td>'.$v1.'<td>'.++$i.'<td>'.$v2.($cols === 3 ? '<td'.($types[2] === 'str-url' ? ' class="wrap"' : '').'>'.$v3 : '');
		}

		if ($i === 0) {
			return null;
		}

		if ($i < $rows && $title !== 'Most Recent URLs') {
			for (; $i < $rows; ++$i) {
				$tbody .= '<tr><td><td>&nbsp;<td>'.($cols === 3 ? '<td>' : '');
			}
		}

		return '<table class="'.($title === 'Most Referenced Domain Names' ? 'medium' : ($cols === 3 ? 'large' : 'small')).'">'.$colgroup.$thead.$tbody.'</table>'."\n";
	}

	/**
	 * Create the "Activity by $period" graph. Calling class affects scope.
	 */
	private function create_table_activity(string $period): ?string
	{
		$page = get_class($this);
		$times = ['evening', 'afternoon', 'morning', 'night'];

		/**
		 * Execute the appropriate query and fill $dates. Return if there is no data.
		 */
		if ($period === 'day') {
			if ($page === 'html') {
				$results = db::query('SELECT date, l_total, l_night, l_morning, l_afternoon, l_evening FROM channel_activity WHERE date >= DATE(\''.$this->now.'\', \'-23 days\') ORDER BY date ASC');
			} elseif ($page === 'user') {
				$results = db::query('SELECT date, l_total, l_night, l_morning, l_afternoon, l_evening FROM ruid_activity_by_day WHERE ruid = '.$this->ruid.' AND date >= DATE(\''.$this->now.'\', \'-23 days\') ORDER BY date ASC');
			}

			for ($i = 23; $i >= 0; --$i) {
				$dates[] = date('Y-m-d', strtotime('-'.$i.' days', strtotime($this->now)));
			}
		} elseif ($period === 'month') {
			if ($page === 'html') {
				$results = db::query('SELECT SUBSTR(date, 1, 7) AS date, SUM(l_total) AS l_total, SUM(l_night) AS l_night, SUM(l_morning) AS l_morning, SUM(l_afternoon) AS l_afternoon, SUM(l_evening) AS l_evening FROM channel_activity WHERE SUBSTR(date, 1, 7) >= SUBSTR(DATE(\''.$this->now.'\', \'start of month\', \'-23 months\'), 1, 7) GROUP BY SUBSTR(date, 1, 7) ORDER BY date ASC');
			} elseif ($page === 'user') {
				$results = db::query('SELECT date, l_total, l_night, l_morning, l_afternoon, l_evening FROM ruid_activity_by_month WHERE ruid = '.$this->ruid.' AND date >= SUBSTR(DATE(\''.$this->now.'\', \'start of month\', \'-23 months\'), 1, 7) ORDER BY date ASC');
			}

			for ($i = 23; $i >= 0; --$i) {
				$dates[] = date('Y-m', strtotime('-'.$i.' months', strtotime(substr($this->now, 0, 7).'-01')));
			}
		} elseif ($period === 'year') {
			$estimate = false;
			$i = 23;

			/**
			 * If there is more than one day left until the end of the current year, and
			 * there has been activity during a 90-day period prior to $now, we display an
			 * additional column with a bar depicting the estimated line count for the year.
			 *
			 * Secondly, when the leftmost 8 columns are empty we shrink the table so that
			 * the "Activity Distribution by Day" table fits horizontally adjacent to it.
			 */
			if ($page === 'html') {
				if (str_starts_with($this->now, date('Y')) && ($days_left = (int) date('z', strtotime(substr($this->now, 0, 4).'-12-31')) - (int) date('z', strtotime($this->now))) !== 0 && db::query_single_col('SELECT EXISTS (SELECT 1 FROM channel_activity WHERE date BETWEEN DATE(\''.$this->now.'\', \'-90 days\') AND DATE(\''.$this->now.'\', \'-1 day\'))') === 1) {
					$estimate = true;
					--$i;
				}

				if (db::query_single_col('SELECT EXISTS (SELECT 1 FROM channel_activity WHERE SUBSTR(date, 1, 4) BETWEEN \''.((int) substr($this->now, 0, 4) - $i).'\' AND \''.((int) substr($this->now, 0, 4) - ($i - 8)).'\')') === 0) {
					$i -= 8;
				}

				$results = db::query('SELECT SUBSTR(date, 1, 4) AS date, SUM(l_total) AS l_total, SUM(l_night) AS l_night, SUM(l_morning) AS l_morning, SUM(l_afternoon) AS l_afternoon, SUM(l_evening) AS l_evening FROM channel_activity WHERE SUBSTR(date, 1, 4) >= \''.((int) substr($this->now, 0, 4) - $i).'\' GROUP BY SUBSTR(date, 1, 4) ORDER BY date ASC');
			} elseif ($page === 'user') {
				if (($days_left = (int) date('z', strtotime(substr($this->now, 0, 4).'-12-31')) - (int) date('z', strtotime($this->now))) !== 0 && db::query_single_col('SELECT EXISTS (SELECT 1 FROM ruid_activity_by_day WHERE ruid = '.$this->ruid.' AND date BETWEEN DATE(\''.$this->now.'\', \'-90 days\') AND DATE(\''.$this->now.'\', \'-1 day\'))') === 1) {
					$estimate = true;
					--$i;
				}

				if (db::query_single_col('SELECT EXISTS (SELECT 1 FROM ruid_activity_by_year WHERE ruid = '.$this->ruid.' AND date BETWEEN \''.((int) substr($this->now, 0, 4) - $i).'\' AND \''.((int) substr($this->now, 0, 4) - ($i - 8)).'\')') === 0) {
					$i -= 8;
				}

				$results = db::query('SELECT date, l_total, l_night, l_morning, l_afternoon, l_evening FROM ruid_activity_by_year WHERE ruid = '.$this->ruid.' AND date >= \''.((int) substr($this->now, 0, 4) - $i).'\' ORDER BY date ASC');
			}

			for (; $i >= 0; --$i) {
				$dates[] = (string) ((int) substr($this->now, 0, 4) - $i);
			}

			if ($estimate) {
				$dates[] = 'estimate';
			}
		}

		if ($results->fetchArray(SQLITE3_ASSOC) === false) {
			return null;
		}

		$results->reset();

		/**
		 * Arrange data in a usable format. Remember the (first) date with the most
		 * lines so we can make the bar label bold later. $high_lines is used to scale
		 * the bar heights.
		 */
		$high_lines = 0;

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			$lines[$result['date']]['total'] = $result['l_total'];

			foreach ($times as $time) {
				$lines[$result['date']][$time] = $result['l_'.$time];
			}

			if ($lines[$result['date']]['total'] > $high_lines) {
				$high_lines = $lines[$result['date']]['total'];
				$high_date = $result['date'];
			}
		}

		/**
		 * Add the estimate column if applicable.
		 */
		if ($period === 'year' && $estimate) {
			$lines['estimate']['total'] = 0;

			foreach ($times as $time) {
				/**
				 * This query consists of three subqueries that calculate the total lines per
				 * 30 days for each time of day in the past 90 days. Each of these values is
				 * then multiplied by a weight factor, which is lower the further back in time
				 * we go. We end up with some arbitrary nonscientific average value to create an
				 * estimate column with.
				 */
				if ($page === 'html') {
					$subquery1 = '(SELECT IFNULL(SUM(l_'.$time.'), 0) FROM channel_activity WHERE date BETWEEN DATE(\''.$this->now.'\', \'-90 days\') AND DATE(\''.$this->now.'\', \'-61 days\'))';
					$subquery2 = '(SELECT IFNULL(SUM(l_'.$time.'), 0) * 2 FROM channel_activity WHERE date BETWEEN DATE(\''.$this->now.'\', \'-60 days\') AND DATE(\''.$this->now.'\', \'-31 days\'))';
					$subquery3 = '(SELECT IFNULL(SUM(l_'.$time.'), 0) * 3 FROM channel_activity WHERE date BETWEEN DATE(\''.$this->now.'\', \'-30 days\') AND DATE(\''.$this->now.'\', \'-1 day\'))';
				} elseif ($page === 'user') {
					$subquery1 = '(SELECT IFNULL(SUM(l_'.$time.'), 0) FROM ruid_activity_by_day WHERE ruid = '.$this->ruid.' AND date BETWEEN DATE(\''.$this->now.'\', \'-90 days\') AND DATE(\''.$this->now.'\', \'-61 days\'))';
					$subquery2 = '(SELECT IFNULL(SUM(l_'.$time.'), 0) * 2 FROM ruid_activity_by_day WHERE ruid = '.$this->ruid.' AND date BETWEEN DATE(\''.$this->now.'\', \'-60 days\') AND DATE(\''.$this->now.'\', \'-31 days\'))';
					$subquery3 = '(SELECT IFNULL(SUM(l_'.$time.'), 0) * 3 FROM ruid_activity_by_day WHERE ruid = '.$this->ruid.' AND date BETWEEN DATE(\''.$this->now.'\', \'-30 days\') AND DATE(\''.$this->now.'\', \'-1 day\'))';
				}

				$lines['estimate'][$time] = ($lines[substr($this->now, 0, 4)][$time] ?? 0) + (int) round(db::query_single_col('SELECT CAST('.$subquery1.' + '.$subquery2.' + '.$subquery3.' AS REAL) / 180') * $days_left);
				$lines['estimate']['total'] += $lines['estimate'][$time];
			}

			if ($lines['estimate']['total'] > $high_lines) {
				/**
				 * Don't set $high_date because we don't want "Est." to be bold. The previously
				 * set $high_date will be bold instead. $high_lines must however be set in order
				 * to properly scale bar heights.
				 */
				$high_lines = $lines['estimate']['total'];
			}
		}

		$colgroup = '<colgroup>'.str_repeat('<col>', count($dates));
		$thead = '<thead><tr><th colspan="'.count($dates).'">Activity by '.ucfirst($period);
		$tbody = '<tbody><tr>';
		$tfoot = '<tfoot><tr>';

		/**
		 * Assemble each column.
		 */
		foreach ($dates as $date) {
			$tbody .= '<td>';

			if (!isset($lines[$date])) {
				$tbody .= '<ul><li style="height:14px"><span class="grey">n/a</span></ul>';
			} else {
				$total = ($lines[$date]['total'] >= 999500 ? number_format($lines[$date]['total'] / 1000000, 1).'M' : ($lines[$date]['total'] >= 10000 ? round($lines[$date]['total'] / 1000).'K' : $lines[$date]['total']));
				$activity_bar = $this->get_activity_bar('vertical', $lines[$date], $high_lines);
				$tbody .= '<ul'.($date === 'estimate' ? ' class="est"' : '').'><li style="height:'.($activity_bar['px'] + 14).'px">'.$total.$activity_bar['contents'].'</ul>';
			}

			$tfoot .= '<td>'.($date === $high_date ? '<span class="bold">' : '');

			if ($period === 'day') {
				$tfoot .= date('D', strtotime($date)).'<br>'.date('j', strtotime($date));
			} elseif ($period === 'month') {
				$tfoot .= date('M', strtotime($date.'-01')).'<br>&apos;'.substr($date, 2, 2);
			} elseif ($period === 'year') {
				$tfoot .= ($date === 'estimate' ? 'Est.' : '&apos;'.substr($date, 2, 2));
			}

			$tfoot .= ($date === $high_date ? '</span>' : '');
		}

		return '<table class="act'.($period === 'year' ? ' year" style="width:'.(2 + (count($dates) * 34)).'px' : '').'">'.$colgroup.$thead.$tbody.$tfoot.'</table>'."\n";
	}

	/**
	 * Create the "Activity Distribution by Day" graph. Calling class affects scope.
	 */
	private function create_table_activity_distribution_day(): ?string
	{
		$page = get_class($this);
		$days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
		$times = ['evening', 'afternoon', 'morning', 'night'];

		/**
		 * Execute the appropriate query. Return if there is no data.
		 */
		if ($page === 'html') {
			$result = db::query_single_row('SELECT SUM(l_mon_night) AS l_mon_night, SUM(l_mon_morning) AS l_mon_morning, SUM(l_mon_afternoon) AS l_mon_afternoon, SUM(l_mon_evening) AS l_mon_evening, SUM(l_tue_night) AS l_tue_night, SUM(l_tue_morning) AS l_tue_morning, SUM(l_tue_afternoon) AS l_tue_afternoon, SUM(l_tue_evening) AS l_tue_evening, SUM(l_wed_night) AS l_wed_night, SUM(l_wed_morning) AS l_wed_morning, SUM(l_wed_afternoon) AS l_wed_afternoon, SUM(l_wed_evening) AS l_wed_evening, SUM(l_thu_night) AS l_thu_night, SUM(l_thu_morning) AS l_thu_morning, SUM(l_thu_afternoon) AS l_thu_afternoon, SUM(l_thu_evening) AS l_thu_evening, SUM(l_fri_night) AS l_fri_night, SUM(l_fri_morning) AS l_fri_morning, SUM(l_fri_afternoon) AS l_fri_afternoon, SUM(l_fri_evening) AS l_fri_evening, SUM(l_sat_night) AS l_sat_night, SUM(l_sat_morning) AS l_sat_morning, SUM(l_sat_afternoon) AS l_sat_afternoon, SUM(l_sat_evening) AS l_sat_evening, SUM(l_sun_night) AS l_sun_night, SUM(l_sun_morning) AS l_sun_morning, SUM(l_sun_afternoon) AS l_sun_afternoon, SUM(l_sun_evening) AS l_sun_evening, SUM(l_total) AS l_total FROM ruid_lines WHERE l_total != 0');
		} elseif ($page === 'user') {
			$result = db::query_single_row('SELECT l_mon_night, l_mon_morning, l_mon_afternoon, l_mon_evening, l_tue_night, l_tue_morning, l_tue_afternoon, l_tue_evening, l_wed_night, l_wed_morning, l_wed_afternoon, l_wed_evening, l_thu_night, l_thu_morning, l_thu_afternoon, l_thu_evening, l_fri_night, l_fri_morning, l_fri_afternoon, l_fri_evening, l_sat_night, l_sat_morning, l_sat_afternoon, l_sat_evening, l_sun_night, l_sun_morning, l_sun_afternoon, l_sun_evening, l_total FROM ruid_lines WHERE ruid = '.$this->ruid.' AND l_total != 0');
		}

		if (is_null($result)) {
			return null;
		}

		/**
		 * Arrange data in a usable format. Remember the (first) day with the most lines
		 * so we can make the bar label bold later. $high_lines is used to scale the bar
		 * heights.
		 */
		$high_lines = 0;

		foreach ($days as $day) {
			$lines[$day]['total'] = 0;

			foreach ($times as $time) {
				$lines[$day][$time] = $result['l_'.$day.'_'.$time];
				$lines[$day]['total'] += $lines[$day][$time];
			}

			if ($lines[$day]['total'] > $high_lines) {
				$high_lines = $lines[$day]['total'];
				$high_day = $day;
			}
		}

		$colgroup = '<colgroup>'.str_repeat('<col>', 7);
		$thead = '<thead><tr><th colspan="7">Activity Distribution by Day';
		$tbody = '<tbody><tr>';
		$tfoot = '<tfoot><tr>';

		/**
		 * Assemble each column.
		 */
		foreach ($days as $day) {
			$tbody .= '<td>';

			if ($lines[$day]['total'] === 0) {
				$tbody .= '<ul><li style="height:14px"><span class="grey">n/a</span></ul>';
			} else {
				$percentage = ($lines[$day]['total'] / $result['l_total']) * 100;
				$percentage = ($percentage >= 9.95 ? round($percentage) : number_format($percentage, 1)).'%';
				$activity_bar = $this->get_activity_bar('vertical', $lines[$day], $high_lines, true);
				$tbody .= '<ul><li style="height:'.($activity_bar['px'] + 14).'px">'.($percentage === '0.0%' ? '<span class="grey">'.$percentage.'</span>' : $percentage).$activity_bar['contents'].'</ul>';
			}

			$tfoot .= '<td>'.($day === $high_day ? '<span class="bold">'.ucfirst($day).'</span>' : ucfirst($day));
		}

		return '<table class="act day">'.$colgroup.$thead.$tbody.$tfoot.'</table>'."\n";
	}

	/**
	 * Create the "Activity Distribution by Hour" graph. Calling class affects
	 * scope.
	 */
	private function create_table_activity_distribution_hour(): ?string
	{
		$page = get_class($this);

		/**
		 * Execute the appropriate query. Return if there is no data.
		 */
		if ($page === 'html') {
			$result = db::query_single_row('SELECT SUM(l_00) AS l_00, SUM(l_01) AS l_01, SUM(l_02) AS l_02, SUM(l_03) AS l_03, SUM(l_04) AS l_04, SUM(l_05) AS l_05, SUM(l_06) AS l_06, SUM(l_07) AS l_07, SUM(l_08) AS l_08, SUM(l_09) AS l_09, SUM(l_10) AS l_10, SUM(l_11) AS l_11, SUM(l_12) AS l_12, SUM(l_13) AS l_13, SUM(l_14) AS l_14, SUM(l_15) AS l_15, SUM(l_16) AS l_16, SUM(l_17) AS l_17, SUM(l_18) AS l_18, SUM(l_19) AS l_19, SUM(l_20) AS l_20, SUM(l_21) AS l_21, SUM(l_22) AS l_22, SUM(l_23) AS l_23, SUM(l_total) AS l_total FROM channel_activity');
		} elseif ($page === 'user') {
			$result = db::query_single_row('SELECT l_00, l_01, l_02, l_03, l_04, l_05, l_06, l_07, l_08, l_09, l_10, l_11, l_12, l_13, l_14, l_15, l_16, l_17, l_18, l_19, l_20, l_21, l_22, l_23, l_total FROM ruid_lines WHERE ruid = '.$this->ruid.' AND l_total != 0');
		} elseif ($page === 'history') {
			$result = db::query_single_row('SELECT SUM(l_00) AS l_00, SUM(l_01) AS l_01, SUM(l_02) AS l_02, SUM(l_03) AS l_03, SUM(l_04) AS l_04, SUM(l_05) AS l_05, SUM(l_06) AS l_06, SUM(l_07) AS l_07, SUM(l_08) AS l_08, SUM(l_09) AS l_09, SUM(l_10) AS l_10, SUM(l_11) AS l_11, SUM(l_12) AS l_12, SUM(l_13) AS l_13, SUM(l_14) AS l_14, SUM(l_15) AS l_15, SUM(l_16) AS l_16, SUM(l_17) AS l_17, SUM(l_18) AS l_18, SUM(l_19) AS l_19, SUM(l_20) AS l_20, SUM(l_21) AS l_21, SUM(l_22) AS l_22, SUM(l_23) AS l_23, SUM(l_total) AS l_total FROM channel_activity WHERE date LIKE \''.$this->year.(!is_null($this->month) ? '-'.($this->month <= 9 ? '0' : '').$this->month : '').'%\'');
		}

		if (is_null($result)) {
			return null;
		}

		/**
		 * Arrange data in a usable format. Remember the (first) hour with the most
		 * lines so we can make the bar label bold later. $high_lines is used to scale
		 * the bar heights.
		 */
		$high_lines = 0;

		for ($hour = 0; $hour <= 23; ++$hour) {
			$lines[$hour] = $result['l_'.($hour <= 9 ? '0' : '').$hour];

			if ($lines[$hour] > $high_lines) {
				$high_lines = $lines[$hour];
				$high_hour = $hour;
			}
		}

		$colgroup = '<colgroup>'.str_repeat('<col>', 24);
		$thead = '<thead><tr><th colspan="24">Activity Distribution by Hour';
		$tbody = '<tbody><tr>';
		$tfoot = '<tfoot><tr>';

		/**
		 * Assemble each column.
		 */
		for ($hour = 0; $hour <= 23; ++$hour) {
			$tbody .= '<td>';

			if ($lines[$hour] === 0) {
				$tbody .= '<ul><li style="height:14px"><span class="grey">n/a</span></ul>';
			} else {
				$percentage = ($lines[$hour] / $result['l_total']) * 100;
				$percentage = ($percentage >= 9.95 ? round($percentage) : number_format($percentage, 1)).'%';
				$px = (int) round(($lines[$hour] / $high_lines) * 100);
				$tbody .= '<ul><li style="height:'.($px + 14).'px">'.($percentage === '0.0%' ? '<span class="grey">'.$percentage.'</span>' : $percentage);

				if ($px !== 0) {
					$tbody .= '<li class="'.($hour <= 5 ? 'n' : ($hour <= 11 ? 'm' : ($hour <= 17 ? 'a' : 'e'))).'" style="height:'.$px.'px" title="'.number_format($lines[$hour]).'">';
				}

				$tbody .= '</ul>';
			}

			$tfoot .= '<td>'.($hour === $high_hour ? '<span class="bold">'.$hour.'h</span>' : $hour.'h');
		}

		return '<table class="act">'.$colgroup.$thead.$tbody.$tfoot.'</table>'."\n";
	}

	/**
	 * Create the "Most Talkative People" table for the given period. If $period is
	 * omitted then all-time is assumed. Calling class affects scope.
	 */
	private function create_table_people(?string $period = null): ?string
	{
		$page = get_class($this);
		$times = ['night', 'morning', 'afternoon', 'evening'];

		/**
		 * Execute the appropriate queries. Return if there is no data.
		 */
		if ($page === 'html') {
			if (!is_null($period)) {
				$title = 'Most Talkative People &ndash; '.($period === 'month' ? date('F Y', strtotime($this->now)) : substr($this->now, 0, 4));

				if (!is_null($l_total = db::query_single_col('SELECT SUM(l_total) FROM ruid_activity_by_'.$period.' AS t1 JOIN uid_details ON t1.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date = \''.($period === 'month' ? substr($this->now, 0, 7) : substr($this->now, 0, 4)).'\''))) {
					$results = db::query('SELECT csnick, t1.l_total, t1.l_night, t1.l_morning, t1.l_afternoon, t1.l_evening, lasttalked, quote, rank_cur, rank_old FROM ruid_activity_by_'.$period.' AS t1 JOIN uid_details ON t1.ruid = uid_details.uid JOIN ruid_lines ON t1.ruid = ruid_lines.ruid JOIN ruid_rank_'.$period.' ON t1.ruid = ruid_rank_'.$period.'.ruid WHERE status NOT IN (3,4) AND date = \''.($period === 'month' ? substr($this->now, 0, 7) : substr($this->now, 0, 4)).'\' ORDER BY t1.l_total DESC, t1.ruid ASC LIMIT 10');
				}

				$show_rank = (db::query_single_col('SELECT COUNT(*) FROM channel_activity WHERE date LIKE \''.substr($this->now, 0, ($period === 'month' ? 7 : 4)).'%\'') === 1 ? false : true);
			} else {
				$title = 'Most Talkative People &ndash; All-Time';

				if (!is_null($l_total = db::query_single_col('SELECT SUM(l_total) FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND l_total != 0'))) {
					$results = db::query('SELECT csnick, l_total, l_night, l_morning, l_afternoon, l_evening, lasttalked, quote, rank_cur, rank_old FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid JOIN ruid_rank_alltime ON ruid_lines.ruid = ruid_rank_alltime.ruid WHERE status NOT IN (3,4) AND l_total != 0 ORDER BY l_total DESC, ruid_lines.ruid ASC LIMIT '.($this->xxl ? '50' : '30'));
				}

				$show_rank = (db::query_single_col('SELECT COUNT(*) FROM channel_activity') === 1 ? false : true);
			}
		} elseif ($page === 'history') {
			$title = 'Most Talkative People &ndash; '.(!is_null($this->month) ? date('F Y', strtotime($this->year.'-'.($this->month <= 9 ? '0' : '').$this->month.'-01')) : $this->year);

			if (!is_null($l_total = db::query_single_col('SELECT SUM(l_total) FROM '.(!is_null($this->month) ? 'ruid_activity_by_month' : 'ruid_activity_by_year').' AS t1 JOIN uid_details ON t1.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date = \''.$this->year.(!is_null($this->month) ? '-'.($this->month <= 9 ? '0' : '').$this->month : '').'\''))) {
				$results = db::query('SELECT csnick, t1.l_total, t1.l_night, t1.l_morning, t1.l_afternoon, t1.l_evening, lasttalked, quote FROM '.(!is_null($this->month) ? 'ruid_activity_by_month' : 'ruid_activity_by_year').' AS t1 JOIN uid_details ON t1.ruid = uid_details.uid JOIN ruid_lines ON t1.ruid = ruid_lines.ruid WHERE status NOT IN (3,4) AND date = \''.$this->year.(!is_null($this->month) ? '-'.($this->month <= 9 ? '0' : '').$this->month : '').'\' ORDER BY t1.l_total DESC, t1.ruid ASC LIMIT '.($this->xxl ? '50' : '30'));
			}
		}

		if (is_null($l_total)) {
			return null;
		}

		$colgroup = '<colgroup>'.str_repeat('<col>', 7);
		$thead = '<thead><tr><th colspan="7">'.($page === 'html' && $this->link_history_php ? '<span class="title-left">'.$title.'</span><span class="title-right"><a href="history.php'.(!is_null($period) ? '?year='.substr($this->now, 0, 4).($period === 'month' ? '&amp;month='.((int) substr($this->now, 5, 2)) : '') : '').'">History</a></span>' : $title);
		$thead .= '<tr><td>Percentage<td>Lines<td><td>User<td>Last Talked<td>Activity<td>Quote';
		$tbody = '<tbody>';

		/**
		 * Assemble each row.
		 */
		$i = 0;

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			/**
			 * Arrange data in a usable format.
			 */
			$lines['total'] = $result['l_total'];

			foreach ($times as $time) {
				$lines[$time] = $result['l_'.$time];
			}

			/**
			 * Indicate change in ranking compared to previous day except when it doesn't
			 * make sense on the very first day of each specific table.
			 */
			if ($page === 'html' && $show_rank) {
				if ($result['rank_cur'] === $result['rank_old']) {
					$pos = $result['rank_cur'];
				} elseif (is_null($result['rank_old']) || $result['rank_cur'] < $result['rank_old']) {
					$pos = '<span class="up">'.$result['rank_cur'].'</span>';
				} else {
					$pos = '<span class="down">'.$result['rank_cur'].'</span>';
				}
			} else {
				$pos = ++$i;
			}

			$percentage = number_format(($lines['total'] / $l_total) * 100, 2).'%';
			$activity_bar = $this->get_activity_bar('horizontal', $lines, $lines['total']);
			$tbody .= '<tr><td>'.($percentage === '0.00%' ? '<span class="grey">'.$percentage.'</span>' : $percentage).'<td>'.number_format($lines['total']).'<td>'.$pos.'<td>'.($this->link_user_php ? '<a href="user.php?nick='.$this->htmlify(urlencode($result['csnick'])).'">'.$this->htmlify($result['csnick']).'</a>' : $this->htmlify($result['csnick'])).'<td>'.$this->ago($result['lasttalked']).'<td><ul>'.$activity_bar['contents'].'</ul><td>'.$this->htmlify($result['quote']);
		}

		return '<table class="ppl">'.$colgroup.$thead.$tbody.'</table>'."\n";
	}

	/**
	 * Create the "Most Talkative People by Time of Day" table. If $buddies is true
	 * then create the "Chat Buddies by Time of Day" table instead. Calling class
	 * affects scope.
	 */
	private function create_table_people_timeofday(bool $buddies = false): ?string
	{
		$page = get_class($this);
		$times = ['night', 'morning', 'afternoon', 'evening'];

		/**
		 * $high_lines is used to scale the bar widths.
		 */
		$high_lines = 0;

		foreach ($times as $time) {
			/**
			 * Execute the appropriate query.
			 */
			if ($buddies) {
				$title = 'Chat Buddies by Time of Day';

				if ($page === 'html') {
					$results = db::query('SELECT MIN(ruid_active, ruid_passive) || \'_\' || MAX(ruid_active, ruid_passive) AS ruid_pair, (SELECT csnick FROM uid_details WHERE uid = t1.ruid_active) || \' : \' || (SELECT csnick FROM uid_details WHERE uid = t1.ruid_passive) AS csnick, SUM(l_'.$time.') AS l_'.$time.' FROM ruid_buddies AS t1 WHERE l_'.$time.' != 0 GROUP BY ruid_pair ORDER BY l_'.$time.' DESC, ruid_pair ASC LIMIT 10');
				} elseif ($page === 'user') {
					$results = db::query('SELECT csnick, l_'.$time.' FROM ruid_buddies JOIN uid_details ON ruid_passive = uid_details.uid WHERE ruid_active = '.$this->ruid.' AND l_'.$time.' != 0 ORDER BY l_'.$time.' DESC, ruid_passive ASC LIMIT 5');
				}
			} else {
				if ($page === 'html') {
					$results = db::query('SELECT csnick, l_'.$time.' FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND l_'.$time.' != 0 ORDER BY l_'.$time.' DESC, ruid_lines.ruid ASC LIMIT 10');
				} elseif ($page === 'history') {
					$results = db::query('SELECT csnick, l_'.$time.' FROM '.(!is_null($this->month) ? 'ruid_activity_by_month' : 'ruid_activity_by_year').' AS t1 JOIN uid_details ON t1.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date = \''.$this->year.(!is_null($this->month) ? '-'.($this->month <= 9 ? '0' : '').$this->month : '').'\' AND l_'.$time.' != 0 ORDER BY l_'.$time.' DESC, t1.ruid ASC LIMIT 10');
				}
			}

			/**
			 * Arrange data in a usable format.
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

		$colgroup = '<colgroup>'.str_repeat('<col>', 5);
		$thead = '<thead><tr><th colspan="5">'.($title ?? 'Most Talkative People by Time of Day');
		$thead .= '<tr><td><td>Night<br>0h &ndash; 5h<td>Morning<br>6h &ndash; 11h<td>Afternoon<br>12h &ndash; 17h<td>Evening<br>18h &ndash; 23h';
		$tbody = '<tbody>';

		/**
		 * Assemble each row, provided there is at least one column with data.
		 */
		for ($i = 1; $i <= 10; ++$i) {
			if (!isset($lines['night'][$i]) && !isset($lines['morning'][$i]) && !isset($lines['afternoon'][$i]) && !isset($lines['evening'][$i])) {
				break;
			}

			$tbody .= '<tr><td>'.$i;

			foreach ($times as $time) {
				$tbody .= '<td>';

				if (isset($lines[$time][$i])) {
					$px = (int) round(($lines[$time][$i] / $high_lines) * 189);
					$tbody .= $this->htmlify($csnick[$time][$i]).' &ndash; '.number_format($lines[$time][$i]).($px !== 0 ? '<br><div class="'.$time[0].'" style="width:'.$px.'px"></div>' : '');
				}
			}
		}

		/**
		 * Don't return an empty table.
		 */
		if ($i === 1) {
			return null;
		}

		return '<table class="tod">'.$colgroup.$thead.$tbody.'</table>'."\n";
	}

	/**
	 * Assemble the activity bar.
	 */
	private function get_activity_bar(string $orientation, array $lines, int $high_lines, bool $title = false): array
	{
		/**
		 * In case of a vertical orientation, the maximum height of an activity bar is
		 * 100 pixels. For a horizontal orientation the maximum width is 50 pixels. This
		 * value is reflected by $px_max. The $times array dictates the order in which
		 * particular bar sections are placed in the stack as well as the priority these
		 * have in receiving any leftover pixels.
		 */
		if ($orientation === 'vertical') {
			$times = ['evening', 'afternoon', 'morning', 'night'];
			$px_max = 100;
		} elseif ($orientation === 'horizontal') {
			$times = ['night', 'morning', 'afternoon', 'evening'];
			$px_max = 50;
		}

		/**
		 * Calculate the total amount of pixels for current activity bar.
		 */
		$px['total'] = (int) round(($lines['total'] / $high_lines) * $px_max);

		/**
		 * Divide the available pixels among times, according to activity.
		 */
		$unclaimed_pixels = $px['total'];
		$unclaimed_subpixels = [];

		foreach ($times as $time) {
			if ($unclaimed_pixels !== 0 && $lines[$time] !== 0) {
				$px[$time] = (int) (($lines[$time] / $high_lines) * $px_max);
				$unclaimed_pixels -= $px[$time];
				$unclaimed_subpixels[$time] = (($lines[$time] / $high_lines) * $px_max) - $px[$time];
			} else {
				$px[$time] = 0;
			}
		}

		while ($unclaimed_pixels !== 0) {
			$high_subpixels = 0;

			foreach ($unclaimed_subpixels as $time => $subpixels) {
				if ($subpixels > $high_subpixels) {
					$high_subpixels = $subpixels;
					$high_time = $time;
				}
			}

			++$px[$high_time];
			--$unclaimed_pixels;
			$unclaimed_subpixels[$high_time] = 0;
		}

		$activity_bar = [
			'px' => $px['total'],
			'contents' => ''];

		foreach ($times as $time) {
			if ($px[$time] === 0) {
				continue;
			}

			if ($orientation === 'vertical') {
				/**
				 * $px_stacked is the total amount of pixels of all stacked bar sections up to
				 * and including current iteration ($time).
				 */
				$px_stacked = 0;

				switch ($time) {
					case 'evening':
						$px_stacked += $px['evening'];
					case 'afternoon':
						$px_stacked += $px['afternoon'];
					case 'morning':
						$px_stacked += $px['morning'];
					case 'night':
						$px_stacked += $px['night'];
				}

				$activity_bar['contents'] .= '<li class="'.$time[0].'" style="height:'.$px_stacked.'px"'.($title ? ' title="'.number_format($lines['total']).'"' : '').'>';
			} elseif ($orientation === 'horizontal') {
				$activity_bar['contents'] .= '<li class="'.$time[0].'" style="width:'.$px[$time].'px">';
			}
		}

		return $activity_bar;
	}

	/**
	 * Make sure passed string is HTML conformant.
	 */
	private function htmlify(string $string): string
	{
		return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}
}
