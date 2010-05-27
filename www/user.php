<?php

/**
 * Copyright (c) 2007-2010, Jos de Ruijter <jos@dutnie.nl>
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
 * Class for creating userstats.
 * Note: data is always one day old (we are generating stats from data up to and including yesterday).
 */
final class User
{
	/**
	 * Make sure to **EDIT** the following settings so they correspond with your setup. Consult the wiki for more details.
	 */
	private $bar_afternoon = 'y.png';
	private $bar_evening = 'r.png';
	private $bar_morning = 'g.png';
	private $bar_night = 'b.png';
	private $channel = '#yourchan';
	private $db_host = '127.0.0.1';
	private $db_port = 3306;
	private $db_user = 'user';
	private $db_pass = 'pass';
	private $db_name = 'superseriousstats';
	private $stylesheet = 'default.css';
	private $timezone = 'Europe/Amsterdam';

	/**
	 * Output debug/error messages on the userstats page. Useful to troubleshoot problems with your webserver configuration.
	 * It's recommended to leave this setting set to FALSE during normal operation.
	 */
	private $debug = FALSE;

	/**
	 * The following variables shouldn't be tampered with.
	 */
	private $RUID = 0;
	private $UID = 0;
	private $csNick = '';
	private $date_max = '';
	private $firstSeen = '';
	private $l_avg = 0;
	private $l_max = 0;
	private $l_total = 0;
	private $lastSeen = '';
	private $month = '';
	private $mysqli;
	private $output = '';
	private $year = '';
	private $years = 0;

	/**
	 * Constructor.
	 */
	public function __construct($UID)
	{
		$this->UID = $UID;
		date_default_timezone_set($this->timezone);
	}

	/**
	 * If something fails we exit with an error message. Used when debugging.
	 */
	private function fail($msg)
	{
		if ($this->debug) {
			exit($msg."\n");
		}
	}

	/**
	 * Generate the HTML page.
	 */
	public function makeHTML()
	{
		$this->mysqli = @mysqli_connect($this->db_host, $this->db_user, $this->db_pass, $this->db_name, $this->db_port) or $this->fail(mysqli_connect_error());
		$query = @mysqli_query($this->mysqli, 'SELECT `RUID`, `csNick` FROM `user_status` JOIN `user_details` ON `user_status`.`RUID` = `user_details`.`UID` WHERE `user_status`.`UID` = '.$this->UID) or $this->fail(mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			exit('This user doesn\'t exist.'."\n");
		}

		$result = mysqli_fetch_object($query);
		$this->RUID = $result->RUID;
		$this->csNick = $result->csNick;
		$query = @mysqli_query($this->mysqli, 'SELECT MIN(`firstSeen`) AS `firstSeen`, MAX(`lastSeen`) AS `lastSeen`, `l_total`, (`l_total` / `activeDays`) AS `l_avg` FROM `user_status` JOIN `user_details` ON `user_status`.`UID` = `user_details`.`UID` JOIN `query_lines` ON `user_status`.`RUID` = `query_lines`.`UID` WHERE `RUID` = '.$this->RUID.' GROUP BY `RUID`') or $this->fail(mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			exit('This user has no lines.'."\n");
		}

		$result = mysqli_fetch_object($query);

		if ($result->l_total == 0) {
			exit('This user has no lines.'."\n");
		}
		
		$this->firstSeen = $result->firstSeen;
		$this->lastSeen = $result->lastSeen;
		$this->l_avg = $result->l_avg;
		$this->l_total = $result->l_total;

		/**
		 * Date and time variables used throughout the script.
		 */
		$this->day = date('j', strtotime('yesterday'));
		$this->month = date('m', strtotime('yesterday'));
		$this->year = date('Y', strtotime('yesterday'));
		$this->years = $this->year - date('Y', strtotime($this->firstSeen)) + 1;

		/**
		 * If we have less than 3 years of data we set the amount of years to 3 so we have that many columns in our table. Looks better.
		 */
		if ($this->years < 3) {
			$this->years = 3;
		}

		/**
		 * HTML Head
		 */
		$query = @mysqli_query($this->mysqli, 'SELECT `date` AS `date_max`, SUM(`l_total`) AS `l_max` FROM `user_status` JOIN `user_activity` ON `user_status`.`UID` = `user_activity`.`UID` WHERE `RUID` = '.$this->RUID.' GROUP BY `date` ORDER BY `l_max` DESC LIMIT 1') or $this->fail(mysqli_error($this->mysqli));
		$result = mysqli_fetch_object($query);
		$this->date_max = $result->date_max;
		$this->l_max = $result->l_max;
		$this->output = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'."\n\n"
			      . '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">'."\n\n"
			      . '<head>'."\n".'<title>'.htmlspecialchars($this->csNick).', seriously.</title>'."\n"
			      . '<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />'."\n"
			      . '<meta http-equiv="Content-Style-Type" content="text/css" />'."\n"
			      . '<link rel="stylesheet" type="text/css" href="'.$this->stylesheet.'" />'."\n"
			      . '<!--[if IE]>'."\n".'  <link rel="stylesheet" type="text/css" href="iefix.css" />'."\n".'<![endif]-->'."\n"
			      . '<style type="text/css">'."\n".'  table.yearly {width:'.(2 + ($this->years * 34)).'px}'."\n".'</style>'."\n"
			      . '</head>'."\n\n".'<body>'."\n"
			      . '<div class="box">'."\n\n"
			      . '<div class="info">'.htmlspecialchars($this->csNick).', seriously.<br /><br />First seen on '.date('M j, Y', strtotime($this->firstSeen)).' and last seen on '.date('M j, Y', strtotime($this->lastSeen)).'.<br />'
			      . '<br />'.htmlspecialchars($this->csNick).' typed '.number_format($this->l_total).' lines on '.htmlspecialchars($this->channel).', an average of '.number_format($this->l_avg).' lines per day.<br />Most active day was '.date('M j, Y', strtotime($this->date_max)).' with a total of '.number_format($result->l_max).' lines typed.</div>'."\n";

		/**
		 * Activity section
		 */
		$this->output .= '<div class="head">Activity</div>'."\n";
		$this->output .= $this->makeTable_MostActiveTimes(array('head' => 'Most Active Times'));
		$this->output .= $this->makeTable_Activity(array('type' => 'days', 'head' => 'Daily Activity'));
		$this->output .= $this->makeTable_Activity(array('type' => 'months', 'head' => 'Monthly Activity'));
		$this->output .= $this->makeTable_MostActiveDays(array('head' => 'Most Active Days'));
		$this->output .= $this->makeTable_Activity(array('type' => 'years', 'head' => 'Yearly Activity'));

		/**
		 * HTML Foot
		 */
		$this->output .= '<div class="info">Statistics created with <a href="http://code.google.com/p/superseriousstats/">superseriousstats</a> on '.date('M j, Y \a\\t g:i A').'.</div>'."\n\n";
		$this->output .= '</div>'."\n".'</body>'."\n\n".'</html>'."\n";
		@mysqli_close($this->mysqli);
		return $this->output;
	}

