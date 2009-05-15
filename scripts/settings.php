<?php

/**
 * Change the settings below according to your specific installation.
 * Additional information can be found at
 * http://code.google.com/p/superseriousstats/wiki/SetupGuide
 */

// Name of the IRC channel you are generating stats for.
$channel = '#example';

// The full path to the directory which contains the PHP classes.
$path = '/home/user/sss/1.0.x';

/**
 * Your timezone. A list of supported timezones can be found at
 * http://php.net/manual/en/timezones.php
 */
$timezone = 'Europe/Amsterdam';

// Database connection variables.
$db_host = '127.0.0.1';
$db_user = 'user';
$db_pass = 'password';
$db_name = 'sss-example';

/**
 * The different output levels for event messages are:
 * 0 - Don't output anything
 * 1 - Critical events only (default)
 * 2 - Critical events and warnings
 * 3 - Critical events, warnings and notices
 * 4 - Critical events, warnings, notices and debug messages
 */
$outputLevel = 1;

/**
 * Enable (TRUE) or disable (FALSE, default) the tracking of words.
 * Enabling this feature may pose a big performance hit on parsetimes,
 * depending on the available processing power of the system used and the
 * amount of lines in the logfile being parsed.
 * The ability to generate the "Most Mentioned Nicks" table is dependent on
 * this feature and possibly other tables as well in the future.
 */
$wordTracking = FALSE;

/**
 * Write the collected data to the database after parsing the logfile.
 * Default is TRUE. Turn this off when you're doing tests or debugging and
 * don't want your database to get corrupted.
 */
$writeData = TRUE;

/**
 * Perform maintenance routines on the database tables. Default is TRUE.
 * It's safe to do maintenance but in case of testing or debugging you may
 * want to skip this step to gain time, e.g. when doing an initial database
 * fill with 100 logfiles you'll only have to do maintenance once at the end.
 */
$doMaintenance = TRUE;

/**
 * The day on which we perform extended database sanitisation.
 * Can be either 'mon|tue|wed|thu|fri|sat|sun'. Default is 'mon'.
 * $doMaintenance has to be set to TRUE for this option to have any effect.
 */
$sanitisationDay = 'mon';

?>
