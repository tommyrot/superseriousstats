<?php

/**
 * Copyright (c) 2007-2016, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Class for creating the main stats page.
 */
class html
{
	/**
	 * Variables listed in $settings_list[] can have their default value overridden
	 * in the configuration file.
	 */
	private $addhtml_foot = '';
	private $addhtml_head = '';
	private $channel = '';
	private $cid = '';
	private $color = [
		'night' => 'b',
		'morning' => 'g',
		'afternoon' => 'y',
		'evening' => 'r'];
	private $columns_act_year = 0;
	private $datetime = [];
	private $estimate = false;
	private $history = false;
	private $l_total = 0;
	private $maxrows = 5;
	private $maxrows_people2 = 10;
	private $maxrows_people_alltime = 30;
	private $maxrows_people_month = 10;
	private $maxrows_people_timeofday = 10;
	private $maxrows_people_year = 10;
	private $maxrows_recenturls = 25;
	private $minrows = 3;
	private $rankings = false;
	private $recenturls_type = 1;
	private $rows_domains_tlds = 10;
	private $search_user = false;
	private $sectionbits = 255;
	private $settings_list = [
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
		'rankings' => 'bool',
		'recenturls_type' => 'int',
		'rows_domains_tlds' => 'int',
		'search_user' => 'bool',
		'sectionbits' => 'int',
		'stylesheet' => 'string',
		'userstats' => 'bool'];
	private $stylesheet = 'sss.css';
	private $userstats = false;

	public function __construct($settings)
	{
		/**
		 * If set, override variables listed in $settings_list[].
		 */
		foreach ($this->settings_list as $key => $type) {
			if (!array_key_exists($key, $settings)) {
				continue;
			}

			/**
			 * Do some explicit type casting because everything is initially a string.
			 */
			if ($type === 'string') {
				$this->$key = $settings[$key];
			} elseif ($type === 'int' && preg_match('/^\d+$/', $settings[$key])) {
				$this->$key = (int) $settings[$key];
			} elseif ($type === 'bool') {
				if (strtolower($settings[$key]) === 'true') {
					$this->$key = true;
				} elseif (strtolower($settings[$key]) === 'false') {
					$this->$key = false;
				}
			}
		}

		/**
		 * If $cid has no value set it to $channel.
		 */
		if ($this->cid === '') {
			$this->cid = $this->channel;
		}
	}

