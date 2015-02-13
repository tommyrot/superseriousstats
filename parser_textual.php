<?php

/**
 * Copyright (c) 2012-2015, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Parse instructions for the Textual logfile format.
 *
 * Line         Format                                                  Notes
 * ---------------------------------------------------------------------------------------------------------------------
 * Normal       NICK: MSG                                               Skip empty lines.
 * Nickchange   NICK is now known as NICK
 * Join         NICK (HOST) joined the channel.
 * Part         NICK (HOST) left the channel. (MSG)                     Part message may be absent, or empty due to
 *                                                                      normalization.
 * Quit         NICK (HOST) left IRC. (MSG)                             Quit message may be empty due to normalization.
 *                                                                      IRC is literal.
 * Mode         NICK sets mode +o-v NICK NICK                           Only check for combinations of ops (+o) and
 *                                                                      voices (+v).
 * Topic        NICK changed the topic to MSG                           Skip empty topics.
 * Kick         NICK kicked NICK from the channel. (MSG)                Kick message may be empty due to normalization.
 * ---------------------------------------------------------------------------------------------------------------------
 *
 * Notes:
 * - normalize_line() scrubs all lines before passing them on to parse_line().
 * - Textual uses the same syntax for actions as "normal" lines. This makes the two indistinguishable. Pretty dumb.
 * - Given that nicks can't contain ":" the order of the regular expressions below is irrelevant (current order aims for
 *   best performance).
 */
class parser_textual extends parser
{
	/**
	 * Parse a line for various chat data.
	 */
	protected function parse_line($line)
	{
		/**
		 * "Normal" lines.
		 */
		if (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<nick>\S+): (?<line>.+)$/', $line, $matches)) {
			$this->set_normal($matches['time'], $matches['nick'], $matches['line']);

		/**
		 * "Join" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<nick>\S+) \(\S+\) joined the channel\.$/', $line, $matches)) {
			$this->set_join($matches['time'], $matches['nick']);

		/**
		 * "Quit" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<nick>\S+) \(\S+\) left IRC\. \(.*\)$/', $line, $matches)) {
			$this->set_quit($matches['time'], $matches['nick']);

		/**
		 * "Mode" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<nick_performing>\S+) sets mode (?<modes>[-+][ov]+([-+][ov]+)?) (?<nicks_undergoing>\S+( \S+)*)$/', $line, $matches)) {
			$modenum = 0;
			$nicks_undergoing = explode(' ', $matches['nicks_undergoing']);

			for ($i = 0, $j = strlen($matches['modes']); $i < $j; $i++) {
				$mode = substr($matches['modes'], $i, 1);

				if ($mode === '-' || $mode === '+') {
					$modesign = $mode;
				} else {
					$this->set_mode($matches['time'], $matches['nick_performing'], $nicks_undergoing[$modenum], $modesign.$mode);
					$modenum++;
				}
			}

		/**
		 * "Nickchange" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<nick_performing>\S+) is now known as (?<nick_undergoing>\S+)$/', $line, $matches)) {
			$this->set_nickchange($matches['time'], $matches['nick_performing'], $matches['nick_undergoing']);

		/**
		 * "Part" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<nick>\S+) \(\S+\) left the channel\.( \(.*\))?$/', $line, $matches)) {
			$this->set_part($matches['time'], $matches['nick']);

		/**
		 * "Topic" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<nick>\S+) changed the topic to (?<line>.+)$/', $line, $matches)) {
			$this->set_topic($matches['time'], $matches['nick'], $matches['line']);

		/**
		 * "Kick" lines.
		 */
		} elseif (preg_match('/^\[(?<time>\d{2}:\d{2}(:\d{2})?)\] (?<line>(?<nick_performing>\S+) kicked (?<nick_undergoing>\S+) from the channel\. \(.*\))$/', $line, $matches)) {
			$this->set_kick($matches['time'], $matches['nick_performing'], $matches['nick_undergoing'], $matches['line']);

		/**
		 * Skip everything else.
		 */
		} elseif ($line !== '') {
			output::output('debug', __METHOD__.'(): skipping line '.$this->linenum.': \''.$line.'\'');
		}
	}
}
