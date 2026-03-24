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

public function initializeDatabaseConnection(?array $options = null, string $connectionName = 'Default'): self;
public function allowNoCacheQueryParameter(): self;
public function forbidNoCacheQueryParameter(): self;
public function allowInvalidCacheHashQueryParameter(): self;
public function forbidInvalidCacheHashQueryParameter(): self;
public function excludeQueryParameterForCacheHashCalculation(string $queryParameter): self;
public function excludeQueryParametersForCacheHashCalculation(array $queryParameters): self;
public function enableDeprecationLogging(): self;
public function disableDeprecationLogging(): self;
public function configureExceptionHandlers(string $productionExceptionHandlerClassName, string $debugExceptionHandlerClassName): self;
public function autoconfigureSolrLogging(string $fileName = 'solr.log', ?string $forceLogLevel = null): self;
public function addFileLogger(string $namespace, ?string $fileName = null, ?string $logLevel = null): self;
public function setNullLogger(string $namespace, string $logLevel = \TYPO3\CMS\Core\Log\LogLevel::DEBUG): self;
public function loadCoreSecrets(?string $dbUser = null, ?string $dbPassword = null, ?string $encriptionKey = null, ?string $installToolPassword = null): self;
public function loadMailSecrets(?string $mailPassword = null, ?string $mailUsername = null, ?string $mailDSN = null): self;
}
