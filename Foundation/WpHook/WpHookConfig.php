<?php

namespace Laraish\Foundation\WpHook;

/**
 * WordPress Hook configuration data object
 */
final class WpHookConfig
{
    /**
     * @param string $hookName The WordPress hook name
     * @param WpHookType $hookType The type of hook (action or filter)
     * @param int $priority The priority of the hook (default: 10)
     * @param int $argumentsNumber The number of arguments the hook accepts (default: 1)
     * @param class-string<WpHookListener> $handlerClassName The fully qualified class name of the handler
     */
    public function __construct(
        public readonly string $hookName,
        public readonly WpHookType $hookType,
        public readonly int $priority,
        public readonly int $argumentsNumber,
        public readonly string $handlerClassName,
    ) {}
}
