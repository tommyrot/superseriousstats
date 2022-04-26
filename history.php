<?php declare(strict_types=1);

/**
 * Copyright (c) 2010-2022, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Include shared code.
 */
require 'web.php';
require 'common.php';
require 'common_web.php';

/**
 * Class for creating historical stats.
 */
class history
{
	use common, common_web;

	private bool $link_user_php = true;
	private bool $show_banner = true;
	private bool $xxl = false;
	private ?int $month = null;
	private int $year = 0;
	private string $channel = 'unconfigured';
	private string $favicon = 'favicon.svg';
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
		$this->apply_vars('settings', ['timezone', 'channel', 'favicon', 'stylesheet', 'main_page', 'link_user_php', 'show_banner', 'xxl']);
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
		$results = db::query('SELECT SUBSTR(date, 1, 4) AS year, SUBSTR(date, 6, 2) AS month, SUM(l_total) AS l_total FROM channel_activity GROUP BY year, month ORDER BY date ASC');

		while ($result = $results->fetchArray(SQLITE3_ASSOC)) {
			$lines[(int) $result['year']][(int) $result['month']] = $result['l_total'];
		}

		$colgroup = '<colgroup>'.str_repeat('<col>', 13);
		$thead = '<thead><tr><th colspan="13">History';
		$thead .= '<tr><td><td>Jan<td>Feb<td>Mar<td>Apr<td>May<td>Jun<td>Jul<td>Aug<td>Sep<td>Oct<td>Nov<td>Dec';
		$tbody = '<tbody>';

		/**
		 * Assemble a line with activity numbers per month for each year since the date
		 * of the first log parsed. Months with no activity will show "n/a".
		 */
		for ($year = (int) substr(db::query_single_col('SELECT MIN(date) FROM parse_history'), 0, 4), $j = (int) substr(db::query_single_col('SELECT MAX(date) FROM parse_history'), 0, 4); $year <= $j; ++$year) {
			if (isset($lines[$year])) {
				$tbody .= '<tr><td><a href="history.php?year='.$year.'">'.$year.'</a>';

				for ($month = 1; $month <= 12; ++$month) {
					$tbody .= '<td>';

					if (isset($lines[$year][$month])) {
						$tbody .= '<a href="history.php?year='.$year.'&amp;month='.$month.'">'.number_format($lines[$year][$month]).'</a>';
					} else {
						$tbody .= '<span class="grey">n/a</span>';
					}
				}
			} else {
				$tbody .= '<tr><td>'.$year.str_repeat('<td><span class="grey">n/a</span>', 12);
			}
		}

		return '<table class="index">'.$colgroup.$thead.$tbody.'</table>'."\n";
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
			. '<html lang="en">'."\n\n"
			. '<head>'."\n"
			. '<meta charset="utf-8">'."\n"
			. '<title>'.$this->htmlify($this->channel).', historically.</title>'."\n"
			. '<link rel="icon" href="'.$this->htmlify($this->favicon).'">'."\n"
			. '<link rel="stylesheet" href="'.$this->htmlify($this->stylesheet).'">'."\n"
			. '</head>'."\n\n"
			. '<body><div id="container">'."\n"
			. ($this->show_banner ? '<div id="bannerbg-top"></div><div id="bannerbg-bottom"></div><svg id="banner" viewBox="0 0 818 62"><path style="fill:#8fce90" d="M459 50h-5V17h8v33z"/><path style="fill:#7697cb" d="M433 50h-5V25h8v25z"/><path style="fill:#89c686" d="M457 50h-3V17h4v33z"/><path style="fill:#e17677" d="M446 50h-5V33h8v17z"/><path style="fill:#999" d="M24 56H0v-2h46V33H0V6h48v4H2v21h46v25zm51 0H51V6h2v48h44V6h2v50zm28 0h-1V6h48v27h-46v23zm23-25h22V10h-44v21zm51 25h-24V6h48v27h-46v21h46v2zm0-25h22V10h-44v21zm28 25h-1V6h46v25h2v25h-2V33h-44v23zm22-25h21V10h-42v21zm52 25h-24v-2h46V33h-46V6h48v4h-46v21h46v25zm51 0h-24V6h48v27h-46v21h46v2zm0-25h22V10h-44v21zm28 25h-1V6h46v25h2v25h-2V33h-44v23zm22-25h21V10h-42v21zm33 25h-5v-2h4V10h-4V6h10v4h-4v44h4v2zm32 0h-24V6h48v50zm0-2h22V10h-44v44zm51 2h-24V6h2v48h44V6h2v50zm51 0h-24v-2h46V33h-46V6h48v4h-46v21h46v25zm51 0h-24v-2h46V33h-46V6h48v4h-46v21h46v25zm50 0h-1V10h-22V6h44v4h-20v46zm25 0h-1V6h48v50h-2V33h-44v23zm23-25h22V10h-44v21zm50 25h-1V10h-22V6h44v4h-20v46zm48 0h-24v-2h46V33h-46V6h48v4h-46v21h46v25z"/><path style="fill:#6c8fc2" d="M431 50h-3V25h4v25z"/><path style="fill:#dc6c6d" d="M444 50h-3V33h4v17z"/><path style="fill:#70a35e" d="M458 50h-4V17h8v33zm0-2h2V19h-4v29z"/><path style="fill:#c74243" d="M445 50h-4V33h8v17zm0-2h2V35h-4v13z"/><path style="fill:#416d9c" d="M432 50h-4V25h8v25zm0-2h2V27h-4v21z"/></svg>'."\n" : '')
			. '<div class="info"><a href="'.$this->htmlify($this->main_page).'">'.$this->htmlify($this->channel).'</a>, historically.<br><br>'
			. 'Displaying statistics for '.(!is_null($this->month) ? date('F', strtotime($this->year.'-'.($this->month <= 9 ? '0' : '').$this->month.'-01')).' ' : '').$this->year.'.</div>'."\n";

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
		$contents .= '<div class="info">Statistics created with <a href="https://sss.dutnie.nl">superseriousstats</a> on '.date('r').' <span class="grey">('.date('T').')</span>.</div>'."\n";
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
		if (db::query_single_col('SELECT EXISTS (SELECT 1 FROM channel_activity)') === 0) {
			out::put('critical', 'There is not enough data to create statistics, yet.');
		}

		echo $this->get_contents();
	}
}

/**
 * Make stats!
 */
$history = new history();
