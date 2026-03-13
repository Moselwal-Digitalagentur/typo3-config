<?php

declare(strict_types=1);

namespace Moselwal\Tests;

use Moselwal\Config;

class SingletonTest extends ConfigTestCase
{
    /**
     * @test
     */
    public function initializeCreatesInstance(): void
    {
        $instance = Config::initialize(false);
        self::assertInstanceOf(Config::class, $instance);
    }

    /**
     * @test
     */
    public function getReturnsSameInstance(): void
    {
        $instance = Config::initialize(false);
        $retrieved = Config::get();
        self::assertSame($instance, $retrieved);
    }

    /**
     * @test
     */
    public function secondInitializeResetsInstance(): void
    {
        $first = Config::initialize(false);
        $second = Config::initialize(false);
        self::assertNotSame($first, $second);
        self::assertSame($second, Config::get());
    }

    /**
     * @test
     */
    public function lateStaticBindingWorksWithSubclass(): void
    {
        $instance = TestableConfig::initialize(false);
        self::assertInstanceOf(TestableConfig::class, $instance);
        self::assertInstanceOf(Config::class, $instance);
    }

    /**
     * @test
     */
    public function initializeWithDefaultsAppliesPresets(): void
    {
        // Testing context is set in setUp, so applyDefaults will run
        $instance = Config::initialize(true);
        self::assertInstanceOf(Config::class, $instance);
        // Verify defaults were applied: forbidInvalidCacheHashQueryParameter sets this to true
        self::assertTrue($GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError']);
        self::assertTrue($GLOBALS['TYPO3_CONF_VARS']['FE']['disableNoCacheParameter']);
    }

    /**
     * @test
     */
    public function fluentInterfaceReturnsInstance(): void
    {
        $instance = Config::initialize(false);
        $result = $instance->forbidNoCacheQueryParameter();
        self::assertSame($instance, $result);
    }
}
