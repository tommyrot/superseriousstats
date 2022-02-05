<?php declare(strict_types=1);

/**
 * Copyright (c) 2010-2022, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Trait with common functions.
 */
trait common
{
	public function add_int(string $var, int $value): void
	{
		$this->$var += $value;
	}

	/**
	 * Retrieve and apply given variables from $table after casting their values to
	 * the appropriate type. $table can be either "settings" or "parse_state".
	 */
	private function apply_vars(string $table, array $vars): void
	{
		foreach ($vars as $var) {
			if (is_null($value = db::query_single_col('SELECT value FROM '.$table.' WHERE var = \''.$var.'\''))) {
				continue;
			}

			if (is_string($this->$var)) {
				$this->$var = $value;
			} elseif (is_int($this->$var)) {
				if (preg_match('/^\d+$/', $value)) {
					$this->$var = (int) $value;
				}
			} elseif (is_bool($this->$var)) {
				if (preg_match('/^true$/i', $value)) {
					$this->$var = true;
				} elseif (preg_match('/^false$/i', $value)) {
					$this->$var = false;
				}
			} elseif (is_array($this->$var)) {
				$this->$var = unserialize($value);
			}
		}
	}

	public function get_int(string $var): int
	{
		return $this->$var;
	}

	public function get_string(string $var): string
	{
		return $this->$var;
	}

	public function set_int(string $var, int $value): void
	{
		$this->$var = $value;
	}

	public function set_string(string $var, string $value): void
	{
		$this->$var = $value;
	}
}
