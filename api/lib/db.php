<?php
/**
 * Database Connection Helper
 *
 * Provides a singleton PDO connection to PostgreSQL.
 */

require_once __DIR__ . '/../config.php';

/**
 * Get the PDO database connection
 *
 * @return PDO
 * @throws PDOException if connection fails
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'pgsql:host=%s;dbname=%s;options=--client_encoding=UTF8',
            DB_HOST,
            DB_NAME
        );

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $pdo;
}

/**
 * Execute a query and return all results
 *
 * @param string $sql SQL query with named placeholders
 * @param array $params Parameters to bind
 * @return array
 */
function dbQueryAll(string $sql, array $params = []): array
{
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Execute a query and return single row
 *
 * @param string $sql SQL query with named placeholders
 * @param array $params Parameters to bind
 * @return array|null
 */
function dbQueryOne(string $sql, array $params = []): ?array
{
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Execute an INSERT/UPDATE/DELETE and return affected row count
 *
 * @param string $sql SQL query with named placeholders
 * @param array $params Parameters to bind
 * @return int Number of affected rows
 */
function dbExecute(string $sql, array $params = []): int
{
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Get the last inserted ID
 *
 * @param string|null $name Sequence name (optional for PostgreSQL)
 * @return string
 */
function dbLastInsertId(?string $name = null): string
{
    return getDB()->lastInsertId($name);
}
