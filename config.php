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
		foreach ($this->settings_allow_override as $setting => $type) {
			if (!array_key_exists($setting, $config)) {
				continue;
			}

			/**
			 * Do some explicit type casting because everything is initially a string.
			 */
			if ($type === 'string') {
				$this->$setting = $config[$setting];
			} elseif ($type === 'integer') {
				if (preg_match('/^\d+$/', $config[$setting])) {
					$this->$setting = (int) $config[$setting];
				}
			} elseif ($type === 'boolean') {
				if (preg_match('/^(true|false)$/i', $config[$setting])) {
					$this->$setting = strtolower($config[$setting]);
				}
			}
		}
	}
}
