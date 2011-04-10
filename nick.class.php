<?php

/**
 * Copyright (c) 2007-2011, Jos de Ruijter <jos@dutnie.nl>
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
 * Class for handling user data.
 */
final class nick extends base
{
	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $long_ex_actions_list = array();
	private $long_ex_exclamations_list = array();
	private $long_ex_questions_list = array();
	private $long_ex_uppercased_list = array();
	private $long_quote_list = array();
	private $short_ex_actions_list = array();
	private $short_ex_exclamations_list = array();
	private $short_ex_questions_list = array();
	private $short_ex_uppercased_list = array();
	private $short_quote_list = array();
	private $topics_list = array();
	private $uid = 0;
	private $urls_objs = array();
	protected $actions = 0;
	protected $activedays = 0;
	protected $characters = 0;
	protected $csnick = '';
	protected $date = '';
	protected $ex_actions = '';
	protected $ex_exclamations = '';
	protected $ex_kicked = '';
	protected $ex_kicks = '';
	protected $ex_questions = '';
	protected $ex_uppercased = '';
	protected $exclamations = 0;
	protected $firstseen = '';
	protected $joins = 0;
	protected $kicked = 0;
	protected $kicks = 0;
	protected $l_00 = 0;
	protected $l_01 = 0;
	protected $l_02 = 0;
	protected $l_03 = 0;
	protected $l_04 = 0;
	protected $l_05 = 0;
	protected $l_06 = 0;
	protected $l_07 = 0;
	protected $l_08 = 0;
	protected $l_09 = 0;
	protected $l_10 = 0;
	protected $l_11 = 0;
	protected $l_12 = 0;
	protected $l_13 = 0;
	protected $l_14 = 0;
	protected $l_15 = 0;
	protected $l_16 = 0;
	protected $l_17 = 0;
	protected $l_18 = 0;
	protected $l_19 = 0;
	protected $l_20 = 0;
	protected $l_21 = 0;
	protected $l_22 = 0;
	protected $l_23 = 0;
	protected $l_afternoon = 0;
	protected $l_evening = 0;
	protected $l_fri_afternoon = 0;
	protected $l_fri_evening = 0;
	protected $l_fri_morning = 0;
	protected $l_fri_night = 0;
	protected $l_mon_afternoon = 0;
	protected $l_mon_evening = 0;
	protected $l_mon_morning = 0;
	protected $l_mon_night = 0;
	protected $l_morning = 0;
	protected $l_night = 0;
	protected $l_sat_afternoon = 0;
	protected $l_sat_evening = 0;
	protected $l_sat_morning = 0;
	protected $l_sat_night = 0;
	protected $l_sun_afternoon = 0;
	protected $l_sun_evening = 0;
	protected $l_sun_morning = 0;
	protected $l_sun_night = 0;
	protected $l_thu_afternoon = 0;
	protected $l_thu_evening = 0;
	protected $l_thu_morning = 0;
	protected $l_thu_night = 0;
	protected $l_total = 0;
	protected $l_tue_afternoon = 0;
	protected $l_tue_evening = 0;
	protected $l_tue_morning = 0;
	protected $l_tue_night = 0;
	protected $l_wed_afternoon = 0;
	protected $l_wed_evening = 0;
	protected $l_wed_morning = 0;
	protected $l_wed_night = 0;
	protected $lastseen = '';
	protected $lasttalked = '';
	protected $m_deop = 0;
	protected $m_deopped = 0;
	protected $m_devoice = 0;
	protected $m_devoiced = 0;
	protected $m_op = 0;
	protected $m_opped = 0;
	protected $m_voice = 0;
	protected $m_voiced = 0;
	protected $monologues = 0;
	protected $mysqli;
	protected $nickchanges = 0;
	protected $parts = 0;
	protected $questions = 0;
	protected $quits = 0;
	protected $quote = '';
	protected $s_01 = 0;
	protected $s_02 = 0;
	protected $s_03 = 0;
	protected $s_04 = 0;
	protected $s_05 = 0;
	protected $s_06 = 0;
	protected $s_07 = 0;
	protected $s_08 = 0;
	protected $s_09 = 0;
	protected $s_10 = 0;
	protected $s_11 = 0;
	protected $s_12 = 0;
	protected $s_13 = 0;
	protected $s_14 = 0;
	protected $s_15 = 0;
	protected $s_16 = 0;
	protected $s_17 = 0;
	protected $s_18 = 0;
	protected $s_19 = 0;
	protected $slapped = 0;
	protected $slaps = 0;
	protected $topics = 0;
	protected $topmonologue = 0;
	protected $uppercased = 0;
	protected $urls = 0;
	protected $words = 0;

