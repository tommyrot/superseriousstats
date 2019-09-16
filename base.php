<?php

/**
 * Copyright (c) 2010-2019, Jos de Ruijter <jos@dutnie.nl>
 */

declare(strict_types=1);

/**
 * Trait with common functions.
 */
trait base
{
	public function add_num(string $var, int $value)
	{
		$this->$var += $value;
	}

	public function get_num(string $var): int
	{
		return $this->$var;
	}

	public function get_str(string $var): string
	{
		return $this->$var;
	}

	public function set_num(string $var, int $value)
	{
		$this->$var = $value;
	}

	public function set_str(string $var, string $value)
	{
		$this->$var = $value;
	}
}
