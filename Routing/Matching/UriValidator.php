<?php

namespace Laraish\Routing\Matching;

use Illuminate\Http\Request;
use Illuminate\Routing\Matching\ValidatorInterface;
use Illuminate\Routing\Route;
use Illuminate\Routing\Matching\UriValidator as OriginalUriValidator;

class UriValidator implements ValidatorInterface
{
    private $conditional_fn_map = [
        /* Generic Types */
        '404'               => 'is_404',
        'search'            => 'is_search',
        'front_page'        => 'is_front_page',
        'home'              => 'is_home',
        'archive'           => 'is_archive',
        'attachment'        => 'is_attachment',
        'date'              => 'is_date',
        'comments_popup'    => 'is_comments_popup',
        'paged'             => 'is_paged',
        /* Specific Types */
        'single'            => 'is_single',
        'singular'          => 'is_singular',
        'page'              => 'is_page',
        'category'          => 'is_category',
        'post_type_archive' => 'is_post_type_archive',
        'taxonomy'          => 'is_tax',
        'tag'               => 'is_tag',
        'author'            => 'is_author',
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
     * UriValidator constructor.
     */
    public function __construct()
    {
        $this->originalUriValidator = new OriginalUriValidator;
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
        $this->route   = $route;
        $this->request = $request;

        $uri    = $route->uri();
        $prefix = $route->getPrefix();

        // Transform the $uri to the form of `prefix.URI-fragment`   e.g. `page.about`
        if ( ! empty($prefix)) {
            $uri = str_replace('/', '.', $uri);
        }

        // if the current page is a generic page
        // then just use the uri to test
        if ($this->is_generic($uri)) {
            return $this->is_it_matches($uri);
        }

        // if the current page is a specified page
        // extract the post type and post slug
        $post_info                = explode('.', $uri);
        $post_type                = $post_info[0];
        $post_slug                = $post_info[1];
        $post_info_greater_than_2 = count($post_info) > 2;
        $post_hierarchy           = array_slice($post_info, 1);

        if ($post_type === 'category' AND \is_category()) {
            $isSubCategory = $post_info_greater_than_2;
            if ($isSubCategory) {
                $cat       = get_category(get_query_var('cat'));
                $hierarchy = $this->get_category_parents($cat);
                array_push($hierarchy, urldecode($cat->slug));

                return $hierarchy == $post_hierarchy;
            }
        }

        if ($post_type === 'taxonomy' AND \is_tax()) {
            $isSubTerm = $post_info_greater_than_2;
            if ($isSubTerm) {
                $taxonomy  = get_query_var('taxonomy');
                $term      = get_term_by('slug', get_query_var('term'), $taxonomy);
                $hierarchy = $this->get_taxonomy_parents($term, $post_slug);
                array_unshift($hierarchy, $taxonomy);
                array_push($hierarchy, urldecode($term->slug));

                return $hierarchy == $post_hierarchy;
            }

            return \is_tax($post_slug);
        }

        if ($post_type === 'page' AND \is_page()) {
            // if sub-page is supplied, detect if the current page matches
            $isSubPage = count($post_info) > 2;
            if ($isSubPage) {
                $currentPost = \get_post(\get_the_ID());
                $hierarchy   = $this->get_page_parents($currentPost);

                array_push($hierarchy, $currentPost->post_name);

                return $hierarchy == $post_hierarchy;
            }
        }

        return $this->is_it_matches($post_type, $post_slug);
    }

    private function is_generic($uri)
    {
        return strpos($uri, '.') ? false : true;
    }

    private function get_conditional_fn($type)
    {
        return isset($this->conditional_fn_map[$type]) ? '\\' . $this->conditional_fn_map[$type] : false;
    }

    private function is_it_matches($type, $slug = null)
    {
        $conditional_fn = $this->get_conditional_fn($type);
        if ($conditional_fn !== false) {
            return is_null($slug) ? call_user_func($conditional_fn) : call_user_func($conditional_fn, $slug);
        }

        return $this->originalUriValidator->matches($this->route, $this->request);
    }

    private function get_page_parents(\WP_Post $post, $parents = array())
    {
        if ($post->post_parent) {
            $parent = \get_post($post->post_parent);
            array_unshift($parents, urldecode($parent->post_name));

            return $this->get_page_parents($parent, $parents);
        }

        return $parents;
    }

    function get_taxonomy_parents($term, $taxonomy, $parents = array())
    {
        if ($term->parent AND ($term->parent != $term->term_id)) {
            $parent = get_term($term->parent, $taxonomy);
            array_unshift($parents, urldecode($parent->slug));

            return $this->get_taxonomy_parents($parent, $taxonomy, $parents);
        }

        return $parents;
    }

    function get_category_parents($term)
    {
        return $this->get_taxonomy_parents($term, 'category');
    }

}