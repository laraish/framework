<?php

namespace Laraish\Support\Wp\Query;

use WP_Query;
use Laraish\Pagination\Paginator;
use Illuminate\Support\Collection;
use Laraish\Contracts\Pagination\Paginator as PaginatorContract;

class QueryResults extends Collection
{
    /**
     * The WP_Query object.
     * @var WP_Query
     */
    protected $wp_query;

    /**
     * @param array $items
     * @param WP_Query $wp_query
     *
     * @return static
     */
    public static function create(array $items, WP_Query $wp_query): self
    {
        $instance = new static($items);

        return $instance->setWpQuery($wp_query);
    }

    /**
     * Get the pagination of the query results.
     *
     * @param array $options
     *
     * @return PaginatorContract
     */
    public function getPagination(array $options = []): PaginatorContract
    {
        $wp_query = $this->wp_query;
        $total = (int) $wp_query->found_posts;
        $perPage = (int) $wp_query->query_vars['posts_per_page'];
        $currentPage = (int) $wp_query->query_vars['paged'];

        return new Paginator($total, $perPage, $currentPage, $options);
    }

    /**
     * @param WP_Query $wp_query
     *
     * @return $this
     */
    public function setWpQuery(WP_Query $wp_query): self
    {
        $this->wp_query = $wp_query;

        return $this;
    }

    /**
     * Get the original WP_Query object.
     * @return WP_Query
     */
    public function wpQuery(): WP_Query
    {
        return $this->wp_query;
    }

    /**
     * All found posts count.
     * @return int
     */
    public function countAll()
    {
        return $this->wp_query->found_posts;
    }
}
