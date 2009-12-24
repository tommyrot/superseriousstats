<?php

/**
 * Copyright (c) 2007-2009, Jos de Ruijter <jos@dutnie.nl>
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
final class Nick extends Nick_MySQL
{
	/**
	 * Variables used in database table "user_details".
	 */
	protected $csNick = '';
	protected $firstSeen = '';
	protected $lastSeen = '';

	/**
	 * Variables used in database table "user_hosts".
	 */
	protected $hosts_list = array();

	/**
	 * Variables used in database table "user_topics".
	 */
	protected $topics_list = array();

	/**
	 * Variables used in database table "user_URLs".
	 */
	protected $URLs_list = array();
	protected $URLs_objs = array();

	/**
	 * Variables used in database table "user_events".
	 */
	protected $m_op = 0;
	protected $m_opped = 0;
	protected $m_voice = 0;
	protected $m_voiced = 0;
	protected $m_deOp = 0;
	protected $m_deOpped = 0;
	protected $m_deVoice = 0;
	protected $m_deVoiced = 0;
	protected $joins = 0;
	protected $parts = 0;
	protected $quits = 0;
	protected $kicks = 0;
	protected $kicked = 0;
	protected $nickchanges = 0;
	protected $topics = 0;
	protected $ex_kicks = '';
	protected $ex_kicked = '';

	/**
	 * Variables used in database table "user_lines".
	 */
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
	protected $l_night = 0;
	protected $l_morning = 0;
	protected $l_afternoon = 0;
	protected $l_evening = 0;
	protected $l_total = 0;
	protected $l_mon_night = 0;
	protected $l_mon_morning = 0;
	protected $l_mon_afternoon = 0;
	protected $l_mon_evening = 0;
	protected $l_tue_night = 0;
	protected $l_tue_morning = 0;
	protected $l_tue_afternoon = 0;
	protected $l_tue_evening = 0;
	protected $l_wed_night = 0;
	protected $l_wed_morning = 0;
	protected $l_wed_afternoon = 0;
	protected $l_wed_evening = 0;
	protected $l_thu_night = 0;
	protected $l_thu_morning = 0;
	protected $l_thu_afternoon = 0;
	protected $l_thu_evening = 0;
	protected $l_fri_night = 0;
	protected $l_fri_morning = 0;
	protected $l_fri_afternoon = 0;
	protected $l_fri_evening = 0;
	protected $l_sat_night = 0;
	protected $l_sat_morning = 0;
	protected $l_sat_afternoon = 0;
	protected $l_sat_evening = 0;
	protected $l_sun_night = 0;
	protected $l_sun_morning = 0;
	protected $l_sun_afternoon = 0;
	protected $l_sun_evening = 0;
	protected $URLs = 0;
	protected $words = 0;
	protected $characters = 0;
	protected $monologues = 0;
	protected $topMonologue = 0;
	protected $activeDays = 0;
	protected $slaps = 0;
	protected $slapped = 0;
	protected $exclamations = 0;
	protected $questions = 0;
	protected $actions = 0;
	protected $uppercased = 0;
	protected $quote = '';
	protected $ex_exclamations = '';
	protected $ex_questions = '';
	protected $ex_actions = '';
	protected $ex_uppercased = '';
	protected $lastTalked = '';

	/**
	 * Variables used in database table "user_smileys".
	 */
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

	/**
	 * Other variables that shouldn't be tampered with.
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
	protected $UID = 0;
	protected $date = '';

        /**
         * Constructor.
         */
	public function __construct($csNick)
	{
		$this->csNick = $csNick;
	}

        /**
         * Keep a list of hosts this user has been seen using.
         */
	public function addHost($csHost)
	{
		$host = strtolower($csHost);

		if (!in_array($host, $this->hosts_list)) {
			$this->hosts_list[] = $host;
		}
	}

        /**
         * Keep two lists of the various types of quotes for the user; one with short quotes and one with long quotes.
         */
	public function addQuote($type, $length, $line)
	{
		$this->{$length.'_'.$type.'_list'}[] = $line;
	}

        /**
         * Keep a list of topics set by the user.
         */
	public function addTopic($csTopic, $dateTime)
	{
		$this->topics_list[] = array('csTopic' => $csTopic
					    ,'setDate' => $dateTime);
	}

        /**
         * Keep a list of URLs pasted by the user. The case of the last mentioned version of the URL will be stored.
         */
	public function addURL($csURL, $dateTime)
	{
		$URL = strtolower($csURL);

		if (!in_array($URL, $this->URLs_list)) {
			$this->URLs_list[] = $URL;
			$this->URLs_objs[$URL] = new URL($csURL);
		} else {
			$this->URLs_objs[$URL]->setValue('csURL', $csURL);
		}

		$this->URLs_objs[$URL]->addValue('total', 1);
		$this->URLs_objs[$URL]->lastUsed($dateTime);
	}

        /**
         * Add a value to a variable.
         */
	public function addValue($var, $value)
	{
		$this->$var += $value;
	}

        /**
         * Get the value of a variable.
         */
	public function getValue($var)
	{
		return $this->$var;
	}

        /**
         * Store the date and time the user was first and last seen.
         */
	public function lastSeen($dateTime)
	{
		if ($this->firstSeen == '' || $dateTime < $this->firstSeen) {
			$this->firstSeen = $dateTime;
		}

		if ($this->lastSeen == '' || $dateTime > $this->lastSeen) {
			$this->lastSeen = $dateTime;
		}
	}

        /**
         * Store the date and time the user last typed a "normal" line.
         */
	public function lastTalked($dateTime)
	{
		if ($this->lastTalked == '' || $dateTime > $this->lastTalked) {
			$this->lastTalked = $dateTime;
		}
	}

	/**
	 * Pick a random line from either the list of long quotes or, when there are no long quotes, from the list of short quotes.
	 * Long quotes are preferred since these look better on the statspage and give away more about the subject.
	 */
	public function randomizeQuotes()
	{
		$types = array('ex_actions', 'ex_exclamations', 'ex_questions', 'ex_uppercased', 'quote');

		foreach ($types as $type) {
			if (!empty($this->{'long_'.$type.'_list'})) {
				$this->$type = $this->{'long_'.$type.'_list'}[mt_rand(0, count($this->{'long_'.$type.'_list'}) - 1)];
			} elseif (!empty($this->{'short_'.$type.'_list'})) {
				$this->$type = $this->{'short_'.$type.'_list'}[mt_rand(0, count($this->{'short_'.$type.'_list'}) - 1)];
			}
		}
	}

        /**
         * Set the value of a variable.
         */
	public function setValue($var, $value)
	{
		$this->$var = $value;
	}
}

?>
