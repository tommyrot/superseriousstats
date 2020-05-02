<?php declare(strict_types=1);

/**
 * Copyright (c) 2020, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Class for handling database related tasks.
 */
class db
{
	private static string $database = '';
	private static SQLite3 $conn;

	/**
	 * This is a static class and should not be instantiated.
	 */
	private function __construct() {}

	/**
	 * Return the number of database rows that were changed (or inserted or deleted)
	 * by the most recent SQL statement.
	 */
	public static function changes(): int
	{
		return self::$conn->changes();
	}

	/**
	 * Open a database connection and start a transaction.
	 */
	public static function connect(): void
	{
		/**
		 * Fail if we encounter an exception.
		 */
		try {
			self::$conn = new SQLite3(self::$database, SQLITE3_OPEN_READWRITE);
			self::$conn->busyTimeout(60000);
			output::msg('notice', 'succesfully connected to database: \''.self::$database.'\'');
		} catch (Exception $e) {
			output::msg('critical', 'sqlite fail: '.$e->getMessage());
		}

		/**
		 * Setup the SQLite connection:
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
		 * Begin a database transaction that lasts until we COMMIT. All database
		 * related actions will be happen in memory during this time.
		 */
		self::$conn->exec('BEGIN TRANSACTION') or self::error();
	}

	/**
	 * Close the database connection after committing any changes in memory to disk
	 * and running a "PRAGMA optimize" as per SQLite recommendations.
	 */
	public static function disconnect(): void
	{
		output::msg('notice', 'updating database');
		self::$conn->exec('COMMIT') or self::error();
		self::$conn->exec('PRAGMA optimize');
		self::$conn->close();
	}

	/**
	 * Output the text describing the most recent failed SQLite request and exit.
	 */
	private static function error(): void
	{
		output::msg('critical', 'sqlite fail: '.self::$conn->lastErrorMsg());
	}

	/**
	 * Execute a query and return the SQLite3Result object.
	 */
	public static function query(string $query): SQLite3Result
	{
		if (($results = self::$conn->query($query)) === false) {
			self::error();
		}

		return $results;
	}

	/**
	 * Execute a resultless query.
	 */
	public static function query_exec(string $query): ?int
	{
		self::$conn->exec($query) or self::error();

		/**
		 * Return the row id of the most recent INSERT (logic in the calling function
		 * should decide if this value has meaning or purpose).
		 */
		if (strpos($query, 'INSERT') === 0) {
			return self::$conn->lastInsertRowID();
		}

		return null;
	}

	/**
	 * Execute a query and return the single column result.
	 */
	public static function query_single_col(string $query)#: int|float|string|null (see PHP8 union types)
	{
		if (($result = self::$conn->querySingle($query)) === false) {
			self::error();
		}

		return $result;
	}

	/**
	 * Execute a query and return the single row result.
	 */
	public static function query_single_row(string $query): ?array
	{
		if (($result = self::$conn->querySingle($query, true)) === false) {
			self::error();
		}

		/**
		 * Return null instead of an empty array.
		 */
		if (empty($result)) {
			return null;
		}

		return $result;
	}

	/**
	 * Set the path to the SQLite database.
	 */
	public static function set_database(string $database): void
	{
		self::$database = $database;
	}
}
