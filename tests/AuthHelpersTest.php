<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

final class AuthHelpersTest extends TestCase
{
    public function testMergeAuthHeadersFromApikeyHeader(): void
    {
        $_SERVER['HTTP_APIKEY'] = 'header-key-value';
        $merged = mergeAuthHeaders(['endpoint' => 'datetime']);
        $this->assertSame('header-key-value', $merged['apikey']);
        unset($_SERVER['HTTP_APIKEY']);
    }

    public function testMergeAuthHeadersBearer(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer bearer-token-xyz';
        $merged = mergeAuthHeaders([]);
        $this->assertSame('bearer-token-xyz', $merged['apikey']);
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    public function testRedactSensitiveParams(): void
    {
        $redacted = redactSensitiveParams([
            'endpoint' => 'datetime',
            'apikey' => 'secret',
            'key' => 'also-secret',
        ]);
        $this->assertSame('[REDACTED]', $redacted['apikey']);
        $this->assertSame('[REDACTED]', $redacted['key']);
        $this->assertSame('datetime', $redacted['endpoint']);
    }

    public function testResolveEndpointAlias(): void
    {
        if (!defined('ENDPOINT_ALIASES')) {
            define('ENDPOINT_ALIASES', ['date' => 'datetime']);
        }
        $this->assertSame('datetime', resolveEndpointName('date'));
        $this->assertSame('quote', resolveEndpointName('quote'));
    }
}
