<?php

/**
 * SQLite / Turso-backed API key storage. Keys are stored as SHA-256 hashes only.
 */
class ApiKeyStore
{
    private const SCHEMA = <<<'SQL'
CREATE TABLE IF NOT EXISTS api_keys (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    name         TEXT NOT NULL UNIQUE,
    key_hash     TEXT NOT NULL UNIQUE,
    options      TEXT NOT NULL DEFAULT '{}',
    enabled      INTEGER NOT NULL DEFAULT 1,
    created_at   TEXT NOT NULL DEFAULT (datetime('now')),
    last_used_at TEXT
);
SQL;

    private object $conn;
    private string $driver;

    public function __construct(object $conn, string $driver)
    {
        $this->conn = $conn;
        $this->driver = $driver;
        $this->ensureSchema();
    }

    public static function hashKey(string $key): string
    {
        return hash('sha256', $key);
    }

    public static function createFromConfig(): ?self
    {
        if (!defined('KEY_STORE_DRIVER') || KEY_STORE_DRIVER === 'php') {
            return null;
        }

        $driver = KEY_STORE_DRIVER;

        if ($driver === 'sqlite') {
            return new self(self::connectSqlite(), 'sqlite');
        }

        if ($driver === 'turso') {
            return new self(self::connectTurso(), 'turso');
        }

        throw new RuntimeException("Unknown KEY_STORE_DRIVER: $driver");
    }

    private static function defaultSqlitePath(): string
    {
        if (defined('KEY_STORE_DSN') && KEY_STORE_DSN !== '') {
            return KEY_STORE_DSN;
        }

        return dirname(__DIR__) . '/data/api.db';
    }

    private static function connectSqlite(): PDO
    {
        $path = self::defaultSqlitePath();
        $dir = dirname($path);

        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new RuntimeException("Unable to create key store directory: $dir");
        }

        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }

    private static function connectTurso(): object
    {
        $url = defined('KEY_STORE_URL') ? KEY_STORE_URL : '';
        $token = defined('KEY_STORE_TOKEN') ? KEY_STORE_TOKEN : (getenv('TURSO_AUTH_TOKEN') ?: '');

        if ($url === '' || $token === '') {
            throw new RuntimeException('Turso requires KEY_STORE_URL and KEY_STORE_TOKEN (or TURSO_AUTH_TOKEN env var)');
        }

        $autoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (is_file($autoload)) {
            require_once $autoload;
        }

        if (class_exists('Libsql\\Database')) {
            $db = new Libsql\Database(['url' => $url, 'authToken' => $token]);
            return $db->connect();
        }

        throw new RuntimeException(
            'Turso driver requires turso/libsql. Run: composer require turso/libsql'
        );
    }

    private function ensureSchema(): void
    {
        $this->exec(self::SCHEMA);
    }

    private function exec(string $sql, array $params = []): void
    {
        if ($this->conn instanceof PDO) {
            if ($params === []) {
                $this->conn->exec($sql);
                return;
            }
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return;
        }

        if (method_exists($this->conn, 'execute')) {
            $this->conn->execute($sql, $params);
            return;
        }

        throw new RuntimeException('Unsupported database connection type');
    }

    /** @return array<string, mixed>|null */
    private function fetchOne(string $sql, array $params = []): ?array
    {
        if ($this->conn instanceof PDO) {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch();
            return $row === false ? null : $row;
        }

        if (method_exists($this->conn, 'query')) {
            $result = $this->conn->query($sql, $params);
            if (method_exists($result, 'fetchArray')) {
                $row = $result->fetchArray();
                return $row === false ? null : $row;
            }
            if (method_exists($result, 'fetch')) {
                $row = $result->fetch();
                return $row === false ? null : $row;
            }
        }

        throw new RuntimeException('Unsupported database connection type');
    }

    /** @return list<array<string, mixed>> */
    private function fetchAll(string $sql, array $params = []): array
    {
        if ($this->conn instanceof PDO) {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        if (method_exists($this->conn, 'query')) {
            $result = $this->conn->query($sql, $params);
            $rows = [];
            if (method_exists($result, 'fetchArray')) {
                while ($row = $result->fetchArray()) {
                    $rows[] = $row;
                }
                return $rows;
            }
            if (method_exists($result, 'fetchAll')) {
                return $result->fetchAll();
            }
        }

        throw new RuntimeException('Unsupported database connection type');
    }

    /** Validate raw key; returns key name or null. */
    public function validate(string $key): ?string
    {
        if ($key === '') {
            return null;
        }

        $hash = self::hashKey($key);
        $row = $this->fetchOne(
            'SELECT name FROM api_keys WHERE key_hash = ? AND enabled = 1',
            [$hash]
        );

        if ($row === null) {
            return null;
        }

        $this->exec(
            "UPDATE api_keys SET last_used_at = datetime('now') WHERE key_hash = ?",
            [$hash]
        );

        return $row['name'];
    }

    /** @return array<string, array{key: string, options: array<string, mixed>}> */
    public function loadAll(): array
    {
        $rows = $this->fetchAll(
            'SELECT name, options FROM api_keys WHERE enabled = 1 ORDER BY name'
        );
        $keys = [];

        foreach ($rows as $row) {
            $options = json_decode($row['options'], true);
            if (!is_array($options)) {
                $options = [];
            }
            $keys[$row['name']] = [
                'key' => '',
                'options' => $options,
            ];
        }

        return $keys;
    }

    /** @return list<array{name: string, enabled: bool, options: array<string, mixed>, created_at: string, last_used_at: ?string}> */
    public function listDetailed(): array
    {
        $rows = $this->fetchAll(
            'SELECT name, options, enabled, created_at, last_used_at FROM api_keys ORDER BY name'
        );
        $keys = [];

        foreach ($rows as $row) {
            $options = json_decode($row['options'], true);
            if (!is_array($options)) {
                $options = [];
            }
            $keys[] = [
                'name' => $row['name'],
                'enabled' => (bool) $row['enabled'],
                'options' => $options,
                'created_at' => $row['created_at'],
                'last_used_at' => $row['last_used_at'],
            ];
        }

        return $keys;
    }

    /** @param array<string, mixed> $options */
    public function create(string $name, string $key, array $options = []): void
    {
        $options = self::mergeDefaultOptions($options);
        $hash = self::hashKey($key);

        $this->exec(
            'INSERT INTO api_keys (name, key_hash, options) VALUES (?, ?, ?)',
            [$name, $hash, json_encode($options, JSON_THROW_ON_ERROR)]
        );
    }

    public function disable(string $name): void
    {
        $this->exec('UPDATE api_keys SET enabled = 0 WHERE name = ?', [$name]);
    }

    public function exists(string $name): bool
    {
        $row = $this->fetchOne('SELECT id FROM api_keys WHERE name = ?', [$name]);
        return $row !== null;
    }

    /** @param array<string, mixed> $options */
    public static function mergeDefaultOptions(array $options): array
    {
        $defaults = defined('APIKEY_DEFAULT_OPTIONS') ? APIKEY_DEFAULT_OPTIONS : [];

        foreach ($defaults as $optName => $optVal) {
            if (!isset($options[$optName])) {
                $options[$optName] = $optVal;
            }
        }

        return $options;
    }

    public static function generateKey(int $length = 64): string
    {
        return bin2hex(random_bytes((int) ceil($length / 2)));
    }
}
