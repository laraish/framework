<?php

namespace Laraish\Foundation\Debugger;

use Illuminate\View\View;
use Illuminate\Support\Facades\Route;

trait ViewDebugger
{
    /**
     * Use this method instead of the `view` function.
     * Make sure you've called the `wp_footer()`.
     *
     * @param null|string|string[] $view
     * @param array $data
     * @param array $mergeData
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    protected function view($view = null, $data = [], $mergeData = [])
    {
        /** @type View $view */
        $viewObject = is_array($view) ? view()->first($view, $data, $mergeData) : view($view, $data, $mergeData);

        if (app()->environment('production')) {
            return $viewObject;
        }

        $debugInfo = json_encode([
            'view_path' => $viewObject->getPath(),
            'compiled_path' => get_compiled_path($viewObject),
            'data' => $viewObject->getData(),
            'action' => Route::currentRouteAction(),
            'route_name' => Route::currentRouteName(),
            'middleware' => Route::current()->computedMiddleware,
        ]);

        $script = "<script>console.log('view-debugger',$debugInfo)</script>";

        add_action('wp_footer', function () use ($script) {
            echo $script;
        });

        return $viewObject;
    }
}
