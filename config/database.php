<?php
/**
 * HostelEase — Database Connection (PDO Singleton)
 * 
 * Provides a single, reusable PDO connection to the MySQL database.
 * Uses prepared statements exclusively to prevent SQL injection.
 */

require_once __DIR__ . '/config.php';

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    /**
     * Private constructor — enforces Singleton pattern.
     * Establishes PDO connection with secure defaults.
     */
    private function __construct()
    {
        $dsnParts = [
            'mysql:host=' . DB_HOST,
            'port=' . DB_PORT,
            'dbname=' . DB_NAME,
            'charset=' . DB_CHARSET,
        ];

        // Aiven MySQL commonly requires SSL ("ssl-mode=REQUIRED").
        // PDO supports this via the `sslmode` DSN parameter.
        if (defined('DB_SSL') && DB_SSL) {
            $dsnParts[] = 'sslmode=require';
        }

        $dsn = implode(';', $dsnParts);

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
        ];

        if (defined('DB_SSL') && DB_SSL) {
            // Disable server certificate verification if SSL is enabled.
            // (Some deployments use self-signed/rotated certs.)
            if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
        }

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            // Friendly dev message (avoid leaking secrets).
            if (defined('APP_ENV') && APP_ENV === 'development') {
                $missing = [];
                if (empty(DB_HOST)) $missing[] = 'DB_HOST';
                if (empty(DB_PORT)) $missing[] = 'DB_PORT';
                if (empty(DB_NAME)) $missing[] = 'DB_NAME';
                if (empty(DB_USER)) $missing[] = 'DB_USER';
                if (empty(DB_PASS)) $missing[] = 'DB_PASS';
                $missingStr = empty($missing) ? '' : (' Missing: ' . implode(', ', $missing) . '.');
                die('Database connection failed.' . $missingStr . ' Check hostelease/.env values.');
            }
            die('A database error occurred. Please try again later.');
        }
    }

    /**
     * Prevent cloning of the singleton instance.
     */
    private function __clone() {}

    /**
     * Prevent unserialization of the singleton instance.
     */
    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize singleton');
    }

    /**
     * Get the singleton Database instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the PDO connection object.
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Shorthand: prepare and execute a query with parameters.
     *
     * @param string $sql    SQL query with placeholders
     * @param array  $params Bound parameters
     * @return PDOStatement
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Get the last inserted ID.
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Begin a transaction.
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit a transaction.
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Roll back a transaction.
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }
}
