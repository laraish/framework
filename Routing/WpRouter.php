<?php

namespace Laraish\Routing;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Container\Container;
use Laraish\Routing\Matching\UriValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Matching\HostValidator;
use Illuminate\Routing\Matching\MethodValidator;
use Illuminate\Routing\Matching\SchemeValidator;

class WpRouter
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var string[]
     */
    public static $defaultMethods = ['GET', 'HEAD'];

    /**
     * Create a new Router instance.
     *
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     * @param \Illuminate\Container\Container|null $container
     * @return void
     */
    public function __construct(Dispatcher $events, Container $container = null)
    {
        $this->router = new Router($events, $container);
    }

    /**
     * @param $uri
     * @param $action
     * @param null $methods
     * @return \Illuminate\Routing\Route
     */
    public function addRoute($uri, $action, $methods = null)
    {
        return $this->router->addRoute($methods ?? static::$defaultMethods, $uri, $action);
    }

    /**
     * Register a new home route with the router.
     *
     * @param array|string|callable $action
     * @param string|string[]|null $methods
     * @return \Illuminate\Routing\Route
     */
    public function home($action, $methods = null)
    {
        $uri = 'home';
        return $this->addRoute($uri, $action, $methods);
    }

    /**
     * Register a new archive route with the router.
     *
     * @param array|string|callable $action
     * @param string|string[]|null $methods
     * @return \Illuminate\Routing\Route
     */
    public function archive($action, $methods = null)
    {
        $uri = 'archive';
        return $this->addRoute($uri, $action, $methods);
    }

    /**
     * Register a new taxonomy route with the router.
     *
     * @param string $selector
     * @param array|string|callable $action
     * @param string|string[]|null $methods
     * @return \Illuminate\Routing\Route
     */
    public function taxonomy(string $selector, $action, $methods = null)
    {
        $selectorSegments = explode('.', $selector);
        $taxonomy = $selectorSegments[0];
        $baseUri = $taxonomy === 'category' ? 'category' : ($taxonomy === 'post_tag' ? 'tag' : 'taxonomy');

        if ($baseUri === 'taxonomy') {
            $uri = "{$baseUri}.{$selector}";
        } else {
            $subSelector = array_slice($selectorSegments, 1);
            $uri = implode('.', array_merge([$baseUri], $subSelector));
        }

        return $this->addRoute($uri, $action, $methods);
    }

    /**
     * Register a new taxonomy route with the router.
     *
     * @param array|string|callable $selectorOrAction
     * @param array|string|callable|null $actionOrMethods
     * @param string|string[]|null $methods
     * @return \Illuminate\Routing\Route
     */
    public function page($selectorOrAction, $actionOrMethods = null, $methods = null)
    {
        $args = $this->normalizeSignature('page', $selectorOrAction, $actionOrMethods, $methods);
        return $this->addRoute(...$args);
    }

    /**
     * Register a new taxonomy route with the router.
     *
     * @param string $postType
     * @param array|string|callable $action
     * @param string|string[]|null $methods
     * @return \Illuminate\Routing\Route
     */
    public function post(string $postType, $action, $methods = null)
    {
        $uri = "singular.{$postType}";
        return $this->addRoute($uri, $action, $methods);
    }

    /**
     * Register a new taxonomy route with the router.
     *
     * @param string $postType
     * @param array|string|callable $action
     * @param string|string[]|null $methods
     * @return \Illuminate\Routing\Route
     */
    public function postArchive(string $postType, $action, $methods = null)
    {
        $uri = "post_type_archive.{$postType}";
        return $this->addRoute($uri, $action, $methods);
    }

    /**
     * Register a new taxonomy route with the router.
     *
     * @param array|string|callable $selectorOrAction
     * @param array|string|callable|null $actionOrMethods
     * @param string|string[]|null $methods
     * @return \Illuminate\Routing\Route
     */
    public function author($selectorOrAction, $actionOrMethods = null, $methods = null)
    {
        $args = $this->normalizeSignature('author', $selectorOrAction, $actionOrMethods, $methods);
        return $this->addRoute(...$args);
    }

    /**
     * Register a new search route with the router.
     *
     * @param array|string|callable $action
     * @param string|string[]|null $methods
     * @return \Illuminate\Routing\Route
     */
    public function search($action, $methods = null)
    {
        $uri = 'search';
        return $this->addRoute($uri, $action, $methods);
    }

    /**
     * Register a new 404-not-found route with the router.
     *
     * @param array|string|callable $action
     * @param string|string[]|null $methods
     * @return \Illuminate\Routing\Route
     */
    public function notFound($action, $methods = null)
    {
        $uri = '404';
        return $this->addRoute($uri, $action, $methods);
    }

    /**
     * Match all uri and any methods.
     *
     * @param array|string|callable $action
     * @param string|string[]|null $methods
     * @return \Illuminate\Routing\Route
     */
    public function matchAll($action)
    {
        $placeholder = 'fallbackPlaceholder';

        return $this->router->any("{{$placeholder}}", $action)->where($placeholder, '.*');
    }

    /**
     * Discovery the controller or view automatically.
     */
    public function autoDiscovery(): void
    {
        $actionAndData = (new WpRouteActionResolver())->resolve();

        if ($actionAndData === null) {
            return;
        }

        $action = array_slice($actionAndData, 0, 2);
        $data = $actionAndData[2] ?? null;

        $route = $this->matchAll($action);

        if ($data) {
            $route->defaults('view', Arr::pull($data, 'view'))->defaults('data', $data);
        }
    }

    /**
     * Dispatch the request to the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dispatch(Request $request)
    {
        $originalValidators = Route::getValidators();
        $newValidators = [new UriValidator(), new MethodValidator(), new SchemeValidator(), new HostValidator()];

        Route::$validators = $newValidators;

        $response = $this->router->dispatch($request);

        Route::$validators = $originalValidators;

        return $response;
    }

    /**
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * @param string $baseUri
     * @param array|string|callable $selectorOrAction
     * @param array|string|callable|null $actionOrMethods
     * @param string|string[]|null $methods
     * @return array
     */
    protected function normalizeSignature(
        string $baseUri,
        $selectorOrAction,
        $actionOrMethods = null,
        $methods = null
    ): array {
        $selector = $selectorOrAction;
        $action = $actionOrMethods;

        if ($this->isAction($selectorOrAction)) {
            $uri = $baseUri;
            $action = $selectorOrAction;
            $methods = $actionOrMethods;
        } else {
            $uri = "{$baseUri}.{$selector}";
        }

        return [$uri, $action, $methods];
    }

    /**
     * Test if the given variable is an action.
     *
     * @param mixed $var
     * @return bool
     */
    protected function isAction($var): bool
    {
        if (is_string($var) && strpos($var, '@') === false) {
            return false;
        }

        return true;
    }

    /**
     * Forward methods to the router instance.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        return $this->router->$method(...$args);
    }
}
