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
 * Class for creating the main statspage.
 */
final class HTML extends Base
{
	/**
	 * Default settings for this script, can be overridden in the config file.
	 * These should all appear in $settings_list[] along with their type.
	 */
	private $bar_afternoon = 'y.png';
	private $bar_evening = 'r.png';
	private $bar_morning = 'g.png';
	private $bar_night = 'b.png';
	private $channel = '#yourchan';
	private $minLines = 500;
	private $minRows = 3;
	private $sectionbits = 63;
	private $stylesheet = 'default.css';
	private $userstats = FALSE;

	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $date_first = '';
	private $date_last = '';
	private $date_lastLogParsed = '';
	private $date_max = '';
	private $days = 0;
	private $day_of_month = 0;
	private $day_of_year = 0;
	private $l_avg = 0;
	private $l_max = 0;
	private $l_total = 0;
	private $month = 0;
	private $month_name = '';
	private $mysqli;
	private $output = '';
	private $settings_list = array(
		'bar_afternoon' => 'string',
		'bar_evening' => 'string',
		'bar_morning' => 'string',
		'bar_night' => 'string',
		'channel' => 'string',
		'minLines' => 'int',
		'minRows' => 'int',
		'outputbits' => 'int',
		'sectionbits' => 'int',
		'stylesheet' => 'string',
		'userstats' => 'bool');
	private $year = 0;
	private $years = 0;

	/**
	 * Constructor.
	 */
	public function __construct($settings)
	{
		foreach ($this->settings_list as $key => $type) {
			if (!array_key_exists($key, $settings)) {
				continue;
			}

			if ($type == 'string') {
				$this->$key = $settings[$key];
			} elseif ($type == 'int') {
				$this->$key = (int) $settings[$key];
			} elseif ($type == 'bool') {
				if (strtoupper($settings[$key]) == 'TRUE') {
					$this->$key = TRUE;
				} elseif (strtoupper($settings[$key]) == 'FALSE') {
					$this->$key = FALSE;
				}
			}
		}
	}

	/**
	 * Calculate how many years, months or days ago a given date plus time is.
	 */
	private function dateTime2DaysAgo($dateTime)
	{
		$daysAgo = round((strtotime($this->date_lastLogParsed) - strtotime(substr($dateTime, 0, 10))) / 86400);

		if (($daysAgo / 365) >= 1) {
			$daysAgo = str_replace('.0', '', number_format($daysAgo / 365, 1));
			$daysAgo .= ' Year'.($daysAgo > 1 ? 's' : '').' Ago';
		} elseif (($daysAgo / 30.42) >= 1) {
			$daysAgo = str_replace('.0', '', number_format($daysAgo / 30.42, 1));
			$daysAgo .= ' Month'.($daysAgo > 1 ? 's' : '').' Ago';
		} elseif ($daysAgo > 1) {
			$daysAgo .= ' Days Ago';
		} elseif ($daysAgo == 1) {
			$daysAgo = 'Yesterday';
		} elseif ($daysAgo == 0) {
			$daysAgo = 'Today';
		}

		return $daysAgo;
	}

