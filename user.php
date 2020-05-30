<?php declare(strict_types=1);

/**
 * Copyright (c) 2007-2020, Jos de Ruijter <jos@dutnie.nl>
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
require 'common.php';
require 'common_user_hist.php';
require 'common_html_user_hist.php';
require 'common_html_user.php';

/**
 * Class for creating user stats.
 */
class user
{
	use common, common_html_user_history, common_html_user;

	private bool $userpics = false;
	private int $l_total = 0;
	private int $ruid = 0;
	private string $channel = 'unconfigured';
	private string $csnick = '';
	private string $main_page = './';
	private string $now = '';
	private string $stylesheet = 'sss.css';
	private string $timezone = '';
	private string $userpics_default = '';
	private string $userpics_dir = './userpics/';

	public function __construct(string $nick)
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
		 * Init done, move to main.
		 */
		$this->main($nick);
	}

	/**
	 * Generate the HTML page.
	 */
	private function get_contents(): string
	{
		/**
		 * Get all the dirt we got on this user.
		 */
		$result = db::query_single_row('SELECT MIN(firstseen) AS firstseen, MAX(lastseen) AS lastseen FROM uid_details WHERE ruid = '.$this->ruid);
		$date_first_seen = date('M j, Y', strtotime($result['firstseen']));
		$date_last_seen = date('M j, Y', strtotime($result['lastseen']));
		$result = db::query_single_row('SELECT date, l_total FROM ruid_activity_by_day WHERE ruid = '.$this->ruid.' ORDER BY l_total DESC, date ASC LIMIT 1');
		$high_date = date('M j, Y', strtotime($result['date']));
		$high_lines = $result['l_total'];
		$mood = db::query_single_col('SELECT smiley FROM ruid_smileys JOIN smileys ON ruid_smileys.sid = smileys.sid WHERE ruid = '.$this->ruid.' AND textual = 0 ORDER BY total DESC, ruid_smileys.sid ASC LIMIT 1');
		$l_avg = $this->l_total / db::query_single_col('SELECT activedays FROM ruid_lines WHERE ruid = '.$this->ruid);

		/**
		 * HEAD
		 */
		$output = '<!DOCTYPE html>'."\n\n"
			. '<html>'."\n\n"
			. '<head>'."\n"
			. '<meta charset="utf-8">'."\n"
			. '<title>'.$this->htmlify($this->csnick).', seriously.</title>'."\n"
			. '<link rel="stylesheet" href="'.$this->htmlify($this->stylesheet).'">'."\n"
			. '</head>'."\n\n"
			. '<body><div id="container">'."\n"
			//. '<div class="info">'.($this->userpics ? $this->get_userpic() : '').$this->htmlify($this->csnick).', seriously'.(!is_null($mood) ? ' '.$this->htmlify($this->mood) : '.').'<br><br>'
			. '<div class="info">'.$this->htmlify($this->csnick).', seriously'.(!is_null($mood) ? ' '.$this->htmlify($mood) : '.').'<br><br>'
			. 'First seen on '.$date_first_seen.' and last seen on '.$date_last_seen.'.<br><br>'
			. $this->htmlify($this->csnick).' typed '.number_format($this->l_total).' line'.($this->l_total > 1 ? 's' : '').' on <a href="'.$this->htmlify($this->main_page).'">'.$this->htmlify($this->channel).'</a> &ndash; an average of '.number_format($l_avg).' line'.($l_avg > 1 ? 's' : '').' per day.<br>'
			. 'Most active day was '.$high_date.' with a total of '.number_format($high_lines).' line'.($high_lines > 1 ? 's' : '').' typed.</div>'."\n";

		/**
		 * CONTENT
		 */
		$output .= '<div class="section">Activity</div>'."\n";
		$output .= $this->create_table_activity_distribution_hour();
		$output .= $this->create_table_activity('day');
		$output .= $this->create_table_activity('month');
		$output .= $this->create_table_activity('year');
		$output .= $this->create_table_activity_distribution_day();

		/**
		 * FOOT
		 */
		$output .= '<div class="info">Statistics created with <a href="http://sss.dutnie.nl">superseriousstats</a> on '.date('r').'.</div>'."\n";
		$output .= '</div></body>'."\n\n".'</html>'."\n";
		return $output;
	}

	/**
	 * Find the user $nick belongs to and create a stats page for it.
	 */
	private function main(string $nick): void
	{
		/**
		 * Open the database connection and update our settings.
		 */
		db::connect();
		$this->apply_settings(['timezone', 'channel', 'userpics', 'userpics_default', 'userpics_dir', 'stylesheet', 'main_page']);
		out::set_stylesheet($this->stylesheet);

		/**
		 * Set the proper timezone.
		 */
		date_default_timezone_set($this->timezone) or out::put('critical', 'invalid timezone: \''.$this->timezone.'\'');
		$this->now = date('Y-m-d');

		/**
		 * Do some input validation. Make sure the nick doesn't contain any invalid
		 * characters and doesn't exceed 64 characters in length.
		 */
		if (mb_strlen($nick) > 64 || !preg_match('/^([\x00-\x7F]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})+$/', $nick) || preg_match('/\p{C}+/u', $nick) || preg_match('/^[0-9-]|[\x20-\x2C\x2E\x2F\x3A-\x40\x7E]|\xC2\xA0|\xE2\x80[\xA8\xA9]/', $nick) || is_null($result = db::query_single_row('SELECT csnick, ruid FROM uid_details WHERE uid = (SELECT ruid FROM uid_details WHERE csnick = \''.preg_replace('/\'/', '\'\'', $nick).'\')'))) {
			out::put('critical', 'Nonexistent and/or erroneous nickname.');
		}

		$this->csnick = $result['csnick'];
		$this->ruid = $result['ruid'];

		/**
		 * Stats require a non-empty dataset.
		 */
		if (($this->l_total = db::query_single_col('SELECT l_total FROM ruid_lines WHERE ruid = '.$this->ruid) ?? 0) === 0) {
			out::put('critical', $this->htmlify($this->csnick).' is a filthy lurker!');
		}

		echo $this->get_contents();
		db::disconnect();
	}
}

/**
 * Make stats!
 */
$user = new user($_GET['nick']);
