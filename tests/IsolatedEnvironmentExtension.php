<?php

namespace Tests;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * Isolates the test process from the real environment.
 *
 * Every variable inherited from the shell is removed, and only the
 * variables declared in the <php> section of phpunit.xml are applied.
 * Variables used to control the test runner itself are preserved.
 */
final class IsolatedEnvironmentExtension implements Extension
{
    /**
     * Prefixes of variables that control the test runner and must be preserved.
     *
     * @var list<string>
     */
    private const RUNNER_VARIABLE_PREFIXES = [
        'COLLISION_',
        'LARAVEL_PARALLEL_TESTING',
        'PAO_',
        'PARATEST',
        'TEST_TOKEN',
        'UNIQUE_TEST_TOKEN',
    ];

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $this->clearInheritedVariables();

        foreach ($configuration->php()->envVariables() as $variable) {
            $value = $variable->value();

            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            $this->setVariable($variable->name(), (string) $value);
        }

        foreach ($configuration->php()->serverVariables() as $variable) {
            $_SERVER[$variable->name()] = $variable->value();
        }
    }

    private function clearInheritedVariables(): void
    {
        $names = array_unique([...array_keys(getenv()), ...array_keys($_ENV)]);

        foreach ($names as $name) {
            if ($this->isRunnerVariable($name)) {
                continue;
            }

            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }
    }

    private function setVariable(string $name, string $value): void
    {
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    private function isRunnerVariable(string $name): bool
    {
        foreach (self::RUNNER_VARIABLE_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
