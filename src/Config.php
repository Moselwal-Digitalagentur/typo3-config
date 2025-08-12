<?php

declare(strict_types=1);

/*
 * This file is part of the package "typo3-config" by Moselwal Digitalagentur GmbH.
 */

namespace Moselwal;

use TYPO3\CMS\Core\Cache\Backend\RedisBackend;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Log\Writer\NullWriter;
use TYPO3\CMS\Core\Log\Writer\PhpErrorLogWriter;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Class to use in your configuration files of a TYPO3 project.
 */
class Config implements ConfigInterface
{
    protected ApplicationContext $context;
    protected Typo3Version $version;
    protected string $configPath;
    protected string $varPath;
    protected bool $ddevEnvironment = false;

    /**
     * @var Config
     */
    protected static $instance;

    private function __construct()
    {
        $this->context = Environment::getContext();
        $this->version = new Typo3Version();
        $this->configPath = Environment::getConfigPath();
        $this->varPath = Environment::getVarPath();
    }

    /**
     * @return static
     */
    public static function initialize(bool $applyDefaults = true): self
    {
        // Late static binding
        self::$instance = new static();
        if ($applyDefaults === false) {
            return self::$instance;
        }
        return self::$instance
            // use sensible default based on Context
            ->applyDefaults();
    }

    /**
     * @return static
     */
    public static function get(): self
    {
        return self::$instance;
    }

    public function applyDefaults(): self
    {
        // Include presets by default
        self::$instance
            ->forbidInvalidCacheHashQueryParameter()
            ->forbidNoCacheQueryParameter()
            ->appendContextToSiteName()
            ->useGraphicsMagick();

        if (php_sapi_name() === 'cli') {
            self::$instance->useCliPreset();
        } elseif (self::$instance->context->isDevelopment() || self::$instance->context->isTesting()) {
            self::$instance->useDevelopmentPreset();
        } elseif (self::$instance->context->isProduction()) {
            if (!empty(getenv('APP_ROOT'))) {
                self::$instance->useProductionPreset();
            } else {
                self::$instance->useProductionPresetVHost();
            }
        }
        return $this;
    }

