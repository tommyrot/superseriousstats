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
ini_set('display_errors', '0'); // To enable set to 1.
ini_set('error_reporting', 0);  // To enable for ALL errors set to -1.

/**
 * Class for creating historical stats.
 */
final class history
{
	/**
	 * Default settings for this script, which can be overridden in the configuration file.
	 */
	private $channel = '';
	private $database = 'sss.db3';
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
		'afternoon' => 'y',
		'evening' => 'r',
		'morning' => 'g',
		'night' => 'b');
	private $datetime = array();
	private $l_total = 0;
	private $output = '';

	public function __construct($cid, $year, $month)
	{
		$this->cid = $cid;
		$this->datetime['month'] = $month;
		$this->datetime['year'] = $year;

		/**
		 * Load settings from vars.php (contained in $settings[]).
		 */
		if ((include 'vars.php') === false) {
			$this->output(null, 'The configuration file could not be read.');
		}

		/**
		 * $cid is the channel ID used in vars.php and is passed along in the URL so that channel specific
		 * settings can be identified and loaded.
		 */
		if (empty($settings[$this->cid])) {
			$this->output(null, 'This channel has not been configured.');
		}

		foreach ($settings[$this->cid] as $key => $value) {
			$this->$key = $value;
		}

		date_default_timezone_set($this->timezone);

		/**
		 * Open the database connection.
		 */
		try {
			$sqlite3 = new SQLite3($this->database, SQLITE3_OPEN_READONLY);
			$sqlite3->busyTimeout(0);
		} catch (Exception $e) {
			$this->output(null, basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$e->getMessage());
		}

		$sqlite3->exec('PRAGMA temp_store = MEMORY');

		/**
		 * Make stats!
		 */
		echo $this->make_html($sqlite3);
		$sqlite3->close();
	}

	/**
	 * Calculate how many days ago a given $datetime is.
	 */
	private function daysago($datetime)
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
		} elseif ($daysago === (float) 1) {
			$daysago = 'Yesterday';
		} elseif ($daysago === (float) 0) {
			$daysago = 'Today';
		}

		return $daysago;
	}

	private function get_activity($sqlite3)
	{
		$query = $sqlite3->query('SELECT SUBSTR(date, 1, 4) AS year, CAST(SUBSTR(date, 6, 2) AS INTEGER) AS month, SUM(l_total) AS l_total FROM channel_activity GROUP BY year, month ORDER BY date ASC') or $this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$this->activity[$result['year']][$result['month']] = $result['l_total'];

			if (!isset($this->activity[$result['year']][0])) {
				$this->activity[$result['year']][0] = 0;
			}

			$this->activity[$result['year']][0] += $result['l_total'];
		}
	}

	/**
	 * Generate the HTML page.
	 */
	private function make_html($sqlite3)
	{
		if (($daycount = $sqlite3->querySingle('SELECT COUNT(*) FROM channel_activity')) === false) {
			$this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		/**
		 * All queries from this point forward require a non empty database.
		 */
		if ($daycount === 0) {
			$this->output('error', 'There is not enough data to create statistics, yet.');
		}

		if (($result = $sqlite3->querySingle('SELECT CAST(MIN(SUBSTR(date, 1, 4)) AS INTEGER) AS year_first, CAST(MAX(SUBSTR(date, 1, 4)) AS INTEGER) AS year_last FROM parse_history', true)) === false) {
			$this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		/**
		 * Date and time variables used throughout the script.
		 */
		$this->datetime['year_first'] = $result['year_first'];
		$this->datetime['year_last'] = $result['year_last'];

		if (!is_null($this->datetime['month'])) {
			$this->datetime['monthname'] = date('F', mktime(0, 0, 0, $this->datetime['month'], 1, $this->datetime['year']));
		}

		/**
		 * HTML Head.
		 */
		$this->output = '<!DOCTYPE html>'."\n\n"
			. '<html>'."\n\n"
			. '<head>'."\n"
			. '<meta charset="utf-8">'."\n"
			. '<title>'.htmlspecialchars($this->channel).', historically.</title>'."\n"
			. '<link rel="stylesheet" href="'.$this->stylesheet.'">'."\n"
			. '</head>'."\n\n"
			. '<body><div id="container">'."\n"
			. '<div class="info"><a href="'.htmlspecialchars($this->mainpage).'">'.htmlspecialchars($this->channel).'</a>, historically.<br><br>'
			. (is_null($this->datetime['year']) ? '<i>Select a year and/or month in the matrix below</i>.' : 'Displaying statistics for '.(!is_null($this->datetime['month']) ? $this->datetime['monthname'].' '.$this->datetime['year'] : 'the year '.$this->datetime['year']).'.').'</div>'."\n";

		/**
		 * Activity section.
		 */
		$this->get_activity($sqlite3);
		$this->output .= '<div class="section">Activity</div>'."\n";
		$this->output .= $this->make_index();

		/**
		 * Only call make_table_* functions for times in which there was activity.
		 */
		if (!is_null($this->datetime['year']) && array_key_exists($this->datetime['year'], $this->activity) && (is_null($this->datetime['month']) || array_key_exists($this->datetime['month'], $this->activity[$this->datetime['year']]))) {
			/**
			 * Set $l_total to the total number of lines in the specific scope.
			 */
			if (is_null($this->datetime['month'])) {
				$this->l_total = $this->activity[$this->datetime['year']][0];
				$type = 'year';
			} else {
				$this->l_total = $this->activity[$this->datetime['year']][$this->datetime['month']];
				$type = 'month';
			}

			$this->output .= $this->make_table_activity_distribution_hour($sqlite3, $type);
			$this->output .= $this->make_table_people($sqlite3, $type);
			$this->output .= $this->make_table_people_timeofday($sqlite3, $type);
		}

		/**
		 * HTML Foot.
		 */
		$this->output .= '<div class="info">Statistics created with <a href="http://sss.dutnie.nl">superseriousstats</a> on '.date('r').'.</div>'."\n";
		$this->output .= '</div></body>'."\n\n".'</html>'."\n";
		return $this->output;
	}

	private function make_index()
	{
		$tr0 = '<colgroup><col class="pos"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c">';
		$tr1 = '<tr><th colspan="13">History';
		$tr2 = '<tr><td class="pos"><td class="k">Jan<td class="k">Feb<td class="k">Mar<td class="k">Apr<td class="k">May<td class="k">Jun<td class="k">Jul<td class="k">Aug<td class="k">Sep<td class="k">Oct<td class="k">Nov<td class="k">Dec';
		$trx = '';

		for ($year = $this->datetime['year_first']; $year <= $this->datetime['year_last']; $year++) {
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

	private function make_table_activity_distribution_hour($sqlite3, $type)
	{
		if ($type === 'month') {
			if (($result = $sqlite3->querySingle('SELECT SUM(l_00) AS l_00, SUM(l_01) AS l_01, SUM(l_02) AS l_02, SUM(l_03) AS l_03, SUM(l_04) AS l_04, SUM(l_05) AS l_05, SUM(l_06) AS l_06, SUM(l_07) AS l_07, SUM(l_08) AS l_08, SUM(l_09) AS l_09, SUM(l_10) AS l_10, SUM(l_11) AS l_11, SUM(l_12) AS l_12, SUM(l_13) AS l_13, SUM(l_14) AS l_14, SUM(l_15) AS l_15, SUM(l_16) AS l_16, SUM(l_17) AS l_17, SUM(l_18) AS l_18, SUM(l_19) AS l_19, SUM(l_20) AS l_20, SUM(l_21) AS l_21, SUM(l_22) AS l_22, SUM(l_23) AS l_23 FROM channel_activity WHERE date LIKE \''.date('Y-m', mktime(0, 0, 0, $this->datetime['month'], 1, $this->datetime['year'])).'%\'', true)) === false) {
				$this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		} elseif ($type === 'year') {
			if (($result = $sqlite3->querySingle('SELECT SUM(l_00) AS l_00, SUM(l_01) AS l_01, SUM(l_02) AS l_02, SUM(l_03) AS l_03, SUM(l_04) AS l_04, SUM(l_05) AS l_05, SUM(l_06) AS l_06, SUM(l_07) AS l_07, SUM(l_08) AS l_08, SUM(l_09) AS l_09, SUM(l_10) AS l_10, SUM(l_11) AS l_11, SUM(l_12) AS l_12, SUM(l_13) AS l_13, SUM(l_14) AS l_14, SUM(l_15) AS l_15, SUM(l_16) AS l_16, SUM(l_17) AS l_17, SUM(l_18) AS l_18, SUM(l_19) AS l_19, SUM(l_20) AS l_20, SUM(l_21) AS l_21, SUM(l_22) AS l_22, SUM(l_23) AS l_23 FROM channel_activity WHERE date LIKE \''.$this->datetime['year'].'%\'', true)) === false) {
				$this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
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

			if ($value === 0) {
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

				if ($height !== (float) 0) {
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

			$tr3 .= '<td'.($key === $high_key ? ' class="bold"' : '').'>'.$hour.'h';
		}

		return '<table class="act">'.$tr1.$tr2.$tr3.'</table>'."\n";
	}

	private function make_table_people($sqlite3, $type)
	{
		/**
		 * Only create the table if there is activity from users other than bots and excluded users.
		 */
		if ($type === 'month') {
			if (($total = $sqlite3->querySingle('SELECT SUM(l_total) FROM ruid_activity_by_month JOIN uid_details ON ruid_activity_by_month.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date = \''.date('Y-m', mktime(0, 0, 0, $this->datetime['month'], 1, $this->datetime['year'])).'\'')) === false) {
				$this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		} elseif ($type === 'year') {
			if (($total = $sqlite3->querySingle('SELECT SUM(l_total) FROM ruid_activity_by_year JOIN uid_details ON ruid_activity_by_year.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date = \''.$this->datetime['year'].'\'')) === false) {
				$this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		}

		if (empty($total)) {
			return null;
		}

		if ($type === 'month') {
			$head = 'Most Talkative People &ndash; '.$this->datetime['monthname'].' '.$this->datetime['year'];
			$query = $sqlite3->query('SELECT csnick, ruid_activity_by_month.l_total AS l_total, ruid_activity_by_month.l_night AS l_night, ruid_activity_by_month.l_morning AS l_morning, ruid_activity_by_month.l_afternoon AS l_afternoon, ruid_activity_by_month.l_evening AS l_evening, quote, (SELECT MAX(lastseen) FROM uid_details WHERE ruid = ruid_activity_by_month.ruid) AS lastseen FROM ruid_activity_by_month JOIN uid_details ON ruid_activity_by_month.ruid = uid_details.uid JOIN ruid_lines ON ruid_activity_by_month.ruid = ruid_lines.ruid WHERE status NOT IN (3,4) AND date = \''.date('Y-m', mktime(0, 0, 0, $this->datetime['month'], 1, $this->datetime['year'])).'\' ORDER BY l_total DESC, ruid_activity_by_month.ruid ASC LIMIT '.$this->maxrows_people_month) or $this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		} elseif ($type === 'year') {
			$head = 'Most Talkative People &ndash; '.$this->datetime['year'];
			$query = $sqlite3->query('SELECT csnick, ruid_activity_by_year.l_total AS l_total, ruid_activity_by_year.l_night AS l_night, ruid_activity_by_year.l_morning AS l_morning, ruid_activity_by_year.l_afternoon AS l_afternoon, ruid_activity_by_year.l_evening AS l_evening, quote, (SELECT MAX(lastseen) FROM uid_details WHERE ruid = ruid_activity_by_year.ruid) AS lastseen FROM ruid_activity_by_year JOIN uid_details ON ruid_activity_by_year.ruid = uid_details.uid JOIN ruid_lines ON ruid_activity_by_year.ruid = ruid_lines.ruid WHERE status NOT IN (3,4) AND date = \''.$this->datetime['year'].'\' ORDER BY l_total DESC, ruid_activity_by_year.ruid ASC LIMIT '.$this->maxrows_people_year) or $this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$i = 0;
		$times = array('night', 'morning', 'afternoon', 'evening');
		$tr0 = '<colgroup><col class="c1"><col class="c2"><col class="pos"><col class="c3"><col class="c4"><col class="c5"><col class="c6">';
		$tr1 = '<tr><th colspan="7">'.$head;
		$tr2 = '<tr><td class="k1">Percentage<td class="k2">Lines<td class="pos"><td class="k3">User<td class="k4">When?<td class="k5">Last Seen<td class="k6">Quote';
		$trx = '';

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$i++;
			$width = 50;

			foreach ($times as $time) {
				if ($result['l_'.$time] !== 0) {
					$width_float[$time] = ($result['l_'.$time] / $result['l_total']) * 50;
					$width_int[$time] = floor($width_float[$time]);
					$width_remainders[$time] = $width_float[$time] - $width_int[$time];
					$width -= $width_int[$time];
				}
			}

			if ($width !== 0) {
				arsort($width_remainders);

				foreach ($width_remainders as $time => $remainder) {
					$width--;
					$width_int[$time]++;

					if ($width === 0) {
						break;
					}
				}
			}

			$when = '';

			foreach ($times as $time) {
				if (!empty($width_int[$time])) {
					$when .= '<li class="'.$this->color[$time].'" style="width:'.$width_int[$time].'px">';
				}
			}

			$trx .= '<tr><td class="v1">'.number_format(($result['l_total'] / $total) * 100, 2).'%<td class="v2">'.number_format($result['l_total']).'<td class="pos">'.$i.'<td class="v3">'.($this->userstats ? '<a href="user.php?cid='.urlencode($this->cid).'&amp;nick='.urlencode($result['csnick']).'">'.htmlspecialchars($result['csnick']).'</a>' : htmlspecialchars($result['csnick'])).'<td class="v4"><ul>'.$when.'</ul><td class="v5">'.$this->daysago($result['lastseen']).'<td class="v6">'.htmlspecialchars($result['quote']);
			unset($width_float, $width_int, $width_remainders);
		}

		return '<table class="ppl">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}

	private function make_table_people_timeofday($sqlite3, $type)
	{
		/**
		 * Only create the table if there is activity from users other than bots and excluded users.
		 */
		if ($type === 'month') {
			if (($total = $sqlite3->querySingle('SELECT SUM(l_total) FROM ruid_activity_by_month JOIN uid_details ON ruid_activity_by_month.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date = \''.date('Y-m', mktime(0, 0, 0, $this->datetime['month'], 1, $this->datetime['year'])).'\'')) === false) {
				$this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		} elseif ($type === 'year') {
			if (($total = $sqlite3->querySingle('SELECT SUM(l_total) FROM ruid_activity_by_year JOIN uid_details ON ruid_activity_by_year.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date = \''.$this->datetime['year'].'\'')) === false) {
				$this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		}

		if (empty($total)) {
			return null;
		}

		$high_value = 0;
		$times = array('night', 'morning', 'afternoon', 'evening');

		foreach ($times as $time) {
			if ($type === 'month') {
				$query = $sqlite3->query('SELECT csnick, l_'.$time.' FROM ruid_activity_by_month JOIN uid_details ON ruid_activity_by_month.ruid = uid_details.uid WHERE date = \''.date('Y-m', mktime(0, 0, 0, $this->datetime['month'], 1, $this->datetime['year'])).'\' AND status NOT IN (3,4) AND l_'.$time.' != 0 ORDER BY l_'.$time.' DESC, ruid_activity_by_month.ruid ASC LIMIT '.$this->maxrows_people_timeofday) or $this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			} elseif ($type === 'year') {
				$query = $sqlite3->query('SELECT csnick, l_'.$time.' FROM ruid_activity_by_year JOIN uid_details ON ruid_activity_by_year.ruid = uid_details.uid WHERE date = \''.$this->datetime['year'].'\' AND status NOT IN (3,4) AND l_'.$time.' != 0 ORDER BY l_'.$time.' DESC, ruid_activity_by_year.ruid ASC LIMIT '.$this->maxrows_people_timeofday) or $this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			$i = 0;

			while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
				$i++;
				${$time}[$i]['lines'] = $result['l_'.$time];
				${$time}[$i]['user'] = $result['csnick'];

				if (${$time}[$i]['lines'] > $high_value) {
					$high_value = ${$time}[$i]['lines'];
				}
			}
		}

		$tr0 = '<colgroup><col class="pos"><col class="c"><col class="c"><col class="c"><col class="c">';
		$tr1 = '<tr><th colspan="5">Most Talkative People by Time of Day';
		$tr2 = '<tr><td class="pos"><td class="k">Night<br>0h - 5h<td class="k">Morning<br>6h - 11h<td class="k">Afternoon<br>12h - 17h<td class="k">Evening<br>18h - 23h';
		$trx = '';

		for ($i = 1; $i <= $this->maxrows_people_timeofday; $i++) {
			if (!isset($night[$i]['lines']) && !isset($morning[$i]['lines']) && !isset($afternoon[$i]['lines']) && !isset($evening[$i]['lines'])) {
				break;
			} else {
				$trx .= '<tr><td class="pos">'.$i;

				foreach ($times as $time) {
					if (!isset(${$time}[$i]['lines'])) {
						$trx .= '<td class="v">';
					} else {
						$width = round((${$time}[$i]['lines'] / $high_value) * 190);

						if ($width !== (float) 0) {
							$trx .= '<td class="v">'.htmlspecialchars(${$time}[$i]['user']).' - '.number_format(${$time}[$i]['lines']).'<br><div class="'.$this->color[$time].'" style="width:'.$width.'px"></div>';
						} else {
							$trx .= '<td class="v">'.htmlspecialchars(${$time}[$i]['user']).' - '.number_format(${$time}[$i]['lines']);
						}
					}
				}
			}
		}

		return '<table class="ppl-tod">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}

	/**
	 * For compatibility reasons this function has the same name as the original version in the base class and
	 * accepts the same arguments. Its functionality is slightly different in that it exits on any type of message
	 * passed to it. SQLite3 result code 5 = SQLITE_BUSY, result code 6 = SQLITE_LOCKED.
	 */
	private function output($code, $msg)
	{
		if ($code === 5 || $code === 6) {
			$msg = 'Statistics are currently being updated, this may take a minute.';
		}

		exit('<!DOCTYPE html>'."\n\n".'<html><head><meta charset="utf-8"><title>seriously?</title><link rel="stylesheet" href="sss.css"></head><body><div id="container"><div class="error">'.htmlspecialchars($msg).'</div></div></body></html>'."\n");
	}
}

/**
 * The channel ID must be set, cannot be empty and cannot be of excessive length.
 */
if (empty($_GET['cid']) || !preg_match('/^\S{1,32}$/', $_GET['cid'])) {
	exit;
}

$cid = $_GET['cid'];

/**
 * Pass along a null value if the year and/or month are not set.
 */
if (isset($_GET['year']) && preg_match('/^[12][0-9]{3}$/', $_GET['year'])) {
	$year = (int) $_GET['year'];

	if (isset($_GET['month']) && preg_match('/^([1-9]|1[0-2])$/', $_GET['month'])) {
		$month = (int) $_GET['month'];
	} else {
		$month = null;
	}
} else {
	$month = null;
	$year = null;
}

/**
 * Make stats!
 */
$history = new history($cid, $year, $month);

?>
