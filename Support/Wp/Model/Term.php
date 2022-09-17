<?php

namespace Laraish\Support\Wp\Model;

use WP_Term;
use InvalidArgumentException;
use Illuminate\Support\Collection;
use Laraish\Support\Wp\Query\QueryResults;

class Term extends BaseModel
{
    /**
     * @var null|string|array
     */
    public const TAXONOMY = null;

    /**
     * @var WP_Term
     */
    protected $wpTerm;

    /**
     * Term constructor.
     *
     * @param mixed $term
     */
    public function __construct(WP_Term $term)
    {
        $this->wpTerm = $term;
    }

    /**
     * Resolve the ACF fields.
     * @return array|bool|mixed
     */
    public function resolveAcfFields()
    {
        if (!\function_exists('get_fields')) {
            return [];
        }

        return get_fields($this->wpTerm);
    }

    /**
     * Get the original WP_Term object.
     * @return WP_Term
     */
    public function wpTerm(): WP_Term
    {
        return $this->wpTerm;
    }

    /**
     * The name of this term.
     * @return string
     */
    public function name(): string
    {
        return $this->wpTerm->name;
    }

    /**
     * The slug of this term.
     * @return string
     */
    public function slug(): string
    {
        return $this->wpTerm->slug;
    }

    /**
     * The term_id of this term.
     * @return int
     */
    public function termId(): int
    {
        return $this->wpTerm->term_id;
    }

    /**
     * The term_taxonomy_id of this term.
     * @return int
     */
    public function termTaxonomyId(): int
    {
        return $this->wpTerm->term_taxonomy_id;
    }

    /**
     * Get the taxonomy of the term.
     * @return string
     */
    public function taxonomy(): string
    {
        return $this->wpTerm->taxonomy;
    }

    /**
     * Get the description of the term.
     * @return string
     */
    public function description(): string
    {
        return $this->wpTerm->description;
    }

    /**
     * Get the count of the term.
     * @return int
     */
    public function count($publicOnly = true): int
    {
        if (!$publicOnly) {
            return $this->wpTerm->count;
        }

        /** @var \wpdb $wpdb */
        global $wpdb;

        $termId = $this->termId();

        $count = $wpdb->get_var(
            "
select COUNT(*) as post_count
    FROM $wpdb->posts as POSTS
WHERE EXISTS (
    SELECT 1
    FROM
         $wpdb->term_relationships as B
             INNER JOIN $wpdb->term_taxonomy as C
                 ON C.term_taxonomy_id = B.term_taxonomy_id
    WHERE C.term_id = $termId
      AND B.object_id = POSTS.ID
    )
  AND POSTS.post_status = 'publish'
  "
        );

        return (int) $count;
    }

    /**
     * Get the url of the term.
     * @return string
     */
    public function url(): string
    {
        $url = get_term_link($this->wpTerm);

        return $this->setAttribute(__METHOD__, $url);
    }

    /**
     * Get the parent of the term.
     * @return static | null
     */
    public function parent(): ?self
    {
        $parentId = $this->wpTerm->parent;
        if (!$parentId) {
            return null;
        }

        $parent = new static(get_term($parentId, $this->wpTerm->taxonomy));

        return $this->setAttribute(__METHOD__, $parent);
    }

    /**
     * Get the children of the term.
     *
     * @param array $query
     *
     * @return Collection
     */
    public function children(array $query = []): Collection
    {
        $defaultQuery = ['parent' => $this->termId()];
        $query = array_merge($query, $defaultQuery);
        $taxonomy = new Taxonomy($this->wpTerm->taxonomy, static::class);
        $children = $taxonomy->terms($query);

        return $this->setAttribute(__METHOD__, $children);
    }