	/**
	 * Generate the HTML page.
	 */
	public function makeHTML($mysqli)
	{
		$this->mysqli = $mysqli;
		$this->output('notice', 'makeHTML(): creating statspage');
		$query = @mysqli_query($this->mysqli, 'SELECT MIN(`date`) AS `date_first`, MAX(`date`) AS `date_last`, COUNT(*) AS `days`, AVG(`l_total`) AS `l_avg`, SUM(`l_total`) AS `l_total` FROM `channel`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			$this->output('critical', 'makeHTML(): database is empty');
		}

		$result = mysqli_fetch_object($query);
		$this->date_first = $result->date_first;
		$this->date_last = $result->date_last;
		$this->days = $result->days;
		$this->l_avg = $result->l_avg;
		$this->l_total = (int) $result->l_total;

		/**
		 * This variable is used to shape most statistics. 1/1000th of the total lines typed in the channel.
		 * 500 is the default minimum so tables will still look interesting on low volume channels.
		 */
		if (round($this->l_total / 1000) >= 500) {
			$this->minLines = round($this->l_total / 1000);
		}

		/**
		 * Date and time variables used throughout the script. We take the date of the last logfile parsed. These variables are used to define our scope.
		 * For whatever reason PHP starts counting days from 0.. so we add 1 to $day_of_year to fix this absurdity.
		 */
		$query = @mysqli_query($this->mysqli, 'SELECT MAX(`date`) AS `date` FROM `parse_history`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		$result = mysqli_fetch_object($query);
		$this->date_lastLogParsed = $result->date;
		$this->day_of_month = date('j', strtotime($this->date_lastLogParsed));
		$this->day_of_year = date('z', strtotime($this->date_lastLogParsed)) + 1;
		$this->month = date('n', strtotime($this->date_lastLogParsed));
		$this->month_name = date('F', strtotime($this->date_lastLogParsed));
		$this->year = date('Y', strtotime($this->date_lastLogParsed));
		$this->years = $this->year - date('Y', strtotime($this->date_first)) + 1;

		/**
		 * If we have less than 3 years of data we set the amount of years to 3 so we have that many columns in our table. Looks better.
		 */
		if ($this->years < 3) {
			$this->years = 3;
		}

		/**
		 * HTML Head
		 */
		$query = @mysqli_query($this->mysqli, 'SELECT `date` AS `date_max`, `l_total` AS `l_max` FROM `channel` ORDER BY `l_total` DESC, `date` ASC LIMIT 1') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		$result = mysqli_fetch_object($query);
		$this->date_max = $result->date_max;
		$this->l_max = $result->l_max;
		$this->output = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'."\n\n"
			      . '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">'."\n\n"
			      . '<head>'."\n".'<title>'.htmlspecialchars($this->channel).', seriously.</title>'."\n"
			      . '<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />'."\n"
			      . '<meta http-equiv="Content-Style-Type" content="text/css" />'."\n"
			      . '<link rel="stylesheet" type="text/css" href="'.$this->stylesheet.'" />'."\n"
			      . '<link rel="stylesheet" type="text/css" href="ellipsis.css" />'."\n"
			      . '<style type="text/css">'."\n".'  .yearly {width:'.(2 + ($this->years * 34)).'px}'."\n".'</style>'."\n"
			      . '</head>'."\n\n".'<body>'."\n"
			      . '<div class="box">'."\n\n"
			      . '<div class="info">'.htmlspecialchars($this->channel).', seriously.<br /><br />'.number_format($this->days).' day'.($this->days > 1 ? 's logged from '.date('M j, Y', strtotime($this->date_first)).' to '.date('M j, Y', strtotime($this->date_last)) : ' logged on '.date('M j, Y', strtotime($this->date_first))).'.<br />'
			      . '<br />Logs contain '.number_format($this->l_total).' lines, an average of '.number_format($this->l_avg).' lines per day.<br />Most active day was '.date('M j, Y', strtotime($this->date_max)).' with a total of '.number_format($this->l_max).' lines typed.</div>'."\n";

		/**
		 * Bots are excluded from statistics unless stated otherwise.
		 * They are, however, included in the (channel) totals.
		 */

		/**
		 * Activity section
		 */
		if ($this->sectionbits & 1) {
			$this->output .= '<div class="head">Activity</div>'."\n";
			$this->output .= $this->makeTable_MostActiveTimes();
			$this->output .= $this->makeTable_Activity('daily');
			$this->output .= $this->makeTable_Activity('monthly');
			$this->output .= $this->makeTable_MostActiveDays();
			$this->output .= $this->makeTable_Activity('yearly');
			$this->output .= $this->makeTable_MostActivePeople('alltime', 30);
			$this->output .= $this->makeTable_MostActivePeople('year', 10);
			$this->output .= $this->makeTable_MostActivePeople('month', 10);
			$this->output .= $this->makeTable_TimeOfDay(10);
		}

		/**
		 * General Chat section
		 */
		if ($this->sectionbits & 2) {
			$output = '';

			$t = new Table('Most Talkative Chatters');
			$t->setValue('decimals', 1);
			$t->setValue('key1', 'Lines/Day');
			$t->setValue('key2', 'User');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT (`l_total` / `activeDays`) AS `v1`, `csNick` AS `v2` FROM `query_lines` JOIN `user_details` ON `query_lines`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `l_total` >= '.$this->minLines.' ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Fluent Chatters');
			$t->setValue('decimals', 1);
			$t->setValue('key1', 'Words/Line');
			$t->setValue('key2', 'User');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT (`words` / `l_total`) AS `v1`, `csNick` AS `v2` FROM `query_lines` JOIN `user_details` ON `query_lines`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `l_total` >= '.$this->minLines.' ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Tedious Chatters');
			$t->setValue('decimals', 1);
			$t->setValue('key1', 'Chars/Line');
			$t->setValue('key2', 'User');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT (`characters` / `l_total`) AS `v1`, `csNick` AS `v2` FROM `query_lines` JOIN `user_details` ON `query_lines`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `l_total` >= '.$this->minLines.' ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Individual Top Days, Alltime');
			$t->setValue('key1', 'Lines');
			$t->setValue('key2', 'User');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT MAX(`l_total`) AS `v1`, `csNick` AS `v2` FROM `mview_activity_by_day` JOIN `user_status` ON `mview_activity_by_day`.`RUID` = `user_status`.`UID` JOIN `user_details` ON `mview_activity_by_day`.`RUID` = `user_details`.`UID` WHERE `status` != 3 GROUP BY `mview_activity_by_day`.`RUID` ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Individual Top Days, '.$this->year);
			$t->setValue('key1', 'Lines');
			$t->setValue('key2', 'User');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT MAX(`l_total`) AS `v1`, `csNick` AS `v2` FROM `mview_activity_by_day` JOIN `user_status` ON `mview_activity_by_day`.`RUID` = `user_status`.`UID` JOIN `user_details` ON `mview_activity_by_day`.`RUID` = `user_details`.`UID` WHERE `status` != 3 AND YEAR(`date`) = \''.$this->year.'\' GROUP BY `mview_activity_by_day`.`RUID` ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Individual Top Days, '.$this->month_name.' '.$this->year);
			$t->setValue('key1', 'Lines');
			$t->setValue('key2', 'User');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT MAX(`l_total`) AS `v1`, `csNick` AS `v2` FROM `mview_activity_by_day` JOIN `user_status` ON `mview_activity_by_day`.`RUID` = `user_status`.`UID` JOIN `user_details` ON `mview_activity_by_day`.`RUID` = `user_details`.`UID` WHERE `status` != 3 AND DATE_FORMAT(`date`, \'%Y-%m\') = \''.date('Y-m', strtotime($this->date_lastLogParsed)).'\' GROUP BY `mview_activity_by_day`.`RUID` ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Active Chatters, Alltime');
			$t->setValue('decimals', 2);
			$t->setValue('key1', 'Activity');
			$t->setValue('key2', 'User');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('percentage', TRUE);
			$t->setValue('query_main', 'SELECT (`activeDays` / '.(((strtotime($this->date_last) - strtotime($this->date_first)) / 86400) + 1).') * 100 AS `v1`, `csNick` AS `v2` FROM `user_status` JOIN `query_lines` ON `user_status`.`UID` = `query_lines`.`RUID` JOIN `user_details` ON `user_status`.`UID` = `user_details`.`UID` WHERE `status` != 3 ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Active Chatters, '.$this->year);
			$t->setValue('decimals', 2);
			$t->setValue('key1', 'Activity');
			$t->setValue('key2', 'User');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('percentage', TRUE);
			$t->setValue('query_main', 'SELECT (COUNT(DISTINCT `date`) / '.$this->day_of_year.') * 100 AS `v1`, `csNick` AS `v2` FROM `mview_activity_by_day` JOIN `user_status` ON `mview_activity_by_day`.`RUID` = `user_status`.`UID` JOIN `user_details` ON `mview_activity_by_day`.`RUID` = `user_details`.`UID` WHERE `status` != 3 AND YEAR(`date`) = '.$this->year.' GROUP BY `mview_activity_by_day`.`RUID` ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Active Chatters, '.$this->month_name.' '.$this->year);
			$t->setValue('decimals', 2);
			$t->setValue('key1', 'Activity');
			$t->setValue('key2', 'User');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('percentage', TRUE);
			$t->setValue('query_main', 'SELECT (COUNT(DISTINCT `date`) / '.$this->day_of_month.') * 100 AS `v1`, `csNick` AS `v2` FROM `mview_activity_by_day` JOIN `user_status` ON `mview_activity_by_day`.`RUID` = `user_status`.`UID` JOIN `user_details` ON `mview_activity_by_day`.`RUID` = `user_details`.`UID` WHERE `status` != 3 AND DATE_FORMAT(`date`, \'%Y-%m\') = \''.date('Y-m', strtotime($this->date_lastLogParsed)).'\' GROUP BY `mview_activity_by_day`.`RUID` ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Exclamations');
			$t->setValue('decimals', 2);
			$t->setValue('key1', 'Percentage');
			$t->setValue('key2', 'User');
			$t->setValue('key3', 'Example');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('percentage', TRUE);
			$t->setValue('query_main', 'SELECT (`exclamations` / `l_total`) * 100 AS `v1`, `csNick` AS `v2`, `ex_exclamations` AS `v3` FROM `query_lines` JOIN `user_details` ON `query_lines`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `exclamations` != 0 AND `l_total` >= '.$this->minLines.' ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$t->setValue('type', 'large');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Questions');
			$t->setValue('decimals', 2);
			$t->setValue('key1', 'Percentage');
			$t->setValue('key2', 'User');
			$t->setValue('key3', 'Example');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('percentage', TRUE);
			$t->setValue('query_main', 'SELECT (`questions` / `l_total`) * 100 AS `v1`, `csNick` AS `v2`, `ex_questions` AS `v3` FROM `query_lines` JOIN `user_details` ON `query_lines`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `questions` != 0 AND `l_total` >= '.$this->minLines.' ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$t->setValue('type', 'large');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most UPPERCASED Lines');
			$t->setValue('decimals', 2);
			$t->setValue('key1', 'Percentage');
			$t->setValue('key2', 'User');
			$t->setValue('key3', 'Example');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('percentage', TRUE);
			$t->setValue('query_main', 'SELECT (`uppercased` / `l_total`) * 100 AS `v1`, `csNick` AS `v2`, `ex_uppercased` AS `v3` FROM `query_lines` JOIN `user_details` ON `query_lines`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `uppercased` != 0 AND `l_total` >= '.$this->minLines.' ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$t->setValue('type', 'large');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most URLs, by Users');
			$t->setValue('key1', 'URLs');
			$t->setValue('key2', 'User');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT `URLs` AS `v1`, `csNick` AS `v2` FROM `query_lines` JOIN `user_details` ON `query_lines`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `URLs` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$t->setValue('query_total', 'SELECT SUM(`URLs`) AS `total` FROM `query_lines` JOIN `user_details` ON `query_lines`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`RUID` = `user_status`.`UID` WHERE `status` != 3');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most URLs, by Bots');
			$t->setValue('key1', 'URLs');
			$t->setValue('key2', 'Bot');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT `URLs` AS `v1`, `csNick` AS `v2` FROM `query_lines` JOIN `user_details` ON `query_lines`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`RUID` = `user_status`.`UID` WHERE `status` = 3 AND `URLs` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$t->setValue('query_total', 'SELECT SUM(`URLs`) AS `total` FROM `query_lines` JOIN `user_details` ON `query_lines`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`RUID` = `user_status`.`UID` WHERE `status` = 3');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Monologues');
			$t->setValue('key1', 'Monologues');
			$t->setValue('key2', 'User');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT `monologues` AS `v1`, `csNick` AS `v2` FROM `query_lines` JOIN `user_details` ON `query_lines`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `monologues` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$t->setValue('query_total', 'SELECT SUM(`monologues`) AS `total` FROM `query_lines`');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Slaps, Given');
			$t->setValue('key1', 'Slaps');
			$t->setValue('key2', 'User');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT `slaps` AS `v1`, `csNick` AS `v2` FROM `query_lines` JOIN `user_details` ON `query_lines`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `slaps` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$t->setValue('query_total', 'SELECT SUM(`slaps`) AS `total` FROM `query_lines`');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Slaps, Received');
			$t->setValue('key1', 'Slaps');
			$t->setValue('key2', 'User');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT `slapped` AS `v1`, `csNick` AS `v2` FROM `query_lines` JOIN `user_details` ON `query_lines`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `slapped` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$t->setValue('query_total', 'SELECT SUM(`slapped`) AS `total` FROM `query_lines`');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Longest Monologue');
			$t->setValue('key1', 'Lines');
			$t->setValue('key2', 'User');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT `topMonologue` AS `v1`, `csNick` AS `v2` FROM `query_lines` JOIN `user_details` ON `query_lines`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `topMonologue` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Actions');
			$t->setValue('decimals', 2);
			$t->setValue('key1', 'Percentage');
			$t->setValue('key2', 'User');
			$t->setValue('key3', 'Example');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('percentage', TRUE);
			$t->setValue('query_main', 'SELECT (`actions` / `l_total`) * 100 AS `v1`, `csNick` AS `v2`, `ex_actions` AS `v3` FROM `query_lines` JOIN `user_details` ON `query_lines`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `actions` != 0 AND `l_total` >= '.$this->minLines.' ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$t->setValue('type', 'large');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Mentioned Nicks');
			$t->setValue('key1', 'Mentioned');
			$t->setValue('key2', 'Nick');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT `total` AS `v1`, `csNick` AS `v2` FROM `user_details` JOIN `words` ON `user_details`.`csNick` = `words`.`word` JOIN `user_activity` ON `user_details`.`UID` = `user_activity`.`UID` GROUP BY `user_details`.`UID` HAVING SUM(`l_total`) >= '.$this->minLines.' ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Chatty Bots');
			$t->setValue('key1', 'Lines');
			$t->setValue('key2', 'Bot');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT `l_total` AS `v1`, `csNick` AS `v2` FROM `query_lines` JOIN `user_details` ON `query_lines`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`RUID` = `user_status`.`UID` WHERE `status` = 3 AND `l_total` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$output .= $t->makeTable($this->mysqli);

			if (!empty($output)) {
				$this->output .= '<div class="head">General Chat</div>'."\n".$output;
			}
		}

		/**
		 * Modes section
		 */
		if ($this->sectionbits & 4) {
			$output = '';
			$modes = array(
				'Most Ops \'+o\', Given' => array('Ops', 'm_op'),
				'Most Ops \'+o\', Received' => array('Ops', 'm_opped'),
				'Most deOps \'-o\', Given' => array('deOps', 'm_deOp'),
				'Most deOps \'-o\', Received' => array('deOps', 'm_deOpped'),
				'Most Voices \'+v\', Given' => array('Voices', 'm_voice'),
				'Most Voices \'+v\', Received' => array('Voices', 'm_voiced'),
				'Most deVoices \'-v\', Given' => array('deVoices', 'm_deVoice'),
				'Most deVoices \'-v\', Received' => array('deVoices', 'm_deVoiced'));

			foreach ($modes as $key => $value) {
				$t = new Table($key);
				$t->setValue('key1', $value[0]);
				$t->setValue('key2', 'User');
				$t->setValue('minRows', $this->minRows);
				$t->setValue('query_main', 'SELECT `'.$value[1].'` AS `v1`, `csNick` AS `v2` FROM `query_events` JOIN `user_details` ON `query_events`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_events`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `'.$value[1].'` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
				$t->setValue('query_total', 'SELECT SUM(`'.$value[1].'`) AS `total` FROM `query_events`');
				$output .= $t->makeTable($this->mysqli);
			}

			if (!empty($output)) {
				$this->output .= '<div class="head">Modes</div>'."\n".$output;
			}
		}

		/**
		 * Events section
		 */
		if ($this->sectionbits & 8) {
			$output = '';

			$t = new Table('Most Kicks');
			$t->setValue('key1', 'Kicks');
			$t->setValue('key2', 'User');
			$t->setValue('key3', 'Example');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT `kicks` AS `v1`, `csNick` AS `v2`, `ex_kicks` AS `v3` FROM `query_events` JOIN `user_details` ON `query_events`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_events`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `kicks` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$t->setValue('query_total', 'SELECT SUM(`kicks`) AS `total` FROM `query_events`');
			$t->setValue('type', 'large');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Kicked');
			$t->setValue('key1', 'Kicked');
			$t->setValue('key2', 'User');
			$t->setValue('key3', 'Example');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT `kicked` AS `v1`, `csNick` AS `v2`, `ex_kicked` AS `v3` FROM `query_events` JOIN `user_details` ON `query_events`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_events`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `kicked` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$t->setValue('query_total', 'SELECT SUM(`kicked`) AS `total` FROM `query_events`');
			$t->setValue('type', 'large');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Joins');
			$t->setValue('key1', 'Joins');
			$t->setValue('key2', 'User');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT `joins` AS `v1`, `csNick` AS `v2` FROM `query_events` JOIN `user_details` ON `query_events`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_events`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `joins` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$t->setValue('query_total', 'SELECT SUM(`joins`) AS `total` FROM `query_events`');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Parts');
			$t->setValue('key1', 'Parts');
			$t->setValue('key2', 'User');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT `parts` AS `v1`, `csNick` AS `v2` FROM `query_events` JOIN `user_details` ON `query_events`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_events`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `parts` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$t->setValue('query_total', 'SELECT SUM(`parts`) AS `total` FROM `query_events`');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Quits');
			$t->setValue('key1', 'Quits');
			$t->setValue('key2', 'User');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT `quits` AS `v1`, `csNick` AS `v2` FROM `query_events` JOIN `user_details` ON `query_events`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_events`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `quits` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$t->setValue('query_total', 'SELECT SUM(`quits`) AS `total` FROM `query_events`');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Nick Changes');
			$t->setValue('key1', 'Nick Changes');
			$t->setValue('key2', 'User');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT `nickchanges` AS `v1`, `csNick` AS `v2` FROM `query_events` JOIN `user_details` ON `query_events`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_events`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `nickchanges` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$t->setValue('query_total', 'SELECT SUM(`nickchanges`) AS `total` FROM `query_events`');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Aliases');
			$t->setValue('key1', 'Aliases');
			$t->setValue('key2', 'User');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT COUNT(*) AS `v1`, `csNick` AS `v2` FROM `user_details` JOIN `user_status` ON `user_details`.`UID` = `user_status`.`UID` WHERE `status` != 3 GROUP BY `RUID` ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$t->setValue('query_total', 'SELECT COUNT(*) AS `total` FROM `user_status`');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Topics');
			$t->setValue('key1', 'Topics');
			$t->setValue('key2', 'User');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT `topics` AS `v1`, `csNick` AS `v2` FROM `query_events` JOIN `user_details` ON `query_events`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_events`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `topics` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
			$t->setValue('query_total', 'SELECT SUM(`topics`) AS `total` FROM `query_events`');
			$output .= $t->makeTable($this->mysqli);

			$t = new Table('Most Recent Topics');
			$t->setValue('key1', 'Date');
			$t->setValue('key2', 'User');
			$t->setValue('key3', 'Topic');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT `setDate` AS `v1`, `csNick` AS `v2`, `csTopic` AS `v3` FROM `user_topics` JOIN `user_status` ON `user_topics`.`UID` = `user_status`.`UID` JOIN `user_details` ON `user_details`.`UID` = `user_status`.`RUID` ORDER BY `v1` DESC LIMIT 5');
			$t->setValue('type', 'topics');
			$output .= $t->makeTable($this->mysqli);

			if (!empty($output)) {
				$this->output .= '<div class="head">Events</div>'."\n".$output;
			}
		}

		/**
		 * Smileys section
		 */
		if ($this->sectionbits & 16) {
			$output = '';
			$smileys = array(
				'Big Cheerful Smile' => array('=]', 's_01'),
				'Cheerful Smile' => array('=)', 's_02'),
				'Lovely Kiss' => array(';x', 's_03'),
				'Retard' => array(';p', 's_04'),
				'Big Winky' => array(';]', 's_05'),
				'Classic Winky' => array(';-)', 's_06'),
				'Winky' => array(';)', 's_07'),
				'Cry' => array(';(', 's_08'),
				'Kiss' => array(':x', 's_09'),
				'Tongue' => array(':P', 's_10'),
				'Laugh' => array(':D', 's_11'),
				'Funny' => array(':>', 's_12'),
				'Big Smile' => array(':]', 's_13'),
				'Skeptical I' => array(':\\', 's_14'),
				'Skeptical II' => array(':/', 's_15'),
				'Classic Happy' => array(':-)', 's_16'),
				'Happy' => array(':)', 's_17'),
				'Sad' => array(':(', 's_18'),
				'Cheer' => array('\\o/', 's_19'));

			$query = @mysqli_query($this->mysqli, 'SELECT SUM(`s_01`) AS `s_01`, SUM(`s_02`) AS `s_02`, SUM(`s_03`) AS `s_03`, SUM(`s_04`) AS `s_04`, SUM(`s_05`) AS `s_05`, SUM(`s_06`) AS `s_06`, SUM(`s_07`) AS `s_07`, SUM(`s_08`) AS `s_08`, SUM(`s_09`) AS `s_09`, SUM(`s_10`) AS `s_10`, SUM(`s_11`) AS `s_11`, SUM(`s_12`) AS `s_12`, SUM(`s_13`) AS `s_13`, SUM(`s_14`) AS `s_14`, SUM(`s_15`) AS `s_15`, SUM(`s_16`) AS `s_16`, SUM(`s_17`) AS `s_17`, SUM(`s_18`) AS `s_18`, SUM(`s_19`) AS `s_19` FROM `query_smileys`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			$result = mysqli_fetch_object($query);

			foreach ($smileys as $key => $value) {
				if ($result->$value[1] < $this->minLines) {
					continue;
				}

				$t = new Table($key);
				$t->setValue('key1', $value[0]);
				$t->setValue('key2', 'User');
				$t->setValue('minRows', $this->minRows);
				$t->setValue('query_main', 'SELECT `'.$value[1].'` AS `v1`, `csNick` AS `v2` FROM `query_smileys` JOIN `user_details` ON `query_smileys`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_smileys`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `'.$value[1].'` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5');
				$t->setValue('total', $result->$value[1]);
				$output .= $t->makeTable($this->mysqli);
			}

			if (!empty($output)) {
				$this->output .= '<div class="head">Smileys</div>'."\n".$output;
			}
		}

		/**
		 * URLs section
		 */
		if ($this->sectionbits & 32) {
			$output = '';

			$t = new Table('Most Recent URLs');
			$t->setValue('key1', 'Date');
			$t->setValue('key2', 'User');
			$t->setValue('key3', 'URL');
			$t->setValue('minRows', $this->minRows);
			$t->setValue('query_main', 'SELECT `lastUsed` AS `v1`, `csNick` AS `v2`, `csURL` AS `v3` FROM `user_URLs` JOIN `user_status` ON `user_URLs`.`UID` = `user_status`.`UID` JOIN `user_details` ON `user_details`.`UID` = `user_status`.`RUID` ORDER BY `v1` DESC LIMIT 100');
			$t->setValue('rows', 100);
			$t->setValue('type', 'URLs');
			$output .= $t->makeTable($this->mysqli);

			if (!empty($output)) {
				$this->output .= '<div class="head">URLs</div>'."\n".$output;
			}
		}

		/**
		 * HTML Foot
		 */
		$this->output .= '<div class="info">Statistics created with <a href="http://code.google.com/p/superseriousstats/">superseriousstats</a> on '.date('M j, Y \a\\t g:i A').'.</div>'."\n\n";
		$this->output .= '</div>'."\n".'</body>'."\n\n".'</html>'."\n";
		@mysqli_close($this->mysqli);
		$this->output('notice', 'makeHTML(): finished creating statspage');
		return $this->output;
	}

