<?php declare(strict_types=1);

/**
 * Copyright (c) 2010-2020, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Include shared code.
 */
require 'common.php';
require 'common_user_history.php';
require 'common_html_user_history.php';
require 'common_html_history.php';

/**
 * Class for creating historical stats.
 */
class history
{
	use common, common_html_user_history, common_html_history;

	private bool $link_history_php = false; /* Don't update via config settings. */
	private bool $link_user_php = true;
	private ?int $month = null;
	private int $year = 0;
	private string $channel = 'unconfigured';
	private string $main_page = './';
	private string $now = '';
	private string $stylesheet = 'sss.css';
	private string $timezone = '';

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
		$this->apply_settings(['timezone', 'channel', 'stylesheet', 'main_page', 'link_user_php']);
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
	 * Create an index with clickable links to all years and months in which there
	 * was activity.
	 */
	private function create_index(): string
	{
		/**
		 * Retrieve all activity and arrange data in a usable format.
		 */
		$results = db::query('SELECT SUBSTR(date, 1, 4) AS year, CAST(SUBSTR(date, 6, 2) AS INTEGER) AS month, SUM(l_total) AS l_total FROM channel_activity GROUP BY year, month ORDER BY date ASC');

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			$lines[(int) $result['year']][$result['month']] = $result['l_total'];
		}

		$tr0 = '<colgroup><col class="pos"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c"><col class="c">';
		$tr1 = '<tr><th colspan="13">History';
		$tr2 = '<tr><td class="pos"><td class="k">Jan<td class="k">Feb<td class="k">Mar<td class="k">Apr<td class="k">May<td class="k">Jun<td class="k">Jul<td class="k">Aug<td class="k">Sep<td class="k">Oct<td class="k">Nov<td class="k">Dec';
		$trx = '';

		/**
		 * Construct a line with activity numbers per month for each year since the
		 * date of the first log parsed. Months with no activity will show "n/a".
		 */
		for ($year = (int) db::query_single_col('SELECT MIN(SUBSTR(date, 1, 4)) FROM parse_history'); $year <= (int) db::query_single_col('SELECT MAX(SUBSTR(date, 1, 4)) FROM parse_history'); ++$year) {
			if (array_key_exists($year, $lines)) {
				$trx .= '<tr><td class="pos"><a href="history.php?year='.$year.'">'.$year.'</a>';

				for ($month = 1; $month <= 12; ++$month) {
					if (array_key_exists($month, $lines[$year])) {
						$trx .= '<td class="v"><a href="history.php?year='.$year.'&amp;month='.$month.'">'.number_format($lines[$year][$month]).'</a>';
					} else {
						$trx .= '<td class="v"><span class="grey">n/a</span>';
					}
				}
			} else {
				$trx .= '<tr><td class="pos">'.$year.'<td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span><td class="v"><span class="grey">n/a</span>';
			}
		}

		return '<table class="index">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}

	/**
	 * Generate the HTML page.
	 */
	private function get_contents(): string
	{
		/**
		 * HEAD
		 */
		$contents = '<!DOCTYPE html>'."\n\n"
			. '<html>'."\n\n"
			. '<head>'."\n"
			. '<meta charset="utf-8">'."\n"
			. '<title>'.$this->htmlify($this->channel).', historically.</title>'."\n"
			. '<link rel="stylesheet" href="'.$this->htmlify($this->stylesheet).'">'."\n"
			. '</head>'."\n\n"
			. '<body><div id="container">'."\n"
			. '<div class="info"><a href="'.$this->htmlify($this->main_page).'">'.$this->htmlify($this->channel).'</a>, historically.<br><br>'
			. 'Displaying statistics for '.(!is_null($this->month) ? date('F', strtotime($this->year.'-'.($this->month <= 9 ? '0' : '').$this->month.'-01')).' ' : '').$this->year.'</div>'."\n";

		/**
		 * CONTENT
		 */
		$contents .= '<div class="section">Activity</div>'."\n";
		$contents .= $this->create_index();
		$contents .= $this->create_table_activity_distribution_hour();
		$contents .= $this->create_table_people();
		$contents .= $this->create_table_people_timeofday();

		/**
		 * FOOT
		 */
		$contents .= '<div class="info">Statistics created with <a href="https://github.com/tommyrot/superseriousstats">superseriousstats</a> on '.date('r').'.</div>'."\n";
		$contents .= '</div></body>'."\n\n".'</html>'."\n";
		return $contents;
	}

	private function main(): void
	{
		/**
		 * Do some input validation. Both $year and $month default to the current date.
		 * $month may also be null.
		 */
		if (isset($_GET['year']) && preg_match('/^[12][0-9]{3}$/', $_GET['year'])) {
			$this->year = (int) $_GET['year'];

			if (isset($_GET['month']) && preg_match('/^([1-9]|1[0-2])$/', $_GET['month'])) {
				$this->month = (int) $_GET['month'];
			} else {
				$this->month = null;
			}
		} else {
			$this->year = (int) date('Y');
			$this->month = (int) date('n');
		}

		/**
		 * Stats require a non-empty dataset.
		 */
		if (db::query_single_col('SELECT COUNT(*) FROM channel_activity') === 0) {
			out::put('critical', 'There is not enough data to create statistics, yet.');
		}

		echo $this->get_contents();
	}
}

/**
 * Make stats!
 */
$history = new history();