	/**
	 * Create the most active times table.
	 */
	private function makeTable_MostActiveTimes($settings)
	{
		$query = @mysqli_query($this->mysqli, 'SELECT `l_00`, `l_01`, `l_02`, `l_03`, `l_04`, `l_05`, `l_06`, `l_07`, `l_08`, `l_09`, `l_10`, `l_11`, `l_12`, `l_13`, `l_14`, `l_15`, `l_16`, `l_17`, `l_18`, `l_19`, `l_20`, `l_21`, `l_22`, `l_23` FROM `query_lines` WHERE `UID` = '.$this->RUID) or $this->fail(mysqli_error($this->mysqli));
		$result = mysqli_fetch_object($query);
		$l_total_high = 0;

		for ($hour = 0; $hour < 24; $hour++) {
			if ($hour < 10) {
				$l_total[$hour] = $result->{'l_0'.$hour};
			} else {
				$l_total[$hour] = $result->{'l_'.$hour};
			}

			if ($l_total[$hour] > $l_total_high) {
				$l_total_high = $l_total[$hour];
				$l_total_high_hour = $hour;
			}
		}

		$output = '<table class="graph"><tr><th colspan="24">'.htmlspecialchars($settings['head']).'</th></tr><tr class="bars">';

		for ($hour = 0; $hour < 24; $hour++) {
			if ($l_total[$hour] != 0) {
				$output .= '<td>';

				if ((($l_total[$hour] / $this->l_total) * 100) >= 9.95) {
					$output .= round(($l_total[$hour] / $this->l_total) * 100).'%';
				} else {
					$output .= number_format(($l_total[$hour] / $this->l_total) * 100, 1).'%';
				}

				$height = round(($l_total[$hour] / $l_total_high) * 100);

				if ($height != 0 && $hour >= 0 && $hour <= 5) {
					$output .= '<img src="'.$this->bar_night.'" height="'.$height.'" alt="" title="'.number_format($l_total[$hour]).'" />';
				} elseif ($height != 0 && $hour >= 6 && $hour <= 11) {
					$output .= '<img src="'.$this->bar_morning.'" height="'.$height.'" alt="" title="'.number_format($l_total[$hour]).'" />';
				} elseif ($height != 0 && $hour >= 12 && $hour <= 17) {
					$output .= '<img src="'.$this->bar_afternoon.'" height="'.$height.'" alt="" title="'.number_format($l_total[$hour]).'" />';
				} elseif ($height != 0 && $hour >= 18 && $hour <= 23) {
					$output .= '<img src="'.$this->bar_evening.'" height="'.$height.'" alt="" title="'.number_format($l_total[$hour]).'" />';
				}

				$output .= '</td>';
			} else {
				$output .= '<td><span class="grey">n/a</span></td>';
			}
		}

		$output .= '</tr><tr class="sub">';

		for ($hour = 0; $hour < 24; $hour++) {
			if ($l_total_high != 0 && $l_total_high_hour == $hour) {
				$output .= '<td class="bold">'.$hour.'h</td>';
			} else {
				$output .= '<td>'.$hour.'h</td>';
			}
		}

		return $output.'</tr></table>'."\n";
	}

