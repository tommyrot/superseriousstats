<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2020, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Trait with code common between html.php and user.php.
 */
trait common_html_user
{
	private function create_table_activity(string $graph, bool $estimate = false): string
	{
		if ($graph === 'day') {
			$results = db::query('SELECT date, l_total, l_night, l_morning, l_afternoon, l_evening FROM channel_activity WHERE date > \''.date('Y-m-d', mktime(0, 0, 0, (int) $this->now->format('n'), (int) $this->now->format('j') - 24, (int) $this->now->format('Y'))).'\'');

			for ($i = 24 - 1; $i >= 0; --$i) {
				$dates[] = date('Y-m-d', mktime(0, 0, 0, (int) $this->now->format('n'), (int) $this->now->format('j') - $i, (int) $this->now->format('Y')));
			}
		} elseif ($graph === 'month') {
			$results = db::query('SELECT SUBSTR(date, 1, 7) AS date, SUM(l_total) AS l_total, SUM(l_night) AS l_night, SUM(l_morning) AS l_morning, SUM(l_afternoon) AS l_afternoon, SUM(l_evening) AS l_evening FROM channel_activity WHERE SUBSTR(date, 1, 7) > \''.date('Y-m', mktime(0, 0, 0, (int) $this->now->format('n') - 24, 1, (int) $this->now->format('Y'))).'\' GROUP BY SUBSTR(date, 1, 7)');

			for ($i = 24 - 1; $i >= 0; --$i) {
				$dates[] = date('Y-m', mktime(0, 0, 0, (int) $this->now->format('n') - $i, 1, (int) $this->now->format('Y')));
			}
		} elseif ($graph === 'year') {
			$results = db::query('SELECT SUBSTR(date, 1, 4) AS date, SUM(l_total) AS l_total, SUM(l_night) AS l_night, SUM(l_morning) AS l_morning, SUM(l_afternoon) AS l_afternoon, SUM(l_evening) AS l_evening FROM channel_activity WHERE SUBSTR(date, 1, 4) > \''.($this->now->format('Y') - 24).'\' GROUP BY SUBSTR(date, 1, 4)');

			for ($i = $this->columns_act_year - ($estimate ? 1 : 0) - 1; $i >= 0; --$i) {
				$dates[] = $this->now->format('Y') - $i;
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
		 * Arrange data in a useable format and remember the first date with the most
		 * lines along with said amount. We use this value to scale the bar heights.
		 */
		$high_lines = 0;

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			$lines[$result['date']]['total'] = $result['l_total'];

			foreach (['evening', 'afternoon', 'morning', 'night'] as $time) {
				$lines[$result['date']][$time] = $result['l_'.$time];
			}

			if ($lines[$result['date']]['total'] > $high_lines) {
				$high_date = $result['date'];
				$high_lines = $lines[$result['date']]['total'];
			}
		}

		/**
		 * Add the estimate bar if applicable.
		 */
		if ($graph === 'year' && $estimate) {
			$lines['estimate']['total'] = 0;

			foreach (['evening', 'afternoon', 'morning', 'night'] as $time) {
				$lastday = new DateTime('last day of december');

				/**
				 * This query consists of three subqueries which calculate the total lines per
				 * 30 days for each time of day in the past 90 days. Each of these values is
				 * then multiplied by a weight factor, which is lower the further back in time
				 * we go. We end up with some artificial non-scientific average value to create
				 * an estimate bar with. Which we totally pulled out of our ass.
				 */
				$subquery1 = 'IFNULL((SELECT SUM(l_'.$time.') FROM channel_activity WHERE date BETWEEN \''.date('Y-m-d', strtotime('-90 day', $this->now->getTimestamp())).'\' AND \''.date('Y-m-d', strtotime('-61 day', $this->now->getTimestamp())).'\'),0)';
				$subquery2 = 'IFNULL((SELECT SUM(l_'.$time.') * 2 FROM channel_activity WHERE date BETWEEN \''.date('Y-m-d', strtotime('-60 day', $this->now->getTimestamp())).'\' AND \''.date('Y-m-d', strtotime('-31 day', $this->now->getTimestamp())).'\'),0)';
				$subquery3 = 'IFNULL((SELECT SUM(l_'.$time.') * 3 FROM channel_activity WHERE date BETWEEN \''.date('Y-m-d', strtotime('-30 day', $this->now->getTimestamp())).'\' AND \''.date('Y-m-d', strtotime('-1 day', $this->now->getTimestamp())).'\'),0)';
				$lines['estimate'][$time] = $lines[date('Y')][$time] + (int) round(db::query_single_col('SELECT CAST(SUM('.$subquery1.' + '.$subquery2.' + '.$subquery3.') AS REAL) / 180') * (int) $lastday->diff($this->now)->days);
				$lines['estimate']['total'] += $lines['estimate'][$time];
			}

			if ($lines['estimate']['total'] > $high_lines) {
				/**
				 * Don't set $high_date because we don't want "Est." to be bold. The previous
				 * highest date will be bold instead. $high_lines must be set in order to
				 * properly calculate bar heights.
				 */
				$high_lines = $lines['estimate']['total'];
			}
		}

		$tr1 = '<tr><th colspan="'.($graph === 'year' ? $this->columns_act_year : 24).'">Activity by '.ucfirst($graph);
		$tr2 = '<tr class="bars">';
		$tr3 = '<tr class="sub">';

		/**
		 * Construct each individual bar.
		 */
		foreach ($dates as $date) {
			if (!array_key_exists($date, $lines)) {
				$tr2 .= '<td><span class="grey">n/a</span>';
			} else {
				$total = ($lines[$date]['total'] >= 999500 ? number_format($lines[$date]['total'] / 1000000, 1).'M' : ($lines[$date]['total'] >= 10000 ? round($lines[$date]['total'] / 1000).'k' : $lines[$date]['total']));
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

					foreach (['evening', 'afternoon', 'morning', 'night'] as $time) {
						if ($lines[$date][$time] !== 0) {
							$height[$time] = intval(($lines[$date][$time] / $high_lines) * 100);
							$unclaimed_pixels -= $height[$time];
							$unclaimed_subpixels[$time] = (($lines[$date][$time] / $high_lines) * 100) - $height[$time];
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

							$tr2 .= '<li class="'.$time[0].'" style="height:'.$height_li.'px">';
						}
					}
				}

				$tr2 .= '</ul>';
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
					 * with highest $unclaimed_subpixels.
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
