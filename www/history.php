<?php

/**
 * Copyright (c) 2010-2013, Jos de Ruijter <jos@dutnie.nl>
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
 * Suppress any error output.
 */
ini_set('display_errors', '0');
ini_set('error_reporting', 0);

/**
 * Class for creating historical stats.
 */
final class history
{
	/**
	 * Default settings for this script, can be overridden in the vars.php file. Should be present in $settings_whitelist in order to get changed.
	 */
	private $channel = '';
	private $database = 'sss.db3';
	private $debug = false;
	private $mainpage = './';
	private $maxrows_people_month = 10;
	private $maxrows_people_timeofday = 10;
	private $maxrows_people_year = 10;
	private $stylesheet = 'sss.css';
	private $timezone = 'UTC';
	private $userstats = false;

	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $activity = array();
	private $cid = '';
	private $color = array(
		'night' => 'b',
		'morning' => 'g',
		'afternoon' => 'y',
		'evening' => 'r');
	private $l_total = 0;
	private $month = 0;
	private $monthname = '';
	private $settings_whitelist = array('channel', 'database', 'debug', 'mainpage', 'maxrows_people_month', 'maxrows_people_timeofday', 'maxrows_people_year', 'stylesheet', 'timezone', 'userstats');
	private $year = 0;
	private $year_firstlogparsed = 0;
	private $year_lastlogparsed = 0;

	public function __construct($cid, $year, $month)
	{
		$this->cid = $cid;
		$this->year = $year;
		$this->month = $month;

		/**
		 * Load settings from vars.php.
		 */
		if ((include 'vars.php') === false) {
			exit('Missing configuration.');
		}

		if (empty($settings[$this->cid])) {
			exit('Not configured.');
		}

		/**
		 * $cid is the channel ID used in vars.php and is passed along in the URL so that channel specific settings can be identified and loaded.
		 */
		foreach ($settings[$this->cid] as $key => $value) {
			if (in_array($key, $this->settings_whitelist)) {
				$this->$key = $value;
			}
		}

		date_default_timezone_set($this->timezone);
	}

	/**
	 * Calculate how many years, months or days ago a given $datetime is.
	 */
	private function datetime2daysago($datetime)
	{
		$daysago = round((strtotime('today') - strtotime(substr($datetime, 0, 10))) / 86400);

		if (($daysago / 365) >= 1) {
			$daysago = str_replace('.0', '', number_format($daysago / 365, 1));
			$daysago .= ' Year'.((float) $daysago > 1 ? 's' : '').' Ago';
		} elseif (($daysago / 30.42) >= 1) {
			$daysago = str_replace('.0', '', number_format($daysago / 30.42, 1));
			$daysago .= ' Month'.((float) $daysago > 1 ? 's' : '').' Ago';
		} elseif ($daysago > 1) {
			$daysago .= ' Days Ago';
		} elseif ($daysago == 1) {
			$daysago = 'Yesterday';
		} elseif ($daysago == 0) {
			$daysago = 'Today';
		}

		return $daysago;
	}

