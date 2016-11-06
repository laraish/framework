<?php

namespace Laraish\WpSupport\Model;

class Taxonomy extends BaseModel
{
    /**
     * The taxonomy name
     * @type string
     */
    public $name;

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
     * @param int|\WP_Post|null $post
     *
     * @return mixed
     */
    public function the_terms($post)
    {
        return array_map(function ($term) {
            $term->url = get_term_link($term);

            return $term;
        }, get_the_terms($post, $this->name) ?: []);
    }

    public function terms($args = [])
    {
        $queried_object = get_queried_object();
        $args           = array_merge(['taxonomy' => $this->name], $args);

        return array_map(function ($term) use ($queried_object) {
            $term->url     = get_term_link($term);
            $term->queried = false;
            if (isset($queried_object->term_id) AND $queried_object->taxonomy === $this->name AND $queried_object->term_id == $term->term_id) {
                $term->queried = true;
            }

            return $term;
        }, get_terms($args));
    }
}