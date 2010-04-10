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
 * Class for creating the statspage.
 * Note: data is always one day old (we are generating stats from data up to and including yesterday).
 */
final class HTML extends Base
{
	/**
	 * Default settings, can be overridden in the config file.
	 */
	private $bar_afternoon = 'y.png';
	private $bar_evening = 'r.png';
	private $bar_morning = 'g.png';
	private $bar_night = 'b.png';
	private $channel = '#yourchan';
	private $db_host = '';
	private $db_name = '';
	private $db_pass = '';
	private $db_port = 0;
	private $db_user = '';
	private $minLines = 500;
	private $minRows = 3;
	private $sectionbits = 31;
	private $stylesheet = 'default.css';
	private $userstats = FALSE;

	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $date_first = '';
	private $date_last = '';
	private $date_max = '';
	private $day = '';
	private $days = 0;
	private $day_of_month = '';
	private $day_of_year = '';
	private $l_avg = 0;
	private $l_max = 0;
	private $l_total = 0;
	private $month = '';
	private $month_name = '';
	private $mysqli;
	private $output = '';
	private $settings_list = array(
		'bar_afternoon' => 'string',
		'bar_evening' => 'string',
		'bar_morning' => 'string',
		'bar_night' => 'string',
		'channel' => 'string',
		'db_host' => 'string',
		'db_name' => 'string',
		'db_pass' => 'string',
		'db_port' => 'int',
		'db_user' => 'string',
		'minLines' => 'int',
		'minRows' => 'int',
		'outputbits' => 'int',
		'sectionbits' => 'int',
		'stylesheet' => 'string',
		'userstats' => 'bool');
	private $year = '';
	private $years = 0;

	/**
	 * Constructor.
	 */
	public function __construct($settings)
	{
		foreach ($this->settings_list as $key => $type) {
			if (array_key_exists($key, $settings)) {
				if ($type == 'string') {
					$this->$key = (string) $settings[$key];
				} elseif ($type == 'int') {
					$this->$key = (int) $settings[$key];
				} elseif ($type == 'bool') {
					if (strcasecmp($settings[$key], 'TRUE') == 0) {
						$this->$key = TRUE;
					} elseif (strcasecmp($settings[$key], 'FALSE') == 0) {
						$this->$key = FALSE;
					}
				}
			}
		}
	}

	/**
	 * Get the case sensitive nick and status for a given UID.
	 */
	private function getDetails($UID)
	{
		$query = @mysqli_query($this->mysqli, 'SELECT `csNick`, `status` FROM `user_details` JOIN `user_status` ON `user_details`.`UID` = `user_status`.`UID` WHERE `user_details`.`UID` = '.$UID) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		$result = mysqli_fetch_object($query);

		return array('csNick' => $result->csNick
			    ,'status' => $result->status);
	}

	/**
	 * Generate the HTML page.
	 */
	public function makeHTML()
	{
		$this->output('notice', 'makeHTML(): creating statspage');
		$this->mysqli = @mysqli_connect($this->db_host, $this->db_user, $this->db_pass, $this->db_name, $this->db_port) or $this->output('critical', 'MySQLi: '.mysqli_connect_error());
		$query = @mysqli_query($this->mysqli, 'SELECT MIN(`date`) AS `date_first`, MAX(`date`) AS `date_last`, COUNT(*) AS `days`, AVG(`l_total`) AS `l_avg`, SUM(`l_total`) AS `l_total` FROM `channel`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			$this->output('critical', 'makeHTML(): database \''.$this->db_name.'\' is empty');
		}

		$result = mysqli_fetch_object($query);
		$this->date_first = $result->date_first;
		$this->date_last = $result->date_last;
		$this->days = $result->days;
		$this->l_avg = $result->l_avg;
		$this->l_total = $result->l_total;

		if ($this->l_total == 0) {
			$this->output('critical', 'makeHTML(): database \''.$this->db_name.'\' contains insufficient data');
		}

		/**
		 * This variable is used to shape most statistics. 1/1000th of the total lines typed in the channel.
		 * 500 is the default minimum so tables will still look interesting on low volume channels.
		 */
		if (round($this->l_total / 1000) >= 500) {
			$this->minLines = round($this->l_total / 1000);
		}