	/**
	 * Create activity tables.
	 */
	private function makeTable_Activity($type)
	{
		if ($type == 'daily') {
			$class = 'graph';
			$cols = 24;

			for ($i = 23; $i >= 0; $i--) {
				$dates[] = date('Y-m-d', mktime(0, 0, 0, $this->month, $this->day_of_month - $i, $this->year));
			}

			$head = 'Daily Activity';
			$query = @mysqli_query($this->mysqli, 'SELECT `date`, `l_total`, `l_night`, `l_morning`, `l_afternoon`, `l_evening` FROM `channel` WHERE `date` > \''.date('Y-m-d', mktime(0, 0, 0, $this->month, $this->day_of_month - 24, $this->year)).'\'') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		} elseif ($type == 'monthly') {
			$class = 'graph';
			$cols = 24;

			for ($i = 23; $i >= 0; $i--) {
				$dates[] = date('Y-m', mktime(0, 0, 0, $this->month - $i, 1, $this->year));
			}

			$head = 'Monthly Activity';
			$query = @mysqli_query($this->mysqli, 'SELECT DATE_FORMAT(`date`, \'%Y-%m\') AS `date`, SUM(`l_total`) AS `l_total`, SUM(`l_night`) AS `l_night`, SUM(`l_morning`) AS `l_morning`, SUM(`l_afternoon`) AS `l_afternoon`, SUM(`l_evening`) AS `l_evening` FROM `channel` WHERE DATE_FORMAT(`date`, \'%Y-%m\') > \''.date('Y-m', mktime(0, 0, 0, $this->month - 24, 1, $this->year)).'\' GROUP BY YEAR(`date`), MONTH(`date`)') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		} elseif ($type == 'yearly') {
			$class = 'yearly';
			$cols = $this->years;

			for ($i = $this->years - 1; $i >= 0; $i--) {
				$dates[] = $this->year - $i;
			}

			$head = 'Yearly Activity';
			$query = @mysqli_query($this->mysqli, 'SELECT YEAR(`date`) AS `date`, SUM(`l_total`) AS `l_total`, SUM(`l_night`) AS `l_night`, SUM(`l_morning`) AS `l_morning`, SUM(`l_afternoon`) AS `l_afternoon`, SUM(`l_evening`) AS `l_evening` FROM `channel` GROUP BY YEAR(`date`)') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		}

		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			return;
		}

