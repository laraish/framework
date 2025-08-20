<?php

namespace Laraish\Foundation\WpHook;

class WpHookRegistry
{
    /**
     * Add a WordPress hook (action or filter)
     *
     * @param WpHookType $hookType The type of hook to add
     * @param string $hookName The name of the WordPress hook
     * @param WpHookConfig $config The listener configuration
     */
    public function registerHook(WpHookType $hookType, string $hookName, WpHookConfig $config)
    {
        $fn = 'add_' . $hookType->value; // `add_action` or `add_filter`

        $className = $config->handlerClassName;
        $priority = $config->priority;
        $argumentsNumber = $config->argumentsNumber;

        $fn(
            $hookName,
            function () use ($className) {
                $listenerInstance = app()->make($className);
                return call_user_func_array([$listenerInstance, 'handle'], func_get_args());
            },
            $priority,
            $argumentsNumber,
        );
    }

    /**
     * Indicates if WordPress hooks should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverHooks = true;

    /**
     * The configured hook discovery paths.
     *
     * @var iterable<int, string>|null
     */
    protected static $hookDiscoveryPaths;

    /**
     * Discover WordPress hooks from listeners.
     *
     * @return array<int, WpHookConfig>
     */
    public function discoverWpHooks(): array
    {
        return $this->shouldDiscoverWpHooks() ? $this->discoverHooks() : [];
    }

    /**
     * Determine if hooks should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverWpHooks(): bool
    {
        return static::$shouldDiscoverHooks === true;
    }

    /**
     * Discover the hooks and listeners for the application.
     *
     * @return array<int, WpHookConfig>
     */
    protected function discoverHooks(): array
    {
        $directories = collect($this->discoverWpHooksWithin())
            ->flatMap(function ($directory) {
                return glob($directory, GLOB_ONLYDIR);
            })
            ->reject(function ($directory) {
                return !is_dir($directory);
            })
            ->all();

        return DiscoverWpHooks::within($directories, $this->hookDiscoveryBasePath());
    }

    /**
     * Get the listener directories that should be used to discover hooks.
     *
     * @return iterable<int, string>
     */
    protected function discoverWpHooksWithin(): iterable
    {
        return static::$hookDiscoveryPaths ?: [app()->path('WpListeners')];
    }

    /**
     * Get the base path to be used during hook discovery.
     *
     * @return string
     */
    protected function hookDiscoveryBasePath(): string
    {
        return base_path();
    }

    /**
     * Add the given hook discovery paths to the application's hook discovery paths.
     *
     * @param string|iterable<int, string> $paths
     * @return void
     */
    public static function addHookDiscoveryPaths(iterable|string $paths): void
    {
        static::$hookDiscoveryPaths = collect(static::$hookDiscoveryPaths)
            ->merge(is_string($paths) ? [$paths] : $paths)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Set the globally configured hook discovery paths.
     *
     * @param iterable<int, string> $paths
     * @return void
     */
    public static function setHookDiscoveryPaths(iterable $paths): void
    {
        static::$hookDiscoveryPaths = $paths;
    }

    /**
     * Disable hook discovery for the application.
     *
     * @return void
     */
    public static function disableHookDiscovery(): void
    {
        static::$shouldDiscoverHooks = false;
    }
}
