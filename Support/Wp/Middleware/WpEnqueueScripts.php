<?php

namespace Laraish\Support\Wp\Middleware;

class WpEnqueueScripts
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        remove_action('wp_head', 'wp_enqueue_scripts', 1);
        wp_enqueue_scripts();

        return $next($request);
    }
}
