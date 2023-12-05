<?php

namespace Laraish\Support\Wp\Providers;

use Laraish\Support\Wp\ThemeOptions;
use Illuminate\Support\ServiceProvider;

class ThemeOptionsProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $options = config('theme');
        foreach ($options as $method => $option) {
            if (method_exists($this, $method)) {
                $this->$method($option);
                continue;
            }
            if (method_exists(ThemeOptions::class, $method)) {
                \call_user_func(ThemeOptions::class . "::${method}", $option);
            }
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
