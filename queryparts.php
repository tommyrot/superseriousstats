<?php declare(strict_types=1);

/**
 * Copyright (c) 2020-2021, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Trait with code for creating parts of the SQLite query.
 */
trait queryparts
{
	private function get_queryparts(array $columns): array|null
	{
		foreach ($columns as $var) {
			if (is_int($this->$var)) {
				if ($this->$var !== 0) {
					$insert_columns[] = $var;
					$insert_values[] = $this->$var;

					if ($var === 'topmonologue') {
						/**
						 * $topmonologue is a high value and should be treated as such.
						 */
						$update_assignments[] = 'topmonologue = CASE WHEN '.$this->topmonologue.' > topmonologue THEN '.$this->topmonologue.' ELSE topmonologue END';
					} else {
						$update_assignments[] = $var.' = '.$var.' + '.$this->$var;
					}
				}
			} elseif (is_string($this->$var)) {
				if ($this->$var !== '') {
					$value = '\''.preg_replace('/\'/', '\'\'', $this->$var).'\'';
					$insert_columns[] = $var;
					$insert_values[] = $value;

					if (($var === 'quote' || $var === 'ex_exclamations' || $var === 'ex_questions' || $var === 'ex_uppercased') && substr_count($this->$var, ' ') < 2) {
						/**
						 * Don't update a quote if that means its length will fall below 3 words.
						 */
						$update_assignments[] = $var.' = CASE WHEN '.$var.' NOT LIKE \'% % %\' THEN '.$value.' ELSE '.$var.' END';
					} else {
						$update_assignments[] = $var.' = '.$value;
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
