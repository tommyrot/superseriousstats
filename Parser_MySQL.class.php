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
 * Super Serious Stats
 * Parser_MySQL.class.php
 *
 * Class for writing channel, user and word data to the database.
 */

abstract class Parser_MySQL
{
	public $dblink;

	final public function writeData()
	{
		// If there are no nicks there is no data. Don't write channel data so the log can be parsed at a later time.
		if (!empty($this->nicks_list)) {
			$this->output('notice', 'writeData(): writing data to database: \''.MYSQL_DB.'\'');
			$mysqli = @mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB) or $this->output('critical', 'MySQL: '.mysqli_connect_error());

			// Write data to database table "channel".
			@mysqli_query($mysqli, 'INSERT INTO `channel` (`date`, `l_00`, `l_01`, `l_02`, `l_03`, `l_04`, `l_05`, `l_06`, `l_07`, `l_08`, `l_09`, `l_10`, `l_11`, `l_12`, `l_13`, `l_14`, `l_15`, `l_16`, `l_17`, `l_18`, `l_19`, `l_20`, `l_21`, `l_22`, `l_23`, `l_night`, `l_morning`, `l_afternoon`, `l_evening`, `l_total`) VALUES (\''.mysqli_real_escape_string($mysqli, DATE).'\', '.$this->l_00.', '.$this->l_01.', '.$this->l_02.', '.$this->l_03.', '.$this->l_04.', '.$this->l_05.', '.$this->l_06.', '.$this->l_07.', '.$this->l_08.', '.$this->l_09.', '.$this->l_10.', '.$this->l_11.', '.$this->l_12.', '.$this->l_13.', '.$this->l_14.', '.$this->l_15.', '.$this->l_16.', '.$this->l_17.', '.$this->l_18.', '.$this->l_19.', '.$this->l_20.', '.$this->l_21.', '.$this->l_22.', '.$this->l_23.', '.$this->l_night.', '.$this->l_morning.', '.$this->l_afternoon.', '.$this->l_evening.', '.$this->l_total.')') or $this->output('critical', 'MySQL: '.mysqli_error($mysqli));

			// Write user data to database.
			foreach ($this->nicks_list as $nick)
				if ($this->nicks_objs[$nick]->getValue('firstSeen') != '')
					$this->nicks_objs[$nick]->writeData($mysqli) or $this->output('critical', 'MySQL: '.mysqli_error($mysqli));
				else
					$this->output('notice', 'writeData(): skipping empty nick: \''.$this->nicks_objs[$nick]->getValue('csNick').'\'');

			/**
			 * Write word data to database.
			 * To keep our database sane words are not linked to users.
			 */
			foreach ($this->words_list as $word)
				$this->words_objs[$word]->writeData($mysqli) or $this->output('critical', 'MySQL: '.mysqli_error($mysqli));

			@mysqli_close($mysqli);
			$this->output('notice', 'writeData(): writing completed');
		} else
			$this->output('notice', 'writeData(): no data to write to database');
	}
}

?>
