<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2020, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Trait with code common between html.php and user.php.
 */
trait common_html_user
{
	private function create_table_activity(string $graph): string
	{
		if ($graph === 'day') {
			$columns = 24;
			$results = db::query('SELECT date, l_total, l_night, l_morning, l_afternoon, l_evening FROM channel_activity WHERE date > \''.date('Y-m-d', mktime(0, 0, 0, (int) $this->date_last_log_parsed->format('n'), (int) $this->date_last_log_parsed->format('j') - 24, (int) $this->date_last_log_parsed->format('Y'))).'\'');

			for ($i = $columns - 1; $i >= 0; --$i) {
				$dates[] = date('Y-m-d', mktime(0, 0, 0, (int) $this->date_last_log_parsed->format('n'), (int) $this->date_last_log_parsed->format('j') - $i, (int) $this->date_last_log_parsed->format('Y')));
			}
		} elseif ($graph === 'month') {
			$columns = 24;
			$results = db::query('SELECT SUBSTR(date, 1, 7) AS date, SUM(l_total) AS l_total, SUM(l_night) AS l_night, SUM(l_morning) AS l_morning, SUM(l_afternoon) AS l_afternoon, SUM(l_evening) AS l_evening FROM channel_activity WHERE SUBSTR(date, 1, 7) > \''.date('Y-m', mktime(0, 0, 0, (int) $this->date_last_log_parsed->format('n') - 24, 1, (int) $this->date_last_log_parsed->format('Y'))).'\' GROUP BY SUBSTR(date, 1, 7)');

			for ($i = $columns - 1; $i >= 0; --$i) {
				$dates[] = date('Y-m', mktime(0, 0, 0, (int) $this->date_last_log_parsed->format('n') - $i, 1, (int) $this->date_last_log_parsed->format('Y')));
			}
		} elseif ($graph === 'year') {
			$columns = $this->columns_act_year;
			$results = db::query('SELECT SUBSTR(date, 1, 4) AS date, SUM(l_total) AS l_total, SUM(l_night) AS l_night, SUM(l_morning) AS l_morning, SUM(l_afternoon) AS l_afternoon, SUM(l_evening) AS l_evening FROM channel_activity WHERE SUBSTR(date, 1, 4) > \''.($this->date_last_log_parsed->format('Y') - 24).'\' GROUP BY SUBSTR(date, 1, 4)');

			for ($i = $columns - ($this->estimate ? 1 : 0) - 1; $i >= 0; --$i) {
				$dates[] = $this->date_last_log_parsed->format('Y') - $i;
			}

			if ($this->estimate) {
				$dates[] = 'estimate';
			}
		}

		if (($result = $results->fetchArray(SQLITE3_ASSOC)) === false) {
			return null;
		}

		$high_date = '';
		$high_value = 0;
		$results->reset();

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			$l_afternoon[$result['date']] = $result['l_afternoon'];
			$l_evening[$result['date']] = $result['l_evening'];
			$l_morning[$result['date']] = $result['l_morning'];
			$l_night[$result['date']] = $result['l_night'];
			$l_total[$result['date']] = $result['l_total'];

			if ($result['l_total'] > $high_value) {
				$high_date = $result['date'];
				$high_value = $result['l_total'];
			}
		}

		if ($graph === 'year' && $this->estimate) {
			$result = db::query_single_row('SELECT CAST(SUM(l_night) AS REAL) / 90 AS l_night_avg, CAST(SUM(l_morning) AS REAL) / 90 AS l_morning_avg, CAST(SUM(l_afternoon) AS REAL) / 90 AS l_afternoon_avg, CAST(SUM(l_evening) AS REAL) / 90 AS l_evening_avg FROM channel_activity WHERE date > \''.date('Y-m-d', mktime(0, 0, 0, (int) $this->date_last_log_parsed->format('n'), (int) $this->date_last_log_parsed->format('j') - 90, (int) $this->date_last_log_parsed->format('Y'))).'\'');
			$l_afternoon['estimate'] = $l_afternoon[$this->date_last_log_parsed->format('Y')] + round($result['l_afternoon_avg'] * $this->days_left);
			$l_evening['estimate'] = $l_evening[$this->date_last_log_parsed->format('Y')] + round($result['l_evening_avg'] * $this->days_left);
			$l_morning['estimate'] = $l_morning[$this->date_last_log_parsed->format('Y')] + round($result['l_morning_avg'] * $this->days_left);
			$l_night['estimate'] = $l_night[$this->date_last_log_parsed->format('Y')] + round($result['l_night_avg'] * $this->days_left);
			$l_total['estimate'] = $l_afternoon['estimate'] + $l_evening['estimate'] + $l_morning['estimate'] + $l_night['estimate'];

			if ($l_total['estimate'] > $high_value) {
				/**
				 * Don't set $high_date because we don't want "Est." to be bold. The previous
				 * highest date will be bold instead. $high_value must be set in order to
				 * calculate bar heights.
				 */
				$high_value = $l_total['estimate'];
			}
		}

