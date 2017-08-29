<?php

namespace Laraish\WpSupport\Model;

use Illuminate\Support\Collection;

class Taxonomy extends BaseModel
{
    /**
     * The taxonomy name
     * @type string
     */
    protected $name;

    /**
     * Taxonomy constructor.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Retrieve the terms of this taxonomy that are attached to the post.
     *
     * @param Post | int | \WP_Post | null $post
     *
     * @return Collection
     */
    public function theTerms($post)
    {
        $theTerms = get_the_terms($post instanceof Post ? $post->wpPost() : $post, $this->name);
        if (is_array($theTerms)) {
            $theTerms = array_map(function ($term) {
                return new Term($term);
            }, $theTerms);
        } else {
            $theTerms = [];
        }

        return $this->setAttribute(__METHOD__, new Collection($theTerms));
    }

    /**
     * Retrieve the terms in the taxonomy.
     *
     * @param array $args
     *
     * @return Collection
     */
    public function terms($args = [])
    {
        $args = array_merge(['taxonomy' => $this->name], $args);

        $terms = array_map(function ($term) {
            return new Term($term);
        }, get_terms($args));

        return $this->setAttribute(__METHOD__, new Collection($terms));
    }

    /**
     * Get all Term data from database by Term field and data.
     *
     * @param $field
     * @param $value
     *
     * @return null|Term
     */
    public function getTermBy($field, $value)
    {
        $term = get_term_by($field, $value, $this->name);
        if ( ! $term) {
            return null;
        }

        return $this->setAttribute(__METHOD__, new Term($term));
    }

    /**
     * The name of this taxonomy.
     * @return string
     */
    public function name()
    {
        return $this->setAttribute(__METHOD__, $this->name);
    }
}