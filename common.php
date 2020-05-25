<?php declare(strict_types=1);

/**
 * Copyright (c) 2010-2020, Jos de Ruijter <jos@dutnie.nl>
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
	 * Retrieve and apply given settings after casting their values to the
	 * appropriate type.
	 */
	private function apply_settings(array $settings): void
	{
		foreach ($settings as $setting) {
			if (is_null($value = db::query_single_col('SELECT value FROM settings WHERE setting = \''.$setting.'\''))) {
				continue;
			}

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
