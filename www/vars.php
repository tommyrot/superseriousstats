<?php

// These settings only apply to user.php and history.php.

// Global settings are the default for ALL channels.
$settings['__global'] = array(
	'bar_afternoon' => 'y.png',
	'bar_evening' => 'r.png',
	'bar_morning' => 'g.png',
	'bar_night' => 'b.png',
	'channel' => '',
	'db_host' => '127.0.0.1',
	'db_name' => 'sss',
	'db_pass' => '',
	'db_port' => 3306,
	'db_user' => '',
	'debug' => false,
	'mainpage' => './',
	'rows_people_month' => 30,
	'rows_people_timeofday' => 10,
	'rows_people_year' => 30,
	'stylesheet' => 'sss.css',
	'timezone' => 'UTC',
	'userstats' => false
);

// Each channel can have their own specific settings overriding the global ones.
// Create a new array with the appropriate channel ID for each channel and put
// the settings in it that differ from the global values.
$settings['MyChannelID'] = array(
	'channel' => '#MyChannel',
	'db_name' => 'MyDatabase',
	'mainpage' => './mychannel.html',
	'userstats' => true
);

?>
