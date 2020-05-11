<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2020, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Trait with code common between html.php, user.php and history.php.
 */
trait common_html_user_history
{
	private function create_table_activity_distribution_hour(string $span): string
	{
		switch ($span) {
			case 'channel':
				$result = db::query_single_row('SELECT SUM(l_00) AS l_00, SUM(l_01) AS l_01, SUM(l_02) AS l_02, SUM(l_03) AS l_03, SUM(l_04) AS l_04, SUM(l_05) AS l_05, SUM(l_06) AS l_06, SUM(l_07) AS l_07, SUM(l_08) AS l_08, SUM(l_09) AS l_09, SUM(l_10) AS l_10, SUM(l_11) AS l_11, SUM(l_12) AS l_12, SUM(l_13) AS l_13, SUM(l_14) AS l_14, SUM(l_15) AS l_15, SUM(l_16) AS l_16, SUM(l_17) AS l_17, SUM(l_18) AS l_18, SUM(l_19) AS l_19, SUM(l_20) AS l_20, SUM(l_21) AS l_21, SUM(l_22) AS l_22, SUM(l_23) AS l_23 FROM channel_activity');
				break;
			case 'user':
				$result = db::query_single_row('SELECT l_00, l_01, l_02, l_03, l_04, l_05, l_06, l_07, l_08, l_09, l_10, l_11, l_12, l_13, l_14, l_15, l_16, l_17, l_18, l_19, l_20, l_21, l_22, l_23 FROM ruid_lines WHERE ruid = '.$this->ruid);
				break;
			case 'year':
				$result = db::query_single_row('SELECT SUM(l_00) AS l_00, SUM(l_01) AS l_01, SUM(l_02) AS l_02, SUM(l_03) AS l_03, SUM(l_04) AS l_04, SUM(l_05) AS l_05, SUM(l_06) AS l_06, SUM(l_07) AS l_07, SUM(l_08) AS l_08, SUM(l_09) AS l_09, SUM(l_10) AS l_10, SUM(l_11) AS l_11, SUM(l_12) AS l_12, SUM(l_13) AS l_13, SUM(l_14) AS l_14, SUM(l_15) AS l_15, SUM(l_16) AS l_16, SUM(l_17) AS l_17, SUM(l_18) AS l_18, SUM(l_19) AS l_19, SUM(l_20) AS l_20, SUM(l_21) AS l_21, SUM(l_22) AS l_22, SUM(l_23) AS l_23 FROM channel_activity WHERE date LIKE \''.$this->year.'%\'');
				break;
			case 'month':
				$result = db::query_single_row('SELECT SUM(l_00) AS l_00, SUM(l_01) AS l_01, SUM(l_02) AS l_02, SUM(l_03) AS l_03, SUM(l_04) AS l_04, SUM(l_05) AS l_05, SUM(l_06) AS l_06, SUM(l_07) AS l_07, SUM(l_08) AS l_08, SUM(l_09) AS l_09, SUM(l_10) AS l_10, SUM(l_11) AS l_11, SUM(l_12) AS l_12, SUM(l_13) AS l_13, SUM(l_14) AS l_14, SUM(l_15) AS l_15, SUM(l_16) AS l_16, SUM(l_17) AS l_17, SUM(l_18) AS l_18, SUM(l_19) AS l_19, SUM(l_20) AS l_20, SUM(l_21) AS l_21, SUM(l_22) AS l_22, SUM(l_23) AS l_23 FROM channel_activity WHERE date LIKE \''.$this->year.'-'.$this->month.'%\'');
				break;
		}

		$high_hour = null;
		$high_l_total = 0;

		for ($hour = 0; $hour <= 23; ++$hour) {
			$l_total[$hour] = $result['l_'.($hour <= 9 ? '0' : '').$hour];

			if ($l_total[$hour] > $high_l_total) {
				$high_hour = $hour;
				$high_l_total = $l_total[$hour];
			}
		}

		$tr1 = '<tr><th colspan="24">Activity Distribution by Hour';
		$tr2 = '<tr class="bars">';
		$tr3 = '<tr class="sub">';

		for ($hour = 0; $hour <= 23; ++$hour) {
			if ($l_total[$hour] === 0) {
				$tr2 .= '<td><span class="grey">n/a</span>';
			} else {
				$percentage = ($l_total[$hour] / $this->l_total) * 100;
				$percentage = ($percentage >= 9.95 ? round($percentage) : number_format($percentage, 1)).'%';
				$height = round(($l_total[$hour] / $high_l_total) * 100);
				$tr2 .= '<td><ul><li class="num" style="height:'.($height + 14).'px">'.$percentage;

				if ($height !== 0.0) {
					if ($hour >= 0 && $hour <= 5) {
						$time = 'night';
					} elseif ($hour >= 6 && $hour <= 11) {
						$time = 'morning';
					} elseif ($hour >= 12 && $hour <= 17) {
						$time = 'afternoon';
					} elseif ($hour >= 18 && $hour <= 23) {
						$time = 'evening';
					}

					$tr2 .= '<li class="'.$time[0].'" style="height:'.$height.'px" title="'.number_format($l_total[$hour]).'">';
				}

				$tr2 .= '</ul>';
			}

			$tr3 .= '<td'.($hour === $high_hour ? ' class="bold"' : '').'>'.$hour.'h';
		}

		return '<table class="act">'.$tr1.$tr2.$tr3.'</table>'."\n";
	}
}
