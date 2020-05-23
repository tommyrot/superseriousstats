<?php declare(strict_types=1);

/**
 * Copyright (c) 2010-2020, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Trait with common functions.
 */
trait base
{
	public function add_int(string $var, int $value): void
	{
		$this->$var += $value;
	}

	/**
	 * Apply given setting after doing some explicit type casting because its value
	 * is initially a string.
	 */
	private function apply_setting(string $setting, string $value): void
	{
		if (is_string($this->$setting)) {
			$this->$setting = $value;
		} elseif (is_int($this->$setting)) {
			if (preg_match('/^\d+$/', $value)) {
				$this->$setting = (int) $value;
			}
		} elseif (is_bool($this->$setting)) {
			if (preg_match('/^true$/i', $value)) {
				$this->$setting = true;
			} elseif (preg_match('/^false$/i', $value)) {
				$this->$setting = false;
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
