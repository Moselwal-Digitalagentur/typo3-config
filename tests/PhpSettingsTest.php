<?php

declare(strict_types=1);

namespace Moselwal\Tests;

use Moselwal\Config;
use phpmock\phpunit\PHPMock;

class PhpSettingsTest extends ConfigTestCase
{
    use PHPMock;

    public function testSetPhpSettingsOverwritesExistingValues(): void
    {
        $iniSet = $this->getFunctionMock('Moselwal', 'ini_set');
        $iniSet->expects(self::once())
            ->with('max_execution_time', '300')
            ->willReturn('30');

        $functionExists = $this->getFunctionMock('Moselwal', 'function_exists');
        $functionExists->expects(self::once())
            ->with('ini_set')
            ->willReturn(true);

        $config = new Config();
        $result = $config->setPhpSettings(['max_execution_time' => '300']);

        self::assertSame($config, $result, 'setPhpSettings must return $this for fluent chaining');
    }

    public function testSetPhpSettingsHandlesDisabledIniSet(): void
    {
        $functionExists = $this->getFunctionMock('Moselwal', 'function_exists');
        $functionExists->expects(self::once())
            ->with('ini_set')
            ->willReturn(false);

        $config = new Config();
        $result = $config->setPhpSettings(['max_execution_time' => '300']);

        self::assertSame($config, $result, 'setPhpSettings must return $this even when ini_set is disabled');
    }
}
