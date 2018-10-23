<?php

namespace Laraish\WpSupport\Query;

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
     * QueryResults constructor.
     *
     * @param array $items
     * @param WP_Query $wp_query
     */
    public function __construct(array $items, WP_Query $wp_query)
    {
        parent::__construct($items);

        $this->wp_query = $wp_query;
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
        $total = (int)$wp_query->found_posts;
        $perPage = (int)$wp_query->query_vars['posts_per_page'];
        $currentPage = (int)$wp_query->query_vars['paged'];

        return new Paginator($total, $perPage, $currentPage, $options);
    }

    /**
     * Get the original WP_Query object.
     * @return WP_Query
     */
    public function wpQuery(): WP_Query
    {
        return $this->wp_query;
    }
}