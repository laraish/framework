<?php

namespace Laraish\Routing\Matching;

use Illuminate\Http\Request;
use Illuminate\Routing\Matching\ValidatorInterface;
use Illuminate\Routing\Route;
use Illuminate\Routing\Matching\UriValidator as OriginalUriValidator;
use Illuminate\Support\Collection;
use Laraish\Support\Wp\Model\Post;
use Laraish\Support\Wp\Model\Term;

class UriValidator implements ValidatorInterface
{
    private static $conditionalFunctionsMap = [
        /* Generic Types */
        '404' => 'is_404',
        'search' => 'is_search',
        'front_page' => 'is_front_page',
        'home' => 'is_home',
        'archive' => 'is_archive',
        'attachment' => 'is_attachment',
        'date' => 'is_date',
        'comments_popup' => 'is_comments_popup',
        'paged' => 'is_paged',
        /* Specific Types */
        'single' => 'is_single',
        'singular' => 'is_singular',
        'page' => 'is_page',
        'category' => 'is_category',
        'post_type_archive' => 'is_post_type_archive',
        'taxonomy' => 'is_tax',
        'tag' => 'is_tag',
        'author' => 'is_author',
    ];

    /**
     * @var \Illuminate\Routing\Route
     */
    private $route;

    /**
     * @var \Illuminate\Http\Request
     */
    private $request;

    /**
     * @var \Illuminate\Routing\Matching\UriValidator
     */
    private $originalUriValidator;

    /**
     * The routing hierarchy for the current routing.
     * @var array
     */
    private $routingHierarchy;

    /**
     * The URI for the current routing.
     * @var string
     */
    private $uri;

    /**
     * The post-type for the current routing.
     * @var string
     */
    private $pageType;

    /**
     * The main selector of the current routing.
     * @var string
     */
    private $mainSelector;

    /**
     * If the current page is a generic page like `home` or `page` etc.
     * @var bool
     */
    private $isGenericPage;

    /**
     * The current queried object.
     * @var object|null
     */
    private $queriedObject = false;

    /**
     * Get the conditional function provided by WordPress.
     * Such as `is_page()` or `is_home()` etc.
     *
     * @param string $pageType
     *
     * @return null|string
     */
    private static function getConditionalFunction($pageType)
    {
        return isset(self::$conditionalFunctionsMap[$pageType])
            ? '\\' . self::$conditionalFunctionsMap[$pageType]
            : null;
    }

    /**
     * UriValidator constructor.
     */
    public function __construct()
    {
        $this->originalUriValidator = new OriginalUriValidator();
    }

    /**
     * Validate a given rule against a route and request.
     *
     * @param  \Illuminate\Routing\Route $route
     * @param  \Illuminate\Http\Request $request
     *
     * @return bool
     */
    public function matches(Route $route, Request $request)
    {
        $this->refreshState($route, $request);

        // if the current page is a generic page
        // then just use the uri to test
        if ($this->isGenericPage) {
            return $this->is($this->pageType);
        }

        // These pages are special because they may have a hierarchy.
        // e.g. `page.foo.bar`

        if ($this->pageType === 'category' and is_category()) {
            return $this->isSpecificTaxonomyTerm(true) ?: $this->tryFallback();
        }

        if ($this->pageType === 'taxonomy' and is_tax()) {
            return $this->isSpecificTaxonomyTerm() ?: $this->tryFallback();
        }

        if ($this->pageType === 'page' and is_page()) {
            return $this->isSpecificPage() ?: $this->tryFallback();
        }

        return $this->is($this->pageType, $this->mainSelector);
    }

