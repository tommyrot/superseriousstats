<?php

/**
 * Copyright (c) 2010-2015, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Trait with common functions.
 */
trait base
{
	public function add_value($var, $value)
	{
		$this->$var += $value;
	}

	public function get_value($var)
	{
		return $this->$var;
	}

	public function set_value($var, $value)
	{
		$this->$var = $value;
	}
}
