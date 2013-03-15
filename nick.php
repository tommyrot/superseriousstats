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
 * Class for handling user data.
 */
final class nick extends base
{
	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $ex_actions_stack = array();
	private $ex_exclamations_stack = array();
	private $ex_questions_stack = array();
	private $ex_uppercased_stack = array();
	private $quote_stack = array();
	private $topics_objs = array();
	private $urls_objs = array();
	protected $actions = 0;
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
	protected $s_20 = 0;
	protected $s_21 = 0;
	protected $s_22 = 0;
	protected $s_23 = 0;
	protected $s_24 = 0;
	protected $s_25 = 0;
	protected $s_26 = 0;
	protected $s_27 = 0;
	protected $s_28 = 0;
	protected $s_29 = 0;
	protected $s_30 = 0;
	protected $s_31 = 0;
	protected $s_32 = 0;
	protected $s_33 = 0;
	protected $s_34 = 0;
	protected $s_35 = 0;
	protected $s_36 = 0;
	protected $s_37 = 0;
	protected $s_38 = 0;
	protected $s_39 = 0;
	protected $s_40 = 0;
	protected $s_41 = 0;
	protected $s_42 = 0;
	protected $s_43 = 0;
	protected $s_44 = 0;
	protected $s_45 = 0;
	protected $s_46 = 0;
	protected $s_47 = 0;
	protected $s_48 = 0;
	protected $s_49 = 0;
	protected $s_50 = 0;
	protected $slapped = 0;
	protected $slaps = 0;
	protected $topics = 0;
	protected $topmonologue = 0;
	protected $uppercased = 0;
	protected $urls = 0;
	protected $words = 0;

	public function __construct($csnick)
	{
		$this->csnick = $csnick;
	}

	/**
	 * Keep a stack of the 100 most recent quotes of each type along with their lengths.
	 */
	public function add_quote($type, $line, $length)
	{
		$this->{$type.'_stack'}[] = array(
			'length' => $length,
			'line' => $line);

		if (count($this->{$type.'_stack'}) > 100) {
			/**
			 * Shift the first (oldest) entry off the stack.
			 */
			array_shift($this->{$type.'_stack'});
		}
	}

	/**
	 * Keep track of every single topic set. These are handled (and stored) while preserving case.
	 */
	public function add_topic($topic, $datetime)
	{
		if (!array_key_exists($topic, $this->topics_objs)) {
			$this->topics_objs[$topic] = new topic($topic);
		}

		$this->topics_objs[$topic]->add_datetime($datetime);
	}

	/**
	 * We keep track of every single URL. These are handled (and stored) while preserving case.
	 */
	public function add_url($urldata, $datetime)
	{
		$url = $urldata['url'];

		if (!array_key_exists($url, $this->urls_objs)) {
			$this->urls_objs[$url] = new url($urldata);
		}

		$this->urls_objs[$url]->add_datetime($datetime);
	}

