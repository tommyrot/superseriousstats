<?php

/**
 * Copyright (c) 2007-2013, Jos de Ruijter <jos@dutnie.nl>
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
	private $maxrows = 5;
	private $maxrows_people2 = 10;
	private $maxrows_people_alltime = 30;
	private $maxrows_people_month = 10;
	private $maxrows_people_timeofday = 10;
	private $maxrows_people_year = 10;
	private $maxrows_recenturls = 25;
	private $minrows = 3;
	private $recenturls_type = 1;
	private $rows_domains_tlds = 10;
	private $sectionbits = 255;
	private $stylesheet = 'sss.css';
	private $userstats = false;

	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $color = array(
		'night' => 'b',
		'morning' => 'g',
		'afternoon' => 'y',
		'evening' => 'r');
	private $currentyear = 0;
	private $date_first = '';
	private $date_last = '';
	private $date_lastlogparsed = '';
	private $date_max = '';
	private $dayofmonth = 0;
	private $days = 0;
	private $daysleft = 0;
	private $estimate = false;
	private $l_avg = 0;
	private $l_max = 0;
	private $l_total = 0;
	private $month = 0;
	private $monthname = '';
	private $output = '';
	private $settings_list = array(
		'addhtml_foot' => 'string',
		'addhtml_head' => 'string',
		'channel' => 'string',
		'cid' => 'string',
		'history' => 'bool',
		'maxrows' => 'int',
		'maxrows_people2' => 'int',
		'maxrows_people_alltime' => 'int',
		'maxrows_people_month' => 'int',
		'maxrows_people_timeofday' => 'int',
		'maxrows_people_year' => 'int',
		'maxrows_recenturls' => 'int',
		'minrows' => 'int',
		'outputbits' => 'int',
		'recenturls_type' => 'int',
		'rows_domains_tlds' => 'int',
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
	public function make_html($sqlite3)
	{
		$this->output('notice', 'make_html(): creating statspage');

		if (($this->l_total = @$sqlite3->querySingle('SELECT SUM(l_total) FROM channel')) === false) {
			$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		/**
		 * Stop if the channel has no logged activity. Everything beyond this point expects a non empty database.
		 */
		if (is_null($this->l_total)) {
			$this->output('warning', 'make_html(): database is empty, nothing to do');
			return 'No data.';
		}

		if (($result = @$sqlite3->querySingle('SELECT MIN(date) AS date_first, MAX(date) AS date_last FROM channel', true)) === false) {
			$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$this->date_first = $result['date_first'];
		$this->date_last = $result['date_last'];

		if (($result = @$sqlite3->querySingle('SELECT COUNT(*) AS days, MAX(date) AS date FROM parse_history', true)) === false) {
			$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$this->days = $result['days'];
		$this->l_avg = $this->l_total / $this->days;

		/**
		 * Date and time variables used throughout the script. These are based on the date of the last logfile parsed and used to define our scope.
		 */
		$this->date_lastlogparsed = $result['date'];
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
			if (($activity = @$sqlite3->querySingle('SELECT COUNT(*) FROM q_activity_by_day WHERE date > \''.date('Y-m-d', mktime(0, 0, 0, $this->month, $this->dayofmonth - 90, $this->year)).'\'')) === false) {
				$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			if ($activity > 0) {
				$this->estimate = true;
			}
		}

		/**
		 * HTML Head.
		 */
		if (($result = @$sqlite3->querySingle('SELECT MIN(date) AS date, l_total FROM channel WHERE l_total = (SELECT MAX(l_total) FROM channel)', true)) === false) {
			$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$this->date_max = $result['date'];
		$this->l_max = $result['l_total'];
		$this->output = '<!DOCTYPE html>'."\n\n"
			      . '<html>'."\n\n"
			      . '<head>'."\n"
			      . '<meta charset="utf-8">'."\n"
			      . '<title>'.htmlspecialchars($this->channel).', seriously.</title>'."\n"
			      . '<link rel="stylesheet" href="'.$this->stylesheet.'">'."\n"
			      . '<style type="text/css">'."\n"
			      . '  .act-year { width:'.(2 + (($this->years + ($this->estimate ? 1 : 0)) * 34)).'px }'."\n"
			      . '</style>'."\n"
			      . '</head>'."\n\n"
			      . '<body><div id="container">'."\n"
			      . '<div class="info">'.htmlspecialchars($this->channel).', seriously.<br><br>'
			      . number_format($this->days).' day'.($this->days > 1 ? 's logged from '.date('M j, Y', strtotime($this->date_first)).' to '.date('M j, Y', strtotime($this->date_last)) : ' logged on '.date('M j, Y', strtotime($this->date_first))).'.<br><br>'
			      . 'Logs contain '.number_format($this->l_total).' line'.($this->l_total > 1 ? 's' : '').' &ndash; an average of '.number_format($this->l_avg).' line'.($this->l_avg > 1 ? 's' : '').' per day.<br>'
			      . 'Most active day was '.date('M j, Y', strtotime($this->date_max)).' with a total of '.number_format($this->l_max).' line'.($this->l_max > 1 ? 's' : '').' typed.'.($this->addhtml_head != '' ? '<br><br>'.trim(@file_get_contents($this->addhtml_head)) : '').'</div>'."\n";

		/**
		 * Activity section.
		 */
		if ($this->sectionbits & 1) {
			$this->output .= '<div class="section">Activity</div>'."\n";
			$this->output .= $this->make_table_activity_distribution_hour($sqlite3);
			$this->output .= $this->make_table_activity($sqlite3, 'day');
			$this->output .= $this->make_table_activity($sqlite3, 'month');
			$this->output .= $this->make_table_activity_distribution_day($sqlite3);
			$this->output .= $this->make_table_activity($sqlite3, 'year');
			$this->output .= $this->make_table_people($sqlite3, 'alltime');
			$this->output .= $this->make_table_people2($sqlite3);
			$this->output .= $this->make_table_people($sqlite3, 'year');
			$this->output .= $this->make_table_people($sqlite3, 'month');
			$this->output .= $this->make_table_people_timeofday($sqlite3);
		}

		/**
		 * General Chat section.
		 */
		if ($this->sectionbits & 2) {
			$output = '';

			$t = new table('Most Talkative Chatters', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Lines/Day',
				'k2' => 'User',
				'v1' => 'float',
				'v2' => 'string'));
			$t->set_value('queries', array('main' => 'SELECT CAST(l_total AS REAL) / activedays AS v1, csnick AS v2 FROM q_lines JOIN user_details ON q_lines.ruid = user_details.uid JOIN user_status ON q_lines.ruid = user_status.uid WHERE status != 3 AND activedays >= 7 AND lasttalked >= \''.date('Y-m-d 00:00:00', mktime(0, 0, 0, $this->month, $this->dayofmonth - 30, $this->year)).'\' ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows));
			$output .= $t->make_table($sqlite3);

			$t = new table('Most Fluent Chatters', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Words/Line',
				'k2' => 'User',
				'v1' => 'float',
				'v2' => 'string'));
			$t->set_value('queries', array('main' => 'SELECT CAST(words AS REAL) / l_total AS v1, csnick AS v2 FROM q_lines JOIN user_details ON q_lines.ruid = user_details.uid JOIN user_status ON q_lines.ruid = user_status.uid WHERE status != 3 AND activedays >= 7 AND lasttalked >= \''.date('Y-m-d 00:00:00', mktime(0, 0, 0, $this->month, $this->dayofmonth - 30, $this->year)).'\' ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows));
			$output .= $t->make_table($sqlite3);

			$t = new table('Most Tedious Chatters', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Chars/Line',
				'k2' => 'User',
				'v1' => 'float',
				'v2' => 'string'));
			$t->set_value('queries', array('main' => 'SELECT CAST(characters AS REAL) / l_total AS v1, csnick AS v2 FROM q_lines JOIN user_details ON q_lines.ruid = user_details.uid JOIN user_status ON q_lines.ruid = user_status.uid WHERE status != 3 AND activedays >= 7 AND lasttalked >= \''.date('Y-m-d 00:00:00', mktime(0, 0, 0, $this->month, $this->dayofmonth - 30, $this->year)).'\' ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows));
			$output .= $t->make_table($sqlite3);

			$t = new table('Individual Top Days &ndash; Alltime', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Lines',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string'));
			$t->set_value('queries', array('main' => 'SELECT MAX(l_total) AS v1, csnick AS v2 FROM q_activity_by_day JOIN user_status ON q_activity_by_day.ruid = user_status.uid JOIN user_details ON q_activity_by_day.ruid = user_details.uid WHERE status != 3 GROUP BY q_activity_by_day.ruid ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows));
			$output .= $t->make_table($sqlite3);

			$t = new table('Individual Top Days &ndash; '.$this->year, $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Lines',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string'));
			$t->set_value('queries', array('main' => 'SELECT MAX(l_total) AS v1, csnick AS v2 FROM q_activity_by_day JOIN user_status ON q_activity_by_day.ruid = user_status.uid JOIN user_details ON q_activity_by_day.ruid = user_details.uid WHERE status != 3 AND date LIKE \''.$this->year.'%\' GROUP BY q_activity_by_day.ruid ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows));
			$output .= $t->make_table($sqlite3);

			$t = new table('Individual Top Days &ndash; '.$this->monthname.' '.$this->year, $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Lines',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string'));
			$t->set_value('queries', array('main' => 'SELECT MAX(l_total) AS v1, csnick AS v2 FROM q_activity_by_day JOIN user_status ON q_activity_by_day.ruid = user_status.uid JOIN user_details ON q_activity_by_day.ruid = user_details.uid WHERE status != 3 AND date LIKE \''.date('Y-m', strtotime($this->date_lastlogparsed)).'%\' GROUP BY q_activity_by_day.ruid ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows));
			$output .= $t->make_table($sqlite3);

			$t = new table('Most Active Chatters &ndash; Alltime', $this->minrows, $this->maxrows);
			$t->set_value('decimals', 2);
			$t->set_value('percentage', true);
			$t->set_value('keys', array(
				'k1' => 'Activity',
				'k2' => 'User',
				'v1' => 'float',
				'v2' => 'string'));
			$t->set_value('queries', array('main' => 'SELECT (CAST(activedays AS REAL) / '.$this->days.') * 100 AS v1, csnick AS v2 FROM user_status JOIN q_lines ON user_status.uid = q_lines.ruid JOIN user_details ON user_status.uid = user_details.uid WHERE status != 3 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows));
			$output .= $t->make_table($sqlite3);

			$t = new table('Most Active Chatters &ndash; '.$this->year, $this->minrows, $this->maxrows);
			$t->set_value('decimals', 2);
			$t->set_value('percentage', true);
			$t->set_value('keys', array(
				'k1' => 'Activity',
				'k2' => 'User',
				'v1' => 'float',
				'v2' => 'string'));
			$t->set_value('queries', array('main' => 'SELECT (CAST(COUNT(DISTINCT date) AS REAL) / (SELECT COUNT(*) FROM parse_history WHERE date LIKE \''.$this->year.'%\')) * 100 AS v1, csnick AS v2 FROM q_activity_by_day JOIN user_status ON q_activity_by_day.ruid = user_status.uid JOIN user_details ON q_activity_by_day.ruid = user_details.uid WHERE status != 3 AND date LIKE \''.$this->year.'%\' GROUP BY q_activity_by_day.ruid ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows));
			$output .= $t->make_table($sqlite3);

			$t = new table('Most Active Chatters &ndash; '.$this->monthname.' '.$this->year, $this->minrows, $this->maxrows);
			$t->set_value('decimals', 2);
			$t->set_value('percentage', true);
			$t->set_value('keys', array(
				'k1' => 'Activity',
				'k2' => 'User',
				'v1' => 'float',
				'v2' => 'string'));
			$t->set_value('queries', array('main' => 'SELECT (CAST(COUNT(DISTINCT date) AS REAL) / (SELECT COUNT(*) FROM parse_history WHERE date LIKE \''.date('Y-m', strtotime($this->date_lastlogparsed)).'%\')) * 100 AS v1, csnick AS v2 FROM q_activity_by_day JOIN user_status ON q_activity_by_day.ruid = user_status.uid JOIN user_details ON q_activity_by_day.ruid = user_details.uid WHERE status != 3 AND date LIKE \''.date('Y-m', strtotime($this->date_lastlogparsed)).'%\' GROUP BY q_activity_by_day.ruid ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows));
			$output .= $t->make_table($sqlite3);

			$t = new table('Exclamations', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Total',
				'k2' => 'User',
				'k3' => 'Example',
				'v1' => 'int',
				'v2' => 'string',
				'v3' => 'string'));
			$t->set_value('queries', array(
				'main' => 'SELECT exclamations AS v1, csnick AS v2, ex_exclamations AS v3 FROM q_lines JOIN user_details ON q_lines.ruid = user_details.uid JOIN user_status ON q_lines.ruid = user_status.uid WHERE status != 3 AND exclamations != 0 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(exclamations) FROM q_lines'));
			$output .= $t->make_table($sqlite3);

			$t = new table('Questions', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Total',
				'k2' => 'User',
				'k3' => 'Example',
				'v1' => 'int',
				'v2' => 'string',
				'v3' => 'string'));
			$t->set_value('queries', array(
				'main' => 'SELECT questions AS v1, csnick AS v2, ex_questions AS v3 FROM q_lines JOIN user_details ON q_lines.ruid = user_details.uid JOIN user_status ON q_lines.ruid = user_status.uid WHERE status != 3 AND questions != 0 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(questions) FROM q_lines'));
			$output .= $t->make_table($sqlite3);

			$t = new table('UPPERCASED Lines', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Total',
				'k2' => 'User',
				'k3' => 'Example',
				'v1' => 'int',
				'v2' => 'string',
				'v3' => 'string'));
			$t->set_value('queries', array(
				'main' => 'SELECT uppercased AS v1, csnick AS v2, ex_uppercased AS v3 FROM q_lines JOIN user_details ON q_lines.ruid = user_details.uid JOIN user_status ON q_lines.ruid = user_status.uid WHERE status != 3 AND uppercased != 0 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(uppercased) FROM q_lines'));
			$output .= $t->make_table($sqlite3);

			$t = new table('Monologues', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Total',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string'));
			$t->set_value('queries', array(
				'main' => 'SELECT monologues AS v1, csnick AS v2 FROM q_lines JOIN user_details ON q_lines.ruid = user_details.uid JOIN user_status ON q_lines.ruid = user_status.uid WHERE status != 3 AND monologues != 0 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(monologues) FROM q_lines'));
			$output .= $t->make_table($sqlite3);

			$t = new table('Longest Monologue', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Lines',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string'));
			$t->set_value('queries', array('main' => 'SELECT topmonologue AS v1, csnick AS v2 FROM q_lines JOIN user_details ON q_lines.ruid = user_details.uid JOIN user_status ON q_lines.ruid = user_status.uid WHERE status != 3 AND topmonologue != 0 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows));
			$output .= $t->make_table($sqlite3);

			$t = new table('Moodiest People', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Smileys',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string'));
			$t->set_value('queries', array(
				'main' => 'SELECT s_01 + s_02 + s_03 + s_04 + s_05 + s_06 + s_07 + s_08 + s_09 + s_10 + s_11 + s_12 + s_13 + s_14 + s_15 + s_16 + s_17 + s_18 + s_19 + s_20 + s_21 + s_22 + s_23 + s_24 + s_25 + s_26 + s_27 + s_28 + s_29 + s_30 + s_31 + s_32 + s_33 + s_34 + s_35 + s_36 + s_37 + s_38 + s_39 + s_40 + s_41 + s_42 + s_43 + s_44 + s_45 + s_46 + s_47 + s_48 + s_49 + s_50 AS v1, csnick AS v2 FROM q_smileys JOIN user_details ON q_smileys.ruid = user_details.uid JOIN user_status ON q_smileys.ruid = user_status.uid WHERE status != 3 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(s_01) + SUM(s_02) + SUM(s_03) + SUM(s_04) + SUM(s_05) + SUM(s_06) + SUM(s_07) + SUM(s_08) + SUM(s_09) + SUM(s_10) + SUM(s_11) + SUM(s_12) + SUM(s_13) + SUM(s_14) + SUM(s_15) + SUM(s_16) + SUM(s_17) + SUM(s_18) + SUM(s_19) + SUM(s_20) + SUM(s_21) + SUM(s_22) + SUM(s_23) + SUM(s_24) + SUM(s_25) + SUM(s_26) + SUM(s_27) + SUM(s_28) + SUM(s_29) + SUM(s_30) + SUM(s_31) + SUM(s_32) + SUM(s_33) + SUM(s_34) + SUM(s_35) + SUM(s_36) + SUM(s_37) + SUM(s_38) + SUM(s_39) + SUM(s_40) + SUM(s_41) + SUM(s_42) + SUM(s_43) + SUM(s_44) + SUM(s_45) + SUM(s_46) + SUM(s_47) + SUM(s_48) + SUM(s_49) + SUM(s_50) FROM q_smileys'));
			$output .= $t->make_table($sqlite3);

			$t = new table('Slaps Given', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Total',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string'));
			$t->set_value('queries', array(
				'main' => 'SELECT slaps AS v1, csnick AS v2 FROM q_lines JOIN user_details ON q_lines.ruid = user_details.uid JOIN user_status ON q_lines.ruid = user_status.uid WHERE status != 3 AND slaps != 0 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(slaps) FROM q_lines'));
			$output .= $t->make_table($sqlite3);

			$t = new table('Slaps Received', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Total',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string'));
			$t->set_value('queries', array(
				'main' => 'SELECT slapped AS v1, csnick AS v2 FROM q_lines JOIN user_details ON q_lines.ruid = user_details.uid JOIN user_status ON q_lines.ruid = user_status.uid WHERE status != 3 AND slapped != 0 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(slapped) FROM q_lines'));
			$output .= $t->make_table($sqlite3);

			$t = new table('Most Lively Bots', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Lines',
				'k2' => 'Bot',
				'v1' => 'int',
				'v2' => 'string'));
			$t->set_value('queries', array('main' => 'SELECT l_total AS v1, csnick AS v2 FROM q_lines JOIN user_details ON q_lines.ruid = user_details.uid JOIN user_status ON q_lines.ruid = user_status.uid WHERE status = 3 AND l_total != 0 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows));
			$output .= $t->make_table($sqlite3);

			$t = new table('Actions Performed', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Total',
				'k2' => 'User',
				'k3' => 'Example',
				'v1' => 'int',
				'v2' => 'string',
				'v3' => 'string'));
			$t->set_value('queries', array(
				'main' => 'SELECT actions AS v1, csnick AS v2, ex_actions AS v3 FROM q_lines JOIN user_details ON q_lines.ruid = user_details.uid JOIN user_status ON q_lines.ruid = user_status.uid WHERE status != 3 AND actions != 0 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(actions) FROM q_lines'));
			$output .= $t->make_table($sqlite3);

			if (!empty($output)) {
				$this->output .= '<div class="section">General Chat</div>'."\n".$output;
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
				'm_op' => 'Ops \'+o\' Given',
				'm_opped' => 'Ops \'+o\' Received',
				'm_deop' => 'deOps \'-o\' Given',
				'm_deopped' => 'deOps \'-o\' Received',
				'm_voice' => 'Voices \'+v\' Given',
				'm_voiced' => 'Voices \'+v\' Received',
				'm_devoice' => 'deVoices \'-v\' Given',
				'm_devoiced' => 'deVoices \'-v\' Received');

			foreach ($modes as $key => $value) {
				$t = new table($value, $this->minrows, $this->maxrows);
				$t->set_value('keys', array(
					'k1' => 'Total',
					'k2' => 'User',
					'v1' => 'int',
					'v2' => 'string'));
				$t->set_value('queries', array(
					'main' => 'SELECT '.$key.' AS v1, csnick AS v2 FROM q_events JOIN user_details ON q_events.ruid = user_details.uid JOIN user_status ON q_events.ruid = user_status.uid WHERE status != 3 AND '.$key.' != 0 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows,
					'total' => 'SELECT SUM('.$key.') FROM q_events'));
				$output .= $t->make_table($sqlite3);
			}

			if (!empty($output)) {
				$this->output .= '<div class="section">Modes</div>'."\n".$output;
			}
		}

		/**
		 * Events section.
		 */
		if ($this->sectionbits & 8) {
			$output = '';

			$t = new table('Kicks Given', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Total',
				'k2' => 'User',
				'k3' => 'Example',
				'v1' => 'int',
				'v2' => 'string',
				'v3' => 'string'));
			$t->set_value('queries', array(
				'main' => 'SELECT kicks AS v1, csnick AS v2, ex_kicks AS v3 FROM q_events JOIN user_details ON q_events.ruid = user_details.uid JOIN user_status ON q_events.ruid = user_status.uid WHERE status != 3 AND kicks != 0 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(kicks) FROM q_events'));
			$output .= $t->make_table($sqlite3);

			$t = new table('Kicks Received', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Total',
				'k2' => 'User',
				'k3' => 'Example',
				'v1' => 'int',
				'v2' => 'string',
				'v3' => 'string'));
			$t->set_value('queries', array(
				'main' => 'SELECT kicked AS v1, csnick AS v2, ex_kicked AS v3 FROM q_events JOIN user_details ON q_events.ruid = user_details.uid JOIN user_status ON q_events.ruid = user_status.uid WHERE status != 3 AND kicked != 0 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(kicked) FROM q_events'));
			$output .= $t->make_table($sqlite3);

			$t = new table('Channel Joins', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Total',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string'));
			$t->set_value('queries', array(
				'main' => 'SELECT joins AS v1, csnick AS v2 FROM q_events JOIN user_details ON q_events.ruid = user_details.uid JOIN user_status ON q_events.ruid = user_status.uid WHERE status != 3 AND joins != 0 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(joins) FROM q_events'));
			$output .= $t->make_table($sqlite3);

			$t = new table('Channel Parts', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Total',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string'));
			$t->set_value('queries', array(
				'main' => 'SELECT parts AS v1, csnick AS v2 FROM q_events JOIN user_details ON q_events.ruid = user_details.uid JOIN user_status ON q_events.ruid = user_status.uid WHERE status != 3 AND parts != 0 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(parts) FROM q_events'));
			$output .= $t->make_table($sqlite3);

			$t = new table('IRC Quits', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Total',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string'));
			$t->set_value('queries', array(
				'main' => 'SELECT quits AS v1, csnick AS v2 FROM q_events JOIN user_details ON q_events.ruid = user_details.uid JOIN user_status ON q_events.ruid = user_status.uid WHERE status != 3 AND quits != 0 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(quits) FROM q_events'));
			$output .= $t->make_table($sqlite3);

			$t = new table('Nick Changes', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Total',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string'));
			$t->set_value('queries', array(
				'main' => 'SELECT nickchanges AS v1, csnick AS v2 FROM q_events JOIN user_details ON q_events.ruid = user_details.uid JOIN user_status ON q_events.ruid = user_status.uid WHERE status != 3 AND nickchanges != 0 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(nickchanges) FROM q_events'));
			$output .= $t->make_table($sqlite3);

			$t = new table('Aliases', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Total',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string'));
			$t->set_value('queries', array(
				'main' => 'SELECT COUNT(*) AS v1, csnick AS v2 FROM user_status JOIN user_details ON user_status.ruid = user_details.uid GROUP BY ruid ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT COUNT(*) FROM user_status'));
			$output .= $t->make_table($sqlite3);

			$t = new table('Topics Set', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Total',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string'));
			$t->set_value('queries', array(
				'main' => 'SELECT topics AS v1, csnick AS v2 FROM q_events JOIN user_details ON q_events.ruid = user_details.uid JOIN user_status ON q_events.ruid = user_status.uid WHERE status != 3 AND topics != 0 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(topics) FROM q_events'));
			$output .= $t->make_table($sqlite3);

			$t = new table('Most Recent Topics', $this->minrows, $this->maxrows);
			$t->set_value('v3a', true);
			$t->set_value('keys', array(
				'k1' => 'Date',
				'k2' => 'User',
				'k3' => 'Topic',
				'v1' => 'date',
				'v2' => 'string',
				'v3' => 'string-url'));
			$t->set_value('queries', array('main' => 'SELECT datetime AS v1, csnick AS v2, topic AS v3 FROM user_topics JOIN topics ON user_topics.tid = topics.tid JOIN user_status ON user_topics.uid = user_status.uid JOIN user_details ON user_status.ruid = user_details.uid ORDER BY v1 DESC LIMIT '.$this->maxrows));
			$output .= $t->make_table($sqlite3);

			if (!empty($output)) {
				$this->output .= '<div class="section">Events</div>'."\n".$output;
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
			if (($result = @$sqlite3->querySingle('SELECT SUM(s_01) AS s_01, SUM(s_02) AS s_02, SUM(s_03) AS s_03, SUM(s_04) AS s_04, SUM(s_05) AS s_05, SUM(s_06) AS s_06, SUM(s_07) AS s_07, SUM(s_08) AS s_08, SUM(s_09) AS s_09, SUM(s_10) AS s_10, SUM(s_11) AS s_11, SUM(s_12) AS s_12, SUM(s_13) AS s_13, SUM(s_14) AS s_14, SUM(s_15) AS s_15, SUM(s_16) AS s_16, SUM(s_17) AS s_17, SUM(s_18) AS s_18, SUM(s_19) AS s_19, SUM(s_20) AS s_20, SUM(s_21) AS s_21, SUM(s_22) AS s_22, SUM(s_23) AS s_23, SUM(s_24) AS s_24, SUM(s_25) AS s_25, SUM(s_26) AS s_26, SUM(s_27) AS s_27, SUM(s_28) AS s_28, SUM(s_29) AS s_29, SUM(s_30) AS s_30, SUM(s_31) AS s_31, SUM(s_32) AS s_32, SUM(s_33) AS s_33, SUM(s_34) AS s_34, SUM(s_35) AS s_35, SUM(s_36) AS s_36, SUM(s_37) AS s_37, SUM(s_38) AS s_38, SUM(s_39) AS s_39, SUM(s_40) AS s_40, SUM(s_41) AS s_41, SUM(s_42) AS s_42, SUM(s_43) AS s_43, SUM(s_44) AS s_44, SUM(s_45) AS s_45, SUM(s_46) AS s_46, SUM(s_47) AS s_47, SUM(s_48) AS s_48, SUM(s_49) AS s_49, SUM(s_50) AS s_50 FROM q_smileys', true)) === false) {
				$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			if (!empty($result)) {
				foreach ($result as $key => $value) {
					if ($value > 0) {
						$smileys_totals[$key] = $value;
					}
				}

				if (!empty($smileys_totals)) {
					arsort($smileys_totals);
					array_splice($smileys_totals, 9);

					foreach ($smileys_totals as $key => $value) {
						$t = new table($smileys[$key][1], $this->minrows, $this->maxrows);
						$t->set_value('keys', array(
							'k1' => htmlspecialchars($smileys[$key][0]),
							'k2' => 'User',
							'v1' => 'int',
							'v2' => 'string'));
						$t->set_value('queries', array('main' => 'SELECT '.$key.' AS v1, csnick AS v2 FROM q_smileys JOIN user_details ON q_smileys.ruid = user_details.uid JOIN user_status ON q_smileys.ruid = user_status.uid WHERE status != 3 AND '.$key.' != 0 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows));
						$t->set_value('total', $value);
						$output .= $t->make_table($sqlite3);
					}
				}

				if (!empty($output)) {
					$this->output .= '<div class="section">Smileys</div>'."\n".$output;
				}
			}
		}

		/**
		 * URLs section.
		 */
		if ($this->sectionbits & 32) {
			$output = '';

			$t = new table('Most Referenced Domain Names', $this->rows_domains_tlds, $this->rows_domains_tlds);
			$t->set_value('keys', array(
				'k1' => 'Total',
				'k2' => 'Domain',
				'k3' => 'First Used',
				'v1' => 'int',
				'v2' => 'url',
				'v3' => 'date'));
			$t->set_value('queries', array('main' => 'SELECT COUNT(*) AS v1, \'http://\' || fqdn AS v2, MIN(datetime) AS v3 FROM user_urls JOIN urls ON user_urls.lid = urls.lid JOIN fqdns ON urls.fid = fqdns.fid GROUP BY urls.fid ORDER BY v1 DESC, v3 ASC LIMIT '.$this->rows_domains_tlds));
			$t->set_value('medium', true);
			$output .= $t->make_table($sqlite3);

			$t = new table('Most Referenced TLDs', $this->rows_domains_tlds, $this->rows_domains_tlds);
			$t->set_value('keys', array(
				'k1' => 'Total',
				'k2' => 'TLD',
				'v1' => 'int',
				'v2' => 'string'));
			$t->set_value('queries', array('main' => 'SELECT COUNT(*) AS v1, tld AS v2 FROM user_urls JOIN urls ON user_urls.lid = urls.lid WHERE tld IS NOT NULL GROUP BY tld ORDER BY v1 DESC, v2 ASC LIMIT '.$this->rows_domains_tlds));
			$output .= $t->make_table($sqlite3);

			if ($this->recenturls_type != 0) {
				$t = new table('Most Recent URLs', $this->minrows, $this->maxrows_recenturls);
				$t->set_value('keys', array(
					'k1' => 'Date',
					'k2' => 'User',
					'k3' => 'URL',
					'v1' => 'date-norepeat',
					'v2' => 'string',
					'v3' => 'url'));

				if ($this->recenturls_type == 1) {
					$t->set_value('queries', array('main' => 'SELECT datetime AS v1, csnick AS v2, url AS v3 FROM user_urls JOIN urls ON user_urls.lid = urls.lid JOIN user_status ON user_urls.uid = user_status.uid JOIN user_details ON user_status.ruid = user_details.uid ORDER BY v1 DESC LIMIT '.$this->maxrows_recenturls));
				} elseif ($this->recenturls_type == 2) {
					$t->set_value('queries', array('main' => 'SELECT datetime AS v1, csnick AS v2, url AS v3 FROM user_urls JOIN urls ON user_urls.lid = urls.lid JOIN user_status ON user_urls.uid = user_status.uid JOIN user_details ON user_status.ruid = user_details.uid WHERE ruid NOT IN (SELECT ruid FROM user_status WHERE status = 3) ORDER BY v1 DESC LIMIT '.$this->maxrows_recenturls));
				}

				$output .= $t->make_table($sqlite3);
			}

			$t = new table('URLs by Users', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Total',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string'));
			$t->set_value('queries', array(
				'main' => 'SELECT urls AS v1, csnick AS v2 FROM q_lines JOIN user_details ON q_lines.ruid = user_details.uid JOIN user_status ON q_lines.ruid = user_status.uid WHERE status != 3 AND urls != 0 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(urls) FROM q_lines JOIN user_status ON q_lines.ruid = user_status.uid WHERE status != 3'));
			$output .= $t->make_table($sqlite3);

			$t = new table('URLs by Bots', $this->minrows, $this->maxrows);
			$t->set_value('keys', array(
				'k1' => 'Total',
				'k2' => 'Bot',
				'v1' => 'int',
				'v2' => 'string'));
			$t->set_value('queries', array(
				'main' => 'SELECT urls AS v1, csnick AS v2 FROM q_lines JOIN user_details ON q_lines.ruid = user_details.uid JOIN user_status ON q_lines.ruid = user_status.uid WHERE status = 3 AND urls != 0 ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(urls) FROM q_lines JOIN user_status ON q_lines.ruid = user_status.uid WHERE status = 3'));
			$output .= $t->make_table($sqlite3);

			if (!empty($output)) {
				$this->output .= '<div class="section">URLs</div>'."\n".$output;
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
			$query = @$sqlite3->query('SELECT length, COUNT(*) AS total FROM words GROUP BY length ORDER BY total DESC, length DESC LIMIT 9') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$result = $query->fetchArray(SQLITE3_ASSOC);

			if ($result !== false) {
				$query->reset();

				while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
					$t = new table('Words of '.$result['length'].' Characters', $this->minrows, $this->maxrows);
					$t->set_value('keys', array(
						'k1' => 'Times Used',
						'k2' => 'Word',
						'v1' => 'int',
						'v2' => 'string'));
					$t->set_value('queries', array('main' => 'SELECT total AS v1, word AS v2 FROM words WHERE length = '.$result['length'].' ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows));
					$t->set_value('total', $result['total']);
					$output .= $t->make_table($sqlite3);
				}

				if (!empty($output)) {
					$this->output .= '<div class="section">Words</div>'."\n".$output;
				}
			}
		}

		/**
		 * Milestones section.
		 */
		if ($this->sectionbits & 128) {
			$output = '';
			$query = @$sqlite3->query('SELECT milestone, COUNT(*) AS total FROM q_milestones GROUP BY milestone ORDER BY milestone ASC') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$result = $query->fetchArray(SQLITE3_ASSOC);

			if ($result !== false) {
				$query->reset();

				while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
					$t = new table(number_format($result['milestone']).' Lines Milestone', 1, $this->maxrows);
					$t->set_value('keys', array(
						'k1' => 'Date',
						'k2' => 'User',
						'v1' => 'date',
						'v2' => 'string'));
					$t->set_value('queries', array('main' => 'SELECT date AS v1, csnick AS v2 FROM q_milestones JOIN user_details ON q_milestones.ruid = user_details.uid WHERE milestone = '.$result['milestone'].' ORDER BY v1 ASC, v2 ASC LIMIT '.$this->maxrows));
					$t->set_value('total', $result['total']);
					$output .= $t->make_table($sqlite3);
				}
			}

			if (!empty($output)) {
				$this->output .= '<div class="section">Milestones</div>'."\n".$output;
			}
		}


		/**
		 * HTML Foot.
		 */
		$this->output .= '<div class="info">Statistics created with <a href="http://sss.dutnie.nl">superseriousstats</a> on '.date('r').'.'.($this->addhtml_foot != '' ? '<br>'.trim(@file_get_contents($this->addhtml_foot)) : '').'</div>'."\n";
		$this->output .= '</div></body>'."\n\n".'</html>'."\n";
		$this->output('notice', 'make_html(): finished creating statspage');
		return $this->output;
	}

	private function make_table_activity($sqlite3, $type)
	{
		if ($type == 'day') {
			$class = 'act';
			$columns = 24;

			for ($i = 23; $i >= 0; $i--) {
				$dates[] = date('Y-m-d', mktime(0, 0, 0, $this->month, $this->dayofmonth - $i, $this->year));
			}

			$head = 'Activity by Day';
			$query = @$sqlite3->query('SELECT date, l_total, l_night, l_morning, l_afternoon, l_evening FROM channel WHERE date > \''.date('Y-m-d', mktime(0, 0, 0, $this->month, $this->dayofmonth - 24, $this->year)).'\'') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		} elseif ($type == 'month') {
			$class = 'act';
			$columns = 24;

			for ($i = 23; $i >= 0; $i--) {
				$dates[] = date('Y-m', mktime(0, 0, 0, $this->month - $i, 1, $this->year));
			}

			$head = 'Activity by Month';
			$query = @$sqlite3->query('SELECT STRFTIME(\'%Y-%m\', date) AS date, SUM(l_total) AS l_total, SUM(l_night) AS l_night, SUM(l_morning) AS l_morning, SUM(l_afternoon) AS l_afternoon, SUM(l_evening) AS l_evening FROM channel WHERE date > \''.date('Y-m-00', mktime(0, 0, 0, $this->month - 24, 1, $this->year)).'%\' GROUP BY STRFTIME(\'%Y\', date), STRFTIME(\'%m\', date)') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
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
			$query = @$sqlite3->query('SELECT STRFTIME(\'%Y\', date) AS date, SUM(l_total) AS l_total, SUM(l_night) AS l_night, SUM(l_morning) AS l_morning, SUM(l_afternoon) AS l_afternoon, SUM(l_evening) AS l_evening FROM channel GROUP BY STRFTIME(\'%Y\', date)') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$result = $query->fetchArray(SQLITE3_ASSOC);

		if ($result === false) {
			return null;
		}

		$high_date = '';
		$high_value = 0;
		$query->reset();

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$l_night[$result['date']] = $result['l_night'];
			$l_morning[$result['date']] = $result['l_morning'];
			$l_afternoon[$result['date']] = $result['l_afternoon'];
			$l_evening[$result['date']] = $result['l_evening'];
			$l_total[$result['date']] = $result['l_total'];

			if ($l_total[$result['date']] > $high_value) {
				$high_date = $result['date'];
				$high_value = $l_total[$result['date']];
			}
		}

		if ($this->estimate && $type == 'year' && !empty($l_total[$this->currentyear])) {
			if (($result = @$sqlite3->querySingle('SELECT CAST(SUM(l_night) AS REAL) / 90 AS l_night_avg, CAST(SUM(l_morning) AS REAL) / 90 AS l_morning_avg, CAST(SUM(l_afternoon) AS REAL) / 90 AS l_afternoon_avg, CAST(SUM(l_evening) AS REAL) / 90 AS l_evening_avg, CAST(SUM(l_total) AS REAL) / 90 AS l_total_avg FROM q_activity_by_day WHERE date > \''.date('Y-m-d', mktime(0, 0, 0, $this->month, $this->dayofmonth - 90, $this->year)).'\'', true)) === false) {
				$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			$l_night['estimate'] = $l_night[$this->currentyear] + round($result['l_night_avg'] * $this->daysleft);
			$l_morning['estimate'] = $l_morning[$this->currentyear] + round($result['l_morning_avg'] * $this->daysleft);
			$l_afternoon['estimate'] = $l_afternoon[$this->currentyear] + round($result['l_afternoon_avg'] * $this->daysleft);
			$l_evening['estimate'] = $l_evening[$this->currentyear] + round($result['l_evening_avg'] * $this->daysleft);
			$l_total['estimate'] = $l_total[$this->currentyear] + round($result['l_total_avg'] * $this->daysleft);

			if ($l_total['estimate'] > $high_value) {
				$high_date = 'estimate';
				$high_value = $l_total['estimate'];
			}
		}

		$tr1 = '<tr><th colspan="'.$columns.'">'.$head;
		$tr2 = '<tr class="bars">';
		$tr3 = '<tr class="sub">';

		foreach ($dates as $date) {
			if (!array_key_exists($date, $l_total) || $l_total[$date] == 0) {
				$tr2 .= '<td><span class="grey">n/a</span>';
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

				$tr2 .= '<td'.($date == 'estimate' ? ' class="est"' : '').'><ul><li class="num" style="height:'.($height['night'] + $height['morning'] + $height['afternoon'] + $height['evening'] + 14).'px">'.$total;

				foreach ($times as $time) {
					if ($time == 'evening') {
						$height_li = $height['night'] + $height['morning'] + $height['afternoon'] + $height['evening'];
					} elseif ($time == 'afternoon') {
						$height_li = $height['night'] + $height['morning'] + $height['afternoon'];
					} elseif ($time == 'morning') {
						$height_li = $height['night'] + $height['morning'];
					} elseif ($time == 'night') {
						$height_li = $height['night'];
					}

					if ($height[$time] != 0) {
						$tr2 .= '<li class="'.$this->color[$time].'" style="height:'.$height_li.'px">';
					}
				}

				$tr2 .= '</ul>';
			}

			if ($type == 'day') {
				$tr3 .= '<td'.($high_date == $date ? ' class="bold"' : '').'>'.date('D', strtotime($date)).'<br>'.date('j', strtotime($date));
			} elseif ($type == 'month') {
				$tr3 .= '<td'.($high_date == $date ? ' class="bold"' : '').'>'.date('M', strtotime($date.'-01')).'<br>'.date('\'y', strtotime($date.'-01'));
			} elseif ($type == 'year') {
				$tr3 .= '<td'.($high_date == $date ? ' class="bold"' : '').'>'.($date == 'estimate' ? 'Est.' : date('\'y', strtotime($date.'-01-01')));
			}
		}

		return '<table class="'.$class.'">'.$tr1.$tr2.$tr3.'</table>'."\n";
	}

	private function make_table_activity_distribution_day($sqlite3)
	{
		if (($result = @$sqlite3->querySingle('SELECT SUM(l_mon_night) AS l_mon_night, SUM(l_mon_morning) AS l_mon_morning, SUM(l_mon_afternoon) AS l_mon_afternoon, SUM(l_mon_evening) AS l_mon_evening, SUM(l_tue_night) AS l_tue_night, SUM(l_tue_morning) AS l_tue_morning, SUM(l_tue_afternoon) AS l_tue_afternoon, SUM(l_tue_evening) AS l_tue_evening, SUM(l_wed_night) AS l_wed_night, SUM(l_wed_morning) AS l_wed_morning, SUM(l_wed_afternoon) AS l_wed_afternoon, SUM(l_wed_evening) AS l_wed_evening, SUM(l_thu_night) AS l_thu_night, SUM(l_thu_morning) AS l_thu_morning, SUM(l_thu_afternoon) AS l_thu_afternoon, SUM(l_thu_evening) AS l_thu_evening, SUM(l_fri_night) AS l_fri_night, SUM(l_fri_morning) AS l_fri_morning, SUM(l_fri_afternoon) AS l_fri_afternoon, SUM(l_fri_evening) AS l_fri_evening, SUM(l_sat_night) AS l_sat_night, SUM(l_sat_morning) AS l_sat_morning, SUM(l_sat_afternoon) AS l_sat_afternoon, SUM(l_sat_evening) AS l_sat_evening, SUM(l_sun_night) AS l_sun_night, SUM(l_sun_morning) AS l_sun_morning, SUM(l_sun_afternoon) AS l_sun_afternoon, SUM(l_sun_evening) AS l_sun_evening FROM q_lines', true)) === false) {
			$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$high_day = '';
		$high_value = 0;
		$days = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun');

		foreach ($days as $day) {
			$l_night[$day] = $result['l_'.$day.'_night'];
			$l_morning[$day] = $result['l_'.$day.'_morning'];
			$l_afternoon[$day] = $result['l_'.$day.'_afternoon'];
			$l_evening[$day] = $result['l_'.$day.'_evening'];
			$l_total[$day] = $l_night[$day] + $l_morning[$day] + $l_afternoon[$day] + $l_evening[$day];

			if ($l_total[$day] > $high_value) {
				$high_day = $day;
				$high_value = $l_total[$day];
			}
		}

		$tr1 = '<tr><th colspan="7">Activity Distribution by Day';
		$tr2 = '<tr class="bars">';
		$tr3 = '<tr class="sub">';

		foreach ($days as $day) {
			if ($l_total[$day] == 0) {
				$tr2 .= '<td><span class="grey">n/a</span>';
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

				$tr2 .= '<td><ul><li class="num" style="height:'.($height['night'] + $height['morning'] + $height['afternoon'] + $height['evening'] + 14).'px">'.$percentage;

				foreach ($times as $time) {
					if ($time == 'evening') {
						$height_li = $height['night'] + $height['morning'] + $height['afternoon'] + $height['evening'];
					} elseif ($time == 'afternoon') {
						$height_li = $height['night'] + $height['morning'] + $height['afternoon'];
					} elseif ($time == 'morning') {
						$height_li = $height['night'] + $height['morning'];
					} elseif ($time == 'night') {
						$height_li = $height['night'];
					}

					if ($height[$time] != 0) {
						$tr2 .= '<li class="'.$this->color[$time].'" style="height:'.$height_li.'px" title="'.number_format($l_total[$day]).'">';
					}
				}

				$tr2 .= '</ul>';
			}

			$tr3 .= '<td'.($high_day == $day ? ' class="bold"' : '').'>'.ucfirst($day);
		}

		return '<table class="act-day">'.$tr1.$tr2.$tr3.'</table>'."\n";
	}

	private function make_table_activity_distribution_hour($sqlite3)
	{
		if (($result = @$sqlite3->querySingle('SELECT SUM(l_00) AS l_00, SUM(l_01) AS l_01, SUM(l_02) AS l_02, SUM(l_03) AS l_03, SUM(l_04) AS l_04, SUM(l_05) AS l_05, SUM(l_06) AS l_06, SUM(l_07) AS l_07, SUM(l_08) AS l_08, SUM(l_09) AS l_09, SUM(l_10) AS l_10, SUM(l_11) AS l_11, SUM(l_12) AS l_12, SUM(l_13) AS l_13, SUM(l_14) AS l_14, SUM(l_15) AS l_15, SUM(l_16) AS l_16, SUM(l_17) AS l_17, SUM(l_18) AS l_18, SUM(l_19) AS l_19, SUM(l_20) AS l_20, SUM(l_21) AS l_21, SUM(l_22) AS l_22, SUM(l_23) AS l_23 FROM channel', true)) === false) {
			$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
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
		if ($type == 'alltime') {
			if (($total = @$sqlite3->querySingle('SELECT SUM(l_total) FROM q_lines JOIN user_status ON q_lines.ruid = user_status.uid WHERE status != 3')) === false) {
				$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		} elseif ($type == 'year') {
			if (($total = @$sqlite3->querySingle('SELECT SUM(l_total) FROM q_activity_by_year JOIN user_status ON q_activity_by_year.ruid = user_status.uid WHERE status != 3 AND date = '.$this->year)) === false) {
				$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		} elseif ($type == 'month') {
			if (($total = @$sqlite3->querySingle('SELECT SUM(l_total) FROM q_activity_by_month JOIN user_status ON q_activity_by_month.ruid = user_status.uid WHERE status != 3 AND date = \''.date('Y-m', mktime(0, 0, 0, $this->month, 1, $this->year)).'\'')) === false) {
				$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		}

		if (is_null($total)) {
			return null;
		}

		/**
		 * The queries below will always yield a proper workable result set.
		 */
		if ($type == 'alltime') {
			$head = 'Most Talkative People &ndash; Alltime';
			$historylink = '<a href="history.php?cid='.urlencode($this->cid).'">History</a>';
			$query = @$sqlite3->query('SELECT csnick, l_total, l_night, l_morning, l_afternoon, l_evening, quote, (SELECT MAX(lastseen) FROM user_details JOIN user_status ON user_details.uid = user_status.uid WHERE user_status.ruid = q_lines.ruid) AS lastseen FROM q_lines JOIN user_details ON q_lines.ruid = user_details.uid JOIN user_status ON q_lines.ruid = user_status.uid WHERE status != 3 AND l_total != 0 ORDER BY l_total DESC, csnick ASC LIMIT '.$this->maxrows_people_alltime) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		} elseif ($type == 'year') {
			$head = 'Most Talkative People &ndash; '.$this->year;
			$historylink = '<a href="history.php?cid='.urlencode($this->cid).'&amp;year='.$this->year.'">History</a>';
			$query = @$sqlite3->query('SELECT csnick, SUM(q_activity_by_year.l_total) AS l_total, SUM(q_activity_by_year.l_night) AS l_night, SUM(q_activity_by_year.l_morning) AS l_morning, SUM(q_activity_by_year.l_afternoon) AS l_afternoon, SUM(q_activity_by_year.l_evening) AS l_evening, quote, (SELECT MAX(lastseen) FROM user_details JOIN user_status ON user_details.uid = user_status.uid WHERE user_status.ruid = q_lines.ruid) AS lastseen FROM q_lines JOIN q_activity_by_year ON q_lines.ruid = q_activity_by_year.ruid JOIN user_status ON q_lines.ruid = user_status.uid JOIN user_details ON q_lines.ruid = user_details.uid WHERE status != 3 AND date = '.$this->year.' GROUP BY q_lines.ruid ORDER BY l_total DESC, csnick ASC LIMIT '.$this->maxrows_people_year) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		} elseif ($type == 'month') {
			$head = 'Most Talkative People &ndash; '.$this->monthname.' '.$this->year;
			$historylink = '<a href="history.php?cid='.urlencode($this->cid).'&amp;year='.$this->year.'&amp;month='.$this->month.'">History</a>';
			$query = @$sqlite3->query('SELECT csnick, SUM(q_activity_by_month.l_total) AS l_total, SUM(q_activity_by_month.l_night) AS l_night, SUM(q_activity_by_month.l_morning) AS l_morning, SUM(q_activity_by_month.l_afternoon) AS l_afternoon, SUM(q_activity_by_month.l_evening) AS l_evening, quote, (SELECT MAX(lastseen) FROM user_details JOIN user_status ON user_details.uid = user_status.uid WHERE user_status.ruid = q_lines.ruid) AS lastseen FROM q_lines JOIN q_activity_by_month ON q_lines.ruid = q_activity_by_month.ruid JOIN user_status ON q_lines.ruid = user_status.uid JOIN user_details ON q_lines.ruid = user_details.uid WHERE status != 3 AND date = \''.date('Y-m', mktime(0, 0, 0, $this->month, 1, $this->year)).'\' GROUP BY q_lines.ruid ORDER BY l_total DESC, csnick ASC LIMIT '.$this->maxrows_people_month) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$tr0 = '<colgroup><col class="c1"><col class="c2"><col class="pos"><col class="c3"><col class="c4"><col class="c5"><col class="c6">';
		$tr1 = '<tr><th colspan="7">'.($this->history ? '<span class="title">'.$head.'</span><span class="total">'.$historylink.'</span>' : $head);
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

	private function make_table_people2($sqlite3)
	{
		$current_column = 1;
		$current_row = 1;
		$query = @$sqlite3->query('SELECT csnick, l_total FROM q_lines JOIN user_status ON q_lines.ruid = user_status.uid JOIN user_details ON user_details.uid = user_status.ruid WHERE status != 3 AND l_total != 0 ORDER BY l_total DESC, csnick ASC LIMIT '.$this->maxrows_people_alltime.', '.($this->maxrows_people2 * 4)) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			if ($current_row > $this->maxrows_people2) {
				$current_column++;
				$current_row = 1;
			}

			$columns[$current_column][$current_row] = array($result['csnick'], $result['l_total']);
			$current_row++;
		}

		if ($current_row < $this->maxrows_people2) {
			return null;
		}

		if (($total = @$sqlite3->querySingle('SELECT COUNT(*) FROM q_lines JOIN user_status ON q_lines.ruid = user_status.uid WHERE status != 3')) === false) {
			$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$total -= $this->maxrows_people_alltime + ($this->maxrows_people2 * 4);
		$tr0 = '<colgroup><col class="c1"><col class="pos"><col class="c2"><col class="c1"><col class="pos"><col class="c2"><col class="c1"><col class="pos"><col class="c2"><col class="c1"><col class="pos"><col class="c2">';
		$tr1 = '<tr><th colspan="12">'.($total != 0 ? '<span class="title">Less Talkative People &ndash; Alltime</span><span class="total">'.number_format($total).' People had even less to say..</span>' : 'Less Talkative People &ndash; Alltime');
		$tr2 = '<tr><td class="k1">Lines<td class="pos"><td class="k2">User<td class="k1">Lines<td class="pos"><td class="k2">User<td class="k1">Lines<td class="pos"><td class="k2">User<td class="k1">Lines<td class="pos"><td class="k2">User';
		$trx = '';

		for ($i = 1; $i <= $this->maxrows_people2; $i++) {
			$trx .= '<tr>';

			for ($j = 1; $j <= 4; $j++) {
				$trx .= '<td class="v1">'.number_format($columns[$j][$i][1]).'<td class="pos">'.($this->maxrows_people_alltime + ($j > 1 ? ($j - 1) * $this->maxrows_people2 : 0) + $i).'<td class="v2">'.($this->userstats ? '<a href="user.php?cid='.urlencode($this->cid).'&amp;nick='.urlencode($columns[$j][$i][0]).'">'.htmlspecialchars($columns[$j][$i][0]).'</a>' : htmlspecialchars($columns[$j][$i][0]));
			}
		}

		return '<table class="ppl2">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}

	private function make_table_people_timeofday($sqlite3)
	{
		/**
		 * Check if there is user activity (bots excluded). If there is none we can skip making the table.
		 */
		if (($total = @$sqlite3->querySingle('SELECT SUM(l_total) FROM q_lines JOIN user_status ON q_lines.ruid = user_status.uid WHERE status != 3')) === false) {
			$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		if (is_null($total)) {
			return null;
		}

		$high_value = 0;
		$times = array('night', 'morning', 'afternoon', 'evening');

		foreach ($times as $time) {
			$query = @$sqlite3->query('SELECT csnick, l_'.$time.' FROM q_lines JOIN user_details ON q_lines.ruid = user_details.uid JOIN user_status ON q_lines.ruid = user_status.uid WHERE status != 3 and l_'.$time.' != 0 ORDER BY l_'.$time.' DESC, csnick ASC LIMIT '.$this->maxrows_people_timeofday) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
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
}

/**
 * Class for creating small, medium and large generic tables.
 */
final class table extends base
{
	private $head = '';
	private $maxrows = 0;
	private $minrows = 0;
	private $urltools;
	protected $decimals = 1;
	protected $keys = array();
	protected $medium = false;
	protected $percentage = false;
	protected $queries = array();
	protected $total = 0;

	/**
	 * This variable controls whether we use an alternative class for "v3", which doesn't use ellipsis. Only relevant when there is data inside the cell.
	 */
	protected $v3a = false;

	public function __construct($head, $minrows, $maxrows)
	{
		$this->head = $head;
		$this->minrows = $minrows;
		$this->maxrows = $maxrows;
	}

	/**
	 * Check if there are URLs in the string and if so, make hyperlinks out of them.
	 */
	private function find_urls($string)
	{
		if (empty($this->urltools)) {
			$urltools = new urltools();
		}

		$words = explode(' ', $string);
		$newstring = '';

		foreach ($words as $word) {
			if (preg_match('/^(www\.|https?:\/\/)/i', $word) && ($urldata = $urltools->get_elements($word)) !== false) {
				$newstring .= '<a href="'.htmlspecialchars($urldata['url']).'">'.htmlspecialchars($urldata['url']).'</a> ';
			} else {
				$newstring .= htmlspecialchars($word).' ';
			}
		}

		return rtrim($newstring);
	}

	public function make_table($sqlite3)
	{
		/**
		 * Find out what table class we are dealing with. The "medium" class is a special case and should be set manually by setting $medium to true.
		 */
		if ($this->medium) {
			$class = 'medium';
		} elseif (array_key_exists('v3', $this->keys)) {
			$class = 'large';
		} else {
			$class = 'small';
		}

		/**
		 * Run the "total" query if present.
		 */
		if (!empty($this->queries['total'])) {
			if (($this->total = @$sqlite3->querySingle($this->queries['total'])) === false) {
				$this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		}

		/**
		 * Create the table head.
		 */
		if ($class == 'small') {
			$tr0 = '<colgroup><col class="c1"><col class="pos"><col class="c2">';
			$tr1 = '<tr><th colspan="3">'.($this->total != 0 ? '<span class="title">'.$this->head.'</span><span class="total">'.number_format($this->total).' Total</span>' : $this->head);
			$tr2 = '<tr><td class="k1">'.$this->keys['k1'].'<td class="pos"><td class="k2">'.$this->keys['k2'];
			$trx = '';
		} else {
			$tr0 = '<colgroup><col class="c1"><col class="pos"><col class="c2"><col class="c3">';
			$tr1 = '<tr><th colspan="4">'.($this->total != 0 ? '<span class="title">'.$this->head.'</span><span class="total">'.number_format($this->total).' Total</span>' : $this->head);
			$tr2 = '<tr><td class="k1">'.$this->keys['k1'].'<td class="pos"><td class="k2">'.$this->keys['k2'].'<td class="k3">'.$this->keys['k3'];
			$trx = '';
		}

		/**
		 * Run the "main" query and structure the table contents.
		 */
		$i = 0;
		$query = @$sqlite3->query($this->queries['main']) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$i++;

			if ($i > $this->maxrows) {
				break;
			}

			foreach ($this->keys as $key => $type) {
				/**
				 * Skip the keys which are irrelevant here.
				 */
				if (strpos($key, 'v') === false) {
					continue;
				}

				switch ($type) {
					case 'string':
						${$key} = htmlspecialchars($result[$key]);
						break;
					case 'int':
						${$key} = number_format($result[$key]);
						break;
					case 'float':
						${$key} = number_format($result[$key], $this->decimals).($this->percentage ? '%' : '');
						break;
					case 'date':
						${$key} = date('j M \'y', strtotime($result[$key]));
						break;
					case 'date-norepeat':
						${$key} = date('j M \'y', strtotime($result[$key]));

						if (!empty($prevdate) && ${$key} == $prevdate) {
							${$key} = '';
						} else {
							$prevdate = ${$key};
						}

						break;
					case 'url':
						${$key} = '<a href="'.htmlspecialchars($result[$key]).'">'.htmlspecialchars($result[$key]).'</a>';
						break;
					case 'string-url':
						${$key} = $this->find_urls($result[$key]);
						break;
				}
			}

			if ($class == 'small') {
				$trx .= '<tr><td class="v1">'.$v1.'<td class="pos">'.$i.'<td class="v2">'.$v2;
			} else {
				$trx .= '<tr><td class="v1">'.$v1.'<td class="pos">'.$i.'<td class="v2">'.$v2.'<td class="'.($this->v3a ? 'v3a' : 'v3').'">'.$v3;
			}
		}

		if ($i < $this->minrows) {
			return null;
		}

		for ($i; $i < $this->maxrows; $i++) {
			if ($class == 'small') {
				$trx .= '<tr><td class="v1"><td class="pos">&nbsp;<td class="v2">';
			} else {
				$trx .= '<tr><td class="v1"><td class="pos">&nbsp;<td class="v2"><td class="v3">';
			}
		}

		return '<table class="'.$class.'">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}
}

?>
