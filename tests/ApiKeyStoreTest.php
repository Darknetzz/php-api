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

    public function testListDetailed(): void
    {
        $this->store->create('alpha', 'key-alpha', ['allowedEndpoints' => ['datetime']]);
        $this->store->create('beta', 'key-beta');
        $this->store->disable('beta');

        $all = $this->store->listDetailed();
        $this->assertCount(2, $all);

        $alpha = $all[0];
        $this->assertSame('alpha', $alpha['name']);
        $this->assertTrue($alpha['enabled']);
        $this->assertSame(['datetime'], $alpha['options']['allowedEndpoints']);
        $this->assertNotEmpty($alpha['created_at']);
        $this->assertNull($alpha['last_used_at']);

        $beta = $all[1];
        $this->assertSame('beta', $beta['name']);
        $this->assertFalse($beta['enabled']);
    }

    public function testHashKeyDeterministic(): void
    {
        $hash = ApiKeyStore::hashKey('my-key');
        $this->assertSame(ApiKeyStore::hashKey('my-key'), $hash);
        $this->assertNotSame(ApiKeyStore::hashKey('other'), $hash);
    }

    public function testUpdateKeyRenameAndEndpoints(): void
    {
        $this->store->create('alpha', 'key-alpha', ['allowedEndpoints' => ['datetime'], 'noTimeOut' => true]);
        $this->store->updateKey('alpha', 'alpha-renamed', ['faker', 'datetime']);

        $this->assertNull($this->store->getByName('alpha'));
        $row = $this->store->getByName('alpha-renamed');
        $this->assertNotNull($row);
        $this->assertSame(['faker', 'datetime'], $row['options']['allowedEndpoints']);
        $this->assertTrue($row['options']['noTimeOut']);
        $this->assertSame('alpha-renamed', $this->store->validate('key-alpha'));
    }

    public function testUpdateKeyRejectsDuplicateName(): void
    {
        $this->store->create('alpha', 'key-alpha');
        $this->store->create('beta', 'key-beta');

        $this->expectException(\InvalidArgumentException::class);
        $this->store->updateKey('alpha', 'beta', ['*']);
    }

    public function testEnableReactivatesDisabledKey(): void
    {
        $this->store->create('alpha', 'key-alpha');
        $this->store->disable('alpha');
        $this->assertNull($this->store->validate('key-alpha'));

        $this->store->enable('alpha');
        $this->assertSame('alpha', $this->store->validate('key-alpha'));
    }

    public function testRotateChangesSecret(): void
    {
        $this->store->create('alpha', 'old-secret');
        $newSecret = $this->store->rotate('alpha');

        $this->assertNotSame('old-secret', $newSecret);
        $this->assertNull($this->store->validate('old-secret'));
        $this->assertSame('alpha', $this->store->validate($newSecret));
    }

    public function testUpdateOptionsPatchesFields(): void
    {
        $this->store->create('alpha', 'key-alpha', ['cooldown' => 1]);
        $this->store->updateOptions('alpha', ['cooldown' => 9, 'noTimeOut' => true]);

        $row = $this->store->getByName('alpha');
        $this->assertNotNull($row);
        $this->assertSame(9, $row['options']['cooldown']);
        $this->assertTrue($row['options']['noTimeOut']);
    }
}