    /**
     * Reset the properties that need to be updated.
     *
     * @param Route $route
     * @param Request $request
     */
    private function refreshState(Route $route, Request $request)
    {
        $this->route = $route;
        $this->request = $request;
        $this->uri = str_replace('/', '.', $route->uri()); // Transform the $uri to the form of `prefix.URI-fragment`   e.g. `page.about`

        $uriComponents = explode('.', $this->uri); // e.g.  `page.foo.bar` =>  ['page', 'foo', 'bar']

        if (count($uriComponents) === 1) {
            // the current page is a generic page
            $this->isGenericPage = true;
            $this->pageType = $uriComponents[0]; // e.g.  `page` `home`
            $this->mainSelector = null;
            $this->routingHierarchy = null;
        } else {
            // the current page is a specified page
            $this->isGenericPage = false;
            $this->pageType = $uriComponents[0]; // e.g.  `{page}.foo.bar`
            $this->mainSelector = $uriComponents[1]; // e.g.  `page.{foo}.bar`
            $this->routingHierarchy = array_slice($uriComponents, 1); // e.g.  `page.{foo.bar}`
        }

        // get and set the queried object only once
        if ($this->queriedObject === false) {
            $this->queriedObject = get_queried_object();
        }
    }

    /**
     * Check if the current page is matching to the routing page.
     * @return bool
     */
    private function isSpecificPage()
    {
        static $currentHierarchy;

        if (!isset($currentHierarchy)) {
            $currentPost = new Post(get_post());
            $currentHierarchy = $currentPost
                ->ancestors()
                ->map(function (Post $post) {
                    return urldecode($post->wpPost()->post_name);
                })
                ->push(urldecode($currentPost->wpPost()->post_name));
        }

        return $this->isSelfOrDescendant($currentHierarchy);
    }

    /**
     * Check if the current page is matching to the routing page.
     *
     * @param bool $isCategory
     *
     * @return bool
     */
    private function isSpecificTaxonomyTerm($isCategory = false)
    {
        static $currentHierarchy;

        $queriedObject = $this->queriedObject;
        if (!$queriedObject instanceof \WP_Term) {
            return $this->fallback();
        }

        if (!isset($currentHierarchy)) {
            $currentTerm = new Term($queriedObject);
            $currentTaxonomy = urldecode($currentTerm->wpTerm()->taxonomy);

            $currentHierarchy = $currentTerm
                ->ancestors()
                ->push($currentTerm)
                ->map(function (Term $term) {
                    return urldecode($term->slug());
                });

            if (!$isCategory) {
                $currentHierarchy->prepend($currentTaxonomy);
            }
        }

        return $this->isSelfOrDescendant($currentHierarchy);
    }

    /**
     * Check if the current page is a descendant page
     * of the routing page. Or if the current page is it self.
     */
    private function isSelfOrDescendant(Collection $currentHierarchy)
    {
        $currentHierarchyLevel = count($currentHierarchy);
        $routingHierarchyLevel = count($this->routingHierarchy);

        // Current: a/b  Routing: a/b/c/d
        // viewing a parent page of specified route.
        if ($currentHierarchyLevel < $routingHierarchyLevel) {
            return false;
        }

        // Current: a/b/c/d  Routing: a/b
        $intersectingHierarchy = $currentHierarchy->slice(0, $routingHierarchyLevel)->all();
        $matched = true;
        foreach ($this->routingHierarchy as $index => $value) {
            if ($value === '**') {
                break;
            }
            if ($value === '*') {
                continue;
            }
            if ($value !== $intersectingHierarchy[$index]) {
                $matched = false;
                break;
            }
        }

        return $matched;
    }

    /**
     * Checks if the given type of page is being displayed
     * by calling the conditional functions provided by WordPress.
     *
     * @param string $pageType The type of conditional function.
     * @param mixed $selector  A selector for increasing the specificity.
     *
     * @return bool
     */
    private function is($pageType, $selector = null)
    {
        $conditionalFunction = self::getConditionalFunction($pageType);

        if ($conditionalFunction !== null) {
            return is_null($selector)
                ? call_user_func($conditionalFunction)
                : call_user_func($conditionalFunction, $selector);
        }

        return $this->fallback();
    }

    /**
     * Try to fallback to original uri validator if it is not a WordPress routing.
     * @return bool
     */
    private function tryFallback()
    {
        $isWordPressSpecificRouting = array_key_exists($this->pageType, self::$conditionalFunctionsMap);

        return $isWordPressSpecificRouting ? false : $this->fallback();
    }

    /**
     * Falls back to the original uri validator.
     * @return bool
     */
    private function fallback()
    {
        return $this->originalUriValidator->matches($this->route, $this->request);
    }
}
