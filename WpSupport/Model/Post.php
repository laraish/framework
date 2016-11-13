<?php

namespace Laraish\WpSupport\Model;

use WP_Post;
use WP_Query;
use DateTime;
use Illuminate\Support\Collection;
use Laraish\WpSupport\Query\QueryResults;

class Post extends BaseModel
{
    /**
     * The default post type for querying.
     *
     * @var string|array
     */
    protected static $postType;

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
        $this->id     = $this->wpPost->ID;
    }

    /**
     * Get the post ID.
     * @return WP_Post
     */
    public function id()
    {
        return $this->setAttribute(__METHOD__, $this->id);
    }

    /**
     * Get the original WP_Post object.
     * @return WP_Post
     */
    public function wpPost()
    {
        return $this->setAttribute(__METHOD__, $this->wpPost);
    }

    /**
     * Get the post title.
     *
     * @return string
     */
    public function title()
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
     * @param string $size           The thumbnail size.
     * @param string $imgPlaceHolder The URL of the placeholder image.
     *
     * @return object
     */
    public function thumbnail($size = 'full', $imgPlaceHolder = null)
    {
        $thumbnailObject = null;
        $thumbnail       = wp_get_attachment_image_src(get_post_thumbnail_id($this->wpPost), $size);
        if ($thumbnail) {
            $thumbnailObject = (object)[
                'url'    => $thumbnail[0],
                'width'  => $thumbnail[1],
                'height' => $thumbnail[2]
            ];
        } else if ($imgPlaceHolder) {
            $thumbnailObject = (object)[
                'url' => $imgPlaceHolder
            ];
        }

        return $this->setAttribute(__METHOD__, $thumbnailObject);
    }

    /**
     * Get the post excerpt.
     * This method must be called within `The Loop`.
     *
     * @return string
     */
    public function excerpt()
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
    public function content()
    {
        ob_start();
        the_content();
        $content = ob_get_clean();

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
    public function date($format = '')
    {
        $date = get_post_time($format ?: get_option('date_format'), false, $this->wpPost, true);

        return $this->setAttribute(__METHOD__, $date);
    }

    /**
     * Get the formatted post time.
     *
     * @param string $format
     *
     * @return mixed
     */
    public function time($format = '')
    {
        $time = get_post_time($format ?: get_option('time_format'), false, $this->wpPost, true);

        return $this->setAttribute(__METHOD__, $time);
    }

    /**
     * Get the author object
     *
     * @return Author
     */
    public function author()
    {
        $author = new Author($this->wpPost->post_author);

        return $this->setAttribute(__METHOD__, $author);
    }

    /**
     * Test if password required for this post.
     *
     * @return bool
     */
    public function isPasswordRequired()
    {
        $isPasswordRequired = post_password_required($this->wpPost);

        return $this->setAttribute(__METHOD__, $isPasswordRequired);
    }

    /**
     * Check if post has an image attached.
     *
     * @return bool
     */
    public function hasPostThumbnail()
    {
        $hasPostThumbnail = has_post_thumbnail($this->wpPost);

        return $this->setAttribute(__METHOD__, $hasPostThumbnail);
    }

    /**
     * Get the parent of this post.
     * @return static|null
     */
    public function parent()
    {
        $parent = null;

        if (isset($this->wpPost->post_parent)) {
            $parent = new static($this->wpPost->post_parent);
        }

        return $this->setAttribute(__METHOD__, $parent);
    }

    /**
     * Get all the ancestors of this post.
     * @return Collection
     */
    public function ancestors()
    {
        $post = $this->wpPost;
        if ( ! $post->post_parent) {
            return new Collection(); // do not have any ancestors
        }

        $ancestor    = get_post($post->post_parent);
        $ancestors   = [];
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
    public function isDescendantOf($post)
    {
        $givenPost    = $post instanceof static ? $post->wpPost() : get_post($post);
        $myAncestors  = $this->ancestors;
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
    public function isAncestorOf($post)
    {
        $givenPost          = $post instanceof static ? $post : new static($post);
        $givenPostAncestors = $givenPost->ancestors;
        $isAncestor         = $givenPostAncestors->search(function (Post $givenPostAncestor) {
            return $this->id === $givenPostAncestor->id;
        });

        return $isAncestor !== false;
    }

    /**
     * Query posts by using the `WP_Query`.
     *
     * @param array $query The argument passed to `WP_Query` constructor.
     *
     * @return \Laraish\Contracts\WpSupport\Query\QueryResults | array
     */
    public static function query(array $query)
    {
        $postType     = static::$postType;
        $defaultQuery = ['no_found_rows' => true];
        $query        = array_merge($defaultQuery, $query);

        if ($postType AND ! isset($query['post_type'])) {
            $query['post_type'] = $postType;
        }

        $wp_query_object = new WP_Query($query);

        $posts = array_map(function ($post) {
            return new static($post);
        }, $wp_query_object->posts);

        return count($posts) ? new QueryResults($posts, $wp_query_object) : [];
    }

    /**
     * Retrieve all posts in the current page.
     *
     * @return \Laraish\Contracts\WpSupport\Query\QueryResults | array
     */
    public static function queriedPosts()
    {
        global $wp_query;
        $posts = [];

        if ($wp_query) {
            $posts = array_map(function ($post) {
                return new static($post);
            }, (array)$wp_query->posts);
        }

        return count($posts) ? new QueryResults($posts, $wp_query) : $posts;
    }

    /**
     * Get all posts.
     * @return array|\Laraish\Contracts\WpSupport\Query\QueryResults
     */
    public static function all()
    {
        return static::query(['nopaging' => true]);
    }

    /**
     * Dynamically retrieve property on the original WP_Post object.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        $value = parent::__get($key);

        if (is_null($value)) {
            $value = isset($this->wpPost->$key) ? $this->wpPost->$key : null;
        }

        return $value;
    }
}