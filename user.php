<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2025, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * If the nick isn't set we exit immediately.
 */
if (!isset($_GET['nick'])) {
	exit;
}

/**
 * Include shared code.
 */
require 'web.php';
require 'common.php';
require 'common_web.php';

/**
 * Class for creating user stats.
 */
class user
{
	use common, common_web;

	private bool $show_banner = true;
	private int $ruid = 0;
	private string $channel = 'unconfigured';
	private string $csnick = '';
	private string $favicon = 'favicon.svg';
	private string $main_page = './';
	private string $now = '';
	private string $stylesheet = 'sss.css';
	private string $timezone = '';
	private string $userpics_default = '';
	private string $userpics_dir = 'userpics';

	public function __construct()
	{
		/**
		 * Explicitly set the locale to C (POSIX) for all categories so there hopefully
		 * won't be any unexpected results between platforms.
		 */
		setlocale(LC_ALL, 'C');

		/**
		 * Use UTC until config specified timezone is set.
		 */
		date_default_timezone_set('UTC');

		/**
		 * Set the character encoding used by all mbstring functions.
		 */
		mb_internal_encoding('UTF-8');

		/**
		 * Open the database connection and update our settings.
		 */
		db::connect();
		$this->apply_vars('settings', ['timezone', 'channel', 'favicon', 'userpics_default', 'userpics_dir', 'stylesheet', 'main_page', 'show_banner']);
		out::set_stylesheet($this->stylesheet);

		/**
		 * Set the proper timezone.
		 */
		date_default_timezone_set($this->timezone) or out::put('critical', 'invalid timezone: \''.$this->timezone.'\'');
		$this->now = date('Y-m-d');

		/**
		 * Init done, move to main.
		 */
		$this->main();

		/**
		 * Close the database connection.
		 */
		db::disconnect();
	}

	/**
	 * Generate the HTML page.
	 */
	private function get_contents(): string
	{
		/**
		 * Collect all details for this user.
		 */
		$result = db::query_single_row('SELECT MIN(firstseen) AS firstseen, MAX(lastseen) AS lastseen FROM uid_details WHERE ruid = '.$this->ruid);
		$firstseen = $result['firstseen'];
		$lastseen = $result['lastseen'];
		$result = db::query_single_row('SELECT date, l_total FROM ruid_activity_by_day WHERE ruid = '.$this->ruid.' ORDER BY l_total DESC, date ASC LIMIT 1');
		$high_date = $result['date'];
		$high_lines = $result['l_total'];
		$mood = db::query_single_col('SELECT smiley FROM ruid_smileys JOIN smileys ON ruid_smileys.sid = smileys.sid WHERE ruid = '.$this->ruid.' AND category IS NOT NULL ORDER BY total DESC, ruid_smileys.sid ASC LIMIT 1');
		$l_total = db::query_single_col('SELECT l_total FROM ruid_lines WHERE ruid = '.$this->ruid);
		$l_avg = (int) round($l_total / db::query_single_col('SELECT activedays FROM ruid_lines WHERE ruid = '.$this->ruid));

		/**
		 * Show the user's current all-time ranking.
		 */
		$rank_cur = db::query_single_col('SELECT rank_cur FROM ruid_rank_alltime WHERE ruid = '.$this->ruid);

		/**
		 * If $rank_cur is null the user has no lines, is excluded or is a bot. In the
		 * first two cases we would have already returned an error so that means the
		 * user is a bot. Indicate this instead of showing a ranking.
		 */
		if (is_null($rank_cur)) {
			$ranking = 'Bot';
		} else {
			switch ($rank_cur % 100) {
				case 11:
				case 12:
				case 13:
					$ordinal_suffix = 'th';
					break;
				default:
					switch ($rank_cur % 10) {
						case 1:
							$ordinal_suffix = 'st';
							break;
						case 2:
							$ordinal_suffix = 'nd';
							break;
						case 3:
							$ordinal_suffix = 'rd';
							break;
						default:
							$ordinal_suffix = 'th';
					}
			}

			$ranking = $rank_cur.$ordinal_suffix;
		}

		/**
		 * HEAD
		 */
		$contents = '<!DOCTYPE html>'."\n\n"
			. '<html lang="en">'."\n\n"
			. '<head>'."\n"
			. '<meta charset="utf-8">'."\n"
			. '<title>'.$this->htmlify($this->csnick).', seriously.</title>'."\n"
			. '<link rel="icon" href="'.$this->htmlify($this->favicon).'">'."\n"
			. '<link rel="stylesheet" href="'.$this->htmlify($this->stylesheet).'">'."\n"
			. '</head>'."\n\n"
			. '<body><div id="container">'."\n"
			. ($this->show_banner ? '<div id="bannerbg-top"></div><div id="bannerbg-bottom"></div><svg id="banner" viewBox="0 0 818 50"><path id="banner-text" d="M0 0h48v4h-46v21h46v25h-48v-2h46v-21h-46zm51 0h2v48h44v-48h2v50h-48zm51 0h48v27h-46v-2h44v-21h-44v46h-2zm51 0h48v27h-46v-2h44v-21h-44v44h46v2h-48zm51 0h46v25h2v25h-2v-23h-44v-2h42v-21h-42v46h-2zm51 0h48v4h-46v21h46v25h-48v-2h46v-21h-46zm51 0h48v27h-46v-2h44v-21h-44v44h46v2h-48zm51 0h46v25h2v25h-2v-23h-44v-2h42v-21h-42v46h-2zm51 0h10v4h-4v44h4v2h-10v-2h4v-44h-4zm13 0h48v50h-46v-2h44v-44h-44v46h-2zm51 0h2v48h44v-48h2v50h-48zm51 0h48v4h-46v21h46v25h-48v-2h46v-21h-46zm51 0h48v4h-46v21h46v25h-48v-2h46v-21h-46zm51 0h44v4h-21v46h-2v-46h-21zm47 0h48v50h-2v-23h-44v-2h44v-21h-44v46h-2zm51 0h44v4h-21v46h-2v-46h-21zm47 0h48v4h-46v21h46v25h-48v-2h46v-21h-46z"/><path class="banner-graph" style="fill:#7697cb" d="M428 19h8v25h-8z"/><path class="banner-graph" style="fill:#6c8fc2" d="M428 19h4v25h-4z"/><path class="banner-graph" style="fill:#416d9c" d="M428 19h8v25h-6v-2h4v-21h-4v23h-2z"/><path class="banner-graph" style="fill:#e17677" d="M441 27h8v17h-8z"/><path class="banner-graph" style="fill:#dc6c6d" d="M441 27h4v17h-4z"/><path class="banner-graph" style="fill:#c74243" d="M441 27h8v17h-6v-2h4v-13h-4v15h-2z"/><path class="banner-graph" style="fill:#8fce90" d="M454 11h8v33h-8z"/><path class="banner-graph" style="fill:#89c686" d="M454 11h4v33h-4z"/><path class="banner-graph" style="fill:#70a35e" d="M454 11h8v33h-6v-2h4v-29h-4v31h-2z"/></svg>'."\n" : '')
			. '<div class="info">'.$this->get_userpic().$this->htmlify($this->csnick).', seriously'.(!is_null($mood) ? ' '.$this->htmlify($mood) : '.').'<span id="ranking">'.$ranking.'</span><br><br>'
			. 'First seen on '.date('M j, Y', strtotime($firstseen)).' and last seen on '.date('M j, Y', strtotime($lastseen)).'.<br><br>'
			. $this->htmlify($this->csnick).' typed '.number_format($l_total).' line'.($l_total !== 1 ? 's' : '').' on <a href="'.$this->htmlify($this->main_page).'">'.$this->htmlify($this->channel).'</a> &ndash; an average of '.number_format($l_avg).' line'.($l_avg !== 1 ? 's' : '').' per day.<br>'
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

		/**
		 * Chat buddies! Smart reuse of create_table_people_timeofday() code. Don't
		 * generate the table if user is a bot (status = 3).
		 */
		if (db::query_single_col('SELECT status FROM uid_details WHERE uid = '.$this->ruid) !== 3) {
			$contents .= $this->create_table_people_timeofday(true);
		}

		/**
		 * FOOT
		 */
		$contents .= '<div class="info">Statistics created with <a href="https://github.com/tommyrot/superseriousstats">superseriousstats</a> on '.date('r').' <span class="grey">('.date('T').')</span>.</div>'."\n";
		$contents .= '</div></body>'."\n\n".'</html>'."\n";
		return $contents;
	}

