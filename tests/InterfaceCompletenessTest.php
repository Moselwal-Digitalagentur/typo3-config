<?php

declare(strict_types=1);

namespace Moselwal\Tests;

use Moselwal\Config;
use Moselwal\ConfigInterface;
use PHPUnit\Framework\TestCase;

class InterfaceCompletenessTest extends TestCase
{
    public function testAllPublicMethodsOfConfigAreDeclaredInInterface(): void
    {
        $configReflection = new \ReflectionClass(Config::class);
        $interfaceReflection = new \ReflectionClass(ConfigInterface::class);

        $configMethods = [];
        foreach ($configReflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() === Config::class) {
                $configMethods[] = $method->getName();
            }
        }

        $interfaceMethods = [];
        foreach ($interfaceReflection->getMethods() as $method) {
            $interfaceMethods[] = $method->getName();
        }

        $missingMethods = array_diff($configMethods, $interfaceMethods);

        self::assertEmpty(
            $missingMethods,
            'The following public methods of Config are not declared in ConfigInterface: '
            . implode(', ', $missingMethods)
        );
    }

    public function testConfigImplementsConfigInterface(): void
    {
        self::assertTrue(
            (new \ReflectionClass(Config::class))->implementsInterface(ConfigInterface::class),
            'Config must implement ConfigInterface'
        );
    }
}
