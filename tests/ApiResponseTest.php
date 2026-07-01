<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

final class ApiResponseTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__) . '/api_base.php';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $GLOBALS['apikey_logging'] = false;
    }

    public function testCompactTrueReturnsSingleLineJson(): void
    {
        $_REQUEST = ['compact' => 'true'];
        $json = api_response('OK', ['response' => ['a' => 1]]);

        $this->assertStringNotContainsString("\n    ", $json);
        $this->assertSame('OK', json_decode($json, true)['status'] ?? null);
    }

    public function testDefaultResponseIsPrettyPrinted(): void
    {
        $_REQUEST = [];
        $json = api_response('OK', ['response' => ['a' => 1]]);

        $this->assertStringContainsString("\n    ", $json);
    }

    public function testRequestFlagEnabledRecognizesCompactValues(): void
    {
        $this->assertTrue(requestFlagEnabled('true'));
        $this->assertTrue(requestFlagEnabled('1'));
        $this->assertFalse(requestFlagEnabled(null));
        $this->assertFalse(requestFlagEnabled('false'));
    }
}