	/**
	 * Calculate how many days ago a given $datetime is.
	 */
	private function daysago($datetime)
	{
		/**
		 * Because the amount of seconds in a day can vary due to DST we have
		 * to round the value of $daysago.
		 */
		$daysago = round((strtotime('today') - strtotime(substr($datetime, 0, 10))) / 86400);

		if ($daysago / 365 >= 1) {
			$daysago = str_replace('.0', '', number_format($daysago / 365, 1));
			$daysago .= ' Year'.((float) $daysago > 1 ? 's' : '').' Ago';
		} elseif ($daysago / 30.42 >= 1) {
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

	/**
	 * Generate the HTML page.
	 */
	public function make_html($sqlite3)
	{
		output::output('notice', __METHOD__.'(): creating stats page');

		if (($this->l_total = $sqlite3->querySingle('SELECT SUM(l_total) FROM channel_activity')) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		/**
		 * All queries from this point forward require a non-empty database.
		 */
		if (is_null($this->l_total)) {
			output::output('warning', __METHOD__.'(): database is empty');
			return '<!DOCTYPE html>'."\n\n".'<html><head><meta charset="utf-8"><title>seriously?</title><link rel="stylesheet" href="sss.css"></head><body><div id="container"><div class="error">There is not enough data to create statistics, yet.</div></div></body></html>'."\n";
		}

		if (($result = $sqlite3->querySingle('SELECT MIN(date) AS date_first, MAX(date) AS date_last FROM channel_activity', true)) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$date_first = $result['date_first'];
		$date_last = $result['date_last'];

		if (($result = $sqlite3->querySingle('SELECT COUNT(*) AS dayslogged, MAX(date) AS date FROM parse_history', true)) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$date_lastlogparsed = $result['date'];
		$dayslogged = $result['dayslogged'];
		$l_avg = (int) round($this->l_total / $dayslogged);

		/**
		 * Date and time variables used throughout the script. These are based on the
		 * date of the last logfile parsed, and are used to define our scope.
		 */
		$this->datetime['currentyear'] = (int) date('Y');
		$this->datetime['dayofmonth'] = (int) date('j', strtotime($date_lastlogparsed));
		$this->datetime['firstyearmonth'] = substr($date_first, 0, 7);
		$this->datetime['month'] = (int) date('n', strtotime($date_lastlogparsed));
		$this->datetime['monthname'] = date('F', strtotime($date_lastlogparsed));
		$this->datetime['year'] = (int) date('Y', strtotime($date_lastlogparsed));
		$this->datetime['daysleft'] = (int) date('z', strtotime('last day of December '.$this->datetime['year'])) - (int) date('z', strtotime($date_lastlogparsed));

		/**
		 * If there are one or more days to come until the end of the year, display an
		 * additional column in the Activity by Year table with an estimated line count
		 * for the current year.
		 */
		if ($this->datetime['daysleft'] !== 0 && $this->datetime['year'] === $this->datetime['currentyear']) {
			/**
			 * Base the estimation on the activity in the last 90 days logged, if there is
			 * any.
			 */
			if (($activity = $sqlite3->querySingle('SELECT COUNT(*) FROM channel_activity WHERE date > \''.date('Y-m-d', mktime(0, 0, 0, $this->datetime['month'], $this->datetime['dayofmonth'] - 90, $this->datetime['year'])).'\'')) === false) {
				output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			if ($activity !== 0) {
				$this->estimate = true;
			}
		}

		/**
		 * Show a minimum of 3 and maximum of 24 columns in the Activity by Year table.
		 * In case the data allows for more than 16 columns there won't be any room for
		 * the Activity Distribution by Day table to be adjacent to the right so we pad
		 * the Activity by Year table up to 24 columns so it looks neat.
		 */
		$this->columns_act_year = $this->datetime['year'] - (int) date('Y', strtotime($date_first)) + ($this->estimate ? 1 : 0) + 1;

		if ($this->columns_act_year < 3) {
			$this->columns_act_year = 3;
		} elseif ($this->columns_act_year > 16) {
			$this->columns_act_year = 24;
		}

		/**
		 * HTML Head.
		 */
		if (($result = $sqlite3->querySingle('SELECT MIN(date) AS date, l_total FROM channel_activity WHERE l_total = (SELECT MAX(l_total) FROM channel_activity)', true)) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$date_max = $result['date'];
		$l_max = $result['l_total'];
		$html = '<!DOCTYPE html>'."\n\n"
			. '<html>'."\n\n"
			. '<head>'."\n"
			. '<meta charset="utf-8">'."\n"
			. '<title>'.htmlspecialchars($this->channel).', seriously.</title>'."\n"
			. '<link rel="stylesheet" href="'.$this->stylesheet.'">'."\n"
			. '<style type="text/css">'."\n"
			. '  .act-year { width:'.(2 + ($this->columns_act_year * 34)).'px }'."\n"
			. '</style>'."\n"
			. '</head>'."\n\n"
			. '<body><div id="container">'."\n"
			. '<div class="info">'.($this->search_user ? '<form action="user.php"><input type="hidden" name="cid" value="'.urlencode($this->cid).'"><input type="text" name="nick" placeholder="Search User.."></form>' : '').htmlspecialchars($this->channel).', seriously.<br><br>'
			. number_format($dayslogged).' day'.($dayslogged > 1 ? 's logged from '.date('M j, Y', strtotime($date_first)).' to '.date('M j, Y', strtotime($date_last)) : ' logged on '.date('M j, Y', strtotime($date_first))).'.<br><br>'
			. 'Logs contain '.number_format($this->l_total).' line'.($this->l_total > 1 ? 's' : '').' &ndash; an average of '.number_format($l_avg).' line'.($l_avg !== 1 ? 's' : '').' per day.<br>'
			. 'Most active day was '.date('M j, Y', strtotime($date_max)).' with a total of '.number_format($l_max).' line'.($l_max > 1 ? 's' : '').' typed.'.($this->addhtml_head !== '' ? '<br><br>'.trim(file_get_contents($this->addhtml_head)) : '').'</div>'."\n";

		/**
		 * Activity section.
		 */
		if ($this->sectionbits & 1) {
			$html .= '<div class="section">Activity</div>'."\n";
			$html .= $this->make_table_activity_distribution_hour($sqlite3);
			$html .= $this->make_table_activity($sqlite3, 'day');
			$html .= $this->make_table_activity($sqlite3, 'month');
			$html .= $this->make_table_activity($sqlite3, 'year');
			$html .= $this->make_table_activity_distribution_day($sqlite3);
			$html .= $this->make_table_people($sqlite3, 'alltime');
			$html .= $this->make_table_people2($sqlite3);
			$html .= $this->make_table_people($sqlite3, 'year');
			$html .= $this->make_table_people($sqlite3, 'month');
			$html .= $this->make_table_people_timeofday($sqlite3);
		}

		/**
		 * General Chat section.
		 */
		if ($this->sectionbits & 2) {
			$output = '';

			$t = new table('Most Talkative Chatters', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Lines/Day',
				'k2' => 'User',
				'v1' => 'float',
				'v2' => 'string']);
			$t->set_value('queries', ['main' => 'SELECT CAST(l_total AS REAL) / activedays AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND activedays >= 7 AND lasttalked >= \''.date('Y-m-d 00:00:00', mktime(0, 0, 0, $this->datetime['month'], $this->datetime['dayofmonth'] - 30, $this->datetime['year'])).'\' ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows]);
			$output .= $t->make_table($sqlite3);

			$t = new table('Most Fluent Chatters', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Words/Line',
				'k2' => 'User',
				'v1' => 'float',
				'v2' => 'string']);
			$t->set_value('queries', ['main' => 'SELECT CAST(words AS REAL) / l_total AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND activedays >= 7 AND lasttalked >= \''.date('Y-m-d 00:00:00', mktime(0, 0, 0, $this->datetime['month'], $this->datetime['dayofmonth'] - 30, $this->datetime['year'])).'\' ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows]);
			$output .= $t->make_table($sqlite3);

			$t = new table('Most Tedious Chatters', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Chars/Line',
				'k2' => 'User',
				'v1' => 'float',
				'v2' => 'string']);
			$t->set_value('queries', ['main' => 'SELECT CAST(characters AS REAL) / l_total AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND activedays >= 7 AND lasttalked >= \''.date('Y-m-d 00:00:00', mktime(0, 0, 0, $this->datetime['month'], $this->datetime['dayofmonth'] - 30, $this->datetime['year'])).'\' ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows]);
			$output .= $t->make_table($sqlite3);

			$t = new table('Individual Top Days &ndash; All-Time', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Lines',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string']);
			$t->set_value('queries', ['main' => 'SELECT MAX(l_total) AS v1, csnick AS v2 FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) GROUP BY ruid_activity_by_day.ruid ORDER BY v1 DESC, ruid_activity_by_day.ruid ASC LIMIT '.$this->maxrows]);
			$output .= $t->make_table($sqlite3);

			$t = new table('Individual Top Days &ndash; '.$this->datetime['year'], $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Lines',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string']);
			$t->set_value('queries', ['main' => 'SELECT MAX(l_total) AS v1, csnick AS v2 FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date LIKE \''.$this->datetime['year'].'%\' GROUP BY ruid_activity_by_day.ruid ORDER BY v1 DESC, ruid_activity_by_day.ruid ASC LIMIT '.$this->maxrows]);
			$output .= $t->make_table($sqlite3);

			$t = new table('Individual Top Days &ndash; '.$this->datetime['monthname'].' '.$this->datetime['year'], $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Lines',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string']);
			$t->set_value('queries', ['main' => 'SELECT MAX(l_total) AS v1, csnick AS v2 FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date LIKE \''.date('Y-m', strtotime($date_lastlogparsed)).'%\' GROUP BY ruid_activity_by_day.ruid ORDER BY v1 DESC, ruid_activity_by_day.ruid ASC LIMIT '.$this->maxrows]);
			$output .= $t->make_table($sqlite3);

			$t = new table('Most Active Chatters &ndash; All-Time', $this->minrows, $this->maxrows);
			$t->set_value('decimals', 2);
			$t->set_value('keys', [
				'k1' => 'Activity',
				'k2' => 'User',
				'v1' => 'float',
				'v2' => 'string']);
			$t->set_value('percentage', true);
			$t->set_value('queries', ['main' => 'SELECT (CAST(activedays AS REAL) / '.$dayslogged.') * 100 AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND activedays != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows]);
			$output .= $t->make_table($sqlite3);

			$t = new table('Most Active Chatters &ndash; '.$this->datetime['year'], $this->minrows, $this->maxrows);
			$t->set_value('decimals', 2);
			$t->set_value('keys', [
				'k1' => 'Activity',
				'k2' => 'User',
				'v1' => 'float',
				'v2' => 'string']);
			$t->set_value('percentage', true);
			$t->set_value('queries', ['main' => 'SELECT (CAST(COUNT(DISTINCT date) AS REAL) / (SELECT COUNT(*) FROM parse_history WHERE date LIKE \''.$this->datetime['year'].'%\')) * 100 AS v1, csnick AS v2 FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date LIKE \''.$this->datetime['year'].'%\' GROUP BY ruid_activity_by_day.ruid ORDER BY v1 DESC, ruid_activity_by_day.ruid ASC LIMIT '.$this->maxrows]);
			$output .= $t->make_table($sqlite3);

			$t = new table('Most Active Chatters &ndash; '.$this->datetime['monthname'].' '.$this->datetime['year'], $this->minrows, $this->maxrows);
			$t->set_value('decimals', 2);
			$t->set_value('keys', [
				'k1' => 'Activity',
				'k2' => 'User',
				'v1' => 'float',
				'v2' => 'string']);
			$t->set_value('percentage', true);
			$t->set_value('queries', ['main' => 'SELECT (CAST(COUNT(DISTINCT date) AS REAL) / (SELECT COUNT(*) FROM parse_history WHERE date LIKE \''.date('Y-m', strtotime($date_lastlogparsed)).'%\')) * 100 AS v1, csnick AS v2 FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date LIKE \''.date('Y-m', strtotime($date_lastlogparsed)).'%\' GROUP BY ruid_activity_by_day.ruid ORDER BY v1 DESC, ruid_activity_by_day.ruid ASC LIMIT '.$this->maxrows]);
			$output .= $t->make_table($sqlite3);

			$t = new table('Exclamations', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Total',
				'k2' => 'User',
				'k3' => 'Example',
				'v1' => 'int',
				'v2' => 'string',
				'v3' => 'string']);
			$t->set_value('queries', [
				'main' => 'SELECT exclamations AS v1, csnick AS v2, ex_exclamations AS v3 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND exclamations != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(exclamations) FROM ruid_lines']);
			$output .= $t->make_table($sqlite3);

			$t = new table('Questions', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Total',
				'k2' => 'User',
				'k3' => 'Example',
				'v1' => 'int',
				'v2' => 'string',
				'v3' => 'string']);
			$t->set_value('queries', [
				'main' => 'SELECT questions AS v1, csnick AS v2, ex_questions AS v3 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND questions != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(questions) FROM ruid_lines']);
			$output .= $t->make_table($sqlite3);

			$t = new table('UPPERCASED Lines', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Total',
				'k2' => 'User',
				'k3' => 'Example',
				'v1' => 'int',
				'v2' => 'string',
				'v3' => 'string']);
			$t->set_value('queries', [
				'main' => 'SELECT uppercased AS v1, csnick AS v2, ex_uppercased AS v3 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND uppercased != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(uppercased) FROM ruid_lines']);
			$output .= $t->make_table($sqlite3);

			$t = new table('Monologues', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Total',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string']);
			$t->set_value('queries', [
				'main' => 'SELECT monologues AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND monologues != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(monologues) FROM ruid_lines']);
			$output .= $t->make_table($sqlite3);

			$t = new table('Longest Monologue', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Lines',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string']);
			$t->set_value('queries', ['main' => 'SELECT topmonologue AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND topmonologue != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows]);
			$output .= $t->make_table($sqlite3);

			$t = new table('Moodiest People', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Smileys',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string']);
			$t->set_value('queries', [
				'main' => 'SELECT s_01 + s_02 + s_03 + s_04 + s_05 + s_06 + s_07 + s_08 + s_09 + s_10 + s_11 + s_12 + s_13 + s_14 + s_15 + s_16 + s_17 + s_18 + s_19 + s_20 + s_21 + s_22 + s_23 + s_24 + s_25 + s_26 + s_27 + s_28 + s_29 + s_30 + s_31 + s_32 + s_33 + s_34 + s_35 + s_36 + s_37 + s_38 + s_39 + s_40 + s_41 + s_42 + s_43 + s_44 + s_45 + s_46 + s_47 + s_48 + s_49 + s_50 AS v1, csnick AS v2 FROM ruid_smileys JOIN uid_details ON ruid_smileys.ruid = uid_details.uid WHERE status NOT IN (3,4) ORDER BY v1 DESC, ruid_smileys.ruid ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(s_01) + SUM(s_02) + SUM(s_03) + SUM(s_04) + SUM(s_05) + SUM(s_06) + SUM(s_07) + SUM(s_08) + SUM(s_09) + SUM(s_10) + SUM(s_11) + SUM(s_12) + SUM(s_13) + SUM(s_14) + SUM(s_15) + SUM(s_16) + SUM(s_17) + SUM(s_18) + SUM(s_19) + SUM(s_20) + SUM(s_21) + SUM(s_22) + SUM(s_23) + SUM(s_24) + SUM(s_25) + SUM(s_26) + SUM(s_27) + SUM(s_28) + SUM(s_29) + SUM(s_30) + SUM(s_31) + SUM(s_32) + SUM(s_33) + SUM(s_34) + SUM(s_35) + SUM(s_36) + SUM(s_37) + SUM(s_38) + SUM(s_39) + SUM(s_40) + SUM(s_41) + SUM(s_42) + SUM(s_43) + SUM(s_44) + SUM(s_45) + SUM(s_46) + SUM(s_47) + SUM(s_48) + SUM(s_49) + SUM(s_50) FROM ruid_smileys']);
			$output .= $t->make_table($sqlite3);

			$t = new table('Slaps Given', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Total',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string']);
			$t->set_value('queries', [
				'main' => 'SELECT slaps AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND slaps != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(slaps) FROM ruid_lines']);
			$output .= $t->make_table($sqlite3);

			$t = new table('Slaps Received', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Total',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string']);
			$t->set_value('queries', [
				'main' => 'SELECT slapped AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND slapped != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(slapped) FROM ruid_lines']);
			$output .= $t->make_table($sqlite3);

			$t = new table('Most Lively Bots', $this->minrows, $this->maxrows);
			$t->set_value('cid', $this->cid);
			$t->set_value('keys', [
				'k1' => 'Lines',
				'k2' => 'Bot',
				'v1' => 'int',
				'v2' => ($this->userstats ? 'userstats' : 'string')]);
			$t->set_value('queries', ['main' => 'SELECT l_total AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status = 3 AND l_total != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows]);
			$output .= $t->make_table($sqlite3);

			$t = new table('Actions Performed', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Total',
				'k2' => 'User',
				'k3' => 'Example',
				'v1' => 'int',
				'v2' => 'string',
				'v3' => 'string']);
			$t->set_value('queries', [
				'main' => 'SELECT actions AS v1, csnick AS v2, ex_actions AS v3 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND actions != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(actions) FROM ruid_lines']);
			$output .= $t->make_table($sqlite3);

			if ($output !== '') {
				$html .= '<div class="section">General Chat</div>'."\n".$output;
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
			$modes = [
				'm_op' => 'Ops \'+o\' Given',
				'm_opped' => 'Ops \'+o\' Received',
				'm_deop' => 'deOps \'-o\' Given',
				'm_deopped' => 'deOps \'-o\' Received',
				'm_voice' => 'Voices \'+v\' Given',
				'm_voiced' => 'Voices \'+v\' Received',
				'm_devoice' => 'deVoices \'-v\' Given',
				'm_devoiced' => 'deVoices \'-v\' Received'];

			foreach ($modes as $key => $value) {
				$t = new table($value, $this->minrows, $this->maxrows);
				$t->set_value('keys', [
					'k1' => 'Total',
					'k2' => 'User',
					'v1' => 'int',
					'v2' => 'string']);
				$t->set_value('queries', [
					'main' => 'SELECT '.$key.' AS v1, csnick AS v2 FROM ruid_events JOIN uid_details ON ruid_events.ruid = uid_details.uid WHERE status NOT IN (3,4) AND '.$key.' != 0 ORDER BY v1 DESC, ruid_events.ruid ASC LIMIT '.$this->maxrows,
					'total' => 'SELECT SUM('.$key.') FROM ruid_events']);
				$output .= $t->make_table($sqlite3);
			}

			if ($output !== '') {
				$html .= '<div class="section">Modes</div>'."\n".$output;
			}
		}

		/**
		 * Events section.
		 */
		if ($this->sectionbits & 8) {
			$output = '';

			$t = new table('Kicks Given', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Total',
				'k2' => 'User',
				'k3' => 'Example',
				'v1' => 'int',
				'v2' => 'string',
				'v3' => 'string']);
			$t->set_value('queries', [
				'main' => 'SELECT kicks AS v1, csnick AS v2, ex_kicks AS v3 FROM ruid_events JOIN uid_details ON ruid_events.ruid = uid_details.uid WHERE status NOT IN (3,4) AND kicks != 0 ORDER BY v1 DESC, ruid_events.ruid ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(kicks) FROM ruid_events']);
			$output .= $t->make_table($sqlite3);

			$t = new table('Kicks Received', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Total',
				'k2' => 'User',
				'k3' => 'Example',
				'v1' => 'int',
				'v2' => 'string',
				'v3' => 'string']);
			$t->set_value('queries', [
				'main' => 'SELECT kicked AS v1, csnick AS v2, ex_kicked AS v3 FROM ruid_events JOIN uid_details ON ruid_events.ruid = uid_details.uid WHERE status NOT IN (3,4) AND kicked != 0 ORDER BY v1 DESC, ruid_events.ruid ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(kicked) FROM ruid_events']);
			$output .= $t->make_table($sqlite3);

			$t = new table('Channel Joins', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Total',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string']);
			$t->set_value('queries', [
				'main' => 'SELECT joins AS v1, csnick AS v2 FROM ruid_events JOIN uid_details ON ruid_events.ruid = uid_details.uid WHERE status NOT IN (3,4) AND joins != 0 ORDER BY v1 DESC, ruid_events.ruid ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(joins) FROM ruid_events']);
			$output .= $t->make_table($sqlite3);

			$t = new table('Channel Parts', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Total',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string']);
			$t->set_value('queries', [
				'main' => 'SELECT parts AS v1, csnick AS v2 FROM ruid_events JOIN uid_details ON ruid_events.ruid = uid_details.uid WHERE status NOT IN (3,4) AND parts != 0 ORDER BY v1 DESC, ruid_events.ruid ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(parts) FROM ruid_events']);
			$output .= $t->make_table($sqlite3);

			$t = new table('IRC Quits', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Total',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string']);
			$t->set_value('queries', [
				'main' => 'SELECT quits AS v1, csnick AS v2 FROM ruid_events JOIN uid_details ON ruid_events.ruid = uid_details.uid WHERE status NOT IN (3,4) AND quits != 0 ORDER BY v1 DESC, ruid_events.ruid ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(quits) FROM ruid_events']);
			$output .= $t->make_table($sqlite3);

			$t = new table('Nick Changes', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Total',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string']);
			$t->set_value('queries', [
				'main' => 'SELECT nickchanges AS v1, csnick AS v2 FROM ruid_events JOIN uid_details ON ruid_events.ruid = uid_details.uid WHERE status NOT IN (3,4) AND nickchanges != 0 ORDER BY v1 DESC, ruid_events.ruid ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(nickchanges) FROM ruid_events']);
			$output .= $t->make_table($sqlite3);

			$t = new table('Aliases', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Total',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string']);
			$t->set_value('queries', [
				'main' => 'SELECT COUNT(*) AS v1, (SELECT csnick FROM uid_details WHERE uid = t1.ruid) AS v2 FROM uid_details AS t1 WHERE ruid IN (SELECT ruid FROM uid_details WHERE status = 1) GROUP BY ruid HAVING v1 > 1 ORDER BY v1 DESC, ruid ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT COUNT(*) FROM uid_details']);
			$output .= $t->make_table($sqlite3);

