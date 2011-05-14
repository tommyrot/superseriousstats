<?php

/**
 * Copyright (c) 2007-2011, Jos de Ruijter <jos@dutnie.nl>
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
	 * Default settings for this script, can be overridden in the config file.
	 * These should all appear in $settings_list[] along with their type.
	 */
	private $addhtml_foot = '';
	private $addhtml_head = '';
	private $bar_afternoon = 'y.png';
	private $bar_evening = 'r.png';
	private $bar_morning = 'g.png';
	private $bar_night = 'b.png';
	private $channel = '';
	private $cid = '';
	private $history = false;
	private $minlines = 500;
	private $minrows = 3;
	private $rows_people_alltime = 30;
	private $rows_people2 = 40;
	private $rows_people_month = 10;
	private $rows_people_timeofday = 10;
	private $rows_people_year = 10;
	private $rows_recenturls = 25;
	private $sectionbits = 127;
	private $stylesheet = 'sss.css';
	private $userstats = false;

	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $date_first = '';
	private $date_last = '';
	private $date_lastlogparsed = '';
	private $date_max = '';
	private $days = 0;
	private $dayofmonth = 0;
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
		'bar_afternoon' => 'string',
		'bar_evening' => 'string',
		'bar_morning' => 'string',
		'bar_night' => 'string',
		'channel' => 'string',
		'cid' => 'string',
		'history' => 'bool',
		'minlines' => 'int',
		'minrows' => 'int',
		'outputbits' => 'int',
		'rows_people2' => 'int',
		'rows_people_alltime' => 'int',
		'rows_people_month' => 'int',
		'rows_people_timeofday' => 'int',
		'rows_people_year' => 'int',
		'rows_recenturls' => 'int',
		'sectionbits' => 'int',
		'stylesheet' => 'string',
		'userstats' => 'bool');
	private $year = 0;
	private $years = 0;

	public function __construct($settings)
	{
		parent::__construct();

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
			$this->output('critical', 'make_html(): database is empty');
		}

		$this->l_total = (int) $result->l_total;
		$query = @mysqli_query($this->mysqli, 'select min(`date`) as `date_first`, max(`date`) as `date_last` from `channel`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$result = mysqli_fetch_object($query);
		$this->date_first = $result->date_first;
		$this->date_last = $result->date_last;
		$query = @mysqli_query($this->mysqli, 'select count(*) as `days`, max(`date`) as `date_lastlogparsed` from `parse_history`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$result = mysqli_fetch_object($query);
		$this->days = (int) $result->days;
		$this->l_avg = $this->l_total / $this->days;

		/**
		 * This variable is used to shape most statistics. 1/1000th of the total lines typed in the channel.
		 * 500 is the default minimum so tables will still look interesting on low volume channels.
		 */
		if (round($this->l_total / 1000) >= 500) {
			$this->minlines = round($this->l_total / 1000);
		}

		/**
		 * Date and time variables used throughout the script. We take the date of the last logfile parsed. These variables are used to define our scope.
		 */
		$this->date_lastlogparsed = $result->date_lastlogparsed;
		$this->dayofmonth = (int) date('j', strtotime($this->date_lastlogparsed));
		$this->month = (int) date('n', strtotime($this->date_lastlogparsed));
		$this->monthname = date('F', strtotime($this->date_lastlogparsed));
		$this->year = (int) date('Y', strtotime($this->date_lastlogparsed));
		$this->years = $this->year - (int) date('Y', strtotime($this->date_first)) + 1;

		/**
		 * If we have less than 3 years of data we set the amount of years to 3 so we have that many columns in our table. Looks better.
		 */
		if ($this->years < 3) {
			$this->years = 3;
		}

		/**
		 * HTML Head.
		 */
		$query = @mysqli_query($this->mysqli, 'select `date` as `date_max`, `l_total` as `l_max` from `channel` where `l_total` = (select max(`l_total`) from `channel`)') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$result = mysqli_fetch_object($query);
		$this->date_max = $result->date_max;
		$this->l_max = $result->l_max;
		$this->output = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'."\n\n"
			      . '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">'."\n\n"
			      . '<head>'."\n".'<title>'.htmlspecialchars($this->channel).', seriously.</title>'."\n"
			      . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n"
			      . '<meta http-equiv="Content-Style-Type" content="text/css" />'."\n"
			      . '<link rel="stylesheet" type="text/css" href="'.$this->stylesheet.'" />'."\n"
			      . '<style type="text/css">'."\n"
			      . '  .yearly {width:'.(2 + ($this->years * 34)).'px}'."\n"
			      . '</style>'."\n"
			      . '</head>'."\n\n".'<body>'."\n"
			      . '<div class="box">'."\n"
			      . "\n".'<div class="info">'.htmlspecialchars($this->channel).', seriously.<br /><br />'.number_format($this->days).' day'.($this->days > 1 ? 's logged from '.date('M j, Y', strtotime($this->date_first)).' to '.date('M j, Y', strtotime($this->date_last)) : ' logged on '.date('M j, Y', strtotime($this->date_first))).'.<br />'
			      . '<br />Logs contain '.number_format($this->l_total).' line'.($this->l_total > 1 ? 's' : '').' &ndash; an average of '.number_format($this->l_avg).' line'.($this->l_avg > 1 ? 's' : '').' per day.<br />Most active day was '.date('M j, Y', strtotime($this->date_max)).' with a total of '.number_format($this->l_max).' line'.($this->l_max > 1 ? 's' : '').' typed.'.($this->addhtml_head != '' ? '<br /><br />'.trim(@file_get_contents($this->addhtml_head)) : '').'</div>'."\n";

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
			$this->output .= $this->make_table_people('alltime', $this->rows_people_alltime);
			$this->output .= $this->make_table_people2($this->rows_people_alltime, $this->rows_people2);
			$this->output .= $this->make_table_people('year', $this->rows_people_year);
			$this->output .= $this->make_table_people('month', $this->rows_people_month);
			$this->output .= $this->make_table_people_timeofday($this->rows_people_timeofday);
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
			$t->set_value('query_main', 'select (`l_total` / `activedays`) as `v1`, `csnick` as `v2` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `l_total` >= '.$this->minlines.' order by `v1` desc, `v2` asc limit 5');
			$output .= $t->mt($this->mysqli);

			$t = new table('Most Fluent Chatters');
			$t->set_value('decimals', 1);
			$t->set_value('key1', 'Words/Line');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select (`words` / `l_total`) as `v1`, `csnick` as `v2` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `l_total` >= '.$this->minlines.' order by `v1` desc, `v2` asc limit 5');
			$output .= $t->mt($this->mysqli);

			$t = new table('Most Tedious Chatters');
			$t->set_value('decimals', 1);
			$t->set_value('key1', 'Chars/Line');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select (`characters` / `l_total`) as `v1`, `csnick` as `v2` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `l_total` >= '.$this->minlines.' order by `v1` desc, `v2` asc limit 5');
			$output .= $t->mt($this->mysqli);

			$t = new table('Individual Top Days &ndash; Alltime');
			$t->set_value('key1', 'Lines');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select max(`l_total`) as `v1`, `csnick` as `v2` from `q_activity_by_day` join `user_status` on `q_activity_by_day`.`ruid` = `user_status`.`uid` join `user_details` on `q_activity_by_day`.`ruid` = `user_details`.`uid` where `status` != 3 group by `q_activity_by_day`.`ruid` order by `v1` desc, `v2` asc limit 5');
			$output .= $t->mt($this->mysqli);

			$t = new table('Individual Top Days &ndash; '.$this->year);
			$t->set_value('key1', 'Lines');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select max(`l_total`) as `v1`, `csnick` as `v2` from `q_activity_by_day` join `user_status` on `q_activity_by_day`.`ruid` = `user_status`.`uid` join `user_details` on `q_activity_by_day`.`ruid` = `user_details`.`uid` where `status` != 3 and year(`date`) = \''.$this->year.'\' group by `q_activity_by_day`.`ruid` order by `v1` desc, `v2` asc limit 5');
			$output .= $t->mt($this->mysqli);

			$t = new table('Individual Top Days &ndash; '.$this->monthname.' '.$this->year);
			$t->set_value('key1', 'Lines');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select max(`l_total`) as `v1`, `csnick` as `v2` from `q_activity_by_day` join `user_status` on `q_activity_by_day`.`ruid` = `user_status`.`uid` join `user_details` on `q_activity_by_day`.`ruid` = `user_details`.`uid` where `status` != 3 and date_format(`date`, \'%Y-%m\') = \''.date('Y-m', strtotime($this->date_lastlogparsed)).'\' group by `q_activity_by_day`.`ruid` order by `v1` desc, `v2` asc limit 5');
			$output .= $t->mt($this->mysqli);

			$t = new table('Most Active Chatters &ndash; Alltime');
			$t->set_value('decimals', 2);
			$t->set_value('key1', 'Activity');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('percentage', true);
			$t->set_value('query_main', 'select (`activedays` / '.$this->days.') * 100 as `v1`, `csnick` as `v2` from `user_status` join `q_lines` on `user_status`.`uid` = `q_lines`.`ruid` join `user_details` on `user_status`.`uid` = `user_details`.`uid` where `status` != 3 order by `v1` desc, `v2` asc limit 5');
			$output .= $t->mt($this->mysqli);

			$t = new table('Most Active Chatters &ndash; '.$this->year);
			$t->set_value('decimals', 2);
			$t->set_value('key1', 'Activity');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('percentage', true);
			$t->set_value('query_main', 'select (count(distinct `date`) / (select count(*) from parse_history where year(`date`) = '.$this->year.')) * 100 as `v1`, `csnick` as `v2` from `q_activity_by_day` join `user_status` on `q_activity_by_day`.`ruid` = `user_status`.`uid` join `user_details` on `q_activity_by_day`.`ruid` = `user_details`.`uid` where `status` != 3 and year(`date`) = '.$this->year.' group by `q_activity_by_day`.`ruid` order by `v1` desc, `v2` asc limit 5');
			$output .= $t->mt($this->mysqli);

			$t = new table('Most Active Chatters &ndash; '.$this->monthname.' '.$this->year);
			$t->set_value('decimals', 2);
			$t->set_value('key1', 'Activity');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('percentage', true);
			$t->set_value('query_main', 'select (count(distinct `date`) / (select count(*) from parse_history where date_format(`date`, \'%Y-%m\') = \''.date('Y-m', strtotime($this->date_lastlogparsed)).'\')) * 100 as `v1`, `csnick` as `v2` from `q_activity_by_day` join `user_status` on `q_activity_by_day`.`ruid` = `user_status`.`uid` join `user_details` on `q_activity_by_day`.`ruid` = `user_details`.`uid` where `status` != 3 and date_format(`date`, \'%Y-%m\') = \''.date('Y-m', strtotime($this->date_lastlogparsed)).'\' group by `q_activity_by_day`.`ruid` order by `v1` desc, `v2` asc limit 5');
			$output .= $t->mt($this->mysqli);

			$t = new table('Exclamations');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('key3', 'Example');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `exclamations` as `v1`, `csnick` as `v2`, `ex_exclamations` as `v3` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `exclamations` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`exclamations`) as `total` from `q_lines`');
			$t->set_value('type', 'large');
			$output .= $t->mt($this->mysqli);

			$t = new table('Questions');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('key3', 'Example');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `questions` as `v1`, `csnick` as `v2`, `ex_questions` as `v3` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `questions` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`questions`) as `total` from `q_lines`');
			$t->set_value('type', 'large');
			$output .= $t->mt($this->mysqli);

			$t = new table('UPPERCASED Lines');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('key3', 'Example');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `uppercased` as `v1`, `csnick` as `v2`, `ex_uppercased` as `v3` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `uppercased` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`uppercased`) as `total` from `q_lines`');
			$t->set_value('type', 'large');
			$output .= $t->mt($this->mysqli);

			$t = new table('Monologues');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `monologues` as `v1`, `csnick` as `v2` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `monologues` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`monologues`) as `total` from `q_lines`');
			$output .= $t->mt($this->mysqli);

			$t = new table('Longest Monologue');
			$t->set_value('key1', 'Lines');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `topmonologue` as `v1`, `csnick` as `v2` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `topmonologue` != 0 order by `v1` desc, `v2` asc limit 5');
			$output .= $t->mt($this->mysqli);

			$t = new table('Slaps Given');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `slaps` as `v1`, `csnick` as `v2` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `slaps` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`slaps`) as `total` from `q_lines`');
			$output .= $t->mt($this->mysqli);

			$t = new table('Slaps Received');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `slapped` as `v1`, `csnick` as `v2` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `slapped` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`slapped`) as `total` from `q_lines`');
			$output .= $t->mt($this->mysqli);

			$t = new table('Most Lively Bots');
			$t->set_value('key1', 'Lines');
			$t->set_value('key2', 'Bot');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `l_total` as `v1`, `csnick` as `v2` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` = 3 and `l_total` != 0 order by `v1` desc, `v2` asc limit 5');
			$output .= $t->mt($this->mysqli);

			$t = new table('Actions Performed');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('key3', 'Example');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `actions` as `v1`, `csnick` as `v2`, `ex_actions` as `v3` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `actions` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`actions`) as `total` from `q_lines`');
			$t->set_value('type', 'large');
			$output .= $t->mt($this->mysqli);

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
				$output .= $t->mt($this->mysqli);
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
			$output .= $t->mt($this->mysqli);

			$t = new table('Kicks Received');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('key3', 'Example');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `kicked` as `v1`, `csnick` as `v2`, `ex_kicked` as `v3` from `q_events` join `user_details` on `q_events`.`ruid` = `user_details`.`uid` join `user_status` on `q_events`.`ruid` = `user_status`.`uid` where `status` != 3 and `kicked` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`kicked`) as `total` from `q_events`');
			$t->set_value('type', 'large');
			$output .= $t->mt($this->mysqli);

			$t = new table('Channel Joins');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `joins` as `v1`, `csnick` as `v2` from `q_events` join `user_details` on `q_events`.`ruid` = `user_details`.`uid` join `user_status` on `q_events`.`ruid` = `user_status`.`uid` where `status` != 3 and `joins` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`joins`) as `total` from `q_events`');
			$output .= $t->mt($this->mysqli);

			$t = new table('Channel Parts');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `parts` as `v1`, `csnick` as `v2` from `q_events` join `user_details` on `q_events`.`ruid` = `user_details`.`uid` join `user_status` on `q_events`.`ruid` = `user_status`.`uid` where `status` != 3 and `parts` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`parts`) as `total` from `q_events`');
			$output .= $t->mt($this->mysqli);

			$t = new table('IRC Quits');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `quits` as `v1`, `csnick` as `v2` from `q_events` join `user_details` on `q_events`.`ruid` = `user_details`.`uid` join `user_status` on `q_events`.`ruid` = `user_status`.`uid` where `status` != 3 and `quits` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`quits`) as `total` from `q_events`');
			$output .= $t->mt($this->mysqli);

			$t = new table('Nick Changes');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `nickchanges` as `v1`, `csnick` as `v2` from `q_events` join `user_details` on `q_events`.`ruid` = `user_details`.`uid` join `user_status` on `q_events`.`ruid` = `user_status`.`uid` where `status` != 3 and `nickchanges` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`nickchanges`) as `total` from `q_events`');
			$output .= $t->mt($this->mysqli);

			$t = new table('Aliases');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select count(*) as `v1`, `csnick` as `v2` from `user_details` join `user_status` on `user_details`.`uid` = `user_status`.`uid` where `status` != 3 group by `ruid` having `v1` > 1 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select count(*) as `total` from `user_status`');
			$output .= $t->mt($this->mysqli);

			$t = new table('Topics Set');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `topics` as `v1`, `csnick` as `v2` from `q_events` join `user_details` on `q_events`.`ruid` = `user_details`.`uid` join `user_status` on `q_events`.`ruid` = `user_status`.`uid` where `status` != 3 and `topics` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`topics`) as `total` from `q_events`');
			$output .= $t->mt($this->mysqli);

			$t = new table('Recent Topics');
			$t->set_value('key1', 'Date');
			$t->set_value('key2', 'User');
			$t->set_value('key3', 'Topic');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `setdate` as `v1`, `csnick` as `v2`, `topic` as `v3` from `user_topics` join `user_status` on `user_topics`.`uid` = `user_status`.`uid` join `user_details` on `user_details`.`uid` = `user_status`.`ruid` order by `v1` desc, `v2` asc limit 5');
			$t->set_value('type', 'topics');
			$output .= $t->mt($this->mysqli);

			if (!empty($output)) {
				$this->output .= "\n".'<div class="head">Events</div>'."\n".$output;
			}
		}

		/**
		 * Smileys section.
		 */
		if ($this->sectionbits & 16) {
			$output = '';

			$t = new table('Moodiest People');
			$t->set_value('key1', 'Smileys');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select (`s_01` + `s_02` + `s_03` + `s_04` + `s_05` + `s_06` + `s_07` + `s_08` + `s_09` + `s_10` + `s_11` + `s_12` + `s_13` + `s_14` + `s_15` + `s_16` + `s_17` + `s_18` + `s_19`) as `v1`, `csnick` as `v2` from `q_smileys` join `user_details` on `q_smileys`.`ruid` = `user_details`.`uid` join `user_status` on `q_smileys`.`ruid` = `user_status`.`uid` where `status` != 3 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select (sum(`s_01`) + sum(`s_02`) + sum(`s_03`) + sum(`s_04`) + sum(`s_05`) + sum(`s_06`) + sum(`s_07`) + sum(`s_08`) + sum(`s_09`) + sum(`s_10`) + sum(`s_11`) + sum(`s_12`) + sum(`s_13`) + sum(`s_14`) + sum(`s_15`) + sum(`s_16`) + sum(`s_17`) + sum(`s_18`) + sum(`s_19`)) as `total` from `q_smileys`');
			$output .= $t->mt($this->mysqli);

			/**
			 * Display smiley tables ordered by totals.
			 */
			$query = @mysqli_query($this->mysqli, 'select sum(`s_01`) as `s_01`, sum(`s_02`) as `s_02`, sum(`s_03`) as `s_03`, sum(`s_04`) as `s_04`, sum(`s_05`) as `s_05`, sum(`s_06`) as `s_06`, sum(`s_07`) as `s_07`, sum(`s_08`) as `s_08`, sum(`s_09`) as `s_09`, sum(`s_10`) as `s_10`, sum(`s_11`) as `s_11`, sum(`s_12`) as `s_12`, sum(`s_13`) as `s_13`, sum(`s_14`) as `s_14`, sum(`s_15`) as `s_15`, sum(`s_16`) as `s_16`, sum(`s_17`) as `s_17`, sum(`s_18`) as `s_18`, sum(`s_19`) as `s_19` from `q_smileys`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$result = mysqli_fetch_object($query);

			foreach ($result as $key => $value) {
				if ((int) $value < $this->minlines) {
					continue;
				}

				$topsmileys[$key] = (int) $value;
			}

			if (!empty($topsmileys)) {
				$smileys = array(
					's_01' => array('=]', 'Big Cheerful Smile'),
					's_02' => array('=)', 'Cheerful Smile'),
					's_03' => array(';x', 'Lovely Kiss'),
					's_04' => array(';p', 'Weirdo'),
					's_05' => array(';]', 'Big Winky'),
					's_06' => array(';-)', 'Classic Winky'),
					's_07' => array(';)', 'Winky'),
					's_08' => array(';(', 'Cry'),
					's_09' => array(':x', 'Kiss'),
					's_10' => array(':P', 'Tongue'),
					's_11' => array(':D', 'Laugh'),
					's_12' => array(':>', 'Funny'),
					's_13' => array(':]', 'Big Smile'),
					's_14' => array(':\\', 'Skeptical I'),
					's_15' => array(':/', 'Skeptical II'),
					's_16' => array(':-)', 'Classic Happy'),
					's_17' => array(':)', 'Happy'),
					's_18' => array(':(', 'Sad'),
					's_19' => array('\\o/', 'Cheer'));
				arsort($topsmileys);

				foreach ($topsmileys as $key => $value) {
					$t = new table($smileys[$key][1]);
					$t->set_value('key1', $smileys[$key][0]);
					$t->set_value('key2', 'User');
					$t->set_value('minrows', $this->minrows);
					$t->set_value('query_main', 'select `'.$key.'` as `v1`, `csnick` as `v2` from `q_smileys` join `user_details` on `q_smileys`.`ruid` = `user_details`.`uid` join `user_status` on `q_smileys`.`ruid` = `user_status`.`uid` where `status` != 3 and `'.$key.'` != 0 order by `v1` desc, `v2` asc limit 5');
					$t->set_value('total', $value);
					$output .= $t->mt($this->mysqli);
				}
			}

			if (!empty($output)) {
				$this->output .= "\n".'<div class="head">Smileys</div>'."\n".$output;
			}
		}

		/**
		 * URLs section.
		 */
		if ($this->sectionbits & 32) {
			$output = '';

			$t = new table('Most Referenced Domains');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'Domain');
			$t->set_value('key3', 'First Used');
			$t->set_value('minrows', 10);
			$t->set_value('query_main', 'select sum(`total`) as `v1`, concat(\'http://\', `authority`) as `v2`, min(`firstused`) as `v3` from `user_urls` group by `v2` order by `v1` desc, `v3` asc limit 10');
			$t->set_value('rows', 10);
			$t->set_value('type', 'domains');
			$output .= $t->mt($this->mysqli);

			$t = new table('Most Referenced TLDs');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'TLD');
			$t->set_value('minrows', 10);
			$t->set_value('query_main', 'select sum(`total`) as `v1`, `tld` as `v2` from `user_urls` where `tld` != \'\' group by `v2` order by `v1` desc, `v2` asc limit 10;');
			$t->set_value('rows', 10);
			$output .= $t->mt($this->mysqli);

			$t = new table('Recent URLs');
			$t->set_value('key1', 'Date');
			$t->set_value('key2', 'User');
			$t->set_value('key3', 'URL');
			$t->set_value('minrows', $this->rows_recenturls);
			$t->set_value('query_main', 'select `lastused` as `v1`, `csnick` as `v2`, `url` as `v3` from `user_urls` join `user_status` on `user_urls`.`uid` = `user_status`.`uid` join `user_details` on `user_details`.`uid` = `user_status`.`ruid` order by `v1` desc limit '.$this->rows_recenturls);
			$t->set_value('rows', $this->rows_recenturls);
			$t->set_value('type', 'urls');
			$output .= $t->mt($this->mysqli);

			$t = new table('URLs by Users');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'User');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `urls` as `v1`, `csnick` as `v2` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `urls` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`urls`) as `total` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3');
			$output .= $t->mt($this->mysqli);

			$t = new table('URLs by Bots');
			$t->set_value('key1', 'Total');
			$t->set_value('key2', 'Bot');
			$t->set_value('minrows', $this->minrows);
			$t->set_value('query_main', 'select `urls` as `v1`, `csnick` as `v2` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` = 3 and `urls` != 0 order by `v1` desc, `v2` asc limit 5');
			$t->set_value('query_total', 'select sum(`urls`) as `total` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` = 3');
			$output .= $t->mt($this->mysqli);

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
			 * Display word tables ordered by totals.
			 */
			$query = @mysqli_query($this->mysqli, 'select `length`, count(*) as `total` from `words` group by `length` order by `total` desc, `length` desc limit 9') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));

			while ($result = mysqli_fetch_object($query)) {
				$t = new table('Words of '.$result->length.' Characters');
				$t->set_value('key1', 'Times Used');
				$t->set_value('key2', 'Word');
				$t->set_value('minrows', $this->minrows);
				$t->set_value('query_main', 'select `total` as `v1`, `word` as `v2` from `words` where `length` = '.$result->length.' order by `v1` desc, `v2` asc limit 5');
				$t->set_value('total', (int) $result->total);
				$output .= $t->mt($this->mysqli);
			}

			if (!empty($output)) {
				$this->output .= "\n".'<div class="head">Words</div>'."\n".$output;
			}
		}

		/**
		 * HTML Foot.
		 */
		$this->output .= "\n".'<div class="info">Statistics created with <a href="https://github.com/tommyrot/superseriousstats">superseriousstats</a> on '.date('r').'.'.($this->addhtml_foot != '' ? '<br />'.trim(@file_get_contents($this->addhtml_foot)) : '').'</div>'."\n";
		$this->output .= "\n".'</div>'."\n".'</body>'."\n\n".'</html>'."\n";
		$this->output('notice', 'make_html(): finished creating statspage');
		return $this->output;
	}

	private function make_table_activity($type)
	{
		if ($type == 'day') {
			$class = 'graph';
			$cols = 24;

			for ($i = 23; $i >= 0; $i--) {
				$dates[] = date('Y-m-d', mktime(0, 0, 0, $this->month, $this->dayofmonth - $i, $this->year));
			}

			$head = 'Activity by Day';
			$query = @mysqli_query($this->mysqli, 'select `date`, `l_total`, `l_night`, `l_morning`, `l_afternoon`, `l_evening` from `channel` where `date` > \''.date('Y-m-d', mktime(0, 0, 0, $this->month, $this->dayofmonth - 24, $this->year)).'\'') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		} elseif ($type == 'month') {
			$class = 'graph';
			$cols = 24;

			for ($i = 23; $i >= 0; $i--) {
				$dates[] = date('Y-m', mktime(0, 0, 0, $this->month - $i, 1, $this->year));
			}

			$head = 'Activity by Month';
			$query = @mysqli_query($this->mysqli, 'select date_format(`date`, \'%Y-%m\') as `date`, sum(`l_total`) as `l_total`, sum(`l_night`) as `l_night`, sum(`l_morning`) as `l_morning`, sum(`l_afternoon`) as `l_afternoon`, sum(`l_evening`) as `l_evening` from `channel` where date_format(`date`, \'%Y-%m\') > \''.date('Y-m', mktime(0, 0, 0, $this->month - 24, 1, $this->year)).'\' group by year(`date`), month(`date`)') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		} elseif ($type == 'year') {
			$class = 'yearly';
			$cols = $this->years;

			for ($i = $this->years - 1; $i >= 0; $i--) {
				$dates[] = $this->year - $i;
			}

			$head = 'Activity by Year';
			$query = @mysqli_query($this->mysqli, 'select year(`date`) as `date`, sum(`l_total`) as `l_total`, sum(`l_night`) as `l_night`, sum(`l_morning`) as `l_morning`, sum(`l_afternoon`) as `l_afternoon`, sum(`l_evening`) as `l_evening` from `channel` group by year(`date`)') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		}

		$rows = mysqli_num_rows($query);

		/**
		 * The queries above will either return one or more rows with activity, or no rows at all.
		 */
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
			$l_total[$result->date] = (int) $result->l_total;

			if ($l_total[$result->date] > $high_value) {
				$high_date = $result->date;
				$high_value = $l_total[$result->date];
			}
		}

		$tr1 = '<tr><th colspan="'.$cols.'">'.$head.'</th></tr>';
		$tr2 = '<tr class="bars">';
		$tr3 = '<tr class="sub">';

		foreach ($dates as $date) {
			if (!array_key_exists($date, $l_total) || $l_total[$date] == 0) {
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

			if ($type == 'day') {
				if ($high_date == $date) {
					$tr3 .= '<td class="bold">'.date('D', strtotime($date)).'<br />'.date('j', strtotime($date)).'</td>';
				} else {
					$tr3 .= '<td>'.date('D', strtotime($date)).'<br />'.date('j', strtotime($date)).'</td>';
				}
			} elseif ($type == 'month') {
				if ($high_date == $date) {
					$tr3 .= '<td class="bold">'.date('M', strtotime($date.'-01')).'<br />'.date('\'y', strtotime($date.'-01')).'</td>';
				} else {
					$tr3 .= '<td>'.date('M', strtotime($date.'-01')).'<br />'.date('\'y', strtotime($date.'-01')).'</td>';
				}
			} elseif ($type == 'year') {
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

	private function make_table_people($type, $rows)
	{
		if ($type == 'alltime') {
			$head = 'Most Talkative People &ndash; Alltime';
			$historylink = '<span class="right"><a href="history.php?cid='.urlencode($this->cid).'">History</a></span>';
			$total = $this->l_total;
			$query = @mysqli_query($this->mysqli, 'select `csnick`, `l_total`, `l_night`, `l_morning`, `l_afternoon`, `l_evening`, `quote`, (select max(`lastseen`) from `user_details` join `user_status` on `user_details`.`uid` = `user_status`.`uid` where `user_status`.`ruid` = `q_lines`.`ruid`) as `lastseen` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 order by `l_total` desc, `csnick` asc limit '.$rows) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		} elseif ($type == 'year') {
			$head = 'Most Talkative People &ndash; '.$this->year;
			$historylink = '<span class="right"><a href="history.php?cid='.urlencode($this->cid).'&amp;year='.$this->year.'">History</a></span>';
			$query = @mysqli_query($this->mysqli, 'select sum(`l_total`) as `l_total` from `q_activity_by_year` where `date` = '.$this->year) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$result = mysqli_fetch_object($query);
			$total = (int) $result->l_total;
			$query = @mysqli_query($this->mysqli, 'select `csnick`, sum(`q_activity_by_year`.`l_total`) as `l_total`, sum(`q_activity_by_year`.`l_night`) as `l_night`, sum(`q_activity_by_year`.`l_morning`) as `l_morning`, sum(`q_activity_by_year`.`l_afternoon`) as `l_afternoon`, sum(`q_activity_by_year`.`l_evening`) as `l_evening`, `quote`, (select max(`lastseen`) from `user_details` join `user_status` on `user_details`.`uid` = `user_status`.`uid` where `user_status`.`ruid` = `q_lines`.`ruid`) as `lastseen` from `q_lines` join `q_activity_by_year` on `q_lines`.`ruid` = `q_activity_by_year`.`ruid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` where `status` != 3 and `date` = '.$this->year.' group by `q_lines`.`ruid` order by `l_total` desc, `csnick` asc limit '.$rows) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		} elseif ($type == 'month') {
			$head = 'Most Talkative People &ndash; '.$this->monthname.' '.$this->year;
			$historylink = '<span class="right"><a href="history.php?cid='.urlencode($this->cid).'&amp;year='.$this->year.'&amp;month='.$this->month.'">History</a></span>';
			$query = @mysqli_query($this->mysqli, 'select sum(`l_total`) as `l_total` from `q_activity_by_month` where `date` = \''.date('Y-m', mktime(0, 0, 0, $this->month, 1, $this->year)).'\'') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$result = mysqli_fetch_object($query);
			$total = (int) $result->l_total;
			$query = @mysqli_query($this->mysqli, 'select `csnick`, sum(`q_activity_by_month`.`l_total`) as `l_total`, sum(`q_activity_by_month`.`l_night`) as `l_night`, sum(`q_activity_by_month`.`l_morning`) as `l_morning`, sum(`q_activity_by_month`.`l_afternoon`) as `l_afternoon`, sum(`q_activity_by_month`.`l_evening`) as `l_evening`, `quote`, (select max(`lastseen`) from `user_details` join `user_status` on `user_details`.`uid` = `user_status`.`uid` where `user_status`.`ruid` = `q_lines`.`ruid`) as `lastseen` from `q_lines` join `q_activity_by_month` on `q_lines`.`ruid` = `q_activity_by_month`.`ruid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` where `status` != 3 and `date` = \''.date('Y-m', mktime(0, 0, 0, $this->month, 1, $this->year)).'\' group by `q_lines`.`ruid` order by `l_total` desc, `csnick` asc limit '.$rows) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		}

		if ($total == 0) {
			return;
		}

		$tr0 = '<col class="c1" /><col class="c2" /><col class="pos" /><col class="c3" /><col class="c4" /><col class="c5" /><col class="c6" />';
		$tr1 = '<tr><th colspan="7"><span class="left">'.$head.'</span>'.($this->history ? $historylink : '').'</th></tr>';
		$tr2 = '<tr><td class="k1">Percentage</td><td class="k2">Lines</td><td class="pos"></td><td class="k3">User</td><td class="k4">When?</td><td class="k5">Last Seen</td><td class="k6">Quote</td></tr>';
		$trx = '';
		$i = 0;

		while ($result = mysqli_fetch_object($query)) {
			$i++;

			if ((int) $result->l_total == 0) {
				break;
			}

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

			$trx .= '<tr><td class="v1">'.number_format(((int) $result->l_total / $total) * 100, 2).'%</td><td class="v2">'.number_format((int) $result->l_total).'</td><td class="pos">'.$i.'</td><td class="v3">'.($this->userstats ? '<a href="user.php?cid='.urlencode($this->cid).'&amp;nick='.urlencode($result->csnick).'">'.htmlspecialchars($result->csnick).'</a>' : htmlspecialchars($result->csnick)).'</td><td class="v4">'.$when.'</td><td class="v5">'.$lastseen.'</td><td class="v6">'.htmlspecialchars($result->quote).'</td></tr>';
		}

		return '<table class="map">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}

	/**
	 * $rowcount must be a multiple of 4 so we get a clean table without empty spaces that would otherwise mess up the layout.
	 */
	private function make_table_people2($offset, $rowcount)
	{
		$query = @mysqli_query($this->mysqli, 'select `q_lines`.`ruid`, `csnick`, `l_total` from `q_lines` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` join `user_details` on `user_details`.`uid` = `user_status`.`ruid` where `status` != 3 and `l_total` != 0 order by `l_total` desc limit '.$offset.', '.$rowcount) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows) || $rows < $rowcount) {
			return;
		}

		$rows_per_column = $rowcount / 4;
		$current_column = 1;
		$current_row = 1;

		while ($result = mysqli_fetch_object($query)) {
			if ($current_row > $rows_per_column) {
				$current_column++;
				$current_row = 1;

				if ($current_column > 4) {
					break;
				}
			}

			${'column'.$current_column}[$current_row] = array($result->ruid, $result->csnick, (int) $result->l_total);
			$current_row++;
		}

		$query = @mysqli_query($this->mysqli, 'select count(*) as `total` from `q_lines` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$result = mysqli_fetch_object($query);
		$total = (int) $result->total - $offset - $rowcount;
		$tr0 = '<col class="c1" /><col class="pos" /><col class="c2" /><col class="c1" /><col class="pos" /><col class="c2" /><col class="c1" /><col class="pos" /><col class="c2" /><col class="c1" /><col class="pos" /><col class="c2" />';
		$tr1 = '<tr><th colspan="12"><span class="left">Less Talkative People &ndash; Alltime</span>'.($total == 0 ? '' : '<span class="right">'.number_format($total).' People had even less to say..</span>').'</th></tr>';
		$tr2 = '<tr><td class="k1">Lines</td><td class="pos"></td><td class="k2">User</td><td class="k1">Lines</td><td class="pos"></td><td class="k2">User</td><td class="k1">Lines</td><td class="pos"></td><td class="k2">User</td><td class="k1">Lines</td><td class="pos"></td><td class="k2">User</td></tr>';
		$trx = '';

		for ($i = 1; $i <= $rows_per_column; $i++) {
			$trx .= '<tr>';

			for ($j = 1; $j <= 4; $j++) {
				$trx .= '<td class="v1">'.number_format(${'column'.$j}[$i][2]).'</td><td class="pos">'.($offset + ($j > 1 ? ($j - 1) * $rows_per_column : 0) + $i).'</td><td class="v2">'.($this->userstats ? '<a href="user.php?cid='.urlencode($this->cid).'&amp;nick='.urlencode(${'column'.$j}[$i][1]).'">'.htmlspecialchars(${'column'.$j}[$i][1]).'</a>' : htmlspecialchars(${'column'.$j}[$i][1])).'</td>';
			}

			$trx .= '</tr>';
		}

		return '<table class="nqsap">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}

	private function make_table_people_timeofday($rows)
	{
		$high_value = 0;
		$times = array('night', 'morning', 'afternoon', 'evening');

		foreach ($times as $time) {
			$query = @mysqli_query($this->mysqli, 'select `csnick`, `l_'.$time.'` from `q_lines` join `user_details` on `q_lines`.`ruid` = `user_details`.`uid` join `user_status` on `q_lines`.`ruid` = `user_status`.`uid` where `status` != 3 and `l_'.$time.'` != 0 order by `l_'.$time.'` desc, `csnick` asc limit '.$rows);
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

		$tr0 = '<col class="pos" /><col class="c" /><col class="c" /><col class="c" /><col class="c" />';
		$tr1 = '<tr><th colspan="5">Most Talkative People by Time of Day</th></tr>';
		$tr2 = '<tr><td class="pos"></td><td class="k">Night<br />0h - 5h</td><td class="k">Morning<br />6h - 11h</td><td class="k">Afternoon<br />12h - 17h</td><td class="k">Evening<br />18h - 23h</td></tr>';
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

		return '<table class="tod">'.$tr0.$tr1.$tr2.$tr3.'</table>'."\n";
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
	protected $minrows = 3;
	protected $percentage = false;
	protected $query_main = '';
	protected $query_total = '';
	protected $rows = 5;
	protected $type = 'small';
	protected $total = 0;

	public function __construct($head)
	{
		parent::__construct();
		$this->head = $head;
	}

	public function mt($mysqli)
	{
		if (empty($this->query_main)) {
			return;
		}

		$query = @mysqli_query($mysqli, $this->query_main) or $this->output('critical', 'mysqli: '.mysqli_error($mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows) || $rows < $this->minrows) {
			return;
		}

		$i = 0;

		while ($result = mysqli_fetch_object($query)) {
			$i++;

			if ($i > $this->rows) {
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
			}
		}

		for ($i = count($content) + 1; $i <= $this->rows; $i++) {
			if ($this->type == 'small') {
				$content[] = array('&nbsp;', '', '');
			} elseif ($this->type == 'large' || $this->type == 'topics' || $this->type == 'urls' || $this->type == 'medium' || $this->type == 'domains') {
				$content[] = array('&nbsp;', '', '', '');
			}
		}

		if (!empty($this->query_total)) {
			$query = @mysqli_query($mysqli, $this->query_total) or $this->output('critical', 'mysqli: '.mysqli_error($mysqli));
			$result = mysqli_fetch_object($query);
			$this->total = (int) $result->total;
		}

		if ($this->type == 'small') {
			$tr0 = '<col class="c1" /><col class="pos" /><col class="c2" />';
			$tr1 = '<tr><th colspan="3"><span class="left">'.$this->head.'</span>'.($this->total == 0 ? '' : '<span class="right">'.number_format($this->total).' Total</span>').'</th></tr>';
			$tr2 = '<tr><td class="k1">'.$this->key1.'</td><td class="pos"></td><td class="k2">'.$this->key2.'</td></tr>';
			$trx = '';
		} elseif ($this->type == 'large' || $this->type == 'domains' || $this->type == 'medium' || $this->type == 'topics' || $this->type == 'urls') {
			$tr0 = '<col class="c1" /><col class="pos" /><col class="c2" /><col class="c3" />';
			$tr1 = '<tr><th colspan="4"><span class="left">'.$this->head.'</span>'.($this->total == 0 ? '' : '<span class="right">'.number_format($this->total).' Total</span>').'</th></tr>';
			$tr2 = '<tr><td class="k1">'.$this->key1.'</td><td class="pos"></td><td class="k2">'.$this->key2.'</td><td class="k3">'.$this->key3.'</td></tr>';
			$trx = '';
		}

		if ($this->type == 'small') {
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
			$prevdate = '';

			foreach ($content as $row) {
				$words = explode(' ', $row[3]);
				$topic = '';

				/**
				 * Check if there are URLs in the topic and make hyperlinks out of them. Use htmlspecialchars() here since we skipped doing it before.
				 */
				foreach ($words as $word) {
					if (preg_match('/^(www\.|https?:\/\/)/i', $word) && ($urldata = $this->urltools->get_elements($word)) !== false) {
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

		if ($this->type == 'small') {
			return '<table class="small">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
		} elseif ($this->type == 'medium' || $this->type == 'domains') {
			return '<table class="medium">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
		} elseif ($this->type == 'large' || $this->type == 'topics' || $this->type == 'urls') {
			return '<table class="large">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
		}
	}
}

?>
