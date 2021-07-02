<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2021, Jos de Ruijter <jos@dutnie.nl>
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
			. '<body'.($this->show_banner ? ' class="bannerbg"' : '').'><div id="container">'."\n"
			. ($this->show_banner ? '<img src="banner.svg" alt="" class="banner">'."\n" : '')
			. '<div class="info">'.$this->get_userpic().$this->htmlify($this->csnick).', seriously'.(!is_null($mood) ? ' '.$this->htmlify($mood) : '.').'<span class="ranking">'.$ranking.'</span><br><br>'
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
		$contents .= '<div class="info">Statistics created with <a href="https://sss.dutnie.nl">superseriousstats</a> on '.date('r').' <span class="grey">('.date('T').')</span>.</div>'."\n";
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
				if (!is_dir($this->userpics_dir.'/'.$entry) && preg_match('/^(?<filename>\S+)\.(bmp|gif|jpe?g|png|svg)$/i', $entry, $matches)) {
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
				return '<img src="'.$this->htmlify($this->userpics_dir.'/'.$images[$nick]).'" alt="" class="userpic">';
			}
		}

		/**
		 * Display $userpics_default if no image could be found.
		 */
		if ($this->userpics_default !== '') {
			return '<img src="'.$this->htmlify($this->userpics_dir.'/'.$this->userpics_default).'" alt="" class="userpic">';
		}

		return null;
	}

	/**
	 * Find the user to whom $_GET['nick'] belongs and create a stats page for it.
	 */
	private function main(): void
	{
		/**
		 * Do some input validation. Make sure the nick doesn't contain any invalid
		 * characters and doesn't exceed 64 characters in length (arbitrary limit).
		 * Don't create a page for excluded users (status = 4).
		 */
		if (mb_strlen($_GET['nick']) > 64 || !preg_match('/^([\x00-\x7F]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})+$/', $_GET['nick']) || preg_match('/\p{C}/u', $_GET['nick']) || preg_match('/^[0-9-]|[\x20-\x2C\x2E\x2F\x3A-\x40\x7E]|\xC2\xA0|\xE2\x80[\xA8\xA9]/', $_GET['nick']) || is_null($result = db::query_single_row('SELECT csnick, ruid, status FROM uid_details WHERE uid = (SELECT ruid FROM uid_details WHERE csnick = \''.preg_replace('/\'/', '\'\'', $_GET['nick']).'\')')) || $result['status'] === 4) {
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
