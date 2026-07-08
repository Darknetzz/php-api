<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

final class ErrTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__) . '/api_base.php';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $GLOBALS['apikey_logging'] = false;
    }

    public function testErrorDataIsHtmlEscaped(): void
    {
        $payload = '<script>alert(1)</script>';
        $json = err($payload, 400, false);
        $decoded = json_decode($json, true);

        $this->assertSame('ERROR', $decoded['status'] ?? null);
        $this->assertSame(
            htmlspecialchars($payload, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $decoded['data'] ?? null
        );
        $this->assertStringNotContainsString('<script>', $decoded['data'] ?? '');
    }
}
