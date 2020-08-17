<?php

namespace Laraish\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Laraish\Routing\WpRouter
 *
 * @method static \Illuminate\Routing\RouteRegistrar middleware(array|string|null $middleware)
 * @method static \Illuminate\Routing\Route notFound(array|string|callable $action, string|string[] $methods = null)
 * @method static \Illuminate\Routing\Route search(array|string|callable $action, string|string[] $methods = null)
 * @method static \Illuminate\Routing\Route author(array|string|callable $selectorOrAction, array|string|callable|null $actionOrMethods = null, string|string[]|null $methods = null)
 * @method static \Illuminate\Routing\Route postArchive(string $postType, array|string|callable $action, string|string[] $methods = null)
 * @method static \Illuminate\Routing\Route post(string $postType, array|string|callable $action, string|string[] $methods = null)
 * @method static \Illuminate\Routing\Route page(array|string|callable $selectorOrAction, array|string|callable|null $actionOrMethods = null, string|string[]|null $methods = null)
 * @method static \Illuminate\Routing\Route taxonomy(string $selector, array|string|callable $action, string|string[] $methods = null)
 * @method static \Illuminate\Routing\Route archive(array|string|callable $action, string|string[] $methods = null)
 * @method static \Illuminate\Routing\Route home(array|string|callable $action, string|string[] $methods = null)
 * @method static void autoDiscovery()
 */
class WpRoute extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'wpRouter';
    }
}
