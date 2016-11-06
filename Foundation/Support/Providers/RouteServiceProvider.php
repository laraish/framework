<?php

namespace Laraish\Foundation\Support\Providers;

use Illuminate\Routing\Matching\HostValidator;
use Illuminate\Routing\Matching\MethodValidator;
use Illuminate\Routing\Matching\SchemeValidator;
use Laraish\Routing\Matching\UriValidator;
use Illuminate\Routing\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Switch validator for WordPress
     *
     * @return void
     */
    public function boot()
    {
        Route::$validators = [
            new MethodValidator,
            new SchemeValidator,
            new HostValidator,
            new UriValidator,
        ];

        parent::boot();
    }
}