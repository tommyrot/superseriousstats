<?php declare(strict_types=1);

/**
 * Copyright (c) 2020-2021, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Trait with code for creating parts of SQLite UPSERT queries.
 */
trait queryparts
{
	private function get_queryparts(array $columns): ?array
	{
		foreach ($columns as $var) {
			if (is_int($this->$var)) {
				if ($this->$var !== 0) {
					$insert_columns[] = $var;
					$insert_values[] = $this->$var;

					/**
					 * $topmonologue is a high value and should be treated as such.
					 */
					if ($var === 'topmonologue') {
						$update_assignments[] = 'topmonologue = CASE WHEN excluded.topmonologue > topmonologue THEN excluded.topmonologue ELSE topmonologue END';
					} else {
						$update_assignments[] = $var.' = '.$var.' + excluded.'.$var;
					}
				}
			} elseif (is_string($this->$var)) {
				if ($this->$var !== '') {
					$insert_columns[] = $var;
					$insert_values[] = ($var === 'lasttalked' ? 'DATETIME(\''.$this->lasttalked.'\')' : '\''.preg_replace('/\'/', '\'\'', $this->$var).'\'');

					/**
					 * Don't update a quote if that means its length will fall below 3 words.
					 */
					if (($var === 'quote' || $var === 'ex_exclamations' || $var === 'ex_questions' || $var === 'ex_uppercased' || 'ex_actions') && substr_count($this->$var, ' ') < 2) {
						$update_assignments[] = $var.' = CASE WHEN '.$var.' IS NULL OR '.$var.' NOT LIKE \'% % %\' THEN excluded.'.$var.' ELSE '.$var.' END';
					} else {
						$update_assignments[] = $var.' = excluded.'.$var;
					}
				}
			}
		}

		if (!isset($insert_columns)) {
			return null;
		}

		return [
			'insert_columns' => implode(', ', $insert_columns),
			'insert_values' => implode(', ', $insert_values),
			'update_assignments' => implode(', ', $update_assignments)];
	}
}