		$times = ['evening', 'afternoon', 'morning', 'night'];
		$tr1 = '<tr><th colspan="'.$columns.'">Activity by '.ucfirst($graph);
		$tr2 = '<tr class="bars">';
		$tr3 = '<tr class="sub">';

		foreach ($dates as $date) {
			if (!array_key_exists($date, $l_total)) {
				$tr2 .= '<td><span class="grey">n/a</span>';
			} else {
				if ($l_total[$date] >= 999500) {
					$total = number_format($l_total[$date] / 1000000, 1).'M';
				} elseif ($l_total[$date] >= 10000) {
					$total = round($l_total[$date] / 1000).'k';
				} else {
					$total = $l_total[$date];
				}

				$height_int['total'] = (int) round(($l_total[$date] / $high_value) * 100);
				$height = $height_int['total'];

				foreach ($times as $time) {
					if (${'l_'.$time}[$date] !== 0) {
						$height_float[$time] = (float) (${'l_'.$time}[$date] / $high_value) * 100;
						$height_int[$time] = (int) floor($height_float[$time]);
						$height_remainders[$time] = $height_float[$time] - $height_int[$time];
						$height -= $height_int[$time];
					} else {
						$height_int[$time] = 0;
					}
				}

				if ($height !== 0) {
					arsort($height_remainders);

					foreach ($height_remainders as $time => $remainder) {
						--$height;
						++$height_int[$time];

						if ($height === 0) {
							break;
						}
					}
				}

				$tr2 .= '<td'.($date === 'estimate' ? ' class="est"' : '').'><ul><li class="num" style="height:'.($height_int['total'] + 14).'px">'.$total;

				foreach ($times as $time) {
					if ($height_int[$time] !== 0) {
						if ($time === 'evening') {
							$height_li = $height_int['night'] + $height_int['morning'] + $height_int['afternoon'] + $height_int['evening'];
						} elseif ($time === 'afternoon') {
							$height_li = $height_int['night'] + $height_int['morning'] + $height_int['afternoon'];
						} elseif ($time === 'morning') {
							$height_li = $height_int['night'] + $height_int['morning'];
						} elseif ($time === 'night') {
							$height_li = $height_int['night'];
						}

						$tr2 .= '<li class="'.$time[0].'" style="height:'.$height_li.'px">';
					}
				}

				$tr2 .= '</ul>';

				/**
				 * It's important to unset $height_remainders so the next iteration won't try to
				 * work with old values.
				 */
				unset($height_remainders);
			}

			if ($graph === 'day') {
				$tr3 .= '<td'.($date === $high_date ? ' class="bold"' : '').'>'.date('D', strtotime($date)).'<br>'.date('j', strtotime($date));
			} elseif ($graph === 'month') {
				$tr3 .= '<td'.($date === $high_date ? ' class="bold"' : '').'>'.date('M', strtotime($date.'-01')).'<br>'.date('\'y', strtotime($date.'-01'));
			} elseif ($graph === 'year') {
				$tr3 .= '<td'.($date === (int) $high_date ? ' class="bold"' : '').'>'.($date === 'estimate' ? 'Est.' : date('\'y', strtotime($date.'-01-01')));
			}
		}

