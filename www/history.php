<?php

/**
 * Copyright (c) 2010-2012, Jos de Ruijter <jos@dutnie.nl>
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
 * Class for creating historical stats.
 */
final class history
{
	/**
	 * Default settings for this script, can be overridden in the vars.php file.
	 */
	private $channel = '';
	private $db_host = '127.0.0.1';
	private $db_pass = '';
	private $db_port = 3306;
	private $db_name = 'sss';
	private $db_user = '';
	private $debug = false;
	private $mainpage = './';
	private $maxrows_people_month = 30;
	private $maxrows_people_timeofday = 10;
	private $maxrows_people_year = 30;
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
	private $mysqli;
	private $year = 0;
	private $year_firstlogparsed = 0;
	private $year_lastlogparsed = 0;

	public function __construct($cid, $year, $month)
	{
		$this->cid = $cid;
		$this->year = $year;
		$this->month = $month;

		/**
		 * Open the vars.php file and load settings from it. First the global settings then the channel specific ones.
		 */
		if ((@include 'vars.php') === false) {
			exit('Missing configuration.');
		}

		if (empty($settings['__global']) || empty($settings[$this->cid])) {
			exit('Not configured.');
		}

		foreach ($settings['__global'] as $key => $value) {
			$this->$key = $value;
		}

		/**
		 * $cid is the channel ID used in vars.php and is passed along in the URL so that channel specific settings can be identified and loaded.
		 */
		foreach ($settings[$this->cid] as $key => $value) {
			$this->$key = $value;
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

	private function get_activity() {
		$query = @mysqli_query($this->mysqli, 'select substring(`date`, 1, 4) as `year`, substring(`date`, 6, 2) as `month`, sum(`l_total`) as `l_total` from `q_activity_by_month` group by `year`, `month` order by `date` asc') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));

		while ($result = mysqli_fetch_object($query)) {
			$result->month = preg_replace('/^0/', '', $result->month);
			$this->activity[(int) $result->year][(int) $result->month] = (int) $result->l_total;

			if (!isset($this->activity[(int) $result->year][0])) {
				$this->activity[(int) $result->year][0] = 0;
			}

			$this->activity[(int) $result->year][0] += (int) $result->l_total;
		}
	}

	public function make_html()
	{
		$this->mysqli = @mysqli_connect($this->db_host, $this->db_user, $this->db_pass, $this->db_name, $this->db_port) or $this->output('critical', 'mysqli: '.mysqli_connect_error());
		mysqli_set_charset($this->mysqli, 'utf8') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$query = @mysqli_query($this->mysqli, 'select sum(`l_total`) as `l_total` from `channel`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (!empty($rows)) {
			$result = mysqli_fetch_object($query);
		}

		if (empty($result->l_total)) {
			exit('No data.');
		}

		/**
		 * Date and time variables used throughout the script. These are based on the date of the last logfile parsed and used to define our scope.
		 */
		$query = @mysqli_query($this->mysqli, 'select count(*) as `days`, min(year(`date`)) as `year_firstlogparsed`, max(year(`date`)) as `year_lastlogparsed` from `parse_history`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$result = mysqli_fetch_object($query);
		$this->year_firstlogparsed = (int) $result->year_firstlogparsed;
		$this->year_lastlogparsed = (int) $result->year_lastlogparsed;

		if (!is_null($this->month)) {
			$this->monthname = date('F', mktime(0, 0, 0, $this->month, 1, $this->year));
		}

		/**
		 * HTML Head.
		 */
		$output = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">'."\n\n"
			. '<head>'."\n".'<title>'.htmlspecialchars($this->channel).', historically.</title>'."\n"
			. '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'."\n"
			. '<meta http-equiv="Content-Style-Type" content="text/css">'."\n"
			. '<link rel="stylesheet" type="text/css" href="'.$this->stylesheet.'">'."\n"
			. '</head>'."\n\n".'<body>'."\n"
			. '<div class="box">'."\n"
			. "\n".'<div class="info"><a href="'.$this->mainpage.'">'.htmlspecialchars($this->channel).'</a>, historically.<br><br>'.(is_null($this->year) ? '<i>Select a year and/or month in the matrix below</i>.' : 'Displaying statistics for '.(!is_null($this->month) ? $this->monthname.' '.$this->year : 'the year '.$this->year).'.').'</div>'."\n";

		/**
		 * Activity section.
		 */
		$this->get_activity();
		$output .= "\n".'<div class="head">Activity</div>'."\n";
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

			$output .= $this->make_table_activity_distribution_hour();

			if (is_null($this->month)) {
				$output .= $this->make_table_people('year');
			} else {
				$output .= $this->make_table_people('month');
			}

			$output .= $this->make_table_people_timeofday();
		}

		/**
		 * HTML Foot.
		 */
		$output .= "\n".'<div class="info">Statistics created with <a href="https://github.com/tommyrot/superseriousstats">superseriousstats</a> on '.date('r').'.</div>'."\n";
		$output .= "\n".'</div>'."\n".'</body>'."\n\n".'</html>'."\n";
		@mysqli_close($this->mysqli);
		return $output;
	}

