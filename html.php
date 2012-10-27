<?php

/**
 * Copyright (c) 2007-2012, Jos de Ruijter <jos@dutnie.nl>
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
final class html extends base
{
	/**
	 * Default settings for this script, can be overridden in the config file. These should all appear in $settings_list[] along with their type.
	 */
	private $addhtml_foot = '';
	private $addhtml_head = '';
	private $channel = '';
	private $cid = '';
	private $history = false;
	private $maxrows_people_alltime = 30;
	private $maxrows_people2 = 10;
	private $maxrows_people_month = 10;
	private $maxrows_people_timeofday = 10;
	private $maxrows_people_year = 10;
	private $maxrows_recenturls = 25;
	private $minrows = 3;
	private $sectionbits = 127;
	private $stylesheet = 'sss.css';
	private $userstats = false;

	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $currentyear = 0;
	private $date_first = '';
	private $date_last = '';
	private $date_lastlogparsed = '';
	private $date_max = '';
	private $days = 0;
	private $dayofmonth = 0;
	private $daysleft = 0;
	private $estimate = false;
	private $l_avg = 0;
	private $l_max = 0;
	private $l_total = 0;
	private $month = 0;
	private $monthname = '';
	private $mysqli;
	private $output = '';
	private $settings_list = array(
		'addhtml_foot' => 'string',
		'addhtml_head' => 'string',
		'channel' => 'string',
		'cid' => 'string',
		'history' => 'bool',
		'maxrows_people2' => 'int',
		'maxrows_people_alltime' => 'int',
		'maxrows_people_month' => 'int',
		'maxrows_people_timeofday' => 'int',
		'maxrows_people_year' => 'int',
		'maxrows_recenturls' => 'int',
		'minrows' => 'int',
		'outputbits' => 'int',
		'sectionbits' => 'int',
		'stylesheet' => 'string',
		'userstats' => 'bool');
	private $year = 0;
	private $years = 0;

	public function __construct($settings)
	{
		/**
		 * The variables that are listed in $settings_list will have their values overridden by those found in the config file.
		 */
		foreach ($this->settings_list as $key => $type) {
			if (!array_key_exists($key, $settings)) {
				continue;
			}

			if ($type == 'string') {
				$this->$key = $settings[$key];
			} elseif ($type == 'int') {
				$this->$key = (int) $settings[$key];
			} elseif ($type == 'bool') {
				if (strtolower($settings[$key]) == 'true') {
					$this->$key = true;
				} elseif (strtolower($settings[$key]) == 'false') {
					$this->$key = false;
				}
			}
		}

		/**
		 * If $cid has no value we use the value of $channel for it.
		 */
		if ($this->cid == '') {
			$this->cid = $this->channel;
		}
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

	/**
	 * Generate the HTML page.
	 */
	public function make_html($mysqli)
	{
		$this->mysqli = $mysqli;
		$this->output('notice', 'make_html(): creating statspage');
		$query = @mysqli_query($this->mysqli, 'select sum(`l_total`) as `l_total` from `channel`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (!empty($rows)) {
			$result = mysqli_fetch_object($query);
		}

		/**
		 * Exit if the channel has no logged activity. Most functions don't expect to be run on an empty database so keep this check in place.
		 */
		if (empty($result->l_total)) {
			$this->output('warning', 'make_html(): database is empty, nothing to do');
			return 'No data.';
		}

		$this->l_total = (int) $result->l_total;
		$query = @mysqli_query($this->mysqli, 'select min(`date`) as `date_first`, max(`date`) as `date_last` from `channel`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$result = mysqli_fetch_object($query);
		$this->date_first = $result->date_first;
		$this->date_last = $result->date_last;
		$query = @mysqli_query($this->mysqli, 'select count(*) as `days`, max(`date`) as `date` from `parse_history`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$result = mysqli_fetch_object($query);
		$this->days = (int) $result->days;
		$this->l_avg = $this->l_total / $this->days;

		/**
		 * Date and time variables used throughout the script. These are based on the date of the last logfile parsed and used to define our scope.
		 */
		$this->date_lastlogparsed = $result->date;
		$this->dayofmonth = (int) date('j', strtotime($this->date_lastlogparsed));
		$this->month = (int) date('n', strtotime($this->date_lastlogparsed));
		$this->monthname = date('F', strtotime($this->date_lastlogparsed));
		$this->year = (int) date('Y', strtotime($this->date_lastlogparsed));
		$this->years = $this->year - (int) date('Y', strtotime($this->date_first)) + 1;
		$this->daysleft = (int) date('z', strtotime('last day of December '.$this->year)) - (int) date('z', strtotime($this->date_lastlogparsed));
		$this->currentyear = (int) date('Y');

		/**
		 * If we have less than 3 years of data we set the amount of years to 3 so we have that many columns in our table. Looks better.
		 */
		if ($this->years < 3) {
			$this->years = 3;
		}

		/**
		 * If there are still days ahead of us in the current year, we try to calculate an estimated line count and display it in an additional column.
		 * Don't forget to add another 34px to the table width, a bit further down in the html head.
		 */
		if ($this->daysleft != 0 && $this->year == $this->currentyear) {
			/**
			 * We base our calculations on the activity of the last 90 days logged. If there is none we won't display the extra column.
			 */
			$query = @mysqli_query($this->mysqli, 'select count(*) as `activity` from `q_activity_by_day` where `date` > \''.date('Y-m-d', mktime(0, 0, 0, $this->month, $this->dayofmonth - 90, $this->year)).'\'') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$result = mysqli_fetch_object($query);

			if (!empty($result->activity)) {
				$this->estimate = true;
			}
		}

		/**
		 * HTML Head.
		 */
		$query = @mysqli_query($this->mysqli, 'select min(`date`) as `date_max`, `l_total` as `l_max` from `channel` where `l_total` = (select max(`l_total`) from `channel`)') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$result = mysqli_fetch_object($query);
		$this->date_max = $result->date_max;
		$this->l_max = (int) $result->l_max;
		$this->output = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">'."\n\n"
			      . '<head>'."\n".'<title>'.htmlspecialchars($this->channel).', seriously.</title>'."\n"
			      . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'."\n"
			      . '<meta http-equiv="Content-Style-Type" content="text/css">'."\n"
			      . '<link rel="stylesheet" type="text/css" href="'.$this->stylesheet.'">'."\n"
			      . '<style type="text/css">'."\n"
			      . '  .act-year {width:'.(2 + (($this->years + ($this->estimate ? 1 : 0)) * 34)).'px}'."\n"
			      . '</style>'."\n"
			      . '</head>'."\n\n".'<body>'."\n"
			      . '<div class="box">'."\n"
			      . "\n".'<div class="info">'.htmlspecialchars($this->channel).', seriously.<br><br>'.number_format($this->days).' day'.($this->days > 1 ? 's logged from '.date('M j, Y', strtotime($this->date_first)).' to '.date('M j, Y', strtotime($this->date_last)) : ' logged on '.date('M j, Y', strtotime($this->date_first))).'.<br>'
			      . '<br>Logs contain '.number_format($this->l_total).' line'.($this->l_total > 1 ? 's' : '').' &ndash; an average of '.number_format($this->l_avg).' line'.($this->l_avg > 1 ? 's' : '').' per day.<br>Most active day was '.date('M j, Y', strtotime($this->date_max)).' with a total of '.number_format($this->l_max).' line'.($this->l_max > 1 ? 's' : '').' typed.'.($this->addhtml_head != '' ? '<br><br>'.trim(@file_get_contents($this->addhtml_head)) : '').'</div>'."\n";

		/**
		 * Activity section.
		 */
		if ($this->sectionbits & 1) {
			$this->output .= "\n".'<div class="head">Activity</div>'."\n";
			$this->output .= $this->make_table_activity_distribution_hour();
			$this->output .= $this->make_table_activity('day');
			$this->output .= $this->make_table_activity('month');
			$this->output .= $this->make_table_activity_distribution_day();
			$this->output .= $this->make_table_activity('year');
			$this->output .= $this->make_table_people('alltime');
			$this->output .= $this->make_table_people2();
			$this->output .= $this->make_table_people('year');
			$this->output .= $this->make_table_people('month');
			$this->output .= $this->make_table_people_timeofday();
		}

		/**
		 * General Chat section.
		 */
		if ($this->sectionbits & 2) {
			$output = '';

			$t = new table('Most Talkative Chatters');
			$t->set_value('decimals', 1);
			$t->set_value('key1', 'Lines/Day');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select (`l_total` / `activedays`) as `v1`, `csnick` as `v2` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `activedays` >= 7 and `lasttalked` >= \''.date('Y-m-d', mktime(0, 0, 0, $this->month, $this->dayofmonth - 30, $this->year)).'\' order by `v1` desc, `v2` asc limit 5');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Most Fluent Chatters');
			$t->set_value('decimals', 1);
			$t->set_value('key1', 'Words/Line');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select (`words` / `l_total`) as `v1`, `csnick` as `v2` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `activedays` >= 7 and `lasttalked` >= \''.date('Y-m-d', mktime(0, 0, 0, $this->month, $this->dayofmonth - 30, $this->year)).'\' order by `v1` desc, `v2` asc limit 5');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Most Tedious Chatters');
			$t->set_value('decimals', 1);
			$t->set_value('key1', 'Chars/Line');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select (`characters` / `l_total`) as `v1`, `csnick` as `v2` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `activedays` >= 7 and `lasttalked` >= \''.date('Y-m-d', mktime(0, 0, 0, $this->month, $this->dayofmonth - 30, $this->year)).'\' order by `v1` desc, `v2` asc limit 5');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Individual Top Days &ndash; Alltime');
			$t->set_value('key1', 'Lines');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select max(`l_total`) as `v1`, `csnick` as `v2` from `q_activity_by_day` join `user_status` on `q_activity_by_day`.`ruid` = `user_status`.`uid` join `user_details` on `q_activity_by_day`.`ruid` = `user_details`.`uid` where `status` != 3 group by `q_activity_by_day`.`ruid` order by `v1` desc, `v2` asc limit 5');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Individual Top Days &ndash; '.$this->year);
			$t->set_value('key1', 'Lines');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select max(`l_total`) as `v1`, `csnick` as `v2` from `q_activity_by_day` join `user_status` on `q_activity_by_day`.`ruid` = `user_status`.`uid` join `user_details` on `q_activity_by_day`.`ruid` = `user_details`.`uid` where `status` != 3 and year(`date`) = \''.$this->year.'\' group by `q_activity_by_day`.`ruid` order by `v1` desc, `v2` asc limit 5');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Individual Top Days &ndash; '.$this->monthname.' '.$this->year);
			$t->set_value('key1', 'Lines');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select max(`l_total`) as `v1`, `csnick` as `v2` from `q_activity_by_day` join `user_status` on `q_activity_by_day`.`ruid` = `user_status`.`uid` join `user_details` on `q_activity_by_day`.`ruid` = `user_details`.`uid` where `status` != 3 and date_format(`date`, \'%Y-%m\') = \''.date('Y-m', strtotime($this->date_lastlogparsed)).'\' group by `q_activity_by_day`.`ruid` order by `v1` desc, `v2` asc limit 5');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Most Active Chatters &ndash; Alltime');
			$t->set_value('decimals', 2);
			$t->set_value('key1', 'Activity');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('percentage', true);
			$t->set_value('query_main', 'select (`activedays` / '.$this->days.') * 100 as `v1`, `csnick` as `v2` from `user_status` join `q_lines` on `user_status`.`uid` = `q_lines`.`ruid` join `user_details` on `user_status`.`uid` = `user_details`.`uid` where `status` != 3 order by `v1` desc, `v2` asc limit 5');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Most Active Chatters &ndash; '.$this->year);
			$t->set_value('decimals', 2);
			$t->set_value('key1', 'Activity');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('percentage', true);
			$t->set_value('query_main', 'select (count(distinct `date`) / (select count(*) from parse_history where year(`date`) = '.$this->year.')) * 100 as `v1`, `csnick` as `v2` from `q_activity_by_day` join `user_status` on `q_activity_by_day`.`ruid` = `user_status`.`uid` join `user_details` on `q_activity_by_day`.`ruid` = `user_details`.`uid` where `status` != 3 and year(`date`) = '.$this->year.' group by `q_activity_by_day`.`ruid` order by `v1` desc, `v2` asc limit 5');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Most Active Chatters &ndash; '.$this->monthname.' '.$this->year);
			$t->set_value('decimals', 2);
			$t->set_value('key1', 'Activity');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('percentage', true);
			$t->set_value('query_main', 'select (count(distinct `date`) / (select count(*) from parse_history where date_format(`date`, \'%Y-%m\') = \''.date('Y-m', strtotime($this->date_lastlogparsed)).'\')) * 100 as `v1`, `csnick` as `v2` from `q_activity_by_day` join `user_status` on `q_activity_by_day`.`ruid` = `user_status`.`uid` join `user_details` on `q_activity_by_day`.`ruid` = `user_details`.`uid` where `status` != 3 and date_format(`date`, \'%Y-%m\') = \''.date('Y-m', strtotime($this->date_lastlogparsed)).'\' group by `q_activity_by_day`.`ruid` order by `v1` desc, `v2` asc limit 5');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Exclamations');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('key3', 'Example');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `exclamations` as `v1`, `csnick` as `v2`, `ex_exclamations` as `v3` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `exclamations` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`exclamations`) as `total` from `q_lines`');
			$t->set_value('type', 'large');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Questions');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('key3', 'Example');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `questions` as `v1`, `csnick` as `v2`, `ex_questions` as `v3` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `questions` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`questions`) as `total` from `q_lines`');
			$t->set_value('type', 'large');
			$output .= $t->make_table($this->mysqli);

			$t = new table('UPPERCASED Lines');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('key3', 'Example');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `uppercased` as `v1`, `csnick` as `v2`, `ex_uppercased` as `v3` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `uppercased` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`uppercased`) as `total` from `q_lines`');
			$t->set_value('type', 'large');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Monologues');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `monologues` as `v1`, `csnick` as `v2` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `monologues` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`monologues`) as `total` from `q_lines`');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Longest Monologue');
			$t->set_value('key1', 'Lines');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `topmonologue` as `v1`, `csnick` as `v2` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `topmonologue` != 0 order by `v1` desc, `v2` asc limit 5');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Moodiest People');
			$t->set_value('key1', 'Smileys');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select (`s_01` + `s_02` + `s_03` + `s_04` + `s_05` + `s_06` + `s_07` + `s_08` + `s_09` + `s_10` + `s_11` + `s_12` + `s_13` + `s_14` + `s_15` + `s_16` + `s_17` + `s_18` + `s_19` + `s_20` + `s_21` + `s_22` + `s_23` + `s_24` + `s_25` + `s_26` + `s_27` + `s_28` + `s_29` + `s_30` + `s_31` + `s_32` + `s_33` + `s_34` + `s_35` + `s_36` + `s_37` + `s_38` + `s_39` + `s_40` + `s_41` + `s_42` + `s_43` + `s_44` + `s_45` + `s_46` + `s_47` + `s_48` + `s_49` + `s_50`) as `v1`, `csnick` as `v2` from `q_smileys` join `user_details` on `q_smileys`.`ruid` = `user_details`.`uid` join `user_status` on `q_smileys`.`ruid` = `user_status`.`uid` where `status` != 3 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select (sum(`s_01`) + sum(`s_02`) + sum(`s_03`) + sum(`s_04`) + sum(`s_05`) + sum(`s_06`) + sum(`s_07`) + sum(`s_08`) + sum(`s_09`) + sum(`s_10`) + sum(`s_11`) + sum(`s_12`) + sum(`s_13`) + sum(`s_14`) + sum(`s_15`) + sum(`s_16`) + sum(`s_17`) + sum(`s_18`) + sum(`s_19`) + sum(`s_20`) + sum(`s_21`) + sum(`s_22`) + sum(`s_23`) + sum(`s_24`) + sum(`s_25`) + sum(`s_26`) + sum(`s_27`) + sum(`s_28`) + sum(`s_29`) + sum(`s_30`) + sum(`s_31`) + sum(`s_32`) + sum(`s_33`) + sum(`s_34`) + sum(`s_35`) + sum(`s_36`) + sum(`s_37`) + sum(`s_38`) + sum(`s_39`) + sum(`s_40`) + sum(`s_41`) + sum(`s_42`) + sum(`s_43`) + sum(`s_44`) + sum(`s_45`) + sum(`s_46`) + sum(`s_47`) + sum(`s_48`) + sum(`s_49`) + sum(`s_50`)) as `total` from `q_smileys`');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Slaps Given');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `slaps` as `v1`, `csnick` as `v2` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `slaps` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`slaps`) as `total` from `q_lines`');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Slaps Received');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `slapped` as `v1`, `csnick` as `v2` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `slapped` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`slapped`) as `total` from `q_lines`');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Most Lively Bots');
			$t->set_value('key1', 'Lines');
			$t->set_value('key2', 'Bot');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `l_total` as `v1`, `csnick` as `v2` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` = 3 and `l_total` != 0 order by `v1` desc, `v2` asc limit 5');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Actions Performed');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('key3', 'Example');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `actions` as `v1`, `csnick` as `v2`, `ex_actions` as `v3` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `actions` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`actions`) as `total` from `q_lines`');
			$t->set_value('type', 'large');
			$output .= $t->make_table($this->mysqli);

			if (!empty($output)) {
				$this->output .= "\n".'<div class="head">General Chat</div>'."\n".$output;
			}
		}

		/**
		 * Modes section.
		 */
		if ($this->sectionbits & 4) {
			$output = '';

			/**
			 * Display mode tables in fixed order.
			 */
			$modes = array(
				'm_op' => array('Total', 'Ops \'+o\' Given'),
				'm_opped' => array('Total', 'Ops \'+o\' Received'),
				'm_deop' => array('Total', 'deOps \'-o\' Given'),
				'm_deopped' => array('Total', 'deOps \'-o\' Received'),
				'm_voice' => array('Total', 'Voices \'+v\' Given'),
				'm_voiced' => array('Total', 'Voices \'+v\' Received'),
				'm_devoice' => array('Total', 'deVoices \'-v\' Given'),
				'm_devoiced' => array('Total', 'deVoices \'-v\' Received'));

			foreach ($modes as $key => $value) {
				$t = new table($value[1]);
				$t->set_value('key1', $value[0]);
				$t->set_value('key2', 'User');
				$t->set_value('minrows', $this->minrows);
				$t->set_value('query_main', 'select `'.$key.'` as `v1`, `csnick` as `v2` from `q_events` join `user_details` on `q_events`.`ruid` = `user_details`.`uid` join `user_status` on `q_events`.`ruid` = `user_status`.`uid` where `status` != 3 and `'.$key.'` != 0 order by `v1` desc, `v2` asc limit 5');
				$t->set_value('query_total', 'select sum(`'.$key.'`) as `total` from `q_events`');
				$output .= $t->make_table($this->mysqli);
			}

			if (!empty($output)) {
				$this->output .= "\n".'<div class="head">Modes</div>'."\n".$output;
			}
		}

		/**
		 * Events section.
		 */
		if ($this->sectionbits & 8) {
			$output = '';

			$t = new table('Kicks Given');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('key3', 'Example');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `kicks` as `v1`, `csnick` as `v2`, `ex_kicks` as `v3` from `q_events` join `user_details` on `q_events`.`ruid` = `user_details`.`uid` join `user_status` on `q_events`.`ruid` = `user_status`.`uid` where `status` != 3 and `kicks` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`kicks`) as `total` from `q_events`');
			$t->set_value('type', 'large');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Kicks Received');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('key3', 'Example');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `kicked` as `v1`, `csnick` as `v2`, `ex_kicked` as `v3` from `q_events` join `user_details` on `q_events`.`ruid` = `user_details`.`uid` join `user_status` on `q_events`.`ruid` = `user_status`.`uid` where `status` != 3 and `kicked` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`kicked`) as `total` from `q_events`');
			$t->set_value('type', 'large');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Channel Joins');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `joins` as `v1`, `csnick` as `v2` from `q_events` join `user_details` on `q_events`.`ruid` = `user_details`.`uid` join `user_status` on `q_events`.`ruid` = `user_status`.`uid` where `status` != 3 and `joins` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`joins`) as `total` from `q_events`');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Channel Parts');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `parts` as `v1`, `csnick` as `v2` from `q_events` join `user_details` on `q_events`.`ruid` = `user_details`.`uid` join `user_status` on `q_events`.`ruid` = `user_status`.`uid` where `status` != 3 and `parts` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`parts`) as `total` from `q_events`');
			$output .= $t->make_table($this->mysqli);

			$t = new table('IRC Quits');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `quits` as `v1`, `csnick` as `v2` from `q_events` join `user_details` on `q_events`.`ruid` = `user_details`.`uid` join `user_status` on `q_events`.`ruid` = `user_status`.`uid` where `status` != 3 and `quits` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`quits`) as `total` from `q_events`');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Nick Changes');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `nickchanges` as `v1`, `csnick` as `v2` from `q_events` join `user_details` on `q_events`.`ruid` = `user_details`.`uid` join `user_status` on `q_events`.`ruid` = `user_status`.`uid` where `status` != 3 and `nickchanges` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`nickchanges`) as `total` from `q_events`');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Aliases');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select count(*) as `v1`, `csnick` as `v2` from `user_details` join `user_status` on `user_details`.`uid` = `user_status`.`uid` where `status` != 3 group by `ruid` having `v1` > 1 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select count(*) as `total` from `user_status`');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Topics Set');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `topics` as `v1`, `csnick` as `v2` from `q_events` join `user_details` on `q_events`.`ruid` = `user_details`.`uid` join `user_status` on `q_events`.`ruid` = `user_status`.`uid` where `status` != 3 and `topics` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`topics`) as `total` from `q_events`');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Recent Topics');
			$t->set_value('key1', 'Date');
			$t->set_value('key2', 'User');
			$t->set_value('key3', 'Topic');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `datetime` as `v1`, `csnick` as `v2`, `topic` as `v3` from `user_topics` join `topics` on `user_topics`.`tid` = `topics`.`tid` join `user_status` on `user_topics`.`uid` = `user_status`.`uid` join `user_details` on `user_status`.`ruid` = `user_details`.`uid` order by `v1` desc limit 5');
			$t->set_value('type', 'topics');
			$output .= $t->make_table($this->mysqli);

			if (!empty($output)) {
				$this->output .= "\n".'<div class="head">Events</div>'."\n".$output;
			}
		}

		/**
		 * Smileys section.
		 */
		if ($this->sectionbits & 16) {
			$output = '';

			/**
			 * All the smileys and their info text.
			 */
			$smileys = array(
				's_01' => array(':)', 'Happy'),
				's_02' => array(';)', 'Wink'),
				's_03' => array(':(', 'Sad'),
				's_04' => array(':P', 'Tongue Sticking Out'),
				's_05' => array(':D', 'Laugh'),
				's_06' => array(';(', 'Cry'),
				's_07' => array(':/', 'Skeptical'),
				's_08' => array('\\o/', 'Cheer'),
				's_09' => array(':))', 'Super Happy'),
				's_10' => array('<3', 'Love'),
				's_11' => array(':o', 'Surprised'),
				's_12' => array('=)', 'Cheerful Smile'),
				's_13' => array(':-)', 'Classic Happy'),
				's_14' => array(':x', 'Kiss'),
				's_15' => array(':\\', 'Skeptical'),
				's_16' => array('D:', 'Shocked'),
				's_17' => array(':|', 'Straight Face'),
				's_18' => array(';-)', 'Classic Wink'),
				's_19' => array(';P', 'Silly'),
				's_20' => array('=]', 'Big Cheerful Smile'),
				's_21' => array(':3', 'Kitty'),
				's_22' => array('8)', 'Cool Smile'),
				's_23' => array(':<', 'Sad'),
				's_24' => array(':>', 'Happy Smile'),
				's_25' => array('=P', 'Funny Face'),
				's_26' => array(';x', 'Lovely Kiss'),
				's_27' => array(':-D', 'Classic Laugh'),
				's_28' => array(';))', 'Extreme Wink'),
				's_29' => array(':]', 'Big Smile'),
				's_30' => array(';D', 'Winking Laugh'),
				's_31' => array('-_-', 'Not Amused'),
				's_32' => array(':S', 'Confused'),
				's_33' => array('=/', 'Skeptical'),
				's_34' => array('=\\', 'Skeptical'),
				's_35' => array(':((', 'Super Sad'),
				's_36' => array('=D', 'Cheerful Laugh'),
				's_37' => array(':-/', 'Classic Skeptical'),
				's_38' => array(':-P', 'Classic Tongue Sticking Out'),
				's_39' => array(';_;', 'Crying'),
				's_40' => array(';/', '...'),
				's_41' => array(';]', 'Big Wink'),
				's_42' => array(':-(', 'Classic Sad'),
				's_43' => array(':\'(', 'Tear'),
				's_44' => array('=(', 'Sad'),
				's_45' => array('-.-', 'Not Amused'),
				's_46' => array(';((', 'Crying'),
				's_47' => array('=X', 'Kiss'),
				's_48' => array(':[', 'Sad'),
				's_49' => array('>:(', 'Angry'),
				's_50' => array(';o', 'Joking'));

			/**
			 * Display the top 9 smiley tables ordered by totals.
			 */
			$query = @mysqli_query($this->mysqli, 'select sum(`s_01`) as `s_01`, sum(`s_02`) as `s_02`, sum(`s_03`) as `s_03`, sum(`s_04`) as `s_04`, sum(`s_05`) as `s_05`, sum(`s_06`) as `s_06`, sum(`s_07`) as `s_07`, sum(`s_08`) as `s_08`, sum(`s_09`) as `s_09`, sum(`s_10`) as `s_10`, sum(`s_11`) as `s_11`, sum(`s_12`) as `s_12`, sum(`s_13`) as `s_13`, sum(`s_14`) as `s_14`, sum(`s_15`) as `s_15`, sum(`s_16`) as `s_16`, sum(`s_17`) as `s_17`, sum(`s_18`) as `s_18`, sum(`s_19`) as `s_19`, sum(`s_20`) as `s_20`, sum(`s_21`) as `s_21`, sum(`s_22`) as `s_22`, sum(`s_23`) as `s_23`, sum(`s_24`) as `s_24`, sum(`s_25`) as `s_25`, sum(`s_26`) as `s_26`, sum(`s_27`) as `s_27`, sum(`s_28`) as `s_28`, sum(`s_29`) as `s_29`, sum(`s_30`) as `s_30`, sum(`s_31`) as `s_31`, sum(`s_32`) as `s_32`, sum(`s_33`) as `s_33`, sum(`s_34`) as `s_34`, sum(`s_35`) as `s_35`, sum(`s_36`) as `s_36`, sum(`s_37`) as `s_37`, sum(`s_38`) as `s_38`, sum(`s_39`) as `s_39`, sum(`s_40`) as `s_40`, sum(`s_41`) as `s_41`, sum(`s_42`) as `s_42`, sum(`s_43`) as `s_43`, sum(`s_44`) as `s_44`, sum(`s_45`) as `s_45`, sum(`s_46`) as `s_46`, sum(`s_47`) as `s_47`, sum(`s_48`) as `s_48`, sum(`s_49`) as `s_49`, sum(`s_50`) as `s_50` from `q_smileys`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query);

			if (!empty($rows)) {
				$result = mysqli_fetch_object($query);

				foreach ($result as $key => $value) {
					if (!empty($value)) {
						$smileys_totals[$key] = (int) $value;
					}
				}

				if (!empty($smileys_totals)) {
					arsort($smileys_totals);
					array_splice($smileys_totals, 9);

					foreach ($smileys_totals as $key => $value) {
						$t = new table($smileys[$key][1]);
						$t->set_value('key1', htmlspecialchars($smileys[$key][0]));
						$t->set_value('key2', 'User');
						$t->set_value('minrows', $this->minrows);
						$t->set_value('query_main', 'select `'.$key.'` as `v1`, `csnick` as `v2` from `q_smileys` join `user_details` on `q_smileys`.`ruid` = `user_details`.`uid` join `user_status` on `q_smileys`.`ruid` = `user_status`.`uid` where `status` != 3 and `'.$key.'` != 0 order by `v1` desc, `v2` asc limit 5');
						$t->set_value('total', $value);
						$output .= $t->make_table($this->mysqli);
					}
				}

				if (!empty($output)) {
					$this->output .= "\n".'<div class="head">Smileys</div>'."\n".$output;
				}
			}
		}

		/**
		 * URLs section.
		 */
		if ($this->sectionbits & 32) {
			$output = '';

			$t = new table('Most Referenced Domain Names');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'Domain');
			$t->set_value('key3', 'First Used');
			$t->set_value('maxrows', 10);
			$t->set_value('minrows', 10);
			$t->set_value('query_main', 'select count(*) as `v1`, (select concat(\'http://\', `fqdn`) from `fqdns` where `fid` = `urls`.`fid`) as `v2`, min(`datetime`) as `v3` from `user_urls` join `urls` on `user_urls`.`lid` = `urls`.`lid` where `fid` is not null group by `fid` order by `v1` desc, `v3` asc limit 10');
			$t->set_value('type', 'domains');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Most Referenced TLDs');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'TLD');
			$t->set_value('maxrows', 10);
			$t->set_value('minrows', 10);
			$t->set_value('query_main', 'select count(*) as `v1`, `tld` as `v2` from `user_urls` join `urls` on `user_urls`.`lid` = `urls`.`lid` where `tld` != \'\' group by `tld` order by `v1` desc, `v2` asc limit 10');
			$output .= $t->make_table($this->mysqli);

			$t = new table('Recent URLs');
			$t->set_value('key1', 'Date');
			$t->set_value('key2', 'User');
			$t->set_value('key3', 'URL');
			$t->set_value('maxrows', $this->maxrows_recenturls);
			$t->set_value('minrows', 5);
			$t->set_value('query_main', 'select `datetime` as `v1`, `csnick` as `v2`, `url` as `v3` from `user_urls` join `urls` on `user_urls`.`lid` = `urls`.`lid` join `user_status` on `user_urls`.`uid` = `user_status`.`uid` join `user_details` on `user_status`.`ruid` = `user_details`.`uid` order by `v1` desc limit '.$this->maxrows_recenturls);
			$t->set_value('type', 'urls');
			$output .= $t->make_table($this->mysqli);

			$t = new table('URLs by Users');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `urls` as `v1`, `csnick` as `v2` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `urls` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`urls`) as `total` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3');
			$output .= $t->make_table($this->mysqli);

			$t = new table('URLs by Bots');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'Bot');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `urls` as `v1`, `csnick` as `v2` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` = 3 and `urls` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`urls`) as `total` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` = 3');
			$output .= $t->make_table($this->mysqli);

			if (!empty($output)) {
				$this->output .= "\n".'<div class="head">URLs</div>'."\n".$output;
			}
		}

		/**
		 * Words section.
		 */
		if ($this->sectionbits & 64) {
			$output = '';

			/**
			 * Display the top 9 word tables ordered by totals.
			 */
			$query = @mysqli_query($this->mysqli, 'select `length`, count(*) as `total` from `words` group by `length` order by `total` desc, `length` desc limit 9') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query);

			if (!empty($rows)) {
				while ($result = mysqli_fetch_object($query)) {
					$t = new table('Words of '.$result->length.' Characters');
					$t->set_value('key1', 'Times Used');
					$t->set_value('key2', 'Word');
					$t->set_value('minrows', $this->minrows);
					$t->set_value('query_main', 'select `total` as `v1`, `word` as `v2` from `words` where `length` = '.$result->length.' order by `v1` desc, `v2` asc limit 5');
					$t->set_value('total', (int) $result->total);
					$output .= $t->make_table($this->mysqli);
				}

				if (!empty($output)) {
					$this->output .= "\n".'<div class="head">Words</div>'."\n".$output;
				}
			}
		}

		/**
		 * Milestones section.
		 */
		if ($this->sectionbits & 128) {
			$output = '';
			$query = @mysqli_query($this->mysqli, 'select `milestone`, count(*) as `total` from `q_milestones` group by `milestone` order by `milestone` asc') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query);

			if (!empty($rows)) {
				while ($result = mysqli_fetch_object($query)) {
					$t = new table(number_format((int) $result->milestone).' Lines Milestone');
					$t->set_value('key1', 'Date');
					$t->set_value('key2', 'User');
					$t->set_value('minrows', 1);
					$t->set_value('query_main', 'select `date` as `v1`, `csnick` as `v2` from `q_milestones` join `user_details` on `q_milestones`.`ruid` = `user_details`.`uid` where `milestone` = '.$result->milestone.' order by `v1` asc, `v2` asc limit 5');
					$t->set_value('total', (int) $result->total);
					$t->set_value('type', 'milestones');
					$output .= $t->make_table($this->mysqli);
				}
			}

			if (!empty($output)) {
				$this->output .= "\n".'<div class="head">Milestones</div>'."\n".$output;
			}
		}


		/**
		 * HTML Foot.
		 */
		$this->output .= "\n".'<div class="info">Statistics created with <a href="https://github.com/tommyrot/superseriousstats">superseriousstats</a> on '.date('r').'.'.($this->addhtml_foot != '' ? '<br>'.trim(@file_get_contents($this->addhtml_foot)) : '').'</div>'."\n";
		$this->output .= "\n".'</div>'."\n".'</body>'."\n\n".'</html>'."\n";
		$this->output('notice', 'make_html(): finished creating statspage');
		return $this->output;
	}

	private function make_table_activity($type)
	{
		if ($type == 'day') {
			$class = 'act';
			$columns = 24;

			for ($i = 23; $i >= 0; $i--) {
				$dates[] = date('Y-m-d', mktime(0, 0, 0, $this->month, $this->dayofmonth - $i, $this->year));
			}

			$head = 'Activity by Day';
			$query = @mysqli_query($this->mysqli, 'select `date`, `l_total`, `l_night`, `l_morning`, `l_afternoon`, `l_evening` from `channel` where `date` > \''.date('Y-m-d', mktime(0, 0, 0, $this->month, $this->dayofmonth - 24, $this->year)).'\'') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		} elseif ($type == 'month') {
			$class = 'act';
			$columns = 24;

			for ($i = 23; $i >= 0; $i--) {
				$dates[] = date('Y-m', mktime(0, 0, 0, $this->month - $i, 1, $this->year));
			}

			$head = 'Activity by Month';
			$query = @mysqli_query($this->mysqli, 'select date_format(`date`, \'%Y-%m\') as `date`, sum(`l_total`) as `l_total`, sum(`l_night`) as `l_night`, sum(`l_morning`) as `l_morning`, sum(`l_afternoon`) as `l_afternoon`, sum(`l_evening`) as `l_evening` from `channel` where date_format(`date`, \'%Y-%m\') > \''.date('Y-m', mktime(0, 0, 0, $this->month - 24, 1, $this->year)).'\' group by year(`date`), month(`date`)') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		} elseif ($type == 'year') {
			$class = 'act-year';
			$columns = $this->years;

			for ($i = $this->years - 1; $i >= 0; $i--) {
				$dates[] = $this->year - $i;
			}

			if ($this->estimate) {
				$columns++;
				$dates[] = 'estimate';
			}

			$head = 'Activity by Year';
			$query = @mysqli_query($this->mysqli, 'select year(`date`) as `date`, sum(`l_total`) as `l_total`, sum(`l_night`) as `l_night`, sum(`l_morning`) as `l_morning`, sum(`l_afternoon`) as `l_afternoon`, sum(`l_evening`) as `l_evening` from `channel` group by year(`date`)') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		}

		$rows = mysqli_num_rows($query);

		/**
		 * The queries above will either return one or more rows with activity, or no rows at all.
		 */
		if (empty($rows)) {
			return null;
		}

		$high_date = '';
		$high_value = 0;

		while ($result = mysqli_fetch_object($query)) {
			$l_night[$result->date] = (int) $result->l_night;
			$l_morning[$result->date] = (int) $result->l_morning;
			$l_afternoon[$result->date] = (int) $result->l_afternoon;
			$l_evening[$result->date] = (int) $result->l_evening;
			$l_total[$result->date] = (int) $result->l_total;

			if ($l_total[$result->date] > $high_value) {
				$high_date = $result->date;
				$high_value = $l_total[$result->date];
			}
		}

		if ($this->estimate && $type == 'year') {
			$query = @mysqli_query($this->mysqli, 'select (sum(`l_night`) / 90) as `l_night_avg`, (sum(`l_morning`) / 90) as `l_morning_avg`, (sum(`l_afternoon`) / 90) as `l_afternoon_avg`, (sum(`l_evening`) / 90) as `l_evening_avg`, (sum(`l_total`) / 90) as `l_total_avg` from `q_activity_by_day` where `date` > \''.date('Y-m-d', mktime(0, 0, 0, $this->month, $this->dayofmonth - 90, $this->year)).'\'') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$result = mysqli_fetch_object($query);
			$l_night['estimate'] = $l_night[$this->currentyear] + round((float) $result->l_night_avg * $this->daysleft);
			$l_morning['estimate'] = $l_morning[$this->currentyear] + round((float) $result->l_morning_avg * $this->daysleft);
			$l_afternoon['estimate'] = $l_afternoon[$this->currentyear] + round((float) $result->l_afternoon_avg * $this->daysleft);
			$l_evening['estimate'] = $l_evening[$this->currentyear] + round((float) $result->l_evening_avg * $this->daysleft);
			$l_total['estimate'] = $l_total[$this->currentyear] + round((float) $result->l_total_avg * $this->daysleft);

			if ($l_total['estimate'] > $high_value) {
				$high_date = 'estimate';
				$high_value = $l_total['estimate'];
			}
		}

		$tr1 = '<tr><th colspan="'.$columns.'">'.$head.'</th></tr>';
		$tr2 = '<tr class="bars">';
		$tr3 = '<tr class="sub">';

		foreach ($dates as $date) {
			if (!array_key_exists($date, $l_total) || $l_total[$date] == 0) {
				$tr2 .= '<td><span class="grey">n/a</span></td>';
			} else {
				if ($l_total[$date] >= 999500) {
					$total = number_format($l_total[$date] / 1000000, 1).'M';
				} elseif ($l_total[$date] >= 10000) {
					$total = round($l_total[$date] / 1000).'K';
				} else {
					$total = $l_total[$date];
				}

				$times = array('evening', 'afternoon', 'morning', 'night');

				foreach ($times as $time) {
					if (${'l_'.$time}[$date] != 0) {
						$height[$time] = round((${'l_'.$time}[$date] / $high_value) * 100);
					} else {
						$height[$time] = 0;
					}
				}

				$tr2 .= '<td'.($date == 'estimate' ? ' class="est"' : '').'><ul><li class="num" style="height:'.($height['night'] + $height['morning'] + $height['afternoon'] + $height['evening'] + 14).'px">'.$total.'</li>';

				foreach ($times as $time) {
					if ($time == 'evening') {
						$class_li = 'r';
						$height_li = $height['night'] + $height['morning'] + $height['afternoon'] + $height['evening'];
					} elseif ($time == 'afternoon') {
						$class_li = 'y';
						$height_li = $height['night'] + $height['morning'] + $height['afternoon'];
					} elseif ($time == 'morning') {
						$class_li = 'g';
						$height_li = $height['night'] + $height['morning'];
					} elseif ($time == 'night') {
						$class_li = 'b';
						$height_li = $height['night'];
					}

					if ($height[$time] != 0) {
						$tr2 .= '<li class="'.$class_li.'" style="height:'.$height_li.'px"></li>';
					}
				}

				$tr2 .= '</ul></td>';
			}

			if ($type == 'day') {
				$tr3 .= '<td'.($high_date == $date ? ' class="bold"' : '').'>'.date('D', strtotime($date)).'<br>'.date('j', strtotime($date)).'</td>';
			} elseif ($type == 'month') {
				$tr3 .= '<td'.($high_date == $date ? ' class="bold"' : '').'>'.date('M', strtotime($date.'-01')).'<br>'.date('\'y', strtotime($date.'-01')).'</td>';
			} elseif ($type == 'year') {
				$tr3 .= '<td'.($high_date == $date ? ' class="bold"' : '').'>'.($date == 'estimate' ? 'Est.' : date('\'y', strtotime($date.'-01-01'))).'</td>';
			}
		}

		$tr2 .= '</tr>';
		$tr3 .= '</tr>';
		return '<table class="'.$class.'">'.$tr1.$tr2.$tr3.'</table>'."\n";
	}

	private function make_table_activity_distribution_day()
	{
		$query = @mysqli_query($this->mysqli, 'select sum(`l_mon_night`) as `l_mon_night`, sum(`l_mon_morning`) as `l_mon_morning`, sum(`l_mon_afternoon`) as `l_mon_afternoon`, sum(`l_mon_evening`) as `l_mon_evening`, sum(`l_tue_night`) as `l_tue_night`, sum(`l_tue_morning`) as `l_tue_morning`, sum(`l_tue_afternoon`) as `l_tue_afternoon`, sum(`l_tue_evening`) as `l_tue_evening`, sum(`l_wed_night`) as `l_wed_night`, sum(`l_wed_morning`) as `l_wed_morning`, sum(`l_wed_afternoon`) as `l_wed_afternoon`, sum(`l_wed_evening`) as `l_wed_evening`, sum(`l_thu_night`) as `l_thu_night`, sum(`l_thu_morning`) as `l_thu_morning`, sum(`l_thu_afternoon`) as `l_thu_afternoon`, sum(`l_thu_evening`) as `l_thu_evening`, sum(`l_fri_night`) as `l_fri_night`, sum(`l_fri_morning`) as `l_fri_morning`, sum(`l_fri_afternoon`) as `l_fri_afternoon`, sum(`l_fri_evening`) as `l_fri_evening`, sum(`l_sat_night`) as `l_sat_night`, sum(`l_sat_morning`) as `l_sat_morning`, sum(`l_sat_afternoon`) as `l_sat_afternoon`, sum(`l_sat_evening`) as `l_sat_evening`, sum(`l_sun_night`) as `l_sun_night`, sum(`l_sun_morning`) as `l_sun_morning`, sum(`l_sun_afternoon`) as `l_sun_afternoon`, sum(`l_sun_evening`) as `l_sun_evening` from `q_lines`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
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

		$tr1 = '<tr><th colspan="7">Activity Distribution by Day</th></tr>';
		$tr2 = '<tr class="bars">';
		$tr3 = '<tr class="sub">';

		foreach ($days as $day) {
			if ($l_total[$day] == 0) {
				$tr2 .= '<td><span class="grey">n/a</span></td>';
			} else {
				$percentage = ($l_total[$day] / $this->l_total) * 100;

				if ($percentage >= 9.95) {
					$percentage = round($percentage).'%';
				} else {
					$percentage = number_format($percentage, 1).'%';
				}

				$times = array('evening', 'afternoon', 'morning', 'night');

				foreach ($times as $time) {
					if (${'l_'.$time}[$day] != 0) {
						$height[$time] = round((${'l_'.$time}[$day] / $high_value) * 100);
					} else {
						$height[$time] = 0;
					}
				}

				$tr2 .= '<td><ul><li class="num" style="height:'.($height['night'] + $height['morning'] + $height['afternoon'] + $height['evening'] + 14).'px">'.$percentage.'</li>';

				foreach ($times as $time) {
					if ($time == 'evening') {
						$class = 'r';
						$height_li = $height['night'] + $height['morning'] + $height['afternoon'] + $height['evening'];
					} elseif ($time == 'afternoon') {
						$class = 'y';
						$height_li = $height['night'] + $height['morning'] + $height['afternoon'];
					} elseif ($time == 'morning') {
						$class = 'g';
						$height_li = $height['night'] + $height['morning'];
					} elseif ($time == 'night') {
						$class = 'b';
						$height_li = $height['night'];
					}

					if ($height[$time] != 0) {
						$tr2 .= '<li class="'.$class.'" style="height:'.$height_li.'px" title="'.number_format($l_total[$day]).'"></li>';
					}
				}

				$tr2 .= '</ul></td>';
			}

			$tr3 .= '<td'.($high_day == $day ? ' class="bold"' : '').'>'.ucfirst($day).'</td>';
		}

		$tr2 .= '</tr>';
		$tr3 .= '</tr>';
		return '<table class="act-day">'.$tr1.$tr2.$tr3.'</table>'."\n";
	}

	private function make_table_activity_distribution_hour()
	{
		$query = @mysqli_query($this->mysqli, 'select sum(`l_00`) as `l_00`, sum(`l_01`) as `l_01`, sum(`l_02`) as `l_02`, sum(`l_03`) as `l_03`, sum(`l_04`) as `l_04`, sum(`l_05`) as `l_05`, sum(`l_06`) as `l_06`, sum(`l_07`) as `l_07`, sum(`l_08`) as `l_08`, sum(`l_09`) as `l_09`, sum(`l_10`) as `l_10`, sum(`l_11`) as `l_11`, sum(`l_12`) as `l_12`, sum(`l_13`) as `l_13`, sum(`l_14`) as `l_14`, sum(`l_15`) as `l_15`, sum(`l_16`) as `l_16`, sum(`l_17`) as `l_17`, sum(`l_18`) as `l_18`, sum(`l_19`) as `l_19`, sum(`l_20`) as `l_20`, sum(`l_21`) as `l_21`, sum(`l_22`) as `l_22`, sum(`l_23`) as `l_23` from `channel`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
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
						$class = 'b';
					} elseif ($hour >= 6 && $hour <= 11) {
						$class = 'g';
					} elseif ($hour >= 12 && $hour <= 17) {
						$class = 'y';
					} elseif ($hour >= 18 && $hour <= 23) {
						$class = 'r';
					}

					$tr2 .= '<li class="'.$class.'" style="height:'.$height.'px" title="'.number_format((int) $value).'"></li>';
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
		if ($type == 'alltime') {
			$query = @mysqli_query($this->mysqli, 'select sum(`l_total`) as `l_total` from `q_lines` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		} elseif ($type == 'year') {
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
		if ($type == 'alltime') {
			$head = 'Most Talkative People &ndash; Alltime';
			$historylink = '<a href="history.php?cid='.urlencode($this->cid).'">History</a>';
			$query = @mysqli_query($this->mysqli, 'select `csnick`, `l_total`, `l_night`, `l_morning`, `l_afternoon`, `l_evening`, `quote`, (select max(`lastseen`) from `user_details` join `user_status` on `user_details`.`uid` = `user_status`.`uid` where `user_status`.`ruid` = `q_lines`.`ruid`) as `lastseen` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `l_total` != 0 order by `l_total` desc, `csnick` asc limit '.$this->maxrows_people_alltime) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		} elseif ($type == 'year') {
			$head = 'Most Talkative People &ndash; '.$this->year;
			$historylink = '<a href="history.php?cid='.urlencode($this->cid).'&amp;year='.$this->year.'">History</a>';
			$query = @mysqli_query($this->mysqli, 'select `csnick`, sum(`q_activity_by_year`.`l_total`) as `l_total`, sum(`q_activity_by_year`.`l_night`) as `l_night`, sum(`q_activity_by_year`.`l_morning`) as `l_morning`, sum(`q_activity_by_year`.`l_afternoon`) as `l_afternoon`, sum(`q_activity_by_year`.`l_evening`) as `l_evening`, `quote`, (select max(`lastseen`) from `user_details` join `user_status` on `user_details`.`uid` = `user_status`.`uid` where `user_status`.`ruid` = `q_lines`.`ruid`) as `lastseen` from `q_lines` join `q_activity_by_year` on `q_lines`.`ruid` = `q_activity_by_year`.`ruid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` where `status` != 3 and `date` = '.$this->year.' group by `q_lines`.`ruid` order by `l_total` desc, `csnick` asc limit '.$this->maxrows_people_year) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		} elseif ($type == 'month') {
			$head = 'Most Talkative People &ndash; '.$this->monthname.' '.$this->year;
			$historylink = '<a href="history.php?cid='.urlencode($this->cid).'&amp;year='.$this->year.'&amp;month='.$this->month.'">History</a>';
			$query = @mysqli_query($this->mysqli, 'select `csnick`, sum(`q_activity_by_month`.`l_total`) as `l_total`, sum(`q_activity_by_month`.`l_night`) as `l_night`, sum(`q_activity_by_month`.`l_morning`) as `l_morning`, sum(`q_activity_by_month`.`l_afternoon`) as `l_afternoon`, sum(`q_activity_by_month`.`l_evening`) as `l_evening`, `quote`, (select max(`lastseen`) from `user_details` join `user_status` on `user_details`.`uid` = `user_status`.`uid` where `user_status`.`ruid` = `q_lines`.`ruid`) as `lastseen` from `q_lines` join `q_activity_by_month` on `q_lines`.`ruid` = `q_activity_by_month`.`ruid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` where `status` != 3 and `date` = \''.date('Y-m', mktime(0, 0, 0, $this->month, 1, $this->year)).'\' group by `q_lines`.`ruid` order by `l_total` desc, `csnick` asc limit '.$this->maxrows_people_month) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		}

		$tr0 = '<col class="c1"><col class="c2"><col class="pos"><col class="c3"><col class="c4"><col class="c5"><col class="c6">';
		$tr1 = '<tr><th colspan="7">'.($this->history ? '<span class="left">'.$head.'</span><span class="right">'.$historylink.'</span>' : $head).'</th></tr>';
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
					if ($time == 'night') {
						$class = 'b';
					} elseif ($time == 'morning') {
						$class = 'g';
					} elseif ($time == 'afternoon') {
						$class = 'y';
					} elseif ($time == 'evening') {
						$class = 'r';
					}

					$when .= '<li class="'.$class.'" style="width:'.$width_int[$time].'px"></li>';
				}
			}

			$trx .= '<tr><td class="v1">'.number_format(((int) $result->l_total / $total) * 100, 2).'%</td><td class="v2">'.number_format((int) $result->l_total).'</td><td class="pos">'.$i.'</td><td class="v3">'.($this->userstats ? '<a href="user.php?cid='.urlencode($this->cid).'&amp;nick='.urlencode($result->csnick).'">'.htmlspecialchars($result->csnick).'</a>' : htmlspecialchars($result->csnick)).'</td><td class="v4"><ul>'.$when.'</ul></td><td class="v5">'.$lastseen.'</td><td class="v6">'.htmlspecialchars($result->quote).'</td></tr>';
		}

		return '<table class="ppl">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}

	private function make_table_people2()
	{
		$query = @mysqli_query($this->mysqli, 'select `csnick`, `l_total` from `q_lines` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` join `user_details` on `user_details`.`uid` = `user_status`.`ruid` where `status` != 3 and `l_total` != 0 order by `l_total` desc, `csnick` asc limit '.$this->maxrows_people_alltime.', '.($this->maxrows_people2 * 4)) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows) || $rows < $this->maxrows_people2 * 4) {
			return null;
		}

		$current_column = 1;
		$current_row = 1;

		while ($result = mysqli_fetch_object($query)) {
			if ($current_row > $this->maxrows_people2) {
				$current_column++;
				$current_row = 1;
			}

			$columns[$current_column][$current_row] = array($result->csnick, (int) $result->l_total);
			$current_row++;
		}

		$query = @mysqli_query($this->mysqli, 'select count(*) as `total` from `q_lines` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$result = mysqli_fetch_object($query);
		$total = (int) $result->total - $this->maxrows_people_alltime - ($this->maxrows_people2 * 4);
		$tr0 = '<col class="c1"><col class="pos"><col class="c2"><col class="c1"><col class="pos"><col class="c2"><col class="c1"><col class="pos"><col class="c2"><col class="c1"><col class="pos"><col class="c2">';
		$tr1 = '<tr><th colspan="12">'.($total != 0 ? '<span class="left">Less Talkative People &ndash; Alltime</span><span class="right">'.number_format($total).' People had even less to say..</span>' : 'Less Talkative People &ndash; Alltime').'</th></tr>';
		$tr2 = '<tr><td class="k1">Lines</td><td class="pos"></td><td class="k2">User</td><td class="k1">Lines</td><td class="pos"></td><td class="k2">User</td><td class="k1">Lines</td><td class="pos"></td><td class="k2">User</td><td class="k1">Lines</td><td class="pos"></td><td class="k2">User</td></tr>';
		$trx = '';

		for ($i = 1; $i <= $this->maxrows_people2; $i++) {
			$trx .= '<tr>';

			for ($j = 1; $j <= 4; $j++) {
				$trx .= '<td class="v1">'.number_format($columns[$j][$i][1]).'</td><td class="pos">'.($this->maxrows_people_alltime + ($j > 1 ? ($j - 1) * $this->maxrows_people2 : 0) + $i).'</td><td class="v2">'.($this->userstats ? '<a href="user.php?cid='.urlencode($this->cid).'&amp;nick='.urlencode($columns[$j][$i][0]).'">'.htmlspecialchars($columns[$j][$i][0]).'</a>' : htmlspecialchars($columns[$j][$i][0])).'</td>';
			}

			$trx .= '</tr>';
		}

		return '<table class="ppl2">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}

	private function make_table_people_timeofday()
	{
		/**
		 * Check if there is user activity (bots excluded). If there is none we can skip making the table.
		 */
		$query = @mysqli_query($this->mysqli, 'select sum(`l_total`) as `l_total` from `q_lines` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
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
			$query = @mysqli_query($this->mysqli, 'select `csnick`, `l_'.$time.'` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `l_'.$time.'` != 0 order by `l_'.$time.'` desc, `csnick` asc limit '.$this->maxrows_people_timeofday) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
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
							if ($time == 'night') {
								$class = 'b';
							} elseif ($time == 'morning') {
								$class = 'g';
							} elseif ($time == 'afternoon') {
								$class = 'y';
							} elseif ($time == 'evening') {
								$class = 'r';
							}

							$tr3 .= '<td class="v">'.htmlspecialchars(${$time}[$i]['user']).' - '.number_format(${$time}[$i]['lines']).'<br><div class="'.$class.'" style="width:'.$width.'px"></div></td>';
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
}

/**
 * Class for creating small, medium and large generic tables.
 */
final class table extends base
{
	private $head = '';
	protected $decimals = 0;
	protected $key1 = '';
	protected $key2 = '';
	protected $key3 = '';
	protected $maxrows = 5;
	protected $minrows = 3;
	protected $percentage = false;
	protected $query_main = '';
	protected $query_total = '';
	protected $type = 'small';
	protected $total = 0;

	public function __construct($head)
	{
		$this->head = $head;
	}

	public function make_table($mysqli)
	{
		if (empty($this->query_main)) {
			return null;
		}

		$query = @mysqli_query($mysqli, $this->query_main) or $this->output('critical', 'mysqli: '.mysqli_error($mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows) || $rows < $this->minrows) {
			return null;
		}

		/**
		 * Fetch and structure table contents.
		 */
		$i = 0;

		while ($result = mysqli_fetch_object($query)) {
			$i++;

			if ($i > $this->maxrows) {
				break;
			}

			if ($this->type == 'small') {
				$content[] = array($i, number_format((float) $result->v1, $this->decimals).($this->percentage ? '%' : ''), htmlspecialchars($result->v2));
			} elseif ($this->type == 'large' || $this->type == 'medium') {
				$content[] = array($i, number_format((float) $result->v1, $this->decimals).($this->percentage ? '%' : ''), htmlspecialchars($result->v2), htmlspecialchars($result->v3));
			} elseif ($this->type == 'topics') {
				/**
				 * Don't use htmlspecialchars() on $v3 yet because we still want to parse for valid URLs later on.
				 */
				$content[] = array($i, date('j M \'y', strtotime($result->v1)), htmlspecialchars($result->v2), $result->v3);
			} elseif ($this->type == 'urls') {
				$content[] = array($i, date('j M \'y', strtotime($result->v1)), htmlspecialchars($result->v2), htmlspecialchars($result->v3));
			} elseif ($this->type == 'domains') {
				$content[] = array($i, number_format((int) $result->v1), htmlspecialchars($result->v2), date('j M \'y', strtotime($result->v3)));
			} elseif ($this->type == 'milestones') {
				$content[] = array($i, date('j M \'y', strtotime($result->v1)), htmlspecialchars($result->v2));
			}
		}

		for ($i = count($content) + 1; $i <= $this->maxrows; $i++) {
			if ($this->type == 'small' || $this->type == 'milestones') {
				$content[] = array('&nbsp;', '', '');
			} elseif ($this->type == 'large' || $this->type == 'topics' || $this->type == 'medium' || $this->type == 'domains') {
				$content[] = array('&nbsp;', '', '', '');
			} elseif ($this->type == 'urls') {
				break;
			}
		}

		if (!empty($this->query_total)) {
			$query = @mysqli_query($mysqli, $this->query_total) or $this->output('critical', 'mysqli: '.mysqli_error($mysqli));
			$result = mysqli_fetch_object($query);
			$this->total = (int) $result->total;
		}

		/**
		 * Create the actual table according to type.
		 */
		if ($this->type == 'small' || $this->type == 'milestones') {
			$tr0 = '<col class="c1"><col class="pos"><col class="c2">';
			$tr1 = '<tr><th colspan="3">'.($this->total != 0 ? '<span class="left">'.$this->head.'</span><span class="right">'.number_format($this->total).' Total</span>' : $this->head).'</th></tr>';
			$tr2 = '<tr><td class="k1">'.$this->key1.'</td><td class="pos"></td><td class="k2">'.$this->key2.'</td></tr>';
			$trx = '';
		} elseif ($this->type == 'large' || $this->type == 'domains' || $this->type == 'medium' || $this->type == 'topics' || $this->type == 'urls') {
			$tr0 = '<col class="c1"><col class="pos"><col class="c2"><col class="c3">';
			$tr1 = '<tr><th colspan="4">'.($this->total != 0 ? '<span class="left">'.$this->head.'</span><span class="right">'.number_format($this->total).' Total</span>' : $this->head).'</th></tr>';
			$tr2 = '<tr><td class="k1">'.$this->key1.'</td><td class="pos"></td><td class="k2">'.$this->key2.'</td><td class="k3">'.$this->key3.'</td></tr>';
			$trx = '';
		}

		if ($this->type == 'small' || $this->type == 'milestones') {
			foreach ($content as $row) {
				$trx .= '<tr><td class="v1">'.$row[1].'</td><td class="pos">'.$row[0].'</td><td class="v2">'.$row[2].'</td></tr>';
			}
		} elseif ($this->type == 'large' || $this->type == 'medium') {
			foreach ($content as $row) {
				$trx .= '<tr><td class="v1">'.$row[1].'</td><td class="pos">'.$row[0].'</td><td class="v2">'.$row[2].'</td><td class="v3">'.$row[3].'</td></tr>';
			}
		} elseif ($this->type == 'domains') {
			foreach ($content as $row) {
				$trx .= '<tr><td class="v1">'.$row[1].'</td><td class="pos">'.$row[0].'</td><td class="v2"><a href="'.$row[2].'">'.$row[2].'</a></td><td class="v3">'.$row[3].'</td></tr>';
			}
		} elseif ($this->type == 'topics') {
			$urltools = new urltools();
			$prevdate = '';

			foreach ($content as $row) {
				$words = explode(' ', $row[3]);
				$topic = '';

				/**
				 * Check if there are URLs in the topic and make hyperlinks out of them. Use htmlspecialchars() here since we skipped doing it
				 * before.
				 */
				foreach ($words as $word) {
					if (preg_match('/^(www\.|https?:\/\/)/i', $word) && ($urldata = $urltools->get_elements($word)) !== false) {
						$topic .= '<a href="'.htmlspecialchars($urldata['url']).'">'.htmlspecialchars($urldata['url']).'</a> ';
					} else {
						$topic .= htmlspecialchars($word).' ';
					}
				}

				$trx .= '<tr><td class="v1">'.($row[1] != $prevdate ? $row[1] : '').'</td><td class="pos">'.$row[0].'</td><td class="v2">'.$row[2].'</td><td class="v3a">'.rtrim($topic).'</td></tr>';
				$prevdate = $row[1];
			}
		} elseif ($this->type == 'urls') {
			$prevdate = '';

			foreach ($content as $row) {
				$trx .= '<tr><td class="v1">'.($row[1] != $prevdate ? $row[1] : '').'</td><td class="pos">'.$row[0].'</td><td class="v2">'.$row[2].'</td><td class="v3"><a href="'.$row[3].'">'.$row[3].'</a></td></tr>';
				$prevdate = $row[1];
			}
		}

		if ($this->type == 'small' || $this->type == 'milestones') {
			return '<table class="small">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
		} elseif ($this->type == 'medium' || $this->type == 'domains') {
			return '<table class="medium">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
		} elseif ($this->type == 'large' || $this->type == 'topics' || $this->type == 'urls') {
			return '<table class="large">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
		}
	}
}

?>
