<?php

/**
 * These settings only apply to user.php and history.php.
 */

/**
 * Global settings are the default for ALL channels.
 */
$settings['__global'] = array(
	'bar_afternoon' => 'y.png',
	'bar_afternoon2' => 'y2.png',
	'bar_evening' => 'r.png',
	'bar_evening2' => 'r2.png',
	'bar_morning' => 'g.png',
	'bar_morning2' => 'g2.png',
	'bar_night' => 'b.png',
	'bar_night2' => 'b2.png',
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

/**
 * Each channel can have their own specific settings overriding the global ones.
 * Create a new array with the appropriate channel ID and put the settings in it
 * that differ from the global values (seen above). You can find the channel ID
 * for your channel in sss.conf - they must be identical! It is advisable to
 * leave out the hash mark from the channel ID (here and in sss.conf) since it
 * will be used in the URL.
 */
$settings['mychannel'] = array(
	'channel' => '#mychannel',
	'db_name' => 'sss-mychannel',
	'mainpage' => './mychan.html',
	'userstats' => true
);

?>
