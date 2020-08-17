<?php

namespace Laraish\Support\Wp\Model;

use WP_User;
use Illuminate\Support\Collection;

class User extends BaseModel
{
    /**
     * @type integer
     */
    protected $id;

    /**
     * @type \WP_User
     */
    protected $wpUser;

    /**
     * Author constructor.
     *
     * @param null|integer $id
     */
    public function __construct($id = null)
    {
        global $post;
        $queriedObject = get_queried_object();

        if (null !== $id) {
            if ($id instanceof WP_User) {
                $wpUser = $id;
                $id = $wpUser->ID;
            } elseif (is_numeric($id)) {
                $id = (int) $id;
                $wpUser = new WP_User($id);
            } else {
                foreach (['slug', 'email', 'login'] as $field) {
                    $user = get_user_by($field, $id);
                    if ($user) {
                        $id = $user->ID;
                        $wpUser = $user;
                        break;
                    }
                }
            }
        } else {
            if ($queriedObject instanceof WP_User) {
                $id = $queriedObject->ID;
                $wpUser = $queriedObject;
            } else {
                $id = $post->post_author;
                $wpUser = new WP_User($id);
            }
        }

        $this->id = $id;
        $this->wpUser = $wpUser;
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

        return get_fields($this->wpUser);
    }

    public function id(): int
    {
        return $this->setAttribute(__METHOD__, $this->id);
    }

    public function wpUser(): WP_User
    {
        return $this->setAttribute(__METHOD__, $this->wpUser);
    }

    public function url(): string
    {
        $url = $this->wpUser->get('user_url');

        return $this->setAttribute(__METHOD__, $url);
    }

    public function postsUrl(): string
    {
        $postsUrl = get_author_posts_url($this->id);

        return $this->setAttribute(__METHOD__, $postsUrl);
    }

    public function displayName()
    {
        $displayName = $this->wpUser->get('display_name');

        return $this->setAttribute(__METHOD__, $displayName);
    }

    public function nickname()
    {
        $nickname = $this->wpUser->get('nickname');

        return $this->setAttribute(__METHOD__, $nickname);
    }

    public function firstName()
    {
        $firstName = $this->wpUser->get('first_name');

        return $this->setAttribute(__METHOD__, $firstName);
    }

    public function lastName()
    {
        $lastName = $this->wpUser->get('last_name');

        return $this->setAttribute(__METHOD__, $lastName);
    }

    public function description()
    {
        $description = $this->wpUser->get('description');

        return $this->setAttribute(__METHOD__, $description);
    }

    public function email()
    {
        $email = $this->wpUser->get('user_email');

        return $this->setAttribute(__METHOD__, $email);
    }

    public function avatarUrl($options = null)
    {
        $avatarUrl = get_avatar_url($this->id, $options);

        return $this->setAttribute(__METHOD__, $avatarUrl);
    }

    /**
     * Get all authors.
     *
     * @param array $query
     *
     * @return Collection
     */
    public static function all(array $query = []): Collection
    {
        $limit = -1;

        return static::query(array_merge($query, ['number' => $limit]));
    }

    /**
     * Find author by using the given query parameter.
     *
     * @param array $query
     *
     * @return Collection
     */
    public static function query(array $query): Collection
    {
        $users = [];
        $query['fields'] = 'ID';

        foreach (get_users($query) as $user_id) {
            $users[] = new static($user_id);
        }

        return new Collection($users);
    }
}
