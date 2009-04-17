<?php

/*
 * Copyright (c) 2007-2009 Jos de Ruijter <jos@dutnie.nl>
 *
 * Permission to use, copy, modify, and distribute this software for any
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

/*
 * Super Serious Stats
 * Parser_Eggdrop.class.php
 *
 * Parse instructions for Eggdrop logfileformat.
 */

final class Parser_Eggdrop extends Parser
{
	// Variable to prevent the parser from repeating a line endlessly.
	private $repeating = FALSE;

	protected function parseLine($line)
	{
		// Only process lines beginning with a timestamp.
		if (preg_match('/^\[([01][0-9]|2[0-3]):[0-5][0-9]\]/', $line)) {
			$dateTime = DATE.' '.substr($line, 1, 5);
			$line = substr($line, 8);
			$lineParts = explode(' ', $line);

			/*
			 * "Normal" lines. Format: "<NICK> MSG".
			 * Catch "normal" lines even if the nick is empty.
			 */
			if (preg_match('/^<.*>$/', $lineParts[0])) {
				/*
				 * Only process non empty "normal" lines.
				 * Empty lines are silently ignored.
				 */
				if (isset($lineParts[1])) {
					$csNick = trim($lineParts[0], '<>');
					$line = implode(' ', array_slice($lineParts, 1));
					$this->setNormal($dateTime, $csNick, $line);
				}

			/*
			 * "Action" lines. Format: "Action: NICK MSG".
			 * "Slap" lines. Format: "Action: NICK slaps MSG".
			 */
			} elseif ($lineParts[0] == 'Action:') {
				/*
				 * Only process non empty "action" lines.
				 * Empty lines are silently ignored.
				 */
				if (isset($lineParts[2])) {
					$csNick = $lineParts[1];
					$line = implode(' ', array_slice($lineParts, 1));

					// There doesn't have to be an "undergoing" nick for a slap to count.
					if ($lineParts[2] == 'slaps') {
						$csNick_undergoing = $lineParts[3];
						$this->setSlap($dateTime, $csNick, $csNick_undergoing);
					}

					$this->setAction($dateTime, $csNick, $line);
				}

			// "Nickchange" lines. Format: "Nick change: NICK -> NICK".
			} elseif ($lineParts[1] == 'change:') {
				$csNick_performing = $lineParts[2];
				$csNick_undergoing = $lineParts[4];
				$this->setNickchange($dateTime, $csNick_performing, $csNick_undergoing);

			// "Join" lines. Format: "NICK (HOST) joined CHAN.".
			} elseif ($lineParts[2] == 'joined') {
				$csNick = $lineParts[0];
				$csHost = trim($lineParts[1], '(~)');
				$this->setJoin($dateTime, $csNick, $csHost);

			/*
			 * "Part" lines. Format: "NICK (HOST) left CHAN (MSG).".
			 * "Quit" lines. Format: "NICK (HOST) left irc: MSG".
			 */
			} elseif ($lineParts[2] == 'left') {
				$csNick = $lineParts[0];
				$csHost = trim($lineParts[1], '(~)');

				if ($lineParts[3] == 'irc:')
					$this->setQuit($dateTime, $csNick, $csHost);
				else
					$this->setPart($dateTime, $csNick, $csHost);

			// "Mode" lines. Format: "CHAN: mode change '+o-v NICK NICK' by NICK!HOST".
			} elseif ($lineParts[1] == 'mode') {
				$modes = ltrim($lineParts[3], '\'');

				// Only process modes consisting of ops and voices.
				if (preg_match('/^[-+][ov]+([-+][ov]+)?$/', $modes)) {
					$modesTotal = substr_count($modes, 'o') + substr_count($modes, 'v');
					$tmp = explode('!', $lineParts[5 + $modesTotal]);
					$csNick_performing = $tmp[0];
					$csHost = ltrim($tmp[1], '~');
					$modeNum = 0;

					for ($i = 0; $i < strlen($modes); $i++) {
						$mode = substr($modes, $i, 1);

						if ($mode == '-' || $mode == '+')
							$modeSign = $mode;
						else {
							$modeNum++;

							if ($modeNum == $modesTotal)
								$csNick_undergoing = rtrim($lineParts[3 + $modeNum], '\'');
							else
								$csNick_undergoing = $lineParts[3 + $modeNum];

							$this->setMode($dateTime, $csNick_performing, $csNick_undergoing, $modeSign.$mode, $csHost);
						}
					}
				}

			// "Topic" lines. Format: "Topic changed on CHAN by NICK!HOST: MSG".
			} elseif ($lineParts[1] == 'changed') {
				$tmp = explode('!', $lineParts[5]);
				$csNick = $tmp[0];
				$csHost = trim($tmp[1], '~:');

				// If the topic is empty we pass on NULL to setTopic().
				if (isset($lineParts[6]))
					$line = implode(' ', array_slice($lineParts, 6));
				else
					$line = NULL;

				$this->setTopic($dateTime, $csNick, $csHost, $line);

			// "Kick" lines. Format: "NICK kicked from CHAN by NICK: MSG".
			} elseif ($lineParts[1] == 'kicked') {
				$csNick_performing = rtrim($lineParts[5], ':');
				$csNick_undergoing = $lineParts[0];
				$this->setKick($dateTime, $csNick_performing, $csNick_undergoing, $line);

			/*
			 * Eggdrops log repeated lines (case insensitive matches) in the format: "Last message repeated NUM time(s).".
			 * We process the previous line NUM times.
			 */
			} elseif ($lineParts[1] == 'message') {
				// Lock the repeating of lines to prevent a loop. Don't touch!!
				if (!$this->repeating) {
					$this->repeating = TRUE;
					$this->lineNum--;
					$this->output('notice', 'parseLine(): repeating line '.$this->lineNum.': '.$lineParts[3].' '.(($lineParts[3] == 1) ? 'time' : 'times'));

					for ($i = $lineParts[3]; $i > 0; $i--)
						$this->parseLine($this->prevLine);

					$this->lineNum++;
					$this->repeating = FALSE;
				}

			// Skip everything else.
			} else
				$this->output('notice', 'parseLine(): skipping line '.$this->lineNum.': \''.$line.'\'');
		}
	}
}

?>
