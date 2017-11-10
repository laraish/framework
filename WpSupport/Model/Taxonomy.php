<?php

namespace Laraish\WpSupport\Model;

use Illuminate\Support\Collection;
use WP_Term_Query;

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
     * Get the terms by specifying a slug hierarchy.
     * An example would like ['animal','cat','american-shorthair'].
     * Where 'animal' is the slug of the root term, and followed by the secondary, tertiary.. and so on.
     * By using this method you'll get the exactly term where positioned at the hierarchy you specified.
     *
     * @param array $slugHierarchy
     *
     * @return Collection
     */
    public function getTermsBySlugHierarchy(array $slugHierarchy)
    {
        $taxonomyName        = $this->name();
        $rootTermSlug        = $slugHierarchy[0];
        $descendantTermSlugs = array_slice($slugHierarchy, 1);
        $parentTerm          = get_term_by('slug', $rootTermSlug, $taxonomyName);
        $terms               = [new Term($parentTerm)];

        foreach ($descendantTermSlugs as $childrenCategorySlug) {
            $query = new WP_Term_Query([
                'taxonomy' => $taxonomyName,
                'slug'     => $childrenCategorySlug,
                'parent'   => $parentTerm->term_id
            ]);

            $queriedTerms = $query->get_terms();
            if ($queriedTerms) {
                $term       = $queriedTerms[0];
                $terms[]    = new Term($term);
                $parentTerm = $term;
            }
        }

        $terms = new Collection($terms);

        return $this->setAttribute(__METHOD__, $terms);
    }

    /**
     * The name of this taxonomy.
     * @return string
     */
    public function name()
    {
        return $this->setAttribute(__METHOD__, $this->name);
    }

    /**
     * Get slug hierarchy by a term.
     *
     * @param Term $term
     *
     * @return array
     */
    public static function getSlugHierarchyByTerm(Term $term)
    {
        $categoryHierarchy = $term->ancestors()->map(function (Term $term) {
            return $term->slug();
        })->push($term->slug());


        return implode('/', $categoryHierarchy->toArray());
    }
}