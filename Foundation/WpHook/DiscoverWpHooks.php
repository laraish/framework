<?php

namespace Laraish\Foundation\WpHook;

use Illuminate\Support\Arr;
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

        return static::getListenerHooks(Finder::create()->files()->in($listenerPath), $basePath);
    }

    /**
     * Get all WordPress hook listeners and their configurations.
     *
     * @param iterable<string, SplFileInfo> $listeners
     * @param string $basePath
     * @return array<int, WpHookConfig>
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
                $listenerHooks[] = $hookConfig;
            }
        }

        return $listenerHooks;
    }

    /**
     * Extract hook configuration from listener class properties.
     *
     * @param ReflectionClass $listener
     * @return WpHookConfig|null
     */
    protected static function extractHookFromListener(ReflectionClass $listener): ?WpHookConfig
    {
        try {
            // Get property values using reflection
            $hookNameProp = $listener->getProperty('hookName');
            $hookNameProp->setAccessible(true);

            $hookTypeProp = $listener->getProperty('hookType');
            $hookTypeProp->setAccessible(true);

            // Create instance to read property values
            $instance = $listener->newInstanceWithoutConstructor();

            $hookName = $hookNameProp->getValue($instance);
            $hookType = $hookTypeProp->getValue($instance);

            $priorityProp = $listener->getProperty('priority');
            $priorityProp->setAccessible(true);
            $priority = $priorityProp->getValue($instance);

            $argumentsNumberProp = $listener->getProperty('argumentsNumber');
            $argumentsNumberProp->setAccessible(true);
            $argumentsNumber = $argumentsNumberProp->getValue($instance);

            return new WpHookConfig(
                hookName: $hookName,
                hookType: $hookType,
                priority: $priority,
                argumentsNumber: $argumentsNumber,
                handlerClassName: $listener->name,
            );
        } catch (ReflectionException) {
            return null;
        }
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
