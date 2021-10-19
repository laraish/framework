<?php

namespace Laraish\Support\Wp\Model;

use WP_Post;
use WP_Query;
use DateTime;
use Illuminate\Support\Collection;
use Laraish\Support\Wp\Query\QueryResults;

class Post extends BaseModel
{
    /**
     * The default post type for querying.
     *
     * @var string|array
     */
    public const POST_TYPE = 'post';

    /**
     * The post id
     * @type integer
     */
    protected $id;

    /**
     * @type WP_Post
     */
    protected $wpPost;

    /**
     * Post constructor.
     *
     * @param int|WP_Post|null $post
     */
    public function __construct($post = null)
    {
        $this->wpPost = get_post($post);
        $this->id = $this->wpPost->ID;
    }

    /**
     * @return array|bool|mixed
     */
    public function resolveAcfFields()
    {
        if (!\function_exists('get_fields')) {
            return [];
        }

        return get_fields($this->id);
    }

    /**
     * Get the post ID.
     * @return int
     */
    public function id(): int
    {
        return $this->setAttribute(__METHOD__, $this->id);
    }

    /**
     * Get the original WP_Post object.
     * @return WP_Post
     */
    public function wpPost(): WP_Post
    {
        return $this->setAttribute(__METHOD__, $this->wpPost);
    }

    /**
     * Get the post title.
     *
     * @return string
     */
    public function title(): string
    {
        $title = get_the_title($this->wpPost);

        return $this->setAttribute(__METHOD__, $title);
    }

    /**
     * Get the post permalink.
     *
     * @return false|string
     */
    public function permalink()
    {
        $permalink = get_permalink($this->wpPost);

        return $this->setAttribute(__METHOD__, $permalink);
    }

    /**
     * Get the post thumbnail.
     *
     * @param string $size The thumbnail size.
     * @param string $imgPlaceHolder The URL of the placeholder image.
     *
     * @return object
     */
    public function thumbnail($size = 'full', $imgPlaceHolder = null)
    {
        $thumbnailObject = null;
        $thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id($this->wpPost), $size);
        if ($thumbnail) {
            $thumbnailObject = (object) [
                'url' => $thumbnail[0],
                'width' => $thumbnail[1],
                'height' => $thumbnail[2],
            ];
        } elseif ($imgPlaceHolder) {
            $thumbnailObject = (object) [
                'url' => $imgPlaceHolder,
            ];
        }

