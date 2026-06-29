<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

final class EndpointDiscoveryTest extends TestCase
{
    private string $fixtureFile;

    protected function setUp(): void
    {
        require_once dirname(__DIR__) . '/lib/endpoint_discovery.php';
        $this->fixtureFile = sys_get_temp_dir() . '/api-discovery-test-' . uniqid('', true) . '.php';
        file_put_contents($this->fixtureFile, <<<'PHP'
<?php
function api_discovery_sample(
    string $required,
    int $optional = 42
): array {
    return ['ok' => true];
}
PHP);
    }

    protected function tearDown(): void
    {
        if (is_file($this->fixtureFile)) {
            unlink($this->fixtureFile);
        }
    }

    public function testDiscoversFunctionFromFile(): void
    {
        $functions = discoverApiFunctionsReflection($this->fixtureFile);
        $this->assertCount(1, $functions);
        $this->assertSame('api_discovery_sample', $functions[0]['name']);
        $this->assertSame('discovery_sample', $functions[0]['clean']);
    }

    public function testReflectionParametersWhenFunctionLoaded(): void
    {
        require_once $this->fixtureFile;
        $functions = discoverApiFunctionsReflection($this->fixtureFile);
        $this->assertCount(2, $functions[0]['parameters']);
        $this->assertSame('required', $functions[0]['parameters'][0]['name']);
        $this->assertFalse($functions[0]['parameters'][0]['optional']);
        $this->assertTrue($functions[0]['parameters'][1]['optional']);
        $this->assertSame(42, $functions[0]['parameters'][1]['default']);
    }
}