		$high_date = '';
		$high_value = 0;

		while ($result = mysqli_fetch_object($query)) {
			$l_night[$result->date] = (int) $result->l_night;
			$l_morning[$result->date] = (int) $result->l_morning;
			$l_afternoon[$result->date] = (int) $result->l_afternoon;
			$l_evening[$result->date] = (int) $result->l_evening;
			$l_total[$result->date] = $l_night[$result->date] + $l_morning[$result->date] + $l_afternoon[$result->date] + $l_evening[$result->date];

			if ((int) $result->l_total > $high_value) {
				$high_date = $result->date;
				$high_value = (int) $result->l_total;
			}
		}

		$tr1 = '<tr><th colspan="'.$cols.'">'.$head.'</th></tr>';
		$tr2 = '<tr class="bars">';
		$tr3 = '<tr class="sub">';

		foreach ($dates as $date) {
			if (empty($l_total[$date])) {
				$tr2 .= '<td><span class="grey">n/a</span></td>';
			} else {
				if ($l_total[$date] >= 999500) {
					$tr2 .= '<td>'.number_format($l_total[$date] / 1000000, 1).'M';
				} elseif ($l_total[$date] >= 10000) {
					$tr2 .= '<td>'.round($l_total[$date] / 1000).'K';
				} else {
					$tr2 .= '<td>'.$l_total[$date];
				}

				$times = array('evening', 'afternoon', 'morning', 'night');

				foreach ($times as $time) {
					if (${'l_'.$time}[$date] != 0) {
						$height = round((${'l_'.$time}[$date] / $high_value) * 100);

						if ($height != 0) {
							$tr2 .= '<img src="'.$this->{'bar_'.$time}.'" height="'.$height.'" alt="" title="" />';
						}
					}
				}

				$tr2 .= '</td>';
			}

			if ($type == 'daily') {
				if ($high_date == $date) {
					$tr3 .= '<td class="bold">'.date('D', strtotime($date)).'<br />'.date('j', strtotime($date)).'</td>';
				} else {
					$tr3 .= '<td>'.date('D', strtotime($date)).'<br />'.date('j', strtotime($date)).'</td>';
				}
			} elseif ($type == 'monthly') {
				if ($high_date == $date) {
					$tr3 .= '<td class="bold">'.date('M', strtotime($date.'-01')).'<br />'.date('\'y', strtotime($date.'-01')).'</td>';
				} else {
					$tr3 .= '<td>'.date('M', strtotime($date.'-01')).'<br />'.date('\'y', strtotime($date.'-01')).'</td>';
				}
			} elseif ($type == 'yearly') {
				if ($high_date == $date) {
					$tr3 .= '<td class="bold">'.date('\'y', strtotime($date.'-01-01')).'</td>';
				} else {
					$tr3 .= '<td>'.date('\'y', strtotime($date.'-01-01')).'</td>';
				}
			}
		}

