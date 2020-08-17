<?php

namespace Laraish\Routing;

use Illuminate\Routing\Controller;
use Laraish\Routing\Traits\ViewDebugger;

class ViewController extends Controller
{
    use ViewDebugger;

    /**
     * Invoke the controller method.
     *
     * @param  array  $args
     * @return \Illuminate\Contracts\View\View
     */
    public function index(...$args)
    {
        [$view, $data] = array_slice($args, -2);

        return $this->view($view, $data);
    }
}
