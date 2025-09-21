<?php

namespace Laraish\Foundation\Support\Providers;

use Laraish\Routing\WpRouter;
use Illuminate\Support\Facades\Route;
use Laraish\Routing\WpRouteController;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerWpRouter();
    }

    public function boot()
    {
        // Register WordPress routes as a fallback
        $this->addWpRoutesAsFallback();
    }

    protected function addWpRoutesAsFallback(): void
    {
        $placeholder = 'fallbackPlaceholder';
        Route::any("{{$placeholder}}", [WpRouteController::class, 'dispatch'])
            ->where($placeholder, '.*')
            ->middleware('web')
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
}