		$tr2 .= '</tr>';
		$tr3 .= '</tr>';
		return '<table class="'.$class.'">'.$tr1.$tr2.$tr3.'</table>'."\n";
	}

	/**
	 * Create the most active days table.
	 */
	private function makeTable_MostActiveDays()
	{
		$query = @mysqli_query($this->mysqli, 'SELECT SUM(`l_mon_night`) AS `l_mon_night`, SUM(`l_mon_morning`) AS `l_mon_morning`, SUM(`l_mon_afternoon`) AS `l_mon_afternoon`, SUM(`l_mon_evening`) AS `l_mon_evening`, SUM(`l_tue_night`) AS `l_tue_night`, SUM(`l_tue_morning`) AS `l_tue_morning`, SUM(`l_tue_afternoon`) AS `l_tue_afternoon`, SUM(`l_tue_evening`) AS `l_tue_evening`, SUM(`l_wed_night`) AS `l_wed_night`, SUM(`l_wed_morning`) AS `l_wed_morning`, SUM(`l_wed_afternoon`) AS `l_wed_afternoon`, SUM(`l_wed_evening`) AS `l_wed_evening`, SUM(`l_thu_night`) AS `l_thu_night`, SUM(`l_thu_morning`) AS `l_thu_morning`, SUM(`l_thu_afternoon`) AS `l_thu_afternoon`, SUM(`l_thu_evening`) AS `l_thu_evening`, SUM(`l_fri_night`) AS `l_fri_night`, SUM(`l_fri_morning`) AS `l_fri_morning`, SUM(`l_fri_afternoon`) AS `l_fri_afternoon`, SUM(`l_fri_evening`) AS `l_fri_evening`, SUM(`l_sat_night`) AS `l_sat_night`, SUM(`l_sat_morning`) AS `l_sat_morning`, SUM(`l_sat_afternoon`) AS `l_sat_afternoon`, SUM(`l_sat_evening`) AS `l_sat_evening`, SUM(`l_sun_night`) AS `l_sun_night`, SUM(`l_sun_morning`) AS `l_sun_morning`, SUM(`l_sun_afternoon`) AS `l_sun_afternoon`, SUM(`l_sun_evening`) AS `l_sun_evening` FROM `query_lines`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			return;
		}

		$result = mysqli_fetch_object($query);
		$high_day = '';
		$high_value = 0;
		$days = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun');

		foreach ($days as $day) {
			$l_night[$day] = (int) $result->{'l_'.$day.'_night'};
			$l_morning[$day] = (int) $result->{'l_'.$day.'_morning'};
			$l_afternoon[$day] = (int) $result->{'l_'.$day.'_afternoon'};
			$l_evening[$day] = (int) $result->{'l_'.$day.'_evening'};
			$l_total[$day] = $l_night[$day] + $l_morning[$day] + $l_afternoon[$day] + $l_evening[$day];

			if ($l_total[$day] > $high_value) {
				$high_day = $day;
				$high_value = $l_total[$day];
			}
		}

		$tr1 = '<tr><th colspan="7">Most Active Days</th></tr>';
		$tr2 = '<tr class="bars">';
		$tr3 = '<tr class="sub">';

		foreach ($days as $day) {
			if ($l_total[$day] == 0) {
				$tr2 .= '<td><span class="grey">n/a</span></td>';
			} else {
				$perc = ($l_total[$day] / $this->l_total) * 100;

				if ($perc >= 9.95) {
					$tr2 .= '<td>'.round($perc).'%';
				} else {
					$tr2 .= '<td>'.number_format($perc, 1).'%';
				}

				$times = array('evening', 'afternoon', 'morning', 'night');

				foreach ($times as $time) {
					if (${'l_'.$time}[$day] != 0) {
						$height = round((${'l_'.$time}[$day] / $high_value) * 100);

						if ($height != 0) {
							$tr2 .= '<img src="'.$this->{'bar_'.$time}.'" height="'.$height.'" alt="" title="'.number_format($l_total[$day]).'" />';
						}
					}
				}

				$tr2 .= '</td>';
			}

			if ($high_day == $day) {
				$tr3 .= '<td class="bold">'.ucfirst($day).'</td>';
			} else {
				$tr3 .= '<td>'.ucfirst($day).'</td>';
			}
		}

		$tr2 .= '</tr>';
		$tr3 .= '</tr>';
		return '<table class="mad">'.$tr1.$tr2.$tr3.'</table>'."\n";
	}

	/**
	 * Create most active people tables.
	 */
	private function makeTable_MostActivePeople($type, $rows)
	{
		if ($type == 'alltime') {
			$head = 'Most Active People, Alltime';
			$total = $this->l_total;
			$query = @mysqli_query($this->mysqli, 'SELECT `query_lines`.`RUID`, `csNick`, `l_total`, `l_night`, `l_morning`, `l_afternoon`, `l_evening`, `quote` FROM `query_lines` JOIN `user_details` ON `query_lines`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`RUID` = `user_status`.`UID` WHERE `status` != 3 ORDER BY `l_total` DESC, `query_lines`.`RUID` ASC LIMIT '.$rows) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		} elseif ($type == 'year') {
			$head = 'Most Active People, '.$this->year;
			$query = @mysqli_query($this->mysqli, 'SELECT SUM(`l_total`) AS `l_total` FROM `mview_activity_by_year` WHERE `date` = '.$this->year.' GROUP BY `date`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			$result = mysqli_fetch_object($query);
			$total = (int) $result->l_total;
			$query = @mysqli_query($this->mysqli, 'SELECT `query_lines`.`RUID`, `csNick`, SUM(`mview_activity_by_year`.`l_total`) AS `l_total`, SUM(`mview_activity_by_year`.`l_night`) AS `l_night`, SUM(`mview_activity_by_year`.`l_morning`) AS `l_morning`, SUM(`mview_activity_by_year`.`l_afternoon`) AS `l_afternoon`, SUM(`mview_activity_by_year`.`l_evening`) AS `l_evening`, `quote` FROM `query_lines` JOIN `mview_activity_by_year` ON `query_lines`.`RUID` = `mview_activity_by_year`.`RUID` JOIN `user_status` ON `query_lines`.`RUID` = `user_status`.`UID` JOIN `user_details` ON `query_lines`.`RUID` = `user_details`.`UID` WHERE `status` != 3 AND `date` = '.$this->year.' GROUP BY `query_lines`.`RUID` ORDER BY `mview_activity_by_year`.`l_total` DESC, `query_lines`.`RUID` ASC LIMIT '.$rows) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		} elseif ($type == 'month') {
			$head = 'Most Active People, '.$this->month_name.' '.$this->year;
			$query = @mysqli_query($this->mysqli, 'SELECT SUM(`l_total`) AS `l_total` FROM `mview_activity_by_month` WHERE `date` = \''.date('Y-m', mktime(0, 0, 0, $this->month, 1, $this->year)).'\' GROUP BY `date`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			$result = mysqli_fetch_object($query);
			$total = (int) $result->l_total;
			$query = @mysqli_query($this->mysqli, 'SELECT `query_lines`.`RUID`, `csNick`, SUM(`mview_activity_by_month`.`l_total`) AS `l_total`, SUM(`mview_activity_by_month`.`l_night`) AS `l_night`, SUM(`mview_activity_by_month`.`l_morning`) AS `l_morning`, SUM(`mview_activity_by_month`.`l_afternoon`) AS `l_afternoon`, SUM(`mview_activity_by_month`.`l_evening`) AS `l_evening`, `quote` FROM `query_lines` JOIN `mview_activity_by_month` ON `query_lines`.`RUID` = `mview_activity_by_month`.`RUID` JOIN `user_status` ON `query_lines`.`RUID` = `user_status`.`UID` JOIN `user_details` ON `query_lines`.`RUID` = `user_details`.`UID` WHERE `status` != 3 AND `date` = \''.date('Y-m', mktime(0, 0, 0, $this->month, 1, $this->year)).'\' GROUP BY `query_lines`.`RUID` ORDER BY `mview_activity_by_month`.`l_total` DESC, `query_lines`.`RUID` ASC LIMIT '.$rows) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		}

		if ($total == 0) {
			return;
		}

		$tr1 = '<tr><th colspan="7">'.$head.'</th></tr>';
		$tr2 = '<tr><td class="k1">Percentage</td><td class="k2">Lines</td><td class="pos"></td><td class="k3">User</td><td class="k4">When?</td><td class="k5">Last Seen</td><td class="k6">Quote</td></tr>';
		$trx = '';
		$i = 0;

		while ($result = mysqli_fetch_object($query)) {
			$i++;

			if ((int) $result->l_total == 0) {
				break;
			}

			$query_lastSeen = @mysqli_query($this->mysqli, 'SELECT MAX(`lastSeen`) AS `lastSeen` FROM `user_details` JOIN `user_status` ON `user_details`.`UID` = `user_status`.`UID` WHERE `RUID` = '.$result->RUID) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			$result_lastSeen = mysqli_fetch_object($query_lastSeen);
			$lastSeen = $this->dateTime2DaysAgo($result_lastSeen->lastSeen);
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

			$trx .= '<tr><td class="v1">'.number_format(((int) $result->l_total / $total) * 100, 2).'%</td><td class="v2">'.number_format((int) $result->l_total).'</td><td class="pos">'.$i.'</td><td class="v3">'.($this->userstats ? '<a href="user.php?uid='.$result->RUID.'">'.htmlspecialchars($result->csNick).'</a>' : htmlspecialchars($result->csNick)).'</td><td class="v4">'.$when.'</td><td class="v5">'.$lastSeen.'</td><td class="v6"><div>'.htmlspecialchars($result->quote).'</div></td></tr>';
		}

		return '<table class="map">'.$tr1.$tr2.$trx.'</table>'."\n";
	}

	/**
	 * Create the most active times table.
	 */
	private function makeTable_MostActiveTimes()
	{
		$query = @mysqli_query($this->mysqli, 'SELECT SUM(`l_00`) AS `l_00`, SUM(`l_01`) AS `l_01`, SUM(`l_02`) AS `l_02`, SUM(`l_03`) AS `l_03`, SUM(`l_04`) AS `l_04`, SUM(`l_05`) AS `l_05`, SUM(`l_06`) AS `l_06`, SUM(`l_07`) AS `l_07`, SUM(`l_08`) AS `l_08`, SUM(`l_09`) AS `l_09`, SUM(`l_10`) AS `l_10`, SUM(`l_11`) AS `l_11`, SUM(`l_12`) AS `l_12`, SUM(`l_13`) AS `l_13`, SUM(`l_14`) AS `l_14`, SUM(`l_15`) AS `l_15`, SUM(`l_16`) AS `l_16`, SUM(`l_17`) AS `l_17`, SUM(`l_18`) AS `l_18`, SUM(`l_19`) AS `l_19`, SUM(`l_20`) AS `l_20`, SUM(`l_21`) AS `l_21`, SUM(`l_22`) AS `l_22`, SUM(`l_23`) AS `l_23` FROM `channel`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			return;
		}

		$result = mysqli_fetch_object($query);
		$high_key = '';
		$high_value = 0;

		foreach ($result as $k => $v) {
			if ((int) $v > $high_value) {
				$high_key = $k;
				$high_value = (int) $v;
			}
		}

		$tr1 = '<tr><th colspan="24">Most Active Times</th></tr>';
		$tr2 = '<tr class="bars">';
		$tr3 = '<tr class="sub">';

		foreach ($result as $k => $v) {
			if (substr($k, -2, 1) == '0') {
				$hour = (int) substr($k, -1);
			} else {
				$hour = (int) substr($k, -2);
			}

			if ((int) $v == 0) {
				$tr2 .= '<td><span class="grey">n/a</span></td>';
			} else {
				$perc = ((int) $v / $this->l_total) * 100;

				if ($perc >= 9.95) {
					$tr2 .= '<td>'.round($perc).'%';
				} else {
					$tr2 .= '<td>'.number_format($perc, 1).'%';
				}

				$height = round(((int) $v / $high_value) * 100);

				if ($height != 0) {
					if ($hour >= 0 && $hour <= 5) {
						$tr2 .= '<img src="'.$this->bar_night.'" height="'.$height.'" alt="" title="'.number_format((int) $v).'" />';
					} elseif ($hour >= 6 && $hour <= 11) {
						$tr2 .= '<img src="'.$this->bar_morning.'" height="'.$height.'" alt="" title="'.number_format((int) $v).'" />';
					} elseif ($hour >= 12 && $hour <= 17) {
						$tr2 .= '<img src="'.$this->bar_afternoon.'" height="'.$height.'" alt="" title="'.number_format((int) $v).'" />';
					} elseif ($hour >= 18 && $hour <= 23) {
						$tr2 .= '<img src="'.$this->bar_evening.'" height="'.$height.'" alt="" title="'.number_format((int) $v).'" />';
					}
				}

				$tr2 .= '</td>';

				if ($high_key == $k) {
					$tr3 .= '<td class="bold">'.$hour.'h</td>';
				} else {
					$tr3 .= '<td>'.$hour.'h</td>';
				}
			}
		}

		$tr2 .= '</tr>';
		$tr3 .= '</tr>';
		return '<table class="graph">'.$tr1.$tr2.$tr3.'</table>'."\n";
	}

	/**
	 * Create the time of day table.
	 */
	private function makeTable_TimeOfDay($rows)
	{
		$high_value = 0;
		$times = array('night', 'morning', 'afternoon', 'evening');

		foreach ($times as $time) {
			$query = @mysqli_query($this->mysqli, 'SELECT `csNick`, `l_'.$time.'` FROM `query_lines` JOIN `user_details` ON `query_lines`.`RUID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`RUID` = `user_status`.`UID` WHERE `status` != 3 AND `l_'.$time.'` != 0 ORDER BY `l_'.$time.'` DESC, `csNick` ASC LIMIT '.$rows);
			$i = 0;

			while ($result = mysqli_fetch_object($query)) {
				$i++;
				${$time}[$i]['user'] = $result->csNick;
				${$time}[$i]['lines'] = (int) $result->{'l_'.$time};

				if (${$time}[$i]['lines'] > $high_value) {
					$high_value = ${$time}[$i]['lines'];
				}
			}
		}

		$tr1 = '<tr><th colspan="5">Activity, by Time of Day</th></tr>';
		$tr2 = '<tr><td class="pos"></td><td class="k">Nightcrawlers<br />0h - 5h</td><td class="k">Early Birds<br />6h - 11h</td><td class="k">Afternoon Shift<br />12h - 17h</td><td class="k">Evening Chatters<br />18h - 23h</td></tr>';
		$tr3 = '';

		for ($i = 1; $i <= $rows; $i++) {
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
							$tr3 .= '<td class="v">'.htmlspecialchars(${$time}[$i]['user']).' - '.number_format(${$time}[$i]['lines']).'<br /><img src="'.$this->{'bar_'.$time}.'" width="'.$width.'" alt="" /></td>';
						} else {
							$tr3 .= '<td class="v">'.htmlspecialchars(${$time}[$i]['user']).' - '.number_format(${$time}[$i]['lines']).'<br />&nbsp;</td>';
						}
					}
				}

				$tr3 .= '</tr>';
			}
		}

		return '<table class="tod">'.$tr1.$tr2.$tr3.'</table>'."\n";
	}
}

