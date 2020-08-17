<?php

namespace Laraish\Routing;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Laraish\Support\Wp\Model\Post;
use Laraish\Support\Wp\Model\Term;
use Laraish\Support\Wp\Model\User;

class WpRouteActionResolver
{
    public static $viewDirectory = 'wp';
    public static $modelNamespace = '\\App\\Models\\Wp\\';
    public static $controllerNamespace = '\\App\\Http\\Controllers\\Wp\\';

    /**
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $view;

    /**
     * @var mixed
     */
    protected $queriedObject;

    /**
     * @var bool
     */
    public $resolveController = true;

    /**
     * @var bool
     */
    public $injectDefaultData = true;

    /**
     * WpRouteActionResolver constructor.
     */
    public function __construct()
    {
        $this->view = app('view');
        $this->queriedObject = get_queried_object();
    }

    /**
     * Return the action for the discovered action
     */
    public function resolve(): ?array
    {
        $queriedObject = $this->queriedObject;

        if ((is_home() || is_front_page()) && $action = $this->getHomeAction()) {
            return $action;
        }

        if (is_page() && $queriedObject instanceof \WP_Post && $action = $this->getPageAction()) {
            return $action;
        }

        if (is_singular() && $queriedObject instanceof \WP_Post && $action = $this->getSingularAction()) {
            return $action;
        }

        if (is_post_type_archive() && $action = $this->getPostTypeArchiveAction()) {
            return $action;
        }

        if ((is_category() || is_tag() || is_tax()) && $queriedObject instanceof \WP_Term && $action = $this->getTermAction()) {
            return $action;
        }

        if (is_author() && $queriedObject instanceof \WP_User && $action = $this->getAuthorAction()) {
            return $action;
        }

        if (is_search() && $action = $this->getSearchAction()) {
            return $action;
        }

        if (is_archive() && $action = $this->getArchiveAction()) {
            return $action;
        }

        if (is_404() && $action = $this->getNotFoundAction()) {
            return $action;
        }

        return null;
    }

    protected function getHomeAction(): ?array
    {
        $viewData = $this->injectDefaultData ? [
            'post' => new Post($this->queriedObject),
        ] : [];

        if ($action = $this->getGenericPageAction('home', $viewData)) {
            return $action;
        }

        return null;
    }

    protected function getPageAction(): ?array
    {
        $post = new Post($this->queriedObject);

        if ($pageTemplate = $post->page_template) {
            $hierarchy = ['template', Str::slug($pageTemplate)];
        } else {
            $hierarchy = $post->ancestors()->map(function (Post $post) {
                return $post->wpPost()->post_name;
            })->concat([$post->wpPost()->post_name])->map(function ($slug) {
                return urldecode($slug);
            })->prepend('page');
        }

        $viewData = $this->injectDefaultData ? [
            'post' => $post,
        ] : [];

        if ($action = $this->getActionByHierarchy($hierarchy, $viewData)) {
            return $action;
        }

        return null;
    }

    protected function getSingularAction(): ?array
    {
        if ($modelClass = $this->getModelClassForPostType()) {
            $post = new $modelClass($this->queriedObject);
        } else {
            $post = new Post($this->queriedObject);
        }

        if ($pageTemplate = $post->page_template) {
            $hierarchy = ['template', Str::slug($pageTemplate)];
        } else {
            $hierarchy = ['post', $this->getPostType()];
        }

        $viewData = $this->injectDefaultData ? [
            'post' => $post,
        ] : [];

        if ($action = $this->getActionByHierarchy($hierarchy, $viewData)) {
            return $action;
        }

        return null;
    }

    protected function getPostTypeArchiveAction(): ?array
    {
        $hierarchy = ['post-archive', $this->getPostType()];
        $viewData = $this->injectDefaultData ? $this->getDataForArchivePage() : [];

        if ($action = $this->getActionByHierarchy($hierarchy, $viewData, 'archive')) {
            return $action;
        }

        return null;
    }

    protected function getTermAction(): ?array
    {
        $currentTerm = new Term($this->queriedObject);
        $currentTaxonomy = urldecode($currentTerm->taxonomy());

        $termHierarchy = $currentTerm->ancestors()->push($currentTerm)->map(function (Term $term) {
            return urldecode($term->slug());
        })->prepend($currentTaxonomy)->prepend('taxonomy');

        $viewData = $this->injectDefaultData ? ([
                'term' => new Term($this->queriedObject),
            ] + $this->getDataForArchivePage()) : [];

        if ($action = $this->getActionByHierarchy($termHierarchy, $viewData, 'archive')) {
            return $action;
        }

        return null;
    }

