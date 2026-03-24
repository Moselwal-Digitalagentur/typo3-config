<?php

declare(strict_types=1);

namespace Moselwal;

interface ConfigInterface
{
public static function initialize(bool $applyDefaults = true): self;
public static function get(): self;

public function applyDefaults(): self;
public function appendContextToSiteName(): self;
public function useCliPreset(): self;
public function useProductionPreset(): self;
public function useProductionPresetVHost(): self;
public function useDevelopmentPreset(): self;
public function useFileFill(): self;
public function useConfigLoader(array $forbiddenKeys = []): self;
public function useGraphicsMagick(string $path = '/usr/bin/'): self;
public function useImageMagick(string $path = '/usr/bin/'): self;
public function useMailpit(string $host = 'localhost', ?int $port = null): self;
public function autoconfigureCaching(array $additionalCachesKeyValue = [], array $additionalCachesAPCU = [], string $keyvaluePassword = ''): self;
public function setAlternativeCachePath(string $path, ?array $applyForCaches = null): self;
public function setPhpSettings(array $settings): self;
public function setConfigPathValues(string $configPath, array $keyValuePairs): self;
}
