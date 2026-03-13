<?php

declare(strict_types=1);

namespace Moselwal\Tests;

use Moselwal\Config;
use phpmock\phpunit\PHPMock;

class CacheConfigurationTest extends ConfigTestCase
{
    use PHPMock;

    /**
     * @dataProvider typo3VersionCacheProvider
     */
    public function testVersionSpecificCacheRemoval(int $majorVersion, bool $expectPagesection, bool $expectImagesizes): void
    {
        // Mock getenv to provide KEYVALUE_HOST
        $getenv = $this->getFunctionMock('Moselwal', 'getenv');
        $getenv->expects(self::any())->willReturnCallback(function ($key) {
            if ($key === 'KEYVALUE_HOST') {
                return 'redis.local';
            }
            if ($key === 'KEYVALUE_PORT') {
                return '6379';
            }
            return false;
        });

        // Mock class_exists for KeyValue classes
        $classExists = $this->getFunctionMock('Moselwal', 'class_exists');
        $classExists->expects(self::any())->willReturn(false);

        // Mock is_readable for TLS (no certs)
        $isReadable = $this->getFunctionMock('Moselwal', 'is_readable');
        $isReadable->expects(self::any())->willReturn(false);

        // Mock function_exists for apcu
        $functionExists = $this->getFunctionMock('Moselwal', 'function_exists');
        $functionExists->expects(self::any())->willReturn(false);

        // Create a test subclass to inject the mocked version
        $config = TestableConfig::initializeWithVersion($majorVersion);
        $config->autoconfigureCaching();

        $cacheConfigs = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] ?? [];

        if ($expectPagesection) {
            self::assertArrayHasKey('pagesection', $cacheConfigs, "TYPO3 v{$majorVersion} should have pagesection cache");
        } else {
            self::assertArrayNotHasKey('pagesection', $cacheConfigs, "TYPO3 v{$majorVersion} should NOT have pagesection cache");
        }

        if ($expectImagesizes) {
            self::assertArrayHasKey('imagesizes', $cacheConfigs, "TYPO3 v{$majorVersion} should have imagesizes cache");
        } else {
            self::assertArrayNotHasKey('imagesizes', $cacheConfigs, "TYPO3 v{$majorVersion} should NOT have imagesizes cache");
        }
    }

    public static function typo3VersionCacheProvider(): array
    {
        return [
            'TYPO3 v11: pagesection+imagesizes present' => [11, true, true],
            'TYPO3 v12: pagesection removed, imagesizes present' => [12, false, true],
            'TYPO3 v13: both removed' => [13, false, false],
            'TYPO3 v14: both removed' => [14, false, false],
        ];
    }
}
