<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2020, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Class for creating the main stats page.
 */
class html
{
	use urlparts, common_html_user_history, common_html_user;

	//required
	private string $now; #last_log_parsed
	//

	private int $days_left = 0;
	private int $days_logged = 0;
	private int $l_total = 0;
	private string $channel = '';
	private DateTime $date_first_activity;
	private DateTime $date_last_activity;
	private DateTime $date_last_log_parsed;
	private $columns_act_year = 0;
	private $history = false;
	private $maxrows_people2 = 10;
	private $maxrows_people_alltime = 30;
	private $maxrows_people_month = 10;
	private $maxrows_people_timeofday = 10;
	private $maxrows_people_year = 10;
	private $maxrows_recenturls = 25;
	private $rankings = false;
	private $search_user = false;
	private $stylesheet = 'sss.css';
	private $user_stats = true;

	public function __construct(string $channel)
	{
		$this->channel = $channel;
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
	public function get_contents(): string
	{
		if (is_null($this->l_total = db::query_single_col('SELECT SUM(l_total) FROM channel_activity'))) {
			return '<!DOCTYPE html>'."\n\n".'<html><head><meta charset="utf-8"><title>seriously?</title><link rel="stylesheet" href="sss.css"></head><body><div id="container"><div class="error">There is not enough data to create statistics, yet.</div></div></body></html>'."\n";
		}

		$result = db::query_single_row('SELECT MIN(date) AS date_first_activity, MAX(date) AS date_last_activity FROM channel_activity');
		$this->date_first_activity = date_create($result['date_first_activity']);
		$this->date_last_activity = date_create($result['date_last_activity']);
		$result = db::query_single_row('SELECT COUNT(*) AS days_logged, MAX(date) AS date FROM parse_history');
		$this->date_last_log_parsed = date_create($result['date']);
		$this->now = $this->date_last_log_parsed->format('Y-m-d');
		$this->days_logged = $result['days_logged'];

		/**
		 * Display an additional column in the "Activity by Year" table showing the
		 * estimated line count for the current year (now). The estimate is based
		 * on activity during the last 90 days. The column won't be shown if there
		 * hasn't been any activity in the current year yet, there hasn't been any
		 * activity in the last 90 days, or if it's the last day of the year.
		 */
		if (date('md') !== '1231' && !is_null($days = db::query_single_col('SELECT MIN(CAST(JULIANDAY(\'now\') - JULIANDAY(date) AS INT)) FROM channel_activity WHERE date LIKE \''.date('Y').'%\'')) && $days < 90) {
			$estimate = true;
		}

		/**
		 * Show a minimum of 3 and maximum of 24 columns in the "Activity by Year"
		 * table. In case the data allows for more than 16 columns there won't be any
		 * room for the "Activity Distribution by Day" table to be adjacent to the
		 * right so we pad the "Activity by Year" table up to 24 columns making it look
		 * neat again.
		 */
		$this->columns_act_year = 1 + (int) $this->date_last_log_parsed->format('Y') - (int) $this->date_first_activity->format('Y') + ($estimate ? 1 : 0);
		$this->columns_act_year = ($this->columns_act_year <= 3 ? 3 : ($this->columns_act_year > 16 ? 24 : $this->columns_act_year));

		/**
		 * HTML Head.
		 */
		$result = db::query_single_row('SELECT MIN(date) AS date, l_total FROM channel_activity WHERE l_total = (SELECT MAX(l_total) FROM channel_activity)');
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
			. '.act-year { width:'.(2 + ($this->columns_act_year * 34)).'px }'."\n"
			. '</style>'."\n"
			. '</head>'."\n\n"
			. '<body><div id="container">'."\n"
			. '<div class="info">'.($this->search_user ? '<form action="user.php"><input type="text" name="nick" placeholder="Search User.."></form>' : '').htmlspecialchars($this->channel, ENT_QUOTES | ENT_HTML5, 'UTF-8').', seriously.<br><br>'
			. number_format($this->days_logged).' day'.($this->days_logged > 1 ? 's logged from '.$this->date_first_activity->format('M j, Y').' to '.$this->date_last_activity->format('M j, Y') : ' logged on '.$this->date_first_activity->format('M j, Y')).'.<br><br>'
			. 'Logs contain '.number_format($this->l_total).' line'.($this->l_total > 1 ? 's' : '').' &ndash; an average of '.number_format(intdiv($this->l_total, $this->days_logged)).' line'.(intdiv($this->l_total, $this->days_logged) !== 1 ? 's' : '').' per day.<br>'
			. 'Most active day was '.date('M j, Y', strtotime($date_l_max)).' with a total of '.number_format($l_max).' line'.($l_max > 1 ? 's' : '').' typed.</div>'."\n";

		/**
		 * Activity section.
		 */
		$html .= '<div class="section">Activity</div>'."\n";
		$html .= $this->create_table_activity_distribution_hour();
		$html .= $this->create_table_activity('day');
		$html .= $this->create_table_activity('month');
		$html .= $this->create_table_activity('year', $estimate);
		$html .= $this->create_table_activity_distribution_day();
		$html .= $this->make_table_people('alltime');
		$html .= $this->make_table_people2();

		/**
		 * In January, don't display the year table if it's identical to the month one.
		 */
		if ((int) $this->date_last_log_parsed->format('n') !== 1 || ((int) $this->date_last_log_parsed->format('n') === 1 && $this->maxrows_people_year !== $this->maxrows_people_month)) {
			$html .= $this->make_table_people('year');
		}

		$html .= $this->make_table_people('month');
		$html .= $this->make_table_people_timeofday();

		/**
		 * General Chat section.
		 */
		$section = '';
		$section .= $this->create_table('Most Talkative Chatters', ['Lines/Day', 'User'], ['num1', 'str'], ['SELECT CAST(l_total AS REAL) / activedays AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND activedays >= 7 AND lasttalked >= \''.date('Y-m-d 00:00:00', mktime(0, 0, 0, (int) $this->date_last_log_parsed->format('n'), (int) $this->date_last_log_parsed->format('j') - 30, (int) $this->date_last_log_parsed->format('Y'))).'\' ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Most Fluent Chatters', ['Words/Line', 'User'], ['num1', 'str'], ['SELECT CAST(words AS REAL) / l_total AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND activedays >= 7 AND lasttalked >= \''.date('Y-m-d 00:00:00', mktime(0, 0, 0, (int) $this->date_last_log_parsed->format('n'), (int) $this->date_last_log_parsed->format('j') - 30, (int) $this->date_last_log_parsed->format('Y'))).'\' ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Most Tedious Chatters', ['Chars/Line', 'User'], ['num1', 'str'], ['SELECT CAST(characters AS REAL) / l_total AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND activedays >= 7 AND lasttalked >= \''.date('Y-m-d 00:00:00', mktime(0, 0, 0, (int) $this->date_last_log_parsed->format('n'), (int) $this->date_last_log_parsed->format('j') - 30, (int) $this->date_last_log_parsed->format('Y'))).'\' ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Individual Top Days &ndash; All-Time', ['Lines', 'User'], ['num', 'str'], ['SELECT MAX(l_total) AS v1, csnick AS v2 FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) GROUP BY ruid_activity_by_day.ruid ORDER BY v1 DESC, ruid_activity_by_day.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Individual Top Days &ndash; '.$this->date_last_log_parsed->format('Y'), ['Lines', 'User'], ['num', 'str'], ['SELECT MAX(l_total) AS v1, csnick AS v2 FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date LIKE \''.$this->date_last_log_parsed->format('Y').'%\' GROUP BY ruid_activity_by_day.ruid ORDER BY v1 DESC, ruid_activity_by_day.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Individual Top Days &ndash; '.$this->date_last_log_parsed->format('F').' '.$this->date_last_log_parsed->format('Y'), ['Lines', 'User'], ['num', 'str'], ['SELECT MAX(l_total) AS v1, csnick AS v2 FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date LIKE \''.$this->date_last_log_parsed->format('Y-m').'%\' GROUP BY ruid_activity_by_day.ruid ORDER BY v1 DESC, ruid_activity_by_day.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Most Active Chatters &ndash; All-Time', ['Activity', 'User'], ['num2-perc', 'str'], ['SELECT (CAST(activedays AS REAL) / '.$this->days_logged.') * 100 AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND activedays != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Most Active Chatters &ndash; '.$this->date_last_log_parsed->format('Y'), ['Activity', 'User'], ['num2-perc', 'str'], ['SELECT (CAST(COUNT(DISTINCT date) AS REAL) / (SELECT COUNT(*) FROM parse_history WHERE date LIKE \''.$this->date_last_log_parsed->format('Y').'%\')) * 100 AS v1, csnick AS v2 FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date LIKE \''.$this->date_last_log_parsed->format('Y').'%\' GROUP BY ruid_activity_by_day.ruid ORDER BY v1 DESC, ruid_activity_by_day.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Most Active Chatters &ndash; '.$this->date_last_log_parsed->format('F').' '.$this->date_last_log_parsed->format('Y'), ['Activity', 'User'], ['num2-perc', 'str'], ['SELECT (CAST(COUNT(DISTINCT date) AS REAL) / (SELECT COUNT(*) FROM parse_history WHERE date LIKE \''.$this->date_last_log_parsed->format('Y-m').'%\')) * 100 AS v1, csnick AS v2 FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date LIKE \''.$this->date_last_log_parsed->format('Y-m').'%\' GROUP BY ruid_activity_by_day.ruid ORDER BY v1 DESC, ruid_activity_by_day.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Exclamations', ['Total', 'User', 'Example'], ['num', 'str', 'str'], ['SELECT exclamations AS v1, csnick AS v2, ex_exclamations AS v3 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND exclamations != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(exclamations) FROM ruid_lines']);
		$section .= $this->create_table('Questions', ['Total', 'User', 'Example'], ['num', 'str', 'str'], ['SELECT questions AS v1, csnick AS v2, ex_questions AS v3 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND questions != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(questions) FROM ruid_lines']);
		$section .= $this->create_table('UPPERCASED Lines', ['Total', 'User', 'Example'], ['num', 'str', 'str'], ['SELECT uppercased AS v1, csnick AS v2, ex_uppercased AS v3 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND uppercased != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(uppercased) FROM ruid_lines']);
		$section .= $this->create_table('Monologues', ['Total', 'User'], ['num', 'str'], ['SELECT monologues AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND monologues != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(monologues) FROM ruid_lines']);
		$section .= $this->create_table('Longest Monologue', ['Lines', 'User'], ['num', 'str'], ['SELECT topmonologue AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND topmonologue != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Moodiest People', ['Smileys', 'User'], ['num', 'str'], ['SELECT smile + wink + sad + cry + silly + big_smile + cheer + concerned + happy + kiss + cool + very_sad + stunned + distressed + heart + confused + surprised + neutral + cute + annoyed AS v1, csnick AS v2 FROM ruid_smileys JOIN uid_details ON ruid_smileys.ruid = uid_details.uid WHERE status NOT IN (3,4) ORDER BY v1 DESC, ruid_smileys.ruid ASC LIMIT 5', 'SELECT SUM(smile) + SUM(wink) + SUM(sad) + SUM(cry) + SUM(silly) + SUM(big_smile) + SUM(cheer) + SUM(concerned) + SUM(happy) + SUM(kiss) + SUM(cool) + SUM(very_sad) + SUM(stunned) + SUM(distressed) + SUM(heart) + SUM(confused) + SUM(surprised) + SUM(neutral) + SUM(cute) + SUM(annoyed) FROM ruid_smileys']);
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
		 * Smileys section.
		 */
		$section = '';

		/**
		 * Display the top 9 smileys and top 6 textual user expressions.
		 */
		$result = db::query_single_row('SELECT SUM(smile) AS smile, SUM(wink) AS wink, SUM(sad) AS sad, SUM(cry) AS cry, SUM(silly) AS silly, SUM(big_smile) AS big_smile, SUM(cheer) AS cheer, SUM(concerned) AS concerned, SUM(happy) AS happy, SUM(kiss) AS kiss, SUM(cool) AS cool, SUM(very_sad) AS very_sad, SUM(stunned) AS stunned, SUM(distressed) AS distressed, SUM(heart) AS heart, SUM(confused) AS confused, SUM(surprised) AS surprised, SUM(neutral) AS neutral, SUM(cute) AS cute, SUM(annoyed) AS annoyed, SUM(hehe) AS hehe, SUM(heh) AS heh, SUM(haha) AS haha, SUM(lol) AS lol, SUM(hmm) AS hmm, SUM(wow) AS wow, SUM(meh) AS meh, SUM(ugh) AS ugh, SUM(pff) AS pff, SUM(rofl) AS rofl, SUM(lmao) AS lmao, SUM(huh) AS huh FROM ruid_smileys');
		$smileys = [
			'smile' => [':)', ':-)', '=]', '=)', ':]', ':>'],
			'wink' => [';)', ';-)'],
			'sad' => [':(', ':<', ':[', '=(', ':-('],
			'cry' => [';(', ';_;', ':\'('],
			'silly' => [':P', ';p', '=p', ':-P'],
			'big_smile' => [':))'],
			'cheer' => ['\o/'],
			'concerned' => [':/', ':-/', '=/'],
			'happy' => [':D', ':-D', '=D', 'xD'],
			'kiss' => [':x'],
			'cool' => ['8)'],
			'very_sad' => [':(('],
			'stunned' => ['o_O'],
			'distressed' => ['D:'],
			'heart' => ['<3'],
			'confused' => [':S'],
			'surprised' => [':o'],
			'neutral' => [':|'],
			'cute' => [':3'],
			'annoyed' => ['-_-'],
			'hehe' => [],
			'heh' => [],
			'haha' => [],
			'lol' => [],
			'hmm' => [],
			'wow' => [],
			'meh' => [],
			'ugh' => [],
			'pff' => [],
			'rofl' => [],
			'lmao' => [],
			'huh' => []];

		if (!empty($result)) {
			arsort($result);
			$count_smileys = 0;
			$count_textual = 0;

			foreach ($result as $key => $value) {
				if ($value === 0) {
					continue;
				}

				if (in_array($key, ['smile', 'wink', 'sad', 'cry', 'silly', 'big_smile', 'cheer', 'concerned', 'happy', 'kiss', 'cool', 'very_sad', 'stunned', 'distressed', 'heart', 'confused', 'surprised', 'neutral', 'cute', 'annoyed'])) {
					if (++$count_smileys > 9) {
						continue;
					}

					$title = ucwords(preg_replace('/_/', ' ', $key)).' '.htmlspecialchars($smileys[$key][rand(0, count($smileys[$key]) - 1)]);
				} else {
					if (++$count_textual > 6) {
						continue;
					}

					$title = '<i>"'.$key.'"</i>';
				}

				$section .= $this->create_table($title, ['Total', 'User'], ['num', 'str'], ['SELECT '.$key.' AS v1, csnick AS v2 FROM ruid_smileys JOIN uid_details ON ruid_smileys.ruid = uid_details.uid WHERE status NOT IN (3,4) AND '.$key.' != 0 ORDER BY v1 DESC, ruid_smileys.ruid ASC LIMIT 5', 'SELECT SUM('.$key.') FROM ruid_smileys']);
			}
		}

		if ($section !== '') {
			$html .= '<div class="section">Smileys</div>'."\n".$section;
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
		$results = db::query('SELECT * FROM (SELECT length, COUNT(*) AS total FROM words GROUP BY length ORDER BY total DESC, length DESC LIMIT 9) ORDER BY length ASC');

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			$section .= $this->create_table('Words of '.$result['length'].' Characters', ['Times Used', 'Word'], ['num', 'str'], ['SELECT total AS v1, word AS v2 FROM words WHERE length = '.$result['length'].' ORDER BY v1 DESC, v2 ASC LIMIT 5', $result['total']]);
		}

		if ($section !== '') {
			$html .= '<div class="section">Words</div>'."\n".$section;
		}

		/**
		 * Milestones section.
		 */
		$section = '';
		$results = db::query('SELECT milestone, COUNT(*) AS total FROM ruid_milestones GROUP BY milestone ORDER BY milestone ASC');

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			$section .= $this->create_table(number_format($result['milestone']).' Lines Milestone', ['Date', 'User'], ['date', 'str'], ['SELECT date AS v1, csnick AS v2 FROM ruid_milestones JOIN uid_details ON ruid_milestones.ruid = uid_details.uid WHERE milestone = '.$result['milestone'].' ORDER BY v1 ASC, ruid_milestones.ruid ASC LIMIT 5', $result['total']]);
		}

		if ($section !== '') {
			$html .= '<div class="section">Milestones</div>'."\n".$section;
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
		 * Retrieve the total for the dataset.
		 */
		if (!empty($queries[1])) {
			if (is_int($queries[1])) {
				$total = $queries[1];
			} else {
				$total = db::query_single_col($queries[1]);
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
		 * Retrieve the main dataset.
		 */
		$row = 0;
		$results = db::query($queries[0]);

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
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

						foreach ($words as $csword) {
							if (preg_match('/^(www\.|https?:\/\/).+/i', $csword) && !is_null($urlparts = $this->get_urlparts($csword))) {
								$line .= '<a href="'.htmlspecialchars($urlparts['url'], ENT_QUOTES | ENT_HTML5, 'UTF-8').'">'.htmlspecialchars($urlparts['url'], ENT_QUOTES | ENT_HTML5, 'UTF-8').'</a> ';
							} else {
								$line .= htmlspecialchars($csword, ENT_QUOTES | ENT_HTML5, 'UTF-8').' ';
							}
						}

						${'v'.$col} = rtrim($line);
						break;
					case 'str-userstats':
						${'v'.$col} = '<a href="user.php?nick='.htmlspecialchars(rawurlencode(${'v'.$col}), ENT_QUOTES | ENT_HTML5, 'UTF-8').'">'.htmlspecialchars(${'v'.$col}, ENT_QUOTES | ENT_HTML5, 'UTF-8').'</a>';
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

	private function make_table_people($type)
	{
		/**
		 * Only create the table if there is activity from users other than bots and
		 * excluded users.
		 */
		if ($type === 'alltime') {
			$total = db::query_single_col('SELECT SUM(l_total) FROM ruid_activity_by_year JOIN uid_details ON ruid_activity_by_year.ruid = uid_details.uid WHERE status NOT IN (3,4)');
		} elseif ($type === 'month') {
			$total = db::query_single_col('SELECT SUM(l_total) FROM ruid_activity_by_month JOIN uid_details ON ruid_activity_by_month.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date = \''.date('Y-m', mktime(0, 0, 0, (int) $this->date_last_log_parsed->format('n'), 1, (int) $this->date_last_log_parsed->format('Y'))).'\'');
		} elseif ($type === 'year') {
			$total = db::query_single_col('SELECT SUM(l_total) FROM ruid_activity_by_year JOIN uid_details ON ruid_activity_by_year.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date = \''.$this->date_last_log_parsed->format('Y').'\'');
		}

		if (is_null($total)) {
			return;
		}

		if ($type === 'alltime') {
			$head = 'Most Talkative People &ndash; All-Time';
			$historylink = '<a href="history.php">History</a>';

			/**
			 * Don't try to calculate changes in rankings if we're dealing with the first
			 * month of activity.
			 */
			if (!$this->rankings || date('Y-m', mktime(0, 0, 0, (int) $this->date_last_log_parsed->format('n'), 1, (int) $this->date_last_log_parsed->format('Y'))) === $this->date_first_activity->format('Y-m')) {
				$results = db::query('SELECT csnick, l_total, l_night, l_morning, l_afternoon, l_evening, quote, lasttalked FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND l_total != 0 ORDER BY l_total DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows_people_alltime);
			} else {
				$results = db::query('SELECT csnick, l_total, l_night, l_morning, l_afternoon, l_evening, quote, lasttalked, (SELECT rank FROM ruid_rankings WHERE ruid = ruid_lines.ruid AND date = \''.date('Y-m', mktime(0, 0, 0, (int) $this->date_last_log_parsed->format('n') - 1, 1, (int) $this->date_last_log_parsed->format('Y'))).'\') AS prevrank FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND l_total != 0 ORDER BY l_total DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows_people_alltime);
			}
		} elseif ($type === 'month') {
			$head = 'Most Talkative People &ndash; '.$this->date_last_log_parsed->format('F').' '.$this->date_last_log_parsed->format('Y');
			$historylink = '<a href="history.php?year='.$this->date_last_log_parsed->format('Y').'&amp;month='.(int) $this->date_last_log_parsed->format('n').'">History</a>';
			$results = db::query('SELECT csnick, ruid_activity_by_month.l_total AS l_total, ruid_activity_by_month.l_night AS l_night, ruid_activity_by_month.l_morning AS l_morning, ruid_activity_by_month.l_afternoon AS l_afternoon, ruid_activity_by_month.l_evening AS l_evening, quote, lasttalked FROM ruid_activity_by_month JOIN uid_details ON ruid_activity_by_month.ruid = uid_details.uid JOIN ruid_lines ON ruid_activity_by_month.ruid = ruid_lines.ruid WHERE status NOT IN (3,4) AND date = \''.date('Y-m', mktime(0, 0, 0, (int) $this->date_last_log_parsed->format('n'), 1, (int) $this->date_last_log_parsed->format('Y'))).'\' ORDER BY l_total DESC, ruid_activity_by_month.ruid ASC LIMIT '.$this->maxrows_people_month);
		} elseif ($type === 'year') {
			$head = 'Most Talkative People &ndash; '.$this->date_last_log_parsed->format('Y');
			$historylink = '<a href="history.php?year='.$this->date_last_log_parsed->format('Y').'">History</a>';
			$results = db::query('SELECT csnick, ruid_activity_by_year.l_total AS l_total, ruid_activity_by_year.l_night AS l_night, ruid_activity_by_year.l_morning AS l_morning, ruid_activity_by_year.l_afternoon AS l_afternoon, ruid_activity_by_year.l_evening AS l_evening, quote, lasttalked FROM ruid_activity_by_year JOIN uid_details ON ruid_activity_by_year.ruid = uid_details.uid JOIN ruid_lines ON ruid_activity_by_year.ruid = ruid_lines.ruid WHERE status NOT IN (3,4) AND date = \''.$this->date_last_log_parsed->format('Y').'\' ORDER BY l_total DESC, ruid_activity_by_year.ruid ASC LIMIT '.$this->maxrows_people_year);
		}

		$i = 0;
		$times = ['night', 'morning', 'afternoon', 'evening'];
		$tr0 = '<colgroup><col class="c1"><col class="c2"><col class="pos"><col class="c3"><col class="c4"><col class="c5"><col class="c6">';
		$tr1 = '<tr><th colspan="7">'.($this->history ? '<span class="title">'.$head.'</span><span class="title-right">'.$historylink.'</span>' : $head);
		$tr2 = '<tr><td class="k1">Percentage<td class="k2">Lines<td class="pos"><td class="k3">User<td class="k4">When?<td class="k5">Last Talked<td class="k6">Quote';
		$trx = '';

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
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
					$when .= '<li class="'.$time[0].'" style="width:'.$width_int[$time].'px">';
				}
			}

			if (!isset($result['prevrank']) || $i === $result['prevrank']) {
				$pos = $i;
			} elseif ($i < $result['prevrank']) {
				$pos = '<span class="green">&#x25B2;'.$i.'</span>';
			} elseif ($i > $result['prevrank']) {
				$pos = '<span class="red">&#x25BC;'.$i.'</span>';
			}

			$trx .= '<tr><td class="v1">'.number_format(($result['l_total'] / $total) * 100, 2).'%<td class="v2">'.number_format($result['l_total']).'<td class="pos">'.$pos.'<td class="v3">'.($this->user_stats ? '<a href="user.php?nick='.htmlspecialchars(rawurlencode($result['csnick']), ENT_QUOTES | ENT_HTML5, 'UTF-8').'">'.$result['csnick'].'</a>' : $result['csnick']).'<td class="v4"><ul>'.$when.'</ul><td class="v5">'.$this->ago($result['lasttalked']).'<td class="v6">'.htmlspecialchars($result['quote'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

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
		if (!$this->rankings || date('Y-m', mktime(0, 0, 0, (int) $this->date_last_log_parsed->format('n'), 1, (int) $this->date_last_log_parsed->format('Y'))) === $this->date_first_activity->format('Y-m')) {
			$results = db::query('SELECT csnick, l_total FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND l_total != 0 ORDER BY l_total DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows_people_alltime.', '.($this->maxrows_people2 * 4));
		} else {
			$results = db::query('SELECT csnick, l_total, (SELECT rank FROM ruid_rankings WHERE ruid = ruid_lines.ruid AND date = \''.date('Y-m', mktime(0, 0, 0, (int) $this->date_last_log_parsed->format('n') - 1, 1, (int) $this->date_last_log_parsed->format('Y'))).'\') AS prevrank FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND l_total != 0 ORDER BY l_total DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows_people_alltime.', '.($this->maxrows_people2 * 4));
		}

		$current_column = 1;
		$current_row = 0;

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
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

		$total = db::query_single_col('SELECT COUNT(*) FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4)');
		$total -= $this->maxrows_people_alltime + ($this->maxrows_people2 * 4);
		$tr0 = '<colgroup><col class="c1"><col class="pos"><col class="c2"><col class="c1"><col class="pos"><col class="c2"><col class="c1"><col class="pos"><col class="c2"><col class="c1"><col class="pos"><col class="c2">';
		$tr1 = '<tr><th colspan="12">'.($total !== 0 ? '<span class="title">Less Talkative People &ndash; All-Time</span><span class="title-right">'.number_format($total).' People had even less to say..</span>' : 'Less Talkative People &ndash; All-Time');
		$tr2 = '<tr><td class="k1">Lines<td class="pos"><td class="k2">User<td class="k1">Lines<td class="pos"><td class="k2">User<td class="k1">Lines<td class="pos"><td class="k2">User<td class="k1">Lines<td class="pos"><td class="k2">User';
		$trx = '';

		for ($i = 1; $i <= $this->maxrows_people2; ++$i) {
			$trx .= '<tr>';

			for ($j = 1; $j <= 4; ++$j) {
				$trx .= '<td class="v1">'.number_format($columns[$j][$i]['l_total']).'<td class="pos">'.$columns[$j][$i]['pos'].'<td class="v2">'.($this->user_stats ? '<a href="user.php?nick='.htmlspecialchars(rawurlencode($columns[$j][$i]['csnick']), ENT_QUOTES | ENT_HTML5, 'UTF-8').'">'.$columns[$j][$i]['csnick'].'</a>' : $columns[$j][$i]['csnick']);
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
		$total = db::query_single_col('SELECT SUM(l_total) FROM ruid_activity_by_year JOIN uid_details ON ruid_activity_by_year.ruid = uid_details.uid WHERE status NOT IN (3,4)');

		if (is_null($total)) {
			return;
		}

		$high_value = 0;
		$times = ['night', 'morning', 'afternoon', 'evening'];

		foreach ($times as $time) {
			$results = db::query('SELECT csnick, l_'.$time.' FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND l_'.$time.' != 0 ORDER BY l_'.$time.' DESC, ruid_lines.ruid ASC LIMIT '.$this->maxrows_people_timeofday);
			$i = 0;

			while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
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
						$trx .= '<td class="v">'.${$time}[$i]['csnick'].' - '.number_format(${$time}[$i]['lines']).'<br><div class="'.$time[0].'" style="width:'.$width.'px"></div>';
					} else {
						$trx .= '<td class="v">'.${$time}[$i]['csnick'].' - '.number_format(${$time}[$i]['lines']);
					}
				}
			}
		}

		return '<table class="ppl-tod">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}
}
