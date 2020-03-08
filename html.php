<?php

/**
 * Copyright (c) 2007-2020, Jos de Ruijter <jos@dutnie.nl>
 */

declare(strict_types=1);

/**
 * Class for creating the main stats page.
 */
class html
{
	use config;

	/**
	 * Variables listed in $settings_allow_override[] can have their default value
	 * overridden through the config file.
	 */
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
	private $settings_allow_override = ['channel', 'cid', 'history', 'recenturls_type', 'search_user', 'stylesheet', 'user_stats'];
	private $sqlite3;
	private $stylesheet = 'sss.css';
	private $user_stats = true;

	public function __construct(array $config, object $sqlite3)
	{
		/**
		 * Apply settings from the config file.
		 */
		$this->apply_settings($config);

		/**
		 * If $cid has no value set it to $channel.
		 */
		if ($this->cid === '') {
			$this->cid = $this->channel;
		}

		$this->sqlite3 = $sqlite3;
	}

	/**
	 * Calculate how many days ago a given $datetime is.
	 */
	private function ago(string $datetime): string
	{
		$diff = date_diff(date_create('today'), date_create(substr($datetime, 0, 10)));

		if ($diff->y > 0) {
			$ago = $diff->y.' Year'.($diff->y !== 1 ? 's' : '').' Ago';
		} elseif ($diff->m > 0) {
			$ago = $diff->m.' Month'.($diff->m !== 1 ? 's' : '').' Ago';
		} elseif ($diff->d === 0) {
			$ago = 'Today';
		} elseif ($diff->d === 1) {
			$ago = 'Yesterday';
		} else {
			$ago = $diff->d.' Days Ago';
		}

		return $ago;
	}