    protected function getAuthorAction(): ?array
    {
        $author = new User($this->queriedObject);
        $hierarchy = ['author', $author->nickname()];
        $viewData = $this->injectDefaultData ? ([
                'author' => $author,
            ] + $this->getDataForArchivePage()) : [];

        if ($action = $this->getActionByHierarchy($hierarchy, $viewData, 'archive')) {
            return $action;
        }

        return null;
    }

    protected function getSearchAction(): ?array
    {
        $viewData = $this->injectDefaultData ? ([
                'keyword' => get_search_query()
            ] + $this->getDataForArchivePage()) : [];

        if ($action = $this->getActionByHierarchy(['search'], $viewData, 'archive')) {
            return $action;
        }

        return null;
    }

    protected function getArchiveAction(): ?array
    {
        $viewData = $this->injectDefaultData ? $this->getDataForArchivePage() : [];

        if ($action = $this->getGenericPageAction('archive', $viewData)) {
            return $action;
        }

        return null;
    }

    protected function getNotFoundAction(): ?array
    {
        if ($action = $this->getGenericPageAction('not-found')) {
            return $action;
        }

        return null;
    }

    protected function getGenericPageAction(string $type, $viewData = []): ?array
    {
        if ($action = $this->getActionByHierarchy([$type], $viewData)) {
            return $action;
        }

        return null;
    }

    /**
     * @param Collection|array $hierarchy
     * @param array|\Closure $viewData
     * @param string ...$fallbackGenericTypes
     * @return array|null
     */
    protected function getActionByHierarchy($hierarchy, $viewData = [], ...$fallbackGenericTypes): ?array
    {
        $hierarchy = is_array($hierarchy) ? new Collection($hierarchy) : $hierarchy;

        if ($this->resolveController && $action = $this->getControllerActionByHierarchy($hierarchy)) {
            return $action;
        }

        if ($action = $this->getViewActionByHierarchy($hierarchy, $viewData)) {
            return $action;
        }

        foreach ($fallbackGenericTypes as $fallbackGenericType) {
            if ($action = $this->getGenericPageAction($fallbackGenericType, $viewData)) {
                return $action;
            }
        }

        return null;
    }

    /**
     * @param Collection|array $hierarchy
     * @return array|null
     */
    protected function getControllerActionByHierarchy($hierarchy): ?array
    {
        $hierarchy = clone $hierarchy;

        while ($hierarchy->count() > 0) {
            $controller = $this->fullyQualifiedController(
                $hierarchy->map(function ($slug) {
                    return ucfirst(Str::camel($slug));
                })->implode('\\')
            );

            if ($this->controllerExists($controller)) {
                return [$controller, 'index'];
            }

            $hierarchy->pop();
        }

        return null;
    }

    /**
     * @param Collection|array $hierarchy
     * @param array|\Closure $viewData
     * @return array|null
     */
    protected function getViewActionByHierarchy($hierarchy, $viewData = []): ?array
    {
        $hierarchy = clone $hierarchy;

        while ($hierarchy->count() > 0) {
            $view = $this->getViewPath($hierarchy->implode('.'));
            if ($this->viewExists($view)) {
                $data = $viewData instanceof \Closure ? $viewData() : $viewData;

                return $this->getViewAction($view, $data);
            }

            $hierarchy->pop();
        }

        return null;
    }


    protected function fullyQualifiedController(string $controllerBaseName): string
    {
        return static::$controllerNamespace . $controllerBaseName;
    }

    public function controllerExists(string $controllerClass): bool
    {
        return class_exists($controllerClass);
    }

    protected function getViewPath(string $view): string
    {
        return static::$viewDirectory . ".$view";
    }

    protected function viewExists(string $view): bool
    {
        return $this->view->exists($view);
    }

    protected function getViewAction(string $view, $data = []): array
    {
        return [ViewController::class, 'index', ['view' => $view] + $data];
    }


    protected function getModelClassForPostType(string $postType = null): ?string
    {
        $postType = $postType ?? $this->getPostType();

        $classBaseName = ucfirst(Str::camel($postType));
        $modelClass = $this->fullyQualifiedModel("Post\\$classBaseName");

        return class_exists($modelClass) ? $modelClass : null;
    }

    protected function getPostType(): string
    {
        return get_post_type() ?: 'post';
    }

    protected function fullyQualifiedModel(string $modelBaseName): string
    {
        return static::$modelNamespace . $modelBaseName;
    }


    protected function getDataForArchivePage(): array
    {
        if ($modelClass = $this->getModelClassForPostType()) {
            $posts = $modelClass::queriedPosts();
        } else {
            $posts = Post::queriedPosts();
        }

        return [
            'posts' => $posts,
        ];
    }


    protected function getAction(string $controller): array
    {
        return [$controller, 'index'];
    }

}