/**
 * Class for creating a small or large generic table.
 */
final class Table extends Base
{
	protected $decimals = 0;
	protected $head = '';
	protected $key1 = '';
	protected $key2 = '';
	protected $key3 = '';
	protected $minRows = 3;
	protected $percentage = FALSE;
	protected $query_main = '';
	protected $query_total = '';
	protected $rows = 5;
	protected $type = 'small';
	protected $total = 0;

	public function __construct($head)
	{
		$this->head = $head;
	}

	public function makeTable($mysqli)
	{
		/**
		 * Fetch data from db.
		 */
		if (empty($this->query_main)) {
			return;
		}

		$query = @mysqli_query($mysqli, $this->query_main) or $this->output('critical', 'MySQLi: '.mysqli_error($mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows) || $rows < $this->minRows) {
			return;
		}

		/**
		 * Put the results into $content[].
		 */
		$i = 0;

		while ($result = mysqli_fetch_object($query)) {
			$i++;

			if  ($i > $this->rows) {
				break;
			}

			if ($this->type == 'small') {
				$content[] = array($i, number_format((float) $result->v1, $this->decimals).($this->percentage ? '%' : ''), htmlspecialchars($result->v2));
			} elseif ($this->type == 'large') {
				$content[] = array($i, number_format((float) $result->v1, $this->decimals).($this->percentage ? '%' : ''), htmlspecialchars($result->v2), htmlspecialchars($result->v3));
			} elseif ($this->type == 'topics' || $this->type == 'URLs') {
				$content[] = array($i, date('j M \'y', strtotime($result->v1)), htmlspecialchars($result->v2), htmlspecialchars($result->v3));
			}
		}

		/**
		 * Fill $content[] with empty values to reach desired amount of rows for display.
		 */
		for ($i = count($content) + 1; $i <= $this->rows; $i++) {
			if ($this->type == 'small') {
				$content[] = array('&nbsp;', '', '');
			} else {
				$content[] = array('&nbsp;', '', '', '');
			}
		}

		/**
		 * If there is a query provided to fetch the total value for this table we process it here.
		 */
		if (!empty($this->query_total)) {
			$query = @mysqli_query($mysqli, $this->query_total) or $this->output('critical', 'MySQLi: '.mysqli_error($mysqli));
			$result = mysqli_fetch_object($query);
			$this->total = (int) $result->total;
		}

		/**
		 * Finally put everything together and return the table.
		 */
		if ($this->type == 'small') {
			$tr1 = '<tr><th colspan="3"><span class="left">'.htmlspecialchars($this->head).'</span>'.($this->total == 0 ? '' : '<span class="right">'.number_format($this->total).' total</span>').'</th></tr>';
			$tr2 = '<tr><td class="k1">'.htmlspecialchars($this->key1).'</td><td class="pos"></td><td class="k2">'.htmlspecialchars($this->key2).'</td></tr>';
			$trx = '';
		} else {
			$tr1 = '<tr><th colspan="4"><span class="left">'.htmlspecialchars($this->head).'</span>'.($this->total == 0 ? '' : '<span class="right">'.number_format($this->total).' total</span>').'</th></tr>';
			$tr2 = '<tr><td class="k1">'.htmlspecialchars($this->key1).'</td><td class="pos"></td><td class="k2">'.htmlspecialchars($this->key2).'</td><td class="k3">'.htmlspecialchars($this->key3).'</td></tr>';
			$trx = '';
		}

		if ($this->type == 'small') {
			foreach ($content as $row) {
				$trx .= '<tr><td class="v1">'.$row[1].'</td><td class="pos">'.$row[0].'</td><td class="v2">'.$row[2].'</td></tr>';
			}
		} elseif ($this->type == 'large') {
			foreach ($content as $row) {
				$trx .= '<tr><td class="v1">'.$row[1].'</td><td class="pos">'.$row[0].'</td><td class="v2">'.$row[2].'</td><td class="v3"><div>'.$row[3].'</div></td></tr>';
			}
		} elseif ($this->type == 'topics') {
			$prevDate = '';

			foreach ($content as $row) {
				$trx .= '<tr><td class="v1">'.($row[1] != $prevDate ? $row[1] : '').'</td><td class="pos">'.$row[0].'</td><td class="v2">'.$row[2].'</td><td class="v3"><div>'.$row[3].'</div></td></tr>';
				$prevDate = $row[1];
			}
		} elseif ($this->type == 'URLs') {
			$prevDate = '';

			foreach ($content as $row) {
				// TODO: <div> inside <a> doesn't validate! other way around breaks ellipsis..
				$trx .= '<tr><td class="v1">'.($row[1] != $prevDate ? $row[1] : '').'</td><td class="pos">'.$row[0].'</td><td class="v2">'.$row[2].'</td><td class="v3"><a href="'.$row[3].'"><div>'.$row[3].'</div></a></td></tr>';
				$prevDate = $row[1];
			}
		}

		if ($this->type == 'small') {
			return '<table class="small">'.$tr1.$tr2.$trx.'</table>'."\n";
		} else {
			return '<table class="large">'.$tr1.$tr2.$trx.'</table>'."\n";
		}
	}
}

?>
