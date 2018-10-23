<?php

namespace Laraish\WpSupport\Model;

use WP_Term;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class Term extends BaseModel
{
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
        if ( ! \function_exists('get_fields')) {
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
        return $this->setAttribute(__METHOD__, $this->wpTerm);
    }

    /**
     * The name of this term.
     * @return string
     */
    public function name(): string
    {
        return $this->setAttribute(__METHOD__, $this->wpTerm->name);
    }

    /**
     * The slug of this term.
     * @return string
     */
    public function slug(): string
    {
        return $this->setAttribute(__METHOD__, $this->wpTerm->slug);
    }

    /**
     * The term_id of this term.
     * @return int
     */
    public function termId(): int
    {
        return $this->setAttribute(__METHOD__, $this->wpTerm->term_id);
    }

    /**
     * The term_taxonomy_id of this term.
     * @return int
     */
    public function termTaxonomyId(): int
    {
        return $this->setAttribute(__METHOD__, $this->wpTerm->term_taxonomy_id);
    }

    /**
     * Check if this term is currently being displayed.
     *
     * @return bool
     */
    public function isQueried(): bool
    {
        $queried_object = get_queried_object();

        $isQueried = (isset($queried_object->term_taxonomy_id) AND $queried_object->term_taxonomy_id === $this->termTaxonomyId());

        return $this->setAttribute(__METHOD__, $isQueried);
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
        if ( ! $parentId) {
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
        $taxonomy = new Taxonomy($this->wpTerm->taxonomy);
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
        while ( ! is_wp_error($term) AND ! empty($term->parent)) {
            $ancestors[] = $term = get_term($term->parent, $this->wpTerm->taxonomy);
        }

        // convert to Term object
        $ancestors = array_map(function ($term) {
            return new static($term);
        }, $ancestors);

        return $this->setAttribute(__METHOD__, new Collection($ancestors));
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
            return $givenTerm->term_id === $myAncestor->termId;
        });

        return $isDescendant !== false;
    }

    /**
     * Test if this term is a descendant of the given term.
     *
     * @param WP_Term|static $term
     *
     * @return bool
     */
    public function isAncestorOf($term): bool
    {
        $this->checkArgumentType($term, __METHOD__);

        $givenTerm = $term instanceof static ? $term : new static($term);
        $givenTermAncestors = $givenTerm->ancestors;
        $isAncestor = $givenTermAncestors->search(function (Term $givenPostAncestor) {
            return $this->termId === $givenPostAncestor->termId;
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
        if ( ! ($value instanceof static OR $value instanceof WP_Term)) {
            $className = static::class;
            throw new InvalidArgumentException("`$methodName` only accepts `WP_Term | $className`.");
        }
    }

    /**
     * Dynamically retrieve property on the original WP_Term object.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        $value = parent::__get($key);

        if (null === $value) {
            $value = $this->wpTerm->$key ?? null;
        }

        return $value;
    }
}