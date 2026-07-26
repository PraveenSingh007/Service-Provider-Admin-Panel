<?php

declare(strict_types=1);

namespace App\Database;

use mysqli;
use mysqli_sql_exception;
use RuntimeException;

/**
 * Database Connection Manager
 *
 * Provides MySQLi database connection management using strict typing,
 * environment configuration, and robust error handling.
 */
class DatabaseConnection
{
    private ?mysqli $connection = null;
    private string $host;
    private string $username;
    private string $password;
    private string $database;
    private int $port;
    private string $charset;

    public function __construct(
        string $host = 'localhost',
        string $username = 'root',
        string $password = '',
        string $database = 'service_provider',
        int $port = 3306,
        string $charset = 'utf8mb4'
    ) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->port = $port;
        $this->charset = $charset;

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    }

    /**
     * Factory method to initialize connection settings from environment variables.
     */
    public static function createFromEnv(): self
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $username = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '';
        $database = getenv('DB_NAME') ?: 'service_provider';
        $port = (int) (getenv('DB_PORT') ?: 3306);
        $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

        return new self($host, $username, $password, $database, $port, $charset);
    }

    /**
     * Returns an active MySQLi database connection.
     */
    public function getConnection(): mysqli
    {
        if ($this->connection === null) {
            $this->connect();
        }

        return $this->connection;
    }

    /**
     * Establishes the database connection.
     */
    private function connect(): void
    {
        try {
            $this->connection = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->database,
                $this->port
            );

            $this->connection->set_charset($this->charset);
        } catch (mysqli_sql_exception $e) {
            error_log('Database Connection Failure: ' . $e->getMessage());
            throw new RuntimeException('Database connection error: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Get diagnostic connection details (safe for output).
     *
     * @return array<string, mixed>
     */
    public function getConnectionDetails(): array
    {
        return [
            'host' => $this->host,
            'database' => $this->database,
            'charset' => $this->charset,
            'server_info' => $this->connection !== null ? $this->connection->server_info : null,
            'host_info' => $this->connection !== null ? $this->connection->host_info : null,
        ];
    }
}

// Global initialization for backward compatibility ($conn variable)
try {
    $db = DatabaseConnection::createFromEnv();
    $conn = $db->getConnection();

    if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'success' => true,
            'message' => 'Hooray Localhost Database Connected Successfully.',
            'data' => $db->getConnectionDetails(),
        ], JSON_PRETTY_PRINT);
    }
} catch (RuntimeException $e) {
    $conn = null;

    if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed.',
            'errors' => [$e->getMessage()],
        ], JSON_PRETTY_PRINT);
    }
}
