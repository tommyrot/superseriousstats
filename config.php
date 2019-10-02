<?php

/**
 * Copyright (c) 2018-2019, Jos de Ruijter <jos@dutnie.nl>
 */

declare(strict_types=1);

/**
 * Trait to apply relevant settings from the config file.
 */
trait config
{
	private function apply_settings(array $config): void
	{
		/**
		 * Update variables listed in $settings_allow_override[].
		 */
		foreach ($this->settings_allow_override as $setting) {
			if (!array_key_exists($setting, $config)) {
				continue;
			}

			/**
			 * Do some explicit type casting because everything is initially a string.
			 */
			if (is_string($this->$setting)) {
				$this->$setting = $config[$setting];
			} elseif (is_int($this->$setting)) {
				if (preg_match('/^\d+$/', $config[$setting])) {
					$this->$setting = (int) $config[$setting];
				}
			} elseif (is_bool($this->$setting)) {
				if (preg_match('/^true$/i', $config[$setting])) {
					$this->$setting = true;
				} elseif (preg_match('/^false$/i', $config[$setting])) {
					$this->$setting = false;
				}
			}
		}
	}
}
