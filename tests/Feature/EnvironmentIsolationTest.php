<?php

namespace Tests\Feature;

use Tests\TestCase;

class EnvironmentIsolationTest extends TestCase
{
    public function test_variables_inherited_from_the_shell_are_not_visible(): void
    {
        $this->assertFalse(getenv('PATH'));
        $this->assertArrayNotHasKey('PATH', $_ENV);
        $this->assertArrayNotHasKey('PATH', $_SERVER);
    }

    public function test_configuration_comes_only_from_phpunit_xml(): void
    {
        $this->assertSame('testing', config('app.env'));
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('array', config('cache.default'));
    }
}
