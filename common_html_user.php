<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2020, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Trait with code common between html.php and user.php.
 */
trait common_html_user
{
	private function create_table_activity(string $period): ?string
	{
		$page = get_class($this);
		$times = ['evening', 'afternoon', 'morning', 'night'];

		/**
		 * Execute the appropriate query and fill $dates. Return if there is no data.
		 */
		if ($period === 'day') {
			if ($page === 'html') {
				$results = db::query('SELECT date, l_total, l_night, l_morning, l_afternoon, l_evening FROM channel_activity WHERE date >= \''.date('Y-m-d', strtotime('-23 days', strtotime($this->now))).'\' ORDER BY date ASC');
			} elseif ($page === 'user') {
				$results = db::query('SELECT date, l_total, l_night, l_morning, l_afternoon, l_evening FROM ruid_activity_by_day WHERE ruid = '.$this->ruid.' AND date >= \''.date('Y-m-d', strtotime('-23 days', strtotime($this->now))).'\' ORDER BY date ASC');
			}

			for ($i = 23; $i >= 0; --$i) {
				$dates[] = date('Y-m-d', strtotime('-'.$i.' days', strtotime($this->now)));
			}
		} elseif ($period === 'month') {
			if ($page === 'html') {
				$results = db::query('SELECT SUBSTR(date, 1, 7) AS date, SUM(l_total) AS l_total, SUM(l_night) AS l_night, SUM(l_morning) AS l_morning, SUM(l_afternoon) AS l_afternoon, SUM(l_evening) AS l_evening FROM channel_activity WHERE SUBSTR(date, 1, 7) >= \''.date('Y-m', strtotime('-23 months', strtotime(substr($this->now, 0, 7).'-01'))).'\' GROUP BY SUBSTR(date, 1, 7) ORDER BY date ASC');
			} elseif ($page === 'user') {
				$results = db::query('SELECT date, l_total, l_night, l_morning, l_afternoon, l_evening FROM ruid_activity_by_month WHERE ruid = '.$this->ruid.' AND date >= \''.date('Y-m', strtotime('-23 months', strtotime(substr($this->now, 0, 7).'-01'))).'\' ORDER BY date ASC');
			}

			for ($i = 23; $i >= 0; --$i) {
				$dates[] = date('Y-m', strtotime('-'.$i.' months', strtotime(substr($this->now, 0, 7).'-01')));
			}
		} elseif ($period === 'year') {
			$estimate = false;
			$i = 23;

			/**
			 * If there is more than one day left until the end of the current year, and
			 * there has been activity during a 90 day period prior to $now, we display an
			 * additional column with a bar depicting the estimated line count for the year.
			 *
			 * Secondly, when the leftmost 8 columns are empty we shrink the table so that
			 * the "Activity Distribution by Day" table fits horizontally adjacent to it.
			 */
			if ($page === 'html') {
				if (substr($this->now, 0, 4) === date('Y') && ($days_left = (int) date('z', strtotime(substr($this->now, 0, 4).'-12-31')) - (int) date('z', strtotime($this->now))) !== 0 && db::query_single_col('SELECT COUNT(*) FROM channel_activity WHERE date BETWEEN \''.date('Y-m-d', strtotime('-90 days', strtotime($this->now))).'\' AND \''.date('Y-m-d', strtotime('-1 days', strtotime($this->now))).'\'') !== 0) {
					$estimate = true;
					--$i;
				}

				if (db::query_single_col('SELECT COUNT(*) FROM channel_activity WHERE SUBSTR(date, 1, 4) BETWEEN \''.((int) substr($this->now, 0, 4) - $i).'\' AND \''.((int) substr($this->now, 0, 4) - ($i - 8)).'\'') === 0) {
					$i -= 8;
				}

				$results = db::query('SELECT SUBSTR(date, 1, 4) AS date, SUM(l_total) AS l_total, SUM(l_night) AS l_night, SUM(l_morning) AS l_morning, SUM(l_afternoon) AS l_afternoon, SUM(l_evening) AS l_evening FROM channel_activity WHERE SUBSTR(date, 1, 4) >= \''.((int) substr($this->now, 0, 4) - $i).'\' GROUP BY SUBSTR(date, 1, 4) ORDER BY date ASC');
			} elseif ($page === 'user') {
				if (($days_left = (int) date('z', strtotime(substr($this->now, 0, 4).'-12-31')) - (int) date('z', strtotime($this->now))) !== 0 && db::query_single_col('SELECT COUNT(*) FROM ruid_activity_by_day WHERE ruid = '.$this->ruid.' AND date BETWEEN \''.date('Y-m-d', strtotime('-90 days', strtotime($this->now))).'\' AND \''.date('Y-m-d', strtotime('-1 days', strtotime($this->now))).'\'') !== 0) {
					$estimate = true;
					--$i;
				}

				if (db::query_single_col('SELECT COUNT(*) FROM ruid_activity_by_year WHERE ruid = '.$this->ruid.' AND date BETWEEN \''.((int) substr($this->now, 0, 4) - $i).'\' AND \''.((int) substr($this->now, 0, 4) - ($i - 8)).'\'') === 0) {
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

		if (($result = $results->fetchArray(SQLITE3_ASSOC)) === false) {
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
					$subquery1 = '(SELECT IFNULL(SUM(l_'.$time.'), 0) FROM channel_activity WHERE l_'.$time.' != 0 AND date BETWEEN \''.date('Y-m-d', strtotime('-90 day', strtotime($this->now))).'\' AND \''.date('Y-m-d', strtotime('-61 day', strtotime($this->now))).'\')';
					$subquery2 = '(SELECT IFNULL(SUM(l_'.$time.'), 0) * 2 FROM channel_activity WHERE l_'.$time.' != 0 AND date BETWEEN \''.date('Y-m-d', strtotime('-60 day', strtotime($this->now))).'\' AND \''.date('Y-m-d', strtotime('-31 day', strtotime($this->now))).'\')';
					$subquery3 = '(SELECT IFNULL(SUM(l_'.$time.'), 0) * 3 FROM channel_activity WHERE l_'.$time.' != 0 AND date BETWEEN \''.date('Y-m-d', strtotime('-30 day', strtotime($this->now))).'\' AND \''.date('Y-m-d', strtotime('-1 day', strtotime($this->now))).'\')';
				} elseif ($page === 'user') {
					$subquery1 = '(SELECT IFNULL(SUM(l_'.$time.'), 0) FROM ruid_activity_by_day WHERE l_'.$time.' != 0 AND ruid = '.$this->ruid.' AND date BETWEEN \''.date('Y-m-d', strtotime('-90 day', strtotime($this->now))).'\' AND \''.date('Y-m-d', strtotime('-61 day', strtotime($this->now))).'\')';
					$subquery2 = '(SELECT IFNULL(SUM(l_'.$time.'), 0) * 2 FROM ruid_activity_by_day WHERE l_'.$time.' != 0 AND ruid = '.$this->ruid.' AND date BETWEEN \''.date('Y-m-d', strtotime('-60 day', strtotime($this->now))).'\' AND \''.date('Y-m-d', strtotime('-31 day', strtotime($this->now))).'\')';
					$subquery3 = '(SELECT IFNULL(SUM(l_'.$time.'), 0) * 3 FROM ruid_activity_by_day WHERE l_'.$time.' != 0 AND ruid = '.$this->ruid.' AND date BETWEEN \''.date('Y-m-d', strtotime('-30 day', strtotime($this->now))).'\' AND \''.date('Y-m-d', strtotime('-1 day', strtotime($this->now))).'\')';
				}

				$lines['estimate'][$time] = $lines[substr($this->now, 0, 4)][$time] + (int) round(db::query_single_col('SELECT CAST(SUM('.$subquery1.' + '.$subquery2.' + '.$subquery3.') AS REAL) / 180') * $days_left);
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

		$tr1 = '<tr><th colspan="'.count($dates).'">Activity by '.ucfirst($period);
		$tr2 = '<tr class="bars">';
		$tr3 = '<tr class="sub">';

		/**
		 * Construct each individual bar.
		 */
		foreach ($dates as $date) {
			if (!array_key_exists($date, $lines)) {
				$tr2 .= '<td><span class="grey">n/a</span>';
			} else {
				$total = ($lines[$date]['total'] >= 999500 ? number_format($lines[$date]['total'] / 1000000, 1).'M' : ($lines[$date]['total'] >= 10000 ? round($lines[$date]['total'] / 1000).'K' : $lines[$date]['total']));
				$height['total'] = (int) round(($lines[$date]['total'] / $high_lines) * 100);
				$tr2 .= '<td'.($date === 'estimate' ? ' class="est"' : '').'><ul><li class="num" style="height:'.($height['total'] + 14).'px">'.$total;

				if ($height['total'] !== 0) {
					/**
					 * Due to flooring (intval) it can happen that not all pixels of the full bar
					 * height are used initially. Distribute the leftover pixels among the times
					 * with highest $unclaimed_subpixels.
					 */
					$unclaimed_pixels = $height['total'];
					$unclaimed_subpixels = [];

					foreach ($times as $time) {
						if ($lines[$date][$time] !== 0) {
							$height[$time] = intval(($lines[$date][$time] / $high_lines) * 100);
							$unclaimed_pixels -= $height[$time];
							$unclaimed_subpixels[$time] = (($lines[$date][$time] / $high_lines) * 100) - $height[$time];
						} else {
							$height[$time] = 0;
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

						++$height[$high_time];
						$unclaimed_subpixels[$high_time] = 0;
						--$unclaimed_pixels;
					}

					/**
					 * The bar sections for different times are layered on top of each other so we
					 * need to add the overlapping parts' heights to get our final height value.
					 */
					foreach ($times as $time) {
						if ($height[$time] !== 0) {
							$height_li = 0;

							switch ($time) {
								case 'evening':
									$height_li += $height['evening'];
								case 'afternoon':
									$height_li += $height['afternoon'];
								case 'morning':
									$height_li += $height['morning'];
								case 'night':
									$height_li += $height['night'];
							}

							$tr2 .= '<li class="'.$time[0].'" style="height:'.$height_li.'px">';
						}
					}
				}

				$tr2 .= '</ul>';
			}

			$tr3 .= '<td'.($date === $high_date ? ' class="bold"' : '').'>';

			if ($period === 'day') {
				$tr3 .= date('D', strtotime($date)).'<br>'.date('j', strtotime($date));
			} elseif ($period === 'month') {
				$tr3 .= date('M', strtotime($date.'-01')).'<br>&apos;'.substr($date, 2, 2);
			} elseif ($period === 'year') {
				$tr3 .= ($date === 'estimate' ? 'Est.' : '&apos;'.substr($date, 2, 2));
			}
		}

		return '<table class="act'.($period === 'year' ? '-year" style="width:'.(2 + (count($dates) * 34)).'px' : '').'">'.$tr1.$tr2.$tr3.'</table>'."\n";
	}

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
			$result = db::query_single_row('SELECT l_mon_night, l_mon_morning, l_mon_afternoon, l_mon_evening, l_tue_night, l_tue_morning, l_tue_afternoon, l_tue_evening, l_wed_night, l_wed_morning, l_wed_afternoon, l_wed_evening, l_thu_night, l_thu_morning, l_thu_afternoon, l_thu_evening, l_fri_night, l_fri_morning, l_fri_afternoon, l_fri_evening, l_sat_night, l_sat_morning, l_sat_afternoon, l_sat_evening, l_sun_night, l_sun_morning, l_sun_afternoon, l_sun_evening, l_total FROM ruid_lines WHERE l_total != 0 AND ruid = '.$this->ruid);
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

		$tr1 = '<tr><th colspan="7">Activity Distribution by Day';
		$tr2 = '<tr class="bars">';
		$tr3 = '<tr class="sub">';

		/**
		 * Construct each individual bar.
		 */
		foreach ($days as $day) {
			if ($lines[$day]['total'] === 0) {
				$tr2 .= '<td><span class="grey">n/a</span>';
			} else {
				$percentage = ($lines[$day]['total'] / $result['l_total']) * 100;
				$percentage = ($percentage >= 9.95 ? round($percentage) : number_format($percentage, 1)).'%';
				$height['total'] = (int) round(($lines[$day]['total'] / $high_lines) * 100);
				$tr2 .= '<td><ul><li class="num" style="height:'.($height['total'] + 14).'px">'.$percentage;

				if ($height['total'] !== 0) {
					/**
					 * Due to flooring (intval) it can happen that not all pixels of the full bar
					 * height are used initially. Distribute the leftover pixels among the times
					 * with highest $unclaimed_subpixels.
					 */
					$unclaimed_pixels = $height['total'];
					$unclaimed_subpixels = [];

					foreach ($times as $time) {
						if ($lines[$day][$time] !== 0) {
							$height[$time] = intval(($lines[$day][$time] / $high_lines) * 100);
							$unclaimed_pixels -= $height[$time];
							$unclaimed_subpixels[$time] = (($lines[$day][$time] / $high_lines) * 100) - $height[$time];
						} else {
							$height[$time] = 0;
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

						++$height[$high_time];
						$unclaimed_subpixels[$high_time] = 0;
						--$unclaimed_pixels;
					}

					/**
					 * The bar sections for different times are layered on top of each other so we
					 * need to add the overlapping parts' heights to get our final height value.
					 */
					foreach ($times as $time) {
						if ($height[$time] !== 0) {
							$height_li = 0;

							switch ($time) {
								case 'evening':
									$height_li += $height['evening'];
								case 'afternoon':
									$height_li += $height['afternoon'];
								case 'morning':
									$height_li += $height['morning'];
								case 'night':
									$height_li += $height['night'];
							}

							$tr2 .= '<li class="'.$time[0].'" style="height:'.$height_li.'px" title="'.number_format($lines[$day]['total']).'">';
						}
					}
				}

				$tr2 .= '</ul>';
			}

			$tr3 .= '<td'.($day === $high_day ? ' class="bold"' : '').'>'.ucfirst($day);
		}

		return '<table class="act-day">'.$tr1.$tr2.$tr3.'</table>'."\n";
	}
}
