<?php declare(strict_types=1);

/**
 * Copyright (c) 2020, Jos de Ruijter <jos@dutnie.nl>
 */

/**
 * Class for handling database related tasks (for the web). This is a stripped
 * down class based on "db.php". For comments see aformentioned file.
 */
class db
{
	private static string $database = '__RTFM__';
	private static SQLite3 $db;

	private function __construct() {}

	public static function changes(): int
	{
		return self::$db->changes();
	}

	public static function connect(): void
	{
		try {
			self::$db = new SQLite3(self::$database, SQLITE3_OPEN_READONLY);
		} catch (Exception $e) {
			out::put('critical', 'sqlite fail: '.$e->getMessage());
		}

		/**
		 * Prevent all changes to database files ("query_only = ON").
		 */
		$pragmas = [
			'busy_timeout' => '0',
			'query_only' => 'ON',
			'temp_store' => 'MEMORY'];

		foreach ($pragmas as $pragma => $value) {
			self::query_exec('PRAGMA '.$pragma.' = '.$value);
		}

		self::query_exec('BEGIN TRANSACTION');
	}

	public static function disconnect(): void
	{
		self::query_exec('COMMIT') or exit;
		self::$db->close();
	}

	private static function fail(): void
	{
		out::put(self::$db->lastErrorCode(), self::$db->lastErrorMsg());
	}

	public static function query(string $query): SQLite3Result
	{
		if (($results = self::$db->query($query)) === false) {
			self::fail();
		}

		return $results;
	}

	public static function query_exec(string $query): void
	{
		self::$db->exec($query) or self::fail();
	}

	public static function query_single_col(string $query)#: int|float|string|null (see PHP8 union types)
	{
		if (($result = self::$db->querySingle($query)) === false) {
			self::fail();
		}

		return $result;
	}

	public static function query_single_row(string $query): ?array
	{
		if (($result = self::$db->querySingle($query, true)) === false) {
			self::fail();
		}

		if (empty($result) || count(array_filter($result, 'is_null')) === count($result)) {
			return null;
		}

		return $result;
	}
}

/**
 * Class for handling output messages (for the web). This is a stripped down
 * class based on "out.php". For comments see aformentioned file.
 */
class out
{
	private static string $stylesheet = 'sss.css';

	private function __construct() {}

	public static function put($type, string $message): void #PHP8 int|string $type
	{
		/**
		 * Code 5 = SQLITE_BUSY, code 6 = SQLITE_LOCKED.
		 */
		if ($type === 5 || $type === 6) {
			$message = 'Statistics are currently being updated, this may take a minute.';
		}

		if (!file_exists(self::$stylesheet)) {
			header('Content-Type: text/plain');
			exit($message."\n");
		}

		exit('<!DOCTYPE html>'."\n\n".'<html><head><meta charset="utf-8"><title>seriously?</title><link rel="stylesheet" href="'.htmlspecialchars(self::$stylesheet, ENT_QUOTES | ENT_HTML5, 'UTF-8').'"></head><body><div id="container"><div class="error">'.htmlspecialchars($message, ENT_QUOTES | ENT_HTML5, 'UTF-8').'</div></div></body></html>'."\n");
	}

	public static function set_stylesheet(string $stylesheet): void
	{
		self::$stylesheet = $stylesheet;
	}
}
