<?php

/**
 * Global settings are the default for ALL channels.
 */
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
'mainpage' => './',
'rows_people_month' => 30,
'rows_people_year' => 30,
'stylesheet' => 'sss.css',
'timezone' => 'UTC',
'userstats' => false
);

/**
 * Each channel can have their own specific settings overriding global ones.
 */
$settings['example'] = array(
'channel' => '#example',
'db_name' => 'sss-example'
);

?>
