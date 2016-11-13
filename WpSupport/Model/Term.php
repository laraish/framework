<?php

namespace Laraish\WpSupport\Model;

use Illuminate\Support\Collection;
use WP_Term;
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
     * Get the original WP_Term object.
     * @return WP_Term
     */
    public function wpTerm()
    {
        return $this->setAttribute(__METHOD__, $this->wpTerm);
    }

    /**
     * The name of this term.
     * @return string
     */
    public function name()
    {
        return $this->setAttribute(__METHOD__, $this->wpTerm->name);
    }

    /**
     * The slug of this term.
     * @return string
     */
    public function slug()
    {
        return $this->setAttribute(__METHOD__, $this->wpTerm->slug);
    }

    /**
     * The term_id of this term.
     * @return int
     */
    public function termId()
    {
        return $this->setAttribute(__METHOD__, $this->wpTerm->term_id);
    }

    /**
     * The term_taxonomy_id of this term.
     * @return int
     */
    public function termTaxonomyId()
    {
        return $this->setAttribute(__METHOD__, $this->wpTerm->term_taxonomy_id);
    }

    /**
     * Check if this term is currently being displayed.
     *
     * @return bool
     */
    public function isQueried()
    {
        $queried_object = get_queried_object();

        $isQueried = (isset($queried_object->term_taxonomy_id) AND $queried_object->term_taxonomy_id === $this->termTaxonomyId());

        return $this->setAttribute(__METHOD__, $isQueried);
    }

    /**
     * Get the url of the term.
     * @return string
     */
    public function url()
    {
        $url = get_term_link($this->wpTerm);

        return $this->setAttribute(__METHOD__, $url);
    }

    /**
     * Get the all the parents of a specific taxonomy term.
     *
     * @return Collection
     */
    public function ancestors()
    {
        $ancestors = [];
        $term      = $this->wpTerm;
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
     */
    public function isDescendantOf($term)
    {
        $this->checkArgumentType($term, __METHOD__);

        $givenTerm    = $term instanceof static ? $term->wpTerm() : $term;
        $myAncestors  = $this->ancestors;
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
    public function isAncestorOf($term)
    {
        $this->checkArgumentType($term, __METHOD__);

        $givenTerm          = $term instanceof static ? $term : new static($term);
        $givenTermAncestors = $givenTerm->ancestors;
        $isAncestor         = $givenTermAncestors->search(function (Term $givenPostAncestor) {
            return $this->termId === $givenPostAncestor->termId;
        });

        return $isAncestor !== false;
    }

    /**
     * Check if the given value is valid.
     *
     * @param mixed $value
     * @param string $methodName
     */
    protected function checkArgumentType($value, $methodName)
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

        if (is_null($value)) {
            $value = isset($this->wpTerm->$key) ? $this->wpTerm->$key : null;
        }

        return $value;
    }
}