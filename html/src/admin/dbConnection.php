<?php

declare(strict_types=1);

namespace App\Admin\Database;

use mysqli;
use mysqli_sql_exception;
use RuntimeException;

require_once __DIR__ . '/Dotenv.php';

// Auto-load .env file from project root
Dotenv::load(__DIR__ . '/../../../.env');

// Set PHP application timezone to GMT +05:30 (Asia/Kolkata / IST)
$appTimezone = getenv('APP_TIMEZONE') ?: 'Asia/Kolkata';
date_default_timezone_set($appTimezone);

/**
 * Database Connection Manager
 *
 * Provides MySQLi database connection management using Dotenv configuration,
 * fallback host checking, strict typing, and robust error handling.
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
        ?string $host = null,
        ?string $username = null,
        ?string $password = null,
        ?string $database = null,
        ?int $port = null,
        ?string $charset = null
    ) {
        $this->host = $host ?? (getenv('DB_HOST') ?: '127.0.0.1');
        $this->username = $username ?? (getenv('DB_USER') ?: 'root');
        $this->password = $password ?? (getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '');
        $this->database = $database ?? (getenv('DB_NAME') ?: 'service_provider');
        $this->port = $port ?? ((int) (getenv('DB_PORT') ?: 3306));
        $this->charset = $charset ?? (getenv('DB_CHARSET') ?: 'utf8mb4');

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    }

    /**
     * Factory method to initialize connection settings from environment variables (.env).
     */
    public static function createFromEnv(): self
    {
        return new self();
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
     * Establishes database connection checking Dotenv primary host & local fallback.
     */
    private function connect(): void
    {
        $hostsToTry = [
            [
                'host' => $this->host,
                'username' => $this->username,
                'password' => $this->password,
                'database' => $this->database,
                'port' => $this->port,
            ],
        ];

        if (getenv('DB_FALLBACK_HOST')) {
            $hostsToTry[] = [
                'host' => (string) getenv('DB_FALLBACK_HOST'),
                'username' => (string) (getenv('DB_FALLBACK_USER') ?: 'root'),
                'password' => getenv('DB_FALLBACK_PASS') !== false ? (string) getenv('DB_FALLBACK_PASS') : '',
                'database' => (string) (getenv('DB_FALLBACK_NAME') ?: 'service_provider'),
                'port' => (int) (getenv('DB_FALLBACK_PORT') ?: 3306),
            ];
        }

        $lastException = null;

        foreach ($hostsToTry as $cfg) {
            try {
                mysqli_report(MYSQLI_REPORT_OFF);
                $conn = @new mysqli(
                    $cfg['host'],
                    $cfg['username'],
                    $cfg['password'],
                    $cfg['database'],
                    $cfg['port']
                );

                if (!$conn->connect_errno) {
                    $conn->set_charset($this->charset);
                    
                    // Set MySQL session timezone to GMT +05:30
                    $dbTimezone = getenv('DB_TIMEZONE') ?: '+05:30';
                    @$conn->query("SET time_zone = '{$dbTimezone}'");

                    $this->connection = $conn;
                    $this->host = $cfg['host'];
                    $this->username = $cfg['username'];
                    $this->database = $cfg['database'];
                    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                    return;
                }

                $lastException = new RuntimeException($conn->connect_error, $conn->connect_errno);
            } catch (\Throwable $e) {
                $lastException = $e;
            }
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        error_log('Database Connection Failure: ' . ($lastException ? $lastException->getMessage() : 'Unknown error'));
        throw new RuntimeException('Database connection error: ' . ($lastException ? $lastException->getMessage() : 'Could not connect to database host.'), 500, $lastException);
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
            'message' => 'Hooray Database Connected Successfully via Dotenv.',
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
