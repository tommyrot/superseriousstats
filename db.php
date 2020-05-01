<?php

/**
 * Copyright (c) 2020, Jos de Ruijter <jos@dutnie.nl>
 */

declare(strict_types=1);

/**
 * Class handling the database connection.
 */
class db
{
	private static string $database = '';
	public static ?SQLite3 $conn = null;

	/**
	 * This is a static class and should not be instantiated.
	 */
	private function __construct() {}

	public static function set_database(string $database): void
	{
		self::$database = $database;
	}

	public static function begin(): void
	{
		if (!is_null(self::$conn)) {
			return;
		}

		/**
		 * Open the database connection.
		 */
		try {
			self::$conn = new SQLite3(self::$database, SQLITE3_OPEN_READWRITE);
			self::$conn->busyTimeout(60000);
			output::msg('notice', 'succesfully connected to database: \''.self::$database.'\'');
		} catch (Exception $e) {
			output::msg('critical', 'fail in '.basename(__FILE__).'#'.__LINE__.': '.$e->getMessage());
		}

		/**
		 * Setup the SQLite3 connection:
		 *  - Disable the rollback journal ("journal_mode OFF").
		 *  - Continue without syncing as soon as data is handed off to the operating
		 *    system ("synchronous OFF").
		 *  - Temporary tables and indices are kept in memory ("temp_store MEMORY").
		 *  - Enable foreign key constraints ("foreign_keys ON").
		 */
		$pragmas = [
			'journal_mode' => 'OFF',
			'synchronous' => 'OFF',
			'temp_store' => 'MEMORY',
			'foreign_keys' => 'ON'];

		foreach ($pragmas as $pragma => $value) {
			self::$conn->exec('PRAGMA '.$pragma.' = '.$value);
		}

		/**
		 * Begin a database transaction that lasts until we commit it. All database
		 * related actions will be happen in memory during this time.
		 */
		self::$conn->exec('BEGIN TRANSACTION') or output::msg('critical', 'fail in '.basename(__FILE__).'#'.__LINE__.': '.self::$conn->lastErrorMsg());
	}

	public static function commit(): void
	{
		output::msg('notice', 'updating database');
		self::$conn->exec('COMMIT') or output::msg('critical', 'fail in '.basename(__FILE__).'#'.__LINE__.': '.self::$conn->lastErrorMsg());
		self::$conn->exec('PRAGMA optimize');
		self::$conn->close();
	}
}
