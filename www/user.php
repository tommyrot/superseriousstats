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
 * Class for creating user stats.
 */
final class user
{
	/**
	 * Default settings for this script, which can be overridden in the configuration file.
	 */
	private $channel = '';
	private $database = 'sss.db3';
	private $mainpage = './';
	private $rankings = false;
	private $stylesheet = 'sss.css';
	private $timezone = 'UTC';
	private $userpics = false;
	private $userpics_default = '';
	private $userpics_dir = './userpics/';

	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $cid = '';
	private $color = array(
		'night' => 'b',
		'morning' => 'g',
		'afternoon' => 'y',
		'evening' => 'r');
	private $csnick = '';
	private $datetime = array();
	private $estimate = false;
	private $l_total = 0;
	private $nick = '';
	private $output = '';
	private $ruid = 0;

	public function __construct($cid, $nick)
	{
		$this->cid = $cid;
		$this->nick = $nick;

		/**
		 * Load settings from vars.php (contained in $settings[]).
		 */
		if ((include 'vars.php') === false) {
			$this->output('error', 'The configuration file could not be read.');
		}

		/**
		 * $cid is the channel ID used in vars.php and is passed along in the URL so that channel specific
		 * settings can be identified and loaded.
		 */
		if (empty($settings[$this->cid])) {
			$this->output('error', 'This channel has not been configured.');
		}

		foreach ($settings[$this->cid] as $key => $value) {
			$this->$key = $value;
		}

		date_default_timezone_set($this->timezone);

		/**
		 * Open the database connection.
		 */
		try {
			$sqlite3 = new SQLite3($this->database, SQLITE3_OPEN_READONLY);
			$sqlite3->busyTimeout(0);
		} catch (Exception $e) {
			$this->output(null, basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$e->getMessage());
		}

		$sqlite3->exec('PRAGMA temp_store = MEMORY');

		/**
		 * Make stats!
		 */
		echo $this->make_html($sqlite3);
		$sqlite3->close();
	}

	/**
	 * Look for an image in $userpics_dir that has an identical name to one of this user's aliases and return it.
	 */
	private function get_userpic($sqlite3)
	{
		/**
		 * If a default image is provided in the configuration file it will be returned if no specific image is
		 * found for the user. If omitted, null will be returned in said case.
		 */
		if (preg_match('/^\S+\.(bmp|gif|jpe?g|png)$/i', $this->userpics_default)) {
			$userpics_default = '<img src="'.htmlspecialchars(rtrim($this->userpics_dir, '/').'/'.$this->userpics_default).'" alt="" class="userpic">';
		} else {
			$userpics_default = null;
		}

		/**
		 * Try to open and read from $userpics_dir.
		 */
		if (is_dir($this->userpics_dir)) {
			if (($dh = opendir($this->userpics_dir)) === false) {
				return null;
			}

			while (($file = readdir($dh)) !== false) {
				if (preg_match('/^(?<name>\S+)\.(bmp|gif|jpe?g|png)$/i', $file, $matches)) {
					$files[strtolower($matches['name'])] = $file;
				}
			}

			closedir($dh);
		}

		if (empty($files)) {
			return $userpics_default;
		}

		/**
		 * Fetch this user's aliases.
		 */
		$query = $sqlite3->query('SELECT csnick FROM uid_details WHERE ruid = '.$this->ruid) or $this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$aliases[] = strtolower($result['csnick']);
		}

		/**
		 * Return the first image that matches any of this user's aliases.
		 */
		foreach ($files as $name => $file) {
			if (in_array($name, $aliases)) {
				return '<img src="'.htmlspecialchars(rtrim($this->userpics_dir, '/').'/'.$file).'" alt="" class="userpic">';
			}
		}