	private function get_activity($sqlite3)
	{
		/**
		 * Suffix a day to the date so strftime has a valid value to work with.
		 */
		$query = $sqlite3->query('SELECT SUBSTR(date, 1, 4) AS year, SUBSTR(date, 6, 2) AS month, SUM(l_total) AS l_total FROM q_activity_by_month GROUP BY year, month ORDER BY date ASC') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$result['month'] = (int) preg_replace('/^0/', '', $result['month']);
			$this->activity[$result['year']][$result['month']] = $result['l_total'];

			if (!isset($this->activity[$result['year']][0])) {
				$this->activity[$result['year']][0] = 0;
			}

			$this->activity[$result['year']][0] += $result['l_total'];
		}
	}

	public function make_html()
	{
		try {
			$sqlite3 = new SQLite3($this->database, SQLITE3_OPEN_READONLY);
		} catch (Exception $e) {
			$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$e->getMessage());
		}

		$sqlite3->exec('PRAGMA temp_store = MEMORY');

		if (($daycount = $sqlite3->querySingle('SELECT COUNT(*) FROM channel')) === false) {
			$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		/**
		 * Stop if the channel has no logged activity. Everything beyond this point expects a non empty database.
		 */
		if ($daycount == 0) {
			exit('No data.');
		}

		/**
		 * Date and time variables used throughout the script. These are based on the date of the last logfile parsed and used to define our scope.
		 */
		if (($result = $sqlite3->querySingle('SELECT MIN(SUBSTR(date, 1, 4)) AS year_firstlogparsed, MAX(SUBSTR(date, 1, 4)) AS year_lastlogparsed FROM parse_history', true)) === false) {
			$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$this->year_firstlogparsed = (int) $result['year_firstlogparsed'];
		$this->year_lastlogparsed = (int) $result['year_lastlogparsed'];

		if (!is_null($this->month)) {
			$this->monthname = date('F', mktime(0, 0, 0, $this->month, 1, $this->year));
		}

		/**
		 * HTML Head.
		 */
		$output = '<!DOCTYPE html>'."\n\n"
			. '<html>'."\n\n"
			. '<head>'."\n"
			. '<meta charset="utf-8">'."\n"
			. '<title>'.htmlspecialchars($this->channel).', historically.</title>'."\n"
			. '<link rel="stylesheet" href="'.$this->stylesheet.'">'."\n"
			. '</head>'."\n\n"
			. '<body><div id="container">'."\n"
			. '<div class="info"><a href="'.$this->mainpage.'">'.htmlspecialchars($this->channel).'</a>, historically.<br><br>'
			. (is_null($this->year) ? '<i>Select a year and/or month in the matrix below</i>.' : 'Displaying statistics for '.(!is_null($this->month) ? $this->monthname.' '.$this->year : 'the year '.$this->year).'.').'</div>'."\n";

		/**
		 * Activity section.
		 */
		$this->get_activity($sqlite3);
		$output .= '<div class="section">Activity</div>'."\n";
		$output .= $this->make_index();

		/**
		 * Only call make_table_* functions for times in which there was activity. This activity includes bots since we got it from the results used in
		 * make_index().
		 */
		if (!is_null($this->year) && array_key_exists($this->year, $this->activity) && (is_null($this->month) || array_key_exists($this->month, $this->activity[$this->year]))) {
			/**
			 * Set $l_total to the total number of lines in the specific scope. Following activity_* functions require this value.
			 */
			if (is_null($this->month)) {
				$this->l_total = $this->activity[$this->year][0];
			} else {
				$this->l_total = $this->activity[$this->year][$this->month];
			}

			$output .= $this->make_table_activity_distribution_hour($sqlite3);

			if (is_null($this->month)) {
				$output .= $this->make_table_people($sqlite3, 'year');
			} else {
				$output .= $this->make_table_people($sqlite3, 'month');
			}

			$output .= $this->make_table_people_timeofday($sqlite3);
		}

		/**
		 * HTML Foot.
		 */
		$output .= '<div class="info">Statistics created with <a href="http://sss.dutnie.nl">superseriousstats</a> on '.date('r').'.</div>'."\n";
		$output .= '</div></body>'."\n\n".'</html>'."\n";
		$sqlite3->close();
		return $output;
	}

	private function make_index()
	{
		$tr0 = '<colgroup><col class="pos"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c">';
		$tr1 = '<tr><th colspan="13">History';
		$tr2 = '<tr><td class="pos"><td class="k">Jan<td class="k">Feb<td class="k">Mar<td class="k">Apr<td class="k">May<td class="k">Jun<td class="k">Jul<td class="k">Aug<td class="k">Sep<td class="k">Oct<td class="k">Nov<td class="k">Dec';
		$trx = '';

		for ($year = $this->year_firstlogparsed; $year <= $this->year_lastlogparsed; $year++) {
			if (array_key_exists($year, $this->activity)) {
				$trx .= '<tr><td class="pos"><a href="history.php?cid='.urlencode($this->cid).'&amp;year='.$year.'">'.$year.'</a>';

				for ($month = 1; $month <= 12; $month++) {
					if (array_key_exists($month, $this->activity[$year])) {
						$trx .= '<td class="v"><a href="history.php?cid='.urlencode($this->cid).'&amp;year='.$year.'&amp;month='.$month.'">'.number_format($this->activity[$year][$month]).'</a>';
					} else {
						$trx .= '<td class="v"><span class="grey">n/a</span>';
					}
				}
			} else {
				$trx .= '<tr><td class="pos">'.$year.'<td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span>';
			}
		}

		return '<table class="index">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}

	private function make_table_activity_distribution_hour($sqlite3)
	{
		if (is_null($this->month)) {
			if (($result = $sqlite3->querySingle('SELECT SUM(l_00) AS l_00, SUM(l_01) AS l_01, SUM(l_02) AS l_02, SUM(l_03) AS l_03, SUM(l_04) AS l_04, SUM(l_05) AS l_05, SUM(l_06) AS l_06, SUM(l_07) AS l_07, SUM(l_08) AS l_08, SUM(l_09) AS l_09, SUM(l_10) AS l_10, SUM(l_11) AS l_11, SUM(l_12) AS l_12, SUM(l_13) AS l_13, SUM(l_14) AS l_14, SUM(l_15) AS l_15, SUM(l_16) AS l_16, SUM(l_17) AS l_17, SUM(l_18) AS l_18, SUM(l_19) AS l_19, SUM(l_20) AS l_20, SUM(l_21) AS l_21, SUM(l_22) AS l_22, SUM(l_23) AS l_23 FROM channel WHERE date LIKE \''.$this->year.'%\'', true)) === false) {
				$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		} else {
			if (($result = $sqlite3->querySingle('SELECT SUM(l_00) AS l_00, SUM(l_01) AS l_01, SUM(l_02) AS l_02, SUM(l_03) AS l_03, SUM(l_04) AS l_04, SUM(l_05) AS l_05, SUM(l_06) AS l_06, SUM(l_07) AS l_07, SUM(l_08) AS l_08, SUM(l_09) AS l_09, SUM(l_10) AS l_10, SUM(l_11) AS l_11, SUM(l_12) AS l_12, SUM(l_13) AS l_13, SUM(l_14) AS l_14, SUM(l_15) AS l_15, SUM(l_16) AS l_16, SUM(l_17) AS l_17, SUM(l_18) AS l_18, SUM(l_19) AS l_19, SUM(l_20) AS l_20, SUM(l_21) AS l_21, SUM(l_22) AS l_22, SUM(l_23) AS l_23 FROM channel WHERE date LIKE \''.date('Y-m', mktime(0, 0, 0, $this->month, 1, $this->year)).'%\'', true)) === false) {
				$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		}

		$high_key = '';
		$high_value = 0;

		foreach ($result as $key => $value) {
			if ($value > $high_value) {
				$high_key = $key;
				$high_value = $value;
			}
		}

		$tr1 = '<tr><th colspan="24">Activity Distribution by Hour';
		$tr2 = '<tr class="bars">';
		$tr3 = '<tr class="sub">';

		foreach ($result as $key => $value) {
			$hour = (int) preg_replace('/^l_0?/', '', $key);

			if ($value == 0) {
				$tr2 .= '<td><span class="grey">n/a</span>';
			} else {
				$percentage = ($value / $this->l_total) * 100;

				if ($percentage >= 9.95) {
					$percentage = round($percentage).'%';
				} else {
					$percentage = number_format($percentage, 1).'%';
				}

				$height = round(($value / $high_value) * 100);
				$tr2 .= '<td><ul><li class="num" style="height:'.($height + 14).'px">'.$percentage;

				if ($height != 0) {
					if ($hour >= 0 && $hour <= 5) {
						$time = 'night';
					} elseif ($hour >= 6 && $hour <= 11) {
						$time = 'morning';
					} elseif ($hour >= 12 && $hour <= 17) {
						$time = 'afternoon';
					} elseif ($hour >= 18 && $hour <= 23) {
						$time = 'evening';
					}

					$tr2 .= '<li class="'.$this->color[$time].'" style="height:'.$height.'px" title="'.number_format($value).'">';
				}

				$tr2 .= '</ul>';
			}

			$tr3 .= '<td'.($high_key == $key ? ' class="bold"' : '').'>'.$hour.'h';
		}

		return '<table class="act">'.$tr1.$tr2.$tr3.'</table>'."\n";
	}

	private function make_table_people($sqlite3, $type)
	{
		/**
		 * Check if there is user activity (bots excluded). If there is none we can skip making the table.
		 */
		if ($type == 'year') {
			if (($total = $sqlite3->querySingle('SELECT SUM(l_total) FROM q_activity_by_year JOIN user_status ON q_activity_by_year.ruid = user_status.uid WHERE status != 3 AND date = \''.$this->year.'\'')) === false) {
				$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		} elseif ($type == 'month') {
			if (($total = $sqlite3->querySingle('SELECT SUM(l_total) FROM q_activity_by_month JOIN user_status ON q_activity_by_month.ruid = user_status.uid WHERE status != 3 AND date = \''.date('Y-m', mktime(0, 0, 0, $this->month, 1, $this->year)).'\'')) === false) {
				$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		}

		if (is_null($total)) {
			return null;
		}

		/**
		 * The queries below will always yield a proper workable result set.
		 */
		if ($type == 'year') {
			$head = 'Most Talkative People &ndash; '.$this->year;
			$query = $sqlite3->query('SELECT csnick, SUM(q_activity_by_year.l_total) AS l_total, SUM(q_activity_by_year.l_night) AS l_night, SUM(q_activity_by_year.l_morning) AS l_morning, SUM(q_activity_by_year.l_afternoon) AS l_afternoon, SUM(q_activity_by_year.l_evening) AS l_evening, quote, (SELECT MAX(lastseen) FROM user_details JOIN user_status ON user_details.uid = user_status.uid WHERE user_status.ruid = q_lines.ruid) AS lastseen FROM q_lines JOIN q_activity_by_year ON q_lines.ruid = q_activity_by_year.ruid JOIN user_status ON q_lines.ruid = user_status.uid JOIN user_details ON q_lines.ruid = user_details.uid WHERE status != 3 AND date = \''.$this->year.'\' GROUP BY q_lines.ruid ORDER BY l_total DESC, csnick ASC LIMIT '.$this->maxrows_people_year) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		} elseif ($type == 'month') {
			$head = 'Most Talkative People &ndash; '.$this->monthname.' '.$this->year;
			$query = $sqlite3->query('SELECT csnick, SUM(q_activity_by_month.l_total) AS l_total, SUM(q_activity_by_month.l_night) AS l_night, SUM(q_activity_by_month.l_morning) AS l_morning, SUM(q_activity_by_month.l_afternoon) AS l_afternoon, SUM(q_activity_by_month.l_evening) AS l_evening, quote, (SELECT MAX(lastseen) FROM user_details JOIN user_status ON user_details.uid = user_status.uid WHERE user_status.ruid = q_lines.ruid) AS lastseen FROM q_lines JOIN q_activity_by_month ON q_lines.ruid = q_activity_by_month.ruid JOIN user_status ON q_lines.ruid = user_status.uid JOIN user_details ON q_lines.ruid = user_details.uid WHERE status != 3 AND date = \''.date('Y-m', mktime(0, 0, 0, $this->month, 1, $this->year)).'\' GROUP BY q_lines.ruid ORDER BY l_total DESC, csnick ASC LIMIT '.$this->maxrows_people_month) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$tr0 = '<colgroup><col class="c1"><col class="c2"><col class="pos"><col class="c3"><col class="c4"><col class="c5"><col class="c6">';
		$tr1 = '<tr><th colspan="7">'.$head;
		$tr2 = '<tr><td class="k1">Percentage<td class="k2">Lines<td class="pos"><td class="k3">User<td class="k4">When?<td class="k5">Last Seen<td class="k6">Quote';
		$trx = '';
		$i = 0;

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$i++;
			$width = 50;
			unset($width_float, $width_int, $width_remainders);
			$times = array('night', 'morning', 'afternoon', 'evening');

			foreach ($times as $time) {
				if ($result['l_'.$time] != 0) {
					$width_float[$time] = ($result['l_'.$time] / $result['l_total']) * 50;
					$width_int[$time] = floor($width_float[$time]);
					$width -= $width_int[$time];
					$width_remainders[$time] = $width_float[$time] - $width_int[$time];
				}
			}

			if (!empty($width_remainders) && $width != 0) {
				arsort($width_remainders);

				foreach ($width_remainders as $time => $remainder) {
					if ($width == 0) {
						break;
					} else {
						$width_int[$time]++;
						$width--;
					}
				}
			}

			$when = '';

			foreach ($times as $time) {
				if (!empty($width_int[$time])) {
					$when .= '<li class="'.$this->color[$time].'" style="width:'.$width_int[$time].'px">';
				}
			}

			$trx .= '<tr><td class="v1">'.number_format(($result['l_total'] / $total) * 100, 2).'%<td class="v2">'.number_format($result['l_total']).'<td class="pos">'.$i.'<td class="v3">'.($this->userstats ? '<a href="user.php?cid='.urlencode($this->cid).'&amp;nick='.urlencode($result['csnick']).'">'.htmlspecialchars($result['csnick']).'</a>' : htmlspecialchars($result['csnick'])).'<td class="v4"><ul>'.$when.'</ul><td class="v5">'.$this->datetime2daysago($result['lastseen']).'<td class="v6">'.htmlspecialchars($result['quote']);
		}

		return '<table class="ppl">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}

	private function make_table_people_timeofday($sqlite3)
	{
		/**
		 * Check if there is user activity (bots excluded). If there is none we can skip making the table.
		 */
		if (is_null($this->month)) {
			if (($total = $sqlite3->querySingle('SELECT SUM(l_total) FROM q_activity_by_year JOIN user_status ON q_activity_by_year.ruid = user_status.uid WHERE status != 3 AND date = \''.$this->year.'\'')) === false) {
				$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		} else {
			if (($total = $sqlite3->querySingle('SELECT SUM(l_total) FROM q_activity_by_month JOIN user_status ON q_activity_by_month.ruid = user_status.uid WHERE status != 3 AND date = \''.date('Y-m', mktime(0, 0, 0, $this->month, 1, $this->year)).'\'')) === false) {
				$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		}

		if (is_null($total)) {
			return null;
		}

		$high_value = 0;
		$times = array('night', 'morning', 'afternoon', 'evening');

		foreach ($times as $time) {
			if (is_null($this->month)) {
				$query = $sqlite3->query('SELECT csnick, l_'.$time.' FROM q_activity_by_year JOIN user_details ON q_activity_by_year.ruid = user_details.uid JOIN user_status ON q_activity_by_year.ruid = user_status.uid WHERE date = \''.$this->year.'\' AND status != 3 AND l_'.$time.' != 0 ORDER BY l_'.$time.' DESC, csnick ASC LIMIT '.$this->maxrows_people_timeofday) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			} else {
				$query = $sqlite3->query('SELECT csnick, l_'.$time.' FROM q_activity_by_month JOIN user_details ON q_activity_by_month.ruid = user_details.uid JOIN user_status ON q_activity_by_month.ruid = user_status.uid WHERE date = \''.date('Y-m', mktime(0, 0, 0, $this->month, 1, $this->year)).'\' AND status != 3 AND l_'.$time.' != 0 ORDER BY l_'.$time.' DESC, csnick ASC LIMIT '.$this->maxrows_people_timeofday) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			$i = 0;

			while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
				$i++;
				${$time}[$i]['user'] = $result['csnick'];
				${$time}[$i]['lines'] = $result['l_'.$time];

				if (${$time}[$i]['lines'] > $high_value) {
					$high_value = ${$time}[$i]['lines'];
				}
			}
		}

		$tr0 = '<colgroup><col class="pos"><col class="c"><col class="c"><col class="c"><col class="c">';
		$tr1 = '<tr><th colspan="5">Most Talkative People by Time of Day';
		$tr2 = '<tr><td class="pos"><td class="k">Night<br>0h - 5h<td class="k">Morning<br>6h - 11h<td class="k">Afternoon<br>12h - 17h<td class="k">Evening<br>18h - 23h';
		$tr3 = '';

		for ($i = 1; $i <= $this->maxrows_people_timeofday; $i++) {
			if (!isset($night[$i]['lines']) && !isset($morning[$i]['lines']) && !isset($afternoon[$i]['lines']) && !isset($evening[$i]['lines'])) {
				break;
			} else {
				$tr3 .= '<tr><td class="pos">'.$i;

				foreach ($times as $time) {
					if (!isset(${$time}[$i]['lines'])) {
						$tr3 .= '<td class="v">';
					} else {
						$width = round((${$time}[$i]['lines'] / $high_value) * 190);

						if ($width != 0) {
							$tr3 .= '<td class="v">'.htmlspecialchars(${$time}[$i]['user']).' - '.number_format(${$time}[$i]['lines']).'<br><div class="'.$this->color[$time].'" style="width:'.$width.'px"></div>';
						} else {
							$tr3 .= '<td class="v">'.htmlspecialchars(${$time}[$i]['user']).' - '.number_format(${$time}[$i]['lines']);
						}
					}
				}
			}
		}

		return '<table class="ppl-tod">'.$tr0.$tr1.$tr2.$tr3.'</table>'."\n";
	}

	/**
	 * For compatibility reasons this function has the same name as the original version in the base class and accepts the same arguments. Its functionality
	 * is slightly different in that it exits on any type of message passed to it.
	 */
	private function output($type, $msg)
	{
		/**
		 * If $debug is set to true we exit with the given message, otherwise exit silently.
		 */
		if ($this->debug) {
			exit($msg);
		} else {
			exit;
		}
	}
}

/**
 * The channel ID cannot be empty or of excessive length.
 */
if (!isset($_GET['cid']) || !preg_match('/^\S{1,50}$/', $_GET['cid'])) {
	exit;
}

$cid = $_GET['cid'];

/**
 * If the year and/or month are not set we pass along a null value.
 */
if (isset($_GET['year']) && preg_match('/^[12][0-9]{3}$/', $_GET['year'])) {
	$year = (int) $_GET['year'];

	if (isset($_GET['month']) && preg_match('/^([1-9]|1[0-2])$/', $_GET['month'])) {
		$month = (int) $_GET['month'];
	} else {
		$month = null;
	}
} else {
	$year = null;
	$month = null;
}

/**
 * Create the statspage!
 */
$history = new history($cid, $year, $month);
echo $history->make_html();

?>
