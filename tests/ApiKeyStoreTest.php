<?php

namespace Tests;

use ApiKeyStore;
use PHPUnit\Framework\TestCase;

final class ApiKeyStoreTest extends TestCase
{
    private ApiKeyStore $store;
    private string $dbPath;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/php-api-testcase-' . uniqid('', true) . '.db';
        if (!defined('KEY_STORE_DSN')) {
            define('KEY_STORE_DSN', $this->dbPath);
        }

        $pdo = new \PDO('sqlite:' . $this->dbPath);
        $this->store = new ApiKeyStore($pdo, 'sqlite');
    }

    protected function tearDown(): void
    {
        if (is_file($this->dbPath)) {
            unlink($this->dbPath);
        }
    }

    public function testValidateValidKey(): void
    {
        $this->store->create('test', 'secret-key-12345', ['allowedEndpoints' => ['datetime']]);
        $this->assertSame('test', $this->store->validate('secret-key-12345'));
    }

    public function testValidateInvalidKey(): void
    {
        $this->store->create('test', 'secret-key-12345');
        $this->assertNull($this->store->validate('wrong-key'));
    }

    public function testDisabledKeyRejected(): void
    {
        $this->store->create('test', 'secret-key-12345');
        $this->store->disable('test');
        $this->assertNull($this->store->validate('secret-key-12345'));
    }

    public function testLoadAllShape(): void
    {
        $this->store->create('alpha', 'key-alpha', ['noTimeOut' => true]);
        $all = $this->store->loadAll();
        $this->assertArrayHasKey('alpha', $all);
        $this->assertSame('', $all['alpha']['key']);
        $this->assertTrue($all['alpha']['options']['noTimeOut']);
    }

    public function testHashKeyDeterministic(): void
    {
        $hash = ApiKeyStore::hashKey('my-key');
        $this->assertSame(ApiKeyStore::hashKey('my-key'), $hash);
        $this->assertNotSame(ApiKeyStore::hashKey('other'), $hash);
    }
}