    /**
     * Get the all the parents of a specific taxonomy term.
     *
     * @return Collection
     */
    public function ancestors(): Collection
    {
        $ancestors = [];
        $term = $this->wpTerm;
        while (!is_wp_error($term) and !empty($term->parent)) {
            $ancestors[] = $term = get_term($term->parent, $this->wpTerm->taxonomy);
        }

        // convert to Term object
        $ancestors = array_map(function ($term) {
            return new static($term);
        }, $ancestors);

        return $this->setAttribute(__METHOD__, new Collection($ancestors));
    }

    /**
     * Check if this term is currently being displayed.
     *
     * @return bool
     */
    public function isQueried(): bool
    {
        $queried_object = get_queried_object();

        $isQueried =
            (isset($queried_object->term_taxonomy_id) and
            $queried_object->term_taxonomy_id === $this->termTaxonomyId());

        return $this->setAttribute(__METHOD__, $isQueried);
    }

    /**
     * Test if this term is a descendant of the given term.
     *
     * @param WP_Term|static $term
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isDescendantOf($term): bool
    {
        $this->checkArgumentType($term, __METHOD__);

        $givenTerm = $term instanceof static ? $term->wpTerm() : $term;
        $myAncestors = $this->ancestors;
        $isDescendant = $myAncestors->search(function (Term $myAncestor) use ($givenTerm) {
            return $givenTerm->term_id === $myAncestor->termId();
        });

        return $isDescendant !== false;
    }

    /**
     * Test if this term is a descendant of the given term.
     *
     * @param WP_Term|static $term
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isAncestorOf($term): bool
    {
        $this->checkArgumentType($term, __METHOD__);

        $givenTerm = $term instanceof static ? $term : new static($term);
        $givenTermAncestors = $givenTerm->ancestors;
        $isAncestor = $givenTermAncestors->search(function (Term $givenPostAncestor) {
            return $this->termId() === $givenPostAncestor->termId();
        });

        return $isAncestor !== false;
    }

    /**
     * Check if the given value is valid.
     *
     * @param mixed $value
     * @param string $methodName
     *
     * @throws \InvalidArgumentException
     */
    protected function checkArgumentType($value, $methodName): void
    {
        if (!($value instanceof static || $value instanceof WP_Term)) {
            $className = static::class;
            throw new InvalidArgumentException("`$methodName` only accepts `WP_Term | $className`.");
        }
    }

    /**
     * Get the related posts for the given post type class.
     *
     * @param string $postClassName
     * @param array $query
     *
     * @return QueryResults
     * @throws \InvalidArgumentException
     */
    public function postsFor(string $postClassName, array $query = []): QueryResults
    {
        if (!($postClassName === Post::class || is_subclass_of($postClassName, Post::class))) {
            $baseClassName = Post::class;
            throw new \InvalidArgumentException(
                "The post class name must be a subclass of $baseClassName. `$postClassName` given."
            );
        }

        $query = array_merge($query, [
            'tax_query' => [
                [
                    'taxonomy' => $this->taxonomy(),
                    'field' => 'term_taxonomy_id',
                    'terms' => $this->termTaxonomyId(),
                ],
            ],
        ]);

        $method = isset($query['posts_per_page']) ? 'query' : 'all';

        return $postClassName::$method($query);
    }

    /**
     * Retrieves metadata for a term.
     * @param string $key
     * @param bool $single
     * @return mixed
     */
    public function meta(string $key = '', bool $single = true)
    {
        return get_term_meta($this->termId(), $key, $single);
    }

    /**
     * Dynamically retrieve property on the original WP_Term object.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        $value = parent::__get($key);

        if (null === $value) {
            $value = $this->wpTerm->$key ?? ($this->meta($key) ?? null);
        }

        return $value;
    }

    /**
     * Get all the terms.
     *
     * @return Collection
     */
    public static function all(): Collection
    {
        return static::query();
    }

    /**
     * Query the terms.
     *
     * @param array $query
     * @return Collection
     */
    public static function query(array $query = []): Collection
    {
        $terms = array_map(function ($term) {
            return new static($term);
        }, get_terms($query + ['taxonomy' => static::TAXONOMY]));

        return new Collection($terms);
    }
}
