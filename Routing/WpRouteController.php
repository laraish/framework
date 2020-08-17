<?php

namespace Laraish\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laraish\Foundation\Support\Providers\RouteServiceProvider;

class WpRouteController extends Controller
{
    public function dispatch(Request $request)
    {
        /** @var WpRouter $wpRouter */
        $wpRouter = app('wpRouter');

        $wpMiddleware = RouteServiceProvider::getWpMiddleware();
        if (empty($wpMiddleware)) {
            require $this->wpRoutes();
        } else {
            $wpRouter->middleware($wpMiddleware)->group($this->wpRoutes());
        }

        $response = $wpRouter->dispatch($request);

        return $response;
    }

    protected function wpRoutes(): string
    {
        return base_path('routes/wp.php');
    }
}
