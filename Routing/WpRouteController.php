<?php

namespace Laraish\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WpRouteController extends Controller
{
    public function dispatch(Request $request)
    {
        /** @var WpRouter $wpRouter */
        $wpRouter = app('wpRouter');

        require $this->wpRoutes();

        $response = $wpRouter->dispatch($request);

        return $response;
    }

    protected function wpRoutes(): string
    {
        return base_path('routes/wp.php');
    }
}