		/**
		 * Date and time variables used throughout the script.
		 * For whatever reason PHP starts counting days from 0.. so we add 1 to $day_of_year to fix this absurdity.
		 */
		$this->day = date('j', strtotime('yesterday'));
		$this->day_of_month = date('d', strtotime('yesterday'));
		$this->day_of_year = date('z', strtotime('yesterday')) + 1;
		$this->month = date('m', strtotime('yesterday'));
		$this->month_name = date('F', strtotime('yesterday'));
		$this->year = date('Y', strtotime('yesterday'));
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
			      . '<!--[if IE]>'."\n".'  <link rel="stylesheet" type="text/css" href="iefix.css" />'."\n".'<![endif]-->'."\n"
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
			$this->output .= $this->makeTable_MostActiveTimes(array('head' => 'Most Active Times'));
			$this->output .= $this->makeTable_Activity(array('type' => 'days', 'head' => 'Daily Activity'));
			$this->output .= $this->makeTable_Activity(array('type' => 'months', 'head' => 'Monthly Activity'));
			$this->output .= $this->makeTable_MostActiveDays(array('head' => 'Most Active Days'));
			$this->output .= $this->makeTable_Activity(array('type' => 'years', 'head' => 'Yearly Activity'));
			$this->output .= $this->makeTable_MostActivePeople(array('type' => 'alltime', 'rows' => 30, 'head' => 'Most Active People, Alltime', 'key1' => 'Percentage', 'key2' => 'Lines', 'key3' => 'User', 'key4' => 'When?', 'key5' => 'Last Seen', 'key6' => 'Quote'));
			$this->output .= $this->makeTable_MostActivePeople(array('type' => 'year', 'rows' => 10, 'head' => 'Most Active People, '.$this->year, 'key1' => 'Percentage', 'key2' => 'Lines', 'key3' => 'User', 'key4' => 'When?', 'key5' => 'Last Seen', 'key6' => 'Quote'));
			$this->output .= $this->makeTable_MostActivePeople(array('type' => 'month', 'rows' => 10, 'head' => 'Most Active People, '.$this->month_name.' '.$this->year, 'key1' => 'Percentage', 'key2' => 'Lines', 'key3' => 'User', 'key4' => 'When?', 'key5' => 'Last Seen', 'key6' => 'Quote'));
			$this->output .= $this->makeTable_TimeOfDay(array('head' => 'Activity, by Time of Day', 'key1' => 'Nightcrawlers', 'key2' => 'Early Birds', 'key3' => 'Afternoon Shift', 'key4' => 'Evening Chatters'));
		}

		/**
		 * General Chat section
		 */
		if ($this->sectionbits & 2) {
			$output = '';
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Most Talkative Chatters', 'key1' => 'Lines/Day', 'key2' => 'User', 'decimals' => 1, 'percentage' => FALSE, 'query' => 'SELECT (`l_total` / `activeDays`) AS `v1`, `csNick` AS `v2` FROM `query_lines` JOIN `user_details` ON `query_lines`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `l_total` >= '.$this->minLines.' ORDER BY `v1` DESC, `v2` ASC LIMIT 5'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Most Fluent Chatters', 'key1' => 'Words/Line', 'key2' => 'User', 'decimals' => 1, 'percentage' => FALSE, 'query' => 'SELECT (`words` / `l_total`) AS `v1`, `csNick` AS `v2` FROM `query_lines` JOIN `user_details` ON `query_lines`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `l_total` >= '.$this->minLines.' ORDER BY `v1` DESC, `v2` ASC LIMIT 5'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Most Tedious Chatters', 'key1' => 'Chars/Line', 'key2' => 'User', 'decimals' => 1, 'percentage' => FALSE, 'query' => 'SELECT (`characters` / `l_total`) AS `v1`, `csNick` AS `v2` FROM `query_lines` JOIN `user_details` ON `query_lines`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `l_total` >= '.$this->minLines.' ORDER BY `v1` DESC, `v2` ASC LIMIT 5'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Individual Top Days, Alltime', 'key1' => 'Lines', 'key2' => 'User', 'decimals' => 0, 'percentage' => FALSE, 'getDetails' => 'RUID', 'query' => 'SELECT `RUID`, `v1` FROM (SELECT `RUID`, SUM(`l_total`) AS `v1` FROM `user_status` JOIN `user_activity` ON `user_status`.`UID` = `user_activity`.`UID` GROUP BY `date`, `RUID` ORDER BY `v1` DESC LIMIT 100) AS `sub` GROUP BY `RUID` ORDER BY `v1` DESC'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Individual Top Days, '.$this->year, 'key1' => 'Lines', 'key2' => 'User', 'decimals' => 0, 'percentage' => FALSE, 'getDetails' => 'RUID', 'query' => 'SELECT `RUID`, `v1` FROM (SELECT `RUID`, SUM(`l_total`) AS `v1` FROM `user_status` JOIN `user_activity` ON `user_status`.`UID` = `user_activity`.`UID` WHERE YEAR(`date`) = '.$this->year.' GROUP BY `date`, `RUID` ORDER BY `v1` DESC LIMIT 100) AS `sub` GROUP BY `RUID` ORDER BY `v1` DESC'));
			$output	.= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Individual Top Days, '.$this->month_name.' '.$this->year, 'key1' => 'Lines', 'key2' => 'User', 'decimals' => 0, 'percentage' => FALSE, 'getDetails' => 'RUID', 'query' => 'SELECT `RUID`, `v1` FROM (SELECT `RUID`, SUM(`l_total`) AS `v1` FROM `user_status` JOIN `user_activity` ON `user_status`.`UID` = `user_activity`.`UID` WHERE YEAR(`date`) = '.$this->year.' AND MONTH(`date`) = '.$this->month.' GROUP BY `date`, `RUID` ORDER BY `v1` DESC LIMIT 100) AS `sub` GROUP BY `RUID` ORDER BY `v1` DESC'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Most Active Chatters, Alltime', 'key1' => 'Activity', 'key2' => 'User', 'decimals' => 2, 'percentage' => TRUE, 'query' => 'SELECT (`activeDays` / '.(((strtotime($this->date_last) - strtotime($this->date_first)) / 86400) + 1).') * 100 AS `v1`, `csNick` AS `v2` FROM `user_status` JOIN `query_lines` ON `user_status`.`UID` = `query_lines`.`UID` JOIN `user_details` ON `user_status`.`UID` = `user_details`.`UID` WHERE `status` != 3 ORDER BY `v1` DESC, `v2` ASC LIMIT 5'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Most Active Chatters, '.$this->year, 'key1' => 'Activity', 'key2' => 'User', 'decimals' => 2, 'percentage' => TRUE, 'getDetails' => 'RUID', 'query' => 'SELECT `RUID`, (COUNT(DISTINCT `date`) / '.$this->day_of_year.') * 100 AS `v1` FROM `user_status` JOIN `user_activity` ON `user_status`.`UID` = `user_activity`.`UID` WHERE YEAR(`date`) = '.$this->year.' GROUP BY `RUID` ORDER BY `v1` DESC LIMIT 25'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Most Active Chatters, '.$this->month_name.' '.$this->year, 'key1' => 'Activity', 'key2' => 'User', 'decimals' => 2, 'percentage' => TRUE, 'getDetails' => 'RUID', 'query' => 'SELECT `RUID`, (COUNT(DISTINCT `date`) / '.$this->day_of_month.') * 100 AS `v1` FROM `user_status` JOIN `user_activity` ON `user_status`.`UID` = `user_activity`.`UID` WHERE YEAR(`date`) = '.$this->year.' AND MONTH(`date`) = '.$this->month.' GROUP BY `RUID` ORDER BY `v1` DESC LIMIT 25'));
			$output .= $this->makeTable(array('size' => 'large', 'rows' => 5, 'head' => 'Most Exclamations', 'key1' => 'Percentage', 'key2' => 'User', 'key3' => 'Example', 'decimals' => 2, 'percentage' => TRUE, 'query' => 'SELECT (`exclamations` / `l_total`) * 100 AS `v1`, `csNick` AS `v2`, `ex_exclamations` AS `v3` FROM `query_lines` JOIN `user_details` ON `query_lines`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `exclamations` != 0 AND `l_total` >= '.$this->minLines.' ORDER BY `v1` DESC, `v2` ASC LIMIT 5'));
			$output .= $this->makeTable(array('size' => 'large', 'rows' => 5, 'head' => 'Most Questions', 'key1' => 'Percentage', 'key2' => 'User', 'key3' => 'Example', 'decimals' => 2, 'percentage' => TRUE, 'query' => 'SELECT (`questions` / `l_total`) * 100 AS `v1`, `csNick` AS `v2`, `ex_questions` AS `v3` FROM `query_lines` JOIN `user_details` ON `query_lines`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `questions` != 0 AND `l_total` >= '.$this->minLines.' ORDER BY `v1` DESC, `v2` ASC LIMIT 5'));
			$output .= $this->makeTable(array('size' => 'large', 'rows' => 5, 'head' => 'Most UPPERCASED Lines', 'key1' => 'Percentage', 'key2' => 'User', 'key3' => 'Example', 'decimals' => 2, 'percentage' => TRUE, 'query' => 'SELECT (`uppercased` / `l_total`) * 100 AS `v1`, `csNick` AS `v2`, `ex_uppercased` AS `v3` FROM `query_lines` JOIN `user_details` ON `query_lines`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `uppercased` != 0 AND `l_total` >= '.$this->minLines.' ORDER BY `v1` DESC, `v2` ASC LIMIT 5'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Most URLs, by Users', 'key1' => 'URLs', 'key2' => 'User', 'decimals' => 0, 'percentage' => FALSE, 'query' => 'SELECT `URLs` AS `v1`, `csNick` AS `v2` FROM `query_lines` JOIN `user_details` ON `query_lines`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `URLs` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5', 'query_total' => 'SELECT SUM(`URLs`) AS `total` FROM `query_lines` JOIN `user_details` ON `query_lines`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`UID` = `user_status`.`UID` WHERE `status` != 3'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Most URLs, by Bots', 'key1' => 'URLs', 'key2' => 'Bot', 'decimals' => 0, 'percentage' => FALSE, 'query' => 'SELECT `URLs` AS `v1`, `csNick` AS `v2` FROM `query_lines` JOIN `user_details` ON `query_lines`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`UID` = `user_status`.`UID` WHERE `status` = 3 AND `URLs` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5', 'query_total' => 'SELECT SUM(`URLs`) AS `total` FROM `query_lines` JOIN `user_details` ON `query_lines`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`UID` = `user_status`.`UID` WHERE `status` = 3'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Most Monologues', 'key1' => 'Monologues', 'key2' => 'User', 'decimals' => 0, 'percentage' => FALSE, 'query' => 'SELECT `monologues` AS `v1`, `csNick` AS `v2` FROM `query_lines` JOIN `user_details` ON `query_lines`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `monologues` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5', 'query_total' => 'SELECT SUM(`monologues`) AS `total` FROM `query_lines`'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Most Slaps, Given', 'key1' => 'Slaps', 'key2' => 'User', 'decimals' => 0, 'percentage' => FALSE, 'query' => 'SELECT `slaps` AS `v1`, `csNick` AS `v2` FROM `query_lines` JOIN `user_details` ON `query_lines`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `slaps` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5', 'query_total' => 'SELECT SUM(`slaps`) AS `total` FROM `query_lines`'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Most Slaps, Received', 'key1' => 'Slaps', 'key2' => 'User', 'decimals' => 0, 'percentage' => FALSE, 'query' => 'SELECT `slapped` AS `v1`, `csNick` AS `v2` FROM `query_lines` JOIN `user_details` ON `query_lines`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `slapped` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5', 'query_total' => 'SELECT SUM(`slapped`) AS `total` FROM `query_lines`'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Longest Monologue', 'key1' => 'Lines', 'key2' => 'User', 'decimals' => 0, 'percentage' => FALSE, 'query' => 'SELECT `topMonologue` AS `v1`, `csNick` AS `v2` FROM `query_lines` JOIN `user_details` ON `query_lines`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `topMonologue` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5'));
			$output .= $this->makeTable(array('size' => 'large', 'rows' => 5, 'head' => 'Most Actions', 'key1' => 'Percentage', 'key2' => 'User', 'key3' => 'Example', 'decimals' => 2, 'percentage' => TRUE, 'query' => 'SELECT (`actions` / `l_total`) * 100 AS `v1`, `csNick` AS `v2`, `ex_actions` AS `v3` FROM `query_lines` JOIN `user_details` ON `query_lines`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `actions` != 0 AND `l_total` >= '.$this->minLines.' ORDER BY `v1` DESC, `v2` ASC LIMIT 5'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Most Mentioned Nicks', 'key1' => 'Mentioned', 'key2' => 'Nick', 'decimals' => 0, 'percentage' => FALSE, 'query' => 'SELECT `total` AS `v1`, `csNick` AS `v2` FROM `user_details` JOIN `words` ON `user_details`.`csNick` = `words`.`word` JOIN `user_activity` ON `user_details`.`UID` = `user_activity`.`UID` GROUP BY `user_details`.`UID` HAVING SUM(`l_total`) >= '.$this->minLines.' ORDER BY `v1` DESC, `v2` ASC LIMIT 5'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Most Chatty Bots', 'key1' => 'Lines', 'key2' => 'Bot', 'decimals' => 0, 'percentage' => FALSE, 'query' => 'SELECT `l_total` AS `v1`, `csNick` AS `v2` FROM `query_lines` JOIN `user_details` ON `query_lines`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`UID` = `user_status`.`UID` WHERE `status` = 3 AND `l_total` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5'));

			if (!empty($output)) {
				$this->output .= '<div class="head">General Chat</div>'."\n".$output;
			}
		}

		/**
		 * Modes section
		 */
		if ($this->sectionbits & 4) {
			$output = '';
			$modes = array('Most Ops \'+o\', Given' => array('Ops', 'm_op')
				      ,'Most Ops \'+o\', Received' => array('Ops', 'm_opped')
				      ,'Most deOps \'-o\', Given' => array('deOps', 'm_deOp')
				      ,'Most deOps \'-o\', Received' => array('deOps', 'm_deOpped')
				      ,'Most Voices \'+v\', Given' => array('Voices', 'm_voice')
				      ,'Most Voices \'+v\', Received' => array('Voices', 'm_voiced')
				      ,'Most deVoices \'-v\', Given' => array('deVoices', 'm_deVoice')
				      ,'Most deVoices \'-v\', Received' => array('deVoices', 'm_deVoiced'));

			foreach ($modes as $key => $value) {
				$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => $key, 'key1' => $value[0], 'key2' => 'User', 'decimals' => 0, 'percentage' => FALSE, 'query' => 'SELECT `'.$value[1].'` AS `v1`, `csNick` AS `v2` FROM `query_events` JOIN `user_details` ON `query_events`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_events`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `'.$value[1].'` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5', 'query_total' => 'SELECT SUM(`'.$value[1].'`) AS `total` FROM `query_events`'));
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
			$output .= $this->makeTable(array('size' => 'large', 'rows' => 5, 'head' => 'Most Kicks', 'key1' => 'Kicks', 'key2' => 'User', 'key3' => 'Example', 'decimals' => 0, 'percentage' => FALSE, 'query' => 'SELECT `kicks` AS `v1`, `csNick` AS `v2`, `ex_kicks` AS `v3` FROM `query_events` JOIN `user_details` ON `query_events`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_events`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `kicks` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5', 'query_total' => 'SELECT SUM(`kicks`) AS `total` FROM `query_events`'));
			$output .= $this->makeTable(array('size' => 'large', 'rows' => 5, 'head' => 'Most Kicked', 'key1' => 'Kicked', 'key2' => 'User', 'key3' => 'Example', 'decimals' => 0, 'percentage' => FALSE, 'query' => 'SELECT `kicked` AS `v1`, `csNick` AS `v2`, `ex_kicked` AS `v3` FROM `query_events` JOIN `user_details` ON `query_events`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_events`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `kicked` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5', 'query_total' => 'SELECT SUM(`kicked`) AS `total` FROM `query_events`'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Most Joins', 'key1' => 'Joins', 'key2' => 'User', 'decimals' => 0, 'percentage' => FALSE, 'query' => 'SELECT `joins` AS `v1`, `csNick` AS `v2` FROM `query_events` JOIN `user_details` ON `query_events`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_events`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `joins` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5', 'query_total' => 'SELECT SUM(`joins`) AS `total` FROM `query_events`'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Most Parts', 'key1' => 'Parts', 'key2' => 'User', 'decimals' => 0, 'percentage' => FALSE, 'query' => 'SELECT `parts` AS `v1`, `csNick` AS `v2` FROM `query_events` JOIN `user_details` ON `query_events`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_events`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `parts` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5', 'query_total' => 'SELECT SUM(`parts`) AS `total` FROM `query_events`'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Most Quits', 'key1' => 'Quits', 'key2' => 'User', 'decimals' => 0, 'percentage' => FALSE, 'query' => 'SELECT `quits` AS `v1`, `csNick` AS `v2` FROM `query_events` JOIN `user_details` ON `query_events`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_events`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `quits` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5', 'query_total' => 'SELECT SUM(`quits`) AS `total` FROM `query_events`'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Most Nick Changes', 'key1' => 'Nick Changes', 'key2' => 'User', 'decimals' => 0, 'percentage' => FALSE, 'query' => 'SELECT `nickChanges` AS `v1`, `csNick` AS `v2` FROM `query_events` JOIN `user_details` ON `query_events`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_events`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `nickChanges` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5', 'query_total' => 'SELECT SUM(`nickChanges`) AS `total` FROM `query_events`'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Most Aliases', 'key1' => 'Aliases', 'key2' => 'User', 'decimals' => 0, 'percentage' => FALSE, 'query' => 'SELECT COUNT(*) AS `v1`, `csNick` AS `v2` FROM `user_details` JOIN `user_status` ON `user_details`.`UID` = `user_status`.`UID` WHERE `status` != 3 GROUP BY `RUID` ORDER BY `v1` DESC, `v2` ASC LIMIT 5', 'query_total' => 'SELECT COUNT(*) AS `total` FROM `user_status`'));
			$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => 'Most Topics', 'key1' => 'Topics', 'key2' => 'User', 'decimals' => 0, 'percentage' => FALSE, 'query' => 'SELECT `topics` AS `v1`, `csNick` AS `v2` FROM `query_events` JOIN `user_details` ON `query_events`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_events`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `topics` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5', 'query_total' => 'SELECT SUM(`topics`) AS `total` FROM `query_events`'));
			$output .= $this->makeTable_Topics(array('rows' => 5, 'head1' => 'Most Recent Topics', 'head2' => 'Longest Standing Topics', 'key1a' => 'Days', 'key1b' => 'Days Ago', 'key2' => 'User', 'key3' => 'Topic'));

			if (!empty($output)) {
				$this->output .= '<div class="head">Events</div>'."\n".$output;
			}
		}

		/**
		 * Smileys section
		 */
		if ($this->sectionbits & 16) {
			$output = '';
			$smileys = array('Big Cheerful Smile' => array('=]', 's_01')
					,'Cheerful Smile' => array('=)', 's_02')
					,'Lovely Kiss' => array(';x', 's_03')
					,'Retard' => array(';p', 's_04')
					,'Big Winky' => array(';]', 's_05')
					,'Classic Winky' => array(';-)', 's_06')
					,'Winky' => array(';)', 's_07')
					,'Cry' => array(';(', 's_08')
					,'Kiss' => array(':x', 's_09')
					,'Tongue' => array(':P', 's_10')
					,'Laugh' => array(':D', 's_11')
					,'Funny' => array(':>', 's_12')
					,'Big Smile' => array(':]', 's_13')
					,'Skeptical I' => array(':\\', 's_14')
					,'Skeptical II' => array(':/', 's_15')
					,'Classic Happy' => array(':-)', 's_16')
					,'Happy' => array(':)', 's_17')
					,'Sad' => array(':(', 's_18')
					,'Cheer' => array('\\o/', 's_19'));

			$query = @mysqli_query($this->mysqli, 'SELECT SUM(`s_01`) AS `s_01`, SUM(`s_02`) AS `s_02`, SUM(`s_03`) AS `s_03`, SUM(`s_04`) AS `s_04`, SUM(`s_05`) AS `s_05`, SUM(`s_06`) AS `s_06`, SUM(`s_07`) AS `s_07`, SUM(`s_08`) AS `s_08`, SUM(`s_09`) AS `s_09`, SUM(`s_10`) AS `s_10`, SUM(`s_11`) AS `s_11`, SUM(`s_12`) AS `s_12`, SUM(`s_13`) AS `s_13`, SUM(`s_14`) AS `s_14`, SUM(`s_15`) AS `s_15`, SUM(`s_16`) AS `s_16`, SUM(`s_17`) AS `s_17`, SUM(`s_18`) AS `s_18`, SUM(`s_19`) AS `s_19` FROM `query_smileys`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			$result = mysqli_fetch_object($query);

			foreach ($smileys as $key => $value) {
				if ($result->$value[1] >= $this->minLines) {
					$output .= $this->makeTable(array('size' => 'small', 'rows' => 5, 'head' => $key, 'key1' => $value[0], 'key2' => 'User', 'decimals' => 0, 'percentage' => FALSE, 'query' => 'SELECT `'.$value[1].'` AS `v1`, `csNick` AS `v2` FROM `query_smileys` JOIN `user_details` ON `query_smileys`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_smileys`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `'.$value[1].'` != 0 ORDER BY `v1` DESC, `v2` ASC LIMIT 5', 'query_total' => 'SELECT SUM(`'.$value[1].'`) AS `total` FROM `query_smileys`'));
				}
			}

			if (!empty($output)) {
				$this->output .= '<div class="head">Smileys</div>'."\n".$output;
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
	 * Create a small or large generic table.
	 */
	private function makeTable($settings)
	{
		$query = @mysqli_query($this->mysqli, $settings['query']) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
		$i = 0;

		while ($result = mysqli_fetch_object($query)) {
			if  ($i >= $settings['rows']) {
				break;
			}

			if (isset($settings['getDetails'])) {
				$details = $this->getDetails($result->$settings['getDetails']);
				$result->v2 = $details['csNick'];
				$status = $details['status'];
			} else {
				$status = 1;
			}

			if ($status != 3) {
				$i++;

				if ($settings['size'] == 'small') {
					$content[] = array($i, number_format($result->v1, $settings['decimals']).($settings['percentage'] ? '%' : ''), htmlspecialchars($result->v2));
				} elseif ($settings['size'] == 'large') {
					$content[] = array($i, number_format($result->v1, $settings['decimals']).($settings['percentage'] ? '%' : ''), htmlspecialchars($result->v2), htmlspecialchars($result->v3));
				}
			}
		}

		$output = '';

		/**
		 * If there are less rows to display than the desired minimum amount of rows we skip this table.
		 */
		if ($i >= $this->minRows) {
			for ($i = count($content); $i < $settings['rows']; $i++) {
				if ($settings['size'] == 'small') {
					$content[] = array('&nbsp;', '', '');
				} elseif ($settings['size'] == 'large') {
					$content[] = array('&nbsp;', '', '', '');
				}
			}

			if (isset($settings['query_total'])) {
				$query_total = @mysqli_query($this->mysqli, $settings['query_total']) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
				$result_total = mysqli_fetch_object($query_total);
			}

			if ($settings['size'] == 'small') {
				$output .= '<table class="small">'
					.  '<tr><th colspan="3"><span class="left">'.htmlspecialchars($settings['head']).'</span>'.(empty($result_total->total) ? '' : '<span class="right">'.number_format($result_total->total).' total</span>').'</th></tr>'
					.  '<tr><td class="k1">'.htmlspecialchars($settings['key1']).'</td><td class="pos"></td><td class="k2">'.htmlspecialchars($settings['key2']).'</td></tr>';

				foreach ($content as $row) {
					$output .= '<tr><td class="v1">'.$row[1].'</td><td class="pos">'.$row[0].'</td><td class="v2">'.$row[2].'</td></tr>';
				}

				$output .= '</table>'."\n";
			} elseif ($settings['size'] == 'large') {
				$output .= '<table class="large">'
					.  '<tr><th colspan="4"><span class="left">'.htmlspecialchars($settings['head']).'</span>'.(empty($result_total->total) ? '' : '<span class="right">'.number_format($result_total->total).' total</span>').'</th></tr>'
					.  '<tr><td class="k1">'.htmlspecialchars($settings['key1']).'</td><td class="pos"></td><td class="k2">'.htmlspecialchars($settings['key2']).'</td><td class="k3">'.htmlspecialchars($settings['key3']).'</td></tr>';

				foreach ($content as $row) {
					$output .= '<tr><td class="v1">'.$row[1].'</td><td class="pos">'.$row[0].'</td><td class="v2">'.$row[2].'</td><td class="v3"><div>'.$row[3].'</div></td></tr>';
				}

				$output .= '</table>'."\n";
			}
		}

		return $output;
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
				$query = @mysqli_query($this->mysqli, 'SELECT `date`, `l_total`, `l_night`, `l_morning`, `l_afternoon`, `l_evening` FROM `channel` WHERE `date` > \''.date('Y-m-d', mktime(0, 0, 0, $this->month, $this->day - 24, $this->year)).'\'') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
				break;
			case 'months':
				$table_class = 'graph';
				$cols = 24;
				$query = @mysqli_query($this->mysqli, 'SELECT `date`, SUM(`l_total`) AS `l_total`, SUM(`l_night`) AS `l_night`, SUM(`l_morning`) AS `l_morning`, SUM(`l_afternoon`) AS `l_afternoon`, SUM(`l_evening`) AS `l_evening` FROM `channel` WHERE DATE_FORMAT(`date`, \'%Y-%m\') > \''.date('Y-m', mktime(0, 0, 0, $this->month - 24, 1, $this->year)).'\' GROUP BY YEAR(`date`), MONTH(`date`)') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
				break;
			case 'years':
				$table_class = 'yearly';
				$cols = $this->years;
				$query = @mysqli_query($this->mysqli, 'SELECT `date`, SUM(`l_total`) AS `l_total`, SUM(`l_night`) AS `l_night`, SUM(`l_morning`) AS `l_morning`, SUM(`l_afternoon`) AS `l_afternoon`, SUM(`l_evening`) AS `l_evening` FROM `channel` GROUP BY YEAR(`date`)') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
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
		$query = @mysqli_query($this->mysqli, 'SELECT SUM(`l_mon_night`) AS `l_mon_night`, SUM(`l_mon_morning`) AS `l_mon_morning`, SUM(`l_mon_afternoon`) AS `l_mon_afternoon`, SUM(`l_mon_evening`) AS `l_mon_evening`, SUM(`l_tue_night`) AS `l_tue_night`, SUM(`l_tue_morning`) AS `l_tue_morning`, SUM(`l_tue_afternoon`) AS `l_tue_afternoon`, SUM(`l_tue_evening`) AS `l_tue_evening`, SUM(`l_wed_night`) AS `l_wed_night`, SUM(`l_wed_morning`) AS `l_wed_morning`, SUM(`l_wed_afternoon`) AS `l_wed_afternoon`, SUM(`l_wed_evening`) AS `l_wed_evening`, SUM(`l_thu_night`) AS `l_thu_night`, SUM(`l_thu_morning`) AS `l_thu_morning`, SUM(`l_thu_afternoon`) AS `l_thu_afternoon`, SUM(`l_thu_evening`) AS `l_thu_evening`, SUM(`l_fri_night`) AS `l_fri_night`, SUM(`l_fri_morning`) AS `l_fri_morning`, SUM(`l_fri_afternoon`) AS `l_fri_afternoon`, SUM(`l_fri_evening`) AS `l_fri_evening`, SUM(`l_sat_night`) AS `l_sat_night`, SUM(`l_sat_morning`) AS `l_sat_morning`, SUM(`l_sat_afternoon`) AS `l_sat_afternoon`, SUM(`l_sat_evening`) AS `l_sat_evening`, SUM(`l_sun_night`) AS `l_sun_night`, SUM(`l_sun_morning`) AS `l_sun_morning`, SUM(`l_sun_afternoon`) AS `l_sun_afternoon`, SUM(`l_sun_evening`) AS `l_sun_evening` FROM `query_lines`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
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

	/**
	 * Create most active people tables.
	 */
	private function makeTable_MostActivePeople($settings)
	{
		switch ($settings['type']) {
			case 'alltime':
				$query = @mysqli_query($this->mysqli, 'SELECT `RUID`, `csNick`, `quote`, `l_total`, `l_night`, `l_morning`, `l_afternoon`, `l_evening` FROM `query_lines` JOIN `user_details` ON `query_lines`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `l_total` != 0 ORDER BY `l_total` DESC, `RUID` ASC LIMIT '.$settings['rows']) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
				$l_total = $this->l_total;
				$skipDetails = TRUE;
				break;
			case 'year':
				$query = @mysqli_query($this->mysqli, 'SELECT `RUID`, SUM(`l_total`) AS `l_total`, SUM(`l_night`) AS `l_night`, SUM(`l_morning`) AS `l_morning`, SUM(`l_afternoon`) AS `l_afternoon`, SUM(`l_evening`) AS `l_evening` FROM `user_activity` JOIN `user_status` ON `user_activity`.`UID` = `user_status`.`UID` WHERE (SELECT `status` FROM `user_status` AS `t1` WHERE `UID` = `user_status`.`RUID`) != 3 AND YEAR(`date`) = '.$this->year.' GROUP BY `RUID` ORDER BY `l_total` DESC, `RUID` ASC LIMIT '.$settings['rows']) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
				$query_l_total = @mysqli_query($this->mysqli, 'SELECT SUM(`l_total`) AS `l_total` FROM `user_activity` WHERE YEAR(`date`) = '.$this->year) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
				$result_l_total = mysqli_fetch_object($query_l_total);
				$l_total = $result_l_total->l_total;
				$skipDetails = FALSE;
				break;
			case 'month':
				$query = @mysqli_query($this->mysqli, 'SELECT `RUID`, SUM(`l_total`) AS `l_total`, SUM(`l_night`) AS `l_night`, SUM(`l_morning`) AS `l_morning`, SUM(`l_afternoon`) AS `l_afternoon`, SUM(`l_evening`) AS `l_evening` FROM `user_activity` JOIN `user_status` ON `user_activity`.`UID` = `user_status`.`UID` WHERE (SELECT `status` FROM `user_status` AS `t1` WHERE `UID` = `user_status`.`RUID`) != 3 AND YEAR(`date`) = '.$this->year.' AND MONTH(`date`) = '.$this->month.' GROUP BY `RUID` ORDER BY `l_total` DESC, `RUID` ASC LIMIT '.$settings['rows']) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
				$query_l_total = @mysqli_query($this->mysqli, 'SELECT SUM(`l_total`) AS `l_total` FROM `user_activity` WHERE YEAR(`date`) = '.$this->year.' AND MONTH(`date`) = '.$this->month) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
				$result_l_total = mysqli_fetch_object($query_l_total);
				$l_total = $result_l_total->l_total;
				$skipDetails = FALSE;
				break;
		}

		if (empty($l_total)) {
			return;
		}

		$output = '<table class="map"><tr><th colspan="7">'.htmlspecialchars($settings['head']).'</th></tr><tr><td class="k1">'.htmlspecialchars($settings['key1']).'</td><td class="k2">'.htmlspecialchars($settings['key2']).'</td><td class="pos"></td><td class="k3">'.htmlspecialchars($settings['key3']).'</td><td class="k4">'.htmlspecialchars($settings['key4']).'</td><td class="k5">'.htmlspecialchars($settings['key5']).'</td><td class="k6">'.htmlspecialchars($settings['key6']).'</td></tr>';
		$i = 0;

		while ($result = mysqli_fetch_object($query)) {
			$i++;
			$RUID = $result->RUID;

			if ($skipDetails) {
				$csNick = $result->csNick;
				$quote = $result->quote;
			} else {
				$query_details = @mysqli_query($this->mysqli, 'SELECT `csNick`, `quote` FROM `query_lines` JOIN `user_details` ON `query_lines`.`UID` = `user_details`.`UID` WHERE `query_lines`.`UID` = '.$RUID) or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
				$result_details = mysqli_fetch_object($query_details);
				$csNick = $result_details->csNick;
				$quote = $result_details->quote;
			}

			$l_total_percentage = number_format(($result->l_total / $l_total) * 100, 2);
			$query_lastSeen = @mysqli_query($this->mysqli, 'SELECT `lastSeen` FROM `user_details` JOIN `user_status` ON `user_details`.`UID` = `user_status`.`UID` WHERE `RUID` = '.$RUID.' ORDER BY `lastSeen` DESC LIMIT 1') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			$result_lastSeen = mysqli_fetch_object($query_lastSeen);
			$lastSeen = substr($result_lastSeen->lastSeen, 0, 10);
			$lastSeen = round((strtotime('today') - strtotime($lastSeen)) / 86400);

			if (($lastSeen / 365) >= 1) {
				$lastSeen = str_replace('.0', '', number_format($lastSeen / 365, 1));
				$lastSeen = $lastSeen.' Year'.($lastSeen > 1 ? 's' : '').' Ago';
			} elseif (($lastSeen / 30.42) >= 1) {
				$lastSeen = str_replace('.0', '', number_format($lastSeen / 30.42, 1));
				$lastSeen = $lastSeen.' Month'.($lastSeen > 1 ? 's' : '').' Ago';
			} elseif ($lastSeen == 1) {
				$lastSeen = 'Yesterday';
			} else {
				$lastSeen = $lastSeen.' Days Ago';
			}

			$when_width = 50;
			$times = array('night', 'morning', 'afternoon', 'evening');
			unset($l_night_width_real, $l_night_width, $l_morning_width_real, $l_morning_width, $l_afternoon_width_real, $l_afternoon_width, $l_evening_width_real, $l_evening_width, $remainders, $when_night, $when_morning, $when_afternoon, $when_evening);

			foreach ($times as $time) {
				if ($result->{'l_'.$time} != 0) {
					${'l_'.$time.'_width_real'} = ($result->{'l_'.$time} / $result->l_total) * 50;

					if (is_int(${'l_'.$time.'_width_real'})) {
						${'l_'.$time.'_width'} = ${'l_'.$time.'_width_real'};
						$when_width -= ${'l_'.$time.'_width'};
					} else {
						${'l_'.$time.'_width'} = floor(${'l_'.$time.'_width_real'});
						$when_width -= ${'l_'.$time.'_width'};
						$remainders[$time] = round((number_format(${'l_'.$time.'_width_real'}, 2) - ${'l_'.$time.'_width'}) * 100);
					}
				}
			}

			if (!empty($remainders)) {
				arsort($remainders);

				foreach ($remainders as $time => $remainder) {
					if ($when_width != 0) {
						$when_width--;
						${'when_'.$time} = '<img src="'.$this->{'bar_'.$time}.'" width="'.++${'l_'.$time.'_width'}.'" alt="" />';
					} else {
						${'when_'.$time} = '<img src="'.$this->{'bar_'.$time}.'" width="'.${'l_'.$time.'_width'}.'" alt="" />';
					}
				}
			} else {
				foreach ($times as $time) {
					if (!empty(${'l_'.$time.'_width'})) {
						${'when_'.$time} = '<img src="'.$this->{'bar_'.$time}.'" width="'.${'l_'.$time.'_width'}.'" alt="" />';
					}
				}
			}

			$when_output = '';

			foreach ($times as $time) {
				if (!empty(${'when_'.$time})) {
					$when_output .= ${'when_'.$time};
				}
			}

			$output .= '<tr><td class="v1">'.$l_total_percentage.'%</td><td class="v2">'.number_format($result->l_total).'</td><td class="pos">'.$i.'</td><td class="v3">'.($this->userstats ? '<a href="user.php?uid='.$RUID.'">'.htmlspecialchars($csNick).'</a>' : htmlspecialchars($csNick)).'</td><td class="v4">'.$when_output.'</td><td class="v5">'.$lastSeen.'</td><td class="v6"><div>'.htmlspecialchars($quote).'</div></td></tr>';
		}

		return $output.'</table>'."\n";
	}

	/**
	 * Create the most active times table.
	 */
	private function makeTable_MostActiveTimes($settings)
	{
		$query = @mysqli_query($this->mysqli, 'SELECT SUM(`l_00`) AS `l_00`, SUM(`l_01`) AS `l_01`, SUM(`l_02`) AS `l_02`, SUM(`l_03`) AS `l_03`, SUM(`l_04`) AS `l_04`, SUM(`l_05`) AS `l_05`, SUM(`l_06`) AS `l_06`, SUM(`l_07`) AS `l_07`, SUM(`l_08`) AS `l_08`, SUM(`l_09`) AS `l_09`, SUM(`l_10`) AS `l_10`, SUM(`l_11`) AS `l_11`, SUM(`l_12`) AS `l_12`, SUM(`l_13`) AS `l_13`, SUM(`l_14`) AS `l_14`, SUM(`l_15`) AS `l_15`, SUM(`l_16`) AS `l_16`, SUM(`l_17`) AS `l_17`, SUM(`l_18`) AS `l_18`, SUM(`l_19`) AS `l_19`, SUM(`l_20`) AS `l_20`, SUM(`l_21`) AS `l_21`, SUM(`l_22`) AS `l_22`, SUM(`l_23`) AS `l_23` FROM `channel`') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
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
	 * Create the time of day table.
	 */
	private function makeTable_TimeOfDay($settings)
	{
		$l_total_high = 0;
		$times = array('night', 'morning', 'afternoon', 'evening');

		foreach ($times as $time) {
			$query = @mysqli_query($this->mysqli, 'SELECT `csNick`, `l_'.$time.'` FROM `query_lines` JOIN `user_details` ON `query_lines`.`UID` = `user_details`.`UID` JOIN `user_status` ON `query_lines`.`UID` = `user_status`.`UID` WHERE `status` != 3 AND `l_'.$time.'` != 0 ORDER BY `l_'.$time.'` DESC, `csNick` ASC LIMIT 10');
			$i = 0;

			while ($result = mysqli_fetch_object($query)) {
				$i++;
				${$time}[$i]['user'] = $result->csNick;
				${$time}[$i]['lines'] = $result->{'l_'.$time};

				if ($i == 1 && ${$time}[$i]['lines'] > $l_total_high) {
					$l_total_high = ${$time}[$i]['lines'];
				}
			}
		}

		$width = (190 / $l_total_high);
		$output = '<table class="tod"><tr><th colspan="5">'.htmlspecialchars($settings['head']).'</th></tr><tr><td class="pos"></td><td class="k">'.htmlspecialchars($settings['key1']).'<br />0h - 5h</td><td class="k">'.htmlspecialchars($settings['key2']).'<br />6h - 11h</td><td class="k">'.htmlspecialchars($settings['key3']).'<br />12h - 17h</td><td class="k">'.htmlspecialchars($settings['key4']).'<br />18h - 23h</td></tr>';

		for ($i = 1; $i <= 10; $i++) {
			if (!isset($night[$i]['lines']) && !isset($morning[$i]['lines']) && !isset($afternoon[$i]['lines']) && !isset($evening[$i]['lines'])) {
				break;
			}

			$output .= '<tr><td class="pos">'.$i.'</td>';

			foreach ($times as $time) {
				if (isset(${$time}[$i]['lines'])) {
					if (round(${$time}[$i]['lines'] * $width) == 0) {
						$output .= '<td class="v">'.htmlspecialchars(${$time}[$i]['user']).' - '.number_format(${$time}[$i]['lines']).'</td>';
					} else {
						$output .= '<td class="v">'.htmlspecialchars(${$time}[$i]['user']).' - '.number_format(${$time}[$i]['lines']).'<br /><img src="'.$this->{'bar_'.$time}.'" width="'.round(${$time}[$i]['lines'] * $width).'" alt="" /></td>';
					}
				} else {
					$output .= '<td class="v"></td>';
				}
			}

			$output .= '</tr>';
		}

		return $output.'</table>'."\n";
	}

	/**
	 * Create the topics table.
	 */
	private function makeTable_Topics($settings)
	{
		$query = @mysqli_query($this->mysqli, 'SELECT `TID`, `setDate` FROM `user_topics` ORDER BY `setDate` ASC, `TID` ASC');
		$rows = mysqli_num_rows($query);
		$output = '';

		if (!empty($rows)) {
			$prevTID = 0;
			$prevDate = $this->date_first;
			$TIDs = array();

			while ($result = mysqli_fetch_object($query)) {
				$hoursPassed = floor((strtotime($result->setDate) - strtotime($prevDate)) / 3600);

				if ($prevTID != 0) {
					$topicTime[$prevTID] += $hoursPassed;
				}

				if (!in_array($result->TID, $TIDs)) {
					$TIDs[] = $result->TID;
					$topicTime[$result->TID] = 0;
				}

				$prevTID = $result->TID;
				$prevDate = $result->setDate;
			}

			/**
			 * Time between yesterday midnight and setDate from last topic will be added to the last topic.
			 */
			$hoursPassed = floor(((strtotime('yesterday') + 86400) - strtotime($prevDate)) / 3600);
			$topicTime[$prevTID] += $hoursPassed;

			/**
			 * Order the results and fill the longest standing topics table.
			 */
			arsort($topicTime);
			$i = 0;

			foreach ($topicTime as $TID => $hoursPassed) {
				if ($i >= $settings['rows']) {
					break;
				}

				$i++;
				$query_csTopic = @mysqli_query($this->mysqli, 'SELECT `csTopic` FROM `user_topics` WHERE `TID` = '.$TID.' ORDER BY `setDate` ASC LIMIT 1') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
				$result_csTopic = mysqli_fetch_object($query_csTopic);
				$query_csNick = @mysqli_query($this->mysqli, 'SELECT `csNick` FROM `user_details` JOIN `user_topics` ON `user_details`.`UID` = `user_topics`.`UID` WHERE `TID` = '.$TID.' ORDER BY `setDate` ASC LIMIT 1') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
				$result_csNick = mysqli_fetch_object($query_csNick);
				$content2[] = array($i, number_format(floor($hoursPassed / 24)), htmlspecialchars($result_csNick->csNick), htmlspecialchars($result_csTopic->csTopic));
			}

			/**
			* If there are less rows to display than the desired minimum amount of rows we skip this table.
			*/
			if ($i >= $this->minRows) {
				for ($i = count($content2); $i < $settings['rows']; $i++) {
					$content2[] = array('&nbsp;', '', '', '');
				}

				$output .= '<table class="large">'
					.  '<tr><th colspan="4"><span class="left">'.htmlspecialchars($settings['head2']).'</span></th></tr>'
					.  '<tr><td class="k1">'.htmlspecialchars($settings['key1a']).'</td><td class="pos"></td><td class="k2">'.htmlspecialchars($settings['key2']).'</td><td class="k3">'.htmlspecialchars($settings['key3']).'</td></tr>';

				foreach ($content2 as $row) {
					$output .= '<tr><td class="v1">'.$row[1].'</td><td class="pos">'.$row[0].'</td><td class="v2">'.$row[2].'</td><td class="v3">'.$row[3].'</td></tr>';
				}

				$output .= '</table>'."\n";
			}

			/**
			 * Most recent topics table.
			 */
			$query = @mysqli_query($this->mysqli, 'SELECT DISTINCT(`TID`), `setDate` FROM `user_topics` ORDER BY `setDate` DESC LIMIT 5') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
			$i = 0;

			while ($result = mysqli_fetch_object($query)) {
				$i++;
				$query_csTopic = @mysqli_query($this->mysqli, 'SELECT `csTopic` FROM `user_topics` WHERE `TID` = '.$result->TID.' ORDER BY `setDate` ASC LIMIT 1') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
				$result_csTopic = mysqli_fetch_object($query_csTopic);
				$query_csNick = @mysqli_query($this->mysqli, 'SELECT `csNick` FROM `user_details` JOIN `user_topics` ON `user_details`.`UID` = `user_topics`.`UID` WHERE `TID` = '.$result->TID.' ORDER BY `setDate` ASC LIMIT 1') or $this->output('critical', 'MySQLi: '.mysqli_error($this->mysqli));
				$result_csNick = mysqli_fetch_object($query_csNick);
				$daysPassed = round(((strtotime('yesterday') + 86400) - strtotime($result->setDate)) / 86400);
				$content1[] = array($i, number_format($daysPassed), htmlspecialchars($result_csNick->csNick), htmlspecialchars($result_csTopic->csTopic));
			}

			/**
			* If there are less rows to display than the desired minimum amount of rows we skip this table.
			*/
			if ($i >= $this->minRows) {
				for ($i = count($content1); $i < $settings['rows']; $i++) {
					$content1[] = array('&nbsp;', '', '', '');
				}

				$output .= '<table class="large">'
					.  '<tr><th colspan="4"><span class="left">'.htmlspecialchars($settings['head1']).'</span></th></tr>'
					.  '<tr><td class="k1">'.htmlspecialchars($settings['key1b']).'</td><td class="pos"></td><td class="k2">'.htmlspecialchars($settings['key2']).'</td><td class="k3">'.htmlspecialchars($settings['key3']).'</td></tr>';

				foreach ($content1 as $row) {
					$output .= '<tr><td class="v1">'.$row[1].'</td><td class="pos">'.$row[0].'</td><td class="v2">'.$row[2].'</td><td class="v3">'.$row[3].'</td></tr>';
				}

				$output .= '</table>'."\n";
			}

		}

		return $output;
	}
}

?>