    /**
     * Append TYPO3_CONTEXT to site name in the TYPO3 backend
     */
    final public function appendContextToSiteName(): self
    {
        if ($this->context->isProduction() === false) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] .= ' - ' . (string)$this->context;
        }
        return $this;
    }

    final public function initializeDatabaseConnection(?array $options = null, $connectionName = 'Default'): self
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][$connectionName] = array_replace_recursive(
            $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][$connectionName],
            $options
        );
        return $this;
    }

    /**
     * @return $this
     */
    final public function useCliPreset(): self
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = TRUE;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = TRUE;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sqlDebug'] = 1;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = 1;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLogLevel'] = 0;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandlerErrors'] = E_ALL ^ E_NOTICE;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors'] = 28674;
        $this->enableDeprecationLogging();
        // no HTTPS errors, because of invalid development certificates
        // Hint: works only together with a core patch
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_host'] = 0;
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_peer'] = 0;
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'] = [
            \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
                \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                    'logFile' => 'var/logs/error.log',
                ],
            ],
        ];

        return $this;
    }

    /**
     * Default settings for production, can be overridden again in each project / production.php
     * @return $this
     */
    final public function useProductionPreset(): self
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = false;
        $GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = false;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = -1;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['belogErrorReporting'] = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors'] = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
        $this->disableDeprecationLogging();
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'] = array_replace_recursive(
            [
                LogLevel::DEBUG => [
                    PhpErrorLogWriter::class => ['disabled' => true],
                ],
                LogLevel::INFO => [
                    PhpErrorLogWriter::class => ['disabled' => true],
                ],
                LogLevel::WARNING => [
                    PhpErrorLogWriter::class => ['disabled' => true],
                ],
                LogLevel::ERROR => [
                    PhpErrorLogWriter::class => ['disabled' => false],
                ],
            ],
            $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration']
        );
        return $this;
    }

    /**
     * Default settings for production, can be overridden again in each project / production.php
     * @return $this
     */
    final public function useProductionPresetVHost(): self
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = false;
        $GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = false;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = -1;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['belogErrorReporting'] = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors'] = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
        $this->disableDeprecationLogging();
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'] = array_replace_recursive(
            [
                LogLevel::DEBUG => [
                    FileWriter::class => ['disabled' => true],
                ],
                LogLevel::INFO => [
                    FileWriter::class => ['disabled' => true],
                ],
                LogLevel::WARNING => [
                    FileWriter::class => ['disabled' => true],
                ],
                LogLevel::ERROR => [
                    FileWriter::class => ['disabled' => false],
                ],
            ],
            $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration']
        );
        return $this;
    }

    final public function useDevelopmentPreset(): self
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sqlDebug'] = '1';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '.*';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = 1;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['belogErrorReporting'] = E_ALL;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors'] = E_ALL;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] = false;
        $this->enableDeprecationLogging();
        // Log warnings to files
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][LogLevel::WARNING] = [
            FileWriter::class => ['disabled' => false],
        ];
        $this->useMailpit();
        $this->useFileFill();
        return $this;
    }

    final public function useFileFill(): self
    {
        // Configure EXT:filefill for local environments without a sync'ed fileadmin folder.
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['filefill']['storages'][1] = [
            [
                'identifier' => 'placeholder',
            ],
        ];
        return $this;
    }

    final public function useConfigLoader(array $forbiddenKeys = ['password' => 1,'transport_smtp_password' => 1, 'encryptionKey' => 1, 'installToolPassword' => 1]): self
    {
        // Automatic Configuration overrides by conventional named environment variables
        // This is always the last place for configuration changes and must be in the end of this file!
        // The Config will be cached!
        if (class_exists(\Helhum\ConfigLoader\CachedConfigurationLoader::class)) {
            $cacheDir = Environment::getVarPath() . '/cache/data';
            $cacheIdentifier = md5(implode('|', [
                (string)filemtime(Environment::getProjectPath() . '/.env'),
                getenv('BUILD_DATE'),
                (string)$this->context
            ]));
            $configReaderFactory = new \Helhum\ConfigLoader\ConfigurationReaderFactory(dirname(__DIR__));
            $configLoader = new \Helhum\ConfigLoader\CachedConfigurationLoader(
                $cacheDir,
                $cacheIdentifier,
                function () use ($configReaderFactory) {
                    return new \Helhum\ConfigLoader\ConfigurationLoader(
                        [
                            $configReaderFactory->createReader('TYPO3', ['type' => 'env']),
                        ]
                    );
                }
            );

            $GLOBALS['TYPO3_CONF_VARS'] = array_replace_recursive(
                $GLOBALS['TYPO3_CONF_VARS'],
                array_diff_key($configLoader->load(), $forbiddenKeys)
            );
        }
        return $this;
    }

    final public function useImageMagick(string $path = '/usr/bin/'): self
    {
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor'] = 'ImageMagick';
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path'] = $path;
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path_lzw'] = $path;
        return $this;
    }

    final public function useGraphicsMagick(string $path = '/usr/bin/'): self
    {
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor'] = 'GraphicsMagick';
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path'] = $path;
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path_lzw'] = $path;
        return $this;
    }

    final public function useMailpit(string $host = 'localhost', ?int $port = null): self
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'smtp';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_encrypt'] = '';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_password'] = '';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_server'] = $host . ($port ? ':' . (string)$port : '');
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_username'] = '';
        return $this;
    }

    final public function allowNoCacheQueryParameter(): self
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['disableNoCacheParameter'] = false;
        return $this;
    }

    final public function forbidNoCacheQueryParameter(): self
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['disableNoCacheParameter'] = true;
        return $this;
    }

    final public function allowInvalidCacheHashQueryParameter(): self
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError'] = false;
        return $this;
    }

    final public function forbidInvalidCacheHashQueryParameter(): self
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError'] = true;
        return $this;
    }

    final public function excludeQueryParameterForCacheHashCalculation(string $queryParameter): self
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = $queryParameter;
        return $this;
    }

    final public function excludeQueryParametersForCacheHashCalculation(array $queryParameters): self
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] = array_merge(
            $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'],
            $queryParameters
        );
        return $this;
    }

    final public function enableDeprecationLogging(): self
    {
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['CMS']['deprecations']['writerConfiguration'][LogLevel::NOTICE]['TYPO3\CMS\Core\Log\Writer\FileWriter']['disabled'] = false;
        return $this;
    }

    final public function disableDeprecationLogging(): self
    {
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['CMS']['deprecations']['writerConfiguration'][LogLevel::NOTICE]['TYPO3\CMS\Core\Log\Writer\FileWriter']['disabled'] = true;
        return $this;
    }

    /**
     * Additional Project-specific methods
     */
    final public function configureExceptionHandlers(string $productionExceptionHandlerClassName, string $debugExceptionHandlerClassName): self
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['productionExceptionHandler'] = $productionExceptionHandlerClassName;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['debugExceptionHandler'] = $debugExceptionHandlerClassName;
        return $this;
    }

    /**
     * Configures a log file for solr based on the TYPO3 Context, with a separate file for solr.
     *
     * @return $this
     */
    final public function autoconfigureSolrLogging(string $fileName = 'solr.log', ?string $forceLogLevel = null): self
    {
        if ($forceLogLevel !== null) {
            $logLevel = $forceLogLevel;
        } else {
            $logLevel = $this->context->isProduction() ? LogLevel::ERROR : LogLevel::DEBUG;
        }
        return $this->addFileLogger('ApacheSolrForTypo3\\Solr', $fileName, $logLevel);
    }

    /**
     * Shorthand function to add a file logger in a quick manner in the typical log folder.
     * @return $this
     */
    public function addFileLogger(string $namespace, ?string $fileName = null, ?string $logLevel = null): self
    {
        $fileName = $fileName ?? strtolower(str_replace('\\', '_', $namespace)) . '.log';
        if ($logLevel === null) {
            $logLevel = $this->context->isProduction() ? LogLevel::ERROR : LogLevel::DEBUG;
        }
        $logFile = $this->varPath . '/log/' . $fileName;
        $value = [
            'writerConfiguration' => [
                $logLevel => [
                    FileWriter::class => [
                        'logFile' => $logFile,
                    ],
                ],
            ],
        ];
        $GLOBALS['TYPO3_CONF_VARS']['LOG'] = ArrayUtility::setValueByPath($GLOBALS['TYPO3_CONF_VARS']['LOG'], $namespace, $value, '\\');
        return $this;
    }

    /**
     * Disable logging for a specific namespace.
     * @return $this
     */
    final public function setNullLogger(string $namespace, string $logLevel = LogLevel::DEBUG): self
    {
        $value = [
            'writerConfiguration' => [
                $logLevel => [
                    NullWriter::class => [],
                ],
            ],
        ];
        $GLOBALS['TYPO3_CONF_VARS']['LOG'] = ArrayUtility::setValueByPath($GLOBALS['TYPO3_CONF_VARS']['LOG'], $namespace, $value, '\\');
        return $this;
    }

    public function autoconfigureCaching(array $additionalCachesKeyValue = [], array $additionalCachesAPCU = [], string $keyvaluePassword = ''): self
    {
        if ($redisHost = trim(getenv('KEYVALUE_HOST') ?: '')) {
            $isVersion12OrHigher = $this->version->getMajorVersion() >= 12;

            $redisPort = (int)trim(getenv('KEYVALUE_PORT') ?: '') ?? 6379;

            $keyvaluePassword = $this->resolveSecret('KEYVALUE_PASSWORD', $keyvaluePassword);

            if (class_exists(\Moselwal\Typo3Sitepackage\Locking\RedisLockingStrategy::class)) {
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['locking']['strategies'][\Moselwal\Typo3Sitepackage\Locking\RedisLockingStrategy::class] = [
                    'options' => [
                        'hostname' => $redisHost,
                        'database' => 0,
                        'port' => $redisPort,
                        'persistentConnection' => true,
                        'ttl' => 10
                    ],
                ];


                if (!is_null($keyvaluePassword)) {
                    $GLOBALS['TYPO3_CONF_VARS']['SYS']['locking']['strategies'][\Moselwal\Typo3Sitepackage\Locking\RedisLockingStrategy::class]['options']['password'] = $keyvaluePassword;
                }
            }

            $GLOBALS['TYPO3_CONF_VARS']['SYS']['session'] = [
                'BE' => [
                    'backend' => \TYPO3\CMS\Core\Session\Backend\RedisSessionBackend::class,
                    'options' => [
                        'hostname' => $redisHost,
                        'database' => 1,
                        'port' => $redisPort,
                        'persistentConnection' => true,
                    ]
                ],
                'FE' => [
                    'backend' => \TYPO3\CMS\Core\Session\Backend\RedisSessionBackend::class,
                    'options' => [
                        'hostname' => $redisHost,
                        'database' => 2,
                        'port' => $redisPort,
                        'persistentConnection' => true,
                    ]
                ],
            ];

            if (!is_null($keyvaluePassword)) {
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['session']['BE']['options']['password'] = $keyvaluePassword;
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['session']['FE']['options']['password'] = $keyvaluePassword;
            }

            $redisCaches = array_merge( $redisCaches ?? [
                // Core
                'pages' => [
                    'defaultLifetime' => 86400*30, // 1 mont
                    'compression' => true,
                ],
                'pagesection' => [
                    'defaultLifetime' => 86400*30,
                ],
                'hash' => [
                    'defaultLifetime' => 86400*30,
                ],
                'rootline' => [
                    'defaultLifetime' => 86400*30,
                ],
                'imagesizes' => [],
                'workspaces_cache' => [],
                // Extensions
                'preview_renderer_cache' => [],
                'sg_mail_registerArrayCache' => [],
                'l10n' => [
                    'defaultLifetime' => 86400*30,
                ],
            ],
                $additionalCachesKeyValue
            );

            if ($isVersion12OrHigher) {
                unset($redisCaches['pagesection'], $redisCaches['cache_pagesection']);
            }

            $redisDatabase = 3;
            foreach ($redisCaches as $name => $values) {
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$name]['backend']
                    =  \TYPO3\CMS\Core\Cache\Backend\RedisBackend::class;
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$name]['options'] = [
                    'database' => $redisDatabase++,
                    'hostname' => $redisHost,
                    'port' => $redisPort,
                    'persistentConnection' => true,
                ];
                if (isset($values['defaultLifetime'])) {
                    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$name]['options']['defaultLifetime']
                        = $values['defaultLifetime'];
                }
                if (isset($values['compression'])) {
                    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$name]['options']['compression']
                        = $values['compression'];
                }
                if (!is_null($keyvaluePassword)) {
                    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$name]['options']['password'] = $keyvaluePassword;
                }
            }
        }

        if (function_exists('apcu_enabled') && apcu_enabled()) {

            $apcuCaches = array_merge($apcuCaches ?? [
                'extbase'        => ['defaultLifetime' => 86400*30],
                'l10n'           => ['defaultLifetime' => 86400*30],
                'ratelimiter'    => [],
                'dashboard_rss'  => ['defaultLifetime' => 86400*30],
                'assets'         => ['defaultLifetime' => 86400*30],
            ],
                $additionalCachesAPCU
            );

            if (!getenv('KEYVALUE_HOST')) {
                $apcuCaches = array_merge($apcuCaches, [
                    'pages'                 => ['defaultLifetime' => 86400*30],
                    'pagesection'           => ['defaultLifetime' => 86400*30],
                    'hash'                  => ['defaultLifetime' => 86400*30],
                    'rootline'              => ['defaultLifetime' => 86400*30],
                    'imagesizes'            => [],
                    'workspaces_cache'      => [],
                    'preview_renderer_cache'=> [],
                ]);

                if ($isVersion12OrHigher) {
                    unset($apcuCaches['pagesection'], $apcuCaches['cache_pagesection']);
                }
            }

            foreach ($apcuCaches as $name => $values) {
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$name]['backend']
                    =  \TYPO3\CMS\Core\Cache\Backend\ApcuBackend::class;
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$name]['options'] = [];
                if (isset($values['defaultLifetime'])) {
                    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$name]['options']['defaultLifetime']
                        = $values['defaultLifetime'];
                }
            }
        }

        return $this;
    }

    /**
     * Resolves a secret based on a single logical key (e.g. "DB_PASSWORD").
     * It tries:
     *  1. File from ENV: ${KEY}_FILE (e.g. DB_PASSWORD_FILE)
     *  2. Default file: /run/secrets/${key_lower}
     *  3. ENV: ${KEY}
     *  4. Optional fallback value
     *
     * @param string $key Logical secret identifier (e.g. "DB_PASSWORD")
     * @param string|null $fallback Optional fallback value
     * @return string|null The resolved secret or null
     */
    final protected function resolveSecret(
        ?string $key = null,
        ?string $fallback = null
    ): ?string {
        $envFileKey = strtoupper($key) . '_FILE';
        $defaultFile = '/run/secrets/' . strtolower($key);

        $envFilePath = trim((string)getenv($envFileKey) ?: '');
        if ($envFilePath !== '' && is_readable($envFilePath)) {
            $value = trim((string)file_get_contents($envFilePath));
            if ($value !== '') {
                return $value;
            }
        }

        if (is_readable($defaultFile)) {
            $value = trim((string)file_get_contents($defaultFile));
            if ($value !== '') {
                return $value;
            }
        }

        $envValue = trim((string)getenv($key) ?: '');
        if ($envValue !== '') {
            return $envValue;
        }

        if ($fallback !== null && trim($fallback) !== '') {
            $sourcesChecked[] = 'fallback';
            return trim($fallback);
        }

        return null;
    }

    /**
     * @param string|null $dbUser
     * @param string|null $dbPassword
     * @param string|null $encriptionKey
     * @param string|null $installToolPassword
     * @return $this
     */
    final public function loadCoreSecrets(
        ?string $dbUser = null,
        ?string $dbPassword = null,
        ?string $encriptionKey = null,
        ?string $installToolPassword = null,
    ): self
    {
        $this->setConfigPathValues('DB/Connections/Default', [
            'user' => $this->resolveSecret('DB_USER', $dbUser),
            'password' => $this->resolveSecret('DB_PASSWORD', $dbPassword),
        ]);

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] =
            $this->resolveSecret('ENCRYPTION_KEY', $encriptionKey);

        $GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] =
            $this->resolveSecret( 'INSTALL_TOOL_PASSWORD', $installToolPassword);

        return $this;
    }

    final public function loadMailSecrets(?string $mailPassword = null, ?string $mailUsername = null, ?string $mailDSN = null): self
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_password'] =
            $this->resolveSecret('MAIL_PASSWORD', $mailPassword);

        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_username'] =
            $this->resolveSecret('MAIL_USERNAME', $mailUsername);

        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['dsn'] =
            $this->resolveSecret('MAIL_DSN', $mailDSN);

        return $this;
    }

    /**
     * Useful for distributed systems to put caches outside an NFS mount.
     *
     * @param string $path
     * @param array|null $applyForCaches
     * @return $this
     */
    public function setAlternativeCachePath(string $path, ?array $applyForCaches = null): self
    {
        $applyForCaches = $applyForCaches ?? [
            'cache_core',
            'fluid_template',
            'assets',
            'l10n',
        ];
        foreach ($applyForCaches as $cacheName) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheName]['options']['cacheDirectory'] = $path;
        }
        return $this;
    }

    /**
     * Set PHP configuration settings.
     *
     * This method attempts to set PHP configuration settings using ini_set.
     * If ini_set is disabled (e.g., due to server restrictions), the setting will not be applied,
     * and a warning will be logged.
     *
     * Example:
     * \Moselwal\Config::get()->setPhpSettings([
     *      'max_execution_time' => 1000,
     *      'max_input_time' => 1000,
     *      'post_max_size' => '100M',
     *      'upload_max_filesize' => '100M',
     * ]);
     *
     * @param array $settings An associative array of PHP settings.
     * @return $this
     */
    final public function setPhpSettings(array $settings): self
    {
        foreach ($settings as $key => $value) {
            try {
                if (function_exists('ini_set') && !ini_get($key)) {
                    ini_set($key, $value);
                } else {
                    error_log("Unable to set PHP setting $key, ini_set is disabled or already set.");
                }
            } catch (\ErrorException $e) {
                error_log("Error setting PHP configuration for $key: " . $e->getMessage());
            }
        }

        return $this;
    }

    /**
     * Override or append TYPO3 configuration settings for a given path with key-value pairs.
     *
     * Examples:
     * \Moselwal\Config::get()->setConfigPathValues('EXTENSIONS/alterations', ['just-a-test' => 'test']);
     * \Moselwal\Config::get()->setConfigPathValues(
     *   'SYS',
     *   [
     *      'just-a-test' => 'test',
     *      'defaultScheme' => 'https'
     *   ]);
     *
     * @param string $configPath A string representing the path to the configuration option.
     * @param array $keyValuePairs An associative array of key-value pairs to set within the specified path.
     * @return $this
     */
    public function setConfigPathValues(string $configPath, array $keyValuePairs): self
    {
        $config = &$GLOBALS['TYPO3_CONF_VARS'];

        $configPath = explode('/', $configPath);

        foreach ($configPath as $key => $value) {
            if (!isset($config[$value])) {
                $config[$value] = [];
            }
            $config = &$config[$value];
        }

        $config = array_replace_recursive($config, $keyValuePairs);

        return $this;
    }

}
