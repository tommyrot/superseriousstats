<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2021, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Class for creating the main stats page.
 */
class html
{
	use common, common_web, urlparts;

	private bool $link_history_php = true;
	private bool $link_user_php = true;
	private bool $show_banner = true;
	private string $channel = 'unconfigured';
	private string $favicon = 'favicon.svg';
	private string $now = '';
	private string $stylesheet = 'sss.css';

	public function __construct()
	{
		$this->apply_vars('settings', ['channel', 'favicon', 'stylesheet', 'link_history_php', 'link_user_php', 'show_banner']);
	}

	/**
	 * Generate the HTML page.
	 */
	public function get_contents(): string
	{
		/**
		 * Stats require a non-empty dataset.
		 */
		if (db::query_single_col('SELECT EXISTS (SELECT 1 FROM channel_activity)') === 0) {
			return '<!DOCTYPE html>'."\n\n".'<html lang="en"><head><meta charset="utf-8"><title>seriously?</title><link rel="stylesheet" href="'.$this->htmlify($this->stylesheet).'"></head><body><div id="container"><div class="error">There is not enough data to create statistics, yet.</div></div></body></html>'."\n";
		}

		/**
		 * Collect all details for this channel.
		 */
		$result = db::query_single_row('SELECT COUNT(*) AS days_logged, MIN(date) AS date_first_log_parsed, MAX(date) AS date_last_log_parsed FROM parse_history');
		$days_logged = $result['days_logged'];
		$date_first_log_parsed = $result['date_first_log_parsed'];
		$date_last_log_parsed = $result['date_last_log_parsed'];
		$this->now = $date_last_log_parsed;
		$result = db::query_single_row('SELECT date, l_total FROM channel_activity ORDER BY l_total DESC, date ASC LIMIT 1');
		$high_date = $result['date'];
		$high_lines = $result['l_total'];
		$l_total = db::query_single_col('SELECT SUM(l_total) FROM channel_activity');
		$l_avg = (int) round($l_total / $days_logged);

		/**
		 * HEAD
		 */
		$contents = '<!DOCTYPE html>'."\n\n"
			. '<html lang="en">'."\n\n"
			. '<head>'."\n"
			. '<meta charset="utf-8">'."\n"
			. '<meta name="referrer" content="no-referrer">'."\n"
			. '<title>'.$this->htmlify($this->channel).', seriously.</title>'."\n"
			. '<link rel="icon" href="'.$this->htmlify($this->favicon).'">'."\n"
			. '<link rel="stylesheet" href="'.$this->htmlify($this->stylesheet).'">'."\n"
			. '</head>'."\n\n"
			. '<body'.($this->show_banner ? ' class="bannerbg"' : '').'><div id="container">'."\n"
			. ($this->show_banner ? '<img src="banner.svg" alt="" class="banner">'."\n" : '')
			. '<div class="info">'.$this->htmlify($this->channel).', seriously.<br><br>'
			. number_format($days_logged).' day'.($days_logged !== 1 ? 's logged from '.date('M j, Y', strtotime($date_first_log_parsed)).' to '.date('M j, Y', strtotime($date_last_log_parsed)) : ' logged on '.date('M j, Y', strtotime($date_first_log_parsed))).'.<br><br>'
			. 'Logs contain '.number_format($l_total).' line'.($l_total !== 1 ? 's' : '').' &ndash; an average of '.number_format($l_avg).' line'.($l_avg !== 1 ? 's' : '').' per day.<br>'
			. 'Most active day was '.date('M j, Y', strtotime($high_date)).' with a total of '.number_format($high_lines).' line'.($high_lines !== 1 ? 's' : '').' typed.</div>'."\n";

		/**
		 * CONTENT
		 */
		$contents .= '<div class="section">Activity</div>'."\n";
		$contents .= $this->create_table_activity_distribution_hour();
		$contents .= $this->create_table_activity('day');
		$contents .= $this->create_table_activity('month');
		$contents .= $this->create_table_activity('year');
		$contents .= $this->create_table_activity_distribution_day();
		$contents .= $this->create_table_people();
		$contents .= $this->create_table_people2();
		$contents .= $this->create_table_people_timeofday();

		/**
		 * Avoid displaying two identical tables.
		 */
		$show_table_year = false;
		$show_table_month = false;

		if (db::query_single_col('SELECT COUNT(DISTINCT date) FROM ruid_activity_by_year') > 1) {
			$show_table_year = true;
			$contents .= $this->create_table_people('year');
		}

		if (db::query_single_col('SELECT COUNT(DISTINCT date) FROM ruid_activity_by_month WHERE date LIKE \''.substr($this->now, 0, 4).'%\'') > 1) {
			$show_table_month = true;
			$contents .= $this->create_table_people('month');
		}

		$contents .= $this->create_table_people_timeofday(true);

		/**
		 * Build the "General Chat" section.
		 */
		$section = '';
		$section .= $this->create_table('Most Talkative Chatters', ['Lines/Day', 'User'], ['num1', 'str'], ['SELECT CAST(l_total AS REAL) / activedays AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND activedays >= 7 AND lasttalked >= DATETIME(\''.$this->now.'\', \'-30 day\') ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Most Fluent Chatters', ['Words/Line', 'User'], ['num1', 'str'], ['SELECT CAST(words AS REAL) / l_total AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND activedays >= 7 AND lasttalked >= DATETIME(\''.$this->now.'\', \'-30 day\') ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Most Tedious Chatters', ['Chars/Line', 'User'], ['num1', 'str'], ['SELECT CAST(characters AS REAL) / l_total AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND activedays >= 7 AND lasttalked >= DATETIME(\''.$this->now.'\', \'-30 day\') ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Individual Top Days &ndash; All-Time', ['Lines', 'User'], ['num', 'str'], ['SELECT MAX(l_total) AS v1, csnick AS v2 FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) GROUP BY ruid_activity_by_day.ruid ORDER BY v1 DESC, ruid_activity_by_day.ruid ASC LIMIT 5']);

		if ($show_table_year) {
			$section .= $this->create_table('Individual Top Days &ndash; '.substr($this->now, 0, 4), ['Lines', 'User'], ['num', 'str'], ['SELECT MAX(l_total) AS v1, csnick AS v2 FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date LIKE \''.substr($this->now, 0, 4).'%\' GROUP BY ruid_activity_by_day.ruid ORDER BY v1 DESC, ruid_activity_by_day.ruid ASC LIMIT 5']);
		}

		if ($show_table_month) {
			$section .= $this->create_table('Individual Top Days &ndash; '.date('F Y', strtotime($this->now)), ['Lines', 'User'], ['num', 'str'], ['SELECT MAX(l_total) AS v1, csnick AS v2 FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date LIKE \''.substr($this->now, 0, 7).'%\' GROUP BY ruid_activity_by_day.ruid ORDER BY v1 DESC, ruid_activity_by_day.ruid ASC LIMIT 5']);
		}

		$section .= $this->create_table('Most Active Chatters &ndash; All-Time', ['Activity', 'User'], ['num2-perc', 'str'], ['SELECT (CAST(activedays AS REAL) / '.$days_logged.') * 100 AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND activedays != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5']);

		if ($show_table_year) {
			$section .= $this->create_table('Most Active Chatters &ndash; '.substr($this->now, 0, 4), ['Activity', 'User'], ['num2-perc', 'str'], ['SELECT (CAST(COUNT(DISTINCT date) AS REAL) / (SELECT COUNT(*) FROM parse_history WHERE date LIKE \''.substr($this->now, 0, 4).'%\')) * 100 AS v1, csnick AS v2 FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date LIKE \''.substr($this->now, 0, 4).'%\' GROUP BY ruid_activity_by_day.ruid ORDER BY v1 DESC, ruid_activity_by_day.ruid ASC LIMIT 5']);
		}

		if ($show_table_month) {
			$section .= $this->create_table('Most Active Chatters &ndash; '.date('F Y', strtotime($this->now)), ['Activity', 'User'], ['num2-perc', 'str'], ['SELECT (CAST(COUNT(DISTINCT date) AS REAL) / (SELECT COUNT(*) FROM parse_history WHERE date LIKE \''.substr($this->now, 0, 7).'%\')) * 100 AS v1, csnick AS v2 FROM ruid_activity_by_day JOIN uid_details ON ruid_activity_by_day.ruid = uid_details.uid WHERE status NOT IN (3,4) AND date LIKE \''.substr($this->now, 0, 7).'%\' GROUP BY ruid_activity_by_day.ruid ORDER BY v1 DESC, ruid_activity_by_day.ruid ASC LIMIT 5']);
		}

		$section .= $this->create_table('Exclamations', ['Total', 'User', 'Example'], ['num', 'str', 'str'], ['SELECT exclamations AS v1, csnick AS v2, ex_exclamations AS v3 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND exclamations != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(exclamations) FROM ruid_lines']);
		$section .= $this->create_table('Questions', ['Total', 'User', 'Example'], ['num', 'str', 'str'], ['SELECT questions AS v1, csnick AS v2, ex_questions AS v3 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND questions != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(questions) FROM ruid_lines']);
		$section .= $this->create_table('UPPERCASED Lines', ['Total', 'User', 'Example'], ['num', 'str', 'str'], ['SELECT uppercased AS v1, csnick AS v2, ex_uppercased AS v3 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND uppercased != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(uppercased) FROM ruid_lines']);
		$section .= $this->create_table('Monologues', ['Total', 'User'], ['num', 'str'], ['SELECT monologues AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND monologues != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(monologues) FROM ruid_lines']);
		$section .= $this->create_table('Longest Monologue', ['Lines', 'User'], ['num', 'str'], ['SELECT topmonologue AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND topmonologue != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Moodiest People', ['Smileys', 'User'], ['num', 'str'], ['SELECT SUM(total) AS v1, csnick AS v2 FROM ruid_smileys JOIN uid_details ON ruid_smileys.ruid = uid_details.uid JOIN smileys ON ruid_smileys.sid = smileys.sid WHERE status NOT IN (3,4) AND category IS NOT NULL GROUP BY ruid_smileys.ruid ORDER BY v1 DESC, ruid_smileys.ruid ASC LIMIT 5', 'SELECT SUM(total) FROM ruid_smileys JOIN smileys ON ruid_smileys.sid = smileys.sid WHERE category IS NOT NULL']);
		$section .= $this->create_table('Slaps Given', ['Total', 'User'], ['num', 'str'], ['SELECT slaps AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND slaps != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(slaps) FROM ruid_lines']);
		$section .= $this->create_table('Slaps Received', ['Total', 'User'], ['num', 'str'], ['SELECT slapped AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND slapped != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(slapped) FROM ruid_lines']);
		$section .= $this->create_table('Most Lively Bots', ['Lines', 'Bot'], ['num', ($this->link_user_php ? 'str-userstats' : 'str')], ['SELECT l_total AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status = 3 AND l_total != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5']);
		$section .= $this->create_table('Actions Performed', ['Total', 'User', 'Example'], ['num', 'str', 'str'], ['SELECT actions AS v1, csnick AS v2, ex_actions AS v3 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND actions != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(actions) FROM ruid_lines']);

		if ($section !== '') {
			$contents .= '<div class="section">General Chat</div>'."\n".$section;
		}

		/**
		 * Build the "Modes" section.
		 */
		$section = '';
		$modes = [
			'm_op' => 'Ops &apos;+o&apos; Given',
			'm_opped' => 'Ops &apos;+o&apos; Received',
			'm_deop' => 'deOps &apos;-o&apos; Given',
			'm_deopped' => 'deOps &apos;-o&apos; Received',
			'm_voice' => 'Voices &apos;+v&apos; Given',
			'm_voiced' => 'Voices &apos;+v&apos; Received',
			'm_devoice' => 'deVoices &apos;-v&apos; Given',
			'm_devoiced' => 'deVoices &apos;-v&apos; Received'];

		foreach ($modes as $mode => $title) {
			$section .= $this->create_table($title, ['Total', 'User'], ['num', 'str'], ['SELECT '.$mode.' AS v1, csnick AS v2 FROM ruid_events JOIN uid_details ON ruid_events.ruid = uid_details.uid WHERE status NOT IN (3,4) AND '.$mode.' != 0 ORDER BY v1 DESC, ruid_events.ruid ASC LIMIT 5', 'SELECT SUM('.$mode.') FROM ruid_events']);
		}

		if ($section !== '') {
			$contents .= '<div class="section">Modes</div>'."\n".$section;
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
		$section .= $this->create_table('Aliases', ['Total', 'User'], ['num', 'str'], ['SELECT COUNT(*) - 1 AS v1, (SELECT csnick FROM uid_details WHERE uid = t1.ruid) AS v2 FROM uid_details AS t1 WHERE ruid IN (SELECT ruid FROM uid_details WHERE status = 1) GROUP BY ruid HAVING v1 != 0 ORDER BY v1 DESC, ruid ASC LIMIT 5', 'SELECT COUNT(*) FROM uid_details WHERE status = 2']);
		$section .= $this->create_table('Topics Set', ['Total', 'User'], ['num', 'str'], ['SELECT topics AS v1, csnick AS v2 FROM ruid_events JOIN uid_details ON ruid_events.ruid = uid_details.uid WHERE status NOT IN (3,4) AND topics != 0 ORDER BY v1 DESC, ruid_events.ruid ASC LIMIT 5', 'SELECT SUM(topics) FROM ruid_events']);
		$section .= $this->create_table('Most Recent Topics', ['Date', 'User', 'Topic'], ['date', 'str', 'str-url'], ['SELECT datetime AS v1, (SELECT csnick FROM uid_details WHERE uid = t1.ruid) AS v2, topic AS v3 FROM uid_topics JOIN uid_details AS t1 ON uid_topics.uid = t1.uid WHERE ruid NOT IN (SELECT ruid FROM uid_details WHERE status = 4) ORDER BY uid_topics.ROWID DESC limit 5']);

		if ($section !== '') {
			$contents .= '<div class="section">Events</div>'."\n".$section;
		}

		/**
		 * Build the "Smileys" section.
		 */
		$section = '';
		$results = db::query('SELECT category, smiley, SUM(total) AS total FROM ruid_smileys JOIN smileys ON ruid_smileys.sid = smileys.sid WHERE category IS NOT NULL GROUP BY category ORDER BY total DESC, ruid_smileys.sid ASC LIMIT 9');

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			$section .= $this->create_table(ucwords($result['category']).' '.$this->htmlify($result['smiley']), ['Total', 'User'], ['num', 'str'], ['SELECT SUM(total) AS v1, csnick AS v2 FROM ruid_smileys JOIN smileys ON ruid_smileys.sid = smileys.sid JOIN uid_details ON ruid_smileys.ruid = uid_details.uid WHERE status NOT IN (3,4) AND category = \''.$result['category'].'\' GROUP BY ruid_smileys.ruid, category ORDER BY v1 DESC, ruid_smileys.ruid ASC LIMIT 5', $result['total']]);
		}

		if ($section !== '') {
			$contents .= '<div class="section">Smileys</div>'."\n".$section;
		}

		/**
		 * Build the "Expressions" section.
		 */
		$section = '';
		$results = db::query('SELECT smiley, SUM(total) AS total FROM ruid_smileys JOIN smileys ON ruid_smileys.sid = smileys.sid WHERE category IS NULL GROUP BY smiley ORDER BY total DESC, ruid_smileys.sid ASC LIMIT 9');

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			$section .= $this->create_table('&quot;<i>'.$result['smiley'].'</i>&quot;', ['Total', 'User'], ['num', 'str'], ['SELECT total AS v1, csnick AS v2 FROM ruid_smileys JOIN smileys ON ruid_smileys.sid = smileys.sid JOIN uid_details ON ruid_smileys.ruid = uid_details.uid WHERE status NOT IN (3,4) AND smiley = \''.$result['smiley'].'\' ORDER BY v1 DESC, ruid_smileys.ruid ASC LIMIT 5', $result['total']]);
		}

		if ($section !== '') {
			$contents .= '<div class="section">Expressions</div>'."\n".$section;
		}

		/**
		 * Build the "URLs" section.
		 */
		$section = '';
		$section .= $this->create_table('Most Referenced Domain Names', ['Total', 'Domain', 'First Used'], ['num', 'url', 'date'], ['SELECT SUM(total) AS v1, \'http://\' || fqdn AS v2, MIN(firstused) AS v3 FROM ruid_urls JOIN urls ON ruid_urls.lid = urls.lid JOIN fqdns ON urls.fid = fqdns.fid WHERE active = 1 GROUP BY urls.fid ORDER BY v1 DESC, v3 ASC LIMIT 10'], 10);
		$section .= $this->create_table('Most Referenced TLDs', ['Total', 'TLD'], ['num', 'str'], ['SELECT SUM(total) AS v1, \'.\' || tld AS v2 FROM ruid_urls JOIN urls ON ruid_urls.lid = urls.lid JOIN fqdns ON urls.fid = fqdns.fid WHERE active = 1 GROUP BY tld ORDER BY v1 DESC, v2 ASC LIMIT 10'], 10);
		$section .= $this->create_table('Most Recent URLs', ['Date', 'User', 'URL'], ['date-norepeat', 'str', 'url'], ['SELECT lastused AS v1, csnick AS v2, url AS v3 FROM ruid_urls JOIN uid_details ON ruid_urls.ruid = uid_details.uid JOIN urls ON ruid_urls.lid = urls.lid WHERE status NOT IN (3,4) ORDER BY v1 DESC LIMIT 30'], 30);
		$section .= $this->create_table('URLs by Users', ['Total', 'User'], ['num', 'str'], ['SELECT urls AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND urls != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(urls) FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status != 3']);
		$section .= $this->create_table('URLs by Bots', ['Total', 'Bot'], ['num', 'str'], ['SELECT urls AS v1, csnick AS v2 FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status = 3 AND urls != 0 ORDER BY v1 DESC, ruid_lines.ruid ASC LIMIT 5', 'SELECT SUM(urls) FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status = 3']);
		$section .= $this->create_table('Reposts by Users', ['Total', 'User'], ['num', 'str'], ['SELECT SUM(total - CASE WHEN firstused = (SELECT MIN(firstused) FROM ruid_urls WHERE lid = t1.lid) THEN 1 ELSE 0 END) AS v1, csnick AS v2 FROM ruid_urls AS t1 JOIN uid_details ON t1.ruid = uid_details.uid WHERE status NOT IN (3,4) GROUP BY t1.ruid HAVING v1 != 0 ORDER BY v1 DESC, t1.ruid ASC LIMIT 5', 'SELECT SUM(total - CASE WHEN firstused = (SELECT MIN(firstused) FROM ruid_urls WHERE lid = t1.lid) THEN 1 ELSE 0 END) FROM ruid_urls AS t1 JOIN uid_details ON t1.ruid = uid_details.uid WHERE status != 3']);

		if ($section !== '') {
			$contents .= '<div class="section">URLs</div>'."\n".$section;
		}

		/**
		 * Build the "Words by Length" section.
		 */
		$section = '';
		$results = db::query('SELECT * FROM (SELECT length, COUNT(*) AS total FROM words GROUP BY length ORDER BY total DESC, length DESC LIMIT 12) ORDER BY length ASC');

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			/**
			 * Hide words for which a nick with 7 or more days of activity exists.
			 */
			$section .= $this->create_table('Words of '.$result['length'].' Characters', ['Total', 'Word'], ['num', 'str'], ['SELECT total AS v1, word AS v2 FROM words LEFT JOIN uid_details AS t1 ON words.word = t1.csnick COLLATE NOCASE WHERE length = '.$result['length'].' AND (csnick IS NULL OR IFNULL((SELECT activedays FROM ruid_lines WHERE ruid = t1.ruid), 0) < 7) ORDER BY v1 DESC, v2 ASC LIMIT 5', $result['total']]);
		}

		if ($section !== '') {
			$contents .= '<div class="section">Words by Length</div>'."\n".$section;
		}

		/**
		 * Build the "Words by Year of First Use" section.
		 */
		$section = '';
		$results = db::query('SELECT DISTINCT firstused FROM words ORDER BY firstused ASC');

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			/**
			 * Hide words for which a nick with 7 or more days of activity exists.
			 */
			$section .= $this->create_table('Words First Used in '.$result['firstused'], ['Total', 'Word'], ['num', 'str'], ['SELECT total AS v1, word AS v2 FROM words LEFT JOIN uid_details AS t1 ON words.word = t1.csnick COLLATE NOCASE WHERE firstused = \''.$result['firstused'].'\' AND (csnick IS NULL OR IFNULL((SELECT activedays FROM ruid_lines WHERE ruid = t1.ruid), 0) < 7) ORDER BY v1 DESC, v2 ASC LIMIT 5', 'SELECT COUNT(*) FROM words WHERE firstused = \''.$result['firstused'].'\'']);
		}

		if ($section !== '') {
			$contents .= '<div class="section">Words by Year of First Use</div>'."\n".$section;
		}

		/**
		 * Build the "Milestones" section.
		 */
		$section = '';
		$results = db::query('SELECT milestone, COUNT(*) AS total FROM ruid_milestones GROUP BY milestone ORDER BY milestone ASC');

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			$section .= $this->create_table(number_format($result['milestone']).' Lines Milestone', ['Date', 'User'], ['date', 'str'], ['SELECT date AS v1, csnick AS v2 FROM ruid_milestones JOIN uid_details ON ruid_milestones.ruid = uid_details.uid WHERE milestone = '.$result['milestone'].' ORDER BY v1 ASC, ruid_milestones.ruid ASC LIMIT 5', $result['total']]);
		}

		if ($section !== '') {
			$contents .= '<div class="section">Milestones</div>'."\n".$section;
		}

		/**
		 * FOOT
		 */
		$contents .= '<div class="info">Statistics created with <a href="https://sss.dutnie.nl">superseriousstats</a> on '.date('r').' <span class="grey">('.date('T').')</span>.</div>'."\n";
		$contents .= '</div></body>'."\n\n".'</html>'."\n";
		return $contents;
	}

