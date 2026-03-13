<?php

declare(strict_types=1);

namespace Moselwal\Tests;

use Moselwal\Config;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;

abstract class ConfigTestCase extends TestCase
{
    private array $originalConfVars = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConfVars = $GLOBALS['TYPO3_CONF_VARS'] ?? [];

        // Reset singleton instance via Reflection
        $this->resetSingleton();

        // Initialize TYPO3 Environment with test values
        Environment::initialize(
            new ApplicationContext('Testing'),
            true,
            false,
            '/tmp/typo3-test',
            '/tmp/typo3-test/public',
            '/tmp/typo3-test/var',
            '/tmp/typo3-test/config',
            '/tmp/typo3-test/public/index.php',
            'UNIX'
        );

        // Ensure base TYPO3_CONF_VARS structure exists
        if (!isset($GLOBALS['TYPO3_CONF_VARS'])) {
            $GLOBALS['TYPO3_CONF_VARS'] = [];
        }
        $GLOBALS['TYPO3_CONF_VARS']['SYS'] = $GLOBALS['TYPO3_CONF_VARS']['SYS'] ?? [];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?? 'Test Site';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching'] ?? ['cacheConfigurations' => []];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locking'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['locking'] ?? ['strategies' => []];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['session'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['session'] ?? [];
        $GLOBALS['TYPO3_CONF_VARS']['BE'] = $GLOBALS['TYPO3_CONF_VARS']['BE'] ?? [];
        $GLOBALS['TYPO3_CONF_VARS']['FE'] = $GLOBALS['TYPO3_CONF_VARS']['FE'] ?? [];
        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash'] = $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash'] ?? ['excludedParameters' => []];
        $GLOBALS['TYPO3_CONF_VARS']['DB'] = $GLOBALS['TYPO3_CONF_VARS']['DB'] ?? ['Connections' => ['Default' => []]];
        $GLOBALS['TYPO3_CONF_VARS']['GFX'] = $GLOBALS['TYPO3_CONF_VARS']['GFX'] ?? [];
        $GLOBALS['TYPO3_CONF_VARS']['MAIL'] = $GLOBALS['TYPO3_CONF_VARS']['MAIL'] ?? [];
        $GLOBALS['TYPO3_CONF_VARS']['HTTP'] = $GLOBALS['TYPO3_CONF_VARS']['HTTP'] ?? [];
        $GLOBALS['TYPO3_CONF_VARS']['LOG'] = $GLOBALS['TYPO3_CONF_VARS']['LOG'] ?? [];
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'] ?? [];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'] = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'] ?? [];
    }

    protected function tearDown(): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = $this->originalConfVars;
        $this->resetSingleton();

        parent::tearDown();
    }

    protected function resetSingleton(): void
    {
        $ref = new \ReflectionProperty(Config::class, 'instance');
        $ref->setValue(null, null);
    }
}