	/**
	 * Create activity tables.
	 */
	private function makeTable_Activity($settings)
	{
		switch ($settings['type']) {
			case 'days':
				$table_class = 'graph';
				$cols = 24;
				$query = @mysqli_query($this->mysqli, 'SELECT `date`, SUM(`l_total`) AS `l_total`, SUM(`l_night`) AS `l_night`, SUM(`l_morning`) AS `l_morning`, SUM(`l_afternoon`) AS `l_afternoon`, SUM(`l_evening`) AS `l_evening` FROM `user_activity` JOIN `user_status` ON `user_activity`.`UID` = `user_status`.`UID` WHERE `date` > \''.date('Y-m-d', mktime(0, 0, 0, $this->month, $this->day - 24, $this->year)).'\' AND `RUID` = '.$this->RUID.' GROUP BY `date`') or $this->fail(mysqli_error($this->mysqli));
				break;
			case 'months':
				$table_class = 'graph';
				$cols = 24;
				$query = @mysqli_query($this->mysqli, 'SELECT `date`, SUM(`l_total`) AS `l_total`, SUM(`l_night`) AS `l_night`, SUM(`l_morning`) AS `l_morning`, SUM(`l_afternoon`) AS `l_afternoon`, SUM(`l_evening`) AS `l_evening` FROM `user_activity` JOIN `user_status` ON `user_activity`.`UID` = `user_status`.`UID` WHERE DATE_FORMAT(`date`, \'%Y-%m\') > \''.date('Y-m', mktime(0, 0, 0, $this->month - 24, 1, $this->year)).'\' AND `RUID` = '.$this->RUID.' GROUP BY YEAR(`date`), MONTH(`date`)') or $this->fail(mysqli_error($this->mysqli));
				break;
			case 'years':
				$table_class = 'yearly';
				$cols = $this->years;
				$query = @mysqli_query($this->mysqli, 'SELECT `date`, SUM(`l_total`) AS `l_total`, SUM(`l_night`) AS `l_night`, SUM(`l_morning`) AS `l_morning`, SUM(`l_afternoon`) AS `l_afternoon`, SUM(`l_evening`) AS `l_evening` FROM `user_activity` JOIN `user_status` ON `user_activity`.`UID` = `user_status`.`UID` WHERE `RUID` = '.$this->RUID.' GROUP BY YEAR(`date`)') or $this->fail(mysqli_error($this->mysqli));
				break;
		}

		$sums = array('l_total', 'l_night', 'l_morning', 'l_afternoon', 'l_evening');
		$l_total_high = 0;
		$l_total_high_date = '';

		while ($result = mysqli_fetch_object($query)) {
			switch ($settings['type']) {
				case 'days':
					$year = date('Y', strtotime($result->date));
					$month = date('n', strtotime($result->date));
					$day = date('j', strtotime($result->date));
					break;
				case 'months':
					$year = date('Y', strtotime($result->date));
					$month = date('n', strtotime($result->date));
					$day = 1;
					break;
				case 'years':
					$year = date('Y', strtotime($result->date));
					$month = 1;
					$day = 1;
					break;
			}

			foreach ($sums as $sum) {
				$activity[$year][$month][$day][$sum] = $result->$sum;
			}

			if ($result->l_total > $l_total_high) {
				$l_total_high = $result->l_total;
				$l_total_high_date = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
			}
		}

		if ($l_total_high == 0) {
			return;
		}

		$output = '<table class="'.$table_class.'"><tr><th colspan="'.$cols.'">'.htmlspecialchars($settings['head']).'</th></tr><tr class="bars">';

		for ($i = $cols - 1; $i >= 0; $i--) {
			switch ($settings['type']) {
				case 'days':
					$year = date('Y', mktime(0, 0, 0, $this->month, $this->day - $i, $this->year));
					$month = date('n', mktime(0, 0, 0, $this->month, $this->day - $i, $this->year));
					$day = date('j', mktime(0, 0, 0, $this->month, $this->day - $i, $this->year));
					break;
				case 'months':
					$year = date('Y', mktime(0, 0, 0, $this->month - $i, 1, $this->year));
					$month = date('n', mktime(0, 0, 0, $this->month - $i, 1, $this->year));
					$day = 1;
					break;
				case 'years':
					$year = date('Y', mktime(0, 0, 0, 1, 1, $this->year - $i));
					$month = 1;
					$day = 1;
					break;
			}

			if (!empty($activity[$year][$month][$day]['l_total'])) {
				$output .= '<td>';

				if ($activity[$year][$month][$day]['l_total'] >= 999500) {
					$output .= number_format(($activity[$year][$month][$day]['l_total'] / 1000000), 1).'M';
				} elseif ($activity[$year][$month][$day]['l_total'] >= 10000) {
					$output .= round($activity[$year][$month][$day]['l_total'] / 1000).'K';
				} else {
					$output .= $activity[$year][$month][$day]['l_total'];
				}

				if ($activity[$year][$month][$day]['l_evening'] != 0) {
					$l_evening_height = round(($activity[$year][$month][$day]['l_evening'] / $l_total_high) * 100);

					if ($l_evening_height != 0) {
						$output .= '<img src="'.$this->bar_evening.'" height="'.$l_evening_height.'" alt="" title="" />';
					}
				}

				if ($activity[$year][$month][$day]['l_afternoon'] != 0) {
					$l_afternoon_height = round(($activity[$year][$month][$day]['l_afternoon'] / $l_total_high) * 100);

					if ($l_afternoon_height != 0) {
						$output .= '<img src="'.$this->bar_afternoon.'" height="'.$l_afternoon_height.'" alt="" title="" />';
					}
				}

				if ($activity[$year][$month][$day]['l_morning'] != 0) {
					$l_morning_height = round(($activity[$year][$month][$day]['l_morning'] / $l_total_high) * 100);

					if ($l_morning_height != 0) {
						$output .= '<img src="'.$this->bar_morning.'" height="'.$l_morning_height.'" alt="" title="" />';
					}
				}

				if ($activity[$year][$month][$day]['l_night'] != 0) {
					$l_night_height = round(($activity[$year][$month][$day]['l_night'] / $l_total_high) * 100);

					if ($l_night_height != 0) {
						$output .= '<img src="'.$this->bar_night.'" height="'.$l_night_height.'" alt="" title="" />';
					}
				}

				$output .= '</td>';
			} else {
				$output .= '<td><span class="grey">n/a</span></td>';
			}
		}

		$output .= '</tr><tr class="sub">';

		for ($i = $cols - 1; $i >= 0; $i--) {
			switch ($settings['type']) {
				case 'days':
					$date = date('Y-m-d', mktime(0, 0, 0, $this->month, $this->day - $i, $this->year));

					if ($l_total_high_date == $date) {
						$output .= '<td class="bold">'.date('D', strtotime($date)).'<br />'.date('j', strtotime($date)).'</td>';
					} else {
						$output .= '<td>'.date('D', strtotime($date)).'<br />'.date('j', strtotime($date)).'</td>';
					}

					break;
				case 'months':
					$date = date('Y-m-d', mktime(0, 0, 0, $this->month - $i, 1, $this->year));

					if ($l_total_high_date == $date) {
						$output .= '<td class="bold">'.date('M', strtotime($date)).'<br />'.date('\'y', strtotime($date)).'</td>';
					} else {
						$output .= '<td>'.date('M', strtotime($date)).'<br />'.date('\'y', strtotime($date)).'</td>';
					}

					break;
				case 'years':
					$date = date('Y-m-d', mktime(0, 0, 0, 1, 1, $this->year - $i));

					if ($l_total_high_date == $date) {
						$output .= '<td class="bold">'.date('\'y', strtotime($date)).'</td>';
					} else {
						$output .= '<td>'.date('\'y', strtotime($date)).'</td>';
					}

					break;
			}
		}

		return $output.'</tr></table>'."\n";
	}

