<?php

namespace Laraish\Foundation\Console;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * @var bool Flag to determine if load wordpress plugins.
     */
    protected bool $loadWordpressPlugins = false;

    /**
     * Create a new console kernel instance.
     */
    public function __construct(Application $app, Dispatcher $events)
    {
        // Used in `functions.php` so that we know we can stop from launching
        // the whole theme when in `Console-Mode`, because our goal is to use
        // some of the functions of WordPress.
        if (!defined('ARTISAN_BINARY')) {
            define('ARTISAN_BINARY', 'artisan');
        }

        // Load WordPress so we can use those functions WordPress supplies for us.
        if (!defined('WP_USE_THEMES')) {
            define('WP_USE_THEMES', false);
        }

        // Prevent from loading plugins (it could be error-prone if load plugins).
        if ($this->loadWordpressPlugins === false && !defined('WP_PLUGIN_DIR')) {
            define('WP_PLUGIN_DIR', '/NULL');
        }

        $wp_load = getenv('WP_LOAD_PATH') ?: realpath($app->basePath() . '/../../../wp-load.php');

        if (file_exists($wp_load)) {
            require_once $wp_load;
        }

        parent::__construct($app, $events);
    }
}