			$t = new table('Topics Set', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Total',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string']);
			$t->set_value('queries', [
				'main' => 'SELECT topics AS v1, csnick AS v2 FROM ruid_events JOIN uid_details ON ruid_events.ruid = uid_details.uid WHERE status NOT IN (3,4) AND topics != 0 ORDER BY v1 DESC, ruid_events.ruid ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(topics) FROM ruid_events']);
			$output .= $t->make_table($sqlite3);

			$t = new table('Most Recent Topics', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Date',
				'k2' => 'User',
				'k3' => 'Topic',
				'v1' => 'date',
				'v2' => 'string',
				'v3' => 'string-url']);
			$t->set_value('queries', ['main' => 'SELECT datetime AS v1, (SELECT csnick FROM uid_details WHERE uid = (SELECT ruid FROM uid_details WHERE uid = uid_topics.uid)) AS v2, topic AS v3 FROM uid_topics JOIN topics ON uid_topics.tid = topics.tid WHERE uid NOT IN (SELECT uid FROM uid_details WHERE ruid IN (SELECT ruid FROM uid_details WHERE status = 4)) ORDER BY v1 DESC LIMIT '.$this->maxrows]);
			$t->set_value('v3a', true);
			$output .= $t->make_table($sqlite3);

			if ($output !== '') {
				$html .= '<div class="section">Events</div>'."\n".$output;
			}
		}

		/**
		 * Smileys section.
		 */
		if ($this->sectionbits & 16) {
			$output = '';

			/**
			 * Display the top 9 smiley tables ordered by totals.
			 */
			if (($result = $sqlite3->querySingle('SELECT SUM(s_01) AS s_01, SUM(s_02) AS s_02, SUM(s_03) AS s_03, SUM(s_04) AS s_04, SUM(s_05) AS s_05, SUM(s_06) AS s_06, SUM(s_07) AS s_07, SUM(s_08) AS s_08, SUM(s_09) AS s_09, SUM(s_10) AS s_10, SUM(s_11) AS s_11, SUM(s_12) AS s_12, SUM(s_13) AS s_13, SUM(s_14) AS s_14, SUM(s_15) AS s_15, SUM(s_16) AS s_16, SUM(s_17) AS s_17, SUM(s_18) AS s_18, SUM(s_19) AS s_19, SUM(s_20) AS s_20, SUM(s_21) AS s_21, SUM(s_22) AS s_22, SUM(s_23) AS s_23, SUM(s_24) AS s_24, SUM(s_25) AS s_25, SUM(s_26) AS s_26, SUM(s_27) AS s_27, SUM(s_28) AS s_28, SUM(s_29) AS s_29, SUM(s_30) AS s_30, SUM(s_31) AS s_31, SUM(s_32) AS s_32, SUM(s_33) AS s_33, SUM(s_34) AS s_34, SUM(s_35) AS s_35, SUM(s_36) AS s_36, SUM(s_37) AS s_37, SUM(s_38) AS s_38, SUM(s_39) AS s_39, SUM(s_40) AS s_40, SUM(s_41) AS s_41, SUM(s_42) AS s_42, SUM(s_43) AS s_43, SUM(s_44) AS s_44, SUM(s_45) AS s_45, SUM(s_46) AS s_46, SUM(s_47) AS s_47, SUM(s_48) AS s_48, SUM(s_49) AS s_49, SUM(s_50) AS s_50 FROM ruid_smileys', true)) === false) {
				output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			if (!empty($result)) {
				$smileys = [
					's_01' => [':)', 'Happy'],
					's_02' => [';)', 'Wink'],
					's_03' => [':(', 'Sad'],
					's_04' => [':P', 'Tongue Sticking Out'],
					's_05' => [':D', 'Laugh'],
					's_06' => [';(', 'Cry'],
					's_07' => [':/', 'Skeptical'],
					's_08' => ['\\o/', 'Cheer'],
					's_09' => [':))', 'Super Happy'],
					's_10' => ['<3', 'Love'],
					's_11' => [':o', 'Surprised'],
					's_12' => ['=)', 'Cheerful Smile'],
					's_13' => [':-)', 'Classic Happy'],
					's_14' => [':x', 'Kiss'],
					's_15' => [':\\', 'Skeptical'],
					's_16' => ['D:', 'Shocked'],
					's_17' => [':|', 'Straight Face'],
					's_18' => [';-)', 'Classic Wink'],
					's_19' => [';P', 'Silly'],
					's_20' => ['=]', 'Big Cheerful Smile'],
					's_21' => [':3', 'Kitty'],
					's_22' => ['8)', 'Cool Smile'],
					's_23' => [':<', 'Sad'],
					's_24' => [':>', 'Happy Smile'],
					's_25' => ['=P', 'Funny Face'],
					's_26' => [';x', 'Lovely Kiss'],
					's_27' => [':-D', 'Classic Laugh'],
					's_28' => [';))', 'Extreme Wink'],
					's_29' => [':]', 'Big Smile'],
					's_30' => [';D', 'Winking Laugh'],
					's_31' => ['-_-', 'Not Amused'],
					's_32' => [':S', 'Confused'],
					's_33' => ['=/', 'Skeptical'],
					's_34' => ['=\\', 'Skeptical'],
					's_35' => [':((', 'Super Sad'],
					's_36' => ['=D', 'Cheerful Laugh'],
					's_37' => [':-/', 'Classic Skeptical'],
					's_38' => [':-P', 'Classic Tongue Sticking Out'],
					's_39' => [';_;', 'Crying'],
					's_40' => [';/', '...'],
					's_41' => [';]', 'Big Wink'],
					's_42' => [':-(', 'Classic Sad'],
					's_43' => [':\'(', 'Tear'],
					's_44' => ['=(', 'Sad'],
					's_45' => ['-.-', 'Not Amused'],
					's_46' => [';((', 'Crying'],
					's_47' => ['=X', 'Kiss'],
					's_48' => [':[', 'Sad'],
					's_49' => ['>:(', 'Angry'],
					's_50' => [';o', 'Joking']];
				arsort($result);
				array_splice($result, 9);

				foreach ($result as $key => $value) {
					$t = new table($smileys[$key][1], $this->minrows, $this->maxrows);
					$t->set_value('keys', [
						'k1' => htmlspecialchars($smileys[$key][0]),
						'k2' => 'User',
						'v1' => 'int',
						'v2' => 'string']);
					$t->set_value('queries', ['main' => 'SELECT '.$key.' AS v1, csnick AS v2 FROM ruid_smileys JOIN uid_details ON ruid_smileys.ruid = uid_details.uid WHERE status NOT IN (3,4) AND '.$key.' != 0 ORDER BY v1 DESC, ruid_smileys.ruid ASC LIMIT '.$this->maxrows]);
					$t->set_value('total', $value);
					$output .= $t->make_table($sqlite3);
				}
			}

			if ($output !== '') {
				$html .= '<div class="section">Smileys</div>'."\n".$output;
			}
		}

		/**
		 * URLs section.
		 */
		if ($this->sectionbits & 32) {
			$output = '';

			$t = new table('Most Referenced Domain Names', $this->rows_domains_tlds, $this->rows_domains_tlds);
			$t->set_value('keys', [
				'k1' => 'Total',
				'k2' => 'Domain',
				'k3' => 'First Used',
				'v1' => 'int',
				'v2' => 'url',
				'v3' => 'date']);
			$t->set_value('medium', true);
			$t->set_value('queries', ['main' => 'SELECT COUNT(*) AS v1, \'http://\' || fqdn AS v2, MIN(datetime) AS v3 FROM uid_urls JOIN urls ON uid_urls.lid = urls.lid JOIN fqdns ON urls.fid = fqdns.fid GROUP BY urls.fid ORDER BY v1 DESC, v3 ASC LIMIT '.$this->rows_domains_tlds]);
			$output .= $t->make_table($sqlite3);

			$t = new table('Most Referenced TLDs', $this->rows_domains_tlds, $this->rows_domains_tlds);
			$t->set_value('keys', [
				'k1' => 'Total',
				'k2' => 'TLD',
				'v1' => 'int',
				'v2' => 'string']);
			$t->set_value('queries', ['main' => 'SELECT COUNT(*) AS v1, tld AS v2 FROM uid_urls JOIN urls ON uid_urls.lid = urls.lid JOIN fqdns ON urls.fid = fqdns.fid GROUP BY tld ORDER BY v1 DESC, v2 ASC LIMIT '.$this->rows_domains_tlds]);
			$output .= $t->make_table($sqlite3);

			if ($this->recenturls_type !== 0) {
				$t = new table('Most Recent URLs', $this->minrows, $this->maxrows_recenturls);
				$t->set_value('keys', [
					'k1' => 'Date',
					'k2' => 'User',
					'k3' => 'URL',
					'v1' => 'date-norepeat',
					'v2' => 'string',
					'v3' => 'url']);

				if ($this->recenturls_type === 1) {
					$t->set_value('queries', ['main' => 'SELECT uid_urls.datetime AS v1, (SELECT csnick FROM uid_details WHERE uid = (SELECT ruid FROM uid_details WHERE uid = uid_urls.uid)) AS v2, url AS v3 FROM uid_urls JOIN (SELECT MAX(datetime) AS datetime, lid FROM uid_urls WHERE uid NOT IN (SELECT uid FROM uid_details WHERE ruid IN (SELECT ruid FROM uid_details WHERE status = 4)) GROUP BY lid) AS t1 ON uid_urls.datetime = t1.datetime AND uid_urls.lid = t1.lid, urls ON uid_urls.lid = urls.lid ORDER BY v1 DESC LIMIT '.$this->maxrows_recenturls]);
				} elseif ($this->recenturls_type === 2) {
					$t->set_value('queries', ['main' => 'SELECT uid_urls.datetime AS v1, (SELECT csnick FROM uid_details WHERE uid = (SELECT ruid FROM uid_details WHERE uid = uid_urls.uid)) AS v2, url AS v3 FROM uid_urls JOIN (SELECT MAX(datetime) AS datetime, lid FROM uid_urls WHERE uid NOT IN (SELECT uid FROM uid_details WHERE ruid IN (SELECT ruid FROM uid_details WHERE status IN (3,4))) GROUP BY lid) AS t1 ON uid_urls.datetime = t1.datetime AND uid_urls.lid = t1.lid, urls ON uid_urls.lid = urls.lid ORDER BY v1 DESC LIMIT '.$this->maxrows_recenturls]);
				}

				$output .= $t->make_table($sqlite3);
			}

			$t = new table('URLs by Users', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Total',
				'k2' => 'User',
				'v1' => 'int',
				'v2' => 'string']);
			$t->set_value('queries', [
				'main' => 'SELECT urls AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND urls != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(urls) FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status != 3']);
			$output .= $t->make_table($sqlite3);

			$t = new table('URLs by Bots', $this->minrows, $this->maxrows);
			$t->set_value('keys', [
				'k1' => 'Total',
				'k2' => 'Bot',
				'v1' => 'int',
				'v2' => 'string']);
			$t->set_value('queries', [
				'main' => 'SELECT urls AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status = 3 AND urls != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows,
				'total' => 'SELECT SUM(urls) FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status = 3']);
			$output .= $t->make_table($sqlite3);

			if ($output !== '') {
				$html .= '<div class="section">URLs</div>'."\n".$output;
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
			$query = $sqlite3->query('SELECT * FROM (SELECT length, COUNT(*) AS total FROM words GROUP BY length ORDER BY total DESC, length DESC LIMIT 9) ORDER BY length ASC') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

			while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
				$t = new table('Words of '.$result['length'].' Characters', $this->minrows, $this->maxrows);
				$t->set_value('keys', [
					'k1' => 'Times Used',
					'k2' => 'Word',
					'v1' => 'int',
					'v2' => 'string']);
				$t->set_value('queries', ['main' => 'SELECT total AS v1, word AS v2 FROM words WHERE length = '.$result['length'].' ORDER BY v1 DESC, v2 ASC LIMIT '.$this->maxrows]);
				$t->set_value('total', $result['total']);
				$output .= $t->make_table($sqlite3);
			}

			if ($output !== '') {
				$html .= '<div class="section">Words</div>'."\n".$output;
			}
		}

		/**
		 * Milestones section.
		 */
		if ($this->sectionbits & 128) {
			$output = '';
			$query = $sqlite3->query('SELECT milestone, COUNT(*) AS total FROM ruid_milestones GROUP BY milestone ORDER BY milestone ASC') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

			while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
				$t = new table(number_format($result['milestone']).' Lines Milestone', 1, $this->maxrows);
				$t->set_value('keys', [
					'k1' => 'Date',
					'k2' => 'User',
					'v1' => 'date',
					'v2' => 'string']);
				$t->set_value('queries', ['main' => 'SELECT date AS v1, csnick AS v2 FROM ruid_milestones JOIN uid_details ON ruid_milestones.ruid = uid_details.uid WHERE milestone = '.$result['milestone'].' ORDER BY v1 ASC, ruid_milestones.ruid ASC LIMIT '.$this->maxrows]);
				$t->set_value('total', $result['total']);
				$output .= $t->make_table($sqlite3);
			}

			if ($output !== '') {
				$html .= '<div class="section">Milestones</div>'."\n".$output;
			}
		}

		/**
		 * HTML Foot.
		 */
		$html .= '<div class="info">Statistics created with <a href="http://sss.dutnie.nl">superseriousstats</a> on '.date('r').'.'.($this->addhtml_foot !== '' ? '<br>'.trim(file_get_contents($this->addhtml_foot)) : '').'</div>'."\n";
		$html .= '</div></body>'."\n\n".'</html>'."\n";
		return $html;
	}

	private function make_table_activity($sqlite3, $type)
	{
		if ($type === 'day') {
			$class = 'act';
			$columns = 24;
			$head = 'Activity by Day';
			$query = $sqlite3->query('SELECT date, l_total, l_night, l_morning, l_afternoon, l_evening FROM channel_activity WHERE date > \''.date('Y-m-d', mktime(0, 0, 0, $this->datetime['month'], $this->datetime['dayofmonth'] - 24, $this->datetime['year'])).'\'') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

			for ($i = $columns - 1; $i >= 0; $i--) {
				$dates[] = date('Y-m-d', mktime(0, 0, 0, $this->datetime['month'], $this->datetime['dayofmonth'] - $i, $this->datetime['year']));
			}
		} elseif ($type === 'month') {
			$class = 'act';
			$columns = 24;
			$head = 'Activity by Month';
			$query = $sqlite3->query('SELECT SUBSTR(date, 1, 7) AS date, SUM(l_total) AS l_total, SUM(l_night) AS l_night, SUM(l_morning) AS l_morning, SUM(l_afternoon) AS l_afternoon, SUM(l_evening) AS l_evening FROM channel_activity WHERE date > \''.date('Y-m', mktime(0, 0, 0, $this->datetime['month'] - 24, 1, $this->datetime['year'])).'%\' GROUP BY SUBSTR(date, 1, 7)') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

			for ($i = $columns - 1; $i >= 0; $i--) {
				$dates[] = date('Y-m', mktime(0, 0, 0, $this->datetime['month'] - $i, 1, $this->datetime['year']));
			}
		} elseif ($type === 'year') {
			$class = 'act-year';
			$columns = $this->columns_act_year;
			$head = 'Activity by Year';
			$query = $sqlite3->query('SELECT SUBSTR(date, 1, 4) AS date, SUM(l_total) AS l_total, SUM(l_night) AS l_night, SUM(l_morning) AS l_morning, SUM(l_afternoon) AS l_afternoon, SUM(l_evening) AS l_evening FROM channel_activity WHERE SUBSTR(date, 1, 4) > \''.($this->datetime['year'] - 24).'\' GROUP BY SUBSTR(date, 1, 4)') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

			for ($i = $columns - ($this->estimate ? 1 : 0) - 1; $i >= 0; $i--) {
				$dates[] = $this->datetime['year'] - $i;
			}

			if ($this->estimate) {
				$dates[] = 'estimate';
			}
		}

		if (($result = $query->fetchArray(SQLITE3_ASSOC)) === false) {
			return null;
		}

		$high_date = '';
		$high_value = 0;
		$query->reset();

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$l_afternoon[$result['date']] = $result['l_afternoon'];
			$l_evening[$result['date']] = $result['l_evening'];
			$l_morning[$result['date']] = $result['l_morning'];
			$l_night[$result['date']] = $result['l_night'];
			$l_total[$result['date']] = $result['l_total'];

			if ($result['l_total'] > $high_value) {
				$high_date = $result['date'];
				$high_value = $result['l_total'];
			}
		}

		if ($this->estimate && $type === 'year' && !empty($l_total[$this->datetime['currentyear']])) {
			if (($result = $sqlite3->querySingle('SELECT CAST(SUM(l_night) AS REAL) / 90 AS l_night_avg, CAST(SUM(l_morning) AS REAL) / 90 AS l_morning_avg, CAST(SUM(l_afternoon) AS REAL) / 90 AS l_afternoon_avg, CAST(SUM(l_evening) AS REAL) / 90 AS l_evening_avg, CAST(SUM(l_total) AS REAL) / 90 AS l_total_avg FROM channel_activity WHERE date > \''.date('Y-m-d', mktime(0, 0, 0, $this->datetime['month'], $this->datetime['dayofmonth'] - 90, $this->datetime['year'])).'\'', true)) === false) {
				output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			$l_afternoon['estimate'] = $l_afternoon[$this->datetime['currentyear']] + round($result['l_afternoon_avg'] * $this->datetime['daysleft']);
			$l_evening['estimate'] = $l_evening[$this->datetime['currentyear']] + round($result['l_evening_avg'] * $this->datetime['daysleft']);
			$l_morning['estimate'] = $l_morning[$this->datetime['currentyear']] + round($result['l_morning_avg'] * $this->datetime['daysleft']);
			$l_night['estimate'] = $l_night[$this->datetime['currentyear']] + round($result['l_night_avg'] * $this->datetime['daysleft']);
			$l_total['estimate'] = $l_total[$this->datetime['currentyear']] + round($result['l_total_avg'] * $this->datetime['daysleft']);

			if ($l_total['estimate'] > $high_value) {
				/**
				 * Don't set $high_date because we don't want "Est." to be bold. The previous
				 * highest date will be bold instead. $high_value must be set in order to
				 * calculate bar heights.
				 */
				$high_value = $l_total['estimate'];
			}
		}

		$times = ['evening', 'afternoon', 'morning', 'night'];
		$tr1 = '<tr><th colspan="'.$columns.'">'.$head;
		$tr2 = '<tr class="bars">';
		$tr3 = '<tr class="sub">';

		foreach ($dates as $date) {
			if (!array_key_exists($date, $l_total)) {
				$tr2 .= '<td><span class="grey">n/a</span>';
			} else {
				if ($l_total[$date] >= 999500) {
					$total = number_format($l_total[$date] / 1000000, 1).'M';
				} elseif ($l_total[$date] >= 10000) {
					$total = round($l_total[$date] / 1000).'k';
				} else {
					$total = $l_total[$date];
				}

				foreach ($times as $time) {
					if (${'l_'.$time}[$date] !== 0) {
						$height[$time] = round((${'l_'.$time}[$date] / $high_value) * 100);
					} else {
						$height[$time] = (float) 0;
					}
				}

				$tr2 .= '<td'.($date === 'estimate' ? ' class="est"' : '').'><ul><li class="num" style="height:'.($height['night'] + $height['morning'] + $height['afternoon'] + $height['evening'] + 14).'px">'.$total;

				foreach ($times as $time) {
					if ($height[$time] !== (float) 0) {
						if ($time === 'evening') {
							$height_li = $height['night'] + $height['morning'] + $height['afternoon'] + $height['evening'];
						} elseif ($time === 'afternoon') {
							$height_li = $height['night'] + $height['morning'] + $height['afternoon'];
						} elseif ($time === 'morning') {
							$height_li = $height['night'] + $height['morning'];
						} elseif ($time === 'night') {
							$height_li = $height['night'];
						}

						$tr2 .= '<li class="'.$this->color[$time].'" style="height:'.$height_li.'px">';
					}
				}

				$tr2 .= '</ul>';
			}

			if ($type === 'day') {
				$tr3 .= '<td'.($date === $high_date ? ' class="bold"' : '').'>'.date('D', strtotime($date)).'<br>'.date('j', strtotime($date));
			} elseif ($type === 'month') {
				$tr3 .= '<td'.($date === $high_date ? ' class="bold"' : '').'>'.date('M', strtotime($date.'-01')).'<br>'.date('\'y', strtotime($date.'-01'));
			} elseif ($type === 'year') {
				$tr3 .= '<td'.($date === (int) $high_date ? ' class="bold"' : '').'>'.($date === 'estimate' ? 'Est.' : date('\'y', strtotime($date.'-01-01')));
			}
		}

		return '<table class="'.$class.'">'.$tr1.$tr2.$tr3.'</table>'."\n";
	}

	private function make_table_activity_distribution_day($sqlite3)
	{
		if (($result = $sqlite3->querySingle('SELECT SUM(l_mon_night) AS l_mon_night, SUM(l_mon_morning) AS l_mon_morning, SUM(l_mon_afternoon) AS l_mon_afternoon, SUM(l_mon_evening) AS l_mon_evening, SUM(l_tue_night) AS l_tue_night, SUM(l_tue_morning) AS l_tue_morning, SUM(l_tue_afternoon) AS l_tue_afternoon, SUM(l_tue_evening) AS l_tue_evening, SUM(l_wed_night) AS l_wed_night, SUM(l_wed_morning) AS l_wed_morning, SUM(l_wed_afternoon) AS l_wed_afternoon, SUM(l_wed_evening) AS l_wed_evening, SUM(l_thu_night) AS l_thu_night, SUM(l_thu_morning) AS l_thu_morning, SUM(l_thu_afternoon) AS l_thu_afternoon, SUM(l_thu_evening) AS l_thu_evening, SUM(l_fri_night) AS l_fri_night, SUM(l_fri_morning) AS l_fri_morning, SUM(l_fri_afternoon) AS l_fri_afternoon, SUM(l_fri_evening) AS l_fri_evening, SUM(l_sat_night) AS l_sat_night, SUM(l_sat_morning) AS l_sat_morning, SUM(l_sat_afternoon) AS l_sat_afternoon, SUM(l_sat_evening) AS l_sat_evening, SUM(l_sun_night) AS l_sun_night, SUM(l_sun_morning) AS l_sun_morning, SUM(l_sun_afternoon) AS l_sun_afternoon, SUM(l_sun_evening) AS l_sun_evening FROM ruid_lines', true)) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
		$high_day = '';
		$high_value = 0;

		foreach ($days as $day) {
			$l_afternoon[$day] = $result['l_'.$day.'_afternoon'];
			$l_evening[$day] = $result['l_'.$day.'_evening'];
			$l_morning[$day] = $result['l_'.$day.'_morning'];
			$l_night[$day] = $result['l_'.$day.'_night'];
			$l_total[$day] = $l_night[$day] + $l_morning[$day] + $l_afternoon[$day] + $l_evening[$day];

			if ($l_total[$day] > $high_value) {
				$high_day = $day;
				$high_value = $l_total[$day];
			}
		}

		$times = ['evening', 'afternoon', 'morning', 'night'];
		$tr1 = '<tr><th colspan="7">Activity Distribution by Day';
		$tr2 = '<tr class="bars">';
		$tr3 = '<tr class="sub">';

		foreach ($days as $day) {
			if ($l_total[$day] === 0) {
				$tr2 .= '<td><span class="grey">n/a</span>';
			} else {
				$percentage = ($l_total[$day] / $this->l_total) * 100;

				if ($percentage >= 9.95) {
					$percentage = round($percentage).'%';
				} else {
					$percentage = number_format($percentage, 1).'%';
				}

				foreach ($times as $time) {
					if (${'l_'.$time}[$day] !== 0) {
						$height[$time] = round((${'l_'.$time}[$day] / $high_value) * 100);
					} else {
						$height[$time] = (float) 0;
					}
				}

				$tr2 .= '<td><ul><li class="num" style="height:'.($height['night'] + $height['morning'] + $height['afternoon'] + $height['evening'] + 14).'px">'.$percentage;

				foreach ($times as $time) {
					if ($height[$time] !== (float) 0) {
						if ($time === 'evening') {
							$height_li = $height['night'] + $height['morning'] + $height['afternoon'] + $height['evening'];
						} elseif ($time === 'afternoon') {
							$height_li = $height['night'] + $height['morning'] + $height['afternoon'];
						} elseif ($time === 'morning') {
							$height_li = $height['night'] + $height['morning'];
						} elseif ($time === 'night') {
							$height_li = $height['night'];
						}

						$tr2 .= '<li class="'.$this->color[$time].'" style="height:'.$height_li.'px" title="'.number_format($l_total[$day]).'">';
					}
				}

				$tr2 .= '</ul>';
			}

			$tr3 .= '<td'.($day === $high_day ? ' class="bold"' : '').'>'.ucfirst($day);
		}

		return '<table class="act-day">'.$tr1.$tr2.$tr3.'</table>'."\n";
	}

	private function make_table_activity_distribution_hour($sqlite3)
	{
		if (($result = $sqlite3->querySingle('SELECT SUM(l_00) AS l_00, SUM(l_01) AS l_01, SUM(l_02) AS l_02, SUM(l_03) AS l_03, SUM(l_04) AS l_04, SUM(l_05) AS l_05, SUM(l_06) AS l_06, SUM(l_07) AS l_07, SUM(l_08) AS l_08, SUM(l_09) AS l_09, SUM(l_10) AS l_10, SUM(l_11) AS l_11, SUM(l_12) AS l_12, SUM(l_13) AS l_13, SUM(l_14) AS l_14, SUM(l_15) AS l_15, SUM(l_16) AS l_16, SUM(l_17) AS l_17, SUM(l_18) AS l_18, SUM(l_19) AS l_19, SUM(l_20) AS l_20, SUM(l_21) AS l_21, SUM(l_22) AS l_22, SUM(l_23) AS l_23 FROM channel_activity', true)) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
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
		 * Only create the table if there is activity from users other than bots and
		 * excluded users.
		 */
		if ($type === 'alltime') {
			if (($total = $sqlite3->querySingle('SELECT SUM(l_total) FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4)')) === false) {
				output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		} elseif ($type === 'month') {
			if (($total = $sqlite3->querySingle('SELECT SUM(l_total) FROM ruid_activity_by_month JOIN uid_details ON ruid_activity_by_month.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date = \''.date('Y-m', mktime(0, 0, 0, $this->datetime['month'], 1, $this->datetime['year'])).'\'')) === false) {
				output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		} elseif ($type === 'year') {
			if (($total = $sqlite3->querySingle('SELECT SUM(l_total) FROM ruid_activity_by_year JOIN uid_details ON ruid_activity_by_year.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date = \''.$this->datetime['year'].'\'')) === false) {
				output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		}

		if (empty($total)) {
			return null;
		}

		if ($type === 'alltime') {
			$head = 'Most Talkative People &ndash; All-Time';
			$historylink = '<a href="history.php?cid='.urlencode($this->cid).'">History</a>';

			/**
			 * Don't try to calculate changes in rankings if we're dealing with the first
			 * month of activity.
			 */
			if (!$this->rankings || date('Y-m', mktime(0, 0, 0, $this->datetime['month'], 1, $this->datetime['year'])) === $this->datetime['firstyearmonth']) {
				$query = $sqlite3->query('SELECT csnick, l_total, l_night, l_morning, l_afternoon, l_evening, quote, (SELECT MAX(lastseen) FROM uid_details WHERE ruid = ruid_lines.ruid) AS lastseen FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND l_total != 0 ORDER BY l_total DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows_people_alltime) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			} else {
				$query = $sqlite3->query('SELECT csnick, l_total, l_night, l_morning, l_afternoon, l_evening, quote, (SELECT MAX(lastseen) FROM uid_details WHERE ruid = ruid_lines.ruid) AS lastseen, (SELECT rank FROM ruid_rankings WHERE ruid = ruid_lines.ruid AND date = \''.date('Y-m', mktime(0, 0, 0, $this->datetime['month'] - 1, 1, $this->datetime['year'])).'\') AS prevrank FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND l_total != 0 ORDER BY l_total DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows_people_alltime) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		} elseif ($type === 'month') {
			$head = 'Most Talkative People &ndash; '.$this->datetime['monthname'].' '.$this->datetime['year'];
			$historylink = '<a href="history.php?cid='.urlencode($this->cid).'&amp;year='.$this->datetime['year'].'&amp;month='.$this->datetime['month'].'">History</a>';
			$query = $sqlite3->query('SELECT csnick, ruid_activity_by_month.l_total AS l_total, ruid_activity_by_month.l_night AS l_night, ruid_activity_by_month.l_morning AS l_morning, ruid_activity_by_month.l_afternoon AS l_afternoon, ruid_activity_by_month.l_evening AS l_evening, quote, (SELECT MAX(lastseen) FROM uid_details WHERE ruid = ruid_activity_by_month.ruid) AS lastseen FROM ruid_activity_by_month JOIN uid_details ON ruid_activity_by_month.ruid = uid_details.uid JOIN ruid_lines ON ruid_activity_by_month.ruid = ruid_lines.ruid WHERE status NOT IN (3,4) AND date = \''.date('Y-m', mktime(0, 0, 0, $this->datetime['month'], 1, $this->datetime['year'])).'\' ORDER BY l_total DESC, ruid_activity_by_month.ruid ASC LIMIT '.$this->maxrows_people_month) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		} elseif ($type === 'year') {
			$head = 'Most Talkative People &ndash; '.$this->datetime['year'];
			$historylink = '<a href="history.php?cid='.urlencode($this->cid).'&amp;year='.$this->datetime['year'].'">History</a>';
			$query = $sqlite3->query('SELECT csnick, ruid_activity_by_year.l_total AS l_total, ruid_activity_by_year.l_night AS l_night, ruid_activity_by_year.l_morning AS l_morning, ruid_activity_by_year.l_afternoon AS l_afternoon, ruid_activity_by_year.l_evening AS l_evening, quote, (SELECT MAX(lastseen) FROM uid_details WHERE ruid = ruid_activity_by_year.ruid) AS lastseen FROM ruid_activity_by_year JOIN uid_details ON ruid_activity_by_year.ruid = uid_details.uid JOIN ruid_lines ON ruid_activity_by_year.ruid = ruid_lines.ruid WHERE status NOT IN (3,4) AND date = \''.$this->datetime['year'].'\' ORDER BY l_total DESC, ruid_activity_by_year.ruid ASC LIMIT '.$this->maxrows_people_year) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$i = 0;
		$times = ['night', 'morning', 'afternoon', 'evening'];
		$tr0 = '<colgroup><col class="c1"><col class="c2"><col class="pos"><col class="c3"><col class="c4"><col class="c5"><col class="c6">';
		$tr1 = '<tr><th colspan="7">'.($this->history ? '<span class="title">'.$head.'</span><span class="title-right">'.$historylink.'</span>' : $head);
		$tr2 = '<tr><td class="k1">Percentage<td class="k2">Lines<td class="pos"><td class="k3">User<td class="k4">When?<td class="k5">Last Seen<td class="k6">Quote';
		$trx = '';

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$i++;
			$width = 50;

			foreach ($times as $time) {
				if ($result['l_'.$time] !== 0) {
					$width_float[$time] = (float) ($result['l_'.$time] / $result['l_total']) * 50;
					$width_int[$time] = (int) floor($width_float[$time]);
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

			if (!isset($result['prevrank']) || $i === $result['prevrank']) {
				$pos = $i;
			} elseif ($i < $result['prevrank']) {
				$pos = '<span class="green">&#x25B2;'.$i.'</span>';
			} elseif ($i > $result['prevrank']) {
				$pos = '<span class="red">&#x25BC;'.$i.'</span>';
			}

			$trx .= '<tr><td class="v1">'.number_format(($result['l_total'] / $total) * 100, 2).'%<td class="v2">'.number_format($result['l_total']).'<td class="pos">'.$pos.'<td class="v3">'.($this->userstats ? '<a href="user.php?cid='.urlencode($this->cid).'&amp;nick='.urlencode($result['csnick']).'">'.htmlspecialchars($result['csnick']).'</a>' : htmlspecialchars($result['csnick'])).'<td class="v4"><ul>'.$when.'</ul><td class="v5">'.$this->daysago($result['lastseen']).'<td class="v6">'.htmlspecialchars($result['quote']);
			unset($width_float, $width_int, $width_remainders);
		}

		return '<table class="ppl">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}

	private function make_table_people2($sqlite3)
	{
		$current_column = 1;
		$current_row = 0;

		/**
		 * Don't try to calculate changes in rankings if we're dealing with the first
		 * month of activity.
		 */
		if (!$this->rankings || date('Y-m', mktime(0, 0, 0, $this->datetime['month'], 1, $this->datetime['year'])) === $this->datetime['firstyearmonth']) {
			$query = $sqlite3->query('SELECT csnick, l_total FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND l_total != 0 ORDER BY l_total DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows_people_alltime.', '.($this->maxrows_people2 * 4)) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		} else {
			$query = $sqlite3->query('SELECT csnick, l_total, (SELECT rank FROM ruid_rankings WHERE ruid = ruid_lines.ruid AND date = \''.date('Y-m', mktime(0, 0, 0, $this->datetime['month'] - 1, 1, $this->datetime['year'])).'\') AS prevrank FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND l_total != 0 ORDER BY l_total DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows_people_alltime.', '.($this->maxrows_people2 * 4)) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$current_row++;

			if ($current_row > $this->maxrows_people2) {
				$current_column++;
				$current_row = 1;
			}

			$i = $this->maxrows_people_alltime + ($current_column > 1 ? ($current_column - 1) * $this->maxrows_people2 : 0) + $current_row;

			if (!isset($result['prevrank']) || $i === $result['prevrank']) {
				$pos = $i;
			} elseif ($i < $result['prevrank']) {
				$pos = '<span class="green">&#x25B2;'.$i.'</span>';
			} elseif ($i > $result['prevrank']) {
				$pos = '<span class="red">&#x25BC;'.$i.'</span>';
			}

			$columns[$current_column][$current_row] = [$pos, $result['csnick'], $result['l_total']];
		}

		if ($current_column < 4 || $current_row < $this->maxrows_people2) {
			return null;
		}

		if (($total = $sqlite3->querySingle('SELECT COUNT(*) FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4)')) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$total -= $this->maxrows_people_alltime + ($this->maxrows_people2 * 4);
		$tr0 = '<colgroup><col class="c1"><col class="pos"><col class="c2"><col class="c1"><col class="pos"><col class="c2"><col class="c1"><col class="pos"><col class="c2"><col class="c1"><col class="pos"><col class="c2">';
		$tr1 = '<tr><th colspan="12">'.($total !== 0 ? '<span class="title">Less Talkative People &ndash; All-Time</span><span class="title-right">'.number_format($total).' People had even less to say..</span>' : 'Less Talkative People &ndash; All-Time');
		$tr2 = '<tr><td class="k1">Lines<td class="pos"><td class="k2">User<td class="k1">Lines<td class="pos"><td class="k2">User<td class="k1">Lines<td class="pos"><td class="k2">User<td class="k1">Lines<td class="pos"><td class="k2">User';
		$trx = '';

		for ($i = 1; $i <= $this->maxrows_people2; $i++) {
			$trx .= '<tr>';

			for ($j = 1; $j <= 4; $j++) {
				$trx .= '<td class="v1">'.number_format($columns[$j][$i][2]).'<td class="pos">'.$columns[$j][$i][0].'<td class="v2">'.($this->userstats ? '<a href="user.php?cid='.urlencode($this->cid).'&amp;nick='.urlencode($columns[$j][$i][1]).'">'.htmlspecialchars($columns[$j][$i][1]).'</a>' : htmlspecialchars($columns[$j][$i][1]));
			}
		}

		return '<table class="ppl2">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}

	private function make_table_people_timeofday($sqlite3)
	{
		/**
		 * Only create the table if there is activity from users other than bots and
		 * excluded users.
		 */
		if (($total = $sqlite3->querySingle('SELECT SUM(l_total) FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4)')) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		if (empty($total)) {
			return null;
		}

		$high_value = 0;
		$times = ['night', 'morning', 'afternoon', 'evening'];

		foreach ($times as $time) {
			$query = $sqlite3->query('SELECT csnick, l_'.$time.' FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND l_'.$time.' != 0 ORDER BY l_'.$time.' DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows_people_timeofday) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
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
			}

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

		return '<table class="ppl-tod">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}
}
