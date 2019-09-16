<?php

/**
 * Copyright (c) 2018, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Trait with functions related to configuration management.
 */
trait config
{
	private function set_config($settings)
	{
		/**
		 * If set, override variables listed in $settings_list[].
		 */
		foreach ($this->settings_list as $setting => $type) {
			if (!array_key_exists($setting, $settings)) {
				continue;
			}

			/**
			 * Do some explicit type casting because everything is initially a string.
			 */
			if ($type === 'string') {
				$this->$setting = $settings[$setting];
			} elseif ($type === 'int') {
				if (preg_match('/^\d+$/', $settings[$setting])) {
					$this->$setting = (int) $settings[$setting];
				}
			} elseif ($type === 'bool') {
				if (strtolower($settings[$setting]) === 'true') {
					$this->$setting = true;
				} elseif (strtolower($settings[$setting]) === 'false') {
					$this->$setting = false;
				}
			}
		}
	}
}
