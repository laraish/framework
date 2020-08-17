<?php

namespace Laraish\Foundation\Http;

use Exception;
use Throwable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Facade;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class Kernel extends HttpKernel
{
    /**
     * Handle an incoming HTTP request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function handle($request)
    {
        if (defined('ARTISAN_BINARY')) {
            return parent::handle($request);
        }

        $request->enableHttpMethodParameterOverride();
        $this->sendRequestThroughRouter($request);

        return $this;
    }

    /**
     * Send the given request through the middleware / router.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response|void
     */
    protected function sendRequestThroughRouter($request)
    {
        if (defined('ARTISAN_BINARY')) {
            return parent::sendRequestThroughRouter($request);
        }

        $this->app->instance('request', $request);

        Facade::clearResolvedInstance('request');

        $this->bootstrap();

        // If administration panel is attempting to be displayed,
        // we don't need any response
        if (is_admin()) {
            return;
        }

        // Get response on `template_include` filter so the conditional functions work correctly
        add_filter(
            'template_include',
            function ($template) use ($request) {
                // If the template is not index.php, then don't output anything
                if ($template !== get_template_directory() . '/index.php') {
                    return $template;
                }

                $this->syncMiddlewareToWpRouter();

                try {
                    $response = (new Pipeline($this->app))
                        ->send($request)
                        ->through($this->app->shouldSkipMiddleware() ? [] : $this->middleware)
                        ->then($this->dispatchToRouter());
                } catch (Exception $e) {
                    $this->reportException($e);

                    $response = $this->renderException($request, $e);
                }

                $this->app['events']->dispatch(new RequestHandled($request, $response));

                return $template;
            },
            PHP_INT_MAX
        );
    }

    protected function syncMiddlewareToWpRouter()
    {
        $originalRouter = $this->router;
        $this->router = $this->app['wpRouter']->getRouter();
        parent::syncMiddlewareToRouter();
        $this->router = $originalRouter;
    }
}
