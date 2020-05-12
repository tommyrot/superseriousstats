<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2020, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Trait with code common between html.php and user.php.
 */
trait common_html_user
{
	private function create_table_activity_distribution_day(string $span): string
	{
		if ($span === 'channel') {
			$result = db::query_single_row('SELECT SUM(l_mon_night) AS l_mon_night, SUM(l_mon_morning) AS l_mon_morning, SUM(l_mon_afternoon) AS l_mon_afternoon, SUM(l_mon_evening) AS l_mon_evening, SUM(l_tue_night) AS l_tue_night, SUM(l_tue_morning) AS l_tue_morning, SUM(l_tue_afternoon) AS l_tue_afternoon, SUM(l_tue_evening) AS l_tue_evening, SUM(l_wed_night) AS l_wed_night, SUM(l_wed_morning) AS l_wed_morning, SUM(l_wed_afternoon) AS l_wed_afternoon, SUM(l_wed_evening) AS l_wed_evening, SUM(l_thu_night) AS l_thu_night, SUM(l_thu_morning) AS l_thu_morning, SUM(l_thu_afternoon) AS l_thu_afternoon, SUM(l_thu_evening) AS l_thu_evening, SUM(l_fri_night) AS l_fri_night, SUM(l_fri_morning) AS l_fri_morning, SUM(l_fri_afternoon) AS l_fri_afternoon, SUM(l_fri_evening) AS l_fri_evening, SUM(l_sat_night) AS l_sat_night, SUM(l_sat_morning) AS l_sat_morning, SUM(l_sat_afternoon) AS l_sat_afternoon, SUM(l_sat_evening) AS l_sat_evening, SUM(l_sun_night) AS l_sun_night, SUM(l_sun_morning) AS l_sun_morning, SUM(l_sun_afternoon) AS l_sun_afternoon, SUM(l_sun_evening) AS l_sun_evening FROM ruid_lines');
		} elseif ($span === 'user') {
			$result = db::query_single_row('SELECT l_mon_night, l_mon_morning, l_mon_afternoon, l_mon_evening, l_tue_night, l_tue_morning, l_tue_afternoon, l_tue_evening, l_wed_night, l_wed_morning, l_wed_afternoon, l_wed_evening, l_thu_night, l_thu_morning, l_thu_afternoon, l_thu_evening, l_fri_night, l_fri_morning, l_fri_afternoon, l_fri_evening, l_sat_night, l_sat_morning, l_sat_afternoon, l_sat_evening, l_sun_night, l_sun_morning, l_sun_afternoon, l_sun_evening FROM ruid_lines WHERE ruid = '.$this->ruid);
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
