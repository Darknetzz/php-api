<?php

namespace Tests;

use ApiKeyStore;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Turso driver requires turso/libsql via Composer.
 */
final class TursoDriverTest extends TestCase
{
    public function testTursoConnectThrowsWithoutLibsqlPackage(): void
    {
        if (class_exists('Libsql\\Database')) {
            $this->markTestSkipped('turso/libsql is installed; skipping missing-package test.');
        }

        if (!defined('KEY_STORE_URL')) {
            define('KEY_STORE_URL', 'libsql://example.turso.io');
        }
        if (!defined('KEY_STORE_TOKEN')) {
            define('KEY_STORE_TOKEN', 'test-token');
        }

        $ref = new ReflectionClass(ApiKeyStore::class);
        $method = $ref->getMethod('connectTurso');
        $method->setAccessible(true);

        $this->expectException(\RuntimeException::class);
        $method->invoke(null);
    }
}