	/**
	 * Generate the HTML page.
	 */
	public function get_contents()
	{
		output::output('notice', __METHOD__.'(): creating stats page');

		if (($this->l_total = $this->sqlite3->querySingle('SELECT SUM(l_total) FROM channel_activity')) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
		}

		/**
		 * All queries from this point forward require that there has at least been one
		 * line typed in the channel.
		 */
		if (is_null($this->l_total)) {
			output::output('debug', __METHOD__.'(): database is empty');
			return '<!DOCTYPE html>'."\n\n".'<html><head><meta charset="utf-8"><title>seriously?</title><link rel="stylesheet" href="sss.css"></head><body><div id="container"><div class="error">There is not enough data to create statistics, yet.</div></div></body></html>'."\n";
		}

		if (($result = $this->sqlite3->querySingle('SELECT MIN(date) AS date_first, MAX(date) AS date_last FROM channel_activity', true)) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
		}

		$date_first = $result['date_first'];
		$date_last = $result['date_last'];

		if (($result = $this->sqlite3->querySingle('SELECT COUNT(*) AS dayslogged, MAX(date) AS date FROM parse_history', true)) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
		}

		$date_lastlogparsed = $result['date'];
		$dayslogged = $result['dayslogged'];
		$l_avg = (int) round($this->l_total / $dayslogged);

		/**
		 * Date and time variables used throughout the script. These are based on the
		 * date of the last logfile parsed, and are used to define our scope.
		 */
		$this->datetime['dayofmonth'] = (int) date('j', strtotime($date_lastlogparsed));
		$this->datetime['firstyearmonth'] = substr($date_first, 0, 7);
		$this->datetime['month'] = (int) date('n', strtotime($date_lastlogparsed));
		$this->datetime['monthname'] = date('F', strtotime($date_lastlogparsed));
		$this->datetime['year'] = (int) date('Y', strtotime($date_lastlogparsed));

		/**
		 * If the date of the last logfile parsed is in the current year and there are
		 * one or more days to come until the end of the year, display an additional
		 * column in the Activity by Year table with an estimated line count for said
		 * year. The estimation is based on the activity in the last 90 days logged and
		 * won't be shown if there has been no activity in the current year yet.
		 */
		if ($this->datetime['year'] === (int) date('Y')) {
			$this->datetime['daysleft'] = (int) date('z', strtotime('last day of December '.$this->datetime['year'])) - (int) date('z', strtotime($date_lastlogparsed));

			if ($this->datetime['daysleft'] !== 0) {
				if (($date_lastactivity = $this->sqlite3->querySingle('SELECT MAX(date) FROM channel_activity WHERE date > \''.date('Y-m-d', mktime(0, 0, 0, $this->datetime['month'], $this->datetime['dayofmonth'] - 90, $this->datetime['year'])).'\'')) === false) {
					output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
				}

				if ((int) substr($date_lastactivity, 0, 4) === $this->datetime['year']) {
					$this->estimate = true;
				}
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
		if (($result = $this->sqlite3->querySingle('SELECT MIN(date) AS date, l_total FROM channel_activity WHERE l_total = (SELECT MAX(l_total) FROM channel_activity)', true)) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
		}

		$date_l_max = $result['date'];
		$l_max = $result['l_total'];
		$html = '<!DOCTYPE html>'."\n\n"
			. '<html>'."\n\n"
			. '<head>'."\n"
			. '<meta charset="utf-8">'."\n"
			. '<title>'.htmlspecialchars($this->channel, ENT_QUOTES | ENT_HTML5, 'UTF-8').', seriously.</title>'."\n"
			. '<link rel="stylesheet" href="'.$this->stylesheet.'">'."\n"
			. '<meta name="referrer" content="no-referrer">'."\n"
			. '<style type="text/css">'."\n"
			. '  .act-year { width:'.(2 + ($this->columns_act_year * 34)).'px }'."\n"
			. '</style>'."\n"
			. '</head>'."\n\n"
			. '<body><div id="container">'."\n"
			. '<div class="info">'.($this->search_user ? '<form action="user.php"><input type="hidden" name="cid" value="'.htmlspecialchars(rawurlencode($this->cid), ENT_QUOTES | ENT_HTML5, 'UTF-8').'"><input type="text" name="nick" placeholder="Search User.."></form>' : '').htmlspecialchars($this->channel, ENT_QUOTES | ENT_HTML5, 'UTF-8').', seriously.<br><br>'
			. number_format($dayslogged).' day'.($dayslogged > 1 ? 's logged from '.date('M j, Y', strtotime($date_first)).' to '.date('M j, Y', strtotime($date_last)) : ' logged on '.date('M j, Y', strtotime($date_first))).'.<br><br>'
			. 'Logs contain '.number_format($this->l_total).' line'.($this->l_total > 1 ? 's' : '').' &ndash; an average of '.number_format($l_avg).' line'.($l_avg !== 1 ? 's' : '').' per day.<br>'
			. 'Most active day was '.date('M j, Y', strtotime($date_l_max)).' with a total of '.number_format($l_max).' line'.($l_max > 1 ? 's' : '').' typed.</div>'."\n";

		/**
		 * Activity section.
		 */
		if ($this->sectionbits & 1) {
			$html .= '<div class="section">Activity</div>'."\n";
			$html .= $this->make_table_activity_distribution_hour();
			$html .= $this->make_table_activity('day');
			$html .= $this->make_table_activity('month');
			$html .= $this->make_table_activity('year');
			$html .= $this->make_table_activity_distribution_day();
			$html .= $this->make_table_people('alltime');
			$html .= $this->make_table_people2();

			/**
			 * In January, don't display the year table if it's identical to the month one.
			 */
			if ($this->datetime['month'] !== 1 || ($this->datetime['month'] === 1 && $this->maxrows_people_year !== $this->maxrows_people_month)) {
				$html .= $this->make_table_people('year');
			}

			$html .= $this->make_table_people('month');
			$html .= $this->make_table_people_timeofday();
		}

		/**
		 * General Chat section.
		 */
		$section = '';
		$section .= $this->create_table('Most Talkative Chatters', ['Lines/Day', 'User'], ['num1', 'str'], ['SELECT CAST(l_total AS REAL) / activedays AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND activedays >= 7 AND lasttalked >= \''.date('Y-m-d 00:00:00', mktime(0, 0, 0, $this->datetime['month'], $this->datetime['dayofmonth'] - 30, $this->datetime['year'])).'\' ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Most Fluent Chatters', ['Words/Line', 'User'], ['num1', 'str'], ['SELECT CAST(words AS REAL) / l_total AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND activedays >= 7 AND lasttalked >= \''.date('Y-m-d 00:00:00', mktime(0, 0, 0, $this->datetime['month'], $this->datetime['dayofmonth'] - 30, $this->datetime['year'])).'\' ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Most Tedious Chatters', ['Chars/Line', 'User'], ['num1', 'str'], ['SELECT CAST(characters AS REAL) / l_total AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND activedays >= 7 AND lasttalked >= \''.date('Y-m-d 00:00:00', mktime(0, 0, 0, $this->datetime['month'], $this->datetime['dayofmonth'] - 30, $this->datetime['year'])).'\' ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Individual Top Days &ndash; All-Time', ['Lines', 'User'], ['num', 'str'], ['SELECT MAX(l_total) AS v1, csnick AS v2 FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) GROUP BY ruid_activity_by_day.ruid ORDER BY v1 DESC, ruid_activity_by_day.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Individual Top Days &ndash; '.$this->datetime['year'], ['Lines', 'User'], ['num', 'str'], ['SELECT MAX(l_total) AS v1, csnick AS v2 FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date LIKE \''.$this->datetime['year'].'%\' GROUP BY ruid_activity_by_day.ruid ORDER BY v1 DESC, ruid_activity_by_day.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Individual Top Days &ndash; '.$this->datetime['monthname'].' '.$this->datetime['year'], ['Lines', 'User'], ['num', 'str'], ['SELECT MAX(l_total) AS v1, csnick AS v2 FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date LIKE \''.date('Y-m', strtotime($date_lastlogparsed)).'%\' GROUP BY ruid_activity_by_day.ruid ORDER BY v1 DESC, ruid_activity_by_day.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Most Active Chatters &ndash; All-Time', ['Activity', 'User'], ['num2-perc', 'str'], ['SELECT (CAST(activedays AS REAL) / '.$dayslogged.') * 100 AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND activedays != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Most Active Chatters &ndash; '.$this->datetime['year'], ['Activity', 'User'], ['num2-perc', 'str'], ['SELECT (CAST(COUNT(DISTINCT date) AS REAL) / (SELECT COUNT(*) FROM parse_history WHERE date LIKE \''.$this->datetime['year'].'%\')) * 100 AS v1, csnick AS v2 FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date LIKE \''.$this->datetime['year'].'%\' GROUP BY ruid_activity_by_day.ruid ORDER BY v1 DESC, ruid_activity_by_day.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Most Active Chatters &ndash; '.$this->datetime['monthname'].' '.$this->datetime['year'], ['Activity', 'User'], ['num2-perc', 'str'], ['SELECT (CAST(COUNT(DISTINCT date) AS REAL) / (SELECT COUNT(*) FROM parse_history WHERE date LIKE \''.date('Y-m', strtotime($date_lastlogparsed)).'%\')) * 100 AS v1, csnick AS v2 FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date LIKE \''.date('Y-m', strtotime($date_lastlogparsed)).'%\' GROUP BY ruid_activity_by_day.ruid ORDER BY v1 DESC, ruid_activity_by_day.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Exclamations', ['Total', 'User', 'Example'], ['num', 'str', 'str'], ['SELECT exclamations AS v1, csnick AS v2, ex_exclamations AS v3 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND exclamations != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(exclamations) FROM ruid_lines']);
		$section .= $this->create_table('Questions', ['Total', 'User', 'Example'], ['num', 'str', 'str'], ['SELECT questions AS v1, csnick AS v2, ex_questions AS v3 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND questions != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(questions) FROM ruid_lines']);
		$section .= $this->create_table('UPPERCASED Lines', ['Total', 'User', 'Example'], ['num', 'str', 'str'], ['SELECT uppercased AS v1, csnick AS v2, ex_uppercased AS v3 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND uppercased != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(uppercased) FROM ruid_lines']);
		$section .= $this->create_table('Monologues', ['Total', 'User'], ['num', 'str'], ['SELECT monologues AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND monologues != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(monologues) FROM ruid_lines']);
		$section .= $this->create_table('Longest Monologue', ['Lines', 'User'], ['num', 'str'], ['SELECT topmonologue AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND topmonologue != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Moodiest People', ['Smileys', 'User'], ['num', 'str'], ['SELECT s_01 + s_02 + s_03 + s_04 + s_05 + s_06 + s_07 + s_08 + s_09 + s_10 + s_11 + s_12 + s_13 + s_14 + s_15 + s_16 + s_17 + s_18 + s_19 + s_20 + s_21 + s_22 + s_23 + s_24 + s_25 + s_26 + s_27 + s_28 + s_29 + s_30 + s_31 + s_32 + s_33 + s_34 + s_35 + s_36 + s_37 + s_47 AS v1, csnick AS v2 FROM ruid_smileys JOIN uid_details ON ruid_smileys.ruid = uid_details.uid WHERE status NOT IN (3,4) ORDER BY v1 DESC, ruid_smileys.ruid ASC LIMIT 5', 'SELECT SUM(s_01) + SUM(s_02) + SUM(s_03) + SUM(s_04) + SUM(s_05) + SUM(s_06) + SUM(s_07) + SUM(s_08) + SUM(s_09) + SUM(s_10) + SUM(s_11) + SUM(s_12) + SUM(s_13) + SUM(s_14) + SUM(s_15) + SUM(s_16) + SUM(s_17) + SUM(s_18) + SUM(s_19) + SUM(s_20) + SUM(s_21) + SUM(s_22) + SUM(s_23) + SUM(s_24) + SUM(s_25) + SUM(s_26) + SUM(s_27) + SUM(s_28) + SUM(s_29) + SUM(s_30) + SUM(s_31) + SUM(s_32) + SUM(s_33) + SUM(s_34) + SUM(s_35) + SUM(s_36) + SUM(s_37) + SUM(s_47) FROM ruid_smileys']);
		$section .= $this->create_table('Slaps Given', ['Total', 'User'], ['num', 'str'], ['SELECT slaps AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND slaps != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(slaps) FROM ruid_lines']);
		$section .= $this->create_table('Slaps Received', ['Total', 'User'], ['num', 'str'], ['SELECT slapped AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND slapped != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(slapped) FROM ruid_lines']);
		$section .= $this->create_table('Most Lively Bots', ['Lines', 'Bot'], ['num', ($this->user_stats ? 'str-userstats' : 'str')], ['SELECT l_total AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status = 3 AND l_total != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Actions Performed', ['Total', 'User', 'Example'], ['num', 'str', 'str'], ['SELECT actions AS v1, csnick AS v2, ex_actions AS v3 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND actions != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(actions) FROM ruid_lines']);

		if ($section !== '') {
			$html .= '<div class="section">General Chat</div>'."\n".$section;
		}

		/**
		 * Modes section.
		 */
		$section = '';
		$modes = [
			'm_op' => 'Ops \'+o\' Given',
			'm_opped' => 'Ops \'+o\' Received',
			'm_deop' => 'deOps \'-o\' Given',
			'm_deopped' => 'deOps \'-o\' Received',
			'm_voice' => 'Voices \'+v\' Given',
			'm_voiced' => 'Voices \'+v\' Received',
			'm_devoice' => 'deVoices \'-v\' Given',
			'm_devoiced' => 'deVoices \'-v\' Received'];

		foreach ($modes as $mode => $title) {
			$section .= $this->create_table($title, ['Total', 'User'], ['num', 'str'], ['SELECT '.$mode.' AS v1, csnick AS v2 FROM ruid_events JOIN uid_details ON ruid_events.ruid = uid_details.uid WHERE status NOT IN (3,4) AND '.$mode.' != 0 ORDER BY v1 DESC, ruid_events.ruid ASC LIMIT 5', 'SELECT SUM('.$mode.') FROM ruid_events']);
		}

		if ($section !== '') {
			$html .= '<div class="section">Modes</div>'."\n".$section;
		}

		/**
		 * Events section.
		 */
		$section = '';
		$section .= $this->create_table('Kicks Given', ['Total', 'User', 'Example'], ['num', 'str', 'str'], ['SELECT kicks AS v1, csnick AS v2, ex_kicks AS v3 FROM ruid_events JOIN uid_details ON ruid_events.ruid = uid_details.uid WHERE status NOT IN (3,4) AND kicks != 0 ORDER BY v1 DESC, ruid_events.ruid ASC LIMIT 5', 'SELECT SUM(kicks) FROM ruid_events']);
		$section .= $this->create_table('Kicks Received', ['Total', 'User', 'Example'], ['num', 'str', 'str'], ['SELECT kicked AS v1, csnick AS v2, ex_kicked AS v3 FROM ruid_events JOIN uid_details ON ruid_events.ruid = uid_details.uid WHERE status NOT IN (3,4) AND kicked != 0 ORDER BY v1 DESC, ruid_events.ruid ASC LIMIT 5', 'SELECT SUM(kicked) FROM ruid_events']);
		$section .= $this->create_table('Channel Joins', ['Total', 'User'], ['num', 'str'], ['SELECT joins AS v1, csnick AS v2 FROM ruid_events JOIN uid_details ON ruid_events.ruid = uid_details.uid WHERE status NOT IN (3,4) AND joins != 0 ORDER BY v1 DESC, ruid_events.ruid ASC LIMIT 5', 'SELECT SUM(joins) FROM ruid_events']);
		$section .= $this->create_table('Channel Parts', ['Total', 'User'], ['num', 'str'], ['SELECT parts AS v1, csnick AS v2 FROM ruid_events JOIN uid_details ON ruid_events.ruid = uid_details.uid WHERE status NOT IN (3,4) AND parts != 0 ORDER BY v1 DESC, ruid_events.ruid ASC LIMIT 5', 'SELECT SUM(parts) FROM ruid_events']);
		$section .= $this->create_table('IRC Quits', ['Total', 'User'], ['num', 'str'], ['SELECT quits AS v1, csnick AS v2 FROM ruid_events JOIN uid_details ON ruid_events.ruid = uid_details.uid WHERE status NOT IN (3,4) AND quits != 0 ORDER BY v1 DESC, ruid_events.ruid ASC LIMIT 5', 'SELECT SUM(quits) FROM ruid_events']);
		$section .= $this->create_table('Nick Changes', ['Total', 'User'], ['num', 'str'], ['SELECT nickchanges AS v1, csnick AS v2 FROM ruid_events JOIN uid_details ON ruid_events.ruid = uid_details.uid WHERE status NOT IN (3,4) AND nickchanges != 0 ORDER BY v1 DESC, ruid_events.ruid ASC LIMIT 5', 'SELECT SUM(nickchanges) FROM ruid_events']);
		$section .= $this->create_table('Aliases', ['Total', 'User'], ['num', 'str'], ['SELECT COUNT(*) - 1 AS v1, (SELECT csnick FROM uid_details WHERE uid = t1.ruid) AS v2 FROM uid_details AS t1 WHERE ruid IN (SELECT ruid FROM uid_details WHERE status = 1) GROUP BY ruid HAVING v1 > 0 ORDER BY v1 DESC, ruid ASC LIMIT 5', 'SELECT COUNT(*) FROM uid_details WHERE status = 2']);
		$section .= $this->create_table('Topics Set', ['Total', 'User'], ['num', 'str'], ['SELECT topics AS v1, csnick AS v2 FROM ruid_events JOIN uid_details ON ruid_events.ruid = uid_details.uid WHERE status NOT IN (3,4) AND topics != 0 ORDER BY v1 DESC, ruid_events.ruid ASC LIMIT 5', 'SELECT SUM(topics) FROM ruid_events']);
		$section .= $this->create_table('Most Recent Topics', ['Date', 'User', 'Topic'], ['date', 'str', 'str-url'], ['SELECT datetime AS v1, (SELECT csnick FROM uid_details WHERE uid = (SELECT ruid FROM uid_details WHERE uid = uid_topics.uid)) AS v2, topic AS v3 FROM uid_topics JOIN topics ON uid_topics.tid = topics.tid WHERE uid NOT IN (SELECT uid FROM uid_details WHERE ruid IN (SELECT ruid FROM uid_details WHERE status = 4)) ORDER BY v1 DESC LIMIT 5']);

		if ($section !== '') {
			$html .= '<div class="section">Events</div>'."\n".$section;
		}

		/**
		 * URLs section.
		 */
		$section = '';
		$section .= $this->create_table('Most Referenced Domain Names', ['Total', 'Domain', 'First Used'], ['num', 'url', 'date'], ['SELECT COUNT(*) AS v1, \'http://\' || fqdn AS v2, MIN(datetime) AS v3 FROM uid_urls JOIN urls ON uid_urls.lid = urls.lid JOIN fqdns ON urls.fid = fqdns.fid GROUP BY urls.fid ORDER BY v1 DESC, v3 ASC LIMIT 10'], 10);
		$section .= $this->create_table('Most Referenced TLDs', ['Total', 'TLD'], ['num', 'str'], ['SELECT COUNT(*) AS v1, \'.\' || tld AS v2 FROM uid_urls JOIN urls ON uid_urls.lid = urls.lid JOIN fqdns ON urls.fid = fqdns.fid GROUP BY tld ORDER BY v1 DESC, v2 ASC LIMIT 10'], 10);
		$section .= $this->create_table('Most Recent URLs', ['Date', 'User', 'URL'], ['date-norepeat', 'str', 'url'], ['SELECT uid_urls.datetime AS v1, (SELECT csnick FROM uid_details WHERE uid = (SELECT ruid FROM uid_details WHERE uid = uid_urls.uid)) AS v2, url AS v3 FROM uid_urls JOIN (SELECT MAX(datetime) AS datetime, lid FROM uid_urls WHERE uid NOT IN (SELECT uid FROM uid_details WHERE ruid IN (SELECT ruid FROM uid_details WHERE status IN (3,4))) GROUP BY lid) AS t1 ON uid_urls.datetime = t1.datetime AND uid_urls.lid = t1.lid, urls ON uid_urls.lid = urls.lid ORDER BY v1 DESC LIMIT 30'], 30);
		$section .= $this->create_table('URLs by Users', ['Total', 'User'], ['num', 'str'], ['SELECT urls AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND urls != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(urls) FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status != 3']);
		$section .= $this->create_table('URLs by Bots', ['Total', 'Bot'], ['num', 'str'], ['SELECT urls AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status = 3 AND urls != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(urls) FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status = 3']);

		if ($section !== '') {
			$html .= '<div class="section">URLs</div>'."\n".$section;
		}

		/**
		 * Words section.
		 */
		$section = '';
		$query = $this->sqlite3->query('SELECT * FROM (SELECT length, COUNT(*) AS total FROM words GROUP BY length ORDER BY total DESC, length DESC LIMIT 9) ORDER BY length ASC') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$section .= $this->create_table('Words of '.$result['length'].' Characters', ['Times Used', 'Word'], ['num', 'str'], ['SELECT total AS v1, word AS v2 FROM words WHERE length = '.$result['length'].' ORDER BY v1 DESC, v2 ASC LIMIT 5', $result['total']]);
		}

		if ($section !== '') {
			$html .= '<div class="section">Words</div>'."\n".$section;
		}

		/**
		 * Milestones section.
		 */
		if ($this->sectionbits & 128) {
			/*
			$output = '';
			$query = $this->sqlite3->query('SELECT milestone, COUNT(*) AS total FROM ruid_milestones GROUP BY milestone ORDER BY milestone ASC') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());

			while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
				$t = new table(number_format($result['milestone']).' Lines Milestone', 1, $this->maxrows);
				$t->set_value('keys', [
					'k1' => 'Date',
					'k2' => 'User',
					'v1' => 'date',
					'v2' => 'string']);
				$t->set_value('queries', ['main' => 'SELECT date AS v1, csnick AS v2 FROM ruid_milestones JOIN uid_details ON ruid_milestones.ruid = uid_details.uid WHERE milestone = '.$result['milestone'].' ORDER BY v1 ASC, ruid_milestones.ruid ASC LIMIT '.$this->maxrows]);
				$t->set_value('total', $result['total']);
				$output .= $t->make_table($this->sqlite3);
			}

			if ($output !== '') {
				$html .= '<div class="section">Milestones</div>'."\n".$output;
			}
			*/
		}

		/**
		 * HTML Foot.
		 */
		$html .= '<div class="info">Statistics created with <a href="http://sss.dutnie.nl">superseriousstats</a> on '.date('r').'.</div>'."\n";
		$html .= '</div></body>'."\n\n".'</html>'."\n";
		return $html;
	}

	private function create_table(string $title, array $keys, array $types, array $queries, int $rows = 5): ?string
	{
		/**
		 * Amount of columns the table will have.
		 */
		$cols = count($keys);

		/**
		 * Retrieve the total for the data set.
		 */
		if (!empty($queries[1])) {
			if (is_int($queries[1])) {
				$total = $queries[1];
			} elseif (($total = $this->sqlite3->querySingle($queries[1])) === false) {
				output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
			}

			/**
			 * Check with empty() here because the returned value comes from an SQL
			 * aggregate function which can be null as well as 0.
			 */
			if (empty($total)) {
				return null;
			}
		}

		$table = '<table class="'.($title === 'Most Referenced Domain Names' ? 'medium' : ($cols === 3 ? 'large' : 'small')).'">';
		$table .= '<colgroup><col class="c1"><col class="pos"><col class="c2">'.($cols === 3 ? '<col class="c3">' : '');
		$table .= '<tr><th colspan="'.($cols + 1).'">'.(isset($total) ? '<span class="title">'.$title.'</span><span class="title-right">'.number_format($total).' Total</span>' : $title);
		$table .= '<tr><td class="k1">'.$keys[0].'<td class="pos"><td class="k2">'.$keys[1].($cols === 3 ? '<td class="k3">'.$keys[2] : '');

		/**
		 * Retrieve the main data set.
		 */
		$row = 0;
		$query = $this->sqlite3->query($queries[0]) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			if (++$row > $rows) {
				break;
			}

			for ($col = 1; $col <= $cols; ++$col) {
				${'v'.$col} = $result['v'.$col];
				$type = $types[$col - 1];

				switch ($type) {
					case 'str':
						${'v'.$col} = htmlspecialchars(${'v'.$col}, ENT_QUOTES | ENT_HTML5, 'UTF-8');
						break;
					case 'str-url':
						$words = explode(' ', ${'v'.$col});
						$line = '';

						foreach ($words as $word) {
							if (preg_match('/^(www\.|https?:\/\/).+/i', $word) && ($url_components = url_tools::get_components($word)) !== false) {
								$line .= '<a href="'.htmlspecialchars($url_components['url'], ENT_QUOTES | ENT_HTML5, 'UTF-8').'">'.htmlspecialchars($url_components['url'], ENT_QUOTES | ENT_HTML5, 'UTF-8').'</a> ';
							} else {
								$line .= htmlspecialchars($word, ENT_QUOTES | ENT_HTML5, 'UTF-8').' ';
							}
						}

						${'v'.$col} = rtrim($line);
						break;
					case 'str-userstats':
						${'v'.$col} = '<a href="user.php?cid='.htmlspecialchars(rawurlencode($this->cid), ENT_QUOTES | ENT_HTML5, 'UTF-8').'&amp;nick='.htmlspecialchars(rawurlencode(${'v'.$col}), ENT_QUOTES | ENT_HTML5, 'UTF-8').'">'.htmlspecialchars(${'v'.$col}, ENT_QUOTES | ENT_HTML5, 'UTF-8').'</a>';
						break;
					case 'date':
						${'v'.$col} = date('j M \'y', strtotime(${'v'.$col}));
						break;
					case 'date-norepeat':
						${'v'.$col} = date('j M \'y', strtotime(${'v'.$col}));

						if (isset($date_prev) && ${'v'.$col} === $date_prev) {
							${'v'.$col} = '';
						} else {
							$date_prev = ${'v'.$col};
						}

						break;
					case 'url':
						${'v'.$col} = '<a href="'.htmlspecialchars(${'v'.$col}, ENT_QUOTES | ENT_HTML5, 'UTF-8').'">'.htmlspecialchars(${'v'.$col}, ENT_QUOTES | ENT_HTML5, 'UTF-8').'</a>';
						break;
					default:
						preg_match('/^num(?<decimals>[0-9])?(?<percentage>-perc)?$/', $type, $matches, PREG_UNMATCHED_AS_NULL);

						if (!is_null($matches['decimals'])) {
							$decimals = (int) $matches['decimals'];
						} else {
							$decimals = 0;
						}

						if (!is_null($matches['percentage'])) {
							$percentage = true;
						} else {
							$percentage = false;
						}

						${'v'.$col} = number_format(${'v'.$col}, $decimals).($percentage ? '%' : '');
				}
			}

			$table .= '<tr><td class="v1">'.$v1.'<td class="pos">'.$row.'<td class="v2">'.$v2.($cols === 3 ? '<td class="'.($types[2] === 'str-url' ? 'v3a' : 'v3').'">'.$v3 : '');
		}

		if ($row === 0) {
			return null;
		} elseif ($row < $rows && $title !== 'Most Recent URLs') {
			for (; $row < $rows; ++$row) {
				$table .= '<tr><td class="v1"><td class="pos">&nbsp;<td class="v2">'.($cols === 3 ? '<td class="v3">' : '');
			}
		}

		$table .= '</table>'."\n";
		return $table;
	}

	private function make_table_activity($type)
	{
		if ($type === 'day') {
			$class = 'act';
			$columns = 24;
			$head = 'Activity by Day';
			$query = $this->sqlite3->query('SELECT date, l_total, l_night, l_morning, l_afternoon, l_evening FROM channel_activity WHERE date > \''.date('Y-m-d', mktime(0, 0, 0, $this->datetime['month'], $this->datetime['dayofmonth'] - 24, $this->datetime['year'])).'\'') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());

			for ($i = $columns - 1; $i >= 0; --$i) {
				$dates[] = date('Y-m-d', mktime(0, 0, 0, $this->datetime['month'], $this->datetime['dayofmonth'] - $i, $this->datetime['year']));
			}
		} elseif ($type === 'month') {
			$class = 'act';
			$columns = 24;
			$head = 'Activity by Month';
			$query = $this->sqlite3->query('SELECT SUBSTR(date, 1, 7) AS date, SUM(l_total) AS l_total, SUM(l_night) AS l_night, SUM(l_morning) AS l_morning, SUM(l_afternoon) AS l_afternoon, SUM(l_evening) AS l_evening FROM channel_activity WHERE SUBSTR(date, 1, 7) > \''.date('Y-m', mktime(0, 0, 0, $this->datetime['month'] - 24, 1, $this->datetime['year'])).'\' GROUP BY SUBSTR(date, 1, 7)') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());

			for ($i = $columns - 1; $i >= 0; --$i) {
				$dates[] = date('Y-m', mktime(0, 0, 0, $this->datetime['month'] - $i, 1, $this->datetime['year']));
			}
		} elseif ($type === 'year') {
			$class = 'act-year';
			$columns = $this->columns_act_year;
			$head = 'Activity by Year';
			$query = $this->sqlite3->query('SELECT SUBSTR(date, 1, 4) AS date, SUM(l_total) AS l_total, SUM(l_night) AS l_night, SUM(l_morning) AS l_morning, SUM(l_afternoon) AS l_afternoon, SUM(l_evening) AS l_evening FROM channel_activity WHERE SUBSTR(date, 1, 4) > \''.($this->datetime['year'] - 24).'\' GROUP BY SUBSTR(date, 1, 4)') or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());

			for ($i = $columns - ($this->estimate ? 1 : 0) - 1; $i >= 0; --$i) {
				$dates[] = $this->datetime['year'] - $i;
			}

			if ($this->estimate) {
				$dates[] = 'estimate';
			}
		}

		if (($result = $query->fetchArray(SQLITE3_ASSOC)) === false) {
			return;
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

		if ($type === 'year' && $this->estimate) {
			if (($result = $this->sqlite3->querySingle('SELECT CAST(SUM(l_night) AS REAL) / 90 AS l_night_avg, CAST(SUM(l_morning) AS REAL) / 90 AS l_morning_avg, CAST(SUM(l_afternoon) AS REAL) / 90 AS l_afternoon_avg, CAST(SUM(l_evening) AS REAL) / 90 AS l_evening_avg FROM channel_activity WHERE date > \''.date('Y-m-d', mktime(0, 0, 0, $this->datetime['month'], $this->datetime['dayofmonth'] - 90, $this->datetime['year'])).'\'', true)) === false) {
				output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
			}

			$l_afternoon['estimate'] = $l_afternoon[$this->datetime['year']] + round($result['l_afternoon_avg'] * $this->datetime['daysleft']);
			$l_evening['estimate'] = $l_evening[$this->datetime['year']] + round($result['l_evening_avg'] * $this->datetime['daysleft']);
			$l_morning['estimate'] = $l_morning[$this->datetime['year']] + round($result['l_morning_avg'] * $this->datetime['daysleft']);
			$l_night['estimate'] = $l_night[$this->datetime['year']] + round($result['l_night_avg'] * $this->datetime['daysleft']);
			$l_total['estimate'] = $l_afternoon['estimate'] + $l_evening['estimate'] + $l_morning['estimate'] + $l_night['estimate'];

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

				$height_int['total'] = (int) round(($l_total[$date] / $high_value) * 100);
				$height = $height_int['total'];

				foreach ($times as $time) {
					if (${'l_'.$time}[$date] !== 0) {
						$height_float[$time] = (float) (${'l_'.$time}[$date] / $high_value) * 100;
						$height_int[$time] = (int) floor($height_float[$time]);
						$height_remainders[$time] = $height_float[$time] - $height_int[$time];
						$height -= $height_int[$time];
					} else {
						$height_int[$time] = 0;
					}
				}

				if ($height !== 0) {
					arsort($height_remainders);

					foreach ($height_remainders as $time => $remainder) {
						--$height;
						++$height_int[$time];

						if ($height === 0) {
							break;
						}
					}
				}

				$tr2 .= '<td'.($date === 'estimate' ? ' class="est"' : '').'><ul><li class="num" style="height:'.($height_int['total'] + 14).'px">'.$total;

				foreach ($times as $time) {
					if ($height_int[$time] !== 0) {
						if ($time === 'evening') {
							$height_li = $height_int['night'] + $height_int['morning'] + $height_int['afternoon'] + $height_int['evening'];
						} elseif ($time === 'afternoon') {
							$height_li = $height_int['night'] + $height_int['morning'] + $height_int['afternoon'];
						} elseif ($time === 'morning') {
							$height_li = $height_int['night'] + $height_int['morning'];
						} elseif ($time === 'night') {
							$height_li = $height_int['night'];
						}

						$tr2 .= '<li class="'.$this->color[$time].'" style="height:'.$height_li.'px">';
					}
				}

				$tr2 .= '</ul>';

				/**
				 * It's important to unset $height_remainders so the next iteration won't try to
				 * work with old values.
				 */
				unset($height_remainders);
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

	private function make_table_activity_distribution_day()
	{
		if (($result = $this->sqlite3->querySingle('SELECT SUM(l_mon_night) AS l_mon_night, SUM(l_mon_morning) AS l_mon_morning, SUM(l_mon_afternoon) AS l_mon_afternoon, SUM(l_mon_evening) AS l_mon_evening, SUM(l_tue_night) AS l_tue_night, SUM(l_tue_morning) AS l_tue_morning, SUM(l_tue_afternoon) AS l_tue_afternoon, SUM(l_tue_evening) AS l_tue_evening, SUM(l_wed_night) AS l_wed_night, SUM(l_wed_morning) AS l_wed_morning, SUM(l_wed_afternoon) AS l_wed_afternoon, SUM(l_wed_evening) AS l_wed_evening, SUM(l_thu_night) AS l_thu_night, SUM(l_thu_morning) AS l_thu_morning, SUM(l_thu_afternoon) AS l_thu_afternoon, SUM(l_thu_evening) AS l_thu_evening, SUM(l_fri_night) AS l_fri_night, SUM(l_fri_morning) AS l_fri_morning, SUM(l_fri_afternoon) AS l_fri_afternoon, SUM(l_fri_evening) AS l_fri_evening, SUM(l_sat_night) AS l_sat_night, SUM(l_sat_morning) AS l_sat_morning, SUM(l_sat_afternoon) AS l_sat_afternoon, SUM(l_sat_evening) AS l_sat_evening, SUM(l_sun_night) AS l_sun_night, SUM(l_sun_morning) AS l_sun_morning, SUM(l_sun_afternoon) AS l_sun_afternoon, SUM(l_sun_evening) AS l_sun_evening FROM ruid_lines', true)) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
		}

		$days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
		$high_day = '';
		$high_value = 0;

		foreach ($days as $day) {
			$l_afternoon[$day] = $result['l_'.$day.'_afternoon'];
			$l_evening[$day] = $result['l_'.$day.'_evening'];
			$l_morning[$day] = $result['l_'.$day.'_morning'];
			$l_night[$day] = $result['l_'.$day.'_night'];
			$l_total[$day] = $l_afternoon[$day] + $l_evening[$day] + $l_morning[$day] + $l_night[$day];

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

				$height_int['total'] = (int) round(($l_total[$day] / $high_value) * 100);
				$height = $height_int['total'];

				foreach ($times as $time) {
					if (${'l_'.$time}[$day] !== 0) {
						$height_float[$time] = (float) (${'l_'.$time}[$day] / $high_value) * 100;
						$height_int[$time] = (int) floor($height_float[$time]);
						$height_remainders[$time] = $height_float[$time] - $height_int[$time];
						$height -= $height_int[$time];
					} else {
						$height_int[$time] = 0;
					}
				}

				if ($height !== 0) {
					arsort($height_remainders);

					foreach ($height_remainders as $time => $remainder) {
						--$height;
						++$height_int[$time];

						if ($height === 0) {
							break;
						}
					}
				}

				$tr2 .= '<td><ul><li class="num" style="height:'.($height_int['total'] + 14).'px">'.$percentage;

				foreach ($times as $time) {
					if ($height_int[$time] !== 0) {
						if ($time === 'evening') {
							$height_li = $height_int['night'] + $height_int['morning'] + $height_int['afternoon'] + $height_int['evening'];
						} elseif ($time === 'afternoon') {
							$height_li = $height_int['night'] + $height_int['morning'] + $height_int['afternoon'];
						} elseif ($time === 'morning') {
							$height_li = $height_int['night'] + $height_int['morning'];
						} elseif ($time === 'night') {
							$height_li = $height_int['night'];
						}

						$tr2 .= '<li class="'.$this->color[$time].'" style="height:'.$height_li.'px" title="'.number_format($l_total[$day]).'">';
					}
				}

				$tr2 .= '</ul>';

				/**
				 * It's important to unset $height_remainders so the next iteration won't try to
				 * work with old values.
				 */
				unset($height_remainders);
			}

			$tr3 .= '<td'.($day === $high_day ? ' class="bold"' : '').'>'.ucfirst($day);
		}

		return '<table class="act-day">'.$tr1.$tr2.$tr3.'</table>'."\n";
	}

	private function make_table_activity_distribution_hour()
	{
		if (($result = $this->sqlite3->querySingle('SELECT SUM(l_00) AS l_00, SUM(l_01) AS l_01, SUM(l_02) AS l_02, SUM(l_03) AS l_03, SUM(l_04) AS l_04, SUM(l_05) AS l_05, SUM(l_06) AS l_06, SUM(l_07) AS l_07, SUM(l_08) AS l_08, SUM(l_09) AS l_09, SUM(l_10) AS l_10, SUM(l_11) AS l_11, SUM(l_12) AS l_12, SUM(l_13) AS l_13, SUM(l_14) AS l_14, SUM(l_15) AS l_15, SUM(l_16) AS l_16, SUM(l_17) AS l_17, SUM(l_18) AS l_18, SUM(l_19) AS l_19, SUM(l_20) AS l_20, SUM(l_21) AS l_21, SUM(l_22) AS l_22, SUM(l_23) AS l_23 FROM channel_activity', true)) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
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

	private function make_table_people($type)
	{
		/**
		 * Only create the table if there is activity from users other than bots and
		 * excluded users.
		 */
		if ($type === 'alltime') {
			if (($total = $this->sqlite3->querySingle('SELECT SUM(l_total) FROM ruid_activity_by_year JOIN uid_details ON ruid_activity_by_year.ruid = uid_details.uid WHERE status NOT IN (3,4)')) === false) {
				output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
			}
		} elseif ($type === 'month') {
			if (($total = $this->sqlite3->querySingle('SELECT SUM(l_total) FROM ruid_activity_by_month JOIN uid_details ON ruid_activity_by_month.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date = \''.date('Y-m', mktime(0, 0, 0, $this->datetime['month'], 1, $this->datetime['year'])).'\'')) === false) {
				output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
			}
		} elseif ($type === 'year') {
			if (($total = $this->sqlite3->querySingle('SELECT SUM(l_total) FROM ruid_activity_by_year JOIN uid_details ON ruid_activity_by_year.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date = \''.$this->datetime['year'].'\'')) === false) {
				output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
			}
		}

		if (is_null($total)) {
			return;
		}

		if ($type === 'alltime') {
			$head = 'Most Talkative People &ndash; All-Time';
			$historylink = '<a href="history.php?cid='.htmlspecialchars(rawurlencode($this->cid), ENT_QUOTES | ENT_HTML5, 'UTF-8').'">History</a>';

			/**
			 * Don't try to calculate changes in rankings if we're dealing with the first
			 * month of activity.
			 */
			if (!$this->rankings || date('Y-m', mktime(0, 0, 0, $this->datetime['month'], 1, $this->datetime['year'])) === $this->datetime['firstyearmonth']) {
				$query = $this->sqlite3->query('SELECT csnick, l_total, l_night, l_morning, l_afternoon, l_evening, quote, lasttalked FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND l_total != 0 ORDER BY l_total DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows_people_alltime) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
			} else {
				$query = $this->sqlite3->query('SELECT csnick, l_total, l_night, l_morning, l_afternoon, l_evening, quote, lasttalked, (SELECT rank FROM ruid_rankings WHERE ruid = ruid_lines.ruid AND date = \''.date('Y-m', mktime(0, 0, 0, $this->datetime['month'] - 1, 1, $this->datetime['year'])).'\') AS prevrank FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND l_total != 0 ORDER BY l_total DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows_people_alltime) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
			}
		} elseif ($type === 'month') {
			$head = 'Most Talkative People &ndash; '.$this->datetime['monthname'].' '.$this->datetime['year'];
			$historylink = '<a href="history.php?cid='.htmlspecialchars(rawurlencode($this->cid), ENT_QUOTES | ENT_HTML5, 'UTF-8').'&amp;year='.$this->datetime['year'].'&amp;month='.$this->datetime['month'].'">History</a>';
			$query = $this->sqlite3->query('SELECT csnick, ruid_activity_by_month.l_total AS l_total, ruid_activity_by_month.l_night AS l_night, ruid_activity_by_month.l_morning AS l_morning, ruid_activity_by_month.l_afternoon AS l_afternoon, ruid_activity_by_month.l_evening AS l_evening, quote, lasttalked FROM ruid_activity_by_month JOIN uid_details ON ruid_activity_by_month.ruid = uid_details.uid JOIN ruid_lines ON ruid_activity_by_month.ruid = ruid_lines.ruid WHERE status NOT IN (3,4) AND date = \''.date('Y-m', mktime(0, 0, 0, $this->datetime['month'], 1, $this->datetime['year'])).'\' ORDER BY l_total DESC, ruid_activity_by_month.ruid ASC LIMIT '.$this->maxrows_people_month) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
		} elseif ($type === 'year') {
			$head = 'Most Talkative People &ndash; '.$this->datetime['year'];
			$historylink = '<a href="history.php?cid='.htmlspecialchars(rawurlencode($this->cid), ENT_QUOTES | ENT_HTML5, 'UTF-8').'&amp;year='.$this->datetime['year'].'">History</a>';
			$query = $this->sqlite3->query('SELECT csnick, ruid_activity_by_year.l_total AS l_total, ruid_activity_by_year.l_night AS l_night, ruid_activity_by_year.l_morning AS l_morning, ruid_activity_by_year.l_afternoon AS l_afternoon, ruid_activity_by_year.l_evening AS l_evening, quote, lasttalked FROM ruid_activity_by_year JOIN uid_details ON ruid_activity_by_year.ruid = uid_details.uid JOIN ruid_lines ON ruid_activity_by_year.ruid = ruid_lines.ruid WHERE status NOT IN (3,4) AND date = \''.$this->datetime['year'].'\' ORDER BY l_total DESC, ruid_activity_by_year.ruid ASC LIMIT '.$this->maxrows_people_year) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
		}

		$i = 0;
		$times = ['night', 'morning', 'afternoon', 'evening'];
		$tr0 = '<colgroup><col class="c1"><col class="c2"><col class="pos"><col class="c3"><col class="c4"><col class="c5"><col class="c6">';
		$tr1 = '<tr><th colspan="7">'.($this->history ? '<span class="title">'.$head.'</span><span class="title-right">'.$historylink.'</span>' : $head);
		$tr2 = '<tr><td class="k1">Percentage<td class="k2">Lines<td class="pos"><td class="k3">User<td class="k4">When?<td class="k5">Last Talked<td class="k6">Quote';
		$trx = '';

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			++$i;
			$width = 50;

			foreach ($times as $time) {
				if ($result['l_'.$time] !== 0) {
					$width_float[$time] = (float) ($result['l_'.$time] / $result['l_total']) * 50;
					$width_int[$time] = (int) floor($width_float[$time]);
					$width_remainders[$time] = $width_float[$time] - $width_int[$time];
					$width -= $width_int[$time];
				} else {
					$width_int[$time] = 0;
				}
			}

			if ($width !== 0) {
				arsort($width_remainders);

				foreach ($width_remainders as $time => $remainder) {
					--$width;
					++$width_int[$time];

					if ($width === 0) {
						break;
					}
				}
			}

			$when = '';

			foreach ($times as $time) {
				if ($width_int[$time] !== 0) {
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

			$trx .= '<tr><td class="v1">'.number_format(($result['l_total'] / $total) * 100, 2).'%<td class="v2">'.number_format($result['l_total']).'<td class="pos">'.$pos.'<td class="v3">'.($this->user_stats ? '<a href="user.php?cid='.htmlspecialchars(rawurlencode($this->cid), ENT_QUOTES | ENT_HTML5, 'UTF-8').'&amp;nick='.htmlspecialchars(rawurlencode($result['csnick']), ENT_QUOTES | ENT_HTML5, 'UTF-8').'">'.$result['csnick'].'</a>' : $result['csnick']).'<td class="v4"><ul>'.$when.'</ul><td class="v5">'.$this->ago($result['lasttalked']).'<td class="v6">'.htmlspecialchars($result['quote'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

			/**
			 * It's important to unset $width_remainders so the next iteration won't try to
			 * work with old values.
			 */
			unset($width_remainders);
		}

		return '<table class="ppl">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}

	private function make_table_people2()
	{
		/**
		 * Don't try to calculate changes in rankings if we're dealing with the first
		 * month of activity.
		 */
		if (!$this->rankings || date('Y-m', mktime(0, 0, 0, $this->datetime['month'], 1, $this->datetime['year'])) === $this->datetime['firstyearmonth']) {
			$query = $this->sqlite3->query('SELECT csnick, l_total FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND l_total != 0 ORDER BY l_total DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows_people_alltime.', '.($this->maxrows_people2 * 4)) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
		} else {
			$query = $this->sqlite3->query('SELECT csnick, l_total, (SELECT rank FROM ruid_rankings WHERE ruid = ruid_lines.ruid AND date = \''.date('Y-m', mktime(0, 0, 0, $this->datetime['month'] - 1, 1, $this->datetime['year'])).'\') AS prevrank FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND l_total != 0 ORDER BY l_total DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows_people_alltime.', '.($this->maxrows_people2 * 4)) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
		}

		$current_column = 1;
		$current_row = 0;

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			++$current_row;

			if ($current_row > $this->maxrows_people2) {
				++$current_column;
				$current_row = 1;
			}

			$i = $this->maxrows_people_alltime + ($current_column - 1) * $this->maxrows_people2 + $current_row;

			if (!isset($result['prevrank']) || $i === $result['prevrank']) {
				$pos = $i;
			} elseif ($i < $result['prevrank']) {
				$pos = '<span class="green">&#x25B2;'.$i.'</span>';
			} elseif ($i > $result['prevrank']) {
				$pos = '<span class="red">&#x25BC;'.$i.'</span>';
			}

			$columns[$current_column][$current_row] = [
				'csnick' => $result['csnick'],
				'l_total' => $result['l_total'],
				'pos' => $pos];
		}

		if ($current_column < 4 || $current_row < $this->maxrows_people2) {
			return;
		}

		if (($total = $this->sqlite3->querySingle('SELECT COUNT(*) FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4)')) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
		}

		$total -= $this->maxrows_people_alltime + ($this->maxrows_people2 * 4);
		$tr0 = '<colgroup><col class="c1"><col class="pos"><col class="c2"><col class="c1"><col class="pos"><col class="c2"><col class="c1"><col class="pos"><col class="c2"><col class="c1"><col class="pos"><col class="c2">';
		$tr1 = '<tr><th colspan="12">'.($total !== 0 ? '<span class="title">Less Talkative People &ndash; All-Time</span><span class="title-right">'.number_format($total).' People had even less to say..</span>' : 'Less Talkative People &ndash; All-Time');
		$tr2 = '<tr><td class="k1">Lines<td class="pos"><td class="k2">User<td class="k1">Lines<td class="pos"><td class="k2">User<td class="k1">Lines<td class="pos"><td class="k2">User<td class="k1">Lines<td class="pos"><td class="k2">User';
		$trx = '';

		for ($i = 1; $i <= $this->maxrows_people2; ++$i) {
			$trx .= '<tr>';

			for ($j = 1; $j <= 4; ++$j) {
				$trx .= '<td class="v1">'.number_format($columns[$j][$i]['l_total']).'<td class="pos">'.$columns[$j][$i]['pos'].'<td class="v2">'.($this->user_stats ? '<a href="user.php?cid='.htmlspecialchars(rawurlencode($this->cid), ENT_QUOTES | ENT_HTML5, 'UTF-8').'&amp;nick='.htmlspecialchars(rawurlencode($columns[$j][$i]['csnick']), ENT_QUOTES | ENT_HTML5, 'UTF-8').'">'.$columns[$j][$i]['csnick'].'</a>' : $columns[$j][$i]['csnick']);
			}
		}

		return '<table class="ppl2">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}

	private function make_table_people_timeofday()
	{
		/**
		 * Only create the table if there is activity from users other than bots and
		 * excluded users.
		 */
		if (($total = $this->sqlite3->querySingle('SELECT SUM(l_total) FROM ruid_activity_by_year JOIN uid_details ON ruid_activity_by_year.ruid = uid_details.uid WHERE status NOT IN (3,4)')) === false) {
			output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
		}

		if (is_null($total)) {
			return;
		}

		$high_value = 0;
		$times = ['night', 'morning', 'afternoon', 'evening'];

		foreach ($times as $time) {
			$query = $this->sqlite3->query('SELECT csnick, l_'.$time.' FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND l_'.$time.' != 0 ORDER BY l_'.$time.' DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows_people_timeofday) or output::output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$this->sqlite3->lastErrorMsg());
			$i = 0;

			while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
				++$i;
				${$time}[$i] = [
					'csnick' => $result['csnick'],
					'lines' => $result['l_'.$time]];

				if ($result['l_'.$time] > $high_value) {
					$high_value = $result['l_'.$time];
				}
			}
		}

		$tr0 = '<colgroup><col class="pos"><col class="c"><col class="c"><col class="c"><col class="c">';
		$tr1 = '<tr><th colspan="5">Most Talkative People by Time of Day';
		$tr2 = '<tr><td class="pos"><td class="k">Night<br>0h - 5h<td class="k">Morning<br>6h - 11h<td class="k">Afternoon<br>12h - 17h<td class="k">Evening<br>18h - 23h';
		$trx = '';

		for ($i = 1; $i <= $this->maxrows_people_timeofday; ++$i) {
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
						$trx .= '<td class="v">'.${$time}[$i]['csnick'].' - '.number_format(${$time}[$i]['lines']).'<br><div class="'.$this->color[$time].'" style="width:'.$width.'px"></div>';
					} else {
						$trx .= '<td class="v">'.${$time}[$i]['csnick'].' - '.number_format(${$time}[$i]['lines']);
					}
				}
			}
		}

		return '<table class="ppl-tod">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}
}
