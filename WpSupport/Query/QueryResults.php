<?php

namespace Laraish\WpSupport\Query;

use WP_Query;
use ArrayObject;
use Laraish\Pagination\Paginator;
use Illuminate\Support\Collection;
use Laraish\Contracts\WpSupport\Query\QueryResults as QueryResultsContracts;

class QueryResults extends ArrayObject implements QueryResultsContracts
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
    public function __construct(array $items = [], WP_Query $wp_query)
    {
        parent::__construct($items);

        $this->wp_query = $wp_query;
    }

    /**
     * Get the pagination of the query results.
     *
     * @param array $options
     *
     * @return \Laraish\Contracts\Pagination\Paginator
     */
    public function getPagination(array $options = [])
    {
        $wp_query    = $this->wp_query;
        $total       = (int)$wp_query->found_posts;
        $perPage     = (int)$wp_query->query_vars['posts_per_page'];
        $currentPage = (int)$wp_query->query_vars['paged'];

        return new Paginator($total, $perPage, $currentPage, $options);
    }

    /**
     * Get the original WP_Query object.
     * @return WP_Query
     */
    public function wpQuery()
    {
        return $this->wp_query;
    }

    /**
     * Convert to Collection object.
     * @return \Illuminate\Support\Collection
     */
    public function toCollection()
    {
        return new Collection((array)$this);
    }
}