	/**
	 * Create the most active days table.
	 */
	private function makeTable_MostActiveDays($settings)
	{
		$query = @mysqli_query($this->mysqli, 'SELECT SUM(`l_mon_night`) AS `l_mon_night`, SUM(`l_mon_morning`) AS `l_mon_morning`, SUM(`l_mon_afternoon`) AS `l_mon_afternoon`, SUM(`l_mon_evening`) AS `l_mon_evening`, SUM(`l_tue_night`) AS `l_tue_night`, SUM(`l_tue_morning`) AS `l_tue_morning`, SUM(`l_tue_afternoon`) AS `l_tue_afternoon`, SUM(`l_tue_evening`) AS `l_tue_evening`, SUM(`l_wed_night`) AS `l_wed_night`, SUM(`l_wed_morning`) AS `l_wed_morning`, SUM(`l_wed_afternoon`) AS `l_wed_afternoon`, SUM(`l_wed_evening`) AS `l_wed_evening`, SUM(`l_thu_night`) AS `l_thu_night`, SUM(`l_thu_morning`) AS `l_thu_morning`, SUM(`l_thu_afternoon`) AS `l_thu_afternoon`, SUM(`l_thu_evening`) AS `l_thu_evening`, SUM(`l_fri_night`) AS `l_fri_night`, SUM(`l_fri_morning`) AS `l_fri_morning`, SUM(`l_fri_afternoon`) AS `l_fri_afternoon`, SUM(`l_fri_evening`) AS `l_fri_evening`, SUM(`l_sat_night`) AS `l_sat_night`, SUM(`l_sat_morning`) AS `l_sat_morning`, SUM(`l_sat_afternoon`) AS `l_sat_afternoon`, SUM(`l_sat_evening`) AS `l_sat_evening`, SUM(`l_sun_night`) AS `l_sun_night`, SUM(`l_sun_morning`) AS `l_sun_morning`, SUM(`l_sun_afternoon`) AS `l_sun_afternoon`, SUM(`l_sun_evening`) AS `l_sun_evening` FROM `query_lines` WHERE `UID` = '.$this->RUID) or $this->fail(mysqli_error($this->mysqli));
		$result = mysqli_fetch_object($query);
		$l_total_high = 0;
		$days = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun');

		foreach ($days as $day) {
			$l_night[$day] = $result->{'l_'.$day.'_night'};
			$l_morning[$day] = $result->{'l_'.$day.'_morning'};
			$l_afternoon[$day] = $result->{'l_'.$day.'_afternoon'};
			$l_evening[$day] = $result->{'l_'.$day.'_evening'};
			$l_total[$day] = $l_night[$day] + $l_morning[$day] + $l_afternoon[$day] + $l_evening[$day];

			if ($l_total[$day] > $l_total_high) {
				$l_total_high = $l_total[$day];
				$l_total_high_day = $day;
			}
		}

		$output = '<table class="mad"><tr><th colspan="7">'.htmlspecialchars($settings['head']).'</th></tr><tr class="bars">';

		foreach ($days as $day) {
			if ($l_total[$day] != 0) {
				$output .= '<td>';

				if ((($l_total[$day] / $this->l_total) * 100) >= 9.95) {
					$output .= round(($l_total[$day] / $this->l_total) * 100).'%';
				} else {
					$output .= number_format(($l_total[$day] / $this->l_total) * 100, 1).'%';
				}

				$times = array('evening', 'afternoon', 'morning', 'night');

				foreach ($times as $time) {
					if (${'l_'.$time}[$day] != 0) {
						${'l_'.$time.'_height'} = round((${'l_'.$time}[$day] / $l_total_high) * 100);

						if (${'l_'.$time.'_height'} != 0) {
							$output .= '<img src="'.$this->{'bar_'.$time}.'" height="'.${'l_'.$time.'_height'}.'" alt="" title="'.number_format($l_total[$day]).'" />';
						}
					}
				}

				$output .= '</td>';
			} else {
				$output .= '<td><span class="grey">n/a</span></td>';
			}
		}

		$output .= '</tr><tr class="sub">';

		foreach ($days as $day) {
			if ($l_total_high != 0 && $l_total_high_day == $day) {
				$output .= '<td class="bold">'.ucfirst($day).'</td>';
			} else {
				$output .= '<td>'.ucfirst($day).'</td>';
			}
		}

		return $output.'</tr></table>'."\n";
	}
}

if (preg_match('/^[1-9][0-9]{0,5}$/', $_GET['uid'])) {
	$user = new User($_GET['uid']);
	echo $user->makeHTML();
} else {
	echo 'FAIL';
}

?>
