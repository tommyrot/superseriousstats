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
 * Parse instructions for mIRC6 logfile format.
 * 
 * The way mIRC logs actions is pretty dumb, we can spoof nearly all other line types with our actions.
 * Even non-chat messages are logged with the same syntax. This makes it very complicated to come up with a good parser.
 * For this reason we won't parse for actions. Also, the checks on the other line types are a little stricter as usual.
 *
 * Use mIRC6hack logfile format if you can!
 */
final class Parser_mIRC6 extends Parser
{
	/**
	 * Parse a line for various chat data.
	 */
	protected function parseLine($line)
	{
		/**
		 * Only process lines beginning with a timestamp.
		 */
		if (preg_match('/^\[([01][0-9]|2[0-3]):[0-5][0-9]\]/', $line)) {
			$dateTime = $this->date.' '.substr($line, 1, 5);

			/**
			 * Normalize the nick in a "normal" line. mIRC logs nicknames with a mode prefix by default. We don't want nor use that info.
			 */
			$line = preg_replace('/^<[\+@%~&](.+)>/', '<$1>', substr($line, 6));
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
			 * "Nickchange" lines. Format: "* NICK is now known as NICK".
			 */
			} elseif (implode(' ', array_slice($lineParts, 2, 4)) == 'is now known as') {
				$csNick_performing = $lineParts[1];
				$csNick_undergoing = $lineParts[6];
				$this->setNickchange($dateTime, $csNick_performing, $csNick_undergoing);

			/**
			 * "Join" lines. Format: "* NICK (HOST) has joined CHAN".
			 */
			} elseif (implode(' ', array_slice($lineParts, 3, 2)) == 'has joined') {
				$csNick = $lineParts[1];
				$csHost = trim($lineParts[2], '(~)');
				$this->setJoin($dateTime, $csNick, $csHost);

			/**
			 * "Part" lines. Format: "* NICK (HOST) left CHAN (MSG)".
			 */
			} elseif ($lineParts[3] == 'left') {
				$csNick = $lineParts[1];
				$csHost = trim($lineParts[2], '(~)');
				$this->setPart($dateTime, $csNick, $csHost);

			/**
			 * "Quit" lines. Format: "* NICK (HOST) Quit (MSG)".
			 */
			} elseif ($lineParts[3] == 'quit') {
				$csNick = $lineParts[1];
				$csHost = trim($lineParts[2], '(~)');
				$this->setQuit($dateTime, $csNick, $csHost);

			/**
			 * "Mode" lines. Format: "* NICK sets mode: +o-v NICK NICK".
			 */
			} elseif (implode(' ', array_slice($lineParts, 2, 2)) == 'sets mode:') {
				$modes = $lineParts[4];

				/**
				 * Only process modes consisting of ops and voices.
				 */
				if (preg_match('/^[-+][ov]+([-+][ov]+)?$/', $modes)) {
					$modesTotal = substr_count($modes, 'o') + substr_count($modes, 'v');
					$csNick_performing = $lineParts[1];

					/**
					 * mIRC doesn't log a user's host for "mode" lines so we pass on NULL to setMode().
					 */
					$csHost = NULL;
					$modeNum = 0;

					for ($i = 0; $i < strlen($modes); $i++) {
						$mode = substr($modes, $i, 1);

						if ($mode == '-' || $mode == '+') {
							$modeSign = $mode;
						} else {
							$modeNum++;
							$csNick_undergoing = $lineParts[4 + $modeNum];
							$this->setMode($dateTime, $csNick_performing, $csNick_undergoing, $modeSign.$mode, $csHost);
						}
					}
				}

			/**
			 * "Topic" lines. Format: "* NICK changes topic to 'MSG'".
			 */
			} elseif (implode(' ', array_slice($lineParts, 2, 3)) == 'changes topic to') {
				$csNick = $lineParts[1];

				/**
				 * mIRC doesn't log a user's host for "topic" lines so we pass on NULL to setTopic().
				 */
				$csHost = NULL;

				/**
				 * If the topic is empty we pass on NULL to setTopic().
				 */
				if (array_slice($lineParts, 5) != '\'\'') {
					$line = substr(implode(' ', array_slice($lineParts, 5)), 1, -1);
				} else {
					$line = NULL;
				}

				$this->setTopic($dateTime, $csNick, $csHost, $line);

			/**
			 * "Kick" lines. Format: "* NICK was kicked by NICK (MSG)".
			 */
			} elseif (implode(' ', array_slice($lineParts, 2, 3)) == 'was kicked by') {
				$csNick_performing = $lineParts[5];
				$csNick_undergoing = $lineParts[1];
				$line = substr($line, 2);
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