	private function make_index() {
		$tr0 = '<col class="pos"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c">';
		$tr1 = '<tr><th colspan="13">History</th></tr>';
		$tr2 = '<tr><td class="pos"></td><td class="k">Jan</td><td class="k">Feb</td><td class="k">Mar</td><td class="k">Apr</td><td class="k">May</td><td class="k">Jun</td><td class="k">Jul</td><td class="k">Aug</td><td class="k">Sep</td><td class="k">Oct</td><td class="k">Nov</td><td class="k">Dec</td></tr>';
		$trx = '';

		for ($year = $this->year_firstlogparsed; $year <= $this->year_lastlogparsed; $year++) {
			if (array_key_exists($year, $this->activity)) {
				$trx .= '<tr><td class="pos"><a href="history.php?cid='.urlencode($this->cid).'&amp;year='.$year.'">'.$year.'</a></td>';

				for ($month = 1; $month <= 12; $month++) {
					if (array_key_exists($month, $this->activity[$year])) {
						$trx .= '<td class="v"><a href="history.php?cid='.urlencode($this->cid).'&amp;year='.$year.'&amp;month='.$month.'">'.number_format($this->activity[$year][$month]).'</a></td>';
					} else {
						$trx .= '<td class="v"><span class="grey">n/a</span></td>';
					}
				}

				$trx .= '</tr>';
			} else {
				$trx .= '<tr><td class="pos">'.$year.'</td><td class="v"><span class="grey">n/a</span></td><td class="v"><span class="grey">n/a</span></td><td class="v"><span class="grey">n/a</span></td><td class="v"><span class="grey">n/a</span></td><td class="v"><span class="grey">n/a</span></td><td class="v"><span class="grey">n/a</span></td><td class="v"><span class="grey">n/a</span></td><td class="v"><span class="grey">n/a</span></td><td class="v"><span class="grey">n/a</span></td><td class="v"><span class="grey">n/a</span></td><td class="v"><span class="grey">n/a</span></td><td class="v"><span class="grey">n/a</span></td></tr>';
			}
		}

		return '<table class="index">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}

	private function make_table_activity_distribution_hour()
	{
		if (is_null($this->month)) {
			$query = @mysqli_query($this->mysqli, 'select sum(`l_00`) as `l_00`, sum(`l_01`) as `l_01`, sum(`l_02`) as `l_02`, sum(`l_03`) as `l_03`, sum(`l_04`) as `l_04`, sum(`l_05`) as `l_05`, sum(`l_06`) as `l_06`, sum(`l_07`) as `l_07`, sum(`l_08`) as `l_08`, sum(`l_09`) as `l_09`, sum(`l_10`) as `l_10`, sum(`l_11`) as `l_11`, sum(`l_12`) as `l_12`, sum(`l_13`) as `l_13`, sum(`l_14`) as `l_14`, sum(`l_15`) as `l_15`, sum(`l_16`) as `l_16`, sum(`l_17`) as `l_17`, sum(`l_18`) as `l_18`, sum(`l_19`) as `l_19`, sum(`l_20`) as `l_20`, sum(`l_21`) as `l_21`, sum(`l_22`) as `l_22`, sum(`l_23`) as `l_23` from `channel` where year(`date`) = '.$this->year) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		} else {
			$query = @mysqli_query($this->mysqli, 'select sum(`l_00`) as `l_00`, sum(`l_01`) as `l_01`, sum(`l_02`) as `l_02`, sum(`l_03`) as `l_03`, sum(`l_04`) as `l_04`, sum(`l_05`) as `l_05`, sum(`l_06`) as `l_06`, sum(`l_07`) as `l_07`, sum(`l_08`) as `l_08`, sum(`l_09`) as `l_09`, sum(`l_10`) as `l_10`, sum(`l_11`) as `l_11`, sum(`l_12`) as `l_12`, sum(`l_13`) as `l_13`, sum(`l_14`) as `l_14`, sum(`l_15`) as `l_15`, sum(`l_16`) as `l_16`, sum(`l_17`) as `l_17`, sum(`l_18`) as `l_18`, sum(`l_19`) as `l_19`, sum(`l_20`) as `l_20`, sum(`l_21`) as `l_21`, sum(`l_22`) as `l_22`, sum(`l_23`) as `l_23` from `channel` where date_format(`date`, \'%Y-%m\') = \''.date('Y-m', mktime(0, 0, 0, $this->month, 1, $this->year)).'\'') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		}

		$result = mysqli_fetch_object($query);
		$high_key = '';
		$high_value = 0;

		foreach ($result as $key => $value) {
			if ((int) $value > $high_value) {
				$high_key = $key;
				$high_value = (int) $value;
			}
		}

		$tr1 = '<tr><th colspan="24">Activity Distribution by Hour</th></tr>';
		$tr2 = '<tr class="bars">';
		$tr3 = '<tr class="sub">';

		foreach ($result as $key => $value) {
			$hour = (int) preg_replace('/^l_0?/', '', $key);

			if ((int) $value == 0) {
				$tr2 .= '<td><span class="grey">n/a</span></td>';
			} else {
				$percentage = ((int) $value / $this->l_total) * 100;

				if ($percentage >= 9.95) {
					$percentage = round($percentage).'%';
				} else {
					$percentage = number_format($percentage, 1).'%';
				}

				$height = round(((int) $value / $high_value) * 100);
				$tr2 .= '<td><ul><li class="num" style="height:'.($height + 14).'px">'.$percentage.'</li>';

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

					$tr2 .= '<li class="'.$this->color[$time].'" style="height:'.$height.'px" title="'.number_format((int) $value).'"></li>';
				}

				$tr2 .= '</ul></td>';
			}

			$tr3 .= '<td'.($high_key == $key ? ' class="bold"' : '').'>'.$hour.'h</td>';
		}

		$tr2 .= '</tr>';
		$tr3 .= '</tr>';
		return '<table class="act">'.$tr1.$tr2.$tr3.'</table>'."\n";
	}