	public function __construct($csnick)
	{
		parent::__construct();
		$this->csnick = $csnick;
	}

	/**
	 * Keep a list of long quotes and a list of short quotes of $type.
	 */
	public function add_quote($type, $length, $line)
	{
		$this->{$length.'_'.$type.'_list'}[] = $line;
	}

	public function add_topic($topic, $datetime)
	{
		$this->topics_list[] = array(
			'topic' => $topic,
			'setdate' => $datetime);
	}

	public function add_url($urldata, $datetime)
	{
		$url = strtolower($urldata['url']);

		if (!array_key_exists($url, $this->urls_objs)) {
			$this->urls_objs[$url] = new url($urldata);
		} else {
			/**
			 * The last used case will be stored for a URL.
			 * E.g. "www.example.com/file.txt" could be corrected in a followup by "www.example.com/File.txt".
			 */
			$this->urls_objs[$url]->set_value('url', $urldata['url']);
		}

		$this->urls_objs[$url]->add_value('total', 1);
		$this->urls_objs[$url]->set_lastused($datetime);
	}

	public function set_lastseen($datetime)
	{
		if ($this->firstseen == '' || strtotime($datetime) < strtotime($this->firstseen)) {
			$this->firstseen = $datetime;
		}

		if ($this->lastseen == '' || strtotime($datetime) > strtotime($this->lastseen)) {
			$this->lastseen = $datetime;
		}
	}

	/**
	 * $datetime of last "normal" line.
	 */
	public function set_lasttalked($datetime)
	{
		if ($this->lasttalked == '' || strtotime($datetime) > strtotime($this->lasttalked)) {
			$this->lasttalked = $datetime;
		}
	}

