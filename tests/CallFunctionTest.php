<?php

namespace Tests;

use ApiKeyStore;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/fixtures/callfunction_endpoints.php';

final class CallFunctionTest extends TestCase
{
    private static ApiKeyStore $store;
    private static string $dbPath;

    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__) . '/api_base.php';

        self::$dbPath = sys_get_temp_dir() . '/php-api-cf-' . uniqid('', true) . '.db';
        $pdo = new \PDO('sqlite:' . self::$dbPath);
        self::$store = new ApiKeyStore($pdo, 'sqlite');
        $GLOBALS['api_key_store'] = self::$store;

        self::$store->create('cfopen', 'open-secret', [
            'allowedEndpoints' => ['cf_protected'],
            'log_write' => false,
            'noTimeOut' => true,
            'cooldown' => 1,
            'sleep' => 0,
        ]);

        self::$store->create('cfcool', 'cool-secret', [
            'allowedEndpoints' => ['cf_protected'],
            'log_write' => false,
            'noTimeOut' => false,
            'cooldown' => 1,
            'sleep' => 0,
        ]);

        if (!defined('API_KEYS')) {
            define('API_KEYS', [
                'cfopen' => [
                    'key' => '',
                    'options' => ApiKeyStore::mergeDefaultOptions([
                        'allowedEndpoints' => ['cf_protected'],
                        'log_write' => false,
                        'noTimeOut' => true,
                        'cooldown' => 1,
                        'sleep' => 0,
                    ]),
                ],
                'cfcool' => [
                    'key' => '',
                    'options' => ApiKeyStore::mergeDefaultOptions([
                        'allowedEndpoints' => ['cf_protected'],
                        'log_write' => false,
                        'noTimeOut' => false,
                        'cooldown' => 1,
                        'sleep' => 0,
                    ]),
                ],
            ]);
        }
    }

    public static function tearDownAfterClass(): void
    {
        $GLOBALS['api_key_store'] = null;
        if (isset(self::$dbPath) && is_file(self::$dbPath)) {
            unlink(self::$dbPath);
        }
    }

    public function testOpenEndpointWithoutApiKey(): void
    {
        if (!endpoint_open('cf_open')) {
            $this->markTestSkipped('cf_open is not in OPEN_ENDPOINTS for this environment.');
        }

        $result = callFunction('api_cf_open', ['endpoint' => 'cf_open']);
        $this->assertIsString($result);
        $decoded = json_decode($result, true);
        $this->assertSame('OK', $decoded['status'] ?? null);
        $this->assertSame(['open' => true], $decoded['data']['response'] ?? null);
    }

    public function testProtectedEndpointWithValidKey(): void
    {
        $result = callFunction('api_cf_protected', [
            'endpoint' => 'cf_protected',
            'apikey' => 'open-secret',
        ]);
        $decoded = json_decode((string) $result, true);
        $this->assertSame('OK', $decoded['status'] ?? null);
        $this->assertSame(['protected' => true], $decoded['data']['response'] ?? null);
    }

    public function testCooldownBlocksRapidRepeat(): void
    {
        $params = [
            'endpoint' => 'cf_protected',
            'apikey' => 'cool-secret',
        ];

        $first = json_decode((string) callFunction('api_cf_protected', $params), true);
        $this->assertSame('OK', $first['status'] ?? null);

        $second = json_decode((string) callFunction('api_cf_protected', $params), true);
        $this->assertSame('ERROR', $second['status'] ?? null);
    }
}