	private function make_table_people($type)
	{
		/**
		 * Check if there is user activity (bots excluded). If there is none we can skip making the table.
		 */
		if ($type == 'year') {
			$query = @mysqli_query($this->mysqli, 'select sum(`l_total`) as `l_total` from `q_activity_by_year` join `user_status` on `q_activity_by_year`.`ruid` = `user_status`.`uid` where `status` != 3 and `date` = '.$this->year) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		} elseif ($type == 'month') {
			$query = @mysqli_query($this->mysqli, 'select sum(`l_total`) as `l_total` from `q_activity_by_month` join `user_status` on `q_activity_by_month`.`ruid` = `user_status`.`uid` where `status` != 3 and `date` = \''.date('Y-m', mktime(0, 0, 0, $this->month, 1, $this->year)).'\'') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		}

		$rows = mysqli_num_rows($query);

		if (!empty($rows)) {
			$result = mysqli_fetch_object($query);
		}

		if (empty($result->l_total)) {
			return null;
		}

		$total = (int) $result->l_total;

		/**
		 * The queries below will always yield a proper workable result set.
		 */
		if ($type == 'year') {
			$head = 'Most Talkative People &ndash; '.$this->year;
			$query = @mysqli_query($this->mysqli, 'select `csnick`, sum(`q_activity_by_year`.`l_total`) as `l_total`, sum(`q_activity_by_year`.`l_night`) as `l_night`, sum(`q_activity_by_year`.`l_morning`) as `l_morning`, sum(`q_activity_by_year`.`l_afternoon`) as `l_afternoon`, sum(`q_activity_by_year`.`l_evening`) as `l_evening`, `quote`, (select max(`lastseen`) from `user_details` join `user_status` on `user_details`.`uid` = `user_status`.`uid` where `user_status`.`ruid` = `q_lines`.`ruid`) as `lastseen` from `q_lines` join `q_activity_by_year` on `q_lines`.`ruid` = `q_activity_by_year`.`ruid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` where `status` != 3 and `date` = '.$this->year.' group by `q_lines`.`ruid` order by `l_total` desc, `csnick` asc limit '.$this->maxrows_people_year) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		} elseif ($type == 'month') {
			$head = 'Most Talkative People &ndash; '.$this->monthname.' '.$this->year;
			$query = @mysqli_query($this->mysqli, 'select `csnick`, sum(`q_activity_by_month`.`l_total`) as `l_total`, sum(`q_activity_by_month`.`l_night`) as `l_night`, sum(`q_activity_by_month`.`l_morning`) as `l_morning`, sum(`q_activity_by_month`.`l_afternoon`) as `l_afternoon`, sum(`q_activity_by_month`.`l_evening`) as `l_evening`, `quote`, (select max(`lastseen`) from `user_details` join `user_status` on `user_details`.`uid` = `user_status`.`uid` where `user_status`.`ruid` = `q_lines`.`ruid`) as `lastseen` from `q_lines` join `q_activity_by_month` on `q_lines`.`ruid` = `q_activity_by_month`.`ruid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` where `status` != 3 and `date` = \''.date('Y-m', mktime(0, 0, 0, $this->month, 1, $this->year)).'\' group by `q_lines`.`ruid` order by `l_total` desc, `csnick` asc limit '.$this->maxrows_people_month) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		}

		$tr0 = '<col class="c1"><col class="c2"><col class="pos"><col class="c3"><col class="c4"><col class="c5"><col class="c6">';
		$tr1 = '<tr><th colspan="7">'.$head.'</th></tr>';
		$tr2 = '<tr><td class="k1">Percentage</td><td class="k2">Lines</td><td class="pos"></td><td class="k3">User</td><td class="k4">When?</td><td class="k5">Last Seen</td><td class="k6">Quote</td></tr>';
		$trx = '';
		$i = 0;

		while ($result = mysqli_fetch_object($query)) {
			$i++;
			$lastseen = $this->datetime2daysago($result->lastseen);
			$when = '';
			$width = 50;
			unset($width_float, $width_int, $width_remainders);
			$times = array('night', 'morning', 'afternoon', 'evening');

			foreach ($times as $time) {
				if ((int) $result->{'l_'.$time} != 0) {
					$width_float[$time] = ((int) $result->{'l_'.$time} / (int) $result->l_total) * 50;
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

			foreach ($times as $time) {
				if (!empty($width_int[$time])) {
					$when .= '<li class="'.$this->color[$time].'" style="width:'.$width_int[$time].'px"></li>';
				}
			}

			$trx .= '<tr><td class="v1">'.number_format(((int) $result->l_total / $total) * 100, 2).'%</td><td class="v2">'.number_format((int) $result->l_total).'</td><td class="pos">'.$i.'</td><td class="v3">'.($this->userstats ? '<a href="user.php?cid='.urlencode($this->cid).'&amp;nick='.urlencode($result->csnick).'">'.htmlspecialchars($result->csnick).'</a>' : htmlspecialchars($result->csnick)).'</td><td class="v4"><ul>'.$when.'</ul></td><td class="v5">'.$lastseen.'</td><td class="v6">'.htmlspecialchars($result->quote).'</td></tr>';
		}

		return '<table class="ppl">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}

	private function make_table_people_timeofday()
	{
		/**
		 * Check if there is user activity (bots excluded). If there is none we can skip making the table.
		 */
		if (is_null($this->month)) {
			$query = @mysqli_query($this->mysqli, 'select sum(`l_total`) as `l_total` from `q_activity_by_year` join `user_status` on `q_activity_by_year`.`ruid` = `user_status`.`uid` where `status` != 3 and `date` = '.$this->year) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		} else {
			$query = @mysqli_query($this->mysqli, 'select sum(`l_total`) as `l_total` from `q_activity_by_month` join `user_status` on `q_activity_by_month`.`ruid` = `user_status`.`uid` where `status` != 3 and `date` = \''.date('Y-m', mktime(0, 0, 0, $this->month, 1, $this->year)).'\'') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		}

		$rows = mysqli_num_rows($query);

		if (!empty($rows)) {
			$result = mysqli_fetch_object($query);
		}

		if (empty($result->l_total)) {
			return null;
		}

		$high_value = 0;
		$times = array('night', 'morning', 'afternoon', 'evening');

		foreach ($times as $time) {
			if (is_null($this->month)) {
				$query = @mysqli_query($this->mysqli, 'select `csnick`, `l_'.$time.'` from `q_activity_by_year` join `user_details` on `q_activity_by_year`.`ruid` = `user_details`.`uid` join `user_status` on `q_activity_by_year`.`ruid` = `user_status`.`uid` where `date` = '.$this->year.' and `status` != 3 and `l_'.$time.'` != 0 order by `l_'.$time.'` desc, `csnick` asc limit '.$this->maxrows_people_timeofday) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			} else {
				$query = @mysqli_query($this->mysqli, 'select `csnick`, `l_'.$time.'` from `q_activity_by_month` join `user_details` on `q_activity_by_month`.`ruid` = `user_details`.`uid` join `user_status` on `q_activity_by_month`.`ruid` = `user_status`.`uid` where `date` = \''.date('Y-m', mktime(0, 0, 0, $this->month, 1, $this->year)).'\' and `status` != 3 and `l_'.$time.'` != 0 order by `l_'.$time.'` desc, `csnick` asc limit '.$this->maxrows_people_timeofday) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			}

			$i = 0;

			while ($result = mysqli_fetch_object($query)) {
				$i++;
				${$time}[$i]['user'] = $result->csnick;
				${$time}[$i]['lines'] = (int) $result->{'l_'.$time};

				if (${$time}[$i]['lines'] > $high_value) {
					$high_value = ${$time}[$i]['lines'];
				}
			}
		}

		$tr0 = '<col class="pos"><col class="c"><col class="c"><col class="c"><col class="c">';
		$tr1 = '<tr><th colspan="5">Most Talkative People by Time of Day</th></tr>';
		$tr2 = '<tr><td class="pos"></td><td class="k">Night<br>0h - 5h</td><td class="k">Morning<br>6h - 11h</td><td class="k">Afternoon<br>12h - 17h</td><td class="k">Evening<br>18h - 23h</td></tr>';
		$tr3 = '';

		for ($i = 1; $i <= $this->maxrows_people_timeofday; $i++) {
			if (!isset($night[$i]['lines']) && !isset($morning[$i]['lines']) && !isset($afternoon[$i]['lines']) && !isset($evening[$i]['lines'])) {
				break;
			} else {
				$tr3 .= '<tr><td class="pos">'.$i.'</td>';

				foreach ($times as $time) {
					if (!isset(${$time}[$i]['lines'])) {
						$tr3 .= '<td class="v"></td>';
					} else {
						$width = round((${$time}[$i]['lines'] / $high_value) * 190);

						if ($width != 0) {
							$tr3 .= '<td class="v">'.htmlspecialchars(${$time}[$i]['user']).' - '.number_format(${$time}[$i]['lines']).'<br><div class="'.$this->color[$time].'" style="width:'.$width.'px"></div></td>';
						} else {
							$tr3 .= '<td class="v">'.htmlspecialchars(${$time}[$i]['user']).' - '.number_format(${$time}[$i]['lines']).'</td>';
						}
					}
				}

				$tr3 .= '</tr>';
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
 * If the channel ID is not set, empty, or has the value "__global" we exit.
 */
if (!isset($_GET['cid']) || !preg_match('/^\S+$/', $_GET['cid']) || preg_match('/^__global$/', $_GET['cid'])) {
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
