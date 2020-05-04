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
