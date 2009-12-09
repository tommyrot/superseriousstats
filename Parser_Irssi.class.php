<?php

/**
 * Copyright (c) 2009, Jos de Ruijter <jos@dutnie.nl>
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
 * Parse instructions for Irssi logfile format.
 */
final class Parser_Irssi extends Parser
{
	/**
	 * Parse a line for various chat data.
	 */
	protected function parseLine($line)
	{
		/**
		 * Only process lines beginning with a timestamp.
		 */
		if (preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]/', $line)) {
			$dateTime = DATE.' '.substr($line, 0, 5);

			/**
			 * Normalize the nick in a "normal" line. Irssi logs a user's status and we don't want nor use that info.
			 */
			$line = preg_replace('/^<[\x20\+@](.+)>/', '<$1>', substr($line, 6));
			$lineParts = explode(' ', $line);

			/**
			 * "Normal" lines. Format: "<NICK> MSG".
			 */
			if (preg_match('/^<.+>$/', $lineParts[0])) {
				/**
				 * Empty "normal" lines are silently ignored.
				 */
				if (isset($lineParts[1])) {
					$csNick = trim($lineParts[0], '<>');
					$line = implode(' ', array_slice($lineParts, 1));
					$this->setNormal($dateTime, $csNick, $line);
				}

			/**
			 * "Action" lines. Format: "* NICK MSG".
			 * "Slap" lines. Format: "* NICK slaps MSG".
			 */
			} elseif ($lineParts[0] == '*') {
				/**
				 * Empty "action" lines are silently ignored.
				 */
				if (isset($lineParts[2])) {
					$csNick = $lineParts[1];
					$line = implode(' ', array_slice($lineParts, 1));

					/**
					 * There doesn't have to be an "undergoing" nick for a slap to count.
					 */
					if (strtolower($lineParts[2]) == 'slaps') {
						if (isset($lineParts[3])) {
							$csNick_undergoing = $lineParts[3];
						} else {
							$csNick_undergoing = NULL;
						}

						$this->setSlap($dateTime, $csNick, $csNick_undergoing);
					}

					$this->setAction($dateTime, $csNick, $line);
				}

			/**
			 * "Mode" lines by user. Format: "-!- mode/CHAN [+o-v NICK NICK] by NICK".
			 * "Mode" lines by server. Format: "-!- ServerMode/CHAN [+o-v NICK NICK] by NICK".
			 */
			} elseif (stripos($lineParts[1], 'mode/') !== FALSE) {
				$modes = ltrim($lineParts[2], '[');

				/**
				 * Only process modes consisting of ops and voices.
				 */
				if (preg_match('/^[-+][ov]+([-+][ov]+)?$/', $modes)) {
					$modesTotal = substr_count($modes, 'o') + substr_count($modes, 'v');

					/**
					 * Irssi may log multiple "performing" nicks separated by commas. We use only the first one and strip the comma from it.
					 */
					$csNick_performing = rtrim($lineParts[4 + $modesTotal], ',');

					/**
					 * Irssi doesn't log a user's host for "mode" lines so we pass on NULL to setMode().
					 */
					$csHost = NULL;
					$modeNum = 0;

					for ($i = 0; $i < strlen($modes); $i++) {
						$mode = substr($modes, $i, 1);

						if ($mode == '-' || $mode == '+') {
							$modeSign = $mode;
						} else {
							$modeNum++;

							if ($modeNum == $modesTotal) {
								$csNick_undergoing = rtrim($lineParts[2 + $modeNum], ']');
							} else {
								$csNick_undergoing = $lineParts[2 + $modeNum];
							}

							$this->setMode($dateTime, $csNick_performing, $csNick_undergoing, $modeSign.$mode, $csHost);
						}
					}
				}

			/**
			 * "Nickchange" lines. Format: "-!- NICK is now known as NICK".
			 */
			} elseif ($lineParts[4] == 'known') {
				$csNick_performing = $lineParts[1];
				$csNick_undergoing = $lineParts[6];
				$this->setNickchange($dateTime, $csNick_performing, $csNick_undergoing);

			/**
			 * "Join" lines. Format: "-!- NICK [HOST] has joined CHAN".
			 */
			} elseif ($lineParts[4] == 'joined') {
				$csNick = $lineParts[1];
				$csHost = trim($lineParts[2], '[~]');
				$this->setJoin($dateTime, $csNick, $csHost);

			/**
			 * "Part" lines. Format: "-!- NICK [HOST] has left CHAN [MSG]".
			 */
			} elseif ($lineParts[4] == 'left') {
				$csNick = $lineParts[1];
				$csHost = trim($lineParts[2], '[~]');
				$this->setPart($dateTime, $csNick, $csHost);

			/**
			 * "Quit" lines. Format: "-!- NICK [HOST] has quit [MSG]".
			 */
			} elseif ($lineParts[4] == 'quit') {
				$csNick = $lineParts[1];
				$csHost = trim($lineParts[2], '[~]');
				$this->setQuit($dateTime, $csNick, $csHost);

			/**
			 * "Topic" lines. Format: "-!- NICK changed the topic of CHAN to: MSG".
			 */
			} elseif ($lineParts[2] == 'changed') {
				$csNick = $lineParts[1];

				/**
				 * Irssi doesn't log a user's host for "topic" lines so we pass on NULL to setTopic().
				 */
				$csHost = NULL;

				/**
				 * If the topic is empty we pass on NULL to setTopic().
				 */
				if (isset($lineParts[8])) {
					$line = implode(' ', array_slice($lineParts, 8));
				} else {
					$line = NULL;
				}

				$this->setTopic($dateTime, $csNick, $csHost, $line);

			/**
			 * "Kick" lines. Format: "-!- NICK was kicked from CHAN by NICK [MSG]".
			 */
			} elseif ($lineParts[3] == 'kicked') {
				$csNick_performing = $lineParts[7];
				$csNick_undergoing = $lineParts[1];
				$line = substr($line, 4);
				$this->setKick($dateTime, $csNick_performing, $csNick_undergoing, $line);

			/**
			 * Skip everything else.
			 */
			} else {
				$this->output('notice', 'parseLine(): skipping line '.$this->lineNum.': \''.$line.'\'');
			}
		}
	}
}

?>
