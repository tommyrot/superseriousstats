<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2021, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Trait with code common between html.php, user.php and history.php.
 */
trait common_html_user_history
{
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

		$tr1 = '<tr><th colspan="24">Activity Distribution by Hour';
		$tr2 = '<tr class="bars">';
		$tr3 = '<tr class="sub">';

		/**
		 * Assemble each column.
		 */
		for ($hour = 0; $hour <= 23; ++$hour) {
			if ($lines[$hour] === 0) {
				$tr2 .= '<td><span class="grey">n/a</span>';
			} else {
				$percentage = ($lines[$hour] / $result['l_total']) * 100;
				$percentage = ($percentage >= 9.95 ? round($percentage) : number_format($percentage, 1)).'%';
				$height = (int) round(($lines[$hour] / $high_lines) * 100);
				$tr2 .= '<td><ul><li class="num" style="height:'.($height + 14).'px">'.($percentage === '0.0%' ? '<span class="grey">'.$percentage.'</span>' : $percentage);

				if ($height !== 0) {
					$tr2 .= '<li class="'.($hour <= 5 ? 'n' : ($hour <= 11 ? 'm' : ($hour <= 17 ? 'a' : 'e'))).'" style="height:'.$height.'px" title="'.number_format($lines[$hour]).'">';
				}

				$tr2 .= '</ul>';
			}

			$tr3 .= '<td'.($hour === $high_hour ? ' class="bold"' : '').'>'.$hour.'h';
		}

		return '<table class="act">'.$tr1.$tr2.$tr3.'</table>'."\n";
	}

	private function htmlify(string $string): string
	{
		return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}
}
