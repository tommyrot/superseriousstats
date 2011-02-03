<?php

/**
 * Copyright (c) 2010, Jos de Ruijter <jos@dutnie.nl>
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
	 * USER EDITABLE SETTINGS: the important stuff; database, timezone, etc.
	 */
	private $channel = '#yourchan';
	private $db_host = '127.0.0.1';
	private $db_port = 3306;
	private $db_user = 'user';
	private $db_pass = 'pass';
	private $db_name = 'sss';
	private $timezone = 'Europe/Amsterdam';

	/**
	 * USER EDITABLE SETTINGS: less important stuff; style and presentation.
	 */
	private $bar_afternoon = 'y.png';
	private $bar_evening = 'r.png';
	private $bar_morning = 'g.png';
	private $bar_night = 'b.png';
	private $mainpage = './';
	private $rows_map_month = 30;
	private $rows_map_year = 30;
	private $stylesheet = 'sss.css';
	private $userstats = false;

	/**
	 * Only set to true when troubleshooting.
	 */
	private $debug = false;

	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $firstyearparsed = 0;
	private $l_total = 0;
	private $lastyearparsed = 0;
	private $month = 0;
	private $mysqli;
	private $year = 0;

	public function __construct($year, $month)
	{
		$this->year = $year;
		$this->month = $month;
		date_default_timezone_set($this->timezone);
	}

	/**
	 * Exit with ($debug = true) or without ($debug = false) an error message.
	 */
	private function output($type, $msg)
	{
		if ($this->debug) {
			exit($msg."\n");
		} else {
			exit;
		}
	}

	public function make_html()
	{
		$this->mysqli = @mysqli_connect($this->db_host, $this->db_user, $this->db_pass, $this->db_name, $this->db_port) or $this->output('critical', 'mysqli: '.mysqli_connect_error());
		$query = @mysqli_query($this->mysqli, 'select count(*) as `days`, min(year(`date`)) as `firstyearparsed`, max(year(`date`)) as `lastyearparsed` from `parse_history`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$result = mysqli_fetch_object($query);

		if ((int) $result->days == 0) {
			exit('This channel may only have a future.'."\n");
		}

		$this->firstyearparsed = (int) $result->firstyearparsed;
		$this->lastyearparsed = (int) $result->lastyearparsed;

		/**
		 * HTML Head
		 */
		$output = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'."\n\n"
			. '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">'."\n\n"
			. '<head>'."\n".'<title>'.htmlspecialchars($this->channel).', historically.</title>'."\n"
			. '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n"
			. '<meta http-equiv="Content-Style-Type" content="text/css" />'."\n"
			. '<link rel="stylesheet" type="text/css" href="'.$this->stylesheet.'" />'."\n"
			. '</head>'."\n\n".'<body>'."\n"
			. '<div class="box">'."\n\n"
			. '<div class="info"><a href="'.$this->mainpage.'">'.htmlspecialchars($this->channel).'</a>, historically.</div>'."\n";

		/**
		 * Activity section
		 */
		$output .= '<div class="head">Activity</div>'."\n";
		$output .= $this->make_index();

		if (array_key_exists($this->year, $this->activity) && ($this->month == 0 || array_key_exists($this->month, $this->activity[$this->year]))) {
			if ($this->month == 0) {
				$this->l_total = $this->activity[$this->year][$this->month];
				$output .= $this->make_table_mostactivetimes('year');
				$output .= $this->make_table_mostactivepeople('year', $this->rows_map_year);
			} else {
				$this->l_total = $this->activity[$this->year][$this->month];
				$output .= $this->make_table_mostactivetimes('month');
				$output .= $this->make_table_mostactivepeople('month', $this->rows_map_month);
			}
		}

		/**
		 * HTML Foot
		 */
		$output .= '<div class="info">Statistics created with <a href="http://code.google.com/p/superseriousstats/">superseriousstats</a> on '.date('r').'.</div>'."\n\n";
		$output .= '</div>'."\n".'</body>'."\n\n".'</html>'."\n";
		@mysqli_close($this->mysqli);
		return $output;
	}

	private function make_index() {
		$query = @mysqli_query($this->mysqli, 'select substring(`date`, 1, 4) as `year`, substring(`date`, 6, 2) as `month`, sum(`l_total`) as `l_total` from `q_activity_by_month` group by substring(`date`, 1, 4), substring(`date`, 6, 2) having `l_total` != 0 order by `year` asc, `month` asc') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));

		while ($result = mysqli_fetch_object($query)) {
			if (strpos($result->month, '0') === 0) {
				$result->month = substr($result->month, 1);
			}

			$this->activity[(int) $result->year][(int) $result->month] = (int) $result->l_total;

			if (!isset($this->activity[(int) $result->year][0])) {
				$this->activity[(int) $result->year][0] = 0;
			}

			$this->activity[(int) $result->year][0] += (int) $result->l_total;
		}

		$tr0 = '<col class="pos" /><col class="c" /><col class="c" /><col class="c" /><col class="c" /><col class="c" /><col class="c" /><col class="c" /><col class="c" /><col class="c" /><col class="c" /><col class="c" /><col class="c" />';
		$tr1 = '<tr><th colspan="13">History</th></tr>';
		$tr2 = '<tr><td class="pos"></td><td class="k">Jan</td><td class="k">Feb</td><td class="k">Mar</td><td class="k">Apr</td><td class="k">May</td><td class="k">Jun</td><td class="k">Jul</td><td class="k">Aug</td><td class="k">Sep</td><td class="k">Oct</td><td class="k">Nov</td><td class="k">Dec</td></tr>';
		$trx = '';

		for ($year = $this->firstyearparsed; $year <= $this->lastyearparsed; $year++) {
			if (array_key_exists($year, $this->activity)) {
				$trx .= '<tr><td class="pos"><a href="history.php?y='.$year.'&amp;m=0">'.$year.'</a></td>';

				for ($month = 1; $month <= 12; $month++) {
					if (array_key_exists($month, $this->activity[$year])) {
						$trx .= '<td class="v"><a href="history.php?y='.$year.'&amp;m='.$month.'">'.number_format($this->activity[$year][$month]).'</a></td>';
					} else {
						$trx .= '<td class="v"><span class="grey">n/a</span></td>';
					}
				}

				$trx .= '</tr>';
			}
		}

		return '<table class="index">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}

	/**
	 * Calculate how many years, months or days ago a given $datetime is.
	 */
	private function datetime2daysago($datetime)
	{
		$daysago = round((strtotime('today') - strtotime(substr($datetime, 0, 10))) / 86400);

		if (($daysago / 365) >= 1) {
			$daysago = str_replace('.0', '', number_format($daysago / 365, 1));
			$daysago .= ' Year'.($daysago > 1 ? 's' : '').' Ago';
		} elseif (($daysago / 30.42) >= 1) {
			$daysago = str_replace('.0', '', number_format($daysago / 30.42, 1));
			$daysago .= ' Month'.($daysago > 1 ? 's' : '').' Ago';
		} elseif ($daysago > 1) {
			$daysago .= ' Days Ago';
		} elseif ($daysago == 1) {
			$daysago = 'Yesterday';
		} elseif ($daysago == 0) {
			$daysago = 'Today';
		}

		return $daysago;
	}

	private function make_table_mostactivepeople($type, $rows)
	{
		if ($type == 'year') {
			$head = 'Most Active People, '.$this->year;
			$query = @mysqli_query($this->mysqli, 'select sum(`l_total`) as `l_total` from `q_activity_by_year` where `date` = '.$this->year.' group by `date`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$result = mysqli_fetch_object($query);
			$total = (int) $result->l_total;
			$query = @mysqli_query($this->mysqli, 'select `q_lines`.`ruid`, `csnick`, sum(`q_activity_by_year`.`l_total`) as `l_total`, sum(`q_activity_by_year`.`l_night`) as `l_night`, sum(`q_activity_by_year`.`l_morning`) as `l_morning`, sum(`q_activity_by_year`.`l_afternoon`) as `l_afternoon`, sum(`q_activity_by_year`.`l_evening`) as `l_evening`, `quote` from `q_lines` join `q_activity_by_year` on `q_lines`.`ruid` = `q_activity_by_year`.`ruid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` where `status` != 3 and `date` = '.$this->year.' group by `q_lines`.`ruid` order by `q_activity_by_year`.`l_total` desc, `q_lines`.`ruid` asc limit '.$rows) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		} elseif ($type == 'month') {
			$head = 'Most Active People, '.date('F', mktime(0, 0, 0, $this->month, 1, $this->year)).' '.$this->year;
			$query = @mysqli_query($this->mysqli, 'select sum(`l_total`) as `l_total` from `q_activity_by_month` where `date` = \''.date('Y-m', mktime(0, 0, 0, $this->month, 1, $this->year)).'\' group by `date`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$result = mysqli_fetch_object($query);
			$total = (int) $result->l_total;
			$query = @mysqli_query($this->mysqli, 'select `q_lines`.`ruid`, `csnick`, sum(`q_activity_by_month`.`l_total`) as `l_total`, sum(`q_activity_by_month`.`l_night`) as `l_night`, sum(`q_activity_by_month`.`l_morning`) as `l_morning`, sum(`q_activity_by_month`.`l_afternoon`) as `l_afternoon`, sum(`q_activity_by_month`.`l_evening`) as `l_evening`, `quote` from `q_lines` join `q_activity_by_month` on `q_lines`.`ruid` = `q_activity_by_month`.`ruid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` where `status` != 3 and `date` = \''.date('Y-m', mktime(0, 0, 0, $this->month, 1, $this->year)).'\' group by `q_lines`.`ruid` order by `q_activity_by_month`.`l_total` desc, `q_lines`.`ruid` asc limit '.$rows) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		}

		if ($total == 0) {
			return;
		}

		$tr0 = '<col class="c1" /><col class="c2" /><col class="pos" /><col class="c3" /><col class="c4" /><col class="c5" /><col class="c6" />';
		$tr1 = '<tr><th colspan="7">'.$head.'</th></tr>';
		$tr2 = '<tr><td class="k1">Percentage</td><td class="k2">Lines</td><td class="pos"></td><td class="k3">User</td><td class="k4">When?</td><td class="k5">Last Seen</td><td class="k6">Quote</td></tr>';
		$trx = '';
		$i = 0;

		while ($result = mysqli_fetch_object($query)) {
			$i++;

			if ((int) $result->l_total == 0) {
				break;
			}

			$query_lastseen = @mysqli_query($this->mysqli, 'select max(`lastseen`) as `lastseen` from `user_details` join `user_status` on `user_details`.`uid` = `user_status`.`uid` where `ruid` = '.$result->ruid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$result_lastseen = mysqli_fetch_object($query_lastseen);
			$lastseen = $this->datetime2daysago($result_lastseen->lastseen);
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

			if (!empty($width_remainders) && $width > 0) {
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
					$when .= '<img src="'.$this->{'bar_'.$time}.'" width="'.$width_int[$time].'" alt="" />';
				}
			}

			$trx .= '<tr><td class="v1">'.number_format(((int) $result->l_total / $total) * 100, 2).'%</td><td class="v2">'.number_format((int) $result->l_total).'</td><td class="pos">'.$i.'</td><td class="v3">'.($this->userstats ? '<a href="user.php?uid='.$result->ruid.'">'.htmlspecialchars($result->csnick).'</a>' : htmlspecialchars($result->csnick)).'</td><td class="v4">'.$when.'</td><td class="v5">'.$lastseen.'</td><td class="v6">'.htmlspecialchars($result->quote).'</td></tr>';
		}

		return '<table class="map">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}

	private function make_table_mostactivetimes($type)
	{
		if ($type == 'year') {
			$query = @mysqli_query($this->mysqli, 'select sum(`l_00`) as `l_00`, sum(`l_01`) as `l_01`, sum(`l_02`) as `l_02`, sum(`l_03`) as `l_03`, sum(`l_04`) as `l_04`, sum(`l_05`) as `l_05`, sum(`l_06`) as `l_06`, sum(`l_07`) as `l_07`, sum(`l_08`) as `l_08`, sum(`l_09`) as `l_09`, sum(`l_10`) as `l_10`, sum(`l_11`) as `l_11`, sum(`l_12`) as `l_12`, sum(`l_13`) as `l_13`, sum(`l_14`) as `l_14`, sum(`l_15`) as `l_15`, sum(`l_16`) as `l_16`, sum(`l_17`) as `l_17`, sum(`l_18`) as `l_18`, sum(`l_19`) as `l_19`, sum(`l_20`) as `l_20`, sum(`l_21`) as `l_21`, sum(`l_22`) as `l_22`, sum(`l_23`) as `l_23` from `channel` where year(`date`) = '.$this->year.' group by year(`date`)') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		} elseif ($type == 'month') {
			$query = @mysqli_query($this->mysqli, 'select sum(`l_00`) as `l_00`, sum(`l_01`) as `l_01`, sum(`l_02`) as `l_02`, sum(`l_03`) as `l_03`, sum(`l_04`) as `l_04`, sum(`l_05`) as `l_05`, sum(`l_06`) as `l_06`, sum(`l_07`) as `l_07`, sum(`l_08`) as `l_08`, sum(`l_09`) as `l_09`, sum(`l_10`) as `l_10`, sum(`l_11`) as `l_11`, sum(`l_12`) as `l_12`, sum(`l_13`) as `l_13`, sum(`l_14`) as `l_14`, sum(`l_15`) as `l_15`, sum(`l_16`) as `l_16`, sum(`l_17`) as `l_17`, sum(`l_18`) as `l_18`, sum(`l_19`) as `l_19`, sum(`l_20`) as `l_20`, sum(`l_21`) as `l_21`, sum(`l_22`) as `l_22`, sum(`l_23`) as `l_23` from `channel` where date_format(`date`, \'%Y-%m\') = \''.$this->year.'-'.($this->month < 10 ? '0' : '').$this->month.'\' group by date_format(`date`, \'%Y-%m\')') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		}

		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			return;
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

		$tr1 = '<tr><th colspan="24">Most Active Times</th></tr>';
		$tr2 = '<tr class="bars">';
		$tr3 = '<tr class="sub">';

		foreach ($result as $key => $value) {
			if (substr($key, -2, 1) == '0') {
				$hour = (int) substr($key, -1);
			} else {
				$hour = (int) substr($key, -2);
			}

			if ((int) $value == 0) {
				$tr2 .= '<td><span class="grey">n/a</span></td>';
			} else {
				$perc = ((int) $value / $this->l_total) * 100;

				if ($perc >= 9.95) {
					$tr2 .= '<td>'.round($perc).'%';
				} else {
					$tr2 .= '<td>'.number_format($perc, 1).'%';
				}

				$height = round(((int) $value / $high_value) * 100);

				if ($height != 0) {
					if ($hour >= 0 && $hour <= 5) {
						$tr2 .= '<img src="'.$this->bar_night.'" height="'.$height.'" alt="" title="'.number_format((int) $value).'" />';
					} elseif ($hour >= 6 && $hour <= 11) {
						$tr2 .= '<img src="'.$this->bar_morning.'" height="'.$height.'" alt="" title="'.number_format((int) $value).'" />';
					} elseif ($hour >= 12 && $hour <= 17) {
						$tr2 .= '<img src="'.$this->bar_afternoon.'" height="'.$height.'" alt="" title="'.number_format((int) $value).'" />';
					} elseif ($hour >= 18 && $hour <= 23) {
						$tr2 .= '<img src="'.$this->bar_evening.'" height="'.$height.'" alt="" title="'.number_format((int) $value).'" />';
					}
				}

				$tr2 .= '</td>';
			}

			if ($high_key == $key) {
				$tr3 .= '<td class="bold">'.$hour.'h</td>';
			} else {
				$tr3 .= '<td>'.$hour.'h</td>';
			}
		}

		$tr2 .= '</tr>';
		$tr3 .= '</tr>';
		return '<table class="graph">'.$tr1.$tr2.$tr3.'</table>'."\n";
	}
}

if (isset($_GET['y']) && preg_match('/^(0|[12]\d{3})$/', $_GET['y']) && isset($_GET['m']) && preg_match('/^([0-9]|1[0-2])$/', $_GET['m'])) {
	$history = new history((int) $_GET['y'], (int) $_GET['m']);
	echo $history->make_html();
}

?>