	public function write_data($sqlite3)
	{
		/**
		 * Write data to database tables "user_details" and "user_status".
		 */
		$query = @$sqlite3->query('SELECT uid, firstseen FROM user_details WHERE csnick = \''.$sqlite3->escapeString($this->csnick).'\'') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		$result = $query->fetchArray(SQLITE3_ASSOC);

		if (empty($result)) {
			@$sqlite3->exec('INSERT INTO user_details (uid, csnick'.($this->firstseen != '' ? ', firstseen, lastseen' : '').') VALUES (NULL, \''.$sqlite3->escapeString($this->csnick).'\''.($this->firstseen != '' ? ', DATETIME(\''.$this->firstseen.'\'), DATETIME(\''.$this->lastseen.'\')' : '').')') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$uid = $sqlite3->lastInsertRowID();
			@$sqlite3->exec('INSERT INTO user_status (uid, ruid) VALUES ('.$uid.', '.$uid.')') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		} else {
			$uid = $result['uid'];

			/**
			 * Only update $firstseen if the value stored in the database is zero. We're parsing logs in chronological order so the stored value of
			 * $firstseen can never be lower and the value of $lastseen can never be higher than the parsed values. (We are not going out of our way
			 * to deal with possible DST nonsense.) Secondly, only update $csnick if the nick was seen. We want to avoid it from being overwritten
			 * by a lowercase $prevnick (streak code) or weirdly cased nick due to a slap.
			 */
			if ($this->firstseen != '') {
				@$sqlite3->exec('UPDATE user_details SET csnick = \''.$sqlite3->escapeString($this->csnick).'\''.($result['firstseen'] == '0000-00-00 00:00:00' ? ', firstseen = DATETIME(\''.$this->firstseen.'\')' : '').', lastseen = DATETIME(\''.$this->lastseen.'\') WHERE uid = '.$uid) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			}
		}

		/**
		 * Write data to database table "user_activity".
		 */
		if ($this->l_total != 0) {
			$queryparts = $this->get_queryparts($sqlite3, array('l_night', 'l_morning', 'l_afternoon', 'l_evening', 'l_total'));
			@$sqlite3->exec('INSERT OR IGNORE INTO user_activity (uid, date, '.implode(', ', $queryparts['columnlist']).') VALUES ('.$uid.', \''.$this->date.'\', '.implode(', ', $queryparts['values']).')') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			@$sqlite3->exec('UPDATE user_activity SET '.implode(', ', $queryparts['update-assignments']).' WHERE CHANGES() = 0 AND uid = '.$uid.' AND date = \''.$this->date.'\'') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		/**
		 * Write data to database table "user_events".
		 */
		$queryparts = $this->get_queryparts($sqlite3, array('m_op', 'm_opped', 'm_voice', 'm_voiced', 'm_deop', 'm_deopped', 'm_devoice', 'm_devoiced', 'joins', 'parts', 'quits', 'kicks', 'kicked', 'nickchanges', 'topics', 'ex_kicks', 'ex_kicked'));

		if (!empty($queryparts)) {
			@$sqlite3->exec('INSERT OR IGNORE INTO user_events (uid, '.implode(', ', $queryparts['columnlist']).') VALUES ('.$uid.', '.implode(', ', $queryparts['values']).')') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			@$sqlite3->exec('UPDATE user_events SET '.implode(', ', $queryparts['update-assignments']).' WHERE CHANGES() = 0 AND uid = '.$uid) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		/**
		 * Pick a random line from each of the quote stacks. Long quotes are preferred since these look better on the statspage and give away more about
		 * the subject.
		 */
		$types = array('ex_actions', 'ex_exclamations', 'ex_questions', 'ex_uppercased', 'quote');

		foreach ($types as $type) {
			if (!empty($this->{$type.'_stack'})) {
				rsort($this->{$type.'_stack'});
				$this->$type = $this->{$type.'_stack'}[mt_rand(0, ceil(count($this->{$type.'_stack'}) / 2) - 1)]['line'];
			}
		}

		/**
		 * Write data to database table "user_lines". Special care taken for $lasttalked because get_queryparts() won't do it for us.
		 */
		$queryparts = $this->get_queryparts($sqlite3, array('l_00', 'l_01', 'l_02', 'l_03', 'l_04', 'l_05', 'l_06', 'l_07', 'l_08', 'l_09', 'l_10', 'l_11', 'l_12', 'l_13', 'l_14', 'l_15', 'l_16', 'l_17', 'l_18', 'l_19', 'l_20', 'l_21', 'l_22', 'l_23', 'l_night', 'l_morning', 'l_afternoon', 'l_evening', 'l_total', 'l_mon_night', 'l_mon_morning', 'l_mon_afternoon', 'l_mon_evening', 'l_tue_night', 'l_tue_morning', 'l_tue_afternoon', 'l_tue_evening', 'l_wed_night', 'l_wed_morning', 'l_wed_afternoon', 'l_wed_evening', 'l_thu_night', 'l_thu_morning', 'l_thu_afternoon', 'l_thu_evening', 'l_fri_night', 'l_fri_morning', 'l_fri_afternoon', 'l_fri_evening', 'l_sat_night', 'l_sat_morning', 'l_sat_afternoon', 'l_sat_evening', 'l_sun_night', 'l_sun_morning', 'l_sun_afternoon', 'l_sun_evening', 'urls', 'words', 'characters', 'monologues', 'slaps', 'slapped', 'exclamations', 'questions', 'actions', 'uppercased', 'quote', 'ex_exclamations', 'ex_questions', 'ex_actions', 'ex_uppercased'));

		if (!empty($queryparts)) {
			@$sqlite3->exec('INSERT OR IGNORE INTO user_lines (uid, '.implode(', ', $queryparts['columnlist']).($this->lasttalked != '' ? ', lasttalked' : '').') VALUES ('.$uid.', '.implode(', ', $queryparts['values']).($this->lasttalked != '' ? ', DATETIME(\''.$this->lasttalked.'\')' : '').')') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			@$sqlite3->exec('UPDATE user_lines SET '.implode(', ', $queryparts['update-assignments']).($this->lasttalked != '' ? ', lasttalked = DATETIME(\''.$this->lasttalked.'\')' : '').' WHERE CHANGES() = 0 AND uid = '.$uid) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

			/**
			 * Update $topmonologue separately as we want to keep the highest value instead of the sum. Note that $createdquery can't be null when
			 * $topmonologue is non zero because, at the very least, $monologues will have a value of 1.
			 */
			if ($this->topmonologue != 0) {
				$topmonologue = @$sqlite3->querySingle('SELECT topmonologue FROM user_lines WHERE uid = '.$uid) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

				if ($this->topmonologue > $topmonologue) {
					@$sqlite3->exec('UPDATE user_lines SET topmonologue = '.$this->topmonologue.' WHERE uid = '.$uid) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
				}
			}
		}

		/**
		 * Write data to database table "user_smileys".
		 */
		$queryparts = $this->get_queryparts($sqlite3, array('s_01', 's_02', 's_03', 's_04', 's_05', 's_06', 's_07', 's_08', 's_09', 's_10', 's_11', 's_12', 's_13', 's_14', 's_15', 's_16', 's_17', 's_18', 's_19', 's_20', 's_21', 's_22', 's_23', 's_24', 's_25', 's_26', 's_27', 's_28', 's_29', 's_30', 's_31', 's_32', 's_33', 's_34', 's_35', 's_36', 's_37', 's_38', 's_39', 's_40', 's_41', 's_42', 's_43', 's_44', 's_45', 's_46', 's_47', 's_48', 's_49', 's_50'));

		if (!empty($queryparts)) {
			@$sqlite3->exec('INSERT OR IGNORE INTO user_smileys (uid, '.implode(', ', $queryparts['columnlist']).') VALUES ('.$uid.', '.implode(', ', $queryparts['values']).')') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			@$sqlite3->exec('UPDATE user_smileys SET '.implode(', ', $queryparts['update-assignments']).' WHERE CHANGES() = 0 AND uid = '.$uid) or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}

		/**
		 * Write topic data to the database.
		 */
		foreach ($this->topics_objs as $topic) {
			$topic->write_data($sqlite3, $uid);
		}

		/**
		 * Write URL data to the database.
		 */
		foreach ($this->urls_objs as $url) {
			$url->write_data($sqlite3, $uid);
		}
	}
}

/**
 * Class for handling URL data.
 */
final class url extends base
{
	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $datetime = array();
	private $extension = '';
	private $fqdn = '';
	private $tld = '';
	private $url = '';

	public function __construct($urldata)
	{
		$this->fqdn = $urldata['fqdn'];
		$this->tld = $urldata['tld'];
		$this->url = $urldata['url'];

		/**
		 * Attempt to get a file extension from the path. This is by no means 100% accurate but rather a cheap way of indexing content.
		 */
		if (preg_match('/(?<extension>\.[a-z0-9]{1,7})$/i', $urldata['path'], $matches)) {
			$this->extension = strtolower($matches['extension']);
		}
	}

	public function add_datetime($datetime)
	{
		$this->datetime[] = $datetime;
	}

	public function write_data($sqlite3, $uid)
	{
		/**
		 * Write data to database table "fqdns".
		 */
		if ($this->fqdn != '') {
			$fid = @$sqlite3->querySingle('SELECT fid FROM fqdns WHERE fqdn = \''.$this->fqdn.'\'') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

			if (is_null($fid)) {
				@$sqlite3->exec('INSERT INTO fqdns (fid, fqdn) VALUES (NULL, \''.$this->fqdn.'\')') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
				$fid = $sqlite3->lastInsertRowID();
			}
		}

		/**
		 * Write data to database tables "urls" and "user_urls".
		 */
		$lid = @$sqlite3->querySingle('SELECT lid FROM urls WHERE url = \''.$sqlite3->escapeString($this->url).'\'') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		if (is_null($lid)) {
			@$sqlite3->exec('INSERT INTO urls (lid, url'.($this->fqdn != '' ? ', fid, tld' : '').($this->extension != '' ? ', extension' : '').') VALUES (NULL, \''.$sqlite3->escapeString($this->url).'\''.($this->fqdn != '' ? ', '.$fid.', \''.$this->tld.'\'' : '').($this->extension != '' ? ', \''.$this->extension.'\'' : '').')') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$lid = $sqlite3->lastInsertRowID();
		}

		foreach ($this->datetime as $datetime) {
			@$sqlite3->exec('INSERT INTO user_urls (uid, lid, datetime) VALUES ('.$uid.', '.$lid.', DATETIME(\''.$datetime.'\'))') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}
	}
}

/**
 * Class for handling topic data.
 */
final class topic extends base
{
	/**
	 * Variables that shouldn't be tampered with.
	 */
	private $datetime = array();
	private $topic = '';

	public function __construct($topic)
	{
		$this->topic = $topic;
	}

	public function add_datetime($datetime)
	{
		$this->datetime[] = $datetime;
	}

	/**
	 * Write data to database tables "topics" and "user_topics".
	 */
	public function write_data($sqlite3, $uid)
	{
		$tid = @$sqlite3->querySingle('SELECT tid FROM topics WHERE topic = \''.$sqlite3->escapeString($this->topic).'\'') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());

		if (is_null($tid)) {
			@$sqlite3->exec('INSERT INTO topics (tid, topic) VALUES (NULL, \''.$sqlite3->escapeString($this->topic).'\')') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
			$tid = $sqlite3->lastInsertRowID();
		}

		foreach ($this->datetime as $datetime) {
			@$sqlite3->exec('INSERT INTO user_topics (uid, tid, datetime) VALUES ('.$uid.', '.$tid.', DATETIME(\''.$datetime.'\'))') or $this->output('critical', basename(__FILE__).':'.__LINE__.', sqlite3 says: '.$sqlite3->lastErrorMsg());
		}
	}
}

?>