		return '<table class="act'.($graph === 'year' ? '-year' : '').'">'.$tr1.$tr2.$tr3.'</table>'."\n";
	}

	private function create_table_activity_distribution_day(): string
	{
		/**
		 * Execute the appropriate query.
		 */
		switch (get_class($this)) {
			case 'html':
				$result = db::query_single_row('SELECT SUM(l_mon_night) AS l_mon_night, SUM(l_mon_morning) AS l_mon_morning, SUM(l_mon_afternoon) AS l_mon_afternoon, SUM(l_mon_evening) AS l_mon_evening, SUM(l_tue_night) AS l_tue_night, SUM(l_tue_morning) AS l_tue_morning, SUM(l_tue_afternoon) AS l_tue_afternoon, SUM(l_tue_evening) AS l_tue_evening, SUM(l_wed_night) AS l_wed_night, SUM(l_wed_morning) AS l_wed_morning, SUM(l_wed_afternoon) AS l_wed_afternoon, SUM(l_wed_evening) AS l_wed_evening, SUM(l_thu_night) AS l_thu_night, SUM(l_thu_morning) AS l_thu_morning, SUM(l_thu_afternoon) AS l_thu_afternoon, SUM(l_thu_evening) AS l_thu_evening, SUM(l_fri_night) AS l_fri_night, SUM(l_fri_morning) AS l_fri_morning, SUM(l_fri_afternoon) AS l_fri_afternoon, SUM(l_fri_evening) AS l_fri_evening, SUM(l_sat_night) AS l_sat_night, SUM(l_sat_morning) AS l_sat_morning, SUM(l_sat_afternoon) AS l_sat_afternoon, SUM(l_sat_evening) AS l_sat_evening, SUM(l_sun_night) AS l_sun_night, SUM(l_sun_morning) AS l_sun_morning, SUM(l_sun_afternoon) AS l_sun_afternoon, SUM(l_sun_evening) AS l_sun_evening FROM ruid_lines');
				break;
			case 'user':
				$result = db::query_single_row('SELECT l_mon_night, l_mon_morning, l_mon_afternoon, l_mon_evening, l_tue_night, l_tue_morning, l_tue_afternoon, l_tue_evening, l_wed_night, l_wed_morning, l_wed_afternoon, l_wed_evening, l_thu_night, l_thu_morning, l_thu_afternoon, l_thu_evening, l_fri_night, l_fri_morning, l_fri_afternoon, l_fri_evening, l_sat_night, l_sat_morning, l_sat_afternoon, l_sat_evening, l_sun_night, l_sun_morning, l_sun_afternoon, l_sun_evening FROM ruid_lines WHERE ruid = '.$this->ruid);
				break;
		}

		/**
		 * Arrange data in a useable format and remember the first day with the most
		 * lines along with said amount. We use this value to scale the bar heights.
		 */
		$high_lines = 0;

		foreach (['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as $day) {
			$lines[$day]['total'] = 0;

			foreach (['evening', 'afternoon', 'morning', 'night'] as $time) {
				$lines[$day][$time] = $result['l_'.$day.'_'.$time];
				$lines[$day]['total'] += $lines[$day][$time];
			}

			if ($lines[$day]['total'] > $high_lines) {
				$high_day = $day;
				$high_lines = $lines[$day]['total'];
			}
		}

		$tr1 = '<tr><th colspan="7">Activity Distribution by Day';
		$tr2 = '<tr class="bars">';
		$tr3 = '<tr class="sub">';

		/**
		 * Construct each individual bar.
		 */
		foreach (['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as $day) {
			if ($lines[$day]['total'] === 0) {
				$tr2 .= '<td><span class="grey">n/a</span>';
			} else {
				$percentage = ($lines[$day]['total'] / $this->l_total) * 100;
				$percentage = ($percentage >= 9.95 ? round($percentage) : number_format($percentage, 1)).'%';
				$height['total'] = (int) round(($lines[$day]['total'] / $high_lines) * 100);
				$tr2 .= '<td><ul><li class="num" style="height:'.($height['total'] + 14).'px">'.$percentage;

				if ($height['total'] !== 0) {
					/**
					 * Due to flooring (intval) it can happen that not all pixels of the full bar
					 * height are used initially. Distribute the leftover pixels among the times
					 * with highest "subpixel" remainders.
					 */
					$unclaimed_pixels = $height['total'];
					$unclaimed_subpixels = [];

					foreach (['evening', 'afternoon', 'morning', 'night'] as $time) {
						if ($lines[$day][$time] !== 0) {
							$height[$time] = intval(($lines[$day][$time] / $high_lines) * 100);
							$unclaimed_pixels -= $height[$time];
							$unclaimed_subpixels[$time] = (($lines[$day][$time] / $high_lines) * 100) - $height[$time];
						} else {
							$height[$time] = 0;
						}
					}

					if ($unclaimed_pixels !== 0) {
						arsort($unclaimed_subpixels);

						foreach ($unclaimed_subpixels as $time => $subpixels) {
							--$unclaimed_pixels;
							++$height[$time];

							if ($unclaimed_pixels === 0) {
								break;
							}
						}
					}

					/**
					 * The bar sections for different times are layered on top of each other so we
					 * need to add the overlapping parts' heights to get our final height value.
					 */
					foreach (['evening', 'afternoon', 'morning', 'night'] as $time) {
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