        return $this->setAttribute(__METHOD__, $thumbnailObject);
    }

    /**
     * Check if post has an image attached.
     *
     * @return bool
     */
    public function hasThumbnail(): bool
    {
        $hasPostThumbnail = has_post_thumbnail($this->wpPost);

        return $this->setAttribute(__METHOD__, $hasPostThumbnail);
    }

    /**
     * Get the post excerpt.
     * This method must be called within `The Loop`.
     *
     * @return string
     */
    public function excerpt(): string
    {
        $excerpt = get_the_excerpt($this->wpPost);

        return $this->setAttribute(__METHOD__, $excerpt);
    }

    /**
     * Get the post content.
     * This method must be called within `The Loop`.
     *
     * @return string
     */
    public function content($moreLinkText = null, $stripTeaser = false): string
    {
        $content = get_the_content($moreLinkText, $stripTeaser, $this->wpPost);
        $content = apply_filters('the_content', $content);
        $content = str_replace(']]>', ']]&gt;', $content);

        return $this->setAttribute(__METHOD__, $content);
    }

    /**
     * The date and time of this post.
     * This is supposed to be used for the `datetime` attribute of `time` element.
     *
     * @param string $format
     *
     * @return false|int|string
     */
    public function dateTime($format = DateTime::RFC3339)
    {
        $dateTime = get_post_time($format, false, $this->wpPost);

        return $this->setAttribute(__METHOD__, $dateTime);
    }

    /**
     * Get the formatted post date.
     *
     * @param string $format
     *
     * @return mixed
     */
    public function date($format = null)
    {
        $date = get_post_time($format ?? get_option('date_format'), false, $this->wpPost, true);

        return $this->setAttribute(__METHOD__, $date);
    }

    /**
     * Get the formatted post time.
     *
     * @param string $format
     *
     * @return mixed
     */
    public function time($format = null)
    {
        $time = get_post_time($format ?? get_option('time_format'), false, $this->wpPost, true);

        return $this->setAttribute(__METHOD__, $time);
    }

    /**
     * The modified date and time of this post.
     * This is supposed to be used for the `datetime` attribute of `time` element.
     *
     * @param string $format
     *
     * @return false|int|string
     */
    public function modifiedDateTime($format = DateTime::RFC3339)
    {
        $dateTime = get_post_modified_time($format, false, $this->wpPost);

        return $this->setAttribute(__METHOD__, $dateTime);
    }

    /**
     * Get the formatted modified post date.
     *
     * @param string $format
     *
     * @return mixed
     */
    public function modifiedDate($format = null)
    {
        $date = get_post_modified_time($format ?: get_option('date_format'), false, $this->wpPost, true);

        return $this->setAttribute(__METHOD__, $date);
    }

    /**
     * Get the formatted modified post time.
     *
     * @param string $format
     *
     * @return mixed
     */
    public function modifiedTime($format = null)
    {
        $time = get_post_modified_time($format ?: get_option('time_format'), false, $this->wpPost, true);

        return $this->setAttribute(__METHOD__, $time);
    }

    /**
     * Get the author object
     *
     * @return User
     */
    public function author(): User
    {
        $author = new User($this->wpPost->post_author);

        return $this->setAttribute(__METHOD__, $author);
    }

    /**
     * Get the parent of this post.
     * @return static|null
     */
    public function parent(): ?self
    {
        $parent = null;

        if ($this->wpPost->post_parent !== null) {
            $parent = new static($this->wpPost->post_parent);
        }

        return $this->setAttribute(__METHOD__, $parent);
    }

    /**
     * Get the children of this post.
     *
     * @param array $query
     *
     * @return Collection
     */
    public function children(array $query = []): Collection
    {
        $children = static::all(array_merge($query, ['post_parent' => $this->id()]));

        return $this->setAttribute(__METHOD__, $children);
    }

    /**
     * Get all the ancestors of this post.
     * @return Collection
     */
    public function ancestors(): Collection
    {
        $post = $this->wpPost;
        if (!$post->post_parent) {
            return new Collection(); // do not have any ancestors
        }

        $ancestor = get_post($post->post_parent);
        $ancestors = [];
        $ancestors[] = $ancestor;

        while ($ancestor->post_parent) {
            $ancestor = get_post($ancestor->post_parent);
            array_unshift($ancestors, $ancestor);
        }

        $ancestors = array_map(function ($post) {
            return new static($post);
        }, $ancestors);

        $ancestors = new Collection($ancestors);

        return $this->setAttribute(__METHOD__, $ancestors);
    }

    /**
     * Test if this post is a descendant of the given post.
     *
     * @param int|WP_Post|static $post
     *
     * @return bool
     */
    public function isDescendantOf($post): bool
    {
        $givenPost = $post instanceof static ? $post->wpPost() : get_post($post);
        $myAncestors = $this->ancestors;
        $isDescendant = $myAncestors->search(function (Post $myAncestor) use ($givenPost) {
            return $givenPost->ID === $myAncestor->id;
        });

        return $isDescendant !== false;
    }

    /**
     * Test if this post is a ancestor of the given post.
     *
     * @param $post
     *
     * @return bool
     */
    public function isAncestorOf($post): bool
    {
        $givenPost = $post instanceof static ? $post : new static($post);
        $givenPostAncestors = $givenPost->ancestors;
        $isAncestor = $givenPostAncestors->search(function (Post $givenPostAncestor) {
            return $this->id === $givenPostAncestor->id;
        });

        return $isAncestor !== false;
    }

    /**
     * Test if password required for this post.
     *
     * @return bool
     */
    public function isPasswordRequired(): bool
    {
        $isPasswordRequired = post_password_required($this->wpPost);

        return $this->setAttribute(__METHOD__, $isPasswordRequired);
    }

    /**
     * Updates the post meta field.
     *
     * @param string $meta_key
     * @param mixed $meta_value
     * @param mixed $prev_value
     * @return $this
     */
    public function updateMeta(string $meta_key, $meta_value, $prev_value = ''): self
    {
        update_post_meta($this->id, $meta_key, $meta_value, $prev_value);

        return $this;
    }

    /**
     * Retrieves a post meta field for the post.
     *
     * @param string $key
     * @param bool $single
     * @return mixed
     */
    public function getMeta(string $key = '', bool $single = false)
    {
        return get_post_meta($this->id, $key, $single);
    }

    /**
     * Sets the post thumbnail (featured image) for the post.
     *
     * @param int $thumbnailId
     * @return $this
     */
    public function setThumbnail(int $thumbnailId): self
    {
        set_post_thumbnail($this->id, $thumbnailId);

        return $this;
    }

    /**
     * Retrieves the edit post link for post.
     *
     * @param string $context How to output the '&' character. Default '&'.
     * @return string
     */
    public function editPostUrl($context = 'display'): string
    {
        $editPostUrl = get_edit_post_link($this->wpPost, $context);

        return $this->setAttribute(__METHOD__, $editPostUrl);
    }

    /**
     * Update the post with given attributes.
     *
     * @param array $attributes
     * @return true
     * @throws \WP_Error
     */
    public function update(array $attributes): bool
    {
        $attributes['ID'] = $this->id;
        $result = wp_update_post($attributes, true);

        if ($result instanceof \WP_Error) {
            throw $result;
        }

        return true;
    }

    /**
     * Delete the post.
     *
     * @param bool $forceDelete
     * @return bool
     */
    public function delete(bool $forceDelete = false): bool
    {
        $result = wp_delete_post($this->id, $forceDelete);

        return $result instanceof \WP_Post;
    }

    /**
     * Publish the post.
     * @return bool
     */
    public function publish(): bool
    {
        return $this->update(['post_status' => 'publish']);
    }

    /**
     * Get the post's status.
     * @return string
     */
    public function status(): string
    {
        return $this->wpPost->post_status;
    }

    /**
     * Query posts by using the `WP_Query`.
     *
     * @param array $query The argument passed to `WP_Query` constructor.
     *
     * @return QueryResults
     */
    public static function query(array $query): QueryResults
    {
        $defaultQuery = ['no_found_rows' => true];
        $query = array_merge($defaultQuery, $query);

        if (!isset($query['post_type'])) {
            $query['post_type'] = static::POST_TYPE;
        }

        $wp_query_object = new WP_Query($query);

        $posts = array_map(function ($post) {
            return new static($post);
        }, $wp_query_object->posts);

        return QueryResults::create($posts, $wp_query_object);
    }

    /**
     * Retrieve all posts in the current page.
     *
     * @return QueryResults
     */
    public static function queriedPosts(): QueryResults
    {
        global $wp_query;
        $posts = [];

        if ($wp_query) {
            $posts = array_map(function ($post) {
                return new static($post);
            }, (array) $wp_query->posts);
        }

        return QueryResults::create($posts, $wp_query);
    }

    /**
     * Get all posts.
     *
     * @param array $query
     *
     * @return QueryResults
     */
    public static function all(array $query = []): QueryResults
    {
        return static::query(array_merge($query, ['nopaging' => true]));
    }

    /**
     * Insert or update a post.
     *
     * @param array $attributes
     * @return static
     * @throws \WP_Error
     */
    public static function create(array $attributes): self
    {
        $result = wp_insert_post($attributes, true);

        if ($result instanceof \WP_Error) {
            throw $result;
        }

        return new static($result);
    }

    /**
     * Create many posts from array.
     *
     * @param array $posts
     *
     * @return Collection
     */
    public static function createManyFromArray(array $posts): Collection
    {
        return collect($posts)->map(function ($post) {
            return new static($post);
        });
    }

    /**
     * Dynamically retrieve property on the original WP_Post object.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        $value = parent::__get($key);

        if (null === $value) {
            $value = $this->wpPost->$key ?? null;
        }

        return $value;
    }
}
