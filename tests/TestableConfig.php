<?php

declare(strict_types=1);

namespace Moselwal\Tests;

use Moselwal\Config;
use TYPO3\CMS\Core\Information\Typo3Version;

class TestableConfig extends Config
{
    public static function initializeWithVersion(int $majorVersion): self
    {
        $instance = static::initialize(false);

        // Override the version via Reflection
        $versionMock = new class($majorVersion) extends Typo3Version {
            private int $major;
            public function __construct(int $major)
            {
                $this->major = $major;
            }
            public function getMajorVersion(): int
            {
                return $this->major;
            }
        };

        $ref = new \ReflectionProperty(Config::class, 'version');
        $ref->setValue($instance, $versionMock);

        return $instance;
    }

    /**
     * Expose protected resolveSecret for testing.
     */
    public function testResolveSecret(?string $key = null, ?string $fallback = null): ?string
    {
        return $this->resolveSecret($key, $fallback);
    }
}
