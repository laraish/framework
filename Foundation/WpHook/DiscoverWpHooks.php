<?php

namespace Laraish\Foundation\WpHook;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class DiscoverWpHooks
{
    /**
     * The callback to be used to guess class names.
     *
     * @var (callable(SplFileInfo, string): class-string)|null
     */
    public static $guessClassNamesUsingCallback;

    /**
     * Get all WordPress hooks and listeners by searching the given listener directory.
     *
     * @param array<int, string>|string $listenerPath
     * @param string $basePath
     * @return array<int, WpHookConfig>
     */
    public static function within($listenerPath, $basePath)
    {
        if (Arr::wrap($listenerPath) === []) {
            return [];
        }

        $listeners = new Collection(static::getListenerHooks(Finder::create()->files()->in($listenerPath), $basePath));

        $discoveredHooks = [];

        foreach ($listeners as $listener => $hookConfig) {
            if ($hookConfig) {
                $discoveredHooks[] = new WpHookConfig(
                    hookName: $hookConfig->hookName,
                    hookType: $hookConfig->hookType,
                    priority: $hookConfig->priority,
                    argumentsNumber: $hookConfig->argumentsNumber,
                    handlerClassName: $listener,
                );
            }
        }

        return $discoveredHooks;
    }

    /**
     * Get all WordPress hook listeners and their configurations.
     *
     * @param iterable<string, SplFileInfo> $listeners
     * @param string $basePath
     * @return array<class-string, WpHookConfig>
     */
    protected static function getListenerHooks($listeners, $basePath)
    {
        $listenerHooks = [];

        foreach ($listeners as $listener) {
            try {
                $listenerClass = new ReflectionClass(static::classFromFile($listener, $basePath));
            } catch (ReflectionException $e) {
                continue;
            }

            if (!$listenerClass->isInstantiable()) {
                continue;
            }

            // Check if it extends WpHookListener
            if (!$listenerClass->isSubclassOf(WpHookListener::class)) {
                continue;
            }

            // Check if it has handle method
            if (!$listenerClass->hasMethod('handle')) {
                continue;
            }

            $hookConfig = static::extractHookFromListener($listenerClass);

            if ($hookConfig) {
                // Set the correct className
                $listenerHooks[$listenerClass->name] = new WpHookConfig(
                    hookName: $hookConfig->hookName,
                    hookType: $hookConfig->hookType,
                    priority: $hookConfig->priority,
                    argumentsNumber: $hookConfig->argumentsNumber,
                    handlerClassName: $listenerClass->name,
                );
            }
        }

        return array_filter($listenerHooks);
    }

    /**
     * Extract hook configuration from listener class properties.
     *
     * @param ReflectionClass $listener
     * @return WpHookConfig|null
     */
    protected static function extractHookFromListener(ReflectionClass $listener): ?WpHookConfig
    {
        // Create an instance to access readonly properties
        // Use reflection to instantiate without constructor
        try {
            $instance = $listener->newInstanceWithoutConstructor();
        } catch (ReflectionException) {
            return null;
        }

        // Since it's a WpHookListener subclass, these properties are guaranteed to exist
        // Note: className will be set by the caller
        return new WpHookConfig(
            hookName: $instance->hookName,
            hookType: $instance->hookType,
            priority: $instance->priority ?? 10,
            argumentsNumber: $instance->argumentsNumber ?? 1,
            handlerClassName: '', // Will be set by the caller
        );
    }

    /**
     * Extract the class name from the given file path.
     *
     * @param SplFileInfo $file
     * @param string $basePath
     * @return class-string
     */
    protected static function classFromFile(SplFileInfo $file, $basePath)
    {
        if (static::$guessClassNamesUsingCallback) {
            return call_user_func(static::$guessClassNamesUsingCallback, $file, $basePath);
        }

        $class = trim(Str::replaceFirst($basePath, '', $file->getRealPath()), DIRECTORY_SEPARATOR);

        return ucfirst(
            Str::camel(
                str_replace(
                    [DIRECTORY_SEPARATOR, ucfirst(basename(app()->path())) . '\\'],
                    ['\\', app()->getNamespace()],
                    ucfirst(Str::replaceLast('.php', '', $class)),
                ),
            ),
        );
    }

    /**
     * Specify a callback to be used to guess class names.
     *
     * @param callable(SplFileInfo, string): class-string $callback
     * @return void
     */
    public static function guessClassNamesUsing(callable $callback)
    {
        static::$guessClassNamesUsingCallback = $callback;
    }
}
