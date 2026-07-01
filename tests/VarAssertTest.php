<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

final class VarAssertTest extends TestCase
{
    public function testByValueDoesNotCreateMissingArrayKeys(): void
    {
        require_once dirname(__DIR__) . '/api_base.php';

        $params = [];
        @var_assert($params['filterdata']);
        $this->assertArrayNotHasKey('filterdata', $params);
    }

    public function testRejectsFalsyPresenceValues(): void
    {
        require_once dirname(__DIR__) . '/api_base.php';

        $empty = [];
        $this->assertFalse(var_assert($empty));
        $this->assertFalse(var_assert(0));
        $this->assertFalse(var_assert(false));
        $this->assertTrue(var_assert('value'));
    }
}
