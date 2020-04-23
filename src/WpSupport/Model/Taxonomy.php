<?php

namespace Laraish\WpSupport\Model;

use WP_Term;
use WP_Term_Query;
use Illuminate\Support\Collection;

class Taxonomy extends BaseModel
{
    /**
     * The taxonomy name.
     * @var string
     */
    protected $name;

    /**
     * The term class name to be used.
     * @var string
     */
    protected $termClass;

    /**
     * Taxonomy constructor.
     *
     * @param string $name
     * @param string $termClass
     */
    public function __construct(string $name, string $termClass = Term::class)
    {
        $this->name = $name;
        $this->termClass = $termClass;
    }

    /**
     * @param WP_Term $term
     *
     * @return Term
     */
    protected function createTerm(WP_Term $term): Term
    {
        $class = $this->termClass;

        return new $class($term);
    }

    /**
     * Retrieve the terms of this taxonomy that are attached to the post.
     *
     * @param Post | int | \WP_Post | null $post
     *
     * @return Collection
     */
    public function theTerms($post): Collection
    {
        $theTerms = get_the_terms($post instanceof Post ? $post->wpPost() : $post, $this->name);
        if (\is_array($theTerms)) {
            $theTerms = array_map(function ($term) {
                return $this->createTerm($term);
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
    public function terms(array $args = []): Collection
    {
        $args = array_merge(['taxonomy' => $this->name], $args);

        $terms = array_map(function ($term) {
            return $this->createTerm($term);
        }, get_terms($args));

        return $this->setAttribute(__METHOD__, new Collection($terms));
    }

    /**
     * Get all Term data from database by Term field and data.
     *
     * @param string $field
     * @param mixed $value
     *
     * @return null|Term
     */
    public function getTermBy($field, $value): ?Term
    {
        $term = get_term_by($field, $value, $this->name);
        if ( ! $term) {
            return null;
        }

        return $this->setAttribute(__METHOD__, $this->createTerm($term));
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
    public function getTermsBySlugHierarchy(array $slugHierarchy): Collection
    {
        $taxonomyName = $this->name();
        $rootTermSlug = $slugHierarchy[0];
        $descendantTermSlugs = \array_slice($slugHierarchy, 1);
        $parentTerm = get_term_by('slug', $rootTermSlug, $taxonomyName);
        $terms = [$this->createTerm($parentTerm)];

        foreach ($descendantTermSlugs as $childrenCategorySlug) {
            $query = new WP_Term_Query([
                'taxonomy' => $taxonomyName,
                'slug'     => $childrenCategorySlug,
                'parent'   => $parentTerm->term_id
            ]);

            $queriedTerms = $query->get_terms();
            if ($queriedTerms) {
                $term = $queriedTerms[0];
                $terms[] = $this->createTerm($term);
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
    public function name(): string
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
    public static function getSlugHierarchyByTerm(Term $term): array
    {
        $categoryHierarchy = $term->ancestors()->map(function (Term $term) {
            return $term->slug();
        })->push($term->slug());


        return implode('/', $categoryHierarchy->toArray());
    }
}