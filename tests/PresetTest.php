<?php

declare(strict_types=1);

namespace Moselwal\Tests;

use Moselwal\Config;
use phpmock\phpunit\PHPMock;

class PresetTest extends ConfigTestCase
{
    use PHPMock;

    /**
     * @test
     */
    public function useCliPresetSetsDebugFlags(): void
    {
        $instance = Config::initialize(false);
        $instance->useCliPreset();

        self::assertTrue($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']);
        self::assertTrue($GLOBALS['TYPO3_CONF_VARS']['BE']['debug']);
        self::assertSame('*', $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']);
        self::assertSame(1, $GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors']);
        self::assertSame(0, $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLogLevel']);
    }

    /**
     * @test
     */
    public function useProductionPresetDisablesDebug(): void
    {
        // Ensure LOG writerConfiguration exists for array_replace_recursive
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'] = [];

        $instance = Config::initialize(false);
        $instance->useProductionPreset();

        self::assertFalse($GLOBALS['TYPO3_CONF_VARS']['BE']['debug']);
        self::assertFalse($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']);
        self::assertSame('', $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']);
        self::assertSame(-1, $GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors']);
    }

    /**
     * @test
     */
    public function useDevelopmentPresetEnablesDebugAndMailpit(): void
    {
        $instance = Config::initialize(false);
        $instance->useDevelopmentPreset();

        self::assertTrue($GLOBALS['TYPO3_CONF_VARS']['BE']['debug']);
        self::assertTrue($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']);
        self::assertSame('*', $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']);
        self::assertSame(1, $GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors']);
        // Mailpit configured
        self::assertSame('smtp', $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport']);
        self::assertSame('', $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_password']);
    }

    /**
     * @test
     */
    public function useProductionPresetVHostUsesFileWriter(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'] = [];

        $instance = Config::initialize(false);
        $instance->useProductionPresetVHost();

        self::assertFalse($GLOBALS['TYPO3_CONF_VARS']['BE']['debug']);
        self::assertFalse($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']);

        // VHost preset uses FileWriter instead of PhpErrorLogWriter
        $writerConfig = $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'];
        self::assertArrayHasKey(\TYPO3\CMS\Core\Log\LogLevel::ERROR, $writerConfig);
        self::assertArrayHasKey(
            \TYPO3\CMS\Core\Log\Writer\FileWriter::class,
            $writerConfig[\TYPO3\CMS\Core\Log\LogLevel::ERROR]
        );
    }

    /**
     * @test
     */
    public function useCliPresetDisablesSSLVerification(): void
    {
        $instance = Config::initialize(false);
        $instance->useCliPreset();

        self::assertSame(0, $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_host']);
        self::assertSame(0, $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_peer']);
    }

    /**
     * @test
     */
    public function enableDeprecationLoggingSetsCorrectFlag(): void
    {
        $instance = Config::initialize(false);
        $instance->enableDeprecationLogging();

        self::assertFalse(
            $GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['CMS']['deprecations']['writerConfiguration']
            [\TYPO3\CMS\Core\Log\LogLevel::NOTICE]
            ['TYPO3\CMS\Core\Log\Writer\FileWriter']['disabled']
        );
    }

    /**
     * @test
     */
    public function disableDeprecationLoggingSetsCorrectFlag(): void
    {
        $instance = Config::initialize(false);
        $instance->disableDeprecationLogging();

        self::assertTrue(
            $GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['CMS']['deprecations']['writerConfiguration']
            [\TYPO3\CMS\Core\Log\LogLevel::NOTICE]
            ['TYPO3\CMS\Core\Log\Writer\FileWriter']['disabled']
        );
    }

    /**
     * @test
     */
    public function useDevelopmentPresetSetsLockSSLFalse(): void
    {
        $instance = Config::initialize(false);
        $instance->useDevelopmentPreset();

        self::assertFalse($GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL']);
    }

    /**
     * @test
     */
    public function useProductionPresetSetsExceptionalErrors(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'] = [];

        $instance = Config::initialize(false);
        $instance->useProductionPreset();

        $expected = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
        self::assertSame($expected, $GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors']);
        self::assertSame($expected, $GLOBALS['TYPO3_CONF_VARS']['SYS']['belogErrorReporting']);
    }
}