	public function write_data($mysqli)
	{
		$this->mysqli = $mysqli;

		/**
		 * Write data to database tables "user_details" and "user_status".
		 */
		$query = @mysqli_query($this->mysqli, 'select * from `user_details` where `csnick` = \''.mysqli_real_escape_string($this->mysqli, $this->csnick).'\'') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		$rows = mysqli_num_rows($query);

		if (empty($rows)) {
			$insertquery = $this->create_insert_query(array('csnick', 'firstseen', 'lastseen'));
			@mysqli_query($this->mysqli, 'insert into `user_details` set `uid` = 0,'.$insertquery) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$this->uid = mysqli_insert_id($this->mysqli);
			@mysqli_query($this->mysqli, 'insert into `user_status` set `uid` = '.$this->uid.', `ruid` = '.$this->uid.', `status` = 0') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
		} else {
			$result = mysqli_fetch_object($query);
			$this->uid = $result->uid;

			/**
			 * Explicitly not update $csnick if "seen" data hasn't changed. Prevents lowercase $prevnick from becoming new $csnick.
			 */
			$updatequery = $this->create_update_query($result, array('uid', 'csnick'));

			if (!is_null($updatequery)) {
				@mysqli_query($this->mysqli, 'update `user_details` set `csnick` = \''.mysqli_real_escape_string($this->mysqli, $this->csnick).'\','.$updatequery.' where `uid` = '.$this->uid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			}
		}

		/**
		 * Write data to database table "user_activity".
		 */
		if ($this->l_total != 0) {
			$query = @mysqli_query($this->mysqli, 'select * from `user_activity` where `uid` = '.$this->uid.' and `date` = \''.mysqli_real_escape_string($this->mysqli, $this->date).'\'') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query);

			if (empty($rows)) {
				$insertquery = $this->create_insert_query(array('l_night', 'l_morning', 'l_afternoon', 'l_evening', 'l_total'));
				@mysqli_query($this->mysqli, 'insert into `user_activity` set `uid` = '.$this->uid.', `date` = \''.mysqli_real_escape_string($this->mysqli, $this->date).'\','.$insertquery) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			} else {
				$result = mysqli_fetch_object($query);
				$updatequery = $this->create_update_query($result, array('uid', 'date'));

				if (!is_null($updatequery)) {
					@mysqli_query($this->mysqli, 'update `user_activity` set'.$updatequery.' where `uid` = '.$this->uid.' and `date` = \''.mysqli_real_escape_string($this->mysqli, $this->date).'\'') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				}
			}
		}

		/**
		 * Write data to database table "user_events".
		 */
		$insertquery = $this->create_insert_query(array('m_op', 'm_opped', 'm_voice', 'm_voiced', 'm_deop', 'm_deopped', 'm_devoice', 'm_devoiced', 'joins', 'parts', 'quits', 'kicks', 'kicked', 'nickchanges', 'topics', 'ex_kicks', 'ex_kicked'));

		if (!is_null($insertquery)) {
			$query = @mysqli_query($this->mysqli, 'select * from `user_events` where `uid` = '.$this->uid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query);

			if (empty($rows)) {
				@mysqli_query($this->mysqli, 'insert into `user_events` set `uid` = '.$this->uid.','.$insertquery) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			} else {
				$result = mysqli_fetch_object($query);
				$updatequery = $this->create_update_query($result, array('uid'));

				if (!is_null($updatequery)) {
					@mysqli_query($this->mysqli, 'update `user_events` set'.$updatequery.' where `uid` = '.$this->uid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				}
			}
		}

		/**
		 * Pick a random line from either the list of long quotes or, when there are no long quotes, from the list of short quotes.
		 * Long quotes are preferred since these look better on the statspage and give away more about the subject.
		 */
		$types = array('ex_actions', 'ex_exclamations', 'ex_questions', 'ex_uppercased', 'quote');

		foreach ($types as $type) {
			if (!empty($this->{'long_'.$type.'_list'})) {
				$this->$type = $this->{'long_'.$type.'_list'}[mt_rand(0, count($this->{'long_'.$type.'_list'}) - 1)];
			} elseif (!empty($this->{'short_'.$type.'_list'})) {
				$this->$type = $this->{'short_'.$type.'_list'}[mt_rand(0, count($this->{'short_'.$type.'_list'}) - 1)];
			}
		}

		/**
		 * Write data to database table "user_lines".
		 */
		$insertquery = $this->create_insert_query(array('l_00', 'l_01', 'l_02', 'l_03', 'l_04', 'l_05', 'l_06', 'l_07', 'l_08', 'l_09', 'l_10', 'l_11', 'l_12', 'l_13', 'l_14', 'l_15', 'l_16', 'l_17', 'l_18', 'l_19', 'l_20', 'l_21', 'l_22', 'l_23', 'l_night', 'l_morning', 'l_afternoon', 'l_evening', 'l_total', 'l_mon_night', 'l_mon_morning', 'l_mon_afternoon', 'l_mon_evening', 'l_tue_night', 'l_tue_morning', 'l_tue_afternoon', 'l_tue_evening', 'l_wed_night', 'l_wed_morning', 'l_wed_afternoon', 'l_wed_evening', 'l_thu_night', 'l_thu_morning', 'l_thu_afternoon', 'l_thu_evening', 'l_fri_night', 'l_fri_morning', 'l_fri_afternoon', 'l_fri_evening', 'l_sat_night', 'l_sat_morning', 'l_sat_afternoon', 'l_sat_evening', 'l_sun_night', 'l_sun_morning', 'l_sun_afternoon', 'l_sun_evening', 'urls', 'words', 'characters', 'monologues', 'topmonologue', 'activedays', 'slaps', 'slapped', 'exclamations', 'questions', 'actions', 'uppercased', 'quote', 'ex_exclamations', 'ex_questions', 'ex_actions', 'ex_uppercased', 'lasttalked'));

		if (!is_null($insertquery)) {
			$query = @mysqli_query($this->mysqli, 'select * from `user_lines` where `uid` = '.$this->uid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query);

			if (empty($rows)) {
				@mysqli_query($this->mysqli, 'insert into `user_lines` set `uid` = '.$this->uid.','.$insertquery) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			} else {
				$result = mysqli_fetch_object($query);
				$updatequery = $this->create_update_query($result, array('uid'));

				if (!is_null($updatequery)) {
					@mysqli_query($this->mysqli, 'update `user_lines` set'.$updatequery.' where `uid` = '.$this->uid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				}
			}
		}

		/**
		 * Write data to database table "user_smileys".
		 */
		$insertquery = $this->create_insert_query(array('s_01', 's_02', 's_03', 's_04', 's_05', 's_06', 's_07', 's_08', 's_09', 's_10', 's_11', 's_12', 's_13', 's_14', 's_15', 's_16', 's_17', 's_18', 's_19'));

		if (!is_null($insertquery)) {
			$query = @mysqli_query($this->mysqli, 'select * from `user_smileys` where `uid` = '.$this->uid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query);

			if (empty($rows)) {
				@mysqli_query($this->mysqli, 'insert into `user_smileys` set `uid` = '.$this->uid.','.$insertquery) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			} else {
				$result = mysqli_fetch_object($query);
				$updatequery = $this->create_update_query($result, array('uid'));

				if (!is_null($updatequery)) {
					@mysqli_query($this->mysqli, 'update `user_smileys` set'.$updatequery.' where `uid` = '.$this->uid) or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				}
			}
		}

		/**
		 * Write data to database table "user_topics".
		 */
		foreach ($this->topics_list as $topic) {
			$query = @mysqli_query($this->mysqli, 'select `tid` from `user_topics` where `topic` = \''.mysqli_real_escape_string($this->mysqli, $topic['topic']).'\' group by `topic`') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			$rows = mysqli_num_rows($query);

			if (empty($rows)) {
				@mysqli_query($this->mysqli, 'insert into `user_topics` set `tid` = 0, `uid` = '.$this->uid.', `topic` = \''.mysqli_real_escape_string($this->mysqli, $topic['topic']).'\', `setdate` = \''.mysqli_real_escape_string($this->mysqli, $topic['setdate']).'\'') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
			} else {
				$result = mysqli_fetch_object($query);
				$query = @mysqli_query($this->mysqli, 'select * from `user_topics` where `tid` = '.$result->tid.' and `uid` = '.$this->uid.' and `setdate` = \''.mysqli_real_escape_string($this->mysqli, $topic['setdate']).'\'') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				$rows = mysqli_num_rows($query);

				if (empty($rows)) {
					@mysqli_query($this->mysqli, 'insert into `user_topics` set `tid` = '.$result->tid.', `uid` = '.$this->uid.', `topic` = \''.mysqli_real_escape_string($this->mysqli, $topic['topic']).'\', `setdate` = \''.mysqli_real_escape_string($this->mysqli, $topic['setdate']).'\'') or $this->output('critical', 'mysqli: '.mysqli_error($this->mysqli));
				}
			}
		}

		/**
		 * Write data to database table "user_urls".
		 */
		foreach ($this->urls_objs as $url) {
			$url->write_data($this->mysqli, $this->uid);
		}
	}
}

?>
