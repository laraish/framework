<?php

namespace Laraish\WpSupport\Model;

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
     * @type integer
     */
    public $pid;

    /**
     * @type \WP_Post
     */
    public $wp_post;

    /**
     * Post constructor.
     *
     * @param int|\WP_Post|null $post
     */
    public function __construct($post = null)
    {
        $this->wp_post = \get_post($post);
        $this->pid     = $this->wp_post->ID;
    }

    /**
     * Get the post title.
     *
     * @return string
     */
    public function title()
    {
        return get_the_title($this->wp_post);
    }

    /**
     * Get the post permalink.
     *
     * @return false|string
     */
    public function permalink()
    {
        return get_permalink($this->wp_post);
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
        $result    = false;
        $thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id($this->wp_post), $size);
        if ($thumbnail) {
            $result = (object)[
                'url'    => $thumbnail[0],
                'width'  => $thumbnail[1],
                'height' => $thumbnail[2]
            ];
        } else if ($imgPlaceHolder) {
            $result = (object)[
                'url' => $imgPlaceHolder
            ];
        }

        return $result;
    }

    /**
     * Get the post excerpt.
     * This method must be called within `The Loop`.
     *
     * @return string
     */
    public function excerpt()
    {
        return get_the_excerpt($this->wp_post);
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

        return $content;
    }

    /**
     * The date and time of this post.
     * This is supposed to be used for the `datetime` attribute of `time` element.
     *
     * @param string $format
     *
     * @return false|int|string
     */
    public function dateTime($format = \DateTime::RFC3339)
    {
        return get_post_time($format, false, $this->wp_post);
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
        return get_post_time($format ?: get_option('date_format'), false, $this->wp_post, true);
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
        return get_post_time($format ?: get_option('time_format'), false, $this->wp_post, true);
    }

    /**
     * Get the author object
     *
     * @return Author
     */
    public function author()
    {
        return new Author($this->wp_post->post_author);
    }

    /**
     * Test if password required for this post.
     *
     * @return bool
     */
    public function isPasswordRequired()
    {
        return post_password_required($this->wp_post);
    }

    /**
     * Check if post has an image attached.
     *
     * @return bool
     */
    public function hasPostThumbnail()
    {
        return has_post_thumbnail($this->wp_post);
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
        $posts    = [];
        $postType = static::$postType;

        if ($postType AND ! isset($query['post_type'])) {
            $query['post_type'] = $postType;
        }

        $wp_query_object = new \WP_Query($query);

        $posts = array_map(function ($post) {
            return new static($post);
        }, $wp_query_object->posts);

        return count($posts) ? new QueryResults($posts, $wp_query_object) : $posts;
    }

    /**
     * Retrieve all posts in the current page.
     *
     * @return \Laraish\Contracts\WpSupport\Query\QueryResults | array
     */
    public static function all()
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
}