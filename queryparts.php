<?php declare(strict_types=1);

/**
 * Copyright (c) 2020, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Trait to create parts of the SQLite3 query.
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
					$update_assignments[] = $var.' = '.$var.' + '.$this->$var;
				}
			} elseif (is_string($this->$var)) {
				if ($this->$var !== '') {
					$value = '\''.preg_replace('/\'/', '\'\'', $this->$var).'\'';
					$insert_columns[] = $var;
					$insert_values[] = $value;
					$update_assignments[] = $var.' = '.$value;
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