		return $userpics_default;
	}

	/**
	 * Generate the HTML page.
	 */
	private function make_html($sqlite3)
	{
		if (($this->ruid = $sqlite3->querySingle('SELECT ruid FROM uid_details WHERE csnick = \''.$sqlite3->escapeString($this->nick).'\'')) === false) {
			$this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		if (is_null($this->ruid)) {
			$this->output('error', 'This user does not exist.');
		}

		if (($result = $sqlite3->querySingle('SELECT (SELECT csnick FROM uid_details WHERE uid = '.$this->ruid.') AS csnick, MIN(firstseen) AS firstseen, MAX(lastseen) AS lastseen, l_total, l_total / activedays AS l_avg FROM uid_details JOIN ruid_lines ON uid_details.ruid = ruid_lines.ruid WHERE uid_details.ruid = '.$this->ruid.' AND firstseen != \'0000-00-00 00:00:00\'', true)) === false) {
			$this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		/**
		 * All queries from this point forward require a non empty database.
		 */
		if (empty($result['l_total'])) {
			$this->output('error', 'This user does not have any activity logged.');
		}

		$firstseen = $result['firstseen'];
		$l_avg = $result['l_avg'];
		$lastseen = $result['lastseen'];
		$this->csnick = $result['csnick'];
		$this->l_total = $result['l_total'];

		/**
		 * Fetch the users mood.
		 */
		if (($result = $sqlite3->querySingle('SELECT * FROM ruid_smileys WHERE ruid = '.$this->ruid, true)) === false) {
			$this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		if (empty($result)) {
			$mood = '';
		} else {
			$smileys = array(
				's_01' => ':)',
				's_02' => ';)',
				's_03' => ':(',
				's_04' => ':P',
				's_05' => ':D',
				's_06' => ';(',
				's_07' => ':/',
				's_08' => '\\o/',
				's_09' => ':))',
				's_10' => '<3',
				's_11' => ':o',
				's_12' => '=)',
				's_13' => ':-)',
				's_14' => ':x',
				's_15' => ':\\',
				's_16' => 'D:',
				's_17' => ':|',
				's_18' => ';-)',
				's_19' => ';P',
				's_20' => '=]',
				's_21' => ':3',
				's_22' => '8)',
				's_23' => ':<',
				's_24' => ':>',
				's_25' => '=P',
				's_26' => ';x',
				's_27' => ':-D',
				's_28' => ';))',
				's_29' => ':]',
				's_30' => ';D',
				's_31' => '-_-',
				's_32' => ':S',
				's_33' => '=/',
				's_34' => '=\\',
				's_35' => ':((',
				's_36' => '=D',
				's_37' => ':-/',
				's_38' => ':-P',
				's_39' => ';_;',
				's_40' => ';/',
				's_41' => ';]',
				's_42' => ':-(',
				's_43' => ':\'(',
				's_44' => '=(',
				's_45' => '-.-',
				's_46' => ';((',
				's_47' => '=X',
				's_48' => ':[',
				's_49' => '>:(',
				's_50' => ';o');
			arsort($result);

			foreach ($result as $key => $value) {
				if ($key !== 'ruid') {
					$mood = $smileys[$key];
					break;
				}
			}
		}

		if (($date_lastlogparsed = $sqlite3->querySingle('SELECT MAX(date) FROM parse_history')) === false) {
			$this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		/**
		 * Date and time variables used throughout the script. These are based on the date of the last logfile
		 * parsed, and are used to define our scope.
		 */
		$this->datetime['currentyear'] = (int) date('Y');
		$this->datetime['dayofmonth'] = (int) date('j', strtotime($date_lastlogparsed));
		$this->datetime['month'] = (int) date('n', strtotime($date_lastlogparsed));
		$this->datetime['year'] = (int) date('Y', strtotime($date_lastlogparsed));
		$this->datetime['years'] = $this->datetime['year'] - (int) date('Y', strtotime($firstseen)) + 1;
		$this->datetime['daysleft'] = (int) date('z', strtotime('last day of December '.$this->datetime['year'])) - (int) date('z', strtotime($date_lastlogparsed));

		/**
		 * Show a minimum of 3 columns in the Activity by Year table.
		 */
		if ($this->datetime['years'] < 3) {
			$this->datetime['years'] = 3;
		}

		/**
		 * If there are one or more days to come until the end of the year, display an additional column in the
		 * Activity by Year table with an estimated line count for the current year.
		 */
		if ($this->datetime['daysleft'] !== 0 && $this->datetime['year'] === $this->datetime['currentyear']) {
			/**
			 * Base the estimation on the activity in the last 90 days logged, if there is any.
			 */
			if (($activity = $sqlite3->querySingle('SELECT COUNT(*) FROM ruid_activity_by_day WHERE ruid = '.$this->ruid.' AND date > \''.date('Y-m-d', mktime(0, 0, 0, $this->datetime['month'], $this->datetime['dayofmonth'] - 90, $this->datetime['year'])).'\'')) === false) {
				$this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			if ($activity !== 0) {
				$this->estimate = true;
			}
		}

		/**
		 * HTML Head.
		 */
		if (($result = $sqlite3->querySingle('SELECT MIN(date) AS date, l_total FROM ruid_activity_by_day WHERE ruid = '.$this->ruid.' AND l_total = (SELECT MAX(l_total) FROM ruid_activity_by_day WHERE ruid = '.$this->ruid.')', true)) === false) {
			$this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$date_max = $result['date'];
		$l_max = $result['l_total'];
		$this->output = '<!DOCTYPE html>'."\n\n"
			. '<html>'."\n\n"
			. '<head>'."\n"
			. '<meta charset="utf-8">'."\n"
			. '<title>'.htmlspecialchars($this->csnick).', seriously.</title>'."\n"
			. '<link rel="stylesheet" href="'.$this->stylesheet.'">'."\n"
			. '<style type="text/css">'."\n"
			. '  .act-year { width:'.(2 + (($this->datetime['years'] + ($this->estimate ? 1 : 0)) * 34)).'px }'."\n"
			. '</style>'."\n"
			. '</head>'."\n\n"
			. '<body><div id="container">'."\n"
			. '<div class="info">'.($this->userpics ? $this->get_userpic($sqlite3) : '').htmlspecialchars($this->csnick).', seriously'.($mood !== '' ? ' '.htmlspecialchars($mood) : '.').'<br><br>'
			. 'First seen on '.date('M j, Y', strtotime($firstseen)).' and last seen on '.date('M j, Y', strtotime($lastseen)).'.<br><br>'
			. htmlspecialchars($this->csnick).' typed '.number_format($this->l_total).' line'.($this->l_total > 1 ? 's' : '').' on <a href="'.htmlspecialchars($this->mainpage).'">'.htmlspecialchars($this->channel).'</a> &ndash; an average of '.number_format($l_avg).' line'.($l_avg > 1 ? 's' : '').' per day.<br>'
			. 'Most active day was '.date('M j, Y', strtotime($date_max)).' with a total of '.number_format($l_max).' line'.($l_max > 1 ? 's' : '').' typed.</div>'."\n";

		/**
		 * Activity section.
		 */
		$this->output .= '<div class="section">Activity</div>'."\n";
		$this->output .= $this->make_table_activity_distribution_hour($sqlite3);
		$this->output .= $this->make_table_activity($sqlite3, 'day');
		$this->output .= $this->make_table_activity($sqlite3, 'month');
		$this->output .= $this->make_table_activity_distribution_day($sqlite3);
		$this->output .= $this->make_table_activity($sqlite3, 'year');

		/**
		 * Rankings section.
		 */
		if ($this->rankings) {
			$output = '';
			$output .= $this->make_table_rankings($sqlite3);

			if ($output !== '') {
				$this->output .= '<div class="section">Rankings</div>'."\n".$output;
			}
		}

		/**
		 * HTML Foot.
		 */
		$this->output .= '<div class="info">Statistics created with <a href="http://sss.dutnie.nl">superseriousstats</a> on '.date('r').'.</div>'."\n";
		$this->output .= '</div></body>'."\n\n".'</html>'."\n";
		return $this->output;
	}

	private function make_table_activity($sqlite3, $type)
	{
		if ($type === 'day') {
			$class = 'act';
			$columns = 24;
			$head = 'Activity by Day';
			$query = $sqlite3->query('SELECT date, l_total, l_night, l_morning, l_afternoon, l_evening FROM ruid_activity_by_day WHERE ruid = '.$this->ruid.' AND date > \''.date('Y-m-d', mktime(0, 0, 0, $this->datetime['month'], $this->datetime['dayofmonth'] - 24, $this->datetime['year'])).'\'') or $this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

			for ($i = $columns - 1; $i >= 0; $i--) {
				$dates[] = date('Y-m-d', mktime(0, 0, 0, $this->datetime['month'], $this->datetime['dayofmonth'] - $i, $this->datetime['year']));
			}
		} elseif ($type === 'month') {
			$class = 'act';
			$columns = 24;
			$head = 'Activity by Month';
			$query = $sqlite3->query('SELECT date, l_total, l_night, l_morning, l_afternoon, l_evening FROM ruid_activity_by_month WHERE ruid = '.$this->ruid.' AND date > \''.date('Y-m', mktime(0, 0, 0, $this->datetime['month'] - 24, 1, $this->datetime['year'])).'\'') or $this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

			for ($i = $columns - 1; $i >= 0; $i--) {
				$dates[] = date('Y-m', mktime(0, 0, 0, $this->datetime['month'] - $i, 1, $this->datetime['year']));
			}
		} elseif ($type === 'year') {
			$class = 'act-year';
			$columns = $this->datetime['years'];
			$head = 'Activity by Year';
			$query = $sqlite3->query('SELECT date, l_total, l_night, l_morning, l_afternoon, l_evening FROM ruid_activity_by_year WHERE ruid = '.$this->ruid) or $this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

			for ($i = $columns - 1; $i >= 0; $i--) {
				$dates[] = $this->datetime['year'] - $i;
			}

			if ($this->estimate) {
				$columns++;
				$dates[] = 'estimate';
			}
		}

		$result = $query->fetchArray(SQLITE3_ASSOC);

		if ($result === false) {
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
			if (($result = $sqlite3->querySingle('SELECT CAST(SUM(l_night) AS REAL) / 90 AS l_night_avg, CAST(SUM(l_morning) AS REAL) / 90 AS l_morning_avg, CAST(SUM(l_afternoon) AS REAL) / 90 AS l_afternoon_avg, CAST(SUM(l_evening) AS REAL) / 90 AS l_evening_avg, CAST(SUM(l_total) AS REAL) / 90 AS l_total_avg FROM ruid_activity_by_day WHERE ruid = '.$this->ruid.' AND date > \''.date('Y-m-d', mktime(0, 0, 0, $this->datetime['month'], $this->datetime['dayofmonth'] - 90, $this->datetime['year'])).'\'', true)) === false) {
				$this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}

			$l_afternoon['estimate'] = $l_afternoon[$this->datetime['currentyear']] + round($result['l_afternoon_avg'] * $this->datetime['daysleft']);
			$l_evening['estimate'] = $l_evening[$this->datetime['currentyear']] + round($result['l_evening_avg'] * $this->datetime['daysleft']);
			$l_morning['estimate'] = $l_morning[$this->datetime['currentyear']] + round($result['l_morning_avg'] * $this->datetime['daysleft']);
			$l_night['estimate'] = $l_night[$this->datetime['currentyear']] + round($result['l_night_avg'] * $this->datetime['daysleft']);
			$l_total['estimate'] = $l_total[$this->datetime['currentyear']] + round($result['l_total_avg'] * $this->datetime['daysleft']);

			if ($l_total['estimate'] > $high_value) {
				/**
				 * Don't set $high_date because we don't want "Est." to be bold. The previous highest
				 * date will be bold instead. $high_value must be set in order to calculate bar heights.
				 */
				$high_value = $l_total['estimate'];
			}
		}

		$times = array('evening', 'afternoon', 'morning', 'night');
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
					$total = round($l_total[$date] / 1000).'K';
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
		if (($result = $sqlite3->querySingle('SELECT l_mon_night, l_mon_morning, l_mon_afternoon, l_mon_evening, l_tue_night, l_tue_morning, l_tue_afternoon, l_tue_evening, l_wed_night, l_wed_morning, l_wed_afternoon, l_wed_evening, l_thu_night, l_thu_morning, l_thu_afternoon, l_thu_evening, l_fri_night, l_fri_morning, l_fri_afternoon, l_fri_evening, l_sat_night, l_sat_morning, l_sat_afternoon, l_sat_evening, l_sun_night, l_sun_morning, l_sun_afternoon, l_sun_evening FROM ruid_lines WHERE ruid = '.$this->ruid, true)) === false) {
			$this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$days = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun');
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

		$times = array('evening', 'afternoon', 'morning', 'night');
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
		if (($result = $sqlite3->querySingle('SELECT l_00, l_01, l_02, l_03, l_04, l_05, l_06, l_07, l_08, l_09, l_10, l_11, l_12, l_13, l_14, l_15, l_16, l_17, l_18, l_19, l_20, l_21, l_22, l_23 FROM ruid_lines WHERE ruid = '.$this->ruid, true)) === false) {
			$this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
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

	private function make_table_rankings($sqlite3)
	{
		if (($dayslogged = $sqlite3->querySingle('SELECT COUNT(*) FROM parse_history')) === false) {
			$this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		$query = $sqlite3->query('SELECT date, rank, l_total, percentage, l_avg, activity, l_max FROM ruid_rankings WHERE ruid = '.$this->ruid.' ORDER BY date ASC') or $this->output($sqlite3->lastErrorCode(), basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$result = $query->fetchArray(SQLITE3_ASSOC);

		if ($result === false) {
			return null;
		}

		$query->reset();
		$rankings = array();

		while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
			$prevdate = date('Y-m', mktime(0, 0, 0, (int) substr($result['date'], 5, 2) - 1, 1, (int) substr($result['date'], 0, 4)));

			if (array_key_exists($prevdate, $rankings)) {
				$rankings[$result['date']]['activity_delta'] = $result['activity'] - $rankings[$prevdate]['activity'];
				$rankings[$result['date']]['l_avg_delta'] = $result['l_avg'] - $rankings[$prevdate]['l_avg'];
				$rankings[$result['date']]['l_total_delta'] = $result['l_total'] - $rankings[$prevdate]['l_total'];
				$rankings[$result['date']]['percentage_delta'] = $result['percentage'] - $rankings[$prevdate]['percentage'];
				$rankings[$result['date']]['rank_delta'] = $rankings[$prevdate]['rank'] - $result['rank'];
			}

			$rankings[$result['date']]['activity'] = $result['activity'];
			$rankings[$result['date']]['l_avg'] = $result['l_avg'];
			$rankings[$result['date']]['l_max'] = $result['l_max'];
			$rankings[$result['date']]['l_total'] = $result['l_total'];
			$rankings[$result['date']]['percentage'] = $result['percentage'];
			$rankings[$result['date']]['rank'] = $result['rank'];
		}

		$tr0 = '<colgroup><col class="c1"><col class="c2"><col class="c3"><col class="c4"><col class="c5"><col class="c6"><col class="c7"><col class="c8"><col class="c9"><col class="c10"><col class="c11"><col class="c12">';
		$tr1 = '<tr><th colspan="12">Rankings';
		$tr2 = '<tr><td class="k1-2" colspan="2">Rank<td class="k3"><td class="k4-5" colspan="2">Lines<td class="k6-7" colspan="2">Percentage<td class="k8-9" colspan="2">Lines/Day<td class="k10-11" colspan="2">Activity<td class="k12">Top Day';
		$trx = '';
		krsort($rankings);

		foreach ($rankings as $date => $values) {
			$trx .= '<tr><td class="v1">'.$values['rank'].'<td class="v2">'.(empty($values['rank_delta']) ? '' : ($values['rank_delta'] < 0 ? '<span class="red">'.$values['rank_delta'].'</span>' : '<span class="green">+'.$values['rank_delta'].'</span>')).'<td class="v3">'.date('M Y', strtotime($date.'-01')).'<td class="v4">'.number_format($values['l_total']).'<td class="v5">'.(empty($values['l_total_delta']) ? '' : '<span class="green">+'.number_format($values['l_total_delta']).'</span>').'<td class="v6">'.number_format($values['percentage'], 2).'%<td class="v7">'.(empty($values['percentage_delta']) ? '' : ($values['percentage_delta'] < 0 ? '<span class="red">'.number_format($values['percentage_delta'], 2).'</span>' : '<span class="green">+'.number_format($values['percentage_delta'], 2).'</span>')).'<td class="v8">'.number_format($values['l_avg'], 1).'<td class="v9">'.(empty($values['l_avg_delta']) ? '' : ($values['l_avg_delta'] < 0 ? '<span class="red">'.number_format($values['l_avg_delta'], 1).'</span>' : '<span class="green">+'.number_format($values['l_avg_delta'], 1).'</span>')).'<td class="v10">'.number_format($values['activity'], 2).'%<td class="v11">'.(empty($values['activity_delta']) ? '' : ($values['activity_delta'] < 0 ? '<span class="red">'.number_format($values['activity_delta'], 2).'</span>' : '<span class="green">+'.number_format($values['activity_delta'], 2).'</span>')).'<td class="v12">'.($values['l_max'] === 0 ? '<span class="grey">n/a</span>' : number_format($values['l_max']));
		}

		return '<table class="rank">'.$tr0.$tr1.$tr2.$trx.'</table>'."\n";
	}

	/**
	 * For compatibility reasons this function has the same name as the original version in the base class and
	 * accepts the same arguments. Its functionality is slightly different in that it exits on any type of message
	 * passed to it. SQLite3 result code 5 = SQLITE_BUSY, result code 6 = SQLITE_LOCKED.
	 */
	private function output($code, $msg)
	{
		if ($code === 5 || $code === 6) {
			$msg = 'Statistics are currently being updated, this may take a minute.';
		}

		exit('<!DOCTYPE html>'."\n\n".'<html><head><meta charset="utf-8"><title>seriously?</title><link rel="stylesheet" href="sss.css"></head><body><div id="container"><div class="error">'.htmlspecialchars($msg).'</div></div></body></html>'."\n");
	}
}

/**
 * The channel ID must be set, cannot be empty and cannot be of excessive length.
 */
if (empty($_GET['cid']) || !preg_match('/^\S{1,32}$/', $_GET['cid'])) {
	exit;
}

$cid = $_GET['cid'];

/**
 * Exit if the nick is not set or has an erroneous value.
 */
if (empty($_GET['nick']) || !preg_match('/^[][^{}|\\\`_0-9a-z-]{1,32}$/i', $_GET['nick'])) {
	exit;
}

$nick = $_GET['nick'];

/**
 * Make stats!
 */
$user = new user($cid, $nick);

?>