	/**
	 * Create the "Less Talkative People" table.
	 */
	private function create_table_people2(): ?string
	{
		$results = db::query('SELECT csnick, l_total FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4) AND l_total != 0 ORDER BY l_total DESC, ruid_lines.ruid ASC limit 30,45');
		$col = 1;
		$row = 0;

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			if (++$row > 15) {
				++$col;
				$row = 1;
			}

			$columns[$col][$row] = [
				'csnick' => $result['csnick'],
				'l_total' => $result['l_total'],
				'pos' => 30 + (($col - 1) * 15) + $row];
		}

		/**
		 * Return if we don't have enough data to fill the table.
		 */
		if (!isset($columns[3][15])) {
			return null;
		}

		$total = db::query_single_col('SELECT COUNT(*) FROM ruid_lines JOIN uid_details ON ruid_lines.ruid = uid_details.uid WHERE status NOT IN (3,4)') - 75;
		$colgroup = '<colgroup>'.str_repeat('<col>', 13);
		$thead = '<thead><tr><th colspan="13">'.($total !== 0 ? '<span class="title-left">Less Talkative People &ndash; All-Time</span><span class="title-right">'.number_format($total).($total !== 1 ? ' People' : ' Person').' had even less to say..</span>' : 'Less Talkative People &ndash; All-Time');
		$thead .= '<tr><td><td>Lines<td><td>User<td><td>Lines<td><td>User<td><td>Lines<td><td>User<td>';
		$tbody = '<tbody>';

		for ($i = 1; $i <= 15; ++$i) {
			$tbody .= '<tr><td>';

			for ($j = 1; $j <= 3; ++$j) {
				$tbody .= '<td>'.number_format($columns[$j][$i]['l_total']).'<td>'.$columns[$j][$i]['pos'].'<td>'.($this->link_user_php ? '<a href="user.php?nick='.$this->htmlify(urlencode($columns[$j][$i]['csnick'])).'">'.$this->htmlify($columns[$j][$i]['csnick']).'</a>' : $this->htmlify($columns[$j][$i]['csnick'])).'<td>';
			}
		}

		return '<table class="ppl2">'.$colgroup.$thead.$tbody.'</table>'."\n";
	}
}
