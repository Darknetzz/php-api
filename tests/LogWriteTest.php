<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

final class LogWriteTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__) . '/api_base.php';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        unset($GLOBALS['apikey_logging']);
        if (is_file(LOG_FILE)) {
            unlink(LOG_FILE);
        }
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['apikey_logging']);
        if (is_file(LOG_FILE)) {
            unlink(LOG_FILE);
        }
    }

    public function testRespectsPerKeyLoggingDisabledViaGlobal(): void
    {
        $GLOBALS['apikey_logging'] = false;

        log_write('must not be logged', 'info');

        $this->assertFileDoesNotExist(LOG_FILE);
    }

    public function testRespectsPerKeyLoggingEnabledViaGlobal(): void
    {
        $GLOBALS['apikey_logging'] = true;

        log_write('must be logged', 'info');

        $this->assertFileExists(LOG_FILE);
        $this->assertStringContainsString('must be logged', (string) file_get_contents(LOG_FILE));
    }

    public function testUsesDefaultWhenGlobalNotSet(): void
    {
        $expected = (bool) (
            defined('APIKEY_DEFAULT_OPTIONS') && is_array(APIKEY_DEFAULT_OPTIONS)
                ? (APIKEY_DEFAULT_OPTIONS['log_write'] ?? true)
                : true
        );

        log_write('uses default option', 'info');

        if ($expected) {
            $this->assertFileExists(LOG_FILE);
            $this->assertStringContainsString('uses default option', (string) file_get_contents(LOG_FILE));
        } else {
            $this->assertFileDoesNotExist(LOG_FILE);
        }
    }
}
