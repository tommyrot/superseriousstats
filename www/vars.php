<?php

/**
 * These settings only apply to user.php and history.php.
 */

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
