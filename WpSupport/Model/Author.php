<?php

namespace Laraish\WpSupport\Model;

class Author extends BaseModel
{
    /**
     * @type integer
     */
    public $id;

    /**
     * @type \WP_User
     */
    public $wp_user;

    /**
     * Article constructor.
     *
     * @param null|integer $id
     */
    public function __construct($id = null)
    {
        global $post;
        $this->id      = $id ?: $post->post_author;
        $this->wp_user = new \WP_User($this->id);
    }

    public function url()
    {
        return $this->wp_user->get('user_url');
    }

    public function posts_url()
    {
        return get_author_posts_url($this->id);
    }

    public function display_name()
    {
        return $this->wp_user->get('display_name');
    }

    public function nickname()
    {
        return $this->wp_user->get('nickname');
    }

    public function first_name()
    {
        return $this->wp_user->get('first_name');
    }

    public function last_name()
    {
        return $this->wp_user->get('last_name');
    }

    public function description()
    {
        return $this->wp_user->get('description');
    }

    public function email()
    {
        return $this->wp_user->get('user_email');
    }

    public function avatar_url($options = null)
    {
        return \get_avatar_url($this->id, $options);
    }

    /**
     * @param int $limit
     *
     * @return array
     */
    static function all($limit = -1)
    {
        $users = [];
        foreach (\get_users(['fields' => 'ID', 'number' => $limit]) as $user_id) {
            $users[] = new Author($user_id);
        }

        return $users;
    }
}