<?php

namespace Laraish\Foundation\Support\Providers;

use Laraish\Routing\WpRouter;
use Illuminate\Support\Facades\Route;
use Laraish\Routing\WpRouteController;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected static $wpMiddleware = [];

    /**
     * Switch validator for WordPress
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        $this->addWpRoutesAsFallback();
    }

    public function register()
    {
        parent::register();
        $this->registerWpRouter();
    }

    protected function addWpRoutesAsFallback(): void
    {
        $placeholder = 'fallbackPlaceholder';

        Route::any("{{$placeholder}}", [WpRouteController::class, 'dispatch'])
            ->where($placeholder, '.*')
            ->fallback();
    }

    /**
     * Register the router instance.
     *
     * @return void
     */
    protected function registerWpRouter()
    {
        $this->app->singleton('wpRouter', function ($app) {
            return new WpRouter($app['events'], $app);
        });
    }

    public static function getWpMiddleware()
    {
        return static::$wpMiddleware;
    }
}
