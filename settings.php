<?php

/**
 * For more details on how to set up sss consult the wiki:
 * http://code.google.com/p/superseriousstats/wiki/SetupGuide
 */

/**
 * Name of the IRC channel you are generating stats for.
 */
$cfg['channel'] = '#superseriousstats';

/**
 * Your timezone. A list of supported timezones can be found at
 * http://php.net/manual/en/timezones.php
 */
$cfg['timezone'] = 'Europe/Amsterdam';

/**
 * Format of the date string suffixed to the logfiles.
 *
 * Options (most common): Ymd, dmY
 */
$cfg['date_format'] = 'Ymd';

/**
 * Logfile format.
 *
 * Options: Eggdrop, Irssi
 */
$cfg['logfile_format'] = 'Eggdrop';

/**
 * Database server. Currently only 'MySQL' is supported.
 *
 * Options: MySQL
 */
$cfg['database_server'] = 'MySQL';

/**
 * Database connection variables.
 *
 * $db_host - IP address of the database server
 * $db_port - Port number the database server is running on
 * $db_user - Username
 * $db_pass - Password
 * $db_name - Database used when performing queries
 */
$cfg['db_host'] = '127.0.0.1';
$cfg['db_port'] = 3306;
$cfg['db_user'] = 'user';
$cfg['db_pass'] = 'pass';
$cfg['db_name'] = 'superseriousstats';

/**
 * Output level for event messages.
 *
 * 0 - Don't output anything
 * 1 - Critical events only (default)
 * 2 - Critical events and warnings
 * 3 - Critical events, warnings and notices
 * 4 - Critical events, warnings, notices and debug messages
 *
 * Options: 0, 1, 2, 3, 4
 */
$cfg['outputLevel'] = 1;

/**
 * Word tracking. Enabling this feature increases parsetimes substantially.
 * Currently the words data is only used to create the "Most Mentioned Nicks"
 * table.
 *
 * Options: TRUE, FALSE
 */
$cfg['wordTracking'] = FALSE;

/**
 * Write data to the database. Disabling this may sometimes be preferred when
 * debugging or developing.
 *
 * Options: TRUE, FALSE
 */
$cfg['writeData'] = TRUE;

/**
 * Perform maintenance routines on the database tables. Generates query tables
 * used by the HTML output functions. Required before every daily HTML output.
 *
 * Options: TRUE, FALSE
 */
$cfg['doMaintenance'] = TRUE;

/**
 * The day on which to perform extended database maintenance. This will run
 * the OPTIMIZE command on all tables which takes slightly longer to complete
 * and is only necessary once a week.
 *
 * Options: mon, tue, wed, thu, fri, sat, sun
 */
$cfg['sanitisationDay'] = 'mon';

?>