	/**
	 * Find an image for this user to display.
	 */
	private function get_userpic(): ?string
	{
		/**
		 * Create an array with all valid images in $userpics_dir.
		 */
		if (is_dir($this->userpics_dir) && ($dh = opendir($this->userpics_dir)) !== false) {
			while (($entry = readdir($dh)) !== false) {
				if (!is_dir($this->userpics_dir.'/'.$entry) && preg_match('/^(?<filename>\S+)\.(bmp|gif|jpe?g|png|svg)$/in', $entry, $matches)) {
					$images[mb_strtolower($matches['filename'])] = $entry;
				}
			}

			closedir($dh);
		}

		if (!isset($images)) {
			return null;
		}

		/**
		 * Search the $images array for a filename that matches $csnick or any of the
		 * aliases belonging to this user.
		 */
		$nicks = explode(',', mb_strtolower($this->csnick.db::query_single_col('SELECT \',\' || GROUP_CONCAT(csnick) FROM uid_details WHERE ruid = '.$this->ruid.' AND status = 2')));

		foreach ($nicks as $nick) {
			if (isset($images[$nick])) {
				return '<img src="'.$this->htmlify($this->userpics_dir.'/'.$images[$nick]).'" alt="" id="userpic">';
			}
		}

		/**
		 * Display $userpics_default if no image could be found.
		 */
		if ($this->userpics_default !== '') {
			return '<img src="'.$this->htmlify($this->userpics_dir.'/'.$this->userpics_default).'" alt="" id="userpic">';
		}

		return null;
	}

	/**
	 * Find the user to whom $_GET['nick'] belongs and create a stats page for it.
	 */
	private function main(): void
	{
		/**
		 * Do some input validation. Make sure the nick is valid UTF-8 and doesn't
		 * exceed 64 characters in length (arbitrary limit). Don't create a page for
		 * excluded users (status = 4).
		 */
		if (mb_strlen($_GET['nick']) > 64 || !preg_match('/^([\x00-\x7F]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})+$/', $_GET['nick']) || is_null($result = db::query_single_row('SELECT csnick, ruid, status FROM uid_details WHERE uid = (SELECT ruid FROM uid_details WHERE csnick = \''.preg_replace('/\'/', '\'\'', $_GET['nick']).'\')')) || $result['status'] === 4) {
			out::put('critical', 'Nonexistent and/or erroneous nickname.');
		}

		$this->csnick = $result['csnick'];
		$this->ruid = $result['ruid'];

		/**
		 * Stats require a non-empty dataset.
		 */
		if (db::query_single_col('SELECT EXISTS (SELECT 1 FROM ruid_activity_by_day WHERE ruid = '.$this->ruid.')') === 0) {
			out::put('critical', $this->htmlify($this->csnick).' is a filthy lurker!');
		}

		echo $this->get_contents();
	}
}

/**
 * Make stats!
 */
$user = new user();
